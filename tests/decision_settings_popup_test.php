<?php
function assertDecisionSettingsPopup($condition, $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$editorSource = file_get_contents($root . '/omo/api/decision/edit_shared.php');
assertDecisionSettingsPopup(
    is_string($editorSource)
        && str_contains($editorSource, 'data-omo-decision-general-settings-open')
        && str_contains($editorSource, 'data-omo-decision-general-hidden-owner-intermediate-results')
        && str_contains($editorSource, 'data-omo-decision-general-hidden-participant-responses-editable')
        && !str_contains($editorSource, 'data-omo-decision-general-hidden-consultation-proposals'),
    'The multi-question editor must keep only decision-level settings in its shared popup.'
);
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
            && str_contains($source, 'data-omo-decision-' . $selectorPrefix . '-popup-participant-responses-editable')
            && str_contains($source, 'data-omo-decision-' . $selectorPrefix . '-popup-consultation-proposals')
            && str_contains($source, 'data-omo-decision-embedded-question'),
        'The ' . $module . ' settings editor must keep question settings in each question.'
    );
}

echo "Decision settings popup tests passed.\n";
