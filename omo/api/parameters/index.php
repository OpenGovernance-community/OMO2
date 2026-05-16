<?php
require_once __DIR__ . '/../bootstrap.php';
require_once dirname(__DIR__, 3) . '/includes/server_env_admin.php';

$currentUserId = commonGetCurrentUserId();
$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = null;
$canEditOrganization = false;
$organizationName = '';
$isSiteAdmin = commonCurrentUserIsSiteAdmin();
$currentLocale = strtolower(trim((string)($_COOKIE['lang'] ?? 'fr')));

if (!in_array($currentLocale, ['fr', 'en', 'de'], true)) {
    $currentLocale = 'fr';
}

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

            <?php if ($isSiteAdmin): ?>
            <button
                type="button"
                class="omo-settings__card omo-card omo-card--interactive"
                data-omo-settings-modal-title="Admin du serveur"
                data-omo-settings-modal-url="/omo/api/parameters/server_env_popup.php"
                data-omo-settings-modal-mode="fetch"
            >
                <strong>Admin du serveur</strong>
                <span>Ouvrir les reglages globaux sensibles du fichier .env, hors configuration de la base de donnees.</span>
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        </div>
        <section class="omo-settings-display generic-section generic-section--stack" data-omo-display-settings>
            <div class="omo-settings-display__header">
                <h3 class="generic-card-title generic-card-title--section">Affichage</h3>
                <p class="omo-settings-display__hint">Choisissez ici la langue et l'apparence de l'interface.</p>
            </div>
            <div class="omo-settings-display__grid">
                <label class="omo-settings-display__field">
                    <span class="omo-settings-display__label">Langue</span>
                    <select class="generic-form-control" data-omo-language-select>
                        <option value="fr" <?= $currentLocale === 'fr' ? 'selected' : '' ?>>FR</option>
                        <option value="en" <?= $currentLocale === 'en' ? 'selected' : '' ?>>EN</option>
                        <option value="de" <?= $currentLocale === 'de' ? 'selected' : '' ?>>DE</option>
                    </select>
                </label>
                <label class="omo-settings-display__field">
                    <span class="omo-settings-display__label">Theme</span>
                    <select class="generic-form-control" data-omo-theme-select>
                        <option value="system">Systeme</option>
                        <option value="light">Clair</option>
                        <option value="dark">Sombre</option>
                    </select>
                </label>
            </div>
            <p class="omo-settings-display__note">La langue recharge la page. Le theme est applique tout de suite.</p>
        </section>
    </div>
</div>

<style>
.omo-settings .omo-panel-view__body_content {
    padding-bottom: 112px;
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

.omo-settings-display {
    position: sticky;
    bottom: 0;
    z-index: 4;
    margin-top: 20px;
    --generic-section-padding-block: 16px;
    --generic-section-padding-inline: 18px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--color-surface, #ffffff) 94%, transparent), color-mix(in srgb, var(--color-surface-alt, #f8fafc) 88%, transparent));
    box-shadow: 0 -10px 30px rgba(15, 23, 42, 0.08);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.omo-settings-display__header {
    display: grid;
    gap: 6px;
}

.omo-settings-display__hint,
.omo-settings-display__note {
    margin: 0;
    color: var(--color-text-light);
}

.omo-settings-display__grid {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.omo-settings-display__field {
    display: grid;
    gap: 8px;
}

.omo-settings-display__label {
    font-size: 13px;
    font-weight: 700;
    color: var(--color-text);
}

@media (max-width: 720px) {
    .omo-settings .omo-panel-view__body_content {
        padding-bottom: 132px;
    }

    .omo-settings-display__grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
(function () {
var languageSelect = document.querySelector('[data-omo-language-select]');
var themeSelect = document.querySelector('[data-omo-theme-select]');

if (themeSelect) {
    if (typeof window.sharedGetThemePreference === 'function') {
        themeSelect.value = window.sharedGetThemePreference();
    } else {
        try {
            themeSelect.value = window.localStorage.getItem('omo-theme-preference') || 'system';
        } catch (error) {
            themeSelect.value = 'system';
        }
    }
}

if (languageSelect) {
    languageSelect.addEventListener('change', function () {
        var nextLocale = String(languageSelect.value || '').toLowerCase();

        if (nextLocale !== 'fr' && nextLocale !== 'en' && nextLocale !== 'de') {
            return;
        }

        if (typeof window.setCookie === 'function') {
            window.setCookie('lang', nextLocale, 365);
        } else {
            document.cookie = 'lang=' + encodeURIComponent(nextLocale) + ';path=/;max-age=' + String(365 * 24 * 60 * 60) + ';SameSite=Lax';
        }

        window.location.reload();
    });
}

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
})();
</script>
