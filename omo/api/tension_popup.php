<?php
require_once __DIR__ . '/../ui_translations.php';

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/holon_scope_helper.php';

$sourceLang = ['space_hint' => ['text' => 'Choisissez un espace auquel vous appartenez pour déclarer cette tension.', 'context' => 'Tension space assignment picker help.']];
$locale = translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
$lang = loadTranslationBundle('tension_popup', $locale, $sourceLang);

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($_GET['oid'] ?? $_POST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$requestedHolonId = (int)($_GET['cid'] ?? $_POST['cid'] ?? 0);
$organization = new \dbObject\Organization();
$contextHolon = null;
$holonOptions = [];
$rootHolon = null;
$selectedHolonId = 0;
$currentContextSelectable = false;
$defaultTitle = trim((string)($_POST['title'] ?? ''));
$defaultDescription = trim((string)($_POST['description'] ?? ''));
$formAction = '/omo/api/tension_save.php';

if ($organizationId > 0 && $organization->load($organizationId)) {
    $contextHolon = commonHolonScopeResolveOrganizationHolon($organization, $requestedHolonId);
    $rootHolon = $organization->getStructuralRootHolon();
    $holonOptions = commonHolonScopeLoadOptionsForOrganization($organization);

    foreach ($holonOptions as $holonOption) {
        $optionId = (int)($holonOption['id'] ?? 0);
        if ($contextHolon instanceof \dbObject\Holon && $optionId === (int)$contextHolon->getId()) {
            $currentContextSelectable = !empty($holonOption['selectable']);
            if ($currentContextSelectable) {
                $selectedHolonId = $optionId;
            }
            break;
        }
    }

    if ($selectedHolonId <= 0) {
        foreach ($holonOptions as $holonOption) {
            if (!empty($holonOption['selectable'])) {
                $selectedHolonId = (int)($holonOption['id'] ?? 0);
                break;
            }
        }
    }
}
$selectableHolonIds = array_map(static fn($option): int => (int)$option['id'], array_filter($holonOptions, static fn($option): bool => !empty($option['selectable'])));
$organizationOptionSelectable = $rootHolon instanceof \dbObject\Holon && in_array((int)$rootHolon->getId(), $selectableHolonIds, true);
$selectorLabels = commonHolonScopeTargetLabels(t('space_hint', [], $lang, $sourceLang));
$selectedHolon = new \dbObject\Holon();
$selectedHolonLabel = $selectedHolonId > 0 && $selectedHolon->load($selectedHolonId) ? trim((string)$selectedHolon->getDisplayName()) : $selectorLabels['none'];
$holonSelectorConfig = ['organizationId' => $organizationId, 'selectableHolonIds' => array_values($selectableHolonIds),
    'allowOrganization' => $organizationOptionSelectable, 'organizationLabel' => $selectorLabels['none'], 'labels' => $selectorLabels];
?>
<div
    class="omo-tension-popup generic-stack generic-stack--flush"
    id="omoTensionPopup"
    data-submit-url="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>"
    data-default-oid="<?= (int)$organizationId ?>"
    data-default-cid="<?= $contextHolon instanceof \dbObject\Holon ? (int)$contextHolon->getId() : 0 ?>"
>
    <link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/tension_popup.css') ?>">

    <?php if ($currentUserId <= 0): ?>
        <div class="omo-tension-popup__error generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoInterfaceUiT('connexion_requise'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars(omoInterfaceUiT('vous_devez_etre_connecte_pour_declarer_une_tension'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php elseif ($organizationId <= 0 || !$organization->getId() || !commonCurrentUserHasOrganizationAccess($organizationId)): ?>
        <div class="omo-tension-popup__error generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoInterfaceUiT('contexte_indisponible'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars(omoInterfaceUiT('impossible_de_retrouver_l_organisation_courante_pour_enregistrer_la_tension'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php else: ?>
        <div class="omo-tension-popup__header generic-drawer-header generic-drawer-header--sticky">
            <div class="generic-drawer-header__copy omo-tension-popup__header-copy omo-tension-popup__hero omo-tension-popup__hero-copy">
                <div class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoInterfaceUiT('gouvernance_partagee'), ENT_QUOTES, 'UTF-8') ?></div>
                <h2 class="generic-card-title generic-card-title--large"><?= htmlspecialchars(omoInterfaceUiT('nouvelle_tension'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars(omoInterfaceUiT('une_tension_capte_un_besoin_un_inconfort_ou_une_question_ouverte_dont_l_issue_n_est_pas_en'), ENT_QUOTES, 'UTF-8') ?></p>
                <div class="omo-tension-popup__meta">
                    <span class="omo-tension-popup__badge"><?= htmlspecialchars(omoInterfaceUiT('organisation_2'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$organization->getLabel(), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($contextHolon instanceof \dbObject\Holon): ?>
                        <span class="omo-tension-popup__badge"><?= htmlspecialchars(\dbObject\Organization::formatLexiconText('Holon: ', $organization->getLexicon()), ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars((string)$contextHolon->getLabel(), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="omo-tension-popup__hero-figure" aria-hidden="true">
                <img src="/common/assets/icon-topbar-tension.png" alt="">
            </div>
        </div>
        <div class="omo-tension-popup__shell generic-drawer-content">
        <div class="omo-tension-popup__panel generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoInterfaceUiT('saisir_la_tension'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="omo-tension-popup__hint generic-help-text"><?= htmlspecialchars(omoInterfaceUiT('le_titre_doit_rester_tres_court_maximum_3_mots_la_description_reste_en_texte_simple'), ENT_QUOTES, 'UTF-8') ?></p>

            <form class="omo-tension-popup__form generic-stack generic-stack--compact" id="omoTensionForm">
                <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">

                <div class="omo-tension-popup__field generic-stack generic-stack--compact">
                    <label for="omoTensionTitle"><?= htmlspecialchars(omoInterfaceUiT('titre'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input
                        type="text"
                        class="generic-form-control"
                        id="omoTensionTitle"
                        name="title"
                        maxlength="80"
                        value="<?= htmlspecialchars($defaultTitle, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="<?= htmlspecialchars(omoInterfaceUiT('ex_roles_flous'), ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                    <small class="omo-tension-popup__hint generic-help-text" data-omo-tension-title-hint><?= htmlspecialchars(omoInterfaceUiT('3_mots_maximum'), ENT_QUOTES, 'UTF-8') ?></small>
                </div>

                <div class="omo-tension-popup__field generic-stack generic-stack--compact">
                    <label for="omoTensionHolonLabel"><?= htmlspecialchars(\dbObject\Organization::formatLexiconText('Holon', $organization->getLexicon()), ENT_QUOTES, 'UTF-8') ?></label>
                    <?php commonHolonScopeRenderTargetField('IDholon', $selectedHolonId, $selectedHolonLabel, $holonSelectorConfig, ['id' => 'omoTensionHolon']); ?>
                </div>

                <div class="omo-tension-popup__field generic-stack generic-stack--compact">
                    <label for="omoTensionDescription"><?= htmlspecialchars(omoInterfaceUiT('description'), ENT_QUOTES, 'UTF-8') ?></label>
                    <textarea
                        class="generic-form-control"
                        id="omoTensionDescription"
                        name="description"
                        rows="8"
                        placeholder="<?= htmlspecialchars(omoInterfaceUiT('decrire_le_besoin_la_situation_ou_la_question_ouverte'), ENT_QUOTES, 'UTF-8') ?>"
                        required
                    ><?= htmlspecialchars($defaultDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="omo-tension-popup__feedback generic-feedback" id="omoTensionFeedback" aria-live="polite"></div>

                <div class="omo-tension-popup__actions generic-action-row">
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-tension-cancel><?= htmlspecialchars(omoInterfaceUiT('annuler'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="submit" class="generic-action-button generic-action-button--main" id="omoTensionSubmit"><?= htmlspecialchars(omoInterfaceUiT('enregistrer'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </form>
        </div>
        </div>
    <?php endif; ?>
</div>
<script src="<?= commonAssetUrl('/omo/api/tension_popup.js') ?>"></script>
