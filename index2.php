<?php
declare(strict_types=1);

/*
 * Public OMO2 homepage. The values in $omo2Links and $nextDiscoveryEvent are
 * intentionally grouped here so the public calls to action can be updated
 * without searching through the page markup.
 */
require_once __DIR__ . '/config.php';

spl_autoload_register(static function (string $class): void {
    $path = __DIR__ . '/class/' . str_replace('\\', '/', strtolower($class)) . '.class.php';
    if (is_file($path)) {
        require_once $path;
    }
});

require_once __DIR__ . '/common/translation_bundles.php';

$sourceLang = [
    'home.meta.title' => ['text' => 'OpenMyOrganization - Mieux s’organiser pour mieux coopérer', 'context' => 'Browser title for the public OMO2 homepage.'],
    'home.meta.description' => ['text' => 'OMO2 est un logiciel open source de gouvernance et de coopération pour structurer votre organisation, partager l’information, décider ensemble et construire votre mémoire collective.', 'context' => 'SEO description for the public OMO2 homepage.'],
    'home.nav.about' => ['text' => 'OMO2', 'context' => 'Main navigation link to the homepage introduction.'],
    'home.nav.capabilities' => ['text' => 'Fonctionnalités', 'context' => 'Main navigation link to the capabilities section.'],
    'home.nav.maturity' => ['text' => 'Maturité organisationnelle', 'context' => 'Main navigation link to the maturity section.'],
    'home.nav.community' => ['text' => 'Communauté', 'context' => 'Main navigation link to the community section.'],
    'home.nav.support' => ['text' => 'Soutenir', 'context' => 'Main navigation support link.'],
    'home.action.login' => ['text' => 'Se connecter', 'context' => 'Header and footer action that opens the OMO workspace.'],
    'home.action.discover' => ['text' => 'Découvrir OMO2', 'context' => 'Primary homepage action that scrolls to the product discovery section.'],
    'home.action.try' => ['text' => 'Essayer OMO2', 'context' => 'Action that opens the OMO workspace.'],
    'home.hero.eyebrow' => ['text' => 'Un logiciel pour soutenir la gouvernance', 'context' => 'Small label shown above the homepage hero heading.'],
    'home.hero.title' => ['text' => 'Mieux s’organiser pour mieux coopérer.', 'context' => 'Homepage main heading.'],
    'home.hero.body' => ['text' => 'Votre collectif a une raison d’être. OMO vous aide à la faire vivre au quotidien : des rôles clairs, des informations partagées et des décisions que l’on peut suivre ensemble.', 'context' => 'Introductory paragraph in the homepage hero.'],
    'home.verb.structure' => ['text' => 'Structurer', 'context' => 'First core OMO2 capability.'],
    'home.verb.share' => ['text' => 'Partager', 'context' => 'Second core OMO2 capability.'],
    'home.verb.decide' => ['text' => 'Décider', 'context' => 'Third core OMO2 capability.'],
    'home.verb.document' => ['text' => 'Documenter', 'context' => 'Fourth core OMO2 capability.'],
    'home.other.title' => ['text' => 'Une autre manière de faire équipe.', 'context' => 'Editorial section heading.'],
    'home.other.body' => ['text' => 'Qui fait quoi ? Où trouver la bonne information ? Comment avancer quand les avis diffèrent ? Ces questions traversent la vie de tous les collectifs. OMO aide à y répondre, pour que chacun trouve sa place et puisse agir.', 'context' => 'Editorial section body.'],
    'home.other.conclusion' => ['text' => 'Un cadre clair, de la confiance et de la place pour chacun.', 'context' => 'Editorial section concluding statement.'],
    'home.idea.one.title' => ['text' => 'Déléguer sans subordonner', 'context' => 'First organizational principle heading.'],
    'home.idea.one.body' => ['text' => 'Rendre les responsabilités lisibles, tout en reconnaissant la même valeur à chaque personne.', 'context' => 'First organizational principle body.'],
    'home.idea.two.title' => ['text' => 'Distribuer sans diluer', 'context' => 'Second organizational principle heading.'],
    'home.idea.two.body' => ['text' => 'Donner à chacun un espace pour agir, avec les informations et les responsabilités qui vont avec.', 'context' => 'Second organizational principle body.'],
    'home.idea.three.title' => ['text' => 'Coopérer sans nier les désaccords', 'context' => 'Third organizational principle heading.'],
    'home.idea.three.body' => ['text' => 'Accueillir les tensions et les points de vue différents pour apprendre et faire évoluer les façons de travailler.', 'context' => 'Third organizational principle body.'],
    'home.capabilities.kicker' => ['text' => 'Quatre façons de mieux coopérer', 'context' => 'Capabilities section eyebrow.'],
    'home.capabilities.title' => ['text' => 'Tout ce qui vous aide à avancer ensemble.', 'context' => 'Capabilities section heading.'],
    'home.organizations.kicker' => ['text' => 'Des collectifs en action', 'context' => 'Organization logo list eyebrow.'],
    'home.cap.structure.title' => ['text' => 'Clarifier sans rigidifier.', 'context' => 'Structure capability subheading.'],
    'home.cap.structure.body' => ['text' => 'Qui porte quoi, et dans quel but ? Rendez visibles les équipes, les rôles et leurs responsabilités. Votre structure peut évoluer avec la vie du collectif.', 'context' => 'Structure capability body.'],
    'home.cap.share.title' => ['text' => 'Le bon contexte pour agir.', 'context' => 'Sharing capability subheading.'],
    'home.cap.share.body' => ['text' => 'Retrouvez les projets, les activités, les documents et les rendez-vous dans leur contexte. Chacun accède à ce dont il a besoin pour contribuer.', 'context' => 'Sharing capability body.'],
    'home.cap.decide.title' => ['text' => 'Faire une place aux bonnes voix.', 'context' => 'Decision capability subheading.'],
    'home.cap.decide.body' => ['text' => 'Partagez une proposition, recueillez les avis, puis choisissez comment décider : consentement, vote ou jugement majoritaire. Le collectif contribue et les responsabilités restent claires.', 'context' => 'Decision capability body.'],
    'home.cap.document.title' => ['text' => 'Garder le fil de votre histoire.', 'context' => 'Documentation capability subheading.'],
    'home.cap.document.body' => ['text' => 'Écrivez à plusieurs, partagez vos fichiers et retrouvez ce qui a été décidé, pourquoi et par qui. Vos documents gardent leur contexte et transmettent l’expérience du collectif aux personnes qui vous rejoignent.', 'context' => 'Documentation capability body.'],
    'home.connected.kicker' => ['text' => 'Tout se relie', 'context' => 'Connected modules section eyebrow.'],
    'home.connected.title' => ['text' => 'Du rôle à l’action, gardez le fil.', 'context' => 'Connected modules section heading.'],
    'home.connected.body' => ['text' => 'Une réunion fait émerger une décision. Cette décision devient un projet, porté par un rôle. Les documents et les échanges gardent la trace du chemin parcouru.', 'context' => 'Connected modules section body.'],
    'home.connected.conclusion' => ['text' => 'Moins de contexte à reconstruire. Plus de temps pour agir ensemble.', 'context' => 'Connected modules section conclusion.'],
    'home.maturity.kicker' => ['text' => 'Ce qui nous guide', 'context' => 'Maturity section eyebrow.'],
    'home.maturity.title' => ['text' => 'Grandir ensemble, à votre manière.', 'context' => 'Maturity section heading.'],
    'home.maturity.body' => ['text' => 'La maturité organisationnelle se cultive au fil de l’expérience : apprendre, ajuster ses règles, partager le pouvoir avec responsabilité. OMO accompagne ce chemin, sans imposer de méthode, en gardant votre raison d’être comme repère.', 'context' => 'Maturity section body.'],
    'home.action.manifesto' => ['text' => 'Evaluez votre maturité organisationnelle', 'context' => 'Call to action for the maturity test.'],
    'home.community.title' => ['text' => 'Un commun que l’on fait grandir ensemble.', 'context' => 'Community section heading.'],
    'home.community.lead' => ['text' => 'OpenMyOrganization est un commun porté par l’association OpenGouvernance.', 'context' => 'Association carrying the OpenMyOrganization commons.'],
    'home.community.body' => ['text' => 'Autour du projet, des personnes qui utilisent le logiciel, accompagnent les collectifs et développent l’outil partagent leurs expériences et construisent la suite ensemble.', 'context' => 'Community section body.'],
    'home.community.conclusion' => ['text' => 'Un retour d’usage, une idée, un coup de main : chaque contribution compte.', 'context' => 'Community section conclusion.'],
    'home.opensource.title' => ['text' => 'Open source, par choix.', 'context' => 'Open source section heading.'],
    'home.opensource.body' => ['text' => 'Le code d’OMO est ouvert : chacun peut comprendre son fonctionnement, l’adapter et contribuer à son évolution. Cette ouverture permet de partager aussi la fabrication de l’outil.', 'context' => 'Open source section body.'],
    'home.opensource.statement' => ['text' => 'Un outil destiné à partager le pouvoir peut aussi partager son code.', 'context' => 'Open source highlighted statement.'],
    'home.action.source' => ['text' => 'Voir le code source', 'context' => 'Call to action for the source repository.'],
    'home.support.title' => ['text' => 'Accessible au plus grand nombre.', 'context' => 'Funding section heading.'],
    'home.support.body' => ['text' => 'Mieux coopérer devrait être à la portée de tous les collectifs. La contribution et le don soutiennent le développement d’OMO et son accessibilité, dans un esprit de solidarité.', 'context' => 'Funding section body.'],
    'home.support.statement' => ['text' => 'Celles et ceux qui peuvent donner davantage aident d’autres collectifs à en bénéficier.', 'context' => 'Funding highlighted statement.'],
    'home.action.support' => ['text' => 'Soutenir OMO', 'context' => 'Call to action for project support.'],
    'home.video.title' => ['text' => 'OMO2 en quelques minutes.', 'context' => 'Presentation video section heading.'],
    'home.video.action' => ['text' => 'Lancer la vidéo de présentation', 'context' => 'Accessible label for the presentation video action.'],
    'home.event.kicker' => ['text' => 'Rencontre mensuelle', 'context' => 'Discovery event section eyebrow.'],
    'home.event.title' => ['text' => 'Faisons connaissance.', 'context' => 'Discovery event section heading.'],
    'home.event.body' => ['text' => 'Chaque mois, retrouvons-nous en ligne pour découvrir OMO2, poser vos questions et parler de vos façons de coopérer. Que vous découvriez le projet ou l’utilisiez déjà, vous êtes les bienvenus.', 'context' => 'Discovery event section body.'],
    'home.event.label' => ['text' => 'Prochaine découverte d’OMO2 en ligne', 'context' => 'Label above the next discovery event details.'],
    'home.event.online' => ['text' => 'En ligne', 'context' => 'Event format label.'],
    'home.action.register' => ['text' => 'S’inscrire au prochain événement', 'context' => 'Call to action to register for the next discovery event.'],
    'home.action.dates' => ['text' => 'Voir les prochaines dates', 'context' => 'Secondary action to view other discovery dates.'],
    'home.story.title' => ['text' => '13 ans de liens, d’essais et d’apprentissages.', 'context' => 'Project history section heading.'],
    'home.story.body' => ['text' => 'OMO a grandi avec les collectifs qui l’utilisent. Leurs questions, leurs essais et leurs retours nourrissent le logiciel depuis treize ans.', 'context' => 'Project history section body.'],
    'home.story.conclusion' => ['text' => 'OMO2 ouvre un nouveau chapitre de cette histoire commune : un espace plus intégré pour relier les personnes, les décisions et l’action.', 'context' => 'Project history section conclusion.'],
    'home.final.title' => ['text' => 'Et si l’on faisait grandir la coopération ensemble ?', 'context' => 'Final homepage call to action heading.'],
    'home.final.body' => ['text' => 'Découvrez OMO2, explorez-le avec votre collectif et partagez ce qui vous aiderait à mieux travailler ensemble.', 'context' => 'Final homepage call to action body.'],
    'home.final.event' => ['text' => 'Participer à la prochaine découverte en ligne', 'context' => 'Final homepage event link.'],
    'home.footer.omo' => ['text' => 'OMO', 'context' => 'First footer column heading.'],
    'home.footer.discover' => ['text' => 'Découvrir', 'context' => 'Second footer column heading.'],
    'home.footer.project' => ['text' => 'Projet', 'context' => 'Third footer column heading.'],
    'home.footer.legal' => ['text' => 'Informations légales', 'context' => 'Fourth footer column heading.'],
    'home.footer.terms' => ['text' => 'Conditions générales', 'context' => 'Footer link to terms and conditions.'],
    'home.footer.privacy' => ['text' => 'Politique de confidentialité', 'context' => 'Footer link to the privacy policy.'],
    'home.menu.open' => ['text' => 'Ouvrir le menu', 'context' => 'Accessible label for mobile navigation menu toggle.'],
    'home.menu.close' => ['text' => 'Fermer le menu', 'context' => 'Accessible label for mobile navigation close action.'],
    'home.skip' => ['text' => 'Aller au contenu', 'context' => 'Public OMO2 homepage: skip.'],
    'home.nav.label' => ['text' => 'Navigation principale', 'context' => 'Public OMO2 homepage: nav label.'],
    'home.other.kicker' => ['text' => 'La coopération au quotidien', 'context' => 'Public OMO2 homepage: other kicker.'],
    'home.hero.visual.caption' => ['text' => 'Des rôles visibles, un collectif qui prend forme.', 'context' => 'Public OMO2 homepage: hero visual caption.'],
    'home.image.structure' => ['text' => 'Illustration de la carte des rôles et des cercles dans OMO2', 'context' => 'Public OMO2 homepage: image structure.'],
    'home.image.calendar' => ['text' => 'Illustration du calendrier partagé dans OMO2', 'context' => 'Public OMO2 homepage: image calendar.'],
    'home.image.projects' => ['text' => 'Illustration du tableau de suivi des projets dans OMO2', 'context' => 'Public OMO2 homepage: image projects.'],
    'home.image.decision' => ['text' => 'Illustration d’une décision collective dans OMO2', 'context' => 'Public OMO2 homepage: image decision.'],
    'home.image.documents' => ['text' => 'Illustration de l’espace de fichiers et de l’édition collaborative de documents dans OMO2', 'context' => 'Alternative text for the supplied collaborative document illustration.'],
    'home.image.documents.caption' => ['text' => 'Écrire et construire ensemble.', 'context' => 'Caption for the supplied collaborative document illustration.'],
    'home.image.story' => ['text' => 'Iceberg aux facettes bleues, symbole des dimensions visibles et invisibles du collectif', 'context' => 'Public OMO2 homepage: image story.'],
    'home.image.community' => ['text' => 'Des manchots qui construisent ensemble, illustration de la communauté OMO', 'context' => 'Public OMO2 homepage: image community.'],
    'home.image.kind' => ['text' => 'Vue illustrée', 'context' => 'Public OMO2 homepage: image kind.'],
    'home.image.structure.caption' => ['text' => 'Rôles, cercles et contributions visibles.', 'context' => 'Public OMO2 homepage: image structure caption.'],
    'home.image.calendar.caption' => ['text' => 'Agenda collectif partagé.', 'context' => 'Public OMO2 homepage: image calendar caption.'],
    'home.image.projects.caption' => ['text' => 'Les projets avancent au même endroit.', 'context' => 'Public OMO2 homepage: image projects caption.'],
    'home.image.decision.caption' => ['text' => 'Recueillir les avis et avancer vers un choix commun.', 'context' => 'Public OMO2 homepage: image decision caption.'],
    'home.carousel.share' => ['text' => 'Illustrations du partage dans OMO2', 'context' => 'Accessible label for the sharing product image carousel.'],
    'home.carousel.previous' => ['text' => 'Afficher l’illustration précédente', 'context' => 'Accessible label for the sharing image carousel previous button.'],
    'home.carousel.next' => ['text' => 'Afficher l’illustration suivante', 'context' => 'Accessible label for the sharing image carousel next button.'],
    'home.carousel.calendar' => ['text' => 'Afficher le calendrier partagé', 'context' => 'Accessible label for the calendar slide selector.'],
    'home.carousel.projects' => ['text' => 'Afficher le tableau de suivi des projets', 'context' => 'Accessible label for the project board slide selector.'],
    'home.flow.structure' => ['text' => 'Structure', 'context' => 'Public OMO2 homepage: flow structure.'],
    'home.flow.information' => ['text' => 'Information', 'context' => 'Public OMO2 homepage: flow information.'],
    'home.flow.decision' => ['text' => 'Décision', 'context' => 'Public OMO2 homepage: flow decision.'],
    'home.flow.action' => ['text' => 'Action', 'context' => 'Public OMO2 homepage: flow action.'],
    'home.flow.memory' => ['text' => 'Mémoire', 'context' => 'Public OMO2 homepage: flow memory.'],
    'home.flow.label' => ['text' => 'De la structure à la mémoire collective', 'context' => 'Public OMO2 homepage: flow label.'],
    'home.flow.structure.question' => ['text' => 'Qui porte quoi ?', 'context' => 'Structure step in the connected modules diagram.'],
    'home.flow.structure.detail' => ['text' => 'Les rôles donnent un cadre et une responsabilité à chacun.', 'context' => 'Explanation of the structure step.'],
    'home.flow.information.question' => ['text' => 'Que faut-il savoir ?', 'context' => 'Information step in the connected modules diagram.'],
    'home.flow.information.detail' => ['text' => 'Les échanges et les documents éclairent le sujet.', 'context' => 'Explanation of the information step.'],
    'home.flow.decision.question' => ['text' => 'Que choisissons-nous ?', 'context' => 'Decision step in the connected modules diagram.'],
    'home.flow.decision.detail' => ['text' => 'Les personnes concernées contribuent au choix.', 'context' => 'Explanation of the decision step.'],
    'home.flow.action.question' => ['text' => 'Comment avancer ?', 'context' => 'Action step in the connected modules diagram.'],
    'home.flow.action.detail' => ['text' => 'La décision devient un projet, avec un responsable.', 'context' => 'Explanation of the action step.'],
    'home.flow.memory.question' => ['text' => 'Qu’apprenons-nous ?', 'context' => 'Memory step in the connected modules diagram.'],
    'home.flow.memory.detail' => ['text' => 'Le contexte et les résultats restent accessibles à tous ceux qui en ont besoin.', 'context' => 'Explanation of the memory step.'],
    'home.flow.return.title' => ['text' => 'Et l’expérience nourrit la suite.', 'context' => 'Feedback loop caption in the connected modules diagram.'],
    'home.flow.return.body' => ['text' => 'Ce que le collectif apprend aide à ajuster les rôles, les pratiques et les prochaines décisions.', 'context' => 'Explanation of the organizational learning feedback loop.'],
    'home.values' => ['text' => 'Confiance · Coopération · Souveraineté · Responsabilité · Pragmatisme · Respect', 'context' => 'Public OMO2 homepage: values.'],
    'home.polarity.1' => ['text' => 'L’autorité assumée plutôt que l’autorité statutaire', 'context' => 'Public OMO2 homepage: polarity 1.'],
    'home.polarity.2' => ['text' => 'L’intelligence organisée du collectif plutôt que la décision isolée', 'context' => 'Public OMO2 homepage: polarity 2.'],
    'home.polarity.3' => ['text' => 'S’orienter par la raison d’être plutôt que par les intérêts particuliers', 'context' => 'Public OMO2 homepage: polarity 3.'],
    'home.polarity.4' => ['text' => 'Le pouvoir qui circule plutôt que le pouvoir qui se concentre', 'context' => 'Public OMO2 homepage: polarity 4.'],
    'home.polarity.5' => ['text' => 'La confiance et la transparence plutôt que le contrôle', 'context' => 'Public OMO2 homepage: polarity 5.'],
    'home.polarity.6' => ['text' => 'Des structures qui apprennent plutôt qu’un modèle figé', 'context' => 'Public OMO2 homepage: polarity 5.'],
    'home.polarity.7' => ['text' => 'Différencier les rôles plutôt que hiérarchiser les personnes', 'context' => 'Public OMO2 homepage: polarity 5.'],
    'home.polarity.8' => ['text' => 'Les tensions comme signal plutôt que comme menace', 'context' => 'Public OMO2 homepage: polarity 5.'],
    'home.polarity.9' => ['text' => 'La présence consciente plutôt que la réaction automatique', 'context' => 'Public OMO2 homepage: polarity 5.'],
    'home.polarity.10' => ['text' => 'Prendre soin de l’écosystème plutôt que rechercher l’efficacité isolée', 'context' => 'Public OMO2 homepage: polarity 5.'],
    'home.community.kicker' => ['text' => 'Porté par OpenGouvernance', 'context' => 'Public OMO2 homepage: community kicker.'],
    'home.community.practitioners' => ['text' => 'Avec notamment Instant Z, Présence Active, We Reinvent et d’autres praticiens et contributeurs.', 'context' => 'Public OMO2 homepage: community practitioners.'],
    'home.opensource.kicker' => ['text' => 'Un outil ouvert', 'context' => 'Public OMO2 homepage: opensource kicker.'],
    'home.support.kicker' => ['text' => 'La solidarité en pratique', 'context' => 'Public OMO2 homepage: support kicker.'],
    'home.support.label' => ['text' => 'Open source et financement', 'context' => 'Public OMO2 homepage: support label.'],
    'home.video.privacy' => ['text' => 'Le lecteur Vimeo se charge seulement lorsque vous lancez la vidéo.', 'context' => 'Public OMO2 homepage: video privacy.'],
    'home.discover.label' => ['text' => 'Vidéo et rencontre de découverte', 'context' => 'Public OMO2 homepage: discover label.'],
    'home.story.kicker' => ['text' => 'Une histoire collective', 'context' => 'Public OMO2 homepage: story kicker.'],
    'home.footer.tagline' => ['text' => 'Un commun porté par l’association OpenGouvernance, créé par et avec sa communauté.', 'context' => 'Public OMO2 homepage: footer tagline.'],
    'home.footer.signature' => ['text' => 'Open source · Créé ensemble', 'context' => 'Public OMO2 homepage: footer signature.'],
    'home.event.pending' => ['text' => 'La prochaine date sera annoncée ici.', 'context' => 'Public OMO2 homepage: event pending.'],
    'home.event.date_pending' => ['text' => 'Date à venir', 'context' => 'Public OMO2 homepage: event date_pending.'],
    'home.event.time_pending' => ['text' => 'Horaire à confirmer', 'context' => 'Public OMO2 homepage: event time_pending.'],
];

$lang = loadTranslationBundle('home_omo2', 'fr', $sourceLang);
$t = static function (string $key) use ($lang, $sourceLang): string {
    return t($key, [], $lang, $sourceLang);
};
$e = static function (string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
};

$omo2Organizations = class_exists('\\dbObject\\Organization')
    ? \dbObject\Organization::fetchPublicLogoRowsByLastConnection()
    : array();

$omo2Links = [
    'login' => '/omo/',
    'try' => '/omo/',
    'manifesto' => '/survey/',
    'source' => 'https://github.com/OpenGovernance-community/OMO2',
    'support' => 'https://www.patreon.com/cw/OpenGovernance',
    'eventRegistration' => '',
    'eventDates' => '',
    'terms' => '/common/conditions-generales.php',
    'privacy' => '/common/politique-confidentialite.php',
];

// Update these four values when the next open discovery session is scheduled.
$nextDiscoveryEvent = [
    'date' => $t('home.event.date_pending'),
    'time' => $t('home.event.time_pending'),
    'duration' => '',
    'format' => $t('home.event.online'),
];

// The external player is created only after a visitor explicitly activates it.
$presentationVideoUrl = 'https://player.vimeo.com/video/1200446731?dnt=1';
$canonicalUrl = '/index2.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= $e($t('home.meta.title')) ?></title>
    <meta name="description" content="<?= $e($t('home.meta.description')) ?>">
    <link rel="canonical" href="<?= $e($canonicalUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= $e($t('home.meta.title')) ?>">
    <meta property="og:description" content="<?= $e($t('home.meta.description')) ?>">
    <meta property="og:image" content="/img/omo2-social-card.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#05263c">
    <link rel="icon" type="image/jpeg" href="/img/omo-iceberg.jpg?v=20260914" sizes="1254x1254">
    <link rel="stylesheet" href="/assets/omo2-home.css?v=20260914-organizations-15">
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"SoftwareApplication","name":"OpenMyOrganization","applicationCategory":"BusinessApplication","operatingSystem":"Web","description":"<?= $e($t('home.meta.description')) ?>","url":"<?= $e($canonicalUrl) ?>"}
    </script>
</head>
<body>
    <a class="omo2-skip-link" href="#contenu"><?= $e($t('home.skip')) ?></a>
    <header class="omo2-header" data-omo2-header>
        <div class="omo2-shell omo2-header__inner">
            <a class="omo2-brand" href="#accueil" aria-label="OpenMyOrganization">
                <img src="/img/omo2/logo-omo.png" width="1081" height="441" alt="OpenMyOrganization">
            </a>
            <button class="omo2-menu-button" type="button" aria-expanded="false" aria-controls="omo2-navigation" data-omo2-menu-button data-label-open="<?= $e($t('home.menu.open')) ?>" data-label-close="<?= $e($t('home.menu.close')) ?>">
                <span class="omo2-menu-button__bars" aria-hidden="true"><i></i><i></i></span>
                <span class="omo2-sr-only" data-omo2-menu-label><?= $e($t('home.menu.open')) ?></span>
            </button>
            <nav class="omo2-navigation" id="omo2-navigation" aria-label="<?= $e($t('home.nav.label')) ?>" data-omo2-navigation>
                <a href="#accueil"><?= $e($t('home.nav.about')) ?></a>
                <a href="#fonctionnalites"><?= $e($t('home.nav.capabilities')) ?></a>
                <a href="#maturite"><?= $e($t('home.nav.maturity')) ?></a>
                <a href="#communaute"><?= $e($t('home.nav.community')) ?></a>
                <a class="omo2-navigation__support" href="<?= $e($omo2Links['support']) ?>" target="_blank" rel="noopener noreferrer"><?= $e($t('home.nav.support')) ?></a>
                <a class="omo2-button omo2-button--small" href="<?= $e($omo2Links['login']) ?>"><?= $e($t('home.action.login')) ?></a>
            </nav>
        </div>
    </header>

    <main id="contenu">
        <section class="omo2-hero" id="accueil">
            <div class="omo2-hero__orb omo2-hero__orb--one" aria-hidden="true"></div>
            <div class="omo2-hero__orb omo2-hero__orb--two" aria-hidden="true"></div>
            <div class="omo2-shell omo2-hero__grid">
                <div class="omo2-hero__copy">
                    <p class="omo2-eyebrow omo2-eyebrow--light"><?= $e($t('home.hero.eyebrow')) ?></p>
                    <h1><?= $e($t('home.hero.title')) ?></h1>
                    <p class="omo2-hero__lead"><?= $e($t('home.hero.body')) ?></p>
                    <div class="omo2-verb-line" aria-label="<?= $e($t('home.verb.structure')) ?>, <?= $e($t('home.verb.share')) ?>, <?= $e($t('home.verb.decide')) ?>, <?= $e($t('home.verb.document')) ?>">
                        <span><?= $e($t('home.verb.structure')) ?></span><i></i><span><?= $e($t('home.verb.share')) ?></span><i></i><span><?= $e($t('home.verb.decide')) ?></span><i></i><span><?= $e($t('home.verb.document')) ?></span>
                    </div>
                    <div class="omo2-actions">
                        <a class="omo2-button omo2-button--light" href="#fonctionnalites"><?= $e($t('home.action.discover')) ?><span aria-hidden="true">↘</span></a>
                        <a class="omo2-button omo2-button--ghost-light" href="<?= $e($omo2Links['try']) ?>"><?= $e($t('home.action.try')) ?></a>
                    </div>
                 </div>
                <figure class="omo2-hero__visual">
                    <img class="omo2-hero__logo" src="/img/omo2/logo-omo.png" width="1081" height="441" alt="OpenMyOrganization" fetchpriority="high">
                    <div class="omo2-hero__window">
                        <img src="/img/omo2/structure.png" width="1672" height="941" alt="<?= $e($t('home.image.structure')) ?>" fetchpriority="high">
                    </div>
                    <figcaption><?= $e($t('home.hero.visual.caption')) ?></figcaption>
                </figure>
            </div>
        </section>

        <section class="omo2-section omo2-section--editorial" aria-labelledby="another-way-title">
            <div class="omo2-shell omo2-editorial">
                <div class="omo2-editorial__intro">
                    <p class="omo2-eyebrow"><?= $e($t('home.other.kicker')) ?></p>
                    <h2 id="another-way-title"><?= $e($t('home.other.title')) ?></h2>
                    <p><?= $e($t('home.other.body')) ?></p>
                    <p class="omo2-statement"><?= $e($t('home.other.conclusion')) ?></p>
                </div>
                <div class="omo2-principles">
                    <article><span class="omo2-number">01</span><h3><?= $e($t('home.idea.one.title')) ?></h3><p><?= $e($t('home.idea.one.body')) ?></p></article>
                    <article><span class="omo2-number">02</span><h3><?= $e($t('home.idea.two.title')) ?></h3><p><?= $e($t('home.idea.two.body')) ?></p></article>
                    <article><span class="omo2-number">03</span><h3><?= $e($t('home.idea.three.title')) ?></h3><p><?= $e($t('home.idea.three.body')) ?></p></article>
                </div>
            </div>
        </section>

        <?php if ($omo2Organizations !== array()): ?>
        <section class="omo2-section omo2-section--organizations" aria-labelledby="organizations-title">
            <div class="omo2-shell omo2-organizations__heading">
                <p class="omo2-eyebrow" id="organizations-title"><?= $e($t('home.organizations.kicker')) ?></p>
            </div>
            <div class="omo2-org-marquee" data-omo2-org-marquee>
                <div class="omo2-org-marquee__track">
                    <div class="omo2-org-marquee__group">
                        <?php foreach ($omo2Organizations as $omo2Organization): ?>
                        <figure class="omo2-org-logo">
                            <img src="<?= $e($omo2Organization['logo']) ?>" alt="<?= $e($omo2Organization['name']) ?>" loading="lazy" decoding="async">
                            <figcaption><?= $e($omo2Organization['name']) ?></figcaption>
                        </figure>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section class="omo2-section omo2-section--capabilities" id="fonctionnalites" aria-labelledby="capabilities-title">
            <div class="omo2-shell">
                <div class="omo2-section-heading omo2-section-heading--center">
                    <p class="omo2-eyebrow"><?= $e($t('home.capabilities.kicker')) ?></p>
                    <h2 id="capabilities-title"><?= $e($t('home.capabilities.title')) ?></h2>
                </div>
                <div class="omo2-capabilities">
                    <article class="omo2-capability omo2-capability--structure">
                        <div class="omo2-capability__copy"><p class="omo2-capability__verb"><?= $e($t('home.verb.structure')) ?></p><h3><?= $e($t('home.cap.structure.title')) ?></h3><p><?= $e($t('home.cap.structure.body')) ?></p></div>
                        <figure class="omo2-product-view"><img src="/img/omo2/structure.png" width="1672" height="941" loading="lazy" decoding="async" alt="<?= $e($t('home.image.structure')) ?>"><figcaption><span><?= $e($t('home.image.kind')) ?></span><?= $e($t('home.image.structure.caption')) ?></figcaption></figure>
                    </article>
                    <article class="omo2-capability omo2-capability--share">
                        <div class="omo2-capability__copy"><p class="omo2-capability__verb"><?= $e($t('home.verb.share')) ?></p><h3><?= $e($t('home.cap.share.title')) ?></h3><p><?= $e($t('home.cap.share.body')) ?></p></div>
                        <div class="omo2-product-carousel" data-omo2-carousel aria-roledescription="carousel" aria-label="<?= $e($t('home.carousel.share')) ?>">
                            <div class="omo2-product-carousel__viewport">
                                <div class="omo2-product-carousel__track">
                                    <figure class="omo2-product-view" data-carousel-slide><img src="/img/omo2/calendar.png" width="1672" height="941" loading="lazy" decoding="async" alt="<?= $e($t('home.image.calendar')) ?>"><figcaption><span><?= $e($t('home.image.kind')) ?></span><?= $e($t('home.image.calendar.caption')) ?></figcaption></figure>
                                    <figure class="omo2-product-view" data-carousel-slide><img src="/img/omo2/decisions.png" width="1672" height="941" loading="lazy" decoding="async" alt="<?= $e($t('home.image.projects')) ?>"><figcaption><span><?= $e($t('home.image.kind')) ?></span><?= $e($t('home.image.projects.caption')) ?></figcaption></figure>
                                </div>
                            </div>
                            <div class="omo2-product-carousel__controls">
                                <button style='display:none' class="omo2-carousel-button" type="button" data-carousel-prev aria-label="<?= $e($t('home.carousel.previous')) ?>">&#8592;</button>
                                <div class="omo2-carousel-dots" role="group" aria-label="<?= $e($t('home.carousel.share')) ?>">
                                    <button class="is-active" type="button" data-carousel-dot aria-label="<?= $e($t('home.carousel.calendar')) ?>" aria-current="true"></button>
                                    <button type="button" data-carousel-dot aria-label="<?= $e($t('home.carousel.projects')) ?>"></button>
                                </div>
                                <button style='display:none' class="omo2-carousel-button" type="button" data-carousel-next aria-label="<?= $e($t('home.carousel.next')) ?>">&#8594;</button>
                            </div>
                        </div>
                    </article>
                    <article class="omo2-capability omo2-capability--decide">
                        <div class="omo2-capability__copy"><p class="omo2-capability__verb"><?= $e($t('home.verb.decide')) ?></p><h3><?= $e($t('home.cap.decide.title')) ?></h3><p><?= $e($t('home.cap.decide.body')) ?></p></div>
                        <figure class="omo2-product-view"><img src="/img/omo2/decision.png" width="1672" height="941" loading="lazy" decoding="async" alt="<?= $e($t('home.image.decision')) ?>"><figcaption><span><?= $e($t('home.image.kind')) ?></span><?= $e($t('home.image.decision.caption')) ?></figcaption></figure>
                    </article>
                    <article class="omo2-capability omo2-capability--document">
                        <div class="omo2-capability__copy"><p class="omo2-capability__verb"><?= $e($t('home.verb.document')) ?></p><h3><?= $e($t('home.cap.document.title')) ?></h3><p><?= $e($t('home.cap.document.body')) ?></p></div>
                        <figure class="omo2-product-view"><img src="/img/omo2/documents.png" width="1672" height="941" loading="lazy" decoding="async" alt="<?= $e($t('home.image.documents')) ?>"><figcaption><span><?= $e($t('home.image.kind')) ?></span><?= $e($t('home.image.documents.caption')) ?></figcaption></figure>
                    </article>
                </div>
            </div>
        </section>

        <section class="omo2-section omo2-section--connected" id="du-role-a-action" aria-labelledby="connected-title">
            <div class="omo2-shell omo2-connected">
                <div class="omo2-connected__intro">
                    <div><p class="omo2-eyebrow omo2-eyebrow--light"><?= $e($t('home.connected.kicker')) ?></p><h2 id="connected-title"><?= $e($t('home.connected.title')) ?></h2></div>
                    <div class="omo2-connected__copy"><p><?= $e($t('home.connected.body')) ?></p><p class="omo2-connected__conclusion"><?= $e($t('home.connected.conclusion')) ?></p></div>
                </div>
                <figure class="omo2-flow-figure" aria-label="<?= $e($t('home.flow.label')) ?>">
                    <ol class="omo2-flow">
                        <?php
                        // The adjacent text describes each decorative illustration.
                        $flowSteps = ['structure', 'information', 'decision', 'action', 'memory'];
                        $flowStep = 0;
                        foreach ($flowSteps as $flowKey):
                            $flowStep++;
                        ?>
                        <li class="omo2-flow__step omo2-flow__step--<?= $e($flowKey) ?>">
                            <div class="omo2-flow__node" aria-hidden="true">
                                <span class="omo2-flow__number"><?= sprintf('%02d', $flowStep) ?></span>
                                <img src="/img/omo2/flow-<?= $e($flowKey) ?>.png" width="1254" height="1254" loading="lazy" decoding="async" alt="">
                            </div>
                            <div class="omo2-flow__text">
                                <h3><?= $e($t('home.flow.' . $flowKey)) ?></h3>
                                <p class="omo2-flow__question"><?= $e($t('home.flow.' . $flowKey . '.question')) ?></p>
                                <p class="omo2-flow__detail"><?= $e($t('home.flow.' . $flowKey . '.detail')) ?></p>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                    <figcaption class="omo2-flow__return">
                        <svg viewBox="0 0 32 32" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M8 10h12a8 8 0 0 1 0 16H12M8 10l5-5M8 10l5 5"/></svg>
                        <div><strong><?= $e($t('home.flow.return.title')) ?></strong><p><?= $e($t('home.flow.return.body')) ?></p></div>
                    </figcaption>
                </figure>
            </div>
        </section>

        <section class="omo2-section omo2-section--maturity" id="maturite" aria-labelledby="maturity-title">
            <div class="omo2-shell omo2-maturity">
                <div class="omo2-maturity__shape" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span></div>
                <div class="omo2-maturity__copy"><p class="omo2-eyebrow"><?= $e($t('home.maturity.kicker')) ?></p><h2 id="maturity-title"><?= $e($t('home.maturity.title')) ?></h2><p><?= $e($t('home.maturity.body')) ?></p><a class="omo2-text-link" href="<?= $e($omo2Links['manifesto']) ?>"><?= $e($t('home.action.manifesto')) ?><span aria-hidden="true">↘</span></a></div>
                <div class="omo2-polarities"><p><?= $e($t('home.values')) ?></p><ul><li><?= $e($t('home.polarity.1')) ?></li><li><?= $e($t('home.polarity.2')) ?></li><li><?= $e($t('home.polarity.3')) ?></li><li><?= $e($t('home.polarity.4')) ?></li><li><?= $e($t('home.polarity.5')) ?></li><li><?= $e($t('home.polarity.6')) ?></li><li><?= $e($t('home.polarity.7')) ?></li><li><?= $e($t('home.polarity.8')) ?></li><li><?= $e($t('home.polarity.9')) ?></li><li><?= $e($t('home.polarity.10')) ?></li></ul></div>
            </div>
        </section>

        <section class="omo2-section omo2-section--community" id="communaute" aria-labelledby="community-title">
            <div class="omo2-shell omo2-community">
                <figure class="omo2-community__visual"><img src="/img/omo2/community.png" width="1672" height="941" loading="lazy" decoding="async" alt="<?= $e($t('home.image.community')) ?>"></figure>
                <div><p class="omo2-eyebrow"><?= $e($t('home.community.kicker')) ?></p><h2 id="community-title"><?= $e($t('home.community.title')) ?></h2><p class="omo2-community__lead"><?= $e($t('home.community.lead')) ?></p><p><?= $e($t('home.community.body')) ?></p><p class="omo2-statement"><?= $e($t('home.community.conclusion')) ?></p><p class="omo2-community__names"><?= $e($t('home.community.practitioners')) ?></p></div>
            </div>
        </section>

        <section class="omo2-section omo2-section--split" aria-label="<?= $e($t('home.support.label')) ?>">
            <div class="omo2-shell omo2-split-grid">
                <article class="omo2-open-source"><p class="omo2-eyebrow omo2-eyebrow--light"><?= $e($t('home.opensource.kicker')) ?></p><h2><?= $e($t('home.opensource.title')) ?></h2><p><?= $e($t('home.opensource.body')) ?></p><p class="omo2-open-source__statement"><?= $e($t('home.opensource.statement')) ?></p><a class="omo2-button omo2-button--light" href="<?= $e($omo2Links['source']) ?>" target="_blank" rel="noopener noreferrer"><?= $e($t('home.action.source')) ?><span aria-hidden="true">↗</span></a></article>
                <article class="omo2-support" id="soutenir"><p class="omo2-eyebrow"><?= $e($t('home.support.kicker')) ?></p><h2><?= $e($t('home.support.title')) ?></h2><p><?= $e($t('home.support.body')) ?></p><p class="omo2-statement"><?= $e($t('home.support.statement')) ?></p><a class="omo2-text-link" href="<?= $e($omo2Links['support']) ?>" target="_blank" rel="noopener noreferrer"><?= $e($t('home.action.support')) ?><span aria-hidden="true">↗</span></a></article>
            </div>
        </section>

        <section class="omo2-section omo2-section--discover" id="decouvrir" aria-label="<?= $e($t('home.discover.label')) ?>">
            <div class="omo2-shell omo2-discover-grid">
                <div class="omo2-video"><p class="omo2-eyebrow"><?= $e($t('home.video.title')) ?></p><button class="omo2-video__poster" type="button" data-video-url="<?= $e($presentationVideoUrl) ?>" data-video-title="<?= $e($t('home.video.title')) ?>" aria-label="<?= $e($t('home.video.action')) ?>"><img src="/img/omo2/structure.png" width="1672" height="941" loading="lazy" alt="<?= $e($t('home.image.structure')) ?>"><span class="omo2-video__play" aria-hidden="true">▶</span></button><p class="omo2-video__privacy"><?= $e($t('home.video.privacy')) ?></p></div>
                <aside class="omo2-event" id="evenement"><p class="omo2-eyebrow omo2-eyebrow--light"><?= $e($t('home.event.kicker')) ?></p><h2><?= $e($t('home.event.title')) ?></h2><p><?= $e($t('home.event.body')) ?></p><div class="omo2-event__details"><span><?= $e($t('home.event.label')) ?></span><strong><?= $e($nextDiscoveryEvent['date']) ?></strong><ul><li><?= $e($nextDiscoveryEvent['time']) ?></li><?php if ($nextDiscoveryEvent['duration'] !== ''): ?><li><?= $e($nextDiscoveryEvent['duration']) ?></li><?php endif; ?><li><?= $e($nextDiscoveryEvent['format']) ?></li></ul></div><?php if ($omo2Links['eventRegistration'] !== ''): ?><a class="omo2-button omo2-button--light omo2-button--full" href="<?= $e($omo2Links['eventRegistration']) ?>"><?= $e($t('home.action.register')) ?></a><?php else: ?><p class="omo2-event__pending"><?= $e($t('home.event.pending')) ?></p><?php endif; ?><?php if ($omo2Links['eventDates'] !== ''): ?><a class="omo2-event__secondary" href="<?= $e($omo2Links['eventDates']) ?>"><?= $e($t('home.action.dates')) ?> <span aria-hidden="true">↘</span></a><?php endif; ?></aside>
            </div>
        </section>

        <section class="omo2-section omo2-section--story" aria-labelledby="story-title"><div class="omo2-shell omo2-story"><img src="/img/omo2/bottom_img.png" width="1254" height="1254" loading="lazy" alt="<?= $e($t('home.image.story')) ?>"><div><p class="omo2-eyebrow"><?= $e($t('home.story.kicker')) ?></p><h2 id="story-title"><?= $e($t('home.story.title')) ?></h2><p><?= $e($t('home.story.body')) ?></p><p class="omo2-statement"><?= $e($t('home.story.conclusion')) ?></p></div></div></section>

        <section class="omo2-final" id="cooperer" aria-labelledby="final-title">
            <img class="omo2-final__art" src="/img/omo2/final-iceberg.png" width="1672" height="941" loading="lazy" decoding="async" alt="">
            <div class="omo2-shell omo2-final__content">
                <p class="omo2-eyebrow omo2-eyebrow--light">OpenMyOrganization - OMO2</p>
                <h2 id="final-title"><?= $e($t('home.final.title')) ?></h2>
                <p><?= $e($t('home.final.body')) ?></p>
                <div class="omo2-actions omo2-actions--center"><a class="omo2-button omo2-button--light" href="#fonctionnalites"><?= $e($t('home.action.discover')) ?><span aria-hidden="true">↗</span></a><a class="omo2-button omo2-button--ghost-light" href="<?= $e($omo2Links['try']) ?>"><?= $e($t('home.action.try')) ?></a></div>
                <a class="omo2-final__event" href="#evenement"><?= $e($t('home.final.event')) ?> <span aria-hidden="true">↓</span></a>
            </div>
        </section>
    </main>

    <footer class="omo2-footer"><div class="omo2-shell"><div class="omo2-footer__top"><a class="omo2-brand omo2-brand--footer" href="#accueil"><img src="/img/omo2/logo-omo.png" width="1081" height="441" alt="OpenMyOrganization"></a><p><?= $e($t('home.footer.tagline')) ?></p></div><div class="omo2-footer__columns"><div><h2><?= $e($t('home.footer.omo')) ?></h2><a href="#accueil"><?= $e($t('home.nav.about')) ?></a><a href="#fonctionnalites"><?= $e($t('home.nav.capabilities')) ?></a><a href="#maturite"><?= $e($t('home.action.manifesto')) ?></a><a href="<?= $e($omo2Links['source']) ?>" target="_blank" rel="noopener noreferrer"><?= $e($t('home.opensource.title')) ?></a><a href="#communaute"><?= $e($t('home.nav.community')) ?></a></div><div><h2><?= $e($t('home.footer.discover')) ?></h2><a href="#evenement"><?= $e($t('home.event.label')) ?></a><a href="#decouvrir"><?= $e($t('home.video.title')) ?></a><a href="<?= $e($omo2Links['try']) ?>"><?= $e($t('home.action.try')) ?></a><a href="<?= $e($omo2Links['login']) ?>"><?= $e($t('home.action.login')) ?></a></div><div><h2><?= $e($t('home.footer.project')) ?></h2><a href="<?= $e($omo2Links['source']) ?>" target="_blank" rel="noopener noreferrer"><?= $e($t('home.action.source')) ?></a><a href="<?= $e($omo2Links['support']) ?>" target="_blank" rel="noopener noreferrer"><?= $e($t('home.action.support')) ?></a></div><div><h2><?= $e($t('home.footer.legal')) ?></h2><a href="<?= $e($omo2Links['terms']) ?>"><?= $e($t('home.footer.terms')) ?></a><a href="<?= $e($omo2Links['privacy']) ?>"><?= $e($t('home.footer.privacy')) ?></a></div></div><div class="omo2-footer__bottom"><span>© <?= date('Y') ?> OpenMyOrganization</span><span><?= $e($t('home.footer.signature')) ?></span></div></div></footer>
    <script src="/assets/omo2-home.js?v=20260914-organizations-9"></script>
</body>
</html>
