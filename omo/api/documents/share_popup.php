<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;
use dbObject\DocumentShareLink;

$sourceLang = [
    'documents.share.error.unavailable' => ['text' => 'Impossible de partager ce document.', 'context' => 'Error shown when the document cannot be shared.'],
    'documents.share.hero.title' => ['text' => 'Partager ce document', 'context' => 'Main title of the document share popup.'],
    'documents.share.list.title' => ['text' => 'Liens existants', 'context' => 'Section title listing existing share links.'],
    'documents.share.list.intro' => ['text' => 'Tu peux copier, modifier, supprimer ou ajouter un nouveau lien de partage pour ce document.', 'context' => 'Intro text shown above the list of existing share links.'],
    'documents.share.meta.created' => ['text' => 'Créé le {date}', 'context' => 'Metadata line showing when a share link was created.'],
    'documents.share.meta.expires' => ['text' => 'Expire le {date}', 'context' => 'Metadata line showing when a share link expires.'],
    'documents.share.meta.no_expiration' => ['text' => 'Sans expiration', 'context' => 'Metadata line shown when a share link has no expiration date.'],
    'documents.share.badge.expired' => ['text' => 'Expiré', 'context' => 'Badge shown on an expired share link.'],
    'documents.share.badge.password' => ['text' => 'Mot de passe', 'context' => 'Badge shown when a share link requires a password.'],
    'documents.share.badge.live' => ['text' => 'Temps réel', 'context' => 'Badge shown when a share link allows live follow.'],
    'documents.share.action.copy' => ['text' => 'Copier', 'context' => 'Button used to copy a share link.'],
    'documents.share.action.edit' => ['text' => 'Modifier', 'context' => 'Button used to edit a share link.'],
    'documents.share.action.delete' => ['text' => 'Supprimer', 'context' => 'Button used to delete a share link.'],
    'documents.share.action.new' => ['text' => 'Nouveau lien', 'context' => 'Button used to create a new share link.'],
    'documents.share.form.title_new' => ['text' => 'Nouveau lien de partage', 'context' => 'Form title when creating an additional share link.'],
    'documents.share.form.title_first' => ['text' => 'Créer un lien de partage', 'context' => 'Form title when creating the first share link.'],
    'documents.share.form.intro_new' => ['text' => 'Configure un nouveau lien ou modifie un lien existant.', 'context' => 'Form intro when existing share links already exist.'],
    'documents.share.form.intro_first' => ['text' => 'Aucun lien n’existe encore pour ce document. Créons le premier.', 'context' => 'Form intro when no share link exists yet.'],
    'documents.share.form.label' => ['text' => 'Libellé interne', 'context' => 'Label of the internal share label field.'],
    'documents.share.form.label_hint' => ['text' => 'Ce libellé sert à retrouver le lien.', 'context' => 'Hint shown below the internal share label field.'],
    'documents.share.form.expiration' => ['text' => 'Expiration', 'context' => 'Label of the share link expiration field.'],
    'documents.share.form.expiration_hint' => ['text' => 'Laisse vide pour un lien sans date de fin.', 'context' => 'Hint shown below the expiration field.'],
    'documents.share.form.password' => ['text' => 'Mot de passe optionnel', 'context' => 'Label of the optional password field.'],
    'documents.share.form.password_hint' => ['text' => 'Si un mot de passe est défini, il sera demandé à l’ouverture du lien.', 'context' => 'Hint shown below the password field in create mode.'],
    'documents.share.form.password_hint_edit' => ['text' => 'Laisse vide pour conserver le mot de passe actuel, ou saisis-en un nouveau.', 'context' => 'Hint shown below the password field in edit mode.'],
    'documents.share.form.clear_password_title' => ['text' => 'Supprimer le mot de passe actuel', 'context' => 'Title shown for the clear password checkbox.'],
    'documents.share.form.clear_password_hint' => ['text' => 'Laisse le champ vide et coche ceci pour retirer la protection existante.', 'context' => 'Hint shown for the clear password checkbox.'],
    'documents.share.form.live_title' => ['text' => 'Suivre en temps réel', 'context' => 'Title shown for the live follow checkbox.'],
    'documents.share.form.live_hint' => ['text' => 'Le lien affichera aussi le brouillon temporaire pendant qu’un utilisateur édite le document.', 'context' => 'Hint shown for the live follow checkbox.'],
    'documents.share.form.back' => ['text' => 'Retour à la liste', 'context' => 'Button used to return from the form to the existing links list.'],
    'documents.share.form.submit_create' => ['text' => 'Créer le lien', 'context' => 'Submit button used to create a share link.'],
    'documents.share.form.submit_save' => ['text' => 'Enregistrer', 'context' => 'Submit button used to save an existing share link.'],
    'documents.share.form.title_edit' => ['text' => 'Modifier le lien de partage', 'context' => 'Form title when editing a share link.'],
    'documents.share.form.intro_edit' => ['text' => 'Mets à jour l’expiration, le mot de passe ou le suivi en temps réel.', 'context' => 'Form intro when editing a share link.'],
    'documents.share.confirm_delete' => ['text' => 'Supprimer le lien "{label}" ?', 'context' => 'Confirmation message shown before deleting a share link.'],
];

$lang = omoLoadTranslationBundle('omo_documents_share_popup', $sourceLang);

function omoDocumentsSharePopupT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

if (!function_exists('omoDocumentShareFormatDateTime')) {
    function omoDocumentShareFormatDateTime($value)
    {
        if (!$value) {
            return '';
        }

        try {
            $date = $value instanceof DateTimeInterface ? $value : new DateTime((string)$value);
        } catch (Exception $exception) {
            return '';
        }

        return $date->format('d.m.Y H:i');
    }
}

if (!function_exists('omoDocumentShareFormatDateTimeInput')) {
    function omoDocumentShareFormatDateTimeInput($value)
    {
        if (!$value) {
            return '';
        }

        try {
            $date = $value instanceof DateTimeInterface ? $value : new DateTime((string)$value);
        } catch (Exception $exception) {
            return '';
        }

        return $date->format('Y-m-d\TH:i');
    }
}

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUserId = (int)commonGetCurrentUserId();
$document = new Document();

if (
    $documentId <= 0
    || !$document->load($documentId)
    || $document->isFolder()
    || !$document->supportsHtmlContent()
    || !$document->canViewInOrganizationContext(
        (int)$document->get('IDorganization'),
        (int)$document->get('IDholon') > 0 ? (int)$document->get('IDholon') : null
    )
) {
    ?>
    <div class="omo-share-popup omo-share-popup--error"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.error.unavailable'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

$organizationId = (int)$document->get('IDorganization');
$shareLinks = DocumentShareLink::findAllForContext($organizationId, $documentId, false);
$hasExistingLinks = count($shareLinks) > 0;
$defaultLabel = trim((string)$document->get('title'));
if ($defaultLabel === '') {
    $defaultLabel = 'Document #' . $documentId;
}
$popupUrl = '/omo/api/documents/share_popup.php?id=' . rawurlencode((string)$documentId);
?>
<div
    class="omo-share-popup"
    id="omoDocumentSharePopupRoot"
    data-document-id="<?= (int)$documentId ?>"
    data-popup-url="<?= htmlspecialchars($popupUrl, ENT_QUOTES, 'UTF-8') ?>"
    data-has-links="<?= $hasExistingLinks ? '1' : '0' ?>"
>
    <style>
.omo-share-popup {
    display: grid;
    gap: 0;
    color: var(--color-text, #1f2937);
}

.omo-share-popup__header {
    position: sticky;
    top: 0;
    z-index: 2;
}

.omo-share-popup__header-copy {
    display: grid;
    gap: 8px;
    min-width: 0;
}

.omo-share-popup__shell {
    display: grid;
    gap: 18px;
    padding: 16px 18px 18px;
}
    .omo-share-popup--error {
        padding: 18px;
        border-radius: 16px;
        background: var(--color-surface-alt, #f0f2f5);
        color: var(--color-text-light, #6b7280);
        border: 1px solid var(--color-border, #e5e7eb);
    }
    .omo-share-popup__hero,
    .omo-share-popup__section,
    .omo-share-popup__list,
    .omo-share-popup__form-panel,
    .omo-share-popup__cards,
    .omo-share-popup__form,
    .omo-share-popup__field {
        display: grid;
        gap: 12px;
    }
    .omo-share-popup__hero h2,
    .omo-share-popup__section-title {
        margin: 0;
    }
    .omo-share-popup__hero p,
    .omo-share-popup__section-text,
    .omo-share-popup__hint {
        margin: 0;
        color: var(--color-text-light, #6b7280);
        line-height: 1.5;
    }
    .omo-share-popup__list[hidden],
    .omo-share-popup__form-panel[hidden] {
        display: none;
    }
    .omo-share-popup__feedback {
        min-height: 20px;
        font-size: 13px;
        color: #b91c1c;
    }
    .omo-share-popup__feedback.is-success {
        color: #166534;
    }
    .omo-share-popup__card {
        --generic-section-gap: 10px;
    }
    .omo-share-popup__card-head,
    .omo-share-popup__card-actions,
    .omo-share-popup__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: space-between;
        align-items: start;
    }
    .omo-share-popup__badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .omo-share-popup__badge {
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        background: var(--color-surface-alt, #f0f2f5);
        border: 1px solid var(--color-border, #e5e7eb);
    }
    .omo-share-popup__badge--expired {
        color: #dc2626;
    }
    .omo-share-popup__meta {
        display: grid;
        gap: 4px;
        font-size: 12px;
        color: var(--color-text-light, #6b7280);
    }
    .omo-share-popup__grid {
        display: grid;
        gap: 14px;
    }
    @media (min-width: 760px) {
        .omo-share-popup__grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    .omo-share-popup__field--full {
        grid-column: 1 / -1;
    }
    .omo-share-popup__label {
        font-size: 13px;
        font-weight: 700;
    }
    .omo-share-popup__check {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 10px;
        align-items: start;
    }
    </style>

    <div class="omo-share-popup__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-share-popup__header-copy">
            <div class="generic-card-title generic-card-title--eyebrow">Partage</div>
            <h2 class="generic-card-title generic-card-title--large"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.hero.title'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p>Le lien ouvrira directement le contenu de <strong><?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?></strong>.</p>
        </div>
    </div>
    <div class="omo-share-popup__shell">

    <div id="omoDocumentSharePopupFeedback" class="omo-share-popup__feedback"></div>

    <div class="omo-share-popup__list" id="omoDocumentSharePopupListSection"<?= $hasExistingLinks ? '' : ' hidden' ?>>
        <div class="omo-share-popup__section">
            <h3 class="omo-share-popup__section-title generic-card-title generic-card-title--large"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.list.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="omo-share-popup__section-text"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.list.intro'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="omo-share-popup__cards">
            <?php foreach ($shareLinks as $shareLink): ?>
                <?php
                $shareUrl = $shareLink->buildShareUrl();
                $shareLabel = trim((string)$shareLink->get('label'));
                if ($shareLabel === '') {
                    $shareLabel = $defaultLabel;
                }
                $expiresAt = $shareLink->get('dateexpiration');
                ?>
                <div
                    class="omo-share-popup__card generic-section generic-section--stack"
                    data-share-card="1"
                    data-share-id="<?= (int)$shareLink->getId() ?>"
                    data-label="<?= htmlspecialchars($shareLabel, ENT_QUOTES, 'UTF-8') ?>"
                    data-dateexpiration="<?= htmlspecialchars(omoDocumentShareFormatDateTimeInput($expiresAt), ENT_QUOTES, 'UTF-8') ?>"
                    data-allow-live-follow="<?= $shareLink->allowsLiveFollow() ? '1' : '0' ?>"
                    data-has-password="<?= $shareLink->requiresPassword() ? '1' : '0' ?>"
                    data-url="<?= htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8') ?>"
                >
                    <div class="omo-share-popup__card-head">
                        <div>
                            <h4 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars($shareLabel, ENT_QUOTES, 'UTF-8') ?></h4>
                            <div class="omo-share-popup__meta">
                                <span><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.meta.created', ['date' => omoDocumentShareFormatDateTime($shareLink->get('datecreation'))]), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($expiresAt): ?>
                                    <span><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.meta.expires', ['date' => omoDocumentShareFormatDateTime($expiresAt)]), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.meta.no_expiration'), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="omo-share-popup__badges">
                            <?php if ($shareLink->isExpired()): ?>
                                <span class="omo-share-popup__badge omo-share-popup__badge--expired"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.badge.expired'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($shareLink->requiresPassword()): ?>
                                <span class="omo-share-popup__badge"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.badge.password'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($shareLink->allowsLiveFollow()): ?>
                                <span class="omo-share-popup__badge"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.badge.live'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="omo-share-popup__card-actions">
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-share-copy="1"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.action.copy'), ENT_QUOTES, 'UTF-8') ?></button>
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-share-edit="1"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.action.edit'), ENT_QUOTES, 'UTF-8') ?></button>
                        <button type="button" class="generic-action-button generic-action-button--danger" data-share-delete="1"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.action.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="omo-share-popup__actions">
            <button type="button" class="generic-action-button generic-action-button--main" id="omoDocumentSharePopupNewButton"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.action.new'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>

    <div class="omo-share-popup__form-panel" id="omoDocumentSharePopupFormSection"<?= $hasExistingLinks ? ' hidden' : '' ?>>
        <div class="omo-share-popup__section">
            <h3 class="omo-share-popup__section-title generic-card-title generic-card-title--large" id="omoDocumentSharePopupFormTitle"><?= htmlspecialchars($hasExistingLinks ? omoDocumentsSharePopupT('documents.share.form.title_new') : omoDocumentsSharePopupT('documents.share.form.title_first'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="omo-share-popup__section-text" id="omoDocumentSharePopupFormIntro"><?= htmlspecialchars($hasExistingLinks ? omoDocumentsSharePopupT('documents.share.form.intro_new') : omoDocumentsSharePopupT('documents.share.form.intro_first'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <form class="omo-share-popup__form" id="omoDocumentSharePopupForm">
            <input type="hidden" name="id" value="<?= (int)$documentId ?>">
            <input type="hidden" name="share_id" id="omoDocumentSharePopupShareId" value="">

            <div class="omo-share-popup__grid">
                <div class="omo-share-popup__field">
                    <label class="omo-share-popup__label" for="omoDocumentSharePopupLabel"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="generic-form-control" type="text" id="omoDocumentSharePopupLabel" name="label" maxlength="150" value="<?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="omo-share-popup__hint"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.label_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="omo-share-popup__field">
                    <label class="omo-share-popup__label" for="omoDocumentSharePopupExpiration"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.expiration'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="generic-form-control" type="datetime-local" id="omoDocumentSharePopupExpiration" name="dateexpiration">
                    <div class="omo-share-popup__hint"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.expiration_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="omo-share-popup__field omo-share-popup__field--full">
                    <label class="omo-share-popup__label" for="omoDocumentSharePopupPassword"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.password'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="generic-form-control" type="password" id="omoDocumentSharePopupPassword" name="password" autocomplete="new-password">
                    <div class="omo-share-popup__hint" id="omoDocumentSharePopupPasswordHint"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.password_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <label class="omo-share-popup__check omo-share-popup__field--full" id="omoDocumentSharePopupClearPasswordWrap" hidden>
                    <input type="checkbox" name="clear_password" id="omoDocumentSharePopupClearPassword">
                    <span>
                        <strong><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.clear_password_title'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.clear_password_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </label>
            </div>

            <div class="generic-soft-panel generic-soft-panel--stack">
                <label class="omo-share-popup__check">
                    <input type="checkbox" name="allow_live_follow" id="omoDocumentSharePopupAllowLiveFollow">
                    <span>
                        <strong><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.live_title'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.live_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </label>
            </div>

            <div class="omo-share-popup__actions">
                <?php if ($hasExistingLinks): ?>
                    <button type="button" class="generic-action-button generic-action-button--secondary" id="omoDocumentSharePopupCancelButton"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.back'), ENT_QUOTES, 'UTF-8') ?></button>
                <?php endif; ?>
                <button type="submit" class="generic-action-button generic-action-button--main" id="omoDocumentSharePopupSubmit"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.submit_create'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </form>
    </div>
    </div>
</div>

<script>
(function () {
    const defaultLabel = <?= json_encode($defaultLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const text = <?= json_encode([
        'passwordHint' => omoDocumentsSharePopupT('documents.share.form.password_hint'),
        'passwordHintEdit' => omoDocumentsSharePopupT('documents.share.form.password_hint_edit'),
        'submitCreate' => omoDocumentsSharePopupT('documents.share.form.submit_create'),
        'submitSave' => omoDocumentsSharePopupT('documents.share.form.submit_save'),
        'titleNew' => omoDocumentsSharePopupT('documents.share.form.title_new'),
        'titleFirst' => omoDocumentsSharePopupT('documents.share.form.title_first'),
        'introNew' => omoDocumentsSharePopupT('documents.share.form.intro_new'),
        'introFirst' => omoDocumentsSharePopupT('documents.share.form.intro_first'),
        'titleEdit' => omoDocumentsSharePopupT('documents.share.form.title_edit'),
        'introEdit' => omoDocumentsSharePopupT('documents.share.form.intro_edit'),
        'confirmDelete' => omoDocumentsSharePopupT('documents.share.confirm_delete'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    window.omoDocumentSharePopupInit = function (popupRoot) {
        const root = popupRoot || document.getElementById('omoDocumentSharePopupRoot');
        if (!root || root.dataset.ready === '1') {
            return;
        }

        root.dataset.ready = '1';

        const hasExistingLinks = root.dataset.hasLinks === '1';
        const popupUrl = root.dataset.popupUrl || '/omo/api/documents/share_popup.php';
        const feedback = document.getElementById('omoDocumentSharePopupFeedback');
        const listSection = document.getElementById('omoDocumentSharePopupListSection');
        const formSection = document.getElementById('omoDocumentSharePopupFormSection');
        const form = document.getElementById('omoDocumentSharePopupForm');
        const title = document.getElementById('omoDocumentSharePopupFormTitle');
        const intro = document.getElementById('omoDocumentSharePopupFormIntro');
        const shareIdInput = document.getElementById('omoDocumentSharePopupShareId');
        const labelInput = document.getElementById('omoDocumentSharePopupLabel');
        const expirationInput = document.getElementById('omoDocumentSharePopupExpiration');
        const allowLiveFollow = document.getElementById('omoDocumentSharePopupAllowLiveFollow');
        const passwordHint = document.getElementById('omoDocumentSharePopupPasswordHint');
        const clearPasswordWrap = document.getElementById('omoDocumentSharePopupClearPasswordWrap');
        const clearPasswordInput = document.getElementById('omoDocumentSharePopupClearPassword');
        const submitButton = document.getElementById('omoDocumentSharePopupSubmit');

        const setFeedback = function (message, isSuccess) {
            if (!feedback) {
                return;
            }

            feedback.textContent = message || '';
            feedback.classList.toggle('is-success', Boolean(isSuccess));
        };

        const resetForm = function () {
            form.reset();
            shareIdInput.value = '';
            labelInput.value = defaultLabel;
            allowLiveFollow.checked = false;
            clearPasswordInput.checked = false;
            clearPasswordWrap.hidden = true;
            passwordHint.textContent = text.passwordHint || '';
            submitButton.textContent = text.submitCreate || '';
            title.textContent = hasExistingLinks ? (text.titleNew || '') : (text.titleFirst || '');
            intro.textContent = hasExistingLinks
                ? (text.introNew || '')
                : (text.introFirst || '');
        };

        const openFormForCreate = function () {
            resetForm();
            if (listSection) {
                listSection.hidden = true;
            }
            if (formSection) {
                formSection.hidden = false;
            }
            setFeedback('', false);
        };

        const openFormForEdit = function (card) {
            if (!card) {
                return;
            }

            resetForm();
            shareIdInput.value = card.dataset.shareId || '';
            labelInput.value = card.dataset.label || defaultLabel;
            expirationInput.value = card.dataset.dateexpiration || '';
            allowLiveFollow.checked = card.dataset.allowLiveFollow === '1';
            clearPasswordWrap.hidden = card.dataset.hasPassword !== '1';
            passwordHint.textContent = text.passwordHintEdit || '';
            submitButton.textContent = text.submitSave || '';
            title.textContent = text.titleEdit || '';
            intro.textContent = text.introEdit || '';

            if (listSection) {
                listSection.hidden = true;
            }
            if (formSection) {
                formSection.hidden = false;
            }
            setFeedback('', false);
        };

        const showList = function () {
            if (!hasExistingLinks) {
                return;
            }

            if (listSection) {
                listSection.hidden = false;
            }
            if (formSection) {
                formSection.hidden = true;
            }
            setFeedback('', false);
        };

        const refreshPopup = async function (flashMessage, isSuccess) {
            window.omoDocumentSharePopupFlash = flashMessage ? {
                message: flashMessage,
                success: Boolean(isSuccess)
            } : null;

            const response = await fetch(popupUrl, {
                method: 'GET',
                credentials: 'same-origin'
            });

            const html = await response.text();
            const container = root.parentNode;
            if (container) {
                container.innerHTML = html;
                if (typeof window.omoDocumentSharePopupInit === 'function') {
                    window.omoDocumentSharePopupInit(container.querySelector('#omoDocumentSharePopupRoot'));
                }
            }
        };

        if (window.omoDocumentSharePopupFlash && window.omoDocumentSharePopupFlash.message) {
            setFeedback(window.omoDocumentSharePopupFlash.message, window.omoDocumentSharePopupFlash.success);
            window.omoDocumentSharePopupFlash = null;
        }

        root.addEventListener('click', async function (event) {
            const newButton = event.target.closest('#omoDocumentSharePopupNewButton');
            if (newButton && root.contains(newButton)) {
                openFormForCreate();
                return;
            }

            const cancelButton = event.target.closest('#omoDocumentSharePopupCancelButton');
            if (cancelButton && root.contains(cancelButton)) {
                showList();
                return;
            }

            const copyButton = event.target.closest('[data-share-copy="1"]');
            if (copyButton && root.contains(copyButton)) {
                const card = copyButton.closest('[data-share-card="1"]');
                const url = card ? (card.dataset.url || '') : '';

                if (!url) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(url);
                    setFeedback('Lien copie.', true);
                } catch (error) {
                    setFeedback('Copie impossible automatiquement.', false);
                }
                return;
            }

            const editButton = event.target.closest('[data-share-edit="1"]');
            if (editButton && root.contains(editButton)) {
                openFormForEdit(editButton.closest('[data-share-card="1"]'));
                return;
            }

            const deleteButton = event.target.closest('[data-share-delete="1"]');
            if (!deleteButton || !root.contains(deleteButton)) {
                return;
            }

            const card = deleteButton.closest('[data-share-card="1"]');
            const shareId = card ? Number(card.dataset.shareId || 0) : 0;
            const label = card ? (card.dataset.label || defaultLabel) : defaultLabel;

            if (!shareId) {
                return;
            }

            if (!window.confirm('Supprimer le lien "' + label + '" ?')) {
                return;
            }

            const response = await fetch('/omo/api/documents/share_delete.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    id: String(root.dataset.documentId || ''),
                    share_id: String(shareId)
                }).toString()
            });

            const payload = await response.json().catch(function () {
                return null;
            });

            if (!response.ok || !payload || payload.status !== true) {
                setFeedback(payload && payload.message ? payload.message : 'Suppression impossible.', false);
                return;
            }

            refreshPopup('Lien supprime.', true);
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            setFeedback('', false);

            const response = await fetch('/omo/api/documents/share_create.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form)
            });

            const payload = await response.json().catch(function () {
                return null;
            });

            if (!response.ok || !payload || payload.status !== true) {
                setFeedback(payload && payload.message ? payload.message : 'Enregistrement impossible.', false);
                return;
            }

            refreshPopup(payload.message || 'Lien enregistre.', true);
        });
    };

    window.omoDocumentSharePopupInit();
})();
</script>
