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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/common/assets/auth.css">
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #f8fafc;
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
        }

        .organization-create-shell {
            display: flex;
            flex-direction: column;
            gap: 18px;
            max-width: 980px;
            margin: 0 auto;
        }

        .organization-create-hero {
            padding: 22px;
            border-radius: 18px;
            border: 1px solid #dbe4ee;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, 0.18), transparent 38%),
                linear-gradient(135deg, #ffffff, #f8fafc);
        }

        .organization-create-kicker {
            margin-bottom: 6px;
        }

        .organization-create-hero h1 {
            margin: 0;
            font-size: 30px;
            line-height: 1.1;
        }

        .organization-create-hero p {
            margin: 10px 0 0;
            max-width: 720px;
            line-height: 1.5;
            color: #475569;
        }

        .organization-create-card {
            --generic-section-padding-block: 18px;
            --generic-section-padding-inline: 18px;
            --generic-section-border: #dbe4ee;
            --generic-section-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .organization-create-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 18px;
        }

        .organization-create-feedback {
            display: none;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #dbe4ee;
            background: #f8fafc;
            color: #475569;
        }

        .organization-create-feedback.is-error {
            display: block;
            color: #b91c1c;
            border-color: rgba(220, 38, 38, 0.18);
            background: rgba(220, 38, 38, 0.06);
        }

        .organization-create-feedback.is-success {
            display: block;
            color: #166534;
            border-color: rgba(22, 163, 74, 0.18);
            background: rgba(22, 163, 74, 0.06);
        }

        .organization-create-shortname-hint {
            margin-top: 8px;
            font-size: 13px;
            line-height: 1.45;
            color: #475569;
        }

        .organization-create-shortname-hint code {
            display: inline-block;
            margin-top: 4px;
            padding: 3px 8px;
            border-radius: 999px;
            background: #eef4ff;
            color: #1d4ed8;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="organization-create-shell">
        <div class="organization-create-hero">
            <div class="organization-create-kicker generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars($heroKicker, ENT_QUOTES, 'UTF-8') ?></div>
            <h1><?= htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars($heroText, ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="organization-create-card generic-section">
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
        </div>
    </div>

    <script>
        (function () {
            var isEditMode = <?= $isEditMode ? 'true' : 'false' ?>;
            var organizationId = <?= (int)$organization->getId() ?>;
            var shortnamePreviewScheme = <?= json_encode($shortnamePreviewScheme, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            var shortnamePreviewHost = <?= json_encode($shortnamePreviewHost, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            var shortnamePreviewPath = <?= json_encode($shortnamePreviewPath, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            var submitButton = document.getElementById('organization_create_submit');
            var cancelButton = document.getElementById('organization_create_cancel');
            var form = document.getElementById('formulaire-edit');
            var feedback = document.getElementById('organization_create_feedback');
            var shortnameInput = document.getElementById('shortname') || document.querySelector('input[name="shortname"]');

            if (form) {
                form.setAttribute('action', isEditMode ? ('/ajax/saveorganization.php?oid=' + encodeURIComponent(String(organizationId))) : '/ajax/saveorganization.php');
                form.setAttribute('method', 'post');
                form.setAttribute('enctype', 'multipart/form-data');
            }

            function buildShortnamePreviewUrl(value) {
                var normalizedValue = String(value || '').trim().toLowerCase();
                if (!normalizedValue || !shortnamePreviewHost) {
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
                    hint.innerHTML = "Ce nom court sera utilise dans l'URL de base du site :<br><code>" + previewUrl + "</code>";
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
                if (window.parent && window.parent !== window && typeof window.parent.commonTopbarCloseModal === 'function') {
                    window.parent.commonTopbarCloseModal();
                    return;
                }

                if (window.parent && window.parent !== window) {
                    var directoryModal = window.parent.document.getElementById('omoDirectoryModal');
                    if (directoryModal) {
                        directoryModal.hidden = true;
                        if (window.parent.document.body) {
                            window.parent.document.body.classList.remove('omo-directory-modal-open');
                        }
                        return;
                    }
                }

                window.close();
            }

            function redirectParent(url) {
                if (!url) {
                    return;
                }

                if (window.parent && window.parent !== window) {
                    window.parent.location.href = url;
                    return;
                }

                window.location.href = url;
            }

            function reloadParentWindow() {
                if (window.parent && window.parent !== window) {
                    window.parent.location.reload();
                    return;
                }

                window.location.reload();
            }

            function getParentLocationHref() {
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
                        redirectParent(redirectUrl);
                        return;
                    }

                    closeModal();
                    return;
                }

                if (!redirectUrl) {
                    reloadParentWindow();
                    return;
                }

                try {
                    var currentUrl = new URL(getParentLocationHref());
                    var targetUrl = new URL(redirectUrl, currentUrl.href);

                    if (normalizeComparableUrl(currentUrl) !== normalizeComparableUrl(targetUrl)) {
                        redirectParent(targetUrl.href);
                        return;
                    }
                } catch (error) {
                    redirectParent(redirectUrl);
                    return;
                }

                reloadParentWindow();
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
</body>
</html>
