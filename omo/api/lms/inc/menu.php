<?php
require_once __DIR__ . '/../ui_translations.php';
require_once dirname(__DIR__, 4) . '/common/topbar.php';
$currentUserId = (int)commonGetCurrentUserId();
?>
<script src="/shared_functions.js"></script>
<script>
sharedApplyDocumentTheme({
    preference: <?php echo $currentUserId > 0 ? 'undefined' : "'system'"; ?>
});
</script>
<?php
commonRenderTopbar([
    'appKey' => 'lms',
    'appLabel' => 'LMS',
    'organization' => isset($org) ? $org : null,
    'logoutReturnTo' => omoLmsBuildPath('/'),
    'search' => [
        'enabled' => true,
        'placeholder' => omoLmsUiT('rechercher_un_parcours_ou_une_mission'),
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
    'helpLabel' => omoLmsUiT('aide'),
    'helpItems' => [
        [
            'key' => 'faq',
            'label' => 'FAQ',
            'description' => omoLmsUiT('acces_aux_questions_les_plus_courantes_avec_moteur_de_recherche_pour_trouver_facilement_la'),
            'title' => omoLmsUiT('faq_lms'),
            'html' => ('<div class="common-help-list"><div class="common-help-card"><h4>' . htmlspecialchars(omoLmsUiT('faq'), ENT_QUOTES, 'UTF-8') . '</h4><p>' . htmlspecialchars(omoLmsUiT('cette_zone_pourra_accueillir_les_questions_frequentes_du_lms_pour_l_instant_elle_sert_de_p'), ENT_QUOTES, 'UTF-8') . '</p></div></div>'),
        ],
        [
            'key' => 'tour',
            'label' => omoLmsUiT('visite_guidee'),
            'description' => omoLmsUiT('tour_des_fonctions_visibles_a_l_ecran_avec_explication_pour_chaque_bouton_et_chaque_possib'),
            'title' => omoLmsUiT('visite_guidee'),
            'html' => ('<div class="common-help-list"><div class="common-help-card"><h4>' . htmlspecialchars(omoLmsUiT('visite_guidee'), ENT_QUOTES, 'UTF-8') . '</h4><p>' . htmlspecialchars(omoLmsUiT('la_visite_guidee_du_lms_pourra_etre_branchee_ici_application_par_application'), ENT_QUOTES, 'UTF-8') . '</p></div></div>'),
        ],
        [
            'key' => 'tutorials',
            'label' => 'Tutoriels',
            'description' => omoLmsUiT('des_formations_ciblees_pour_monter_en_competences_dans_l_utilisation_du_logiciel'),
            'title' => 'Tutoriels',
            'html' => ('<div class="common-help-list"><div class="common-help-card"><h4>' . htmlspecialchars(omoLmsUiT('tutoriels'), ENT_QUOTES, 'UTF-8') . '</h4><p>' . htmlspecialchars(omoLmsUiT('cette_section_pourra_ensuite_lister_des_tutoriels_video_des_guides_pas_a_pas_ou_des_suppor'), ENT_QUOTES, 'UTF-8') . '</p></div></div>'),
        ],
    ],
]);
?>
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
