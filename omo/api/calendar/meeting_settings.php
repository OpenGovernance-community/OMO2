<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/meeting/service.php';
require_once dirname(__DIR__, 3) . '/meeting/translations.php';

use dbObject\MeetingProfile;
use dbObject\ArrayExternalCalendar;

$userId = (int)commonGetCurrentUserId();
if ($userId <= 0) { http_response_code(403); exit; }
if (!MeetingProfile::isStorageAvailable()) { http_response_code(503); exit(meetingEscape(meetingT('storage'))); }
$_SESSION['meeting_settings_csrf'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['meeting_settings_csrf'];
$profile = MeetingProfile::forUser($userId);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    $locked = false;
    try {
        if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) { http_response_code(403); throw new RuntimeException('csrf'); }
        $slug = meetingSlug((string)($_POST['slug'] ?? ''));
        if (!MeetingProfile::slugAvailable($slug, $userId)) { throw new RuntimeException('slug_taken'); }
        if (($_POST['action'] ?? '') === 'check') {
            echo json_encode(['status' => true, 'message' => meetingT('slug_available')]); exit;
        }
        $locked = MeetingProfile::lock($userId);
        if (!$locked) { throw new RuntimeException('busy'); }
        $profile = MeetingProfile::forUser($userId);
        $hours = meetingValidateHours((array)($_POST['hours'] ?? []));
        $profile->set('slug', $slug);
        $profile->set('weekly_hours', json_encode($hours));
        $profile->set('enabled', empty($_POST['enabled']) ? 0 : 1);
        $profile->set('IDexternalcalendar', (int)($_POST['calendar'] ?? 0) ?: null);
        $profile->set('timezone', 'Europe/Zurich');
        if ((int)$profile->get('enabled') || (int)$profile->get('IDexternalcalendar') > 0) {
            $calendar = meetingDestination($profile);
            if ((int)$profile->get('enabled') && !commonExternalCalendarCanCreate($calendar)) { throw new RuntimeException('calendar_invalid'); }
        }
        meetingSave($profile);
        echo json_encode(['status' => true, 'message' => meetingT('saved'), 'path' => '/meeting/' . $slug]);
    } catch (Throwable $exception) {
        $key = $exception instanceof RuntimeException ? $exception->getMessage() : 'storage';
        if (!in_array($key, ['slug_invalid', 'slug_taken', 'csrf', 'busy', 'hours_invalid', 'calendar_invalid', 'storage'], true)) { $key = 'storage'; }
        echo json_encode(['status' => false, 'message' => meetingT($key)]);
    } finally { if ($locked) { MeetingProfile::unlock($userId); } }
    exit;
}
$calendars = new ArrayExternalCalendar();
$calendars->loadForUser($userId, true);
$hours = $profile->hours();
$text = [];
foreach (['settings', 'saving', 'copied', 'unavailable'] as $key) { $text[$key] = meetingT($key); }
?>
<link rel="stylesheet" href="/omo/api/calendar/popups.css?v=20260917-calendar-ui-6">
<form class="generic-drawer-content generic-form-stack" data-topbar-modal-max-width="860px" data-meeting-settings data-text="<?= meetingEscape(json_encode($text)) ?>" action="/omo/api/calendar/meeting_settings.php" method="post">
    <input type="hidden" name="csrf" value="<?= meetingEscape($csrf) ?>">
    <label class="generic-checkbox"><input type="checkbox" name="enabled" value="1" <?= $profile->get('enabled') ? 'checked' : '' ?>><?= meetingEscape(meetingT('enable')) ?></label>
    <div class="generic-form-stack" data-meeting-enabled-content<?= $profile->get('enabled') ? '' : ' hidden' ?>>
        <section class="generic-form-section generic-form-section--divided generic-form-stack">
            <div class="generic-form-grid">
                <label class="generic-form-field"><span class="generic-form-label"><?= meetingEscape(meetingT('slug')) ?></span>
                    <input class="generic-form-control" type="text" name="slug" required minlength="3" maxlength="48" pattern="[A-Za-z][A-Za-z0-9-]{1,46}[A-Za-z0-9]" value="<?= meetingEscape($profile->get('slug')) ?>" placeholder="david" autocapitalize="none">
                    <span class="generic-help-text"><?= meetingEscape(meetingT('slug_hint')) ?></span>
                    <span class="generic-feedback generic-feedback--collapse-empty" data-meeting-slug-status aria-live="polite"></span>
                </label>
                <label class="generic-form-field"><span class="generic-form-label"><?= meetingEscape(meetingT('calendar')) ?></span>
                    <select class="generic-form-control" name="calendar">
                        <option value=""><?= meetingEscape(meetingT('calendar_choose')) ?></option>
                        <?php foreach ($calendars as $calendar): $writable = commonExternalCalendarCanCreate($calendar); ?>
                            <option value="<?= (int)$calendar->getId() ?>" <?= (int)$profile->get('IDexternalcalendar') === (int)$calendar->getId() ? 'selected' : '' ?> <?= $writable ? '' : 'disabled' ?>><?= meetingEscape($calendar->get('title') . ($writable ? '' : ' - ' . meetingT('readonly'))) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="generic-help-text"><?= meetingEscape(meetingT('calendar_hint')) ?></span>
                </label>
            </div>
            <div class="generic-control-action">
                <label class="generic-form-field"><span class="generic-form-label"><?= meetingEscape(meetingT('link')) ?></span><input class="generic-form-control" data-meeting-link readonly value="<?= meetingEscape($profile->get('slug') ? appGetCurrentSiteBaseUrl() . '/meeting/' . $profile->get('slug') : '') ?>"></label>
                <button class="generic-action-button generic-action-button--secondary" type="button" data-meeting-copy><?= meetingEscape(meetingT('copy')) ?></button>
            </div>
        </section>
        <section class="generic-form-section generic-form-section--divided">
            <div class="generic-form-section__copy">
                <h3 class="generic-card-title"><?= meetingEscape(meetingT('hours')) ?></h3>
                <p class="generic-help-text"><?= meetingEscape(meetingT('timezone')) ?></p>
            </div>
            <?php foreach (meetingWeekdayKeys() as $day => $label): $row = $hours[$day]; ?>
                <section class="omo-calendar-meeting__day" data-meeting-day aria-labelledby="meetingDay<?= $day ?>">
                    <div class="omo-calendar-meeting__day-name">
                        <h4 class="generic-card-title generic-card-title--small" id="meetingDay<?= $day ?>"><?= meetingEscape(meetingT($label)) ?></h4>
                        <label class="generic-checkbox"><input type="checkbox" name="hours[<?= $day ?>][open]" value="1" data-meeting-open <?= $row['open'] ? 'checked' : '' ?>><?= meetingEscape(meetingT('open')) ?></label>
                    </div>
                    <div data-meeting-hours class="omo-calendar-meeting__hours" <?= $row['open'] ? '' : 'hidden' ?>>
                        <label class="generic-form-field"><span class="generic-form-label"><?= meetingEscape(meetingT('start')) ?></span><input type="time" class="generic-form-control" name="hours[<?= $day ?>][start]" step="1800" required value="<?= meetingEscape($row['start']) ?>"></label>
                        <div class="generic-form-stack generic-form-stack--compact">
                            <label class="generic-checkbox"><input type="checkbox" name="hours[<?= $day ?>][pause]" value="1" data-meeting-pause <?= $row['pause'] ? 'checked' : '' ?>><?= meetingEscape(meetingT('pause')) ?></label>
                            <div class="omo-calendar-meeting__times" data-meeting-break <?= $row['pause'] ? '' : 'hidden' ?>>
                                <?php foreach (['pause_start', 'pause_end'] as $field): ?>
                                    <input type="time" class="generic-form-control" name="hours[<?= $day ?>][<?= $field ?>]" aria-label="<?= meetingEscape(meetingT($field)) ?>" title="<?= meetingEscape(meetingT($field)) ?>" step="1800" required value="<?= meetingEscape($row[$field]) ?>">
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <label class="generic-form-field"><span class="generic-form-label"><?= meetingEscape(meetingT('end')) ?></span><input type="time" class="generic-form-control" name="hours[<?= $day ?>][end]" step="1800" required value="<?= meetingEscape($row['end']) ?>"></label>
                    </div>
                </section>
            <?php endforeach; ?>
        </section>
    </div>
    <div class="generic-form-actions generic-form-actions--sticky">
        <p class="generic-feedback generic-feedback--collapse-empty" data-meeting-feedback aria-live="polite"></p>
        <button class="generic-action-button generic-action-button--main" type="submit"><?= meetingEscape(meetingT('save')) ?></button>
    </div>
</form>
