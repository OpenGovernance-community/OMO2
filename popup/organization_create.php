<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/config.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/shared_functions.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/common/auth.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/common/patreon.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/common/leaflet_helper.php");

$connected = checklogin();
if (!$connected) {
    die("Login requis");
}

$currentUserId = (int)($_SESSION["currentUser"] ?? 0);
if ($currentUserId <= 0) {
    die("Utilisateur inconnu");
}

$organizationId = isset($_GET['oid']) && is_numeric($_GET['oid']) ? (int)$_GET['oid'] : 0;
$modelOrganizationId = isset($_GET['model_id']) && is_numeric($_GET['model_id']) ? (int)$_GET['model_id'] : 0;
$organization = new \dbObject\Organization();
$isEditMode = false;
$isModelCreateMode = false;

if ($organizationId > 0) {
    if (!$organization->load($organizationId) || (int)$organization->getId() <= 0) {
        die("Organisation inconnue");
    }

    if (!$organization->canEdit()) {
        die("Acces refuse");
    }

    $isEditMode = true;
}

if (!$isEditMode && $modelOrganizationId > 0) {
    $modelOrganization = new \dbObject\Organization();
    if (!$modelOrganization->load($modelOrganizationId) || !$modelOrganization->isSharedAsTemplate()) {
        die("Modèle public introuvable");
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

$pageTitle = $isEditMode ? "Modifier une organisation" : ($isModelCreateMode ? "Créer à partir d'un modèle" : "Creer une organisation");
$submitLabel = $isEditMode ? "Enregistrer les modifications" : ($isModelCreateMode ? "Créer depuis ce modèle" : "Creer l'organisation");
$pendingLabel = $isEditMode ? "Enregistrement en cours..." : "Création en cours...";
$successLabel = $isEditMode ? "Organisation enregistree." : ($isModelCreateMode ? "Organisation créée depuis le modèle." : "Organisation creee.");
$errorLabel = $isEditMode ? "Impossible d'enregistrer l'organisation." : "Impossible de creer l'organisation.";
$formAction = $isEditMode
    ? '/ajax/saveorganization.php?oid=' . (int)$organization->getId()
    : ($isModelCreateMode ? '/omo/api/organizations/model_create.php' : '/ajax/saveorganization.php');
$refreshApplicationPrompt = "Les modifications de l organisation seront visibles apres un rechargement de l application. Recharger maintenant ?";
$shortnamePreviewScheme = commonGetRequestScheme();
$shortnamePreviewHost = commonGetRootHost();
$shortnamePreviewPath = '/omo/';
$organizationSubdomainRoutingEnabled = commonUseOrganizationSubdomains();
$isFetchRequest = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$leafletMapsEnabled = commonLeafletMapsEnabled();
$canManageOrganizationRouting = patreonCanManageOrganizationRouting($currentUserId);
$organizationRoutingLockedMessage = "Le nom court et le domaine sont reserves aux associations et aux organisations.";
$organizationInterfaceLevel = $organization->getInterfaceLevel();
$organizationInterfaceLevels = \dbObject\Organization::interfaceLevelCatalog();
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
                        <span class="generic-form-label">Nom</span>
                        <input class="generic-form-control" type="text" name="name" id="name" maxlength="100" required value="<?= htmlspecialchars((string)$organization->get('name'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>

                    <label class="generic-form-field">
                        <span class="generic-form-label">Nom court</span>
                        <input class="generic-form-control" type="text" name="shortname" id="shortname" maxlength="50" pattern="[A-Za-z0-9_-]+" value="<?= htmlspecialchars((string)$organization->get('shortname'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>

                    <label class="generic-form-field">
                        <span class="generic-form-label">Couleur</span>
                        <input class="organization-create-color-control" type="color" name="color" id="color" value="<?= htmlspecialchars($organizationColor, ENT_QUOTES, 'UTF-8') ?>">
                    </label>

                    <label class="generic-form-field">
                        <span class="generic-form-label">Domaine</span>
                        <input class="generic-form-control" type="text" name="domain" id="domain" maxlength="100" value="<?= htmlspecialchars((string)$organization->get('domain'), ENT_QUOTES, 'UTF-8') ?>">
                    </label>

                    <div class="generic-form-field generic-form-field--full">
                        <span class="generic-form-label">Position geographique</span>
                        <div class="generic-form-grid">
                            <label class="generic-form-field">
                                <span class="generic-form-label">Latitude</span>
                                <input class="generic-form-control" type="number" name="latlong[]" id="latlong_lat" min="-90" max="90" step="any" value="<?= htmlspecialchars($organizationLatitude, ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                            <label class="generic-form-field">
                                <span class="generic-form-label">Longitude</span>
                                <input class="generic-form-control" type="number" name="latlong[]" id="latlong_long" min="-180" max="180" step="any" value="<?= htmlspecialchars($organizationLongitude, ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                        </div>
<?php if ($leafletMapsEnabled) { ?>
                        <div class="organization-create-location-map" id="organization-create-location-map"></div>
                        <p class="organization-create-help">Cliquez sur la carte pour choisir l emplacement.</p>
<?php } else { ?>
                        <p class="organization-create-help">Renseignez latitude et longitude manuellement si la carte n est pas disponible.</p>
<?php } ?>
                    </div>

                    <div class="generic-form-field generic-form-field--full">
                        <span class="generic-form-label">Niveau d utilisation</span>
                        <p class="organization-create-help">Definit la quantite d options affichees progressivement dans le logiciel.</p>
                        <input type="hidden" name="interface_level" id="interface_level" value="<?= (int)$organizationInterfaceLevel ?>">
                        <div class="generic-filter-chips generic-filter-chips--unconstrained" id="organization-interface-level-select" role="group" aria-label="Niveau d utilisation">
<?php foreach ($organizationInterfaceLevels as $level => $option) { ?>
                            <button type="button" class="generic-filter-chip" data-organization-interface-level="<?= (int)$level ?>" aria-pressed="<?= (int)$level === $organizationInterfaceLevel ? 'true' : 'false' ?>"><?= htmlspecialchars((string)($option['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></button>
<?php } ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="generic-form-section generic-section generic-section--stack">
                <div class="generic-form-section__heading">
                    <div class="generic-form-section__copy">
                        <h3 class="generic-card-title">Identite visuelle</h3>
                        <p class="organization-create-help">Les fichiers sont redimensionnes automatiquement a l enregistrement.</p>
                    </div>
                </div>

                <div class="generic-form-grid organization-create-illustration-grid">
                    <div class="generic-form-field">
                        <span class="generic-form-label">Logo</span>
                        <div class="organization-create-image-editor" data-organization-image-editor="logo">
                            <input type="hidden" name="logo" id="logo" value="<?= htmlspecialchars($organizationLogo, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="file" id="imageFileInput_logo" accept="image/jpeg,image/png,image/webp" hidden data-organization-image-file="logo">
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-organization-image-choose="logo">Choisir une image</button>
                            <div class="organization-create-image-crop" style="width: min(100%, <?= $organizationLogoPreviewWidth ?>px); aspect-ratio: <?= (int)$organizationImageDisplaySizes['logo']['width'] ?> / <?= (int)$organizationImageDisplaySizes['logo']['height'] ?>;" data-organization-image-crop="logo" data-output-width="<?= (int)$organizationImageDisplaySizes['logo']['outputWidth'] ?>" data-output-height="<?= (int)$organizationImageDisplaySizes['logo']['outputHeight'] ?>">
<?php if ($organizationLogo !== '') { ?>
                                <img src="<?= htmlspecialchars($organizationLogo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo actuel" data-organization-image-preview="logo">
<?php } ?>
                            </div>
                            <label class="organization-create-image-zoom" style="width: min(100%, <?= $organizationLogoPreviewWidth ?>px);">
                                <span>Zoom</span>
                                <input type="range" min="0" max="100" step="1" value="0" data-organization-image-zoom="logo">
                            </label>
                        </div>
                    </div>

                    <div class="generic-form-field">
                        <span class="generic-form-label">Banniere</span>
                        <div class="organization-create-image-editor" data-organization-image-editor="banner">
                            <input type="hidden" name="banner" id="banner" value="<?= htmlspecialchars($organizationBanner, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="file" id="imageFileInput_banner" accept="image/jpeg,image/png,image/webp" hidden data-organization-image-file="banner">
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-organization-image-choose="banner">Choisir une image</button>
                            <div class="organization-create-image-crop" style="width: min(100%, <?= $organizationBannerPreviewWidth ?>px); aspect-ratio: <?= (int)$organizationImageDisplaySizes['banner']['width'] ?> / <?= (int)$organizationImageDisplaySizes['banner']['height'] ?>;" data-organization-image-crop="banner" data-output-width="<?= (int)$organizationImageDisplaySizes['banner']['outputWidth'] ?>" data-output-height="<?= (int)$organizationImageDisplaySizes['banner']['outputHeight'] ?>">
<?php if ($organizationBanner !== '') { ?>
                                <img src="<?= htmlspecialchars($organizationBanner, ENT_QUOTES, 'UTF-8') ?>" alt="Banniere actuelle" data-organization-image-preview="banner">
<?php } ?>
                            </div>
                            <label class="organization-create-image-zoom" style="width: min(100%, <?= $organizationBannerPreviewWidth ?>px);">
                                <span>Zoom</span>
                                <input type="range" min="0" max="100" step="1" value="0" data-organization-image-zoom="banner">
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <div class="organization-create-actions generic-form-actions">
                <button type="button" class="generic-action-button generic-action-button--secondary" id="organization_create_cancel">Annuler</button>
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
]) ?>

<?php if (!$isFetchRequest) { ?>
</body>
</html>
<?php } ?>
