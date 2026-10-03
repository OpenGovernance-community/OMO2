<?php
require_once dirname(__DIR__) . '/translation_bundles.php';

function omoObjectMailT(string $key, array $variables = []): string
{
    static $bundle;
    if ($bundle === null) {
        $sourceLang = [
            'title' => ['text' => 'Envoyer un e-mail', 'context' => 'Object mail composer title and action.'],
            'popup_unavailable' => ['text' => 'La popup OMO est indisponible. Rechargez la page.', 'context' => 'Mail popup launch failure.'],
            'sending_progress' => ['text' => 'Envoi en cours...', 'context' => 'Persistent progress while posting mail.'],
            'recipients' => ['one' => '{count} destinataire', 'other' => '{count} destinataires', 'context' => 'Object mail preview count.'],
            'close' => ['text' => 'Fermer', 'context' => 'Close the mail popup.'],
            'cancel' => ['text' => 'Annuler', 'context' => 'Close the composer without sending.'],
            'reply_to' => ['text' => 'Les reponses arriveront a {email}.', 'context' => 'Reply address below the message field.'],
            'empty' => ['text' => 'Aucun destinataire avec une adresse e-mail.', 'context' => 'Empty mail audience.'],
            'too_many' => ['text' => 'Ce groupe depasse la limite de {count} destinataires.', 'context' => 'Mail audience exceeds the delivery limit.'],
            'unavailable' => ['text' => 'Formulaire indisponible.', 'context' => 'Persistent state when the mail object cannot be opened.'],
            'audience' => ['text' => 'Membres et invites', 'context' => 'Audience list heading.'],
            'subject' => ['text' => 'Objet du message', 'context' => 'Email subject label.'],
            'message' => ['text' => 'Message', 'context' => 'Plain text email body label.'],
            'send' => ['text' => 'Envoyer', 'context' => 'Explicit send action.'],
            'denied' => ['text' => 'Envoi indisponible. Verifiez vos droits et votre adresse e-mail dans votre profil.', 'context' => 'Persistent send permission state.'],
            'delivery_done' => ['text' => 'Envoi termine.', 'context' => 'All messages accepted by the outgoing mail server.'],
            'delivery_pending' => ['text' => 'Envoi en cours. Vous pouvez fermer cette fenetre.', 'context' => 'Background mail delivery started.'],
            'delivery_issue' => ['text' => 'Envoi incomplet. Consultez le suivi.', 'context' => 'Some deliveries failed, were skipped or have an uncertain result.'],
            'refresh' => ['text' => 'Actualiser le suivi', 'context' => 'Refresh delivery counters.'],
            'new' => ['text' => 'Rediger un autre message', 'context' => 'Start another explicit mail operation.'],
            'tracking' => ['text' => 'Suivi de l envoi', 'context' => 'Outbox status heading.'],
            'queued' => ['text' => 'En attente', 'context' => 'Queued delivery count label.'],
            'sending' => ['text' => 'En cours', 'context' => 'Claimed delivery count label.'],
            'sent' => ['text' => 'Acceptes par le serveur e-mail', 'context' => 'SMTP success, not final delivery guarantee.'],
            'failed' => ['text' => 'Echecs', 'context' => 'SMTP failure count.'],
            'skipped' => ['text' => 'Annules', 'context' => 'Skipped delivery count.'],
            'unknown' => ['text' => 'Resultat incertain', 'context' => 'Ambiguous SMTP outcome count.'],
            'tracking_hint' => ['text' => 'Les envois en echec ou incertains ne sont pas relances automatiquement.', 'context' => 'Shown only when a delivery failed or has an uncertain result.'],
        ];
        $bundle = [loadTranslationBundle('common_object_mail', commonAuthGetTranslationLocale(), $sourceLang), $sourceLang];
    }
    return t($key, $variables, $bundle[0], $bundle[1]);
}
function omoObjectMailEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function omoObjectMailButton(int $oid, string $type, int $id, string $attribute = ''): void
{
    if (!in_array($attribute, ['', 'data-omo-subdrawer-action', 'data-omo-calendar-drawer-action'], true)) return;
    try { $audience = \dbObject\ObjectAudience::resolve($oid, $type, $id); }
    catch (DomainException $error) { return; }
    if (!$audience['can_send'] || !$audience['recipients']) return;
    $url = '/omo/api/object_mail/index.php?' . http_build_query(['oid' => $oid, 'object_type' => $type, 'object_id' => $id]);
    echo '<button type="button" class="generic-action-button generic-action-button--secondary" ' . $attribute
        . ' data-object-mail-open="' . omoObjectMailEscape($url) . '" data-object-mail-error="' . omoObjectMailEscape(omoObjectMailT('popup_unavailable'))
        . '">' . omoObjectMailEscape(omoObjectMailT('title')) . '</button>';
}
