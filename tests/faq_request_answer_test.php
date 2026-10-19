<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/faq_mail.php';

use dbObject\{FAQ, User, UserOrganization};

if (($argv[1] ?? '') === '--fail-mail') {
    // Real endpoint and persistence, with a deliberately closed local SMTP port.
    $_SESSION = ['currentUser' => (int)$argv[3], 'currentOrganization' => (int)$argv[4]];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET = ['id' => (int)$argv[2], 'oid' => (int)$argv[4], 'cid' => (int)$argv[5]];
    $_POST = ['faq_answer_request' => 1, 'faq_publish_answer' => 1, 'faq_scope_kind' => 'organization',
        'IDorganization' => (int)$argv[6], 'question' => 'Mail failure fixture', 'answer' => 'Saved for retry', 'detail' => '<p>Retry detail</p>'];
    $GLOBALS['mailHost'] = '127.0.0.1'; $GLOBALS['mailPort'] = 9; $GLOBALS['mailAuth'] = false; $GLOBALS['mailTimeout'] = 3;
    chdir(dirname(__DIR__) . '/ajax');
    require dirname(__DIR__) . '/ajax/faq_update.php';
    exit;
}

// Synthetic email delivery is permitted only to the development SMTP sink.
mcpCheck(in_array('--mailpit', $argv, true) && ($GLOBALS['mailHost'] ?? '') === 'mailpit'
    && (int)($GLOBALS['mailPort'] ?? 0) === 1025 && empty($GLOBALS['mailAuth']), 'Run with --mailpit and local unauthenticated Mailpit.');
$jar = tempnam(sys_get_temp_dir(), 'omo-faq-answer-');
$previousSession = $_SESSION;
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$items = mcpFixtures();

function faqAnswerHttp(string $path, ?array $data = null): array
{
    global $jar;
    $curl = curl_init('https://localtest.me' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_RESOLVE => ['localtest.me:443:127.0.0.1'], CURLOPT_PROXY => '',
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
        CURLOPT_HTTPHEADER => ['Origin: https://localtest.me', 'X-Requested-With: XMLHttpRequest']]);
    if ($data !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data)]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    unset($curl);
    mcpCheck(is_string($body), 'HTTP request failed.');
    return ['body' => $body, 'json' => json_decode($body, true), 'status' => $status];
}
function faqAnswerMailpit(string $path): array
{
    $curl = curl_init('http://mailpit:8025/api/v1/' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_PROXY => '']);
    $body = curl_exec($curl); unset($curl);
    return json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);
}
try {
    $uid = (int)$items['user']->getId(); $oid = (int)$items['org']->getId();
    $other = (int)$items['other_org']->getId();
    $nonce = bin2hex(random_bytes(6));
    $recipient = 'faq-answer-' . $nonce . '@example.invalid';
    $items['membership']->setOrganizationAdmin(true); $items['membership']->save();
    $items['destination'] = mcpFixture(McpTestOrganization::class, ['name' => 'FAQ destination ' . $nonce]);
    $destination = (int)$items['destination']->getId();
    $items['destination_admin'] = mcpFixture(UserOrganization::class,
        ['IDorganization' => $destination, 'IDuser' => $uid, 'active' => 1, 'parameters' => ['isAdmin' => true]]);
    $items['destination_root'] = mcpFixture(McpTestHolon::class, ['IDorganization' => $destination,
        'name' => 'FAQ destination root', 'IDtypeholon' => 2, 'visible' => 1, 'active' => 1]);
    $request = static function (string $name) use (&$items, $uid, $oid, $recipient): FAQ {
        return $items[$name] = mcpFixture(FAQ::class, ['question' => 'Synthetic request ' . $name,
            'answer' => '', 'IDorganization' => $oid, 'IDholon' => $items['role']->getId(), 'isactive' => 0,
            'request_user_id' => $uid, 'request_author_name' => 'Test author', 'request_author_email' => $recipient,
            'request_description' => 'Synthetic support request']);
    };
    $query = static fn(FAQ $faq) => '?oid=' . $oid . '&cid=' . $items['role']->getId() . '&id=' . $faq->getId();
    $login = faqAnswerHttp('/common/login_password.php', ['email' => $items['user']->get('email'), 'password' => $items['password']]);
    mcpCheck(($login['json']['status'] ?? '') === 'ok', 'Admin login must succeed.');
    $denied = faqAnswerHttp('/omo/o/' . $other . '/');
    mcpCheck($denied['status'] === 403 && str_contains($denied['body'], '"canAnswerFaq":false'), 'An administrator of another organization gets no request access.');
    $faq = $request('private_reply');
    $view = faqAnswerHttp('/ajax/faq_detail.php' . $query($faq) . '&edit=auto');
    mcpCheck($view['status'] === 200 && str_contains($view['body'], 'data-faq-answer-save')
        && str_contains($view['body'], 'data-faq-publish-answer'), 'An organization admin can reply without enabling admin mode.');
    mcpCheck(str_contains($view['body'], "summernote") && str_contains($view['body'], "name='detail'"), 'The detailed response uses the adminEdit HTML adapter.');
    mcpCheck(str_contains($view['body'], 'FAQ destination ' . $nonce) && !str_contains($view['body'], 'value="generic"')
        && !str_contains($view['body'], $items['other_org']->getLabel()), 'Offer all administered organizations, no generic or foreign destination.');
    mcpCheck(!str_contains($view['body'], "name='isactive'") && !str_contains($view['body'], "name='displayorder'"), 'Only the explicit publication checkbox controls publishing.');
    $body = ['faq_answer_request' => 1, 'question' => 'Synthetic private reply ' . $nonce, 'answer' => 'Short reply',
        'detail' => '<p><strong>Formatted answer</strong></p><ul><li>First step</li></ul><script>evil()</script>',
        'isactive' => 1, 'faq_scope_kind' => 'generic', 'IDorganization' => $other, 'request_author_email' => 'forged@example.invalid'];
    $saved = faqAnswerHttp('/ajax/faq_update.php' . $query($faq), $body);
    mcpCheck(!empty($saved['json']['status']), 'Private reply must succeed: ' . $saved['body']);
    $faq->load($faq->getId(), true);
    mcpCheck(!$faq->get('isactive') && $faq->hasRequestBeenAnswered() && !$faq->isPendingRequest()
        && $faq->getResolvedOrganizationId() === $oid && $faq->get('request_author_email') === $recipient,
        'Reply privately without publishing, moving the request, or accepting forged metadata.');
    $duplicate = faqAnswerHttp('/ajax/faq_update.php' . $query($faq), $body);
    mcpCheck($duplicate['status'] === 409, 'A repeated reply must not send another email.');

    $faq = $request('publish_reply');
    $body['faq_publish_answer'] = 1;
    foreach ([['faq_scope_kind' => 'generic'], ['faq_scope_kind' => 'organization', 'IDorganization' => $other],
        ['faq_scope_kind' => 'organization', 'IDorganization' => $destination, 'IDholon' => $items['other_root']->getId()]] as $invalid) {
        $denied = faqAnswerHttp('/ajax/faq_update.php' . $query($faq), array_replace($body, $invalid));
        mcpCheck($denied['status'] === 403, 'Reject unauthorized or cross-organization publication.');
        $faq->load($faq->getId(), true);
        mcpCheck($faq->isPendingRequest() && !$faq->get('isactive') && !$faq->hasRequestBeenAnswered(), 'Rejected publication sends no reply and changes no request state.');
    }
    $published = faqAnswerHttp('/ajax/faq_update.php' . $query($faq), array_replace($body,
        ['faq_scope_kind' => 'organization', 'IDorganization' => $destination, 'IDholon' => $items['destination_root']->getId(),
            'IDapplication' => $items['app']->get('IDapplication')]));
    mcpCheck(!empty($published['json']['status']), 'Admin may publish in another administered organization: ' . $published['body']);
    $faq->load($faq->getId(), true);
    mcpCheck($faq->get('isactive') && $faq->hasRequestBeenAnswered() && $faq->getResolvedOrganizationId() === $destination
        && (int)$faq->get('IDholon') === (int)$items['destination_root']->getId()
        && (int)$faq->get('IDapplication') === (int)$items['app']->get('IDapplication'), 'Persist the selected organization, space and app.');

    $failed = $request('failed_reply');
    $process = proc_open([PHP_BINARY, __FILE__, '--fail-mail', (string)$failed->getId(), (string)$uid, (string)$oid,
        (string)$items['role']->getId(), (string)$destination], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $diagnostic = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $code = proc_close($process);
    $failure = json_decode($output, true);
    mcpCheck($code === 0 && ($failure['status'] ?? null) === false, 'SMTP failure must report an error: ' . $output . $diagnostic);
    $failed->load($failed->getId(), true);
    mcpCheck($failed->isPendingRequest() && !$failed->get('isactive') && !$failed->hasRequestBeenAnswered()
        && $failed->getResolvedOrganizationId() === $oid && $failed->get('answer') === 'Saved for retry',
        'Failed mail preserves the answer for retry, restores its origin and leaves it unpublished.');

    $password = 'FAQ-admin-' . $nonce . '!';
    $items['site_admin'] = mcpFixture(User::class, ['email' => 'faq-site-' . $nonce . '@example.invalid', 'active' => 1,
        'password' => commonHashUserPassword($password), 'allow_password_login' => 1, 'siteadmin' => 1]);
    $login = faqAnswerHttp('/common/login_password.php', ['email' => $items['site_admin']->get('email'), 'password' => $password]);
    mcpCheck(($login['json']['status'] ?? '') === 'ok', 'Super admin login must succeed.');
    $faq = $request('generic_reply');
    $denied = faqAnswerHttp('/omo/o/' . $oid . '/');
    mcpCheck($denied['status'] === 403 && str_contains($denied['body'], 'organization-access-denied.js')
        && str_contains($denied['body'], '"canAnswerFaq":true') && !str_contains($denied['body'], '/omo/assets/js/app.js'),
        'Super admins retain the denied background and receive only the FAQ request launcher.');
    $view = faqAnswerHttp('/ajax/faq_detail.php' . $query($faq) . '&edit=auto');
    mcpCheck($view['status'] === 200 && str_contains($view['body'], 'value="generic"')
        && str_contains($view['body'], 'data-faq-answer-save') && str_contains($view['body'], 'structureData'), 'Super admin without enabled mode or membership can reply, choose generic FAQ and use its authorized space tree.');
    $shell = faqAnswerHttp('/popup/faq.php' . $query($faq));
    mcpCheck($shell['status'] === 200 && str_contains($shell['body'], 'data-faq-initial-id')
        && str_contains($shell['body'], 'data-faq-request-only="1"') && str_contains($shell['body'], 'data-faq-request-complete'),
        'Email links open only the authorized request, with standalone completion, for an inactive super admin.');
    $published = faqAnswerHttp('/ajax/faq_update.php' . $query($faq), array_replace($body, ['faq_scope_kind' => 'generic', 'IDholon' => '', 'IDapplication' => '']));
    mcpCheck(!empty($published['json']['status']), 'Super admin generic publication must succeed: ' . $published['body']);
    $faq->load($faq->getId(), true);
    mcpCheck($faq->get('isactive') && $faq->isGeneric() && $faq->hasRequestBeenAnswered(), 'Generic publication clears organization and space.');

    $messages = faqAnswerMailpit('search?query=' . rawurlencode('to:' . $recipient));
    mcpCheck(count($messages['messages'] ?? []) === 3, 'Exactly three replies reach the original recipient; rejections and duplicates send none.');
    $mail = faqAnswerMailpit('message/' . $messages['messages'][0]['ID']);
    mcpCheck(str_contains($mail['HTML'], '<strong>Formatted answer</strong>') && str_contains($mail['HTML'], '<li>First step</li>')
        && !str_contains($mail['HTML'], 'evil()'), 'Email preserves sanitized rich text, lists and emphasis.');
    echo "PASS FAQ request replies: inactive admin roles, private email, optional publication, target isolation, HTML mail and duplicate guards\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $previousSession;
    if (is_file($jar)) unlink($jar);
}
