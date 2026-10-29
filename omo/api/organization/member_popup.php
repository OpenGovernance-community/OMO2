<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

$organizationId = (int)($_GET['oid'] ?? $_POST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$currentUserId = (int)commonGetCurrentUserId();

if ($organizationId <= 0 || $currentUserId <= 0) {
    http_response_code(403);
    ?>
    <div class="omo-organization-member-popup__empty generic-description"><?= htmlspecialchars(omoOrganizationUiT('vous_devez_etre_connecte_a_une_organisation_pour_inviter_un_membre'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-organization-member-popup__empty generic-description"><?= htmlspecialchars(omoOrganizationUiT('l_organisation_demandee_est_introuvable'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

if (!$organization->canEdit()) {
    http_response_code(403);
    ?>
    <div class="omo-organization-member-popup__empty generic-description"><?= htmlspecialchars(omoOrganizationUiT('vous_n_avez_pas_le_droit_d_ajouter_un_membre_dans_cette_organisation'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    $selectedUserId = (int)($_POST['user_id'] ?? 0);
    $email = trim((string)($_POST['email'] ?? ''));

    $result = $organization->addMember($selectedUserId, $email);
    if (!($result['status'] ?? false)) {
        http_response_code(422);
        echo json_encode(array(
            'status' => false,
            'message' => (string)($result['message'] ?? omoOrganizationUiT('impossible_d_ajouter_ce_membre')),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode(array(
        'status' => true,
        'message' => (string)($result['message'] ?? omoOrganizationUiT('membre_ajoute')),
        'organizationId' => $organizationId,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
?>
<style>
    .omo-organization-member-popup__empty {
        padding: 18px;
    }
</style>

<form
    id="omoOrganizationMemberPopupForm"
    class="omo-organization-member-popup generic-stack generic-stack--flush"
    action="/omo/api/organization/member_popup.php?oid=<?= (int)$organizationId ?>"
    method="post"
>
    <div class="omo-organization-member-popup__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-organization-member-popup__header-copy">
            <div class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoOrganizationUiT('organisation'), ENT_QUOTES, 'UTF-8') ?></div>
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoOrganizationUiT('ajouter_un_membre'), ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
    </div>

    <div class="omo-organization-member-popup__shell generic-drawer-content">
        <p class="omo-organization-member-popup__intro generic-description">
            <?= htmlspecialchars(omoOrganizationUiT('invitez_une_personne_dans'), ENT_QUOTES, 'UTF-8') ?> <strong><?= omoApiEscape(trim((string)$organization->get('name')) !== '' ? trim((string)$organization->get('name')) : 'cette organisation') ?></strong>
            <?= htmlspecialchars(omoOrganizationUiT('en_saisissant_son_adresse_e_mail'), ENT_QUOTES, 'UTF-8') ?>
        </p>

        <div class="omo-organization-member-popup__group generic-stack generic-stack--compact">
            <label class="omo-organization-member-popup__label generic-form-label" for="omoOrganizationMemberEmail"><?= htmlspecialchars(omoOrganizationUiT('adresse_e_mail'), ENT_QUOTES, 'UTF-8') ?></label>
            <input
                type="email"
                id="omoOrganizationMemberEmail"
                name="email"
                class="omo-organization-member-popup__email generic-form-control"
                placeholder="prenom.nom@exemple.ch"
                inputmode="email"
                autocomplete="email"
            >
            <div class="omo-organization-member-popup__hint generic-help-text generic-help-text--regular">
                <?= htmlspecialchars(omoOrganizationUiT('si_cette_adresse_existe_deja_le_profil_existant_sera_rattache_a_l_organisation_sinon_une_i'), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

        <div id="omoOrganizationMemberPopupFeedback" class="omo-organization-member-popup__feedback generic-feedback"></div>

        <div class="omo-organization-member-popup__actions generic-action-row">
            <button type="submit" id="omoOrganizationMemberPopupSubmit" class="generic-action-button generic-action-button--main">
                <?= htmlspecialchars(omoOrganizationUiT('inviter'), ENT_QUOTES, 'UTF-8') ?>
            </button>
        </div>
    </div>
</form>

<?= commonPageScriptTags('/omo/api/organization/member_popup.js', [
    'organizationId' => (int)$organizationId,
]) ?>
