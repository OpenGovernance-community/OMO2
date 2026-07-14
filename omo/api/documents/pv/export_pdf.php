<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$sourceLang = [
    'documents.pdf.error.invalid' => ['text' => 'PV invalide.', 'context' => 'Error shown when a PDF export request has no valid PV id.'],
    'documents.pdf.error.forbidden' => ['text' => 'PV introuvable ou inaccessible.', 'context' => 'Error shown when a user cannot export the requested PV.'],
    'documents.pdf.error.library' => ['text' => 'La librairie d export PDF est indisponible.', 'context' => 'Error shown when Dompdf cannot be loaded.'],
    'documents.pdf.error.generate' => ['text' => 'Impossible de generer le PDF.', 'context' => 'Error shown when Dompdf fails to generate the PV export.'],
    'documents.pdf.meta.stage' => ['text' => 'Etape', 'context' => 'PV stage label in the PDF header.'],
    'documents.pdf.meta.event' => ['text' => 'Reunion', 'context' => 'Associated event label in the PDF header.'],
    'documents.pdf.meta.schedule' => ['text' => 'Horaire', 'context' => 'Meeting schedule label in the PDF header.'],
    'documents.pdf.meta.location' => ['text' => 'Lieu', 'context' => 'Meeting location label in the PDF header.'],
    'documents.pdf.footer.generated' => ['text' => 'Exporte le {date}', 'context' => 'Generation timestamp shown in the PDF footer.'],
];
$lang = omoLoadTranslationBundle('omo_documents_pdf', $sourceLang);

function omoDocumentsPdfT(string $key, array $replace = []): string
{
    global $lang, $sourceLang;
    return (string)t($key, $replace, $lang, $sourceLang);
}

function omoDocumentsPdfFail(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}

function omoDocumentsPdfEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function omoDocumentsPdfSafeFilename(string $title, int $documentId): string
{
    $title = trim($title);
    $asciiTitle = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
    $asciiTitle = is_string($asciiTitle) ? $asciiTitle : $title;
    $slug = trim((string)preg_replace('/[^A-Za-z0-9._-]+/', '-', $asciiTitle), '-.');
    if ($slug === '') {
        $slug = 'PV-' . $documentId;
    }

    return substr($slug, 0, 100) . '.pdf';
}

function omoDocumentsPdfResolveLocalImage(string $source, string $projectRoot): string
{
    $source = trim(html_entity_decode($source, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($source === '' || preg_match('/^(?:data:|cid:)/i', $source)) {
        return $source;
    }

    $sourceParts = parse_url($source);
    if (!is_array($sourceParts)) {
        return $source;
    }

    $scheme = strtolower((string)($sourceParts['scheme'] ?? ''));
    if ($scheme !== '' && !in_array($scheme, ['http', 'https'], true)) {
        return $source;
    }

    if ($scheme !== '') {
        $sourceHost = strtolower((string)($sourceParts['host'] ?? ''));
        $requestHost = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
        if ($sourceHost === '' || $requestHost === '' || $sourceHost !== $requestHost) {
            return $source;
        }
    }

    $urlPath = rawurldecode((string)($sourceParts['path'] ?? ''));
    if ($urlPath === '' || str_ends_with(strtolower($urlPath), '.php')) {
        return $source;
    }

    $projectRoot = realpath($projectRoot) ?: '';
    $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $urlPath), DIRECTORY_SEPARATOR);
    $imagePath = $projectRoot !== '' ? realpath($projectRoot . DIRECTORY_SEPARATOR . $relativePath) : false;
    if (
        !is_string($imagePath)
        || !is_file($imagePath)
        || !str_starts_with($imagePath, $projectRoot . DIRECTORY_SEPARATOR)
    ) {
        return $source;
    }

    $imageInfo = @getimagesize($imagePath);
    $mimeType = is_array($imageInfo) ? strtolower((string)($imageInfo['mime'] ?? '')) : '';
    if (!in_array($mimeType, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) {
        return $source;
    }

    $imageContent = @file_get_contents($imagePath);
    if (!is_string($imageContent) || $imageContent === '') {
        return $source;
    }

    return 'data:' . $mimeType . ';base64,' . base64_encode($imageContent);
}

function omoDocumentsPdfEmbedLocalImages(string $html, string $projectRoot): string
{
    if ($html === '' || !class_exists('DOMDocument')) {
        return $html;
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    $previousState = libxml_use_internal_errors(true);
    $loaded = $dom->loadHTML($html, LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previousState);
    if (!$loaded) {
        return $html;
    }

    foreach ($dom->getElementsByTagName('img') as $imageNode) {
        if (!$imageNode instanceof DOMElement || !$imageNode->hasAttribute('src')) {
            continue;
        }

        $resolvedSource = omoDocumentsPdfResolveLocalImage($imageNode->getAttribute('src'), $projectRoot);
        if ($resolvedSource !== '') {
            $imageNode->setAttribute('src', $resolvedSource);
        }

        $classNames = preg_split('/\s+/', trim($imageNode->getAttribute('class'))) ?: [];
        if (in_array('omo-document-pv__point-type-icon', $classNames, true)) {
            $imageNode->setAttribute('width', '16');
            $imageNode->setAttribute('height', '16');
            $imageNode->setAttribute(
                'style',
                rtrim($imageNode->getAttribute('style'), '; ') . ';width:16px;height:16px;vertical-align:middle;'
            );
        }
    }

    $resolvedHtml = $dom->saveHTML();
    return is_string($resolvedHtml) && $resolvedHtml !== '' ? $resolvedHtml : $html;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    omoDocumentsPdfFail(405, 'Methode non autorisee.');
}

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$organizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$currentUserId = (int)commonGetCurrentUserId();
if ($documentId <= 0 || $organizationId <= 0 || $currentUserId <= 0) {
    omoDocumentsPdfFail(400, omoDocumentsPdfT('documents.pdf.error.invalid'));
}

$document = new \dbObject\Document();
if (
    !$document->load($documentId)
    || !$document->isPvDocument()
    || $document->isArchived()
    || (int)$document->get('IDorganization') !== $organizationId
) {
    omoDocumentsPdfFail(404, omoDocumentsPdfT('documents.pdf.error.forbidden'));
}

$hasPvInvitationAccess = !$document->isPvValidated()
    && $document->canUserAccessPvBeforeValidation($currentUserId, $organizationId);
$documentHolonId = (int)$document->get('IDholon');
$canAccessDocument = $document->canUserPassPvMeetingVisibilityGate($currentUserId, $organizationId)
    && (
        $hasPvInvitationAccess
        || $document->canViewInOrganizationContext($organizationId, $documentHolonId > 0 ? $documentHolonId : null)
        || $document->canViewDirectlyInOrganization($organizationId)
    );
if (!$canAccessDocument) {
    omoDocumentsPdfFail(404, omoDocumentsPdfT('documents.pdf.error.forbidden'));
}

$dompdfAutoload = dirname(__DIR__, 4) . '/dompdf/vendor/autoload.php';
if (!is_file($dompdfAutoload)) {
    omoDocumentsPdfFail(503, omoDocumentsPdfT('documents.pdf.error.library'));
}
require_once $dompdfAutoload;

$title = trim((string)$document->get('title'));
$description = trim((string)$document->get('description'));
$event = $document->getAssociatedEvent();
$eventTitle = '';
$eventSchedule = '';
$eventLocation = '';
if ($event instanceof \dbObject\Event) {
    $eventTitle = trim((string)$event->get('title'));
    $startAt = $event->get('start_at');
    $endAt = $event->get('end_at');
    if ($startAt instanceof \DateTimeInterface) {
        $eventSchedule = $startAt->format('d.m.Y H:i');
        if ($endAt instanceof \DateTimeInterface) {
            $eventSchedule .= ' - ' . $endAt->format('H:i');
        }
    }
    $locationData = $event->getLocationDisplayData();
    $locationParts = array_filter([
        trim((string)($locationData['address'] ?? '')),
        trim((string)($locationData['videoUrl'] ?? '')),
    ]);
    $eventLocation = implode(' | ', $locationParts);
}

$metaRows = [
    [omoDocumentsPdfT('documents.pdf.meta.stage'), $document->getPvStageLabel()],
    [omoDocumentsPdfT('documents.pdf.meta.event'), $eventTitle],
    [omoDocumentsPdfT('documents.pdf.meta.schedule'), $eventSchedule],
    [omoDocumentsPdfT('documents.pdf.meta.location'), $eventLocation],
];
$metaHtml = '';
foreach ($metaRows as $metaRow) {
    if (trim((string)$metaRow[1]) === '') {
        continue;
    }
    $metaHtml .= '<div class="meta-row"><strong>' . omoDocumentsPdfEscape($metaRow[0]) . '</strong><span>' . omoDocumentsPdfEscape($metaRow[1]) . '</span></div>';
}

$generatedAt = (new \DateTimeImmutable())->format('d.m.Y H:i');
$documentHtml = '<!doctype html><html lang="fr"><head><meta charset="UTF-8"><style>'
    . '@page { margin: 18mm 16mm 18mm; }'
    . 'body { color:#172b3a; font-family:"DejaVu Sans",sans-serif; font-size:10pt; line-height:1.45; }'
    . 'h1 { margin:0 0 3mm; color:#092f43; font-size:23pt; line-height:1.15; }'
    . '.lead { margin:0 0 5mm; color:#536b79; font-size:10.5pt; }'
    . '.meta { margin:0 0 8mm; padding:3mm 4mm; border:1px solid #cbdde5; background:#f2f8fa; }'
    . '.meta-row { margin:0 0 1.2mm; } .meta-row:last-child { margin-bottom:0; }'
    . '.meta-row strong { display:inline-block; width:26mm; color:#31596c; }'
    . '.omo-document-pv__summary { margin-bottom:5mm; }'
    . '.omo-document-pv__summary-pills { font-size:9pt; color:#526b78; }'
    . '.omo-document-pv__summary-pills span { display:inline-block; margin:0 2mm 1mm 0; padding:1mm 2mm; border:1px solid #cbdde5; border-radius:4px; background:#f6fafb; }'
    . '.omo-document-pv__group { margin-top:5mm; padding:2mm 3mm; border-left:4px solid #2d7c8f; background:#edf6f7; page-break-after:avoid; }'
    . '.omo-document-pv__group-title { margin:0; color:#16485a; font-size:13pt; }'
    . '.omo-document-pv__group-order { margin-right:2mm; color:#68838f; }'
    . '.omo-document-pv__point { margin-top:4mm; padding:4mm; border:1px solid #ccdce3; border-radius:5px; page-break-inside:auto; }'
    . '.omo-document-pv__point-head { display:table; width:100%; page-break-after:avoid; }'
    . '.omo-document-pv__point-order { display:table-cell; width:12mm; color:#51707e; font-size:12pt; font-weight:bold; vertical-align:top; }'
    . '.omo-document-pv__point-main { display:table-cell; vertical-align:top; }'
    . '.omo-document-pv__point-topline { color:#51707e; font-size:8.5pt; }'
    . '.omo-document-pv__point-type { margin-right:3mm; font-weight:bold; text-transform:uppercase; }'
    . '.omo-document-pv__point-type-icon { display:inline-block; width:4mm; height:4mm; margin-right:1mm; vertical-align:middle; }'
    . '.omo-document-pv__point-duration { margin-right:2mm; }'
    . '.omo-document-pv__point-title { margin:1mm 0 1.5mm; color:#132f3e; font-size:14pt; }'
    . '.omo-document-pv__point-fields, .omo-document-pv__point-chips { margin:1mm 0; color:#526b78; font-size:8.5pt; }'
    . '.omo-document-pv__field, .omo-document-pv__chip-group { display:block; margin-bottom:1mm; }'
    . '.omo-document-pv__field strong, .omo-document-pv__chip-group-label { margin-right:2mm; color:#31596c; }'
    . '.omo-document-pv__chip { display:inline-block; margin:0 1mm 1mm 0; padding:.5mm 1.5mm; border:1px solid #d5e2e8; border-radius:3px; }'
    . '.omo-document-pv__point-content { margin-top:3mm; }'
    . '.omo-document-pv__point-content p { margin:0 0 2mm; }'
    . '.omo-document-pv__point-content img { display:block; width:auto; height:auto; max-width:70mm; max-height:70mm; margin:2mm 0; }'
    . '.omo-simple-html-embed { margin:2mm 0; padding:2.5mm; border:1px solid #ccdce3; background:#f7fafb; }'
    . 'a { color:#126b86; text-decoration:none; }'
    . '.footer { margin-top:9mm; padding-top:2mm; border-top:1px solid #d9e3e8; color:#78909b; font-size:8pt; text-align:right; }'
    . '</style></head><body>'
    . '<h1>' . omoDocumentsPdfEscape($title !== '' ? $title : ('PV #' . $documentId)) . '</h1>'
    . ($description !== '' ? '<p class="lead">' . nl2br(omoDocumentsPdfEscape($description)) . '</p>' : '')
    . ($metaHtml !== '' ? '<div class="meta">' . $metaHtml . '</div>' : '')
    . $document->getRenderedContentForCurrentViewer()
    . '<div class="footer">' . omoDocumentsPdfEscape(omoDocumentsPdfT('documents.pdf.footer.generated', ['date' => $generatedAt])) . '</div>'
    . '</body></html>';
$projectRoot = dirname(__DIR__, 4);
$documentHtml = omoDocumentsPdfEmbedLocalImages($documentHtml, $projectRoot);

try {
    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isRemoteEnabled', false);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('chroot', $projectRoot);

    $dompdf = new Dompdf($options);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($documentHtml, 'UTF-8');
    $dompdf->render();
    $pdfContent = $dompdf->output();
} catch (\Throwable $exception) {
    omoDocumentsPdfFail(500, omoDocumentsPdfT('documents.pdf.error.generate'));
}

$filename = omoDocumentsPdfSafeFilename($title, $documentId);
header('X-Content-Type-Options: nosniff');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . addcslashes($filename, "\\\"") . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Content-Length: ' . strlen($pdfContent));
echo $pdfContent;
exit;
