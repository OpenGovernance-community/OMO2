<?php

if (!function_exists('commonBuildOmoPublicOrganizationContext')) {
    function commonBuildOmoPublicOrganizationContext($organization)
    {
        if (!($organization instanceof \dbObject\Organization)) {
            return [
                'id' => 0,
                'name' => '',
                'shortname' => '',
                'domain' => '',
                'logo' => '',
                'banner' => '',
                'color' => '',
                'host' => commonGetRequestHost(),
            ];
        }

        return [
            'id' => (int)$organization->getId(),
            'name' => (string)$organization->get('name'),
            'shortname' => (string)$organization->get('shortname'),
            'domain' => (string)$organization->get('domain'),
            'logo' => (string)$organization->get('logo'),
            'banner' => (string)$organization->get('banner'),
            'color' => trim((string)$organization->get('color')),
            'host' => commonGetRequestHost(),
        ];
    }
}

if (!function_exists('commonBuildOmoPublicHelpItems')) {
    function commonBuildOmoPublicHelpCardHtml($title, array $paragraphs = [])
    {
        $title = trim((string)$title);
        $html = '<div class="common-help-list"><div class="common-help-card">';

        if ($title !== '') {
            $html .= '<h4>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h4>';
        }

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim((string)$paragraph);
            if ($paragraph === '') {
                continue;
            }

            $html .= '<p>' . $paragraph . '</p>';
        }

        $html .= '</div></div>';

        return $html;
    }

    function commonBuildOmoPublicHelpItems($pageType = 'generic', $organizationName = '')
    {
        $pageType = trim((string)$pageType);
        $organizationName = trim((string)$organizationName);

        $organizationHtml = $organizationName !== ''
            ? '<strong>' . htmlspecialchars($organizationName, ENT_QUOTES, 'UTF-8') . '</strong>'
            : 'l organisation concernee';

        $whatIsOmoHtml = commonBuildOmoPublicHelpCardHtml(
            'Qu est-ce que OpenMyOrganization ?',
            [
                'OpenMyOrganization (OMO) est un environnement collaboratif qui peut regrouper la structure d une organisation, ses roles, ses decisions, ses documents et d autres espaces de coordination.',
                'Les pages publiques d OMO servent a partager seulement une partie choisie du contenu. Le lien que vous avez recu n ouvre donc pas toute l organisation, mais uniquement le perimetre rendu visible par ' . $organizationHtml . '.',
            ]
        );

        $pagePurposeTitle = 'A quoi sert cette page ?';
        $pagePurposeDescription = 'Comprendre cette page publique';
        $pagePurposeHtml = commonBuildOmoPublicHelpCardHtml(
            $pagePurposeTitle,
            [
                'Cette page publique est diffusee avec OpenMyOrganization (OMO).',
            ]
        );

        if ($pageType === 'decision') {
            $pagePurposeDescription = 'Comprendre ce scrutin public';
            $pagePurposeHtml = commonBuildOmoPublicHelpCardHtml(
                $pagePurposeTitle,
                [
                    'Cette page sert a consulter ou a participer a un scrutin partage publiquement par ' . $organizationHtml . '.',
                    'Selon le lien recu et votre situation, vous pouvez lire le contexte du scrutin, demander un acces personnel, voter, puis consulter les resultats lorsque leur publication est autorisee.',
                ]
            );
        } elseif ($pageType === 'share') {
            $pagePurposeDescription = 'Comprendre cette structure partagee';
            $pagePurposeHtml = commonBuildOmoPublicHelpCardHtml(
                $pagePurposeTitle,
                [
                    'Cette page sert a parcourir une structure organisationnelle partagee publiquement par ' . $organizationHtml . '.',
                    'Vous pouvez y explorer les cercles, les roles et les relations visibles dans le perimetre partage, sans acceder aux espaces internes qui ne font pas partie du lien public.',
                ]
            );
        }

        return [
            [
                'label' => 'Qu est-ce que OpenMyOrganization ?',
                'title' => 'Qu est-ce que OpenMyOrganization ?',
                'description' => 'Presentation rapide de l outil',
                'html' => $whatIsOmoHtml,
            ],
            [
                'label' => 'A quoi sert cette page ?',
                'title' => $pagePurposeTitle,
                'description' => $pagePurposeDescription,
                'html' => $pagePurposeHtml,
            ],
            [
                'label' => 'Politique de confidentialite',
                'title' => 'Politique de confidentialite',
                'description' => 'Traitement des donnees et cadre provisoire',
                'url' => '/common/politique-confidentialite.php?embed=1',
                'mode' => 'fetch',
            ],
        ];
    }
}

?>
