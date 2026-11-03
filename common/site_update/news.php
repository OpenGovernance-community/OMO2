<?php

function siteUpdateNewsT(string $key, array $variables = []): string
{
    static $bundle = null;
    $sourceLang = [
        'title' => ['text' => 'Nouveautes du site - {date}', 'context' => 'Installed site update announcement title.'],
        'changes' => ['text' => 'Modifications de la mise a jour', 'context' => 'Update details popup title and help button label.'],
        'journal' => ['text' => 'Journal des nouveautes', 'context' => 'Release notes added by this update.'],
        'history' => ['text' => 'Descriptions GitHub et historique Git', 'context' => 'Legacy update announcement section heading.'],
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

/** Read the old concatenated snapshot without modifying saved news or fetching GitHub. */
function siteUpdateNewsLegacyContent(string $description): array
{
    $journalLabels = implode('|', array_map(static fn($label) => preg_quote($label, '~'), array_unique([siteUpdateNewsT('journal'), 'Journal des nouveautes'])));
    $historyLabels = implode('|', array_map(static fn($label) => preg_quote($label, '~'), array_unique([siteUpdateNewsT('history'), 'Descriptions GitHub et historique Git'])));
    if (!preg_match('~\A## (?:' . $journalLabels . ')\s*\R+(.*?)\R+## (?:' . $historyLabels . ')\s*\R+(.*)\z~s', trim($description), $parts)) {
        return ['description' => $description, 'journal' => ''];
    }
    $journal = trim($parts[1]);
    preg_match_all('/^## ([^\n]+) \(#\d+\)[ \t]*\R(.*?)(?=^## [^\n]+ \(#\d+\)[ \t]*$|\z)/ms', $parts[2], $pulls, PREG_SET_ORDER);
    $presentations = [];
    foreach ($pulls as $pull) {
        if (preg_match('/^Merge pull request #\d+\b/', trim($pull[1]))) { continue; }
        $prepared = preg_split('~^## ' . preg_quote(trim($pull[1]), '~') . '[ \t]*(?:\R|\z)~m', $pull[2], 2)[0];
        $prepared = trim($prepared);
        if ($prepared !== '' && $prepared !== trim($pull[1])) {
            $presentations[hash('sha256', $prepared)] = $prepared;
        }
    }
    return $presentations
        ? ['description' => implode("\n\n---\n\n", $presentations), 'journal' => $journal]
        : ['description' => $journal, 'journal' => ''];
}

function siteUpdateNewsRender(string $description, string $journal = ''): string
{
    if ($journal === '') {
        $content = siteUpdateNewsLegacyContent($description);
        $description = $content['description'];
        $journal = $content['journal'];
    }
    // Preserve the author's Markdown structure; only the shared HTML sanitizer transforms it.
    $html = '<div class="generic-rich-text">'
        . \dbObject\PropertyFormat::formattedTextToHtml($description, 'markdown') . '</div>';
    if (trim($journal) !== '') {
        $html .= '<details class="generic-accordion generic-help-text"><summary>'
            . htmlspecialchars(siteUpdateNewsT('journal'), ENT_QUOTES, 'UTF-8')
            . '</summary><div class="generic-accordion__content generic-rich-text">'
            . \dbObject\PropertyFormat::formattedTextToHtml($journal, 'markdown') . '</div></details>';
    }
    return '<div class="generic-section generic-section--stack">' . $html . '</div>';
}
