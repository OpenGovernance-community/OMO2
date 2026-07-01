<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/translation.php';
require_once dirname(__DIR__, 3) . '/includes/server_env_admin.php';

$currentUserId = commonGetCurrentUserId();
$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = null;
$canEditOrganization = false;
$hasStructureTemplates = false;
$organizationName = '';
$isSiteAdmin = commonCurrentUserIsSiteAdminModeEnabled();
if ($currentOrganizationId > 0) {
    $organization = new \dbObject\Organization();
    if ($organization->load($currentOrganizationId)) {
        $canEditOrganization = $organization->canEdit();
        $hasStructureTemplates = $organization->getEnabledStructuralRootHolon() !== null;
        $organizationName = trim((string)$organization->get('name'));
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
                <strong><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.profile.title'), ENT_QUOTES, 'UTF-8') ?></strong>
                <span><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.profile.description'), ENT_QUOTES, 'UTF-8') ?></span>
            </button>

            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive"
                data-omo-settings-modal-title="<?= htmlspecialchars(omoParametersIndexT('parameters.index.card.organization.title'), ENT_QUOTES, 'UTF-8') ?>"
                data-omo-settings-modal-url="/popup/organization_create.php?oid=<?= (int)$currentOrganizationId ?>"
                data-omo-settings-modal-mode="fetch"
                <?= $canEditOrganization ? '' : 'disabled' ?>
            >
                <strong><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.organization.title'), ENT_QUOTES, 'UTF-8') ?></strong>
                <span><?= htmlspecialchars(
                    $canEditOrganization
                        ? omoParametersIndexT('parameters.index.card.organization.description', ['organizationName' => $organizationName])
                        : omoParametersIndexT('parameters.index.card.organization.forbidden'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></span>
            </button>

            <?php if ($hasStructureTemplates): ?>
            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive noMobile"
                data-omo-settings-drawer-title="<?= htmlspecialchars(omoParametersIndexT('parameters.index.card.holon_templates.title'), ENT_QUOTES, 'UTF-8') ?>"
                data-omo-settings-drawer-url="/omo/api/parameters/holon-templates/index.php"
                data-omo-settings-drawer-mode="fetch"
                data-omo-settings-contextual="1"
            >
                <strong><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.holon_templates.title'), ENT_QUOTES, 'UTF-8') ?></strong>
                <span><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.holon_templates.description'), ENT_QUOTES, 'UTF-8') ?></span>
            </button>
            <?php endif; ?>

            <?php if ($isSiteAdmin): ?>
            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive"
                data-omo-settings-modal-title="<?= htmlspecialchars(omoParametersIndexT('parameters.index.card.server_admin.title'), ENT_QUOTES, 'UTF-8') ?>"
                data-omo-settings-modal-url="/omo/api/parameters/server_env_popup.php"
                data-omo-settings-modal-mode="fetch"
            >
                <strong><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.server_admin.title'), ENT_QUOTES, 'UTF-8') ?></strong>
                <span><?= htmlspecialchars(omoParametersIndexT('parameters.index.card.server_admin.description'), ENT_QUOTES, 'UTF-8') ?></span>
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        </div>
    </div>
    <div class="omo-overlay-drawer omo-settings__nested-drawer" data-omo-settings-nested-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-omo-settings-nested-close></div>
        <div class="omo-overlay-drawer__panel">
            <div class="omo-overlay-drawer__header">
                <div class="omo-overlay-drawer__header-copy">
                    <h3 class="omo-overlay-drawer__title" data-omo-settings-nested-title><?= htmlspecialchars(omoParametersIndexT('parameters.index.title'), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="omo-overlay-drawer__description" data-omo-settings-nested-description></p>
                </div>
                <button type="button" class="omo-overlay-drawer__close" data-omo-settings-nested-close><?= htmlspecialchars(omoParametersIndexT('parameters.index.action.close'), ENT_QUOTES, 'UTF-8') ?></button>
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
    align-items: start;
}

.omo-settings__card {
    display: grid;
    gap: 8px;
    text-align: left;
    cursor: pointer;
}

.omo-settings__card strong {
    font-size: 16px;
}

.omo-settings__card span {
    color: var(--color-text-light);
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
                (button.querySelector('span') || {}).textContent || ''
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
