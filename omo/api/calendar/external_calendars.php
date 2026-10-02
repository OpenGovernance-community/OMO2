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
    'ics_url' => ['text' => 'Indiquez une adresse ICS HTTPS valide.', 'context' => 'Invalid subscription URL.'],
    'ics_connected' => ['text' => 'Agenda ICS ajoute et synchronise.', 'context' => 'ICS subscription created.'],
    'updated' => ['text' => 'Calendrier mis a jour.', 'context' => 'Calendar settings saved.'],
    'title_required' => ['text' => 'Indiquez un nom pour ce calendrier.', 'context' => 'Missing calendar display name.'],
    'duplicate' => ['text' => 'Cette adresse est deja utilisee par un autre calendrier connecte.', 'context' => 'Duplicate calendar URL during editing.'],
    'availability_destination' => ['text' => 'Ce calendrier reçoit vos réservations. Choisissez d’abord un autre calendrier de destination dans les paramètres de prise de rendez-vous.', 'context' => 'A booking destination cannot become an availability calendar.'],
    'busy' => ['text' => 'Une réservation ou une modification est en cours. Réessayez dans un instant.', 'context' => 'Calendar settings owner lock unavailable.'],
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
function omoExternalCalendarSetAvailability(ExternalCalendar $calendar, int $userId): void
{
    if (!array_key_exists('availability_only', $_POST)) { return; }
    $availability = !empty($_POST['availability_only']);
    if ($availability && $calendar->getId() && \dbObject\MeetingProfile::isStorageAvailable()) {
        $profile = \dbObject\MeetingProfile::forUser($userId);
        if ((int)$profile->get('IDexternalcalendar') === (int)$calendar->getId()) {
            omoExternalCalendarReply(false, omoExternalCalendarT('availability_destination'));
        }
    }
    $calendar->set('availability_only', $availability ? 1 : 0);
}
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
            'color' => $connected ? $existing->get('color') : $item['color'], 'connected' => $connected,
            'availability_only' => $connected && (bool)$existing->get('availability_only')];
    }
    omoExternalCalendarReply(true, omoExternalCalendarT('found'), ['discoveryToken' => $token, 'calendars' => $items]);
}
$calendarId = (int)($_POST['calendar_id'] ?? 0);
if (in_array($action, ['update', 'save', 'save_ics'], true)) {
    if (!\dbObject\MeetingProfile::lock($userId)) { omoExternalCalendarReply(false, omoExternalCalendarT('busy')); }
    register_shutdown_function(static fn() => \dbObject\MeetingProfile::unlock($userId));
}
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

if ($action === 'update') {
    if (!$calendar instanceof ExternalCalendar) {
        omoExternalCalendarReply(false, omoExternalCalendarT('missing'));
    }
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') { omoExternalCalendarReply(false, omoExternalCalendarT('title_required')); }
    $isIcs = (string)$calendar->get('provider') === 'ics';
    $url = (string)$calendar->get('calendar_url');
    $username = (string)$calendar->get('username');
    $encrypted = (string)$calendar->get('password_encrypted');
    $connectionChanged = false;
    if ($isIcs) {
        // Never return the stored bearer URL to the browser. Blank means unchanged.
        $replacement = trim((string)($_POST['ics_url'] ?? ''));
        if ($replacement !== '') {
            $replacement = commonExternalCalendarNormalizeUrl($replacement);
            if ($replacement === null) { omoExternalCalendarReply(false, omoExternalCalendarT('ics_url')); }
            $url = 'ics:' . hash('sha256', $replacement);
            $encrypted = commonExternalCalendarEncryptPassword($replacement);
            $connectionChanged = true;
        }
    } else {
        $url = commonExternalCalendarNormalizeUrl($_POST['calendar_url'] ?? '');
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if ($url === null || $username === '' || strlen($username) > 250 || str_contains($username, ':') || strlen($password) > 4096) {
            omoExternalCalendarReply(false, omoExternalCalendarT('credentials'));
        }
        $connectionChanged = $url !== (string)$calendar->get('calendar_url') || $username !== (string)$calendar->get('username') || $password !== '';
        if ($password !== '') { $encrypted = commonExternalCalendarEncryptPassword($password); }
    }
    if ($encrypted === null) { omoExternalCalendarReply(false, omoExternalCalendarT('key')); }
    $duplicate = new ExternalCalendar();
    if ($duplicate->load([['IDuser', $userId], ['calendar_url', $url]]) && (int)$duplicate->getId() !== (int)$calendar->getId()) {
        omoExternalCalendarReply(false, omoExternalCalendarT('duplicate'));
    }
    $calendar->set('title', mb_substr($title, 0, 190, 'UTF-8'));
    $calendar->set('color', ExternalCalendar::normalizeColor($_POST['color'] ?? ''));
    $calendar->set('calendar_url', $url);
    $calendar->set('username', $username);
    $calendar->set('password_encrypted', $encrypted);
    $calendar->set('updated_at', new DateTimeImmutable('now'));
    omoExternalCalendarSetAvailability($calendar, $userId);
    if ($connectionChanged) {
        $calendar->set('source_ctag', null);
        $calendar->set('last_sync_at', null);
    }
    $saved = $calendar->save();
    if (!is_array($saved) || empty($saved['status'])) { omoExternalCalendarReply(false, omoExternalCalendarT('save_failed')); }
    if (!$connectionChanged) { omoExternalCalendarReply(true, omoExternalCalendarT('updated')); }
    $calendar->load((int)$calendar->getId(), true);
    $sync = commonExternalCalendarSynchronize($calendar, null, null, true);
    omoExternalCalendarReply(true, !empty($sync['status']) ? omoExternalCalendarT('updated')
        : omoExternalCalendarT('sync_failed', ['reason' => (string)($sync['message'] ?? '')]),
        ['synced' => !empty($sync['status'])]);
}

if ($action === 'save_ics') {
    $url = commonExternalCalendarNormalizeUrl($_POST['ics_url'] ?? '');
    if ($url === null) { omoExternalCalendarReply(false, omoExternalCalendarT('ics_url')); }
    $encrypted = commonExternalCalendarEncryptPassword($url);
    if ($encrypted === null) { omoExternalCalendarReply(false, omoExternalCalendarT('key')); }
    // The private URL is a bearer credential: keep only a digest in the visible URL field.
    $calendarKey = 'ics:' . hash('sha256', $url);
    $title = trim((string)($_POST['title'] ?? '')) ?: 'Agenda ICS';
    $calendar = new ExternalCalendar();
    if (!$calendar->load([['IDuser', $userId], ['calendar_url', $calendarKey]])) {
        $calendar->set('IDuser', $userId);
        $calendar->set('created_at', new DateTimeImmutable('now'));
    }
    $calendar->set('provider', 'ics');
    omoExternalCalendarSetAvailability($calendar, $userId);
    $calendar->set('title', mb_substr($title, 0, 190, 'UTF-8'));
    $calendar->set('calendar_url', $calendarKey);
    $calendar->set('username', 'ics');
    $calendar->set('password_encrypted', $encrypted);
    $calendar->set('color', ExternalCalendar::normalizeColor($_POST['color'] ?? ''));
    $calendar->set('active', 1);
    $calendar->set('updated_at', new DateTimeImmutable('now'));
    $saved = $calendar->save();
    if (!is_array($saved) || empty($saved['status'])) { omoExternalCalendarReply(false, omoExternalCalendarT('save_failed')); }
    $calendar->load((int)$calendar->getId(), true);
    $sync = commonExternalCalendarSynchronize($calendar, null, null, true);
    omoExternalCalendarReply(true, !empty($sync['status']) ? omoExternalCalendarT('ics_connected')
        : omoExternalCalendarT('sync_failed', ['reason' => (string)($sync['message'] ?? '')]),
        ['calendarId' => (int)$calendar->getId(), 'synced' => !empty($sync['status']), 'count' => (int)($sync['count'] ?? 0)]);
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
omoExternalCalendarSetAvailability($calendar, $userId);
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
