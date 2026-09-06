<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use dbObject\Document;
use dbObject\DocumentShareLink;
use dbObject\Event;
use dbObject\Organization;

$sourceLang = [
    'documents.pv_invitations.denied' => ['text' => 'Vous ne pouvez pas envoyer les invitations de ce PV.', 'context' => 'Error shown when the current user cannot send PV invitations.'],
    'documents.pv_invitations.title' => ['text' => 'Envoyer les invitations', 'context' => 'Title of the PV invitation sending popup.'],
    'documents.pv_invitations.intro' => ['text' => 'Le lien ouvre le PV en direct. Chaque invité peut ajouter et modifier ses propres points tant que le PV n’est pas validé.', 'context' => 'Explanation shown above the PV invitation email editor.'],
    'documents.pv_invitations.no_recipient' => ['text' => 'Aucun invité ne possède une adresse e-mail valide.', 'context' => 'Empty state when no PV invitation email recipient can be found.'],
    'documents.pv_invitations.recipient_count' => ['one' => '{count} destinataire recevra un lien individuel vers la réunion.', 'other' => '{count} destinataires recevront un lien individuel vers la réunion.', 'context' => 'Recipient count shown in the PV invitation email editor.'],
    'documents.pv_invitations.message' => ['text' => 'Texte du message', 'context' => 'Label for the customizable PV invitation email message.'],
    'documents.pv_invitations.send' => ['text' => 'Envoyer les invitations', 'context' => 'Submit button for sending PV invitation emails.'],
    'documents.pv_invitations.message_required' => ['text' => 'Le texte du message est obligatoire.', 'context' => 'Error returned when the PV invitation email message is empty.'],
    'documents.pv_invitations.share_error' => ['text' => 'Le lien public de la réunion n’a pas pu être créé. Aucun e-mail n’a été envoyé.', 'context' => 'Error returned when the public PV share link cannot be created before invitation emails are sent.'],
    'documents.pv_invitations.send_error' => ['text' => 'Aucune invitation n’a pu être envoyée.', 'context' => 'Error returned when every PV invitation email failed.'],
    'documents.pv_invitations.send_result' => ['one' => '{count} invitation envoyée.', 'other' => '{count} invitations envoyées.', 'context' => 'Success message after sending one or more PV invitation emails.'],
    'documents.pv_invitations.send_partial' => ['one' => '{count} envoi a échoué.', 'other' => '{count} envois ont échoué.', 'context' => 'Partial failure message after sending PV invitation emails.'],
    'documents.pv_invitations.default_message' => ['text' => "Bonjour,\n\nVous êtes invité à participer à la réunion « {title} ».\n\nUtilisez le lien ci-dessous pour consulter le PV en direct et ajouter vos propres points.\n\nÀ bientôt,\n{organization}", 'context' => 'Default body of the PV invitation email.'],
    'documents.pv_invitations.subject' => ['text' => 'Invitation à la réunion : {title}', 'context' => 'Subject of the PV invitation email.'],
    'documents.pv_invitations.open' => ['text' => 'Ouvrir la réunion', 'context' => 'Call to action in the PV invitation email.'],
    'documents.pv_invitations.footer' => ['text' => 'Cette invitation a été envoyée depuis {organization}.', 'context' => 'Footer of the PV invitation email.'],
    'documents.pv_invitations.network_error' => ['text' => 'Impossible d’envoyer les invitations pour le moment.', 'context' => 'Browser error shown when sending PV invitation emails fails.'],
    'documents.pv_invitations.fallback_title' => ['text' => 'Réunion', 'context' => 'Fallback title of a PV invitation when the PV has no title.'],
    'documents.pv_invitations.fallback_organization' => ['text' => 'Organisation', 'context' => 'Fallback organization name in a PV invitation.'],
];
$lang = omoLoadTranslationBundle('omo_documents_pv_send_invitations', $sourceLang);

function omoDocumentsPvSendInvitationT(string $key, array $replace = []): string
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

function omoDocumentsPvSendInvitationJson(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function omoDocumentsPvSendInvitationEmail(Document $document, Organization $organization, array $recipient, string $accessUrl, string $message): array
{
    $email = trim((string)($recipient['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $accessUrl === '') {
        return [
            'status' => false,
            'reason' => 'invalid_recipient',
        ];
    }

    require_once dirname(__DIR__, 4) . '/common/email_layout.php';

    $organizationName = trim((string)$organization->get('name'));
    $documentTitle = trim((string)$document->get('title'));
    if ($documentTitle === '') {
        $documentTitle = omoDocumentsPvSendInvitationT('documents.pv_invitations.fallback_title');
    }
    if ($organizationName === '') {
        $organizationName = omoDocumentsPvSendInvitationT('documents.pv_invitations.fallback_organization');
    }

    $fromAddress = trim((string)($GLOBALS['mailUser'] ?? ''));
    if ($fromAddress === '') {
        $host = preg_replace('/:\d+$/', '', commonGetRootHost() ?: 'localhost');
        $fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
    }

    $subject = omoDocumentsPvSendInvitationT('documents.pv_invitations.subject', ['title' => $documentTitle]);
    $html = commonRenderMailLayout([
        'brand_name' => $organizationName,
        'brand_color' => trim((string)$organization->get('color')),
        'logo_url' => trim((string)$organization->get('logo')),
        'banner_url' => trim((string)$organization->get('banner')),
        'heading' => $documentTitle,
        'intro_html' => commonMailTextToHtml($message),
        'button_label' => omoDocumentsPvSendInvitationT('documents.pv_invitations.open'),
        'button_url' => $accessUrl,
        'footer_html' => '<p style="margin:0;">' . commonMailEscape(omoDocumentsPvSendInvitationT('documents.pv_invitations.footer', ['organization' => $organizationName])) . '</p>',
    ]);

    $mailSent = (bool)myHTMLMail([$fromAddress, $organizationName], $email, $subject, $html);
    return [
        'status' => $mailSent,
        'reason' => $mailSent ? '' : 'mail_failed',
        'mailError' => $mailSent || !function_exists('appGetLastMailError') ? '' : appGetLastMailError(),
    ];
}

$documentId = (int)($_REQUEST['id'] ?? 0);
$organizationId = (int)($_REQUEST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$currentUserId = (int)commonGetCurrentUserId();
$document = new Document();
$organization = new Organization();
$canSend = $documentId > 0
    && $organizationId > 0
    && $currentUserId > 0
    && $document->load($documentId)
    && $organization->load($organizationId)
    && (int)$document->get('IDorganization') === $organizationId
    && $document->isPvDocument()
    && $document->getPvStage() === Document::PV_STAGE_PREPARATION
    && $document->canUserManagePvDocument($currentUserId);

if (!$canSend) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        omoDocumentsPvSendInvitationJson(403, ['status' => false, 'message' => omoDocumentsPvSendInvitationT('documents.pv_invitations.denied')]);
    }

    http_response_code(403);
    ?><div class="omo-empty-state"><?= omoApiEscape(omoDocumentsPvSendInvitationT('documents.pv_invitations.denied')) ?></div><?php
    exit;
}

$event = $document->getAssociatedEvent();
$recipients = $event instanceof Event
    ? $event->getInvitationEmailRecipients($organizationId)
    : $document->getInvitationEmailRecipients($organizationId);
$documentTitle = trim((string)$document->get('title'));
if ($documentTitle === '') {
    $documentTitle = omoDocumentsPvSendInvitationT('documents.pv_invitations.fallback_title');
}
$organizationName = trim((string)$organization->get('name'));
if ($organizationName === '') {
    $organizationName = omoDocumentsPvSendInvitationT('documents.pv_invitations.fallback_organization');
}
$defaultMessage = omoDocumentsPvSendInvitationT('documents.pv_invitations.default_message', [
    'title' => $documentTitle,
    'organization' => $organizationName,
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim((string)($_POST['message'] ?? ''));
    if ($message === '') {
        omoDocumentsPvSendInvitationJson(422, ['status' => false, 'message' => omoDocumentsPvSendInvitationT('documents.pv_invitations.message_required')]);
    }

    if (count($recipients) === 0) {
        omoDocumentsPvSendInvitationJson(422, ['status' => false, 'message' => omoDocumentsPvSendInvitationT('documents.pv_invitations.no_recipient')]);
    }

    $sentCount = 0;
    $failedCount = 0;
    $lastMailError = '';
    $lastShareError = '';
    foreach ($recipients as $recipient) {
        $shareLink = DocumentShareLink::getOrCreatePvParticipantLink(
            $document,
            $currentUserId,
            (string)($recipient['email'] ?? ''),
            (int)($recipient['user_id'] ?? 0)
        );
        if (!($shareLink instanceof DocumentShareLink)) {
            $dbError = \dbObject\DbObject::getLastDbError();
            $lastShareError = trim((string)($dbError['message'] ?? '')) ?: $lastShareError;
            $failedCount++;
            continue;
        }

        $accessUrl = commonBuildUrl($shareLink->buildPvParticipationUrl(), commonGetRequestHost());
        $sendResult = omoDocumentsPvSendInvitationEmail($document, $organization, $recipient, $accessUrl, $message);
        if (!empty($sendResult['status'])) {
            $sentCount++;
        } else {
            $failedCount++;
            $lastMailError = trim((string)($sendResult['mailError'] ?? '')) ?: $lastMailError;
        }
    }

    if ($sentCount === 0) {
        $response = [
            'status' => false,
            'message' => $lastShareError !== ''
                ? omoDocumentsPvSendInvitationT('documents.pv_invitations.share_error')
                : omoDocumentsPvSendInvitationT('documents.pv_invitations.send_error'),
        ];
        $diagnostic = $lastShareError !== '' ? $lastShareError : $lastMailError;
        if ($diagnostic !== '') {
            $response['diagnostic'] = $diagnostic;
        }
        omoDocumentsPvSendInvitationJson(500, $response);
    }

    $resultMessage = omoDocumentsPvSendInvitationT('documents.pv_invitations.send_result', ['count' => $sentCount]);
    if ($failedCount > 0) {
        $resultMessage .= ' ' . omoDocumentsPvSendInvitationT('documents.pv_invitations.send_partial', ['count' => $failedCount]);
    }

    omoDocumentsPvSendInvitationJson(200, [
        'status' => true,
        'message' => $resultMessage,
        'shareUrl' => '',
    ]);
}
?>
<form id="omoPvSendInvitationsForm" class="generic-stack generic-stack--flush" action="/omo/api/documents/pv/send_invitations_popup.php?oid=<?= (int)$organizationId ?>&amp;id=<?= (int)$document->getId() ?>" method="post">
    <div class="generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy">
            <div class="generic-card-title generic-card-title--eyebrow">PV</div>
            <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoDocumentsPvSendInvitationT('documents.pv_invitations.title')) ?></h3>
        </div>
    </div>
    <div class="generic-drawer-content generic-stack">
        <p class="generic-description generic-description--relaxed"><?= omoApiEscape(omoDocumentsPvSendInvitationT('documents.pv_invitations.intro')) ?></p>
        <?php if (count($recipients) === 0): ?>
            <p class="generic-feedback generic-feedback--warning"><?= omoApiEscape(omoDocumentsPvSendInvitationT('documents.pv_invitations.no_recipient')) ?></p>
        <?php else: ?>
            <p class="generic-description"><?= omoApiEscape(omoDocumentsPvSendInvitationT('documents.pv_invitations.recipient_count', ['count' => count($recipients)])) ?></p>
        <?php endif; ?>
        <label class="generic-stack generic-stack--compact" for="omoPvSendInvitationsMessage">
            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoDocumentsPvSendInvitationT('documents.pv_invitations.message')) ?></span>
            <textarea id="omoPvSendInvitationsMessage" name="message" class="generic-form-control" rows="9"<?= count($recipients) > 0 ? '' : ' disabled' ?>><?= omoApiEscape($defaultMessage) ?></textarea>
        </label>
        <div id="omoPvSendInvitationsFeedback" class="generic-feedback" hidden></div>
        <div class="generic-action-row">
            <button type="submit" id="omoPvSendInvitationsSubmit" class="generic-action-button generic-action-button--main"<?= count($recipients) > 0 ? '' : ' disabled' ?>><?= omoApiEscape(omoDocumentsPvSendInvitationT('documents.pv_invitations.send')) ?></button>
        </div>
    </div>
</form>
<script>
(function () {
    const form = document.getElementById('omoPvSendInvitationsForm');
    const feedback = document.getElementById('omoPvSendInvitationsFeedback');
    const submitButton = document.getElementById('omoPvSendInvitationsSubmit');
    if (!form || !feedback || !submitButton) {
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        feedback.hidden = true;
        submitButton.disabled = true;
        fetch(form.getAttribute('action'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: new FormData(form)
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    return {ok: response.ok, payload: payload};
                });
            })
            .then(function (result) {
                feedback.textContent = result.payload && result.payload.message
                    ? result.payload.message
                    : <?= json_encode(omoDocumentsPvSendInvitationT('documents.pv_invitations.network_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                if (result.payload && result.payload.diagnostic) {
                    feedback.textContent += '\n' + result.payload.diagnostic;
                }
                feedback.classList.toggle('is-success', result.ok && result.payload && result.payload.status === true);
                feedback.hidden = false;
                submitButton.disabled = !(result.ok && result.payload && result.payload.status === true);
            })
            .catch(function () {
                feedback.textContent = <?= json_encode(omoDocumentsPvSendInvitationT('documents.pv_invitations.network_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                feedback.classList.remove('is-success');
                feedback.hidden = false;
                submitButton.disabled = false;
            });
    });
}());
</script>
