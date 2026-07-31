<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/modules/context.php';
require_once __DIR__ . '/modules/common.php';
use dbObject\DecisionProcess;
use dbObject\DecisionParticipant;

$input = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? array_merge($_GET, $_POST)
    : $_GET;
$context = omoDecisionResolveEditorContext($input);

if (empty($context['status']) || empty($context['decision']) || !($context['decision'] instanceof DecisionProcess) || empty($context['canManage'])) {
    $statusCode = (int)($context['code'] ?? 403);
    http_response_code($statusCode);
    ?>
    <div class="omo-decision-send-invitations-popup__empty">Vous ne pouvez pas envoyer les invitations de ce scrutin.</div>
    <?php
    exit;
}

$decision = $context['decision'];
$organization = $context['organization'];
$organizationId = (int)$context['organizationId'];
$targetHolonId = (int)$context['targetHolonId'];
$currentUserId = (int)$context['currentUserId'];
$method = DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'));
$allRecipientList = $decision->getInvitationEmailRecipients(true, false);
$pendingRecipientList = $decision->getInvitationEmailRecipients(true, true);
$recipientList = $pendingRecipientList;
$recipientCount = count($recipientList);
$allRecipientCount = count($allRecipientList);
$isDraft = DecisionProcess::normalizeStatus($decision->get('status')) === DecisionProcess::STATUS_DRAFT;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    if ($isDraft) {
        omoDecisionModuleJsonResponse(422, [
            'status' => false,
            'message' => 'Envoyez les invitations une fois le scrutin sorti du brouillon.',
        ]);
    }

    $sendScope = trim((string)($_POST['send_scope'] ?? 'pending'));
    if ($sendScope !== 'all') {
        $sendScope = 'pending';
    }

    $recipientList = $sendScope === 'all' ? $allRecipientList : $pendingRecipientList;
    $recipientCount = count($recipientList);

    if ($recipientCount === 0) {
        $messageText = $sendScope === 'all'
            ? 'Aucun destinataire avec une adresse e-mail valide n a ete trouve.'
            : 'Tous les participants avec une adresse e-mail valide ont deja repondu.';
        omoDecisionModuleJsonResponse(422, [
            'status' => false,
            'message' => $messageText,
        ]);
    }

    $message = trim((string)($_POST['message'] ?? ''));
    if ($message === '') {
        omoDecisionModuleJsonResponse(422, [
            'status' => false,
            'message' => 'Le texte du message est obligatoire.',
        ]);
    }

    $subject = $decision->buildDefaultInvitationEmailSubject();

    $sentCount = 0;
    $failedRecipients = [];
    foreach ($recipientList as $recipient) {
        $email = trim((string)($recipient['email'] ?? ''));
        if ($email === '') {
            continue;
        }

        $participant = null;
        foreach ((array)($recipient['participant_ids'] ?? []) as $participantId) {
            $candidate = new DecisionParticipant();
            if ($candidate->load((int)$participantId) && (int)$candidate->get('active') === 1) {
                $participant = $candidate;
                break;
            }
        }

        if (!($participant instanceof DecisionParticipant)) {
            $failedRecipients[] = $email;
            continue;
        }

        $sendResult = omoDecisionSendParticipantAccessEmail($decision, $participant, $message);
        if (!empty($sendResult['status'])) {
            $sentCount++;
            continue;
        }

        $failedRecipients[] = $email;
    }

    if ($sentCount > 0) {
        $decision->saveInvitationEmailState([
            'last_message' => $message,
            'last_sent_at' => date('c'),
            'last_sent_by' => $currentUserId,
            'last_recipient_count' => $sentCount,
            'last_failed_count' => count($failedRecipients),
            'last_subject' => $subject,
            'last_scope' => $sendScope,
        ]);
    }

    if ($sentCount === 0) {
        omoDecisionModuleJsonResponse(500, [
            'status' => false,
            'message' => 'Aucune invitation n a pu etre envoyee.',
        ]);
    }

    $messageText = $sentCount === 1
        ? '1 invitation envoyee.'
        : $sentCount . ' invitations envoyees.';
    if (count($failedRecipients) > 0) {
        $messageText .= ' ' . count($failedRecipients) . ' envoi(s) ont echoue.';
    }

    omoDecisionModuleJsonResponse(200, [
        'status' => true,
        'message' => $messageText,
        'redirectUrl' => omoDecisionBuildEditorUrl($organizationId, $targetHolonId, (int)$decision->getId(), $method, 'manage'),
        'drawerTitle' => 'Prises de decision',
    ]);
}

$mailState = $decision->getInvitationEmailState();
$messageValue = trim((string)($mailState['last_message'] ?? ''));
if ($messageValue === '') {
    $messageValue = $decision->buildDefaultInvitationEmailMessage();
}
$canSendPending = !$isDraft && $recipientCount > 0;
$canSendAll = !$isDraft && $allRecipientCount > 0;
?>
<style>
.omo-decision-send-invitations-popup__textarea {
    min-height: 220px;
}

.omo-decision-send-invitations-popup__split {
    position: relative;
    display: inline-flex;
    align-items: stretch;
}

.omo-decision-send-invitations-popup__split-main {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.omo-decision-send-invitations-popup__split-toggle {
    min-width: 44px;
    padding-inline: 12px;
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    border-left-color: color-mix(in srgb, rgba(255, 255, 255, 0.28) 75%, transparent);
    font-size: 12px;
}

.omo-decision-send-invitations-popup__split-toggle::before {
    content: "";
    display: inline-block;
    width: 8px;
    height: 8px;
    border-right: 2px solid currentColor;
    border-bottom: 2px solid currentColor;
    transform: rotate(45deg) translateY(-1px);
}

.omo-decision-send-invitations-popup__menu {
    position: absolute;
    right: 0;
    bottom: calc(100% + 8px);
    min-width: 240px;
    padding: 8px;
    border: 1px solid var(--topbar-panel-border, #dbe3ef);
    border-radius: var(--radius-md);
    background: var(--topbar-panel-bg, #ffffff);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
    display: grid;
    gap: 6px;
    z-index: 20;
}

.omo-decision-send-invitations-popup__menu[hidden] {
    display: none;
}

.omo-decision-send-invitations-popup__menu-action {
    width: 100%;
    justify-content: flex-start;
    text-align: left;
    box-shadow: none;
}

</style>

<form
    id="omoDecisionSendInvitationsPopupForm"
    class="omo-decision-send-invitations-popup generic-stack generic-stack--flush"
    action="/omo/api/decision/send_invitations_popup.php?oid=<?= (int)$organizationId ?>&cid=<?= (int)$targetHolonId ?>&id=<?= (int)$decision->getId() ?>&method=<?= urlencode($method) ?>"
    method="post"
>
    <input type="hidden" id="omoDecisionSendInvitationsScope" name="send_scope" value="pending">
    <div class="omo-decision-send-invitations-popup__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-decision-send-invitations-popup__header-copy">
            <div class="generic-card-title generic-card-title--eyebrow">Prises de decision</div>
            <h3 class="generic-card-title generic-card-title--medium">Envoyer les invitations</h3>
        </div>
    </div>
    <div class="omo-decision-send-invitations-popup__shell generic-drawer-content">

    <p class="omo-decision-send-invitations-popup__intro generic-description generic-description--relaxed">
        <?php if ($recipientCount > 0): ?>
        Personnalisez le message qui sera envoye a <?= (int)$recipientCount ?> destinataire<?= (int)$recipientCount === 1 ? '' : 's' ?> n ayant pas encore repondu.
        <?php elseif ($allRecipientCount > 0): ?>
        Tous les destinataires avec une adresse e-mail valide ont deja repondu. Utilisez la fleche pour renvoyer le lien a tout le monde.
        <?php else: ?>
        Personnalisez le message avant l envoi des invitations.
        <?php endif; ?>
    </p>

    <?php if ($isDraft): ?>
    <p class="omo-decision-send-invitations-popup__hint generic-description generic-description--relaxed">
        Ce scrutin est encore en brouillon. Sortez-le du brouillon avant d envoyer les invitations.
    </p>
    <?php elseif ($allRecipientCount === 0): ?>
    <p class="omo-decision-send-invitations-popup__hint generic-description generic-description--relaxed">
        Aucun destinataire avec une adresse e-mail valide n a ete trouve pour ce scrutin.
    </p>
    <?php else: ?>
    <p class="omo-decision-send-invitations-popup__hint generic-description generic-description--relaxed">
        L action principale relance seulement les participants qui n ont pas encore repondu. La fleche permet aussi d envoyer le lien a tout le monde, y compris a l auteur s il fait partie des participants.
    </p>
    <?php endif; ?>

    <label for="omoDecisionSendInvitationsMessage"><strong>Texte du message</strong></label>
    <textarea
        id="omoDecisionSendInvitationsMessage"
        name="message"
        class="omo-decision-send-invitations-popup__textarea generic-form-control"
        <?= ($isDraft || $allRecipientCount === 0) ? 'disabled' : '' ?>
    ><?= omoApiEscape($messageValue) ?></textarea>

    <div id="omoDecisionSendInvitationsPopupFeedback" class="omo-decision-send-invitations-popup__feedback generic-feedback"></div>

    <div class="omo-decision-send-invitations-popup__actions generic-action-row">
        <div class="omo-decision-send-invitations-popup__split">
            <button
                type="submit"
                id="omoDecisionSendInvitationsPopupSubmit"
                class="generic-action-button generic-action-button--main omo-decision-send-invitations-popup__split-main"
                <?= $canSendPending ? '' : 'disabled' ?>
            >
                Envoyer aux non-repondants
            </button>
            <button
                type="button"
                id="omoDecisionSendInvitationsPopupToggle"
                class="generic-action-button generic-action-button--main omo-decision-send-invitations-popup__split-toggle"
                aria-haspopup="menu"
                aria-expanded="false"
                aria-controls="omoDecisionSendInvitationsPopupMenu"
                aria-label="Choisir un autre envoi"
                <?= $canSendAll ? '' : 'disabled' ?>
            >
            </button>
            <div
                id="omoDecisionSendInvitationsPopupMenu"
                class="omo-decision-send-invitations-popup__menu"
                role="menu"
                hidden
            >
                <button
                    type="button"
                    class="generic-action-button generic-action-button--secondary omo-decision-send-invitations-popup__menu-action"
                    data-send-scope="all"
                    role="menuitem"
                >
                    Envoyer a tout le monde (<?= (int)$allRecipientCount ?>)
                </button>
            </div>
        </div>
    </div>
    </div>
</form>

<script>
(function () {
    var form = document.getElementById('omoDecisionSendInvitationsPopupForm');
    var feedback = document.getElementById('omoDecisionSendInvitationsPopupFeedback');
    var submitButton = document.getElementById('omoDecisionSendInvitationsPopupSubmit');
    var toggleButton = document.getElementById('omoDecisionSendInvitationsPopupToggle');
    var menu = document.getElementById('omoDecisionSendInvitationsPopupMenu');
    var scopeField = document.getElementById('omoDecisionSendInvitationsScope');
    var canSendPending = <?= $canSendPending ? 'true' : 'false' ?>;
    var canSendAll = <?= $canSendAll ? 'true' : 'false' ?>;

    if (!form || !feedback || !submitButton || !toggleButton || !menu || !scopeField) {
        return;
    }

    function closeMenu() {
        menu.hidden = true;
        toggleButton.setAttribute('aria-expanded', 'false');
    }

    function openMenu() {
        if (toggleButton.disabled) {
            return;
        }
        menu.hidden = false;
        toggleButton.setAttribute('aria-expanded', 'true');
    }

    function applyAvailabilityState(isBusy) {
        submitButton.disabled = isBusy || !canSendPending;
        toggleButton.disabled = isBusy || !canSendAll;
    }

    submitButton.addEventListener('click', function () {
        scopeField.value = 'pending';
    });

    toggleButton.addEventListener('click', function () {
        if (menu.hidden) {
            openMenu();
            return;
        }

        closeMenu();
    });

    document.addEventListener('click', function (event) {
        if (!menu.hidden && !form.contains(event.target)) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });

    menu.addEventListener('click', function (event) {
        var actionButton = event.target.closest('[data-send-scope]');
        if (!actionButton) {
            return;
        }

        scopeField.value = actionButton.getAttribute('data-send-scope') || 'pending';
        closeMenu();
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }

        form.dispatchEvent(new Event('submit', { cancelable: true }));
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        closeMenu();
        feedback.textContent = '';
        feedback.classList.remove('is-success');
        applyAvailabilityState(true);

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
                    applyAvailabilityState(false);
                    return;
                }

                feedback.textContent = result.data.message || 'Invitations envoyees.';
                feedback.classList.add('is-success');

                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }

                if (result.data.redirectUrl && typeof window.omoDecisionOpenNestedDrawer === 'function') {
                    window.omoDecisionOpenNestedDrawer(result.data.drawerTitle || 'Prises de decision', result.data.redirectUrl, '');
                    return;
                }

                if (result.data.redirectUrl && typeof window.commonTopbarOpenDrawer === 'function') {
                    window.commonTopbarOpenDrawer(result.data.drawerTitle || 'Prises de decision', result.data.redirectUrl, 'fetch');
                }
            })
            .catch(function () {
                feedback.textContent = 'Impossible d envoyer ces invitations pour le moment.';
                applyAvailabilityState(false);
            });
    });

    applyAvailabilityState(false);
})();
</script>
