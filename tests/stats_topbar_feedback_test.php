<?php
declare(strict_types=1);

function statsTopbarAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$editor = (string)file_get_contents($root . '/omo/api/stats/edit.php');
$index = (string)file_get_contents($root . '/omo/api/stats/index.php');

statsTopbarAssert(
    str_contains($editor, "window.omoNotify(message, 'error')"),
    'Indicator editor errors must use the topbar notification.'
);
statsTopbarAssert(
    substr_count($index, "window.omoNotify(message, 'error')") >= 2,
    'Indicator list actions must use the topbar notification without duplicate inline feedback.'
);

echo "stats_topbar_feedback_test: OK\n";
