var omoUiText = window.omoUiText || function (key, fallback) { return fallback; };
var omoUiHtml = window.omoUiHtml || function (key, fallback) { return fallback; };
function omoGetOrganizationSetupRoute() {
    if (typeof parseUrl === 'function') {
        return parseUrl();
    }

    return {
        oid: window.omoConfig && window.omoConfig.oid ? Number(window.omoConfig.oid) : null,
        cid: null,
        hash: window.location.hash ? window.location.hash.replace('#', '') : null
    };
}

function omoReloadOrganizationPanels(oid) {
    const targetOid = Number(oid || 0);
    if (!targetOid || typeof loadContent !== 'function') {
        return;
    }

    loadContent(typeof omoGetLeftPanelContentSelector === 'function' ? omoGetLeftPanelContentSelector() : '#panel-left', 'api/getOrg.php?oid=' + targetOid);

    if (typeof window.omoResetMainRightPanel === 'function') {
        window.omoResetMainRightPanel();
    } else {
        $('#panel-right').empty();
    }

    const route = omoGetOrganizationSetupRoute();
    let drawerUrl = 'api/getStructure.php?drawer=1&oid=' + targetOid;

    if (route && route.cid) {
        drawerUrl += '&cid=' + encodeURIComponent(route.cid);
    }

    if (typeof refreshDrawer === 'function' && refreshDrawer('drawer_structure', drawerUrl)) {
        return;
    }

    if (typeof openDrawer === 'function') {
        openDrawer('drawer_structure', drawerUrl);
    }
}

window.omoReloadOrganizationPanels = omoReloadOrganizationPanels;

$(document)
  .off('click.omoOrgSetup', '[data-omo-org-setup="1"] [data-omo-org-init-button="1"]')
  .on('click.omoOrgSetup', '[data-omo-org-setup="1"] [data-omo-org-init-button="1"]', function () {
    const button = $(this);
    const panel = button.closest('[data-omo-org-setup="1"]');
    const feedback = panel.find('[data-omo-org-init-feedback="1"]').first();
    const templateId = Number(button.data('template-id') || 0);
    const organizationId = Number(panel.data('organization-id') || 0);

    if (!organizationId) {
        return;
    }

    panel.find('[data-omo-org-init-button="1"]').prop('disabled', true);
    feedback.prop('hidden', false).removeClass('is-error').text(omoUiText("initialisation_en_cours", "Initialisation en cours..."));

    fetch('/omo/api/organizations/initialize.php', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            templateId: templateId
        })
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
            throw new Error(result.data && result.data.message ? result.data.message : omoUiText("impossible_d_initialiser_l_organisation", "Impossible d'initialiser l'organisation."));
        }

        feedback.removeClass('is-error').text(result.data.message || omoUiText("organisation_initialisee", "Organisation initialisée."));

        const route = omoGetOrganizationSetupRoute();
        omoReloadOrganizationPanels(route.oid || organizationId);
    })
    .catch(function (error) {
        feedback.addClass('is-error').text(error && error.message ? error.message : omoUiText("impossible_d_initialiser_l_organisation", "Impossible d'initialiser l'organisation."));
    })
  .finally(function () {
        panel.find('[data-omo-org-init-button="1"]').prop('disabled', false);
    });
  });

$(document)
  .off('click.omoOrgImport', '[data-omo-org-setup="1"] [data-omo-org-import-button="1"]')
  .on('click.omoOrgImport', '[data-omo-org-setup="1"] [data-omo-org-import-button="1"]', function () {
    const button = $(this);
    const panel = button.closest('[data-omo-org-setup="1"]');
    const organizationId = Number(panel.data('organization-id') || 0);

    if (!organizationId || typeof window.commonTopbarOpenModal !== 'function') {
        return;
    }

    let popupUrl = '/omo/api/organizations/import_popup.php?oid=' + encodeURIComponent(organizationId);

    if (typeof window.omoResolveAppUrl === 'function') {
        popupUrl = window.omoResolveAppUrl(popupUrl);
    }

    window.commonTopbarOpenModal(omoUiText("importer_une_organisation", "Importer une organisation"), popupUrl, 'fetch');
  });
