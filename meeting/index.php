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

try {
    if (!MeetingProfile::isStorageAvailable()) { throw new RuntimeException('disabled'); }
    $limit = AuthRateLimit::consume('meeting-read', commonAuthHashIdentifier('meeting-ip', commonGetRequestIp()), 90, 60);
    if (empty($limit['allowed'])) { http_response_code(429); throw new RuntimeException('rate'); }
    $candidate = new MeetingProfile();
    $found = ctype_digit($name) ? $candidate->load(['IDuser', (int)$name]) : $candidate->load(['slug', $name]);
    if (!$found || !$candidate->get('enabled')) { http_response_code(404); throw new RuntimeException('disabled'); }
    meetingDestination($candidate);
    $profile = $candidate;
    $userId = (int)$profile->get('IDuser');
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
            echo $booking->get('calendar_data'); exit;
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
        $selectedDay = meetingDate((string)($_POST['date'] ?? ''), $zone);
        $selectedTime = (string)($_POST['time'] ?? '');
    }
    if (!$receipt && !$draft) {
        if (!$selectedDay && !empty($_GET['date'])) { $selectedDay = meetingDate((string)$_GET['date'], $zone); }
        $monthValue = $selectedDay ? $selectedDay->format('Y-m') : (string)($_GET['month'] ?? $month->format('Y-m'));
        $month = meetingDate($monthValue . '-01', $zone);
        $lastDate = $now->modify('+365 days');
        if ($month < $now->modify('first day of this month')->setTime(0, 0) || $month > $lastDate
            || ($selectedDay && ($selectedDay < $now->setTime(0, 0) || $selectedDay > $lastDate))) { throw new RuntimeException('date_invalid'); }
        $busy = meetingBusy($profile, $month, $month->modify('+1 month'));
        for ($day = $month; $day < $month->modify('+1 month'); $day = $day->modify('+1 day')) {
            $dayResults[$day->format('Y-m-d')] = $day > $lastDate ? ['state' => 'closed', 'slots' => []] : meetingDay($day, $profile->hours(), $busy, $now);
        }
        $selectedTime = $selectedTime ?? (string)($_GET['time'] ?? '');
        if ($selectedDay && $selectedTime !== '') {
            foreach ($dayResults[$selectedDay->format('Y-m-d')]['slots'] as $slot) {
                if ($slot['time'] === $selectedTime && $slot['free']) { $selectedSlot = $slot; }
            }
            if (!$selectedSlot) { throw new RuntimeException('slot_taken'); }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$selectedSlot) { throw new RuntimeException('slot_taken'); }
            if ($guest['name'] === '' || mb_strlen($guest['name']) > 190 || preg_match('/[\x00-\x1f\x7f]/', $guest['name'])
                || !filter_var($guest['email'], FILTER_VALIDATE_EMAIL) || strlen($guest['email']) > 254
                || $guest['reason'] === '' || mb_strlen($guest['reason']) > 4000 || str_contains($guest['reason'], "\0")) { throw new RuntimeException('guest_invalid'); }
            $limit = AuthRateLimit::consume('meeting-email', commonAuthHashIdentifier('meeting-email', $guest['email']), 10, 3600);
            if (empty($limit['allowed'])) { throw new RuntimeException('rate'); }
            $draft = $guest + ['token' => bin2hex(random_bytes(32)), 'user' => $userId, 'date' => $selectedDay->format('Y-m-d'),
                'time' => $selectedTime, 'expires' => time() + 900];
            $drafts = array_slice((array)($_SESSION['meeting_drafts'] ?? []), -19, null, true);
            $drafts[$draft['token']] = $draft;
            $_SESSION['meeting_drafts'] = $drafts;
        }
    }
} catch (Throwable $exception) {
    $key = $exception instanceof RuntimeException ? $exception->getMessage() : 'unavailable';
    if (!in_array($key, ['disabled', 'rate', 'csrf', 'expired', 'guest_invalid', 'date_invalid', 'slot_taken', 'busy', 'pending', 'write_failed', 'calendar_invalid', 'storage', 'unavailable'], true)) {
        error_log('Meeting request failed: ' . get_class($exception)); $key = 'unavailable';
    }
    $error = meetingT($key);
}
$monthKeys = [1 => 'january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
require __DIR__ . '/view.php';
