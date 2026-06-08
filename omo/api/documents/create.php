<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/patreon.php';
require_once dirname(__DIR__, 3) . '/common/openai_text.php';

use dbObject\Document;
use dbObject\Holon;
use dbObject\ObjectVisibility;
use dbObject\Organization;

$organizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$parentDocumentId = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
$currentUserId = (int)commonGetCurrentUserId();
$escape = 'omoApiEscape';
$document = new Document();
$isEditing = false;
$canCreate = $organizationId > 0 && $currentUserId > 0 && commonCurrentUserHasOrganizationAccess($organizationId);
$canUseForm = $canCreate;
$formErrorMessage = '';
$openAiAvailable = commonOpenAiGetApiKey() !== '';
$patreonConnected = false;

if ($currentUserId > 0 && patreonSupportUiIsEnabled()) {
    $patreonConnection = \dbObject\UserPatreon::findByUserId($currentUserId);
    $patreonConnected = $patreonConnection !== false && $patreonConnection->isConnected();
}

$canUseAiTools = $openAiAvailable && $patreonConnected;

if ($documentId > 0) {
    $isEditing = $document->load($documentId)
        && $document->canEditInOrganizationContext($organizationId);
    $canUseForm = $isEditing;

    if ($canUseForm) {
        $lockResult = $document->touchEditLock($organizationId, $currentUserId);
        if (!is_array($lockResult) || ($lockResult['status'] ?? false) !== true) {
            $canUseForm = false;
            $formErrorMessage = trim((string)($lockResult['text'] ?? 'Ce document est deja en cours d edition.'));
        }
    }
}

$visibilityOptions = ObjectVisibility::getVisibilityTypeOptions();
$documentTitle = '';
$documentDescription = '';
$documentContent = '';
$documentType = Document::TYPE_HTML;
$documentExternalUrl = '';
$documentOpenInNewWindow = false;
$documentStoredFilename = '';
$documentStoredFileMime = '';
$documentStoredFileSize = 0;
$isFolder = false;
$selectedVisibilityType = ObjectVisibility::TYPE_ORGANIZATION;
$disabledVisibilityTypes = array();
$visibilityHelpText = 'Les portees cercle et role suivent automatiquement le holon du document.';
$contextHolonId = $isEditing ? (int)$document->get('IDholon') : $holonId;
$parentFolderTitle = '';
$embeddableDocumentsPayload = array();
$organization = new Organization();
$organizationLoaded = $organizationId > 0 && $organization->load($organizationId);
$nextcloudDocumentsAvailable = $organizationLoaded && $organization->hasNextcloudDocumentStorage();

if (!$isEditing && $parentDocumentId > 0) {
    $parentDocument = new Document();
    if (
        $parentDocument->load($parentDocumentId)
        && (int)$parentDocument->get('IDorganization') === $organizationId
        && $parentDocument->isFolder()
    ) {
        $contextHolonId = (int)$parentDocument->get('IDholon');
        $parentFolderTitle = trim((string)$parentDocument->get('title'));
    } else {
        $parentDocumentId = 0;
    }
}

if ($contextHolonId <= 0) {
    $disabledVisibilityTypes[ObjectVisibility::TYPE_CIRCLE] = true;
    $disabledVisibilityTypes[ObjectVisibility::TYPE_ROLE] = true;
    $visibilityHelpText = 'Ce document n est pas lie a un holon. Les portees cercle et role ne sont pas disponibles.';
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

if ($isEditing) {
    $documentTitle = trim((string)$document->get('title'));
    $documentDescription = trim((string)$document->get('description'));
    $documentContent = $document->getEffectiveEditingContentForUser($currentUserId);
    $documentType = $document->getDocumentType();
    $documentExternalUrl = $document->getExternalUrl();
    $documentOpenInNewWindow = $document->shouldOpenExternalLinkInNewWindow();
    $documentStoredFilename = $document->getStoredFileDownloadName();
    $documentStoredFileMime = $document->getStoredFileMimeType();
    $documentStoredFileSize = $document->getStoredFileSize();
    $isFolder = $document->isFolder();
    $parentDocumentId = (int)$document->get('IDdocument_parent');
    if ($parentDocumentId > 0) {
        $parentDocument = $document->getParentDocument();
        if ($parentDocument instanceof Document) {
            $parentFolderTitle = trim((string)$parentDocument->get('title'));
        }
    }
    $visibilityRule = $document->getPrimaryVisibilityRuleRow();
    $selectedVisibilityType = ObjectVisibility::normalizeVisibilityType($visibilityRule['visibility_type'] ?? ObjectVisibility::TYPE_ORGANIZATION);
}

if (!empty($disabledVisibilityTypes[$selectedVisibilityType])) {
    $selectedVisibilityType = ObjectVisibility::TYPE_ORGANIZATION;
}

if ($organizationId > 0 && $currentUserId > 0 && commonCurrentUserHasOrganizationAccess($organizationId)) {
    $visibleDocuments = new \dbObject\ArrayDocument();
    $visibleDocuments->loadVisibleForOrganizationContext($organizationId, 0, 'global');
    $holonTitleCache = array();

    foreach ($visibleDocuments as $visibleDocument) {
        if (
            !($visibleDocument instanceof \dbObject\Document)
            || !$visibleDocument->canBeEmbedded()
            || (int)$visibleDocument->getId() <= 0
            || ($documentId > 0 && (int)$visibleDocument->getId() === $documentId)
        ) {
            continue;
        }

        $itemHolonId = (int)$visibleDocument->get('IDholon');
        $contextLabel = 'Organisation';
        if ($itemHolonId > 0) {
            if (!array_key_exists($itemHolonId, $holonTitleCache)) {
                $holonItem = new Holon();
                $holonTitleCache[$itemHolonId] = $holonItem->load($itemHolonId)
                    ? trim((string)$holonItem->get('title'))
                    : '';
            }

            if (trim((string)$holonTitleCache[$itemHolonId]) !== '') {
                $contextLabel = (string)$holonTitleCache[$itemHolonId];
            }
        }

        $embeddableDocumentsPayload[] = array(
            'id' => (int)$visibleDocument->getId(),
            'title' => trim((string)$visibleDocument->get('title')),
            'description' => trim((string)$visibleDocument->get('description')),
            'contextLabel' => $contextLabel,
        );
    }

    usort($embeddableDocumentsPayload, static function (array $left, array $right): int {
        $leftTitle = mb_strtolower(trim((string)($left['title'] ?? '')), 'UTF-8');
        $rightTitle = mb_strtolower(trim((string)($right['title'] ?? '')), 'UTF-8');
        if ($leftTitle !== $rightTitle) {
            return $leftTitle <=> $rightTitle;
        }

        return ((int)($left['id'] ?? 0)) <=> ((int)($right['id'] ?? 0));
    });
}
?>
<div class="omo-document-editor">
    <?php if (!$canUseForm): ?>
        <div class="omo-empty-state"><?= $escape($formErrorMessage !== '' ? $formErrorMessage : ($isEditing ? 'Impossible de modifier ce document.' : 'Impossible de creer un document dans ce contexte.')) ?></div>
    <?php else: ?>
        <form class="omo-document-editor__form" action="/omo/api/documents/save.php" method="post" enctype="multipart/form-data" data-omo-document-create-form>
            <input type="hidden" name="oid" value="<?= $escape($organizationId) ?>">
            <input type="hidden" name="cid" value="<?= $escape($holonId) ?>">
            <input type="hidden" name="parent_document_id" value="<?= (int)$parentDocumentId ?>">
            <?php if ($isEditing): ?>
                <input type="hidden" name="id" value="<?= (int)$document->getId() ?>">
            <?php endif; ?>

            <div class="omo-document-editor__grid">
                <label class="omo-document-editor__field">
                    <span class="omo-document-editor__label">Type</span>
                    <select
                        name="document_type"
                        class="generic-form-control"
                        data-omo-document-type
                        <?= $isEditing ? 'disabled' : '' ?>
                    >
                        <option value="<?= $escape(Document::TYPE_HTML) ?>" <?= $documentType === Document::TYPE_HTML ? ' selected' : '' ?>>Document HTML</option>
                        <option value="<?= $escape(Document::TYPE_EXTERNAL_LINK) ?>" <?= $documentType === Document::TYPE_EXTERNAL_LINK ? ' selected' : '' ?>>Lien externe</option>
                        <?php if ($nextcloudDocumentsAvailable || $documentType === Document::TYPE_UPLOADED_FILE): ?>
                            <option value="<?= $escape(Document::TYPE_UPLOADED_FILE) ?>" <?= $documentType === Document::TYPE_UPLOADED_FILE ? ' selected' : '' ?>>Fichier uploade</option>
                        <?php endif; ?>
                        <option value="<?= $escape(Document::TYPE_FOLDER) ?>" <?= $documentType === Document::TYPE_FOLDER ? ' selected' : '' ?>>Dossier</option>
                    </select>
                    <?php if ($isEditing): ?>
                        <input type="hidden" name="document_type" value="<?= $escape($documentType) ?>">
                    <?php endif; ?>
                </label>

                <label class="omo-document-editor__field">
                    <span class="omo-document-editor__label">Titre</span>
                    <input
                        type="text"
                        name="title"
                        class="generic-form-control"
                        maxlength="100"
                        required
                        autocomplete="off"
                        placeholder="Nom du document"
                        value="<?= $escape($documentTitle) ?>"
                    >
                </label>

                <?php if ($parentFolderTitle !== ''): ?>
                    <div class="omo-document-editor__field">
                        <span class="omo-document-editor__label">Dossier parent</span>
                        <div class="omo-document-editor__hint"><?= $escape($parentFolderTitle) ?></div>
                    </div>
                <?php endif; ?>

                <label class="omo-document-editor__field">
                    <span class="omo-document-editor__label">Resume</span>
                    <textarea
                        name="description"
                        class="generic-form-control"
                        rows="3"
                        placeholder="Presentation rapide du document"
                    ><?= $escape($documentDescription) ?></textarea>
                </label>

                <label class="omo-document-editor__field">
                    <span class="omo-document-editor__label">Visibilite</span>
                    <select
                        name="visibility_type"
                        class="generic-form-control"
                    >
                        <?php foreach ($visibilityOptions as $optionValue => $optionLabel): ?>
                            <option
                                value="<?= $escape($optionValue) ?>"
                                <?= $optionValue === $selectedVisibilityType ? ' selected' : '' ?>
                                <?= !empty($disabledVisibilityTypes[$optionValue]) ? ' disabled' : '' ?>
                            ><?= $escape($optionLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="omo-document-editor__hint"><?= $escape($visibilityHelpText) ?></span>
                </label>

                <div class="omo-document-editor__content-section" data-omo-document-content-section<?= $documentType !== Document::TYPE_HTML ? ' hidden' : '' ?>>
                    <div class="omo-document-editor__field" data-omo-document-content-field>
                        <span class="omo-document-editor__label">Contenu HTML</span>
                        <div class="omo-document-editor__html" data-omo-document-editor-html></div>
                        <div class="omo-document-editor__dictation-status" data-omo-document-dictation-status hidden></div>
                    </div>
                </div>

                <div class="omo-document-editor__external-section" data-omo-document-external-section<?= $documentType !== Document::TYPE_EXTERNAL_LINK ? ' hidden' : '' ?>>
                    <label class="omo-document-editor__field">
                        <span class="omo-document-editor__label">URL externe</span>
                        <input
                            type="url"
                            name="external_url"
                            class="generic-form-control"
                            maxlength="2000"
                            autocomplete="off"
                            placeholder="https://example.com/"
                            data-omo-document-external-url
                            value="<?= $escape($documentExternalUrl) ?>"
                        >
                        <span class="omo-document-editor__hint">Utilisez une adresse complete en http:// ou https://.</span>
                    </label>

                    <label class="omo-document-editor__checkbox">
                        <input
                            type="checkbox"
                            name="open_in_new_window"
                            value="1"
                            <?= $documentOpenInNewWindow ? ' checked' : '' ?>
                        >
                        <span>Ouvrir dans une autre fenetre</span>
                    </label>
                </div>

                <div class="omo-document-editor__upload-section" data-omo-document-upload-section<?= $documentType !== Document::TYPE_UPLOADED_FILE ? ' hidden' : '' ?>>
                    <label class="omo-document-editor__field">
                        <span class="omo-document-editor__label">Fichier</span>
                        <input
                            type="file"
                            name="uploaded_file"
                            class="generic-form-control"
                            data-omo-document-upload-input
                        >
                        <span class="omo-document-editor__hint">
                            <?php if ($nextcloudDocumentsAvailable): ?>
                                Le fichier sera envoye vers le stockage Nextcloud configure pour cette organisation.
                            <?php else: ?>
                                Aucun stockage Nextcloud n est configure pour cette organisation.
                            <?php endif; ?>
                        </span>
                    </label>

                    <?php if ($documentType === Document::TYPE_UPLOADED_FILE && $documentStoredFilename !== ''): ?>
                        <div class="omo-document-editor__upload-current">
                            <div class="omo-document-editor__upload-current-title">Fichier actuel</div>
                            <div class="omo-document-editor__upload-current-name"><?= $escape($documentStoredFilename) ?></div>
                            <div class="omo-document-editor__upload-current-meta">
                                <?= $escape($documentStoredFileMime) ?>
                                <?php if ($documentStoredFileSize > 0): ?>
                                    · <?= $escape(number_format($documentStoredFileSize, 0, '.', '\'')) ?> octets
                                <?php endif; ?>
                            </div>
                        </div>

                        <label class="omo-document-editor__checkbox">
                            <input type="checkbox" name="remove_uploaded_file" value="1">
                            <span>Supprimer le fichier distant</span>
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="omo-document-editor__status" data-omo-document-editor-status hidden></div>

            <div class="omo-document-editor__actions">
                <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-document-editor-cancel>Annuler</button>
                <button type="submit" class="generic-action-button generic-action-button--main" data-omo-document-editor-submit><?= $isEditing ? 'Enregistrer' : 'Creer le document' ?></button>
            </div>
        </form>
    <?php endif; ?>
</div>

<style>
.omo-document-editor {
    padding: 20px;
}

.omo-document-editor__form {
    display: grid;
    gap: 18px;
}

.omo-document-editor__grid {
    display: grid;
    gap: 16px;
}

.omo-document-editor__field {
    display: grid;
    gap: 8px;
}

.omo-document-editor__label {
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--color-text);
}

.omo-document-editor__hint {
    color: var(--color-text-light);
    font-size: 0.82rem;
}

.omo-document-editor__checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--color-text);
    font-size: 0.92rem;
}

.omo-document-editor__checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin: 0;
}

.omo-document-editor__upload-current {
    display: grid;
    gap: 4px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid var(--color-border);
    background: var(--color-surface-alt);
}

.omo-document-editor__upload-current-title {
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: var(--color-text-light);
}

.omo-document-editor__upload-current-name {
    font-weight: 600;
    color: var(--color-text);
    word-break: break-word;
}

.omo-document-editor__upload-current-meta {
    font-size: 0.84rem;
    color: var(--color-text-light);
}

.omo-document-editor__status {
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid color-mix(in srgb, #dc2626 25%, var(--color-border));
    background: color-mix(in srgb, #fef2f2 88%, var(--color-surface));
    color: #991b1b;
}

.omo-document-editor__actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.omo-document-editor__dictation-status {
    margin-top: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    border: 1px solid color-mix(in srgb, var(--color-border) 85%, #38bdf8 15%);
    background: color-mix(in srgb, var(--color-surface) 88%, #eff6ff 12%);
    color: var(--color-text-light);
    font-size: 0.84rem;
    line-height: 1.45;
}

.omo-document-editor__dictation-status.is-live {
    border-color: color-mix(in srgb, #f59e0b 35%, var(--color-border));
    background: color-mix(in srgb, #fffbeb 82%, var(--color-surface));
    color: #92400e;
}

.omo-document-editor__dictation-status.is-error {
    border-color: color-mix(in srgb, #dc2626 28%, var(--color-border));
    background: color-mix(in srgb, #fef2f2 88%, var(--color-surface));
    color: #991b1b;
}

.omo-document-editor__dictation-status.is-success {
    border-color: color-mix(in srgb, #16a34a 26%, var(--color-border));
    background: color-mix(in srgb, #f0fdf4 88%, var(--color-surface));
    color: #166534;
}

.omo-document-embed-picker {
    display: grid;
    gap: 14px;
}

.omo-document-embed-picker__field {
    display: grid;
    gap: 8px;
}

.omo-document-embed-picker__label {
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--color-text);
}

.omo-document-embed-picker__select {
    min-height: 240px;
}

.omo-document-embed-picker__preview {
    display: grid;
    gap: 6px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid var(--color-border);
    background: var(--color-surface-alt);
}

.omo-document-embed-picker__preview-title {
    font-weight: 700;
    color: var(--color-text);
}

.omo-document-embed-picker__preview-context,
.omo-document-embed-picker__preview-description {
    color: var(--color-text-light);
    font-size: 0.9rem;
    line-height: 1.55;
}

.omo-document-embed-picker__actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

@media (max-width: 768px) {
    .omo-document-editor {
        padding: 14px;
    }

    .omo-document-editor__actions {
        flex-direction: column-reverse;
    }
}
</style>

<script>
(function () {
    const form = document.querySelector('[data-omo-document-create-form]');
    if (!form || form.dataset.omoDocumentCreateReady === '1') {
        return;
    }

    form.dataset.omoDocumentCreateReady = '1';

    const htmlHost = form.querySelector('[data-omo-document-editor-html]');
    const statusNode = form.querySelector('[data-omo-document-editor-status]');
    const dictationStatusNode = form.querySelector('[data-omo-document-dictation-status]');
    const cancelButton = form.querySelector('[data-omo-document-editor-cancel]');
    const typeSelect = form.querySelector('[data-omo-document-type]');
    const contentSection = form.querySelector('[data-omo-document-content-section]');
    const externalSection = form.querySelector('[data-omo-document-external-section]');
    const uploadSection = form.querySelector('[data-omo-document-upload-section]');
    const externalUrlField = form.querySelector('[data-omo-document-external-url]');
    const uploadInput = form.querySelector('[data-omo-document-upload-input]');
    const uploadHasExistingFile = <?= $documentType === Document::TYPE_UPLOADED_FILE && $documentStoredFilename !== '' ? 'true' : 'false' ?>;
    const aiToolsEnabled = <?= $canUseAiTools ? 'true' : 'false' ?>;
    const initialHtmlValue = <?= json_encode($documentContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const embeddableDocuments = <?= json_encode($embeddableDocumentsPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const editingDocumentId = <?= $isEditing ? (int)$document->getId() : 0 ?>;
    const editLockEndpointUrl = '/omo/api/documents/edit_lock.php';
    const editLockHeartbeatIntervalMs = <?= (int)(\dbObject\Document::getDraftHeartbeatIntervalSeconds() * 1000) ?>;
    const draftSyncDebounceMs = 1000;
    let htmlField = null;
    let htmlValueCache = String(initialHtmlValue || '');
    let mediaStream = null;
    let mediaRecorder = null;
    let recordedChunks = [];
    let recordingMimeType = '';
    let transcriptionController = null;
    let dictationMode = 'idle';
    let rewriteController = null;
    let rewriteMode = 'idle';
    let summarizeController = null;
    let summarizeMode = 'idle';
    let aiToolsOpen = false;
    let editLockLost = false;
    let editLockHeartbeatTimer = null;
    let draftSyncTimer = null;

    function getSelectedDocumentType() {
        if (!typeSelect) {
            return 'html';
        }

        return String(typeSelect.value || 'html').trim().toLowerCase() || 'html';
    }

    function isFolderTypeSelected() {
        return getSelectedDocumentType() === 'folder';
    }

    function isHtmlTypeSelected() {
        return getSelectedDocumentType() === 'html';
    }

    function isUploadedFileTypeSelected() {
        return getSelectedDocumentType() === 'uploaded_file';
    }

    function syncTypeUi() {
        const isHtmlDocument = isHtmlTypeSelected();
        const isExternalLink = getSelectedDocumentType() === 'external_link';
        const isUploadedFile = isUploadedFileTypeSelected();

        if (contentSection) {
            contentSection.hidden = !isHtmlDocument;
        }

        if (externalSection) {
            externalSection.hidden = !isExternalLink;
        }

        if (externalUrlField) {
            externalUrlField.required = isExternalLink;
        }

        if (uploadSection) {
            uploadSection.hidden = !isUploadedFile;
        }

        if (uploadInput) {
            uploadInput.required = isUploadedFile && !uploadHasExistingFile;
        }

        if (!isHtmlDocument) {
            cleanupDictation({ discard: true });
            cleanupRewrite({ keepStatus: true });
            cleanupSummarize({ keepStatus: true });
            destroyHtmlField();
        } else {
            ensureHtmlFieldMounted();
        }
    }

    function hasEditLockSupport() {
        return Number.isInteger(editingDocumentId) && editingDocumentId > 0;
    }

    function clearEditLockHeartbeatTimer() {
        if (editLockHeartbeatTimer) {
            window.clearTimeout(editLockHeartbeatTimer);
            editLockHeartbeatTimer = null;
        }
    }

    function clearDraftSyncTimer() {
        if (draftSyncTimer) {
            window.clearTimeout(draftSyncTimer);
            draftSyncTimer = null;
        }
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function buildDocumentEmbedHtml(documentItem) {
        if (!documentItem) {
            return '';
        }

        const numericId = Number.parseInt(String(documentItem.id || ''), 10);
        if (!Number.isInteger(numericId) || numericId <= 0) {
            return '';
        }

        const title = String(documentItem.title || '').trim();
        const description = String(documentItem.description || '').trim();
        const resolvedTitle = title !== '' ? title : 'Document #' + String(numericId);
        let html = ''
            + '<span class="omo-document-embed"'
            + ' data-omo-embed-type="document"'
            + ' data-omo-document-id="' + escapeHtml(String(numericId)) + '"'
            + ' data-omo-document-title="' + escapeHtml(resolvedTitle) + '"';

        if (description !== '') {
            html += ' data-omo-document-description="' + escapeHtml(description) + '"';
        }

        html += ' contenteditable="false">'
            + '<strong>Document lie</strong><br>'
            + '<strong>' + escapeHtml(resolvedTitle) + '</strong>';

        if (description !== '') {
            html += '<br>' + escapeHtml(description);
        }

        html += '</span>';
        return html;
    }

    function findEmbeddableDocumentById(documentId) {
        const numericId = Number.parseInt(String(documentId || ''), 10);
        if (!Number.isInteger(numericId) || numericId <= 0) {
            return null;
        }

        return embeddableDocuments.find(function (documentItem) {
            return Number.parseInt(String(documentItem.id || ''), 10) === numericId;
        }) || null;
    }

    function getEmbedNodeDocumentId(targetNode) {
        if (!targetNode || !targetNode.getAttribute) {
            return 0;
        }

        return Number.parseInt(String(targetNode.getAttribute('data-omo-document-id') || ''), 10) || 0;
    }

    function updateDocumentEmbedPickerPreview(modalBody, selectedItem) {
        if (!modalBody) {
            return;
        }

        const titleNode = modalBody.querySelector('[data-omo-document-embed-preview-title]');
        const contextNode = modalBody.querySelector('[data-omo-document-embed-preview-context]');
        const descriptionNode = modalBody.querySelector('[data-omo-document-embed-preview-description]');
        const applyButton = modalBody.querySelector('[data-omo-document-embed-apply]');

        if (!selectedItem) {
            if (titleNode) {
                titleNode.textContent = 'Aucun document selectionne.';
            }
            if (contextNode) {
                contextNode.textContent = '';
                contextNode.hidden = true;
            }
            if (descriptionNode) {
                descriptionNode.textContent = '';
                descriptionNode.hidden = true;
            }
            if (applyButton) {
                applyButton.disabled = true;
            }
            return;
        }

        if (titleNode) {
            const title = String(selectedItem.title || '').trim();
            titleNode.textContent = title !== '' ? title : 'Document #' + String(selectedItem.id || '');
        }

        if (contextNode) {
            const contextLabel = String(selectedItem.contextLabel || '').trim();
            contextNode.textContent = contextLabel;
            contextNode.hidden = contextLabel === '';
        }

        if (descriptionNode) {
            const description = String(selectedItem.description || '').trim();
            descriptionNode.textContent = description;
            descriptionNode.hidden = description === '';
        }

        if (applyButton) {
            applyButton.disabled = false;
        }
    }

    function renderDocumentEmbedPickerOptions(selectNode, searchValue, selectedDocumentId) {
        if (!selectNode) {
            return null;
        }

        const normalizedSearch = String(searchValue || '').trim().toLowerCase();
        const matchingItems = embeddableDocuments.filter(function (documentItem) {
            if (normalizedSearch === '') {
                return true;
            }

            const haystack = [
                String(documentItem.title || ''),
                String(documentItem.description || ''),
                String(documentItem.contextLabel || '')
            ].join(' ').toLowerCase();

            return haystack.indexOf(normalizedSearch) >= 0;
        });

        selectNode.innerHTML = '';
        matchingItems.forEach(function (documentItem) {
            const option = document.createElement('option');
            const title = String(documentItem.title || '').trim();
            option.value = String(documentItem.id || '');
            option.textContent = title !== '' ? title : 'Document #' + String(documentItem.id || '');
            selectNode.appendChild(option);
        });

        let selectedItem = null;
        if (matchingItems.length > 0) {
            const preferredId = Number.parseInt(String(selectedDocumentId || ''), 10) || 0;
            selectedItem = matchingItems.find(function (documentItem) {
                return Number.parseInt(String(documentItem.id || ''), 10) === preferredId;
            }) || matchingItems[0];

            selectNode.value = String(selectedItem.id || '');
        }

        selectNode.disabled = matchingItems.length === 0;
        return selectedItem;
    }

    function applyDocumentEmbedSelection(documentItem, targetNode, insertionMarker) {
        if (!htmlField || !documentItem) {
            return;
        }

        const embedHtml = buildDocumentEmbedHtml(documentItem);
        if (embedHtml === '') {
            return;
        }

        if (targetNode && typeof htmlField.replaceNodeWithHtml === 'function') {
            htmlField.replaceNodeWithHtml(targetNode, embedHtml);
            return;
        }

        if (insertionMarker && typeof htmlField.replaceMarkerWithHtml === 'function') {
            htmlField.replaceMarkerWithHtml(insertionMarker, embedHtml);
            return;
        }

        if (typeof htmlField.insertHtmlAtCursor === 'function') {
            htmlField.insertHtmlAtCursor(embedHtml);
        }
    }

    function openDocumentEmbedPicker(options) {
        if (!htmlField || typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        const settings = options && typeof options === 'object' ? options : {};
        const targetNode = settings.targetNode || null;
        let selectedDocumentId = Number.parseInt(String(settings.selectedDocumentId || ''), 10) || 0;
        let insertionMarker = null;
        let pickerResolved = false;

        if (!targetNode && typeof htmlField.createTemporaryCursorMarker === 'function') {
            insertionMarker = htmlField.createTemporaryCursorMarker();
        }

        const modalHtml = ''
            + '<div class="omo-document-embed-picker">'
            + '  <label class="omo-document-embed-picker__field">'
            + '    <span class="omo-document-embed-picker__label">Recherche</span>'
            + '    <input type="search" class="generic-form-control" data-omo-document-embed-search placeholder="Titre, resume ou contexte">'
            + '  </label>'
            + '  <label class="omo-document-embed-picker__field">'
            + '    <span class="omo-document-embed-picker__label">Documents visibles</span>'
            + '    <select class="generic-form-control omo-document-embed-picker__select" data-omo-document-embed-select size="10"></select>'
            + '  </label>'
            + '  <div class="omo-document-embed-picker__preview">'
            + '    <div class="omo-document-embed-picker__preview-title" data-omo-document-embed-preview-title>Aucun document selectionne.</div>'
            + '    <div class="omo-document-embed-picker__preview-context" data-omo-document-embed-preview-context hidden></div>'
            + '    <div class="omo-document-embed-picker__preview-description" data-omo-document-embed-preview-description hidden></div>'
            + '  </div>'
            + '  <div class="omo-document-embed-picker__actions">'
            + (targetNode
                ? '    <button type="button" class="generic-action-button generic-action-button--danger" data-omo-document-embed-delete>Supprimer</button>'
                : '')
            + '    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-document-embed-cancel>Annuler</button>'
            + '    <button type="button" class="generic-action-button generic-action-button--main" data-omo-document-embed-apply disabled>' + (targetNode ? 'Mettre a jour' : 'Inserer le document') + '</button>'
            + '  </div>'
            + '</div>';

        window.commonTopbarOpenModal('Inserer un document', modalHtml, 'html');

        const modalBody = document.getElementById('commonTopbarModalBody');
        if (!modalBody) {
            if (
                insertionMarker
                && htmlField
                && typeof htmlField.removeTemporaryMarker === 'function'
            ) {
                htmlField.removeTemporaryMarker(insertionMarker);
            }
            return;
        }

        const searchNode = modalBody.querySelector('[data-omo-document-embed-search]');
        const selectNode = modalBody.querySelector('[data-omo-document-embed-select]');
        const deleteButton = modalBody.querySelector('[data-omo-document-embed-delete]');
        const cancelButton = modalBody.querySelector('[data-omo-document-embed-cancel]');
        const applyButton = modalBody.querySelector('[data-omo-document-embed-apply]');

        function cleanupInsertionMarker() {
            if (
                insertionMarker
                && htmlField
                && typeof htmlField.removeTemporaryMarker === 'function'
            ) {
                htmlField.removeTemporaryMarker(insertionMarker);
            }

            insertionMarker = null;
        }

        window.addEventListener('common-topbar-modal-close', function () {
            if (!pickerResolved) {
                cleanupInsertionMarker();
            }
        }, { once: true });

        function syncSelection(nextSelectedDocumentId) {
            selectedDocumentId = Number.parseInt(String(nextSelectedDocumentId || ''), 10) || 0;
            const selectedItem = findEmbeddableDocumentById(selectedDocumentId);
            updateDocumentEmbedPickerPreview(modalBody, selectedItem);
            return selectedItem;
        }

        function rerenderOptions() {
            const selectedItem = renderDocumentEmbedPickerOptions(
                selectNode,
                searchNode ? searchNode.value : '',
                selectedDocumentId > 0 ? selectedDocumentId : (targetNode ? getEmbedNodeDocumentId(targetNode) : 0)
            );

            selectedDocumentId = selectedItem ? Number.parseInt(String(selectedItem.id || ''), 10) : 0;
            updateDocumentEmbedPickerPreview(modalBody, selectedItem);
        }

        rerenderOptions();

        if (searchNode) {
            searchNode.focus();
            searchNode.addEventListener('input', rerenderOptions);
        }

        if (selectNode) {
            selectNode.addEventListener('change', function () {
                syncSelection(selectNode.value);
            });

            selectNode.addEventListener('dblclick', function () {
                const selectedItem = syncSelection(selectNode.value);
                if (!selectedItem) {
                    return;
                }

                pickerResolved = true;
                applyDocumentEmbedSelection(selectedItem, targetNode, insertionMarker);
                insertionMarker = null;
                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                pickerResolved = false;
                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });
        }

        if (deleteButton) {
            deleteButton.addEventListener('click', function () {
                pickerResolved = true;
                cleanupInsertionMarker();
                if (targetNode && htmlField && typeof htmlField.removeNode === 'function') {
                    htmlField.removeNode(targetNode);
                }

                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });
        }

        if (applyButton) {
            applyButton.addEventListener('click', function () {
                const selectedItem = syncSelection(selectNode ? selectNode.value : 0);
                if (!selectedItem) {
                    return;
                }

                pickerResolved = true;
                applyDocumentEmbedSelection(selectedItem, targetNode, insertionMarker);
                insertionMarker = null;
                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });
        }
    }

    function getCurrentDraftContent() {
        if (!isHtmlTypeSelected()) {
            return '';
        }

        if (htmlField && typeof htmlField.getValue === 'function') {
            return String(htmlField.getValue() || '');
        }

        return String(htmlValueCache || '');
    }

    function postEditLockAction(action, options) {
        if (!hasEditLockSupport()) {
            return Promise.resolve({ status: true });
        }

        const settings = options && typeof options === 'object' ? options : {};
        const body = new URLSearchParams();
        body.set('id', String(editingDocumentId));
        body.set('action', String(action || 'heartbeat'));
        if (settings.clearDraft) {
            body.set('clear_draft', '1');
        }
        if (settings.includeDraft) {
            body.set('draft_content', getCurrentDraftContent());
        }

        return fetch(editLockEndpointUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString(),
            cache: 'no-store',
            keepalive: !!settings.keepalive
        }).then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (payload) {
                return {
                    ok: response.ok,
                    payload: payload
                };
            });
        });
    }

    function markEditLockLost(message) {
        editLockLost = true;
        clearEditLockHeartbeatTimer();
        clearDraftSyncTimer();
        setStatus(String(message || 'Ce document n est plus verrouille pour votre edition.'));
        syncDictationToolbarButtons();
    }

    function scheduleDraftSync(delayMs) {
        if (!hasEditLockSupport() || editLockLost || !isHtmlTypeSelected()) {
            return;
        }

        clearDraftSyncTimer();
        draftSyncTimer = window.setTimeout(function () {
            postEditLockAction('heartbeat', { includeDraft: true })
                .then(function (result) {
                    if (!result || !result.ok || !result.payload || result.payload.status !== true) {
                        const message = result && result.payload && result.payload.message
                            ? result.payload.message
                            : 'Ce document est desormais edite ailleurs.';
                        markEditLockLost(message);
                    }
                })
                .catch(function () {
                });
        }, Math.max(250, Number(delayMs || 0)));
    }

    function scheduleEditLockHeartbeat() {
        if (!hasEditLockSupport() || editLockLost) {
            return;
        }

        clearEditLockHeartbeatTimer();
        editLockHeartbeatTimer = window.setTimeout(function () {
            postEditLockAction('heartbeat', { includeDraft: true })
                .then(function (result) {
                    if (!result || !result.ok || !result.payload || result.payload.status !== true) {
                        const message = result && result.payload && result.payload.message
                            ? result.payload.message
                            : 'Ce document est desormais edite ailleurs.';
                        markEditLockLost(message);
                        return;
                    }

                    scheduleEditLockHeartbeat();
                })
                .catch(function () {
                    scheduleEditLockHeartbeat();
                });
        }, editLockHeartbeatIntervalMs);
    }

    function releaseEditLock(options) {
        if (!hasEditLockSupport()) {
            return Promise.resolve();
        }

        const settings = options && typeof options === 'object' ? options : {};
        clearEditLockHeartbeatTimer();
        clearDraftSyncTimer();

        if (settings.beacon && navigator && typeof navigator.sendBeacon === 'function') {
            const body = new URLSearchParams();
            body.set('id', String(editingDocumentId));
            body.set('action', 'release');
            if (settings.clearDraft) {
                body.set('clear_draft', '1');
            }
            navigator.sendBeacon(editLockEndpointUrl, body);
            return Promise.resolve();
        }

        return postEditLockAction('release', {
            keepalive: !!settings.keepalive,
            clearDraft: !!settings.clearDraft
        })
            .catch(function () {
            });
    }

    function setDictationStatus(message, type) {
        if (!dictationStatusNode) {
            return;
        }

        const text = String(message || '').trim();
        const statusType = String(type || '').trim().toLowerCase();

        dictationStatusNode.hidden = text === '';
        dictationStatusNode.textContent = text;
        dictationStatusNode.classList.toggle('is-live', statusType === 'live');
        dictationStatusNode.classList.toggle('is-error', statusType === 'error');
        dictationStatusNode.classList.toggle('is-success', statusType === 'success');
    }

    function getRecordingMimeType() {
        if (typeof window.MediaRecorder === 'undefined' || typeof window.MediaRecorder.isTypeSupported !== 'function') {
            return '';
        }

        const candidates = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/mp4',
            'audio/ogg;codecs=opus',
            'audio/ogg'
        ];

        for (let index = 0; index < candidates.length; index += 1) {
            if (window.MediaRecorder.isTypeSupported(candidates[index])) {
                return candidates[index];
            }
        }

        return '';
    }

    function getRecordingFileExtension(mimeType) {
        const normalizedMimeType = String(mimeType || '').toLowerCase();

        if (normalizedMimeType.indexOf('mp4') >= 0 || normalizedMimeType.indexOf('m4a') >= 0) {
            return 'm4a';
        }

        if (normalizedMimeType.indexOf('ogg') >= 0) {
            return 'ogg';
        }

        if (normalizedMimeType.indexOf('wav') >= 0) {
            return 'wav';
        }

        return 'webm';
    }

    function releaseMediaStream() {
        if (mediaStream && typeof mediaStream.getTracks === 'function') {
            mediaStream.getTracks().forEach(function (track) {
                try {
                    track.stop();
                } catch (error) {
                }
            });
        }

        mediaStream = null;
    }

    function resetDictationRecorderState() {
        mediaRecorder = null;
        recordedChunks = [];
        recordingMimeType = '';
        releaseMediaStream();
    }

    function syncDictationToolbarButtons() {
        if (!aiToolsEnabled || !htmlField || typeof htmlField.setToolbarButtonState !== 'function') {
            return;
        }

        const isRecording = dictationMode === 'recording';
        const isTranscribing = dictationMode === 'transcribing';
        const isRewriting = rewriteMode === 'pending';
        const isSummarizing = summarizeMode === 'pending';
        const isAiBusy = isRecording || isTranscribing || isRewriting || isSummarizing;
        const aiToolsVisible = aiToolsOpen || isAiBusy;
        const submitButton = form.querySelector('[data-omo-document-editor-submit]');

        htmlField.setToolbarButtonState('omoDocumentAiToggle', {
            label: 'IA',
            title: aiToolsVisible ? 'Masquer les outils IA' : 'Afficher les outils IA',
            active: aiToolsVisible,
            disabled: false
        });

        htmlField.setToolbarButtonState('omoDocumentDictate', {
            label: isRecording ? 'En cours...' : 'Dicter',
            title: isRecording ? 'Enregistrement en cours' : 'Demarrer une dictee',
            disabled: isRecording || isTranscribing || isRewriting || isSummarizing,
            hidden: !aiToolsVisible
        });
        htmlField.setToolbarButtonState('omoDocumentTranscript', {
            label: isTranscribing ? 'Transcription...' : 'Transcript',
            title: 'Arreter l enregistrement et transcrire',
            disabled: !isRecording || isTranscribing || isRewriting || isSummarizing,
            hidden: !aiToolsVisible
        });
        htmlField.setToolbarButtonState('omoDocumentDictationCancel', {
            label: 'Annuler',
            title: 'Annuler la dictee en cours',
            disabled: (!isRecording && !isTranscribing),
            hidden: !aiToolsVisible || (!isRecording && !isTranscribing)
        });
        htmlField.setToolbarButtonState('omoDocumentRewrite', {
            label: isRewriting ? 'Rewrite...' : 'Rewrite',
            title: isRewriting ? 'Reecriture en cours' : 'Reecrire la selection',
            disabled: isRecording || isTranscribing || isRewriting || isSummarizing,
            hidden: !aiToolsVisible
        });
        htmlField.setToolbarButtonState('omoDocumentSummarize', {
            label: isSummarizing ? 'Resumer...' : 'Resumer',
            title: isSummarizing ? 'Resume en cours' : 'Resumer la selection',
            disabled: isRecording || isTranscribing || isRewriting || isSummarizing,
            hidden: !aiToolsVisible
        });

        if (submitButton) {
            submitButton.disabled = editLockLost || isRecording || isTranscribing || isRewriting || isSummarizing;
        }
    }

    function setDictationMode(nextMode) {
        dictationMode = String(nextMode || 'idle').trim().toLowerCase() || 'idle';
        syncDictationToolbarButtons();
    }

    function toggleAiTools(forceOpen) {
        if (!aiToolsEnabled) {
            return;
        }

        const shouldOpen = forceOpen === undefined
            ? !aiToolsOpen
            : !!forceOpen;

        aiToolsOpen = shouldOpen;
        syncDictationToolbarButtons();
    }

    function formatDictationError(error) {
        const errorName = error && error.name ? String(error.name) : '';

        if (errorName === 'NotAllowedError' || errorName === 'SecurityError') {
            return 'L acces au micro a ete refuse.';
        }

        if (errorName === 'NotFoundError' || errorName === 'DevicesNotFoundError') {
            return 'Aucun micro n est disponible sur cet appareil.';
        }

        if (errorName === 'AbortError') {
            return 'La dictee a ete interrompue.';
        }

        if (error && error.message) {
            return String(error.message);
        }

        return 'Impossible d utiliser la dictee pour le moment.';
    }

    function abortTranscriptionRequest() {
        if (transcriptionController && typeof transcriptionController.abort === 'function') {
            try {
                transcriptionController.abort();
            } catch (error) {
            }
        }

        transcriptionController = null;
    }

    function abortRewriteRequest() {
        if (rewriteController && typeof rewriteController.abort === 'function') {
            try {
                rewriteController.abort();
            } catch (error) {
            }
        }

        rewriteController = null;
    }

    function abortSummarizeRequest() {
        if (summarizeController && typeof summarizeController.abort === 'function') {
            try {
                summarizeController.abort();
            } catch (error) {
            }
        }

        summarizeController = null;
    }

    function stopRecorder(discardRecording) {
        return new Promise(function (resolve, reject) {
            if (!mediaRecorder) {
                resetDictationRecorderState();
                resolve(null);
                return;
            }

            const recorder = mediaRecorder;
            const mimeType = recordingMimeType || recorder.mimeType || 'audio/webm';

            const handleStop = function () {
                const blob = discardRecording
                    ? null
                    : new Blob(recordedChunks, { type: mimeType });
                resetDictationRecorderState();
                resolve(blob);
            };

            const handleError = function () {
                resetDictationRecorderState();
                reject(new Error('Impossible de finaliser l enregistrement audio.'));
            };

            recorder.addEventListener('stop', handleStop, { once: true });
            recorder.addEventListener('error', handleError, { once: true });

            try {
                if (typeof recorder.requestData === 'function' && recorder.state === 'recording') {
                    recorder.requestData();
                }

                if (recorder.state !== 'inactive') {
                    recorder.stop();
                    return;
                }

                handleStop();
            } catch (error) {
                resetDictationRecorderState();
                reject(error);
            }
        });
    }

    function cleanupDictation(options) {
        const shouldDiscardRecording = !options || options.discard !== false;
        abortTranscriptionRequest();

        if (mediaRecorder) {
            stopRecorder(shouldDiscardRecording).catch(function () {
            });
        } else {
            resetDictationRecorderState();
        }

        setDictationMode('idle');
        if (options && options.keepStatus) {
            return;
        }

        setDictationStatus('', '');
    }

    function setRewriteMode(nextMode) {
        rewriteMode = String(nextMode || 'idle').trim().toLowerCase() || 'idle';
        syncDictationToolbarButtons();
    }

    function normalizeRewriteComparisonText(text) {
        return String(text || '')
            .replace(/\r\n?/g, '\n')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function cleanupRewrite(options) {
        abortRewriteRequest();
        setRewriteMode('idle');

        if (options && options.keepStatus) {
            return;
        }

        setDictationStatus('', '');
    }

    function setSummarizeMode(nextMode) {
        summarizeMode = String(nextMode || 'idle').trim().toLowerCase() || 'idle';
        syncDictationToolbarButtons();
    }

    function cleanupSummarize(options) {
        abortSummarizeRequest();
        setSummarizeMode('idle');

        if (options && options.keepStatus) {
            return;
        }

        setDictationStatus('', '');
    }

    function destroyHtmlField() {
        if (!htmlField) {
            return;
        }

        if (typeof htmlField.getValue === 'function') {
            htmlValueCache = String(htmlField.getValue() || '');
        }

        if (typeof htmlField.destroy === 'function') {
            htmlField.destroy();
        }

        htmlField = null;
        if (htmlHost) {
            htmlHost.innerHTML = '';
        }
    }

    function mountHtmlField() {
        if (
            !htmlHost
            || !isHtmlTypeSelected()
            || htmlField
            || !window.omoSimpleHtmlField
            || typeof window.omoSimpleHtmlField.mount !== 'function'
        ) {
            return;
        }

        const customButtons = [];

        customButtons.push({
            name: 'omoDocumentEmbed',
            group: 'omo-embed',
            label: 'Document',
            title: 'Inserer un document',
            className: 'note-btn-light',
            onClick: function () {
                openDocumentEmbedPicker();
            }
        });

        if (aiToolsEnabled) {
            customButtons.push({
                name: 'omoDocumentAiToggle',
                group: 'omo-ai-toggle',
                label: 'IA',
                title: 'Afficher les outils IA',
                className: 'note-btn-light',
                onClick: function () {
                    toggleAiTools();
                }
            });
            customButtons.push({
                name: 'omoDocumentDictate',
                group: 'omo-ai-tools',
                label: 'Dicter',
                title: 'Demarrer une dictee',
                className: 'note-btn-light',
                hidden: true,
                onClick: function () {
                    startDictation();
                }
            });
            customButtons.push({
                name: 'omoDocumentTranscript',
                group: 'omo-ai-tools',
                label: 'Transcript',
                title: 'Arreter l enregistrement et transcrire',
                className: 'note-btn-light',
                disabled: true,
                hidden: true,
                onClick: function () {
                    transcribeCurrentRecording();
                }
            });
            customButtons.push({
                name: 'omoDocumentDictationCancel',
                group: 'omo-ai-tools',
                label: 'Annuler',
                title: 'Annuler la dictee en cours',
                className: 'note-btn-light',
                hidden: true,
                disabled: true,
                onClick: function () {
                    cleanupDictation({ discard: true });
                    setDictationStatus('Dictee annulee.', 'error');
                }
            });
            customButtons.push({
                name: 'omoDocumentRewrite',
                group: 'omo-ai-tools',
                label: 'Rewrite',
                title: 'Reecrire la selection',
                className: 'note-btn-light',
                hidden: true,
                onClick: function () {
                    rewriteSelectedPassage();
                }
            });
            customButtons.push({
                name: 'omoDocumentSummarize',
                group: 'omo-ai-tools',
                label: 'Resumer',
                title: 'Resumer la selection',
                className: 'note-btn-light',
                hidden: true,
                onClick: function () {
                    summarizeSelectedPassage();
                }
            });
        }

        htmlField = window.omoSimpleHtmlField.mount(htmlHost, {
            value: htmlValueCache,
            placeholder: 'Redigez le contenu du document...',
            height: 240,
            customButtons: customButtons,
            onChange: function (value) {
                htmlValueCache = String(value || '');
                scheduleDraftSync(draftSyncDebounceMs);
            },
            onDoubleClick: function (context) {
                const rawTarget = context && context.target && context.target.closest
                    ? context.target.closest('.omo-document-embed[data-omo-embed-type="document"]')
                    : null;

                if (!rawTarget) {
                    return;
                }

                openDocumentEmbedPicker({
                    targetNode: rawTarget,
                    selectedDocumentId: getEmbedNodeDocumentId(rawTarget)
                });
            }
        });

        syncDictationToolbarButtons();

        if (aiToolsEnabled && htmlField && typeof htmlField.setToolbarButtonState === 'function') {
            htmlField.setToolbarButtonState('omoDocumentAiToggle', {
                label: 'IA',
                title: 'Afficher les outils IA'
            });
            htmlField.setToolbarButtonState('omoDocumentDictate', {
                label: 'Dicter',
                title: 'Demarrer une dictee'
            });
            htmlField.setToolbarButtonState('omoDocumentTranscript', {
                title: 'Arreter l enregistrement et transcrire'
            });
            htmlField.setToolbarButtonState('omoDocumentRewrite', {
                title: 'Reecrire la selection'
            });
            htmlField.setToolbarButtonState('omoDocumentSummarize', {
                title: 'Resumer la selection'
            });
        }

        syncDictationToolbarButtons();
    }

    function ensureHtmlFieldMounted() {
        if (!isHtmlTypeSelected()) {
            destroyHtmlField();
            return;
        }

        if (window.omoSimpleHtmlField && typeof window.omoSimpleHtmlField.mount === 'function') {
            mountHtmlField();
            return;
        }

        const scriptSelector = 'script[data-omo-simple-html-field-script="1"]';
        const existingScript = document.querySelector(scriptSelector);
        if (existingScript) {
            if (existingScript.getAttribute('data-loaded') === '1') {
                mountHtmlField();
            } else {
                existingScript.addEventListener('load', mountHtmlField, { once: true });
            }
            return;
        }

        const script = document.createElement('script');
        script.src = '/omo/assets/js/simple-html-field.js';
        script.async = false;
        script.setAttribute('data-omo-simple-html-field-script', '1');
        script.onload = function () {
            script.setAttribute('data-loaded', '1');
            mountHtmlField();
        };
        document.head.appendChild(script);
    }

    async function startDictation() {
        if (!aiToolsEnabled || dictationMode === 'recording' || dictationMode === 'transcribing') {
            return;
        }

        if (typeof window.MediaRecorder === 'undefined' || !navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            setDictationStatus('La dictee n est pas disponible sur ce navigateur.', 'error');
            return;
        }

        try {
            if (htmlField && typeof htmlField.saveRange === 'function') {
                htmlField.saveRange();
            }

            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const mimeType = getRecordingMimeType();
            const recorder = mimeType !== ''
                ? new window.MediaRecorder(stream, { mimeType: mimeType })
                : new window.MediaRecorder(stream);

            mediaStream = stream;
            mediaRecorder = recorder;
            recordedChunks = [];
            recordingMimeType = mimeType || recorder.mimeType || 'audio/webm';

            recorder.addEventListener('dataavailable', function (recordEvent) {
                if (recordEvent.data && recordEvent.data.size > 0) {
                    recordedChunks.push(recordEvent.data);
                }
            });

            recorder.start();
            setDictationMode('recording');
            setDictationStatus('Enregistrement en cours. Quand vous avez fini, cliquez sur Transcript.', 'live');
        } catch (error) {
            cleanupDictation({ discard: true, keepStatus: true });
            setDictationStatus(formatDictationError(error), 'error');
        }
    }

    async function transcribeCurrentRecording() {
        if (dictationMode !== 'recording') {
            return;
        }

        setDictationMode('transcribing');
        setDictationStatus('Transcription en cours...', 'live');

        try {
            const audioBlob = await stopRecorder(false);
            if (!audioBlob || audioBlob.size <= 0) {
                throw new Error('Aucun son n a ete enregistre.');
            }

            const formData = new FormData();
            formData.set('oid', String(<?= (int)$organizationId ?>));
            formData.set('cid', String(<?= (int)$holonId ?>));
            formData.set('title', String(form.querySelector('input[name="title"]') ? form.querySelector('input[name="title"]').value || '' : ''));
            formData.set(
                'audio',
                audioBlob,
                'document-dictation.' + getRecordingFileExtension(audioBlob.type || recordingMimeType)
            );

            transcriptionController = typeof window.AbortController === 'function'
                ? new window.AbortController()
                : null;

            const response = await fetch('/omo/api/documents/transcribe.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: transcriptionController ? transcriptionController.signal : undefined,
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('Impossible de transcrire cet enregistrement.');
            }

            const payload = await response.json();
            if (!payload || payload.status !== true) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible de transcrire cet enregistrement.');
            }

            const transcriptText = String(payload.text || '').trim();
            if (transcriptText === '') {
                throw new Error('La transcription est vide.');
            }

            if (!htmlField || typeof htmlField.insertTextAtCursor !== 'function') {
                throw new Error('Impossible d inserer la transcription dans l editeur.');
            }

            htmlField.insertTextAtCursor(transcriptText);
            if (typeof htmlField.focus === 'function') {
                htmlField.focus();
            }

            setDictationMode('idle');
            setDictationStatus('Transcript insere dans le document.', 'success');
        } catch (error) {
            setDictationMode('idle');
            setDictationStatus(formatDictationError(error), 'error');
        } finally {
            transcriptionController = null;
        }
    }

    async function rewriteSelectedPassage() {
        if (!aiToolsEnabled || rewriteMode === 'pending' || summarizeMode === 'pending' || dictationMode === 'recording' || dictationMode === 'transcribing') {
            return;
        }

        if (!htmlField || typeof htmlField.getSelectedText !== 'function' || typeof htmlField.getPlainText !== 'function') {
            setDictationStatus('Impossible d acceder a la selection dans l editeur.', 'error');
            return;
        }

        if (typeof htmlField.saveRange === 'function') {
            htmlField.saveRange();
        }

        const selectedText = String(htmlField.getSelectedText() || '').trim();
        const fullText = String(htmlField.getPlainText() || '').trim();
        const rewritesWholeDocument = normalizeRewriteComparisonText(selectedText) === normalizeRewriteComparisonText(fullText);

        if (selectedText === '') {
            setDictationStatus('Selectionnez un bloc de texte avant de lancer Rewrite.', 'error');
            return;
        }

        if (fullText === '') {
            setDictationStatus('Le document est vide.', 'error');
            return;
        }

        setRewriteMode('pending');
        setDictationStatus('Reecriture en cours...', 'live');

        try {
            const formData = new FormData();
            formData.set('oid', String(<?= (int)$organizationId ?>));
            formData.set('cid', String(<?= (int)$holonId ?>));
            formData.set('title', String(form.querySelector('input[name="title"]') ? form.querySelector('input[name="title"]').value || '' : ''));
            formData.set('selected_text', selectedText);
            formData.set('full_text', fullText);
            formData.set('rewrite_full_document', rewritesWholeDocument ? '1' : '0');

            rewriteController = typeof window.AbortController === 'function'
                ? new window.AbortController()
                : null;

            const response = await fetch('/omo/api/documents/rewrite.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: rewriteController ? rewriteController.signal : undefined,
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('Impossible de reecrire cette selection.');
            }

            const payload = await response.json();
            if (!payload || payload.status !== true) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible de reecrire cette selection.');
            }

            const rewrittenText = String(payload.text || '').trim();
            if (rewrittenText === '') {
                throw new Error('La reecriture est vide.');
            }

            if (typeof htmlField.replaceSelectionWithText !== 'function') {
                throw new Error('Impossible de remplacer la selection dans l editeur.');
            }

            htmlField.replaceSelectionWithText(rewrittenText);
            if (typeof htmlField.focus === 'function') {
                htmlField.focus();
            }

            setRewriteMode('idle');
            setDictationStatus('Selection reecrite.', 'success');
        } catch (error) {
            setRewriteMode('idle');
            setDictationStatus(
                error && error.message ? String(error.message) : 'Impossible de reecrire cette selection.',
                'error'
            );
        } finally {
            rewriteController = null;
        }
    }

    async function summarizeSelectedPassage() {
        if (!aiToolsEnabled || summarizeMode === 'pending' || rewriteMode === 'pending' || dictationMode === 'recording' || dictationMode === 'transcribing') {
            return;
        }

        if (!htmlField || typeof htmlField.getSelectedText !== 'function' || typeof htmlField.getPlainText !== 'function') {
            setDictationStatus('Impossible d acceder a la selection dans l editeur.', 'error');
            return;
        }

        if (typeof htmlField.saveRange === 'function') {
            htmlField.saveRange();
        }

        const selectedText = String(htmlField.getSelectedText() || '').trim();
        const fullText = String(htmlField.getPlainText() || '').trim();

        if (selectedText === '') {
            setDictationStatus('Selectionnez un bloc de texte avant de lancer Resumer.', 'error');
            return;
        }

        if (fullText === '') {
            setDictationStatus('Le document est vide.', 'error');
            return;
        }

        setSummarizeMode('pending');
        setDictationStatus('Resume en cours...', 'live');

        try {
            const formData = new FormData();
            formData.set('oid', String(<?= (int)$organizationId ?>));
            formData.set('cid', String(<?= (int)$holonId ?>));
            formData.set('title', String(form.querySelector('input[name="title"]') ? form.querySelector('input[name="title"]').value || '' : ''));
            formData.set('selected_text', selectedText);
            formData.set('full_text', fullText);

            summarizeController = typeof window.AbortController === 'function'
                ? new window.AbortController()
                : null;

            const response = await fetch('/omo/api/documents/summarize.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: summarizeController ? summarizeController.signal : undefined,
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('Impossible de resumer cette selection.');
            }

            const payload = await response.json();
            if (!payload || payload.status !== true) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible de resumer cette selection.');
            }

            const summarizedText = String(payload.text || '').trim();
            if (summarizedText === '') {
                throw new Error('Le resume est vide.');
            }

            if (typeof htmlField.replaceSelectionWithText !== 'function') {
                throw new Error('Impossible de remplacer la selection dans l editeur.');
            }

            htmlField.replaceSelectionWithText(summarizedText);
            if (typeof htmlField.focus === 'function') {
                htmlField.focus();
            }

            setSummarizeMode('idle');
            setDictationStatus('Selection resumee.', 'success');
        } catch (error) {
            setSummarizeMode('idle');
            setDictationStatus(
                error && error.message ? String(error.message) : 'Impossible de resumer cette selection.',
                'error'
            );
        } finally {
            summarizeController = null;
        }
    }

    ensureHtmlFieldMounted();

    syncDictationToolbarButtons();

    function setStatus(message) {
        if (!statusNode) {
            return;
        }

        const text = String(message || '').trim();
        statusNode.hidden = text === '';
        statusNode.textContent = text;
    }

    function setSavingState(isSaving) {
        form.querySelectorAll('input, textarea, select, button').forEach(function (field) {
            field.disabled = !!isSaving;
        });
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            cleanupDictation({ discard: true });
            cleanupRewrite({ keepStatus: true });
            cleanupSummarize({ keepStatus: true });
            releaseEditLock({ keepalive: true });
            if (typeof window.omoCloseDocumentEditorDrawer === 'function') {
                window.omoCloseDocumentEditorDrawer();
            }
        });
    }

    const handleDrawerClose = function () {
        cleanupDictation({ discard: true });
        cleanupRewrite({ keepStatus: true });
        cleanupSummarize({ keepStatus: true });
        releaseEditLock({ keepalive: true });
        destroyHtmlField();
        window.removeEventListener('omo-document-editor-drawer-close', handleDrawerClose);
    };

    window.addEventListener('omo-document-editor-drawer-close', handleDrawerClose);

    if (typeSelect) {
        typeSelect.addEventListener('change', syncTypeUi);
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        setStatus('');

        if (editLockLost) {
            setStatus('Ce document n est plus verrouille pour votre edition. Rechargez le formulaire.');
            return;
        }

        const formData = new FormData(form);
        formData.set('content', !isHtmlTypeSelected()
            ? ''
            : (htmlField && typeof htmlField.getValue === 'function'
                ? String(htmlField.getValue() || '')
                : htmlValueCache));

        setSavingState(true);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-store'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('save_failed');
                }

                return response.json();
            })
            .then(function (payload) {
                if (!payload || payload.status !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'save_failed');
                }

                const refreshPromise = typeof window.omoRefreshDocumentsPanel === 'function'
                    ? window.omoRefreshDocumentsPanel()
                    : Promise.resolve(null);

                return Promise.resolve(refreshPromise).finally(function () {
                    releaseEditLock({ keepalive: true });
                    cleanupDictation({ discard: true });
                    cleanupRewrite({ keepStatus: true });
                    cleanupSummarize({ keepStatus: true });
                    if (typeof window.omoCloseDocumentEditorDrawer === 'function') {
                        window.omoCloseDocumentEditorDrawer();
                    }
                });
            })
            .catch(function (error) {
                setStatus(error && error.message && error.message !== 'save_failed'
                    ? error.message
                    : 'Impossible d enregistrer ce document pour le moment.');
            })
            .finally(function () {
                setSavingState(false);
                syncDictationToolbarButtons();
            });
    });

    syncTypeUi();
    scheduleEditLockHeartbeat();
    scheduleDraftSync(400);
    window.addEventListener('pagehide', function () {
        releaseEditLock({ beacon: true });
    }, { once: true });
})();
</script>
