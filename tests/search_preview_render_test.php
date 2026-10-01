<?php
declare(strict_types=1);

// Exercise the shared renderer without a database or a translation service.
function omoLoadTranslationBundle($key, $sourceLang) { return []; }
function t($key, $replace, $bundle, $sourceLang) { return $sourceLang[$key]['text']; }
function omoApiEscape($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
require dirname(__DIR__) . '/omo/api/search/preview_shared.php';

function previewAssert(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

foreach ([new DateTime('2026-09-25 12:30'), new DateTimeImmutable('2026-09-25 12:30')] as $date) {
    previewAssert(omoSearchPreviewText($date) === '25.09.2026 12:30', 'Mutable and immutable dates must render safely.');
}
previewAssert(omoSearchPreviewText(null) === '', 'Missing values must be empty.');
previewAssert(omoSearchPreviewText(new stdClass()) === '', 'Unexpected objects must never be cast to strings.');
foreach (['&#13;', '&#013;', '&#xD;', '&#x0d;', '&amp;#13;', '&amp;amp;#xD;'] as $break) {
    previewAssert(omoSearchPreviewText('Premier' . $break . ' ' . $break . ' Second') === "Premier\n\nSecond", 'Encoded carriage returns must become real paragraph breaks: ' . $break);
}
previewAssert(omoSearchPreviewText("Premier&#13;&#10;Second\r\nTroisieme") === "Premier\nSecond\nTroisieme", 'CRLF must not create extra empty lines.');
previewAssert(omoSearchPreviewText('Premier&#13; &#13; &#13; &#13;Second') === "Premier\n\nSecond", 'Repeated empty lines must stay compact.');
previewAssert(commonSearchWords('budget&#13;&#13;factures', true) === ['budget', 'factures'], 'Encoded whitespace must not add fake numeric search terms.');
previewAssert(commonSearchNormalizeWhitespace('&amp;lt;img src=x&amp;gt;') === '&amp;lt;img src=x&amp;gt;', 'Whitespace cleanup must not decode arbitrary markup.');
previewAssert(omoSearchPreviewText('<p>Premier</p><p>Second<br>Troisieme</p>') === "Premier\nSecond\nTroisieme", 'Rich text paragraph boundaries must survive.');
previewAssert(str_contains(omoSearchPreviewText('<table><tr><td>Budget</td><td>2026</td></tr></table>'), 'Budget 2026'), 'Table cells must not concatenate words.');

$html = omoSearchPreviewRender([
    'fields' => ['author' => '&lt;img src=x onerror=alert(1)&gt;', 'votes' => 0, 'created' => new DateTimeImmutable('2026-09-25 12:30')],
    'sections' => [['title'=>'<img src=x onerror=alert(1)>', 'text'=>'<script>alert(1)</script><p>Texte &amp; contexte</p>']],
]);
previewAssert(!str_contains($html, '<img') && !str_contains($html, '<script'), 'Content and headings must not inject HTML.');
previewAssert(str_contains($html, 'Texte &amp; contexte'), 'Entities must be escaped exactly once.');
previewAssert(str_contains($html, '>0</span>'), 'A zero rating must not be hidden as an empty value.');
previewAssert(str_contains($html, '25.09.2026 12:30'), 'Metadata must format dates.');
previewAssert(str_contains(omoSearchPreviewRender([]), omoSearchPreviewT('empty')), 'Objects without metadata need an empty state.');
$longHtml = omoSearchPreviewRender(['sections'=>[['title'=>'Long', 'text'=>str_repeat('abc ', 2000)]]]);
previewAssert(strlen($longHtml) < 4000 && str_contains($longHtml, '...'), 'Long descriptions must remain condensed.');
$query = 'validation factures';
$longText = str_repeat('Introduction sans rapport. ', 100) . 'Validation des factures recurrentes. ' . str_repeat('Conclusion generale. ', 100);
$excerpt = omoSearchPreviewExcerpt($longText, $query);
previewAssert(str_contains($excerpt, 'Validation des factures'), 'A late match must survive excerpt selection.');
previewAssert(mb_strlen($excerpt) < 1250, 'Passages must stay condensed.');
$twoPassages = omoSearchPreviewExcerpt('Validation du budget. ' . str_repeat('Autre contenu. ', 200) . 'Reglement des factures.', $query);
previewAssert(str_contains($twoPassages, 'Validation') && str_contains($twoPassages, 'factures'), 'Separate matching passages must be discoverable.');
previewAssert(omoSearchPreviewHighlight('Gestions et gestion', 'gestion') === '<mark class="generic-search-highlight">Gestions</mark> et <mark class="generic-search-highlight">gestion</mark>', 'Highlight whole plural variants.');
$safeHighlight = omoSearchPreviewHighlight('<img src=x> & budget', 'img budget');
previewAssert(!str_contains($safeHighlight, '<img') && str_contains($safeHighlight, '&amp;'), 'Highlighted text must remain escaped.');
$accentedHighlight = omoSearchPreviewHighlight("R\u{00e8}gles et re\u{0300}gles", 'regles');
previewAssert(substr_count($accentedHighlight, '<mark') === 2, 'Precomposed and decomposed accents must match.');
previewAssert(!str_contains(omoSearchPreviewHighlight('phrase 20260', 'RH 2026'), '<mark'), 'Acronyms and numbers require whole words.');

$html = omoSearchPreviewRender([
    'sections' => [omoSearchPreviewSection('summary', 'Introduction'), omoSearchPreviewSection('content', $longText)],
    'collections' => [['title' => 'Points', 'matchingOnly' => true, 'items' => [
        ['title' => 'Point sans rapport', 'text' => 'Autre'],
        ['title' => 'Validation', 'text' => 'Les factures sont examinees.'],
    ]]],
], $query);
previewAssert(strpos($html, '>Contenu</h4>') < strpos($html, '>Resume</h4>'), 'Matching content must precede unrelated summary.');
previewAssert(!str_contains($html, 'Point sans rapport'), 'When PV points match, show the relevant points.');
previewAssert(str_contains($html, omoSearchPreviewT('matching_points')), 'Identify the matching PV points.');
$skills = [];
for ($i = 0; $i < 8; $i++) { $skills[] = ['title' => 'Competence ' . $i, 'text' => 'Autre domaine']; }
$skills[] = ['title' => 'Validation des factures', 'text' => 'Competence decisive'];
$html = omoSearchPreviewRender(['collections' => [['title' => 'Competences', 'items' => $skills]], 'metrics' => ['documents' => 0]], $query);
previewAssert(str_contains($html, 'Competence decisive'), 'A matching skill must rise above the collection limit.');
previewAssert(substr_count($html, '<article') === 5 && str_contains($html, omoSearchPreviewT('more')), 'Limit collections while indicating omitted items.');
previewAssert(str_contains($html, '0 Documents associes'), 'Keep zero associated counts visible.');

echo "search_preview_render_test: OK\n";
