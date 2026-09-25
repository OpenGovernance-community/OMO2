<?php

function omoSearchPreviewT(string $key): string
{
    static $bundle;
    $sourceLang = [
        'preview' => ['text' => 'Apercu', 'context' => 'Recherche : ouvrir une vue condensee'],
        'open' => ['text' => 'Ouvrir', 'context' => 'Recherche : ouvrir la fiche complete'],
        'close' => ['text' => 'Fermer', 'context' => 'Fermer uniquement l apercu'],
        'loading' => ['text' => 'Chargement de l apercu...', 'context' => 'Recherche'],
        'error' => ['text' => 'Apercu indisponible. Cet objet a peut-etre ete supprime ou son acces a change.', 'context' => 'Recherche'],
        'retry' => ['text' => 'Reessayer', 'context' => 'Recherche'],
        'empty' => ['text' => 'Aucune information complementaire.', 'context' => 'Apercu de recherche'],
        'summary' => ['text' => 'Resume', 'context' => 'Apercu de recherche'],
        'context' => ['text' => 'Contexte', 'context' => 'Apercu de recherche'],
        'tags' => ['text' => 'Tags', 'context' => 'Apercu de document'],
        'format' => ['text' => 'Format', 'context' => 'Apercu de document'],
        'created' => ['text' => 'Creation', 'context' => 'Date de creation'],
        'updated' => ['text' => 'Mise a jour', 'context' => 'Date de modification'],
        'author' => ['text' => 'Auteur', 'context' => 'Apercu de document'],
        'status' => ['text' => 'Statut', 'context' => 'Apercu de recherche'],
        'responsible' => ['text' => 'Responsable', 'context' => 'Apercu de recherche'],
        'frequency' => ['text' => 'Frequence', 'context' => 'Apercu de recherche'],
        'schedule' => ['text' => 'Planification', 'context' => 'Apercu de recherche'],
        'start' => ['text' => 'Debut', 'context' => 'Apercu de recherche'],
        'end' => ['text' => 'Fin', 'context' => 'Apercu de recherche'],
        'location' => ['text' => 'Lieu', 'context' => 'Apercu de calendrier'],
        'text' => ['text' => 'Texte', 'context' => 'Apercu de regle'],
        'intention' => ['text' => 'Intention', 'context' => 'Apercu de regle'],
        'authority' => ['text' => 'Autorite', 'context' => 'Apercu de regle'],
        'rating' => ['text' => 'Note de fiabilite', 'context' => 'FAQ : score de fiabilite pondere, en pourcentage'],
        'votes' => ['text' => 'Votes', 'context' => 'Apercu FAQ'],
        'unrated' => ['text' => 'Pas encore evaluee', 'context' => 'FAQ sans vote'],
        'chart' => ['text' => 'Evolution', 'context' => 'Graphique d indicateur'],
        'steps' => ['text' => 'Etapes', 'context' => 'Apercu de processus'],
        'trigger' => ['text' => 'Declenchement', 'context' => 'Apercu de processus'],
        'email' => ['text' => 'E-mail', 'context' => 'Apercu de membre'],
        'presentation' => ['text' => 'Presentation', 'context' => 'Apercu de membre'],
        'skills' => ['text' => 'Competences', 'context' => 'Apercu de membre'],
        'tutorial' => ['text' => 'Parcours', 'context' => 'Apercu de tutoriel'],
    ];
    $bundle ??= omoLoadTranslationBundle('omo_search_preview', $sourceLang);
    return t($key, [], $bundle, $sourceLang);
}

function omoSearchPreviewText($value): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('d.m.Y H:i');
    }
    if (!is_scalar($value)) {
        return '';
    }
    $value = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', '', (string)$value);
    $value = preg_replace('~<br\s*/?>|</(?:p|div|li|h[1-6])>~i', "\n", $value);
    return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function omoSearchPreviewRender(array $preview): string
{
    ob_start();
    $hasContent = false;
    ?>
    <div class="generic-drawer-content omo-search-preview__content">
        <?php foreach (($preview['fields'] ?? []) as $key => $value): ?>
            <?php $value = omoSearchPreviewText($value); if ($value === '') { continue; } ?>
            <?php $hasContent = true; ?>
            <div class="generic-meta">
                <span class="generic-meta-label"><?= omoApiEscape(omoSearchPreviewT($key)) ?></span>
                <span class="generic-meta-value"><?= omoApiEscape($value) ?></span>
            </div>
        <?php endforeach; ?>
        <?php foreach (($preview['sections'] ?? []) as $section): ?>
            <?php $text = omoSearchPreviewText($section['text'] ?? ''); if ($text === '') { continue; } ?>
            <?php $hasContent = true; $text = mb_strimwidth($text, 0, 2400, '...', 'UTF-8'); ?>
            <section class="generic-section">
                <h4 class="generic-card-title"><?= omoApiEscape($section['title']) ?></h4>
                <div class="omo-search-preview__text"><?= omoApiEscape($text) ?></div>
            </section>
        <?php endforeach; ?>
        <?php if (isset($preview['chart'])): ?>
            <?php $hasContent = true; ?>
            <section class="generic-section omo-stats">
                <h4 class="generic-card-title"><?= omoApiEscape(omoSearchPreviewT('chart')) ?></h4>
                <?= $preview['chart'] /* SVG generated by the existing indicator renderer. */ ?>
            </section>
        <?php endif; ?>
        <?php if (!$hasContent): ?>
            <p><?= omoApiEscape(omoSearchPreviewT('empty')) ?></p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function omoSearchPreviewSection(string $key, $value): array
{
    return ['title' => omoSearchPreviewT($key), 'text' => $value];
}
