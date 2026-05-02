<?php

function omoGetTopbarHelpItems(string $variant = 'app'): array
{
    $faqItem = [
        'key' => 'faq',
        'label' => 'FAQ',
        'description' => 'Accès aux questions les plus courantes, avec moteur de recherche pour trouver facilement la réponse à ses questions.',
        'title' => 'FAQ OMO',
    ];

    $tutorialsItem = [
        'key' => 'tutorials',
        'label' => 'Tutoriels',
        'description' => 'Des formations ciblées pour monter en compétences dans l’utilisation du logiciel.',
        'title' => 'Tutoriels',
        'mode' => 'drawer',
        'url' => '/lms/parcours.php?idp=1&embed=1',
    ];

    if ($variant === 'app') {
        $faqItem['callback'] = 'omoOpenFaqHelp';

        return [
            $faqItem,
            [
                'key' => 'tour',
                'label' => 'Visite guidée',
                'description' => 'Tour des fonctions visibles à l’écran avec explication pour chaque bouton et chaque possibilité.',
                'callback' => 'omoStartGuidedTour',
            ],
            $tutorialsItem,
        ];
    }

    $faqItem['mode'] = 'fetch';
    $faqItem['url'] = '/popup/faq.php';

    return [
        $faqItem,
        $tutorialsItem,
    ];
}

function omoBuildTopbarOptions(array $organizationContext, array $options = []): array
{
    $variant = (string)($options['variant'] ?? 'app');
    $isDemoGuest = !empty($options['isDemoGuest']);

    $config = [
        'appKey' => 'omo',
        'appLabel' => 'OMO',
        'organization' => $organizationContext,
        'logoutReturnTo' => (string)($options['logoutReturnTo'] ?? '/omo/'),
        'helpLabel' => 'Aide',
        'helpItems' => omoGetTopbarHelpItems($variant),
        'profile' => [
            'enabled' => !$isDemoGuest,
            'buttonLabel' => 'Profil',
            'editTitle' => 'Votre profil',
            'editUrl' => '/popup/profil.php',
            'editMode' => 'fetch',
        ],
        'search' => [
            'enabled' => false,
            'placeholder' => 'Rechercher un cercle, un rôle ou un outil',
            'buttonLabel' => 'Recherche',
            'callback' => 'omoHandleTopbarSearch',
        ],
    ];

    if ($variant === 'app') {
        $config['search']['enabled'] = true;
    }

    if ($variant === 'login') {
        $config['profile']['enabled'] = false;
    }

    if (!empty($options['search']) && is_array($options['search'])) {
        $config['search'] = array_replace($config['search'], $options['search']);
    }

    if (!empty($options['profile']) && is_array($options['profile'])) {
        $config['profile'] = array_replace($config['profile'], $options['profile']);
    }

    if (!empty($options['helpItems']) && is_array($options['helpItems'])) {
        $config['helpItems'] = array_values($options['helpItems']);
    }

    return $config;
}
