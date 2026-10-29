<?php
require_once __DIR__ . '/../ui_translations.php';

if (!function_exists('omoRenderOrganizationSetupPanel')) {
    function omoRenderOrganizationSetupPanel(\dbObject\Organization $organization)
    {
        $setupData = $organization->getStructuralInitializationData();
        $templates = $setupData['templates'] ?? array();
        $canStartFromScratch = !$organization->isDiscoveryMode() || count($templates) === 0;
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
        <div class="omo-setup-panel__section-title generic-card-title generic-card-title--small"><?= htmlspecialchars(omoInterfaceUiT('choisissez_un_point_de_depart'), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="omo-setup-card-grid">
            <?php if ($canStartFromScratch): ?>
            <button
                type="button"
                class="omo-setup-card omo-setup-card--primary"
                data-omo-org-init-button="1"
                data-template-id="0"
            >
                <span class="omo-setup-card__media" style="<?= $emptyCardMediaStyle ?>">
                    <span class="omo-setup-card__badge"><?= htmlspecialchars(omoInterfaceUiT('structure_vide'), ENT_QUOTES, 'UTF-8') ?></span>
                </span>
                <span class="omo-setup-card__content">
                    <span class="omo-setup-card__title generic-card-title generic-card-title--big"><?= htmlspecialchars(omoInterfaceUiT('creer_a_partir_de_rien'), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="omo-setup-card__text generic-description generic-description--small"><?= htmlspecialchars(\dbObject\Organization::formatLexiconText('Crée uniquement le holon racine de type organisation, sans cercle ni rôle.', $organization->getLexicon()), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="omo-setup-card__cta"><?= htmlspecialchars(omoInterfaceUiT('creer_l_organisation'), ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            </button>
            <?php endif; ?>

            <button
                type="button"
                class="omo-setup-card"
                data-omo-org-import-button="1"
            >
                <span class="omo-setup-card__media" style="<?= $importCardMediaStyle ?>">
                    <span class="omo-setup-card__badge"><?= htmlspecialchars(omoInterfaceUiT('import_json'), ENT_QUOTES, 'UTF-8') ?></span>
                </span>
                <span class="omo-setup-card__content">
                    <span class="omo-setup-card__title generic-card-title generic-card-title--big"><?= htmlspecialchars(omoInterfaceUiT('importer_une_organisation'), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="omo-setup-card__text generic-description generic-description--small"><?= htmlspecialchars(omoInterfaceUiT('charge_un_export_json_et_reconstruit_la_structure_les_roles_et_les_proprietes_dans_cette_n'), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="omo-setup-card__cta"><?= htmlspecialchars(omoInterfaceUiT('selectionner_un_fichier'), ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            </button>

            <?php foreach ($templates as $template): ?>
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
                        <span class="omo-setup-card__badge"><?= htmlspecialchars(omoInterfaceUiT('modele'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($templateIcon !== ''): ?>
                            <span class="omo-setup-card__icon">
                                <img src="<?= omoApiEscape($templateIcon) ?>" alt="">
                            </span>
                        <?php endif; ?>
                    </span>
                    <span class="omo-setup-card__content">
                        <span class="omo-setup-card__title generic-card-title generic-card-title--big"><?= omoApiEscape($template['name'] ?? omoInterfaceUiT('modele')) ?></span>
                        <span class="omo-setup-card__text generic-description generic-description--small">
                            <?php if (!empty($template['sourceOrganizationName'])): ?>
                                Inspiré de <?= omoApiEscape($template['sourceOrganizationName']) ?>.
                            <?php else: ?>
                                Duplique la structure de ce modèle d'organisation.
                            <?php endif; ?>
                        </span>
                        <span class="omo-setup-card__cta"><?= htmlspecialchars(omoInterfaceUiT('utiliser_ce_modele'), ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </button>
            <?php endforeach; ?>
        </div>

        <?php if (count($templates) === 0): ?>
            <div class="omo-setup-panel__empty generic-description generic-description--small"><?= htmlspecialchars(omoInterfaceUiT('aucun_modele_d_organisation_n_est_disponible_pour_le_moment'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="omo-setup-panel__feedback generic-soft-panel generic-description generic-description--small" data-omo-org-init-feedback="1" hidden></div>
</div>

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/organization_setup_panel.css') ?>">

<script src="<?= commonAssetUrl('/omo/api/organization_setup_panel.js') ?>"></script>
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
            ];
        }

        usort($memberCards, static function (array $left, array $right): int {
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
<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/organization_info_panel.css') ?>">
<?php endif; ?>
<div class="omo-org-info-panel" data-omo-org-info-panel="1" data-organization-id="<?= (int)$organizationId ?>">
    <div class="omo-org-info-panel__hero" style="<?= $heroStyle ?>">
        <div class="omo-org-info-panel__hero-content">
            <div class="omo-org-info-panel__kicker generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoInterfaceUiT('organisation'), ENT_QUOTES, 'UTF-8') ?></div>
            <h2 class="omo-org-info-panel__title generic-title generic-title--hero"><?= omoApiEscape($organizationName) ?></h2>
        </div>
    </div>

    <div class="omo-org-info-panel__card">
        <div class="omo-org-members">
            <div class="omo-org-members__head">
                <div class="generic-card-title generic-card-title--small"><?= htmlspecialchars(omoInterfaceUiT('membres'), ENT_QUOTES, 'UTF-8') ?></div>
                <?php if ($canAddMembers): ?>
                    <button
                        type="button"
                        class="generic-action-button generic-action-button--secondary"
                        data-omo-org-open-member-popup="1"
                        data-oid="<?= (int)$organizationId ?>"
                    ><?= htmlspecialchars(omoInterfaceUiT('inviter_un_membre'), ENT_QUOTES, 'UTF-8') ?></button>
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
                        $memberTooltip = implode(' - ', array_filter($memberTooltipParts));
                        ?>
                        <button
                            type="button"
                            class="omo-org-members__avatar omo-org-members__avatar--button<?= !empty($member['isPending']) ? ' generic-member generic-member--inactive' : '' ?>"
                            data-omo-org-open-user-popup="1"
                            data-oid="<?= (int)$organizationId ?>"
                            data-user-id="<?= (int)$member['userId'] ?>"
                            title="<?= omoApiEscape($memberTooltip) ?>"
                            aria-label="<?= omoApiEscape($memberTooltip) ?>"
                        >
                                <?php if (trim((string)($member['photoUrl'] ?? '')) !== ''): ?>
                                    <img src="<?= omoApiEscape((string)$member['photoUrl']) ?>" alt="" width="36" height="36" decoding="async">
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
                <div class="omo-org-members__empty generic-description"><?= htmlspecialchars(omoInterfaceUiT('aucun_membre_n_est_encore_rattache_a_cette_organisation'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="omo-org-info-panel__card">
        <div class="omo-org-info-list">
            <div class="omo-org-info-list__item">
                <span class="omo-org-info-list__label generic-title generic-title--eyebrow"><?= htmlspecialchars(omoInterfaceUiT('nom'), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="omo-org-info-list__value generic-title generic-title--compact"><?= omoApiEscape($organizationName) ?></span>
            </div>
            <div class="omo-org-info-list__item">
                <span class="omo-org-info-list__label generic-title generic-title--eyebrow"><?= omoApiEscape(\dbObject\Organization::attributeLabels()['shortname']) ?></span>
                <span class="omo-org-info-list__value generic-title generic-title--compact"><?= omoApiEscape($organizationShortname !== '' ? $organizationShortname : 'Non défini') ?></span>
            </div>
            <div class="omo-org-info-list__item">
                <span class="omo-org-info-list__label generic-title generic-title--eyebrow"><?= htmlspecialchars(omoInterfaceUiT('domaine'), ENT_QUOTES, 'UTF-8') ?></span>
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
