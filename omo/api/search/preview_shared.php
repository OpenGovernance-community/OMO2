<?php
require_once dirname(__DIR__, 3) . '/common/search_text.php';

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
        'content' => ['text' => 'Contenu', 'context' => 'Texte ou extraits du contenu dans un apercu'],
        'matches' => ['text' => 'Termes retrouves', 'context' => 'Badge sur une section contenant des termes de la recherche'],
        'points' => ['text' => 'Points du PV', 'context' => 'Points visibles de la reunion dans un apercu'],
        'matching_points' => ['text' => 'Points correspondant a la recherche', 'context' => 'Points du PV contenant les termes recherches'],
        'children' => ['text' => 'Sous-projets', 'context' => 'Sous-projets directs accessibles dans un apercu'],
        'documents' => ['text' => 'Documents associes', 'context' => 'Nombre de documents accessibles lies au projet'],
        'events' => ['text' => 'Dates associees', 'context' => 'Evenements accessibles lies au projet, passes et futurs'],
        'more' => ['text' => 'Autres elements a consulter dans la fiche complete', 'context' => 'Nombre d elements supplementaires non affiches dans l apercu'],
        'meeting' => ['text' => 'Reunion', 'context' => 'Reunion associee a un PV'],
        'username' => ['text' => 'Identifiant', 'context' => 'Identifiant du membre dans cette organisation'],
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
    $value = preg_replace('~<br\s*/?>|</(?:p|div|li|tr|h[1-6])>~i', "\n", $value);
    $value = preg_replace('~</(?:td|th|span|a)>~i', ' ', $value);
    return commonSearchNormalizeWhitespace(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

/** Plain text only: highlighting never runs on markup or HTML attributes. */
function omoSearchPreviewHighlight(string $text, string $query): string
{
    $terms = commonSearchQueryTerms($query);
    if (!$terms) { return omoApiEscape($text); }
    $parts = preg_split('/([\p{L}\p{N}][\p{L}\p{N}\p{M}]*)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($parts === false) { return omoApiEscape($text); }
    foreach ($parts as $index => &$part) {
        $match = false;
        if ($index % 2 === 1) {
            $word = commonSearchNormalizeText($part);
            foreach ($terms as $term) {
                if (commonSearchWordQuality($word, $term) > 0) { $match = true; break; }
            }
        }
        $part = ($match ? '<mark class="generic-search-highlight">' : '') . omoApiEscape($part) . ($match ? '</mark>' : '');
    }
    return implode('', $parts);
}

function omoSearchPreviewMatchScore(string $text, string $query): float
{
    $score = 0.0;
    foreach (commonSearchQueryTerms($query) as $term) {
        $score += commonSearchTextQuality($text, $term);
    }
    return $score;
}

/** Two non-overlapping windows, chosen by distinct terms rather than repetitions. */
function omoSearchPreviewExcerpt(string $text, string $query, int $limit = 1200): string
{
    $length = mb_strlen($text, 'UTF-8');
    if ($length <= $limit) { return $text; }
    $terms = commonSearchQueryTerms($query);
    $windows = [];
    if ($terms) {
        $pattern = '/(?:' . implode('|', array_map('commonBuildSearchMatchPattern', $terms)) . ')/iu';
        preg_match_all($pattern, $text, $hits, PREG_OFFSET_CAPTURE);
        $previousByte = 0;
        $position = 0;
        $windowSize = (int)floor($limit / 2);
        foreach ($hits[0] as [$match, $byte]) {
            $position += mb_strlen(substr($text, $previousByte, $byte - $previousByte), 'UTF-8');
            $previousByte = $byte;
            $start = max(0, $position - (int)floor($windowSize / 4));
            // Nearby hits share a candidate to keep long repetitive texts inexpensive.
            $bucket = (int)floor($start / 80);
            if (isset($windows[$bucket])) { continue; }
            $end = min($length, $start + $windowSize);
            while ($start > 0 && $start < $position && !preg_match('/\s/u', mb_substr($text, $start - 1, 1, 'UTF-8'))) { $start++; }
            while ($end < $length && $end > $position + mb_strlen($match, 'UTF-8') && !preg_match('/\s/u', mb_substr($text, $end, 1, 'UTF-8'))) { $end--; }
            $fragment = trim(mb_substr($text, $start, $end - $start, 'UTF-8'));
            $windows[$bucket] = ['start' => $start, 'end' => $end, 'text' => $fragment, 'score' => omoSearchPreviewMatchScore($fragment, $query)];
        }
    }
    if (!$windows) { return mb_strimwidth($text, 0, $limit, '...', 'UTF-8'); }
    usort($windows, static fn($a, $b) => $b['score'] <=> $a['score'] ?: $a['start'] <=> $b['start']);
    $selected = [array_shift($windows)];
    foreach ($windows as $window) {
        if ($window['end'] <= $selected[0]['start'] || $window['start'] >= $selected[0]['end']) {
            $selected[] = $window;
            break;
        }
    }
    usort($selected, static fn($a, $b) => $a['start'] <=> $b['start']);
    return implode("\n\n", array_map(static fn($window) => ($window['start'] > 0 ? '... ' : '') . $window['text'] . ($window['end'] < $length ? ' ...' : ''), $selected));
}

function omoSearchPreviewRender(array $preview, string $query = ''): string
{
    $blocks = array_merge($preview['sections'] ?? [], $preview['collections'] ?? []);
    foreach ($blocks as &$block) {
        $block['score'] = omoSearchPreviewMatchScore(omoSearchPreviewText($block['text'] ?? ''), $query);
        if (isset($block['items'])) {
            foreach ($block['items'] as &$item) {
                $item['score'] = omoSearchPreviewMatchScore(omoSearchPreviewText($item['title'] ?? '') . ' ' . omoSearchPreviewText($item['text'] ?? ''), $query);
                $block['score'] = max($block['score'], $item['score']);
            }
            unset($item);
            usort($block['items'], static fn($a, $b) => $b['score'] <=> $a['score']);
            if (!empty($block['matchingOnly']) && $block['score'] > 0) {
                $block['items'] = array_values(array_filter($block['items'], static fn($item) => $item['score'] > 0));
                $block['title'] = omoSearchPreviewT('matching_points');
            }
        }
    }
    unset($block);
    usort($blocks, static fn($a, $b) => $b['score'] <=> $a['score']);
    $fields = array_filter(array_map('omoSearchPreviewText', $preview['fields'] ?? []), static fn($value) => $value !== '');
    ob_start();
    $hasContent = false;
    ?>
    <div class="generic-drawer-content omo-search-preview__content">
        <?php if ($fields): $hasContent = true; ?>
            <div class="omo-search-preview__metadata">
                <?php foreach ($fields as $key => $value): ?>
                    <div class="generic-stack generic-stack--compact">
                        <span class="generic-meta-label"><?= omoApiEscape(omoSearchPreviewT($key)) ?></span>
                        <span class="generic-meta-value generic-meta-value--compact"><?= omoSearchPreviewHighlight($value, $query) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($preview['metrics'])): $hasContent = true; ?>
            <div class="generic-action-row generic-action-row--start">
                <?php foreach ($preview['metrics'] as $key => $count): ?>
                    <span class="generic-badge"><?= (int)$count ?> <?= omoApiEscape(omoSearchPreviewT($key)) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php foreach (array_slice($blocks, 0, 8) as $block): ?>
            <?php
            $text = omoSearchPreviewText($block['text'] ?? '');
            $items = $block['items'] ?? [];
            if ($text === '' && !$items) { continue; }
            $hasContent = true;
            ?>
            <section class="generic-section generic-stack generic-stack--compact">
                <div class="generic-action-row generic-action-row--start">
                    <h4 class="generic-card-title"><?= omoApiEscape($block['title']) ?></h4>
                    <?php if ($block['score'] > 0): ?>
                        <span class="generic-badge"><?= omoApiEscape(omoSearchPreviewT('matches')) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($text !== ''): ?>
                    <div class="omo-search-preview__text generic-description"><?= omoSearchPreviewHighlight(omoSearchPreviewExcerpt($text, $query), $query) ?></div>
                <?php endif; ?>
                <?php foreach (array_slice($items, 0, 5) as $item): ?>
                    <article class="generic-soft-panel generic-soft-panel--stack<?= $item['score'] > 0 ? ' generic-soft-panel--summary' : '' ?>">
                        <h5 class="generic-card-title generic-card-title--small"><?= omoSearchPreviewHighlight(omoSearchPreviewText($item['title'] ?? ''), $query) ?></h5>
                        <?php if (omoSearchPreviewText($item['meta'] ?? '') !== ''): ?>
                            <div class="generic-meta"><?= omoSearchPreviewHighlight(omoSearchPreviewText($item['meta']), $query) ?></div>
                        <?php endif; ?>
                        <?php if (omoSearchPreviewText($item['text'] ?? '') !== ''): ?>
                            <div class="omo-search-preview__text generic-description"><?= omoSearchPreviewHighlight(omoSearchPreviewExcerpt(omoSearchPreviewText($item['text']), $query, 700), $query) ?></div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php if (count($items) > 5): ?>
                    <p class="generic-meta"><?= count($items) - 5 ?> — <?= omoApiEscape(omoSearchPreviewT('more')) ?></p>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
        <?php if (count($blocks) > 8): ?>
            <p class="generic-meta"><?= count($blocks) - 8 ?> — <?= omoApiEscape(omoSearchPreviewT('more')) ?></p>
        <?php endif; ?>
        <?php if (isset($preview['chart'])): $hasContent = true; ?>
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
