<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/user_availability.php';

use dbObject\{DbObject, Organization, MeetingProfile, User, UserOrganization};

function calendarOrganizationExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $organization = new Organization();
    $organization->set('name', 'Calendar hours fixture');
    $organization->set('shortname', 'calendar-hours-' . bin2hex(random_bytes(5)));
    calendarOrganizationExpect($organization->getCalendarSettings() === ['dayStart' => '09:00', 'dayEnd' => '17:00'], 'Existing organizations have bounded defaults.');
    $organization->setParametersArray(['unrelated' => ['preserved' => true]]);
    $organization->setCalendarSettings(['dayStart' => '08:00', 'dayEnd' => '18:00']);
    calendarOrganizationExpect(!empty($organization->save()['status']), 'Organization hours save through dbObject.');
    $reloaded = new Organization();
    calendarOrganizationExpect($reloaded->load($organization->getId(), true), 'Reload saved organization.');
    calendarOrganizationExpect($reloaded->getCalendarSettings() === ['dayStart' => '08:00', 'dayEnd' => '18:00'], 'Hours survive a database reload.');
    calendarOrganizationExpect(!$reloaded->canReadField('parameters'), 'Background checks expose calendar defaults without exposing private parameters.');
    $user = new User();
    $user->set('firstname', 'Calendar hours fixture');
    $user->set('email', 'calendar-hours-' . bin2hex(random_bytes(5)) . '@example.invalid');
    calendarOrganizationExpect(!empty($user->save()['status']), 'Create test member.');
    $membership = new UserOrganization();
    $membership->loadFromArray(['IDuser' => $user->getId(), 'IDorganization' => $organization->getId(), 'active' => 1]);
    calendarOrganizationExpect(!empty($membership->save()['status']), 'Create test membership.');
    $_SESSION['currentUser'] = $user->getId();
    $reloaded->load($organization->getId(), true);
    calendarOrganizationExpect($reloaded->getParametersArray()['unrelated']['preserved'] === true, 'Other organization parameters survive.');
    calendarOrganizationExpect((new Organization())->getCalendarSettings() === Organization::DEFAULT_CALENDAR_SETTINGS, 'Defaults do not leak between organizations.');

    foreach ([['18:00', '08:00'], ['09:00', '09:00'], ['24:00', '24:00'], ['08:15', '18:00'], ['08:00', '25:00'], [[], '18:00'], ['8:00', '18:00'], ['08:00', "18:00\n"]] as [$start, $end]) {
        $rejected = false;
        try { $reloaded->setCalendarSettings(['dayStart' => $start, 'dayEnd' => $end]); }
        catch (InvalidArgumentException $exception) { $rejected = true; }
        calendarOrganizationExpect($rejected, 'Malformed, inverted or unaligned hours must be rejected.');
        calendarOrganizationExpect($reloaded->getCalendarSettings()['dayStart'] === '08:00', 'Rejected changes preserve settings.');
    }

    $profile = new MeetingProfile();
    $profile->set('enabled', 0);
    $hours = $profile->availabilityHours($reloaded);
    calendarOrganizationExpect(count($hours) === 7 && $hours[7]['open'] && !$hours[1]['pause'], 'Fallback keeps all days open without introducing a lunch break.');
    $day = new DateTimeImmutable('2030-01-07', new DateTimeZone('Europe/Zurich'));
    $busy = [[$day->setTime(7, 0), $day->setTime(19, 0)]];
    $full = commonUserAvailabilityBuildDay($day, $hours, $busy);
    calendarOrganizationExpect($full['state'] === 'full' && $full['workingCount'] === 20 && $full['busySlotCount'] === 20, 'A fully busy working day has no false night availability.');
    calendarOrganizationExpect(commonUserAvailabilityEncodeDay($full) === str_repeat('0', 16) . str_repeat('2', 20) . str_repeat('0', 12), 'Only organization working hours enter the encoded view.');
    $partial = commonUserAvailabilityBuildDay($day, $hours, [[$day->setTime(8, 0), $day->setTime(13, 0)]]);
    calendarOrganizationExpect($partial['workingCount'] === 20 && $partial['busySlotCount'] === 10, 'The occupation ratio uses the configured window.');
    $combined = commonUserAvailabilityBuildCombinedDay($day, [['hours' => $hours, 'busy' => $busy], ['hours' => $hours, 'busy' => []]]);
    calendarOrganizationExpect($combined['state'] === 'full' && $combined['workingCount'] === 20, 'Shared availability uses the same fallback and denominator.');

    $personal = MeetingProfile::defaultHours();
    $personal[1] = array_replace($personal[1], ['start' => '10:00', 'end' => '16:00', 'pause' => true]);
    $profile->set('weekly_hours', json_encode($personal));
    $profile->set('enabled', 1);
    calendarOrganizationExpect($profile->availabilityHours($reloaded) === $personal, 'Active personal hours, days and pauses override organization defaults.');
    $profile->set('enabled', 0);
    calendarOrganizationExpect($profile->availabilityHours($reloaded) === $hours, 'Disabled personal booking uses organization defaults.');
    $reloaded->setCalendarSettings(['dayStart' => '23:00', 'dayEnd' => '24:00']);
    $late = commonUserAvailabilityBuildDay($day, $profile->availabilityHours($reloaded), []);
    calendarOrganizationExpect(count($late['slots']) === 2 && commonUserAvailabilityEncodeDay($late) === str_repeat('0', 46) . '11', 'Midnight can be the exclusive end without spilling into the next day.');
    $reloaded->setParametersArray(['calendar' => ['dayStart' => 'bad']]);
    calendarOrganizationExpect($reloaded->getCalendarSettings() === Organization::DEFAULT_CALENDAR_SETTINGS, 'Malformed stored settings fall back safely.');
    echo "calendar_organization_hours_test: OK\n";
} finally {
    $pdo->rollBack();
}
