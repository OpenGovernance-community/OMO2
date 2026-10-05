<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/omo/translations.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/registry.php';

// Supply the escaping callback normally provided by the authenticated API bootstrap.
function omoApiEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (function_exists('omoDecisionScheduleGetSourceLang')) {
    throw new RuntimeException('This test must start before a decision method loads the schedule helper.');
}

$omoDecisionInput = [];
$context = [
    'status' => true, 'intent' => 'manage', 'canManage' => true,
    'organizationId' => 0, 'targetHolonId' => 0,
    'organization' => null, 'decision' => null, 'effectiveHolon' => null,
    'previewLayout' => true,
];
ob_start();
require dirname(__DIR__) . '/omo/api/decision/edit_shared.php';
$html = ob_get_clean();

if (!function_exists('omoDecisionScheduleGetSourceLang') || $selectedMethod !== '' || $moduleDefinitionsToLoad !== []) {
    throw new RuntimeException('The editor must load scheduling before any method is selected.');
}
if (!str_contains($html, 'Choisir une m') || !str_contains($html, 'method=simple_vote')) {
    throw new RuntimeException('The new decision method chooser must render successfully.');
}
echo "Decision editor bootstrap: passed.\n";
