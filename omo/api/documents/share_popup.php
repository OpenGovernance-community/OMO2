<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__, 3) . '/common/share/popup.php';
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/document/display.php';

use dbObject\Document;
use dbObject\DocumentShareLink;

$sourceLang = [
    'documents.share.reader.title' => ['text' => 'Lien avec les droits OMO', 'context' => 'Permanent read-only document link section.'],
    'documents.share.reader.hint' => ['text' => 'Lecture selon les droits OMO, avec connexion si nécessaire.', 'context' => 'Explanation of visibility-based document reader access.'],
    'documents.share.reader.copy' => ['text' => 'Copier le lien de consultation', 'context' => 'Copy permanent read-only document link.'],
    'documents.share.reader.open' => ['text' => 'Ouvrir', 'context' => 'Preview permanent document reader.'],
    'documents.share.error.unavailable' => ['text' => 'Impossible de partager ce document.', 'context' => 'Error shown when the document cannot be shared.'],
    'documents.share.hero.title' => ['text' => 'Partager ce document', 'context' => 'Main title of the document share popup.'],
    'documents.share.list.title' => ['text' => 'Liens externes', 'context' => 'Section title listing existing share links.'],
    'documents.share.list.intro' => ['text' => 'Vous pouvez copier, modifier, supprimer ou ajouter un nouveau lien de partage pour ce document.', 'context' => 'Intro text shown above the list of existing share links.'],
    'documents.share.meta.created' => ['text' => 'Créé le {date}', 'context' => 'Metadata line showing when a share link was created.'],
    'documents.share.meta.expires' => ['text' => 'Expire le {date}', 'context' => 'Metadata line showing when a share link expires.'],
    'documents.share.meta.no_expiration' => ['text' => 'Sans expiration', 'context' => 'Metadata line shown when a share link has no expiration date.'],
    'documents.share.badge.expired' => ['text' => 'Expiré', 'context' => 'Badge shown on an expired share link.'],
    'documents.share.badge.password' => ['text' => 'Mot de passe', 'context' => 'Badge shown when a share link requires a password.'],
    'documents.share.badge.live' => ['text' => 'Temps réel', 'context' => 'Badge shown when a share link allows live follow.'],
    'documents.share.badge.edit' => ['text' => 'Édition', 'context' => 'Badge for a collaborative editing link.'],
    'documents.share.form.edit_title' => ['text' => 'Autoriser l’édition', 'context' => 'Collaborative document editing share option.'],
    'documents.share.form.edit_hint' => ['text' => 'Modifier le contenu dans l’outil collaboratif.', 'context' => 'Scope of a collaborative editing share.'],
    'documents.share.action.copy' => ['text' => 'Copier', 'context' => 'Button used to copy a share link.'],
    'documents.share.action.edit' => ['text' => 'Modifier', 'context' => 'Button used to edit a share link.'],
    'documents.share.action.delete' => ['text' => 'Supprimer', 'context' => 'Button used to delete a share link.'],
    'documents.share.action.new' => ['text' => 'Nouveau lien', 'context' => 'Button used to create a new share link.'],
    'documents.share.form.title_new' => ['text' => 'Nouveau lien de partage', 'context' => 'Form title when creating an additional share link.'],
    'documents.share.form.title_first' => ['text' => 'Créer un lien de partage', 'context' => 'Form title when creating the first share link.'],
    'documents.share.form.intro_new' => ['text' => 'Accès au document sans compte OMO.', 'context' => 'Form intro when existing share links already exist.'],
    'documents.share.form.intro_first' => ['text' => 'Accès au document sans compte OMO.', 'context' => 'Form intro explaining optional token-based access separately from the visibility-based reader link.'],
    'documents.share.form.label' => ['text' => 'Nom du lien', 'context' => 'Label of the internal share label field.'],
    'documents.share.form.label_hint' => ['text' => 'Ce libellé sert à retrouver le lien.', 'context' => 'Hint shown below the internal share label field.'],
    'documents.share.form.expiration' => ['text' => 'Expiration', 'context' => 'Label of the share link expiration field.'],
    'documents.share.form.expiration_hint' => ['text' => 'Vide pour ne pas expirer.', 'context' => 'Hint shown below the expiration field.'],
    'documents.share.form.password' => ['text' => 'Mot de passe optionnel', 'context' => 'Label of the optional password field.'],
    'documents.share.form.password_hint' => ['text' => 'Demandé à l’ouverture du lien.', 'context' => 'Hint shown below the password field in create mode.'],
    'documents.share.form.password_hint_edit' => ['text' => 'Vide pour conserver le mot de passe.', 'context' => 'Hint shown below the password field in edit mode.'],
    'documents.share.form.clear_password_title' => ['text' => 'Supprimer le mot de passe actuel', 'context' => 'Title shown for the clear password checkbox.'],
    'documents.share.form.clear_password_hint' => ['text' => 'Laissez le champ vide et cochez cette case pour retirer la protection existante.', 'context' => 'Hint shown for the clear password checkbox.'],
    'documents.share.form.live_title' => ['text' => 'Suivre en temps réel', 'context' => 'Title shown for the live follow checkbox.'],
    'documents.share.form.live_hint' => ['text' => 'Inclure le brouillon pendant l’édition.', 'context' => 'Hint shown for the live follow checkbox.'],
    'documents.share.form.back' => ['text' => 'Retour à la liste', 'context' => 'Button used to return from the form to the existing links list.'],
    'documents.share.form.submit_create' => ['text' => 'Créer le lien', 'context' => 'Submit button used to create a share link.'],
    'documents.share.form.submit_save' => ['text' => 'Enregistrer', 'context' => 'Submit button used to save an existing share link.'],
    'documents.share.form.title_edit' => ['text' => 'Modifier le lien de partage', 'context' => 'Form title when editing a share link.'],
    'documents.share.form.intro_edit' => ['text' => 'Accès au document sans compte OMO.', 'context' => 'Form intro when editing a share link.'],
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
    || !$document->canReadFromLink($currentUserId)
) {
    ?>
    <div class="omo-share-popup omo-share-popup--error generic-soft-panel"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.error.unavailable'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

$organizationId = (int)$document->get('IDorganization');
$canManageShares = $document->canManageShareLinks($currentUserId);
$shareLinks = $canManageShares ? array_filter(DocumentShareLink::findAllForContext($organizationId, $documentId, false),
    static fn(DocumentShareLink $link): bool => !$link->allowsPvContribution()) : [];
$hasExistingLinks = count($shareLinks) > 0;
$defaultLabel = trim((string)$document->get('title'));
if ($defaultLabel === '') {
    $defaultLabel = 'Document #' . $documentId;
}
$popupUrl = '/omo/api/documents/share_popup.php?id=' . rawurlencode((string)$documentId);
$documentDescription = trim((string)preg_replace('/\s+/u', ' ', strip_tags((string)$document->get('description'))));
?>
<div
    class="omo-share-popup"
    id="omoDocumentSharePopupRoot"
    data-document-id="<?= (int)$documentId ?>"
    data-popup-url="<?= htmlspecialchars($popupUrl, ENT_QUOTES, 'UTF-8') ?>"
    data-has-links="<?= $hasExistingLinks ? '1' : '0' ?>"
>
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/share/popup.css') ?>">

    <div class="omo-share-popup__shell generic-form-stack generic-form-stack--compact">
    <div class="omo-share-popup__resource">
        <img class="omo-share-popup__resource-icon black-icon" src="<?= htmlspecialchars(commonAssetUrl(commonDocumentIconUrl($document)), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
        <div class="omo-share-popup__resource-copy">
            <p class="omo-share-popup__resource-title" title="<?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($documentDescription !== ''): ?>
                <p class="omo-share-popup__resource-description" title="<?= htmlspecialchars($documentDescription, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($documentDescription, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div id="omoDocumentSharePopupFeedback" class="omo-share-popup__feedback generic-feedback"></div>

    <section class="omo-share-popup__reader" data-share-card="1" data-url="<?= htmlspecialchars(commonBuildUrl($document->buildReaderUrl()), ENT_QUOTES, 'UTF-8') ?>">
        <label class="generic-form-label" for="omoDocumentReaderLink"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.reader.title'), ENT_QUOTES, 'UTF-8') ?></label>
        <div class="omo-share-popup__reader-actions">
            <input class="generic-form-control generic-form-control--compact" id="omoDocumentReaderLink" type="url" readonly value="<?= htmlspecialchars(commonBuildUrl($document->buildReaderUrl()), ENT_QUOTES, 'UTF-8') ?>">
            <button type="button" class="generic-action-button generic-action-button--compact generic-action-button--secondary" data-share-copy="1" aria-label="<?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.reader.copy'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.action.copy'), ENT_QUOTES, 'UTF-8') ?></button>
            <a class="generic-action-button generic-action-button--compact generic-action-button--secondary" href="<?= htmlspecialchars($document->buildReaderUrl(), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.reader.open'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
        <p class="generic-help-text"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.reader.hint'), ENT_QUOTES, 'UTF-8') ?></p>
    </section>

    <?php if ($canManageShares): ?>
    <div>
    <div class="omo-share-popup__list" id="omoDocumentSharePopupListSection"<?= $hasExistingLinks ? '' : ' hidden' ?>>
        <div class="omo-share-popup__section-heading">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.list.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <button type="button" class="generic-action-button generic-action-button--main generic-action-button--compact" id="omoDocumentSharePopupNewButton"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.action.new'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>

        <div class="omo-share-popup__cards generic-correspondence-list generic-correspondence-list--compact">
            <?php foreach ($shareLinks as $shareLink): ?>
                <?php
                $shareUrl = commonBuildUrl($shareLink->buildShareUrl());
                $shareLabel = trim((string)$shareLink->get('label'));
                if ($shareLabel === '') {
                    $shareLabel = $defaultLabel;
                }
                $expiresAt = $shareLink->get('dateexpiration');
                ?>
                <div
                    class="omo-share-popup__card generic-correspondence-list__row"
                    data-share-card="1"
                    data-share-id="<?= (int)$shareLink->getId() ?>"
                    data-label="<?= htmlspecialchars($shareLabel, ENT_QUOTES, 'UTF-8') ?>"
                    data-dateexpiration="<?= htmlspecialchars(omoDocumentShareFormatDateTimeInput($expiresAt), ENT_QUOTES, 'UTF-8') ?>"
                    data-allow-live-follow="<?= $shareLink->allowsLiveFollow() ? '1' : '0' ?>"
                    data-allow-edit="<?= $shareLink->allowsDocumentEditing($document) ? '1' : '0' ?>"
                    data-has-password="<?= $shareLink->requiresPassword() ? '1' : '0' ?>"
                    data-url="<?= htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8') ?>"
                >
                    <div class="omo-share-popup__row">
                        <div class="omo-share-popup__identity">
                            <h4 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars($shareLabel, ENT_QUOTES, 'UTF-8') ?></h4>
                            <span class="generic-help-text"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.meta.created', ['date' => omoDocumentShareFormatDateTime($shareLink->get('datecreation'))]), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="omo-share-popup__card-actions">
                            <button type="button" class="generic-action-button generic-action-button--compact generic-action-button--secondary" data-share-copy="1"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.action.copy'), ENT_QUOTES, 'UTF-8') ?></button>
                            <button type="button" class="generic-action-button generic-action-button--icon-only generic-action-button--secondary" data-share-edit="1" aria-label="<?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.action.edit'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.action.edit'), ENT_QUOTES, 'UTF-8') ?>"><?= commonSharePopupIcon('edit') ?></button>
                            <button type="button" class="generic-action-button generic-action-button--icon-only generic-action-button--danger" data-share-delete="1" aria-label="<?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.action.delete'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.action.delete'), ENT_QUOTES, 'UTF-8') ?>"><?= commonSharePopupIcon('delete') ?></button>
                        </div>
                        <div class="omo-share-popup__badges"><?php commonRenderShareLinkIndicators($shareLink, $shareLink->allowsDocumentEditing($document), $shareLink->allowsLiveFollow() ? [omoDocumentsSharePopupT('documents.share.badge.live')] : []); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>


    </div>

    <div class="omo-share-popup__form-panel" id="omoDocumentSharePopupFormSection"<?= $hasExistingLinks ? ' hidden' : '' ?>>
        <div class="omo-share-popup__section">
            <h3 class="omo-share-popup__section-title generic-card-title generic-card-title--medium" id="omoDocumentSharePopupFormTitle"><?= htmlspecialchars($hasExistingLinks ? omoDocumentsSharePopupT('documents.share.form.title_new') : omoDocumentsSharePopupT('documents.share.form.title_first'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="omo-share-popup__section-text generic-description" id="omoDocumentSharePopupFormIntro"><?= htmlspecialchars($hasExistingLinks ? omoDocumentsSharePopupT('documents.share.form.intro_new') : omoDocumentsSharePopupT('documents.share.form.intro_first'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <form class="omo-share-popup__form generic-form-stack generic-form-stack--compact" id="omoDocumentSharePopupForm">
            <input type="hidden" name="id" value="<?= (int)$documentId ?>">
            <input type="hidden" name="share_id" id="omoDocumentSharePopupShareId" value="">

            <div class="omo-share-popup__grid generic-form-grid generic-form-grid--pair">
                <div class="omo-share-popup__field generic-form-field generic-form-field--full">
                    <label class="omo-share-popup__label generic-form-label" for="omoDocumentSharePopupLabel"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="generic-form-control generic-form-control--compact" type="text" id="omoDocumentSharePopupLabel" name="label" maxlength="150" value="<?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="omo-share-popup__field generic-form-field">
                    <label class="omo-share-popup__label generic-form-label" for="omoDocumentSharePopupExpiration"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.expiration'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="generic-form-control generic-form-control--compact" type="datetime-local" id="omoDocumentSharePopupExpiration" name="dateexpiration">
                    <div class="omo-share-popup__hint generic-help-text"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.expiration_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="omo-share-popup__field generic-form-field">
                    <label class="omo-share-popup__label generic-form-label" for="omoDocumentSharePopupPassword"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.password'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="generic-form-control generic-form-control--compact" type="password" id="omoDocumentSharePopupPassword" name="password" autocomplete="new-password">
                    <div class="omo-share-popup__hint generic-help-text" id="omoDocumentSharePopupPasswordHint"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.password_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <label class="omo-share-popup__check generic-checkbox omo-share-popup__field--full generic-form-field--full" id="omoDocumentSharePopupClearPasswordWrap" hidden>
                    <input type="checkbox" name="clear_password" id="omoDocumentSharePopupClearPassword">
                    <span>
                        <strong><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.clear_password_title'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </label>
            </div>

            <?php if ($document->supportsSharedEditing() || $document->supportsHtmlContent()): ?>
            <div class="omo-share-popup__permissions generic-form-stack generic-form-stack--compact">
                <?php if ($document->supportsSharedEditing()): ?>
                <label class="omo-share-popup__check generic-checkbox">
                    <input type="checkbox" name="allow_edit" id="omoDocumentSharePopupAllowEdit">
                    <span>
                        <strong><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.edit_title'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.edit_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </label>
                <?php endif; ?>
                <div<?= $document->supportsHtmlContent() ? '' : ' hidden' ?>>
                <label class="omo-share-popup__check generic-checkbox">
                    <input type="checkbox" name="allow_live_follow" id="omoDocumentSharePopupAllowLiveFollow">
                    <span>
                        <strong><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.live_title'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.live_hint'), ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </label>
                </div>
            </div>
            <?php endif; ?>

            <div class="omo-share-popup__actions generic-form-actions">
                <?php if ($hasExistingLinks): ?>
                    <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--compact" id="omoDocumentSharePopupCancelButton"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.back'), ENT_QUOTES, 'UTF-8') ?></button>
                <?php endif; ?>
                <button type="submit" class="generic-action-button generic-action-button--main generic-action-button--compact" id="omoDocumentSharePopupSubmit"><?= htmlspecialchars(omoDocumentsSharePopupT('documents.share.form.submit_create'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </form>
    </div>
    </div>
    <?php endif; ?>
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
