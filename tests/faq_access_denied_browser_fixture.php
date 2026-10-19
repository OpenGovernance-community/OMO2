<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
mcpCheck(($GLOBALS['mailHost'] ?? '') === 'mailpit' && (int)($GLOBALS['mailPort'] ?? 0) === 1025
    && empty($GLOBALS['mailAuth']), 'Local Mailpit is required for the synthetic support reply.');
$items = mcpFixtures(false);
try {
    $nonce = bin2hex(random_bytes(6));
    $password = 'FAQ-browser-' . $nonce . '!';
    foreach (['admin' => true, 'outsider' => false] as $name => $isSiteAdmin) {
        $items[$name] = mcpFixture(\dbObject\User::class, ['email' => 'faq-' . $name . '-' . $nonce . '@example.invalid',
            'password' => commonHashUserPassword($password), 'allow_password_login' => 1, 'active' => 1, 'siteadmin' => $isSiteAdmin]);
    }
    $items['request'] = mcpFixture(\dbObject\FAQ::class, ['question' => 'FAQ outside organization ' . $nonce,
        'request_description' => 'Synthetic support request', 'request_user_id' => $items['user']->getId(),
        'request_author_name' => 'Synthetic author', 'request_author_email' => $items['user']->get('email'),
        'IDorganization' => $items['org']->getId(), 'IDholon' => $items['root']->getId(), 'isactive' => 0, 'answer' => '']);
    echo json_encode(['origin' => 'https://localtest.me', 'oid' => (int)$items['org']->getId(),
        'faqId' => (int)$items['request']->getId(), 'adminEmail' => $items['admin']->get('email'),
        'outsiderEmail' => $items['outsider']->get('email'), 'password' => $password]) . "\n";
    fflush(STDOUT);
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    fgets(STDIN);
} finally { mcpCleanup($items); }
