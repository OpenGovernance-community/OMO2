<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$sourceLang = [
    'documents.pdf.error.invalid' => ['text' => 'PV invalide.', 'context' => 'Error shown when a PDF export request has no valid PV id.'],
    'documents.pdf.error.forbidden' => ['text' => 'PV introuvable ou inaccessible.', 'context' => 'Error shown when a user cannot export the requested PV.'],
    'documents.pdf.error.library' => ['text' => 'La bibliothèque d’export PDF est indisponible.', 'context' => 'Error shown when Dompdf cannot be loaded.'],
    'documents.pdf.error.generate' => ['text' => 'Impossible de générer le PDF.', 'context' => 'Error shown when Dompdf fails to generate the PV export.'],
    'documents.pdf.meta.stage' => ['text' => 'Étape', 'context' => 'PV stage label in the PDF header.'],
    'documents.pdf.meta.event' => ['text' => 'Réunion', 'context' => 'Associated event label in the PDF header.'],
    'documents.pdf.meta.schedule' => ['text' => 'Horaire', 'context' => 'Meeting schedule label in the PDF header.'],
    'documents.pdf.meta.location' => ['text' => 'Lieu', 'context' => 'Meeting location label in the PDF header.'],
    'documents.pdf.footer.generated' => ['text' => 'Exporté le {date}', 'context' => 'Generation timestamp shown in the PDF footer.'],
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

function omoDocumentsPdfConvertIndicatorSvgs(string $html): string
{
    if ($html === '' || !class_exists('DOMDocument')) {
        return $html;
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    $previousState = libxml_use_internal_errors(true);
    $loaded = $dom->loadHTML(
        '<?xml encoding="utf-8" ?><div>' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previousState);
    if (!$loaded) {
        return $html;
    }

    $svgNodes = [];
    foreach ($dom->getElementsByTagName('svg') as $svgNode) {
        if (!($svgNode instanceof DOMElement)) {
            continue;
        }

        $classNames = preg_split('/\s+/', trim($svgNode->getAttribute('class'))) ?: [];
        if (in_array('omo-stats-chart', $classNames, true)) {
            $svgNodes[] = $svgNode;
        }
    }

    foreach ($svgNodes as $svgNode) {
        $isOverdue = false;
        $isWarning = false;
        $ancestor = $svgNode->parentNode;
        while ($ancestor instanceof DOMElement) {
            $ancestorClasses = preg_split('/\s+/', trim($ancestor->getAttribute('class'))) ?: [];
            if (in_array('omo-indicator-embed--overdue', $ancestorClasses, true)) {
                $isOverdue = true;
                break;
            }
            if (in_array('omo-indicator-embed--warning', $ancestorClasses, true)) {
                $isWarning = true;
                break;
            }
            $ancestor = $ancestor->parentNode;
        }

        foreach ($svgNode->getElementsByTagName('polyline') as $polyline) {
            if (!($polyline instanceof DOMElement)) {
                continue;
            }

            $classNames = preg_split('/\s+/', trim($polyline->getAttribute('class'))) ?: [];
            $sourceStyle = trim($polyline->getAttribute('style'));
            $stroke = $isOverdue ? '#dc2626' : ($isWarning ? '#ca8a04' : '#2563eb');
            if (in_array('omo-stats-chart__reference', $classNames, true)) {
                $stroke = '#7b9aa8';
            } elseif (preg_match('/stroke\s*:\s*(#[0-9a-f]{6})/i', $sourceStyle, $strokeMatch)) {
                $stroke = strtolower($strokeMatch[1]);
            }

            $style = 'fill:none;stroke:' . $stroke . ';stroke-width:'
                . (in_array('omo-stats-chart__reference', $classNames, true) ? '1.7' : '2.4')
                . ';stroke-linecap:round;stroke-linejoin:round;';
            if (in_array('omo-stats-chart__reference', $classNames, true)) {
                $style .= 'stroke-dasharray:4 3;';
            }
            $polyline->setAttribute('style', $style);
        }

        foreach ($svgNode->getElementsByTagName('circle') as $circle) {
            if (!($circle instanceof DOMElement)) {
                continue;
            }

            $circle->setAttribute(
                'style',
                'fill:' . ($isOverdue ? '#dc2626' : ($isWarning ? '#ca8a04' : '#2563eb')) . ';stroke:#ffffff;stroke-width:1.5;'
            );
        }

        foreach ($svgNode->getElementsByTagName('rect') as $rect) {
            if (!($rect instanceof DOMElement)) {
                continue;
            }

            $classNames = preg_split('/\s+/', trim($rect->getAttribute('class'))) ?: [];
            if (in_array('omo-stats-chart__bar', $classNames, true)) {
                $color = $isOverdue ? '#dc2626' : ($isWarning ? '#ca8a04' : '#2563eb');
                $rect->setAttribute('style', 'fill:' . $color . ';fill-opacity:0.3;stroke:' . $color . ';stroke-width:1;');
            }
        }

        foreach ($svgNode->getElementsByTagName('line') as $line) {
            if (!($line instanceof DOMElement)) {
                continue;
            }

            $classNames = preg_split('/\s+/', trim($line->getAttribute('class'))) ?: [];
            if (in_array('omo-stats-chart__scale-line', $classNames, true)) {
                $line->setAttribute(
                    'style',
                    'fill:none;stroke:#7b9aa8;stroke-width:1.2;stroke-dasharray:4 3;'
                );
            }
        }

        foreach ($svgNode->getElementsByTagName('text') as $text) {
            if (!($text instanceof DOMElement)) {
                continue;
            }

            $classNames = preg_split('/\s+/', trim($text->getAttribute('class'))) ?: [];
            if (in_array('omo-stats-chart__scale-label', $classNames, true)) {
                $color = in_array('omo-stats-chart__scale-label--cumulative', $classNames, true)
                    ? ($isOverdue ? '#dc2626' : ($isWarning ? '#ca8a04' : '#2563eb'))
                    : '#71838d';
                $text->setAttribute('style', 'fill:' . $color . ';font-size:9px;');
            }
        }

        $svgMarkup = $dom->saveXML($svgNode);
        if (!is_string($svgMarkup) || trim($svgMarkup) === '') {
            continue;
        }

        if (stripos($svgMarkup, '<svg xmlns=') !== 0) {
            $svgMarkup = preg_replace(
                '/^<svg\b/i',
                '<svg xmlns="http://www.w3.org/2000/svg"',
                $svgMarkup,
                1
            ) ?: $svgMarkup;
        }

        $imageNode = $dom->createElement('img');
        $imageNode->setAttribute('class', 'omo-indicator-embed__chart-image');
        $imageNode->setAttribute('src', 'data:image/svg+xml;base64,' . base64_encode($svgMarkup));
        $imageNode->setAttribute('width', '180');
        $imageNode->setAttribute('height', '54');
        $imageNode->setAttribute('alt', '');
        $imageNode->setAttribute('aria-hidden', 'true');

        if ($svgNode->parentNode instanceof DOMNode) {
            $svgNode->parentNode->replaceChild($imageNode, $svgNode);
        }
    }

    $resolvedHtml = $dom->saveHTML();
    return is_string($resolvedHtml) && $resolvedHtml !== '' ? $resolvedHtml : $html;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    omoDocumentsPdfFail(405, 'Méthode non autorisée.');
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

$dompdfAutoload = dirname(__DIR__, 4) . '/vendor/autoload.php';
if (!is_file($dompdfAutoload)) {
    omoDocumentsPdfFail(503, omoDocumentsPdfT('documents.pdf.error.library'));
}
require_once $dompdfAutoload;

if (!class_exists(Dompdf::class)) {
    omoDocumentsPdfFail(503, omoDocumentsPdfT('documents.pdf.error.library'));
}

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

try {
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
    . '.omo-document-pv__point { margin-top:4mm; padding:4mm 0 0; border:0; border-radius:0; page-break-inside:auto; }'
    . '.omo-document-pv__point:first-of-type { border-top:0; }'
    . '.omo-document-pv__point ~ .omo-document-pv__point { border-top:1px solid #ccdce3; }'
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
    . '.omo-project-embed--resolved { display:block; margin:2mm 0; padding:2.5mm; border:1px solid #ccdce3; border-radius:4px; background:#f7fafb; page-break-inside:avoid; }'
    . '.omo-project-embed__head { display:table; width:100%; } .omo-project-embed__title { display:table-cell; color:#173b4d; font-weight:bold; text-decoration:none; } .omo-project-embed__external { display:table-cell; width:6mm; color:#526b78; text-align:right; text-decoration:none; }'
    . '.omo-project-embed__meta { display:block; margin-top:1mm; color:#71838d; font-size:8.5pt; }'
    . '.omo-project-embed__toggle { display:block; width:100%; margin-top:2mm; padding:0; border:0; background:transparent; text-align:left; } .omo-project-embed__toggle-label { display:block; margin-bottom:1mm; color:#526b78; font-size:8pt; font-weight:bold; }'
    . '.omo-project-status-bar { display:block; width:100%; height:2mm; overflow:hidden; border-radius:2mm; background:#dbe3e8; font-size:0; line-height:0; } .omo-project-status-bar__segment { display:inline-block; height:2mm; vertical-align:top; }'
    . '.omo-project-status-bar__segment--ready { background:#5e88d5; } .omo-project-status-bar__segment--in_progress { background:#d0a857; } .omo-project-status-bar__segment--blocked { background:#d67272; } .omo-project-status-bar__segment--review { background:#9884c7; } .omo-project-status-bar__segment--done { background:#6fa98d; } .omo-project-status-bar__segment--someday { background:#99a3b1; }'
    . '.omo-project-embed__children { display:none; }'
    . '.omo-simple-html-embed { margin:2mm 0; padding:2.5mm; border:1px solid #ccdce3; background:#f7fafb; }'
    . '.omo-indicator-embed { display:block; margin:2mm 0; padding:3mm; border:1px solid #cbdde5; border-radius:5px; background:#f7fafb; page-break-inside:avoid; }'
    . '.omo-indicator-embed--overdue { border-color:#e6b8b8; background:#fff7f7; }'
    . '.omo-indicator-embed--warning { border-color:#ead58a; background:#fffdf0; }'
    . '.omo-indicator-embed__main { display:table; width:100%; table-layout:fixed; }'
    . '.omo-indicator-embed__chart { display:table-cell; width:33%; max-width:56mm; height:31.5mm; padding:2mm; border:1px solid #cbdde5; border-radius:3px; background:#f7fafb; vertical-align:middle; }'
    . '.omo-indicator-embed--overdue .omo-indicator-embed__chart { border-color:#e6b8b8; background:#fff7f7; }'
    . '.omo-indicator-embed--warning .omo-indicator-embed__chart { border-color:#ead58a; background:#fffdf0; }'
    . '.omo-indicator-embed__copy { display:table-cell; width:auto; padding-left:3mm; vertical-align:middle; }'
    . '.omo-indicator-embed__title { display:block; overflow:hidden; margin:0; color:#173b4d; font-weight:bold; white-space:nowrap; }'
    . '.omo-indicator-embed__description { display:block; max-height:12mm; overflow:hidden; margin-top:1mm; color:#71838d; font-size:inherit; line-height:1.3; }'
    . '.omo-indicator-embed__status-dot { display:inline-block; width:2mm; height:2mm; margin-right:1mm; border-radius:50%; background:#94a3b8; }'
    . '.omo-indicator-embed__status-dot--current { background:#16a34a; }'
    . '.omo-indicator-embed__status-dot--warning { background:#eab308; }'
    . '.omo-indicator-embed__status-dot--overdue { background:#dc2626; }'
    . '.omo-indicator-embed__chart svg, .omo-indicator-embed__chart-image { display:block; width:100%; height:18mm; }'
    . '.omo-indicator-embed__chart .omo-stats-chart__line { fill:none; stroke:#2563eb; stroke-width:2.4; stroke-linecap:round; stroke-linejoin:round; }'
    . '.omo-indicator-embed__chart .omo-stats-chart__bar { fill:#2563eb; fill-opacity:.3; stroke:#2563eb; stroke-width:1; }'
    . '.omo-indicator-embed__chart .omo-stats-chart__reference { fill:none; stroke:#7b9aa8; stroke-width:1.7; stroke-dasharray:4 3; }'
    . '.omo-indicator-embed__chart .omo-stats-chart__point { fill:#2563eb; stroke:#ffffff; stroke-width:1.5; }'
    . '.omo-indicator-embed--overdue .omo-indicator-embed__chart .omo-stats-chart__line { stroke:#dc2626; }'
    . '.omo-indicator-embed--overdue .omo-indicator-embed__chart .omo-stats-chart__bar { fill:#dc2626; stroke:#dc2626; }'
    . '.omo-indicator-embed--overdue .omo-indicator-embed__chart .omo-stats-chart__point { fill:#dc2626; }'
    . '.omo-indicator-embed--warning .omo-indicator-embed__chart .omo-stats-chart__line { stroke:#ca8a04; }'
    . '.omo-indicator-embed--warning .omo-indicator-embed__chart .omo-stats-chart__bar { fill:#ca8a04; stroke:#ca8a04; }'
    . '.omo-indicator-embed--warning .omo-indicator-embed__chart .omo-stats-chart__point { fill:#ca8a04; }'
    . '.omo-indicator-embed__values { display:table-cell; width:28%; padding-left:3mm; color:#526b78; font-size:8pt; text-align:right; vertical-align:middle; }'
    . '.omo-indicator-embed__values b { display:block; color:#173b4d; font-size:11pt; }'
    . '.omo-indicator-embed__values time { display:block; font-size:8pt; }'
    . '.omo-indicator-embed__values em { display:block; margin-top:1mm; color:#b91c1c; font-style:normal; font-weight:bold; }'
    . '.omo-indicator-embed--warning .omo-indicator-embed__values em { color:#a16207; }'
    . 'a { color:#126b86; text-decoration:none; }'
    . '.footer { margin-top:9mm; padding-top:2mm; border-top:1px solid #d9e3e8; color:#78909b; font-size:8pt; text-align:right; }'
    . '</style></head><body>'
    . '<h1>' . omoDocumentsPdfEscape($title !== '' ? $title : ('PV #' . $documentId)) . '</h1>'
    . ($description !== '' ? '<p class="lead">' . nl2br(omoDocumentsPdfEscape($description)) . '</p>' : '')
    . ($metaHtml !== '' ? '<div class="meta">' . $metaHtml . '</div>' : '')
    . $document->getRenderedContentForCurrentViewer()
    . '<div class="footer">' . omoDocumentsPdfEscape(omoDocumentsPdfT('documents.pdf.footer.generated', ['date' => $generatedAt])) . '</div>'
    . '</body></html>';
    $documentHtml = omoDocumentsPdfConvertIndicatorSvgs($documentHtml);
    $projectRoot = dirname(__DIR__, 4);
    $documentHtml = omoDocumentsPdfEmbedLocalImages($documentHtml, $projectRoot);

    $previousErrorReporting = error_reporting();
    error_reporting($previousErrorReporting & ~E_DEPRECATED & ~E_USER_DEPRECATED);
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
    } finally {
        error_reporting($previousErrorReporting);
    }
} catch (\Throwable $exception) {
    error_log('PV PDF export failed for document #' . $documentId . ': ' . $exception->getMessage());
    omoDocumentsPdfFail(500, omoDocumentsPdfT('documents.pdf.error.generate'));
}

$filename = omoDocumentsPdfSafeFilename($title, $documentId);
header('X-Content-Type-Options: nosniff');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . addcslashes($filename, "\\\"") . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Content-Length: ' . strlen($pdfContent));
echo $pdfContent;
exit;
