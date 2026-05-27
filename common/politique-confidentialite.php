<?php
require_once __DIR__ . '/../shared_functions.php';
require_once __DIR__ . '/legal_page_helper.php';

$siteTitle = trim((string)($GLOBALS['siteTitle'] ?? 'Le site'));
$locale = commonLegalResolveLocale();
$sourceLang = array_merge(commonGetLegalSharedSourceLang(), [
    'legal.privacy.page_title' => [
        'text' => 'Politique de confidentialite - {siteTitle}',
        'context' => 'Browser page title for the privacy policy page.',
    ],
    'legal.privacy.document_title' => [
        'text' => 'Politique de confidentialite',
        'context' => 'Main heading on the privacy policy page.',
    ],
    'legal.privacy.intro.temporary' => [
        'text' => 'Cette page constitue une version temporaire de la politique de confidentialite de <strong>{siteTitle}</strong>.',
        'context' => 'Introductory paragraph on the privacy policy page.',
    ],
    'legal.privacy.section.1.title' => [
        'text' => '1. Donnees concernees',
        'context' => 'Section title on the privacy policy page.',
    ],
    'legal.privacy.section.1.body' => [
        'text' => 'Le site peut etre amene a traiter certaines donnees necessaires a son fonctionnement, a la gestion des comptes utilisateurs, a la securite des acces, ainsi qu a certaines integrations techniques avec des services tiers.',
        'context' => 'Section body on the privacy policy page.',
    ],
    'legal.privacy.section.2.title' => [
        'text' => '2. Finalites du traitement',
        'context' => 'Section title on the privacy policy page.',
    ],
    'legal.privacy.section.2.body' => [
        'text' => 'Les donnees peuvent etre utilisees, a titre provisoire et non exhaustif, pour :',
        'context' => 'Section body on the privacy policy page introducing a list.',
    ],
    'legal.privacy.section.2.list.1' => [
        'text' => 'permettre l acces au service et l authentification des utilisateurs ;',
        'context' => 'List item on the privacy policy page.',
    ],
    'legal.privacy.section.2.list.2' => [
        'text' => 'gerer les preferences et parametres lies au compte ;',
        'context' => 'List item on the privacy policy page.',
    ],
    'legal.privacy.section.2.list.3' => [
        'text' => 'assurer la securite technique, la maintenance et le suivi du service ;',
        'context' => 'List item on the privacy policy page.',
    ],
    'legal.privacy.section.2.list.4' => [
        'text' => 'verifier l etat d une connexion ou d un abonnement via un service tiers autorise.',
        'context' => 'List item on the privacy policy page.',
    ],
    'legal.privacy.section.3.title' => [
        'text' => '3. Services tiers',
        'context' => 'Section title on the privacy policy page.',
    ],
    'legal.privacy.section.3.body' => [
        'text' => 'Certaines fonctionnalites peuvent impliquer des echanges avec des plateformes tierces. Dans ce cadre, les donnees strictement necessaires a l integration concernee peuvent etre recues, stockees ou mises a jour selon les autorisations accordees par l utilisateur.',
        'context' => 'Section body on the privacy policy page.',
    ],
    'legal.privacy.section.4.title' => [
        'text' => '4. Conservation',
        'context' => 'Section title on the privacy policy page.',
    ],
    'legal.privacy.section.4.body' => [
        'text' => 'Les donnees sont conservees pendant la duree necessaire au fonctionnement du service, sous reserve des obligations legales, techniques ou contractuelles applicables.',
        'context' => 'Section body on the privacy policy page.',
    ],
    'legal.privacy.section.5.title' => [
        'text' => '5. Securite',
        'context' => 'Section title on the privacy policy page.',
    ],
    'legal.privacy.section.5.body' => [
        'text' => 'L editeur met en oeuvre des mesures raisonnables pour limiter les acces non autorises, les usages abusifs et les pertes de donnees, sans pouvoir garantir une securite absolue.',
        'context' => 'Section body on the privacy policy page.',
    ],
    'legal.privacy.section.6.title' => [
        'text' => '6. Droits des personnes',
        'context' => 'Section title on the privacy policy page.',
    ],
    'legal.privacy.section.6.body' => [
        'text' => 'Une version definitive de cette politique precisera les modalites d exercice des droits d acces, de rectification, d effacement, d opposition et, le cas echeant, de portabilite.',
        'context' => 'Section body on the privacy policy page.',
    ],
    'legal.privacy.section.7.title' => [
        'text' => '7. Caractere provisoire',
        'context' => 'Section title on the privacy policy page.',
    ],
    'legal.privacy.section.7.body' => [
        'text' => 'Ce document est fourni a titre transitoire. Il sera remplace par une version complete integrant les mentions legales, les bases juridiques, les coordonnees de contact et les details operationnels du traitement.',
        'context' => 'Section body on the privacy policy page.',
    ],
    'legal.privacy.note' => [
        'text' => 'Document de travail a completer. Prevoir ensuite l ajout des categories exactes de donnees, des durees de conservation, des sous-traitants, des transferts eventuels et du contact de reference pour les demandes liees a la confidentialite.',
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
