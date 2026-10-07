<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, Organization, Holon, User, UserOrganization, UserHolon, Application, OrganizationApplication, HolonPermission, Permission};

function assignmentAdminCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function assignmentAdminFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $key => $value) $object->set($key, $value);
    assignmentAdminCheck(!empty($object->save()['status']), 'Fixture save failed: ' . $class);
    return $object;
}
function assignmentAdminReload(UserHolon $link): UserHolon
{
    $fresh = new UserHolon();
    assignmentAdminCheck($fresh->load($link->getId(), true), 'Reload failed');
    return $fresh;
}
function assignmentAdminRender(Holon $context, User $member, Organization $org): string
{
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/omo/api/team/member_assignment_popup.php';
    $_GET = $_REQUEST = ['oid' => $org->getId(), 'hid' => $context->getId(), 'user_id' => $member->getId()];
    ob_start();
    require dirname(__DIR__) . '/omo/api/team/member_assignment_popup.php';
    return (string)ob_get_clean();
}

// No persistent data: all fixtures and handovers are rolled back.
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(5));
    $org = assignmentAdminFixture(Organization::class, ['name' => 'Assignment admin test', 'shortname' => 'assignment-' . $nonce,
        'interface_level' => Organization::INTERFACE_LEVEL_EXPERT]);
    $app = new Application();
    assignmentAdminCheck($app->load(['hash', 'structure']), 'Structure application missing');
    assignmentAdminFixture(OrganizationApplication::class, ['IDorganization' => $org->getId(), 'IDapplication' => $app->getId(), 'active' => 1]);
    $root = assignmentAdminFixture(Holon::class, ['name' => 'Root', 'IDorganization' => $org->getId(), 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $root->set('IDholon_org', $root->getId());
    $root->save();
    $makeHolon = static function (array $fields) use ($root, $org): Holon {
        return assignmentAdminFixture(Holon::class, $fields + ['name' => 'Role', 'IDtypeholon' => 1, 'IDorganization' => $org->getId(),
            'IDholon_org' => $root->getId(), 'IDholon_parent' => $root->getId(), 'active' => 1, 'visible' => 1]);
    };
    $template = $makeHolon(['templatename' => 'Admin test', 'admin_min' => 1, 'admin_max' => 1]);
    $role = $makeHolon(['IDholon_template' => $template->getId()]);
    $users = [];
    $links = [];
    for ($i = 0; $i < 4; $i++) {
        $users[$i] = assignmentAdminFixture(User::class, ['firstname' => 'Member ' . $i, 'email' => "assignment-$nonce-$i@example.invalid", 'active' => 1]);
        assignmentAdminFixture(UserOrganization::class, ['IDuser' => $users[$i]->getId(), 'IDorganization' => $org->getId(),
            'active' => 1, 'parameters' => $i === 0 ? ['isAdmin' => true] : []]);
        if ($i > 0) $links[$i] = assignmentAdminFixture(UserHolon::class, ['IDholon' => $role->getId(), 'IDuser' => $users[$i]->getId(),
            'active' => 1, 'is_membership' => 1, 'focus' => 'Keep focus', 'parameters' => $i === 1 ? ['isAdmin' => true, 'custom' => 'keep'] : []]);
    }
    $_SESSION['currentUser'] = $users[0]->getId();
    $_SESSION['currentOrganization'] = $org->getId();
    $_SESSION['auth_security_version'] = (int)$users[0]->get('security_version');
    assignmentAdminCheck(commonSetCurrentUserAdminMode(true, $org->getId()), 'Organization admin mode required');

    $options = $links[2]->getAdminTransitionOptions();
    assignmentAdminCheck($options['grant']['required'] === 1 && count($options['grant']['candidates']) === 1, 'Maximum must offer existing admin');
    $html = assignmentAdminRender($role, $users[2], $org);
    assignmentAdminCheck(str_contains($html, '<input type="checkbox" name="is_admin"') && str_contains($html, '<select name="admin_replacements[]"'), 'Editor must render checkbox and replacement selects');
    if (getenv('ASSIGNMENT_ADMIN_SCRIPT_OUTPUT')) {
        preg_match_all('~<script>(.*?)</script>~s', $html, $scripts);
        file_put_contents(getenv('ASSIGNMENT_ADMIN_SCRIPT_OUTPUT'), implode("\n", $scripts[1]));
    }
    $result = $links[2]->saveAssignmentWithAdmin([], true, [], false);
    assignmentAdminCheck(empty($result['status']), 'Promotion at maximum needs replacement');
    $result = $links[2]->saveAssignmentWithAdmin(['focus' => 'New focus'], true, [$users[1]->getId()], false);
    assignmentAdminCheck(!empty($result['status']), 'Replacement failed: ' . json_encode($result));
    $old = assignmentAdminReload($links[1]);
    assignmentAdminCheck(!$old->isHolonAdmin() && $old->get('active') && $old->get('is_membership'), 'Replaced admin must stay a member');
    assignmentAdminCheck($old->get('focus') === 'Keep focus' && $old->getParameter('custom') === 'keep', 'Replacement must preserve assignment and parameters');
    assignmentAdminCheck(assignmentAdminReload($links[2])->isHolonAdmin(), 'New admin must persist');

    $result = $links[2]->saveAssignmentWithAdmin([], false, [$users[3]->getId()], false);
    assignmentAdminCheck(($result['reason'] ?? '') === 'admin_stale', 'Stale checkbox must be rejected');
    $result = $links[2]->saveAssignmentWithAdmin([], false, [], true);
    assignmentAdminCheck(empty($result['status']), 'Minimum must require a successor');
    $result = $links[2]->saveAssignmentWithAdmin(['focus' => str_repeat('x', 251)], false, [$users[3]->getId()], true);
    assignmentAdminCheck(empty($result['status']), 'Invalid fields must reject entire handover');
    assignmentAdminCheck(assignmentAdminReload($links[2])->isHolonAdmin() && !assignmentAdminReload($links[3])->isHolonAdmin(), 'Failed save must roll back both admin statuses');
    $result = $links[2]->saveAssignmentWithAdmin([], false, [$users[3]->getId()], true);
    assignmentAdminCheck(!empty($result['status']), 'Successor save failed');
    assignmentAdminCheck(assignmentAdminReload($links[3])->isHolonAdmin() && !assignmentAdminReload($links[2])->isHolonAdmin(), 'Successor must replace current admin');

    foreach ([1, 2] as $i) { $member = assignmentAdminReload($links[$i]); $member->set('active', false); $member->save(); }
    $only = assignmentAdminReload($links[3]);
    $vacancyOptions = $only->getAdminTransitionOptions()['revoke'];
    assignmentAdminCheck($vacancyOptions['vacant'] && $vacancyOptions['required'] === 0 && count($vacancyOptions['candidates']) > 0,
        'No other context member must allow vacancy even with organization candidates');
    assignmentAdminCheck(!empty($only->saveAssignmentWithAdmin([], false, [], true)['status']), 'Vacancy save failed');
    assignmentAdminCheck((bool)assignmentAdminReload($only)->get('active'), 'Vacant admin position must not remove ordinary membership');

    $template->set('admin_min', 0); $template->set('admin_max', 0); $template->save();
    $only = assignmentAdminReload($only);
    assignmentAdminCheck($only->getAdminTransitionOptions()['max'] === 0, 'Zero maximum must be exposed to hide checkbox');
    assignmentAdminCheck(!str_contains(assignmentAdminRender($role, $users[3], $org), '<input type="checkbox" name="is_admin"'), 'Zero maximum must hide actual checkbox markup');
    assignmentAdminCheck(empty($only->saveAssignmentWithAdmin([], true, [], false)['status']), 'Zero maximum must also forbid promotion on server');
    assignmentAdminCheck(!empty($only->saveAssignmentWithAdmin(['focus' => 'Allowed edit'])['status']), 'Other fields must remain editable at zero maximum');

    $template->set('admin_max', null); $template->save();
    assignmentAdminCheck(!empty($only->saveAssignmentWithAdmin([], true, [], false)['status']), 'Unbounded role must allow promotion');
    assignmentAdminCheck(!empty($only->saveAssignmentWithAdmin([], false, [], true)['status']), 'Zero minimum must allow demotion');

    // Organization successors become active context members in the same transaction.
    $externalRole = $makeHolon(['IDholon_template' => $template->getId()]);
    $externalSubject = assignmentAdminFixture(UserHolon::class, ['IDholon' => $externalRole->getId(), 'IDuser' => $users[3]->getId(),
        'active' => 1, 'is_membership' => 1, 'parameters' => ['isAdmin' => true]]);
    assignmentAdminFixture(UserHolon::class, ['IDholon' => $externalRole->getId(), 'IDuser' => $users[1]->getId(), 'active' => 1, 'is_membership' => 1]);
    $technical = assignmentAdminFixture(UserHolon::class, ['IDholon' => $externalRole->getId(), 'IDuser' => $users[2]->getId(),
        'active' => 0, 'is_membership' => 0, 'parameters' => ['custom' => 'preserve']]);
    $externalOptions = $externalSubject->getAdminTransitionOptions()['revoke'];
    assignmentAdminCheck($externalOptions['required'] === 1 && !$externalOptions['vacant'], 'Other context members must require a successor even with minimum zero');
    assignmentAdminCheck($externalOptions['candidates'][0]['userId'] === (int)$users[1]->getId()
        && $externalOptions['candidates'][0]['group'] === 'context' && $externalOptions['candidates'][1]['group'] === 'organization', 'Context candidates must precede other organization members');
    assignmentAdminCheck(empty($externalSubject->saveAssignmentWithAdmin([], false, [], true)['status']), 'Last admin with peers must not leave without a successor');
    $outsider = assignmentAdminFixture(User::class, ['firstname' => 'Outside organization', 'email' => 'outside-' . $nonce . '@example.invalid', 'active' => 1]);
    assignmentAdminCheck(empty($externalSubject->saveAssignmentWithAdmin([], false, [$outsider->getId()], true)['status']), 'A user outside the organization must not be nominated');
    assignmentAdminCheck(empty($externalSubject->saveAssignmentWithAdmin([], false, [$users[1]->getId(), $users[1]->getId()], true)['status']), 'Duplicate selections must be rejected');
    $result = $externalSubject->saveAssignmentWithAdmin(['focus' => str_repeat('x', 251)], false, [$users[0]->getId()], true);
    assignmentAdminCheck(empty($result['status']), 'Invalid fields must roll back a new successor assignment');
    $newAdmin = new UserHolon();
    $newAdminCriteria = [['IDholon', $externalRole->getId()], ['IDuser', $users[0]->getId()]];
    assignmentAdminCheck(!$newAdmin->load($newAdminCriteria), 'Failed handover must leave no new membership');
    assignmentAdminCheck(!empty($externalSubject->saveAssignmentWithAdmin([], false, [$users[0]->getId()], true)['status']), 'Organization successor nomination failed');
    assignmentAdminCheck($newAdmin->load($newAdminCriteria) && $newAdmin->isHolonAdmin() && $newAdmin->get('active') && $newAdmin->get('is_membership'), 'Organization successor must become an active context admin');
    assignmentAdminCheck(!assignmentAdminReload($externalSubject)->isHolonAdmin() && assignmentAdminReload($externalSubject)->get('active'), 'Replaced admin remains a member');
    assignmentAdminCheck(!empty($newAdmin->saveAssignmentWithAdmin([], false, [$users[2]->getId()], true)['status']), 'Technical membership must support successor activation');
    $technical = assignmentAdminReload($technical);
    assignmentAdminCheck($technical->isHolonAdmin() && $technical->get('active') && $technical->get('is_membership')
        && $technical->getParameter('custom') === 'preserve', 'Activation must preserve existing context settings');

    $twoAdminTemplate = $makeHolon(['templatename' => 'Two admins', 'admin_min' => 2, 'admin_max' => 2]);
    $twoAdminRole = $makeHolon(['IDholon_template' => $twoAdminTemplate->getId()]);
    $soleAdmin = assignmentAdminFixture(UserHolon::class, ['IDholon' => $twoAdminRole->getId(), 'IDuser' => $users[3]->getId(),
        'active' => 1, 'is_membership' => 1, 'parameters' => ['isAdmin' => true]]);
    assignmentAdminCheck(empty($soleAdmin->saveAssignmentWithAdmin([], false, [$users[0]->getId()], true)['status']), 'An optional handover must still respect the minimum once new members are added');
    assignmentAdminCheck(!empty($soleAdmin->saveAssignmentWithAdmin([], false, [$users[0]->getId(), $users[1]->getId()], true)['status']), 'Several external successors must be assigned atomically');

    // More than one administrator: require the complete selected handover.
    foreach ([1, 2] as $i) { $member = assignmentAdminReload($links[$i]); $member->set('active', true); $member->setHolonAdmin(true); }
    $template->set('admin_min', 1); $template->set('admin_max', 1); $template->save();
    assignmentAdminCheck($only->getAdminTransitionOptions()['grant']['required'] === 2, 'An already overfull role must request enough replacements');
    assignmentAdminCheck(empty($only->saveAssignmentWithAdmin([], true, [$users[1]->getId()], false)['status']), 'Incomplete multiple handover must fail');
    assignmentAdminCheck(!empty($only->saveAssignmentWithAdmin([], true, [$users[1]->getId(), $users[2]->getId()], false)['status']), 'Multiple replacements must save together');

    // A circle admin inherited from a role cannot be removed by clearing its direct flag.
    $circleTemplate = $makeHolon(['templatename' => 'Circle test', 'IDtypeholon' => 2, 'admin_min' => 1, 'admin_max' => 1]);
    $circle = $makeHolon(['IDtypeholon' => 2, 'IDholon_template' => $circleTemplate->getId()]);
    $parentAdminTemplate = $makeHolon(['templatename' => 'Parent admin', 'adminparent' => true]);
    $childRole = $makeHolon(['IDholon_parent' => $circle->getId(), 'IDholon_template' => $parentAdminTemplate->getId()]);
    assignmentAdminFixture(UserHolon::class, ['IDholon' => $childRole->getId(), 'IDuser' => $users[1]->getId(), 'active' => 1,
        'is_membership' => 1, 'parameters' => ['isAdmin' => true]]);
    $inherited = assignmentAdminFixture(UserHolon::class, ['IDholon' => $circle->getId(), 'IDuser' => $users[1]->getId(), 'active' => 1,
        'is_membership' => 1, 'parameters' => []]);
    assignmentAdminCheck($inherited->getAdminTransitionOptions()['grant']['required'] === 0, 'Inherited admin must not count twice');
    assignmentAdminCheck(!empty($inherited->saveAssignmentWithAdmin([], true, [], false)['status']), 'Direct flag for inherited admin must be allowed at maximum');
    assignmentAdminCheck($inherited->getAdminTransitionOptions()['revoke']['required'] === 0, 'Inherited admin still meets circle minimum');
    assignmentAdminCheck(!empty($inherited->saveAssignmentWithAdmin([], false, [], true)['status']), 'Clearing direct flag must preserve inherited authority');
    $other = assignmentAdminFixture(UserHolon::class, ['IDholon' => $circle->getId(), 'IDuser' => $users[2]->getId(), 'active' => 1, 'is_membership' => 1]);
    assignmentAdminCheck($other->getAdminTransitionOptions()['grant']['blocked'], 'Inherited admin cannot be replaced through an unrelated direct assignment');

    // CAN_ADD_ADMIN alone must allow a handover but preserve protected fields.
    assignmentAdminFixture(HolonPermission::class, ['IDholon' => $template->getId(), 'IDpermission' => Permission::findByKey('CAN_ADD_ADMIN')->getId(),
        'range' => 'self', 'member_type' => 'member', 'is_extended' => 0]);
    assignmentAdminFixture(HolonPermission::class, ['IDholon' => $root->getId(), 'IDpermission' => Permission::findByKey('CAN_EDIT_MEMBER_ASSIGNMENT')->getId(),
        'range' => 'self', 'member_type' => 'admin', 'is_extended' => 0]);
    $only = assignmentAdminReload($only);
    $only->set('time_budget_hours', 12);
    $only->set('time_budget_recurrence', 'month');
    $only->set('assignment_review_date', '2027-01-15');
    $only->save();
    $_SESSION['currentUser'] = $users[2]->getId();
    commonClearCurrentUserAllAdminModes();
    commonClearCurrentUserPermissionCache();
    assignmentAdminCheck($role->isAllowed('CAN_ADD_ADMIN', false) && !$role->isAllowed('CAN_EDIT_MEMBER_ASSIGNMENT', false), 'Admin-only permission fixture must be isolated: ' . json_encode([$role->isAllowed('CAN_ADD_ADMIN', false), $role->isAllowed('CAN_EDIT_MEMBER_ASSIGNMENT', false)]));
    $result = $only->saveAssignmentWithAdmin(['focus' => 'Forbidden field', 'time_budget_hours' => 99, 'assignment_review_date' => '2028-01-01'],
        false, [$users[1]->getId()], true);
    assignmentAdminCheck(!empty($result['status']), 'Admin-only permission must allow successor selection');
    $only = assignmentAdminReload($only);
    assignmentAdminCheck($only->get('focus') === 'Allowed edit' && (float)$only->get('time_budget_hours') === 12.0
        && $only->get('assignment_review_date')->format('Y-m-d') === '2027-01-15', 'Admin-only handover must preserve protected fields');

    $_SESSION['currentUser'] = 0;
    commonClearCurrentUserPermissionCache();
    assignmentAdminCheck(empty($only->saveAssignmentWithAdmin(['focus' => 'Forbidden'], true, [], false)['status']), 'Unauthorized user must not edit assignment or admin');
    assignmentAdminCheck(assignmentAdminReload($only)->get('focus') === 'Allowed edit', 'Denied write must preserve fields');
    echo "member_assignment_admin_test: OK\n";
} finally {
    $pdo->rollBack();
    DbObject::$preload = [];
}
