<?php
require_once __DIR__ . '/../bootstrap.php';

$currentUserId = commonGetCurrentUserId();
$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = null;
$canEditOrganization = false;
$organizationName = '';

if ($currentOrganizationId > 0) {
    $organization = new \dbObject\Organization();
    if ($organization->load($currentOrganizationId)) {
        $canEditOrganization = $organization->canEdit();
        $organizationName = trim((string)$organization->get('name'));
    }
}

if ($organizationName === '') {
    $organizationName = 'cette organisation';
}
?>
<div class="omo-settings omo-panel-view">
    <div class="omo-settings__header omo-panel-view__header">
        <div class="omo-panel-view__header-copy">
            <h2 class="omo-panel-view__title">Parametres</h2>
            <p class="omo-panel-view__description">Retrouvez ici vos reglages personnels ainsi que les ecrans de configuration disponibles pour l'organisation.</p>
        </div>
    </div>
    <div class="omo-panel-view__body">
        <div class='omo-panel-view__body_content'>
        <?php if ($currentUserId <= 0): ?>
        <div class="omo-settings__empty omo-empty-state">
            Connectez-vous pour acceder a vos parametres utilisateur.
        </div>
        <?php else: ?>
        <div class="omo-settings__grid omo-card-grid omo-card-grid--fluid">

            <button type="button" class="omo-settings__card omo-card omo-card--interactive" data-topbar-profile-edit>
                <strong>Profil</strong>
                <span>Ouvrir l'edition de votre profil.</span>
            </button>

            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive"
                data-omo-settings-modal-title="Organisation"
                data-omo-settings-modal-url="/popup/organization_create.php?oid=<?= (int)$currentOrganizationId ?>"
                data-omo-settings-modal-mode="iframe"
                <?= $canEditOrganization ? '' : 'disabled' ?>
            >
                <strong>Organisation</strong>
                <span><?= htmlspecialchars($canEditOrganization ? "Modifier le nom, le nom court, les illustrations et la couleur de " . $organizationName . "." : "Vous devez etre admin de l'organisation pour modifier ces parametres.", ENT_QUOTES, 'UTF-8') ?></span>
            </button>

            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive noMobile"
                data-omo-settings-drawer-title="Modeles de holons"
                data-omo-settings-drawer-url="/omo/api/parameters/holon-templates/index.php"
                data-omo-settings-drawer-mode="fetch"
                data-omo-settings-contextual="1"
                <?= $currentOrganizationId > 0 ? '' : 'disabled' ?>
            >
                <strong>Modeles de holons</strong>
                <span>Configurer les types de noeuds et leurs proprietes pour votre organisation.</span>
            </button>
        </div>
        <?php endif; ?>
        </div>
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
            button.getAttribute('data-omo-settings-drawer-title') || 'Parametres',
            drawerUrl,
            button.getAttribute('data-omo-settings-drawer-mode') || 'iframe'
        );
    });
});

document.querySelectorAll('[data-omo-settings-modal-url]').forEach(function (button) {
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
            button.getAttribute('data-omo-settings-modal-title') || 'Parametres',
            modalUrl,
            button.getAttribute('data-omo-settings-modal-mode') || 'iframe'
        );
    });
});
</script>
