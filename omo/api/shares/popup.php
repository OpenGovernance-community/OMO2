<?php
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
            <div class="generic-card-title generic-card-title--eyebrow">Partage</div>
            <h2 class="generic-card-title generic-card-title--large">Partager ce contexte</h2>
            <p>Le lien demarrera sur <strong><?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?></strong> et pourra etre transmis a des personnes externes.</p>
        </div>
    </div>
    <div class="omo-share-popup__shell generic-drawer-content">

    <div id="omoSharePopupFeedback" class="omo-share-popup__feedback generic-feedback"></div>

    <div class="omo-share-popup__list" id="omoSharePopupListSection"<?= $hasExistingLinks ? '' : ' hidden' ?>>
        <div class="omo-share-popup__section">
            <h3 class="omo-share-popup__section-title generic-card-title generic-card-title--large">Liens existants</h3>
            <p class="omo-share-popup__section-text generic-description">Tu peux copier, modifier, supprimer ou ajouter un nouveau lien de partage pour ce holon.</p>
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
                                <span>Cree le <?= htmlspecialchars(omoSharePopupFormatDateTime($shareLink->get('datecreation')), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($expiresAt): ?>
                                    <span>Expire le <?= htmlspecialchars(omoSharePopupFormatDateTime($expiresAt), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span>Sans expiration</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="omo-share-popup__badges">
                            <?php if ($shareLink->isExpired()): ?>
                                <span class="omo-share-popup__badge omo-share-popup__badge--expired">Expire</span>
                            <?php endif; ?>
                            <?php if ($shareLink->requiresPassword()): ?>
                                <span class="omo-share-popup__badge">Mot de passe</span>
                            <?php endif; ?>
                            <?php if ($shareLink->allowsStructure()): ?>
                                <span class="omo-share-popup__badge">Structure</span>
                            <?php endif; ?>
                            <?php if ($shareLink->allowsPeople()): ?>
                                <span class="omo-share-popup__badge">Personnes</span>
                            <?php endif; ?>
                            <?php if ($shareLink->allowsPeopleDetail()): ?>
                                <span class="omo-share-popup__badge">Detail</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="omo-share-popup__card-actions">
                        <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--secondary" data-share-copy="1">Copier</button>
                        <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--secondary" data-share-edit="1">Editer</button>
                        <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--danger" data-share-delete="1">Supprimer</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="omo-share-popup__actions">
            <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--main" id="omoSharePopupNewButton">Nouveau lien</button>
        </div>
    </div>

    <div class="omo-share-popup__form-panel" id="omoSharePopupFormSection"<?= $hasExistingLinks ? ' hidden' : '' ?>>
        <div class="omo-share-popup__section">
            <h3 class="omo-share-popup__section-title generic-card-title generic-card-title--large" id="omoSharePopupFormTitle"><?= $hasExistingLinks ? 'Nouveau lien de partage' : 'Creer un lien de partage' ?></h3>
            <p class="omo-share-popup__section-text generic-description" id="omoSharePopupFormIntro"><?= $hasExistingLinks ? 'Configure un nouveau lien ou modifie un lien existant.' : 'Aucun lien n existe encore pour ce holon. Creons le premier.' ?></p>
        </div>

        <form class="omo-share-popup__form" id="omoSharePopupForm">
            <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
            <input type="hidden" name="cid" value="<?= (int)$currentHolon->getId() ?>">
            <input type="hidden" name="share_id" id="omoSharePopupShareId" value="">

            <div class="omo-share-popup__grid">
                <div class="omo-share-popup__field">
                    <label class="omo-share-popup__label generic-form-label" for="omoSharePopupLabel">Libelle interne</label>
                    <input class="omo-share-popup__input generic-form-control" type="text" id="omoSharePopupLabel" name="label" maxlength="150" value="<?= htmlspecialchars($defaultLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="omo-share-popup__hint generic-help-text">Ce libelle est interne et servira a retrouver le lien.</div>
                </div>

                <div class="omo-share-popup__field">
                    <label class="omo-share-popup__label generic-form-label" for="omoSharePopupExpiration">Expiration</label>
                    <input class="omo-share-popup__input generic-form-control" type="datetime-local" id="omoSharePopupExpiration" name="dateexpiration">
                    <div class="omo-share-popup__hint generic-help-text">Laisse vide pour un lien sans date de fin.</div>
                </div>

                <div class="omo-share-popup__field omo-share-popup__field--full">
                    <label class="omo-share-popup__label generic-form-label" for="omoSharePopupPassword">Mot de passe optionnel</label>
                    <input class="omo-share-popup__input generic-form-control" type="password" id="omoSharePopupPassword" name="password" autocomplete="new-password">
                    <div class="omo-share-popup__hint generic-help-text" id="omoSharePopupPasswordHint">Si un mot de passe est defini, il sera demande a l ouverture du lien.</div>
                </div>

                <label class="omo-share-popup__check omo-share-popup__field--full" id="omoSharePopupClearPasswordWrap" hidden>
                    <input type="checkbox" name="clear_password" id="omoSharePopupClearPassword">
                    <span>
                        <strong>Supprimer le mot de passe actuel</strong>
                        <span>Laisse le champ mot de passe vide et coche ceci pour retirer la protection existante.</span>
                    </span>
                </label>
            </div>

            <div class="omo-share-popup__permissions generic-soft-panel generic-soft-panel--stack">
                <label class="omo-share-popup__check">
                    <input type="checkbox" name="allow_structure" id="omoSharePopupAllowStructure" checked>
                    <span>
                        <strong>Voir la structure</strong>
                        <span>Autorise l affichage de la structure et du detail des espaces.</span>
                    </span>
                </label>

                <label class="omo-share-popup__check">
                    <input type="checkbox" name="allow_people" id="omoSharePopupAllowPeople">
                    <span>
                        <strong>Voir les personnes</strong>
                        <span>Autorise l affichage des membres visibles dans le contexte partage.</span>
                    </span>
                </label>

                <label class="omo-share-popup__check">
                    <input type="checkbox" name="allow_people_detail" id="omoSharePopupAllowPeopleDetail">
                    <span>
                        <strong>Voir le detail des personnes</strong>
                        <span>Autorise l ouverture de la popup detail d une personne visible.</span>
                    </span>
                </label>
            </div>

            <div class="omo-share-popup__actions">
                <?php if ($hasExistingLinks): ?>
                    <button type="button" class="omo-share-popup__button generic-action-button generic-action-button--secondary" id="omoSharePopupCancelButton">Retour a la liste</button>
                <?php endif; ?>
                <button type="submit" class="omo-share-popup__button generic-action-button generic-action-button--main" id="omoSharePopupSubmit">Creer le lien</button>
            </div>
        </form>
    </div>
    </div>
</div>

<?= commonPageScriptTags('/omo/api/shares/popup.js', [
    'defaultLabel' => $defaultLabel,
]) ?>
