<?php
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
    <div class="omo-holon-member-popup__empty generic-description">Vous devez être connecté à une organisation pour ajouter un membre.</div>
    <?php
    exit;
}

$organization = new Organization();
$holon = new Holon();

if (!$organization->load($organizationId) || !$holon->load($holonId) || !$organization->containsHolon($holon)) {
    http_response_code(404);
    ?>
    <div class="omo-holon-member-popup__empty generic-description">Le holon demandé est introuvable.</div>
    <?php
    exit;
}

$organizationLexicon = $organization->getLexicon();
$adminLabel = trim((string)($organizationLexicon['admin']['label'] ?? '')) ?: 'Admin';
$adminLabelLower = function_exists('mb_strtolower')
	? mb_strtolower($adminLabel, 'UTF-8')
	: strtolower($adminLabel);

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
    <div class="omo-holon-member-popup__empty generic-description">Vous n'avez pas le droit d'ajouter un membre dans ce contexte.</div>
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
			'message' => 'Le focus ne peut pas depasser ' . $focusMaximumLength . ' caracteres.',
		), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	if ($isAdmin && !$canAddAdmin) {
		http_response_code(403);
		echo json_encode(array(
			'status' => false,
			'message' => "Vous n'avez pas le droit de gerer le statut " . $adminLabelLower . ' dans ce contexte.',
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
            'message' => (string)($result['message'] ?? "Impossible d'ajouter ce membre."),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode(array(
        'status' => true,
        'message' => (string)($result['message'] ?? 'Membre ajouté.'),
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
            <h3 class="generic-card-title generic-card-title--medium">Ajouter un membre</h3>
        </div>
    </div>
    <div class="omo-holon-member-popup__shell generic-drawer-content">
    <p class="omo-holon-member-popup__intro generic-description">
        Ajoutez une personne au holon <strong><?= omoApiEscape($holon->getDisplayName()) ?></strong>,
        soit en choisissant un membre déjà présent dans l'organisation, soit en saisissant une nouvelle adresse e-mail.
    </p>

    <div class="omo-holon-member-popup__member-sources">
    <div class="omo-holon-member-popup__group generic-stack generic-stack--compact">
        <label class="omo-holon-member-popup__label generic-form-label" for="omoHolonMemberExistingUser">Membre existant</label>
        <select id="omoHolonMemberExistingUser" name="user_id" class="omo-holon-member-popup__select generic-form-control">
            <option value="">Choisir dans l'organisation</option>
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
            Les personnes déjà liées directement à ce holon sont désactivées dans la liste.
        </div>
    </div>

    <div class="omo-holon-member-popup__group generic-stack generic-stack--compact">
        <label class="omo-holon-member-popup__label generic-form-label" for="omoHolonMemberEmail">Nouvelle adresse e-mail</label>
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
            Si l'adresse existe déjà, le profil existant sera réutilisé. Sinon, un nouveau profil minimal sera créé puis rattaché.
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
                <label class="omo-holon-member-popup__label generic-form-label" for="omoHolonMemberFocus">Focus</label>
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
            Ajouter au holon
        </button>
    </div>
    </div>
</form>

<script>
    (function () {
        var form = document.getElementById('omoHolonMemberPopupForm');
        var feedback = document.getElementById('omoHolonMemberPopupFeedback');
        var submitButton = document.getElementById('omoHolonMemberPopupSubmit');
        var select = document.getElementById('omoHolonMemberExistingUser');
        var emailInput = document.getElementById('omoHolonMemberEmail');
        var holonId = <?= (int)$holon->getId() ?>;
        var rootHolonId = <?= (int)($organization->getStructuralRootHolon() ? $organization->getStructuralRootHolon()->getId() : 0) ?>;
        var organizationId = <?= (int)$organizationId ?>;

        if (!form || !feedback || !submitButton || !select || !emailInput) {
            return;
        }

        select.addEventListener('change', function () {
            if (select.value) {
                emailInput.value = '';
            }
        });

        emailInput.addEventListener('input', function () {
            if (emailInput.value.trim() !== '') {
                select.value = '';
            }
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            feedback.textContent = '';
            feedback.classList.remove('is-success');

            if (!select.value && !emailInput.value.trim()) {
                feedback.textContent = 'Choisissez une personne ou saisissez une adresse e-mail.';
                return;
            }

            submitButton.disabled = true;

            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return {
                            ok: response.ok,
                            data: data
                        };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data || !result.data.status) {
                        feedback.textContent = result.data && result.data.message ? result.data.message : 'Une erreur est survenue.';
                        submitButton.disabled = false;
                        return;
                    }

                    feedback.textContent = result.data.message || 'Membre ajouté.';
                    feedback.classList.add('is-success');

                    if (typeof refreshDrawer === 'function') {
                        var drawerUrl = '/omo/api/team/index.php?oid=' + organizationId;
                        if (holonId > 0 && holonId !== rootHolonId) {
                            drawerUrl += '&cid=' + holonId;
                        }
                        refreshDrawer('drawer_team', drawerUrl);
                    }

                    if (typeof loadContent === 'function') {
                        var leftUrl = 'api/getOrg.php?oid=' + organizationId;
                        if (holonId > 0 && holonId !== rootHolonId) {
                            leftUrl += '&cid=' + holonId;
                        }
                        loadContent(typeof omoGetLeftPanelContentSelector === 'function' ? omoGetLeftPanelContentSelector() : '#panel-left', leftUrl);
                    }

                    if (typeof window.omoReloadStructureAndFocus === 'function') {
                        window.omoReloadStructureAndFocus(holonId > 0 ? holonId : null, {
                            quickZoom: true
                        });
                    } else {
                        window.dispatchEvent(new CustomEvent('omo-structure-refresh', {
                            detail: {
                                cid: holonId > 0 ? holonId : null
                            }
                        }));
                    }

                    window.setTimeout(function () {
                        if (typeof window.commonTopbarCloseModal === 'function') {
                            window.commonTopbarCloseModal();
                        }
                    }, 250);
                })
                .catch(function () {
                    feedback.textContent = "Impossible d'ajouter ce membre pour le moment.";
                    submitButton.disabled = false;
                });
        });
    })();
</script>
