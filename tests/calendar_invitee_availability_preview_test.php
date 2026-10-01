<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common/user_availability.php';
require_once dirname(__DIR__) . '/common/calendar/availability-grid.php';

function inviteAvailabilityExpect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$zone = new DateTimeZone('Europe/Zurich');
$day = new DateTimeImmutable('2030-01-07', $zone);
$firstHours = [1 => ['open' => true, 'start' => '09:00', 'end' => '12:00', 'pause' => false]];
$secondHours = [1 => ['open' => true, 'start' => '10:00', 'end' => '13:00', 'pause' => false]];
$participants = [
    ['hours' => $firstHours, 'busy' => []],
    ['hours' => $secondHours, 'busy' => [[$day->setTime(10, 30), $day->setTime(11, 0)]]],
];
$combined = commonUserAvailabilityBuildCombinedDay($day, $participants);
$slots = array_column($combined['slots'], null);
inviteAvailabilityExpect($combined['state'] === 'partial', 'One busy invitee makes the shared day partial.');
inviteAvailabilityExpect(count($slots) === 4 && $slots[0]['start']->format('H:i') === '10:00' && $slots[3]['end']->format('H:i') === '12:00', 'The preview shows only overlapping working hours.');
inviteAvailabilityExpect(!$slots[0]['busy'] && $slots[1]['busy'] && !$slots[2]['busy'], 'Busy intervals from any participant block the shared slot.');
inviteAvailabilityExpect($slots[1]['busyCount'] === 1 && $slots[1]['participantCount'] === 2, 'Conflicts count people, not appointments.');
inviteAvailabilityExpect($combined['workingCount'] === 4 && $combined['busySlotCount'] === 1, 'Daily ratio counts common half-hours, not people.');
$encoded = commonUserAvailabilityEncodeDay(commonUserAvailabilityBuildDay($day, $secondHours, $participants[1]['busy']));
inviteAvailabilityExpect(strlen($encoded) === 48 && $encoded[19] === '0' && $encoded[20] === '1' && $encoded[21] === '2' && $encoded[22] === '1', 'Compact client data retains closed, free and occupied half-hours.');
inviteAvailabilityExpect(commonUserAvailabilityBuildCombinedDay($day, [['hours' => $firstHours, 'busy' => []], ['hours' => [1 => ['open' => false]], 'busy' => []]])['state'] === 'closed', 'A closed participant closes the shared day.');

$labels = array_fill_keys(['heading', 'previous_month', 'next_month', 'free', 'partial', 'full', 'closed', 'select_day', 'select_day_hint', 'no_hours', 'pause', 'busy', 'available'], 'Test');
ob_start();
commonCalendarRenderAvailabilityGrid($day->modify('first day of this month'), $day, [$day->format('Y-m-d') => $combined], $labels, 'data-omo-calendar-preview-target', static fn() => 'month=2030-01&date=2030-01-07');
$html = (string)ob_get_clean();
inviteAvailabilityExpect(str_contains($html, '10:30 - 11:00') && str_contains($html, 'data-state="busy"'), 'The shared grid renders occupied half-hour slots.');
inviteAvailabilityExpect(!str_contains($html, '09:00 - 09:30') && !str_contains($html, '12:00 - 12:30'), 'Hours outside the overlap are hidden.');

$selectableLabels = $labels + ['select_slot' => 'Choisir', 'selection_hint' => 'Choisir une plage', 'range_blocked' => 'Plage occupée', 'range_selected' => 'Plage choisie', 'day_availability' => '{free} / {total} libres', 'occupation_scale' => 'Plus disponible → Moins disponible'];
ob_start();
commonCalendarRenderAvailabilityGrid($day->modify('first day of this month'), $day, [$day->format('Y-m-d') => $combined], $selectableLabels, 'data-omo-calendar-preview-target', static fn() => 'month=2030-01&date=2030-01-07', '', true);
$selectableHtml = (string)ob_get_clean();
inviteAvailabilityExpect(str_contains($selectableHtml, 'data-busy-slots="1" style="--param-freebusy-busy-hue:36"') && str_contains($selectableHtml, 'title="3 / 4 libres"'), 'Server-rendered days use the daily ratio and show common free counts.');
inviteAvailabilityExpect(str_contains($selectableHtml, 'data-state="occupation"'), 'Calendar legend explains the continuous gradient.');
inviteAvailabilityExpect(str_contains($selectableHtml, '<button type="button" class="calendar-freebusy-slot" data-state="free"'), 'Free half-hours can be selected in the event editor.');
inviteAvailabilityExpect(str_contains($selectableHtml, '<div class="calendar-freebusy-slot" data-state="busy"'), 'Busy half-hours are never selectable.');
inviteAvailabilityExpect(str_contains($selectableHtml, 'data-omo-calendar-preview-slot-start="2030-01-07T10:00"'), 'Selectable slots expose local event times.');

$firstBreakHours = [1 => ['open' => true, 'start' => '09:00', 'end' => '17:00', 'pause' => true, 'pause_start' => '12:00', 'pause_end' => '13:00']];
$secondBreakHours = [1 => ['open' => true, 'start' => '09:00', 'end' => '17:00', 'pause' => true, 'pause_start' => '14:00', 'pause_end' => '15:00']];
$singleBreak = commonUserAvailabilityBuildDay($day, $firstBreakHours, []);
ob_start();
commonCalendarRenderAvailabilityGrid($day->modify('first day of this month'), $day, [$day->format('Y-m-d') => $singleBreak], $labels, 'data-user-availability-url', static fn() => '/popup/user.php');
$singleBreakHtml = (string)ob_get_clean();
inviteAvailabilityExpect(substr_count($singleBreakHtml, '<div class="calendar-freebusy-pause">') === 1, 'A one-hour break has one separator in the profile.');

$separateBreaks = commonUserAvailabilityBuildCombinedDay($day, [
    ['hours' => $firstBreakHours, 'busy' => []],
    ['hours' => $secondBreakHours, 'busy' => []],
]);
ob_start();
commonCalendarRenderAvailabilityGrid($day->modify('first day of this month'), $day, [$day->format('Y-m-d') => $separateBreaks], $selectableLabels, 'data-omo-calendar-preview-target', static fn() => 'month=2030-01&date=2030-01-07', '', true);
$separateBreaksHtml = (string)ob_get_clean();
inviteAvailabilityExpect(substr_count($separateBreaksHtml, '<div class="calendar-freebusy-pause">') === 2, 'Two distinct breaks keep separate markers in the event editor.');

$root = dirname(__DIR__);
$editor = (string)file_get_contents($root . '/omo/api/calendar/create.php');
$script = (string)file_get_contents($root . '/common/calendar/availability.js');
inviteAvailabilityExpect(str_contains($editor, 'data-omo-calendar-preview-tab') && str_contains($editor, "!empty(\$_POST['availability_preview'])"), 'The editor exposes a lazy preview endpoint.');
inviteAvailabilityExpect(str_contains($script, 'markPreviewDirty') && str_contains($script, 'loadPreview(form, false)'), 'Changed invitations are recalculated when the tab is visited.');
inviteAvailabilityExpect(str_contains($script, "var initialDate = start ? String(start.value || '').slice(0, 10)") && str_contains($script, "state.date = String(field.value || '').slice(0, 10)"), 'Initial and changed event dates select their own day in the preview.');
inviteAvailabilityExpect(str_contains($script, 'choosePreviewSlot(slotForm, slot, event.shiftKey)') && str_contains($script, 'form.dataset.omoCalendarLastStart = startValue'), 'Shift-click updates the editor schedule and its duration tracking.');

echo "calendar_invitee_availability_preview_test: OK\n";
