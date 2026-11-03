<?php
require_once dirname(__DIR__, 2) . '/common/lms/ui-translations.php';
require_once dirname(__DIR__, 2) . '/common/topbar.php';
$currentUserId = (int)commonGetCurrentUserId();
$showLoginDrawerButton = !empty($showLoginDrawerButton) && $currentUserId <= 0;
$loginDrawerReturnTo = isset($loginDrawerReturnTo)
    ? commonNormalizeLocalPath($loginDrawerReturnTo, '/lms/')
    : commonNormalizeLocalPath($_SERVER['REQUEST_URI'] ?? '/lms/', '/lms/');
?>
<script>
sharedApplyDocumentTheme({
    preference: <?php echo $currentUserId > 0 ? 'undefined' : "'system'"; ?>
});
</script>
<?php
commonRenderTopbar([
    'appKey' => 'lms',
    'appLabel' => commonLmsUiT('tutoriels'),
    'organization' => isset($org) ? $org : null,
    'logoutReturnTo' => lmsBuildLocalPath('/lms/'),
    'search' => [
        'enabled' => true,
        'placeholder' => commonLmsUiT('rechercher_un_parcours_ou_une_mission'),
        'buttonLabel' => commonLmsUiT('search'),
        'callback' => 'lmsHandleTopbarSearch',
    ],
    'profile' => [
        'enabled' => $currentUserId > 0,
        'buttonLabel' => commonLmsUiT('profile'),
        'editTitle' => commonLmsUiT('your_profile'),
        'editUrl' => '/popup/profil.php',
        'editMode' => 'fetch',
    ],
    'helpLabel' => commonLmsUiT('aide'),
    'helpItems' => [
        [
            'key' => 'faq',
            'label' => 'FAQ',
            'description' => commonLmsUiT('acces_aux_questions_les_plus_courantes_avec_moteur_de_recherche_pour_trouver_facilement_la'),
            'title' => commonLmsUiT('faq_lms'),
            'html' => ('<div class="common-help-list"><div class="common-help-card"><h4>' . htmlspecialchars(commonLmsUiT('faq'), ENT_QUOTES, 'UTF-8') . '</h4><p>' . htmlspecialchars(commonLmsUiT('cette_zone_pourra_accueillir_les_questions_frequentes_du_lms_pour_l_instant_elle_sert_de_p'), ENT_QUOTES, 'UTF-8') . '</p></div></div>'),
        ],
        [
            'key' => 'tour',
            'label' => commonLmsUiT('visite_guidee'),
            'description' => commonLmsUiT('tour_des_fonctions_visibles_a_l_ecran_avec_explication_pour_chaque_bouton_et_chaque_possib'),
            'title' => commonLmsUiT('visite_guidee'),
            'html' => ('<div class="common-help-list"><div class="common-help-card"><h4>' . htmlspecialchars(commonLmsUiT('visite_guidee'), ENT_QUOTES, 'UTF-8') . '</h4><p>' . htmlspecialchars(commonLmsUiT('la_visite_guidee_du_lms_pourra_etre_branchee_ici_application_par_application'), ENT_QUOTES, 'UTF-8') . '</p></div></div>'),
        ],
        [
            'key' => 'tutorials',
            'label' => commonLmsUiT('tutoriels'),
            'description' => commonLmsUiT('des_formations_ciblees_pour_monter_en_competences_dans_l_utilisation_du_logiciel'),
            'title' => commonLmsUiT('tutoriels'),
            'html' => ('<div class="common-help-list"><div class="common-help-card"><h4>' . htmlspecialchars(commonLmsUiT('tutoriels'), ENT_QUOTES, 'UTF-8') . '</h4><p>' . htmlspecialchars(commonLmsUiT('cette_section_pourra_ensuite_lister_des_tutoriels_video_des_guides_pas_a_pas_ou_des_suppor'), ENT_QUOTES, 'UTF-8') . '</p></div></div>'),
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
        window.location.href = <?= json_encode(lmsBuildLocalPath('/lms/login_drawer.php'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '&return_to=' + encodeURIComponent(target);
        return;
    }

    window.commonTopbarOpenDrawer(
        <?= json_encode(commonLmsUiT('login'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        <?= json_encode(lmsBuildLocalPath('/lms/login_drawer.php'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '&return_to=' + encodeURIComponent(target),
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
    wrap.innerHTML = '<button type="button" class="common-topbar__action common-topbar__action--square" data-lms-login-button><span class="common-topbar__action-label"></span></button>';
    wrap.querySelector('.common-topbar__action-label').textContent = <?= json_encode(commonLmsUiT('login'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
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
