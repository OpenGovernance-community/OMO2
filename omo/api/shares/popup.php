<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__, 3) . '/common/share/popup.php';
require_once __DIR__ . '/context.php';

use dbObject\HolonShareLink;

if (!function_exists('omoSharePopupFormatDateTime')) {
    function omoSharePopupFormatDateTime($value)
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

if (!function_exists('omoSharePopupFormatDateTimeInput')) {
    function omoSharePopupFormatDateTimeInput($value)
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

$context = omoShareResolveManageContext($_GET);
if (empty($context['status'])) {
    ?>
    <div class="omo-share-popup omo-share-popup--error generic-soft-panel"><?= htmlspecialchars((string)($context['message'] ?? 'Erreur.'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

$organizationId = (int)$context['organizationId'];
$organization = $context['organization'];
$currentHolon = $context['currentHolon'];
$shareLinks = HolonShareLink::findAllForContext($organizationId, (int)$currentHolon->getId(), false);
$hasExistingLinks = count($shareLinks) > 0;
$defaultLabel = $currentHolon->getDisplayName();
$popupUrl = 'api/shares/popup.php?oid=' . rawurlencode((string)$organizationId) . '&cid=' . rawurlencode((string)$currentHolon->getId());
?>
<div
    class="omo-share-popup"
    id="omoSharePopupRoot"
    data-oid="<?= (int)$organizationId ?>"
    data-cid="<?= (int)$currentHolon->getId() ?>"
    data-popup-url="<?= htmlspecialchars($popupUrl, ENT_QUOTES, 'UTF-8') ?>"
    data-has-links="<?= $hasExistingLinks ? '1' : '0' ?>"
>
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/share/popup.css') ?>">

    <div class="omo-share-popup__shell generic-form-stack generic-form-stack--compact">
    <p class="omo-share-popup__context"><?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?></p>

    <div id="omoSharePopupFeedback" class="omo-share-popup__feedback generic-feedback"></div>

    <div class="omo-share-popup__list" id="omoSharePopupListSection"<?= $hasExistingLinks ? '' : ' hidden' ?>>
        <div class="omo-share-popup__section-heading">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoSharesUiT('liens_existants'), ENT_QUOTES, 'UTF-8') ?></h3>
            <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--main generic-action-button--compact" id="omoSharePopupNewButton"><?= htmlspecialchars(omoSharesUiT('nouveau_lien'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>

        <div class="omo-share-popup__cards generic-correspondence-list generic-correspondence-list--compact">
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
                    class="omo-share-popup__card generic-correspondence-list__row"
                    data-share-card="1"
                    data-share-id="<?= (int)$shareLink->getId() ?>"
                    data-label="<?= htmlspecialchars($shareLabel, ENT_QUOTES, 'UTF-8') ?>"
                    data-dateexpiration="<?= htmlspecialchars(omoSharePopupFormatDateTimeInput($expiresAt), ENT_QUOTES, 'UTF-8') ?>"
                    data-allow-structure="<?= $shareLink->allowsStructure() ? '1' : '0' ?>"
                    data-allow-people="<?= $shareLink->allowsPeople() ? '1' : '0' ?>"
                    data-allow-people-detail="<?= $shareLink->allowsPeopleDetail() ? '1' : '0' ?>"
                    data-has-password="<?= $shareLink->requiresPassword() ? '1' : '0' ?>"
                    data-url="<?= htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8') ?>"
                >
                    <div class="omo-share-popup__row">
                        <div class="omo-share-popup__identity">
                            <h4 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars($shareLabel, ENT_QUOTES, 'UTF-8') ?></h4>
                            <span class="generic-help-text"><?= htmlspecialchars(omoSharesUiT('cree_le'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars(omoSharePopupFormatDateTime($shareLink->get('datecreation')), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="omo-share-popup__card-actions">
                            <button type="button" class="generic-action-button generic-action-button--compact generic-action-button--secondary" data-share-copy="1"><?= htmlspecialchars(omoSharesUiT('copier'), ENT_QUOTES, 'UTF-8') ?></button>
                            <button type="button" class="generic-action-button generic-action-button--icon-only generic-action-button--secondary" data-share-edit="1" aria-label="<?= htmlspecialchars(omoSharesUiT('modifier'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars(omoSharesUiT('modifier'), ENT_QUOTES, 'UTF-8') ?>"><?= commonSharePopupIcon('edit') ?></button>
                            <button type="button" class="generic-action-button generic-action-button--icon-only generic-action-button--danger" data-share-delete="1" aria-label="<?= htmlspecialchars(omoSharesUiT('supprimer'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars(omoSharesUiT('supprimer'), ENT_QUOTES, 'UTF-8') ?>"><?= commonSharePopupIcon('delete') ?></button>
                        </div>
                        <div class="omo-share-popup__badges"><?php commonRenderShareLinkIndicators($shareLink, false, array_filter([
                            $shareLink->allowsStructure() ? omoSharesUiT('structure') : null,
                            $shareLink->allowsPeople() ? omoSharesUiT('personnes') : null,
                            $shareLink->allowsPeopleDetail() ? omoSharesUiT('detail') : null,
                        ])); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>


    </div>

    <div class="omo-share-popup__form-panel" id="omoSharePopupFormSection"<?= $hasExistingLinks ? ' hidden' : '' ?>>
        <div class="omo-share-popup__section">
            <h3 class="omo-share-popup__section-title generic-card-title generic-card-title--medium" id="omoSharePopupFormTitle"><?= htmlspecialchars(omoSharesUiT('form.title_new'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="omo-share-popup__section-text generic-description" id="omoSharePopupFormIntro"><?= htmlspecialchars(omoSharesUiT('form.intro'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <form class="omo-share-popup__form generic-form-stack generic-form-stack--compact" id="omoSharePopupForm">
            <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
            <input type="hidden" name="cid" value="<?= (int)$currentHolon->getId() ?>">
            <input type="hidden" name="share_id" id="omoSharePopupShareId" value="">

            <div class="omo-share-popup__grid generic-form-grid generic-form-grid--pair">
                <div class="omo-share-popup__field generic-form-field generic-form-field--full">
                    <label class="omo-share-popup__label generic-form-label" for="omoSharePopupLabel"><?= htmlspecialchars(omoSharesUiT('libelle_interne'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="omo-share-popup__input generic-form-control generic-form-control--compact" type="text" id="omoSharePopupLabel" name="label" maxlength="150" value="<?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="omo-share-popup__field generic-form-field">
                    <label class="omo-share-popup__label generic-form-label" for="omoSharePopupExpiration"><?= htmlspecialchars(omoSharesUiT('expiration'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="omo-share-popup__input generic-form-control generic-form-control--compact" type="datetime-local" id="omoSharePopupExpiration" name="dateexpiration">
                    <div class="omo-share-popup__hint generic-help-text"><?= htmlspecialchars(omoSharesUiT('laisse_vide_pour_un_lien_sans_date_de_fin'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="omo-share-popup__field generic-form-field">
                    <label class="omo-share-popup__label generic-form-label" for="omoSharePopupPassword"><?= htmlspecialchars(omoSharesUiT('mot_de_passe_optionnel'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="omo-share-popup__input generic-form-control generic-form-control--compact" type="password" id="omoSharePopupPassword" name="password" autocomplete="new-password">
                    <div class="omo-share-popup__hint generic-help-text" id="omoSharePopupPasswordHint"><?= htmlspecialchars(omoSharesUiT('si_un_mot_de_passe_est_defini_il_sera_demande_a_l_ouverture_du_lien'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <label class="omo-share-popup__check generic-checkbox omo-share-popup__field--full" id="omoSharePopupClearPasswordWrap" hidden>
                    <input type="checkbox" name="clear_password" id="omoSharePopupClearPassword">
                    <span>
                        <strong><?= htmlspecialchars(omoSharesUiT('supprimer_le_mot_de_passe_actuel'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </label>
            </div>

            <div class="omo-share-popup__permissions generic-form-grid generic-form-grid--pair">
                <label class="omo-share-popup__check generic-checkbox">
                    <input type="checkbox" name="allow_structure" id="omoSharePopupAllowStructure" checked>
                    <span>
                        <strong><?= htmlspecialchars(omoSharesUiT('voir_la_structure'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </label>

                <label class="omo-share-popup__check generic-checkbox">
                    <input type="checkbox" name="allow_people" id="omoSharePopupAllowPeople">
                    <span>
                        <strong><?= htmlspecialchars(omoSharesUiT('voir_les_personnes'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </label>

                <label class="omo-share-popup__check generic-checkbox">
                    <input type="checkbox" name="allow_people_detail" id="omoSharePopupAllowPeopleDetail">
                    <span>
                        <strong><?= htmlspecialchars(omoSharesUiT('voir_le_detail_des_personnes'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </label>
            </div>

            <div class="omo-share-popup__actions generic-form-actions">
                <?php if ($hasExistingLinks): ?>
                    <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--secondary generic-action-button--compact" id="omoSharePopupCancelButton"><?= htmlspecialchars(omoSharesUiT('retour_a_la_liste'), ENT_QUOTES, 'UTF-8') ?></button>
                <?php endif; ?>
                <button type="submit" class="omo-share-popup__button generic-action-button generic-action-button--main generic-action-button--compact" id="omoSharePopupSubmit"><?= htmlspecialchars(omoSharesUiT('creer_le_lien'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </form>
    </div>
    </div>
</div>

<?= commonPageScriptTags('/omo/api/shares/popup.js', [
    'defaultLabel' => $defaultLabel,
    'text' => [
        'titleNew' => omoSharesUiT('form.title_new'),
        'titleEdit' => omoSharesUiT('form.title_edit'),
        'intro' => omoSharesUiT('form.intro'),
        'passwordHint' => omoSharesUiT('si_un_mot_de_passe_est_defini_il_sera_demande_a_l_ouverture_du_lien'),
        'passwordHintEdit' => omoSharesUiT('form.password_edit'),
    ],
]) ?>
