<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

$respond = static function (bool $status, string $message = '', array $extra = [], int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode(
        array_merge(['status' => $status, 'message' => $message], $extra),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
};

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    $respond(false, 'Cette action doit etre envoyee en POST.', [], 405);
}

$payload = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $respond(false, 'Contenu invalide.', [], 400);
}

$currentUserId = (int)commonGetCurrentUserId();
$documentId = (int)($payload['documentId'] ?? 0);
$organizationId = (int)($payload['organizationId'] ?? 0);
$action = trim((string)($payload['action'] ?? ''));
$csrfToken = trim((string)($payload['csrfToken'] ?? ''));
$expectedCsrfToken = trim((string)($_SESSION['omo_pv_application_tabs_csrf'] ?? ''));

if ($currentUserId <= 0) {
    $respond(false, 'Connexion requise.', [], 401);
}
if ($csrfToken === '' || $expectedCsrfToken === '' || !hash_equals($expectedCsrfToken, $csrfToken)) {
    $respond(false, 'Jeton de securite invalide.', [], 403);
}
if (!in_array($action, ['add', 'remove'], true)) {
    $respond(false, 'Action invalide.', [], 400);
}

$document = new \dbObject\Document();
if (
    $documentId <= 0
    || $organizationId <= 0
    || !$document->load($documentId)
    || (int)$document->get('IDorganization') !== $organizationId
    || !$document->canUserManagePvDocument($currentUserId)
) {
    $respond(false, 'Vous ne pouvez pas modifier les applications de ce PV.', [], 403);
}

if ($action === 'add') {
    $applicationId = (int)($payload['applicationId'] ?? 0);
    $result = \dbObject\DocumentApplicationTab::createForDocument($document, $applicationId);
    $tab = $result['item'] ?? null;
    if (empty($result['status']) || !($tab instanceof \dbObject\DocumentApplicationTab)) {
        $respond(false, (string)($result['text'] ?? 'Impossible d ajouter cette application.'), [], 422);
    }

    $respond(true, '', [
        'tabId' => (int)$tab->getId(),
        'applicationId' => (int)$tab->get('IDapplication'),
        'created' => !empty($result['created']),
    ]);
}

$tabId = (int)($payload['tabId'] ?? 0);
$tab = new \dbObject\DocumentApplicationTab();
if ($tabId <= 0 || !$tab->load($tabId) || !$tab->belongsToDocument($document)) {
    $respond(false, 'Onglet introuvable.', [], 404);
}
if (!$tab->delete()) {
    $respond(false, 'Impossible de retirer cet onglet.', [], 422);
}

$respond(true, '', ['tabId' => $tabId]);

