<?php

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

header('Content-Type: application/json; charset=UTF-8');

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = new \dbObject\Organization();

if ($organizationId <= 0 || !$organization->load($organizationId)) {
    http_response_code(404);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => omoLexiconT('parameters.lexicon.error.organization'),
        ),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    exit;
}

if (!$organization->canEdit()) {
    http_response_code(403);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => omoLexiconT('parameters.lexicon.error.access'),
        ),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    exit;
}

$organization->setLexicon(array(
    'tension' => array(
        'label' => (string)($_POST['tension_label'] ?? ''),
        'article' => (string)($_POST['tension_article'] ?? ''),
    ),
    'admin' => array(
        'label' => (string)($_POST['admin_label'] ?? ''),
    ),
));

$saveResult = $organization->save();
if (empty($saveResult['status'])) {
    http_response_code(422);
    echo json_encode(
        array(
            'status' => 'error',
            'message' => omoLexiconT('parameters.lexicon.status.error'),
        ),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    exit;
}

echo json_encode(
    array(
        'status' => 'ok',
        'lexicon' => $organization->getLexicon(),
        'message' => omoLexiconT('parameters.lexicon.status.saved'),
    ),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);
