<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/omo/api/resource_archives.php';
function omoLoadTranslationBundle($domain, $source) { return []; }
function t($key, $replace, $bundle, $source) { return strtr($source[$key]['text'], array_combine(array_map(fn ($k) => '{' . $k . '}', array_keys($replace)), array_values($replace))); }
function omoApiEscape($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function archiveAssert($ok, $message) { if (!$ok) throw new RuntimeException($message); }
$items = [];
foreach (['Older' => '2026-10-06 08:00:00', 'Earlier today' => '2026-10-07 09:00:00', '<Latest>' => '2026-10-07 15:00:00', 'Unknown' => null] as $name => $date) {
    $items[] = ['id' => count($items) + 1, 'title' => $name, 'date' => $date ? new DateTimeImmutable($date) : null, 'url' => '#stats-i1', 'metadata' => ['Circle & person']];
}
ob_start();
omoRenderResourceArchives($items, 'Empty', new DateTimeImmutable('2026-10-07'));
$html = ob_get_clean();
archiveAssert(strpos($html, '&lt;Latest&gt;') < strpos($html, 'Earlier today') && strpos($html, 'Earlier today') < strpos($html, 'Older'), 'Archives must sort newest first inside and across date groups.');
archiveAssert(str_contains($html, "Aujourd&#039;hui") && str_contains($html, 'Hier') && str_contains($html, 'Date inconnue'), 'Relative date groups and missing dates must render.');
archiveAssert(!str_contains($html, '<Latest>') && str_contains($html, 'Circle &amp; person'), 'Archive contents must be escaped.');
ob_start();
omoRenderResourceArchives([], 'Empty');
archiveAssert(str_contains(ob_get_clean(), 'Empty'), 'Empty filtered archives must explain the result.');
echo "Resource archive rendering tests passed.\n";
