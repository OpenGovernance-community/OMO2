<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/object_visibility_selector.php';

use dbObject\Document;
use dbObject\Holon;
use dbObject\ObjectVisibility;

$sourceLang = [
    'documents.merge.error.invalid' => ['text' => 'Sélection invalide.', 'context' => 'Error shown when the HTML documents selected for merging are invalid.'],
    'documents.merge.field.title' => ['text' => 'Titre', 'context' => 'Label of the merged document title.'],
    'documents.merge.field.visibility' => ['text' => 'Lecture', 'context' => 'Label of the merged document read visibility selector.'],
    'documents.merge.field.edit_visibility' => ['text' => 'Modification', 'context' => 'Label of the merged document edit visibility selector.'],
    'documents.merge.visibility.help_no_holon' => ['text' => 'Ce document n’est pas lié à un espace. Les portées cercle et rôle ne sont pas disponibles.', 'context' => 'Visibility help shown when the merged document has no space context.'],
    'documents.merge.field.tags' => ['text' => 'Tags', 'context' => 'Heading above tags inherited by the merged document.'],
    'documents.merge.field.documents' => ['text' => 'Documents à fusionner', 'context' => 'Heading above the ordered source document list.'],
    'documents.merge.help.order' => ['text' => 'Utilisez les flèches ou glissez les documents pour définir l’ordre de leur contenu.', 'context' => 'Help text explaining how to reorder merge sources.'],
    'documents.merge.field.keep_sources' => ['text' => 'Conserver les documents source', 'context' => 'Checkbox controlling whether source documents remain after a merge.'],
    'documents.merge.help.sources_required' => ['text' => 'Certains documents source ne peuvent pas être supprimés.', 'context' => 'Help shown when source deletion is unavailable.'],
    'documents.merge.action.cancel' => ['text' => 'Annuler', 'context' => 'Button cancelling document merge.'],
    'documents.merge.action.submit' => ['text' => 'Fusionner', 'context' => 'Button creating the merged document.'],
    'documents.merge.action.remove_tag' => ['text' => 'Retirer le tag', 'context' => 'Accessible label prefix for removing a tag.'],
    'documents.merge.action.move_up' => ['text' => 'Monter', 'context' => 'Accessible label for moving a source document upward.'],
    'documents.merge.action.move_down' => ['text' => 'Descendre', 'context' => 'Accessible label for moving a source document downward.'],
    'documents.merge.document_fallback' => ['text' => 'Document', 'context' => 'Fallback source document title.'],
    'documents.merge.error.submit' => ['text' => 'Impossible de fusionner les documents.', 'context' => 'Fallback error when merged document creation fails.'],
];
$lang = omoLoadTranslationBundle('omo_documents_merge', $sourceLang);

function omoDocumentsMergeT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$rawIds = $_GET['ids'] ?? '';
$documentIds = is_array($rawIds) ? $rawIds : preg_split('/[,\s]+/', (string)$rawIds, -1, PREG_SPLIT_NO_EMPTY);
$mergeData = Document::getHtmlMergeEditorData(is_array($documentIds) ? $documentIds : array(), (int)commonGetCurrentUserId());
$visibilityOptions = ObjectVisibility::getVisibilityTypeOptions();
$disabledVisibilityTypes = array();
$visibilityHelpText = '';

if (($mergeData['status'] ?? false) === true) {
    $contextHolonId = (int)($mergeData['holonId'] ?? 0);
    if ($contextHolonId <= 0) {
        $disabledVisibilityTypes[ObjectVisibility::TYPE_CIRCLE] = true;
        $disabledVisibilityTypes[ObjectVisibility::TYPE_ROLE] = true;
        $visibilityHelpText = omoDocumentsMergeT('documents.merge.visibility.help_no_holon');
    } else {
        $contextHolon = new Holon();
        if ($contextHolon->load($contextHolonId)) {
            $contextHolonTypeId = (int)$contextHolon->get('IDtypeholon');
            if ($contextHolonTypeId !== 1) {
                $disabledVisibilityTypes[ObjectVisibility::TYPE_ROLE] = true;
            }
            if ($contextHolonTypeId !== 2 && (int)$contextHolon->getContainingCircleId(false) <= 0) {
                $disabledVisibilityTypes[ObjectVisibility::TYPE_CIRCLE] = true;
            }
        }
    }
}

if (($mergeData['status'] ?? false) !== true):
?>
<div class="generic-drawer-content generic-stack generic-stack--roomy">
    <div class="omo-empty-state"><?= omoApiEscape((string)($mergeData['text'] ?? omoDocumentsMergeT('documents.merge.error.invalid'))) ?></div>
</div>
<?php else: ?>
<form class="omo-document-merge generic-drawer-content generic-stack generic-stack--roomy" data-omo-document-merge-form>
    <label class="generic-form-field generic-stack generic-stack--compact">
        <span class="generic-form-label"><?= omoApiEscape(omoDocumentsMergeT('documents.merge.field.title')) ?></span>
        <input class="generic-form-control" type="text" maxlength="100" required data-omo-document-merge-title value="<?= omoApiEscape((string)$mergeData['title']) ?>">
    </label>

    <div class="omo-document-merge__visibility-fields generic-stack">
        <div class="generic-form-field">
            <?= commonRenderObjectVisibilitySelector(array(
                'inputName' => 'visibility_type',
                'fieldLabel' => omoDocumentsMergeT('documents.merge.field.visibility'),
                'ariaLabel' => omoDocumentsMergeT('documents.merge.field.visibility'),
                'selectedValue' => (string)$mergeData['visibilityType'],
                'optionLabels' => $visibilityOptions,
                'disabledValues' => $disabledVisibilityTypes,
                'idPrefix' => 'omo-document-merge-visibility',
                'hint' => $visibilityHelpText,
            )) ?>
        </div>
        <div class="generic-form-field">
            <?= commonRenderObjectVisibilitySelector(array(
                'inputName' => 'edit_visibility_type',
                'fieldLabel' => omoDocumentsMergeT('documents.merge.field.edit_visibility'),
                'ariaLabel' => omoDocumentsMergeT('documents.merge.field.edit_visibility'),
                'selectedValue' => (string)$mergeData['editVisibilityType'],
                'optionLabels' => $visibilityOptions,
                'disabledValues' => $disabledVisibilityTypes,
                'idPrefix' => 'omo-document-merge-edit-visibility',
                'hint' => $visibilityHelpText,
            )) ?>
        </div>
    </div>

    <section class="omo-document-merge__tag-section generic-stack generic-stack--compact">
        <h3 class="generic-card-title"><?= omoApiEscape(omoDocumentsMergeT('documents.merge.field.tags')) ?></h3>
        <div class="omo-document-merge__tags" data-omo-document-merge-tags></div>
    </section>

    <section class="generic-stack generic-stack--compact">
        <h3 class="generic-card-title"><?= omoApiEscape(omoDocumentsMergeT('documents.merge.field.documents')) ?></h3>
        <p class="generic-help-text"><?= omoApiEscape(omoDocumentsMergeT('documents.merge.help.order')) ?></p>
        <div class="omo-document-merge__documents generic-stack generic-stack--compact" data-omo-document-merge-documents></div>
    </section>

    <div class="omo-document-merge__footer generic-action-row">
        <div class="omo-document-merge__source-choice generic-stack generic-stack--compact">
            <label class="generic-checkbox">
                <input type="checkbox" value="1" checked data-omo-document-merge-keep-sources<?= empty($mergeData['canDeleteSources']) ? ' disabled' : '' ?>>
                <span><?= omoApiEscape(omoDocumentsMergeT('documents.merge.field.keep_sources')) ?></span>
            </label>
            <?php if (empty($mergeData['canDeleteSources'])): ?>
                <span class="generic-help-text"><?= omoApiEscape(omoDocumentsMergeT('documents.merge.help.sources_required')) ?></span>
            <?php endif; ?>
        </div>
        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-document-merge-cancel><?= omoApiEscape(omoDocumentsMergeT('documents.merge.action.cancel')) ?></button>
        <button type="submit" class="generic-action-button generic-action-button--main" data-omo-document-merge-submit><?= omoApiEscape(omoDocumentsMergeT('documents.merge.action.submit')) ?></button>
    </div>
    <div class="generic-feedback" data-omo-document-merge-feedback hidden></div>
</form>

<?= commonPageScriptTags('/omo/api/documents/merge.js', [
    'data' => $mergeData,
    'ui' => [
    'removeTag' => omoDocumentsMergeT('documents.merge.action.remove_tag'),
    'moveUp' => omoDocumentsMergeT('documents.merge.action.move_up'),
    'moveDown' => omoDocumentsMergeT('documents.merge.action.move_down'),
    'documentFallback' => omoDocumentsMergeT('documents.merge.document_fallback'),
    'submitError' => omoDocumentsMergeT('documents.merge.error.submit'),
],
]) ?>
<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/documents/merge.css') ?>">
<?php endif; ?>
