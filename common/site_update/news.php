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
    return '<div class="generic-section generic-rich-text">'
        . \dbObject\PropertyFormat::formattedTextToHtml($description, 'markdown') . '</div>';
}
