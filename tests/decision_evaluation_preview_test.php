<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me'; $_SERVER['HTTPS'] = 'on';
require_once dirname(__DIR__) . '/shared_functions.php';
if (($argv[1] ?? '') === '--readonly-probe') {
    set_error_handler(static fn() => true, E_WARNING);
    \dbObject\DecisionProcess::beginReadOnlyEvaluationPreview();
    $user = new \dbObject\User(); $user->load((int)$argv[2]); $user->set('firstname', 'Forbidden preview write');
    $first = $user->save();
    \dbObject\DbObject::getPdo()->commit();
    $user->set('firstname', 'Forbidden after commit'); $second = $user->save();
    require_once dirname(__DIR__) . '/common/translation_bundles.php';
    $queued = translationBundleQueueRefresh('preview-read-only-probe', 'de', ['x' => ['text' => 'Preview', 'context' => 'Read only test']]);
    echo json_encode(['firstBlocked' => empty($first['status']), 'secondBlocked' => empty($second['status']), 'translationQueued' => $queued], JSON_THROW_ON_ERROR);
    exit;
}
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/context.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/vote/shared.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/consent/shared.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/majority_judgment/shared.php';
use dbObject\{Application, OrganizationApplication, User, UserOrganization, UserHolon, DecisionProcess, DecisionGroup, DecisionProposal, DecisionParticipant, DecisionResponse};

function evaluationPreviewHttp(string $url, ?array $post = null): array
{
    if ($post !== null) $post['_csrf'] = commonCsrfToken();
    $cookie = session_name() . '=' . session_id(); session_write_close();
    $curl = curl_init('https://localhost' . $url); $headers = [];
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie],
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$headers): int {
            if (str_contains($line, ':')) { [$key, $value] = explode(':', $line, 2); $headers[strtolower(trim($key))] = trim($value); }
            return strlen($line);
        }]);
    if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]);
    $html = curl_exec($curl); $code = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE); unset($curl); session_start();
    mcpCheck(is_string($html), 'Preview HTTP response');
    return ['code' => $code, 'html' => $html, 'headers' => $headers];
}
function evaluationPreviewSnapshot(DecisionProcess $decision): string
{
    $decision->load((int)$decision->getId(), true);
    $fields = [];
    foreach (['title', 'status', 'evaluation_start_at', 'evaluation_end_at', 'consultation_start_at', 'consultation_end_at', 'results_published_at', 'archived_at', 'updated_at', 'parameters'] as $key) $fields[$key] = $decision->get($key);
    $fields['participants'] = count($decision->getParticipants(false));
    $fields['responses'] = count($decision->getResponses());
    $fields['groups'] = [];
    foreach ($decision->getDecisionGroups(false) as $group) $fields['groups'][] = [$group->getId(), $group->get('updated_at'), count($group->getProposals(false))];
    return serialize($fields);
}
function evaluationPreviewDom(string $html): DOMXPath
{
    $document = new DOMDocument(); $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<meta charset="utf-8">' . $html); libxml_clear_errors(); libxml_use_internal_errors($previous);
    return new DOMXPath($document);
}

$items = mcpFixtures(); $decision = null; $before = $_SESSION;
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['role']->getId();
    $app = new Application(); mcpCheck($app->load(['hash', 'decision']), 'Decision app exists');
    $items['decision_app'] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    foreach (['editor', 'reader'] as $key) {
        $items[$key] = mcpFixture(User::class, ['email' => $key . '-' . bin2hex(random_bytes(5)) . '@example.invalid', 'active' => 1]);
        $items[$key . '_org'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $items[$key]->getId(), 'active' => 1]);
    }
    $items['editor_role'] = mcpFixture(UserHolon::class, ['IDholon' => $hid, 'IDuser' => $items['editor']->getId(), 'active' => 1, 'is_membership' => 1]);
    $parameters = omoDecisionVoteMergeConfigIntoParameters([], ['one_proposal_at_a_time' => true, 'choice_mode' => 'multiple', 'max_choices' => 2]);
    $decision = mcpFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid, 'title' => 'Evaluation preview fixture',
        'status' => 'draft', 'evaluation_method' => 'simple_vote', 'parameters' => $parameters]);
    $decision->saveVisibilityRulesFromInput(['edit_visibility_type' => 'role']);
    $groups = [$decision->getPrimaryGroup(false)];
    foreach (['majority_judgment', 'consent'] as $method) {
        $group = $decision->addDecisionGroup($method, 'decision', 'Preview ' . $method);
        $merge = $method === 'consent' ? 'omoDecisionConsentMergeConfigIntoParameters' : 'omoDecisionMajorityJudgmentMergeConfigIntoParameters';
        $group->set('parameters', $merge([], ['one_proposal_at_a_time' => true])); $group->save(); $groups[] = $group;
    }
    foreach ($groups as $group) foreach (['First proposal', 'Second proposal'] as $position => $title) {
        mcpFixture(DecisionProposal::class, ['IDdecision_process' => $decision->getId(), 'IDdecision_group' => $group->getId(),
            'title' => $title, 'position' => $position + 1, 'IDuser_author' => $uid, 'active' => 1]);
    }
    $participant = DecisionParticipant::findByDecisionAndUser((int)$decision->getId(), $uid)
        ?: mcpFixture(DecisionParticipant::class, ['IDdecision_process' => $decision->getId(), 'IDuser' => $uid, 'role' => 'owner', 'active' => 1, 'status' => 'active']);
    mcpFixture(DecisionResponse::class, ['IDdecision_process' => $decision->getId(), 'IDdecision_group' => $groups[0]->getId(),
        'IDdecision_participant' => $participant->getId(), 'status' => 'submitted', 'parameters' => ['simple_vote' => ['selected_proposal_ids' => [$groups[0]->getProposals(true)[0]->getId()]]]]);
    $url = omoDecisionBuildEvaluationPreviewUrl($oid, (int)$decision->getId());
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid, 'auth_security_version' => $items['user']->get('security_version')];
    foreach (['draft', 'scheduled', 'consultation', 'evaluation', 'results', 'archived'] as $status) {
        $decision->set('status', $status); $decision->save(); $snapshot = evaluationPreviewSnapshot($decision);
        $response = evaluationPreviewHttp($url);
        mcpCheck($response['code'] === 200 && str_contains($response['html'], 'data-omo-decision-evaluation-preview'), "$status: preview page renders: " . substr($response['html'], 0, 300));
        mcpCheck(str_contains($response['headers']['content-security-policy'] ?? '', "connect-src 'none'")
            && str_contains($response['headers']['content-security-policy'] ?? '', "form-action 'none'"), 'Preview disallows AJAX and form writes');
        $xpath = evaluationPreviewDom($response['html']);
        mcpCheck($xpath->query('//input[@name="evaluation_preview" and @value="1"]')->length === 3, "$status: all methods render interactive preview forms");
        mcpCheck($xpath->query('//form[contains(@action,"respond.php")] | //*[@data-omo-decision-one-at-a-time-draft-url]')->length === 0, 'No save endpoint or draft autosave URL');
        mcpCheck($xpath->query('//input[@checked and (@name="proposal_id" or @name="proposal_ids[]" or starts-with(@name,"scores[") or starts-with(@name,"choices["))]')->length === 0, 'Real participant responses are never loaded into the preview');
        mcpCheck(evaluationPreviewSnapshot($decision) === $snapshot, "$status: preview never changes lifecycle, participants, responses or groups");
        if ($status === 'draft') file_put_contents(dirname(__DIR__) . '/tmp/decision-evaluation-preview.html', $response['html']);
    }
    mcpCheck(evaluationPreviewHttp($url, ['evaluation_preview' => '1'])['code'] === 405, 'Native preview POST is rejected');
    $snapshot = evaluationPreviewSnapshot($decision);
    $translated = evaluationPreviewHttp($url . '&lang=de');
    mcpCheck($translated['code'] === 200 && evaluationPreviewSnapshot($decision) === $snapshot,
        'Preview in another language reads existing translations without changing decision data');
    $editor = evaluationPreviewHttp('/omo/api/decision/edit.php?' . http_build_query(['oid' => $oid, 'id' => $decision->getId(), 'intent' => 'manage']));
    $links = evaluationPreviewDom($editor['html'])->query('//a[@data-omo-decision-evaluation-preview-link and @target="_blank"]');
    mcpCheck($editor['code'] === 200 && $links->length === 1 && $links[0]->getAttribute('href') === $url
        && str_contains($links[0]->getAttribute('rel'), 'noopener'), 'Management opens the saved questionnaire in a separate browsing context');
    foreach (['vote', 'majority_judgment', 'consent'] as $module) {
        $result = evaluationPreviewHttp('/omo/api/decision/modules/' . $module . '/respond.php', ['oid' => $oid, 'id' => $decision->getId(), 'evaluation_preview' => '1', 'intent' => 'participate']);
        mcpCheck($result['code'] === 405, 'Preview cannot be submitted to real response endpoint: ' . $module);
    }
    foreach (['editor' => 200, 'reader' => 403] as $key => $expected) {
        $_SESSION = ['currentUser' => $items[$key]->getId(), 'currentOrganization' => $oid, 'auth_security_version' => $items[$key]->get('security_version')];
        mcpCheck(evaluationPreviewHttp($url)['code'] === $expected, 'Preview permission: ' . $key);
    }
    $_SESSION = []; mcpCheck(evaluationPreviewHttp($url)['code'] === 403, 'Public visitors cannot access management preview');
    $process = proc_open([PHP_BINARY, __FILE__, '--readonly-probe', (string)$uid], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Start read-only probe'); fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]); $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0 && $errors === '', 'Read-only probe runs: ' . $errors . $output);
    $probe = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    mcpCheck($probe['firstBlocked'] && $probe['secondBlocked'] && !$probe['translationQueued'], 'SQL and translation writes are blocked even after a commit');
    $items['user']->load($uid, true); mcpCheck($items['user']->get('firstname') === 'MCP test', 'Read-only probe never changes user data');
    echo "decision_evaluation_preview_test: OK\n";
} finally {
    $_SESSION = $before;
    if ($decision) $decision->deleteWithRelations();
    mcpCleanup($items);
}
