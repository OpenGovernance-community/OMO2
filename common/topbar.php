<?php

function commonRenderTopbar(array $options = [])
{
    static $assetsLoaded = false;

    $organizationContext = $options['organization'] ?? null;
    $brandLogo = (string)($options['brandLogo'] ?? ($organizationContext['logo'] ?? '/img/logo-OGC.png'));
    if ($brandLogo === '') {
        $brandLogo = '/img/logo-OGC.png';
    }

    $config = [
        'appKey' => (string)($options['appKey'] ?? 'app'),
        'appLabel' => (string)($options['appLabel'] ?? 'Application'),
        'userName' => (string)($options['userName'] ?? commonGetCurrentUserDisplayName() ?: 'Profil'),
        'brandAlt' => (string)($options['brandAlt'] ?? ($organizationContext['name'] ?? ($options['appLabel'] ?? 'Application'))),
        'logoutPath' => (string)($options['logoutPath'] ?? '/common/logout.php'),
        'logoutReturnTo' => commonNormalizeLocalPath($options['logoutReturnTo'] ?? ($_SERVER['REQUEST_URI'] ?? '/'), '/'),
        'search' => [
            'enabled' => !empty($options['search']['enabled']),
            'placeholder' => (string)($options['search']['placeholder'] ?? 'Rechercher'),
            'callback' => (string)($options['search']['callback'] ?? ''),
            'buttonLabel' => (string)($options['search']['buttonLabel'] ?? 'Recherche'),
        ],
        'profile' => [
            'enabled' => array_key_exists('enabled', $options['profile'] ?? []) ? !empty($options['profile']['enabled']) : true,
            'editLabel' => (string)($options['profile']['editLabel'] ?? 'Editer le profil'),
            'editTitle' => (string)($options['profile']['editTitle'] ?? 'Profil'),
            'editMode' => (string)($options['profile']['editMode'] ?? 'iframe'),
            'editUrl' => (string)($options['profile']['editUrl'] ?? '/popup/profil.php'),
            'editCallback' => (string)($options['profile']['editCallback'] ?? ''),
            'buttonLabel' => (string)($options['profile']['buttonLabel'] ?? 'Profil'),
        ],
        'helpLabel' => (string)($options['helpLabel'] ?? 'Aide'),
        'helpItems' => array_values($options['helpItems'] ?? []),
    ];

    if (!$assetsLoaded) {
        echo '<link rel="stylesheet" href="/common/assets/topbar.css">' . PHP_EOL;
        echo '<script src="/common/assets/topbar.js" defer></script>' . PHP_EOL;
        $assetsLoaded = true;
    }
    ?>
<header class="topbar common-topbar" data-app-key="<?= htmlspecialchars($config['appKey']) ?>">
    <div class="common-topbar__left">
        <div class="common-topbar__brand" title="<?= htmlspecialchars($config['brandAlt']) ?>">
            <img src="<?= htmlspecialchars($brandLogo) ?>" alt="<?= htmlspecialchars($config['brandAlt']) ?>" class="common-topbar__brand-logo">
        </div>
    </div>

    <div class="common-topbar__actions">
        <?php if (!empty($config['search']['enabled'])): ?>
        <div class="common-topbar__menu-wrap common-topbar__menu-wrap--panel">
            <button type="button" class="common-topbar__action common-topbar__action--square" data-topbar-menu-trigger="search">
                <span class="common-topbar__action-icon" aria-hidden="true">
                    <img src="/common/assets/icon-topbar-search.png" alt="" class="common-topbar__icon-image">
                </span>
                <span class="common-topbar__action-label"><?= htmlspecialchars($config['search']['buttonLabel']) ?></span>
            </button>
            <div class="common-topbar__menu common-topbar__menu--panel common-topbar__menu--right" data-topbar-menu="search">
                <form class="common-topbar__search-panel" data-topbar-search-form>
                    <label class="common-topbar__search-panel-label" for="commonTopbarSearchInput"><?= htmlspecialchars($config['search']['placeholder']) ?></label>
                    <div class="common-topbar__search-panel-row">
                        <input
                            type="search"
                            id="commonTopbarSearchInput"
                            class="common-topbar__search-input"
                            data-topbar-search-input
                            placeholder="<?= htmlspecialchars($config['search']['placeholder']) ?>"
                            aria-label="<?= htmlspecialchars($config['search']['placeholder']) ?>"
                        >
                        <button type="submit" class="common-topbar__search-button">Lancer</button>
                    </div>
                    <div class="common-topbar__search-panel-hint">D’autres filtres avancés pourront s’ajouter ici.</div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="common-topbar__menu-wrap">
            <button type="button" class="common-topbar__action common-topbar__action--square" data-topbar-menu-trigger="help">
                <span class="common-topbar__action-icon" aria-hidden="true">
                    <img src="/common/assets/icon-topbar-help.png" alt="" class="common-topbar__icon-image">
                </span>
                <span class="common-topbar__action-label"><?= htmlspecialchars($config['helpLabel']) ?></span>
            </button>
            <div class="common-topbar__menu common-topbar__menu--help" data-topbar-menu="help">
                <?php foreach ($config['helpItems'] as $item): ?>
                    <button
                        type="button"
                        class="common-topbar__menu-item common-topbar__help-item"
                        data-topbar-help-item='<?= htmlspecialchars(json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'
                    >
                        <span class="common-topbar__help-title"><?= htmlspecialchars($item['label'] ?? 'Aide') ?></span>
                        <?php if (!empty($item['description'])): ?>
                            <span class="common-topbar__help-description"><?= htmlspecialchars($item['description']) ?></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($config['profile']['enabled'])): ?>
        <div class="common-topbar__menu-wrap">
            <button type="button" class="common-topbar__action common-topbar__action--square common-topbar__profile" data-topbar-menu-trigger="profile">
                <span class="common-topbar__avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($config['userName'], 0, 1))) ?></span>
                <span class="common-topbar__action-label"><?= htmlspecialchars($config['profile']['buttonLabel']) ?></span>
            </button>
            <div class="common-topbar__menu common-topbar__menu--right" data-topbar-menu="profile">
                <button type="button" class="common-topbar__menu-item" data-topbar-profile-edit><?= htmlspecialchars($config['profile']['editLabel']) ?></button>
                <button type="button" class="common-topbar__menu-item common-topbar__menu-item--danger" data-topbar-logout>Se déconnecter</button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</header>

<div class="common-topbar-modal" id="commonTopbarModal" hidden>
    <div class="common-topbar-modal__backdrop" data-topbar-modal-close></div>
    <div class="common-topbar-modal__panel" role="dialog" aria-modal="true" aria-labelledby="commonTopbarModalTitle">
        <div class="common-topbar-modal__header">
            <h3 id="commonTopbarModalTitle">Panneau</h3>
            <button type="button" class="common-topbar-modal__close" data-topbar-modal-close>Fermer</button>
        </div>
        <div class="common-topbar-modal__body" id="commonTopbarModalBody"></div>
    </div>
</div>

<div class="common-topbar-drawer" id="commonTopbarDrawer" hidden>
    <div class="common-topbar-drawer__backdrop" data-topbar-drawer-close></div>
    <div class="common-topbar-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="commonTopbarDrawerTitle">
        <div class="common-topbar-drawer__header">
            <h3 id="commonTopbarDrawerTitle">Panneau latéral</h3>
            <button type="button" class="common-topbar-drawer__close" data-topbar-drawer-close>Fermer</button>
        </div>
        <div class="common-topbar-drawer__body" id="commonTopbarDrawerBody"></div>
    </div>
</div>

<script>
window.commonTopbarConfig = <?= json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php
}
