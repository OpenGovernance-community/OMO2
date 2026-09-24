<?php

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/holon_scope_helper.php';

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
            <h3 class="generic-card-title generic-card-title--medium">Connexion requise</h3>
            <p>Vous devez etre connecte pour declarer une tension.</p>
        </div>
    <?php elseif ($organizationId <= 0 || !$organization->getId() || !commonCurrentUserHasOrganizationAccess($organizationId)): ?>
        <div class="omo-tension-popup__error generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium">Contexte indisponible</h3>
            <p>Impossible de retrouver l organisation courante pour enregistrer la tension.</p>
        </div>
    <?php else: ?>
        <div class="omo-tension-popup__header generic-drawer-header generic-drawer-header--sticky">
            <div class="generic-drawer-header__copy omo-tension-popup__header-copy omo-tension-popup__hero omo-tension-popup__hero-copy">
                <div class="generic-card-title generic-card-title--eyebrow">Gouvernance partagee</div>
                <h2 class="generic-card-title generic-card-title--large">Nouvelle tension</h2>
                <p>Une tension capte un besoin, un inconfort ou une question ouverte dont l issue n est pas encore connue.</p>
                <div class="omo-tension-popup__meta">
                    <span class="omo-tension-popup__badge">Organisation: <?= htmlspecialchars((string)$organization->getLabel(), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($contextHolon instanceof \dbObject\Holon): ?>
                        <span class="omo-tension-popup__badge">Holon: <?= htmlspecialchars((string)$contextHolon->getLabel(), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="omo-tension-popup__hero-figure" aria-hidden="true">
                <img src="/common/assets/icon-topbar-tension.png" alt="">
            </div>
        </div>
        <div class="omo-tension-popup__shell generic-drawer-content">
        <div class="omo-tension-popup__panel generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium">Saisir la tension</h3>
            <p class="omo-tension-popup__hint generic-help-text">Le titre doit rester tres court, maximum 3 mots. La description reste en texte simple.</p>

            <form class="omo-tension-popup__form generic-stack generic-stack--compact" id="omoTensionForm">
                <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">

                <div class="omo-tension-popup__field generic-stack generic-stack--compact">
                    <label for="omoTensionTitle">Titre</label>
                    <input
                        type="text"
                        class="generic-form-control"
                        id="omoTensionTitle"
                        name="title"
                        maxlength="80"
                        value="<?= htmlspecialchars($defaultTitle, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Ex: roles flous"
                        required
                    >
                    <small class="omo-tension-popup__hint generic-help-text" data-omo-tension-title-hint>3 mots maximum.</small>
                </div>

                <div class="omo-tension-popup__field generic-stack generic-stack--compact">
                    <label for="omoTensionHolon">Holon</label>
                    <?php if ($contextHolon instanceof \dbObject\Holon && !$currentContextSelectable): ?>
                        <small class="omo-tension-popup__hint generic-help-text">Le holon courant n est pas selectionnable directement. Son chemin reste visible en grise ci-dessous.</small>
                    <?php endif; ?>
                    <select class="generic-form-control" id="omoTensionHolon" name="IDholon">
                        <?php
                        $organizationOptionSelectable = false;
                        foreach ($holonOptions as $holonOption) {
                            if (!$rootHolon instanceof \dbObject\Holon || (int)($holonOption['id'] ?? 0) !== (int)$rootHolon->getId()) {
                                continue;
                            }

                            $organizationOptionSelectable = !empty($holonOption['selectable']);
                            break;
                        }
                        ?>
                        <option value=""<?= $organizationOptionSelectable ? '' : ' disabled' ?>>Organisation entiere</option>
                        <?php foreach ($holonOptions as $holonOption): ?>
                            <?php
                            $optionId = (int)($holonOption['id'] ?? 0);
                            $optionLabel = trim((string)($holonOption['label'] ?? ''));
                            if ($optionId <= 0 || $optionLabel === '') {
                                continue;
                            }
                            ?>
                            <option
                                value="<?= $optionId ?>"
                                <?= $selectedHolonId === $optionId ? ' selected' : '' ?>
                                <?= !empty($holonOption['disabled']) ? ' disabled' : '' ?>
                                <?= !empty($holonOption['disabled']) ? ' style="color:#9ca3af;"' : '' ?>
                            >
                                <?= htmlspecialchars($optionLabel . (!empty($holonOption['disabled']) ? ' [chemin]' : ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="omo-tension-popup__field generic-stack generic-stack--compact">
                    <label for="omoTensionDescription">Description</label>
                    <textarea
                        class="generic-form-control"
                        id="omoTensionDescription"
                        name="description"
                        rows="8"
                        placeholder="Decrire le besoin, la situation ou la question ouverte."
                        required
                    ><?= htmlspecialchars($defaultDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="omo-tension-popup__feedback generic-feedback" id="omoTensionFeedback" aria-live="polite"></div>

                <div class="omo-tension-popup__actions generic-action-row">
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-tension-cancel>Annuler</button>
                    <button type="submit" class="generic-action-button generic-action-button--main" id="omoTensionSubmit">Enregistrer</button>
                </div>
            </form>
        </div>
        </div>
    <?php endif; ?>
</div>
<script src="<?= commonAssetUrl('/omo/api/tension_popup.js') ?>"></script>
