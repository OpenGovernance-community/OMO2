<?php
function assertDecisionSettingsPopup($condition, $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$modules = [
    'majority_judgment' => 'mj',
    'vote' => 'vote',
    'consent' => 'consent',
];

foreach ($modules as $module => $selectorPrefix) {
    $source = file_get_contents($root . '/omo/api/decision/modules/' . $module . '/module.php');
    assertDecisionSettingsPopup(
        is_string($source)
            && str_contains($source, 'data-omo-decision-' . $selectorPrefix . '-popup-owner-intermediate-results')
            && str_contains($source, 'data-omo-decision-' . $selectorPrefix . '-popup-participant-intermediate-results')
            && str_contains($source, 'data-omo-decision-' . $selectorPrefix . '-popup-participant-responses-editable'),
        'The ' . $module . ' settings popup must expose the three shared participation settings.'
    );
}

echo "Decision settings popup tests passed.\n";
