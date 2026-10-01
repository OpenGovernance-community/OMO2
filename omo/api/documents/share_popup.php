<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;
use dbObject\DocumentShareLink;

$sourceLang = [
    'documents.share.error.unavailable' => ['text' => 'Impossible de partager ce document.', 'context' => 'Error shown when the document cannot be shared.'],
    'documents.share.hero.title' => ['text' => 'Partager ce document', 'context' => 'Main title of the document share popup.'],
    'documents.share.list.title' => ['text' => 'Liens existants', 'context' => 'Section title listing existing share links.'],
    'documents.share.list.intro' => ['text' => 'Vous pouvez copier, modifier, supprimer ou ajouter un nouveau lien de partage pour ce document.', 'context' => 'Intro text shown above the list of existing share links.'],
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
    'documents.share.form.intro_new' => ['text' => 'Configurez un nouveau lien ou modifiez un lien existant.', 'context' => 'Form intro when existing share links already exist.'],
    'documents.share.form.intro_first' => ['text' => 'Aucun lien n’existe encore pour ce document. Créons le premier.', 'context' => 'Form intro when no share link exists yet.'],
    'documents.share.form.label' => ['text' => 'Libellé interne', 'context' => 'Label of the internal share label field.'],
    'documents.share.form.label_hint' => ['text' => 'Ce libellé sert à retrouver le lien.', 'context' => 'Hint shown below the internal share label field.'],
    'documents.share.form.expiration' => ['text' => 'Expiration', 'context' => 'Label of the share link expiration field.'],
    'documents.share.form.expiration_hint' => ['text' => 'Laissez vide pour un lien sans date de fin.', 'context' => 'Hint shown below the expiration field.'],
    'documents.share.form.password' => ['text' => 'Mot de passe optionnel', 'context' => 'Label of the optional password field.'],
    'documents.share.form.password_hint' => ['text' => 'Si un mot de passe est défini, il sera demandé à l’ouverture du lien.', 'context' => 'Hint shown below the password field in create mode.'],
    'documents.share.form.password_hint_edit' => ['text' => 'Laissez vide pour conserver le mot de passe actuel, ou saisissez-en un nouveau.', 'context' => 'Hint shown below the password field in edit mode.'],
    'documents.share.form.clear_password_title' => ['text' => 'Supprimer le mot de passe actuel', 'context' => 'Title shown for the clear password checkbox.'],
    'documents.share.form.clear_password_hint' => ['text' => 'Laissez le champ vide et cochez cette case pour retirer la protection existante.', 'context' => 'Hint shown for the clear password checkbox.'],
    'documents.share.form.live_title' => ['text' => 'Suivre en temps réel', 'context' => 'Title shown for the live follow checkbox.'],
    'documents.share.form.live_hint' => ['text' => 'Le lien affichera aussi le brouillon temporaire pendant qu’un utilisateur édite le document.', 'context' => 'Hint shown for the live follow checkbox.'],
    'documents.share.form.back' => ['text' => 'Retour à la liste', 'context' => 'Button used to return from the form to the existing links list.'],
    'documents.share.form.submit_create' => ['text' => 'Créer le lien', 'context' => 'Submit button used to create a share link.'],
    'documents.share.form.submit_save' => ['text' => 'Enregistrer', 'context' => 'Submit button used to save an existing share link.'],
    'documents.share.form.title_edit' => ['text' => 'Modifier le lien de partage', 'context' => 'Form title when editing a share link.'],
    'documents.share.form.intro_edit' => ['text' => 'Mettez à jour l’expiration, le mot de passe ou le suivi en temps réel.', 'context' => 'Form intro when editing a share link.'],
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
    <div class="omo-share-popup omo-share-popup--error generic-soft-panel"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.error.unavailable'), ENT_QUOTES, 'UTF-8') ?></div>
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
    class="omo-share-popup generic-stack generic-stack--flush"
    id="omoDocumentSharePopupRoot"
    data-document-id="<?= (int)$documentId ?>"
    data-popup-url="<?= htmlspecialchars($popupUrl, ENT_QUOTES, 'UTF-8') ?>"
    data-has-links="<?= $hasExistingLinks ? '1' : '0' ?>"
>
    <link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/documents/share.css') ?>">

    <div class="omo-share-popup__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-share-popup__header-copy">
            <div class="generic-card-title generic-card-title--eyebrow">Partage</div>
            <h2 class="generic-card-title generic-card-title--large"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.hero.title'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p>Le lien ouvrira directement le contenu de <strong><?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?></strong>.</p>
        </div>
    </div>
    <div class="omo-share-popup__shell generic-drawer-content">

    <div id="omoDocumentSharePopupFeedback" class="omo-share-popup__feedback generic-feedback"></div>

    <div class="omo-share-popup__list generic-stack" id="omoDocumentSharePopupListSection"<?= $hasExistingLinks ? '' : ' hidden' ?>>
        <div class="omo-share-popup__section generic-stack">
            <h3 class="omo-share-popup__section-title generic-card-title generic-card-title--large"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.list.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="omo-share-popup__section-text generic-description"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.list.intro'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="omo-share-popup__cards generic-stack">
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
                            <div class="omo-share-popup__meta generic-meta generic-meta--compact">
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

    <div class="omo-share-popup__form-panel generic-stack" id="omoDocumentSharePopupFormSection"<?= $hasExistingLinks ? ' hidden' : '' ?>>
        <div class="omo-share-popup__section generic-stack">
            <h3 class="omo-share-popup__section-title generic-card-title generic-card-title--large" id="omoDocumentSharePopupFormTitle"><?= htmlspecialchars($hasExistingLinks ? omoDocumentsSharePopupT('documents.share.form.title_new') : omoDocumentsSharePopupT('documents.share.form.title_first'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="omo-share-popup__section-text generic-description" id="omoDocumentSharePopupFormIntro"><?= htmlspecialchars($hasExistingLinks ? omoDocumentsSharePopupT('documents.share.form.intro_new') : omoDocumentsSharePopupT('documents.share.form.intro_first'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <form class="omo-share-popup__form generic-form-stack" id="omoDocumentSharePopupForm">
            <input type="hidden" name="id" value="<?= (int)$documentId ?>">
            <input type="hidden" name="share_id" id="omoDocumentSharePopupShareId" value="">

            <div class="omo-share-popup__grid generic-form-grid">
                <div class="omo-share-popup__field generic-form-field">
                    <label class="omo-share-popup__label generic-form-label" for="omoDocumentSharePopupLabel"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="generic-form-control" type="text" id="omoDocumentSharePopupLabel" name="label" maxlength="150" value="<?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="omo-share-popup__hint generic-help-text"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.label_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="omo-share-popup__field generic-form-field">
                    <label class="omo-share-popup__label generic-form-label" for="omoDocumentSharePopupExpiration"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.expiration'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="generic-form-control" type="datetime-local" id="omoDocumentSharePopupExpiration" name="dateexpiration">
                    <div class="omo-share-popup__hint generic-help-text"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.expiration_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="omo-share-popup__field omo-share-popup__field--full generic-form-field generic-form-field--full">
                    <label class="omo-share-popup__label generic-form-label" for="omoDocumentSharePopupPassword"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.password'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="generic-form-control" type="password" id="omoDocumentSharePopupPassword" name="password" autocomplete="new-password">
                    <div class="omo-share-popup__hint generic-help-text" id="omoDocumentSharePopupPasswordHint"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.password_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <label class="omo-share-popup__check generic-checkbox omo-share-popup__field--full generic-form-field--full" id="omoDocumentSharePopupClearPasswordWrap" hidden>
                    <input type="checkbox" name="clear_password" id="omoDocumentSharePopupClearPassword">
                    <span>
                        <strong><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.clear_password_title'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.clear_password_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </label>
            </div>

            <div class="generic-soft-panel generic-soft-panel--stack">
                <label class="omo-share-popup__check generic-checkbox">
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

<?= commonPageScriptTags('/omo/api/documents/share_popup.js', [
    'defaultLabel' => $defaultLabel,
    'text' => [
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
    ],
]) ?>
