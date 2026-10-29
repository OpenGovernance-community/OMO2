var omoUiText = window.omoUiText || function (key, fallback) { return fallback; };
var omoUiHtml = window.omoUiHtml || function (key, fallback) { return fallback; };
(function () {
    window.omoGetTourStepDefinitions = function (context) {
        const isMobile = !!(context && context.isMobile);
        const sidebarSide = isMobile ? 'top' : 'right';
        const contextSide = isMobile ? 'top' : 'left';
        const structureSide = isMobile ? 'top' : 'left';

        return [
            {
                selector: '.common-topbar',
                popover: {
                    title: omoUiText("barre_superieure", "Barre supérieure"),
                    description: omoUiText("cette_barre_commune_regroupe_les_actions_transversales_de_l_application_recherche_aide_pro", "Cette barre commune regroupe les actions transversales de l’application: recherche, aide, profil et identité visuelle de l’organisation."),
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                selector: '[data-topbar-menu-trigger="search"]',
                popover: {
                    title: omoUiText("recherche", "Recherche"),
                    description: omoUiText("la_recherche_est_pensee_pour_s_adapter_au_contexte_omo_sur_mobile_elle_reste_discrete_et_s", "La recherche est pensée pour s’adapter au contexte OMO. Sur mobile, elle reste discrète et s’ouvre à la demande."),
                    side: 'bottom',
                    align: 'end',
                },
            },
            {
                selector: '#sidebar',
                popover: {
                    title: omoUiText("barre_laterale", "Barre latérale"),
                    description: omoUiText("la_barre_laterale_rassemble_les_outils_carres_du_workspace_elle_reste_compacte_pour_laisse", "La barre latérale rassemble les outils carrés du espace de travail. Elle reste compacte pour laisser la place à la structure et au contexte."),
                    side: sidebarSide,
                    align: 'start',
                },
            },
            {
                selector: '#sidebar-toggle',
                popover: {
                    title: omoUiText("mode_compact", "Mode compact"),
                    description: omoUiText("ce_bouton_replie_ou_deplie_la_barre_laterale_pratique_pour_privilegier_soit_les_libelles_s", "Ce bouton replie ou déplie la barre latérale. Pratique pour privilégier soit les libellés, soit l’espace de travail."),
                    side: sidebarSide,
                    align: 'start',
                },
            },
            {
                selectors: [
                    '#menu_sidebar .menu-item[data-hash="projects"]',
                    '#menu_sidebar .menu-item[data-hash="documents"]',
                    '#menu_sidebar .menu-item',
                ],
                popover: {
                    title: omoUiText("modules", "Modules"),
                    description: omoUiText("chaque_bloc_de_ce_menu_ouvre_un_module_metier_dans_un_panneau_coulissant_sans_quitter_le_c", "Chaque bloc de ce menu ouvre un module métier dans un panneau coulissant, sans quitter le contexte de l’organisation."),
                    side: sidebarSide,
                    align: 'start',
                },
            },
            {
                selector: '#panel-left',
                popover: {
                    title: omoUiText("contexte_courant", "Contexte courant"),
                    description: omoUiText("le_panneau_de_gauche_presente_l_espace_ses_attendus_ses_domaines_d_autorite_et_les_element", "Le panneau de gauche présente l’espace, ses attendus, ses domaines d’autorité et les éléments utiles pour comprendre où l’on se situé."),
                    side: contextSide,
                    align: 'center',
                },
            },
            {
                selectors: ['#panel-left .breadcrumb', '#panel-left .circle-header'],
                popover: {
                    title: omoUiText("fil_d_ariane_et_responsables", "Fil d’Ariane et responsables"),
                    description: omoUiText("cette_zone_aide_a_se_reperer_rapidement_dans_la_hierarchie_et_a_visualiser_les_personnes_a", "Cette zone aide à se repérer rapidement dans la hiérarchie et à visualiser les personnes associées à l’espace affiché."),
                    side: contextSide,
                    align: 'center',
                },
            },
            {
                selector: '#resizer',
                popover: {
                    title: omoUiText("reglage_de_l_espace", "Réglage de l’espace"),
                    description: omoUiText("le_separateur_permet_d_elargir_ou_reduire_le_panneau_de_contexte_pour_adapter_la_lecture_a", "Le séparateur permet d’élargir ou réduire le panneau de contexte pour adapter la lecture à la démonstration ou à l’usage réel."),
                    side: 'left',
                    align: 'center',
                },
            },
            {
                selector: '#panel-right',
                popover: {
                    title: omoUiText("structure_visuelle", "Structure visuelle"),
                    description: omoUiText("le_panneau_principal_met_en_scene_l_organisation_ici_on_visualise_les_espaces_de_maniere_p", "Le panneau principal met en scène l’organisation. Ici, on visualise les espaces de manière plus immersive."),
                    side: structureSide,
                    align: 'center',
                },
            },
            {
                selectors: ['#panel-right .chart-toggle', '#panel-right #role_list'],
                popover: {
                    title: omoUiText("plusieurs_vues_possibles", "Plusieurs vues possibles"),
                    description: omoUiText("la_structure_peut_basculer_entre_une_vue_graphique_et_une_lecture_plus_listee_selon_le_pub", "La structure peut basculer entre une vue graphique et une lecture plus listée, selon le public ou le besoin du moment."),
                    side: structureSide,
                    align: 'center',
                },
            },
            {
                selector: '#omo-mobile-nav',
                popover: {
                    title: omoUiText("navigation_mobile", "Navigation mobile"),
                    description: omoUiText("sur_telephone_on_privilegie_une_navigation_simple_par_onglets_pour_passer_d_un_espace_a_l", "Sur téléphone, on privilégie une navigation simple par onglets pour passer d’un espace à l’autre sans surcharger l’écran."),
                    side: 'top',
                    align: 'center',
                },
            },
            {
                selector: '[data-topbar-menu-trigger="profile"]',
                popover: {
                    title: omoUiText("profil_et_sortie", "Profil et sortie"),
                    description: omoUiText("le_menu_profil_centralise_l_edition_du_compte_et_la_deconnexion_de_facon_homogene_avec_les", "Le menu profil centralise l’édition du compte et la déconnexion, de façon homogène avec les autres applications du site."),
                    side: 'bottom',
                    align: 'end',
                },
            },
        ];
    };
})();
