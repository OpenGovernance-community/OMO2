<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';

if (($argv[1] ?? '') === '--generate') {
    $_SESSION = [];
    try {
        $count = \dbObject\EventRecurrence::generateDueBatch(100,
            new DateTimeImmutable('2030-04-02 08:00:00', new DateTimeZone('Europe/Zurich')), (int)$argv[2]);
        echo json_encode(['status' => true, 'count' => $count]);
    } catch (Throwable $exception) { echo json_encode(['status' => false]); }
    exit;
}

if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session']; $_GET = $request['get']; $_POST = $request['post']; $_REQUEST = array_merge($_GET, $_POST);
    $user = new \dbObject\User(); $user->load((int)$_SESSION['currentUser']);
    $_SESSION['auth_security_version'] = (int)$user->get('security_version');
    $endpoint = $request['endpoint'];
    mcpCheck(in_array($endpoint, ['calendar/create.php', 'calendar/detail.php', 'calendar/delete.php', 'calendar/recurrence_next.php', 'calendar/recurrence_availability.php', 'documents/pv/editor.php', 'documents/pv/action.php'], true), 'Known endpoint.');
    $_SERVER['REQUEST_METHOD'] = $request['method']; $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_URI'] = '/omo/api/' . $endpoint; $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    require dirname(__DIR__) . '/omo/api/' . $endpoint; exit;
}
function recurrenceEndpointRequest(array $request): string
{
    $process = proc_open([PHP_BINARY, '-d', 'display_errors=1', __FILE__, '--request', base64_encode(json_encode($request))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fclose($pipes[0]); $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0 && $errors === '', 'Endpoint failed: ' . substr($output, -1800) . $errors); return $output;
}
function recurrenceStartGenerator(int $seriesId): array
{
    $process = proc_open([PHP_BINARY, '-d', 'display_errors=1', __FILE__, '--generate', (string)$seriesId],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fclose($pipes[0]); return [$process, $pipes];
}
function recurrenceFinishGenerator(array $job, bool $expectSuccess): array
{
    [$process, $pipes] = $job;
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0, 'Generator exits normally.');
    $payload = json_decode($output, true);
    mcpCheck(is_array($payload) && $payload['status'] === $expectSuccess, 'Generator response: ' . $output . $errors);
    if ($expectSuccess) { mcpCheck($errors === '', 'No unexpected generator diagnostics: ' . $errors); }
    else { mcpCheck($errors === '' || str_contains($errors, 'OMO meeting recurrence'), 'Only the expected failure is logged: ' . $errors); }
    return $payload;
}

$before = $_SESSION; $items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['root']->getId();
    $items['root']->set('IDholon_org', $hid); $items['root']->save();
    $items['assignment'] = mcpFixture(\dbObject\UserHolon::class, ['IDuser' => $uid, 'IDholon' => $hid, 'is_membership' => 1, 'active' => 1]);
    foreach (['CAN_CREATE_EVENT', 'CAN_EDIT_EVENT', 'CAN_DELETE_EVENT', 'CAN_CREATE_DOCUMENT', 'CAN_DELETE_DOCUMENT'] as $key) {
        $permission = \dbObject\Permission::findByKey($key);
        $items['permission_' . $key] = mcpFixture(\dbObject\HolonPermission::class, ['IDholon' => $hid,
            'IDpermission' => $permission->getId(), 'member_type' => 'member', 'range' => 'self']);
    }
    foreach (['calendar', 'documents'] as $hash) {
        $app = new \dbObject\Application(); $app->load([['hash', $hash]]);
        $items['app_' . $hash] = mcpFixture(\dbObject\OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    $request = ['session' => ['currentUser' => $uid, 'currentOrganization' => $oid, 'omo_event_recurrence_csrf' => 'recurrence-test'],
        'get' => ['oid' => $oid, 'cid' => $hid], 'post' => [], 'endpoint' => 'calendar/create.php', 'method' => 'GET'];
    $html = recurrenceEndpointRequest($request);
    mcpCheck(str_contains($html, 'data-meeting-recurrence-enabled') && str_contains($html, 'value="on_close"'), 'Simple checkbox and both recurrence practices render.');
    file_put_contents(dirname(__DIR__) . '/tmp/meeting-recurrence-form.html', $html);
    $request['method'] = 'POST';
    $request['post'] = ['title' => 'Recurring operational meeting', 'description' => 'Shared framework', 'status' => 'confirmed', 'IDholon' => $hid,
        'start_at' => '2030-04-02T09:00', 'end_at' => '2030-04-02T10:00', 'document_type' => 'pv',
        'invitation_holon_ids' => [$hid], 'recurrence_enabled' => 1, 'recurrence_csrf' => 'recurrence-test', 'recurrence' => ['frequency' => 'on_close', 'interval_days' => 14]];
    foreach ([['', 'on_close', 'copy', 'nécessite un document de type PV'], ['html', 'on_close', 'reuse', 'nécessite un document de type PV'],
        ['pv', 'weekly', 'reuse', 'Un PV doit être propre']] as [$type, $frequency, $mode, $message]) {
        $invalid = $request; $invalid['post']['document_type'] = $type;
        $invalid['post']['recurrence'] = ['frequency' => $frequency, 'document_mode' => $mode];
        $invalidResponse = json_decode(recurrenceEndpointRequest($invalid), true);
        mcpCheck(empty($invalidResponse['status']) && str_contains($invalidResponse['message'], $message), 'Incompatible documents reject saving with a useful message.');
    }
    $invalid = $request; $invalid['post']['recurrence_csrf'] = 'wrong';
    mcpCheck(empty(json_decode(recurrenceEndpointRequest($invalid), true)['status']), 'Recurrence writes require CSRF.');
    $response = json_decode(recurrenceEndpointRequest($request), true);
    mcpCheck(!empty($response['status']), 'Create recurring event: ' . json_encode($response));
    $event = new \dbObject\Event(); $event->load((int)$response['eventId'], true); $pv = $event->getAssociatedDocument();
    mcpCheck($event->getRecurrence()?->get('frequency') === 'on_close' && $pv instanceof \dbObject\Document, 'Save recurrence and associated PV together.');
    mcpCheck((int)$event->getRecurrence()->get('interval_days') === 14, 'Approximate on-close interval is saved in the existing interval_days field.');
    mcpCheck($pv->get('title') === 'PV Recurring operational meeting du 02.04.2030 09:00', 'Default source title uses its meeting date.');
    $request['method'] = 'GET'; $request['post'] = []; $request['get']['id'] = $event->getId();
    $html = recurrenceEndpointRequest($request);
    mcpCheck(str_contains($html, 'value="on_close" selected') && str_contains($html, 'name="recurrence_apply_following"'), 'Editing preserves selected recurrence and offers following occurrences.');
    mcpCheck(str_contains($html, 'data-meeting-edit-scope') && str_contains($html, 'data-meeting-save-toggle'), 'Existing meeting renders the split save action.');
    file_put_contents(dirname(__DIR__) . '/tmp/meeting-recurrence-edit-form.html', $html);
    $request['endpoint'] = 'calendar/detail.php';
    $detailHtml = recurrenceEndpointRequest($request);
    mcpCheck(str_contains($detailHtml, 'data-meeting-plan-next'), 'Meetings without closing a PV can schedule from the detail.');
    mcpCheck(str_contains($detailHtml, 'data-meeting-delete-toggle'), 'Recurring meeting detail offers split deletion.');
    file_put_contents(dirname(__DIR__) . '/tmp/meeting-recurrence-detail.html', $detailHtml);
    $items['joining_user'] = mcpFixture(\dbObject\User::class, ['email' => 'availability-' . bin2hex(random_bytes(5)) . '@example.invalid', 'firstname' => 'Joining guest', 'active' => 1]);
    $joiningId = (int)$items['joining_user']->getId();
    $items['joining_membership'] = mcpFixture(\dbObject\UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $joiningId, 'active' => 1]);
    $items['joining_holon'] = mcpFixture(\dbObject\UserHolon::class, ['IDuser' => $joiningId, 'IDholon' => $hid, 'is_membership' => 1, 'active' => 1]);
    $items['busy_meeting'] = mcpFixture(\dbObject\Event::class, ['IDorganization' => $oid, 'IDuser' => $joiningId, 'title' => 'Private conflict title',
        'status' => 'confirmed', 'active' => 1, 'start_at' => '2030-04-09 10:00:00', 'end_at' => '2030-04-09 11:00:00']);
    $items['busy_invitation'] = mcpFixture(\dbObject\EventInvitation::class, ['IDevent' => $items['busy_meeting']->getId(), 'IDuser' => $joiningId, 'invitation_type' => 'user', 'active' => 1, 'status' => 'invited']);
    $previewRequest = $request; $previewRequest['endpoint'] = 'calendar/recurrence_availability.php'; $previewRequest['method'] = 'POST';
    $previewRequest['post'] = ['month' => '2030-04', 'date' => '2030-04-09', 'invitation_user_ids' => [999999]];
    $previewHtml = recurrenceEndpointRequest($previewRequest);
    preg_match('~<script type="application/json" data-omo-calendar-preview-data>(.*?)</script>~s', $previewHtml, $previewMatch);
    $previewData = json_decode($previewMatch[1] ?? '', true);
    mcpCheck(is_array($previewData) && count($previewData['people']) === 2, 'Availability includes live holon members and ignores forged participant input: ' . json_encode($previewData['people'] ?? substr($previewHtml, 0, 600)));
    $joiningData = array_values(array_filter($previewData['people'], static fn($person) => (int)$person['id'] === $joiningId))[0];
    mcpCheck($joiningData['days']['2030-04-09'][20] === '2' && !str_contains($previewHtml, 'Private conflict title'), 'Shared availability marks conflicts without exposing appointment titles.');
    file_put_contents(dirname(__DIR__) . '/tmp/meeting-recurrence-availability.html', $previewHtml);
    $deniedPreview = $previewRequest; $deniedPreview['session']['currentUser'] = 0;
    $items['joining_holon']->set('active', 0); $items['joining_holon']->save();
    mcpCheck(!str_contains(recurrenceEndpointRequest($deniedPreview), 'data-omo-calendar-preview-data'), 'Anonymous callers cannot query availability.');
    $withoutMemberHtml = recurrenceEndpointRequest($previewRequest);
    preg_match('~<script type="application/json" data-omo-calendar-preview-data>(.*?)</script>~s', $withoutMemberHtml, $previewMatch);
    mcpCheck(count(json_decode($previewMatch[1], true)['people']) === 1, 'Departed members disappear on refresh.');
    $request['endpoint'] = 'documents/pv/editor.php'; $request['get']['id'] = $pv->getId();
    $token = 'pv-recurrence-test';
    $request['session']['omo_pv_editor_tokens'][$oid . ':' . $pv->getId() . ':user:' . $uid] = $token;
    $html = recurrenceEndpointRequest($request);
    mcpCheck(str_contains($html, '"asksNextMeetingDate":true'), 'PV editor requests the next date on closure.');
    file_put_contents(dirname(__DIR__) . '/tmp/meeting-recurrence-pv.html', $html);
    $request['endpoint'] = 'documents/pv/action.php'; $request['method'] = 'POST';
    $request['post'] = ['action' => 'update_stage', 'document_id' => $pv->getId(), 'oid' => $oid, 'editor_token' => $token,
        'pv_stage' => 'review', 'next_meeting_start' => '2030-04-18T15:30', 'duration_seconds' => 900, 'end_at' => '2030-04-18T15:45'];
    $invalid = $request; $invalid['post']['next_meeting_start'] = '2030-02-31T09:00';
    mcpCheck(empty(json_decode(recurrenceEndpointRequest($invalid), true)['status']), 'Invalid next date rejects the whole closure.');
    $pv->load((int)$pv->getId(), true); mcpCheck($pv->getPvStage() === 'preparation', 'Failed closure leaves original PV state intact.');
    $closed = json_decode(recurrenceEndpointRequest($request), true);
    mcpCheck(!empty($closed['status']), 'Close and schedule: ' . json_encode($closed));
    $event->load((int)$event->getId(), true); $nextId = (int)$event->getParameter('next_meeting_id');
    $next = new \dbObject\Event(); mcpCheck($nextId > 0 && $next->load($nextId, true), 'One next meeting exists after closure.');
    mcpCheck($next->getAssociatedDocument() instanceof \dbObject\Document && $next->get('start_at')->format('H:i') === '15:30', 'Next meeting has its own PV and the chosen time.');
    mcpCheck($next->get('end_at')->format('H:i') === '16:30', 'Only the model duration determines the end, regardless of submitted duration fields.');
    mcpCheck($next->getAssociatedDocument()->get('title') === 'PV Recurring operational meeting du 18.04.2030 15:30', 'On-close documents use the chosen next date rather than the reference date.');
    $nextPv = $next->getAssociatedDocument();
    $validationRequest = $request;
    $validationRequest['get']['id'] = $nextPv->getId();
    $validationRequest['session']['omo_pv_editor_tokens'][$oid . ':' . $nextPv->getId() . ':user:' . $uid] = $token;
    $validationRequest['post']['document_id'] = $nextPv->getId(); $validationRequest['post']['next_meeting_start'] = '';
    $reviewed = json_decode(recurrenceEndpointRequest($validationRequest), true);
    mcpCheck(!empty($reviewed['status']) && $reviewed['document']['asksNextMeetingDate'], 'Skipping the date at review keeps the choice available for validation.');
    $validationRequest['post']['pv_stage'] = 'validated'; $validationRequest['post']['next_meeting_start'] = '2030-05-02T11:00';
    $validated = json_decode(recurrenceEndpointRequest($validationRequest), true);
    mcpCheck(!empty($validated['status']) && !$validated['document']['asksNextMeetingDate'], 'Validation schedules the next meeting and clears the prompt.');
    $next->load((int)$next->getId(), true); $validationNextId = (int)$next->getParameter('next_meeting_id');
    mcpCheck($validationNextId > 0, 'Validation created a successor when review had skipped it.');
    $directEvent = new \dbObject\Event(); $directEvent->load($validationNextId, true); $directPv = $directEvent->getAssociatedDocument();
    $directRequest = $validationRequest; $directRequest['get']['id'] = $directPv->getId();
    $directRequest['session']['omo_pv_editor_tokens'][$oid . ':' . $directPv->getId() . ':user:' . $uid] = $token;
    $directRequest['post']['document_id'] = $directPv->getId(); $directRequest['post']['next_meeting_start'] = '2030-05-16T11:00';
    $directValidated = json_decode(recurrenceEndpointRequest($directRequest), true);
    mcpCheck(!empty($directValidated['status']) && !$directValidated['document']['asksNextMeetingDate'], 'Direct validation can also schedule, without first passing through review.');
    $request['endpoint'] = 'calendar/recurrence_next.php'; $request['get'] = ['oid' => $oid];
    $request['post'] = ['id' => $event->getId(), 'csrf' => 'recurrence-test', 'next_meeting_start' => '2030-04-20T10:00'];
    $retried = json_decode(recurrenceEndpointRequest($request), true);
    mcpCheck(!empty($retried['status']) && (int)$retried['id'] === $nextId, 'Scheduling retry returns the existing successor.');
    $invalid = $request; $invalid['session']['currentUser'] = 0;
    mcpCheck(empty(json_decode(recurrenceEndpointRequest($invalid), true)['status']), 'Anonymous scheduling is rejected.');

    // The first editor save must fill the horizon without waiting for runtime cron
    // or a second edit, both on creation and when enabling an existing meeting.
    $automaticRequest = $request;
    $automaticRequest['endpoint'] = 'calendar/create.php';
    $automaticRequest['get'] = ['oid' => $oid, 'cid' => $hid];
    $firstDate = new DateTimeImmutable('tomorrow');
    $automaticRequest['post'] = ['title' => 'Immediate weekly meeting', 'status' => 'confirmed', 'IDholon' => $hid,
        'start_at' => $firstDate->format('Y-m-d') . 'T09:00', 'end_at' => $firstDate->format('Y-m-d') . 'T10:00',
        'document_type' => 'pv', 'invitation_holon_ids' => [$hid], 'recurrence_enabled' => 1,
        'recurrence_csrf' => 'recurrence-test', 'recurrence' => ['frequency' => 'weekly',
            'weekday' => (int)$firstDate->format('N'), 'horizon_months' => 1]];
    $immediateResponse = json_decode(recurrenceEndpointRequest($automaticRequest), true);
    mcpCheck(!empty($immediateResponse['status']), 'Create immediate weekly series: ' . json_encode($immediateResponse));
    $immediateEvent = new \dbObject\Event(); $immediateEvent->load((int)$immediateResponse['eventId'], true);
    $immediateSeries = $immediateEvent->getRecurrence();
    $immediateOccurrences = new \dbObject\ArrayEvent();
    $immediateOccurrences->load(['where' => [['field' => 'IDeventrecurrence', 'value' => $immediateSeries->getId()]]]);
    mcpCheck(count($immediateOccurrences) > 1, 'First creation immediately generates following meetings.');
    foreach ($immediateOccurrences as $occurrence) {
        $occurrencePv = $occurrence->getAssociatedDocument();
        mcpCheck($occurrencePv instanceof \dbObject\Document
            && $occurrencePv->get('title') === 'PV Immediate weekly meeting du ' . $occurrence->get('start_at')->format('d.m.Y H:i'),
            'Every immediate occurrence has its own correctly dated PV.');
    }
    $immediateCount = count($immediateOccurrences);
    $sharedRequest = $automaticRequest; $sharedRequest['post']['title'] = 'Reusable notebook meeting';
    $sharedRequest['post']['start_at'] = $firstDate->format('Y-m-d') . 'T15:00';
    $sharedRequest['post']['end_at'] = $firstDate->format('Y-m-d') . 'T16:00';
    $sharedRequest['post']['document_type'] = 'html'; $sharedRequest['post']['recurrence']['document_mode'] = 'reuse';
    $sharedResponse = json_decode(recurrenceEndpointRequest($sharedRequest), true);
    mcpCheck(!empty($sharedResponse['status']), 'Save a shared-document series through the editor: ' . json_encode($sharedResponse));
    $sharedEvent = new \dbObject\Event(); $sharedEvent->load((int)$sharedResponse['eventId'], true);
    $sharedDocument = $sharedEvent->getAssociatedDocument();
    $sharedNext = $sharedEvent->getRecurrence()->getAdjacentEvents($sharedEvent, static fn($candidate) => true)['next'];
    mcpCheck((int)$sharedNext->getAssociatedDocument()->getId() === (int)$sharedDocument->getId(), 'First save generates meetings with the same document.');
    $sharedForm = $sharedRequest; $sharedForm['method'] = 'GET'; $sharedForm['post'] = []; $sharedForm['get']['id'] = $sharedNext->getId();
    mcpCheck(str_contains(recurrenceEndpointRequest($sharedForm), 'value="reuse" selected'), 'Document strategy is preserved when reopening an occurrence.');
    $sharedDelete = $sharedRequest; $sharedDelete['endpoint'] = 'calendar/delete.php';
    $sharedDelete['post'] = ['id' => $sharedEvent->getId(), 'recurrence_scope' => 'single', 'delete_documents' => 1, 'recurrence_csrf' => 'recurrence-test'];
    mcpCheck(!empty(json_decode(recurrenceEndpointRequest($sharedDelete), true)['status']), 'Delete the source meeting while requesting document deletion.');
    mcpCheck($sharedDocument->load((int)$sharedDocument->getId(), true)
        && (int)$sharedNext->getAssociatedDocument()->getId() === (int)$sharedDocument->getId(), 'Shared document survives source deletion and remains linked to the next meeting.');
    $navigationRequest = $automaticRequest; $navigationRequest['method'] = 'GET'; $navigationRequest['post'] = [];
    $navigationRequest['endpoint'] = 'calendar/detail.php'; $navigationRequest['get']['id'] = $immediateEvent->getId();
    $navigationHtml = recurrenceEndpointRequest($navigationRequest);
    mcpCheck(str_contains($navigationHtml, 'data-meeting-recurrence-navigation')
        && str_contains($navigationHtml, 'data-omo-calendar-open-detail-url='), 'Recurring detail links to adjacent meetings through the existing drawer navigation.');
    file_put_contents(dirname(__DIR__) . '/tmp/meeting-recurrence-navigation.html', $navigationHtml);
    $navigationRequest['get']['id'] = $immediateSeries->getAdjacentEvents($immediateEvent, static fn($candidate) => true)['next']->getId();
    file_put_contents(dirname(__DIR__) . '/tmp/meeting-recurrence-navigation-next.html', recurrenceEndpointRequest($navigationRequest));
    $automaticRequest['get']['id'] = $immediateEvent->getId();
    $automaticRequest['post']['id'] = $immediateEvent->getId();
    $unchangedRequest = $automaticRequest;
    $unchangedRequest['post']['recurrence_apply_following'] = 0;
    mcpCheck(!empty(json_decode(recurrenceEndpointRequest($unchangedRequest), true)['status']), 'Save the same meeting without changes.');
    $immediateEvent->load((int)$immediateEvent->getId(), true); $immediateSeries->load((int)$immediateSeries->getId(), true);
    mcpCheck(!$immediateEvent->get('recurrence_exception') && (int)$immediateSeries->get('IDreference_event') === (int)$immediateEvent->getId(), 'No-op save preserves ordinary membership and the source reference.');
    $unchangedRequest['post']['time_buffers_enabled'] = 1;
    $unchangedRequest['post']['preparation_minutes'] = 5;
    mcpCheck(!empty(json_decode(recurrenceEndpointRequest($unchangedRequest), true)['status']), 'Save only personal preparation time.');
    $immediateEvent->load((int)$immediateEvent->getId(), true);
    mcpCheck(!$immediateEvent->get('recurrence_exception') && $immediateEvent->getTimeBuffers($uid)[0] === 5, 'Personal time buffers do not personalize the series occurrence.');
    $unchangedRequest['post']['title'] = 'Individual title';
    mcpCheck(!empty(json_decode(recurrenceEndpointRequest($unchangedRequest), true)['status']), 'Save a real individual title change.');
    $immediateEvent->load((int)$immediateEvent->getId(), true); $immediateSeries->load((int)$immediateSeries->getId(), true);
    mcpCheck($immediateEvent->get('recurrence_exception') && (int)$immediateSeries->get('IDreference_event') !== (int)$immediateEvent->getId(), 'Actual individual edits personalize the meeting and transfer the reference.');
    mcpCheck(!empty(json_decode(recurrenceEndpointRequest($unchangedRequest), true)['status']), 'Save an existing exception unchanged.');
    $immediateEvent->load((int)$immediateEvent->getId(), true);
    mcpCheck((bool)$immediateEvent->get('recurrence_exception'), 'No-op save does not erase an existing personalization.');
    $personalizedDetail = $unchangedRequest; $personalizedDetail['method'] = 'GET'; $personalizedDetail['post'] = []; $personalizedDetail['endpoint'] = 'calendar/detail.php';
    $personalizedHtml = recurrenceEndpointRequest($personalizedDetail);
    mcpCheck(str_contains($personalizedHtml, 'Occurrence personnalisée de la série :') && !str_contains($personalizedHtml, 'hors récurrence'), 'Detail explains that the personalized meeting still belongs to its series.');
    $automaticRequest['post']['recurrence_apply_following'] = 1;
    $immediateEdit = json_decode(recurrenceEndpointRequest($automaticRequest), true);
    mcpCheck(!empty($immediateEdit['status']), 'Edit the immediate series.');
    $immediateOccurrences = new \dbObject\ArrayEvent();
    $immediateOccurrences->load(['where' => [['field' => 'IDeventrecurrence', 'value' => $immediateSeries->getId()]]]);
    mcpCheck(count($immediateOccurrences) === $immediateCount,
        'Editing does not duplicate the initial generation: ' . $immediateCount . ' => ' . count($immediateOccurrences));
    mcpCheck(\dbObject\EventRecurrence::generateDueBatch(100, null, (int)$immediateSeries->getId()) === 0,
        'Subsequent cron does not duplicate the initial generation.');

    unset($automaticRequest['get']['id'], $automaticRequest['post']['id'], $automaticRequest['post']['recurrence_apply_following']);
    $automaticRequest['post']['recurrence_enabled'] = 0;
    $automaticRequest['post']['start_at'] = $firstDate->format('Y-m-d') . 'T11:00';
    $automaticRequest['post']['end_at'] = $firstDate->format('Y-m-d') . 'T12:00';
    $plainResponse = json_decode(recurrenceEndpointRequest($automaticRequest), true);
    mcpCheck(!empty($plainResponse['status']), 'Create an ordinary meeting: ' . json_encode($plainResponse));
    $automaticRequest['get']['id'] = $plainResponse['eventId'];
    $automaticRequest['post']['id'] = $plainResponse['eventId'];
    $automaticRequest['post']['recurrence_enabled'] = 1;
    $enabledResponse = json_decode(recurrenceEndpointRequest($automaticRequest), true);
    mcpCheck(!empty($enabledResponse['status']), 'Enable recurrence on an existing meeting: ' . json_encode($enabledResponse));
    $immediateEvent->load((int)$plainResponse['eventId'], true);
    $immediateOccurrences = new \dbObject\ArrayEvent();
    $immediateOccurrences->load(['where' => [['field' => 'IDeventrecurrence', 'value' => $immediateEvent->get('IDeventrecurrence')]]]);
    mcpCheck(count($immediateOccurrences) > 1, 'First activation on an existing meeting immediately fills the horizon.');

    // Explicit recurrence editing is separate from the ordinary meeting save scope.
    $strategyRequest = $automaticRequest;
    $strategyRequest['post']['recurrence_edit'] = 1;
    $strategyRequest['post']['recurrence_strategy'] = 'stop_delete';
    $strategyRequest['post']['recurrence_apply_following'] = 0;
    $strategyRequest['post']['recurrence'] = ['frequency' => 'days', 'interval_days' => 10, 'horizon_months' => 1];
    $strategySeries = $immediateEvent->getRecurrence(); $strategyId = (int)$strategySeries->getId();
    $planned = new \dbObject\ArrayEvent();
    $planned->load(['where' => [['field' => 'IDeventrecurrence', 'value' => $strategyId]], 'orderBy' => [['field' => 'recurrence_position', 'dir' => 'ASC']]]);
    $planned = array_values($planned->getArrayCopy());
    $plannedDates = array_map(static fn($item) => $item->get('start_at')->format('Y-m-d H:i:s'), $planned);
    mcpCheck(!empty(json_decode(recurrenceEndpointRequest($strategyRequest), true)['status']), 'Single save with pending recurrence edits succeeds.');
    $strategySeries->load($strategyId, true);
    mcpCheck($strategySeries->get('active') && $strategySeries->get('frequency') === 'weekly', 'Single save ignores pending stop and rule changes.');
    foreach ($planned as $i => $item) {
        mcpCheck($item->load((int)$item->getId(), true) && $item->get('start_at')->format('Y-m-d H:i:s') === $plannedDates[$i], 'Single save keeps all scheduled occurrences.');
    }
    $strategyRequest['post']['recurrence_apply_following'] = 1;
    $strategyRequest['post']['recurrence_strategy'] = 'after_last';
    $firstNewPosition = (int)$strategySeries->get('next_position');
    mcpCheck(!empty(json_decode(recurrenceEndpointRequest($strategyRequest), true)['status']), 'Apply new rule only after the last planned date.');
    $strategySeries->load($strategyId, true);
    mcpCheck($strategySeries->get('frequency') === 'days' && $strategySeries->occurrenceDate($firstNewPosition)->format('Y-m-d') === (new DateTimeImmutable(max($plannedDates)))->modify('+10 days')->format('Y-m-d'), 'Future rule uses the last planned date.');
    $firstNewEvent = new \dbObject\Event();
    mcpCheck($firstNewEvent->load([['IDeventrecurrence', $strategyId], ['recurrence_position', $firstNewPosition]])
        && $firstNewEvent->get('start_at')->format('Y-m-d') === $strategySeries->occurrenceDate($firstNewPosition)->format('Y-m-d'), 'Saving immediately fills the new future horizon after the last planned date.');
    foreach ($planned as $i => $item) {
        $item->load((int)$item->getId(), true);
        mcpCheck($item->get('start_at')->format('Y-m-d H:i:s') === $plannedDates[$i], 'Future-only strategy leaves planned dates unchanged.');
    }
    $strategyRequest['post']['recurrence_strategy'] = 'stop_keep';
    mcpCheck(!empty(json_decode(recurrenceEndpointRequest($strategyRequest), true)['status']), 'Stop without removing planned dates.');
    $strategySeries->load($strategyId, true);
    mcpCheck(!$strategySeries->get('active'), 'Stop disables generation.');
    foreach ($planned as $item) { mcpCheck($item->load((int)$item->getId(), true), 'Stop keeps every meeting.'); }
    $strategyRequest['post']['recurrence_strategy'] = 'replan';
    $strategyRequest['post']['recurrence']['interval_days'] = 3;
    mcpCheck(!empty(json_decode(recurrenceEndpointRequest($strategyRequest), true)['status']), 'Restart and replan following dates.');
    $planned[1]->load((int)$planned[1]->getId(), true);
    mcpCheck($planned[1]->get('start_at')->format('Y-m-d') === $firstDate->modify('+3 days')->format('Y-m-d'), 'Replan redistributes existing meetings.');
    $strategyRequest['get']['id'] = $planned[1]->getId(); $strategyRequest['post']['id'] = $planned[1]->getId();
    $strategyRequest['post']['start_at'] = $planned[1]->get('start_at')->format('Y-m-d\TH:i');
    $strategyRequest['post']['end_at'] = $planned[1]->get('end_at')->format('Y-m-d\TH:i');
    $strategyRequest['post']['recurrence_strategy'] = 'stop_delete';
    $strategyRequest['post']['recurrence_delete_documents'] = 0;
    $protected = end($planned); $protected->load((int)$protected->getId(), true);
    $protected->set('IDholon', $items['role']->getId()); $protected->set('recurrence_exception', 1); $protected->save();
    mcpCheck(empty(json_decode(recurrenceEndpointRequest($strategyRequest), true)['status']), 'Stopping with deletion rejects inaccessible following exceptions.');
    $strategySeries->load($strategyId, true);
    mcpCheck($strategySeries->get('active') && $planned[2]->load((int)$planned[2]->getId(), true), 'Denied strategy rolls back without stopping or deleting.');
    $protected->set('IDholon', $hid); $protected->save();
    $retainedDocument = $protected->getAssociatedDocument();
    mcpCheck(!empty(json_decode(recurrenceEndpointRequest($strategyRequest), true)['status']), 'Stop and delete following including personalized meetings.');
    $strategySeries->load($strategyId, true);
    mcpCheck(!$strategySeries->get('active') && $planned[0]->load((int)$planned[0]->getId(), true) && $planned[1]->load((int)$planned[1]->getId(), true), 'Stop-delete keeps the current and earlier meetings.');
    foreach (array_slice($planned, 2) as $item) { mcpCheck(!$item->load((int)$item->getId(), true), 'Following planned meetings are removed.'); }
    mcpCheck($retainedDocument->load((int)$retainedDocument->getId(), true) && !$retainedDocument->get('IDevent'), 'Stop-delete can retain detached documents.');
    $lastDetail = $strategyRequest; $lastDetail['method'] = 'GET'; $lastDetail['post'] = []; $lastDetail['endpoint'] = 'calendar/detail.php';
    mcpCheck(str_contains(recurrenceEndpointRequest($lastDetail), 'Supprimer et mettre fin à la récurrence'), 'Last meeting deletion has the explicit stop label.');

    // Individual deletion preserves the series and its document; collective deletion
    // checks all permissions, includes exceptions, preserves earlier meetings and stops cron.
    $deleteOccurrences = new \dbObject\ArrayEvent();
    $deleteOccurrences->load(['where' => [['field' => 'IDeventrecurrence', 'value' => $immediateSeries->getId()]],
        'orderBy' => [['field' => 'recurrence_position', 'dir' => 'ASC']]]);
    $deleteTargets = array_values($deleteOccurrences->getArrayCopy());
    $keptPv = $deleteTargets[1]->getAssociatedDocument();
    $deleteRequest = $automaticRequest; $deleteRequest['endpoint'] = 'calendar/delete.php';
    $deleteRequest['get'] = ['oid' => $oid];
    $deleteRequest['post'] = ['id' => $deleteTargets[1]->getId(), 'recurrence_scope' => 'single', 'delete_documents' => 0, 'recurrence_csrf' => 'recurrence-test'];
    $singleDeleted = json_decode(recurrenceEndpointRequest($deleteRequest), true);
    mcpCheck(!empty($singleDeleted['status']) && $singleDeleted['deletedCount'] === 1, 'Delete just one occurrence.');
    mcpCheck($keptPv->load((int)$keptPv->getId(), true) && !$keptPv->get('IDevent'), 'Keep and detach the document when requested.');
    $immediateSeries->load((int)$immediateSeries->getId(), true);
    mcpCheck($immediateSeries->get('active') && \dbObject\EventRecurrence::generateDueBatch(100, null, (int)$immediateSeries->getId()) === 0,
        'Single deletion keeps generation active without recreating the deleted slot.');
    $followingPvs = array_map(static fn($item) => (int)$item->getAssociatedDocument()->getId(), array_slice($deleteTargets, 2));
    $deleteRequest['post']['id'] = $deleteTargets[2]->getId();
    $deleteRequest['post']['recurrence_scope'] = 'following'; $deleteRequest['post']['delete_documents'] = 1;
    $invalidDelete = $deleteRequest; $invalidDelete['post']['recurrence_csrf'] = 'wrong';
    mcpCheck(empty(json_decode(recurrenceEndpointRequest($invalidDelete), true)['status']), 'Collective deletion requires CSRF.');
    $protectedTarget = end($deleteTargets);
    $protectedTarget->set('IDholon', $items['role']->getId()); $protectedTarget->set('recurrence_exception', 1);
    mcpCheck(!empty($protectedTarget->save()['status']), 'Prepare a following exception outside deletion permissions.');
    $deniedDelete = json_decode(recurrenceEndpointRequest($deleteRequest), true);
    mcpCheck(empty($deniedDelete['status']), 'An inaccessible following occurrence rejects the whole deletion.');
    $immediateSeries->load((int)$immediateSeries->getId(), true);
    mcpCheck($immediateSeries->get('active') && $deleteTargets[2]->load((int)$deleteTargets[2]->getId(), true), 'Permission failure preserves meetings and generation.');
    foreach ($followingPvs as $documentId) { $doc = new \dbObject\Document(); mcpCheck($doc->load($documentId, true), 'No documents deleted on permission failure.'); }
    $protectedTarget->set('IDholon', $hid); mcpCheck(!empty($protectedTarget->save()['status']), 'Restore exception permission.');
    $followingDeleted = json_decode(recurrenceEndpointRequest($deleteRequest), true);
    mcpCheck(!empty($followingDeleted['status']) && $followingDeleted['deletedCount'] === count($deleteTargets) - 2,
        'Delete selected and following meetings, including the exception: ' . json_encode($followingDeleted));
    mcpCheck($deleteTargets[0]->load((int)$deleteTargets[0]->getId(), true), 'Earlier future meeting survives collective deletion.');
    foreach ($followingPvs as $documentId) { $doc = new \dbObject\Document(); mcpCheck(!$doc->load($documentId, true), 'Selected documents removed.'); }
    mcpCheck($keptPv->load((int)$keptPv->getId(), true), 'Previously retained document remains.');
    mcpCheck(\dbObject\EventRecurrence::generateDueBatch(100, new DateTimeImmutable('+3 months'), (int)$immediateSeries->getId()) === 0,
        'Collective deletion stops all subsequent generation.');

    // Two real CLI cron processes must share the locked cursor without duplicates.
    $automatic = mcpFixture(\dbObject\Event::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'title' => 'Concurrent series', 'status' => 'confirmed', 'timezone' => 'Europe/Zurich', 'active' => 1,
        'start_at' => '2030-04-02 09:00:00', 'end_at' => '2030-04-02 10:00:00']);
    $pdo = \dbObject\DbObject::getPdo(); $pdo->beginTransaction();
    \dbObject\EventRecurrence::configure($automatic, ['frequency' => 'weekly', 'weekday' => 2, 'horizon_months' => 1], false, true);
    $pdo->commit(); $automaticSeries = $automatic->getRecurrence();
    $jobA = recurrenceStartGenerator((int)$automaticSeries->getId());
    $jobB = recurrenceStartGenerator((int)$automaticSeries->getId());
    $resultA = recurrenceFinishGenerator($jobA, true); $resultB = recurrenceFinishGenerator($jobB, true);
    mcpCheck($resultA['count'] + $resultB['count'] === 4, 'Concurrent cron creates exactly four occurrences.');
    $occurrences = new \dbObject\ArrayEvent(); $occurrences->load(['where' => [['field' => 'IDeventrecurrence', 'value' => $automaticSeries->getId()]]]);
    mcpCheck(count($occurrences) === 5, 'Unique cursor positions survive concurrent workers.');

    // A document failure must roll back the event and leave its cursor available for retry.
    $broken = mcpFixture(\dbObject\Event::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'title' => 'Retryable series', 'status' => 'confirmed', 'timezone' => 'Europe/Zurich', 'active' => 1,
        'start_at' => '2030-04-02 09:00:00', 'end_at' => '2030-04-02 10:00:00']);
    $pdo->beginTransaction();
    \dbObject\EventRecurrence::configure($broken, ['frequency' => 'weekly', 'weekday' => 2, 'horizon_months' => 1], false, true);
    $brokenSeries = $broken->getRecurrence();
    $blueprint = $brokenSeries->getParameter('blueprint');
    $blueprint['documents'] = [['source_id' => 0, 'values' => ['title' => 'Missing source', 'document_type' => 'folder']]];
    $brokenSeries->set('parameters', ['blueprint' => $blueprint]); $brokenSeries->save(); $pdo->commit();
    recurrenceFinishGenerator(recurrenceStartGenerator((int)$brokenSeries->getId()), false);
    $brokenSeries->load((int)$brokenSeries->getId(), true);
    mcpCheck((int)$brokenSeries->get('next_position') === 1, 'Failed creation does not advance the cursor.');
    $occurrences = new \dbObject\ArrayEvent();
    $occurrences->load(['where' => [['field' => 'IDeventrecurrence', 'value' => $brokenSeries->getId()]]]);
    mcpCheck(count($occurrences) === 1, 'Failed creation leaves no partial event.');

    // Correcting a mistakenly long original meeting must complete the same request,
    // even when the corrected end is already past; future schedules and PV dates follow.
    $yesterday = (new DateTimeImmutable('yesterday'))->format('Y-m-d');
    $tomorrow = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
    $correction = mcpFixture(\dbObject\Event::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'title' => 'Correctable original', 'status' => 'confirmed', 'timezone' => 'Europe/Zurich', 'active' => 1,
        'start_at' => $yesterday . ' 08:00:00', 'end_at' => $tomorrow . ' 10:00:00']);
    $correctionPv = new \dbObject\Document();
    mcpCheck(!empty($correctionPv->createInOrganizationContext($oid, $hid, $uid,
        ['title' => 'Correction PV', 'document_type' => 'pv', 'event_id' => $correction->getId()])['status']), 'Create correction PV.');
    $settings = ['frequency' => 'weekly', 'weekday' => (int)$correction->get('start_at')->format('N'), 'horizon_months' => 1];
    $pdo->beginTransaction(); \dbObject\EventRecurrence::configure($correction, $settings, false, true);
    $correctionSeries = $correction->getRecurrence();
    mcpCheck(\dbObject\EventRecurrence::generateDueBatch(100, new DateTimeImmutable(), (int)$correctionSeries->getId()) > 0, 'Prepare following occurrences.');
    $pdo->commit();
    $request['endpoint'] = 'calendar/create.php'; $request['get'] = ['oid' => $oid, 'cid' => $hid, 'id' => $correction->getId()];
    $request['post'] = ['id' => $correction->getId(), 'title' => 'Correctable original', 'status' => 'confirmed', 'IDholon' => $hid,
        'start_at' => $yesterday . 'T08:00', 'end_at' => $yesterday . 'T09:00', 'invitation_holon_ids' => [$hid],
        'recurrence_enabled' => 1, 'recurrence_apply_following' => 1, 'recurrence_csrf' => 'recurrence-test', 'recurrence' => $settings];
    $corrected = json_decode(recurrenceEndpointRequest($request), true);
    mcpCheck(!empty($corrected['status']), 'Correct original hours through the real editor: ' . json_encode($corrected));
    $correction->load((int)$correction->getId(), true); $correctionPv->load((int)$correctionPv->getId(), true);
    mcpCheck($correction->get('end_at')->format('Y-m-d H:i') === $yesterday . ' 09:00'
        && $correctionPv->get('datecreation')->format('Y-m-d H:i') === $yesterday . ' 09:00', 'Corrected event and source PV date saved together.');
    $occurrences = new \dbObject\ArrayEvent();
    $occurrences->load(['where' => [['field' => 'IDeventrecurrence', 'value' => $correctionSeries->getId()]]]);
    foreach ($occurrences as $occurrence) {
        $occurrence->load((int)$occurrence->getId(), true);
        mcpCheck($occurrence->get('end_at')->format('H:i') === '09:00', 'Following meetings receive the corrected one-hour duration.');
    }
    $request['method'] = 'GET'; $request['post'] = [];
    $historicalForm = recurrenceEndpointRequest($request);
    mcpCheck(str_contains($historicalForm, 'data-omo-calendar-personal-time-buffers-form')
        && !str_contains($historicalForm, 'name="start_at"') && !str_contains($historicalForm, 'name="end_at"'), 'A later request cannot edit the now-historical schedule.');
    echo "calendar_recurrence_endpoint_test: OK\n";
} finally {
    $pdo = \dbObject\DbObject::getPdo();
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    $documents = new \dbObject\ArrayDocument(); $documents->load(['where' => [['field' => 'IDorganization', 'value' => $items['org']->getId()]]]);
    foreach ($documents as $document) { $document->delete(); }
    $events = new \dbObject\ArrayEvent(); $events->load(['where' => [['field' => 'IDorganization', 'value' => $items['org']->getId()]]]);
    foreach ($events as $event) { foreach ($event->getAssociatedDocuments() as $document) { $document->delete(); } $event->delete(); }
    // Organization FK cascade removes the now-empty recurrence rows.
    mcpCleanup($items); $_SESSION = $before;
}
