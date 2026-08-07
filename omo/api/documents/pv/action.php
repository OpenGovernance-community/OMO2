<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/helpers.php';
require_once dirname(__DIR__, 2) . '/stats/shared.php';

header('Content-Type: application/json; charset=UTF-8');

$sourceLang = omoDocumentsPvEditorSourceLang();
$lang = omoLoadTranslationBundle('omo_documents_pv_editor', $sourceLang);

function omoDocumentsPvEditorActionT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

function omoDocumentsPvEditorJsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function omoDocumentsPvEditorLoadDocumentOrFail(int $documentId, int $organizationId, int $userId): \dbObject\Document
{
    $document = new \dbObject\Document();
    if (
        $documentId <= 0
        || $organizationId <= 0
        || $userId <= 0
        || !$document->load($documentId)
        || !$document->canUserOpenPvEditor($userId, $organizationId)
    ) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    return $document;
}

function omoDocumentsPvEditorOrganizationHasApplication(int $organizationId, int $userId, string $applicationHash): bool
{
    static $cache = [];

    $cacheKey = $organizationId . ':' . $userId . ':' . $applicationHash;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $organization = new \dbObject\Organization();
    $cache[$cacheKey] = $organizationId > 0
        && $organization->load($organizationId)
        && $organization->isApplicationEnabled($applicationHash, $userId);
    return $cache[$cacheKey];
}

function omoDocumentsPvEditorHasValidSessionToken(int $organizationId, int $documentId, int $userId, string $token): bool
{
    if ($organizationId <= 0 || $documentId <= 0 || $userId <= 0 || $token === '') {
        return false;
    }

    $sessionKey = $organizationId . ':' . $documentId . ':' . $userId;
    $storedToken = trim((string)($_SESSION['omo_pv_editor_tokens'][$sessionKey] ?? ''));
    return $storedToken !== '' && hash_equals($storedToken, $token);
}

function omoDocumentsPvEditorBuildPointResponsePayload(\dbObject\DocumentPvPoint $point, int $organizationId, int $currentUserId): array
{
    $uiText = omoDocumentsPvEditorBuildUiText('omoDocumentsPvEditorActionT');
    $lockToken = trim((string)($_POST['editor_token'] ?? ''));
    $document = new \dbObject\Document();
    $hasStructureApplication = omoDocumentsPvEditorOrganizationHasApplication($organizationId, $currentUserId, 'structure');
    if (!$document->load((int)$point->get('IDdocument'))) {
        return [];
    }
    $allPoints = new \dbObject\ArrayDocumentPvPoint();
    $allPoints->loadForDocument((int)$document->getId(), true);
    $groupSummaryMap = omoDocumentsPvEditorBuildGroupSummaryMap($allPoints);
    $authorOptions = $document->getPvPointAuthorOptions($organizationId);
    $authorHolonOptions = omoDocumentsPvEditorBuildAuthorHolonOptions(
        $document,
        $authorOptions,
        $hasStructureApplication
    );
    $positionLabels = \dbObject\DocumentPvPoint::buildHierarchyPositionLabelsForDocument((int)$point->get('IDdocument'));

    return omoDocumentsPvEditorBuildContextualPointPayload(
        $point,
        $document,
        $organizationId,
        $currentUserId,
        $lockToken,
        $uiText,
        $hasStructureApplication,
        $authorOptions,
        $authorHolonOptions,
        (string)($positionLabels[(int)$point->getId()] ?? '--'),
        $groupSummaryMap[(int)$point->getId()] ?? []
    );
}

function omoDocumentsPvEditorBuildPointsPayloadForDocument(int $documentId, int $organizationId, int $currentUserId, string $lockToken = ''): array
{
    $uiText = omoDocumentsPvEditorBuildUiText('omoDocumentsPvEditorActionT');
    $points = new \dbObject\ArrayDocumentPvPoint();
    $points->loadForDocument($documentId, true);
    $document = new \dbObject\Document();
    $hasDocument = $document->load($documentId);
    $hasStructureApplication = omoDocumentsPvEditorOrganizationHasApplication($organizationId, $currentUserId, 'structure');
    $authorOptions = $hasDocument ? $document->getPvPointAuthorOptions($organizationId) : [];
    $authorHolonOptions = $hasDocument
        ? omoDocumentsPvEditorBuildAuthorHolonOptions($document, $authorOptions, $hasStructureApplication)
        : [];
    $positionLabels = \dbObject\DocumentPvPoint::buildHierarchyPositionLabels($points);
    $groupSummaryMap = omoDocumentsPvEditorBuildGroupSummaryMap($points);

    $payload = [];
    foreach ($points as $point) {
        if (!($point instanceof \dbObject\DocumentPvPoint) || (int)$point->getId() <= 0) {
            continue;
        }

        if (!$hasDocument) {
            continue;
        }
        $payload[] = omoDocumentsPvEditorBuildContextualPointPayload(
            $point,
            $document,
            $organizationId,
            $currentUserId,
            $lockToken,
            $uiText,
            $hasStructureApplication,
            $authorOptions,
            $authorHolonOptions,
            (string)($positionLabels[(int)$point->getId()] ?? '--'),
            $groupSummaryMap[(int)$point->getId()] ?? []
        );
    }

    return $payload;
}

function omoDocumentsPvEditorBuildDocumentPayload(\dbObject\Document $document, int $organizationId, int $currentUserId): array
{
    $pvEditorUserId = $document->getPvEditorUserId();
    $visibility = $document->getVisibilityDisplayData($organizationId);
    $modifiedAt = $document->get('datemodification');
    $modifiedAtValue = $modifiedAt instanceof \DateTimeInterface ? $modifiedAt->format('Y-m-d H:i:s.u') : '';
    $syncVersion = implode('|', [
        $modifiedAtValue,
        (string)$document->getPvStage(),
        (string)$pvEditorUserId,
        $document->isPvEditorHandoverOpen() ? '1' : '0',
        trim((string)$document->get('title')),
        trim((string)$document->get('description')),
        (string)($visibility['type'] ?? \dbObject\ObjectVisibility::TYPE_ORGANIZATION),
        $document->isPvTemplate() ? '1' : '0',
    ]);
    return [
        'id' => (int)$document->getId(),
        'syncVersion' => hash('sha256', $syncVersion),
        'title' => trim((string)$document->get('title')),
        'description' => trim((string)$document->get('description')),
        'visibilityType' => (string)($visibility['type'] ?? \dbObject\ObjectVisibility::TYPE_ORGANIZATION),
        'pvStage' => $document->getPvStage(),
        'pvStageLabel' => $document->getPvStageLabel(),
        'canManagePvStage' => $document->canManagePvStage($organizationId, $currentUserId),
        'pvEditorUserId' => $pvEditorUserId,
        'pvEditorLabel' => $pvEditorUserId > 0
            ? \dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($pvEditorUserId, $organizationId)
            : '',
        'pvCreatorUserId' => $document->getCreatedByUserId(),
        'pvCreatorLabel' => $document->getCreatedByUserId() > 0
            ? \dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($document->getCreatedByUserId(), $organizationId)
            : '',
        'isPvEditor' => $document->isPvEditor($currentUserId),
        'canManagePvDocument' => $document->canUserManagePvDocument($currentUserId),
        'canClaimPvEditor' => $document->canUserClaimPvEditor($organizationId, $currentUserId),
        'canReplacePvEditor' => $document->canUserReplacePvEditor($organizationId, $currentUserId),
        'pvEditorHandoverOpen' => $document->isPvEditorHandoverOpen(),
        'isPvValidated' => $document->isPvValidated(),
        'isPvTemplate' => $document->isPvTemplate(),
        'canManagePvTemplate' => $document->canUserManagePvDocument($currentUserId),
    ];
}

function omoDocumentsPvEditorBuildAttendancePayload(\dbObject\Document $document, int $organizationId): ?array
{
    return omoDocumentsPvEditorBuildAttendancePayloadFromDocument($document, $organizationId);
}

function omoDocumentsPvEditorParseLocalDateTime($rawValue): ?\DateTimeImmutable
{
    $rawValue = trim((string)$rawValue);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $rawValue)) {
        return null;
    }

    try {
        $timezone = new \DateTimeZone(date_default_timezone_get());
    } catch (\Throwable $exception) {
        $timezone = new \DateTimeZone('UTC');
    }

    $dateTime = \DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $rawValue, $timezone);
    return $dateTime instanceof \DateTimeImmutable && $dateTime->format('Y-m-d\\TH:i') === $rawValue
        ? $dateTime
        : null;
}

function omoDocumentsPvEditorBuildEventEmbedPayload(\dbObject\Event $event): array
{
    $startAt = $event->get('start_at');
    $endAt = $event->get('end_at');
    $formatDateTime = static function ($value): string {
        return $value instanceof \DateTimeInterface ? $value->format('d.m.Y H:i') : '';
    };
    $scheduleLabel = trim(
        $formatDateTime($startAt)
        . ($endAt instanceof \DateTimeInterface ? ' - ' . $formatDateTime($endAt) : '')
    );
    $locationData = $event->getLocationDisplayData();

    return [
        'id' => (int)$event->getId(),
        'contextHolonId' => (int)$event->get('IDholon'),
        'title' => trim((string)$event->get('title')),
        'scheduleLabel' => $scheduleLabel,
        'locationLabel' => trim(implode(' | ', array_filter([
            trim((string)($locationData['address'] ?? '')),
            trim((string)($locationData['videoUrl'] ?? '')),
        ]))),
        'startAt' => $startAt instanceof \DateTimeInterface ? $startAt->format(DATE_ATOM) : '',
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    omoDocumentsPvEditorJsonResponse([
        'status' => false,
        'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.invalid_request'),
    ], 405);
}

$action = trim((string)($_POST['action'] ?? ''));
$documentId = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
$organizationId = isset($_POST['oid']) ? (int)$_POST['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$currentUserId = (int)commonGetCurrentUserId();
$editorToken = trim((string)($_POST['editor_token'] ?? ''));

$document = omoDocumentsPvEditorLoadDocumentOrFail($documentId, $organizationId, $currentUserId);
$hasTeamApplication = omoDocumentsPvEditorOrganizationHasApplication($organizationId, $currentUserId, 'team');
$hasStructureApplication = omoDocumentsPvEditorOrganizationHasApplication($organizationId, $currentUserId, 'structure');
$hasCalendarApplication = omoDocumentsPvEditorOrganizationHasApplication($organizationId, $currentUserId, 'calendar');

if ($action === 'set_pv_template') {
    $templateResult = $document->updatePvTemplateState(
        $organizationId,
        $currentUserId,
        !empty($_POST['is_template'])
    );
    if (!is_array($templateResult) || ($templateResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($templateResult['text'] ?? 'Impossible de modifier le modele de PV.')),
        ], 403);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'document' => omoDocumentsPvEditorBuildDocumentPayload($document, $organizationId, $currentUserId),
    ]);
}

if ($action === 'claim_pv_editor') {
    $claimResult = $document->claimPvEditor($organizationId, $currentUserId);
    if (!is_array($claimResult) || ($claimResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($claimResult['text'] ?? 'Impossible de devenir editeur du PV.')),
            'document' => omoDocumentsPvEditorBuildDocumentPayload($document, $organizationId, $currentUserId),
        ], 403);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'document' => omoDocumentsPvEditorBuildDocumentPayload($document, $organizationId, $currentUserId),
        'points' => omoDocumentsPvEditorBuildPointsPayloadForDocument((int)$document->getId(), $organizationId, $currentUserId, $editorToken),
    ]);
}

if ($action === 'pass_pv_editor') {
    $handoverResult = $document->openPvEditorHandover($currentUserId);
    if (!is_array($handoverResult) || ($handoverResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($handoverResult['text'] ?? 'Impossible de passer la main pour ce PV.')),
            'document' => omoDocumentsPvEditorBuildDocumentPayload($document, $organizationId, $currentUserId),
        ], 403);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'document' => omoDocumentsPvEditorBuildDocumentPayload($document, $organizationId, $currentUserId),
    ]);
}

if ($action === 'replace_pv_editor') {
    $replaceResult = $document->replacePvEditor($organizationId, $currentUserId);
    if (!is_array($replaceResult) || ($replaceResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($replaceResult['text'] ?? 'Impossible de remplacer l editeur du PV.')),
            'document' => omoDocumentsPvEditorBuildDocumentPayload($document, $organizationId, $currentUserId),
        ], 403);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'document' => omoDocumentsPvEditorBuildDocumentPayload($document, $organizationId, $currentUserId),
        'points' => omoDocumentsPvEditorBuildPointsPayloadForDocument((int)$document->getId(), $organizationId, $currentUserId, $editorToken),
    ]);
}

if ($action === 'update_document_metadata') {
    if (!$document->canUserManagePvDocument($currentUserId)) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $visibilityType = trim((string)($_POST['visibility_type'] ?? ''));
    $visibilityOptions = \dbObject\ObjectVisibility::getVisibilityTypeOptions();
    if (!isset($visibilityOptions[$visibilityType])) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $result = $document->updateInOrganizationContext($organizationId, $currentUserId, [
        'title' => trim((string)($_POST['title'] ?? '')),
        'description' => trim((string)($_POST['description'] ?? '')),
        'keywords' => trim((string)$document->get('keywords')),
        'content' => '',
        'document_type' => $document->getDocumentType(),
        'visibility_type' => $visibilityType,
    ]);
    if (!is_array($result) || ($result['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($result['text'] ?? 'Impossible de sauver le PV.')),
        ], 400);
    }

    $document->load((int)$document->getId());
    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'document' => omoDocumentsPvEditorBuildDocumentPayload($document, $organizationId, $currentUserId),
    ]);
}

if ($action === 'complete_archive_checklist_project') {
    if (
        !$document->canUserManagePvDocument($currentUserId)
        || !omoDocumentsPvEditorHasValidSessionToken($organizationId, $documentId, $currentUserId, $editorToken)
    ) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $project = new \dbObject\Project();
    if (
        $projectId <= 0
        || !$project->load($projectId)
        || (int)$project->get('IDorganization') !== $organizationId
        || (int)$project->get('active') !== 1
        || \dbObject\Project::normalizeKind($project->get('project_kind')) !== \dbObject\Project::KIND_STANDARD
        || (int)$project->get('IDproject_template') <= 0
    ) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $archiveResult = $project->completeAndArchiveActiveTree();
    if (!is_array($archiveResult) || empty($archiveResult['status'])) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.checklist.complete_archive_error'),
        ], 422);
    }

    $activatedCount = 0;
    $runIds = [];
    foreach ((array)($archiveResult['projectIds'] ?? [$projectId]) as $affectedProjectId) {
        $runItems = new \dbObject\ArrayChecklistRunItem();
        $runItems->loadForProject((int)$affectedProjectId);
        foreach ($runItems as $runItem) {
            $run = $runItem instanceof \dbObject\ChecklistRunItem ? $runItem->getRun() : null;
            if ($run instanceof \dbObject\ChecklistRun) {
                $runIds[(int)$run->getId()] = $run;
            }
        }
    }
    foreach ($runIds as $run) {
        $sync = $run->synchronizeItemActivations();
        $activatedCount += (int)($sync['activatedCount'] ?? 0);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'projectId' => $projectId,
        'affectedCount' => (int)($archiveResult['affectedCount'] ?? 0),
        'activatedCount' => $activatedCount,
    ]);
}

if ($action === 'add_indicator_value') {
    if (!omoDocumentsPvEditorOrganizationHasApplication($organizationId, $currentUserId, 'stats')) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    if (!omoDocumentsPvEditorHasValidSessionToken($organizationId, $documentId, $currentUserId, $editorToken)) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $indicatorId = isset($_POST['indicator_id']) ? (int)$_POST['indicator_id'] : 0;
    $indicator = omoStatsLoadIndicator($indicatorId, $organizationId);
    if (!($indicator instanceof \dbObject\StatIndicator)) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $pointId = isset($_POST['point_id']) ? (int)$_POST['point_id'] : 0;
    if ($pointId > 0) {
        $point = new \dbObject\DocumentPvPoint();
        if (!$point->load($pointId) || (int)$point->get('IDdocument') !== (int)$document->getId()) {
            omoDocumentsPvEditorJsonResponse([
                'status' => false,
                'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
            ], 403);
        }
    }

    $isPvEditor = $document->isPvEditor($currentUserId);
    if (!$indicator->canEdit() && !$isPvEditor) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $rawValue = str_replace([' ', ','], ['', '.'], trim((string)($_POST['value'] ?? '')));
    if ($rawValue === '' || !is_numeric($rawValue)) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.indicator.value_error'),
        ], 422);
    }

    $value = new \dbObject\StatIndicatorValue();
    $value->set('IDstatindicator', (int)$indicator->getId());
    $value->set('IDuser', $currentUserId > 0 ? $currentUserId : null);
    $value->set('value', (float)$rawValue);
    $value->set('measured_at', new \DateTime('now'));
    $saveResult = $value->save();
    if (!is_array($saveResult) || empty($saveResult['status'])) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.indicator.value_error'),
        ], 500);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'indicator' => omoDocumentsPvEditorBuildIndicatorEmbedPayload(
            $indicator,
            $isPvEditor,
            'omoDocumentsPvEditorActionT'
        ),
    ]);
}

if ($action === 'add_point') {
    if ($document->isPvValidated()) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }
    $point = new \dbObject\DocumentPvPoint();
    $point->set('IDdocument', (int)$document->getId());
    $point->set('item_type', \dbObject\DocumentPvPoint::ITEM_TYPE_POINT);
    $point->set('IDuser_author', $currentUserId);
    $point->set('IDuser_modification', $currentUserId);
    $point->set('title', omoDocumentsPvEditorActionT('documents.pv_editor.point.default_title'));
    $point->set('pointtype', \dbObject\DocumentPvPoint::TYPE_INFORMATION);
    $point->set('content', '');
    $point->set('active', 1);

    $saveResult = $point->save();
    if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($saveResult['text'] ?? 'Impossible de creer le point.')),
        ], 400);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'point' => omoDocumentsPvEditorBuildPointResponsePayload($point, $organizationId, $currentUserId),
    ]);
}

if ($action === 'add_group') {
    if (!$document->canUserCreatePvGroups($currentUserId)) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $group = new \dbObject\DocumentPvPoint();
    $group->set('IDdocument', (int)$document->getId());
    $group->set('item_type', \dbObject\DocumentPvPoint::ITEM_TYPE_GROUP);
    $group->set('IDuser_modification', $currentUserId);
    $group->set('title', omoDocumentsPvEditorActionT('documents.pv_editor.group.default_title'));
    $group->set('pointtype', \dbObject\DocumentPvPoint::TYPE_INFORMATION);
    $group->set('active', 1);
    $saveResult = $group->save();
    if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($saveResult['text'] ?? 'Impossible de creer le groupe.')),
        ], 400);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'point' => omoDocumentsPvEditorBuildPointResponsePayload($group, $organizationId, $currentUserId),
    ]);
}

if ($action === 'update_group') {
    $groupId = isset($_POST['point_id']) ? (int)$_POST['point_id'] : 0;
    $group = new \dbObject\DocumentPvPoint();
    if (
        !$document->canUserCreatePvGroups($currentUserId)
        || $groupId <= 0
        || !$group->load($groupId)
        || !$group->isGroup()
        || (int)$group->get('IDdocument') !== (int)$document->getId()
    ) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $group->set('title', trim((string)($_POST['title'] ?? '')));
    $group->set('IDuser_modification', $currentUserId);
    $saveResult = $group->save();
    if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($saveResult['text'] ?? 'Impossible de sauver le groupe.')),
        ], 400);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'point' => omoDocumentsPvEditorBuildPointResponsePayload($group, $organizationId, $currentUserId),
    ]);
}

if ($action === 'lock_point') {
    $pointId = isset($_POST['point_id']) ? (int)$_POST['point_id'] : 0;
    $point = new \dbObject\DocumentPvPoint();
    if (
        $pointId <= 0
        || !$point->load($pointId)
        || (int)$point->get('IDdocument') !== (int)$document->getId()
        || !$document->canUserEditPvPoint($point, $currentUserId)
    ) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $lockResult = $point->touchEditLock($organizationId, $currentUserId, $editorToken);
    if (!is_array($lockResult) || ($lockResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($lockResult['text'] ?? 'Point verrouille.')),
            'lock' => is_array($lockResult['lock'] ?? null) ? $lockResult['lock'] : null,
            'point' => omoDocumentsPvEditorBuildPointResponsePayload($point, $organizationId, $currentUserId),
        ], 423);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'point' => omoDocumentsPvEditorBuildPointResponsePayload($point, $organizationId, $currentUserId),
    ]);
}

if ($action === 'unlock_point') {
    $pointId = isset($_POST['point_id']) ? (int)$_POST['point_id'] : 0;
    $point = new \dbObject\DocumentPvPoint();
    if (
        $pointId <= 0
        || !$point->load($pointId)
        || (int)$point->get('IDdocument') !== (int)$document->getId()
    ) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $point->releaseEditLock($currentUserId, $editorToken);
    $point->load($pointId);

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'point' => omoDocumentsPvEditorBuildPointResponsePayload($point, $organizationId, $currentUserId),
    ]);
}

if ($action === 'take_over_point_lock') {
    $pointId = isset($_POST['point_id']) ? (int)$_POST['point_id'] : 0;
    $point = new \dbObject\DocumentPvPoint();
    if (
        $pointId <= 0
        || !$point->load($pointId)
        || (int)$point->get('IDdocument') !== (int)$document->getId()
        || !$document->isPvEditor($currentUserId)
        || !$document->canUserManagePvDocument($currentUserId)
        || !$document->canUserEditPvPoint($point, $currentUserId)
        || !omoDocumentsPvEditorHasValidSessionToken($organizationId, $documentId, $currentUserId, $editorToken)
    ) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $lockResult = $point->takeOverEditLockAsPvEditor($organizationId, $currentUserId, $editorToken);
    if (!is_array($lockResult) || ($lockResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($lockResult['text'] ?? 'Impossible de reprendre le verrou.')),
            'point' => omoDocumentsPvEditorBuildPointResponsePayload($point, $organizationId, $currentUserId),
        ], 400);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'point' => omoDocumentsPvEditorBuildPointResponsePayload($point, $organizationId, $currentUserId),
    ]);
}

if ($action === 'save_point') {
    $pointId = isset($_POST['point_id']) ? (int)$_POST['point_id'] : 0;
    $point = new \dbObject\DocumentPvPoint();
    $canSaveLockedDraft = false;
    if (
        $pointId > 0
        && $point->load($pointId)
        && (int)$point->get('IDdocument') === (int)$document->getId()
    ) {
        // Allow only an already locked browser session to finish its draft after a handover.
        $canSaveLockedDraft = $point->isEditLockOwnedByUserSession($currentUserId, $editorToken);
    }
    if (
        $pointId <= 0
        || (int)$point->get('IDdocument') !== (int)$document->getId()
        || (!$document->canUserEditPvPoint($point, $currentUserId) && !$canSaveLockedDraft)
    ) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $lockResult = $point->touchEditLock($organizationId, $currentUserId, $editorToken);
    if (!is_array($lockResult) || ($lockResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($lockResult['text'] ?? 'Point verrouille.')),
            'lock' => is_array($lockResult['lock'] ?? null) ? $lockResult['lock'] : null,
            'point' => omoDocumentsPvEditorBuildPointResponsePayload($point, $organizationId, $currentUserId),
        ], 423);
    }

    $currentAuthorValue = (int)$point->get('IDuser_author') > 0
        ? 'user:' . (int)$point->get('IDuser_author')
        : (trim((string)$point->get('author_email')) !== '' ? 'email:' . trim((string)$point->get('author_email')) : '');
    $requestedAuthorValue = trim((string)($_POST['author'] ?? $currentAuthorValue));
    if ($requestedAuthorValue === '') {
        $requestedAuthorValue = $currentAuthorValue;
    }
    $authorOptionsByValue = [];
    foreach ($document->getPvPointAuthorOptions($organizationId) as $authorOption) {
        $optionValue = trim((string)($authorOption['value'] ?? ''));
        if ($optionValue !== '') {
            $authorOptionsByValue[$optionValue] = $authorOption;
        }
    }
    if (!isset($authorOptionsByValue[$requestedAuthorValue])) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }
    if ($requestedAuthorValue !== $currentAuthorValue) {
        if (!$document->canUserManagePvDocument($currentUserId)) {
            omoDocumentsPvEditorJsonResponse([
                'status' => false,
                'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
            ], 403);
        }

    }

    $selectedAuthorOption = $authorOptionsByValue[$requestedAuthorValue];
    $requestedAuthorUserId = (int)($selectedAuthorOption['userId'] ?? 0);
    $requestedAuthorEmail = trim((string)($selectedAuthorOption['email'] ?? ''));

    $requestedConcernedHolonId = isset($_POST['concerned_holon_id']) ? (int)$_POST['concerned_holon_id'] : 0;
    if (!$hasStructureApplication) {
        $requestedConcernedHolonId = 0;
    }
    $currentConcernedHolonId = (int)$point->get('IDholon_concerned');
    if (
        $requestedConcernedHolonId > 0
        && $requestedConcernedHolonId !== $currentConcernedHolonId
        && ($requestedAuthorUserId <= 0 || !\dbObject\DocumentPvPoint::concernedHolonIsAllowedForDocument($document, $requestedAuthorUserId, $requestedConcernedHolonId))
    ) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $point->set('title', trim((string)($_POST['title'] ?? '')));
    $point->set('pointtype', trim((string)($_POST['pointtype'] ?? '')));
    $point->set('desired_duration_minutes', trim((string)($_POST['desired_duration_minutes'] ?? '')));
    $point->set('IDuser_author', $requestedAuthorUserId > 0 ? $requestedAuthorUserId : null);
    $point->set('author_email', $requestedAuthorEmail !== '' ? $requestedAuthorEmail : null);
    $point->set('IDholon_concerned', $requestedConcernedHolonId > 0 ? $requestedConcernedHolonId : null);
    $point->set('content', (string)($_POST['content'] ?? ''));
    $point->set('IDuser_modification', $currentUserId);

    $saveResult = $point->save();
    if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($saveResult['text'] ?? 'Impossible de sauver le point.')),
        ], 400);
    }

    $point->releaseEditLock($currentUserId, $editorToken);
    $point->load($pointId);

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'point' => omoDocumentsPvEditorBuildPointResponsePayload($point, $organizationId, $currentUserId),
        'message' => omoDocumentsPvEditorActionT('documents.pv_editor.state.saved'),
    ]);
}

if ($action === 'delete_point') {
    $pointId = isset($_POST['point_id']) ? (int)$_POST['point_id'] : 0;
    $point = new \dbObject\DocumentPvPoint();
    if (
        $pointId <= 0
        || !$point->load($pointId)
        || (int)$point->get('IDdocument') !== (int)$document->getId()
        || (! $point->isGroup()
            && !$document->canUserEditPvPoint($point, $currentUserId))
        || ($point->isGroup()
            && !$document->canUserCreatePvGroups($currentUserId))
    ) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    if (!$point->delete()) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => 'Impossible de supprimer cet element.',
        ], 400);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'deletedPointId' => $pointId,
        'points' => omoDocumentsPvEditorBuildPointsPayloadForDocument((int)$document->getId(), $organizationId, $currentUserId, $editorToken),
    ]);
}

if ($action === 'toggle_handled') {
    $pointId = isset($_POST['point_id']) ? (int)$_POST['point_id'] : 0;
    $point = new \dbObject\DocumentPvPoint();
    if (
        $pointId <= 0
        || !$point->load($pointId)
        || (int)$point->get('IDdocument') !== (int)$document->getId()
        || !$document->canUserManagePvDocument($currentUserId)
    ) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $point->set('is_handled', !empty($_POST['is_handled']) ? 1 : 0);
    $point->set('IDuser_modification', $currentUserId);
    $saveResult = $point->save();
    if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($saveResult['text'] ?? 'Impossible de sauver le point.')),
        ], 400);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'point' => omoDocumentsPvEditorBuildPointResponsePayload($point, $organizationId, $currentUserId),
    ]);
}

if ($action === 'reorder_points') {
    if (!$document->canUserReorderPvPoints($currentUserId)) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $layout = json_decode((string)($_POST['layout'] ?? ''), true);
    $layout = is_array($layout) ? $layout : [];
    $reorderResult = \dbObject\DocumentPvPoint::reorderHierarchyForDocumentByUser(
        (int)$document->getId(),
        $layout,
        $currentUserId
    );
    if (!is_array($reorderResult) || ($reorderResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($reorderResult['message'] ?? 'Impossible de reordonner les points.')),
        ], 400);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'points' => omoDocumentsPvEditorBuildPointsPayloadForDocument((int)$document->getId(), $organizationId, $currentUserId, $editorToken),
    ]);
}

if ($action === 'update_stage') {
    $stageResult = $document->updatePvStageInOrganizationContext(
        $organizationId,
        $currentUserId,
        trim((string)($_POST['pv_stage'] ?? ''))
    );
    if (!is_array($stageResult) || ($stageResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($stageResult['text'] ?? 'Impossible de changer l etape du PV.')),
            'document' => omoDocumentsPvEditorBuildDocumentPayload($document, $organizationId, $currentUserId),
        ], 400);
    }

    $document->load((int)$document->getId());
    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'message' => omoDocumentsPvEditorActionT('documents.pv_editor.state.saved'),
        'document' => omoDocumentsPvEditorBuildDocumentPayload($document, $organizationId, $currentUserId),
    ]);
}

if ($action === 'toggle_attendance') {
    if (!$hasTeamApplication || !$document->canUserManagePvDocument($currentUserId)) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $event = method_exists($document, 'getAssociatedEvent')
        ? $document->getAssociatedEvent()
        : null;
    $identityKey = trim((string)($_POST['identity_key'] ?? ''));
    $isPresent = !empty($_POST['is_present']);
    $saveResult = $event instanceof \dbObject\Event
        ? $event->setAttendancePresence($organizationId, $currentUserId, $identityKey, $isPresent)
        : $document->setInvitationAttendance($organizationId, $currentUserId, $identityKey, $isPresent);
    if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($saveResult['text'] ?? 'Impossible de sauver la presence.')),
            'attendance' => omoDocumentsPvEditorBuildAttendancePayload($document, $organizationId),
        ], 400);
    }

    $document->load((int)$document->getId());
    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'attendance' => omoDocumentsPvEditorBuildAttendancePayload($document, $organizationId),
    ]);
}

if ($action === 'create_event') {
    if (
        !$hasCalendarApplication
        || !omoDocumentsPvEditorHasValidSessionToken($organizationId, $documentId, $currentUserId, $editorToken)
    ) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $startAt = omoDocumentsPvEditorParseLocalDateTime($_POST['start_at'] ?? '');
    $endAt = omoDocumentsPvEditorParseLocalDateTime($_POST['end_at'] ?? '');
    $holonId = isset($_POST['IDholon']) ? (int)$_POST['IDholon'] : 0;

    if ($title === '') {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.event.error_title'),
        ], 400);
    }
    if (!($startAt instanceof \DateTimeImmutable)) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.event.error_start'),
        ], 400);
    }
    if (!($endAt instanceof \DateTimeImmutable)) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.event.error_end'),
        ], 400);
    }
    if ($endAt < $startAt) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.event.error_end_before_start'),
        ], 400);
    }

    $organization = new \dbObject\Organization();
    $holon = new \dbObject\Holon();
    if (
        $holonId <= 0
        || !$organization->load($organizationId)
        || !$holon->load($holonId)
        || !$organization->containsHolon($holon)
    ) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.event.error_holon'),
        ], 400);
    }
    if (!$holon->isAllowed('CAN_CREATE_EVENT', false, $currentUserId)) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $event = new \dbObject\Event();
    $event->set('IDorganization', $organizationId);
    $event->set('IDholon', $holonId);
    $event->set('IDuser', $currentUserId);
    $event->set('active', 1);
    $event->set('title', $title);
    $event->set('description', $description !== '' ? $description : null);
    $event->set('status', \dbObject\Event::STATUS_CONFIRMED);
    $event->set('timezone', date_default_timezone_get());
    $event->set('start_at', $startAt);
    $event->set('end_at', $endAt);
    $event->set('is_all_day', 0);
    $saveResult = $event->save();
    if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => trim((string)($saveResult['text'] ?? '')) !== ''
                ? trim((string)$saveResult['text'])
                : omoDocumentsPvEditorActionT('documents.pv_editor.event.create_error'),
        ], 400);
    }

    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'event' => omoDocumentsPvEditorBuildEventEmbedPayload($event),
    ]);
}

if ($action === 'poll_updates') {
    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'serverTime' => date(DATE_ATOM),
        'document' => omoDocumentsPvEditorBuildDocumentPayload($document, $organizationId, $currentUserId),
        'attendance' => $hasTeamApplication ? omoDocumentsPvEditorBuildAttendancePayload($document, $organizationId) : null,
        'points' => omoDocumentsPvEditorBuildPointsPayloadForDocument((int)$document->getId(), $organizationId, $currentUserId, $editorToken),
    ]);
}

omoDocumentsPvEditorJsonResponse([
    'status' => false,
    'message' => 'Action invalide.',
], 400);
