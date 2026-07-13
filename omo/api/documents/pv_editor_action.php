<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/pv_editor_helpers.php';

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

function omoDocumentsPvEditorBuildPointResponsePayload(\dbObject\DocumentPvPoint $point, int $organizationId, int $currentUserId): array
{
    $uiText = omoDocumentsPvEditorBuildUiText('omoDocumentsPvEditorActionT');
    $lockToken = trim((string)($_POST['editor_token'] ?? $_GET['editor_token'] ?? ''));
    $document = new \dbObject\Document();
    $hasStructureApplication = omoDocumentsPvEditorOrganizationHasApplication($organizationId, $currentUserId, 'structure');
    $concernedHolonOptions = [];
    $authorOptions = [];
    if ($document->load((int)$point->get('IDdocument'))) {
        $concernedHolonOptions = $hasStructureApplication
            ? \dbObject\DocumentPvPoint::buildConcernedHolonOptionsForDocument($document, (int)$point->get('IDuser_author'))
            : [];
        $authorOptions = $document->getPvPointAuthorOptions($organizationId);
    }
    $pointData = $point->buildEditorData($organizationId, $currentUserId, $lockToken);
    $pointData['isEditable'] = $document->canUserEditPvPoint($point, $currentUserId);
    $pointData['canEditNow'] = !empty($pointData['isEditable']) && empty($pointData['lock']['isLockedByOther']);
    $pointData['canReorder'] = $document->canUserReorderPvPoints($currentUserId);
    $pointData['isPvEditor'] = $document->isPvEditor($currentUserId);
    $pointData['canToggleHandled'] = $document->canUserManagePvDocument($currentUserId);
    $pointData['canAssignAuthor'] = $document->canUserManagePvDocument($currentUserId);
    $pointData['hasStructureApplication'] = $hasStructureApplication;
    $pointData['authorOptions'] = $authorOptions;
    $pointData['authorHolonOptions'] = [];
    foreach ($authorOptions as $authorOption) {
		$authorValue = trim((string)($authorOption['value'] ?? ''));
        $authorUserId = (int)($authorOption['userId'] ?? 0);
        if ($authorValue !== '') {
            $pointData['authorHolonOptions'][$authorValue] = $hasStructureApplication && $authorUserId > 0
                ? \dbObject\DocumentPvPoint::buildConcernedHolonOptionsForDocument($document, $authorUserId)
                : [];
        }
    }
    $pointData = omoDocumentsPvEditorAttachConcernedHolonOptions(
        $pointData,
        $concernedHolonOptions
    );

    return omoDocumentsPvEditorBuildPointPayload($pointData, $uiText);
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

    $payload = [];
    foreach ($points as $point) {
        if (!($point instanceof \dbObject\DocumentPvPoint) || (int)$point->getId() <= 0) {
            continue;
        }

        $pointData = $point->buildEditorData($organizationId, $currentUserId, $lockToken);
        $pointData['isEditable'] = $hasDocument && $document->canUserEditPvPoint($point, $currentUserId);
        $pointData['canEditNow'] = !empty($pointData['isEditable']) && empty($pointData['lock']['isLockedByOther']);
        $pointData['canReorder'] = $hasDocument && $document->canUserReorderPvPoints($currentUserId);
        $pointData['isPvEditor'] = $hasDocument && $document->isPvEditor($currentUserId);
        $pointData['canToggleHandled'] = $hasDocument && $document->canUserManagePvDocument($currentUserId);
        $pointData['canAssignAuthor'] = $hasDocument && $document->canUserManagePvDocument($currentUserId);
        $pointData['hasStructureApplication'] = $hasStructureApplication;
        $pointData['authorOptions'] = $authorOptions;
        $pointData['authorHolonOptions'] = [];
        if ($hasDocument) {
            foreach ($authorOptions as $authorOption) {
				$authorValue = trim((string)($authorOption['value'] ?? ''));
                $authorUserId = (int)($authorOption['userId'] ?? 0);
                if ($authorValue !== '') {
                    $pointData['authorHolonOptions'][$authorValue] = $hasStructureApplication && $authorUserId > 0
                        ? \dbObject\DocumentPvPoint::buildConcernedHolonOptionsForDocument($document, $authorUserId)
                        : [];
                }
            }
        }
        $pointData = omoDocumentsPvEditorAttachConcernedHolonOptions(
            $pointData,
            $hasDocument && $hasStructureApplication
                ? \dbObject\DocumentPvPoint::buildConcernedHolonOptionsForDocument($document, (int)$point->get('IDuser_author'))
                : []
        );
        $payload[] = omoDocumentsPvEditorBuildPointPayload($pointData, $uiText);
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
        trim((string)$document->get('title')),
        trim((string)$document->get('description')),
        (string)($visibility['type'] ?? \dbObject\ObjectVisibility::TYPE_ORGANIZATION),
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
        'isPvEditor' => $document->isPvEditor($currentUserId),
        'canManagePvDocument' => $document->canUserManagePvDocument($currentUserId),
        'canClaimPvEditor' => $document->canUserClaimPvEditor($organizationId, $currentUserId),
        'isPvValidated' => $document->isPvValidated(),
    ];
}

function omoDocumentsPvEditorBuildAttendancePayload(\dbObject\Document $document, int $organizationId): ?array
{
    return omoDocumentsPvEditorBuildAttendancePayloadFromDocument($document, $organizationId);
}

$action = trim((string)($_POST['action'] ?? $_GET['action'] ?? ''));
$documentId = isset($_POST['document_id']) ? (int)$_POST['document_id'] : (isset($_GET['document_id']) ? (int)$_GET['document_id'] : 0);
$organizationId = isset($_POST['oid']) ? (int)$_POST['oid'] : (isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0));
$currentUserId = (int)commonGetCurrentUserId();
$editorToken = trim((string)($_POST['editor_token'] ?? $_GET['editor_token'] ?? ''));

$document = omoDocumentsPvEditorLoadDocumentOrFail($documentId, $organizationId, $currentUserId);
$hasTeamApplication = omoDocumentsPvEditorOrganizationHasApplication($organizationId, $currentUserId, 'team');
$hasStructureApplication = omoDocumentsPvEditorOrganizationHasApplication($organizationId, $currentUserId, 'structure');

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

if ($action === 'add_point') {
    if ($document->isPvValidated()) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }
    $point = new \dbObject\DocumentPvPoint();
    $point->set('IDdocument', (int)$document->getId());
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

if ($action === 'save_point') {
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

    $rawPointIds = $_POST['point_ids'] ?? [];
    if (!is_array($rawPointIds)) {
        $decoded = json_decode((string)$rawPointIds, true);
        $rawPointIds = is_array($decoded) ? $decoded : [];
    }

    $reorderResult = \dbObject\DocumentPvPoint::reorderForDocumentByUser((int)$document->getId(), $rawPointIds, $currentUserId);
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
    if (!($event instanceof \dbObject\Event)) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.unavailable'),
        ], 400);
    }

    $saveResult = $event->setAttendancePresence(
        $organizationId,
        $currentUserId,
        trim((string)($_POST['identity_key'] ?? '')),
        !empty($_POST['is_present'])
    );
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
