<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayUserHolon;
use dbObject\ArrayUserOrganization;
use dbObject\Holon;
use dbObject\UserOrganization;

header('Content-Type: application/json; charset=UTF-8');

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$holonId = isset($_GET['hid']) && is_numeric($_GET['hid']) ? (int)$_GET['hid'] : 0;
$context = omoProjectsResolveContext($organizationId, $holonId);

if (empty($context['status']) || !omoProjectsCanManageContext($context)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => omoProjectsT('projects.error.forbidden')]);
    exit;
}

$holon = $context['currentHolon'] ?? null;
if (!($holon instanceof Holon)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => omoProjectsT('projects.error.context')]);
    exit;
}

$members = [];
$seenUserIds = [];
$appendMember = static function ($userId) use (&$members, &$seenUserIds, $organizationId): void {
    $userId = (int)$userId;
    if ($userId <= 0 || isset($seenUserIds[$userId])) {
        return;
    }

    $membership = new UserOrganization();
    if (!$membership->load([
        ['IDuser', $userId],
        ['IDorganization', $organizationId],
    ]) || !(bool)$membership->get('active')) {
        return;
    }

    $seenUserIds[$userId] = true;
    $members[] = [
        'id' => $userId,
        'label' => \dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($userId, $organizationId),
    ];
};

$rootHolon = $context['rootHolon'] ?? null;
$isOrganizationHolon = $rootHolon instanceof Holon && (int)$holon->getId() === (int)$rootHolon->getId();
if ($isOrganizationHolon) {
    $organizationMembers = new ArrayUserOrganization();
    $organizationMembers->loadActiveForOrganization($organizationId);
    foreach ($organizationMembers as $membership) {
        $appendMember((int)$membership->get('IDuser'));
    }
} else {
    $holonMembers = new ArrayUserHolon();
    $holonMembers->loadActiveForHolonIds([(int)$holon->getId()]);
    foreach ($holonMembers as $member) {
        $appendMember((int)$member->get('IDuser'));
    }
}

echo json_encode([
    'success' => true,
    'holon' => [
        'id' => (int)$holon->getId(),
        'label' => trim((string)$holon->getDisplayName()),
    ],
    'members' => $members,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
