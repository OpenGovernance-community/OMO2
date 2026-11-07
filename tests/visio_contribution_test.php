<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/visio.php';
use dbObject\{VideoRoom, OrganizationPatreon};
function rejectVideo(callable $fn, string $key): void {
    try { $fn(); } catch (RuntimeException $e) { mcpCheck($e->getMessage() === $key, 'Unexpected rejection: ' . $e->getMessage()); return; }
    throw new RuntimeException('Expected ' . $key);
}
$items = mcpFixtures();
try {
    $uid = (int)$items['user']->getId(); $oid = (int)$items['org']->getId();
    $_SESSION['currentUser'] = $uid;
    $personal = $items['patreon_subscription'];
    $room = VideoRoom::createForCurrentUser('Personal room'); $items['room'] = $room;
    mcpCheck($room->hasValidContribution(), 'Personal room active');
    $ids = static fn() => array_map(static fn($o) => (int)$o->getId(), VideoRoom::creationOrganizations($uid));
    mcpCheck(in_array($oid, $ids(), true), 'Personal contributor can choose noncontributing membership organization');
    $personal->set('currently_entitled_amount_cents', 0); $personal->save();
    mcpCheck(!$room->hasValidContribution(), 'Existing room becomes inactive');
    mcpCheck(!VideoRoom::listForCurrentUser()[0]['contribution_active'], 'Inactive flag in listing');
    rejectVideo(static fn() => VideoRoom::createForCurrentUser('Denied'), 'contributor_required');
    rejectVideo(static fn() => visioIssueAccess($room, $uid), 'room_inactive');
    rejectVideo(static fn() => visioIssueAccess($room, 0), 'room_inactive');
    mcpCheck(!in_array($oid, $ids(), true), 'Noncontributing organization excluded');
    $org = OrganizationPatreon::loadOrCreateByOrganizationId($oid); $items['org_patreon'] = $org;
    $GLOBALS['patreonOrganizationMinimumAmount'] = '40';
    $GLOBALS['patreonCreatorCampaignId'] = '77'; $GLOBALS['patreonCreatorUserId'] = '';
    foreach (['is_connected' => 1, 'refresh_token' => 'fixture-only', 'campaign_id' => '77', 'patron_status' => 'active_patron', 'currently_entitled_amount_cents' => 4000] as $f => $v) $org->set($f, $v);
    mcpCheck(!empty($org->save()['status']), 'Org contribution saves');
    mcpCheck(in_array($oid, $ids(), true), 'Contributing organization offered');
    rejectVideo(static fn() => VideoRoom::createForCurrentUser('Missing org'), 'contributor_required');
    $orgRoom = VideoRoom::createForCurrentUser('Org room', $oid); $items['org_room'] = $orgRoom;
    mcpCheck($orgRoom->hasValidContribution() && !VideoRoom::hasPersonalContribution($uid), 'Org funds room without personal contribution');
    $room->updateForCurrentUser(['IDorganization' => $oid]);
    mcpCheck($room->hasValidContribution(), 'Reassigning inactive room reactivates it');
    $guest = visioIssueAccess($room, 0);
    mcpCheck(!$guest['moderator'], 'Noncontributing guest can join active room');
    $membership = $items['membership']; $membership->set('active', 0); $membership->save();
    mcpCheck(!$room->hasValidContribution(), 'Membership revocation disables org funded room');
    rejectVideo(static fn() => VideoRoom::createForCurrentUser('Foreign org', $oid), 'invalid_organization');
    $membership->set('active', 1); $membership->save();
    $org->set('is_connected', 0); $org->save();
    mcpCheck(!$room->hasValidContribution(), 'Disconnected organization disables room');
    $personal->set('currently_entitled_amount_cents', 100); $personal->save();
    mcpCheck($room->hasValidContribution(), 'Personal contribution restores room regardless of organization funding');
    $room->updateForCurrentUser(['IDorganization' => '']);
    mcpCheck((int)$room->get('IDorganization') === 0 && $room->hasValidContribution(), 'Personal contributor may clear organization');
    echo "OK: video contribution choices, enforcement, guest entry, revocation and reactivation.\n";
} finally { mcpCleanup($items); }
