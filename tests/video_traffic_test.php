<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me';
require_once dirname(__DIR__) . '/shared_functions.php';
use dbObject\VideoTraffic;
use dbObject\VideoRoom;
function trafficExpect(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$code = str_repeat('a', 32);
$node = static fn(int $bytes) => ['PacketStreamStats' => ['bytes_sent' => $bytes]];
$payload = ['conferences' => ['c1' => ['name' => 'omo-' . $code . '@muc.meet.jitsi', 'creation_time' => 123,
    'endpoints' => ['e1' => ['transceiver' => ['receiver' => $node(100), 'sender' => ['rtp' => $node(200), 'rtcp' => $node(200)]]]]]]];
$observed = VideoTraffic::observations($payload, 'test');
trafficExpect(count($observed) === 1 && $observed[0]['received'] === 100 && $observed[0]['sent'] === 200, 'Shared sender counter counted twice');
try { VideoTraffic::observations(['conferences' => ['x' => ['name' => 'omo-' . $code, 'endpoints' => ['e' => []]]]], 'test'); throw new LogicException('Missing counters accepted'); }
catch (RuntimeException $expected) {}
if (!envIsLocalRuntimeHost()) throw new RuntimeException('Integration test requires local runtime');
$pdo = VideoTraffic::getPdo(); $pdo->beginTransaction();
try {
    $user = new \dbObject\User();
    foreach (['email' => 'traffic-' . bin2hex(random_bytes(8)) . '@example.invalid', 'firstname' => 'Traffic', 'lastname' => 'Test', 'active' => 1] as $field => $value) $user->set($field, $value);
    trafficExpect(!empty($user->save()['status']), 'User fixture failed');
    $subscription = new \dbObject\UserPatreon();
    foreach (['IDuser' => $user->getId(), 'is_connected' => 1, 'refresh_token' => 'fixture-only', 'patron_status' => 'active_patron', 'currently_entitled_amount_cents' => 100] as $field => $value) $subscription->set($field, $value);
    if (empty($subscription->save()['status'])) throw new RuntimeException('Contribution fixture failed');
    $_SESSION['currentUser'] = $user->getId();
    $room = VideoRoom::createForCurrentUser('Traffic test');
    $observed[0]['code'] = $room->get('code');
    trafficExpect(VideoTraffic::ingest($observed)['samples'] === 1, 'Initial counters missing');
    trafficExpect(VideoTraffic::ingest($observed)['samples'] === 0, 'Duplicate counters counted');
    $observed[0]['received'] = 90;
    trafficExpect(VideoTraffic::ingest($observed)['samples'] === 0, 'Counter regression counted');
    $observed[0]['received'] = 150; $observed[0]['sent'] = 280;
    VideoTraffic::ingest($observed);
    $organization = new \dbObject\Organization();
    $organization->set('name', 'Traffic test organization');
    trafficExpect(!empty($organization->save()['status']), 'Organization fixture failed');
    $room->set('IDorganization', $organization->getId());
    trafficExpect(!empty($room->save()['status']), 'Organization assignment failed');
    $observed[0]['received'] = 200; $observed[0]['sent'] = 380;
    VideoTraffic::ingest($observed);
    $byOrg = VideoTraffic::dashboard(gmdate('Y-m-d') . ' 00:00:00', gmdate('Y-m-d', time()+86400) . ' 00:00:00', 'organization', $organization->getId());
    trafficExpect(count($byOrg['rows']) === 1 && (int)$byOrg['rows'][0]['sent_bytes'] === 100, 'Historical organization snapshot or filter incorrect');
    $room->deleteForCurrentUser();
    $report = VideoTraffic::dashboard(gmdate('Y-m-d') . ' 00:00:00', gmdate('Y-m-d', time()+86400) . ' 00:00:00', 'person', 0);
    $rows = array_values(array_filter($report['rows'], static fn($row) => (int)$row['owner_id'] === $user->getId()));
    trafficExpect(count($rows) === 1 && (int)$rows[0]['received_bytes'] === 200 && (int)$rows[0]['sent_bytes'] === 380, 'Totals or historical attribution incorrect');
    echo "Video traffic parser, deduplication, regression and historical attribution OK\n";
} finally { $pdo->rollBack(); }
