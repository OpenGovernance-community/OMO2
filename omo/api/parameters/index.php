<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/translation.php';
require_once dirname(__DIR__, 3) . '/includes/server_env_admin.php';

$currentUserId = commonGetCurrentUserId();
$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = null;
$isOrganizationAdmin = false;
$isOrganizationAdminModeEnabled = false;
$hasStructureTemplates = false;
$organizationName = '';
$isSiteAdmin = commonCurrentUserIsSiteAdminModeEnabled();
$applicationSettingsCards = [];
$organizationAdminLabel = 'Admin';
if ($currentOrganizationId > 0) {
    $organization = new \dbObject\Organization();
    if ($organization->load($currentOrganizationId)) {
        $isOrganizationAdmin = commonCurrentUserCanUseAdminMode($currentOrganizationId)
            || commonCurrentUserIsSiteAdminModeEnabled();
        $isOrganizationAdminModeEnabled = commonCurrentUserIsAdminModeEnabled($currentOrganizationId)
            || commonCurrentUserIsSiteAdminModeEnabled();
        $hasStructureTemplates = $organization->getEnabledStructuralRootHolon() !== null;
        $organizationName = trim((string)$organization->get('name'));
        $installedApplications = new \dbObject\ArrayApplication();
        $installedApplications->loadEnabledForOrganization($currentOrganizationId, (int)$currentUserId);
        foreach ($installedApplications as $installedApplication) {
            if (!$installedApplication->hasOrganizationParametersEntryPoint()) {
                continue;
            }

            $applicationSettingsCards[] = $installedApplication;
        }
    }
}

if ($organizationName === '') {
    $organizationName = omoParametersIndexT('parameters.index.card.organization.fallback_name');
}

$parametersIndexClientTexts = [
    'title' => omoParametersIndexT('parameters.index.title'),
    'loading' => omoParametersIndexT('parameters.index.drawer.loading'),
    'loadError' => omoParametersIndexT('parameters.index.drawer.error'),
];
$profileCardIconUrl = '/img/omo-parameters/profile.png';
$organizationCardIconUrl = '/img/omo-parameters/organization.png';
$lexiconCardIconUrl = '/img/omo-parameters/dictionnaire.png';
$exportCardIconUrl = '/img/download.png';
$holonTemplateCardIconUrl = '/img/omo-parameters/holon-template.png';
?>
<div class="omo-settings omo-panel-view">
    <div class="omo-settings__header omo-panel-view__header">
        <div class="omo-panel-view__header-copy">
            <h2 class="omo-panel-view__title"><?= htmlspecialchars(omoParametersIndexT('parameters.index.title'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="omo-panel-view__description"><?= htmlspecialchars(omoParametersIndexT('parameters.index.description'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>
    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content">
        <?php if ($currentUserId <= 0): ?>
        <div class="omo-settings__empty omo-empty-state">
            <?= htmlspecialchars(omoParametersIndexT('parameters.index.empty.login'), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php else: ?>
        <div class="omo-settings__grid omo-card-grid omo-card-grid--fluid">

            <button type="button" class="omo-settings__card omo-card omo-card--interactive" data-topbar-profile-edit>
                <span class="omo-settings__card-head">
                    <span class="omo-settings__card-icon-shell">
                        <img class="omo-settings__card-icon black-icon" src="<?= htmlspecialchars($profileCardIconUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                    </span>
                    <span class="omo-settings__card-title-wrap">
                        <span class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.profile.eyebrow'), ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="generic-card-title generic-card-title--big"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.profile.title'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </span>
                <span class="omo-settings__card-description generic-description"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.profile.description'), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="omo-settings__card-footer" aria-hidden="true">
                    <span class="omo-settings__card-cta generic-action-button generic-action-button--main">editer</span>
                </span>
            </button>

            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive"
                data-omo-settings-drawer-title="<?= htmlspecialchars(omoParametersIndexT('parameters.index.card.notifications.title'), ENT_QUOTES, 'UTF-8') ?>"
                data-omo-settings-drawer-url="/omo/api/parameters/notifications/index.php"
                data-omo-settings-drawer-mode="fetch"
            >
                <span class="omo-settings__card-head">
                    <span class="omo-settings__card-icon-shell omo-settings__card-icon-shell--fallback">
                        <span class="omo-settings__card-fallback-icon">PUSH</span>
                    </span>
                    <span class="omo-settings__card-title-wrap">
                        <span class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.notifications.eyebrow'), ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="generic-card-title generic-card-title--big"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.notifications.title'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </span>
                <span class="omo-settings__card-description generic-description"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.notifications.description'), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="omo-settings__card-footer" aria-hidden="true">
                    <span class="omo-settings__card-cta generic-action-button generic-action-button--main"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.notifications.cta'), ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            </button>

            <?php if ($isOrganizationAdmin): ?>
            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive omo-settings__card--admin-mode-required"
                data-omo-settings-drawer-title="<?= htmlspecialchars(omoParametersIndexT('parameters.index.card.lexicon.title'), ENT_QUOTES, 'UTF-8') ?>"
                data-omo-settings-drawer-url="/omo/api/parameters/lexicon/index.php"
                data-omo-settings-drawer-mode="fetch"
                <?= $isOrganizationAdminModeEnabled ? '' : 'disabled aria-disabled="true"' ?>
            >
                <span class="omo-settings__card-head">
                    <span class="omo-settings__card-icon-shell">
                        <img class="omo-settings__card-icon black-icon" src="<?= htmlspecialchars($lexiconCardIconUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                    </span>
                    <span class="omo-settings__card-title-wrap">
                        <span class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.lexicon.eyebrow'), ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="generic-card-title generic-card-title--big"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.lexicon.title'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </span>
                <span class="omo-settings__card-description generic-description"><?= htmlspecialchars(
                    $isOrganizationAdminModeEnabled
                        ? omoParametersIndexT('parameters.index.card.lexicon.description')
                        : omoParametersIndexT('parameters.index.card.lexicon.admin_mode_required', ['adminLabel' => $organizationAdminLabel]),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></span>
                <span class="omo-settings__card-footer" aria-hidden="true">
                    <span class="omo-settings__card-cta generic-action-button <?= $isOrganizationAdminModeEnabled ? 'generic-action-button--main' : 'generic-action-button--secondary' ?>"><?= htmlspecialchars($isOrganizationAdminModeEnabled ? 'editer' : omoParametersIndexT('parameters.index.card.lexicon.admin_mode_cta', ['adminLabel' => $organizationAdminLabel]), ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            </button>

            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive omo-settings__card--admin-mode-required"
                data-omo-settings-drawer-title="<?= htmlspecialchars(omoParametersIndexT('parameters.index.card.organization.title'), ENT_QUOTES, 'UTF-8') ?>"
                data-omo-settings-drawer-url="/popup/organization_create.php?oid=<?= (int)$currentOrganizationId ?>"
                data-omo-settings-drawer-mode="fetch"
                <?= $isOrganizationAdminModeEnabled ? '' : 'disabled aria-disabled="true"' ?>
            >
                <span class="omo-settings__card-head">
                    <span class="omo-settings__card-icon-shell">
                        <img class="omo-settings__card-icon black-icon" src="<?= htmlspecialchars($organizationCardIconUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                    </span>
                    <span class="omo-settings__card-title-wrap">
                        <span class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.organization.eyebrow'), ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="generic-card-title generic-card-title--big"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.organization.title'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </span>
                <span class="omo-settings__card-description generic-description"><?= htmlspecialchars(
                    $isOrganizationAdminModeEnabled
                        ? omoParametersIndexT('parameters.index.card.organization.description', ['organizationName' => $organizationName])
                        : omoParametersIndexT('parameters.index.card.organization.admin_mode_required', ['adminLabel' => $organizationAdminLabel]),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></span>
                <span class="omo-settings__card-footer" aria-hidden="true">
                    <span class="omo-settings__card-cta generic-action-button <?= $isOrganizationAdminModeEnabled ? 'generic-action-button--main' : 'generic-action-button--secondary' ?>"><?= htmlspecialchars($isOrganizationAdminModeEnabled ? 'editer' : omoParametersIndexT('parameters.index.card.organization.admin_mode_cta', ['adminLabel' => $organizationAdminLabel]), ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            </button>

            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive"
                data-omo-settings-modal-title="<?= htmlspecialchars(omoParametersIndexT('parameters.index.card.export.title'), ENT_QUOTES, 'UTF-8') ?>"
                data-omo-settings-modal-url="/omo/api/organizations/export_popup.php"
                data-omo-settings-modal-mode="fetch"
            >
                <span class="omo-settings__card-head">
                    <span class="omo-settings__card-icon-shell">
                        <img class="omo-settings__card-icon black-icon" src="<?= htmlspecialchars($exportCardIconUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                    </span>
                    <span class="omo-settings__card-title-wrap">
                        <span class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.export.eyebrow'), ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="generic-card-title generic-card-title--big"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.export.title'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </span>
                <span class="omo-settings__card-description generic-description"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.export.description', ['organizationName' => $organizationName]), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="omo-settings__card-footer" aria-hidden="true">
                    <span class="omo-settings__card-cta generic-action-button generic-action-button--main">exporter</span>
                </span>
            </button>

            <?php foreach ($applicationSettingsCards as $applicationSettingsCard): ?>
            <?php
                $applicationLabel = trim((string)$applicationSettingsCard->get('label'));
                if ($applicationLabel === '') {
                    $applicationLabel = 'Application';
                }
            ?>
            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive"
                data-omo-settings-drawer-title="<?= htmlspecialchars($applicationLabel, ENT_QUOTES, 'UTF-8') ?>"
                data-omo-settings-drawer-url="<?= htmlspecialchars((string)$applicationSettingsCard->getOrganizationParametersUrl(), ENT_QUOTES, 'UTF-8') ?>"
                data-omo-settings-drawer-mode="fetch"
            >
                <span class="omo-settings__card-head">
                    <span class="omo-settings__card-icon-shell">
                        <img class="omo-settings__card-icon black-icon" src="<?= htmlspecialchars((string)$applicationSettingsCard->get('icon'), ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                    </span>
                    <span class="omo-settings__card-title-wrap">
                        <span class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.application.eyebrow'), ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="generic-card-title generic-card-title--big"><?= htmlspecialchars($applicationLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </span>
                <span class="omo-settings__card-description generic-description"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.application.description', [
                    'applicationName' => $applicationLabel,
                    'organizationName' => $organizationName,
                ]), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="omo-settings__card-footer" aria-hidden="true">
                    <span class="omo-settings__card-cta generic-action-button generic-action-button--main">editer</span>
                </span>
            </button>
            <?php endforeach; ?>

            <?php if ($hasStructureTemplates): ?>
            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive omo-settings__card--admin-mode-required noMobile"
                data-omo-settings-drawer-title="<?= htmlspecialchars(omoParametersIndexT('parameters.index.card.holon_templates.title'), ENT_QUOTES, 'UTF-8') ?>"
                data-omo-settings-drawer-url="/omo/api/parameters/holon-templates/index.php"
                data-omo-settings-drawer-mode="fetch"
                <?= $isOrganizationAdminModeEnabled ? '' : 'disabled aria-disabled="true"' ?>
            >
                <span class="omo-settings__card-head">
                    <span class="omo-settings__card-icon-shell">
                        <img class="omo-settings__card-icon black-icon" src="<?= htmlspecialchars($holonTemplateCardIconUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                    </span>
                    <span class="omo-settings__card-title-wrap">
                        <span class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.holon_templates.eyebrow'), ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="generic-card-title generic-card-title--big"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.holon_templates.title'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </span>
                <span class="omo-settings__card-description generic-description"><?= htmlspecialchars(
                    $isOrganizationAdminModeEnabled
                        ? omoParametersIndexT('parameters.index.card.holon_templates.description')
                        : omoParametersIndexT('parameters.index.card.holon_templates.admin_mode_required', ['adminLabel' => $organizationAdminLabel]),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></span>
                <span class="omo-settings__card-footer" aria-hidden="true">
                    <span class="omo-settings__card-cta generic-action-button <?= $isOrganizationAdminModeEnabled ? 'generic-action-button--main' : 'generic-action-button--secondary' ?>"><?= htmlspecialchars($isOrganizationAdminModeEnabled ? 'editer' : omoParametersIndexT('parameters.index.card.holon_templates.admin_mode_cta', ['adminLabel' => $organizationAdminLabel]), ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            </button>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($isSiteAdmin): ?>
            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive"
                data-omo-settings-modal-title="<?= htmlspecialchars(omoParametersIndexT('parameters.index.card.server_admin.title'), ENT_QUOTES, 'UTF-8') ?>"
                data-omo-settings-modal-url="/omo/api/parameters/server_env_popup.php"
                data-omo-settings-modal-mode="fetch"
            >
                <span class="omo-settings__card-head">
                    <span class="omo-settings__card-icon-shell omo-settings__card-icon-shell--fallback">
                        <span class="omo-settings__card-fallback-icon">ENV</span>
                    </span>
                    <span class="omo-settings__card-title-wrap">
                        <span class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.server_admin.eyebrow'), ENT_QUOTES, 'UTF-8') ?></span>
                        <strong class="generic-card-title generic-card-title--big"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.server_admin.title'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </span>
                <span class="omo-settings__card-description generic-description"><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.server_admin.description'), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="omo-settings__card-footer" aria-hidden="true">
                    <span class="omo-settings__card-cta generic-action-button generic-action-button--main">editer</span>
                </span>
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        </div>
    </div>
    <div class="omo-overlay-drawer omo-settings__nested-drawer" data-omo-settings-nested-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-omo-settings-nested-close></div>
        <div class="omo-overlay-drawer__panel">
            <div class="omo-overlay-drawer__header generic-drawer-header">
                <div class="omo-overlay-drawer__header-copy generic-drawer-header__copy">
                    <h3 class="omo-overlay-drawer__title" data-omo-settings-nested-title><?= htmlspecialchars(omoParametersIndexT('parameters.index.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="omo-overlay-drawer__description" data-omo-settings-nested-description></p>
                </div>
                <div class="generic-drawer-header__actions">
                    <button type="button" class="omo-overlay-drawer__close" data-omo-settings-nested-close><?= htmlspecialchars(omoParametersIndexT('parameters.index.action.close'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </div>
            <div class="omo-overlay-drawer__body" data-omo-settings-nested-body></div>
        </div>
    </div>
</div>

<style>
.omo-settings {
    position: relative;
    min-height: 100%;
}

.omo-settings__grid {
    align-items: stretch;
    grid-auto-rows: 1fr;
    margin: var(--generic-container-gap);
}

.omo-settings__card {
    display: grid;
    grid-template-rows: auto 1fr auto;
    gap: var(--generic-space-4);
    min-height: 220px;
    height: 100%;
    padding: var(--generic-layout-gutter);
    text-align: left;
    cursor: pointer;
    border-radius: var(--generic-container-radius);
    border-color: color-mix(in srgb, var(--color-primary, #2563eb) 12%, var(--color-border, #d1d5db));
    --generic-section-background: var(--generic-container-surface-accent);
    transition:
        transform 0.18s ease,
        box-shadow 0.18s ease,
        border-color 0.18s ease,
        background-color 0.18s ease;
}

.omo-settings__card:not(:disabled):hover,
.omo-settings__card:not(:disabled):focus-visible {
    transform: translateY(-3px);
    box-shadow: 0 24px 44px rgba(15, 23, 42, 0.12);
    border-color: color-mix(in srgb, var(--color-primary, #2563eb) 28%, var(--color-border, #d1d5db));
}

.omo-settings__card:focus-visible {
    outline: 0;
}

.omo-settings__card:disabled {
    cursor: not-allowed;
    opacity: 0.78;
    box-shadow: none;
}

.omo-settings__card--admin-mode-required:disabled {
    opacity: 1;
    border: 1px dashed color-mix(in srgb, var(--color-primary, #2563eb) 38%, var(--color-border, #d1d5db));
    background: linear-gradient(135deg, color-mix(in srgb, var(--color-primary, #2563eb) 7%, var(--color-surface, #ffffff)), var(--color-surface-alt, #f8fafc));
}

.omo-settings__card--admin-mode-required:disabled .omo-settings__card-icon-shell {
    filter: grayscale(0.7);
    opacity: 0.68;
}

.omo-settings__card--admin-mode-required:disabled .omo-settings__card-cta {
    color: var(--color-primary, #2563eb);
    opacity: 1;
}

.omo-settings__card-head {
    display: flex;
    align-items: center;
    gap: var(--generic-container-gap);
}

.omo-settings__card-icon-shell {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    min-width: 64px;
    border-radius: var(--generic-container-radius);
    background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface, #ffffff));
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-primary, #2563eb) 12%, var(--color-border, #d1d5db));
}

.omo-settings__card-icon-shell--fallback {
    background: linear-gradient(135deg, color-mix(in srgb, var(--color-primary, #2563eb) 18%, var(--color-surface, #ffffff)), color-mix(in srgb, var(--color-surface-alt, #f8fafc) 82%, white));
}

.omo-settings__card-icon {
    width: 34px;
    height: 34px;
    object-fit: contain;
}

.omo-settings__card-fallback-icon {
    color: var(--color-primary, #2563eb);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.12em;
}

.omo-settings__card-title-wrap {
    display: grid;
    gap: 5px;
    min-width: 0;
}

.omo-settings__card-title-wrap strong {
    margin: 0;
}

.omo-settings__card-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--generic-container-gap);
    margin-top: auto;
}

.omo-settings__card-cta {
    min-height: 36px;
    padding: 8px 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    pointer-events: none;
}

@media (max-width: 768px) {
    .omo-settings__grid {
        margin: 0;
    }

    .omo-settings__card {
        min-height: 0;
        padding: var(--generic-container-padding-block);
    }
}
</style>

<script>
(function () {
var settingsTexts = <?= json_encode($parametersIndexClientTexts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
document.querySelectorAll('.omo-settings').forEach(function (root) {
    if (!root || root.dataset.omoSettingsInitialized === '1') {
        return;
    }

    root.dataset.omoSettingsInitialized = '1';

    var nestedDrawer = root.querySelector('[data-omo-settings-nested-drawer]');
    var nestedTitle = root.querySelector('[data-omo-settings-nested-title]');
    var nestedDescription = root.querySelector('[data-omo-settings-nested-description]');
    var nestedBody = root.querySelector('[data-omo-settings-nested-body]');
    var nestedRequestToken = 0;

    function resolveSettingsDrawerUrl(button) {
        var drawerUrl = button.getAttribute('data-omo-settings-drawer-url');
        if (!drawerUrl) {
            return '';
        }

        if (button.getAttribute('data-omo-settings-contextual') === '1' && typeof window.parseUrl === 'function') {
            var route = window.parseUrl();
            var cid = Number(route && route.cid ? route.cid : 0);
            if (cid > 0) {
                drawerUrl += (drawerUrl.indexOf('?') === -1 ? '?' : '&') + 'cid=' + cid;
            }
        }

        return drawerUrl;
    }

    function renderNestedDrawerLoading() {
        if (!nestedBody) {
            return;
        }

        if (typeof window.getSkeleton === 'function') {
            nestedBody.innerHTML = window.getSkeleton('panel');
            return;
        }

        nestedBody.innerHTML = '<div class="loading">' + String(settingsTexts.loading || '') + '</div>';
    }

    function renderNestedDrawerError() {
        if (!nestedBody) {
            return;
        }

        nestedBody.innerHTML = '<div class="omo-empty-state">' + String(settingsTexts.loadError || '') + '</div>';
    }

    function closeNestedDrawer() {
        if (!nestedDrawer) {
            return;
        }

        nestedDrawer.classList.remove('is-open');
        window.setTimeout(function () {
            if (!nestedDrawer.classList.contains('is-open')) {
                nestedDrawer.hidden = true;
                if (nestedBody) {
                    nestedBody.innerHTML = '';
                }
            }
        }, 200);
    }

    root.addEventListener('omo-settings-close-nested-drawer', closeNestedDrawer);

    function openNestedDrawer(title, url, mode, description) {
        if (!url) {
            return;
        }

        if (!nestedDrawer || !nestedBody || mode !== 'fetch' || typeof window.jQuery !== 'function') {
            if (typeof window.commonTopbarOpenDrawer === 'function') {
                window.commonTopbarOpenDrawer(title || settingsTexts.title || '', url, mode || 'iframe');
                return;
            }

            window.location.href = url;
            return;
        }

        if (nestedTitle) {
            nestedTitle.textContent = title || settingsTexts.title || '';
        }
        if (nestedDescription) {
            nestedDescription.textContent = description || '';
        }

        renderNestedDrawerLoading();
        nestedDrawer.hidden = false;
        window.requestAnimationFrame(function () {
            nestedDrawer.classList.add('is-open');
        });

        var requestToken = ++nestedRequestToken;
        var resolvedUrl = typeof window.omoResolveAppUrl === 'function'
            ? window.omoResolveAppUrl(url)
            : url;

        window.jQuery.ajax({
            url: resolvedUrl,
            method: 'GET',
            cache: false,
            success: function (data) {
                if (requestToken !== nestedRequestToken || !nestedBody) {
                    return;
                }

                window.jQuery(nestedBody).html(data);
            },
            error: function () {
                if (requestToken !== nestedRequestToken) {
                    return;
                }

                renderNestedDrawerError();
            }
        });
    }

    root.querySelectorAll('[data-omo-settings-nested-close]').forEach(function (button) {
        button.addEventListener('click', closeNestedDrawer);
    });

    root.querySelectorAll('[data-omo-settings-drawer-url]').forEach(function (button) {
        if (button.dataset.omoSettingsReady === '1') {
            return;
        }

        button.dataset.omoSettingsReady = '1';
        button.addEventListener('click', function () {
            if (button.disabled) {
                return;
            }

            var drawerUrl = resolveSettingsDrawerUrl(button);
            if (!drawerUrl) {
                return;
            }

            openNestedDrawer(
                button.getAttribute('data-omo-settings-drawer-title') || settingsTexts.title || '',
                drawerUrl,
                button.getAttribute('data-omo-settings-drawer-mode') || 'iframe',
                (button.querySelector('.omo-settings__card-description') || {}).textContent || ''
            );
        });
    });

    root.querySelectorAll('[data-omo-settings-modal-url]').forEach(function (button) {
        if (button.dataset.omoSettingsModalReady === '1') {
            return;
        }

        button.dataset.omoSettingsModalReady = '1';
        button.addEventListener('click', function () {
            if (button.disabled) {
                return;
            }

            var modalUrl = button.getAttribute('data-omo-settings-modal-url');
            if (!modalUrl) {
                return;
            }

            if (typeof window.commonTopbarOpenModal !== 'function') {
                window.location.href = modalUrl;
                return;
            }

            window.commonTopbarOpenModal(
                button.getAttribute('data-omo-settings-modal-title') || settingsTexts.title || '',
                modalUrl,
                button.getAttribute('data-omo-settings-modal-mode') || 'iframe'
            );
        });
    });
});
})();
</script>
