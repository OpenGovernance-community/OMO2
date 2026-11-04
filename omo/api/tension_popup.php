<?php
require_once __DIR__ . '/../ui_translations.php';

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/holon_scope_helper.php';
require_once __DIR__ . '/tension_translations.php';

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
$locale = translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
$contextQuery = '?oid=' . $organizationId . '&lang=' . rawurlencode($locale);
$formAction = '/omo/api/tension_save.php' . $contextQuery;

if ($currentUserId > 0 && $organizationId > 0 && $organization->load($organizationId)
    && commonCurrentUserHasOrganizationAccess($organizationId)) {
    $contextHolon = commonHolonScopeResolveOrganizationHolon($organization, $requestedHolonId);
    $rootHolon = $organization->getStructuralRootHolon();
    foreach (\dbObject\Tension::loadCreationTargets($organizationId, $currentUserId) as $targetHolon) {
        $holonOptions[] = ['id' => (int)$targetHolon->getId(), 'selectable' => true];
    }

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
$selectorLabels = commonHolonScopeTargetLabels(omoTensionT('space_hint'));
$selectedHolon = new \dbObject\Holon();
$selectedHolonLabel = $selectedHolonId > 0 && $selectedHolon->load($selectedHolonId) ? trim((string)$selectedHolon->getDisplayName()) : $selectorLabels['none'];
$holonSelectorConfig = ['organizationId' => $organizationId, 'selectableHolonIds' => array_values($selectableHolonIds),
    'allowOrganization' => $organizationOptionSelectable, 'organizationLabel' => $selectorLabels['none'], 'labels' => $selectorLabels];
$listTexts = [];
foreach (array_keys(omoTensionSourceLang()) as $key) {
    $listTexts[$key] = omoTensionT($key);
}
?>
<div
    class="omo-tension-popup generic-stack generic-stack--flush"
    id="omoTensionPopup"
    data-submit-url="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>"
    data-default-oid="<?= (int)$organizationId ?>"
    data-default-cid="<?= $contextHolon instanceof \dbObject\Holon ? (int)$contextHolon->getId() : 0 ?>"
    data-list-url="<?= omoApiEscape('/omo/api/tension_list.php' . $contextQuery) ?>"
    data-delete-url="<?= omoApiEscape('/omo/api/tension_delete.php' . $contextQuery) ?>"
    data-csrf="<?= omoApiEscape(commonCsrfToken()) ?>"
    data-texts="<?= omoApiEscape(json_encode($listTexts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
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
        <div class="generic-drawer-header">
            <div class="generic-drawer-header__copy">
                <p class="generic-description generic-description--small"><?= htmlspecialchars(omoInterfaceUiT('organisation_2'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$organization->getLabel(), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
        <div class="omo-tension-popup__shell generic-drawer-content">
        <div class="generic-tabs generic-tabs--embedded generic-tabs--no-lift" data-generic-tabs>
            <div class="generic-tabs__list" aria-label="<?= omoApiEscape(omoTensionT('tabs')) ?>">
                <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="omoTensionCreatePanel"><?= omoApiEscape(omoInterfaceUiT('nouvelle_tension')) ?></button>
                <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omoTensionListPanel"><?= omoApiEscape(omoTensionT('existing')) ?></button>
            </div>
            <div class="generic-tabs__panels">
        <div class="generic-tabs__panel generic-section generic-section--stack" id="omoTensionCreatePanel" data-generic-tab-panel>
            <?php if ($selectableHolonIds === []): ?>
            <p class="generic-help-text"><?= omoApiEscape(omoTensionT('no_target')) ?></p>
            <?php else: ?>
            <p class="generic-description generic-description--small"><?= omoApiEscape(omoInterfaceUiT('une_tension_capte_un_besoin_un_inconfort_ou_une_question_ouverte_dont_l_issue_n_est_pas_en')) ?></p>
            <p class="omo-tension-popup__hint generic-help-text"><?= htmlspecialchars(omoInterfaceUiT('le_titre_doit_rester_tres_court_maximum_3_mots_la_description_reste_en_texte_simple'), ENT_QUOTES, 'UTF-8') ?></p>

            <form class="omo-tension-popup__form generic-stack generic-stack--compact" id="omoTensionForm">
                <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
                <input type="hidden" name="_csrf" value="<?= omoApiEscape(commonCsrfToken()) ?>">

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
                        rows="5"
                        placeholder="<?= htmlspecialchars(omoInterfaceUiT('decrire_le_besoin_la_situation_ou_la_question_ouverte'), ENT_QUOTES, 'UTF-8') ?>"
                        required
                    ><?= htmlspecialchars($defaultDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="omo-tension-popup__actions generic-action-row">
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-tension-cancel><?= htmlspecialchars(omoInterfaceUiT('annuler'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="submit" class="generic-action-button generic-action-button--main" id="omoTensionSubmit"><?= htmlspecialchars(omoInterfaceUiT('enregistrer'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </form>
            <?php endif; ?>
        </div>
        <div class="generic-tabs__panel generic-section generic-section--stack" id="omoTensionListPanel" data-generic-tab-panel hidden>
            <div class="generic-action-row">
                <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-tension-refresh><?= omoApiEscape(omoTensionT('refresh')) ?></button>
            </div>
            <p class="generic-help-text" id="omoTensionListStatus" role="status"></p>
            <div class="generic-stack generic-stack--compact" id="omoTensionList"></div>
        </div>
            </div>
        </div>
        <div class="generic-feedback" id="omoTensionFeedback" aria-live="polite" hidden></div>
        </div>
    <?php endif; ?>
</div>
<script src="<?= commonAssetUrl('/omo/api/tension_popup.js') ?>"></script>
