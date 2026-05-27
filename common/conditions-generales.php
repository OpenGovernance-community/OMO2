<?php
require_once __DIR__ . '/../shared_functions.php';
require_once __DIR__ . '/legal_page_helper.php';

$siteTitle = trim((string)($GLOBALS['siteTitle'] ?? 'Le site'));
$locale = commonLegalResolveLocale();
$sourceLang = array_merge(commonGetLegalSharedSourceLang(), [
    'legal.terms.page_title' => [
        'text' => 'Conditions générales - {siteTitle}',
        'context' => 'Browser page title for the terms and conditions page.',
    ],
    'legal.terms.document_title' => [
        'text' => 'Conditions générales d’utilisation',
        'context' => 'Main heading on the terms and conditions page.',
    ],
    'legal.terms.intro.temporary' => [
        'text' => 'Cette page constitue une version temporaire des conditions générales d’utilisation de <strong>{siteTitle}</strong>.',
        'context' => 'Introductory paragraph on the terms and conditions page.',
    ],
    'legal.terms.section.1.title' => [
        'text' => '1. Objet',
        'context' => 'Section title on the terms and conditions page.',
    ],
    'legal.terms.section.1.body' => [
        'text' => 'Le présent site propose des services numériques, des contenus, des fonctionnalités et des espaces d’interaction destinés à ses utilisateurs. Les présentes conditions ont pour objet de définir, à titre provisoire, le cadre général d’utilisation du service.',
        'context' => 'Section body on the terms and conditions page.',
    ],
    'legal.terms.section.2.title' => [
        'text' => '2. Acceptation',
        'context' => 'Section title on the terms and conditions page.',
    ],
    'legal.terms.section.2.body' => [
        'text' => 'L’utilisation du site implique l’acceptation des présentes conditions générales, dans leur version en vigueur au moment de la consultation ou de l’utilisation du service.',
        'context' => 'Section body on the terms and conditions page.',
    ],
    'legal.terms.section.3.title' => [
        'text' => '3. Accès au service',
        'context' => 'Section title on the terms and conditions page.',
    ],
    'legal.terms.section.3.body' => [
        'text' => 'L’éditeur s’efforce d’assurer un accès raisonnable au service, sans garantie d’accessibilité permanente, de disponibilité continue ou d’absence d’erreur.',
        'context' => 'Section body on the terms and conditions page.',
    ],
    'legal.terms.section.4.title' => [
        'text' => '4. Compte utilisateur',
        'context' => 'Section title on the terms and conditions page.',
    ],
    'legal.terms.section.4.body' => [
        'text' => 'Certaines fonctionnalités peuvent nécessiter la création ou l’utilisation d’un compte. L’utilisateur s’engage à fournir des informations exactes et à ne pas détourner le service de son usage normal.',
        'context' => 'Section body on the terms and conditions page.',
    ],
    'legal.terms.section.5.title' => [
        'text' => '5. Services tiers',
        'context' => 'Section title on the terms and conditions page.',
    ],
    'legal.terms.section.5.body' => [
        'text' => 'Le site peut s’interfacer avec des services externes, notamment des plateformes tierces d’authentification, de paiement, de soutien ou d’abonnement. L’utilisation de ces services reste également soumise aux conditions propres de leurs éditeurs.',
        'context' => 'Section body on the terms and conditions page.',
    ],
    'legal.terms.section.6.title' => [
        'text' => '6. Limitation de responsabilité',
        'context' => 'Section title on the terms and conditions page.',
    ],
    'legal.terms.section.6.body' => [
        'text' => 'Cette version provisoire est fournie à des fins de préfiguration. Tant que la version définitive n’a pas été publiée, aucun élément de cette page ne doit être interprété comme une rédaction juridique finale ou comme un engagement exhaustif.',
        'context' => 'Section body on the terms and conditions page.',
    ],
    'legal.terms.section.7.title' => [
        'text' => '7. Évolution du document',
        'context' => 'Section title on the terms and conditions page.',
    ],
    'legal.terms.section.7.body' => [
        'text' => 'Ces conditions générales pourront être modifiées, complétées ou remplacées à tout moment par une version définitive plus complète.',
        'context' => 'Section body on the terms and conditions page.',
    ],
    'legal.terms.note' => [
        'text' => 'Document de travail à compléter. Prévoir ensuite l’ajout des mentions légales, des dispositions sur les données personnelles, des modalités d’abonnement, des conditions de résiliation et du droit applicable.',
        'context' => 'Closing note on the terms and conditions page.',
    ],
]);
$lang = commonLegalLoadBundle('common_legal_terms_page', $sourceLang, $locale);

commonRenderLegalPage([
    'siteTitle' => $siteTitle,
    'locale' => $locale,
    'pageTitle' => commonLegalT('legal.terms.page_title', ['siteTitle' => $siteTitle], $lang, $sourceLang),
    'documentTitle' => commonLegalT('legal.terms.document_title', [], $lang, $sourceLang),
    'badge' => commonLegalT('legal.shared.badge.temporary', [], $lang, $sourceLang),
    'accent' => '#2563eb',
    'accentSoft' => '#dbeafe',
    'backgroundStart' => '#eff6ff',
    'pageBackground' => '#f8fafc',
    'noteBackground' => '#f8fbff',
    'borderColor' => '#dbe4ee',
    'intro' => [
        commonLegalT('legal.terms.intro.temporary', ['siteTitle' => $siteTitle], $lang, $sourceLang),
        commonLegalT('legal.shared.intro.activation_notice', [], $lang, $sourceLang),
    ],
    'sections' => [
        [
            'title' => commonLegalT('legal.terms.section.1.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.terms.section.1.body', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.terms.section.2.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.terms.section.2.body', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.terms.section.3.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.terms.section.3.body', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.terms.section.4.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.terms.section.4.body', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.terms.section.5.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.terms.section.5.body', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.terms.section.6.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.terms.section.6.body', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.terms.section.7.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.terms.section.7.body', [], $lang, $sourceLang),
            ],
        ],
    ],
    'note' => [
        commonLegalT('legal.terms.note', [], $lang, $sourceLang),
    ],
]);
