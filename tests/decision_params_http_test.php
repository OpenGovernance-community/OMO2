<?php
declare(strict_types=1);

// Run as www-data in local Docker. Fixtures are removed afterwards.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/omo/api/decision/params/shared.php';

use dbObject\{Application, DecisionProcess, OrganizationApplication};

function decisionParamsHttp(string $endpoint, ?array $post = null): string
{
    if ($post !== null) $post['_csrf'] = commonCsrfToken();
    $cookie = session_name() . '=' . session_id();
    session_write_close();
    $curl = curl_init('https://localhost/omo/api/decision/params/' . $endpoint);
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
    mcpCheck(is_string($body) && $status === 200, 'Settings request failed: ' . $status . ' ' . (string)$body);
    return $body;
}

function decisionParamsCheckForm(array $methods): void
{
    $document = new DOMDocument();
    @$document->loadHTML(decisionParamsHttp('index.php'));
    $xpath = new DOMXPath($document);
    foreach ($methods as $method => $enabled) {
        $inputs = $xpath->query('//input[@name="methods[' . $method . ']"]');
        mcpCheck($inputs->length === 1, 'The settings form must contain ' . $method);
        mcpCheck($inputs->item(0)->hasAttribute('checked') === $enabled, 'Reopened checkbox must retain its value: ' . $method);
    }
}

$before = $_SESSION;
$items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId();
    $uid = (int)$items['user']->getId();
    mcpCheck(!empty($items['membership']->setOrganizationAdmin(true)['status']), 'Enable fixture organization admin.');
    $application = new Application();
    mcpCheck($application->load(['hash', 'decision']), 'Decision app exists.');
    $items['decision_app'] = mcpFixture(OrganizationApplication::class, [
        'IDorganization' => $oid,
        'IDapplication' => $application->getId(),
        'active' => 1,
        'parameters' => [
            'unrelated_setting' => 'preserved',
            'decisionSettings' => ['methods' => [DecisionProcess::METHOD_SIMPLE_VOTE => true]],
        ],
    ]);
    $_SESSION = [
        'currentUser' => $uid,
        'currentOrganization' => $oid,
        'auth_security_version' => (int)$items['user']->get('security_version'),
        'isAdminByOrganization' => [$oid => true],
    ];
    $legacyMethods = array_fill_keys(array_keys(omoDecisionParamsGetDefaultConfig()['methods']), false);
    $legacyMethods[DecisionProcess::METHOD_SIMPLE_VOTE] = true;
    $legacyMethods[DecisionProcess::METHOD_CONSULTATION_ONLY] = true;
    decisionParamsCheckForm($legacyMethods);

    foreach ([
        [DecisionProcess::METHOD_SIMPLE_VOTE],
        [],
        array_keys($legacyMethods),
        [DecisionProcess::METHOD_CONSULTATION_ONLY],
        [DecisionProcess::METHOD_MAJORITY_JUDGMENT, DecisionProcess::METHOD_CONSENT],
    ] as $enabledMethods) {
        // Browsers omit unchecked checkboxes, including the entire methods array when all are unchecked.
        $post = ['oid' => $oid];
        if ($enabledMethods) $post['methods'] = array_fill_keys($enabledMethods, '1');
        $response = json_decode(decisionParamsHttp('save.php', $post), true, 512, JSON_THROW_ON_ERROR);
        mcpCheck(!empty($response['status']), 'Save settings successfully.');
        $items['decision_app']->load((int)$items['decision_app']->getId(), true);
        $parameters = $items['decision_app']->getParametersArray();
        mcpCheck($parameters['unrelated_setting'] === 'preserved', 'Keep unrelated application settings.');
        $expectedMethods = array_fill_keys(array_keys($legacyMethods), false);
        foreach ($enabledMethods as $method) $expectedMethods[$method] = true;
        mcpCheck($parameters['decisionSettings']['methods'] === $expectedMethods, 'Persist every checked and unchecked method.');
        decisionParamsCheckForm($expectedMethods);
    }
    echo "Decision parameter HTTP tests passed.\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $before;
}
