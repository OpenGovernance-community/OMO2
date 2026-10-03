<?php
require_once dirname(__DIR__) . '/email_layout.php';

function omoObjectMailDeliver(array $audience, array $row): bool
{
    // SMTP credentials need not be an email address (API key login, local relay, etc.).
    // Use OMO's configured sender when available; otherwise use the authenticated profile.
    $from = trim((string)commonReadRuntimeEnvValue('MAIL_FROM', ''));
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) $from = trim((string)($GLOBALS['mailUser'] ?? ''));
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) $from = $audience['sender']['email'];
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) throw new DomainException('Adresse du profil expediteur indisponible.');
    $signature = $audience['sender']['name'] . ' - ' . $audience['organization_name'] . "\n" . $audience['title'];
    $plain = $row['message'] . "\n\n" . $signature;
    $html = commonRenderMailLayout(['brand_name' => $audience['organization_name'], 'heading' => $row['subject'],
        'body_html' => commonMailTextToHtml($row['message']), 'footer_html' => commonMailTextToHtml($signature)]);
    return myHTMLMail([$from, $audience['sender']['name'] . ' - ' . $audience['organization_name']], $row['email'], $row['subject'], $html,
        null, null, [], $plain, [$audience['sender']['email'], $audience['sender']['name']]);
}
