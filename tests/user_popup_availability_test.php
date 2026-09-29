<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common/user_availability.php';

function userAvailabilityExpect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$zone = new DateTimeZone('Europe/Zurich');
$day = new DateTimeImmutable('2030-01-07', $zone);
$hours = [];
for ($weekday = 1; $weekday <= 7; $weekday += 1) {
    $hours[$weekday] = [
        'open' => $weekday <= 5,
        'start' => '09:00',
        'end' => '17:00',
        'pause' => $weekday === 1,
        'pause_start' => '12:00',
        'pause_end' => '13:00',
    ];
}

$partial = commonUserAvailabilityBuildDay($day, $hours, [[$day->setTime(9, 30), $day->setTime(10, 30)]]);
userAvailabilityExpect($partial['state'] === 'partial', 'A partly busy working day is yellow.');
$slots = [];
foreach ($partial['slots'] as $slot) {
    $slots[$slot['start']->format('H:i')] = $slot;
}
userAvailabilityExpect(!$slots['09:00']['busy'] && $slots['09:30']['busy'] && $slots['10:00']['busy'] && !$slots['10:30']['busy'], 'Busy slots match the true event interval.');
userAvailabilityExpect($slots['12:00']['pause'] && $slots['12:30']['pause'], 'The lunch break remains distinct from busy time.');

$full = commonUserAvailabilityBuildDay($day, $hours, [[$day->setTime(9, 0), $day->setTime(17, 0)]]);
userAvailabilityExpect($full['state'] === 'full', 'A fully busy working day is occupied.');
userAvailabilityExpect(commonUserAvailabilityBuildDay($day->modify('+5 days'), $hours, [])['state'] === 'closed', 'A closed day is not reported as free.');
userAvailabilityExpect(!commonUserAvailabilityOverlaps($day->setTime(9, 0), $day->setTime(10, 0), [[$day->setTime(10, 0), $day->setTime(11, 0)]]), 'Touching intervals do not overlap.');

$root = dirname(__DIR__);
$popup = (string)file_get_contents($root . '/popup/user.php');
$script = (string)file_get_contents($root . '/common/team/user-popup.js');
$styles = (string)file_get_contents($root . '/common/team/user-popup.css');
userAvailabilityExpect(str_contains($popup, 'omo-user-context-panel-availability') && str_contains($popup, 'section=availability'), 'The profile exposes a lazy availability tab.');
userAvailabilityExpect(str_contains($script, 'data-user-availability-url') && str_contains($script, 'data-user-availability-host="1"'), 'Month and day controls reload the profile availability fragment.');
userAvailabilityExpect(str_contains($styles, '.omo-user-context__availability-calendar') && str_contains($styles, '[data-state="partial"]'), 'Availability states have a dedicated calendar presentation.');

echo "user_popup_availability_test: OK\n";
