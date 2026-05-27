<?php
require_once __DIR__ . '/../shared_functions.php';
require_once __DIR__ . '/legal_page_helper.php';

$siteTitle = trim((string)($GLOBALS['siteTitle'] ?? 'Le site'));
$locale = commonLegalResolveLocale();
$sourceLang = array_merge(commonGetLegalSharedSourceLang(), [
    'legal.terms.page_title' => [
        'text' => 'Conditions generales - {siteTitle}',
        'context' => 'Browser page title for the terms and conditions page.',
    ],
    'legal.terms.document_title' => [
        'text' => 'Conditions generales d utilisation',
        'context' => 'Main heading on the terms and conditions page.',
    ],
    'legal.terms.intro.temporary' => [
        'text' => 'Cette page constitue une version temporaire des conditions generales d utilisation de <strong>{siteTitle}</strong>.',
        'context' => 'Introductory paragraph on the terms and conditions page.',
    ],
    'legal.terms.section.1.title' => [
        'text' => '1. Objet',
        'context' => 'Section title on the terms and conditions page.',
    ],
    'legal.terms.section.1.body' => [
        'text' => 'Le present site propose des services numeriques, contenus, fonctionnalites et espaces d interaction destines a ses utilisateurs. Les presentes conditions ont pour objet de definir, a titre provisoire, le cadre general d utilisation du service.',
        'context' => 'Section body on the terms and conditions page.',
    ],
    'legal.terms.section.2.title' => [
        'text' => '2. Acceptation',
        'context' => 'Section title on the terms and conditions page.',
    ],
    'legal.terms.section.2.body' => [
        'text' => 'L utilisation du site implique l acceptation des presentes conditions generales, dans leur version en vigueur au moment de la consultation ou de l utilisation du service.',
        'context' => 'Section body on the terms and conditions page.',
    ],
    'legal.terms.section.3.title' => [
        'text' => '3. Acces au service',
        'context' => 'Section title on the terms and conditions page.',
    ],
    'legal.terms.section.3.body' => [
        'text' => 'L editeur s efforce d assurer un acces raisonnable au service, sans garantie d accessibilite permanente, de disponibilite continue ou d absence d erreur.',
        'context' => 'Section body on the terms and conditions page.',
    ],
    'legal.terms.section.4.title' => [
        'text' => '4. Compte utilisateur',
        'context' => 'Section title on the terms and conditions page.',
    ],
    'legal.terms.section.4.body' => [
        'text' => 'Certaines fonctionnalites peuvent necessiter la creation ou l utilisation d un compte. L utilisateur s engage a fournir des informations exactes et a ne pas detourner le service de son usage normal.',
        'context' => 'Section body on the terms and conditions page.',
    ],
    'legal.terms.section.5.title' => [
        'text' => '5. Services tiers',
        'context' => 'Section title on the terms and conditions page.',
    ],
    'legal.terms.section.5.body' => [
        'text' => 'Le site peut s interfacer avec des services externes, notamment des plateformes tierces d authentification, de paiement, de soutien ou d abonnement. L utilisation de ces services reste egalement soumise aux conditions propres de leurs editeurs.',
        'context' => 'Section body on the terms and conditions page.',
    ],
    'legal.terms.section.6.title' => [
        'text' => '6. Limitation de responsabilite',
        'context' => 'Section title on the terms and conditions page.',
    ],
    'legal.terms.section.6.body' => [
        'text' => 'Cette version provisoire est fournie a des fins de prefiguration. Tant que la version definitive n a pas ete publiee, aucun element de cette page ne doit etre interprete comme une redaction juridique finale ou comme un engagement exhaustif.',
        'context' => 'Section body on the terms and conditions page.',
    ],
    'legal.terms.section.7.title' => [
        'text' => '7. Evolution du document',
        'context' => 'Section title on the terms and conditions page.',
    ],
    'legal.terms.section.7.body' => [
        'text' => 'Ces conditions generales pourront etre modifiees, completees ou remplacees a tout moment par une version definitive plus complete.',
        'context' => 'Section body on the terms and conditions page.',
    ],
    'legal.terms.note' => [
        'text' => 'Document de travail a completer. Prevoir ensuite l ajout des mentions legales, des dispositions sur les donnees personnelles, des modalites d abonnement, des conditions de resiliation et du droit applicable.',
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
