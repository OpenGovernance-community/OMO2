<?php
require_once dirname(__DIR__) . '/external_calendar.php';
require_once dirname(__DIR__) . '/user_availability.php';
require_once __DIR__ . '/availability-grid.php';

function commonCalendarAvailabilityPreviewSourceLang(): array
{
    return commonCalendarAvailabilityGridSourceLang() + [
    'calendar.create.preview.heading' => ['text' => 'Disponibilités communes', 'context' => 'Heading of the combined invitee calendar.'],
    'calendar.create.preview.people_count' => ['one' => '{count} personne prise en compte', 'other' => '{count} personnes prises en compte', 'context' => 'Count of OMO people whose calendars are included in the combined preview.'],
    'calendar.create.preview.organizer' => ['text' => 'Organisateur', 'context' => 'Role of the event owner in the people list above combined availability.'],
    'calendar.create.preview.member' => ['text' => 'Membre', 'context' => 'Fallback label when a selected member has no display name.'],
    'calendar.create.preview.filter_hint' => ['text' => 'Cochez les personnes à prendre en compte pour rechercher une date. Cela ne modifie pas les invitations.', 'context' => 'Local participant filters in the availability preview.'],
    'calendar.create.preview.busy_names' => ['text' => 'Occupé : {names}', 'context' => 'Tooltip listing busy people in a half-hour slot.'],
    'calendar.create.preview.day_availability' => ['text' => '{free} / {total} créneaux libres en commun', 'context' => 'Daily count of common free half-hours, excluding breaks and outside working hours.'],
    'calendar.create.preview.occupation_scale' => ['text' => 'Plus disponible → Moins disponible', 'context' => 'Yellow-to-red gradient legend for common daily availability.'],
    'calendar.create.preview.selected_count' => ['text' => '{selected} / {total} personnes prises en compte', 'context' => 'Number of checked people in the availability preview.'],
    'calendar.create.preview.busy_count' => ['text' => '{busy} / {total} occupés', 'context' => 'Number of busy people in a half-hour slot.'],
    'calendar.create.preview.no_people' => ['text' => 'Cochez au moins une personne pour afficher les disponibilités.', 'context' => 'Empty availability preview after excluding every person.'],
    'calendar.create.preview.hint' => ['text' => 'Créneaux communs aux invités et à l’organisateur, selon leurs agendas OMO et externes.', 'context' => 'Explanation of the combined availability preview.'],
    'calendar.create.preview.email_warning' => ['text' => 'Les invitations par e-mail ne peuvent pas être vérifiées.', 'context' => 'Caution when the preview includes guests without an OMO account.'],
    'calendar.create.preview.cache_warning' => ['text' => 'Certains agendas externes ne sont pas à jour ou ne couvrent pas cette période.', 'context' => 'Caution when external calendar cache is incomplete.'],
    'calendar.create.preview.error' => ['text' => 'Impossible de calculer les disponibilités pour le moment.', 'context' => 'Combined availability preview failure.'],
    'calendar.create.preview.loading' => ['text' => 'Calcul des disponibilités…', 'context' => 'Loading state of the combined availability tab.'],
    'calendar.create.preview.select_day' => ['text' => 'Choisissez un jour', 'context' => 'Prompt to choose a day in the invitee availability preview.'],
    'calendar.create.preview.select_day_hint' => ['text' => 'Sélectionnez une date pour afficher les créneaux communs.', 'context' => 'Explanation before a day is selected.'],
    'calendar.create.preview.no_hours' => ['text' => 'Aucun créneau commun pour cette journée.', 'context' => 'Combined availability when participants have no overlapping working hours.'],
    'calendar.create.preview.free' => ['text' => 'Libre', 'context' => 'All participants are free.'],
    'calendar.create.preview.partial' => ['text' => 'Partiellement occupé', 'context' => 'Some common slots are occupied.'],
    'calendar.create.preview.full' => ['text' => 'Occupé', 'context' => 'All common slots are occupied.'],
    'calendar.create.preview.closed' => ['text' => 'Indisponible', 'context' => 'No shared working hours.'],
    'calendar.create.preview.busy' => ['text' => 'Occupé', 'context' => 'A half-hour slot is occupied.'],
    'calendar.create.preview.pause' => ['text' => 'Pause', 'context' => 'A participant has a configured break.'],
    'calendar.create.preview.previous_month' => ['text' => 'Mois précédent', 'context' => 'Navigate combined availability calendar backward.'],
    'calendar.create.preview.next_month' => ['text' => 'Mois suivant', 'context' => 'Navigate combined availability calendar forward.'],
    'calendar.create.preview.select_slot' => ['text' => 'Sélectionner ce créneau', 'context' => 'Accessible label for a free half-hour slot used to set event times.'],
    'calendar.create.preview.selection_hint' => ['text' => 'Un clic sélectionne la durée de l’événement par créneaux de 30 minutes, vers l’avant ou vers l’arrière si nécessaire. Maj + clic permet d’ajuster la plage.', 'context' => 'Instructions for selecting event start and end from shared availability.'],
    'calendar.create.preview.range_blocked' => ['text' => 'Aucune plage libre consécutive ne permet cette sélection sans traverser une pause ou un créneau occupé.', 'context' => 'Invalid duration or shift-click range in shared availability.'],
    'calendar.create.preview.range_selected' => ['text' => 'Début et fin de l’événement mis à jour.', 'context' => 'Confirmation after choosing a time range from shared availability.'],
    ];
}

function commonCalendarRenderInviteeAvailability(\dbObject\Event $previewEvent, array $targets, callable $translate, int $excludedEventId = 0, bool $compactHelp = false): void
{
    $organizationId = (int)$previewEvent->get('IDorganization');
    $zone = new DateTimeZone('Europe/Zurich');
    $today = new DateTimeImmutable('today', $zone);
    $monthValue = trim((string)($_POST['month'] ?? $today->format('Y-m')));
    $month = DateTimeImmutable::createFromFormat('!Y-m-d', $monthValue . '-01', $zone);
    if (!$month || $month->format('Y-m') !== $monthValue) {
        $month = $today->modify('first day of this month');
    }
    $selectedValue = trim((string)($_POST['date'] ?? ''));
    $selectedDay = $selectedValue !== '' ? DateTimeImmutable::createFromFormat('!Y-m-d', $selectedValue, $zone) : null;
    if (!$selectedDay || $selectedDay->format('Y-m-d') !== $selectedValue || $selectedDay->format('Y-m') !== $month->format('Y-m')) {
        $selectedDay = null;
    }

    $userIds = $targets['userIds'];
    $rangeStart = $month->setTime(0, 0);
    $rangeEnd = $month->modify('+1 month')->setTime(0, 0);
    $participants = [];
    $participantNames = [];
    $participantData = [];
    $incomplete = false;
    $refreshDeadline = microtime(true) + 18;
    try {
        foreach ($userIds as $userId) {
            $participantUser = new \dbObject\User();
            $participantNames[$userId] = $participantUser->load((int)$userId)
                ? trim((string)$participantUser->getScopedDisplayName($organizationId))
                : '';
            if ($participantNames[$userId] === '') {
                $participantNames[$userId] = $translate('calendar.create.preview.member');
            }
            commonExternalCalendarRefreshForAvailability((int)$userId, $refreshDeadline);
            $hours = \dbObject\MeetingProfile::isStorageAvailable()
                ? \dbObject\MeetingProfile::forUser((int)$userId)->availabilityHours()
                : \dbObject\MeetingProfile::defaultHours();
            $calendarIncomplete = false;
            $busy = commonUserAvailabilityLoadBusyIntervals((int)$userId, $rangeStart, $rangeEnd, $calendarIncomplete, $excludedEventId);
            $incomplete = $incomplete || $calendarIncomplete;
            $participants[] = ['hours' => $hours, 'busy' => $busy];
            $personDays = [];
            for ($personDay = $rangeStart; $personDay < $rangeEnd; $personDay = $personDay->modify('+1 day')) {
                $personDays[$personDay->format('Y-m-d')] = commonUserAvailabilityEncodeDay(commonUserAvailabilityBuildDay($personDay, $hours, $busy));
            }
            $participantData[] = ['id' => (string)$userId, 'name' => $participantNames[$userId], 'days' => $personDays, 'incomplete' => $calendarIncomplete];
        }
    } catch (Throwable $exception) {
        error_log('Combined calendar availability preview failed: ' . get_class($exception));
        http_response_code(503);
        echo '<div class="omo-calendar-create__preview-feedback is-error">' . omoApiEscape($translate('calendar.create.preview.error')) . '</div>';
        exit;
    }

    $days = [];
    $dateLabels = [];
    for ($day = $rangeStart; $day < $rangeEnd; $day = $day->modify('+1 day')) {
        $days[$day->format('Y-m-d')] = commonUserAvailabilityBuildCombinedDay($day, $participants);
        $dateLabels[$day->format('Y-m-d')] = commonUserAvailabilityFormatDate($day, true);
    }
    $labelKeys = ['heading', 'previous_month', 'next_month', 'free', 'partial', 'full', 'closed', 'select_day', 'select_day_hint', 'no_hours', 'pause', 'busy', 'select_slot', 'selection_hint', 'range_blocked', 'range_selected', 'selected_count', 'busy_count', 'busy_names', 'no_people', 'day_availability', 'occupation_scale'];
    $labels = [];
    foreach ($labelKeys as $key) {
        $labels[$key] = $translate('calendar.create.preview.' . $key);
    }
    $labels['available'] = $labels['free'];
    $labels['weekdays'] = array_map($translate, array_keys(commonCalendarAvailabilityGridSourceLang()));
    if ($compactHelp) { $labels['selection_hint'] = ''; }
    ?>
    <div class="omo-calendar-create__preview-people" aria-label="<?= omoApiEscape($translate('calendar.create.preview.people_count', ['count' => (string)count($userIds)])) ?>">
        <div class="generic-heading-with-help">
            <strong data-omo-calendar-preview-people-count><?= omoApiEscape($translate('calendar.create.preview.people_count', ['count' => (string)count($userIds)])) ?></strong>
            <details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>
                <summary aria-label="<?= omoApiEscape($translate('calendar.create.preview.filter_hint')) ?>">?</summary>
                <div class="generic-context-help__content"><?= omoApiEscape($translate('calendar.create.preview.filter_hint')) ?><?php if ($compactHelp): ?><p><?= omoApiEscape($translate('calendar.create.preview.hint')) ?></p><?php endif; ?></div>
            </details>
        </div>
        <ul>
            <?php foreach ($participantNames as $userId => $name): ?>
                <li><label><input type="checkbox" data-omo-calendar-preview-person="<?= (int)$userId ?>" checked> <?= omoApiEscape($name) ?><?php if ((int)$userId === (int)$previewEvent->get('IDuser')): ?> <span><?= omoApiEscape($translate('calendar.create.preview.organizer')) ?></span><?php endif; ?></label></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
    if ($targets['emails']) {
        echo '<p class="omo-calendar-create__preview-warning">' . omoApiEscape($translate('calendar.create.preview.email_warning')) . '</p>';
    }
    echo '<p class="omo-calendar-create__preview-warning" data-omo-calendar-preview-cache-warning' . ($incomplete ? '' : ' hidden') . '>' . omoApiEscape($translate('calendar.create.preview.cache_warning')) . '</p>';
    $makeControlValue = static function (DateTimeImmutable $targetMonth, ?DateTimeImmutable $targetDay = null): string {
        return 'month=' . rawurlencode($targetMonth->format('Y-m'))
            . ($targetDay ? '&date=' . rawurlencode($targetDay->format('Y-m-d')) : '');
    };
    commonCalendarRenderAvailabilityGrid($month, $selectedDay, $days, $labels, 'data-omo-calendar-preview-target', $makeControlValue, '', true);
    ?>
    <?php if (!$compactHelp): ?><p class="generic-description generic-description--small"><?= omoApiEscape($translate('calendar.create.preview.hint')) ?></p><?php endif; ?>
    <script type="application/json" data-omo-calendar-preview-data><?= json_encode([
        'month' => $month->format('Y-m'), 'people' => $participantData, 'dates' => $dateLabels, 'labels' => $labels,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?></script>
    <?php
}
