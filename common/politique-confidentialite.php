<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/legal_page_helper.php';

$siteTitle = trim((string)($GLOBALS['siteTitle'] ?? 'Le site'));

commonRenderLegalPage([
    'siteTitle' => $siteTitle,
    'pageTitle' => 'Politique de confidentialite - ' . $siteTitle,
    'documentTitle' => 'Politique de confidentialite',
    'badge' => 'Version provisoire',
    'accent' => '#0f766e',
    'accentSoft' => '#ccfbf1',
    'backgroundStart' => '#ecfeff',
    'pageBackground' => '#f8fafc',
    'noteBackground' => '#f0fdfa',
    'borderColor' => '#dbe4ee',
    'intro' => [
        'Cette page constitue une version temporaire de la politique de confidentialite de <strong>{siteTitle}</strong>.',
        'Elle est publiee afin de permettre l activation technique de certaines integrations et sera completee, relue et validee ulterieurement.',
    ],
    'sections' => [
        [
            'title' => '1. Donnees concernees',
            'paragraphs' => [
                'Le site peut etre amene a traiter certaines donnees necessaires a son fonctionnement, a la gestion des comptes utilisateurs, a la securite des acces, ainsi qu a certaines integrations techniques avec des services tiers.',
            ],
        ],
        [
            'title' => '2. Finalites du traitement',
            'paragraphs' => [
                'Les donnees peuvent etre utilisees, a titre provisoire et non exhaustif, pour :',
            ],
            'list' => [
                'permettre l acces au service et l authentification des utilisateurs ;',
                'gerer les preferences et parametres lies au compte ;',
                'assurer la securite technique, la maintenance et le suivi du service ;',
                'verifier l etat d une connexion ou d un abonnement via un service tiers autorise.',
            ],
        ],
        [
            'title' => '3. Services tiers',
            'paragraphs' => [
                'Certaines fonctionnalites peuvent impliquer des echanges avec des plateformes tierces. Dans ce cadre, les donnees strictement necessaires a l integration concernee peuvent etre recues, stockees ou mises a jour selon les autorisations accordees par l utilisateur.',
            ],
        ],
        [
            'title' => '4. Conservation',
            'paragraphs' => [
                'Les donnees sont conservees pendant la duree necessaire au fonctionnement du service, sous reserve des obligations legales, techniques ou contractuelles applicables.',
            ],
        ],
        [
            'title' => '5. Securite',
            'paragraphs' => [
                'L editeur met en oeuvre des mesures raisonnables pour limiter les acces non autorises, les usages abusifs et les pertes de donnees, sans pouvoir garantir une securite absolue.',
            ],
        ],
        [
            'title' => '6. Droits des personnes',
            'paragraphs' => [
                'Une version definitive de cette politique precisera les modalites d exercice des droits d acces, de rectification, d effacement, d opposition et, le cas echeant, de portabilite.',
            ],
        ],
        [
            'title' => '7. Caractere provisoire',
            'paragraphs' => [
                'Ce document est fourni a titre transitoire. Il sera remplace par une version complete integrant les mentions legales, les bases juridiques, les coordonnees de contact et les details operationnels du traitement.',
            ],
        ],
    ],
    'note' => [
        'Document de travail a completer. Prevoir ensuite l ajout des categories exactes de donnees, des durees de conservation, des sous-traitants, des transferts eventuels et du contact de reference pour les demandes liees a la confidentialite.',
    ],
]);
