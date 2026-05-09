<?php
require_once dirname(__DIR__, 2) . '/common/topbar.php';
$currentUserId = (int)commonGetCurrentUserId();
$showLoginDrawerButton = !empty($showLoginDrawerButton) && $currentUserId <= 0;
$loginDrawerReturnTo = isset($loginDrawerReturnTo)
    ? commonNormalizeLocalPath($loginDrawerReturnTo, '/lms/')
    : commonNormalizeLocalPath($_SERVER['REQUEST_URI'] ?? '/lms/', '/lms/');
?>
<script src="/shared_functions.js"></script>
<script>
sharedApplyDocumentTheme();
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<?php
commonRenderTopbar([
    'appKey' => 'lms',
    'appLabel' => 'LMS',
    'organization' => isset($org) ? $org : null,
    'logoutReturnTo' => '/lms/',
    'search' => [
        'enabled' => true,
        'placeholder' => 'Rechercher un parcours ou une mission',
        'buttonLabel' => 'Recherche',
        'callback' => 'lmsHandleTopbarSearch',
    ],
    'profile' => [
        'enabled' => $currentUserId > 0,
        'buttonLabel' => 'Profil',
        'editTitle' => 'Votre profil',
        'editUrl' => '/popup/profil.php',
        'editMode' => 'fetch',
    ],
    'helpLabel' => 'Aide',
    'helpItems' => [
        [
            'key' => 'faq',
            'label' => 'FAQ',
            'description' => 'Accès aux questions les plus courantes, avec moteur de recherche pour trouver facilement la réponse à ses questions.',
            'title' => 'FAQ LMS',
            'html' => '<div class="common-help-list"><div class="common-help-card"><h4>FAQ</h4><p>Cette zone pourra accueillir les questions fréquentes du LMS. Pour l’instant, elle sert de point d’entrée commun.</p></div></div>',
        ],
        [
            'key' => 'tour',
            'label' => 'Visite guidée',
            'description' => 'Tour des fonctions visibles à l’écran avec explication pour chaque bouton et chaque possibilité.',
            'title' => 'Visite guidée',
            'html' => '<div class="common-help-list"><div class="common-help-card"><h4>Visite guidée</h4><p>La visite guidée du LMS pourra être branchée ici application par application.</p></div></div>',
        ],
        [
            'key' => 'tutorials',
            'label' => 'Tutoriels',
            'description' => 'Des formations ciblées pour monter en compétences dans l’utilisation du logiciel.',
            'title' => 'Tutoriels',
            'html' => '<div class="common-help-list"><div class="common-help-card"><h4>Tutoriels</h4><p>Cette section pourra ensuite lister des tutoriels vidéo, des guides pas à pas ou des supports de formation.</p></div></div>',
        ],
    ],
]);
?>
<?php if ($showLoginDrawerButton): ?>
<link rel="stylesheet" href="/common/assets/auth.css">
<style>
    #commonTopbarDrawer.lms-login-drawer-mode .common-topbar-drawer__panel {
        left: auto;
        right: 0;
        width: min(520px, 92vw);
        max-width: 92vw;
        box-shadow: -18px 0 36px rgba(17,24,39,0.22);
        animation: lmsLoginDrawerSlideIn 220ms ease;
    }

    #commonTopbarDrawer.lms-login-drawer-mode .common-topbar-drawer__body {
        background: linear-gradient(180deg, #eef3f7 0%, #f8fafc 100%);
    }

    @keyframes lmsLoginDrawerSlideIn {
        from {
            transform: translateX(32px);
            opacity: 0.01;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @media (max-width: 860px) {
        #commonTopbarDrawer.lms-login-drawer-mode .common-topbar-drawer__panel {
            width: 100vw;
            max-width: 100vw;
        }
    }
</style>
<script>
function lmsResetLoginDrawerAppearance() {
    const drawer = document.getElementById('commonTopbarDrawer');
    if (drawer) {
        drawer.classList.remove('lms-login-drawer-mode');
    }
}

function lmsOpenLoginDrawer(returnTo) {
    const target = returnTo || <?php echo json_encode($loginDrawerReturnTo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    if (typeof window.commonTopbarOpenDrawer !== 'function') {
        window.location.href = '/lms/login_drawer.php?return_to=' + encodeURIComponent(target);
        return;
    }

    window.commonTopbarOpenDrawer(
        'Connexion',
        '/lms/login_drawer.php?return_to=' + encodeURIComponent(target),
        'fetch'
    );

    const drawer = document.getElementById('commonTopbarDrawer');
    if (drawer) {
        drawer.classList.add('lms-login-drawer-mode');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const actions = document.querySelector('.common-topbar__actions');
    if (!actions || actions.querySelector('[data-lms-login-button]')) {
        return;
    }

    const wrap = document.createElement('div');
    wrap.className = 'common-topbar__menu-wrap';
    wrap.innerHTML = '<button type="button" class="common-topbar__action common-topbar__action--square" data-lms-login-button><span class="common-topbar__action-label">Login</span></button>';
    actions.appendChild(wrap);

    const button = wrap.querySelector('[data-lms-login-button]');
    if (button) {
        button.addEventListener('click', function () {
            lmsOpenLoginDrawer();
        });
    }
});

document.addEventListener('click', function (event) {
    if (event.target.closest('[data-topbar-drawer-close]')) {
        lmsResetLoginDrawerAppearance();
    }
});
</script>
<?php endif; ?>
<script>
function lmsHandleTopbarSearch(query) {
    const normalized = (query || '').trim().toLowerCase();
    const cards = document.querySelectorAll('.card');

    cards.forEach(card => {
        const text = (card.textContent || '').toLowerCase();
        card.style.display = normalized === '' || text.indexOf(normalized) !== -1 ? '' : 'none';
    });

    document.querySelectorAll('.branch').forEach(branch => {
        const visibleCards = Array.from(branch.querySelectorAll('.card')).filter(card => card.style.display !== 'none');
        branch.style.display = visibleCards.length > 0 || normalized === '' ? '' : 'none';
    });
}
</script>
