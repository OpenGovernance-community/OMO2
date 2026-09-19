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

<script>
(() => {
const data = <?= json_encode($mergeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const ui = <?= json_encode([
    'removeTag' => omoDocumentsMergeT('documents.merge.action.remove_tag'),
    'moveUp' => omoDocumentsMergeT('documents.merge.action.move_up'),
    'moveDown' => omoDocumentsMergeT('documents.merge.action.move_down'),
    'documentFallback' => omoDocumentsMergeT('documents.merge.document_fallback'),
    'submitError' => omoDocumentsMergeT('documents.merge.error.submit'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const form = document.querySelector('[data-omo-document-merge-form]');
if (!form) return;
const titleInput = form.querySelector('[data-omo-document-merge-title]');
const tagsHost = form.querySelector('[data-omo-document-merge-tags]');
const documentsHost = form.querySelector('[data-omo-document-merge-documents]');
const feedback = form.querySelector('[data-omo-document-merge-feedback]');
const submit = form.querySelector('[data-omo-document-merge-submit]');
const keepSourcesInput = form.querySelector('[data-omo-document-merge-keep-sources]');
let tags = Array.isArray(data.keywords) ? data.keywords.slice() : [];
let documents = Array.isArray(data.documents) ? data.documents.slice() : [];
let draggedId = 0;

function renderTags() {
    tagsHost.replaceChildren();
    tags.forEach(function (tag, index) {
        const chip = document.createElement('span');
        chip.className = 'omo-document-merge__tag';
        chip.textContent = String(tag);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'omo-document-merge__tag-remove';
        remove.textContent = '×';
        remove.setAttribute('aria-label', ui.removeTag + ' ' + String(tag));
        remove.addEventListener('click', function () { tags.splice(index, 1); renderTags(); });
        chip.appendChild(remove);
        tagsHost.appendChild(chip);
    });
}

function moveDocument(index, direction) {
    const targetIndex = index + direction;
    if (targetIndex < 0 || targetIndex >= documents.length) return;
    const item = documents.splice(index, 1)[0];
    documents.splice(targetIndex, 0, item);
    renderDocuments();
}

function renderDocuments() {
    documentsHost.replaceChildren();
    documents.forEach(function (documentItem, index) {
        const row = document.createElement('div');
        row.className = 'omo-document-merge__document';
        row.draggable = true;
        row.dataset.documentId = String(documentItem.id || '');
        const handle = document.createElement('span');
        handle.className = 'generic-drag-handle generic-drag-handle--minimal';
        handle.innerHTML = '<svg viewBox="0 0 16 24" aria-hidden="true"><circle cx="5" cy="5" r="1.4"></circle><circle cx="11" cy="5" r="1.4"></circle><circle cx="5" cy="12" r="1.4"></circle><circle cx="11" cy="12" r="1.4"></circle><circle cx="5" cy="19" r="1.4"></circle><circle cx="11" cy="19" r="1.4"></circle></svg>';
        const title = document.createElement('strong');
        title.textContent = String(documentItem.title || ui.documentFallback);
        const actions = document.createElement('div');
        actions.className = 'omo-document-merge__document-actions';
        [['up', -1, ui.moveUp], ['down', 1, ui.moveDown]].forEach(function (definition) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'generic-action-button generic-action-button--quiet-icon';
            button.innerHTML = definition[0] === 'up'
                ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 14 5-5 5 5"></path></svg>'
                : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"></path></svg>';
            button.title = definition[2];
            button.setAttribute('aria-label', definition[2]);
            button.disabled = definition[1] < 0 ? index === 0 : index === documents.length - 1;
            button.addEventListener('click', function () { moveDocument(index, definition[1]); });
            actions.appendChild(button);
        });
        row.append(handle, title, actions);
        row.addEventListener('dragstart', function () { draggedId = Number(documentItem.id || 0); row.classList.add('is-dragging'); });
        row.addEventListener('dragend', function () { draggedId = 0; row.classList.remove('is-dragging'); });
        row.addEventListener('dragover', function (event) { event.preventDefault(); });
        row.addEventListener('drop', function (event) {
            event.preventDefault();
            const from = documents.findIndex(function (item) { return Number(item.id) === draggedId; });
            const to = documents.findIndex(function (item) { return Number(item.id) === Number(documentItem.id); });
            if (from < 0 || to < 0 || from === to) return;
            const item = documents.splice(from, 1)[0];
            documents.splice(to, 0, item);
            renderDocuments();
        });
        documentsHost.appendChild(row);
    });
}

form.addEventListener('submit', function (event) {
    event.preventDefault();
    const title = String(titleInput.value || '').trim();
    if (title === '') { titleInput.focus(); return; }
    submit.disabled = true;
    fetch('/omo/api/documents/merge_action.php', {
        method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify({
            title: title,
            keywords: tags,
            contextDocumentId: Number(data.contextDocumentId || 0),
            visibilityType: String((form.querySelector('input[name="visibility_type"]:checked') || {}).value || ''),
            editVisibilityType: String((form.querySelector('input[name="edit_visibility_type"]:checked') || {}).value || ''),
            keepSources: !keepSourcesInput || keepSourcesInput.checked,
            ids: documents.map(function (item) { return Number(item.id || 0); })
        })
    }).then(function (response) { return response.json().then(function (payload) { if (!response.ok || !payload.status) throw new Error(payload.message || ui.submitError); return payload; }); })
      .then(function () {
          window.dispatchEvent(new CustomEvent('omo-documents-merge-complete', {detail: {ids: documents.map(function (item) { return Number(item.id || 0); })}}));
          if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
          if (typeof window.omoRefreshDocumentsPanel === 'function') window.omoRefreshDocumentsPanel();
      }).catch(function (error) {
          feedback.hidden = false; feedback.className = 'generic-feedback is-error'; feedback.textContent = error.message; submit.disabled = false;
      });
});
form.querySelector('[data-omo-document-merge-cancel]').addEventListener('click', function () { if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal(); });
renderTags(); renderDocuments();
})();
</script>
<style>
.omo-document-merge__visibility-fields {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--color-border);
}
.omo-document-merge__tag-section {
    padding: 0;
}
.omo-document-merge__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}
.omo-document-merge__tag {
    display: inline-flex;
    gap: 7px;
    align-items: center;
    padding: 5px 6px 5px 10px;
    border: 1px solid color-mix(in srgb, var(--color-primary) 26%, var(--color-border));
    border-radius: 999px;
    background: var(--color-surface);
    color: var(--color-text);
}
.omo-document-merge__tag-remove {
    display: inline-grid;
    place-items: center;
    width: 20px;
    height: 20px;
    border: 0;
    border-radius: 50%;
    padding: 0;
    background: color-mix(in srgb, var(--color-primary) 9%, transparent);
    color: var(--color-primary);
    cursor: pointer;
    font: inherit;
    line-height: 1;
}
.omo-document-merge__tag-remove:hover,
.omo-document-merge__tag-remove:focus-visible {
    background: color-mix(in srgb, var(--color-primary) 18%, transparent);
}
.omo-document-merge__documents {
    gap: 0;
    border-top: 1px solid var(--color-border);
}
.omo-document-merge__document {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 10px;
    align-items: center;
    min-height: 52px;
    padding: 7px 0;
    border-bottom: 1px solid var(--color-border);
    background: transparent;
}
.omo-document-merge__document.is-dragging {
    opacity: .45;
}
.omo-document-merge__document-actions {
    display: flex;
    gap: 6px;
}
.omo-document-merge__document-actions svg {
    width: 20px;
    height: 20px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2.2;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.omo-document-merge .generic-drag-handle svg {
    width: 16px;
    height: 24px;
    fill: currentColor;
}
.omo-document-merge__source-choice {
    margin-right: auto;
}
.omo-document-merge__footer {
    align-items: center;
}
@media (max-width: 680px) {
    .omo-document-merge__visibility-fields {
        grid-template-columns: 1fr;
    }
    .omo-document-merge__footer {
        align-items: stretch;
    }
    .omo-document-merge__source-choice {
        width: 100%;
    }
}
</style>
<?php endif; ?>
