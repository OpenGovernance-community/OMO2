<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Invitation;
use dbObject\Organization;

$organizationId = (int)($_GET['oid'] ?? $_POST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$currentUserId = (int)commonGetCurrentUserId();

if ($organizationId <= 0 || $currentUserId <= 0) {
    http_response_code(403);
    ?>
    <div class="omo-access-request-popup__empty generic-description generic-drawer-content">Vous devez etre connecte pour envoyer une demande d acces.</div>
    <?php
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-access-request-popup__empty generic-description generic-drawer-content">L organisation demandee est introuvable.</div>
    <?php
    exit;
}

if (commonUserHasOrganizationAccess($currentUserId, $organizationId)) {
    http_response_code(409);
    ?>
    <div class="omo-access-request-popup__empty generic-description generic-drawer-content">Votre compte a deja acces a cette organisation.</div>
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
            'message' => (string)($result['message'] ?? "Impossible d'envoyer cette demande."),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode(array(
        'status' => true,
        'message' => (string)($result['message'] ?? 'Demande envoyee.'),
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
            <div class="generic-card-title generic-card-title--eyebrow">Organisation</div>
            <h3 class="generic-card-title generic-card-title--medium">Demande d acces</h3>
        </div>
    </div>

    <div class="omo-access-request-popup__shell generic-drawer-content">
        <p class="omo-access-request-popup__intro generic-description">
            Envoyez un court message aux administrateurs de
            <strong><?= omoApiEscape(trim((string)$organization->get('name')) !== '' ? trim((string)$organization->get('name')) : 'cette organisation') ?></strong>
            pour expliquer pourquoi vous souhaitez rejoindre cet espace.
        </p>

        <?php if ($hasPendingMemberRequest): ?>
            <p class="omo-access-request-popup__status generic-description">
                Une demande est deja en attente pour cette organisation. Vous pouvez toutefois mettre a jour votre message ci-dessous et renvoyer la demande.
            </p>
        <?php endif; ?>

        <div class="omo-access-request-popup__group generic-stack generic-stack--compact">
            <label class="omo-access-request-popup__label generic-form-label" for="omoAccessRequestMessage">Votre message</label>
            <textarea
                id="omoAccessRequestMessage"
                name="message"
                class="omo-access-request-popup__textarea generic-form-control"
                maxlength="2000"
                placeholder="Bonjour, je souhaite rejoindre cette organisation pour..."
            ><?= $hasPendingMemberRequest ? omoApiEscape($pendingInvitation->getRequestMessage()) : '' ?></textarea>
            <div class="omo-access-request-popup__hint generic-help-text generic-help-text--regular">
                Le message est optionnel, mais il aide les administrateurs a comprendre votre demande.
            </div>
        </div>

        <div id="omoAccessRequestPopupFeedback" class="omo-access-request-popup__feedback generic-feedback"></div>

        <div class="omo-access-request-popup__actions generic-action-row">
            <button type="submit" id="omoAccessRequestPopupSubmit" class="generic-action-button generic-action-button--main">
                Envoyer la demande
            </button>
        </div>
    </div>
</form>

<script src="<?= commonAssetUrl('/omo/api/organization/access_request_popup.js') ?>"></script>
