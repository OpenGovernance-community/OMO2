<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/external_calendar.php';

use dbObject\ExternalCalendar;

$sourceLang = [
    'unavailable' => ['text' => 'Les calendriers externes ne sont pas disponibles pour le moment.', 'context' => 'External calendar storage or login unavailable.'],
    'method' => ['text' => 'Methode non autorisee.', 'context' => 'Invalid external calendar request method.'],
    'csrf' => ['text' => 'Rechargez le panneau de connexion puis reessayez.', 'context' => 'Missing or expired CSRF token.'],
    'credentials' => ['text' => 'Renseignez une adresse HTTPS, un identifiant et un mot de passe d application.', 'context' => 'Missing CalDAV discovery credentials.'],
    'key' => ['text' => 'La cle EXTERNAL_CALENDAR_ENCRYPTION_KEY doit etre configuree sur le serveur.', 'context' => 'Missing encryption key for calendar credentials.'],
    'expired' => ['text' => 'La recherche a expire. Recherchez a nouveau les agendas.', 'context' => 'Expired CalDAV discovery selection.'],
    'selection' => ['text' => 'Selectionnez un agenda issu de la recherche.', 'context' => 'Invalid discovered calendar selection.'],
    'found' => ['text' => 'Choisissez les agendas a connecter et leur couleur.', 'context' => 'Successful calendar discovery.'],
    'missing' => ['text' => 'Calendrier externe introuvable.', 'context' => 'Calendar absent or not owned by the current user.'],
    'removed' => ['text' => 'Calendrier externe retire.', 'context' => 'Successful calendar disconnection.'],
    'remove_failed' => ['text' => 'Impossible de retirer le calendrier externe.', 'context' => 'Calendar disconnection failed.'],
    'unknown' => ['text' => 'Action inconnue.', 'context' => 'Unknown calendar action.'],
    'save_failed' => ['text' => 'Impossible d enregistrer le calendrier externe.', 'context' => 'Calendar could not be saved.'],
    'synced' => ['text' => '{count} evenement(s) synchronise(s).', 'context' => 'Successful manual calendar sync.'],
    'connected' => ['text' => 'Agenda connecte et synchronise.', 'context' => 'Calendar saved and first sync completed.'],
    'sync_failed' => ['text' => 'Agenda enregistre. Synchronisation a relancer : {reason}', 'context' => 'Calendar saved but initial sync failed.'],
];
$lang = omoLoadTranslationBundle('omo_calendar_external_actions', $sourceLang);
function omoExternalCalendarT($key, array $replace = [])
{
    global $sourceLang, $lang;
    return t($key, $replace, $lang, $sourceLang);
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function omoExternalCalendarReply($status, $message, array $extra = [])
{
    echo json_encode(array_merge(['status' => (bool)$status, 'message' => (string)$message], $extra), JSON_UNESCAPED_SLASHES);
    exit;
}

$userId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
if ($userId <= 0 || !ExternalCalendar::isStorageAvailable()) {
    omoExternalCalendarReply(false, omoExternalCalendarT('unavailable'));
}
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    omoExternalCalendarReply(false, omoExternalCalendarT('method'));
}
$expectedCsrf = (string)($_SESSION['omo_external_calendar_csrf'] ?? '');
if ($expectedCsrf === '' || !hash_equals($expectedCsrf, (string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    omoExternalCalendarReply(false, omoExternalCalendarT('csrf'));
}

$action = trim((string)($_POST['action'] ?? ''));
if ($action === 'discover') {
    $url = commonExternalCalendarNormalizeUrl($_POST['server_url'] ?? '');
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($url === null || $username === '' || strlen($username) > 250 || str_contains($username, ':') || $password === '' || strlen($password) > 4096) {
        omoExternalCalendarReply(false, omoExternalCalendarT('credentials'));
    }
    $encrypted = commonExternalCalendarEncryptPassword($password);
    if ($encrypted === null) {
        omoExternalCalendarReply(false, omoExternalCalendarT('key'));
    }
    $result = commonExternalCalendarDiscover($url, $username, $password);
    if (empty($result['status'])) {
        omoExternalCalendarReply(false, $result['message']);
    }
    $token = bin2hex(random_bytes(24));
    // Short-lived, user-bound selection; the browser never receives the stored credentials.
    $discoveries = array_filter((array)($_SESSION['omo_external_calendar_discoveries'] ?? []),
        static fn($item) => is_array($item) && ($item['expires'] ?? 0) > time() && ($item['user'] ?? 0) === $userId);
    $discoveries = array_slice($discoveries, -3, null, true);
    $discoveries[$token] = ['user' => $userId, 'expires' => time() + 900, 'username' => $username,
        'password' => $encrypted, 'calendars' => $result['calendars']];
    $_SESSION['omo_external_calendar_discoveries'] = $discoveries;
    $items = [];
    foreach ($result['calendars'] as $index => $item) {
        $existing = new ExternalCalendar();
        $connected = $existing->load([['IDuser', $userId], ['calendar_url', $item['url']]]);
        $items[] = ['index' => $index, 'title' => $connected ? $existing->get('title') : $item['title'],
            'color' => $connected ? $existing->get('color') : $item['color'], 'connected' => $connected];
    }
    omoExternalCalendarReply(true, omoExternalCalendarT('found'), ['discoveryToken' => $token, 'calendars' => $items]);
}
$calendarId = (int)($_POST['calendar_id'] ?? 0);
$calendar = null;
if ($calendarId > 0) {
    $candidate = new ExternalCalendar();
    if ($candidate->load($calendarId) && (int)$candidate->get('IDuser') === $userId) {
        $calendar = $candidate;
    } else {
        omoExternalCalendarReply(false, omoExternalCalendarT('missing'));
    }
}

if ($action === 'delete') {
    if (!$calendar instanceof ExternalCalendar) {
        omoExternalCalendarReply(false, omoExternalCalendarT('missing'));
    }
    $result = $calendar->delete();
    omoExternalCalendarReply($result === true, omoExternalCalendarT($result === true ? 'removed' : 'remove_failed'));
}

if ($action === 'sync') {
    if (!$calendar instanceof ExternalCalendar) {
        omoExternalCalendarReply(false, omoExternalCalendarT('missing'));
    }
    $result = commonExternalCalendarSynchronize($calendar, null, null, true);
    omoExternalCalendarReply(
        !empty($result['status']),
        !empty($result['status'])
            ? omoExternalCalendarT('synced', ['count' => (int)($result['count'] ?? 0)])
            : (string)($result['message'] ?? 'Synchronisation impossible.'),
        ['count' => (int)($result['count'] ?? 0)]
    );
}

if ($action !== 'save') {
    omoExternalCalendarReply(false, omoExternalCalendarT('unknown'));
}

$discovery = $_SESSION['omo_external_calendar_discoveries'][(string)($_POST['discovery_token'] ?? '')] ?? null;
if (!is_array($discovery) || $discovery['user'] !== $userId || $discovery['expires'] <= time()) {
    omoExternalCalendarReply(false, omoExternalCalendarT('expired'));
}
$index = filter_var($_POST['calendar_index'] ?? null, FILTER_VALIDATE_INT);
$selected = $index !== false && $index !== null ? ($discovery['calendars'][$index] ?? null) : null;
if (!is_array($selected)) {
    omoExternalCalendarReply(false, omoExternalCalendarT('selection'));
}
$title = trim((string)($_POST['title'] ?? '')) ?: $selected['title'];
$calendarUrl = $selected['url'];
$username = $discovery['username'];
$color = ExternalCalendar::normalizeColor($_POST['color'] ?? '');
$calendar = new ExternalCalendar();
if (!$calendar->load([['IDuser', $userId], ['calendar_url', $calendarUrl]])) {
    $calendar->set('IDuser', $userId);
    $calendar->set('provider', 'caldav');
    $calendar->set('created_at', new DateTimeImmutable('now'));
}
$calendar->set('title', mb_substr($title, 0, 190, 'UTF-8'));
$calendar->set('calendar_url', $calendarUrl);
$calendar->set('username', mb_substr($username, 0, 250, 'UTF-8'));
$calendar->set('color', $color);
$calendar->set('active', 1);
$calendar->set('updated_at', new DateTimeImmutable('now'));
$calendar->set('password_encrypted', $discovery['password']);
$saveResult = $calendar->save();
if (!is_array($saveResult) || empty($saveResult['status'])) {
    omoExternalCalendarReply(false, omoExternalCalendarT('save_failed'));
}
$calendar->load((int)$calendar->getId(), true);

$syncResult = commonExternalCalendarSynchronize($calendar, null, null, true);
omoExternalCalendarReply(
    true,
    !empty($syncResult['status'])
        ? omoExternalCalendarT('connected')
        : omoExternalCalendarT('sync_failed', ['reason' => (string)($syncResult['message'] ?? '')]),
    ['calendarId' => (int)$calendar->getId(), 'synced' => !empty($syncResult['status']), 'count' => (int)($syncResult['count'] ?? 0)]
);
