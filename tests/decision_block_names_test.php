<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session'];
    $_POST = $_REQUEST = ['groups' => $request['groups']];
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/omo/api/decision/save_multi.php';
    require dirname(__DIR__) . '/omo/api/decision/save_multi.php';
    exit;
}
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/export_common.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/import_common.php';
use dbObject\{DecisionProcess, DecisionGroup, Application, OrganizationApplication};

function saveBlockNames(array $session, array $groups): void
{
    $request = base64_encode(json_encode(['session' => $session, 'groups' => array_map('http_build_query', $groups)], JSON_THROW_ON_ERROR));
    $process = proc_open([PHP_BINARY, __FILE__, '--request', $request],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Start save request');
    fclose($pipes[0]); $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0 && $errors === '', 'Save request failed: ' . $errors . $output);
    $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($result['status']), 'Save failed: ' . $output);
}

$items = mcpFixtures(); $decision = null; $copy = null;
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['role']->getId();
    $app = new Application(); mcpCheck($app->load(['hash', 'decision']), 'Decision app exists');
    $items['decision_app'] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $decision = mcpFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'title' => 'Questionnaire', 'evaluation_method' => 'simple_vote', 'status' => 'draft']);
    $groups = [$decision->ensurePrimaryGroup(), $decision->addDecisionGroup('majority_judgment'), $decision->addDecisionGroup('consent')];
    $names = ['Restaurant', 'Évaluation', 'Décision']; $payloads = [];
    foreach ($groups as $index => $group) {
        $payloads[] = ['oid' => $oid, 'cid' => $hid, 'id' => $decision->getId(), 'gid' => $group->getId(),
            'method' => $group->get('evaluation_method'), 'process_title' => 'Questionnaire',
            'title' => 'Quelle proposition préférez-vous dans la liste suivante ? ' . $index, 'short_title' => $names[$index],
            'proposals' => ['A', 'B'], 'proposal_content_title' => 1, 'status' => 'draft', 'is_anonymous' => 1];
    }
    $session = ['currentUser' => $uid, 'currentOrganization' => $oid, 'auth_security_version' => $items['user']->get('security_version')];
    saveBlockNames($session, $payloads);
    foreach ($groups as $index => $group) {
        $group->load((int)$group->getId(), true);
        mcpCheck($group->get('short_title') === $names[$index] && $group->get('title') === $payloads[$index]['title'], 'Question and short name saved independently for ' . $group->get('evaluation_method'));
    }
    $bundles = omoDecisionExportBuildBundleList($decision, null, true);
    $json = omoDecisionExportBuildProcessJsonData($decision, $bundles);
    $normalized = omoDecisionImportNormalizeStructuredData(json_decode(json_encode($json, JSON_THROW_ON_ERROR), true), 'Copy');
    mcpCheck(array_column($normalized['blocks'], 'short_title') === $names, 'Structured export/import preserves short names');
    $xml = omoDecisionImportNormalizeStructuredData(omoDecisionImportDecodeXmlFile(omoDecisionExportEncodeXml($json)), 'Copy');
    mcpCheck(array_column($xml['blocks'], 'short_title') === $names, 'XML preserves short names');
    $csvRows = [];
    foreach ($bundles as $bundle) foreach ($bundle['proposals'] as $proposal) {
        $csvRows[] = array_combine(omoDecisionExportGetCsvHeader(), omoDecisionExportFlattenCsvRow(omoDecisionExportBuildCsvMetadataRow($bundle, $proposal), '', ''));
    }
    $csv = omoDecisionImportNormalizeCsvData(['rows' => $csvRows], 'Copy');
    mcpCheck(array_column($csv['blocks'], 'short_title') === $names, 'CSV preserves short names');
    $copy = mcpFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'title' => 'Copy', 'evaluation_method' => 'simple_vote', 'status' => 'draft']);
    foreach ($normalized['blocks'] as $index => $block) {
        $method = $block['method']; omoDecisionEnsureMethodImportLoaded($method);
        $definition = omoDecisionGetModuleDefinition($method);
        $group = $index === 0 ? $copy->ensurePrimaryGroup() : $copy->addDecisionGroup($method);
        $result = ($definition['import_function'])($copy, $group, $normalized['process'], $block, $index === 0);
        mcpCheck(!empty($result['status']), 'Import succeeds: ' . json_encode($result));
        $group->load((int)$group->getId(), true);
        mcpCheck($group->get('short_title') === $names[$index] && $group->get('title') === $payloads[$index]['title'], 'Imported short name remains independent of question');
    }
    // Older clients omit the new field: they must preserve existing names.
    $legacy = $payloads;
    foreach ($legacy as &$payload) unset($payload['short_title']);
    unset($payload);
    saveBlockNames($session, $legacy);
    $decision->load((int)$decision->getId(), true);
    $decision->set('status', 'evaluation');
    $decision->set('evaluation_start_at', '2020-01-01 00:00:00');
    $decision->save();
    foreach ($payloads as &$payload) {
        $payload['status'] = 'evaluation'; $payload['evaluation_start_at'] = '2020-01-01 00:00:00';
        $payload['short_title'] = 'Forbidden'; $payload['title'] = 'Forbidden question';
    }
    unset($payload);
    saveBlockNames($session, $payloads);
    foreach ($groups as $index => $group) {
        $group->load((int)$group->getId(), true);
        mcpCheck($group->get('short_title') === $names[$index] && str_starts_with($group->get('title'), 'Quelle proposition'), 'Evaluation lock protects names and question text');
    }
    echo "decision_block_names_test: OK\n";
} finally {
    if ($copy) $copy->deleteWithRelations();
    if ($decision) $decision->deleteWithRelations();
    mcpCleanup($items);
}
