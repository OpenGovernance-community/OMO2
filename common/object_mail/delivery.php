<?php
require_once dirname(__DIR__) . '/email_layout.php';

/** Resolve a stable technical sender, including for workers without an HTTP request. */
function omoObjectMailFromAddress(): string
{
    $from = trim((string)commonReadRuntimeEnvValue('MAIL_FROM', ''));
    if ($from !== '') {
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) throw new DomainException('Adresse MAIL_FROM invalide.');
        return $from;
    }
    // Prefer the SMTP account domain, not a colleague's domain or an arbitrary Host header.
    $smtpUser = trim((string)($GLOBALS['mailUser'] ?? ''));
    $domain = filter_var($smtpUser, FILTER_VALIDATE_EMAIL) ? substr(strrchr($smtpUser, '@'), 1) : '';
    if ($domain === '') {
        foreach (['AUTH_PUBLIC_URL', 'MCP_PUBLIC_URL'] as $key) {
            $url = trim((string)commonReadRuntimeEnvValue($key, ''));
            if ($url === '') continue;
            $domain = (string)(parse_url($url, PHP_URL_HOST) ?: '');
            if ($domain !== '') break;
        }
    }
    // The development relay has no authenticated domain, including in CLI workers.
    if ($domain === '' && empty($GLOBALS['mailAuth']) && in_array(strtolower((string)($GLOBALS['mailHost'] ?? '')), ['mailpit', 'localhost', '127.0.0.1', '::1'], true)) {
        $domain = 'localhost.localdomain';
    }
    $from = 'noreply@' . strtolower($domain);
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) throw new DomainException('Configurez MAIL_FROM avec une adresse technique du domaine d envoi.');
    return $from;
}

function omoObjectMailDeliver(array $audience, array $row): bool
{
    $from = omoObjectMailFromAddress();
    $signature = $audience['sender']['name'] . ' - ' . $audience['organization_name'] . "\n" . $audience['title'];
    $plain = \dbObject\ObjectMail::messageText($row['message'], $row['message_format'] ?? 'plain') . "\n\n" . $signature;
    $html = commonRenderMailLayout(['brand_name' => $audience['organization_name'], 'heading' => $row['subject'],
        'body_html' => \dbObject\ObjectMail::renderMessage($row['message'], $row['message_format'] ?? 'plain'), 'footer_html' => commonMailTextToHtml($signature)]);
    return myHTMLMail([$from, $audience['sender']['name'] . ' - ' . $audience['organization_name']], $row['email'], $row['subject'], $html,
        null, null, [], $plain, [$audience['sender']['email'], $audience['sender']['name']]);
}
