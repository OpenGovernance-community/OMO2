<?php

if (!function_exists('omoRenderOrganizationSetupPanel')) {
    function omoRenderOrganizationSetupPanel(\dbObject\Organization $organization)
    {
        $setupData = $organization->getStructuralInitializationData();
        $organizationName = trim((string)($setupData['organizationName'] ?? ''));
        $organizationColor = trim((string)$organization->get('color'));
        $emptyCardImage = '/omo/images/organization-setup/rien.png';
        $importCardImage = '/omo/images/organization-setup/import.png';

        if ($organizationName === '') {
            $organizationName = 'Cette organisation';
        }

        if ($organizationColor === '') {
            $organizationColor = 'var(--color-primary, #2563eb)';
        }

        $emptyCardMediaStyle = 'background: linear-gradient(180deg, rgba(15,23,42,0.08), rgba(15,23,42,0.38)), url(' . omoApiEscape($emptyCardImage) . ') center/cover;';
        $importCardMediaStyle = 'background: linear-gradient(180deg, rgba(15,23,42,0.06), rgba(15,23,42,0.34)), url(' . omoApiEscape($importCardImage) . ') center/cover;';
        ?>
<div
    class="omo-setup-panel"
    data-omo-org-setup="1"
    data-organization-id="<?= (int)($setupData['organizationId'] ?? 0) ?>"
>
    <div class="omo-setup-panel__section generic-section">
        <div class="omo-setup-panel__section-title generic-card-title generic-card-title--small">Choisissez un point de départ</div>
        <div class="omo-setup-card-grid">
            <button
                type="button"
                class="omo-setup-card omo-setup-card--primary"
                data-omo-org-init-button="1"
                data-template-id="0"
            >
                <span class="omo-setup-card__media" style="<?= $emptyCardMediaStyle ?>">
                    <span class="omo-setup-card__badge">Structure vide</span>
                </span>
                <span class="omo-setup-card__content">
                    <span class="omo-setup-card__title generic-card-title generic-card-title--big">Créer à partir de rien</span>
                    <span class="omo-setup-card__text generic-description generic-description--small">Crée uniquement le holon racine de type organisation, sans cercle ni rôle.</span>
                    <span class="omo-setup-card__cta">Créer l'organisation</span>
                </span>
            </button>

            <button
                type="button"
                class="omo-setup-card"
                data-omo-org-import-button="1"
            >
                <span class="omo-setup-card__media" style="<?= $importCardMediaStyle ?>">
                    <span class="omo-setup-card__badge">Import JSON</span>
                </span>
                <span class="omo-setup-card__content">
                    <span class="omo-setup-card__title generic-card-title generic-card-title--big">Importer une organisation</span>
                    <span class="omo-setup-card__text generic-description generic-description--small">Charge un export JSON et reconstruit la structure, les roles et les proprietes dans cette nouvelle organisation.</span>
                    <span class="omo-setup-card__cta">Selectionner un fichier</span>
                </span>
            </button>

            <?php foreach (($setupData['templates'] ?? array()) as $template): ?>
                <?php
                $templateColor = trim((string)($template['color'] ?? ''));
                if ($templateColor === '') {
                    $templateColor = 'linear-gradient(135deg, #dbeafe, #bfdbfe)';
                }
                $templateBanner = trim((string)($template['banner'] ?? ''));
                $templateIcon = trim((string)($template['icon'] ?? ''));
                $templateMediaStyle = $templateBanner !== ''
                    ? 'background: linear-gradient(180deg, rgba(15,23,42,0.08), rgba(15,23,42,0.42)), url(' . omoApiEscape($templateBanner) . ') center/cover;'
                    : 'background: ' . omoApiEscape($templateColor) . ';';
                ?>
                <button
                    type="button"
                    class="omo-setup-card"
                    data-omo-org-init-button="1"
                    data-template-id="<?= (int)($template['id'] ?? 0) ?>"
                >
                    <span class="omo-setup-card__media" style="<?= $templateMediaStyle ?>">
                        <span class="omo-setup-card__badge">Modèle</span>
                        <?php if ($templateIcon !== ''): ?>
                            <span class="omo-setup-card__icon">
                                <img src="<?= omoApiEscape($templateIcon) ?>" alt="">
                            </span>
                        <?php endif; ?>
                    </span>
                    <span class="omo-setup-card__content">
                        <span class="omo-setup-card__title generic-card-title generic-card-title--big"><?= omoApiEscape($template['name'] ?? 'Modèle') ?></span>
                        <span class="omo-setup-card__text generic-description generic-description--small">
                            <?php if (!empty($template['sourceOrganizationName'])): ?>
                                Inspiré de <?= omoApiEscape($template['sourceOrganizationName']) ?>.
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
            <div class="omo-setup-panel__empty generic-description generic-description--small">Aucun modèle d'organisation n'est disponible pour le moment.</div>
        <?php endif; ?>
    </div>

    <div class="omo-setup-panel__feedback generic-soft-panel generic-description generic-description--small" data-omo-org-init-feedback="1" hidden></div>
</div>

<style>
.omo-setup-panel {
    display: flex;
    flex-direction: column;
    gap: 18px;
    height: 100%;
    min-height: 0;
    padding: 24px;
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-gutter: stable;
    color: var(--color-text, #1f2937);
}

.omo-setup-panel__section {
    --generic-section-padding-block: 18px;
    --generic-section-radius: var(--radius-md);
    --generic-section-shadow: var(--shadow-sm, 0 2px 6px rgba(15, 23, 42, 0.05));
}

.omo-setup-panel__section-title {
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
    border-radius: var(--radius-md);
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
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.2);
}

.omo-setup-card__icon {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 62px;
    height: 62px;
    border-radius: var(--radius-md);
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.38);
    background: rgba(255, 255, 255, 0.18);
    box-shadow: 0 10px 26px rgba(15, 23, 42, 0.18);
    backdrop-filter: blur(6px);
}

.omo-setup-card__icon img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.omo-setup-card__content {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 16px;
    min-height: 164px;
}

.omo-setup-card__text {
    flex: 1 1 auto;
}

.omo-setup-card__cta {
    color: var(--color-primary, #2563eb);
    font-weight: 600;
}

.omo-setup-panel__empty {
    margin-top: 14px;
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

    window.commonTopbarOpenModal('Importer une organisation', popupUrl, 'fetch');
  });
</script>
        <?php
    }
}

if (!function_exists('omoRenderOrganizationInfoPanel')) {
    function omoBuildOrganizationMemberCards(\dbObject\Organization $organization)
    {
        $organizationId = (int)$organization->getId();
        $memberships = new \dbObject\ArrayUserOrganization();
        $memberships->loadVisibleForOrganization($organizationId, true);

        $memberCards = [];
        foreach ($memberships as $membership) {
            if (!$membership instanceof \dbObject\UserOrganization) {
                continue;
            }

            $userId = (int)$membership->get('IDuser');
            if ($userId <= 0) {
                continue;
            }

            $displayName = trim((string)$membership->getUserDisplayName());
            $secondary = trim((string)$membership->getScopedEmail());
            if ($secondary === '') {
                $secondary = trim((string)$membership->getUserSecondaryLabel());
            }

            $initials = trim((string)$membership->getUserInitials());
            if ($initials === '') {
                $initials = 'P';
            }

            $memberCards[] = [
                'userId' => $userId,
                'displayName' => $displayName !== '' ? $displayName : ('Utilisateur ' . $userId),
                'secondary' => $secondary,
                'photoUrl' => trim((string)$membership->getProfilePhotoUrl()),
                'initials' => $initials,
                'isPending' => !(bool)$membership->get('active'),
                'isOrganizationAdmin' => $membership->isOrganizationAdmin(),
            ];
        }

        usort($memberCards, static function (array $left, array $right): int {
            if (($left['isOrganizationAdmin'] ?? false) !== ($right['isOrganizationAdmin'] ?? false)) {
                return !empty($left['isOrganizationAdmin']) ? -1 : 1;
            }

            if (($left['isPending'] ?? false) !== ($right['isPending'] ?? false)) {
                return empty($left['isPending']) ? -1 : 1;
            }

            return strcmp(
                omoApiSortKey((string)($left['displayName'] ?? '')),
                omoApiSortKey((string)($right['displayName'] ?? ''))
            );
        });

        return $memberCards;
    }

    function omoRenderOrganizationInfoPanel(\dbObject\Organization $organization)
    {
        static $stylesRendered = false;
        $organizationId = (int)$organization->getId();
        $organizationName = trim((string)$organization->get('name'));
        $organizationShortname = trim((string)$organization->get('shortname'));
        $organizationDomain = trim((string)$organization->get('domain'));
        $organizationColor = trim((string)$organization->get('color'));
        $organizationLogo = trim((string)$organization->get('logo'));
        $organizationBanner = trim((string)$organization->get('banner'));
        $memberCards = omoBuildOrganizationMemberCards($organization);
        $visibleMemberCards = array_slice($memberCards, 0, 8);
        $hiddenMemberCount = max(0, count($memberCards) - count($visibleMemberCards));
        $canAddMembers = $organization->canEdit();

        if ($organizationName === '') {
            $organizationName = 'Organisation';
        }

        if ($organizationColor === '') {
            $organizationColor = '#2563eb';
        }

        $heroStyle = $organizationBanner !== ''
            ? 'background: linear-gradient(180deg, rgba(15,23,42,0.06), rgba(15,23,42,0.24)), url(' . omoApiEscape($organizationBanner) . ') center/cover;'
            : 'background: ' . omoApiEscape($organizationColor) . ';';
        ?>
<?php if (!$stylesRendered): $stylesRendered = true; ?>
<style>
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
    border-radius: var(--radius-md);
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
    opacity: 0.8;
}

.omo-org-info-panel__title {
    margin: 8px 0 0;
}

.omo-org-info-panel__card {
    background: var(--color-surface, #fff);
    border: 1px solid var(--color-border, #d1d5db);
    border-radius: var(--radius-md);
    padding: 16px;
    box-shadow: var(--shadow-sm, 0 2px 6px rgba(15, 23, 42, 0.05));
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
    border-radius: var(--radius-md);
    background: var(--color-surface-alt, #f8fafc);
}

.omo-org-members {
    display: grid;
    gap: 8px;
}

.omo-org-members__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.omo-org-members__list {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.omo-org-members__avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    min-width: 36px;
    border-radius: 999px;
    overflow: hidden;
    border: 1px solid var(--color-border, #d1d5db);
    background: color-mix(in srgb, var(--color-primary, #2563eb) 12%, var(--color-surface-alt, #f8fafc));
    box-shadow: var(--shadow-sm, 0 1px 2px rgba(15, 23, 42, 0.08));
    padding: 0;
}

.omo-org-members__avatar--button {
    cursor: pointer;
}

.omo-org-members__avatar--button:hover,
.omo-org-members__avatar--button:focus-visible {
    border-color: color-mix(in srgb, var(--color-primary, #2563eb) 35%, var(--color-border, #d1d5db));
    background: color-mix(in srgb, var(--color-primary, #2563eb) 16%, var(--color-surface-alt, #f8fafc));
}

.omo-org-members__avatar--pending {
    opacity: 0.6;
    border-style: dashed;
}

.omo-org-members__avatar img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}

.omo-org-members__initials {
    font-size: 12px;
    font-weight: 700;
    color: var(--color-primary, #2563eb);
}

.omo-org-members__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    min-height: 36px;
    padding: 0 10px;
    border-radius: 999px;
    background: rgba(37, 99, 235, 0.08);
    color: var(--color-primary, #2563eb);
    font-size: 11px;
    font-weight: 600;
}

.omo-org-members__badge--pending {
    background: rgba(148, 163, 184, 0.14);
    color: var(--color-text-light, #6b7280);
}

</style>
<?php endif; ?>
<div class="omo-org-info-panel" data-omo-org-info-panel="1" data-organization-id="<?= (int)$organizationId ?>">
    <div class="omo-org-info-panel__hero" style="<?= $heroStyle ?>">
        <div class="omo-org-info-panel__hero-content">
            <div class="omo-org-info-panel__kicker generic-card-title generic-card-title--eyebrow">Organisation</div>
            <h2 class="omo-org-info-panel__title generic-title generic-title--hero"><?= omoApiEscape($organizationName) ?></h2>
        </div>
    </div>

    <div class="omo-org-info-panel__card">
        <div class="omo-org-members">
            <div class="omo-org-members__head">
                <div class="generic-card-title generic-card-title--small">Membres</div>
                <?php if ($canAddMembers): ?>
                    <button
                        type="button"
                        class="generic-action-button generic-action-button--secondary"
                        data-omo-org-open-member-popup="1"
                        data-oid="<?= (int)$organizationId ?>"
                    >Inviter un membre</button>
                <?php endif; ?>
            </div>

            <?php if (count($memberCards) > 0): ?>
                <div class="omo-org-members__list">
                    <?php foreach ($visibleMemberCards as $member): ?>
                        <?php
                        $memberTooltipParts = [(string)$member['displayName']];
                        if (trim((string)($member['secondary'] ?? '')) !== '') {
                            $memberTooltipParts[] = (string)$member['secondary'];
                        }
                        if (!empty($member['isPending'])) {
                            $memberTooltipParts[] = 'invitation en attente';
                        }
                        if (!empty($member['isOrganizationAdmin'])) {
                            $memberTooltipParts[] = 'admin';
                        }
                        $memberTooltip = implode(' - ', array_filter($memberTooltipParts));
                        ?>
                        <button
                            type="button"
                            class="omo-org-members__avatar omo-org-members__avatar--button<?= !empty($member['isPending']) ? ' omo-org-members__avatar--pending' : '' ?>"
                            data-omo-org-open-user-popup="1"
                            data-oid="<?= (int)$organizationId ?>"
                            data-user-id="<?= (int)$member['userId'] ?>"
                            title="<?= omoApiEscape($memberTooltip) ?>"
                            aria-label="<?= omoApiEscape($memberTooltip) ?>"
                        >
                                <?php if (trim((string)($member['photoUrl'] ?? '')) !== ''): ?>
                                    <img src="<?= omoApiEscape((string)$member['photoUrl']) ?>" alt="">
                                <?php else: ?>
                                    <span class="omo-org-members__initials"><?= omoApiEscape((string)$member['initials']) ?></span>
                                <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                    <?php if ($hiddenMemberCount > 0): ?>
                        <span class="omo-org-members__badge">+<?= (int)$hiddenMemberCount ?></span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="omo-org-members__empty generic-description">Aucun membre n est encore rattache a cette organisation.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="omo-org-info-panel__card">
        <div class="omo-org-info-list">
            <div class="omo-org-info-list__item">
                <span class="omo-org-info-list__label generic-title generic-title--eyebrow">Nom</span>
                <span class="omo-org-info-list__value generic-title generic-title--compact"><?= omoApiEscape($organizationName) ?></span>
            </div>
            <div class="omo-org-info-list__item">
                <span class="omo-org-info-list__label generic-title generic-title--eyebrow">Nom court</span>
                <span class="omo-org-info-list__value generic-title generic-title--compact"><?= omoApiEscape($organizationShortname !== '' ? $organizationShortname : 'Non défini') ?></span>
            </div>
            <div class="omo-org-info-list__item">
                <span class="omo-org-info-list__label generic-title generic-title--eyebrow">Domaine</span>
                <span class="omo-org-info-list__value generic-title generic-title--compact"><?= omoApiEscape($organizationDomain !== '' ? $organizationDomain : 'Non défini') ?></span>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    function omoRefreshOrganizationInfoPanel(oid) {
        const targetOid = Number(oid || 0);
        if (!targetOid || typeof loadContent !== 'function') {
            return;
        }

        loadContent(typeof omoGetLeftPanelContentSelector === 'function' ? omoGetLeftPanelContentSelector() : '#panel-left', 'api/getOrg.php?oid=' + targetOid);
    }

    window.omoRefreshOrganizationInfoPanel = omoRefreshOrganizationInfoPanel;

    $(document)
      .off('click.omoOrgInfoMemberPopup', '[data-omo-org-info-panel="1"] [data-omo-org-open-member-popup="1"]')
      .on('click.omoOrgInfoMemberPopup', '[data-omo-org-info-panel="1"] [data-omo-org-open-member-popup="1"]', function () {
        const organizationId = Number($(this).data('oid') || 0);

        if (!organizationId || typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        window.commonTopbarOpenModal(
            'Ajouter un membre',
            '/omo/api/organization/member_popup.php?oid=' + encodeURIComponent(organizationId),
            'fetch'
        );
      });

    $(document)
      .off('click.omoOrgInfoUserPopup', '[data-omo-org-info-panel="1"] [data-omo-org-open-user-popup="1"]')
      .on('click.omoOrgInfoUserPopup', '[data-omo-org-info-panel="1"] [data-omo-org-open-user-popup="1"]', function () {
        const organizationId = Number($(this).data('oid') || 0);
        const userId = Number($(this).data('user-id') || 0);

        if (!organizationId || !userId || typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        window.commonTopbarOpenModal(
            'Profil',
            '/popup/user.php?id=' + encodeURIComponent(userId) + '&oid=' + encodeURIComponent(organizationId),
            'fetch'
        );
      });
})();
</script>
        <?php
    }
}
