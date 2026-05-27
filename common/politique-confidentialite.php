<?php
require_once __DIR__ . '/../shared_functions.php';
require_once __DIR__ . '/legal_page_helper.php';

$siteTitle = trim((string)($GLOBALS['siteTitle'] ?? 'Le site'));
$locale = commonLegalResolveLocale();
$sourceLang = array_merge(commonGetLegalSharedSourceLang(), [
    'legal.privacy.page_title' => [
        'text' => 'Politique de confidentialité - {siteTitle}',
        'context' => 'Browser page title for the privacy policy page.',
    ],
    'legal.privacy.document_title' => [
        'text' => 'Politique de confidentialité',
        'context' => 'Main heading on the privacy policy page.',
    ],
    'legal.privacy.intro.temporary' => [
        'text' => 'Cette page constitue une version temporaire de la politique de confidentialité de <strong>{siteTitle}</strong>.',
        'context' => 'Introductory paragraph on the privacy policy page.',
    ],
    'legal.privacy.section.1.title' => [
        'text' => '1. Données concernées',
        'context' => 'Section title on the privacy policy page.',
    ],
    'legal.privacy.section.1.body' => [
        'text' => 'Le site peut être amené à traiter certaines données nécessaires à son fonctionnement, à la gestion des comptes utilisateurs, à la sécurité des accès, ainsi qu’à certaines intégrations techniques avec des services tiers.',
        'context' => 'Section body on the privacy policy page.',
    ],
    'legal.privacy.section.2.title' => [
        'text' => '2. Finalités du traitement',
        'context' => 'Section title on the privacy policy page.',
    ],
    'legal.privacy.section.2.body' => [
        'text' => 'Les données peuvent être utilisées, à titre provisoire et non exhaustif, pour :',
        'context' => 'Section body on the privacy policy page introducing a list.',
    ],
    'legal.privacy.section.2.list.1' => [
        'text' => 'permettre l’accès au service et l’authentification des utilisateurs ;',
        'context' => 'List item on the privacy policy page.',
    ],
    'legal.privacy.section.2.list.2' => [
        'text' => 'gérer les préférences et paramètres liés au compte ;',
        'context' => 'List item on the privacy policy page.',
    ],
    'legal.privacy.section.2.list.3' => [
        'text' => 'assurer la sécurité technique, la maintenance et le suivi du service ;',
        'context' => 'List item on the privacy policy page.',
    ],
    'legal.privacy.section.2.list.4' => [
        'text' => 'vérifier l’état d’une connexion ou d’un abonnement via un service tiers autorisé.',
        'context' => 'List item on the privacy policy page.',
    ],
    'legal.privacy.section.3.title' => [
        'text' => '3. Services tiers',
        'context' => 'Section title on the privacy policy page.',
    ],
    'legal.privacy.section.3.body' => [
        'text' => 'Certaines fonctionnalités peuvent impliquer des échanges avec des plateformes tierces. Dans ce cadre, les données strictement nécessaires à l’intégration concernée peuvent être recueillies, stockées ou mises à jour selon les autorisations accordées par l’utilisateur.',
        'context' => 'Section body on the privacy policy page.',
    ],
    'legal.privacy.section.4.title' => [
        'text' => '4. Conservation',
        'context' => 'Section title on the privacy policy page.',
    ],
    'legal.privacy.section.4.body' => [
        'text' => 'Les données sont conservées pendant la durée nécessaire au fonctionnement du service, sous réserve des obligations légales, techniques ou contractuelles applicables.',
        'context' => 'Section body on the privacy policy page.',
    ],
    'legal.privacy.section.5.title' => [
        'text' => '5. Sécurité',
        'context' => 'Section title on the privacy policy page.',
    ],
    'legal.privacy.section.5.body' => [
        'text' => 'L’éditeur met en oeuvre des mesures raisonnables pour limiter les accès non autorisés, les usages abusifs et les pertes de données, sans pouvoir garantir une sécurité absolue.',
        'context' => 'Section body on the privacy policy page.',
    ],
    'legal.privacy.section.6.title' => [
        'text' => '6. Droits des personnes',
        'context' => 'Section title on the privacy policy page.',
    ],
    'legal.privacy.section.6.body' => [
        'text' => 'Une version définitive de cette politique précisera les modalités d’exercice des droits d’accès, de rectification, d’effacement, d’opposition et, le cas échéant, de portabilité.',
        'context' => 'Section body on the privacy policy page.',
    ],
    'legal.privacy.section.7.title' => [
        'text' => '7. Caractère provisoire',
        'context' => 'Section title on the privacy policy page.',
    ],
    'legal.privacy.section.7.body' => [
        'text' => 'Ce document est fourni à titre transitoire. Il sera remplacé par une version complète intégrant les mentions légales, les bases juridiques, les coordonnées de contact et les détails opérationnels du traitement.',
        'context' => 'Section body on the privacy policy page.',
    ],
    'legal.privacy.note' => [
        'text' => 'Document de travail à compléter. Prévoir ensuite l’ajout des catégories exactes de données, des durées de conservation, des sous-traitants, des transferts éventuels et du contact de référence pour les demandes liées à la confidentialité.',
        'context' => 'Closing note on the privacy policy page.',
    ],
]);
$lang = commonLegalLoadBundle('common_legal_privacy_page', $sourceLang, $locale);

commonRenderLegalPage([
    'siteTitle' => $siteTitle,
    'locale' => $locale,
    'pageTitle' => commonLegalT('legal.privacy.page_title', ['siteTitle' => $siteTitle], $lang, $sourceLang),
    'documentTitle' => commonLegalT('legal.privacy.document_title', [], $lang, $sourceLang),
    'badge' => commonLegalT('legal.shared.badge.temporary', [], $lang, $sourceLang),
    'accent' => '#0f766e',
    'accentSoft' => '#ccfbf1',
    'backgroundStart' => '#ecfeff',
    'pageBackground' => '#f8fafc',
    'noteBackground' => '#f0fdfa',
    'borderColor' => '#dbe4ee',
    'intro' => [
        commonLegalT('legal.privacy.intro.temporary', ['siteTitle' => $siteTitle], $lang, $sourceLang),
        commonLegalT('legal.shared.intro.activation_notice', [], $lang, $sourceLang),
    ],
    'sections' => [
        [
            'title' => commonLegalT('legal.privacy.section.1.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.1.body', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.2.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.2.body', [], $lang, $sourceLang),
            ],
            'list' => [
                commonLegalT('legal.privacy.section.2.list.1', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.2.list.2', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.2.list.3', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.2.list.4', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.3.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.3.body', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.4.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.4.body', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.5.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.5.body', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.6.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.6.body', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.7.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.7.body', [], $lang, $sourceLang),
            ],
        ],
    ],
    'note' => [
        commonLegalT('legal.privacy.note', [], $lang, $sourceLang),
    ],
]);
