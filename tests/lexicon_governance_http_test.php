<?php
declare(strict_types=1);

// Run as www-data in local Docker. Fixtures are removed afterwards.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{Application, Organization, OrganizationApplication};

function lexiconGovernanceHttp(string $path, ?array $post = null): string
{
    if ($post !== null) $post['_csrf'] = commonCsrfToken();
    $cookie = session_name() . '=' . session_id();
    session_write_close();
    $curl = curl_init('https://localhost' . $path);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie],
    ]);
    if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    session_start();
    mcpCheck(is_string($body) && $status === 200, 'Lexicon request failed: ' . $status . ' ' . (string)$body);
    return $body;
}

$before = $_SESSION;
$items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId();
    $uid = (int)$items['user']->getId();
    mcpCheck(!empty($items['membership']->setOrganizationAdmin(true)['status']), 'Enable organization admin.');
    $items['org']->set('interface_level', Organization::INTERFACE_LEVEL_AUTONOMOUS);
    mcpCheck(!empty($items['org']->save()['status']), 'Enable governance settings.');
    $application = new Application();
    mcpCheck($application->load(['hash', 'decision']), 'Decision app exists.');
    $items['decision_app'] = mcpFixture(OrganizationApplication::class, [
        'IDorganization' => $oid, 'IDapplication' => $application->getId(), 'active' => 1,
    ]);
    $_SESSION = [
        'currentUser' => $uid,
        'currentOrganization' => $oid,
        'auth_security_version' => (int)$items['user']->get('security_version'),
        'isAdminByOrganization' => [$oid => true],
    ];
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . lexiconGovernanceHttp('/omo/api/parameters/lexicon/index.php'));
    $xpath = new DOMXPath($document);
    $post = [];
    foreach ($xpath->query('//form[@data-omo-lexicon-form]//input[@name]') as $input) {
        if ($input->getAttribute('type') !== 'checkbox' || $input->hasAttribute('checked')) {
            $post[$input->getAttribute('name')] = $input->getAttribute('value');
        }
    }
    mcpCheck(($post['governance_label'] ?? '') === 'Hors réorg', 'Show the default governance term in the lexicon.');

    foreach (['Évolution continue', 'Gouvernance <collective>', Organization::getInitialLexicon()['governance']['label']] as $label) {
        $post['governance_label'] = $label;
        $response = json_decode(lexiconGovernanceHttp('/omo/api/parameters/lexicon/save.php', $post), true, 512, JSON_THROW_ON_ERROR);
        mcpCheck($response['status'] === 'ok' && $response['lexicon']['governance']['label'] === $label, 'Save the custom governance term.');
        $items['org']->load($oid, true);
        mcpCheck($items['org']->getLexicon()['governance']['label'] === $label, 'Persist the custom term in the organization.');
        mcpCheck($items['org']->getLexicon()['space']['label'] === $post['space_label'], 'Keep other lexicon terms.');
        $html = lexiconGovernanceHttp('/omo/api/parameters/lexicon/index.php');
        mcpCheck(str_contains($html, 'value="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"'), 'Reopen the lexicon with the escaped custom term.');
        $lowerLabel = mb_strtolower(mb_substr($label, 0, 1)) . mb_substr($label, 1);
        $settings = lexiconGovernanceHttp('/omo/api/decision/params/index.php');
        mcpCheck(str_contains($settings, htmlspecialchars('Décisions ' . $lowerLabel, ENT_QUOTES, 'UTF-8')), 'Use the custom term in decision settings.');
        $javascript = json_decode(lexiconGovernanceHttp('/common/jstranslation/omo_ui.php'), true, 512, JSON_THROW_ON_ERROR);
        mcpCheck($javascript['copy_modifier_la_decision_hors_reorg'] === 'Modifier la décision ' . $lowerLabel, 'Use the custom term in the shared JavaScript labels.');
    }
    echo "Lexicon governance HTTP tests passed.\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $before;
}
