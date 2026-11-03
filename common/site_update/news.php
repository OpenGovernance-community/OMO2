<?php

function siteUpdateNewsT(string $key, array $variables = []): string
{
    static $bundle = null;
    $sourceLang = [
        'title' => ['text' => 'Nouveautes du site - {date}', 'context' => 'Installed site update announcement title.'],
        'changes' => ['text' => 'Modifications de la mise a jour', 'context' => 'Update details popup title and help button label.'],
        'fallback' => ['text' => 'Certaines descriptions GitHub sont absentes ou indisponibles. Les messages Git et les ajouts du journal des nouveautes completent la liste.', 'context' => 'Update details fallback explanation.'],
        'journal' => ['text' => 'Journal des nouveautes', 'context' => 'Release notes added by this update.'],
        'history' => ['text' => 'Descriptions GitHub et historique Git', 'context' => 'Detailed pull request and commit descriptions.'],
        'empty' => ['text' => 'Aucune description disponible.', 'context' => 'Update details without a description.'],
        'missing' => ['text' => 'Cette news est introuvable.', 'context' => 'Unknown update announcement.'],
        'login' => ['text' => 'Connectez-vous pour consulter les nouveautes.', 'context' => 'Update announcement login requirement.'],
    ];
    if ($bundle === null) {
        $bundle = function_exists('omoLoadTranslationBundle') ? omoLoadTranslationBundle('site_update_news', $sourceLang) : [];
    }
    if (function_exists('t')) { return t($key, $variables, $bundle, $sourceLang); }
    return strtr($sourceLang[$key]['text'] ?? $key, array_combine(
        array_map(static fn($name) => '{' . $name . '}', array_keys($variables)), array_values($variables)) ?: []);
}

function siteUpdateNewsRender(string $description): string
{
    // PR descriptions sometimes copy a journal entry without its first list marker.
    $description = preg_replace('/^(?![-+*#>\s])([^\n]+\(\d{4}-\d{2}-\d{2}\)\s*:[^\n]*)$/m', '- $1', $description);
    $html = \dbObject\PropertyFormat::formattedTextToHtml($description, 'markdown');
    return '<div class="generic-section generic-rich-text">'
        . siteUpdateNewsDeduplicateHtml($html) . '</div>';
}

/** Clean the shared display, including immutable news saved by older versions. */
function siteUpdateNewsDeduplicateHtml(string $html): string
{
    $document = new \DOMDocument('1.0', 'UTF-8');
    $previousErrors = libxml_use_internal_errors(true);
    try {
        $document->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>', LIBXML_NONET);
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
    }
    $root = $document->getElementsByTagName('body')->item(0);
    if (!$root) { return $html; }
    $textKey = static fn(string $text): string => trim((string)preg_replace('/[\s\p{Z}]+/u', ' ', $text));
    $contentKey = static function (\DOMElement $node) use ($textKey): string {
        $references = [];
        foreach ($node->getElementsByTagName('a') as $link) { $references[] = $link->getAttribute('href'); }
        foreach ($node->getElementsByTagName('img') as $image) { $references[] = 'image:' . $image->getAttribute('src'); }
        return $textKey($node->textContent) . ($references ? "\n" . implode("\n", $references) : '');
    };
    $sections = [['heading' => null, 'body' => [], 'key' => '']];
    foreach (iterator_to_array($root->childNodes) as $node) {
        if ($node instanceof \DOMElement && preg_match('/^h[1-6]$/', $node->tagName)) {
            $key = $textKey((string)preg_replace('/\s*\(#\d+\)\s*$/', '', $node->textContent));
            $sections[] = ['heading' => $node, 'body' => [], 'key' => $key];
        } elseif ($node instanceof \DOMElement || trim($node->textContent) !== '') {
            $sections[array_key_last($sections)]['body'][] = $node;
        }
    }
    $describedTitles = [];
    foreach ($sections as $section) {
        if ($section['body']) { $describedTitles[$section['key']] = true; }
    }
    $seenContent = [];
    $seenTitles = [];
    $result = '';
    foreach ($sections as $section) {
        $body = '';
        foreach ($section['body'] as $node) {
            if (!($node instanceof \DOMElement)) {
                $body .= $document->saveHTML($node);
                continue;
            }
            if (in_array($node->tagName, ['ul', 'ol'], true)) {
                foreach (iterator_to_array($node->childNodes) as $item) {
                    if (!($item instanceof \DOMElement) || $item->tagName !== 'li') { continue; }
                    $key = $contentKey($item);
                    if (isset($seenContent[$key])) { $node->removeChild($item); }
                    else { $seenContent[$key] = true; }
                }
                if ($node->getElementsByTagName('li')->length === 0) { continue; }
            } elseif ($node->tagName === 'p') {
                $key = $contentKey($node);
                if (isset($seenContent[$key])) { continue; }
                $seenContent[$key] = true;
            }
            $body .= $document->saveHTML($node);
        }
        $heading = $section['heading'];
        $key = $section['key'];
        $redundant = $body === '' && ($section['body'] || isset($seenTitles[$key])
            || isset($describedTitles[$key]) || $key === siteUpdateNewsT('history'));
        if ($heading) { $seenTitles[$key] = true; }
        if ($redundant) { continue; }
        $result .= ($heading ? $document->saveHTML($heading) : '') . $body;
    }
    return $result;
}
