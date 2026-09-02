<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Event;
use dbObject\Holon;
use dbObject\Organization;

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function omoCalendarDeleteResponse(array $payload, $statusCode = 200)
{
    http_response_code((int)$statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    omoCalendarDeleteResponse([
        'status' => false,
        'message' => 'Méthode non autorisée.',
    ], 405);
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_POST['oid'] ?? 0));
$eventId = isset($_POST['id']) && is_numeric($_POST['id'])
    ? (int)$_POST['id']
    : (isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0);
$deleteDocuments = !empty($_POST['delete_documents']);
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;

if ($organizationId <= 0 || $eventId <= 0 || $currentUserId <= 0) {
    omoCalendarDeleteResponse([
        'status' => false,
        'message' => 'Accès refusé.',
    ], 403);
}

$organization = new Organization();
if (!$organization->load($organizationId) || !$organization->canViewDetail()) {
    omoCalendarDeleteResponse([
        'status' => false,
        'message' => 'Organisation invalide.',
    ], 403);
}

$event = new Event();
if (
    !$event->load($eventId)
    || (int)$event->get('IDorganization') !== $organizationId
    || (int)$event->get('active') !== 1
) {
    omoCalendarDeleteResponse([
        'status' => false,
        'message' => 'Événement invalide.',
    ], 403);
}

$rootHolon = $organization->getEnabledStructuralRootHolon($currentUserId);
$permissionHolon = $rootHolon;
$eventHolonId = (int)$event->get('IDholon');
if ($eventHolonId > 0) {
    $eventHolon = new Holon();
    if (
        !$eventHolon->load($eventHolonId)
        || !($rootHolon instanceof Holon)
        || !$eventHolon->isDescendantOf((int)$rootHolon->getId(), true)
    ) {
        omoCalendarDeleteResponse([
            'status' => false,
            'message' => 'Événement invalide.',
        ], 403);
    }

    $permissionHolon = $eventHolon;
}

$canDelete = $permissionHolon instanceof Holon
    ? $permissionHolon->isAllowed('CAN_DELETE_EVENT', false, $currentUserId)
    : commonCurrentUserHasOrganizationAccess($organizationId);
if (!$canDelete) {
    omoCalendarDeleteResponse([
        'status' => false,
        'message' => "Vous n'avez pas le droit de supprimer cet événement.",
    ], 403);
}

$associatedDocuments = $event->getAssociatedDocuments();
if ($deleteDocuments) {
    foreach ($associatedDocuments as $associatedDocument) {
        if (
            !($associatedDocument instanceof \dbObject\Document)
            || !$associatedDocument->canManageLifecycle($organizationId, $currentUserId)
            || !$associatedDocument->canDeleteDocument(true)
        ) {
            omoCalendarDeleteResponse([
                'status' => false,
                'message' => 'Vous ne pouvez pas supprimer un des documents associés.',
            ], 403);
        }
    }
}

$pdo = \dbObject\DbObject::getPdo();
$startedTransaction = $pdo instanceof \PDO && !$pdo->inTransaction();
$deleted = false;
try {
    if ($startedTransaction) {
        $pdo->beginTransaction();
    }

    if ($deleteDocuments) {
        foreach ($associatedDocuments as $associatedDocument) {
            if (!$associatedDocument->delete()) {
                throw new \RuntimeException('document_delete_failed');
            }
        }
    }

    $deleted = $event->delete();
    if (!$deleted) {
        throw new \RuntimeException('event_delete_failed');
    }

    if ($startedTransaction && $pdo->inTransaction()) {
        $pdo->commit();
    }
    \dbObject\CalDavCache::invalidateOrganization($organizationId);
} catch (\Throwable $exception) {
    if ($startedTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    omoCalendarDeleteResponse([
        'status' => false,
        'message' => 'Impossible de supprimer cet événement.',
    ], 422);
}

omoCalendarDeleteResponse([
    'status' => true,
    'message' => 'Événement supprimé.',
]);
