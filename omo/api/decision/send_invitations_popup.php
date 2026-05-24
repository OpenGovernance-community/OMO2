<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/modules/context.php';
require_once __DIR__ . '/modules/common.php';
require_once dirname(__DIR__, 3) . '/common/email_layout.php';

use dbObject\DecisionProcess;
use dbObject\DecisionParticipant;

function omoDecisionSendInvitationsPopupBuildDetailsHtml(DecisionProcess $decision)
{
    $items = [];
    $holon = $decision->getHolonObject();
    $consultationStart = null;
    $consultationEnd = null;

    $consultationStartValue = trim((string)$decision->get('consultation_start_at'));
    if ($consultationStartValue !== '') {
        try {
            $consultationStart = new DateTimeImmutable($consultationStartValue);
        } catch (Throwable $exception) {
            $consultationStart = null;
        }
    }

    $consultationEndValue = trim((string)$decision->get('consultation_end_at'));
    if ($consultationEndValue !== '') {
        try {
            $consultationEnd = new DateTimeImmutable($consultationEndValue);
        } catch (Throwable $exception) {
            $consultationEnd = null;
        }
    }

    if ($holon) {
        $items[] = '<li><strong>Contexte</strong>: '
            . commonMailEscape(trim((string)$holon->getTemplateLabel(true)) . ' ' . trim((string)$holon->getDisplayName()))
            . '</li>';
    }

    if ($consultationStart instanceof DateTimeInterface) {
        $items[] = '<li><strong>Debut</strong>: ' . commonMailEscape($consultationStart->format('d.m.Y H:i')) . '</li>';
    }

    if ($consultationEnd instanceof DateTimeInterface) {
        $items[] = '<li><strong>Fin</strong>: ' . commonMailEscape($consultationEnd->format('d.m.Y H:i')) . '</li>';
    }

    if (count($items) === 0) {
        return '';
    }

    return '<ul style="margin:0; padding-left:18px; color:#475569; line-height:1.7;">' . implode('', $items) . '</ul>';
}

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
$recipientList = $decision->getInvitationEmailRecipients();
$recipientCount = count($recipientList);
$isDraft = DecisionProcess::normalizeStatus($decision->get('status')) === DecisionProcess::STATUS_DRAFT;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    if ($isDraft) {
        omoDecisionModuleJsonResponse(422, [
            'status' => false,
            'message' => 'Envoyez les invitations une fois le scrutin sorti du brouillon.',
        ]);
    }

    if ($recipientCount === 0) {
        omoDecisionModuleJsonResponse(422, [
            'status' => false,
            'message' => 'Aucun destinataire avec une adresse e-mail valide n a ete trouve.',
        ]);
    }

    $message = trim((string)($_POST['message'] ?? ''));
    if ($message === '') {
        omoDecisionModuleJsonResponse(422, [
            'status' => false,
            'message' => 'Le texte du message est obligatoire.',
        ]);
    }

    $organizationName = trim((string)$organization->get('name'));
    $decisionTitle = trim((string)$decision->get('title'));
    $subject = 'Invitation a participer';
    if ($decisionTitle !== '') {
        $subject .= ' : ' . $decisionTitle;
    }

    $detailsHtml = omoDecisionSendInvitationsPopupBuildDetailsHtml($decision);
    $fromAddress = trim((string)($GLOBALS['mailUser'] ?? ''));
    if ($fromAddress === '') {
        $host = preg_replace('/:\d+$/', '', commonGetRootHost() ?: 'localhost');
        $fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
    }

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

        $hadToken = trim((string)$participant->get('access_token')) !== '';
        $accessUrl = $participant->getPublicAccessUrl();
        if (!$hadToken) {
            $tokenSaveResult = $participant->save();
            if (empty($tokenSaveResult['status'])) {
                $failedRecipients[] = $email;
                continue;
            }
        }

        $html = commonRenderMailLayout([
            'brand_name' => $organizationName,
            'brand_color' => trim((string)$organization->get('color')),
            'logo_url' => trim((string)$organization->get('logo')),
            'banner_url' => trim((string)$organization->get('banner')),
            'heading' => $decisionTitle !== '' ? $decisionTitle : 'Prise de decision',
            'intro_html' => commonMailTextToHtml($message),
            'details_html' => $detailsHtml,
            'button_label' => 'Ouvrir la prise de decision',
            'button_url' => $accessUrl,
            'footer_html' => '<p style="margin:0;">Ce message a ete envoye depuis ' . commonMailEscape($organizationName !== '' ? $organizationName : 'votre organisation') . '.</p>',
        ]);

        if (myHTMLMail([$fromAddress, $organizationName !== '' ? $organizationName : 'Organisation'], $email, $subject, $html)) {
            $participant->markInvitationSent();
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
?>
<style>
.omo-decision-send-invitations-popup {
    display: grid;
    gap: 16px;
    padding: 8px 4px 4px;
    color: var(--color-text, #1f2937);
}

.omo-decision-send-invitations-popup__intro,
.omo-decision-send-invitations-popup__hint {
    margin: 0;
    color: var(--topbar-panel-muted, #64748b);
    line-height: 1.6;
}

.omo-decision-send-invitations-popup__textarea {
    min-height: 220px;
    --generic-form-control-border: var(--topbar-panel-border, #dbe3ef);
    --generic-form-control-background: var(--topbar-panel-bg, #ffffff);
    --generic-form-control-background-focus: var(--topbar-panel-bg, #ffffff);
    --generic-form-control-color: inherit;
}

.omo-decision-send-invitations-popup__actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.omo-decision-send-invitations-popup__feedback {
    min-height: 22px;
    color: #b91c1c;
    font-weight: 600;
}

.omo-decision-send-invitations-popup__feedback.is-success {
    color: #15803d;
}
</style>

<form
    id="omoDecisionSendInvitationsPopupForm"
    class="omo-decision-send-invitations-popup"
    action="/omo/api/decision/send_invitations_popup.php?oid=<?= (int)$organizationId ?>&cid=<?= (int)$targetHolonId ?>&id=<?= (int)$decision->getId() ?>&method=<?= urlencode($method) ?>"
    method="post"
>
    <p class="omo-decision-send-invitations-popup__intro">
        Personnalisez le message qui sera envoye a <?= (int)$recipientCount ?> destinataire<?= (int)$recipientCount === 1 ? '' : 's' ?> avant l envoi.
    </p>

    <?php if ($isDraft): ?>
    <p class="omo-decision-send-invitations-popup__hint">
        Ce scrutin est encore en brouillon. Sortez-le du brouillon avant d envoyer les invitations.
    </p>
    <?php elseif ($recipientCount === 0): ?>
    <p class="omo-decision-send-invitations-popup__hint">
        Aucun destinataire avec une adresse e-mail valide n a ete trouve pour ce scrutin.
    </p>
    <?php else: ?>
    <p class="omo-decision-send-invitations-popup__hint">
        Le rendu de l e-mail est centralise dans un helper partage pour faciliter l harmonisation des messages par la suite.
    </p>
    <?php endif; ?>

    <label for="omoDecisionSendInvitationsMessage"><strong>Texte du message</strong></label>
    <textarea
        id="omoDecisionSendInvitationsMessage"
        name="message"
        class="omo-decision-send-invitations-popup__textarea generic-form-control"
        <?= ($isDraft || $recipientCount === 0) ? 'disabled' : '' ?>
    ><?= omoApiEscape($messageValue) ?></textarea>

    <div id="omoDecisionSendInvitationsPopupFeedback" class="omo-decision-send-invitations-popup__feedback"></div>

    <div class="omo-decision-send-invitations-popup__actions">
        <button
            type="submit"
            id="omoDecisionSendInvitationsPopupSubmit"
            class="generic-action-button generic-action-button--main"
            <?= ($isDraft || $recipientCount === 0) ? 'disabled' : '' ?>
        >
            Envoyer les invitations
        </button>
    </div>
</form>

<script>
(function () {
    var form = document.getElementById('omoDecisionSendInvitationsPopupForm');
    var feedback = document.getElementById('omoDecisionSendInvitationsPopupFeedback');
    var submitButton = document.getElementById('omoDecisionSendInvitationsPopupSubmit');

    if (!form || !feedback || !submitButton) {
        return;
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
                submitButton.disabled = false;
            });
    });
})();
</script>
