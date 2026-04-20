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
                    title: 'Barre supérieure',
                    description: 'Cette barre commune regroupe les actions transversales de l’application: recherche, aide, profil et identité visuelle de l’organisation.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                selector: '[data-topbar-menu-trigger="search"]',
                popover: {
                    title: 'Recherche',
                    description: 'La recherche est pensée pour s’adapter au contexte OMO. Sur mobile, elle reste discrète et s’ouvre à la demande.',
                    side: 'bottom',
                    align: 'end',
                },
            },
            {
                selector: '#sidebar',
                popover: {
                    title: 'Barre latérale',
                    description: 'La barre latérale rassemble les outils carrés du workspace. Elle reste compacte pour laisser la place à la structure et au contexte.',
                    side: sidebarSide,
                    align: 'start',
                },
            },
            {
                selector: '#sidebar-toggle',
                popover: {
                    title: 'Mode compact',
                    description: 'Ce bouton replie ou déplie la barre latérale. Pratique pour privilégier soit les libellés, soit l’espace de travail.',
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
                    title: 'Modules',
                    description: 'Chaque bloc de ce menu ouvre un module métier dans un panneau coulissant, sans quitter le contexte de l’organisation.',
                    side: sidebarSide,
                    align: 'start',
                },
            },
            {
                selector: '#panel-left',
                popover: {
                    title: 'Contexte courant',
                    description: 'Le panneau de gauche présente le cercle, ses attendus, ses domaines d’autorité et les éléments utiles pour comprendre où l’on se situe.',
                    side: contextSide,
                    align: 'center',
                },
            },
            {
                selectors: ['#panel-left .breadcrumb', '#panel-left .circle-header'],
                popover: {
                    title: 'Fil d’Ariane et responsables',
                    description: 'Cette zone aide à se repérer rapidement dans la hiérarchie et à visualiser les personnes associées au cercle affiché.',
                    side: contextSide,
                    align: 'center',
                },
            },
            {
                selector: '#resizer',
                popover: {
                    title: 'Réglage de l’espace',
                    description: 'Le séparateur permet d’élargir ou réduire le panneau de contexte pour adapter la lecture à la démonstration ou à l’usage réel.',
                    side: 'left',
                    align: 'center',
                },
            },
            {
                selector: '#panel-right',
                popover: {
                    title: 'Structure visuelle',
                    description: 'Le panneau principal met en scène l’organisation. Ici, on visualise les cercles et rôles de manière plus immersive.',
                    side: structureSide,
                    align: 'center',
                },
            },
            {
                selectors: ['#panel-right .chart-toggle', '#panel-right #role_list'],
                popover: {
                    title: 'Plusieurs vues possibles',
                    description: 'La structure peut basculer entre une vue graphique et une lecture plus listée, selon le public ou le besoin du moment.',
                    side: structureSide,
                    align: 'center',
                },
            },
            {
                selector: '#omo-mobile-nav',
                popover: {
                    title: 'Navigation mobile',
                    description: 'Sur téléphone, on privilégie une navigation simple par onglets pour passer d’un espace à l’autre sans surcharger l’écran.',
                    side: 'top',
                    align: 'center',
                },
            },
            {
                selector: '[data-topbar-menu-trigger="profile"]',
                popover: {
                    title: 'Profil et sortie',
                    description: 'Le menu profil centralise l’édition du compte et la déconnexion, de façon homogène avec les autres applications du site.',
                    side: 'bottom',
                    align: 'end',
                },
            },
        ];
    };
})();
