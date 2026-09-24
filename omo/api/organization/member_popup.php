<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

$organizationId = (int)($_GET['oid'] ?? $_POST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$currentUserId = (int)commonGetCurrentUserId();

if ($organizationId <= 0 || $currentUserId <= 0) {
    http_response_code(403);
    ?>
    <div class="omo-organization-member-popup__empty generic-description">Vous devez etre connecte a une organisation pour inviter un membre.</div>
    <?php
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-organization-member-popup__empty generic-description">L organisation demandee est introuvable.</div>
    <?php
    exit;
}

if (!$organization->canEdit()) {
    http_response_code(403);
    ?>
    <div class="omo-organization-member-popup__empty generic-description">Vous n avez pas le droit d ajouter un membre dans cette organisation.</div>
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
            'message' => (string)($result['message'] ?? "Impossible d'ajouter ce membre."),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode(array(
        'status' => true,
        'message' => (string)($result['message'] ?? 'Membre ajoute.'),
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
            <div class="generic-card-title generic-card-title--eyebrow">Organisation</div>
            <h3 class="generic-card-title generic-card-title--medium">Ajouter un membre</h3>
        </div>
    </div>

    <div class="omo-organization-member-popup__shell generic-drawer-content">
        <p class="omo-organization-member-popup__intro generic-description">
            Invitez une personne dans <strong><?= omoApiEscape(trim((string)$organization->get('name')) !== '' ? trim((string)$organization->get('name')) : 'cette organisation') ?></strong>
            en saisissant son adresse e-mail.
        </p>

        <div class="omo-organization-member-popup__group generic-stack generic-stack--compact">
            <label class="omo-organization-member-popup__label generic-form-label" for="omoOrganizationMemberEmail">Adresse e-mail</label>
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
                Si cette adresse existe deja, le profil existant sera rattache a l organisation. Sinon, une invitation sera preparee pour cette adresse.
            </div>
        </div>

        <div id="omoOrganizationMemberPopupFeedback" class="omo-organization-member-popup__feedback generic-feedback"></div>

        <div class="omo-organization-member-popup__actions generic-action-row">
            <button type="submit" id="omoOrganizationMemberPopupSubmit" class="generic-action-button generic-action-button--main">
                Inviter
            </button>
        </div>
    </div>
</form>

<?= commonPageScriptTags('/omo/api/organization/member_popup.js', [
    'organizationId' => (int)$organizationId,
]) ?>
