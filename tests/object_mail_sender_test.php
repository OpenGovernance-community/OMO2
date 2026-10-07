<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/object_mail/delivery.php';

$_ENV['MAIL_FROM'] = '';
$_ENV['AUTH_PUBLIC_URL'] = '';
$_ENV['MCP_PUBLIC_URL'] = '';
$GLOBALS['mailUser'] = 'smtp-account@example.org';
$GLOBALS['mailHost'] = 'relay.example.org';
$GLOBALS['mailAuth'] = true;
$_SERVER['HTTP_HOST'] = 'untrusted.example.net';
mcpCheck(omoObjectMailFromAddress() === 'noreply@example.org', 'SMTP domain determines the default sender, never HTTP_HOST');
unset($_SERVER['HTTP_HOST']);
mcpCheck(omoObjectMailFromAddress() === 'noreply@example.org', 'CLI workers resolve the same sender');
$_ENV['MAIL_FROM'] = 'noreply@configured.example.org';
mcpCheck(omoObjectMailFromAddress() === 'noreply@configured.example.org', 'Explicit technical sender takes priority');
$_ENV['MAIL_FROM'] = "noreply@example.org\r\nBcc: someone@example.org";
$denied = false;
try { omoObjectMailFromAddress(); } catch (DomainException $error) { $denied = true; }
mcpCheck($denied, 'Invalid explicit sender fails instead of falling back silently');
$_ENV['MAIL_FROM'] = '';
$GLOBALS['mailUser'] = 'smtp-api-key-login';
$_ENV['AUTH_PUBLIC_URL'] = 'https://omo.example.org';
mcpCheck(omoObjectMailFromAddress() === 'noreply@omo.example.org', 'API-key SMTP login uses the configured canonical domain');
$_ENV['AUTH_PUBLIC_URL'] = '';
$_ENV['MCP_PUBLIC_URL'] = 'https://mcp.example.org/mcp';
mcpCheck(omoObjectMailFromAddress() === 'noreply@mcp.example.org', 'MCP canonical domain works without an HTTP request');
$_ENV['MCP_PUBLIC_URL'] = '';
$denied = false;
try { omoObjectMailFromAddress(); } catch (DomainException $error) { $denied = true; }
mcpCheck($denied, 'Production without a configured domain never impersonates the member');
$GLOBALS['mailHost'] = 'mailpit'; $GLOBALS['mailAuth'] = false;
mcpCheck(omoObjectMailFromAddress() === 'noreply@localhost.localdomain', 'Local development relay works in web and CLI');
echo "object_mail_sender_test: OK\n";
