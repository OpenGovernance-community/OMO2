<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Invitation;
use dbObject\Organization;

$organizationId = (int)($_GET['oid'] ?? $_POST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$currentUserId = (int)commonGetCurrentUserId();

if ($organizationId <= 0 || $currentUserId <= 0) {
    http_response_code(403);
    ?>
    <div class="omo-access-request-popup__empty">Vous devez etre connecte pour envoyer une demande d acces.</div>
    <?php
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-access-request-popup__empty">L organisation demandee est introuvable.</div>
    <?php
    exit;
}

if (commonUserHasOrganizationAccess($currentUserId, $organizationId)) {
    http_response_code(409);
    ?>
    <div class="omo-access-request-popup__empty">Votre compte a deja acces a cette organisation.</div>
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
    .omo-access-request-popup {
        display: grid;
        gap: 0;
        color: var(--color-text, #1f2937);
    }

    .omo-access-request-popup__header {
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .omo-access-request-popup__header-copy {
        display: grid;
        gap: 4px;
    }

    .omo-access-request-popup__shell {
        display: grid;
        gap: 16px;
        padding: 16px 18px 18px;
    }

    .omo-access-request-popup__intro,
    .omo-access-request-popup__hint,
    .omo-access-request-popup__status,
    .omo-access-request-popup__empty {
        color: var(--topbar-panel-muted, #64748b);
        line-height: 1.5;
        margin: 0;
    }

    .omo-access-request-popup__group {
        display: grid;
        gap: 8px;
    }

    .omo-access-request-popup__label {
        font-weight: 700;
    }

    .omo-access-request-popup__textarea {
        min-height: 132px;
        resize: vertical;
    }

    .omo-access-request-popup__feedback {
        min-height: 22px;
        color: #b91c1c;
        font-weight: 600;
    }

    .omo-access-request-popup__feedback.is-success {
        color: #15803d;
    }

    .omo-access-request-popup__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
</style>

<form
    id="omoAccessRequestPopupForm"
    class="omo-access-request-popup"
    action="/omo/api/organization/access_request_popup.php?oid=<?= (int)$organizationId ?>"
    method="post"
>
    <div class="omo-access-request-popup__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-access-request-popup__header-copy">
            <div class="generic-card-title generic-card-title--eyebrow">Organisation</div>
            <h3 class="generic-card-title generic-card-title--medium">Demande d acces</h3>
        </div>
    </div>

    <div class="omo-access-request-popup__shell">
        <p class="omo-access-request-popup__intro">
            Envoyez un court message aux administrateurs de
            <strong><?= omoApiEscape(trim((string)$organization->get('name')) !== '' ? trim((string)$organization->get('name')) : 'cette organisation') ?></strong>
            pour expliquer pourquoi vous souhaitez rejoindre cet espace.
        </p>

        <?php if ($hasPendingMemberRequest): ?>
            <p class="omo-access-request-popup__status">
                Une demande est deja en attente pour cette organisation. Vous pouvez toutefois mettre a jour votre message ci-dessous et renvoyer la demande.
            </p>
        <?php endif; ?>

        <div class="omo-access-request-popup__group">
            <label class="omo-access-request-popup__label" for="omoAccessRequestMessage">Votre message</label>
            <textarea
                id="omoAccessRequestMessage"
                name="message"
                class="omo-access-request-popup__textarea generic-form-control"
                maxlength="2000"
                placeholder="Bonjour, je souhaite rejoindre cette organisation pour..."
            ><?= $hasPendingMemberRequest ? omoApiEscape($pendingInvitation->getRequestMessage()) : '' ?></textarea>
            <div class="omo-access-request-popup__hint">
                Le message est optionnel, mais il aide les administrateurs a comprendre votre demande.
            </div>
        </div>

        <div id="omoAccessRequestPopupFeedback" class="omo-access-request-popup__feedback"></div>

        <div class="omo-access-request-popup__actions">
            <button type="submit" id="omoAccessRequestPopupSubmit" class="generic-action-button generic-action-button--main">
                Envoyer la demande
            </button>
        </div>
    </div>
</form>

<script>
    (function () {
        var form = document.getElementById('omoAccessRequestPopupForm');
        var feedback = document.getElementById('omoAccessRequestPopupFeedback');
        var submitButton = document.getElementById('omoAccessRequestPopupSubmit');

        if (!form || !feedback || !submitButton) {
            return;
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

        function notifyParent(result) {
            try {
                window.dispatchEvent(new CustomEvent('omo-access-request-submitted', {
                    detail: result || {}
                }));
            } catch (error) {
            }

            if (window.parent && window.parent !== window) {
                try {
                    window.parent.dispatchEvent(new CustomEvent('omo-access-request-submitted', {
                        detail: result || {}
                    }));
                } catch (error) {
                }
            }
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            feedback.textContent = '';
            feedback.classList.remove('is-success');
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

                feedback.textContent = result.data.message || 'Demande envoyee.';
                feedback.classList.add('is-success');
                notifyParent(result.data);

                window.setTimeout(function () {
                    closeModal();
                }, 220);
            })
            .catch(function () {
                feedback.textContent = 'Une erreur est survenue.';
                submitButton.disabled = false;
            });
        });
    })();
</script>
