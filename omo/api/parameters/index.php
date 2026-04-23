<?php
require_once __DIR__ . '/../bootstrap.php';

$currentUserId = commonGetCurrentUserId();
?>
<div class="omo-settings omo-panel-view">
    <div class="omo-settings__header omo-panel-view__header">
        <div class="omo-panel-view__header-copy">
            <h2 class="omo-panel-view__title">Paramètres</h2>
            <p class="omo-panel-view__description">Retrouvez ici vos réglages personnels et les écrans historiques déjà disponibles dans le projet.</p>
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
            >
                <strong>Compte</strong>
                <span>Afficher les paramètres généraux de votre compte.</span>
            </button>

            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive"
                data-omo-settings-drawer-title="Paramètres EasyMEMO"
                data-omo-settings-drawer-url="/popup/memo/parameters.php"
            >
                <strong>EasyMEMO</strong>
                <span>Ouvrir les réglages spécifiques à EasyMEMO.</span>
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
        if (typeof window.commonTopbarOpenDrawer !== 'function') {
            window.location.href = button.getAttribute('data-omo-settings-drawer-url');
            return;
        }

        window.commonTopbarOpenDrawer(
            button.getAttribute('data-omo-settings-drawer-title') || 'Paramètres',
            button.getAttribute('data-omo-settings-drawer-url'),
            'iframe'
        );
    });
});
</script>
