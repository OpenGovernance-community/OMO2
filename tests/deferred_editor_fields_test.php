<?php
declare(strict_types=1);

// Isolated rendering test: no database or translation service needed.
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/project.class.php';
require_once dirname(__DIR__) . '/common/choice/deferred-editor-fields.php';

function omoLoadTranslationBundle(string $domain, array $source): array { return $source; }
function t(string $key, array $variables, array $bundle, array $source): string { return $source[$key]['text']; }
function assertEditorFields(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

assertEditorFields(omoDeferredEditorT('save') === 'Enregistrer la modification', 'Shared editors must name individual changes modifications.');

ob_start();
omoDeferredEditorRenderFields('rule', [
    'title' => 'Règle "test"', 'intention' => '<p>Intention conservée</p>',
    'description' => '</textarea><script>unsafe</script>',
    'review_date' => '2026-11-01', 'expiration_date' => '2027-01-01',
]);
$ruleHtml = ob_get_clean();
assertEditorFields(str_contains($ruleHtml, 'Règle &quot;test&quot;'), 'Titles must be initialized and escaped.');
assertEditorFields(str_contains($ruleHtml, '&lt;p&gt;Intention conservée&lt;/p&gt;'), 'Rich intention must survive textarea initialization.');
assertEditorFields(!str_contains($ruleHtml, '<script>'), 'Field content must not break out of textareas.');
assertEditorFields(str_contains($ruleHtml, 'value="2026-11-01"'), 'Rule dates must be initialized.');
assertEditorFields(str_contains($ruleHtml, 'generic-form-stack'), 'Shared fields must use the common form spacing.');

ob_start();
omoDeferredEditorRenderFields('project', [
    'title' => 'Projet', 'status' => \dbObject\Project::STATUS_IN_PROGRESS,
    'project_size' => 'L', 'priority' => 2, 'importance' => 4,
    'planned_start_date' => new DateTimeImmutable('2026-10-05'),
]);
$projectHtml = ob_get_clean();
assertEditorFields(str_contains($projectHtml, 'value="L" selected'), 'Project size must retain its value.');
assertEditorFields(str_contains($projectHtml, 'value="2" selected>P2'), 'Priority must retain its value.');
assertEditorFields(str_contains($projectHtml, 'value="4" selected>4/5'), 'Importance must retain its value.');
assertEditorFields(str_contains($projectHtml, 'value="2026-10-05"'), 'Date objects must be formatted for HTML date inputs.');

echo "deferred_editor_fields_test: OK\n";
