<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

$organizationId = (int)($_GET['oid'] ?? $_POST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$currentUserId = (int)commonGetCurrentUserId();

if ($organizationId <= 0 || $currentUserId <= 0) {
    http_response_code(403);
    ?>
    <div class="omo-organization-member-popup__empty">Vous devez etre connecte a une organisation pour inviter un membre.</div>
    <?php
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-organization-member-popup__empty">L organisation demandee est introuvable.</div>
    <?php
    exit;
}

if (!$organization->canEdit()) {
    http_response_code(403);
    ?>
    <div class="omo-organization-member-popup__empty">Vous n avez pas le droit d ajouter un membre dans cette organisation.</div>
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
    .omo-organization-member-popup {
        display: grid;
        gap: 0;
        color: var(--color-text, #1f2937);
    }

    .omo-organization-member-popup__header {
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .omo-organization-member-popup__header-copy {
        display: grid;
        gap: 4px;
    }

    .omo-organization-member-popup__shell {
        display: grid;
        gap: 16px;
        padding: 16px 18px 18px;
    }

    .omo-organization-member-popup__intro {
        color: var(--topbar-panel-muted, #64748b);
        line-height: 1.5;
        margin: 0;
    }

    .omo-organization-member-popup__group {
        display: grid;
        gap: 8px;
    }

    .omo-organization-member-popup__label {
        font-weight: 700;
    }

    .omo-organization-member-popup__hint {
        color: var(--topbar-panel-muted, #64748b);
        font-size: 0.92rem;
        line-height: 1.45;
    }

    .omo-organization-member-popup__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .omo-organization-member-popup__feedback {
        min-height: 22px;
        color: #b91c1c;
        font-weight: 600;
    }

    .omo-organization-member-popup__feedback.is-success {
        color: #15803d;
    }

    .omo-organization-member-popup__empty {
        padding: 18px;
        color: var(--topbar-panel-muted, #64748b);
        line-height: 1.5;
    }
</style>

<form
    id="omoOrganizationMemberPopupForm"
    class="omo-organization-member-popup"
    action="/omo/api/organization/member_popup.php?oid=<?= (int)$organizationId ?>"
    method="post"
>
    <div class="omo-organization-member-popup__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-organization-member-popup__header-copy">
            <div class="generic-card-title generic-card-title--eyebrow">Organisation</div>
            <h3 class="generic-card-title generic-card-title--medium">Ajouter un membre</h3>
        </div>
    </div>

    <div class="omo-organization-member-popup__shell">
        <p class="omo-organization-member-popup__intro">
            Invitez une personne dans <strong><?= omoApiEscape(trim((string)$organization->get('name')) !== '' ? trim((string)$organization->get('name')) : 'cette organisation') ?></strong>
            en saisissant son adresse e-mail.
        </p>

        <div class="omo-organization-member-popup__group">
            <label class="omo-organization-member-popup__label" for="omoOrganizationMemberEmail">Adresse e-mail</label>
            <input
                type="email"
                id="omoOrganizationMemberEmail"
                name="email"
                class="omo-organization-member-popup__email generic-form-control"
                placeholder="prenom.nom@exemple.ch"
                inputmode="email"
                autocomplete="email"
            >
            <div class="omo-organization-member-popup__hint">
                Si cette adresse existe deja, le profil existant sera rattache a l organisation. Sinon, une invitation sera preparee pour cette adresse.
            </div>
        </div>

        <div id="omoOrganizationMemberPopupFeedback" class="omo-organization-member-popup__feedback"></div>

        <div class="omo-organization-member-popup__actions">
            <button type="submit" id="omoOrganizationMemberPopupSubmit" class="generic-action-button generic-action-button--main">
                Inviter
            </button>
        </div>
    </div>
</form>

<script>
    (function () {
        var form = document.getElementById('omoOrganizationMemberPopupForm');
        var feedback = document.getElementById('omoOrganizationMemberPopupFeedback');
        var submitButton = document.getElementById('omoOrganizationMemberPopupSubmit');
        var emailInput = document.getElementById('omoOrganizationMemberEmail');
        var organizationId = <?= (int)$organizationId ?>;

        if (!form || !feedback || !submitButton || !emailInput) {
            return;
        }

        function refreshParentPanel() {
            if (typeof window.omoRefreshOrganizationInfoPanel === 'function') {
                window.omoRefreshOrganizationInfoPanel(organizationId);
                return;
            }

            if (window.parent && window.parent !== window && typeof window.parent.omoRefreshOrganizationInfoPanel === 'function') {
                window.parent.omoRefreshOrganizationInfoPanel(organizationId);
            }
        }

        function closeModal() {
            if (typeof window.commonTopbarCloseModal === 'function') {
                window.commonTopbarCloseModal();
                return;
            }

            if (window.parent && window.parent !== window && typeof window.parent.commonTopbarCloseModal === 'function') {
                window.parent.commonTopbarCloseModal();
            }
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            feedback.textContent = '';
            feedback.classList.remove('is-success');

            if (!emailInput.value.trim()) {
                feedback.textContent = 'Saisissez une adresse e-mail.';
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
                if (!result.ok || !result.data || !result.data.status) {
                    feedback.textContent = result.data && result.data.message ? result.data.message : 'Une erreur est survenue.';
                    submitButton.disabled = false;
                    return;
                }

                feedback.textContent = result.data.message || 'Invitation envoyee.';
                feedback.classList.add('is-success');
                refreshParentPanel();

                window.setTimeout(function () {
                    closeModal();
                }, 250);
            })
            .catch(function () {
                feedback.textContent = 'Une erreur est survenue.';
                submitButton.disabled = false;
            });
        });
    })();
</script>
