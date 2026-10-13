<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/omo/translations.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/export_common.php';

use dbObject\{DbObject, Holon, Authority, DecisionGovernanceAction, DecisionProposal, PropertyFormat};

function descriptionCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $holon = new Holon();
    $holon->set('name', 'Description fixture');
    $holon->set('IDtypeholon', 2);
    descriptionCheck(!empty($holon->save()['status']), 'Create fixture context.');
    $authority = new Authority();
    $authority->set('IDholon', $holon->getId());
    $authority->set('label', 'Budget & finances');
    descriptionCheck(!empty($authority->save()['status']), 'Create authority fixture.');
    $property = ['id' => 1, 'name' => 'Authority', 'formatId' => 2, 'listItemType' => 'authority',
        'value' => json_encode([(int)$authority->getId()])];
    $before = ['name' => 'Role', 'full_name' => '', 'editor_payload' => ['properties' => [$property]]];
    $after = ['name' => 'Role', 'full_name' => 'Test', 'editor_payload' => ['properties' => [array_replace($property, ['value' => ''])]]];
    $summary = DecisionGovernanceAction::buildRoleUpdateDescription($before, $after);
    descriptionCheck(str_contains($summary, '<em>vide</em> &rarr; Test'), 'Empty previous values are italic.');
    descriptionCheck(str_contains($summary, 'Budget &amp; finances &rarr; <em>vide</em>'), 'Authority IDs resolve to labels and empty next values are italic.');
    descriptionCheck(!str_contains($summary, '[' . $authority->getId() . ']'), 'No raw authority ID list.');
    descriptionCheck(str_contains(DecisionGovernanceAction::buildRuleUpdateDescription(
        ['IDauthority' => $authority->getId()], ['IDauthority' => null]),
        'Budget &amp; finances &rarr; <em>vide</em>'), 'Rule attachment summaries also resolve authority labels.');
    foreach (['[]', json_encode([['id' => $authority->getId(), 'delete' => true]])] as $deleted) {
        $after['editor_payload']['properties'][0]['value'] = $deleted;
        $after['editor_payload']['properties'][0]['displayValue'] = 'Stale cached label';
        descriptionCheck(DecisionGovernanceAction::buildRoleUpdateDescription($before, $after) === $summary,
            'Empty lists, deletion markers and stale display metadata render consistently.');
    }
    $after['editor_payload']['properties'] = [];
    descriptionCheck(DecisionGovernanceAction::buildRoleUpdateDescription($before, $after) === $summary,
        'Removing a property still shows its previous value.');
    $after['editor_payload']['properties'] = [array_replace($property,
        ['value' => json_encode([['id' => 0, 'label' => 'New authority']])])];
    descriptionCheck(str_contains(DecisionGovernanceAction::buildRoleUpdateDescription($before, $after),
        'Budget &amp; finances &rarr; New authority'), 'Unsaved authority labels are retained.');
    $html = '<p>Summary <strong>bold &amp; clear</strong></p><ul><li>Before: <em>vide</em></li><li>After: valid</li></ul>';
    $plain = "Summary bold & clear\nBefore: vide\nAfter: valid";
    descriptionCheck(DecisionProposal::descriptionToPlainText($html) === $plain, 'Plain text preserves block boundaries and decodes entities.');
    $rendered = omoDecisionRenderProposalSupplementHtml($html . '<script>alert(1)</script>', null,
        static fn ($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
    descriptionCheck(str_contains($rendered, '<strong>bold &amp; clear</strong>') && str_contains($rendered, '<em>vide</em>'),
        'Shared consultation, ballot and result renderer preserves formatting.');
    descriptionCheck(!str_contains($rendered, '<script>') && !str_contains($rendered, '&lt;p&gt;'), 'Render safe HTML without visible tags.');
    $csv = omoDecisionExportFlattenCsvRow(['detail_question' => $html], '', '');
    descriptionCheck($csv[11] === $plain, 'CSV descriptions contain readable text.');
    descriptionCheck(str_contains(PropertyFormat::sanitizeHtml($summary), '<em>vide</em>'), 'Italic empty marker survives persistence sanitization.');
    echo "decision_proposal_description_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
