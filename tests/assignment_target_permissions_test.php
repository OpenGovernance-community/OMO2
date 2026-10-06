<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\DbObject;
use dbObject\Event;
use dbObject\Holon;
use dbObject\Project;

if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session'];
    $_GET = $request['get'];
    $_POST = $request['post'];
    $_REQUEST = array_merge($_GET, $_POST);
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_METHOD'] = $request['method'];
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    $endpoint = $request['endpoint'];
    if ($endpoint === 'faq/save-test') {
        chdir(dirname(__DIR__) . '/ajax');
        require dirname(__DIR__) . '/ajax/faq_save.php';
        exit;
    }
    if ($endpoint === 'faq/scope-test') {
        require_once dirname(__DIR__) . '/common/faq_popup_helper.php';
        $context = \dbObject\FAQ::resolvePopupContext((int)$_GET['oid'], (int)$_GET['cid']);
        $faq = new \dbObject\FAQ();
        $faq->set('IDorganization', (int)$_GET['oid']); $faq->set('IDholon', (int)$_GET['cid']);
        faqPopupRenderScopeFields($faq, $context, ['allowScopeEditing' => true, 'allowContextualAttachment' => true, 'allowParcoursAttachment' => true]);
        exit;
    }
    if (!in_array($endpoint, ['calendar/create.php', 'projects/create.php', 'projects/action.php', 'tension_popup.php', 'checklist/item_edit.php'], true)) { exit(1); }
    $_SERVER['REQUEST_URI'] = '/omo/api/' . $endpoint;
    require dirname(__DIR__) . '/omo/api/' . $endpoint;
    exit;
}

function assignmentExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function assignmentRequest(array $request): string
{
    $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($request))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    assignmentExpect(is_resource($process), 'Cannot start endpoint.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    assignmentExpect(proc_close($process) === 0, 'Endpoint failed: ' . $errors);
    return $output;
}
function assignmentSelector(array $request, ?string $expectedLabel = null): array
{
    $html = assignmentRequest($request);
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($dom);
    $button = $xpath->query('//*[@data-holon-target-selector]')->item(0);
    assignmentExpect($button instanceof DOMElement, 'Missing shared selector: ' . $html);
    assignmentExpect($xpath->query('//input[@data-holon-target-label and @readonly]')->length === 1, 'Current space must be readonly.');
    assignmentExpect($xpath->query('//select[@name="IDholon"]')->length === 0, 'No unrestricted space select.');
    if ($expectedLabel !== null) {
        assignmentExpect($xpath->query('//input[@data-holon-target-label]')->item(0)->getAttribute('value') === $expectedLabel, 'Initial assignment must show the name alone.');
    }
    return json_decode($button->getAttribute('data-holon-target-selector'), true, 512, JSON_THROW_ON_ERROR);
}
function assignmentResult(array $request): array
{
    $output = assignmentRequest($request);
    $result = json_decode($output, true);
    assignmentExpect(is_array($result), 'Invalid response: ' . $output);
    return $result;
}

$fixtures = [];
$fixture = static function (string $class, array $fields) use (&$fixtures): DbObject {
    $object = new $class();
    foreach ($fields as $field => $value) { $object->set($field, $value); }
    assignmentExpect(!empty($object->save()['status']), 'Cannot save fixture ' . $class);
    $fixtures[] = $object;
    return $object;
};
try {
    $nonce = bin2hex(random_bytes(8));
    $user = $fixture(\dbObject\User::class, ['email' => 'assignment-' . $nonce . '@example.invalid', 'firstname' => 'Member']);
    $org = $fixture(\dbObject\Organization::class, ['name' => 'Assignment fixture', 'shortname' => 'assignment-' . $nonce]);
    $fixture(\dbObject\UserOrganization::class, ['IDorganization' => $org->getId(), 'IDuser' => $user->getId(), 'active' => 1]);
    foreach (['calendar', 'structure', 'projects'] as $hash) {
        $app = new \dbObject\Application();
        if ($app->load([['hash', $hash]])) {
            $fixture(\dbObject\OrganizationApplication::class, ['IDapplication' => $app->getId(), 'IDorganization' => $org->getId(), 'active' => 1]);
        }
    }
    $root = $fixture(Holon::class, ['IDorganization' => $org->getId(), 'name' => 'Root', 'IDtypeholon' => 2, 'visible' => 1, 'active' => 1]);
    $root->set('IDholon_org', $root->getId()); $root->save();
    $spaces = [];
    $grants = [];
    foreach (['allowed', 'denied', 'editOnly', 'revoked'] as $name) {
        $spaces[$name] = $fixture(Holon::class, ['IDorganization' => $org->getId(), 'IDholon_org' => $root->getId(),
            'IDholon_parent' => $root->getId(), 'name' => $name, 'IDtypeholon' => $name === 'revoked' ? 1 : 2, 'visible' => 1, 'active' => 1]);
        $fixture(\dbObject\UserHolon::class, ['IDholon' => $spaces[$name]->getId(), 'IDuser' => $user->getId(), 'active' => 1, 'is_membership' => 1]);
        $keys = $name === 'denied' ? [] : ['CAN_EDIT_EVENT', 'CAN_EDIT_PROJECT'];
        if (in_array($name, ['allowed', 'revoked'], true)) { $keys = array_merge($keys, ['CAN_CREATE_EVENT', 'CAN_CREATE_PROJECT', 'CAN_CREATE_FAQ']); }
        foreach ($keys as $key) {
            $grants[$name][$key] = $fixture(\dbObject\HolonPermission::class, ['IDholon' => $spaces[$name]->getId(),
                'IDpermission' => \dbObject\Permission::findByKey($key)->getId(), 'member_type' => 'member', 'range' => 'self']);
        }
    }
    $events = [];
    foreach (['allowed', 'editOnly'] as $index => $name) {
        $day = new DateTimeImmutable('2030-01-07 +' . $index . ' days');
        $events[$name] = $fixture(Event::class, ['IDorganization' => $org->getId(), 'IDholon' => $spaces[$name]->getId(),
            'IDuser' => $user->getId(), 'title' => $name, 'start_at' => $day->setTime(10, 0), 'end_at' => $day->setTime(11, 0),
            'status' => Event::STATUS_DRAFT, 'active' => 1]);
    }
    $project = $fixture(Project::class, ['IDorganization' => $org->getId(), 'IDholon' => $spaces['allowed']->getId(),
        'IDuser' => $user->getId(), 'title' => 'Owned project', 'status' => Project::STATUS_IN_PROGRESS, 'active' => 1]);
    $nonMemberSpace = $fixture(Holon::class, ['IDorganization' => $org->getId(), 'IDholon_org' => $root->getId(),
        'IDholon_parent' => $root->getId(), 'name' => 'Readable but not a member', 'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
    $fixture(\dbObject\HolonPermission::class, ['IDholon' => $spaces['allowed']->getId(),
        'IDpermission' => \dbObject\Permission::findByKey('CAN_EDIT_PROCESS')->getId(), 'member_type' => 'member', 'range' => 'self']);
    $checklist = $fixture(\dbObject\Checklist::class, ['IDorganization' => $org->getId(), 'IDproject_template_root' => $project->getId(),
        'status' => \dbObject\Checklist::STATUS_DRAFT, 'active' => 1]);
    $request = ['session' => ['currentUser' => (int)$user->getId(), 'currentOrganization' => (int)$org->getId()],
        'get' => ['oid' => $org->getId(), 'cid' => $spaces['allowed']->getId()], 'post' => [], 'method' => 'GET', 'endpoint' => 'calendar/create.php'];
    $expected = [(int)$spaces['allowed']->getId(), (int)$spaces['revoked']->getId()];
    $request['endpoint'] = 'faq/scope-test';
    $config = assignmentSelector($request, 'allowed');
    $faqIds = $config['selectableHolonIds']; sort($faqIds); $sorted = $expected; sort($sorted);
    assignmentExpect($faqIds === $sorted && !$config['allowOrganization'], 'FAQ authors must be offered every permitted space.');
    $faqRequest = $request;
    $faqRequest['get']['cid'] = $spaces['denied']->getId();
    assignmentSelector($faqRequest, 'allowed');
    $faqRequest['endpoint'] = 'faq/save-test'; $faqRequest['method'] = 'POST';
    $faqRequest['post'] = ['IDorganization' => $org->getId(), 'IDholon' => $spaces['revoked']->getId(), 'faq_scope_kind' => 'organization',
        'question' => 'Other role?', 'answer' => 'Created in another permitted role.'];
    $faqResult = assignmentResult($faqRequest);
    assignmentExpect($faqResult['status'], 'FAQ creation in another permitted role must succeed: ' . json_encode($faqResult));
    $savedFaq = new \dbObject\FAQ();
    assignmentExpect($savedFaq->load((int)$faqResult['id']), 'Cannot reload created FAQ.');
    $fixtures[] = $savedFaq;
    assignmentExpect((int)$savedFaq->get('IDholon') === (int)$spaces['revoked']->getId(), 'FAQ must retain its selected role rather than the page context.');
    foreach ([$spaces['denied']->getId(), $nonMemberSpace->getId(), 0] as $forbiddenId) {
        $faqRequest['post']['IDholon'] = $forbiddenId;
        assignmentExpect(!assignmentResult($faqRequest)['status'], 'A forged FAQ destination must be rejected.');
    }
    $grants['revoked']['CAN_CREATE_FAQ']->delete();
    $faqRequest['post']['IDholon'] = $spaces['revoked']->getId();
    assignmentExpect(!assignmentResult($faqRequest)['status'], 'FAQ creation rights must be rechecked after revocation.');
    $request['endpoint'] = 'tension_popup.php';
    $config = assignmentSelector($request, 'allowed');
    assignmentExpect(in_array((int)$spaces['allowed']->getId(), $config['selectableHolonIds'], true)
        && !in_array((int)$nonMemberSpace->getId(), $config['selectableHolonIds'], true) && !$config['allowOrganization'],
        'The tension picker must preserve its membership restrictions.');
    $request['endpoint'] = 'checklist/item_edit.php';
    $request['get']['checklist_id'] = $checklist->getId();
    $config = assignmentSelector($request, 'allowed');
    assignmentExpect(in_array((int)$nonMemberSpace->getId(), $config['selectableHolonIds'], true) && !$config['allowOrganization'],
        'Managing a process template must retain the existing space assignment options.');
    unset($request['get']['checklist_id']);
    foreach (['calendar/create.php', 'projects/create.php'] as $endpoint) {
        $request['endpoint'] = $endpoint;
        $config = assignmentSelector($request);
        $ids = $config['selectableHolonIds']; sort($ids); $sorted = $expected; sort($sorted);
        assignmentExpect($ids === $sorted && !$config['allowOrganization'], $endpoint . ' must only allow creation destinations.');
    }
    $request['endpoint'] = 'calendar/create.php';
    $request['get']['id'] = $events['editOnly']->getId();
    $config = assignmentSelector($request);
    assignmentExpect(in_array((int)$spaces['editOnly']->getId(), $config['selectableHolonIds'], true), 'Keep current edit-only event space.');
    assignmentExpect(!in_array((int)$spaces['denied']->getId(), $config['selectableHolonIds'], true), 'Never allow denied event space.');
    $request['endpoint'] = 'projects/create.php';
    $request['get']['id'] = $project->getId();
    $config = assignmentSelector($request);
    assignmentExpect(!in_array((int)$spaces['editOnly']->getId(), $config['selectableHolonIds'], true), 'Project ownership cannot authorize another space.');

    $request['method'] = 'POST';
    $request['endpoint'] = 'calendar/create.php';
    $request['get']['id'] = $events['allowed']->getId();
    $request['post'] = ['IDholon' => $spaces['editOnly']->getId(), 'title' => 'Changed', 'status' => Event::STATUS_DRAFT,
        'start_at' => '2030-01-07T10:00', 'end_at' => '2030-01-07T11:00'];
    assignmentExpect(!assignmentResult($request)['status'], 'Event move requires creation rights despite edit rights.');
    unset($request['get']['id']);
    foreach (['denied', 'editOnly'] as $name) {
        $request['post']['IDholon'] = $spaces[$name]->getId();
        assignmentExpect(!assignmentResult($request)['status'], 'Cannot create event in ' . $name);
    }
    $request['get']['id'] = $events['editOnly']->getId();
    $request['post']['start_at'] = '2030-01-08T10:00'; $request['post']['end_at'] = '2030-01-08T11:00';
    assignmentExpect(assignmentResult($request)['status'], 'Saving the same edit-only space must still work.');
    $events['editOnly']->load((int)$events['editOnly']->getId(), true);
    assignmentExpect($events['editOnly']->get('title') === 'Changed', 'Edit-only save persisted.');

    $request['endpoint'] = 'projects/action.php';
    $request['post'] = ['action' => 'save_project', 'id' => $project->getId(), 'IDholon' => $spaces['editOnly']->getId(), 'title' => 'Changed'];
    assignmentExpect(!assignmentResult($request)['status'], 'Project owner cannot move into an edit-only space.');
    $request['post']['id'] = 0; $request['post']['IDholon'] = $spaces['denied']->getId();
    $result = assignmentResult($request);
    if ($result['status']) {
        // Proposals are a separate existing right, and must remain proposals.
        $proposal = new Project();
        assignmentExpect($proposal->load((int)$result['id']), 'Cannot reload project proposal.');
        $fixtures[] = $proposal;
        assignmentExpect($proposal->isPendingProposal(), 'A space without creation rights can only accept a proposal.');
    }

    // A previously offered destination is rejected after its creation grant is revoked.
    foreach (['CAN_CREATE_EVENT', 'CAN_CREATE_PROJECT'] as $key) { $grants['revoked'][$key]->delete(); }
    $request['post']['id'] = $project->getId();
    $request['post']['IDholon'] = $spaces['revoked']->getId();
    assignmentExpect(!assignmentResult($request)['status'], 'Project grants must be rechecked on save.');
    $request['endpoint'] = 'calendar/create.php'; unset($request['get']['id']);
    $request['post'] = ['IDholon' => $spaces['revoked']->getId(), 'title' => 'Revoked', 'status' => Event::STATUS_DRAFT,
        'start_at' => '2030-01-09T10:00', 'end_at' => '2030-01-09T11:00'];
    assignmentExpect(!assignmentResult($request)['status'], 'Event grants must be rechecked on save.');
    echo "assignment_target_permissions_test: OK\n";
} finally {
    foreach (array_reverse($fixtures) as $object) { $object->delete(); }
}
