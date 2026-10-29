<?php
require_once __DIR__ . '/ui_translations.php';
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
    class="omo-share-popup generic-stack generic-stack--flush"
    id="omoSharePopupRoot"
    data-oid="<?= (int)$organizationId ?>"
    data-cid="<?= (int)$currentHolon->getId() ?>"
    data-popup-url="<?= htmlspecialchars($popupUrl, ENT_QUOTES, 'UTF-8') ?>"
    data-has-links="<?= $hasExistingLinks ? '1' : '0' ?>"
>
    <link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/shares/shares.css') ?>">

    <div class="omo-share-popup__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-share-popup__header-copy">
            <div class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoSharesUiT('partage'), ENT_QUOTES, 'UTF-8') ?></div>
            <h2 class="generic-card-title generic-card-title--large"><?= htmlspecialchars(omoSharesUiT('partager_ce_contexte'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars(omoSharesUiT('le_lien_demarrera_sur'), ENT_QUOTES, 'UTF-8') ?> <strong><?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?></strong> <?= htmlspecialchars(omoSharesUiT('et_pourra_etre_transmis_a_des_personnes_externes'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>
    <div class="omo-share-popup__shell generic-drawer-content">

    <div id="omoSharePopupFeedback" class="omo-share-popup__feedback generic-feedback"></div>

    <div class="omo-share-popup__list" id="omoSharePopupListSection"<?= $hasExistingLinks ? '' : ' hidden' ?>>
        <div class="omo-share-popup__section">
            <h3 class="omo-share-popup__section-title generic-card-title generic-card-title--large"><?= htmlspecialchars(omoSharesUiT('liens_existants'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="omo-share-popup__section-text generic-description"><?= htmlspecialchars(\dbObject\Organization::formatLexiconText('Tu peux copier, modifier, supprimer ou ajouter un nouveau lien de partage pour ce holon.', $organization->getLexicon()), ENT_QUOTES, 'UTF-8') ?></p>
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
                    data-dateexpiration="<?= htmlspecialchars(omoSharePopupFormatDateTimeInput($expiresAt), ENT_QUOTES, 'UTF-8') ?>"
                    data-allow-structure="<?= $shareLink->allowsStructure() ? '1' : '0' ?>"
                    data-allow-people="<?= $shareLink->allowsPeople() ? '1' : '0' ?>"
                    data-allow-people-detail="<?= $shareLink->allowsPeopleDetail() ? '1' : '0' ?>"
                    data-has-password="<?= $shareLink->requiresPassword() ? '1' : '0' ?>"
                    data-url="<?= htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8') ?>"
                >
                    <div class="omo-share-popup__card-head">
                        <div>
                            <h4 class="omo-share-popup__card-title generic-card-title generic-card-title--medium"><?= htmlspecialchars($shareLabel, ENT_QUOTES, 'UTF-8') ?></h4>
                            <div class="omo-share-popup__meta">
                                <span><?= htmlspecialchars(omoSharesUiT('cree_le'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars(omoSharePopupFormatDateTime($shareLink->get('datecreation')), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($expiresAt): ?>
                                    <span><?= htmlspecialchars(omoSharesUiT('expire_le'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars(omoSharePopupFormatDateTime($expiresAt), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span><?= htmlspecialchars(omoSharesUiT('sans_expiration'), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="omo-share-popup__badges">
                            <?php if ($shareLink->isExpired()): ?>
                                <span class="omo-share-popup__badge omo-share-popup__badge--expired"><?= htmlspecialchars(omoSharesUiT('expire'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($shareLink->requiresPassword()): ?>
                                <span class="omo-share-popup__badge"><?= htmlspecialchars(omoSharesUiT('mot_de_passe'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($shareLink->allowsStructure()): ?>
                                <span class="omo-share-popup__badge"><?= htmlspecialchars(omoSharesUiT('structure'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($shareLink->allowsPeople()): ?>
                                <span class="omo-share-popup__badge"><?= htmlspecialchars(omoSharesUiT('personnes'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($shareLink->allowsPeopleDetail()): ?>
                                <span class="omo-share-popup__badge"><?= htmlspecialchars(omoSharesUiT('detail'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="omo-share-popup__card-actions">
                        <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--secondary" data-share-copy="1"><?= htmlspecialchars(omoSharesUiT('copier'), ENT_QUOTES, 'UTF-8') ?></button>
                        <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--secondary" data-share-edit="1"><?= htmlspecialchars(omoSharesUiT('modifier'), ENT_QUOTES, 'UTF-8') ?></button>
                        <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--danger" data-share-delete="1"><?= htmlspecialchars(omoSharesUiT('supprimer'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="omo-share-popup__actions">
            <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--main" id="omoSharePopupNewButton"><?= htmlspecialchars(omoSharesUiT('nouveau_lien'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>

    <div class="omo-share-popup__form-panel" id="omoSharePopupFormSection"<?= $hasExistingLinks ? ' hidden' : '' ?>>
        <div class="omo-share-popup__section">
            <h3 class="omo-share-popup__section-title generic-card-title generic-card-title--large" id="omoSharePopupFormTitle"><?= $hasExistingLinks ? 'Nouveau lien de partage' : 'Creer un lien de partage' ?></h3>
            <p class="omo-share-popup__section-text generic-description" id="omoSharePopupFormIntro"><?= $hasExistingLinks ? 'Configure un nouveau lien ou modifie un lien existant.' : htmlspecialchars(\dbObject\Organization::formatLexiconText('Aucun lien n existe encore pour ce holon. Creons le premier.'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <form class="omo-share-popup__form" id="omoSharePopupForm">
            <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
            <input type="hidden" name="cid" value="<?= (int)$currentHolon->getId() ?>">
            <input type="hidden" name="share_id" id="omoSharePopupShareId" value="">

            <div class="omo-share-popup__grid">
                <div class="omo-share-popup__field">
                    <label class="omo-share-popup__label generic-form-label" for="omoSharePopupLabel"><?= htmlspecialchars(omoSharesUiT('libelle_interne'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="omo-share-popup__input generic-form-control" type="text" id="omoSharePopupLabel" name="label" maxlength="150" value="<?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="omo-share-popup__hint generic-help-text"><?= htmlspecialchars(omoSharesUiT('ce_libelle_est_interne_et_servira_a_retrouver_le_lien'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="omo-share-popup__field">
                    <label class="omo-share-popup__label generic-form-label" for="omoSharePopupExpiration"><?= htmlspecialchars(omoSharesUiT('expiration'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="omo-share-popup__input generic-form-control" type="datetime-local" id="omoSharePopupExpiration" name="dateexpiration">
                    <div class="omo-share-popup__hint generic-help-text"><?= htmlspecialchars(omoSharesUiT('laisse_vide_pour_un_lien_sans_date_de_fin'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="omo-share-popup__field omo-share-popup__field--full">
                    <label class="omo-share-popup__label generic-form-label" for="omoSharePopupPassword"><?= htmlspecialchars(omoSharesUiT('mot_de_passe_optionnel'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="omo-share-popup__input generic-form-control" type="password" id="omoSharePopupPassword" name="password" autocomplete="new-password">
                    <div class="omo-share-popup__hint generic-help-text" id="omoSharePopupPasswordHint"><?= htmlspecialchars(omoSharesUiT('si_un_mot_de_passe_est_defini_il_sera_demande_a_l_ouverture_du_lien'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <label class="omo-share-popup__check omo-share-popup__field--full" id="omoSharePopupClearPasswordWrap" hidden>
                    <input type="checkbox" name="clear_password" id="omoSharePopupClearPassword">
                    <span>
                        <strong><?= htmlspecialchars(omoSharesUiT('supprimer_le_mot_de_passe_actuel'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars(omoSharesUiT('laisse_le_champ_mot_de_passe_vide_et_coche_ceci_pour_retirer_la_protection_existante'), ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </label>
            </div>

            <div class="omo-share-popup__permissions generic-soft-panel generic-soft-panel--stack">
                <label class="omo-share-popup__check">
                    <input type="checkbox" name="allow_structure" id="omoSharePopupAllowStructure" checked>
                    <span>
                        <strong><?= htmlspecialchars(omoSharesUiT('voir_la_structure'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars(omoSharesUiT('autorise_l_affichage_de_la_structure_et_du_detail_des_espaces'), ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </label>

                <label class="omo-share-popup__check">
                    <input type="checkbox" name="allow_people" id="omoSharePopupAllowPeople">
                    <span>
                        <strong><?= htmlspecialchars(omoSharesUiT('voir_les_personnes'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars(omoSharesUiT('autorise_l_affichage_des_membres_visibles_dans_le_contexte_partage'), ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </label>

                <label class="omo-share-popup__check">
                    <input type="checkbox" name="allow_people_detail" id="omoSharePopupAllowPeopleDetail">
                    <span>
                        <strong><?= htmlspecialchars(omoSharesUiT('voir_le_detail_des_personnes'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars(omoSharesUiT('autorise_l_ouverture_de_la_popup_detail_d_une_personne_visible'), ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </label>
            </div>

            <div class="omo-share-popup__actions">
                <?php if ($hasExistingLinks): ?>
                    <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--secondary" id="omoSharePopupCancelButton"><?= htmlspecialchars(omoSharesUiT('retour_a_la_liste'), ENT_QUOTES, 'UTF-8') ?></button>
                <?php endif; ?>
                <button type="submit" class="omo-share-popup__button generic-action-button generic-action-button--main" id="omoSharePopupSubmit"><?= htmlspecialchars(omoSharesUiT('creer_le_lien'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </form>
    </div>
    </div>
</div>

<?= commonPageScriptTags('/omo/api/shares/popup.js', [
    'defaultLabel' => $defaultLabel,
]) ?>
