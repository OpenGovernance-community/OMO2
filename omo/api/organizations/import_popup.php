<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$organization = new \dbObject\Organization();

if (
    $organizationId <= 0
    || !$organization->load($organizationId)
    || (int)commonGetCurrentUserId() <= 0
    || !commonCurrentUserHasOrganizationAccess($organizationId)
) {
    http_response_code(403);
    ?>
    <div class="omo-import-popup__feedback omo-import-popup__feedback--error generic-drawer-content">Acces refuse.</div>
    <?php
    exit;
}
?>
<div class="omo-import-popup generic-drawer-content" data-omo-org-import-popup="1" data-organization-id="<?= (int)$organizationId ?>">
    <div class="omo-import-popup__hero generic-hero-panel accent">
        <div class="omo-import-popup__kicker generic-card-title generic-card-title--eyebrow">Import JSON</div>
        <h3 class="omo-import-popup__title generic-card-title generic-card-title--large">Importer une organisation</h3>
        <p class="omo-import-popup__text generic-description">Selectionnez un fichier JSON exporte depuis le menu structure. L'import reconstruit les holons, roles, proprietes et les references internes du sous-arbre.</p>
    </div>

    <form class="omo-import-popup__form" data-omo-org-import-form="1" enctype="multipart/form-data">
        <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">

        <label class="omo-import-popup__field">
            <span class="omo-import-popup__label generic-form-label">Fichier JSON</span>
            <input
                type="file"
                name="structure_file"
                class="omo-import-popup__input generic-form-control"
                accept=".json,application/json"
                required
            >
        </label>

        <div class="omo-import-popup__hint generic-help-text">
            Conseil: creez d'abord une organisation vide, puis importez un fichier genere par `Export` depuis la vue structure.
        </div>

        <div class="omo-import-popup__actions">
            <button type="button" class="omo-import-popup__button generic-action-button generic-action-button--secondary" data-omo-org-import-cancel="1">Annuler</button>
            <button type="submit" class="omo-import-popup__button generic-action-button generic-action-button--main">Importer</button>
        </div>
    </form>

    <div class="omo-import-popup__feedback generic-soft-panel" data-omo-org-import-feedback="1" hidden></div>
</div>

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/organizations/import.css') ?>">

<script>
(function () {
    const root = document.querySelector('[data-omo-org-import-popup="1"]');
    if (!root) {
        return;
    }

    const form = root.querySelector('[data-omo-org-import-form="1"]');
    const feedback = root.querySelector('[data-omo-org-import-feedback="1"]');
    const cancelButton = root.querySelector('[data-omo-org-import-cancel="1"]');
    const submitButton = form ? form.querySelector('button[type="submit"]') : null;
    const organizationId = Number(root.getAttribute('data-organization-id') || 0);

    function setFeedback(message, isError) {
        if (!feedback) {
            return;
        }

        feedback.hidden = !message;
        feedback.textContent = message || '';
        feedback.className = 'omo-import-popup__feedback' + (isError ? ' omo-import-popup__feedback--error' : '');
    }

    function closeModal() {
        if (typeof window.commonTopbarCloseModal === 'function') {
            window.commonTopbarCloseModal();
        }
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            closeModal();
        });
    }

    if (!form) {
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!submitButton) {
            return;
        }

        submitButton.disabled = true;
        setFeedback('Import en cours...', false);

        const formData = new FormData(form);

        fetch('/omo/api/organizations/import.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (data) {
                return {
                    ok: response.ok,
                    data: data
                };
            });
        })
        .then(function (result) {
            if (!result.ok || !result.data || result.data.status !== 'ok') {
                throw new Error(result.data && result.data.message ? result.data.message : "Impossible d'importer l'organisation.");
            }

            setFeedback(result.data.message || "L'organisation a ete importee.", false);

            if (typeof window.omoReloadOrganizationPanels === 'function' && organizationId > 0) {
                window.omoReloadOrganizationPanels(organizationId);
            }

            window.setTimeout(function () {
                closeModal();
            }, 250);
        })
        .catch(function (error) {
            setFeedback(error && error.message ? error.message : "Impossible d'importer l'organisation.", true);
        })
        .finally(function () {
            submitButton.disabled = false;
        });
    });
})();
</script>
