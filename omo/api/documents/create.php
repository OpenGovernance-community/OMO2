<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/patreon.php';
require_once dirname(__DIR__, 3) . '/common/openai_text.php';

use dbObject\Document;
use dbObject\Holon;
use dbObject\ObjectVisibility;

$organizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUserId = (int)commonGetCurrentUserId();
$escape = 'omoApiEscape';
$document = new Document();
$isEditing = false;
$canCreate = $organizationId > 0 && $currentUserId > 0 && commonCurrentUserHasOrganizationAccess($organizationId);
$canUseForm = $canCreate;
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
}

$visibilityOptions = ObjectVisibility::getVisibilityTypeOptions();
$documentTitle = '';
$documentDescription = '';
$documentContent = '';
$selectedVisibilityType = ObjectVisibility::TYPE_ORGANIZATION;
$disabledVisibilityTypes = array();
$visibilityHelpText = 'Les portees cercle et role suivent automatiquement le holon du document.';
$contextHolonId = $isEditing ? (int)$document->get('IDholon') : $holonId;

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
    $documentContent = (string)$document->get('content');
    $visibilityRule = $document->getPrimaryVisibilityRuleRow();
    $selectedVisibilityType = ObjectVisibility::normalizeVisibilityType($visibilityRule['visibility_type'] ?? ObjectVisibility::TYPE_ORGANIZATION);
}

if (!empty($disabledVisibilityTypes[$selectedVisibilityType])) {
    $selectedVisibilityType = ObjectVisibility::TYPE_ORGANIZATION;
}
?>
<div class="omo-document-editor">
    <?php if (!$canUseForm): ?>
        <div class="omo-empty-state"><?= $isEditing ? 'Impossible de modifier ce document.' : 'Impossible de creer un document dans ce contexte.' ?></div>
    <?php else: ?>
        <form class="omo-document-editor__form" action="/omo/api/documents/save.php" method="post" data-omo-document-create-form>
            <input type="hidden" name="oid" value="<?= $escape($organizationId) ?>">
            <input type="hidden" name="cid" value="<?= $escape($holonId) ?>">
            <?php if ($isEditing): ?>
                <input type="hidden" name="id" value="<?= (int)$document->getId() ?>">
            <?php endif; ?>

            <div class="omo-document-editor__grid">
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

                <div class="omo-document-editor__field">
                    <span class="omo-document-editor__label">Contenu HTML</span>
                    <div class="omo-document-editor__html" data-omo-document-editor-html></div>
                    <div class="omo-document-editor__dictation-status" data-omo-document-dictation-status hidden></div>
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
    const aiToolsEnabled = <?= $canUseAiTools ? 'true' : 'false' ?>;
    const initialHtmlValue = <?= json_encode($documentContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    let htmlField = null;
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
            submitButton.disabled = isRecording || isTranscribing || isRewriting || isSummarizing;
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

    function mountHtmlField() {
        if (!htmlHost || !window.omoSimpleHtmlField || typeof window.omoSimpleHtmlField.mount !== 'function') {
            return;
        }

        const customButtons = [];

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
            value: String(initialHtmlValue || ''),
            placeholder: 'Redigez le contenu du document...',
            height: 240,
            customButtons: customButtons
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

    if (window.omoSimpleHtmlField && typeof window.omoSimpleHtmlField.mount === 'function') {
        mountHtmlField();
    } else {
        const scriptSelector = 'script[data-omo-simple-html-field-script="1"]';
        const existingScript = document.querySelector(scriptSelector);
        if (existingScript) {
            if (existingScript.getAttribute('data-loaded') === '1') {
                mountHtmlField();
            } else {
                existingScript.addEventListener('load', mountHtmlField, { once: true });
            }
        } else {
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
    }

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
            if (typeof window.omoCloseDocumentEditorDrawer === 'function') {
                window.omoCloseDocumentEditorDrawer();
            }
        });
    }

    const handleDrawerClose = function () {
        cleanupDictation({ discard: true });
        cleanupRewrite({ keepStatus: true });
        cleanupSummarize({ keepStatus: true });
        window.removeEventListener('omo-document-editor-drawer-close', handleDrawerClose);
    };

    window.addEventListener('omo-document-editor-drawer-close', handleDrawerClose);

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        setStatus('');

        const formData = new FormData(form);
        formData.set('content', htmlField && typeof htmlField.getValue === 'function'
            ? String(htmlField.getValue() || '')
            : '');

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
            });
    });
})();
</script>
