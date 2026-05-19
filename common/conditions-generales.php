<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/legal_page_helper.php';

$siteTitle = trim((string)($GLOBALS['siteTitle'] ?? 'Le site'));

commonRenderLegalPage([
    'siteTitle' => $siteTitle,
    'pageTitle' => 'Conditions generales - ' . $siteTitle,
    'documentTitle' => 'Conditions generales d utilisation',
    'badge' => 'Version provisoire',
    'accent' => '#2563eb',
    'accentSoft' => '#dbeafe',
    'backgroundStart' => '#eff6ff',
    'pageBackground' => '#f8fafc',
    'noteBackground' => '#f8fbff',
    'borderColor' => '#dbe4ee',
    'intro' => [
        'Cette page constitue une version temporaire des conditions generales d utilisation de <strong>{siteTitle}</strong>.',
        'Elle est publiee afin de permettre l activation technique de certaines integrations et sera completee, relue et validee ulterieurement.',
    ],
    'sections' => [
        [
            'title' => '1. Objet',
            'paragraphs' => [
                'Le present site propose des services numeriques, contenus, fonctionnalites et espaces d interaction destines a ses utilisateurs. Les presentes conditions ont pour objet de definir, a titre provisoire, le cadre general d utilisation du service.',
            ],
        ],
        [
            'title' => '2. Acceptation',
            'paragraphs' => [
                'L utilisation du site implique l acceptation des presentes conditions generales, dans leur version en vigueur au moment de la consultation ou de l utilisation du service.',
            ],
        ],
        [
            'title' => '3. Acces au service',
            'paragraphs' => [
                'L editeur s efforce d assurer un acces raisonnable au service, sans garantie d accessibilite permanente, de disponibilite continue ou d absence d erreur.',
            ],
        ],
        [
            'title' => '4. Compte utilisateur',
            'paragraphs' => [
                'Certaines fonctionnalites peuvent necessiter la creation ou l utilisation d un compte. L utilisateur s engage a fournir des informations exactes et a ne pas detourner le service de son usage normal.',
            ],
        ],
        [
            'title' => '5. Services tiers',
            'paragraphs' => [
                'Le site peut s interfacer avec des services externes, notamment des plateformes tierces d authentification, de paiement, de soutien ou d abonnement. L utilisation de ces services reste egalement soumise aux conditions propres de leurs editeurs.',
            ],
        ],
        [
            'title' => '6. Limitation de responsabilite',
            'paragraphs' => [
                'Cette version provisoire est fournie a des fins de prefiguration. Tant que la version definitive n a pas ete publiee, aucun element de cette page ne doit etre interprete comme une redaction juridique finale ou comme un engagement exhaustif.',
            ],
        ],
        [
            'title' => '7. Evolution du document',
            'paragraphs' => [
                'Ces conditions generales pourront etre modifiees, completees ou remplacees a tout moment par une version definitive plus complete.',
            ],
        ],
    ],
    'note' => [
        'Document de travail a completer. Prevoir ensuite l ajout des mentions legales, des dispositions sur les donnees personnelles, des modalites d abonnement, des conditions de resiliation et du droit applicable.',
    ],
]);
