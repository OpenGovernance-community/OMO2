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
                'lexicon' => \dbObject\Organization::getDefaultLexicon(),
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
            'lexicon' => $organization->getLexicon(),
            'datecreation' => $organization->get('datecreation') instanceof \DateTimeInterface
                ? $organization->get('datecreation')->format('Y-m-d')
                : '',
            'host' => commonGetRequestHost(),
        ];
    }
}

if (!function_exists('commonOmoPublicPagesGetSourceLang')) {
    function commonOmoPublicPagesGetSourceLang()
    {
        return [
            'common.public_help.organization_fallback' => [
                'text' => 'l’organisation concernée',
                'context' => 'Fallback organization reference used in public help content when no organization name is available.',
            ],
            'common.public_help.omo.title' => [
                'text' => 'Qu’est-ce qu’OpenMyOrganization ?',
                'context' => 'Title of the public help entry explaining OpenMyOrganization.',
            ],
            'common.public_help.omo.description' => [
                'text' => 'Présentation rapide du logiciel',
                'context' => 'Short description of the public help entry explaining OpenMyOrganization.',
            ],
            'common.public_help.omo.paragraph_1' => [
                'text' => 'OpenMyOrganization (OMO) est un logiciel collaboratif conçu pour aider une organisation à rendre sa structure, ses responsabilités et son fonctionnement plus clairs. Il permet de représenter les organisations, groupes, cercles, rôles et responsabilités, puis de centraliser les informations utiles au travail collectif.',
                'context' => 'First paragraph explaining the purpose and organizational structure of OpenMyOrganization.',
            ],
            'common.public_help.omo.paragraph_2' => [
                'text' => 'Selon les applications activées, OMO accompagne les prises de décision, les projets, les documents et procès-verbaux, le calendrier, les indicateurs, les checklists ainsi que les règles et autorités. Les droits d’accès dépendent du contexte et des responsabilités de chacun.',
                'context' => 'Second paragraph listing the main OpenMyOrganization applications and access model.',
            ],
            'common.public_help.omo.paragraph_3' => [
                'text' => 'En rassemblant ces éléments dans leur contexte, OMO facilite la coopération au quotidien : les rôles et les responsabilités de chacun sont plus clairs, les décisions peuvent être préparées et suivies collectivement, et les règles, processus et projets restent documentés et accessibles.',
                'context' => 'Third paragraph presenting the practical collaborative value of OpenMyOrganization.',
            ],
            'common.public_help.page.title' => [
                'text' => 'À quoi sert cette page ?',
                'context' => 'Title of the public help entry explaining the current public page.',
            ],
            'common.public_help.page.generic_description' => [
                'text' => 'Comprendre cette page publique',
                'context' => 'Short description of the generic public page help entry.',
            ],
            'common.public_help.page.generic_paragraph' => [
                'text' => 'Cette page publique présente une partie du contenu partagé par {organization}. Elle ne donne pas accès aux espaces internes qui ne font pas partie du lien public.',
                'context' => 'Explanation of a generic public OpenMyOrganization page.',
            ],
            'common.public_help.page.decision_description' => [
                'text' => 'Comprendre ce scrutin public',
                'context' => 'Short description of the public decision page help entry.',
            ],
            'common.public_help.page.decision_paragraph_1' => [
                'text' => 'Cette page est la porte d’accès publique à une prise de décision organisée par {organization}. Elle présente le contexte, les questions, la méthode choisie, les étapes et les règles utiles pour comprendre comment la consultation et le scrutin vont se dérouler.',
                'context' => 'First paragraph explaining the purpose of the public decision page.',
            ],
            'common.public_help.page.decision_paragraph_2' => [
                'text' => 'Selon les paramètres du scrutin et votre accès, vous pouvez consulter les informations, demander un accès personnel par e-mail, participer à la consultation ou au vote, et consulter les résultats lorsque leur publication est autorisée.',
                'context' => 'Second paragraph explaining available actions on the public decision page.',
            ],
            'common.public_help.page.decision_paragraph_3' => [
                'text' => 'Lorsque les propositions sont autorisées, vous pouvez aussi en ajouter pendant la consultation. Si les discussions sont activées, les participants disposant d’un compte peuvent échanger sur chaque proposition et, selon les paramètres, choisir de publier leur message anonymement.',
                'context' => 'Third paragraph explaining public proposals and proposal discussions.',
            ],
            'common.public_help.page.decision_paragraph_4' => [
                'text' => 'Le panneau d’informations sert de repère pendant tout le processus : il rappelle qui organise le scrutin, pour qui il est ouvert, quelles sont les étapes prévues et quelles options de participation sont disponibles.',
                'context' => 'Fourth paragraph explaining how the information panel helps participants follow the public decision process.',
            ],
            'common.public_help.page.share_description' => [
                'text' => 'Comprendre cette structure partagée',
                'context' => 'Short description of the public structure page help entry.',
            ],
            'common.public_help.page.share_paragraph_1' => [
                'text' => 'Cette page sert à parcourir une structure organisationnelle partagée publiquement par {organization}. Vous pouvez y explorer les cercles, les rôles et les relations visibles dans le périmètre partagé.',
                'context' => 'First paragraph explaining the public shared structure page.',
            ],
            'common.public_help.page.share_paragraph_2' => [
                'text' => 'Elle ne permet pas d’accéder aux espaces internes ni aux informations qui ne font pas partie du lien public.',
                'context' => 'Second paragraph explaining the limits of the public shared structure page.',
            ],
            'common.public_help.privacy.label' => [
                'text' => 'Politique de confidentialité',
                'context' => 'Label of the privacy policy entry in the public help menu.',
            ],
            'common.public_help.privacy.description' => [
                'text' => 'Traitement des données et cadre provisoire',
                'context' => 'Description of the privacy policy entry in the public help menu.',
            ],
        ];
    }

    function commonOmoPublicPagesT($key, array $variables = [])
    {
        static $sourceLang = null;
        static $bundle = null;

        if ($sourceLang === null) {
            $sourceLang = commonOmoPublicPagesGetSourceLang();
        }

        if ($bundle === null && function_exists('omoLoadTranslationBundle')) {
            $bundle = omoLoadTranslationBundle('common_omo_public_pages', $sourceLang);
        }

        if (function_exists('t')) {
            return t($key, $variables, $bundle, $sourceLang);
        }

        $text = (string)($sourceLang[$key]['text'] ?? $key);
        foreach ($variables as $variable => $value) {
            $text = str_replace('{' . $variable . '}', (string)$value, $text);
        }

        return $text;
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
            : commonOmoPublicPagesT('common.public_help.organization_fallback');

        $whatIsOmoHtml = commonBuildOmoPublicHelpCardHtml(
            '',
            [
                commonOmoPublicPagesT('common.public_help.omo.paragraph_1'),
                commonOmoPublicPagesT('common.public_help.omo.paragraph_2'),
                commonOmoPublicPagesT('common.public_help.omo.paragraph_3'),
            ]
        );

        $pagePurposeTitle = commonOmoPublicPagesT('common.public_help.page.title');
        $pagePurposeDescription = commonOmoPublicPagesT('common.public_help.page.generic_description');
        $pagePurposeHtml = commonBuildOmoPublicHelpCardHtml(
            '',
            [
                commonOmoPublicPagesT('common.public_help.page.generic_paragraph', ['organization' => $organizationHtml]),
            ]
        );

        if ($pageType === 'decision') {
            $pagePurposeDescription = commonOmoPublicPagesT('common.public_help.page.decision_description');
            $pagePurposeHtml = commonBuildOmoPublicHelpCardHtml(
                '',
                [
                    commonOmoPublicPagesT('common.public_help.page.decision_paragraph_1', ['organization' => $organizationHtml]),
                    commonOmoPublicPagesT('common.public_help.page.decision_paragraph_2'),
                    commonOmoPublicPagesT('common.public_help.page.decision_paragraph_3'),
                    commonOmoPublicPagesT('common.public_help.page.decision_paragraph_4'),
                ]
            );
        } elseif ($pageType === 'share') {
            $pagePurposeDescription = commonOmoPublicPagesT('common.public_help.page.share_description');
            $pagePurposeHtml = commonBuildOmoPublicHelpCardHtml(
                '',
                [
                    commonOmoPublicPagesT('common.public_help.page.share_paragraph_1', ['organization' => $organizationHtml]),
                    commonOmoPublicPagesT('common.public_help.page.share_paragraph_2'),
                ]
            );
        }

        return [
            [
                'label' => commonOmoPublicPagesT('common.public_help.omo.title'),
                'title' => commonOmoPublicPagesT('common.public_help.omo.title'),
                'description' => commonOmoPublicPagesT('common.public_help.omo.description'),
                'html' => $whatIsOmoHtml,
            ],
            [
                'label' => $pagePurposeTitle,
                'title' => $pagePurposeTitle,
                'description' => $pagePurposeDescription,
                'html' => $pagePurposeHtml,
            ],
            [
                'label' => commonOmoPublicPagesT('common.public_help.privacy.label'),
                'title' => commonOmoPublicPagesT('common.public_help.privacy.label'),
                'description' => commonOmoPublicPagesT('common.public_help.privacy.description'),
                'url' => '/common/politique-confidentialite.php?embed=1',
                'mode' => 'fetch',
            ],
        ];
    }
}

?>
