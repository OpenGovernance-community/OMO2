<?php
function assertDecisionDuplicateSource($condition, $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$indexSource = file_get_contents($root . '/omo/api/decision/index.php');
$contextSource = file_get_contents($root . '/omo/api/decision/modules/context.php');
$editorSource = file_get_contents($root . '/omo/api/decision/edit_shared.php');
$multiSaveSource = file_get_contents($root . '/omo/api/decision/save_multi.php');

assertDecisionDuplicateSource(
    is_string($indexSource) && str_contains($indexSource, "'decisions.index.action.duplicate'") && str_contains($indexSource, "'duplicate_id' => \$decisionId") && str_contains($indexSource, "'behavior' => 'direct'"),
    'Decision cards must expose the duplicate action.'
);
assertDecisionDuplicateSource(
    is_string($contextSource) && str_contains($contextSource, '$duplicateDecisionId') && str_contains($contextSource, "'duplicateDecision' => \$duplicateDecision"),
    'The editor context must load a duplication source separately from the edited decision.'
);
assertDecisionDuplicateSource(
    is_string($editorSource) && str_contains($editorSource, '$isDuplicate ? 0') && str_contains($editorSource, '$isDuplicate ? \'\' :'),
    'Duplicate editors must submit no existing decision identifier and must clear dates.'
);
assertDecisionDuplicateSource(
    is_string($multiSaveSource) && str_contains($multiSaveSource, '$isCreatingDecision') && str_contains($multiSaveSource, "['group_action'] = 'create'"),
    'The multi-question saver must create the decision only when its first question is saved.'
);

echo "Decision duplicate source tests passed.\n";
