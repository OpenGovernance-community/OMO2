<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/config.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/shared_functions.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/common/auth.php");

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
$heroKicker = $isEditMode ? "Parametres de l'organisation" : "Nouvelle organisation";
$heroTitle = $isEditMode ? "Modifier cet espace OMO" : "Creer un nouvel espace OMO";
$heroText = $isEditMode
    ? "Mettez a jour le nom, le nom court, le domaine, les illustrations et la couleur de cette organisation."
    : "Renseignez les informations principales de l'organisation. Le formulaire reutilise le canvas d'administration standard pour le logo, la banniere et les autres champs editables.";
$submitLabel = $isEditMode ? "Enregistrer les modifications" : "Creer l'organisation";
$pendingLabel = $isEditMode ? "Enregistrement en cours..." : "Creation en cours...";
$successLabel = $isEditMode ? "Organisation enregistree." : "Organisation creee.";
$errorLabel = $isEditMode ? "Impossible d'enregistrer l'organisation." : "Impossible de creer l'organisation.";
$shortnamePreviewScheme = commonGetRequestScheme();
$shortnamePreviewHost = commonGetRootHost();
$shortnamePreviewPath = '/omo/';
$organizationSubdomainRoutingEnabled = commonUseOrganizationSubdomains();
$isFetchRequest = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
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
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="organization-create-page">
<?php } ?>

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

    .organization-create-hero {
        gap: 10px;
    }

    .organization-create-kicker {
        margin-bottom: 0;
    }

    .organization-create-hero h1 {
        margin: 0;
        font-size: clamp(28px, 4vw, 34px);
        line-height: 1.08;
        color: var(--color-text, #0f172a);
    }

    .organization-create-hero p {
        margin: 0;
        max-width: 720px;
        line-height: 1.55;
        color: var(--color-text-light, #475569);
    }

    .organization-create-card {
        --generic-section-padding-block: 18px;
        --generic-section-padding-inline: 18px;
        --generic-section-border: var(--color-border, #dbe4ee);
        --generic-section-radius: 18px;
        --generic-section-background: var(--color-surface, #ffffff);
        --generic-section-shadow: var(--shadow-sm, none);
        --generic-section-gap: 18px;
    }

    .organization-create-card .admin-edit__panel {
        padding: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    .organization-create-card .admin-edit__toolbar {
        display: none;
    }

    .organization-create-card .admin-edit__control,
    .organization-create-card #formulaire-edit select,
    .organization-create-card #formulaire-edit textarea,
    .organization-create-card #formulaire-edit input[type='text'],
    .organization-create-card #formulaire-edit input[type='email'],
    .organization-create-card #formulaire-edit input[type='password'],
    .organization-create-card #formulaire-edit input[type='number'],
    .organization-create-card #formulaire-edit input[type='date'],
    .organization-create-card #formulaire-edit input[type='time'],
    .organization-create-card #formulaire-edit input[type='datetime-local'] {
        --generic-form-control-border: var(--color-border, #d1d5db);
        --generic-form-control-background: var(--color-surface-alt, #f8fafc);
        --generic-form-control-background-focus: var(--color-surface, #ffffff);
        --generic-form-control-color: var(--color-text, #0f172a);
    }

    .organization-create-card table.dbobjecttable th {
        color: var(--color-text, #0f172a);
    }

    .organization-create-card .char_count,
    .organization-create-card .admin-edit__latlong-help {
        color: var(--color-text-light, #64748b);
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

    #commonTopbarModalBody .organization-create-view {
        max-width: none;
    }

    @media (max-width: 860px) {
        .organization-create-page {
            padding: 14px;
        }

        .organization-create-view {
            gap: 14px;
        }

        .organization-create-card {
            --generic-section-padding-block: 14px;
            --generic-section-padding-inline: 14px;
        }

        .organization-create-actions {
            justify-content: stretch;
        }

        .organization-create-actions .generic-action-button {
            flex: 1 1 220px;
        }
    }
</style>

<div class="organization-create-view" id="organizationCreateRoot" data-render-mode="<?= $isFetchRequest ? 'fetch' : 'document' ?>">
    <section class="organization-create-hero generic-hero-panel generic-hero-panel--accent">
        <div class="organization-create-kicker generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars($heroKicker, ENT_QUOTES, 'UTF-8') ?></div>
        <h1><?= htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($heroText, ENT_QUOTES, 'UTF-8') ?></p>
    </section>

    <section class="organization-create-card generic-section generic-section--stack">
<?php
        $params = array(
            "buttons" => false,
            "fields" => array(
                "{title:Informations principales}",
                "name",
                "shortname",
                "domain",
                "color",
                "{title:Identite visuelle}",
                "logo",
                "banner",
            ),
        );
        $organization->display("adminEdit.php", $params);
?>

        <div class="organization-create-actions">
            <button type="button" class="generic-action-button generic-action-button--secondary" id="organization_create_cancel">Annuler</button>
            <button type="button" class="generic-action-button generic-action-button--main" id="organization_create_submit"><?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') ?></button>
        </div>

        <div class="organization-create-feedback" id="organization_create_feedback"></div>
    </section>
</div>

<script>
    (function () {
        var root = document.getElementById('organizationCreateRoot');
        var isEditMode = <?= $isEditMode ? 'true' : 'false' ?>;
        var organizationId = <?= (int)$organization->getId() ?>;
        var shortnamePreviewScheme = <?= json_encode($shortnamePreviewScheme, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var shortnamePreviewHost = <?= json_encode($shortnamePreviewHost, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var shortnamePreviewPath = <?= json_encode($shortnamePreviewPath, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var organizationSubdomainRoutingEnabled = <?= $organizationSubdomainRoutingEnabled ? 'true' : 'false' ?>;
        var submitButton = document.getElementById('organization_create_submit');
        var cancelButton = document.getElementById('organization_create_cancel');
        var form = document.getElementById('formulaire-edit');
        var feedback = document.getElementById('organization_create_feedback');
        var shortnameInput = document.getElementById('shortname') || document.querySelector('input[name="shortname"]');

        if (!root || !submitButton || !cancelButton || !feedback) {
            return;
        }

        function decorateAdminEditForm() {
            if (!form) {
                return;
            }

            Array.prototype.forEach.call(
                form.querySelectorAll('input, select, textarea'),
                function (field) {
                    var type = String(field.type || '').toLowerCase();

                    if (type === 'hidden' || type === 'checkbox' || type === 'radio' || type === 'button' || type === 'submit' || type === 'color' || type === 'file' || type === 'range') {
                        return;
                    }

                    field.classList.add('generic-form-control');
                }
            );
        }

        if (form) {
            form.setAttribute('action', isEditMode ? ('/ajax/saveorganization.php?oid=' + encodeURIComponent(String(organizationId))) : '/ajax/saveorganization.php');
            form.setAttribute('method', 'post');
            form.setAttribute('enctype', 'multipart/form-data');
            decorateAdminEditForm();
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

            var shortnameRow = document.getElementById('row_shortname');
            var shortnameCell = shortnameRow ? shortnameRow.querySelector('td') : null;
            var hint = document.createElement('div');
            hint.id = 'organization_create_shortname_hint';
            hint.className = 'organization-create-shortname-hint';
            if (shortnameCell) {
                shortnameCell.appendChild(hint);
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
            feedback.textContent = message || '';
            feedback.className = 'organization-create-feedback' + (message ? (isError ? ' is-error' : ' is-success') : '');
            feedback.style.display = message ? 'block' : 'none';
        }

        function closeModal() {
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

        function handleSuccessfulSave(result) {
            var redirectUrl = result && result.redirect ? String(result.redirect) : '';

            if (!isEditMode) {
                if (redirectUrl) {
                    redirectTargetWindow(redirectUrl);
                    return;
                }

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

        submitButton.addEventListener('click', function () {
            if (!form) {
                setFeedback("Le formulaire n'est pas disponible.", true);
                return;
            }

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
                        formData.append(key, blob, key + '.jpg');
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
                        handleSuccessfulSave(result.data);
                    }, 250);
                })
                .catch(function (error) {
                    setFeedback(error && error.message ? error.message : <?= json_encode($errorLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, true);
                    submitButton.disabled = false;
                });
        });
    })();
</script>

<?php if (!$isFetchRequest) { ?>
</body>
</html>
<?php } ?>
