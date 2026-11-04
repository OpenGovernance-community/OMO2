<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/tension_translations.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$userId = (int)commonGetCurrentUserId();
$organizationId = (int)($_GET['oid'] ?? 0);
if ($userId <= 0 || $organizationId <= 0 || !commonCurrentUserHasOrganizationAccess($organizationId)) {
    http_response_code($userId <= 0 ? 401 : 403);
    echo json_encode(['status' => false, 'message' => omoInterfaceUiT('organisation_invalide')]);
    exit;
}

$tensions = new \dbObject\ArrayTension();
$tensions->loadForOrganization($organizationId);
$items = [];
foreach ($tensions as $tension) {
    $holon = new \dbObject\Holon();
    $author = new \dbObject\User();
    $createdAt = $tension->get('datecreation');
    $items[] = [
        'id' => (int)$tension->getId(),
        'title' => (string)$tension->get('title'),
        'description' => (string)$tension->get('description'),
        'date' => $createdAt instanceof \DateTimeInterface ? $createdAt->format('Y-m-d') : '',
        'holon' => (int)$tension->get('IDholon') > 0 && $holon->load((int)$tension->get('IDholon')) ? (string)$holon->getDisplayName() : '',
        'author' => $author->load((int)$tension->get('IDuser')) ? (string)$author->getScopedDisplayName($organizationId) : '',
        'canDelete' => $tension->canDeleteInOrganizationContext($organizationId, $userId),
    ];
}
echo json_encode(['status' => true, 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
