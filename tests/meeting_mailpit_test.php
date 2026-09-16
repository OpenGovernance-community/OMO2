<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/meeting/service.php';
require_once dirname(__DIR__) . '/meeting/translations.php';

// Explicit opt-in: this test delivers one synthetic message to local Mailpit only.
if (!in_array('--run', $argv, true) || ($GLOBALS['mailHost'] ?? '') !== 'mailpit'
    || (int)($GLOBALS['mailPort'] ?? 0) !== 1025 || !empty($GLOBALS['mailAuth'])) {
    echo "meeting_mailpit_test: SKIP (requires --run and local unauthenticated Mailpit)\n";
    exit;
}
function meetingMailpitGet(string $path): string
{
    $curl = curl_init('http://mailpit:8025/api/v1/' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_PROXY => '']);
    $body = curl_exec($curl);
    if (!is_string($body) || curl_getinfo($curl, CURLINFO_RESPONSE_CODE) !== 200) {
        throw new RuntimeException('Mailpit API request failed');
    }
    return $body;
}

$GLOBALS['mailUser'] = '';
$_SERVER['HTTP_HOST'] = 'localhost';
$reference = 'OMO-Mailpit-meeting-test-' . bin2hex(random_bytes(8));
$start = new DateTimeImmutable('tomorrow 10:00', new DateTimeZone('Europe/Zurich'));
$ics = meetingIcs(bin2hex(random_bytes(32)), $start, $start->modify('+1 hour'), $reference, 'Synthetic email test only. No appointment created.');
$booking = new \dbObject\MeetingBooking();
$booking->set('calendar_data', $ics);
$booking->set('start_at', $start);
$booking->set('guest_name', 'Mailpit Test <visitor>');
$booking->set('guest_email', 'meeting-test@example.invalid');
$booking->set('reason', "Synthetic email test only. No appointment created.\n<script>untrusted input</script>");
if (!meetingMail($booking)) { throw new RuntimeException('Meeting confirmation could not reach Mailpit'); }
$message = null;
for ($attempt = 0; $attempt < 5; $attempt++) {
    $result = json_decode(meetingMailpitGet('search?query=' . rawurlencode('subject:' . $reference)), true, 512, JSON_THROW_ON_ERROR);
    foreach ($result['messages'] ?? [] as $candidate) {
        if (($candidate['Subject'] ?? '') === $reference) { $message = $candidate; break; }
    }
    if ($message) { break; }
    usleep(200000);
}
if (!$message) { throw new RuntimeException('Synthetic confirmation not found in Mailpit'); }
$detail = json_decode(meetingMailpitGet('message/' . rawurlencode($message['ID'])), true, 512, JSON_THROW_ON_ERROR);
$html = (string)($detail['HTML'] ?? '');
if (!str_contains($html, "width='640'") || !str_contains($html, 'background:#004663')
    || !str_contains($html, commonMailEscape(meetingT('confirmed')))
    || !str_contains($html, 'Mailpit Test &lt;visitor&gt;')
    || !str_contains($html, '&lt;script&gt;untrusted input&lt;/script&gt;') || str_contains($html, '<script>')) {
    throw new RuntimeException('Shared OMO mail layout missing or visitor text not escaped');
}
$attachment = $detail['Attachments'][0] ?? null;
if (!$attachment || $attachment['FileName'] !== 'rendez-vous.ics'
    || !str_starts_with($attachment['ContentType'], 'text/calendar')) {
    throw new RuntimeException('Calendar attachment missing or incorrect');
}
$receivedIcs = meetingMailpitGet('message/' . rawurlencode($message['ID']) . '/part/' . rawurlencode($attachment['PartID']));
if ($receivedIcs !== $ics) { throw new RuntimeException('Calendar attachment was changed during delivery'); }
echo "meeting_mailpit_test: OK (OMO layout and escaped details verified, ICS intact, no booking created)\n";
