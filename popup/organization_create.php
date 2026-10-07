<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/config.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/shared_functions.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/common/auth.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/common/patreon.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/common/leaflet_helper.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/omo/translations.php");

$sourceLang = array(
    'organization.error.login' => array('text' => 'Connexion requise.', 'context' => 'Authentication error in the organization editor.'),
    'organization.error.user' => array('text' => 'Utilisateur inconnu.', 'context' => 'Unknown user error in the organization editor.'),
    'organization.error.unknown' => array('text' => 'Organisation inconnue.', 'context' => 'Unknown organization error in the editor.'),
    'organization.error.forbidden' => array('text' => 'Accès refusé.', 'context' => 'Permission error in the organization editor.'),
    'organization.error.model' => array('text' => 'Modèle public introuvable.', 'context' => 'Unknown public model error in the organization editor.'),
    'organization.title.edit' => array('text' => 'Modifier une organisation', 'context' => 'Organization editor page title.'),
    'organization.title.from_model' => array('text' => 'Créer à partir d’un modèle', 'context' => 'Create organization from model page title.'),
    'organization.title.create' => array('text' => 'Créer une organisation', 'context' => 'Create organization page title.'),
    'organization.action.save' => array('text' => 'Enregistrer les modifications', 'context' => 'Save existing organization button.'),
    'organization.action.create_from_model' => array('text' => 'Créer depuis ce modèle', 'context' => 'Create from model button.'),
    'organization.action.create' => array('text' => 'Créer l’organisation', 'context' => 'Create organization button.'),
    'organization.action.cancel' => array('text' => 'Annuler', 'context' => 'Cancel organization editor button.'),
    'organization.state.saving' => array('text' => 'Enregistrement en cours…', 'context' => 'Organization save pending state.'),
    'organization.state.creating' => array('text' => 'Création en cours…', 'context' => 'Organization create pending state.'),
    'organization.state.saved' => array('text' => 'Organisation enregistrée.', 'context' => 'Organization saved confirmation.'),
    'organization.state.created_from_model' => array('text' => 'Organisation créée depuis le modèle.', 'context' => 'Organization created from model confirmation.'),
    'organization.state.created' => array('text' => 'Organisation créée.', 'context' => 'Organization created confirmation.'),
    'organization.error.save' => array('text' => 'Impossible d’enregistrer l’organisation.', 'context' => 'Organization save error.'),
    'organization.error.create' => array('text' => 'Impossible de créer l’organisation.', 'context' => 'Organization create error.'),
    'organization.refresh_prompt' => array('text' => 'Les modifications de l’organisation seront visibles après un rechargement de l’application. Recharger maintenant ?', 'context' => 'Prompt to refresh the application after editing an organization.'),
    'organization.routing.locked' => array('text' => 'L identifiant URL et le domaine sont reserves aux associations et aux organisations.', 'context' => 'Restricted organization routing fields guidance.'),
    'organization.routing.access_restricted' => array('text' => 'Accès réservé.', 'context' => 'Heading for restricted organization routing fields.'),
    'organization.routing.preview' => array('text' => 'Cet identifiant sera utilise dans l URL de base du site :', 'context' => 'Organization URL identifier preview introduction.'),
    'organization.routing.preview_disabled' => array('text' => 'Les sous-domaines d’organisation sont désactivés sur ce serveur. L’accès se fera via une URL de type :', 'context' => 'Short name URL preview without subdomain routing.'),
    'organization.routing.preview_example' => array('text' => 'Cet identifiant sera utilise dans l URL de base du site, par exemple :', 'context' => 'Organization URL identifier example introduction.'),
    'organization.form.unavailable' => array('text' => 'Le formulaire n’est pas disponible.', 'context' => 'Missing organization form error.'),
    'organization.field.name' => array('text' => 'Nom', 'context' => 'Organization name field label.'),
    'organization.field.shortname' => array('text' => 'Identifiant URL', 'context' => 'Organization URL identifier field label, distinct from the holon short display name.'),
    'organization.field.color' => array('text' => 'Couleur', 'context' => 'Organization color field label.'),
    'organization.field.domain' => array('text' => 'Domaine', 'context' => 'Organization domain field label.'),
    'organization.field.interface_level' => array('text' => 'Niveau d’utilisation', 'context' => 'Organization interface level field label.'),
    'organization.field.interface_level_help' => array('text' => 'Définit la quantité d’options affichées progressivement dans le logiciel.', 'context' => 'Organization interface level field help.'),
    'organization.interface_level.discovery' => array('text' => 'Découverte', 'context' => 'Discovery interface level choice.'),
    'organization.interface_level.autonomous' => array('text' => 'Autonome', 'context' => 'Autonomous interface level choice.'),
    'organization.interface_level.expert' => array('text' => 'Expert', 'context' => 'Expert interface level choice.'),
    'organization.visual.title' => array('text' => 'Identité visuelle', 'context' => 'Organization visual identity section heading.'),
    'organization.visual.help' => array('text' => 'Les fichiers sont redimensionnés automatiquement à l’enregistrement.', 'context' => 'Organization visual identity image help.'),
    'organization.visual.logo' => array('text' => 'Logo', 'context' => 'Organization logo field label.'),
    'organization.visual.logo_current' => array('text' => 'Logo actuel', 'context' => 'Alternative text for current organization logo.'),
    'organization.visual.banner' => array('text' => 'Bannière', 'context' => 'Organization banner field label.'),
    'organization.visual.banner_current' => array('text' => 'Bannière actuelle', 'context' => 'Alternative text for current organization banner.'),
    'organization.visual.choose' => array('text' => 'Choisir une image', 'context' => 'Choose organization image button.'),
    'organization.visual.zoom' => array('text' => 'Zoom', 'context' => 'Image crop zoom control label.'),
    'organization.location.title' => array('text' => 'Position géographique', 'context' => 'Organization location section heading.'),
    'organization.location.latitude' => array('text' => 'Latitude', 'context' => 'Organization latitude field label.'),
    'organization.location.longitude' => array('text' => 'Longitude', 'context' => 'Organization longitude field label.'),
    'organization.location.map_help' => array('text' => 'Cliquez sur la carte pour choisir l’emplacement.', 'context' => 'Organization map location picker help.'),
    'organization.location.manual_help' => array('text' => 'Renseignez la latitude et la longitude manuellement si la carte n’est pas disponible.', 'context' => 'Organization location help without a map.'),
    'organization.location.address' => array('text' => 'Adresse à rechercher', 'context' => 'Address input label in the organization location editor.'),
    'organization.location.address_placeholder' => array('text' => 'Rue, numéro, code postal, ville, pays', 'context' => 'Address search example in the organization location editor.'),
    'organization.location.search' => array('text' => 'Chercher les coordonnées', 'context' => 'Action to geocode an organization address.'),
    'organization.location.searching' => array('text' => 'Recherche en cours…', 'context' => 'Busy state for organization address geocoding.'),
    'organization.location.empty' => array('text' => 'Saisissez une adresse avant de lancer la recherche.', 'context' => 'Empty organization address search error.'),
    'organization.location.not_found' => array('text' => 'Aucun lieu trouvé pour cette adresse.', 'context' => 'Organization address search with no results.'),
    'organization.location.error' => array('text' => 'La recherche d’adresse est indisponible pour le moment.', 'context' => 'Organization geocoding service error.'),
    'organization.location.result' => array('text' => 'Coordonnées trouvées : {place}. Vérifiez le point sur la carte, puis enregistrez.', 'context' => 'Organization geocoding result guidance.'),
    'organization.location.result_no_map' => array('text' => 'Coordonnées trouvées : {place}. Vérifiez la latitude et la longitude, puis enregistrez.', 'context' => 'Organization geocoding result guidance without a map.'),
    'organization.location.help' => array('text' => 'L’adresse sert à chercher le lieu. Seules les coordonnées seront enregistrées.', 'context' => 'Explain what is stored after organization address geocoding.'),
    'organization.location.attribution' => array('text' => 'Recherche : OpenStreetMap / Nominatim', 'context' => 'Attribution for address geocoding in the organization editor.'),
);
$lang = translationBundleInit('omo_organization_location', omoGetTranslationLocale(), $sourceLang);

$connected = checklogin();
if (!$connected) {
    die(htmlspecialchars(t('organization.error.login', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8'));
}

$currentUserId = (int)($_SESSION["currentUser"] ?? 0);
if ($currentUserId <= 0) {
    die(htmlspecialchars(t('organization.error.user', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8'));
}

$organizationId = isset($_GET['oid']) && is_numeric($_GET['oid']) ? (int)$_GET['oid'] : 0;
$modelOrganizationId = isset($_GET['model_id']) && is_numeric($_GET['model_id']) ? (int)$_GET['model_id'] : 0;
$organization = new \dbObject\Organization();
$isEditMode = false;
$isModelCreateMode = false;

if ($organizationId > 0) {
    if (!$organization->load($organizationId) || (int)$organization->getId() <= 0) {
        die(htmlspecialchars(t('organization.error.unknown', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8'));
    }

    if (!$organization->canEdit()) {
        die(htmlspecialchars(t('organization.error.forbidden', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8'));
    }

    $isEditMode = true;
}

if (!$isEditMode && $modelOrganizationId > 0) {
    $modelOrganization = new \dbObject\Organization();
    if (!$modelOrganization->load($modelOrganizationId) || !$modelOrganization->isSharedAsTemplate()) {
        die(htmlspecialchars(t('organization.error.model', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8'));
    }

    foreach (array('color', 'latlong', 'interface_level', 'logo', 'banner') as $field) {
        $organization->set($field, $modelOrganization->get($field));
    }
    $organization->set('name', '');
    // Routes must remain unique to the newly created organization.
    $organization->set('shortname', '');
    $organization->set('domain', '');
    $isModelCreateMode = true;
}

$pageTitle = t($isEditMode ? 'organization.title.edit' : ($isModelCreateMode ? 'organization.title.from_model' : 'organization.title.create'), array(), $lang, $sourceLang);
$submitLabel = t($isEditMode ? 'organization.action.save' : ($isModelCreateMode ? 'organization.action.create_from_model' : 'organization.action.create'), array(), $lang, $sourceLang);
$pendingLabel = t($isEditMode ? 'organization.state.saving' : 'organization.state.creating', array(), $lang, $sourceLang);
$successLabel = t($isEditMode ? 'organization.state.saved' : ($isModelCreateMode ? 'organization.state.created_from_model' : 'organization.state.created'), array(), $lang, $sourceLang);
$errorLabel = t($isEditMode ? 'organization.error.save' : 'organization.error.create', array(), $lang, $sourceLang);
$formAction = $isEditMode
    ? '/ajax/saveorganization.php?oid=' . (int)$organization->getId()
    : ($isModelCreateMode ? '/omo/api/organizations/model_create.php' : '/ajax/saveorganization.php');
$refreshApplicationPrompt = t('organization.refresh_prompt', array(), $lang, $sourceLang);
$shortnamePreviewScheme = commonGetRequestScheme();
$shortnamePreviewHost = commonGetRootHost();
$shortnamePreviewPath = '/omo/';
$organizationSubdomainRoutingEnabled = commonUseOrganizationSubdomains();
$isFetchRequest = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$leafletMapsEnabled = commonLeafletMapsEnabled();
$canManageOrganizationRouting = patreonCanManageOrganizationRouting($currentUserId);
$organizationRoutingLockedMessage = t('organization.routing.locked', array(), $lang, $sourceLang);
$organizationInterfaceLevel = $organization->getInterfaceLevel();
$organizationInterfaceLevels = \dbObject\Organization::interfaceLevelCatalog();
$organizationInterfaceLevelLabels = array(
    \dbObject\Organization::INTERFACE_LEVEL_DISCOVERY => t('organization.interface_level.discovery', array(), $lang, $sourceLang),
    \dbObject\Organization::INTERFACE_LEVEL_AUTONOMOUS => t('organization.interface_level.autonomous', array(), $lang, $sourceLang),
    \dbObject\Organization::INTERFACE_LEVEL_EXPERT => t('organization.interface_level.expert', array(), $lang, $sourceLang),
);
$organizationLatlong = $organization->get('latlong');
$organizationLatitude = is_object($organizationLatlong) && isset($organizationLatlong->lat) ? (string)$organizationLatlong->lat : '';
$organizationLongitude = is_object($organizationLatlong) && isset($organizationLatlong->long) ? (string)$organizationLatlong->long : '';
$organizationColor = trim((string)$organization->get('color'));
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $organizationColor)) {
    $organizationColor = '#45a9aa';
}
$organizationLogo = trim((string)$organization->get('logo'));
$organizationBanner = trim((string)$organization->get('banner'));
$organizationImageDisplaySizes = array();
foreach (array('logo', 'banner') as $organizationImageField) {
    $organizationImageSize = \dbObject\Organization::attributeLength()[$organizationImageField] ?? null;
    $organizationImageWidth = 200;
    $organizationImageHeight = 200;
    $organizationImageOutputWidth = 200;
    $organizationImageOutputHeight = 200;
    if (is_array($organizationImageSize)) {
        if (isset($organizationImageSize[0]) && is_array($organizationImageSize[0])) {
            $organizationImageOutputWidth = (int)($organizationImageSize[0][0] ?? $organizationImageOutputWidth);
            $organizationImageOutputHeight = (int)($organizationImageSize[0][1] ?? $organizationImageOutputHeight);
            $organizationImageWidth = (int)($organizationImageSize[1][0] ?? $organizationImageSize[0][0] ?? $organizationImageWidth);
            $organizationImageHeight = (int)($organizationImageSize[1][1] ?? $organizationImageSize[0][1] ?? $organizationImageHeight);
        } else {
            $organizationImageWidth = (int)($organizationImageSize[0] ?? $organizationImageWidth);
            $organizationImageHeight = (int)($organizationImageSize[1] ?? $organizationImageHeight);
            $organizationImageOutputWidth = $organizationImageWidth;
            $organizationImageOutputHeight = $organizationImageHeight;
        }
    }
    $organizationImageDisplaySizes[$organizationImageField] = array(
        'width' => max(1, $organizationImageWidth),
        'height' => max(1, $organizationImageHeight),
        'outputWidth' => max(1, $organizationImageOutputWidth),
        'outputHeight' => max(1, $organizationImageOutputHeight),
    );
}
$organizationIllustrationPreviewHeight = max(1, (int)round(1.5 * min(
    (int)$organizationImageDisplaySizes['logo']['height'],
    (int)floor($organizationImageDisplaySizes['banner']['height'] / 2)
)));
$organizationLogoPreviewWidth = max(1, (int)round(
    $organizationImageDisplaySizes['logo']['width']
    * $organizationIllustrationPreviewHeight
    / $organizationImageDisplaySizes['logo']['height']
));
$organizationBannerPreviewWidth = max(1, (int)round(
    $organizationImageDisplaySizes['banner']['width']
    * $organizationIllustrationPreviewHeight
    / $organizationImageDisplaySizes['banner']['height']
));
?>
<?php if (!$isFetchRequest) { ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="/shared_functions.js"></script>
    <script>sharedApplyDocumentTheme();</script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/components.css') ?>">
    <link rel="stylesheet" href="/common/assets/auth.css">
<?php if ($leafletMapsEnabled) { commonRenderLeafletAssets(); } ?>
</head>
<body class="organization-create-page">
<?php } ?>

<?php if ($isFetchRequest && $leafletMapsEnabled) { commonRenderLeafletAssets(); } ?>

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/assets/css/organization-create.css') ?>">

<div class="organization-create-view" id="organizationCreateRoot" data-render-mode="<?= $isFetchRequest ? 'fetch' : 'document' ?>">
    <div class="organization-create-shell">

    <section class="organization-create-card generic-stack">
        <form id="organization_create_form" class="generic-form-stack" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data">
<?php if ($isEditMode) { ?>
            <input type="hidden" name="id" value="<?= (int)$organization->getId() ?>">
<?php } ?>
<?php if ($isModelCreateMode) { ?>
            <input type="hidden" name="model_id" value="<?= (int)$modelOrganizationId ?>">
<?php } ?>
            <section class="generic-form-section generic-section generic-section--stack">
                <div class="generic-form-grid">
                    <label class="generic-form-field generic-form-field--full">
                        <span class="generic-form-label"><?= htmlspecialchars(t('organization.field.name', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
                        <input class="generic-form-control" type="text" name="name" id="name" maxlength="100" required value="<?= htmlspecialchars((string)$organization->get('name'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>

                    <label class="generic-form-field">
                        <span class="generic-form-label"><?= htmlspecialchars(t('organization.field.shortname', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
                        <input class="generic-form-control" type="text" name="shortname" id="shortname" maxlength="50" pattern="[A-Za-z0-9_-]+" value="<?= htmlspecialchars((string)$organization->get('shortname'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>

                    <label class="generic-form-field">
                        <span class="generic-form-label"><?= htmlspecialchars(t('organization.field.color', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
                        <input class="organization-create-color-control" type="color" name="color" id="color" value="<?= htmlspecialchars($organizationColor, ENT_QUOTES, 'UTF-8') ?>">
                    </label>

                    <label class="generic-form-field">
                        <span class="generic-form-label"><?= htmlspecialchars(t('organization.field.domain', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
                        <input class="generic-form-control" type="text" name="domain" id="domain" maxlength="100" value="<?= htmlspecialchars((string)$organization->get('domain'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>

                    <div class="generic-form-field generic-form-field--full">
                        <span class="generic-form-label"><?= htmlspecialchars(t('organization.field.interface_level', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
                        <p class="organization-create-help"><?= htmlspecialchars(t('organization.field.interface_level_help', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
                        <input type="hidden" name="interface_level" id="interface_level" value="<?= (int)$organizationInterfaceLevel ?>">
                        <div class="generic-filter-chips generic-filter-chips--unconstrained" id="organization-interface-level-select" role="group" aria-label="<?= htmlspecialchars(t('organization.field.interface_level', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?>">
<?php foreach ($organizationInterfaceLevels as $level => $option) { ?>
                            <button type="button" class="generic-filter-chip" data-organization-interface-level="<?= (int)$level ?>" aria-pressed="<?= (int)$level === $organizationInterfaceLevel ? 'true' : 'false' ?>"><?= htmlspecialchars((string)($organizationInterfaceLevelLabels[$level] ?? $option['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></button>
<?php } ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="generic-form-section generic-section generic-section--stack">
                <div class="generic-form-section__heading">
                    <div class="generic-form-section__copy">
                        <h3 class="generic-card-title"><?= htmlspecialchars(t('organization.visual.title', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="organization-create-help"><?= htmlspecialchars(t('organization.visual.help', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>

                <div class="generic-form-grid organization-create-illustration-grid">
                    <div class="generic-form-field">
                        <span class="generic-form-label"><?= htmlspecialchars(t('organization.visual.logo', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="organization-create-image-editor" data-organization-image-editor="logo">
                            <input type="hidden" name="logo" id="logo" value="<?= htmlspecialchars($organizationLogo, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="file" id="imageFileInput_logo" accept="image/jpeg,image/png,image/webp" hidden data-organization-image-file="logo">
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-organization-image-choose="logo"><?= htmlspecialchars(t('organization.visual.choose', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></button>
                            <div class="organization-create-image-crop" style="width: min(100%, <?= $organizationLogoPreviewWidth ?>px); aspect-ratio: <?= (int)$organizationImageDisplaySizes['logo']['width'] ?> / <?= (int)$organizationImageDisplaySizes['logo']['height'] ?>;" data-organization-image-crop="logo" data-output-width="<?= (int)$organizationImageDisplaySizes['logo']['outputWidth'] ?>" data-output-height="<?= (int)$organizationImageDisplaySizes['logo']['outputHeight'] ?>">
<?php if ($organizationLogo !== '') { ?>
                                <img src="<?= htmlspecialchars($organizationLogo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(t('organization.visual.logo_current', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?>" data-organization-image-preview="logo">
<?php } ?>
                            </div>
                            <label class="organization-create-image-zoom" style="width: min(100%, <?= $organizationLogoPreviewWidth ?>px);">
                                <span><?= htmlspecialchars(t('organization.visual.zoom', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
                                <input type="range" min="0" max="100" step="1" value="0" data-organization-image-zoom="logo">
                            </label>
                        </div>
                    </div>

                    <div class="generic-form-field">
                        <span class="generic-form-label"><?= htmlspecialchars(t('organization.visual.banner', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="organization-create-image-editor" data-organization-image-editor="banner">
                            <input type="hidden" name="banner" id="banner" value="<?= htmlspecialchars($organizationBanner, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="file" id="imageFileInput_banner" accept="image/jpeg,image/png,image/webp" hidden data-organization-image-file="banner">
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-organization-image-choose="banner"><?= htmlspecialchars(t('organization.visual.choose', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></button>
                            <div class="organization-create-image-crop" style="width: min(100%, <?= $organizationBannerPreviewWidth ?>px); aspect-ratio: <?= (int)$organizationImageDisplaySizes['banner']['width'] ?> / <?= (int)$organizationImageDisplaySizes['banner']['height'] ?>;" data-organization-image-crop="banner" data-output-width="<?= (int)$organizationImageDisplaySizes['banner']['outputWidth'] ?>" data-output-height="<?= (int)$organizationImageDisplaySizes['banner']['outputHeight'] ?>">
<?php if ($organizationBanner !== '') { ?>
                                <img src="<?= htmlspecialchars($organizationBanner, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(t('organization.visual.banner_current', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?>" data-organization-image-preview="banner">
<?php } ?>
                            </div>
                            <label class="organization-create-image-zoom" style="width: min(100%, <?= $organizationBannerPreviewWidth ?>px);">
                                <span><?= htmlspecialchars(t('organization.visual.zoom', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
                                <input type="range" min="0" max="100" step="1" value="0" data-organization-image-zoom="banner">
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <section class="generic-form-section generic-section generic-section--stack">
                <div class="generic-form-section__heading">
                    <div class="generic-form-section__copy">
                        <h3 class="generic-card-title"><?= htmlspecialchars(t('organization.location.title', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                </div>
                <div class="generic-form-field generic-form-field--full">
                    <label class="generic-form-label" for="organization_location_address"><?= htmlspecialchars(t('organization.location.address', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></label>
                    <div class="organization-create-address-search">
                        <input class="generic-form-control" type="search" id="organization_location_address" maxlength="200" autocomplete="street-address" placeholder="<?= htmlspecialchars(t('organization.location.address_placeholder', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" class="generic-action-button generic-action-button--secondary" id="organization_location_search"><?= htmlspecialchars(t('organization.location.search', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                    <p class="organization-create-help" id="organization_location_feedback" role="status" aria-live="polite" hidden><span id="organization_location_feedback_text"></span> <a class="organization-create-location-attribution" id="organization_location_attribution" href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer" hidden><?= htmlspecialchars(t('organization.location.attribution', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></a></p>
                    <p class="organization-create-help"><?= htmlspecialchars(t('organization.location.help', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="generic-form-grid">
                    <label class="generic-form-field">
                        <span class="generic-form-label"><?= htmlspecialchars(t('organization.location.latitude', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
                        <input class="generic-form-control" type="number" name="latlong[]" id="latlong_lat" min="-90" max="90" step="any" value="<?= htmlspecialchars($organizationLatitude, ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label class="generic-form-field">
                        <span class="generic-form-label"><?= htmlspecialchars(t('organization.location.longitude', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
                        <input class="generic-form-control" type="number" name="latlong[]" id="latlong_long" min="-180" max="180" step="any" value="<?= htmlspecialchars($organizationLongitude, ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                </div>
<?php if ($leafletMapsEnabled) { ?>
                <div class="organization-create-location-map" id="organization-create-location-map"></div>
                <p class="organization-create-help"><?= htmlspecialchars(t('organization.location.map_help', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
<?php } else { ?>
                <p class="organization-create-help"><?= htmlspecialchars(t('organization.location.manual_help', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
<?php } ?>
            </section>

            <div class="organization-create-actions generic-form-actions">
                <button type="button" class="generic-action-button generic-action-button--secondary" id="organization_create_cancel"><?= htmlspecialchars(t('organization.action.cancel', array(), $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></button>
                <button type="button" class="generic-action-button generic-action-button--main" id="organization_create_submit"><?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') ?></button>
            </div>

            <div class="organization-create-feedback" id="organization_create_feedback"></div>
        </form>
    </section>
    </div>
</div>

<script>
    (function () {
        function syncInterfaceLevelValue(event) {
            var option = event.target.closest('[data-organization-interface-level]');
            var hiddenInput;
            var selector;

            selector = document.getElementById('organization-interface-level-select');
            if (!option || !selector || !selector.contains(option)) {
                return;
            }

            hiddenInput = document.getElementById('interface_level');
            if (hiddenInput) {
                hiddenInput.value = option.getAttribute('data-organization-interface-level') || '1';
            }
            selector.querySelectorAll('[data-organization-interface-level]').forEach(function (candidate) {
                candidate.setAttribute('aria-pressed', candidate === option ? 'true' : 'false');
            });
        }

        document.addEventListener('click', syncInterfaceLevelValue);
    })();
</script>

<?= commonPageScriptTags('/omo/assets/js/organization-create.js', [
    'isEditMode' => ($isEditMode),
    'organizationId' => (int)$organization->getId(),
    'formAction' => $formAction,
    'shortnamePreviewScheme' => $shortnamePreviewScheme,
    'shortnamePreviewHost' => $shortnamePreviewHost,
    'shortnamePreviewPath' => $shortnamePreviewPath,
    'organizationSubdomainRoutingEnabled' => ($organizationSubdomainRoutingEnabled),
    'canManageOrganizationRouting' => ($canManageOrganizationRouting),
    'organizationRoutingLockedMessage' => $organizationRoutingLockedMessage,
    'refreshApplicationPrompt' => $refreshApplicationPrompt,
    'pendingLabel' => $pendingLabel,
    'message' => $errorLabel,
    'successLabel' => $successLabel,
    'uiLabels' => array(
        'accessRestricted' => t('organization.routing.access_restricted', array(), $lang, $sourceLang),
        'shortnamePreview' => t('organization.routing.preview', array(), $lang, $sourceLang),
        'shortnamePreviewDisabled' => t('organization.routing.preview_disabled', array(), $lang, $sourceLang),
        'shortnamePreviewExample' => t('organization.routing.preview_example', array(), $lang, $sourceLang),
        'formUnavailable' => t('organization.form.unavailable', array(), $lang, $sourceLang),
    ),
    'locationLabels' => array(
        'searching' => t('organization.location.searching', array(), $lang, $sourceLang),
        'empty' => t('organization.location.empty', array(), $lang, $sourceLang),
        'notFound' => t('organization.location.not_found', array(), $lang, $sourceLang),
        'error' => t('organization.location.error', array(), $lang, $sourceLang),
        'result' => t($leafletMapsEnabled ? 'organization.location.result' : 'organization.location.result_no_map', array(), $lang, $sourceLang),
    ),
]) ?>

<?php if (!$isFetchRequest) { ?>
</body>
</html>
<?php } ?>
