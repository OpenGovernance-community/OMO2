<?php
require_once __DIR__ . '/../bootstrap.php';

$currentUserId = commonGetCurrentUserId();
$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
?>
<div class="omo-settings omo-panel-view">
    <div class="omo-settings__header omo-panel-view__header">
        <div class="omo-panel-view__header-copy">
            <h2 class="omo-panel-view__title">Paramètres</h2>
            <p class="omo-panel-view__description">Retrouvez ici vos réglages personnels ainsi que les écrans de configuration disponibles pour l’organisation.</p>
        </div>
    </div>
    <div class="omo-panel-view__body">
        <?php if ($currentUserId <= 0): ?>
        <div class="omo-settings__empty omo-empty-state">
            Connectez-vous pour accéder à vos paramètres utilisateur.
        </div>
        <?php else: ?>
        <div class="omo-settings__grid omo-card-grid omo-card-grid--fluid">
            <button type="button" class="omo-settings__card omo-card omo-card--interactive" data-topbar-profile-edit>
                <strong>Profil</strong>
                <span>Ouvrir l’édition de votre profil.</span>
            </button>

            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive"
                data-omo-settings-drawer-title="Paramètres du compte"
                data-omo-settings-drawer-url="/popup/parameters.php"
                data-omo-settings-drawer-mode="iframe"
            >
                <strong>Compte</strong>
                <span>Afficher les paramètres généraux de votre compte.</span>
            </button>

            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive"
                data-omo-settings-drawer-title="Paramètres EasyMEMO"
                data-omo-settings-drawer-url="/popup/memo/parameters.php"
                data-omo-settings-drawer-mode="iframe"
            >
                <strong>EasyMEMO</strong>
                <span>Ouvrir les réglages spécifiques à EasyMEMO.</span>
            </button>

            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive"
                data-omo-settings-drawer-title="Modèles de holons"
                data-omo-settings-drawer-url="/omo/api/parameters/holon-templates/index.php"
                data-omo-settings-drawer-mode="fetch"
                data-omo-settings-contextual="1"
                <?= $currentOrganizationId > 0 ? '' : 'disabled' ?>
            >
                <strong>Modèles de holons</strong>
                <span>Configurer les types de nœuds et leurs propriétés pour votre organisation.</span>
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
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
document.querySelectorAll('[data-omo-settings-drawer-url]').forEach(function (button) {
    if (button.dataset.omoSettingsReady === '1') {
        return;
    }

    button.dataset.omoSettingsReady = '1';
    button.addEventListener('click', function () {
        if (button.disabled) {
            return;
        }

        let drawerUrl = button.getAttribute('data-omo-settings-drawer-url');
        if (button.getAttribute('data-omo-settings-contextual') === '1' && typeof window.parseUrl === 'function') {
            const route = window.parseUrl();
            const cid = Number(route && route.cid ? route.cid : 0);
            if (cid > 0) {
                drawerUrl += (drawerUrl.indexOf('?') === -1 ? '?' : '&') + 'cid=' + cid;
            }
        }

        if (typeof window.commonTopbarOpenDrawer !== 'function') {
            window.location.href = drawerUrl;
            return;
        }

        window.commonTopbarOpenDrawer(
            button.getAttribute('data-omo-settings-drawer-title') || 'Paramètres',
            drawerUrl,
            button.getAttribute('data-omo-settings-drawer-mode') || 'iframe'
        );
    });
});
</script>
