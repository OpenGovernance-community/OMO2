<?php

function commonResolvePvMeetingPermissionContext(int $organizationId): ?array
{
    static $resolvedByOrganization = [];

    if (array_key_exists($organizationId, $resolvedByOrganization)) {
        return $resolvedByOrganization[$organizationId];
    }

    $request = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    $rawDocumentId = $request['pv_meeting_document_id'] ?? $request['document_id'] ?? 0;
    $documentId = is_numeric($rawDocumentId)
        ? (int)$rawDocumentId
        : 0;
    $editorToken = trim((string)($request['pv_meeting_editor_token'] ?? $request['editor_token'] ?? ''));
    $currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
    if ($organizationId <= 0 || $documentId <= 0 || $editorToken === '' || $currentUserId <= 0) {
        return $resolvedByOrganization[$organizationId] = null;
    }

    $sessionKey = $organizationId . ':' . $documentId . ':user:' . $currentUserId;
    $storedToken = trim((string)($_SESSION['omo_pv_editor_tokens'][$sessionKey] ?? ''));
    if ($storedToken === '' || !hash_equals($storedToken, $editorToken)) {
        return $resolvedByOrganization[$organizationId] = null;
    }

    $document = new \dbObject\Document();
    if (
        !$document->load($documentId)
        || (int)$document->get('IDorganization') !== $organizationId
        || $document->getPvStage() !== \dbObject\Document::PV_STAGE_MEETING
        || !$document->isPvEditor($currentUserId)
        || !$document->canUserOpenPvEditor($currentUserId, $organizationId)
    ) {
        return $resolvedByOrganization[$organizationId] = null;
    }

    return $resolvedByOrganization[$organizationId] = [
        'document' => $document,
        'documentId' => $documentId,
        'userId' => $currentUserId,
    ];
}

function commonResolveHolonOrganizationId(\dbObject\Holon $holon): int
{
    $organizationId = (int)$holon->get('IDorganization');
    if ($organizationId > 0) {
        return $organizationId;
    }

    $rootHolon = new \dbObject\Holon();
    return $rootHolon->load((int)$holon->get('IDholon_org'))
        ? (int)$rootHolon->get('IDorganization')
        : 0;
}

function commonPvMeetingCanUseCollectivePermission(?array $meetingContext, \dbObject\Holon $holon, string $permissionKey): bool
{
    if (!is_array($meetingContext) || !($meetingContext['document'] ?? null) instanceof \dbObject\Document) {
        return false;
    }

    $document = $meetingContext['document'];
    $organizationId = (int)$document->get('IDorganization');
    $holonOrganizationId = commonResolveHolonOrganizationId($holon);
    return $organizationId > 0
        && $holonOrganizationId === $organizationId
        && \dbObject\HolonPermission::userHasCollectivePermissionForHolonContext(
            (int)($meetingContext['userId'] ?? 0),
            $organizationId,
            $permissionKey,
            (int)$holon->getId()
        );
}
