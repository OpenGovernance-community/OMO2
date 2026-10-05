<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/common.php';
require_once dirname(__DIR__) . '/common/caldav.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/omo/translations.php';
ini_set('error_log', '/tmp/decision-calendar-test-errors.log');

use dbObject\DbObject;
use dbObject\DecisionProcess;
use dbObject\DecisionProposal;
use dbObject\DecisionParticipant;
use dbObject\DecisionInvitation;
use dbObject\DecisionResponse;
use dbObject\Event;

function dateExpect(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function dateFixture(string $class, array $values): DbObject {
    $object = new $class();
    foreach ($values as $field => $value) $object->set($field, $value);
    $result = $object->save();
    dateExpect(!empty($result['status']), $class . ': ' . json_encode($result));
    return $object;
}
function dateGuests(DecisionProposal $proposal): array {
    $event = Event::findByDecisionProposal((int)$proposal->getId());
    $guests = [];
    foreach ($event->getInvitations(true) as $invitation) $guests[] = $invitation->getIdentityKey();
    sort($guests);
    return $guests;
}
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
register_shutdown_function(static function () use ($pdo): void { if ($pdo->inTransaction()) $pdo->rollBack(); });
try {
    $nonce = bin2hex(random_bytes(6));
    $owner = dateFixture(\dbObject\User::class, ['email' => 'date-owner-' . $nonce . '@example.invalid', 'firstname' => 'Owner']);
    $guest = dateFixture(\dbObject\User::class, ['email' => 'date-guest-' . $nonce . '@example.invalid', 'firstname' => 'Guest']);
    $org = dateFixture(\dbObject\Organization::class, ['name' => 'Dates ' . $nonce, 'shortname' => 'dates-' . $nonce]);
    foreach ([$owner, $guest] as $user) dateFixture(\dbObject\UserOrganization::class, ['IDorganization' => $org->getId(), 'IDuser' => $user->getId(), 'active' => 1]);
    $decision = dateFixture(DecisionProcess::class, ['IDorganization' => $org->getId(), 'IDuser' => $owner->getId(), 'title' => 'Schedule',
        'status' => 'draft', 'decision_type' => 'decision', 'evaluation_method' => DecisionProcess::METHOD_SIMPLE_VOTE,
        'parameters' => ['simple_vote' => ['proposal_content' => ['date' => true]]]]);
    $invite = dateFixture(DecisionInvitation::class, ['IDdecision_process' => $decision->getId(), 'invitation_type' => 'user', 'IDuser' => $guest->getId(), 'active' => 1, 'status' => 'invited']);
    $external = dateFixture(DecisionInvitation::class, ['IDdecision_process' => $decision->getId(), 'invitation_type' => 'email', 'email' => 'external-' . $nonce . '@example.invalid', 'active' => 1, 'status' => 'invited']);
    $range = DecisionProposal::normalizeCalendarRange('2026-11-01T09:00', '2026-11-01T10:00', 'Europe/Zurich');
    dateExpect($range['status'], 'Valid range');
    foreach ([['2026-02-30T10:00', '2026-03-01T11:00'], ['2026-11-01T10:00', '2026-11-01T09:00'], ['2026-11-01T10:00', ''], ['2026-11-01T10:00', '2026-11-01T10:00']] as [$start, $end]) {
        dateExpect(!DecisionProposal::normalizeCalendarRange($start, $end)['status'], 'Invalid range must be rejected');
    }
    dateExpect(!DecisionProposal::normalizeCalendarRange('2026-11-01T09:00', '2026-11-01T10:00', 'bad/timezone')['status'], 'Invalid timezone rejected');
    $group = $decision->getPrimaryGroup(false);
    $dateOnly = omoDecisionNormalizeProposalContent(['title' => false, 'description' => false, 'url' => false, 'date' => true]);
    dateExpect($dateOnly === ['title' => false, 'description' => false, 'url' => false, 'date' => true], 'Date-only proposals need no other content field');
    dateExpect(!omoDecisionNormalizeProposalContent(['title' => true])['date'] && !omoDecisionGetDefaultProposalContent()['date'], 'Dates require explicit activation');
    $disabledContent = ['title' => true, 'description' => false, 'url' => false, 'date' => false];
    $disabledItems = omoDecisionBuildProposalItemsFromInput(['A'], [], [], [], $disabledContent, ['2026-11-01T09:00'], ['2026-11-01T10:00'], ['Europe/Zurich']);
    dateExpect($disabledItems[0]['start_at'] === null && $disabledItems[0]['end_at'] === null, 'Disabled dates ignore posted values');
    $dateItems = omoDecisionBuildProposalItemsFromInput([''], [], [], [], $dateOnly, ['2026-11-01T09:00'], ['2026-11-01T10:00'], ['Europe/Zurich']);
    dateExpect(count($dateItems) === 1 && $dateItems[0]['title'] === '', 'Date-only proposals are retained');
    $a = dateFixture(DecisionProposal::class, ['IDdecision_process' => $decision->getId(), 'IDdecision_group' => $group->getId(), 'title' => 'A', 'position' => 1, 'active' => 1] + $range['values']);
    $b = dateFixture(DecisionProposal::class, ['IDdecision_process' => $decision->getId(), 'IDdecision_group' => $group->getId(), 'title' => 'B', 'position' => 2, 'active' => 1] + $range['values']);
    $expected = ['user:' . $owner->getId(), 'user:' . $guest->getId(), 'email:' . $external->get('email')];
    sort($expected);
    dateExpect(dateGuests($a) === $expected, 'Same decision invitees, including organizer and external email');
    $eventId = Event::findByDecisionProposal((int)$a->getId())->getId();
    dateExpect(Event::findByDecisionProposal((int)$a->getId())->get('status') === 'option', 'Initial event is tentative');
    $ics = commonCalDavBuildEventCalendarData($org, Event::findByDecisionProposal((int)$a->getId()));
    dateExpect(str_contains($ics, 'STATUS:TENTATIVE') && str_contains($ics, 'ATTENDEE;ROLE=REQ-PARTICIPANT:mailto:' . $external->get('email')), 'ICS includes tentative status and invitees');
    $a->set('title', 'Updated');
    dateExpect($a->save()['status'], 'Update proposal');
    dateExpect(Event::findByDecisionProposal((int)$a->getId())->getId() === $eventId, 'Stable event identity');
    dateExpect(Event::findByDecisionProposal((int)$a->getId())->get('title') === 'Updated', 'Title is synchronized');
    $group->set('parameters', ['simple_vote' => ['proposal_content' => $disabledContent]]);
    dateExpect($group->save()['status'] && $decision->syncProposalCalendarEvents()['status'], 'Disable proposal dates');
    dateExpect(Event::findByDecisionProposal((int)$a->getId())->get('status') === 'cancelled', 'Disabling dates cancels calendar reservations');
    dateExpect(!str_contains(omoDecisionRenderProposalCalendar($a, [], 'htmlspecialchars'), 'choice-proposal-calendar__stamp'), 'Disabled dates are hidden in proposal cards');
    $group->set('parameters', ['simple_vote' => ['proposal_content' => ['date' => true]]]);
    dateExpect($group->save()['status'] && $decision->syncProposalCalendarEvents()['status'], 'Enable proposal dates again');
    dateExpect(Event::findByDecisionProposal((int)$a->getId())->getId() === $eventId && Event::findByDecisionProposal((int)$a->getId())->get('status') === 'option', 'Re-enabling keeps the same event identity');
    $participant = DecisionParticipant::findByDecisionAndUser($decision->getId(), $guest->getId());
    $response = dateFixture(DecisionResponse::class, ['IDdecision_process' => $decision->getId(), 'IDdecision_group' => $group->getId(),
        'IDdecision_participant' => $participant->getId(), 'status' => 'draft', 'parameters' => ['simple_vote' => ['selected_proposal_ids' => [$a->getId()]]]]);
    dateExpect(dateGuests($b) === $expected, 'Draft ballots do not release reservations');
    $response->set('status', 'submitted');
    dateExpect($response->save()['status'], 'Submit ballot');
    dateExpect(!in_array('user:' . $guest->getId(), dateGuests($b), true), 'Unselected date removed from guest calendar');
    dateExpect(!Event::findByDecisionProposal((int)$b->getId())->isVisibleToInvitationViewer((int)$guest->getId(), (int)$org->getId()), 'Refused date excluded from personal calendar export');
    dateExpect(in_array('user:' . $guest->getId(), dateGuests($a), true), 'Selected date retained');
    $response->set('parameters', ['simple_vote' => ['selected_proposal_ids' => [$b->getId()]]]);
    dateExpect($response->save()['status'], 'Change ballot');
    dateExpect(in_array('user:' . $guest->getId(), dateGuests($b), true), 'Changing ballot restores invitation');
    $decision->set('status', 'results');
    dateExpect($decision->save()['status'], 'Close decision');
    dateExpect(Event::findByDecisionProposal((int)$b->getId())->get('status') === 'confirmed', 'Winning date confirmed');
    dateExpect(Event::findByDecisionProposal((int)$a->getId())->get('status') === 'cancelled', 'Other date cancelled');
    $response->set('parameters', ['simple_vote' => ['selected_proposal_ids' => [$a->getId(), $b->getId()]]]);
    dateExpect($response->save()['status'], 'Multiple selection');
    dateExpect(Event::findByDecisionProposal((int)$a->getId())->get('status') === 'option' && Event::findByDecisionProposal((int)$b->getId())->get('status') === 'option', 'Ties remain tentative');
    dateExpect($decision->setCalendarProposalStatus($a, 'confirmed')['status'], 'Manager resolves tie');
    dateExpect(Event::findByDecisionProposal((int)$a->getId())->get('status') === 'confirmed' && Event::findByDecisionProposal((int)$b->getId())->get('status') === 'cancelled', 'Manual resolution retains one date');
    dateExpect($decision->save()['status'] && Event::findByDecisionProposal((int)$a->getId())->get('status') === 'confirmed', 'Manual selection survives saving process');
    $group->load($group->getId());
    $group->set('decision_type', 'consultation');
    $group->set('parameters', ['simple_vote' => ['proposal_content' => ['date' => true]]]);
    dateExpect($group->save()['status'] && $decision->syncProposalCalendarEvents()['status'], 'Consultative ballot');
    dateExpect(Event::findByDecisionProposal((int)$a->getId())->get('status') === 'option', 'Consultation never auto-confirms');
    dateExpect($decision->setCalendarProposalStatus($a, 'confirmed')['status'] && $decision->setCalendarProposalStatus($b, 'confirmed')['status'], 'Consultation can confirm several dates');
    dateExpect(Event::findByDecisionProposal((int)$a->getId())->get('status') === 'confirmed', 'Confirming B preserves A in consultation');
    dateExpect($decision->setCalendarProposalStatus($b, 'cancelled')['status'], 'Cancel consultation date');
    dateExpect($decision->syncProposalCalendarEvents()['status'] && Event::findByDecisionProposal((int)$b->getId())->get('status') === 'cancelled', 'Manual decision survives later synchronization');
    $escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    $context = ['decision' => $decision, 'decisionGroup' => $group, 'organizationId' => (int)$org->getId(), 'targetHolonId' => 0,
        'canManage' => true, 'isOwner' => true, 'intent' => 'manage', 'accessMode' => 'private', 'participant' => DecisionParticipant::findByDecisionAndUser($decision->getId(), $owner->getId())];
    $calendarHtml = omoDecisionRenderProposalCalendar($a, $context, $escape);
    $multiDayProposal = clone $b;
    $multiRange = DecisionProposal::normalizeCalendarRange('2026-11-01T09:00', '2026-11-03T16:00', 'Europe/Zurich');
    foreach ($multiRange['values'] as $field => $value) $multiDayProposal->set($field, $value);
    $multiHtml = omoDecisionRenderProposalCalendar($multiDayProposal, $context, $escape);
    dateExpect(str_contains($multiHtml, 'Du ') && str_contains($multiHtml, 'Au ') && str_contains($multiHtml, '16:00'), 'Multi-day display associates a date with each time');
    $multiEditor = omoDecisionRenderProposalDates($multiRange['values'], $escape);
    dateExpect(str_contains($multiEditor, 'data-omo-proposal-date-multiple checked') && str_contains($multiEditor, 'data-omo-proposal-date-single hidden'), 'Existing multi-day ranges open in multi-day mode');
    dateExpect(str_contains($calendarHtml, 'data-omo-proposal-calendar-action="confirmed"') && str_contains($calendarHtml, 'data-omo-proposal-calendar-action="cancelled"'), 'Manager can confirm and cancel consultation dates');
    dateExpect(!str_contains(omoDecisionRenderProposalCalendar($a, array_replace($context, ['canManage' => false]), $escape), 'data-omo-proposal-calendar-action'), 'Participants do not see manager actions');
    $group->load($group->getId(), true);
    require_once dirname(__DIR__) . '/omo/api/decision/modules/vote/module.php';
    ob_start();
    omoDecisionVoteModuleRender(['context' => $context, 'decision' => $decision, 'decisionGroup' => $group,
        'lang' => [], 'sourceLang' => omoDecisionVoteModuleGetSourceLang(), 'escape' => $escape]);
    $moduleHtml = ob_get_clean();
    dateExpect(str_contains($moduleHtml, 'data-omo-proposal-calendar-action="confirmed"'), 'Actions visible in manager proposal list');
    if (($argv[1] ?? '') === '--preview') {
        $html = '<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Test des propositions de dates</title><link rel="stylesheet" href="/common/assets/components.css"><link rel="stylesheet" href="/omo/assets/css/styles.css"><link rel="stylesheet" href="/common/choice/proposal-dates.css"><script src="/common/choice/proposal-dates.js" defer></script><body><main class="generic-section generic-section--stack" style="max-width:800px;margin:24px auto"><h1>Propositions de dates</h1><h2>Ajouter une plage horaire</h2>'
            . '<form id="date-preview-form">' . omoDecisionRenderProposalDates(['start_at' => $range['values']['start_at'], 'end_at' => $range['values']['end_at'], 'timezone' => 'Europe/Zurich'], $escape)
            . '<button type="submit">Verifier la saisie</button><output id="date-preview-values"></output></form><script>document.getElementById("date-preview-form").addEventListener("submit",function(event){event.preventDefault();document.getElementById("date-preview-values").textContent=JSON.stringify(Array.from(new FormData(this).entries()));});</script>'
            . '<h2>Resultats de la consultation</h2>' . $calendarHtml . $multiHtml . '<h2>Liste complete</h2>' . $moduleHtml . '</main></body></html>';
        file_put_contents(dirname(__DIR__) . '/tmp/decision-calendar-preview.html', $html);
    }
    $group->set('parameters', ['simple_vote' => ['proposal_content' => $disabledContent]]);
    dateExpect($group->save()['status'] && $decision->syncProposalCalendarEvents()['status'], 'Disable dates in results');
    dateExpect(!$decision->setCalendarProposalStatus($a, 'confirmed')['status'], 'Disabled dates cannot be confirmed');
    $group->set('evaluation_method', 'consent');
    $group->set('decision_type', 'decision');
    $group->set('parameters', ['consent' => ['proposal_content' => ['date' => true]]]);
    dateExpect($group->save()['status'], 'Consent group');
    $response->set('parameters', ['consent' => ['choices' => [$a->getId() => 'objection', $b->getId() => 'favor']]]);
    dateExpect($response->save()['status'], 'Consent ballot');
    dateExpect($decision->getCalendarWinningProposalId($group, [$response]) === (int)$b->getId(), 'Consent ranks objections then favors');
    dateExpect(!in_array('user:' . $guest->getId(), dateGuests($a), true), 'Objection releases invitation');
    $response->set('parameters', ['consent' => ['choices' => [$a->getId() => 'no_objection', $b->getId() => 'favor']]]);
    dateExpect($response->save()['status'] && $decision->getCalendarWinningProposalId($group, [$response]) === (int)$b->getId(), 'Consent favors break equal objections');
    $group->set('evaluation_method', 'majority_judgment');
    $group->set('parameters', ['majority_judgment' => ['proposal_content' => ['date' => true]]]);
    dateExpect($group->save()['status'], 'Majority judgment group');
    $response->set('parameters', ['majority_judgment' => ['scores' => [$a->getId() => 0, $b->getId() => 5]]]);
    dateExpect($response->save()['status'], 'Majority judgment ballot');
    dateExpect($decision->getCalendarWinningProposalId($group, [$response]) === (int)$b->getId(), 'Best majority mention wins');
    dateExpect(!in_array('user:' . $guest->getId(), dateGuests($a), true), 'Worst mention releases invitation');
    $external->set('active', 0);
    $external->set('status', 'revoked');
    dateExpect($external->save()['status'] && $decision->syncParticipantsFromInvitations()['status'], 'Remove decision invitation');
    dateExpect(!in_array('email:' . $external->get('email'), dateGuests($b), true), 'Removed guest also removed from events');
    $a->set('start_at', null);
    $a->set('end_at', null);
    dateExpect($a->save()['status'], 'Remove date range');
    dateExpect(Event::findByDecisionProposal((int)$a->getId())->get('status') === 'cancelled', 'Removing dates cancels linked event');
    $otherZone = DecisionProposal::normalizeCalendarRange('2026-11-01T09:00', '2026-11-01T10:00', 'America/New_York');
    foreach ($otherZone['values'] as $field => $value) $a->set($field, $value);
    dateExpect($a->save()['status'], 'Save range in another timezone');
    dateExpect($a->getCalendarData()['startAt'] === '2026-11-01T09:00', 'Local time round trips across timezones');
    $ics = commonCalDavBuildEventCalendarData($org, Event::findByDecisionProposal((int)$a->getId()));
    dateExpect(str_contains($ics, 'DTSTART;TZID=America/New_York:20261101T090000'), 'ICS preserves timezone and local start');
    $a->set('active', 0);
    dateExpect($a->save()['status'] && Event::findByDecisionProposal((int)$a->getId())->get('status') === 'cancelled', 'Withdrawing proposal cancels event');
    $event = Event::findByDecisionProposal((int)$b->getId());
    foreach ($event->getInvitations(true) as $invitation) {
        $invitation->set('active', 0);
        $invitation->set('status', 'revoked');
        dateExpect($invitation->save()['status'], 'Revoke fixture invitations');
    }
    $targets = $event->getEffectiveInvitationTargets((int)$org->getId());
    dateExpect($targets['hasExplicitInvitations'] && !$targets['userIds'] && !$targets['emails'], 'Empty poll invitations never fall back to all organization members');
    $bEventId = (int)$event->getId();
    dateExpect($b->delete(), 'Delete date proposal');
    $event->load($bEventId, true);
    dateExpect($event->get('status') === 'cancelled' && !$event->get('IDdecision_proposal'), 'Deleting proposal cancels stable event before detaching it');
    $pdo->rollBack();
    echo "decision_calendar_test: OK\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $exception;
}
