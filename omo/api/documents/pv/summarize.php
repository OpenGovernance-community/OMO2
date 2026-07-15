<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/openai_text.php';

header('Content-Type: application/json; charset=UTF-8');

$jsonResponse = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $jsonResponse(['status' => false, 'message' => 'Requete invalide.'], 405);
}

$organizationId = isset($_POST['oid'])
    ? (int)$_POST['oid']
    : (int)($_SESSION['currentOrganization'] ?? 0);
$documentId = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
$currentUserId = (int)commonGetCurrentUserId();

if (
    $organizationId <= 0
    || $documentId <= 0
    || $currentUserId <= 0
    || !commonCurrentUserHasOrganizationAccess($organizationId)
) {
    $jsonResponse(['status' => false, 'message' => 'Acces refuse.'], 403);
}

$document = new \dbObject\Document();
if (
    !$document->load($documentId)
    || (int)$document->get('IDorganization') !== $organizationId
    || !$document->isPvDocument()
    || $document->getPvStage() !== \dbObject\Document::PV_STAGE_REVIEW
    || !$document->canUserManagePvDocument($currentUserId)
) {
    $jsonResponse(['status' => false, 'message' => 'Vous ne pouvez pas generer le resume de ce PV.'], 403);
}

$normalizeText = static function ($value): string {
    $text = html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', (string)$text);
    return trim((string)$text);
};

$sections = [];
$title = trim((string)$document->get('title'));
$description = $normalizeText($document->get('description'));
$stageLabel = trim((string)$document->getPvStageLabel());

if ($title !== '') {
    $sections[] = 'Titre du PV: ' . $title;
}
if ($description !== '') {
    $sections[] = 'Description actuelle: ' . $description;
}
if ($stageLabel !== '') {
    $sections[] = 'Etape: ' . $stageLabel;
}

$event = $document->getAssociatedEvent();
if ($event instanceof \dbObject\Event) {
    $eventParts = [];
    $eventTitle = trim((string)$event->get('title'));
    $startAt = $event->get('start_at');
    $endAt = $event->get('end_at');
    if ($eventTitle !== '') {
        $eventParts[] = 'nom=' . $eventTitle;
    }
    if ($startAt instanceof \DateTimeInterface) {
        $schedule = $startAt->format('d.m.Y H:i');
        if ($endAt instanceof \DateTimeInterface) {
            $schedule .= ' - ' . $endAt->format('H:i');
        }
        $eventParts[] = 'horaire=' . $schedule;
    }
    if (method_exists($event, 'getLocationDisplayData')) {
        $location = $event->getLocationDisplayData();
        $locationParts = array_filter([
            trim((string)($location['address'] ?? '')),
            trim((string)($location['videoUrl'] ?? '')),
        ]);
        if ($locationParts !== []) {
            $eventParts[] = 'lieu=' . implode(' | ', $locationParts);
        }
    }
    if ($eventParts !== []) {
        $sections[] = 'Evenement: ' . implode('; ', $eventParts);
    }
}

$attendanceEntries = $event instanceof \dbObject\Event
    ? $event->getAttendanceEntries($organizationId)
    : $document->getInvitationAttendanceEntries($organizationId);
if (is_array($attendanceEntries) && $attendanceEntries !== []) {
    $attendanceLines = [];
    foreach ($attendanceEntries as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $label = trim((string)($entry['displayLabel'] ?? $entry['secondaryLabel'] ?? ''));
        if ($label === '') {
            continue;
        }
        $attendanceLines[] = $label . (!empty($entry['isPresent']) ? ' (present)' : ' (absent)');
    }
    if ($attendanceLines !== []) {
        $sections[] = "Liste de presence:\n- " . implode("\n- ", $attendanceLines);
    }
}

$pointLines = [];
foreach ($document->getPvPoints(true) as $point) {
    if (!($point instanceof \dbObject\DocumentPvPoint)) {
        continue;
    }

    $pointTitle = trim((string)$point->get('title'));
    $pointType = trim((string)$point->get('pointtype'));
    $duration = (int)$point->get('desired_duration_minutes');
    $content = $normalizeText($point->get('content'));
    $line = $point->isGroup() ? 'Groupe' : 'Point';
    if ($pointTitle !== '') {
        $line .= ': ' . $pointTitle;
    }
    if ($pointType !== '' && !$point->isGroup()) {
        $line .= ' (type=' . $pointType . ')';
    }
    if ($duration > 0 && !$point->isGroup()) {
        $line .= ' (duree=' . $duration . ' min)';
    }
    if ($content !== '') {
        $line .= ': ' . $content;
    }
    $pointLines[] = $line;
}
if ($pointLines !== []) {
    $sections[] = "Ordre du jour:\n- " . implode("\n- ", $pointLines);
}

$fullText = trim(implode("\n\n", $sections));
if ($fullText === '') {
    $jsonResponse(['status' => false, 'message' => 'Le contenu du PV est vide.'], 422);
}

$result = commonOpenAiSummarizeSelectedDocumentText($fullText, $fullText, [
    'title' => $title,
    'pv_summary' => true,
]);
if (empty($result['status'])) {
    $jsonResponse([
        'status' => false,
        'message' => trim((string)($result['message'] ?? 'Impossible de generer le resume.')),
    ], 422);
}

$summary = trim((string)($result['text'] ?? ''));
if ($summary === '') {
    $jsonResponse(['status' => false, 'message' => 'Le resume genere est vide.'], 422);
}

$jsonResponse([
    'status' => true,
    'text' => $summary,
    'model' => trim((string)($result['model'] ?? '')),
]);
