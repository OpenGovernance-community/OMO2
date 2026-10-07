<?php
require_once dirname(__DIR__) . '/translation_bundles.php';

function omoObjectMailT(string $key, array $variables = []): string
{
    static $bundle;
    if ($bundle === null) {
        $sourceLang = [
            'folder_sent' => ['text' => 'Envoyes', 'context' => 'Sent email folder heading.'],
            'folder_scope' => ['text' => 'Vos 30 derniers messages, OMO et agents IA', 'context' => 'Sent mailbox scope.'],
            'from' => ['text' => 'De', 'context' => 'Email sender label.'],
            'you' => ['text' => 'Vous', 'context' => 'Current account owns the sent email.'],
            'to' => ['text' => 'A', 'context' => 'Email recipients label.'],
            'reply_address' => ['text' => 'Adresse de reponse', 'context' => 'Actual reply-to address of a composed email.'],
            'compose_subject' => ['text' => 'Objet', 'context' => 'Email subject line.'],
            'compose_placeholder' => ['text' => 'Redigez votre message...', 'context' => 'Empty email body hint.'],
            'message_invalid' => ['text' => 'Redigez un message de 20 000 caracteres maximum, mise en forme comprise.', 'context' => 'Required rich email content and storage limit.'],
            'retention_title' => ['text' => 'Conservation des messages', 'context' => 'Expandable email retention explanation.'],
            'status_sent' => ['text' => 'Envoye', 'context' => 'All recipients accepted by SMTP, not a read receipt.'],
            'status_pending' => ['text' => 'En cours', 'context' => 'Some recipients are still queued or sending.'],
            'status_issue' => ['text' => 'Envoi incomplet', 'context' => 'Email has failed, cancelled or uncertain recipients.'],
            'status_cancelled' => ['text' => 'Annule', 'context' => 'All recipients cancelled before sending.'],
            'history_empty' => ['text' => 'Aucun e-mail envoye pour votre compte dans cette organisation.', 'context' => 'Empty sender-owned outbox history.'],
            'history_intro' => ['text' => 'Les 30 derniers e-mails envoyes pour votre compte dans cette organisation, y compris par vos agents IA.', 'context' => 'Scope and limit of sent email history.'],
            'history_retention' => ['text' => 'Apres 30 jours, le contenu et les destinataires des envois termines sont effaces. La trace de l envoi et ses statuts sont conserves.', 'context' => 'Explanation of outbox retention and anonymization.'],
            'history_mcp' => ['text' => 'Agent IA (MCP)', 'context' => 'Email sent through an authorized MCP agent.'],
            'history_native' => ['text' => 'Depuis OMO', 'context' => 'Email sent through the native composer.'],
            'history_expired' => ['text' => 'Contenu expire', 'context' => 'Email whose stored content has been cleared by retention.'],
            'history_recipient_expired' => ['text' => 'Destinataire anonymise', 'context' => 'Recipient address removed by retention.'],
            'history_back' => ['text' => 'Retour aux messages', 'context' => 'Return from sent email detail to history.'],
            'history_open' => ['text' => 'Lire le message', 'context' => 'Open the stored subject, content and recipients.'],
            'history_unavailable' => ['text' => 'Historique indisponible.', 'context' => 'Sent email history could not be loaded.'],
            'history_smtp_hint' => ['text' => 'Accepte par le serveur e-mail indique l acceptation de l envoi, sans garantir sa reception finale.', 'context' => 'SMTP acceptance is not proof of final receipt.'],
            'title' => ['text' => 'Envoyer un e-mail', 'context' => 'Object mail composer title and action.'],
            'popup_unavailable' => ['text' => 'La popup OMO est indisponible. Rechargez la page.', 'context' => 'Mail popup launch failure.'],
            'sending_progress' => ['text' => 'Envoi en cours...', 'context' => 'Persistent progress while posting mail.'],
            'recipients' => ['one' => '{count} destinataire', 'other' => '{count} destinataires', 'context' => 'Object mail preview count.'],
            'select_all' => ['text' => 'Tout cocher', 'context' => 'Select every eligible mail recipient.'],
            'select_none' => ['text' => 'Tout decocher', 'context' => 'Deselect every mail recipient.'],
            'selection_empty' => ['text' => 'Selectionnez au moins un destinataire.', 'context' => 'Mail recipient selection validation.'],
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

/** One overall state for the mailbox, reading pane and delivery screen. */
function omoObjectMailDeliveryState(array $counts): array
{
    if (($counts['queued'] ?? 0) + ($counts['sending'] ?? 0) > 0) return ['pending', ''];
    if (($counts['skipped'] ?? 0) > 0 && (int)$counts['skipped'] === array_sum($counts)) return ['cancelled', 'muted'];
    if (($counts['failed'] ?? 0) + ($counts['skipped'] ?? 0) + ($counts['unknown'] ?? 0) > 0) return ['issue', 'warning'];
    return ($counts['sent'] ?? 0) > 0 ? ['sent', 'success'] : ['pending', ''];
}

function omoObjectMailIcon(string $name = 'mail'): string
{
    $paths = [
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'back' => '<path d="m12 5-7 7 7 7M5 12h14"/>',
        'send' => '<path d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13"/>',
    ];
    return '<svg class="object-mail-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['mail']) . '</svg>';
}
function omoObjectMailButton(int $oid, string $type, int $id, string $attribute = '', bool $menuItem = false): void
{
    if (!in_array($attribute, ['', 'data-omo-subdrawer-action', 'data-omo-calendar-drawer-action'], true)) return;
    try { $audience = \dbObject\ObjectAudience::resolve($oid, $type, $id); }
    catch (DomainException $error) { return; }
    if (!$audience['can_send'] || !$audience['recipients']) return;
    $url = '/omo/api/object_mail/index.php?' . http_build_query(['oid' => $oid, 'object_type' => $type, 'object_id' => $id]);
    echo '<button type="button" class="' . ($menuItem ? 'generic-menu-item' : 'generic-action-button generic-action-button--secondary') . '" ' . ($menuItem ? 'role="menuitem" ' : '') . $attribute
        . ' data-object-mail-open="' . omoObjectMailEscape($url) . '" data-object-mail-error="' . omoObjectMailEscape(omoObjectMailT('popup_unavailable'))
        . '">' . omoObjectMailEscape(omoObjectMailT('title')) . '</button>';
}
