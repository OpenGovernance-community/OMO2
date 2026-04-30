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
    <div class="omo-holon-member-popup__empty">Vous devez être connecté à une organisation pour ajouter un membre.</div>
    <?php
    exit;
}

$organization = new Organization();
$holon = new Holon();

if (!$organization->load($organizationId) || !$holon->load($holonId) || !$organization->containsHolon($holon)) {
    http_response_code(404);
    ?>
    <div class="omo-holon-member-popup__empty">Le holon demandé est introuvable.</div>
    <?php
    exit;
}

if (!$holon->canEdit()) {
    http_response_code(403);
    ?>
    <div class="omo-holon-member-popup__empty">Vous n'avez pas le droit de modifier ce holon.</div>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    $selectedUserId = (int)($_POST['user_id'] ?? 0);
    $email = trim((string)($_POST['email'] ?? ''));

    $result = $holon->addMember($selectedUserId, $email);
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
    .omo-holon-member-popup {
        display: grid;
        gap: 16px;
        padding: 8px 4px 4px;
        color: var(--color-text, #1f2937);
    }

    .omo-holon-member-popup__intro {
        color: var(--topbar-panel-muted, #64748b);
        line-height: 1.5;
        margin: 0;
    }

    .omo-holon-member-popup__group {
        display: grid;
        gap: 8px;
    }

    .omo-holon-member-popup__label {
        font-weight: 700;
    }

    .omo-holon-member-popup__select,
    .omo-holon-member-popup__email {
        width: 100%;
        min-height: 44px;
        padding: 11px 12px;
        border: 1px solid var(--topbar-panel-border, #dbe3ef);
        border-radius: 12px;
        background: var(--topbar-panel-bg, #ffffff);
        color: inherit;
        font: inherit;
        box-sizing: border-box;
    }

    .omo-holon-member-popup__hint {
        color: var(--topbar-panel-muted, #64748b);
        font-size: 0.92rem;
        line-height: 1.45;
    }

    .omo-holon-member-popup__separator {
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--topbar-panel-muted, #64748b);
        font-size: 0.86rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .omo-holon-member-popup__separator::before,
    .omo-holon-member-popup__separator::after {
        content: "";
        flex: 1 1 auto;
        height: 1px;
        background: var(--topbar-panel-border, #e2e8f0);
    }

    .omo-holon-member-popup__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .omo-holon-member-popup__button {
        border: 0;
        border-radius: 10px;
        padding: 12px 16px;
        font-weight: 700;
        cursor: pointer;
    }

    .omo-holon-member-popup__button--primary {
        background: var(--color-primary, #4f46e5);
        color: #ffffff;
    }

    .omo-holon-member-popup__button--primary:disabled {
        opacity: 0.6;
        cursor: wait;
    }

    .omo-holon-member-popup__feedback {
        min-height: 22px;
        color: #b91c1c;
        font-weight: 600;
    }

    .omo-holon-member-popup__feedback.is-success {
        color: #15803d;
    }

    .omo-holon-member-popup__empty {
        padding: 18px 6px;
        color: var(--topbar-panel-muted, #64748b);
        line-height: 1.5;
    }
</style>

<form
    id="omoHolonMemberPopupForm"
    class="omo-holon-member-popup"
    action="api/holons/member_popup.php?hid=<?= (int)$holon->getId() ?>"
    method="post"
>
    <p class="omo-holon-member-popup__intro">
        Ajoutez une personne au holon <strong><?= omoApiEscape($holon->getDisplayName()) ?></strong>,
        soit en choisissant un membre déjà présent dans l'organisation, soit en saisissant une nouvelle adresse e-mail.
    </p>

    <div class="omo-holon-member-popup__group">
        <label class="omo-holon-member-popup__label" for="omoHolonMemberExistingUser">Membre existant</label>
        <select id="omoHolonMemberExistingUser" name="user_id" class="omo-holon-member-popup__select">
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
        <div class="omo-holon-member-popup__hint">
            Les personnes déjà liées directement à ce holon sont désactivées dans la liste.
        </div>
    </div>

    <div class="omo-holon-member-popup__separator">ou</div>

    <div class="omo-holon-member-popup__group">
        <label class="omo-holon-member-popup__label" for="omoHolonMemberEmail">Nouvelle adresse e-mail</label>
        <input
            type="email"
            id="omoHolonMemberEmail"
            name="email"
            class="omo-holon-member-popup__email"
            placeholder="prenom.nom@exemple.ch"
            inputmode="email"
            autocomplete="email"
        >
        <div class="omo-holon-member-popup__hint">
            Si l'adresse existe déjà, le profil existant sera réutilisé. Sinon, un nouveau profil minimal sera créé puis rattaché.
        </div>
    </div>

    <div id="omoHolonMemberPopupFeedback" class="omo-holon-member-popup__feedback"></div>

    <div class="omo-holon-member-popup__actions">
        <button type="submit" id="omoHolonMemberPopupSubmit" class="omo-holon-member-popup__button omo-holon-member-popup__button--primary">
            Ajouter au holon
        </button>
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

                    if (typeof loadContent === 'function') {
                        var leftUrl = 'api/getOrg.php?oid=' + organizationId;
                        if (holonId > 0 && holonId !== rootHolonId) {
                            leftUrl += '&cid=' + holonId;
                        }
                        loadContent('#panel-left', leftUrl);
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
