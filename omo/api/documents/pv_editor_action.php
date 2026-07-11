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

function omoDocumentsPvEditorBuildPointResponsePayload(\dbObject\DocumentPvPoint $point, int $organizationId, int $currentUserId): array
{
    $uiText = omoDocumentsPvEditorBuildUiText('omoDocumentsPvEditorActionT');
    $lockToken = trim((string)($_POST['editor_token'] ?? $_GET['editor_token'] ?? ''));
    $document = new \dbObject\Document();
    $concernedHolonOptions = [];
    if ($document->load((int)$point->get('IDdocument'))) {
        $concernedHolonOptions = \dbObject\DocumentPvPoint::buildConcernedHolonOptionsForDocument($document, $currentUserId);
    }
    $pointData = omoDocumentsPvEditorAttachConcernedHolonOptions(
        $point->buildEditorData($organizationId, $currentUserId, $lockToken),
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
    $concernedHolonOptions = $document->load($documentId)
        ? \dbObject\DocumentPvPoint::buildConcernedHolonOptionsForDocument($document, $currentUserId)
        : [];

    $payload = [];
    foreach ($points as $point) {
        if (!($point instanceof \dbObject\DocumentPvPoint) || (int)$point->getId() <= 0) {
            continue;
        }

        $payload[] = omoDocumentsPvEditorBuildPointPayload(
            omoDocumentsPvEditorAttachConcernedHolonOptions(
                $point->buildEditorData($organizationId, $currentUserId, $lockToken),
                $concernedHolonOptions
            ),
            $uiText
        );
    }

    return $payload;
}

function omoDocumentsPvEditorBuildDocumentPayload(\dbObject\Document $document, int $organizationId, int $currentUserId): array
{
    return [
        'id' => (int)$document->getId(),
        'pvStage' => $document->getPvStage(),
        'pvStageLabel' => $document->getPvStageLabel(),
        'canManagePvStage' => $document->canManagePvStage($organizationId, $currentUserId),
    ];
}

$action = trim((string)($_POST['action'] ?? $_GET['action'] ?? ''));
$documentId = isset($_POST['document_id']) ? (int)$_POST['document_id'] : (isset($_GET['document_id']) ? (int)$_GET['document_id'] : 0);
$organizationId = isset($_POST['oid']) ? (int)$_POST['oid'] : (isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0));
$currentUserId = (int)commonGetCurrentUserId();
$editorToken = trim((string)($_POST['editor_token'] ?? $_GET['editor_token'] ?? ''));

$document = omoDocumentsPvEditorLoadDocumentOrFail($documentId, $organizationId, $currentUserId);

if ($action === 'add_point') {
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
        || !$point->isEditableByUser($currentUserId)
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
        || !$point->isEditableByUser($currentUserId)
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

    $requestedConcernedHolonId = isset($_POST['concerned_holon_id']) ? (int)$_POST['concerned_holon_id'] : 0;
    $currentConcernedHolonId = (int)$point->get('IDholon_concerned');
    if (
        $requestedConcernedHolonId > 0
        && $requestedConcernedHolonId !== $currentConcernedHolonId
        && !\dbObject\DocumentPvPoint::concernedHolonIsAllowedForDocument($document, $currentUserId, $requestedConcernedHolonId)
    ) {
        omoDocumentsPvEditorJsonResponse([
            'status' => false,
            'message' => omoDocumentsPvEditorActionT('documents.pv_editor.error.forbidden'),
        ], 403);
    }

    $point->set('title', trim((string)($_POST['title'] ?? '')));
    $point->set('pointtype', trim((string)($_POST['pointtype'] ?? '')));
    $point->set('desired_duration_minutes', trim((string)($_POST['desired_duration_minutes'] ?? '')));
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

if ($action === 'poll_updates') {
    omoDocumentsPvEditorJsonResponse([
        'status' => true,
        'serverTime' => date(DATE_ATOM),
        'document' => omoDocumentsPvEditorBuildDocumentPayload($document, $organizationId, $currentUserId),
        'points' => omoDocumentsPvEditorBuildPointsPayloadForDocument((int)$document->getId(), $organizationId, $currentUserId, $editorToken),
    ]);
}

omoDocumentsPvEditorJsonResponse([
    'status' => false,
    'message' => 'Action invalide.',
], 400);
