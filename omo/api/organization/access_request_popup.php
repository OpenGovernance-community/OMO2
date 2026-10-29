<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Invitation;
use dbObject\Organization;

$organizationId = (int)($_GET['oid'] ?? $_POST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$currentUserId = (int)commonGetCurrentUserId();

if ($organizationId <= 0 || $currentUserId <= 0) {
    http_response_code(403);
    ?>
    <div class="omo-access-request-popup__empty generic-description generic-drawer-content"><?= htmlspecialchars(omoOrganizationUiT('vous_devez_etre_connecte_pour_envoyer_une_demande_d_acces'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-access-request-popup__empty generic-description generic-drawer-content"><?= htmlspecialchars(omoOrganizationUiT('l_organisation_demandee_est_introuvable'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

if (commonUserHasOrganizationAccess($currentUserId, $organizationId)) {
    http_response_code(409);
    ?>
    <div class="omo-access-request-popup__empty generic-description generic-drawer-content"><?= htmlspecialchars(omoOrganizationUiT('votre_compte_a_deja_acces_a_cette_organisation'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

$pendingInvitation = Invitation::findPendingForOrganizationUser($organizationId, $currentUserId);
$hasPendingMemberRequest = $pendingInvitation instanceof Invitation && $pendingInvitation->isMemberInitiatedRequest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    $message = trim((string)($_POST['message'] ?? ''));
    $result = $organization->requestAccess($currentUserId, $message);

    if (!($result['status'] ?? false)) {
        http_response_code(422);
        echo json_encode(array(
            'status' => false,
            'message' => (string)($result['message'] ?? omoOrganizationUiT('impossible_d_envoyer_cette_demande')),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode(array(
        'status' => true,
        'message' => (string)($result['message'] ?? omoOrganizationUiT('demande_envoyee')),
        'created' => !empty($result['created']),
        'organizationId' => $organizationId,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
?>
<style>
    .omo-access-request-popup__textarea {
        min-height: 132px;
        resize: vertical;
    }

</style>

<form
    id="omoAccessRequestPopupForm"
    class="omo-access-request-popup generic-stack generic-stack--flush"
    action="/omo/api/organization/access_request_popup.php?oid=<?= (int)$organizationId ?>"
    method="post"
>
    <div class="omo-access-request-popup__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-access-request-popup__header-copy">
            <div class="generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoOrganizationUiT('organisation'), ENT_QUOTES, 'UTF-8') ?></div>
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoOrganizationUiT('demande_d_acces'), ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
    </div>

    <div class="omo-access-request-popup__shell generic-drawer-content">
        <p class="omo-access-request-popup__intro generic-description">
            <?= htmlspecialchars(omoOrganizationUiT('envoyez_un_court_message_aux_administrateurs_de'), ENT_QUOTES, 'UTF-8') ?>
            <strong><?= omoApiEscape(trim((string)$organization->get('name')) !== '' ? trim((string)$organization->get('name')) : 'cette organisation') ?></strong>
            <?= htmlspecialchars(omoOrganizationUiT('pour_expliquer_pourquoi_vous_souhaitez_rejoindre_cet_espace'), ENT_QUOTES, 'UTF-8') ?>
        </p>

        <?php if ($hasPendingMemberRequest): ?>
            <p class="omo-access-request-popup__status generic-description">
                <?= htmlspecialchars(omoOrganizationUiT('une_demande_est_deja_en_attente_pour_cette_organisation_vous_pouvez_toutefois_mettre_a_jou'), ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <div class="omo-access-request-popup__group generic-stack generic-stack--compact">
            <label class="omo-access-request-popup__label generic-form-label" for="omoAccessRequestMessage"><?= htmlspecialchars(omoOrganizationUiT('votre_message'), ENT_QUOTES, 'UTF-8') ?></label>
            <textarea
                id="omoAccessRequestMessage"
                name="message"
                class="omo-access-request-popup__textarea generic-form-control"
                maxlength="2000"
                placeholder="<?= htmlspecialchars(omoOrganizationUiT('bonjour_je_souhaite_rejoindre_cette_organisation_pour'), ENT_QUOTES, 'UTF-8') ?>"
            ><?= $hasPendingMemberRequest ? omoApiEscape($pendingInvitation->getRequestMessage()) : '' ?></textarea>
            <div class="omo-access-request-popup__hint generic-help-text generic-help-text--regular">
                <?= htmlspecialchars(omoOrganizationUiT('le_message_est_optionnel_mais_il_aide_les_administrateurs_a_comprendre_votre_demande'), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

        <div id="omoAccessRequestPopupFeedback" class="omo-access-request-popup__feedback generic-feedback"></div>

        <div class="omo-access-request-popup__actions generic-action-row">
            <button type="submit" id="omoAccessRequestPopupSubmit" class="generic-action-button generic-action-button--main">
                <?= htmlspecialchars(omoOrganizationUiT('envoyer_la_demande'), ENT_QUOTES, 'UTF-8') ?>
            </button>
        </div>
    </div>
</form>

<script src="<?= commonAssetUrl('/omo/api/organization/access_request_popup.js') ?>"></script>
