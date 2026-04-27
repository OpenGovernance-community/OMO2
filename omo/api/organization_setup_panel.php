<?php

if (!function_exists('omoSetupEscape')) {
    function omoSetupEscape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('omoRenderOrganizationSetupPanel')) {
    function omoRenderOrganizationSetupPanel(\dbObject\Organization $organization)
    {
        $setupData = $organization->getStructuralInitializationData();
        $organizationName = trim((string)($setupData['organizationName'] ?? ''));
        $organizationColor = trim((string)$organization->get('color'));

        if ($organizationName === '') {
            $organizationName = 'Cette organisation';
        }

        if ($organizationColor === '') {
            $organizationColor = 'var(--color-primary, #2563eb)';
        }
        ?>
<div
    class="omo-setup-panel"
    data-omo-org-setup="1"
    data-organization-id="<?= (int)($setupData['organizationId'] ?? 0) ?>"
>
    <div class="omo-setup-panel__hero">
        <div class="omo-setup-panel__kicker">Organisation à initialiser</div>
        <h2 class="omo-setup-panel__title"><?= omoSetupEscape($organizationName) ?></h2>
        <p class="omo-setup-panel__intro">Cette organisation n'a pas encore de holon racine. Choisissez un point de départ pour créer sa structure.</p>
    </div>

    <div class="omo-setup-panel__section">
        <div class="omo-setup-panel__section-title">Choisissez un point de départ</div>
        <div class="omo-setup-card-grid">
            <button
                type="button"
                class="omo-setup-card omo-setup-card--primary"
                data-omo-org-init-button="1"
                data-template-id="0"
            >
                <span class="omo-setup-card__media" style="background: <?= omoSetupEscape($organizationColor) ?>;">
                    <span class="omo-setup-card__badge">Structure vide</span>
                </span>
                <span class="omo-setup-card__content">
                    <span class="omo-setup-card__title">Créer à partir de rien</span>
                    <span class="omo-setup-card__text">Crée uniquement le holon racine de type organisation, sans cercle ni rôle.</span>
                    <span class="omo-setup-card__cta">Créer l'organisation</span>
                </span>
            </button>

            <?php foreach (($setupData['templates'] ?? array()) as $template): ?>
                <?php
                $templateColor = trim((string)($template['color'] ?? ''));
                if ($templateColor === '') {
                    $templateColor = 'linear-gradient(135deg, #dbeafe, #bfdbfe)';
                }
                ?>
                <button
                    type="button"
                    class="omo-setup-card"
                    data-omo-org-init-button="1"
                    data-template-id="<?= (int)($template['id'] ?? 0) ?>"
                >
                    <span class="omo-setup-card__media" style="background: <?= omoSetupEscape($templateColor) ?>;">
                        <span class="omo-setup-card__badge">Modèle</span>
                    </span>
                    <span class="omo-setup-card__content">
                        <span class="omo-setup-card__title"><?= omoSetupEscape($template['name'] ?? 'Modèle') ?></span>
                        <span class="omo-setup-card__text">
                            <?php if (!empty($template['sourceOrganizationName'])): ?>
                                Inspiré de <?= omoSetupEscape($template['sourceOrganizationName']) ?>.
                            <?php else: ?>
                                Duplique la structure de ce modèle d'organisation.
                            <?php endif; ?>
                        </span>
                        <span class="omo-setup-card__cta">Utiliser ce modèle</span>
                    </span>
                </button>
            <?php endforeach; ?>
        </div>

        <?php if (count($setupData['templates'] ?? array()) === 0): ?>
            <div class="omo-setup-panel__empty">Aucun modèle d'organisation n'est disponible pour le moment.</div>
        <?php endif; ?>
    </div>

    <div class="omo-setup-panel__feedback" data-omo-org-init-feedback="1" hidden></div>
</div>

<style>
.omo-setup-panel {
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding: 24px;
    color: var(--color-text, #1f2937);
}

.omo-setup-panel__hero {
    padding: 22px;
    border-radius: 18px;
    background:
        radial-gradient(circle at top right, color-mix(in srgb, var(--color-primary, #2563eb) 18%, transparent), transparent 45%),
        linear-gradient(135deg, color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface, #fff)), var(--color-surface, #fff));
    border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 16%, var(--color-border, #d1d5db));
}

.omo-setup-panel__kicker {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--color-text-light, #6b7280);
    margin-bottom: 6px;
}

.omo-setup-panel__title {
    margin: 0;
    font-size: 28px;
    line-height: 1.1;
}

.omo-setup-panel__intro {
    margin: 10px 0 0;
    max-width: 720px;
    line-height: 1.5;
    color: var(--color-text-light, #6b7280);
}

.omo-setup-panel__section {
    background: var(--color-surface, #fff);
    border: 1px solid var(--color-border, #d1d5db);
    border-radius: 16px;
    padding: 18px;
    box-shadow: var(--shadow-sm, 0 2px 6px rgba(15, 23, 42, 0.05));
}

.omo-setup-panel__section-title {
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--color-text-light, #6b7280);
    margin-bottom: 12px;
}

.omo-setup-card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 14px;
}

.omo-setup-card {
    display: flex;
    flex-direction: column;
    width: 100%;
    padding: 0;
    border: 1px solid var(--color-border, #d1d5db);
    border-radius: 16px;
    overflow: hidden;
    background: var(--color-surface, #fff);
    color: inherit;
    cursor: pointer;
    text-align: left;
    transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
}

.omo-setup-card:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--color-primary, #2563eb) 32%, var(--color-border, #d1d5db));
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.omo-setup-card__media {
    position: relative;
    display: block;
    width: 100%;
    aspect-ratio: 16 / 9;
    background: linear-gradient(135deg, var(--color-primary, #2563eb), #1d4ed8);
}

.omo-setup-card__badge {
    position: absolute;
    left: 12px;
    bottom: 12px;
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.04em;
    backdrop-filter: blur(4px);
}

.omo-setup-card__content {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 16px;
    min-height: 164px;
}

.omo-setup-card__title {
    font-weight: 600;
    font-size: 18px;
}

.omo-setup-card__text {
    color: var(--color-text-light, #6b7280);
    font-size: 14px;
    line-height: 1.45;
    flex: 1 1 auto;
}

.omo-setup-card__cta {
    color: var(--color-primary, #2563eb);
    font-weight: 600;
}

.omo-setup-panel__empty,
.omo-setup-panel__feedback {
    font-size: 14px;
    color: var(--color-text-light, #6b7280);
}

.omo-setup-panel__empty {
    margin-top: 14px;
}

.omo-setup-panel__feedback {
    padding: 12px 14px;
    border-radius: 12px;
    background: var(--color-surface-alt, #f8fafc);
    border: 1px solid var(--color-border, #d1d5db);
}

.omo-setup-panel__feedback.is-error {
    color: #b91c1c;
    background: rgba(220, 38, 38, 0.06);
    border-color: rgba(220, 38, 38, 0.18);
}

.omo-setup-panel button[disabled] {
    opacity: 0.7;
    cursor: wait;
}

.omo-org-info-panel {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 18px;
    color: var(--color-text, #1f2937);
}

.omo-org-info-panel__hero {
    position: relative;
    min-height: 180px;
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid var(--color-border, #d1d5db);
    background: var(--color-surface-alt, #dbeafe);
}

.omo-org-info-panel__hero::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.04), rgba(15, 23, 42, 0.5));
}

.omo-org-info-panel__hero-content {
    position: absolute;
    inset: auto 18px 18px 18px;
    z-index: 1;
    color: #fff;
}

.omo-org-info-panel__kicker {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    opacity: 0.8;
}

.omo-org-info-panel__title {
    margin: 8px 0 0;
    font-size: 28px;
    line-height: 1.1;
}

.omo-org-info-panel__card {
    background: var(--color-surface, #fff);
    border: 1px solid var(--color-border, #d1d5db);
    border-radius: 16px;
    padding: 16px;
    box-shadow: var(--shadow-sm, 0 2px 6px rgba(15, 23, 42, 0.05));
}

.omo-org-info-panel__copy {
    margin: 0;
    line-height: 1.5;
    color: var(--color-text-light, #6b7280);
}

.omo-org-info-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
}

.omo-org-info-list__item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 12px;
    border-radius: 12px;
    background: var(--color-surface-alt, #f8fafc);
}

.omo-org-info-list__label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--color-text-light, #6b7280);
}

.omo-org-info-list__value {
    font-size: 15px;
    font-weight: 600;
}
</style>

<script>
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

    loadContent('#panel-left', 'api/getOrg.php?oid=' + targetOid);
    loadContent('#panel-right', 'api/getStructure.php?oid=' + targetOid);
}

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
    feedback.prop('hidden', false).removeClass('is-error').text('Initialisation en cours...');

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
            throw new Error(result.data && result.data.message ? result.data.message : "Impossible d'initialiser l'organisation.");
        }

        feedback.removeClass('is-error').text(result.data.message || 'Organisation initialisée.');

        const route = omoGetOrganizationSetupRoute();
        omoReloadOrganizationPanels(route.oid || organizationId);
    })
    .catch(function (error) {
        feedback.addClass('is-error').text(error && error.message ? error.message : "Impossible d'initialiser l'organisation.");
    })
    .finally(function () {
        panel.find('[data-omo-org-init-button="1"]').prop('disabled', false);
    });
  });
</script>
        <?php
    }
}

if (!function_exists('omoRenderOrganizationInfoPanel')) {
    function omoRenderOrganizationInfoPanel(\dbObject\Organization $organization)
    {
        $organizationName = trim((string)$organization->get('name'));
        $organizationShortname = trim((string)$organization->get('shortname'));
        $organizationDomain = trim((string)$organization->get('domain'));
        $organizationColor = trim((string)$organization->get('color'));
        $organizationLogo = trim((string)$organization->get('logo'));
        $organizationBanner = trim((string)$organization->get('banner'));

        if ($organizationName === '') {
            $organizationName = 'Organisation';
        }

        if ($organizationColor === '') {
            $organizationColor = '#2563eb';
        }

        $heroStyle = $organizationBanner !== ''
            ? 'background: linear-gradient(180deg, rgba(15,23,42,0.06), rgba(15,23,42,0.24)), url(' . omoSetupEscape($organizationBanner) . ') center/cover;'
            : 'background: ' . omoSetupEscape($organizationColor) . ';';
        ?>
<div class="omo-org-info-panel">
    <div class="omo-org-info-panel__hero" style="<?= $heroStyle ?>">
        <div class="omo-org-info-panel__hero-content">
            <div class="omo-org-info-panel__kicker">Organisation</div>
            <h2 class="omo-org-info-panel__title"><?= omoSetupEscape($organizationName) ?></h2>
        </div>
    </div>

    <div class="omo-org-info-panel__card">
        <p class="omo-org-info-panel__copy">Cette organisation n'a pas encore de structure. Utilisez le panneau de droite pour créer une organisation vide ou partir d'un modèle.</p>
    </div>

    <div class="omo-org-info-panel__card">
        <div class="omo-org-info-list">
            <div class="omo-org-info-list__item">
                <span class="omo-org-info-list__label">Nom</span>
                <span class="omo-org-info-list__value"><?= omoSetupEscape($organizationName) ?></span>
            </div>
            <div class="omo-org-info-list__item">
                <span class="omo-org-info-list__label">Nom court</span>
                <span class="omo-org-info-list__value"><?= omoSetupEscape($organizationShortname !== '' ? $organizationShortname : 'Non défini') ?></span>
            </div>
            <div class="omo-org-info-list__item">
                <span class="omo-org-info-list__label">Domaine</span>
                <span class="omo-org-info-list__value"><?= omoSetupEscape($organizationDomain !== '' ? $organizationDomain : 'Non défini') ?></span>
            </div>
            <div class="omo-org-info-list__item">
                <span class="omo-org-info-list__label">Couleur</span>
                <span class="omo-org-info-list__value"><?= omoSetupEscape($organizationColor) ?></span>
            </div>
            <div class="omo-org-info-list__item">
                <span class="omo-org-info-list__label">Logo</span>
                <span class="omo-org-info-list__value"><?= omoSetupEscape($organizationLogo !== '' ? $organizationLogo : 'Non défini') ?></span>
            </div>
        </div>
    </div>
</div>
        <?php
    }
}
