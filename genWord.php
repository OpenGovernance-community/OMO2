<?php
require_once __DIR__ . '/shared_functions.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Html;

function pvExportEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function pvExportSafeImageData(string $source): ?string
{
    if (!preg_match('/^data:image\/(png|jpeg|gif);base64,([A-Za-z0-9+\/=]+)$/i', trim($source), $matches)) {
        return null;
    }

    $imageBytes = base64_decode($matches[2], true);
    if (!is_string($imageBytes) || strlen($imageBytes) > 5 * 1024 * 1024) {
        return null;
    }

    $imageInfo = @getimagesizefromstring($imageBytes);
    if (!is_array($imageInfo)
        || !in_array($imageInfo['mime'] ?? '', ['image/png', 'image/jpeg', 'image/gif'], true)
        || (int)($imageInfo[0] ?? 0) < 1
        || (int)($imageInfo[1] ?? 0) < 1
        || (int)$imageInfo[0] * (int)$imageInfo[1] > 25000000
    ) {
        return null;
    }

    return 'data:' . $imageInfo['mime'] . ';base64,' . base64_encode($imageBytes);
}

function pvExportSafeHref(string $href): bool
{
    $href = trim($href);
    if ($href === '' || preg_match('/[\x00-\x20]/', $href)) {
        return false;
    }

    if ($href[0] === '#') {
        return true;
    }
    if ($href[0] === '/') {
        return !str_starts_with($href, '//');
    }

    $scheme = parse_url($href, PHP_URL_SCHEME);
    return $scheme === null
        ? !str_contains($href, ':')
        : in_array(strtolower((string)$scheme), ['http', 'https', 'mailto'], true);
}

function pvExportSafeStyle(string $style): string
{
    $allowedProperties = [
        'background-color',
        'color',
        'font-family',
        'font-size',
        'font-style',
        'font-weight',
        'margin-bottom',
        'margin-left',
        'margin-right',
        'margin-top',
        'text-align',
        'text-decoration',
        'text-indent',
        'vertical-align',
    ];
    $safeStyles = [];

    foreach (explode(';', $style) as $declaration) {
        $parts = explode(':', $declaration, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $property = strtolower(trim($parts[0]));
        $value = trim($parts[1]);
        if (!in_array($property, $allowedProperties, true)
            || $value === ''
            || strlen($value) > 120
            || preg_match('/[<>\\\\]|url\s*\(|expression\s*\(|@import/i', $value)
        ) {
            continue;
        }

        $safeStyles[] = $property . ': ' . $value;
    }

    return implode('; ', $safeStyles);
}

function pvExportCleanHtmlNode(DOMNode $node, array $allowedTags): void
{
    if ($node instanceof DOMComment || $node instanceof DOMProcessingInstruction) {
        $node->parentNode?->removeChild($node);
        return;
    }
    if (!$node instanceof DOMElement) {
        return;
    }

    $tagName = strtolower($node->tagName);
    if (in_array($tagName, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'video', 'audio', 'form', 'input', 'button', 'link', 'meta'], true)) {
        $node->parentNode?->removeChild($node);
        return;
    }

    if (!in_array($tagName, $allowedTags, true)) {
        foreach (iterator_to_array($node->childNodes) as $child) {
            pvExportCleanHtmlNode($child, $allowedTags);
        }
        $parent = $node->parentNode;
        if ($parent !== null) {
            while ($node->firstChild !== null) {
                $parent->insertBefore($node->firstChild, $node);
            }
            $parent->removeChild($node);
        }
        return;
    }

    if ($tagName === 'img') {
        $safeImage = pvExportSafeImageData((string)$node->getAttribute('src'));
        if ($safeImage === null) {
            $node->parentNode?->removeChild($node);
            return;
        }
        $node->setAttribute('src', $safeImage);
    }

    $allowedAttributes = ['style'];
    if ($tagName === 'a') {
        $allowedAttributes[] = 'href';
    } elseif ($tagName === 'img') {
        $allowedAttributes = array_merge($allowedAttributes, ['alt', 'height', 'src', 'width']);
    } elseif (in_array($tagName, ['td', 'th'], true)) {
        $allowedAttributes = array_merge($allowedAttributes, ['align', 'colspan', 'rowspan', 'valign']);
    } elseif ($tagName === 'ol') {
        $allowedAttributes[] = 'start';
    }

    $attributesToRemove = [];
    foreach ($node->attributes as $attribute) {
        $attributeName = strtolower($attribute->name);
        $attributeValue = trim($attribute->value);
        if (!in_array($attributeName, $allowedAttributes, true)) {
            $attributesToRemove[] = $attribute->name;
            continue;
        }

        if ($attributeName === 'href' && !pvExportSafeHref($attributeValue)) {
            $attributesToRemove[] = $attribute->name;
        } elseif ($attributeName === 'style') {
            $safeStyle = pvExportSafeStyle($attributeValue);
            if ($safeStyle === '') {
                $attributesToRemove[] = $attribute->name;
            } else {
                $node->setAttribute('style', $safeStyle);
            }
        } elseif (in_array($attributeName, ['height', 'width', 'colspan', 'rowspan', 'start'], true)) {
            if (!preg_match('/^[0-9]{1,4}$/', $attributeValue)) {
                $attributesToRemove[] = $attribute->name;
            }
        } elseif (in_array($attributeName, ['align', 'valign'], true)) {
            if (!in_array(strtolower($attributeValue), ['left', 'center', 'right', 'justify', 'top', 'middle', 'bottom'], true)) {
                $attributesToRemove[] = $attribute->name;
            }
        }
    }

    foreach ($attributesToRemove as $attributeName) {
        $node->removeAttribute($attributeName);
    }

    $children = [];
    foreach ($node->childNodes as $child) {
        $children[] = $child;
    }
    foreach ($children as $child) {
        pvExportCleanHtmlNode($child, $allowedTags);
    }
}

function pvExportCalloutStyles(): array
{
    // EasyPV uses h4/h5 as semantic blocks, with 30% green/yellow on white.
    return [
        'h4' => ['style' => 'Heading4', 'fill' => 'B3FFB3', 'icon' => 'thumb-up.png', 'label' => 'Decision'],
        'h5' => ['style' => 'Heading5', 'fill' => 'FFFFB3', 'icon' => 'clipboard.png', 'label' => 'Action'],
    ];
}

function pvExportSanitizeHtml(string $html, string $fontSize, string $format): string
{
    $allowedTags = [
        'a', 'b', 'blockquote', 'br', 'caption', 'code', 'del', 'div', 'em', 'font', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'hr', 'i', 'img', 'li', 'ol', 'p', 'pre', 's', 'span', 'strike', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'th',
        'thead', 'tr', 'u', 'ul',
    ];
    $document = new DOMDocument('1.0', 'UTF-8');
    $previousLibxmlState = libxml_use_internal_errors(true);
    try {
        $loaded = $document->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="omo-pv-export-root">' . $html . '</div></body></html>',
            LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlState);
    }
    if (!$loaded) {
        return '';
    }

    $root = null;
    foreach ($document->getElementsByTagName('div') as $div) {
        if ($div->getAttribute('id') === 'omo-pv-export-root') {
            $root = $div;
            break;
        }
    }
    if (!$root instanceof DOMElement) {
        return '';
    }

    $children = [];
    foreach ($root->childNodes as $child) {
        $children[] = $child;
    }
    foreach ($children as $child) {
        pvExportCleanHtmlNode($child, $allowedTags);
    }

    // Add trusted decoration only after sanitizing all user HTML.
    foreach (pvExportCalloutStyles() as $tag => $callout) {
        foreach ($root->getElementsByTagName($tag) as $block) {
            $block->setAttribute('style', $block->getAttribute('style')
                . ';font-family:Arial;font-size:' . $fontSize . 'pt;font-weight:bold;'
                . 'background-color:#' . $callout['fill'] . ';'
                . 'margin-top:6pt;margin-bottom:6pt;padding:4pt;page-break-inside:avoid;');
            $iconPath = __DIR__ . '/img/' . $callout['icon'];
            $icon = $document->createElement('img');
            // PHPWord embeds the fixed local asset; Dompdf receives embedded data.
            // No local path from the submitted document is ever accepted.
            $icon->setAttribute('src', $format === 'pdf'
                ? 'data:image/png;base64,' . base64_encode(file_get_contents($iconPath))
                : $iconPath);
            $icon->setAttribute('width', '21');
            $icon->setAttribute('height', '21');
            $icon->setAttribute('alt', $callout['label']);
            $icon->setAttribute('style', 'vertical-align:middle;');
            $block->insertBefore($document->createTextNode(' '), $block->firstChild);
            $block->insertBefore($icon, $block->firstChild);
        }
    }

    $cleanHtml = '';
    foreach ($root->childNodes as $child) {
        // PHPWord parses XML: serialize entities and empty elements as XHTML.
        $cleanHtml .= $document->saveXML($child);
    }

    return $cleanHtml;
}

function pvExportBuildHtml(array $data, string $fontSize, string $format): string
{
    $title = trim((string)($data['title'] ?? ''));
    $startTime = trim((string)($data['starttime'] ?? ''));
    $endTime = trim((string)($data['endtime'] ?? ''));
    $timeRange = $startTime !== '' && $endTime !== ''
        ? $startTime . '–' . $endTime
        : ($startTime !== '' ? $startTime : $endTime);
    $locationDetails = array_filter([
        trim((string)($data['location'] ?? '')),
        trim((string)($data['dateevent'] ?? '')),
        $timeRange,
    ], static fn(string $value): bool => $value !== '');

    $html = '<html><head><meta charset="UTF-8"/><style>'
        . 'body{font-family:Arial,sans-serif;font-size:' . $fontSize . 'pt;color:#202124;}'
        . 'h1{font-size:1.6em;font-weight:normal;margin:0 0 8pt;}'
        . 'h2{font-size:1.2em;font-weight:normal;margin:18pt 0 6pt;}'
        . '.pv-meta{color:#555;font-size:.9em;margin:0 0 18pt;}'
        . '.pv-footer{color:#666;font-size:.75em;margin-top:24pt;}'
        . 'img{max-width:100%;height:auto;}'
        . 'table{border-collapse:collapse;}td,th{border:1px solid #999;padding:4pt;}'
        . '</style></head><body>';

    $html .= '<h1>' . pvExportEscape($title !== '' ? $title : 'Procès-verbal') . '</h1>';
    if ($locationDetails !== []) {
        $html .= '<p class="pv-meta">' . pvExportEscape(implode(' · ', $locationDetails)) . '</p>';
    }

    $agendaItems = $data['oj'] ?? [];
    if (!is_array($agendaItems)) {
        $agendaItems = [];
    }

    foreach ($agendaItems as $item) {
        if (!is_array($item)) {
            continue;
        }
        $content = pvExportSanitizeHtml((string)($item['content'] ?? ''), $fontSize, $format);
        if (trim(strip_tags($content)) === '' && !str_contains($content, '<img')) {
            continue;
        }

        $headingParts = [];
        $who = trim((string)($item['who'] ?? ''));
        $itemTitle = trim((string)($item['title'] ?? ''));
        if ($who !== '') {
            $headingParts[] = pvExportEscape($who);
        }
        if ($itemTitle !== '') {
            $headingParts[] = '<strong>' . pvExportEscape($itemTitle) . '</strong>';
        }

        $duration = max(
            is_numeric($item['duration'] ?? null) ? (float)$item['duration'] : 0.0,
            is_numeric($item['realduration'] ?? null) ? (float)$item['realduration'] : 0.0
        );
        if ($duration > 0) {
            $durationLabel = floor($duration) === $duration
                ? (string)(int)$duration
                : rtrim(rtrim(number_format($duration, 2, '.', ''), '0'), '.');
            $headingParts[] = '(' . pvExportEscape($durationLabel) . "')";
        }

        if ($headingParts !== []) {
            $html .= '<h2>' . implode(' - ', $headingParts) . '</h2>';
        }
        $html .= $content . '<hr/>';
    }

    if (function_exists('appGetCurrentSiteBaseUrl')) {
        $siteUrl = preg_replace('#^https?://#i', '', appGetCurrentSiteBaseUrl());
        $html .= '<p class="pv-footer"><em>Généré sur ' . pvExportEscape($siteUrl) . '</em></p>';
    }

    return $html . '</body></html>';
}

function pvExportSendError(int $statusCode, string $message): never
{
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    echo $message;
    exit;
}

$rawData = (string)($_POST['data'] ?? '');
if ($rawData === '' || strlen($rawData) > 15 * 1024 * 1024) {
    pvExportSendError(400, 'Les données du procès-verbal sont absentes ou trop volumineuses.');
}

try {
    $data = json_decode($rawData, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    pvExportSendError(400, 'Les données du procès-verbal sont invalides.');
}
if (!is_array($data)) {
    pvExportSendError(400, 'Les données du procès-verbal sont invalides.');
}

$format = strtolower(trim((string)($_POST['format'] ?? '')));
if ($format === '') {
    $format = !empty($_POST['pdf']) ? 'pdf' : (!empty($_POST['odt']) ? 'odt' : 'docx');
}
if (!in_array($format, ['docx', 'odt', 'pdf'], true)) {
    pvExportSendError(400, 'Le format demandé n’est pas pris en charge.');
}

$availableFontSizes = ['8', '9', '10', '10.5', '11', '12', '14'];
$requestedFontSize = trim((string)($_POST['fontsize'] ?? '10.5'));
$fontSize = in_array($requestedFontSize, $availableFontSizes, true) ? $requestedFontSize : '10.5';
$filename = 'PV_' . (new DateTimeImmutable())->format('Ymd_His') . '.' . $format;
$outputPath = null;
$exportError = null;
$bufferLevel = ob_get_level();
ob_start();
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    // Vendor deprecations belong in the server log, never in a binary download.
    if (in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
        error_log('PV export: ' . $message . ' in ' . $file . ':' . $line);
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
try {
    $html = pvExportBuildHtml($data, $fontSize, $format);
    $outputPath = tempnam(sys_get_temp_dir(), 'omo-pv-doc-');
    if ($outputPath === false) {
        throw new RuntimeException('Unable to create temporary document.');
    }

    if ($format === 'pdf') {
        $dompdf = new Dompdf(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        if (file_put_contents($outputPath, $dompdf->output(), LOCK_EX) === false) {
            throw new RuntimeException('Unable to write PDF document.');
        }
    } else {
        Settings::setOutputEscapingEnabled(true);
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize((float)$fontSize);
        foreach (pvExportCalloutStyles() as $callout) {
            $phpWord->addParagraphStyle($callout['style'], [
                'basedOn' => 'Normal',
                'shading' => ['fill' => $callout['fill']],
                'spaceBefore' => 120,
                'spaceAfter' => 120,
                'keepNext' => false,
                'keepLines' => true,
            ]);
        }
        $section = $phpWord->addSection();
        Html::addHtml($section, $html, true);
        $writerType = $format === 'odt' ? 'ODText' : 'Word2007';
        IOFactory::createWriter($phpWord, $writerType)->save($outputPath);
        if ($format === 'odt') {
            require_once __DIR__ . '/common/document/odt.php';
            commonCompletePhpWordOdt($outputPath, (float)$fontSize);
        }
    }
    clearstatcache(true, $outputPath);
    if (!is_file($outputPath) || filesize($outputPath) < 1) {
        throw new RuntimeException('The document writer produced an empty file.');
    }
    if (ob_get_length() > 0) {
        throw new RuntimeException('Unexpected output during document generation.');
    }
} catch (Throwable $exception) {
    $exportError = $exception;
} finally {
    restore_error_handler();
    while (ob_get_level() > $bufferLevel) {
        ob_end_clean();
    }
    if ($exportError !== null && is_string($outputPath)) {
        @unlink($outputPath);
    }
}

if ($exportError !== null) {
    error_log('PV export failed: ' . $exportError->getMessage());
    pvExportSendError(500, 'La génération du document a échoué. Réessayez ou imprimez le PV depuis votre navigateur.');
}

$contentTypes = [
    'pdf' => 'application/pdf',
    'odt' => 'application/vnd.oasis.opendocument.text',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
try {
    header('Content-Type: ' . $contentTypes[$format]);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($outputPath));
    header('X-Content-Type-Options: nosniff');
    readfile($outputPath);
} finally {
    @unlink($outputPath);
}
