<?php

require_once __DIR__ . '/config.php';

spl_autoload_register(static function ($class) {
    $path = __DIR__ . '/class/' . str_replace('\\', '/', strtolower((string)$class)) . '.class.php';
    if (is_file($path)) {
        require_once $path;
    }
});

require_once __DIR__ . '/common/translation_bundles.php';

// TODO: Confirm these public destinations before publishing this page.
$demoUrl = '/omo/';
$documentationUrl = '#';
$sourceCodeUrl = 'https://github.com/URL-A-CONFIGURER';
$communityUrl = '#communaute';
$supportUrl = '#communaute';
$contactUrl = '#';
$legalUrl = '#';
$privacyUrl = '/common/politique-confidentialite.php';
$licenseUrl = '#';
$canonicalUrl = '';
$socialImagePath = '/img/omo2-social-card.png';

$requestHost = preg_replace('/[^a-zA-Z0-9.:-]/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
$requestScheme = (
    (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
    || (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
) ? 'https' : 'http';
$socialImageUrl = $requestHost !== ''
    ? $requestScheme . '://' . $requestHost . $socialImagePath
    : $socialImagePath;

$sourceLang = [
    'meta.title' => [
        'text' => 'Open My Organization 2 — Logiciel open source de maturité organisationnelle',
        'context' => 'SEO title of the public OMO 2 landing page.',
    ],
    'meta.description' => [
        'text' => 'Clarifiez les rôles, distribuez les responsabilités, pilotez vos projets et faites évoluer votre organisation avec une plateforme ouverte, modulaire et coopérative.',
        'context' => 'SEO description of the public OMO 2 landing page.',
    ],
    'skip.content' => [
        'text' => 'Aller au contenu principal',
        'context' => 'Accessibility skip link.',
    ],
    'brand.name' => [
        'text' => 'Open My Organization',
        'context' => 'Product name in the header.',
    ],
    'brand.version' => [
        'text' => 'Version 2',
        'context' => 'Product version badge.',
    ],
    'nav.label' => [
        'text' => 'Navigation principale',
        'context' => 'Accessible label for the primary navigation.',
    ],
    'nav.toggle' => [
        'text' => 'Ouvrir le menu',
        'context' => 'Accessible label for the mobile menu button.',
    ],
    'nav.why' => [
        'text' => 'Pourquoi OMO ?',
        'context' => 'Navigation link to the benefits section.',
    ],
    'nav.features' => [
        'text' => 'Fonctionnalités',
        'context' => 'Navigation link to the features section.',
    ],
    'nav.principles' => [
        'text' => 'Nos principes',
        'context' => 'Navigation link to the principles section.',
    ],
    'nav.opensource' => [
        'text' => 'Open source',
        'context' => 'Navigation link to the open source section.',
    ],
    'nav.community' => [
        'text' => 'Communauté',
        'context' => 'Navigation link to the community section.',
    ],
    'nav.documentation' => [
        'text' => 'Documentation',
        'context' => 'Header link to the product documentation.',
    ],
    'action.demo' => [
        'text' => 'Essayer la démo',
        'context' => 'Primary call to action leading to the demo.',
    ],
    'action.demo_access' => [
        'text' => 'Accéder à la démo',
        'context' => 'Primary call to action inside the dedicated demo section.',
    ],
    'action.explore' => [
        'text' => 'Explorer les fonctionnalités',
        'context' => 'Secondary call to action leading to the features.',
    ],
    'action.source' => [
        'text' => 'Voir le code source',
        'context' => 'Call to action leading to the source repository.',
    ],
    'action.community' => [
        'text' => 'Rejoindre la communauté',
        'context' => 'Call to action leading to community information.',
    ],
    'action.support' => [
        'text' => 'Soutenir le projet',
        'context' => 'Call to action for voluntary project support.',
    ],
    'hero.eyebrow' => [
        'text' => 'Logiciel open source de maturité organisationnelle',
        'context' => 'Hero eyebrow describing the product category.',
    ],
    'hero.title' => [
        'text' => 'Rendez votre organisation plus claire, autonome et coopérative.',
        'context' => 'Main hero heading.',
    ],
    'hero.description' => [
        'text' => 'Open My Organization 2 aide les équipes à clarifier leur fonctionnement, distribuer les responsabilités, piloter leurs activités et faire évoluer leur cadre commun.',
        'context' => 'Hero product description.',
    ],
    'hero.proof.opensource' => [
        'text' => 'Open source',
        'context' => 'Short product attribute below the hero actions.',
    ],
    'hero.proof.modular' => [
        'text' => 'Modulaire',
        'context' => 'Short product attribute below the hero actions.',
    ],
    'hero.proof.selfhosted' => [
        'text' => 'Auto-hébergeable',
        'context' => 'Short product attribute below the hero actions.',
    ],
    'hero.proof.progressive' => [
        'text' => 'Adoption progressive',
        'context' => 'Short product attribute below the hero actions.',
    ],
    'visual.organization' => [
        'text' => 'Organisation',
        'context' => 'Label in the abstract organization illustration.',
    ],
    'visual.teams' => [
        'text' => 'Équipes',
        'context' => 'Label in the abstract organization illustration.',
    ],
    'visual.roles' => [
        'text' => 'Rôles',
        'context' => 'Label in the abstract organization illustration.',
    ],
    'visual.projects' => [
        'text' => 'Projets',
        'context' => 'Label in the abstract organization illustration.',
    ],
    'visual.decisions' => [
        'text' => 'Décisions',
        'context' => 'Label in the abstract organization illustration.',
    ],
    'visual.shared_frame' => [
        'text' => 'Un cadre partagé, vivant et lisible',
        'context' => 'Caption in the abstract hero illustration.',
    ],
    'experience.eyebrow' => [
        'text' => 'Un projet qui apprend',
        'context' => 'Eyebrow for the product history section.',
    ],
    'experience.title' => [
        'text' => 'Douze années d’expérience au service des organisations qui évoluent',
        'context' => 'Heading for the product history section.',
    ],
    'experience.text' => [
        'text' => 'Né pour soutenir la pratique de l’Holacratie, Open My Organization s’est progressivement ouvert à d’autres formes de gouvernance et à différentes manières d’organiser le travail. OMO 2 poursuit cette histoire avec une ambition plus large : aider chaque organisation à rendre son fonctionnement réel visible, explicite et capable d’évoluer.',
        'context' => 'Product history and positioning paragraph.',
    ],
    'experience.marker.years.value' => [
        'text' => '12 ans',
        'context' => 'Safe longevity marker.',
    ],
    'experience.marker.years.label' => [
        'text' => 'd’évolution continue',
        'context' => 'Label for the longevity marker.',
    ],
    'experience.marker.open.value' => [
        'text' => 'Ouvert',
        'context' => 'Safe open source marker.',
    ],
    'experience.marker.open.label' => [
        'text' => 'dans ses usages et son code',
        'context' => 'Label for the open source marker.',
    ],
    'experience.marker.models.value' => [
        'text' => 'Pluraliste',
        'context' => 'Safe marker about organizational models.',
    ],
    'experience.marker.models.label' => [
        'text' => 'sans modèle unique imposé',
        'context' => 'Label for the model flexibility marker.',
    ],
    'experience.marker.modules.value' => [
        'text' => 'Modulaire',
        'context' => 'Safe marker about product modularity.',
    ],
    'experience.marker.modules.label' => [
        'text' => 'à composer selon vos besoins',
        'context' => 'Label for the modularity marker.',
    ],
    'why.eyebrow' => [
        'text' => 'Du flou vers un cadre partagé',
        'context' => 'Eyebrow for the problem and benefits section.',
    ],
    'why.title' => [
        'text' => 'Mieux travailler commence par mieux voir comment l’organisation fonctionne',
        'context' => 'Heading for the problem and benefits section.',
    ],
    'why.intro' => [
        'text' => 'OMO 2 ne promet pas une organisation parfaite. Il crée un espace commun pour observer ce qui existe, clarifier ce qui manque et faire évoluer les pratiques avec méthode.',
        'context' => 'Introduction to the problem and benefits comparison.',
    ],
    'why.before.title' => [
        'text' => 'Quand le fonctionnement reste implicite',
        'context' => 'Title for the organizational problems list.',
    ],
    'why.after.title' => [
        'text' => 'Avec un environnement partagé',
        'context' => 'Title for the organizational benefits list.',
    ],
    'why.before.1' => [
        'text' => 'Les responsabilités se chevauchent ou restent floues.',
        'context' => 'Organizational problem statement.',
    ],
    'why.before.2' => [
        'text' => 'Les informations et décisions sont difficiles à retrouver.',
        'context' => 'Organizational problem statement.',
    ],
    'why.before.3' => [
        'text' => 'Les projets dépendent trop de quelques personnes.',
        'context' => 'Organizational problem statement.',
    ],
    'why.before.4' => [
        'text' => 'Les réunions produisent peu de résultats concrets.',
        'context' => 'Organizational problem statement.',
    ],
    'why.before.5' => [
        'text' => 'Les règles évoluent, mais leur mémoire se perd.',
        'context' => 'Organizational problem statement.',
    ],
    'why.after.1' => [
        'text' => 'Chacun peut retrouver qui porte quoi et avec quelle autorité.',
        'context' => 'Organizational benefit statement.',
    ],
    'why.after.2' => [
        'text' => 'Le cadre, les accords et les décisions deviennent accessibles.',
        'context' => 'Organizational benefit statement.',
    ],
    'why.after.3' => [
        'text' => 'Les tensions se transforment en actions, projets ou évolutions.',
        'context' => 'Organizational benefit statement.',
    ],
    'why.after.4' => [
        'text' => 'Les activités et indicateurs offrent une vision commune.',
        'context' => 'Organizational benefit statement.',
    ],
    'why.after.5' => [
        'text' => 'L’organisation apprend de son expérience et ajuste ses pratiques.',
        'context' => 'Organizational benefit statement.',
    ],
    'maturity.eyebrow' => [
        'text' => 'Une progression, pas un modèle figé',
        'context' => 'Eyebrow for the organizational maturity section.',
    ],
    'maturity.title' => [
        'text' => 'La maturité organisationnelle, concrètement',
        'context' => 'Heading for the organizational maturity section.',
    ],
    'maturity.intro' => [
        'text' => 'La maturité organisationnelle désigne la capacité d’une organisation à comprendre son propre fonctionnement, à reconnaître les responsabilités, à permettre l’initiative et à faire évoluer ses règles à partir de l’expérience. Elle cherche un équilibre vivant entre autonomie individuelle et coopération collective.',
        'context' => 'Plain language explanation of organizational maturity.',
    ],
    'value.trust.title' => [
        'text' => 'Confiance',
        'context' => 'Organizational value card title.',
    ],
    'value.trust.text' => [
        'text' => 'Donner accès aux informations utiles et permettre à chacun d’agir dans un cadre lisible.',
        'context' => 'Organizational value card description.',
    ],
    'value.cooperation.title' => [
        'text' => 'Coopération',
        'context' => 'Organizational value card title.',
    ],
    'value.cooperation.text' => [
        'text' => 'Créer des espaces partagés pour coordonner activités, projets, décisions et réunions.',
        'context' => 'Organizational value card description.',
    ],
    'value.sovereignty.title' => [
        'text' => 'Souveraineté',
        'context' => 'Organizational value card title.',
    ],
    'value.sovereignty.text' => [
        'text' => 'Permettre à chaque personne ou équipe d’exercer pleinement l’autorité qui lui est confiée.',
        'context' => 'Organizational value card description.',
    ],
    'value.responsibility.title' => [
        'text' => 'Responsabilité',
        'context' => 'Organizational value card title.',
    ],
    'value.responsibility.text' => [
        'text' => 'Clarifier qui porte quoi, les résultats attendus et les décisions qui peuvent être prises.',
        'context' => 'Organizational value card description.',
    ],
    'value.pragmatism.title' => [
        'text' => 'Pragmatisme',
        'context' => 'Organizational value card title.',
    ],
    'value.pragmatism.text' => [
        'text' => 'Transformer les besoins et tensions en actions, projets, décisions ou évolutions concrètes.',
        'context' => 'Organizational value card description.',
    ],
    'value.respect.title' => [
        'text' => 'Respect',
        'context' => 'Organizational value card title.',
    ],
    'value.respect.text' => [
        'text' => 'Rendre règles et attentes explicites pour soutenir des relations plus justes et plus lisibles.',
        'context' => 'Organizational value card description.',
    ],
    'features.eyebrow' => [
        'text' => 'Des outils reliés entre eux',
        'context' => 'Eyebrow for the product features section.',
    ],
    'features.title' => [
        'text' => 'Un espace commun, composé selon vos besoins',
        'context' => 'Heading for the product features section.',
    ],
    'features.intro' => [
        'text' => 'Chaque organisation choisit les applications qui servent sa situation actuelle. Le vocabulaire, les usages et le rythme d’adoption peuvent évoluer avec elle.',
        'context' => 'Introduction to the product features section.',
    ],
    'feature.structure.title' => [
        'text' => 'Rendre la structure compréhensible',
        'context' => 'Feature section title about organization structure.',
    ],
    'feature.structure.text' => [
        'text' => 'Représentez votre organisation avec les mots qui lui ressemblent : cercles, équipes, unités, rôles, fonctions ou autre vocabulaire configurable.',
        'context' => 'Feature section description about organization structure.',
    ],
    'feature.structure.1' => [
        'text' => 'Créer des équipes, unités, rôles ou fonctions.',
        'context' => 'Organization structure feature.',
    ],
    'feature.structure.2' => [
        'text' => 'Définir raison d’être, responsabilités et domaines d’autorité.',
        'context' => 'Organization structure feature.',
    ],
    'feature.structure.3' => [
        'text' => 'Affecter des personnes et visualiser les liens.',
        'context' => 'Organization structure feature.',
    ],
    'feature.structure.4' => [
        'text' => 'Retrouver rapidement qui fait quoi.',
        'context' => 'Organization structure feature.',
    ],
    'feature.frame.title' => [
        'text' => 'Clarifier le cadre et l’autorité',
        'context' => 'Feature section title about organizational rules.',
    ],
    'feature.frame.text' => [
        'text' => 'Un cadre clair ne réduit pas l’autonomie : il indique où elle peut pleinement s’exercer et rend les responsabilités vérifiables.',
        'context' => 'Feature section description about organizational rules.',
    ],
    'feature.frame.1' => [
        'text' => 'Documenter règles, politiques et accords.',
        'context' => 'Organizational rules feature.',
    ],
    'feature.frame.2' => [
        'text' => 'Attribuer des domaines et droits de décision.',
        'context' => 'Organizational rules feature.',
    ],
    'feature.frame.3' => [
        'text' => 'Faire évoluer le cadre de fonctionnement.',
        'context' => 'Organizational rules feature.',
    ],
    'feature.frame.4' => [
        'text' => 'Conserver la trace des décisions structurantes.',
        'context' => 'Organizational rules feature.',
    ],
    'feature.projects.title' => [
        'text' => 'Transformer les intentions en projets',
        'context' => 'Feature section title about projects and actions.',
    ],
    'feature.projects.text' => [
        'text' => 'Reliez l’activité opérationnelle à la structure et aux espaces où les besoins apparaissent.',
        'context' => 'Feature section description about projects and actions.',
    ],
    'feature.projects.1' => [
        'text' => 'Créer et suivre projets et prochaines actions.',
        'context' => 'Projects feature.',
    ],
    'feature.projects.2' => [
        'text' => 'Affecter des responsables et suivre l’avancement.',
        'context' => 'Projects feature.',
    ],
    'feature.projects.3' => [
        'text' => 'Organiser le travail en listes ou tableaux.',
        'context' => 'Projects feature.',
    ],
    'feature.projects.4' => [
        'text' => 'Relier un projet à une équipe, un rôle ou une séance, et transformer une tension en résultat concret.',
        'context' => 'Projects feature.',
    ],
    'feature.dashboard.title' => [
        'text' => 'Partager une vision de la situation',
        'context' => 'Feature section title about dashboards.',
    ],
    'feature.dashboard.text' => [
        'text' => 'Des tableaux de pilotage rassemblent les données utiles pour observer, décider et apprendre ensemble.',
        'context' => 'Feature section description about dashboards.',
    ],
    'feature.dashboard.1' => [
        'text' => 'Suivre des indicateurs et l’évolution des activités.',
        'context' => 'Dashboard feature.',
    ],
    'feature.dashboard.2' => [
        'text' => 'Présenter les données utiles à une équipe.',
        'context' => 'Dashboard feature.',
    ],
    'feature.dashboard.3' => [
        'text' => 'Partager les résultats et soutenir les revues.',
        'context' => 'Dashboard feature.',
    ],
    'feature.dashboard.4' => [
        'text' => 'Construire une compréhension commune.',
        'context' => 'Dashboard feature.',
    ],
    'feature.quality.title' => [
        'text' => 'Faire vivre la qualité au quotidien',
        'context' => 'Feature section title about quality and checklists.',
    ],
    'feature.quality.text' => [
        'text' => 'Les processus et leurs activités rendent les pratiques transmissibles, observables et améliorables.',
        'context' => 'Feature section description about quality and checklists.',
    ],
    'feature.quality.1' => [
        'text' => 'Créer et formaliser des processus réutilisables.',
        'context' => 'Quality feature.',
    ],
    'feature.quality.2' => [
        'text' => 'Vérifier les opérations récurrentes.',
        'context' => 'Quality feature.',
    ],
    'feature.quality.3' => [
        'text' => 'Rendre visibles les bonnes pratiques.',
        'context' => 'Quality feature.',
    ],
    'feature.quality.4' => [
        'text' => 'Faire évoluer les méthodes avec l’expérience.',
        'context' => 'Quality feature.',
    ],
    'feature.meetings.title' => [
        'text' => 'Tenir des séances qui font avancer',
        'context' => 'Feature section title about meetings.',
    ],
    'feature.meetings.text' => [
        'text' => 'Préparez et animez des réunions structurées, inspirées de l’Holacratie ou d’autres pratiques, sans imposer une méthode unique.',
        'context' => 'Feature section description about meetings.',
    ],
    'feature.meetings.1' => [
        'text' => 'Préparer l’ordre du jour et recueillir les tensions.',
        'context' => 'Meetings feature.',
    ],
    'feature.meetings.2' => [
        'text' => 'Suivre un processus orienté vers des résultats concrets.',
        'context' => 'Meetings feature.',
    ],
    'feature.meetings.3' => [
        'text' => 'Créer actions, projets, décisions ou évolutions du cadre.',
        'context' => 'Meetings feature.',
    ],
    'feature.meetings.4' => [
        'text' => 'Conserver une mémoire accessible de la séance.',
        'context' => 'Meetings feature.',
    ],
    'modular.eyebrow' => [
        'text' => 'Une plateforme modulaire',
        'context' => 'Eyebrow for the modularity section.',
    ],
    'modular.title' => [
        'text' => 'Commencez là où le besoin est le plus concret',
        'context' => 'Heading for the modularity section.',
    ],
    'modular.text' => [
        'text' => 'Activez uniquement les applications utiles aujourd’hui, puis enrichissez votre environnement au fil de votre évolution. Adopter OMO 2 ne demande pas de transformer toute l’organisation en une seule fois.',
        'context' => 'Description of progressive modular adoption.',
    ],
    'module.structure' => ['text' => 'Structure', 'context' => 'Module card label.'],
    'module.roles' => ['text' => 'Rôles et responsabilités', 'context' => 'Module card label.'],
    'module.frame' => ['text' => 'Cadre', 'context' => 'Module card label.'],
    'module.projects' => ['text' => 'Projets', 'context' => 'Module card label.'],
    'module.metrics' => ['text' => 'Indicateurs', 'context' => 'Module card label.'],
    'module.checklists' => ['text' => 'Processus', 'context' => 'Module card label.'],
    'module.processes' => ['text' => 'Processus', 'context' => 'Module card label.'],
    'module.meetings' => ['text' => 'Séances', 'context' => 'Module card label.'],
    'module.documentation' => ['text' => 'Documentation', 'context' => 'Module card label.'],
    'module.help' => ['text' => 'Aide et tutoriels', 'context' => 'Module card label.'],
    'pedagogy.eyebrow' => [
        'text' => 'Un logiciel pédagogique',
        'context' => 'Eyebrow for the learning support section.',
    ],
    'pedagogy.title' => [
        'text' => 'Faire grandir les pratiques autant que les outils',
        'context' => 'Heading for the learning support section.',
    ],
    'pedagogy.text' => [
        'text' => 'L’aide contextuelle, les tutoriels, la FAQ et la documentation accompagnent un apprentissage progressif. Chaque organisation peut enrichir ces contenus avec son vocabulaire, ses règles et ses méthodes pour mieux accueillir les nouveaux membres et transmettre ses pratiques.',
        'context' => 'Description of the learning support system.',
    ],
    'pedagogy.card.context.title' => [
        'text' => 'Au bon moment',
        'context' => 'Learning support benefit title.',
    ],
    'pedagogy.card.context.text' => [
        'text' => 'Une aide contextualisée au plus près de l’action.',
        'context' => 'Learning support benefit description.',
    ],
    'pedagogy.card.custom.title' => [
        'text' => 'À vos mots',
        'context' => 'Learning support benefit title.',
    ],
    'pedagogy.card.custom.text' => [
        'text' => 'Des contenus adaptables à votre vocabulaire et vos pratiques.',
        'context' => 'Learning support benefit description.',
    ],
    'pedagogy.card.transmission.title' => [
        'text' => 'Dans la durée',
        'context' => 'Learning support benefit title.',
    ],
    'pedagogy.card.transmission.text' => [
        'text' => 'Une mémoire partagée pour transmettre et progresser.',
        'context' => 'Learning support benefit description.',
    ],
    'usecases.eyebrow' => [
        'text' => 'Pour des organisations différentes',
        'context' => 'Eyebrow for the use cases section.',
    ],
    'usecases.title' => [
        'text' => 'OMO 2 s’adapte à votre manière de travailler',
        'context' => 'Heading for the use cases section.',
    ],
    'usecases.intro' => [
        'text' => 'Entreprise, association, coopérative, collectif, institution ou équipe autonome : l’outil soutient une progression choisie, d’un cadre hiérarchique clarifié à une autorité plus distribuée.',
        'context' => 'Introduction to the use cases section.',
    ],
    'usecase.1.title' => ['text' => 'Clarifier les responsabilités', 'context' => 'Use case title.'],
    'usecase.1.text' => ['text' => 'Rendre visibles les rôles, attentes et domaines de décision d’une équipe.', 'context' => 'Use case description.'],
    'usecase.2.title' => ['text' => 'Documenter le fonctionnement', 'context' => 'Use case title.'],
    'usecase.2.text' => ['text' => 'Rassembler structure, accords, processus et mémoire des décisions.', 'context' => 'Use case description.'],
    'usecase.3.title' => ['text' => 'Distribuer l’autorité', 'context' => 'Use case title.'],
    'usecase.3.text' => ['text' => 'Faire évoluer progressivement qui peut décider de quoi, dans un cadre explicite.', 'context' => 'Use case description.'],
    'usecase.4.title' => ['text' => 'Structurer les réunions', 'context' => 'Use case title.'],
    'usecase.4.text' => ['text' => 'Transformer les sujets et tensions en résultats traçables.', 'context' => 'Use case description.'],
    'usecase.5.title' => ['text' => 'Piloter les activités', 'context' => 'Use case title.'],
    'usecase.5.text' => ['text' => 'Relier projets, actions et indicateurs à la structure réelle.', 'context' => 'Use case description.'],
    'usecase.6.title' => ['text' => 'Accompagner une transformation', 'context' => 'Use case title.'],
    'usecase.6.text' => ['text' => 'Soutenir l’Holacratie, la sociocratie ou une démarche propre à l’organisation.', 'context' => 'Use case description.'],
    'opensource.eyebrow' => [
        'text' => 'Souveraineté numérique',
        'context' => 'Eyebrow for the open source section.',
    ],
    'opensource.title' => [
        'text' => 'Un logiciel ouvert, jusque dans son code',
        'context' => 'Heading for the open source section.',
    ],
    'opensource.text' => [
        'text' => 'Le code source peut être consulté, discuté et amélioré. Vous pouvez proposer des évolutions, signaler un besoin, contribuer au développement ou installer OMO 2 sur votre propre serveur pour garder la maîtrise de votre infrastructure et de vos données.',
        'context' => 'Description of open source and self-hosting options.',
    ],
    'opensource.option.use.title' => ['text' => 'Utiliser', 'context' => 'Open source option title.'],
    'opensource.option.use.text' => ['text' => 'Découvrir l’instance proposée et choisir les modules utiles.', 'context' => 'Open source option description.'],
    'opensource.option.contribute.title' => ['text' => 'Contribuer', 'context' => 'Open source option title.'],
    'opensource.option.contribute.text' => ['text' => 'Partager du code, des retours, des traductions ou de la documentation.', 'context' => 'Open source option description.'],
    'opensource.option.host.title' => ['text' => 'Auto-héberger', 'context' => 'Open source option title.'],
    'opensource.option.host.text' => ['text' => 'Installer OMO 2 sur votre infrastructure et garder la main.', 'context' => 'Open source option description.'],
    'community.eyebrow' => [
        'text' => 'Un projet collectif',
        'context' => 'Eyebrow for the community funding section.',
    ],
    'community.title' => [
        'text' => 'Libre d’accès, soutenu par celles et ceux qui le font vivre',
        'context' => 'Heading for the community funding section.',
    ],
    'community.text' => [
        'text' => 'L’accès libre repose sur une communauté et un travail continu. Chacun peut contribuer selon ses moyens : code, retours d’usage, documentation, traduction, partage d’expérience ou soutien financier volontaire. Cette diversité aide le projet à rester ouvert, utile et durable.',
        'context' => 'Description of the community contribution model.',
    ],
    'partners.eyebrow' => [
        'text' => 'Partenaires et écosystème',
        'context' => 'Eyebrow for the partner section.',
    ],
    'partners.title' => [
        'text' => 'Conçu en dialogue avec celles et ceux qui accompagnent les organisations',
        'context' => 'Heading for the partner section.',
    ],
    'partners.text' => [
        'text' => 'Open My Organization 2 échange avec des professionnels de la formation, de l’accompagnement et de la transformation organisationnelle. Ces regards nourrissent une approche pratique et ancrée dans le réel.',
        'context' => 'Description of the partner ecosystem.',
    ],
    'partners.logo.placeholder' => [
        'text' => 'Emplacement du logo',
        'context' => 'Visible placeholder label for a future partner logo.',
    ],
    'partner.presence' => ['text' => 'Présence Active', 'context' => 'Partner organization name.'],
    'partner.instant' => ['text' => 'l’Instant Z', 'context' => 'Partner organization name.'],
    'partner.wereinvent' => ['text' => 'WeReinvent', 'context' => 'Partner organization name.'],
    'journey.eyebrow' => [
        'text' => 'Un parcours simple',
        'context' => 'Eyebrow for the discovery journey section.',
    ],
    'journey.title' => [
        'text' => 'Découvrir aujourd’hui, construire progressivement',
        'context' => 'Heading for the discovery journey section.',
    ],
    'journey.1.title' => ['text' => 'Explorez la démonstration', 'context' => 'Discovery journey step title.'],
    'journey.1.text' => ['text' => 'Parcourez les espaces, rôles, projets et séances dans un environnement d’expérimentation.', 'context' => 'Discovery journey step description.'],
    'journey.2.title' => ['text' => 'Choisissez vos modules', 'context' => 'Discovery journey step title.'],
    'journey.2.text' => ['text' => 'Activez les applications correspondant aux besoins actuels de votre organisation.', 'context' => 'Discovery journey step description.'],
    'journey.3.title' => ['text' => 'Faites évoluer votre cadre', 'context' => 'Discovery journey step title.'],
    'journey.3.text' => ['text' => 'Enrichissez progressivement structure, pratiques, contenus d’aide et règles de fonctionnement.', 'context' => 'Discovery journey step description.'],
    'demo.eyebrow' => [
        'text' => 'Un espace pour expérimenter',
        'context' => 'Eyebrow for the demo section.',
    ],
    'demo.title' => [
        'text' => 'Explorez OMO 2 librement',
        'context' => 'Heading for the demo section.',
    ],
    'demo.text' => [
        'text' => 'Découvrez l’interface, explorez un exemple d’organisation et testez la création de contenus dans un environnement prévu pour l’essai.',
        'context' => 'Description of the public demo.',
    ],
    'demo.benefit.interface' => [
        'text' => 'Découvrir l’interface',
        'context' => 'Short benefit of the public demo.',
    ],
    'demo.benefit.content' => [
        'text' => 'Créer des contenus',
        'context' => 'Short benefit of the public demo.',
    ],
    'demo.benefit.features' => [
        'text' => 'Tester les fonctionnalités',
        'context' => 'Short benefit of the public demo.',
    ],
    'demo.benefit.example' => [
        'text' => 'Explorer une organisation exemple',
        'context' => 'Short benefit of the public demo.',
    ],
    'demo.warning.title' => [
        'text' => 'La démonstration est un espace d’essai public.',
        'context' => 'Title of the demo privacy warning.',
    ],
    'demo.warning.text' => [
        'text' => 'N’y saisissez aucune information confidentielle. Les données sont régulièrement supprimées et réinitialisées.',
        'context' => 'Demo privacy and reset warning.',
    ],
    'faq.eyebrow' => [
        'text' => 'Questions fréquentes',
        'context' => 'Eyebrow for the FAQ section.',
    ],
    'faq.title' => [
        'text' => 'Ce qu’il faut savoir avant de commencer',
        'context' => 'Heading for the FAQ section.',
    ],
    'faq.1.q' => ['text' => 'Open My Organization est-il réservé à l’Holacratie ?', 'context' => 'FAQ question.'],
    'faq.1.a' => ['text' => 'Non. L’Holacratie fait partie de son histoire, mais OMO 2 peut soutenir d’autres formes de gouvernance partagée, ainsi que des organisations hiérarchiques qui souhaitent simplement clarifier leur fonctionnement.', 'context' => 'FAQ answer.'],
    'faq.2.q' => ['text' => 'Peut-on adapter le vocabulaire des cercles et des rôles ?', 'context' => 'FAQ question.'],
    'faq.2.a' => ['text' => 'Oui. L’objectif est de décrire votre organisation avec des termes qui ont du sens pour elle, par exemple équipes, unités, fonctions ou responsabilités.', 'context' => 'FAQ answer.'],
    'faq.3.q' => ['text' => 'Doit-on activer toutes les fonctionnalités ?', 'context' => 'FAQ question.'],
    'faq.3.a' => ['text' => 'Non. La plateforme est modulaire : vous pouvez commencer avec quelques applications et en ajouter lorsque de nouveaux besoins apparaissent.', 'context' => 'FAQ answer.'],
    'faq.4.q' => ['text' => 'Peut-on installer OMO 2 sur son propre serveur ?', 'context' => 'FAQ question.'],
    'faq.4.a' => ['text' => 'Le projet vise à permettre l’auto-hébergement. Les modalités techniques et la documentation d’installation doivent être confirmées avant une mise en production.', 'context' => 'Cautious FAQ answer about self-hosting.'],
    'faq.5.q' => ['text' => 'Le logiciel est-il réellement open source ?', 'context' => 'FAQ question.'],
    'faq.5.a' => ['text' => 'Le projet est présenté comme open source et son dépôt permettra de consulter le code et de proposer des contributions. Le lien public définitif et les informations de licence restent à configurer sur cette page.', 'context' => 'Cautious FAQ answer about source code availability.'],
    'faq.6.q' => ['text' => 'Comment les données de la démonstration sont-elles gérées ?', 'context' => 'FAQ question.'],
    'faq.6.a' => ['text' => 'La démonstration est publique et régulièrement réinitialisée. Elle ne doit jamais contenir de données personnelles, sensibles ou confidentielles.', 'context' => 'FAQ answer about demo data.',
    ],
    'faq.7.q' => ['text' => 'Comment contribuer au projet ?', 'context' => 'FAQ question.'],
    'faq.7.a' => ['text' => 'Vous pouvez contribuer par du code, des retours d’usage, de la documentation, des traductions, des propositions ou un soutien financier volontaire.', 'context' => 'FAQ answer about contributions.'],
    'faq.8.q' => ['text' => 'Existe-t-il un accompagnement pour faire évoluer les pratiques ?', 'context' => 'FAQ question.'],
    'faq.8.a' => ['text' => 'Des partenaires de formation et d’accompagnement participent à l’écosystème. Les offres et modalités précises devront être consultées directement auprès d’eux.', 'context' => 'Cautious FAQ answer about partner support.'],
    'final.title' => [
        'text' => 'Votre organisation évolue. Son système de fonctionnement peut évoluer avec elle.',
        'context' => 'Final call to action heading.',
    ],
    'final.text' => [
        'text' => 'Commencez par explorer, choisissez ce qui vous est utile et construisez un cadre commun à votre rythme.',
        'context' => 'Final call to action supporting text.',
    ],
    'footer.tagline' => [
        'text' => 'Un environnement ouvert, modulaire et coopératif pour la maturité organisationnelle.',
        'context' => 'Short product tagline in the footer.',
    ],
    'footer.product' => ['text' => 'Produit', 'context' => 'Footer navigation group title.'],
    'footer.project' => ['text' => 'Projet', 'context' => 'Footer navigation group title.'],
    'footer.information' => ['text' => 'Informations', 'context' => 'Footer navigation group title.'],
    'footer.source' => ['text' => 'Code source', 'context' => 'Footer link label.'],
    'footer.support' => ['text' => 'Soutien', 'context' => 'Footer link label.'],
    'footer.legal' => ['text' => 'Mentions légales', 'context' => 'Footer link label.'],
    'footer.privacy' => ['text' => 'Politique de confidentialité', 'context' => 'Footer link label.'],
    'footer.contact' => ['text' => 'Contact', 'context' => 'Footer link label.'],
    'footer.license' => ['text' => 'Licence open source', 'context' => 'Footer link label.'],
    'footer.copyright' => [
        'text' => '© {year} Open My Organization. Projet open source.',
        'context' => 'Footer copyright statement with a dynamic year.',
    ],
];

$locale = translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
$lang = translationBundleInit('omo_landing_v2_page', $locale, $sourceLang);

function landingEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function landingUrl($value)
{
    return landingEscape($value);
}

$values = [
    ['title' => 'value.trust.title', 'text' => 'value.trust.text', 'symbol' => '↗'],
    ['title' => 'value.cooperation.title', 'text' => 'value.cooperation.text', 'symbol' => '○'],
    ['title' => 'value.sovereignty.title', 'text' => 'value.sovereignty.text', 'symbol' => '◇'],
    ['title' => 'value.responsibility.title', 'text' => 'value.responsibility.text', 'symbol' => '✓'],
    ['title' => 'value.pragmatism.title', 'text' => 'value.pragmatism.text', 'symbol' => '→'],
    ['title' => 'value.respect.title', 'text' => 'value.respect.text', 'symbol' => '≈'],
];

$features = [
    ['key' => 'structure', 'visual' => 'structure', 'items' => 4],
    ['key' => 'frame', 'visual' => 'frame', 'items' => 4],
    ['key' => 'projects', 'visual' => 'projects', 'items' => 4],
    ['key' => 'dashboard', 'visual' => 'dashboard', 'items' => 4],
    ['key' => 'quality', 'visual' => 'quality', 'items' => 4],
    ['key' => 'meetings', 'visual' => 'meetings', 'items' => 4],
];

$moduleKeys = ['structure', 'roles', 'frame', 'projects', 'metrics', 'checklists', 'processes', 'meetings', 'documentation', 'help'];
$useCases = range(1, 6);
$journeySteps = range(1, 3);
$faqItems = range(1, 8);

$softwareSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => 'Open My Organization 2',
    'alternateName' => 'OMO 2',
    'applicationCategory' => 'BusinessApplication',
    'operatingSystem' => 'Web',
    'description' => t('meta.description'),
    'isAccessibleForFree' => true,
    'softwareHelp' => $documentationUrl !== '#' ? $documentationUrl : null,
    'codeRepository' => strpos($sourceCodeUrl, 'URL-A-CONFIGURER') === false ? $sourceCodeUrl : null,
];
$softwareSchema = array_filter($softwareSchema, static fn($value) => $value !== null);
?>
<!DOCTYPE html>
<html lang="<?= landingEscape($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= landingEscape(t('meta.title')) ?></title>
    <meta name="description" content="<?= landingEscape(t('meta.description')) ?>">
    <meta name="theme-color" content="#0c6b62">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= landingEscape(t('meta.title')) ?>">
    <meta property="og:description" content="<?= landingEscape(t('meta.description')) ?>">
    <meta property="og:site_name" content="Open My Organization">
    <meta property="og:image" content="<?= landingUrl($socialImageUrl) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= landingEscape(t('hero.title')) ?>">
<?php if ($canonicalUrl !== ''): ?>
    <link rel="canonical" href="<?= landingUrl($canonicalUrl) ?>">
    <meta property="og:url" content="<?= landingUrl($canonicalUrl) ?>">
<?php else: ?>
    <!-- TODO: Configure the canonical public URL and og:url before publication. -->
<?php endif; ?>
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/assets/css/index2.css">
    <script>document.documentElement.classList.add('js');</script>
    <script type="application/ld+json"><?= json_encode($softwareSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</head>
<body>
    <a class="landing-skip-link" href="#contenu"><?= landingEscape(t('skip.content')) ?></a>

    <header class="landing-header" data-landing-header>
        <div class="landing-container landing-header__inner">
            <a class="landing-brand" href="#accueil" aria-label="<?= landingEscape(t('brand.name')) ?>">
                <span class="landing-brand__mark" aria-hidden="true"><span></span><span></span><span></span></span>
                <span class="landing-brand__copy">
                    <strong><?= landingEscape(t('brand.name')) ?></strong>
                    <span><?= landingEscape(t('brand.version')) ?></span>
                </span>
            </a>

            <button class="landing-menu-toggle" type="button" aria-expanded="false" aria-controls="landing-navigation" data-menu-toggle>
                <span class="landing-menu-toggle__icon" aria-hidden="true"></span>
                <span class="landing-visually-hidden"><?= landingEscape(t('nav.toggle')) ?></span>
            </button>

            <nav class="landing-nav" id="landing-navigation" aria-label="<?= landingEscape(t('nav.label')) ?>" data-menu>
                <a href="#pourquoi"><?= landingEscape(t('nav.why')) ?></a>
                <a href="#fonctionnalites"><?= landingEscape(t('nav.features')) ?></a>
                <a href="#principes"><?= landingEscape(t('nav.principles')) ?></a>
                <a href="#open-source"><?= landingEscape(t('nav.opensource')) ?></a>
                <a href="#communaute"><?= landingEscape(t('nav.community')) ?></a>
                <a href="<?= landingUrl($documentationUrl) ?>"><?= landingEscape(t('nav.documentation')) ?></a>
                <a class="generic-action-button generic-action-button--main landing-nav__cta" href="<?= landingUrl($demoUrl) ?>"><?= landingEscape(t('action.demo')) ?></a>
            </nav>
        </div>
    </header>

    <main id="contenu">
        <section class="landing-hero" id="accueil">
            <div class="landing-orb landing-orb--one" aria-hidden="true"></div>
            <div class="landing-orb landing-orb--two" aria-hidden="true"></div>
            <div class="landing-container landing-hero__grid">
                <div class="landing-hero__copy">
                    <p class="landing-eyebrow"><?= landingEscape(t('hero.eyebrow')) ?></p>
                    <h1><?= landingEscape(t('hero.title')) ?></h1>
                    <p class="landing-hero__lead"><?= landingEscape(t('hero.description')) ?></p>
                    <div class="landing-actions">
                        <a class="generic-action-button generic-action-button--main landing-button landing-button--primary" href="<?= landingUrl($demoUrl) ?>"><?= landingEscape(t('action.demo')) ?><span aria-hidden="true">→</span></a>
                        <a class="generic-action-button generic-action-button--secondary landing-button" href="#fonctionnalites"><?= landingEscape(t('action.explore')) ?></a>
                    </div>
                    <ul class="landing-proof-list" aria-label="<?= landingEscape(t('hero.eyebrow')) ?>">
                        <?php foreach (['opensource', 'modular', 'selfhosted', 'progressive'] as $proof): ?>
                            <li><span aria-hidden="true">✓</span><?= landingEscape(t('hero.proof.' . $proof)) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="landing-hero-visual" aria-label="<?= landingEscape(t('visual.shared_frame')) ?>" role="img">
                    <div class="landing-hero-visual__halo" aria-hidden="true"></div>
                    <div class="landing-node landing-node--center">
                        <span class="landing-node__dot" aria-hidden="true"></span>
                        <strong><?= landingEscape(t('visual.organization')) ?></strong>
                    </div>
                    <div class="landing-node landing-node--teams"><span aria-hidden="true">○</span><?= landingEscape(t('visual.teams')) ?></div>
                    <div class="landing-node landing-node--roles"><span aria-hidden="true">◇</span><?= landingEscape(t('visual.roles')) ?></div>
                    <div class="landing-node landing-node--projects"><span aria-hidden="true">↗</span><?= landingEscape(t('visual.projects')) ?></div>
                    <div class="landing-node landing-node--decisions"><span aria-hidden="true">✓</span><?= landingEscape(t('visual.decisions')) ?></div>
                    <div class="landing-hero-visual__line landing-hero-visual__line--one" aria-hidden="true"></div>
                    <div class="landing-hero-visual__line landing-hero-visual__line--two" aria-hidden="true"></div>
                    <div class="landing-hero-visual__line landing-hero-visual__line--three" aria-hidden="true"></div>
                    <div class="landing-hero-visual__line landing-hero-visual__line--four" aria-hidden="true"></div>
                    <p><?= landingEscape(t('visual.shared_frame')) ?></p>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section--compact">
            <div class="landing-container">
                <div class="landing-experience generic-hero-panel">
                    <div class="landing-experience__copy">
                        <p class="landing-eyebrow"><?= landingEscape(t('experience.eyebrow')) ?></p>
                        <h2><?= landingEscape(t('experience.title')) ?></h2>
                        <p><?= landingEscape(t('experience.text')) ?></p>
                    </div>
                    <div class="landing-markers">
                        <?php foreach (['years', 'open', 'models', 'modules'] as $marker): ?>
                            <div class="landing-marker">
                                <strong><?= landingEscape(t('experience.marker.' . $marker . '.value')) ?></strong>
                                <span><?= landingEscape(t('experience.marker.' . $marker . '.label')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section--mist" id="pourquoi">
            <div class="landing-container">
                <div class="landing-section-heading landing-section-heading--center">
                    <p class="landing-eyebrow"><?= landingEscape(t('why.eyebrow')) ?></p>
                    <h2><?= landingEscape(t('why.title')) ?></h2>
                    <p><?= landingEscape(t('why.intro')) ?></p>
                </div>
                <div class="landing-comparison">
                    <?php foreach (['before', 'after'] as $side): ?>
                        <article class="generic-section landing-comparison__card landing-comparison__card--<?= landingEscape($side) ?>">
                            <div class="landing-comparison__icon" aria-hidden="true"><?= $side === 'before' ? '…' : '↗' ?></div>
                            <h3><?= landingEscape(t('why.' . $side . '.title')) ?></h3>
                            <ul class="landing-check-list">
                                <?php for ($item = 1; $item <= 5; $item++): ?>
                                    <li><span aria-hidden="true"><?= $side === 'before' ? '—' : '✓' ?></span><?= landingEscape(t('why.' . $side . '.' . $item)) ?></li>
                                <?php endfor; ?>
                            </ul>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="landing-section" id="principes">
            <div class="landing-container">
                <div class="landing-section-heading">
                    <p class="landing-eyebrow"><?= landingEscape(t('maturity.eyebrow')) ?></p>
                    <h2><?= landingEscape(t('maturity.title')) ?></h2>
                    <p><?= landingEscape(t('maturity.intro')) ?></p>
                </div>
                <div class="landing-values">
                    <?php foreach ($values as $value): ?>
                        <article class="generic-section landing-value-card">
                            <span class="landing-value-card__symbol" aria-hidden="true"><?= landingEscape($value['symbol']) ?></span>
                            <h3><?= landingEscape(t($value['title'])) ?></h3>
                            <p><?= landingEscape(t($value['text'])) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section--sand" id="fonctionnalites">
            <div class="landing-container">
                <div class="landing-section-heading landing-section-heading--center">
                    <p class="landing-eyebrow"><?= landingEscape(t('features.eyebrow')) ?></p>
                    <h2><?= landingEscape(t('features.title')) ?></h2>
                    <p><?= landingEscape(t('features.intro')) ?></p>
                </div>
                <div class="landing-feature-list">
                    <?php foreach ($features as $index => $feature): ?>
                        <article class="landing-feature<?= $index % 2 === 1 ? ' landing-feature--reverse' : '' ?>">
                            <div class="landing-feature__copy">
                                <span class="landing-feature__number" aria-hidden="true"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                <h3><?= landingEscape(t('feature.' . $feature['key'] . '.title')) ?></h3>
                                <p><?= landingEscape(t('feature.' . $feature['key'] . '.text')) ?></p>
                                <ul class="landing-check-list landing-check-list--compact">
                                    <?php for ($item = 1; $item <= $feature['items']; $item++): ?>
                                        <li><span aria-hidden="true">✓</span><?= landingEscape(t('feature.' . $feature['key'] . '.' . $item)) ?></li>
                                    <?php endfor; ?>
                                </ul>
                            </div>
                            <div class="landing-feature-visual landing-feature-visual--<?= landingEscape($feature['visual']) ?>" aria-hidden="true">
                                <div class="landing-feature-visual__chrome"><i></i><i></i><i></i></div>
                                <div class="landing-feature-visual__canvas">
                                    <?php if ($feature['visual'] === 'structure'): ?>
                                        <span class="visual-circle visual-circle--large"></span><span class="visual-circle visual-circle--medium"></span><span class="visual-circle visual-circle--small"></span><span class="visual-link"></span>
                                    <?php elseif ($feature['visual'] === 'frame'): ?>
                                        <span class="visual-rule"></span><span class="visual-rule"></span><span class="visual-rule"></span><span class="visual-stamp">✓</span>
                                    <?php elseif ($feature['visual'] === 'projects'): ?>
                                        <span class="visual-column"><i></i><i></i></span><span class="visual-column"><i></i><i></i><i></i></span><span class="visual-column"><i></i></span>
                                    <?php elseif ($feature['visual'] === 'dashboard'): ?>
                                        <span class="visual-chart"><i></i><i></i><i></i><i></i><i></i></span><span class="visual-metric">72</span>
                                    <?php elseif ($feature['visual'] === 'quality'): ?>
                                        <span class="visual-check">✓</span><span class="visual-check">✓</span><span class="visual-check"></span><span class="visual-check">✓</span>
                                    <?php else: ?>
                                        <span class="visual-agenda"></span><span class="visual-agenda"></span><span class="visual-agenda"></span><span class="visual-result">→</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="landing-section">
            <div class="landing-container landing-modular">
                <div class="landing-modular__copy">
                    <p class="landing-eyebrow"><?= landingEscape(t('modular.eyebrow')) ?></p>
                    <h2><?= landingEscape(t('modular.title')) ?></h2>
                    <p><?= landingEscape(t('modular.text')) ?></p>
                    <a class="generic-action-button generic-action-button--secondary landing-button" href="<?= landingUrl($demoUrl) ?>"><?= landingEscape(t('action.demo')) ?></a>
                </div>
                <div class="landing-module-grid">
                    <?php foreach ($moduleKeys as $index => $moduleKey): ?>
                        <div class="landing-module-card<?= $index < 6 ? ' is-active' : '' ?>">
                            <span class="landing-module-card__switch" aria-hidden="true"><i></i></span>
                            <strong><?= landingEscape(t('module.' . $moduleKey)) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section--dark">
            <div class="landing-container landing-pedagogy">
                <div class="landing-pedagogy__copy">
                    <p class="landing-eyebrow"><?= landingEscape(t('pedagogy.eyebrow')) ?></p>
                    <h2><?= landingEscape(t('pedagogy.title')) ?></h2>
                    <p><?= landingEscape(t('pedagogy.text')) ?></p>
                </div>
                <div class="landing-pedagogy__cards">
                    <?php foreach (['context', 'custom', 'transmission'] as $item): ?>
                        <article>
                            <span aria-hidden="true"><?= $item === 'context' ? '?' : ($item === 'custom' ? 'Aa' : '↗') ?></span>
                            <h3><?= landingEscape(t('pedagogy.card.' . $item . '.title')) ?></h3>
                            <p><?= landingEscape(t('pedagogy.card.' . $item . '.text')) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="landing-section" id="cas-usages">
            <div class="landing-container">
                <div class="landing-section-heading">
                    <p class="landing-eyebrow"><?= landingEscape(t('usecases.eyebrow')) ?></p>
                    <h2><?= landingEscape(t('usecases.title')) ?></h2>
                    <p><?= landingEscape(t('usecases.intro')) ?></p>
                </div>
                <div class="landing-use-cases">
                    <?php foreach ($useCases as $useCase): ?>
                        <article class="generic-soft-panel landing-use-case">
                            <span aria-hidden="true"><?= str_pad((string)$useCase, 2, '0', STR_PAD_LEFT) ?></span>
                            <div>
                                <h3><?= landingEscape(t('usecase.' . $useCase . '.title')) ?></h3>
                                <p><?= landingEscape(t('usecase.' . $useCase . '.text')) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section--open" id="open-source">
            <div class="landing-container">
                <div class="landing-open-source">
                    <div class="landing-open-source__copy">
                        <p class="landing-eyebrow"><?= landingEscape(t('opensource.eyebrow')) ?></p>
                        <h2><?= landingEscape(t('opensource.title')) ?></h2>
                        <p><?= landingEscape(t('opensource.text')) ?></p>
                        <a class="generic-action-button generic-action-button--main landing-button landing-button--light" href="<?= landingUrl($sourceCodeUrl) ?>" target="_blank" rel="noopener noreferrer"><?= landingEscape(t('action.source')) ?><span aria-hidden="true">↗</span></a>
                    </div>
                    <div class="landing-open-source__options">
                        <?php foreach (['use', 'contribute', 'host'] as $option): ?>
                            <article>
                                <span aria-hidden="true"><?= $option === 'use' ? '○' : ($option === 'contribute' ? '↗' : '⌂') ?></span>
                                <h3><?= landingEscape(t('opensource.option.' . $option . '.title')) ?></h3>
                                <p><?= landingEscape(t('opensource.option.' . $option . '.text')) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section" id="communaute">
            <div class="landing-container landing-community">
                <div class="landing-community__copy">
                    <p class="landing-eyebrow"><?= landingEscape(t('community.eyebrow')) ?></p>
                    <h2><?= landingEscape(t('community.title')) ?></h2>
                    <p><?= landingEscape(t('community.text')) ?></p>
                    <div class="landing-actions">
                        <a class="generic-action-button generic-action-button--main landing-button" href="<?= landingUrl($communityUrl) ?>"><?= landingEscape(t('action.community')) ?></a>
                        <a class="generic-action-button generic-action-button--secondary landing-button" href="<?= landingUrl($supportUrl) ?>"><?= landingEscape(t('action.support')) ?></a>
                    </div>
                </div>
                <div class="landing-community-visual" aria-hidden="true">
                    <span></span><span></span><span></span><span></span><span></span><span></span>
                    <i></i><i></i><i></i>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section--mist">
            <div class="landing-container">
                <div class="landing-section-heading landing-section-heading--center">
                    <p class="landing-eyebrow"><?= landingEscape(t('partners.eyebrow')) ?></p>
                    <h2><?= landingEscape(t('partners.title')) ?></h2>
                    <p><?= landingEscape(t('partners.text')) ?></p>
                </div>
                <div class="landing-partners">
                    <?php foreach (['presence', 'instant', 'wereinvent'] as $partner): ?>
                        <article class="generic-section landing-partner">
                            <div class="landing-partner__placeholder" aria-hidden="true">
                                <span><?= landingEscape(t('partners.logo.placeholder')) ?></span>
                            </div>
                            <h3><?= landingEscape(t('partner.' . $partner)) ?></h3>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Testimonials intentionally omitted until verified quotes and permissions are available. -->

        <section class="landing-section">
            <div class="landing-container">
                <div class="landing-section-heading landing-section-heading--center">
                    <p class="landing-eyebrow"><?= landingEscape(t('journey.eyebrow')) ?></p>
                    <h2><?= landingEscape(t('journey.title')) ?></h2>
                </div>
                <ol class="landing-journey">
                    <?php foreach ($journeySteps as $step): ?>
                        <li>
                            <span><?= str_pad((string)$step, 2, '0', STR_PAD_LEFT) ?></span>
                            <h3><?= landingEscape(t('journey.' . $step . '.title')) ?></h3>
                            <p><?= landingEscape(t('journey.' . $step . '.text')) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>

        <section class="landing-section landing-section--demo" id="demo">
            <div class="landing-container landing-demo">
                <div class="landing-demo__copy">
                    <p class="landing-eyebrow"><?= landingEscape(t('demo.eyebrow')) ?></p>
                    <h2><?= landingEscape(t('demo.title')) ?></h2>
                    <p><?= landingEscape(t('demo.text')) ?></p>
                    <ul class="landing-demo__benefits">
                        <?php foreach (['interface', 'content', 'features', 'example'] as $benefit): ?>
                            <li><span aria-hidden="true">✓</span><?= landingEscape(t('demo.benefit.' . $benefit)) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="generic-action-button generic-action-button--main landing-button landing-button--primary" href="<?= landingUrl($demoUrl) ?>"><?= landingEscape(t('action.demo_access')) ?><span aria-hidden="true">→</span></a>
                </div>
                <aside class="landing-demo__warning" aria-labelledby="demo-warning-title">
                    <span aria-hidden="true">!</span>
                    <div>
                        <h3 id="demo-warning-title"><?= landingEscape(t('demo.warning.title')) ?></h3>
                        <p><?= landingEscape(t('demo.warning.text')) ?></p>
                    </div>
                </aside>
            </div>
        </section>

        <section class="landing-section" id="faq">
            <div class="landing-container landing-faq">
                <div class="landing-section-heading">
                    <p class="landing-eyebrow"><?= landingEscape(t('faq.eyebrow')) ?></p>
                    <h2><?= landingEscape(t('faq.title')) ?></h2>
                </div>
                <div class="landing-faq__list">
                    <?php foreach ($faqItems as $faq): ?>
                        <details class="generic-section generic-accordion--card landing-faq-item">
                            <summary>
                                <span><?= landingEscape(t('faq.' . $faq . '.q')) ?></span>
                                <span class="landing-faq-item__icon" aria-hidden="true"></span>
                            </summary>
                            <div class="generic-accordion__content">
                                <p><?= landingEscape(t('faq.' . $faq . '.a')) ?></p>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="landing-section landing-final">
            <div class="landing-container">
                <div class="landing-final__panel">
                    <div>
                        <h2><?= landingEscape(t('final.title')) ?></h2>
                        <p><?= landingEscape(t('final.text')) ?></p>
                    </div>
                    <div class="landing-actions">
                        <a class="generic-action-button generic-action-button--main landing-button landing-button--light" href="<?= landingUrl($demoUrl) ?>"><?= landingEscape(t('action.demo')) ?></a>
                        <a class="generic-action-button generic-action-button--secondary landing-button landing-button--outline-light" href="<?= landingUrl($sourceCodeUrl) ?>" target="_blank" rel="noopener noreferrer"><?= landingEscape(t('action.source')) ?></a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="landing-container landing-footer__grid">
            <div class="landing-footer__brand">
                <a class="landing-brand landing-brand--footer" href="#accueil">
                    <span class="landing-brand__mark" aria-hidden="true"><span></span><span></span><span></span></span>
                    <span class="landing-brand__copy">
                        <strong><?= landingEscape(t('brand.name')) ?></strong>
                        <span><?= landingEscape(t('brand.version')) ?></span>
                    </span>
                </a>
                <p><?= landingEscape(t('footer.tagline')) ?></p>
            </div>
            <nav aria-label="<?= landingEscape(t('footer.product')) ?>">
                <h2><?= landingEscape(t('footer.product')) ?></h2>
                <a href="#fonctionnalites"><?= landingEscape(t('nav.features')) ?></a>
                <a href="#cas-usages"><?= landingEscape(t('usecases.eyebrow')) ?></a>
                <a href="<?= landingUrl($demoUrl) ?>"><?= landingEscape(t('action.demo')) ?></a>
                <a href="<?= landingUrl($documentationUrl) ?>"><?= landingEscape(t('nav.documentation')) ?></a>
            </nav>
            <nav aria-label="<?= landingEscape(t('footer.project')) ?>">
                <h2><?= landingEscape(t('footer.project')) ?></h2>
                <a href="<?= landingUrl($sourceCodeUrl) ?>" target="_blank" rel="noopener noreferrer"><?= landingEscape(t('footer.source')) ?></a>
                <a href="<?= landingUrl($communityUrl) ?>"><?= landingEscape(t('nav.community')) ?></a>
                <a href="<?= landingUrl($supportUrl) ?>"><?= landingEscape(t('footer.support')) ?></a>
                <a href="<?= landingUrl($licenseUrl) ?>"><?= landingEscape(t('footer.license')) ?></a>
            </nav>
            <nav aria-label="<?= landingEscape(t('footer.information')) ?>">
                <h2><?= landingEscape(t('footer.information')) ?></h2>
                <a href="<?= landingUrl($legalUrl) ?>"><?= landingEscape(t('footer.legal')) ?></a>
                <a href="<?= landingUrl($privacyUrl) ?>"><?= landingEscape(t('footer.privacy')) ?></a>
                <a href="<?= landingUrl($contactUrl) ?>"><?= landingEscape(t('footer.contact')) ?></a>
                <a href="#faq"><?= landingEscape(t('faq.eyebrow')) ?></a>
            </nav>
        </div>
        <div class="landing-container landing-footer__bottom">
            <p><?= landingEscape(t('footer.copyright', ['year' => date('Y')])) ?></p>
        </div>
    </footer>

    <script src="/assets/js/index2.js" defer></script>
</body>
</html>
