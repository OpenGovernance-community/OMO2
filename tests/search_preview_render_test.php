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
previewAssert(omoSearchPreviewText('<p>Premier</p><p>Second<br>Troisieme</p>') === "Premier\nSecond\nTroisieme", 'Rich text paragraph boundaries must survive.');

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
echo "search_preview_render_test: OK\n";
