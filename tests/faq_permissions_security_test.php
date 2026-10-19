<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{FAQ, User, UserOrganization, UserHolon, HolonPermission, Permission, Parcours};

// HTTP requests are pinned to this Docker container; never use a configured public URL.
$jar = tempnam(sys_get_temp_dir(), 'omo-faq-security-');
$items = [];
$previousSession = $_SESSION;
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
function faqSecurityHttp(string $path, ?array $data = null, string $method = 'POST', ?string $origin = 'https://localtest.me'): array
{
    global $jar;
    $headers = ['Accept: application/json', 'X-Requested-With: XMLHttpRequest'];
    if ($origin !== null) $headers[] = 'Origin: ' . $origin;
    $curl = curl_init('https://localtest.me' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_RESOLVE => ['localtest.me:443:127.0.0.1'], CURLOPT_PROXY => '',
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method]);
    if ($data !== null) curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    $body = curl_exec($curl);
    mcpCheck(is_string($body), 'Local FAQ HTTP request failed.');
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    unset($curl);
    return ['status' => $status, 'json' => json_decode($body, true), 'body' => $body];
}
function faqSecurityReload(FAQ $faq): FAQ
{
    mcpCheck((bool)$faq->load((int)$faq->getId(), true), 'FAQ fixture must still exist.');
    return $faq;
}
try {
    $nonce = bin2hex(random_bytes(6));
    $password = 'FAQ-Fixture-Password!48-' . $nonce;
    $items['user'] = $user = mcpFixture(User::class, ['email' => 'faq-security-' . $nonce . '@example.invalid',
        'password' => commonHashUserPassword($password), 'allow_password_login' => 1, 'active' => 1]);
    $items['org'] = $org = mcpFixture(McpTestOrganization::class, ['name' => 'FAQ security ' . $nonce]);
    $items['membership'] = $membership = mcpFixture(UserOrganization::class, ['IDuser' => $user->getId(),
        'IDorganization' => $org->getId(), 'active' => 1, 'parameters' => ['isAdmin' => false]]);
    $app = new \dbObject\Application();
    foreach (['structure', 'lms'] as $hash) {
        if ($app->load([['hash', $hash]])) {
            $items['app_' . $hash] = mcpFixture(\dbObject\OrganizationApplication::class,
                ['IDorganization' => $org->getId(), 'IDapplication' => $app->getId(), 'active' => 1]);
        }
    }
    $items['root'] = $root = mcpFixture(McpTestHolon::class, ['IDorganization' => $org->getId(),
        'name' => 'FAQ root', 'IDtypeholon' => 2, 'visible' => 1, 'active' => 1]);
    $root->set('IDholon_org', $root->getId()); $root->save();
    $items['role'] = $role = mcpFixture(McpTestHolon::class, ['IDorganization' => $org->getId(),
        'IDholon_org' => $root->getId(), 'IDholon_parent' => $root->getId(),
        'name' => 'FAQ role', 'IDtypeholon' => 1, 'visible' => 1, 'active' => 1]);
    foreach (['root' => $root, 'role' => $role] as $name => $holon) {
        $items['assignment_' . $name] = mcpFixture(UserHolon::class, ['IDuser' => $user->getId(),
            'IDholon' => $holon->getId(), 'active' => 1, 'is_membership' => 1]);
    }
    $items['other_org'] = $otherOrg = mcpFixture(McpTestOrganization::class, ['name' => 'Other FAQ ' . $nonce]);
    $items['other_membership'] = mcpFixture(UserOrganization::class, ['IDuser' => $user->getId(),
        'IDorganization' => $otherOrg->getId(), 'active' => 1]);
    $items['other_root'] = $otherRoot = mcpFixture(McpTestHolon::class, ['IDorganization' => $otherOrg->getId(),
        'name' => 'Other FAQ root', 'IDtypeholon' => 2, 'visible' => 1, 'active' => 1]);
    $otherRoot->set('IDholon_org', $otherRoot->getId()); $otherRoot->save();
    $items['other_assignment'] = mcpFixture(UserHolon::class, ['IDuser' => $user->getId(),
        'IDholon' => $otherRoot->getId(), 'active' => 1, 'is_membership' => 1]);
    $grant = static function (string $name, string $key, $holon) use (&$items): HolonPermission {
        $permission = Permission::findByKey($key);
        mcpCheck($permission !== null, 'Missing existing FAQ permission ' . $key);
        return $items[$name] = mcpFixture(HolonPermission::class, ['IDholon' => $holon->getId(),
            'IDpermission' => $permission->getId(), 'member_type' => 'member', 'range' => 'self']);
    };
    $fields = ['question' => 'FAQ fixture ' . $nonce, 'answer' => 'Original answer', 'isactive' => 1,
        'IDorganization' => $org->getId(), 'IDholon' => $role->getId()];
    $items['faq'] = $faq = mcpFixture(FAQ::class, $fields);
    $items['foreign_faq'] = $foreign = mcpFixture(FAQ::class, array_replace($fields,
        ['IDorganization' => $otherOrg->getId(), 'IDholon' => $otherRoot->getId()]));
    $query = '?oid=' . $org->getId() . '&cid=' . $role->getId();
    $update = '/ajax/faq_update.php' . $query . '&id=' . $faq->getId();
    $delete = '/ajax/faq_delete.php' . $query . '&id=' . $faq->getId();
    $save = '/ajax/faq_save.php' . $query;
    $input = ['IDorganization' => $org->getId(), 'IDholon' => $role->getId(),
        'faq_scope_kind' => 'organization', 'question' => 'Created FAQ ' . $nonce, 'answer' => 'Created answer'];
    $credentials = ['email' => $user->get('email'), 'password' => $password];
    $login = faqSecurityHttp('/common/login_password.php', $credentials);
    mcpCheck(($login['json']['status'] ?? '') === 'ok', 'Fixture login must succeed.');

    foreach ([$save, $update, $delete] as $path) {
        mcpCheck(faqSecurityHttp($path, null, 'GET')['status'] === 405, 'GET FAQ mutations must be refused.');
        $denied = faqSecurityHttp($path, $input);
        mcpCheck($denied['status'] === 403, 'No FAQ right means no mutation: ' . $path . ' HTTP ' . $denied['status'] . ' ' . substr($denied['body'], 0, 300));
    }
    mcpCheck(faqSecurityReload($faq)->get('answer') === 'Original answer', 'Denied actions must preserve content.');
    $list = faqSecurityHttp('/popup/faq.php' . $query, null, 'GET');
    mcpCheck(!str_contains($list['body'], 'data-faq-add>'), 'The creation button must be hidden without creation rights.');

    $createGrant = $grant('create', 'CAN_CREATE_FAQ', $role);
    // CLI fixture changes do not invalidate a browser's read-only permission snapshot.
    mcpCheck((faqSecurityHttp('/common/login_password.php', $credentials)['json']['status'] ?? '') === 'ok', 'Refresh UI permission snapshot.');
    $list = faqSecurityHttp('/popup/faq.php' . $query, null, 'GET');
    mcpCheck(str_contains($list['body'], 'data-faq-add>'), 'The creation button must follow CAN_CREATE_FAQ: HTTP ' . $list['status']
        . ', attribute present=' . (int)str_contains($list['body'], 'data-faq-add') . ', length=' . strlen($list['body']));
    $forged = $input + ['id' => $faq->getId(), 'request_user_id' => $user->getId(),
        'request_author_email' => 'forged@example.invalid', 'request_description' => 'Forged',
        'request_ai_draft' => 1, 'viewcount' => 999, 'total_votes' => 999, 'positive_score' => 999];
    $result = faqSecurityHttp($save, $forged);
    mcpCheck(!empty($result['json']['status']), 'CAN_CREATE_FAQ must allow creation in its target.');
    $created = new FAQ();
    mcpCheck((bool)$created->load((int)$result['json']['id'], true), 'Created FAQ must exist.');
    $items['created_faq'] = $created;
    mcpCheck($created->getId() !== $faq->getId() && (int)$created->get('IDholon') === (int)$role->getId(), 'Creation must not overwrite a supplied ID.');
    mcpCheck(!(int)$created->get('request_user_id') && !$created->get('request_author_email')
        && !(int)$created->get('viewcount') && !(int)$created->get('total_votes') && !$created->hasAiDraft(),
        'Creation must ignore forged request metadata and scores.');
    mcpCheck(faqSecurityHttp($update, ['answer' => 'Forbidden edit'])['status'] === 403, 'Creation must not grant editing.');
    mcpCheck(faqSecurityHttp($delete, [])['status'] === 403, 'Creation must not grant deletion.');
    $forbiddenTarget = array_replace($input, ['IDorganization' => $otherOrg->getId(), 'IDholon' => $otherRoot->getId()]);
    mcpCheck(faqSecurityHttp($save, $forbiddenTarget)['status'] === 403, 'A foreign creation target must be rejected.');
    $createGrant->delete();
    mcpCheck(faqSecurityHttp($save, $input)['status'] === 403, 'Revoked creation rights must take effect on the next request.');

    $editGrant = $grant('edit', 'CAN_EDIT_FAQ', $role);
    mcpCheck((faqSecurityHttp('/common/login_password.php', $credentials)['json']['status'] ?? '') === 'ok', 'Refresh UI edit snapshot.');
    $detail = faqSecurityHttp('/ajax/faq_detail.php' . $query . '&id=' . $faq->getId(), null, 'GET');
    mcpCheck(str_contains($detail['body'], 'data-faq-edit ') && !str_contains($detail['body'], 'data-faq-delete '),
        'Editing and deletion buttons must follow their separate rights.');
    $result = faqSecurityHttp($update, ['answer' => 'Allowed edit', 'IDorganization' => $otherOrg->getId(),
        'IDholon' => $otherRoot->getId(), 'request_user_id' => $user->getId(),
        'request_author_email' => 'forged@example.invalid', 'request_ai_draft' => 1,
        'request_answered_at' => '2030-01-01 00:00:00', 'total_votes' => 999]);
    mcpCheck(!empty($result['json']['status']), 'CAN_EDIT_FAQ alone must allow content editing.');
    faqSecurityReload($faq);
    mcpCheck($faq->get('answer') === 'Allowed edit' && (int)$faq->get('IDholon') === (int)$role->getId()
        && (int)$faq->get('IDorganization') === (int)$org->getId() && !(int)$faq->get('request_user_id')
        && !$faq->get('request_author_email') && !(int)$faq->get('total_votes') && !$faq->hasAiDraft(),
        'Content editors must not alter scope, requester or scores.');
    mcpCheck(faqSecurityHttp($save, $input)['status'] === 403 && faqSecurityHttp($delete, [])['status'] === 403,
        'Editing must not grant creation or deletion.');
    $grant('foreign_edit', 'CAN_EDIT_FAQ', $otherRoot);
    mcpCheck(faqSecurityHttp('/ajax/faq_update.php' . $query . '&id=' . $foreign->getId(), ['answer' => 'Wrong context'])['status'] === 403,
        'A foreign FAQ must stay outside the requested organization even when the actor has rights in both.');
    $editGrant->delete();
    mcpCheck(faqSecurityHttp($update, ['answer' => 'Revoked edit'])['status'] === 403, 'Revoked edit rights must take effect.');

    $grant('delete', 'CAN_DELETE_FAQ', $role);
    mcpCheck((faqSecurityHttp('/common/login_password.php', $credentials)['json']['status'] ?? '') === 'ok', 'Refresh UI deletion snapshot.');
    $detail = faqSecurityHttp('/ajax/faq_detail.php' . $query . '&id=' . $faq->getId(), null, 'GET');
    mcpCheck(str_contains($detail['body'], 'data-faq-delete ') && !str_contains($detail['body'], 'data-faq-edit '),
        'Deletion must remain available without editing rights.');
    mcpCheck(faqSecurityHttp($delete, null, 'GET')['status'] === 405, 'GET must not delete even with deletion rights.');
    mcpCheck(faqSecurityHttp($delete, [], 'POST', 'https://other.localtest.me')['status'] === 403,
        'A sibling-origin request must not delete.');
    mcpCheck(faqSecurityHttp($delete, [], 'POST', null)['status'] === 403, 'A tokenless request without Origin must not delete.');
    faqSecurityReload($faq);
    mcpCheck(faqSecurityHttp($update, ['answer' => 'Delete is not edit'])['status'] === 403, 'Deletion must not grant editing.');
    $result = faqSecurityHttp($delete, []);
    mcpCheck(!empty($result['json']['status']), 'CAN_DELETE_FAQ alone must allow deletion.');
    $missing = new FAQ();
    mcpCheck(!$missing->load($faq->getId(), true), 'Deletion must remove only the authorized FAQ.');
    faqSecurityReload($foreign);

    // Same database permissions as HTTP; additionally exercise LMS and explicit admin modes.
    $_SESSION = ['currentUser' => (int)$user->getId(), 'currentOrganization' => (int)$org->getId(),
        'auth_security_version' => (int)$user->get('security_version')];
    $_GET = ['oid' => (int)$org->getId(), 'cid' => (int)$root->getId()];
    $_REQUEST = $_GET;
    require_once dirname(__DIR__) . '/omo/api/lms/inc/access.php';
    require_once dirname(__DIR__) . '/common/faq_popup_helper.php';
    $context = FAQ::resolvePopupContext($org->getId(), $root->getId());
    $grant('lms_edit', 'CAN_EDIT_PARCOURS', $root);
    mcpCheck(FAQ::canManageParcoursInContext($context, $user->getId(), false), 'Fixture must have LMS management rights.');
    mcpCheck(!faqPopupCanCreateParcoursFaqs($context, $user->getId(), false), 'LMS rights alone must not grant FAQ creation.');
    $rootCreate = $grant('root_create', 'CAN_CREATE_FAQ', $root);
    mcpCheck(faqPopupCanCreateParcoursFaqs($context, $user->getId(), false), 'LMS plus FAQ creation rights must allow parcours FAQ creation.');
    $items['parcours'] = $parcours = mcpFixture(Parcours::class, ['title' => 'FAQ parcours ' . $nonce,
        'IDorganization' => $org->getId(), 'ispublic' => 0, 'isarchived' => 0]);
    $parcoursInput = array_replace($input, ['faq_scope_kind' => 'parcours', 'IDholon' => '', 'IDparcours' => $parcours->getId()]);
    $result = faqSecurityHttp($save, $parcoursInput);
    mcpCheck(!empty($result['json']['status']), 'Parcours creation must enforce and accept both permissions over HTTP.');
    $parcoursFaq = new FAQ();
    mcpCheck((bool)$parcoursFaq->load((int)$result['json']['id'], true), 'Parcours FAQ must exist.');
    $items['parcours_faq'] = $parcoursFaq;
    $rootCreate->delete();
    mcpCheck(faqSecurityHttp($save, $parcoursInput)['status'] === 403, 'LMS users must lose FAQ creation immediately after revocation.');

    $items['org_admin'] = $orgAdmin = mcpFixture(User::class, ['email' => 'faq-org-admin-' . $nonce . '@example.invalid', 'active' => 1]);
    $items['org_admin_membership'] = mcpFixture(UserOrganization::class, ['IDuser' => $orgAdmin->getId(),
        'IDorganization' => $org->getId(), 'active' => 1, 'parameters' => ['isAdmin' => true]]);
    $_SESSION = ['currentUser' => (int)$orgAdmin->getId(), 'currentOrganization' => (int)$org->getId()];
    mcpCheck(!FAQ::currentViewerHasOrganizationAdminAccess($org->getId()), 'Administrator status without enabled mode must not bypass rights.');
    $_SESSION['isAdminByOrganization'][(int)$org->getId()] = true;
    mcpCheck(FAQ::currentViewerHasOrganizationAdminAccess($org->getId())
        && !FAQ::currentViewerHasOrganizationAdminAccess($otherOrg->getId()), 'Active organization admin mode must stay within its organization.');
    unset($_SESSION['isAdminByOrganization']);
    $items['site_admin'] = $siteAdmin = mcpFixture(User::class, ['email' => 'faq-site-admin-' . $nonce . '@example.invalid',
        'active' => 1, 'siteadmin' => 1]);
    $_SESSION = ['currentUser' => (int)$siteAdmin->getId(), 'currentOrganization' => (int)$org->getId()];
    mcpCheck(!FAQ::currentViewerHasSiteAdminAccess(), 'Site administrator status must also require enabled mode.');
    $_SESSION['isSiteAdminModeEnabled'] = true;
    $generic = new FAQ();
    mcpCheck(FAQ::currentViewerHasSiteAdminAccess() && $generic->canBeCreatedInContext([])
        && $generic->canBeEditedInContext([]) && $generic->canBeDeletedInContext([]), 'Active site admin mode must retain generic FAQ management.');

    echo "faq_permissions_security_test: OK (HTTP CRUD, GET/CSRF, field protection, scope isolation, LMS, revocation, admin modes)\n";
} finally {
    $_SESSION = $previousSession;
    mcpCleanup($items);
    if (is_file($jar)) unlink($jar);
}
