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

$organization = new \dbObject\Organization();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une organisation</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
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
            padding: 18px;
            border-radius: 18px;
            border: 1px solid #dbe4ee;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .organization-create-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 18px;
        }

        .organization-create-actions button {
            min-height: 44px;
            padding: 10px 18px;
            border: 0;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .organization-create-actions__cancel {
            background: #e2e8f0;
            color: #0f172a;
        }

        .organization-create-actions__submit {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.18);
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
    </style>
</head>
<body>
    <div class="organization-create-shell">
        <div class="organization-create-hero">
            <div class="organization-create-kicker">Nouvelle organisation</div>
            <h1>Créer un nouvel espace OMO</h1>
            <p>Renseignez les informations principales de l'organisation. Le formulaire utilise le canvas d'administration standard afin de garder le même comportement pour le logo, la bannière et les autres champs éditables.</p>
        </div>

        <div class="organization-create-card">
<?php
            $params = array(
                "buttons" => false,
                "fields" => array(
                    "{title:Informations principales}",
                    "name",
                    "shortname",
                    "domain",
                    "color",
                    "{title:Identité visuelle}",
                    "logo",
                    "banner",
                ),
            );
            $organization->display("adminEdit.php", $params);
?>

            <div class="organization-create-actions">
                <button type="button" class="organization-create-actions__cancel" id="organization_create_cancel">Annuler</button>
                <button type="button" class="organization-create-actions__submit" id="organization_create_submit">Créer l'organisation</button>
            </div>

            <div class="organization-create-feedback" id="organization_create_feedback"></div>
        </div>
    </div>

    <script>
        (function () {
            var submitButton = document.getElementById('organization_create_submit');
            var cancelButton = document.getElementById('organization_create_cancel');
            var form = document.getElementById('formulaire-edit');
            var feedback = document.getElementById('organization_create_feedback');

            if (form) {
                form.setAttribute('action', '/ajax/saveorganization.php');
                form.setAttribute('method', 'post');
                form.setAttribute('enctype', 'multipart/form-data');
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

            cancelButton.addEventListener('click', function () {
                closeModal();
            });

            submitButton.addEventListener('click', function () {
                if (!form) {
                    setFeedback("Le formulaire n'est pas disponible.", true);
                    return;
                }

                submitButton.disabled = true;
                setFeedback('Création en cours...', false);

                var formData = new FormData(form);

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
                            throw new Error(result.data && result.data.message ? result.data.message : "Impossible de créer l'organisation.");
                        }

                        setFeedback(result.data.message || 'Organisation créée.', false);

                        window.setTimeout(function () {
                            closeModal();
                            redirectParent(result.data.redirect || '');
                        }, 250);
                    })
                    .catch(function (error) {
                        setFeedback(error && error.message ? error.message : "Impossible de créer l'organisation.", true);
                        submitButton.disabled = false;
                    });
            });
        })();
    </script>
</body>
</html>
