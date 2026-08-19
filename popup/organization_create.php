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
$organization = new \dbObject\Organization();
$isEditMode = false;

if ($organizationId > 0) {
    if (!$organization->load($organizationId) || (int)$organization->getId() <= 0) {
        die("Organisation inconnue");
    }

    if (!$organization->canEdit()) {
        die("Acces refuse");
    }

    $isEditMode = true;
}

$pageTitle = $isEditMode ? "Modifier une organisation" : "Creer une organisation";
$submitLabel = $isEditMode ? "Enregistrer les modifications" : "Creer l'organisation";
$pendingLabel = $isEditMode ? "Enregistrement en cours..." : "Creation en cours...";
$successLabel = $isEditMode ? "Organisation enregistree." : "Organisation creee.";
$errorLabel = $isEditMode ? "Impossible d'enregistrer l'organisation." : "Impossible de creer l'organisation.";
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
    <link rel="stylesheet" href="/common/assets/components.css?v=20260819-filter-chips-level">
    <link rel="stylesheet" href="/common/assets/auth.css">
<?php if ($leafletMapsEnabled) { commonRenderLeafletAssets(); } ?>
</head>
<body class="organization-create-page">
<?php } ?>

<?php if ($isFetchRequest && $leafletMapsEnabled) { commonRenderLeafletAssets(); } ?>

<style>
    .organization-create-page {
        margin: 0;
        padding: 20px;
        background: var(--color-bg, var(--auth-page-bg, #f8fafc));
        color: var(--color-text, var(--auth-page-text, #0f172a));
        font-family: system-ui, sans-serif;
    }

    .organization-create-view {
        display: flex;
        flex-direction: column;
        gap: 18px;
        max-width: 980px;
        margin: 0 auto;
        color: var(--color-text, var(--auth-page-text, #0f172a));
    }

    .organization-create-shell {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .organization-create-help {
        margin: 0;
        color: var(--color-text-light, #64748b);
        font-size: 13px;
        line-height: 1.45;
    }

    .organization-create-color-control {
        width: 100%;
        min-height: 44px;
        padding: 4px;
        border: 1px solid var(--color-border, #d1d5db);
        border-radius: var(--radius-md);
        background: var(--color-surface-alt, #f8fafc);
        cursor: pointer;
        box-sizing: border-box;
    }

    .organization-create-location-map {
        width: 100%;
        height: 260px;
        border: 1px solid var(--color-border, #d1d5db);
        border-radius: var(--radius-md);
        background: var(--color-surface-alt, #f8fafc);
        overflow: hidden;
    }

    .organization-create-image-editor {
        display: grid;
        justify-items: start;
        gap: 10px;
    }

    .organization-create-illustration-grid {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.78fr);
    }

    .organization-create-image-crop {
        position: relative;
        max-width: 100%;
        overflow: hidden;
        border: 1px solid var(--color-border, #d1d5db);
        border-radius: var(--radius-md);
        background: var(--color-surface-alt, #f8fafc);
        cursor: grab;
        touch-action: none;
    }

    .organization-create-image-crop:active {
        cursor: grabbing;
    }

    .organization-create-image-crop img {
        position: absolute;
        top: 0;
        left: 0;
        display: block;
        max-width: none;
        user-select: none;
        pointer-events: none;
    }

    .organization-create-image-zoom {
        display: grid;
        grid-template-columns: auto minmax(120px, 1fr);
        align-items: center;
        gap: 8px;
        width: min(100%, 300px);
        color: var(--color-text-light, #64748b);
        font-size: 13px;
    }

    .organization-create-image-zoom input {
        width: 100%;
    }

    .organization-create-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 4px;
        flex-wrap: wrap;
    }

    .organization-create-feedback {
        display: none;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid var(--color-border, #dbe4ee);
        background: var(--color-surface-alt, #f8fafc);
        color: var(--color-text-light, #475569);
    }

    .organization-create-feedback.is-error {
        display: block;
        color: #b91c1c;
        border-color: color-mix(in srgb, #dc2626 18%, var(--color-border, #dbe4ee));
        background: color-mix(in srgb, #dc2626 8%, var(--color-surface, #ffffff));
    }

    .organization-create-feedback.is-success {
        display: block;
        color: #166534;
        border-color: color-mix(in srgb, #16a34a 18%, var(--color-border, #dbe4ee));
        background: color-mix(in srgb, #16a34a 8%, var(--color-surface, #ffffff));
    }

    .organization-create-shortname-hint {
        margin-top: 8px;
        font-size: 13px;
        line-height: 1.45;
        color: var(--color-text-light, #475569);
    }

    .organization-create-shortname-hint code {
        display: inline-block;
        margin-top: 4px;
        padding: 3px 8px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface, #ffffff));
        color: var(--color-primary, #1d4ed8);
        font-size: 12px;
    }

    .organization-create-routing-lock {
        margin-top: 8px;
        font-size: 13px;
        line-height: 1.45;
        color: var(--color-text-light, #64748b);
    }

    .organization-create-routing-lock strong {
        color: var(--color-text, #0f172a);
    }

    .organization-create-field--locked {
        opacity: 0.72;
    }

    .organization-create-field--locked input[disabled] {
        cursor: not-allowed;
        background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 92%, #cbd5e1);
        color: var(--color-text-light, #64748b);
    }

    #commonTopbarModalBody .organization-create-view {
        max-width: none;
        gap: 0;
    }

    #commonTopbarModalBody .organization-create-shell {
        padding: 16px 18px 18px;
        box-sizing: border-box;
    }

    @media (max-width: 860px) {
        .organization-create-page {
            padding: 14px;
        }

        .organization-create-view {
            gap: 14px;
        }

        .organization-create-actions {
            justify-content: stretch;
        }

        .organization-create-actions .generic-action-button {
            flex: 1 1 220px;
        }

        .organization-create-illustration-grid {
            grid-template-columns: 1fr;
        }

    }
</style>

<div class="organization-create-view" id="organizationCreateRoot" data-render-mode="<?= $isFetchRequest ? 'fetch' : 'document' ?>">
    <div class="organization-create-shell">

    <section class="organization-create-card generic-stack">
        <form id="organization_create_form" class="generic-form-stack" action="<?= $isEditMode ? '/ajax/saveorganization.php?oid=' . (int)$organization->getId() : '/ajax/saveorganization.php' ?>" method="post" enctype="multipart/form-data">
<?php if ($isEditMode) { ?>
            <input type="hidden" name="id" value="<?= (int)$organization->getId() ?>">
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

<script>
    (function () {
        var root = document.getElementById('organizationCreateRoot');
        var isEditMode = <?= $isEditMode ? 'true' : 'false' ?>;
        var organizationId = <?= (int)$organization->getId() ?>;
        var shortnamePreviewScheme = <?= json_encode($shortnamePreviewScheme, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var shortnamePreviewHost = <?= json_encode($shortnamePreviewHost, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var shortnamePreviewPath = <?= json_encode($shortnamePreviewPath, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var organizationSubdomainRoutingEnabled = <?= $organizationSubdomainRoutingEnabled ? 'true' : 'false' ?>;
        var canManageOrganizationRouting = <?= $canManageOrganizationRouting ? 'true' : 'false' ?>;
        var organizationRoutingLockedMessage = <?= json_encode($organizationRoutingLockedMessage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var refreshApplicationPrompt = <?= json_encode($refreshApplicationPrompt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var submitButton = document.getElementById('organization_create_submit');
        var cancelButton = document.getElementById('organization_create_cancel');
        var form = document.getElementById('organization_create_form');
        var feedback = document.getElementById('organization_create_feedback');
        var shortnameInput = document.getElementById('shortname') || document.querySelector('input[name="shortname"]');
        var domainInput = document.getElementById('domain') || document.querySelector('input[name="domain"]');
        var initialFormSnapshot = '';
        var initialApplicationSnapshot = '';

        if (!root || !form || !submitButton || !cancelButton || !feedback) {
            return;
        }

        function ensureRoutingLockHint(input, hintId) {
            if (!input) {
                return null;
            }

            var existingHint = document.getElementById(hintId);
            if (existingHint) {
                return existingHint;
            }

            var field = input.closest('.generic-form-field');
            var hint = document.createElement('div');
            hint.id = hintId;
            hint.className = 'organization-create-routing-lock';
            hint.innerHTML = '<strong>Acces reserve.</strong> ' + organizationRoutingLockedMessage;

            if (field) {
                field.appendChild(hint);
            } else {
                input.insertAdjacentElement('afterend', hint);
            }

            return hint;
        }

        function lockRoutingField(input, hintId) {
            if (!input) {
                return;
            }

            input.disabled = true;
            input.setAttribute('aria-disabled', 'true');

            var field = input.closest('.generic-form-field');
            if (field) {
                field.classList.add('organization-create-field--locked');
            }

            ensureRoutingLockHint(input, hintId);
        }

        function applyRoutingRestrictions() {
            if (canManageOrganizationRouting) {
                return;
            }

            lockRoutingField(shortnameInput, 'organization_create_shortname_lock');
            lockRoutingField(domainInput, 'organization_create_domain_lock');
        }

        function getFormSnapshot() {
            var values = [];

            Array.prototype.forEach.call(form.querySelectorAll('input[name], select[name], textarea[name]'), function (field) {
                var type = String(field.type || '').toLowerCase();
                if (field.disabled || type === 'file') {
                    return;
                }
                if ((type === 'checkbox' || type === 'radio') && !field.checked) {
                    return;
                }
                values.push(field.name + '=' + String(field.value || ''));
            });

            return values.join('\n');
        }

        function getApplicationSnapshot() {
            var fields = ['name', 'shortname', 'domain', 'color', 'interface_level', 'logo', 'banner'];

            return fields.map(function (fieldName) {
                var field = form.querySelector('[name="' + fieldName + '"]');
                return fieldName + '=' + (field ? String(field.value || '') : '');
            }).join('\n');
        }

        if (form) {
            form.setAttribute('action', isEditMode ? ('/ajax/saveorganization.php?oid=' + encodeURIComponent(String(organizationId))) : '/ajax/saveorganization.php');
            form.setAttribute('method', 'post');
            form.setAttribute('enctype', 'multipart/form-data');
            applyRoutingRestrictions();
            initialFormSnapshot = getFormSnapshot();
            initialApplicationSnapshot = getApplicationSnapshot();
        }

        function buildShortnamePreviewUrl(value) {
            var normalizedValue = String(value || '').trim().toLowerCase();
            if (!shortnamePreviewHost) {
                return '';
            }

            if (!organizationSubdomainRoutingEnabled) {
                var targetId = organizationId > 0 ? organizationId : 123;
                return shortnamePreviewScheme + '://' + shortnamePreviewHost + '/omo/o/' + targetId;
            }

            if (!normalizedValue) {
                return '';
            }

            return shortnamePreviewScheme + '://' + normalizedValue + '.' + shortnamePreviewHost + shortnamePreviewPath;
        }

        function ensureShortnameHint() {
            if (!shortnameInput) {
                return null;
            }

            var existingHint = document.getElementById('organization_create_shortname_hint');
            if (existingHint) {
                return existingHint;
            }

            var shortnameField = shortnameInput.closest('.generic-form-field');
            var hint = document.createElement('div');
            hint.id = 'organization_create_shortname_hint';
            hint.className = 'organization-create-shortname-hint';
            if (shortnameField) {
                shortnameField.appendChild(hint);
            } else {
                shortnameInput.insertAdjacentElement('afterend', hint);
            }
            return hint;
        }

        function updateShortnameHint() {
            var hint = ensureShortnameHint();
            if (!hint) {
                return;
            }

            if (!canManageOrganizationRouting) {
                hint.style.display = 'none';
                return;
            }

            hint.style.display = '';

            var previewUrl = buildShortnamePreviewUrl(shortnameInput ? shortnameInput.value : '');
            if (previewUrl) {
                if (organizationSubdomainRoutingEnabled) {
                    hint.innerHTML = "Ce nom court sera utilise dans l'URL de base du site :<br><code>" + previewUrl + "</code>";
                } else {
                    hint.innerHTML = "Les sous-domaines d'organisation sont desactives sur ce serveur. L'acces se fera via une URL de type :<br><code>" + previewUrl + "</code>";
                }
                return;
            }

            hint.innerHTML = "Ce nom court sera utilise dans l'URL de base du site, par exemple :<br><code>" + shortnamePreviewScheme + "://nomcourt." + shortnamePreviewHost + shortnamePreviewPath + "</code>";
        }

        function setFeedback(message, isError) {
            if (message && typeof window.commonNotify === 'function') {
                window.commonNotify(message, isError ? 'error' : 'success');
                feedback.textContent = '';
                feedback.className = 'organization-create-feedback';
                feedback.style.display = 'none';
                return;
            }

            feedback.textContent = message || '';
            feedback.className = 'organization-create-feedback' + (message ? (isError ? ' is-error' : ' is-success') : '');
            feedback.style.display = message ? 'block' : 'none';
        }

        function closeModal() {
            var settingsRoot = document.querySelector('.omo-settings');
            if (settingsRoot && settingsRoot.querySelector('[data-omo-settings-nested-drawer]')) {
                settingsRoot.dispatchEvent(new Event('omo-settings-close-nested-drawer'));
                return;
            }

            if (typeof window.commonTopbarCloseModal === 'function') {
                window.commonTopbarCloseModal();
                return;
            }

            if (window.parent && window.parent !== window && typeof window.parent.commonTopbarCloseModal === 'function') {
                window.parent.commonTopbarCloseModal();
                return;
            }

            if (window.parent && window.parent !== window) {
                window.parent.location.reload();
                return;
            }

            window.close();
        }

        function redirectTargetWindow(url) {
            if (!url) {
                return;
            }

            if (window.parent && window.parent !== window) {
                window.parent.location.href = url;
                return;
            }

            window.location.href = url;
        }

        function reloadTargetWindow() {
            if (window.parent && window.parent !== window) {
                window.parent.location.reload();
                return;
            }

            window.location.reload();
        }

        function getComparableLocationHref() {
            if (window.parent && window.parent !== window && window.parent.location) {
                return window.parent.location.href;
            }

            return window.location.href;
        }

        function normalizeComparableUrl(url) {
            return String(url.protocol || '') + '//' + String(url.host || '') + String(url.pathname || '') + String(url.search || '');
        }

        function handleSuccessfulSave(result, shouldRefreshApplication) {
            var redirectUrl = result && result.redirect ? String(result.redirect) : '';

            if (!isEditMode) {
                if (redirectUrl) {
                    redirectTargetWindow(redirectUrl);
                    return;
                }

                closeModal();
                return;
            }

            if (!shouldRefreshApplication) {
                closeModal();
                return;
            }

            if (!window.confirm(refreshApplicationPrompt)) {
                closeModal();
                return;
            }

            if (!redirectUrl) {
                reloadTargetWindow();
                return;
            }

            try {
                var currentUrl = new URL(getComparableLocationHref());
                var targetUrl = new URL(redirectUrl, currentUrl.href);

                if (normalizeComparableUrl(currentUrl) !== normalizeComparableUrl(targetUrl)) {
                    redirectTargetWindow(targetUrl.href);
                    return;
                }
            } catch (error) {
                redirectTargetWindow(redirectUrl);
                return;
            }

            reloadTargetWindow();
        }

        cancelButton.addEventListener('click', function () {
            closeModal();
        });

        if (shortnameInput) {
            shortnameInput.addEventListener('input', updateShortnameHint);
            shortnameInput.addEventListener('change', updateShortnameHint);
            updateShortnameHint();
        }

        function initializeLocationPicker() {
            var mapElement = document.getElementById('organization-create-location-map');
            var latInput = document.getElementById('latlong_lat');
            var longInput = document.getElementById('latlong_long');
            var initialLat;
            var initialLng;
            var map;
            var marker = null;

            if (!mapElement || !latInput || !longInput || typeof window.L === 'undefined' || mapElement.dataset.leafletReady === '1') {
                return;
            }

            mapElement.dataset.leafletReady = '1';
            initialLat = parseFloat(latInput.value);
            initialLng = parseFloat(longInput.value);
            if (Number.isNaN(initialLat) || Number.isNaN(initialLng)) {
                initialLat = null;
                initialLng = null;
            }

            map = window.L.map(mapElement).setView(
                initialLat === null ? [46.8182, 8.2275] : [initialLat, initialLng],
                initialLat === null ? 7 : 13
            );

            if (typeof window.commonBindLeafletTheme === 'function') {
                window.commonBindLeafletTheme(map, { layer: null, theme: null });
            }

            function updateMarker(lat, lng, centerMap) {
                if (Number.isNaN(lat) || Number.isNaN(lng)) {
                    return;
                }
                if (marker) {
                    marker.setLatLng([lat, lng]);
                } else {
                    marker = window.L.circleMarker([lat, lng], {
                        radius: 9,
                        color: '#0f766e',
                        weight: 2,
                        fillColor: '#14b8a6',
                        fillOpacity: 0.85
                    }).addTo(map);
                }
                if (centerMap) {
                    map.setView([lat, lng], Math.max(map.getZoom(), 13));
                }
            }

            function setCoordinates(lat, lng, centerMap) {
                latInput.value = Number(lat).toFixed(6);
                longInput.value = Number(lng).toFixed(6);
                updateMarker(Number(lat), Number(lng), centerMap);
            }

            function syncInputs() {
                var lat = parseFloat(latInput.value);
                var lng = parseFloat(longInput.value);
                updateMarker(lat, lng, true);
            }

            map.on('click', function (event) {
                setCoordinates(event.latlng.lat, event.latlng.lng, true);
            });
            latInput.addEventListener('change', syncInputs);
            longInput.addEventListener('change', syncInputs);
            if (initialLat !== null) {
                updateMarker(initialLat, initialLng, false);
            }
            window.setTimeout(function () { map.invalidateSize(); }, 0);
            window.setTimeout(function () { map.invalidateSize(); }, 250);
        }

        if (typeof window.commonWhenLeafletReady === 'function') {
            window.commonWhenLeafletReady(initializeLocationPicker);
        } else {
            initializeLocationPicker();
        }

        function initializeImageEditor(editor) {
            var field = editor.getAttribute('data-organization-image-editor');
            var crop = editor.querySelector('[data-organization-image-crop]');
            var fileInput = editor.querySelector('[data-organization-image-file]');
            var chooseButton = editor.querySelector('[data-organization-image-choose]');
            var zoomInput = editor.querySelector('[data-organization-image-zoom]');
            var valueInput = document.getElementById(field);
            var image = crop ? crop.querySelector('img') : null;
            var naturalWidth = 0;
            var naturalHeight = 0;
            var baseScale = 1;
            var scale = 1;
            var positionX = 0;
            var positionY = 0;
            var exportMime = 'image/jpeg';
            var pointerStart = null;

            if (!field || !crop || !fileInput || !chooseButton || !zoomInput || !valueInput) {
                return;
            }

            function cropWidth() {
                return crop.clientWidth;
            }

            function cropHeight() {
                return crop.clientHeight;
            }

            function clampPosition() {
                var imageWidth = naturalWidth * scale;
                var imageHeight = naturalHeight * scale;
                positionX = Math.min(0, Math.max(cropWidth() - imageWidth, positionX));
                positionY = Math.min(0, Math.max(cropHeight() - imageHeight, positionY));
            }

            function renderImage() {
                if (!image || !naturalWidth || !naturalHeight) {
                    return;
                }
                clampPosition();
                image.style.width = (naturalWidth * scale) + 'px';
                image.style.height = (naturalHeight * scale) + 'px';
                image.style.left = positionX + 'px';
                image.style.top = positionY + 'px';
            }

            function saveCrop() {
                var sourceX;
                var sourceY;
                var sourceWidth;
                var sourceHeight;
                var canvas;
                var context;
                var outputWidth;
                var outputHeight;

                if (!image || !naturalWidth || !naturalHeight || !window.HTMLCanvasElement) {
                    return;
                }

                sourceX = -positionX / scale;
                sourceY = -positionY / scale;
                sourceWidth = cropWidth() / scale;
                sourceHeight = cropHeight() / scale;
                outputWidth = parseInt(crop.getAttribute('data-output-width'), 10) || cropWidth();
                outputHeight = parseInt(crop.getAttribute('data-output-height'), 10) || cropHeight();
                canvas = document.createElement('canvas');
                canvas.width = outputWidth;
                canvas.height = outputHeight;
                context = canvas.getContext('2d');
                context.drawImage(image, sourceX, sourceY, sourceWidth, sourceHeight, 0, 0, outputWidth, outputHeight);
                canvas.toBlob(function (blob) {
                    if (!blob) {
                        return;
                    }
                    window.croppedImages = window.croppedImages || {};
                    window.croppedImages[field] = blob;
                    valueInput.value = 'newimage';
                }, exportMime, exportMime === 'image/png' ? undefined : 0.9);
            }

            function resetImageLayout(retryCount, onReady) {
                if (!image || !image.naturalWidth || !image.naturalHeight) {
                    return;
                }
                if (!cropWidth() || !cropHeight()) {
                    if ((retryCount || 0) < 20) {
                        window.setTimeout(function () {
                            resetImageLayout((retryCount || 0) + 1, onReady);
                        }, 50);
                    }
                    return;
                }
                naturalWidth = image.naturalWidth;
                naturalHeight = image.naturalHeight;
                baseScale = Math.max(cropWidth() / naturalWidth, cropHeight() / naturalHeight);
                scale = baseScale;
                positionX = (cropWidth() - (naturalWidth * scale)) / 2;
                positionY = (cropHeight() - (naturalHeight * scale)) / 2;
                zoomInput.value = '0';
                renderImage();
                if (typeof onReady === 'function') {
                    onReady();
                }
            }

            function setImageSource(source, mimeType, shouldSave) {
                if (!image) {
                    image = document.createElement('img');
                    image.setAttribute('data-organization-image-preview', field);
                    crop.appendChild(image);
                }
                exportMime = mimeType === 'image/png' ? 'image/png' : 'image/jpeg';
                image.onload = function () {
                    resetImageLayout(0, shouldSave ? saveCrop : null);
                };
                image.src = source;
            }

            chooseButton.addEventListener('click', function () {
                fileInput.click();
            });

            fileInput.addEventListener('change', function () {
                var file = fileInput.files && fileInput.files.length ? fileInput.files[0] : null;
                var reader;
                if (!file) {
                    return;
                }
                reader = new FileReader();
                reader.onload = function (event) {
                    setImageSource(event.target.result, file.type, true);
                };
                reader.readAsDataURL(file);
            });

            zoomInput.addEventListener('input', function () {
                var oldScale = scale;
                var centerX;
                var centerY;
                if (!naturalWidth || !naturalHeight) {
                    return;
                }
                centerX = (cropWidth() / 2 - positionX) / oldScale;
                centerY = (cropHeight() / 2 - positionY) / oldScale;
                scale = baseScale * (1 + (parseInt(zoomInput.value, 10) || 0) / 100 * 3);
                positionX = cropWidth() / 2 - centerX * scale;
                positionY = cropHeight() / 2 - centerY * scale;
                renderImage();
                saveCrop();
            });

            crop.addEventListener('pointerdown', function (event) {
                if (!image || !naturalWidth || !naturalHeight) {
                    return;
                }
                pointerStart = {
                    id: event.pointerId,
                    x: event.clientX,
                    y: event.clientY,
                    positionX: positionX,
                    positionY: positionY
                };
                crop.setPointerCapture(event.pointerId);
            });

            crop.addEventListener('pointermove', function (event) {
                if (!pointerStart || pointerStart.id !== event.pointerId) {
                    return;
                }
                positionX = pointerStart.positionX + event.clientX - pointerStart.x;
                positionY = pointerStart.positionY + event.clientY - pointerStart.y;
                renderImage();
            });

            function finishImageMove(event) {
                if (!pointerStart || pointerStart.id !== event.pointerId) {
                    return;
                }
                pointerStart = null;
                saveCrop();
            }

            crop.addEventListener('pointerup', finishImageMove);
            crop.addEventListener('pointercancel', finishImageMove);

            if (image) {
                setImageSource(image.currentSrc || image.src, /\.png(?:$|\?)/i.test(image.currentSrc || image.src) ? 'image/png' : 'image/jpeg', false);
            }
        }

        Array.prototype.forEach.call(document.querySelectorAll('[data-organization-image-editor]'), initializeImageEditor);

        function submitOrganization() {
            var shouldRefreshApplication;

            if (!form) {
                setFeedback("Le formulaire n'est pas disponible.", true);
                return;
            }

            if (isEditMode && getFormSnapshot() === initialFormSnapshot) {
                closeModal();
                return;
            }

            shouldRefreshApplication = !isEditMode || getApplicationSnapshot() !== initialApplicationSnapshot;

            submitButton.disabled = true;
            setFeedback(<?= json_encode($pendingLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, false);

            var formData = new FormData(form);
            if (isEditMode && organizationId > 0) {
                formData.set('id', String(organizationId));
            }

            if (window.croppedImages) {
                Object.keys(window.croppedImages).forEach(function (key) {
                    var blob = window.croppedImages[key];

                    if (blob) {
                        var extension = 'jpg';

                        if (blob.type === 'image/png') {
                            extension = 'png';
                        }

                        formData.append(key, blob, key + '.' + extension);
                    }
                });
            }

            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                .then(function (response) {
                    return response.text().then(function (text) {
                        var data = null;

                        try {
                            data = JSON.parse(text);
                        } catch (error) {
                            data = null;
                        }

                        return {
                            ok: response.ok,
                            data: data
                        };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data || result.data.success !== true) {
                        throw new Error(result.data && result.data.message ? result.data.message : <?= json_encode($errorLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
                    }

                    setFeedback(result.data.message || <?= json_encode($successLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, false);

                    window.setTimeout(function () {
                        handleSuccessfulSave(result.data, shouldRefreshApplication);
                    }, 250);
                })
                .catch(function (error) {
                    setFeedback(error && error.message ? error.message : <?= json_encode($errorLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, true);
                    submitButton.disabled = false;
                });
        }

        submitButton.addEventListener('click', submitOrganization);
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitOrganization();
        });
    })();
</script>

<?php if (!$isFetchRequest) { ?>
</body>
</html>
<?php } ?>
