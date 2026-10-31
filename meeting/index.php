<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once __DIR__ . '/translations.php';
require_once __DIR__ . '/service.php';

use dbObject\MeetingProfile;
use dbObject\MeetingBooking;
use dbObject\AuthRateLimit;
use dbObject\User;

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: frame-ancestors \'self\'');
$_SESSION['meeting_csrf'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['meeting_csrf'];
$error = '';
$profile = null;
$draft = null;
$receipt = null;
$selectedSlot = null;
$durationMinutes = 30;
$selectedMethod = null;
$dayResults = [];
$guest = ['name' => '', 'email' => '', 'reason' => ''];
$name = strtolower(trim((string)($_GET['name'] ?? '')));
$zone = new DateTimeZone('Europe/Zurich');
$now = new DateTimeImmutable('now', $zone);
$month = $now->modify('first day of this month')->setTime(0, 0);
$selectedDay = null;
$path = '/meeting/' . rawurlencode($name);
$ownerName = '';
$ownerPhoto = '';
$visitor = null;
$identifiedVisitor = null;
$visitorCalendars = new \dbObject\ArrayExternalCalendar();
$visitorCalendarId = 0;

try {
    $visitor = meetingVisitorIdentity();
    if (!MeetingProfile::isStorageAvailable()) { throw new RuntimeException('disabled'); }
    $limit = AuthRateLimit::consume('meeting-read', commonAuthHashIdentifier('meeting-ip', commonGetRequestIp()), 90, 60);
    if (empty($limit['allowed'])) { http_response_code(429); throw new RuntimeException('rate'); }
    $candidate = new MeetingProfile();
    $found = ctype_digit($name) ? $candidate->load(['IDuser', (int)$name]) : $candidate->load(['slug', $name]);
    if (!$found || !$candidate->get('enabled')) { http_response_code(404); throw new RuntimeException('disabled'); }
    meetingDestination($candidate);
    $profile = $candidate;
    $userId = (int)$profile->get('IDuser');
    $identityKey = $userId . ':' . ($visitor['id'] ?? 0);
    $identifiedVisitor = $visitor && empty($_SESSION['meeting_manual_identity'][$identityKey]) ? $visitor : null;
    if ($identifiedVisitor) {
        $visitorCalendars->loadMeetingDestinationsForUser($identifiedVisitor['id']);
        $defaultCalendarId = (int)MeetingProfile::forUser($identifiedVisitor['id'])->get('IDexternalcalendar');
        foreach ($visitorCalendars as $calendar) {
            if ((int)$calendar->getId() === $defaultCalendarId) { $visitorCalendarId = $defaultCalendarId; }
        }
    }
    $ownerName = (string)$profile->get('slug');
    $owner = new User();
    if ($owner->load($userId)) {
        $displayName = trim((string)$owner->getScopedDisplayName());
        if ($displayName !== '') { $ownerName = $displayName; }
        $photo = $owner->getProfilePhotoUrl();
        // Only image URLs, never executable schemes.
        if (preg_match('~^(?:https?://|/(?!/))~i', $photo)) { $ownerPhoto = $photo; }
    }
    $path = '/meeting/' . rawurlencode($profile->get('slug'));
    if (!empty($_GET['receipt'])) {
        $token = (string)$_GET['receipt'];
        $stored = $_SESSION['meeting_drafts'][$token] ?? null;
        $booking = new MeetingBooking();
        if (!is_array($stored) || (int)$stored['user'] !== $userId || !$booking->load(['token', $token])
            || (int)$booking->get('IDuser') !== $userId || $booking->get('status') !== 'confirmed') { throw new RuntimeException('expired'); }
        $receipt = $booking;
        if (isset($_GET['download'])) {
            header('Content-Type: text/calendar; charset=UTF-8');
            header('Content-Disposition: attachment; filename="rendez-vous.ics"');
            echo meetingGuestIcs($booking); exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) { http_response_code(403); throw new RuntimeException('csrf'); }
        $limit = AuthRateLimit::consume('meeting-submit', commonAuthHashIdentifier('meeting-ip', commonGetRequestIp()), 20, 600);
        if (empty($limit['allowed'])) { http_response_code(429); throw new RuntimeException('rate'); }
        if (($_POST['action'] ?? '') === 'confirm') {
            $token = (string)($_POST['token'] ?? '');
            $stored = $_SESSION['meeting_drafts'][$token] ?? null;
            if (!is_array($stored) || (int)$stored['user'] !== $userId) { throw new RuntimeException('expired'); }
            $draft = $stored;
            $existing = new MeetingBooking();
            $hasBooking = $existing->load(['token', $token]);
            if ($stored['expires'] < time() && !$hasBooking) { throw new RuntimeException('expired'); }
            $receipt = meetingBook($userId, $draft);
            header('Location: ' . $path . '?receipt=' . rawurlencode($token), true, 303); exit;
        }
        if (!empty($_POST['website'])) { throw new RuntimeException('guest_invalid'); }
        foreach ($guest as $field => $value) { $guest[$field] = trim((string)($_POST[$field] ?? '')); }
        if (($_POST['action'] ?? '') === 'manual_identity') {
            $_SESSION['meeting_manual_identity'][$identityKey] = true;
            $identifiedVisitor = null;
            $guest['name'] = $guest['email'] = '';
        } elseif (!empty($_POST['identity_user_id'])) {
            if (!$identifiedVisitor || (int)$_POST['identity_user_id'] !== $identifiedVisitor['id']) { throw new RuntimeException('identity_changed'); }
            $guest['name'] = $identifiedVisitor['name'];
            $guest['email'] = $identifiedVisitor['email'];
            $visitorCalendarId = filter_var($_POST['guest_calendar_id'] ?? 0, FILTER_VALIDATE_INT);
            if ($visitorCalendarId === false || $visitorCalendarId < 0) { throw new RuntimeException('guest_calendar_invalid'); }
            meetingVisitorCalendar($identifiedVisitor['id'], $visitorCalendarId);
        } else {
            // A form opened anonymously stays a manual booking even if another tab logged in.
            $identifiedVisitor = null;
            if (($_POST['identity_mode'] ?? '') === 'manual' && $visitor) { $_SESSION['meeting_manual_identity'][$identityKey] = true; }
        }
        $selectedDay = meetingDate((string)($_POST['date'] ?? ''), $zone);
        $selectedTime = (string)($_POST['time'] ?? '');
    }
    if (!$receipt && !$draft) {
        $durationMinutes = meetingValidateDuration($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['duration'] ?? 30) : ($_GET['duration'] ?? 30), $profile->maxDurationMinutes());
        $selectedMethod = meetingResolveMethod($profile, $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['method'] ?? '') : ($_GET['method'] ?? ''), $_SERVER['REQUEST_METHOD'] !== 'POST');
        if (!$selectedDay && !empty($_GET['date'])) { $selectedDay = meetingDate((string)$_GET['date'], $zone); }
        $monthValue = $selectedDay ? $selectedDay->format('Y-m') : (string)($_GET['month'] ?? $month->format('Y-m'));
        $month = meetingDate($monthValue . '-01', $zone);
        $lastDate = $now->modify('+365 days');
        if ($month < $now->modify('first day of this month')->setTime(0, 0) || $month > $lastDate
            || ($selectedDay && ($selectedDay < $now->setTime(0, 0) || $selectedDay > $lastDate))) { throw new RuntimeException('date_invalid'); }
        [$availabilityStart, $availabilityEnd] = meetingCalendarQueryRange($month, $now,
            (int)$profile->get('preparation_minutes'), (int)$profile->get('closing_minutes'));
        $busy = meetingBusy($profile, $availabilityStart, $availabilityEnd);
        for ($day = $month; $day < $month->modify('+1 month'); $day = $day->modify('+1 day')) {
            $dayResults[$day->format('Y-m-d')] = $day > $lastDate ? ['state' => 'closed', 'slots' => []] : meetingDay($day, $profile->availabilityHours(), $busy, $now, $durationMinutes, (int)$profile->get('preparation_minutes'), (int)$profile->get('closing_minutes'));
        }
        $selectedTime = $selectedTime ?? (string)($_GET['time'] ?? '');
        if ($selectedDay && $selectedTime !== '') {
            foreach ($dayResults[$selectedDay->format('Y-m-d')]['slots'] as $slot) {
                if ($slot['time'] === $selectedTime && $slot['bookable']) { $selectedSlot = $slot; }
            }
            if (!$selectedSlot) { throw new RuntimeException('slot_taken'); }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'manual_identity') {
            if (!$selectedSlot) { throw new RuntimeException('slot_taken'); }
            if ($guest['name'] === '' || mb_strlen($guest['name']) > 190 || preg_match('/[\x00-\x1f\x7f]/', $guest['name'])
                || !filter_var($guest['email'], FILTER_VALIDATE_EMAIL) || strlen($guest['email']) > 254
                || $guest['reason'] === '' || mb_strlen($guest['reason']) > 4000 || str_contains($guest['reason'], "\0")) { throw new RuntimeException('guest_invalid'); }
            $limit = AuthRateLimit::consume('meeting-email', commonAuthHashIdentifier('meeting-email', $guest['email']), 10, 3600);
            if (empty($limit['allowed'])) { throw new RuntimeException('rate'); }
            $draft = $guest + ['token' => bin2hex(random_bytes(32)), 'user' => $userId, 'date' => $selectedDay->format('Y-m-d'),
                'time' => $selectedTime, 'duration' => $durationMinutes, 'method' => $selectedMethod, 'expires' => time() + 900,
                'guest_user_id' => $identifiedVisitor['id'] ?? null,
                'guest_calendar_id' => $identifiedVisitor ? $visitorCalendarId : 0,
                'guest_calendar_title' => $identifiedVisitor && $visitorCalendarId ? meetingVisitorCalendar($identifiedVisitor['id'], $visitorCalendarId)->get('title') : ''];
            $drafts = array_slice((array)($_SESSION['meeting_drafts'] ?? []), -19, null, true);
            $drafts[$draft['token']] = $draft;
            $_SESSION['meeting_drafts'] = $drafts;
        }
    }
} catch (Throwable $exception) {
    $key = $exception instanceof RuntimeException ? $exception->getMessage() : 'unavailable';
    if (!in_array($key, ['disabled', 'rate', 'csrf', 'expired', 'identity_changed', 'guest_calendar_invalid', 'guest_invalid', 'date_invalid', 'duration_invalid', 'method_invalid', 'slot_taken', 'busy', 'pending', 'write_failed', 'calendar_invalid', 'storage', 'unavailable'], true)) {
        error_log('Meeting request failed: ' . get_class($exception)); $key = 'unavailable';
    }
    $error = meetingT($key);
}
$monthKeys = [1 => 'january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
require __DIR__ . '/view.php';
