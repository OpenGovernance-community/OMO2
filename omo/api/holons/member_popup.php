<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\ArrayUserOrganization;
use dbObject\Holon;
use dbObject\Organization;

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$currentUserId = (int)commonGetCurrentUserId();
$holonId = (int)($_GET['hid'] ?? $_POST['hid'] ?? 0);

if ($organizationId <= 0 || $currentUserId <= 0 || $holonId <= 0) {
    http_response_code(403);
    ?>
    <div class="omo-holon-member-popup__empty generic-description"><?= htmlspecialchars(omoHolonsUiT('vous_devez_etre_connecte_a_une_organisation_pour_ajouter_un_membre'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

$organization = new Organization();
$holon = new Holon();

if (!$organization->load($organizationId) || !$holon->load($holonId) || !$organization->containsHolon($holon)) {
    http_response_code(404);
    ?>
    <div class="omo-holon-member-popup__empty generic-description"><?= htmlspecialchars(omoHolonsUiT('l_espace_demande_est_introuvable'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

$organizationLexicon = $organization->getLexicon();
$adminLabel = trim((string)($organizationLexicon['admin']['label'] ?? '')) ?: 'Admin';
$adminLabelLower = function_exists('mb_strtolower')
	? mb_strtolower($adminLabel, 'UTF-8')
	: strtolower($adminLabel);

commonEnableReadOnlyCaches();
$canAddMember = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? $holon->isAllowed('CAN_ADD_MEMBER', false)
    : $holon->isAllowed('CAN_ADD_MEMBER');
$canAddAdmin = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? $holon->isAllowed('CAN_ADD_ADMIN', false)
    : $holon->isAllowed('CAN_ADD_ADMIN');
$adminConstraintState = $holon->getAdminMemberConstraintState($organizationId);
$adminMinimum = (int)($adminConstraintState['min'] ?? 0);
$adminMaximum = $adminConstraintState['max'] ?? null;
$adminCount = (int)($adminConstraintState['adminCount'] ?? 0);
$focusMaximumLength = (int)(\dbObject\UserHolon::attributeLength()['focus'] ?? 250);
$adminCheckboxChecked = false;
$adminCheckboxDisabled = false;
$adminCheckboxHint = '';

if ($adminMinimum > $adminCount) {
    $adminCheckboxChecked = true;
    $adminCheckboxDisabled = true;
    $adminCheckboxHint = 'Le statut ' . $adminLabelLower . ' est requis tant que le minimum de ' . $adminMinimum . ' n est pas atteint.';
} elseif ($adminMaximum !== null && $adminCount >= (int)$adminMaximum) {
    $adminCheckboxDisabled = true;
    $adminCheckboxHint = 'Le maximum de ' . (int)$adminMaximum . ' ' . $adminLabelLower . ' est deja atteint.';
} elseif (!$canAddAdmin) {
    $adminCheckboxDisabled = true;
    $adminCheckboxHint = 'Vous n avez pas le droit de definir ce statut dans ce contexte.';
}

if (!$canAddMember) {
    http_response_code(403);
    ?>
    <div class="omo-holon-member-popup__empty generic-description"><?= htmlspecialchars(omoHolonsUiT('vous_n_avez_pas_le_droit_d_ajouter_un_membre_dans_ce_contexte'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $adminMinimum > 0 && $adminCount < $adminMinimum && !$canAddAdmin) {
    http_response_code(403);
    ?>
    <div class="omo-holon-member-popup__empty generic-description">Ce role exige au moins <?= (int)$adminMinimum ?> <?= omoApiEscape($adminLabelLower) ?> avant l ajout d un membre normal. Vous ne pouvez pas definir ce statut dans ce contexte.</div>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    $selectedUserId = (int)($_POST['user_id'] ?? 0);
    $email = trim((string)($_POST['email'] ?? ''));
	$isAdmin = !empty($_POST['is_admin']);
	$focus = trim((string)($_POST['focus'] ?? ''));
	if (mb_strlen($focus, 'UTF-8') > $focusMaximumLength) {
		http_response_code(422);
		echo json_encode(array(
			'status' => false,
			'message' => omoHolonsUiT('focus_maximum', ['count' => $focusMaximumLength]),
		), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	if ($isAdmin && !$canAddAdmin) {
		http_response_code(403);
		echo json_encode(array(
			'status' => false,
			'message' => omoHolonsUiT('manage_admin_denied', ['admin' => $adminLabelLower]),
		), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	$result = $holon->addMember($selectedUserId, $email, array(
		'isAdmin' => $isAdmin,
		'focus' => $focus,
	));
    if (!($result['status'] ?? false)) {
        http_response_code(422);
        echo json_encode(array(
            'status' => false,
            'message' => (string)($result['message'] ?? omoHolonsUiT('impossible_d_ajouter_ce_membre')),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode(array(
        'status' => true,
        'message' => (string)($result['message'] ?? omoHolonsUiT('membre_ajoute')),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$memberships = new ArrayUserOrganization();
$memberships->loadActiveForOrganization($organizationId);
$directMembers = $holon->getDirectMemberCards($organizationId);
$directMemberUserIds = array();
foreach ($directMembers as $member) {
    $directMemberUserIds[(int)($member['userId'] ?? 0)] = true;
}
?>
<style>
    .omo-holon-member-popup__checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
    }

    .omo-holon-member-popup__checkbox input[type="checkbox"] {
        width: 18px;
        height: 18px;
    }

    .omo-holon-member-popup__member-sources {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .omo-holon-member-popup__admin-focus {
        display: grid;
        grid-template-columns: auto minmax(180px, 1fr);
        align-items: end;
        gap: 12px;
    }

    .omo-holon-member-popup__focus-field {
        display: grid;
        gap: 5px;
    }

    @media (max-width: 620px) {
        .omo-holon-member-popup__member-sources,
        .omo-holon-member-popup__admin-focus {
            grid-template-columns: 1fr;
        }
    }

    .omo-holon-member-popup__empty {
        padding: 18px;
    }
</style>

<form
    id="omoHolonMemberPopupForm"
    class="omo-holon-member-popup generic-stack generic-stack--flush"
    action="api/holons/member_popup.php?hid=<?= (int)$holon->getId() ?>"
    method="post"
>
    <div class="omo-holon-member-popup__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-holon-member-popup__header-copy">
            <div class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape((string)$holon->getTemplateLabel(true)) ?></div>
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoHolonsUiT('ajouter_un_membre'), ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
    </div>
    <div class="omo-holon-member-popup__shell generic-drawer-content">
    <p class="omo-holon-member-popup__intro generic-description">
        <?= htmlspecialchars(omoHolonsUiT('ajoutez_une_personne_au_holon'), ENT_QUOTES, 'UTF-8') ?> <strong><?= omoApiEscape($holon->getDisplayName()) ?></strong><?= htmlspecialchars(omoHolonsUiT('soit_en_choisissant_un_membre_deja_present_dans_l_organisation_soit_en_saisissant_une_nouv'), ENT_QUOTES, 'UTF-8') ?>
    </p>

    <div class="omo-holon-member-popup__member-sources">
    <div class="omo-holon-member-popup__group generic-stack generic-stack--compact">
        <label class="omo-holon-member-popup__label generic-form-label" for="omoHolonMemberExistingUser"><?= htmlspecialchars(omoHolonsUiT('membre_existant'), ENT_QUOTES, 'UTF-8') ?></label>
        <select id="omoHolonMemberExistingUser" name="user_id" class="omo-holon-member-popup__select generic-form-control">
            <option value=""><?= htmlspecialchars(omoHolonsUiT('choisir_dans_l_organisation'), ENT_QUOTES, 'UTF-8') ?></option>
            <?php foreach ($memberships as $membership): ?>
                <?php
                $userId = (int)$membership->get('IDuser');
                if ($userId <= 0) {
                    continue;
                }
                $displayName = $membership->getUserDisplayName();
                $secondary = $membership->getScopedEmail() !== '' ? $membership->getScopedEmail() : $membership->getUserSecondaryLabel();
                $isAlreadyDirectMember = isset($directMemberUserIds[$userId]);
                ?>
                <option value="<?= $userId ?>"<?= $isAlreadyDirectMember ? ' disabled' : '' ?>>
                    <?= omoApiEscape($displayName . ($secondary !== '' ? ' - ' . $secondary : '') . ($isAlreadyDirectMember ? ' (déjà ajouté)' : '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="omo-holon-member-popup__hint generic-help-text generic-help-text--regular">
            <?= htmlspecialchars(omoHolonsUiT('les_personnes_deja_liees_directement_a_ce_holon_sont_desactivees_dans_la_liste'), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>

    <div class="omo-holon-member-popup__group generic-stack generic-stack--compact">
        <label class="omo-holon-member-popup__label generic-form-label" for="omoHolonMemberEmail"><?= htmlspecialchars(omoHolonsUiT('nouvelle_adresse_e_mail'), ENT_QUOTES, 'UTF-8') ?></label>
        <input
            type="email"
            id="omoHolonMemberEmail"
            name="email"
            class="omo-holon-member-popup__email generic-form-control"
            placeholder="prenom.nom@exemple.ch"
            inputmode="email"
            autocomplete="email"
        >
        <div class="omo-holon-member-popup__hint generic-help-text generic-help-text--regular">
            <?= htmlspecialchars(omoHolonsUiT('si_l_adresse_existe_deja_le_profil_existant_sera_reutilise_sinon_un_nouveau_profil_minimal'), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
    </div>

    <div class="omo-holon-member-popup__group generic-stack generic-stack--compact">
        <div class="omo-holon-member-popup__admin-focus">
            <label class="omo-holon-member-popup__checkbox" for="omoHolonMemberAdmin">
                <?php if ($adminCheckboxChecked && $adminCheckboxDisabled): ?>
                    <input type="hidden" name="is_admin" value="1">
                <?php endif; ?>
                <input type="checkbox" id="omoHolonMemberAdmin" name="is_admin" value="1"<?= $adminCheckboxChecked ? ' checked' : '' ?><?= $adminCheckboxDisabled ? ' disabled' : '' ?>>
                <?= omoApiEscape($adminLabel) ?>
            </label>
            <div class="omo-holon-member-popup__focus-field">
                <label class="omo-holon-member-popup__label generic-form-label" for="omoHolonMemberFocus"><?= htmlspecialchars(omoHolonsUiT('focus'), ENT_QUOTES, 'UTF-8') ?></label>
                <input
                    type="text"
                    id="omoHolonMemberFocus"
                    name="focus"
                    class="omo-holon-member-popup__focus generic-form-control"
                    maxlength="<?= (int)$focusMaximumLength ?>"
                >
            </div>
        </div>
        <div class="omo-holon-member-popup__hint generic-help-text generic-help-text--regular">
            <?= $adminCheckboxHint !== ''
                ? omoApiEscape($adminCheckboxHint)
                : ($canAddAdmin
                ? 'La personne recevra le statut ' . $adminLabelLower . ' de ce contexte, maintenant ou apres validation de l invitation.'
                : 'Vous n avez pas le droit de definir ce statut dans ce contexte.') ?>
            <?php if ($adminMinimum > 0 || $adminMaximum !== null): ?>
                <br><?= omoApiEscape($adminLabel) ?> : <?= (int)$adminCount ?><?= $adminMinimum > 0 ? ' (minimum : ' . (int)$adminMinimum . ')' : '' ?><?= $adminMaximum !== null ? ' (maximum : ' . (int)$adminMaximum . ')' : '' ?>.
            <?php endif; ?>
        </div>
    </div>

    <div id="omoHolonMemberPopupFeedback" class="omo-holon-member-popup__feedback generic-feedback"></div>

    <div class="omo-holon-member-popup__actions generic-action-row">
        <button type="submit" id="omoHolonMemberPopupSubmit" class="omo-holon-member-popup__button generic-action-button generic-action-button--main">
            <?= htmlspecialchars(omoHolonsUiT('ajouter_au_holon'), ENT_QUOTES, 'UTF-8') ?>
        </button>
    </div>
    </div>
</form>

<?= commonPageScriptTags('/omo/api/holons/member_popup.js', [
    'holonId' => (int)$holon->getId(),
    'rootHolonId' => (int)($organization->getStructuralRootHolon() ? $organization->getStructuralRootHolon()->getId() : 0),
    'organizationId' => (int)$organizationId,
]) ?>
