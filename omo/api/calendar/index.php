<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\ArrayEvent;
use dbObject\Event;
use dbObject\Holon;
use dbObject\Organization;

$sourceLang = [
    'calendar.page.title' => [
        'text' => 'Calendrier',
        'context' => 'Main title of the calendar application.',
    ],
    'calendar.page.description' => [
        'text' => 'Visualisez les événements de votre organisation et ajoutez-en de nouveaux.',
        'context' => 'Introductory text shown in the calendar application.',
    ],
    'calendar.scope.contextual' => [
        'text' => 'Contextuel',
        'context' => 'Label used to show only events from the current context.',
    ],
    'calendar.scope.children' => [
        'text' => 'Enfants directs',
        'context' => 'Label used to show events from the current holon and its direct children.',
    ],
    'calendar.scope.descendants' => [
        'text' => 'Descendants',
        'context' => 'Label used to show events from the current holon and its descendants.',
    ],
    'calendar.action.add' => [
        'text' => 'Ajouter un événement',
        'context' => 'Primary button used to open the event creation drawer.',
    ],
    'calendar.action.today' => [
        'text' => "Aujourd'hui",
        'context' => 'Button used to return to the current month in the calendar application.',
    ],
    'calendar.action.edit' => [
        'text' => 'Editer',
        'context' => 'Action shown in the compact event menu for events the current user can edit.',
    ],
    'calendar.action.delete' => [
        'text' => 'Supprimer',
        'context' => 'Action shown in the compact event menu for events the current user can delete.',
    ],
    'calendar.action.more' => [
        'text' => 'Actions',
        'context' => 'Accessible label for the compact event action menu.',
    ],
    'calendar.confirm.delete' => [
        'text' => 'Supprimer cet evenement ?',
        'context' => 'Confirmation shown before deleting an event from the compact menu.',
    ],
    'calendar.error.delete' => [
        'text' => 'Impossible de supprimer cet evenement.',
        'context' => 'Fallback error shown when deleting an event from the compact menu fails.',
    ],
    'calendar.delete.documents.title' => [
        'text' => 'Documents associes',
        'context' => 'Title of the choice dialog shown before deleting documents linked to an event.',
    ],
    'calendar.delete.documents.question' => [
        'text' => 'Voulez-vous supprimer les documents associes ?',
        'context' => 'Question shown before deleting documents linked to an event.',
    ],
    'calendar.delete.documents.yes' => [
        'text' => 'Oui',
        'context' => 'Choice that deletes documents linked to the event.',
    ],
    'calendar.delete.documents.no' => [
        'text' => 'Non',
        'context' => 'Choice that keeps documents linked to the event.',
    ],
    'calendar.view.month' => [
        'text' => 'Mois',
        'context' => 'Label used for the monthly calendar view switch.',
    ],
    'calendar.view.week' => [
        'text' => 'Semaine',
        'context' => 'Label used for the weekly calendar view switch.',
    ],
    'calendar.view.day' => [
        'text' => 'Jour',
        'context' => 'Label used for the daily calendar view switch.',
    ],
    'calendar.view.list' => [
        'text' => 'Liste',
        'context' => 'Label used for the upcoming list view switch.',
    ],
    'calendar.axis.all_day' => [
        'text' => 'Journée',
        'context' => 'Label used for the all-day row in week and day views.',
    ],
    'calendar.drawer.title' => [
        'text' => 'Événement',
        'context' => 'Title of the internal drawer used to inspect or edit an event from the calendar application.',
    ],
    'calendar.drawer.description' => [
        'text' => 'Consultez les détails puis modifiez si besoin.',
        'context' => 'Description shown in the internal event drawer.',
    ],
    'calendar.empty.month' => [
        'text' => 'Aucun événement sur cette période.',
        'context' => 'Empty state shown when the current month contains no event.',
    ],
    'calendar.empty.week' => [
        'text' => 'Aucun événement sur cette semaine.',
        'context' => 'Empty state shown when the current week contains no event.',
    ],
    'calendar.empty.day' => [
        'text' => 'Aucun événement sur cette journée.',
        'context' => 'Empty state shown when the current day contains no event.',
    ],
    'calendar.empty.list' => [
        'text' => 'Aucun événement à venir.',
        'context' => 'Empty state shown when no upcoming event is available.',
    ],
    'calendar.summary.month' => [
        'text' => '{count} événement(s) ce mois',
        'context' => 'Summary badge for the monthly calendar view.',
    ],
    'calendar.summary.week' => [
        'text' => '{count} événement(s) cette semaine',
        'context' => 'Summary badge for the weekly calendar view.',
    ],
    'calendar.summary.day' => [
        'text' => '{count} événement(s) ce jour',
        'context' => 'Summary badge for the daily calendar view.',
    ],
    'calendar.summary.list' => [
        'text' => '{count} événement(s) à venir',
        'context' => 'Summary badge for the upcoming list view.',
    ],
    'calendar.list.column.event' => [
        'text' => 'Événement',
        'context' => 'Header label for the event title column in the upcoming list view.',
    ],
    'calendar.list.column.schedule' => [
        'text' => 'Horaire',
        'context' => 'Header label for the schedule column in the upcoming list view.',
    ],
    'calendar.list.column.context' => [
        'text' => 'Contexte',
        'context' => 'Header label for the context column in the upcoming list view.',
    ],
    'calendar.list.column.date' => [
        'text' => 'Date',
        'context' => 'Header label for the date column in the upcoming list view.',
    ],
    'calendar.context.organization' => [
        'text' => 'Organisation',
        'context' => 'Fallback context label when the organization root is displayed in the calendar application.',
    ],
    'calendar.day.more' => [
        'one' => '+{count} autre',
        'other' => '+{count} autres',
        'context' => 'Label shown inside a day cell when additional events are hidden.',
    ],
    'calendar.loading' => [
        'text' => 'Chargement...',
        'context' => 'Loading label shown while fetching the event creation form.',
    ],
    'calendar.error.load_form' => [
        'text' => 'Impossible de charger ce contenu.',
        'context' => 'Error shown inside the drawer when the event detail or form could not be loaded.',
    ],
    'calendar.section.today' => [
        'text' => "Aujourd'hui",
        'context' => 'Upcoming events section for events happening today.',
    ],
    'calendar.section.tomorrow' => [
        'text' => 'Demain',
        'context' => 'Upcoming events section for events happening tomorrow.',
    ],
    'calendar.section.this_week' => [
        'text' => 'Cette semaine',
        'context' => 'Upcoming events section for events happening later this week.',
    ],
    'calendar.section.next_week' => [
        'text' => 'La semaine prochaine',
        'context' => 'Upcoming events section for events happening next week.',
    ],
    'calendar.section.this_month' => [
        'text' => 'Ce mois',
        'context' => 'Upcoming events section for events happening later this month.',
    ],
    'calendar.section.next_month' => [
        'text' => 'Le mois prochain',
        'context' => 'Upcoming events section for events happening next month.',
    ],
    'calendar.day.mon' => [
        'text' => 'Lun',
        'context' => 'Short weekday label in the monthly calendar view.',
    ],
    'calendar.day.tue' => [
        'text' => 'Mar',
        'context' => 'Short weekday label in the monthly calendar view.',
    ],
    'calendar.day.wed' => [
        'text' => 'Mer',
        'context' => 'Short weekday label in the monthly calendar view.',
    ],
    'calendar.day.thu' => [
        'text' => 'Jeu',
        'context' => 'Short weekday label in the monthly calendar view.',
    ],
    'calendar.day.fri' => [
        'text' => 'Ven',
        'context' => 'Short weekday label in the monthly calendar view.',
    ],
    'calendar.day.sat' => [
        'text' => 'Sam',
        'context' => 'Short weekday label in the monthly calendar view.',
    ],
    'calendar.day.sun' => [
        'text' => 'Dim',
        'context' => 'Short weekday label in the monthly calendar view.',
    ],
];

$lang = omoLoadTranslationBundle('omo_calendar_index', $sourceLang);

function omoCalendarT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

function omoCalendarParseMonth($rawValue)
{
    $rawValue = trim((string)$rawValue);
    if (!preg_match('/^\d{4}-\d{2}$/', $rawValue)) {
        return null;
    }

    $month = \DateTimeImmutable::createFromFormat('!Y-m', $rawValue);
    return $month instanceof \DateTimeImmutable ? $month : null;
}

function omoCalendarParseView($rawValue)
{
    $view = strtolower(trim((string)$rawValue));
    if ($view === 'calendar') {
        return 'month';
    }

    return in_array($view, ['month', 'week', 'day', 'list'], true) ? $view : 'month';
}

function omoCalendarParseScope($rawValue, array $allowedScopes = ['contextual', 'children', 'descendants'])
{
    return omoApiNormalizeContextScope($rawValue, $allowedScopes);
}

function omoCalendarParseDate($rawValue)
{
    $rawValue = trim((string)$rawValue);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawValue)) {
        return null;
    }

    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $rawValue);
    return $date instanceof \DateTimeImmutable ? $date : null;
}

function omoCalendarBuildUrl($organizationId, $holonId, \DateTimeInterface $month, $view = 'month', $date = null, $scope = 'contextual')
{
    $query = [
        'oid=' . rawurlencode((string)(int)$organizationId),
        'month=' . rawurlencode($month->format('Y-m')),
        'view=' . rawurlencode(omoCalendarParseView($view)),
        'scope=' . rawurlencode(omoCalendarParseScope($scope)),
    ];

    if ((int)$holonId > 0) {
        $query[] = 'cid=' . rawurlencode((string)(int)$holonId);
    }

    if ($date instanceof \DateTimeInterface) {
        $query[] = 'date=' . rawurlencode($date->format('Y-m-d'));
    }

    return '/omo/api/calendar/index.php?' . implode('&', $query);
}

function omoCalendarGetMonthNames()
{
    return [
        1 => 'janvier',
        2 => 'fevrier',
        3 => 'mars',
        4 => 'avril',
        5 => 'mai',
        6 => 'juin',
        7 => 'juillet',
        8 => 'aout',
        9 => 'septembre',
        10 => 'octobre',
        11 => 'novembre',
        12 => 'decembre',
    ];
}

function omoCalendarGetWeekdayNames()
{
    return [
        1 => 'Lun',
        2 => 'Mar',
        3 => 'Mer',
        4 => 'Jeu',
        5 => 'Ven',
        6 => 'Sam',
        7 => 'Dim',
    ];
}

function omoCalendarFormatMonthLabel(\DateTimeInterface $month)
{
    $monthNames = omoCalendarGetMonthNames();
    return ucfirst((string)($monthNames[(int)$month->format('n')] ?? $month->format('F'))) . ' ' . $month->format('Y');
}

function omoCalendarFormatDayMonthLabel(\DateTimeInterface $date)
{
    $monthNames = omoCalendarGetMonthNames();
    return $date->format('j') . ' ' . (string)($monthNames[(int)$date->format('n')] ?? $date->format('F'));
}

function omoCalendarFormatWeekdayLabel(\DateTimeInterface $date)
{
    $weekdayNames = omoCalendarGetWeekdayNames();
    return (string)($weekdayNames[(int)$date->format('N')] ?? $date->format('D'));
}

function omoCalendarFormatDayLabel(\DateTimeInterface $date)
{
    return omoCalendarFormatWeekdayLabel($date) . ' ' . omoCalendarFormatDayMonthLabel($date);
}

function omoCalendarFormatDayLabelWithYear(\DateTimeInterface $date)
{
    return omoCalendarFormatDayLabel($date) . ' ' . $date->format('Y');
}

function omoCalendarFormatWeekRangeLabel(\DateTimeInterface $weekStart, \DateTimeInterface $weekEnd)
{
    $sameMonth = $weekStart->format('Y-m') === $weekEnd->format('Y-m');
    $sameYear = $weekStart->format('Y') === $weekEnd->format('Y');

    if ($sameMonth) {
        return 'Semaine du ' . $weekStart->format('j') . ' au ' . $weekEnd->format('j') . ' ' . omoCalendarFormatMonthLabel($weekStart);
    }

    if ($sameYear) {
        return 'Semaine du ' . omoCalendarFormatDayMonthLabel($weekStart) . ' au ' . omoCalendarFormatDayMonthLabel($weekEnd) . ' ' . $weekStart->format('Y');
    }

    return 'Semaine du ' . omoCalendarFormatDayLabelWithYear($weekStart) . ' au ' . omoCalendarFormatDayLabelWithYear($weekEnd);
}

function omoCalendarFormatTimeLabel(Event $event, \DateTimeInterface $day)
{
    $startAt = $event->get('start_at');
    $endAt = $event->get('end_at');
    if (!($startAt instanceof \DateTimeInterface) || !($endAt instanceof \DateTimeInterface)) {
        return '';
    }

    if ((bool)$event->get('is_all_day')) {
        return 'Journee';
    }

    $dayKey = $day->format('Y-m-d');
    $startKey = $startAt->format('Y-m-d');
    $endKey = $endAt->format('Y-m-d');

    if ($startKey === $dayKey && $endKey === $dayKey) {
        return $startAt->format('H:i') . ' - ' . $endAt->format('H:i');
    }

    if ($startKey === $dayKey) {
        return $startAt->format('H:i') . ' ->';
    }

    if ($endKey === $dayKey) {
        return '<- ' . $endAt->format('H:i');
    }

    return 'En cours';
}

function omoCalendarFormatUpcomingRangeLabel(Event $event)
{
    $startAt = $event->get('start_at');
    $endAt = $event->get('end_at');
    if (!($startAt instanceof \DateTimeInterface) || !($endAt instanceof \DateTimeInterface)) {
        return '';
    }

    if ((bool)$event->get('is_all_day')) {
        if ($startAt->format('Y-m-d') === $endAt->format('Y-m-d')) {
            return 'Journee';
        }

        return omoCalendarFormatDayMonthLabel($startAt) . ' -> ' . omoCalendarFormatDayMonthLabel($endAt);
    }

    if ($startAt->format('Y-m-d') === $endAt->format('Y-m-d')) {
        return $startAt->format('H:i') . ' - ' . $endAt->format('H:i');
    }

    return omoCalendarFormatDayMonthLabel($startAt) . ' ' . $startAt->format('H:i')
        . ' -> '
        . omoCalendarFormatDayMonthLabel($endAt) . ' ' . $endAt->format('H:i');
}

function omoCalendarResolveUpcomingSection(\DateTimeImmutable $anchorDate, \DateTimeImmutable $todayStart)
{
    $tomorrowStart = $todayStart->modify('+1 day');
    $dayAfterTomorrow = $todayStart->modify('+2 days');
    $weekEnd = $todayStart->modify('sunday this week')->setTime(23, 59, 59);
    $nextWeekStart = $todayStart->modify('monday next week')->setTime(0, 0, 0);
    $nextWeekEnd = $nextWeekStart->modify('sunday this week')->setTime(23, 59, 59);
    $thisMonthEnd = $todayStart->modify('last day of this month')->setTime(23, 59, 59);
    $nextMonthStart = $todayStart->modify('first day of next month')->setTime(0, 0, 0);
    $nextMonthEnd = $nextMonthStart->modify('last day of this month')->setTime(23, 59, 59);

    if ($anchorDate < $tomorrowStart) {
        return [
            'key' => 'today',
            'label' => omoCalendarT('calendar.section.today'),
            'sort' => 10,
        ];
    }

    if ($anchorDate < $dayAfterTomorrow) {
        return [
            'key' => 'tomorrow',
            'label' => omoCalendarT('calendar.section.tomorrow'),
            'sort' => 20,
        ];
    }

    if ($anchorDate <= $weekEnd) {
        return [
            'key' => 'this_week',
            'label' => omoCalendarT('calendar.section.this_week'),
            'sort' => 30,
        ];
    }

    if ($anchorDate >= $nextWeekStart && $anchorDate <= $nextWeekEnd) {
        return [
            'key' => 'next_week',
            'label' => omoCalendarT('calendar.section.next_week'),
            'sort' => 40,
        ];
    }

    if ($anchorDate <= $thisMonthEnd) {
        return [
            'key' => 'this_month',
            'label' => omoCalendarT('calendar.section.this_month'),
            'sort' => 50,
        ];
    }

    if ($anchorDate >= $nextMonthStart && $anchorDate <= $nextMonthEnd) {
        return [
            'key' => 'next_month',
            'label' => omoCalendarT('calendar.section.next_month'),
            'sort' => 60,
        ];
    }

    $monthStart = $anchorDate->modify('first day of this month')->setTime(0, 0, 0);

    return [
        'key' => 'month_' . $monthStart->format('Y_m'),
        'label' => omoCalendarFormatMonthLabel($monthStart),
        'sort' => 100000 + (int)$monthStart->format('U'),
    ];
}

function omoCalendarAssignTimelineColumns(array $segments)
{
    if (count($segments) === 0) {
        return [];
    }

    usort($segments, static function (array $left, array $right) {
        $leftStart = (int)($left['startMinute'] ?? 0);
        $rightStart = (int)($right['startMinute'] ?? 0);
        if ($leftStart !== $rightStart) {
            return $leftStart <=> $rightStart;
        }

        $leftEnd = (int)($left['endMinute'] ?? 0);
        $rightEnd = (int)($right['endMinute'] ?? 0);
        if ($leftEnd !== $rightEnd) {
            return $leftEnd <=> $rightEnd;
        }

        return (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
    });

    $clusters = [];
    $currentCluster = [];
    $currentClusterEnd = null;

    foreach ($segments as $segment) {
        $segmentStart = (int)($segment['startMinute'] ?? 0);
        $segmentEnd = (int)($segment['endMinute'] ?? 0);

        if (count($currentCluster) === 0) {
            $currentCluster = [$segment];
            $currentClusterEnd = $segmentEnd;
            continue;
        }

        if ($segmentStart < (int)$currentClusterEnd) {
            $currentCluster[] = $segment;
            $currentClusterEnd = max((int)$currentClusterEnd, $segmentEnd);
            continue;
        }

        $clusters[] = $currentCluster;
        $currentCluster = [$segment];
        $currentClusterEnd = $segmentEnd;
    }

    if (count($currentCluster) > 0) {
        $clusters[] = $currentCluster;
    }

    $enriched = [];

    foreach ($clusters as $cluster) {
        $active = [];
        $maxColumns = 0;

        foreach ($cluster as $index => $segment) {
            $segmentStart = (int)($segment['startMinute'] ?? 0);

            foreach ($active as $activeIndex => $activeSegment) {
                if ((int)($activeSegment['endMinute'] ?? 0) <= $segmentStart) {
                    unset($active[$activeIndex]);
                }
            }

            $usedColumns = [];
            foreach ($active as $activeSegment) {
                $usedColumns[(int)($activeSegment['column'] ?? 0)] = true;
            }

            $column = 0;
            while (isset($usedColumns[$column])) {
                $column += 1;
            }

            $cluster[$index]['column'] = $column;
            $active[] = [
                'endMinute' => (int)($segment['endMinute'] ?? 0),
                'column' => $column,
            ];
            $maxColumns = max($maxColumns, $column + 1);
        }

        foreach ($cluster as $segment) {
            $segment['columnCount'] = max(1, $maxColumns);
            $enriched[] = $segment;
        }
    }

    return $enriched;
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$openEventId = isset($_GET['open_event_id']) && is_numeric($_GET['open_event_id']) ? (int)$_GET['open_event_id'] : 0;
$viewMode = omoCalendarParseView($_GET['view'] ?? '');
$requestedScopeRaw = trim((string)($_GET['scope'] ?? ''));
$calendarScope = 'contextual';
$requestedMonth = omoCalendarParseMonth($_GET['month'] ?? '');
$requestedDate = omoCalendarParseDate($_GET['date'] ?? '');
$anchorDate = $requestedDate ?: ($requestedMonth ?: new \DateTimeImmutable('today 00:00:00'));
$anchorDate = $anchorDate->setTime(0, 0, 0);
$monthStart = ($requestedDate ?: ($requestedMonth ?: $anchorDate))->modify('first day of this month')->setTime(0, 0, 0);

if ($organizationId <= 0) {
    http_response_code(400);
    ?>
    <div class="omo-calendar omo-empty-state">Organisation invalide.</div>
    <?php
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-calendar omo-empty-state">Organisation introuvable.</div>
    <?php
    exit;
}

if (!$organization->canViewDetail()) {
    http_response_code(403);
    ?>
    <div class="omo-calendar omo-empty-state">Acces refuse a cette organisation.</div>
    <?php
    exit;
}

$rootHolon = $organization->getEnabledStructuralRootHolon();
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$openedEvent = null;
$openedEventHolonId = 0;
$requestedViewRaw = trim((string)($_GET['view'] ?? ''));

if ($openEventId > 0) {
    $candidateEvent = new Event();
    if (
        $candidateEvent->load($openEventId)
        && (int)$candidateEvent->get('IDorganization') === $organizationId
        && (int)$candidateEvent->get('active') === 1
        && Event::normalizeStatus($candidateEvent->get('status')) !== Event::STATUS_CANCELLED
    ) {
        $candidateHolonId = (int)$candidateEvent->get('IDholon');
        $canUseCandidateEvent = true;

        if ($candidateHolonId > 0) {
            $candidateHolon = new Holon();
            if (
                !$candidateHolon->load($candidateHolonId)
                || !($rootHolon instanceof Holon)
                || !$candidateHolon->isDescendantOf((int)$rootHolon->getId(), true)
                || !$candidateHolon->canViewDetail()
            ) {
                $canUseCandidateEvent = false;
            }
        }

        if ($canUseCandidateEvent) {
            $openedEvent = $candidateEvent;
            $openedEventHolonId = $candidateHolonId;

            $eventStartAt = $candidateEvent->get('start_at');
            if ($eventStartAt instanceof \DateTimeInterface && $requestedDate === null && $requestedMonth === null) {
                $anchorDate = \DateTimeImmutable::createFromInterface($eventStartAt)->setTime(0, 0, 0);
                $monthStart = $anchorDate->modify('first day of this month')->setTime(0, 0, 0);
            }

            if ($requestedViewRaw === '') {
                $viewMode = 'day';
            }

        }
    }
}

$currentHolon = null;
$canToggleScope = false;

if ($currentHolonId > 0) {
    $candidateHolon = new Holon();
    if (
        !$candidateHolon->load($currentHolonId)
        || !($rootHolon instanceof Holon)
        || !$candidateHolon->isDescendantOf((int)$rootHolon->getId(), true)
        || !$candidateHolon->canViewDetail()
    ) {
        http_response_code(404);
        ?>
        <div class="omo-calendar omo-empty-state">Holon introuvable pour cette organisation.</div>
        <?php
        exit;
    }

    $currentHolon = $candidateHolon;
}

if (!($currentHolon instanceof Holon) && $rootHolon instanceof Holon) {
    $currentHolon = $rootHolon;
    $currentHolonId = (int)$rootHolon->getId();
}

$canToggleScope = $organization->getId() > 0 && $rootHolon instanceof Holon;
$calendarScopes = omoApiGetAvailableContextScopes($canToggleScope, $currentHolon, $rootHolon);
$calendarScope = omoCalendarParseScope($requestedScopeRaw, $calendarScopes);
$calendarScopeActiveIndex = omoApiResolveContextScopeIndex($calendarScope, $calendarScopes);
$descendantHolonIdMap = omoApiGetDescendantHolonIdMap($currentHolon);
$directChildHolonIdMap = omoApiGetDirectChildHolonIdMap($currentHolon);

if (
    $requestedScopeRaw === ''
    && $openedEventHolonId > 0
    && ($currentHolonId <= 0 || $currentHolonId !== $openedEventHolonId)
) {
    if (
        isset($directChildHolonIdMap[$openedEventHolonId])
        && in_array('children', $calendarScopes, true)
    ) {
        $calendarScope = 'children';
    } elseif (
        $currentHolon instanceof Holon
        && ($openedEventHolonId === (int)$currentHolon->getId() || isset($descendantHolonIdMap[$openedEventHolonId]))
        && in_array('descendants', $calendarScopes, true)
    ) {
        $calendarScope = 'descendants';
    }
}

$createPermissionHolon = $currentHolon instanceof Holon ? $currentHolon : $rootHolon;
$canCreateEvent = $currentUserId > 0
    && (
        $createPermissionHolon instanceof Holon
            ? $createPermissionHolon->isAllowed('CAN_CREATE_EVENT')
            : commonCurrentUserHasOrganizationAccess($organizationId)
    );

$monthEnd = $monthStart->modify('last day of this month')->setTime(23, 59, 59);
$gridStart = $monthStart->modify('monday this week')->setTime(0, 0, 0);
$gridEnd = $monthEnd->modify('sunday this week')->setTime(23, 59, 59);
$weekStart = $anchorDate->modify('monday this week')->setTime(0, 0, 0);
$weekEnd = $weekStart->modify('+6 days')->setTime(23, 59, 59);
$dayStart = $anchorDate->setTime(0, 0, 0);
$dayEnd = $dayStart->modify('+1 day')->modify('-1 second');
$todayStart = new \DateTimeImmutable('today 00:00:00');
$todayDayKey = $todayStart->format('Y-m-d');

$events = new ArrayEvent();
$events->loadForCalendarContext($organizationId, 0, false);
$openEventTargetId = $openedEvent instanceof Event ? (int)$openedEvent->getId() : 0;

$holonLabelsById = [];
$dayBucketsByScope = [];
$viewCountsByScope = [];
$upcomingSectionsByScope = [];
$upcomingSectionLayersByScope = [];
$eventStatusCatalog = Event::getStatusCatalog();
$timelineViewsByScope = [];
$timelineHours = [];

for ($hourIndex = 0; $hourIndex < 24; $hourIndex += 1) {
    $timelineHours[] = sprintf('%02d:00', $hourIndex);
}

$resolveHolonLabel = static function ($eventHolonId) use (&$holonLabelsById) {
    $eventHolonId = (int)$eventHolonId;
    if ($eventHolonId <= 0) {
        return '';
    }

    if (!isset($holonLabelsById[$eventHolonId])) {
        $eventHolon = new Holon();
        $holonLabelsById[$eventHolonId] = $eventHolon->load($eventHolonId)
            ? trim((string)$eventHolon->getDisplayName())
            : '';
    }

    return (string)$holonLabelsById[$eventHolonId];
};

$eventAudienceMatchByHolonId = [];
$eventPersonallyRelevantForViewer = static function (Event $event) use ($organizationId, $currentUserId, &$eventAudienceMatchByHolonId) {
    if ($currentUserId <= 0) {
        return true;
    }

    if ((int)$event->get('IDorganization') !== $organizationId) {
        return false;
    }

    $eventHolonId = (int)$event->get('IDholon');
    if ($eventHolonId <= 0) {
        return true;
    }

    if (!array_key_exists($eventHolonId, $eventAudienceMatchByHolonId)) {
        $eventAudienceMatchByHolonId[$eventHolonId] = false;

        $eventHolon = new Holon();
        if (
            $eventHolon->load($eventHolonId)
            && (bool)$eventHolon->get('active')
            && (bool)$eventHolon->get('visible')
        ) {
            $eventAudienceMatchByHolonId[$eventHolonId] = in_array(
                $currentUserId,
                $eventHolon->getAssociatedMemberUserIds([
                    'organizationId' => $organizationId,
                    'skipPermissionFilter' => true,
                ]),
                true
            );
        }
    }

    return $eventAudienceMatchByHolonId[$eventHolonId];
};

$buildTimelineDays = static function (\DateTimeImmutable $rangeStart, int $dayCount) use ($todayDayKey) {
    $days = [];
    $cursor = $rangeStart;
    for ($index = 0; $index < $dayCount; $index += 1) {
        $dayKey = $cursor->format('Y-m-d');
        $days[$dayKey] = [
            'date' => $cursor,
            'dayKey' => $dayKey,
            'label' => omoCalendarFormatDayLabel($cursor),
            'fullLabel' => omoCalendarFormatDayLabelWithYear($cursor),
            'isToday' => $dayKey === $todayDayKey,
            'allDay' => [],
            'timed' => [],
        ];
        $cursor = $cursor->modify('+1 day');
    }

    return $days;
};

foreach ($calendarScopes as $scopeKey) {
    $dayBucketsByScope[$scopeKey] = [];
    $viewCountsByScope[$scopeKey] = [
        'month' => 0,
        'week' => 0,
        'day' => 0,
        'list' => 0,
    ];
    $upcomingSectionsByScope[$scopeKey] = [];
    $upcomingSectionLayersByScope[$scopeKey] = [];
    $timelineViewsByScope[$scopeKey] = [
        'week' => $buildTimelineDays($weekStart, 7),
        'day' => $buildTimelineDays($dayStart, 1),
    ];
}

foreach ($events as $event) {
    if (!($event instanceof Event) || (int)$event->getId() <= 0) {
        continue;
    }

    $startAt = $event->get('start_at');
    $endAt = $event->get('end_at');
    if (!($startAt instanceof \DateTimeInterface) || !($endAt instanceof \DateTimeInterface)) {
        continue;
    }

    $eventId = (int)$event->getId();
    $eventTitle = trim((string)$event->get('title')) !== '' ? trim((string)$event->get('title')) : ('Evenement #' . $eventId);
    $eventDescription = trim((string)$event->get('description'));
    $eventStatus = Event::normalizeStatus($event->get('status'));
    $eventStatusLabel = trim((string)($eventStatusCatalog[$eventStatus]['label'] ?? ''));
    $eventHolonId = (int)$event->get('IDholon');
    $eventHolonLabel = $resolveHolonLabel($eventHolonId);
    $isAllDay = (bool)$event->get('is_all_day');
    $isInCurrentContext = !$canToggleScope || $eventHolonId === 0 || $eventHolonId === $currentHolonId;
    $isInDirectChildContext = $isInCurrentContext || ($eventHolonId > 0 && isset($directChildHolonIdMap[$eventHolonId]));
    $isInDescendantContext = $isInCurrentContext || ($eventHolonId > 0 && isset($descendantHolonIdMap[$eventHolonId]));
    $isPersonallyRelevant = $eventPersonallyRelevantForViewer($event);
    $canEditEvent = $currentUserId > 0 && (int)$event->get('IDuser') === $currentUserId;
    $deletePermissionHolon = $rootHolon;
    if ($eventHolonId > 0) {
        $eventPermissionHolon = new Holon();
        if ($eventPermissionHolon->load($eventHolonId)) {
            $deletePermissionHolon = $eventPermissionHolon;
        }
    }
    $canDeleteEvent = $currentUserId > 0
        && (
            $deletePermissionHolon instanceof Holon
                ? $deletePermissionHolon->isAllowed('CAN_DELETE_EVENT', false, $currentUserId)
                : commonCurrentUserHasOrganizationAccess($organizationId)
        );
    $eventHasAssociatedDocuments = $canDeleteEvent && count($event->getAssociatedDocuments()) > 0;
    $eventEditUrl = '/omo/api/calendar/create.php?oid=' . rawurlencode((string)$organizationId)
        . '&id=' . rawurlencode((string)$eventId);
    $eventDeleteUrl = '/omo/api/calendar/delete.php?oid=' . rawurlencode((string)$organizationId)
        . '&id=' . rawurlencode((string)$eventId);
    if ($currentHolonId > 0) {
        $eventEditUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
    }

    foreach ($calendarScopes as $scopeKey) {
        $includeEvent = $scopeKey === 'children'
            ? $isInDirectChildContext
            : ($scopeKey === 'descendants' ? $isInDescendantContext : $isInCurrentContext);
        if (!$includeEvent) {
            continue;
        }

        $isFadedInScope = !$isPersonallyRelevant;

        if ($startAt <= $monthEnd && $endAt >= $monthStart) {
            $viewCountsByScope[$scopeKey]['month'] += 1;
        }

        $cursor = new \DateTimeImmutable(max($startAt->format('Y-m-d 00:00:00'), $gridStart->format('Y-m-d 00:00:00')));
        $cursorEnd = new \DateTimeImmutable(min($endAt->format('Y-m-d 00:00:00'), $gridEnd->format('Y-m-d 00:00:00')));

        while ($cursor <= $cursorEnd) {
            $dayKey = $cursor->format('Y-m-d');
            if (!isset($dayBucketsByScope[$scopeKey][$dayKey])) {
                $dayBucketsByScope[$scopeKey][$dayKey] = [];
            }

            $dayBucketsByScope[$scopeKey][$dayKey][] = [
                'id' => $eventId,
                'title' => $eventTitle,
                'timeLabel' => omoCalendarFormatTimeLabel($event, $cursor),
                'status' => $eventStatus,
                'holonLabel' => $eventHolonLabel,
                'isFaded' => $isFadedInScope,
                'isRouteTarget' => $openEventTargetId > 0 && $eventId === $openEventTargetId,
            ];

            $cursor = $cursor->modify('+1 day');
        }

        if ($endAt >= $todayStart) {
            $upcomingAnchorDate = \DateTimeImmutable::createFromInterface($startAt);
            if ($upcomingAnchorDate < $todayStart && $endAt >= $todayStart) {
                $upcomingAnchorDate = $todayStart;
            }

            $sectionDefinition = omoCalendarResolveUpcomingSection($upcomingAnchorDate, $todayStart);
            $sectionKey = (string)$sectionDefinition['key'];

            if (!isset($upcomingSectionsByScope[$scopeKey][$sectionKey])) {
                $upcomingSectionsByScope[$scopeKey][$sectionKey] = [
                    'key' => $sectionKey,
                    'label' => (string)$sectionDefinition['label'],
                    'sort' => (int)$sectionDefinition['sort'],
                    'items' => [],
                ];
            }

            $upcomingSectionsByScope[$scopeKey][$sectionKey]['items'][] = [
                'id' => $eventId,
                'title' => $eventTitle,
                'description' => $eventDescription,
                'weekdayLabel' => omoCalendarFormatWeekdayLabel($upcomingAnchorDate),
                'dateLabel' => omoCalendarFormatDayMonthLabel($upcomingAnchorDate),
                'timeLabel' => omoCalendarFormatUpcomingRangeLabel($event),
                'status' => $eventStatus,
                'statusLabel' => $eventStatusLabel,
                'holonLabel' => $eventHolonLabel,
                'canEdit' => $canEditEvent,
                'editUrl' => $eventEditUrl,
                'canDelete' => $canDeleteEvent,
                'deleteUrl' => $eventDeleteUrl,
                'hasAssociatedDocuments' => $eventHasAssociatedDocuments,
                'sort' => (int)$startAt->format('U'),
                'isFaded' => $isFadedInScope,
                'isRouteTarget' => $openEventTargetId > 0 && $eventId === $openEventTargetId,
            ];

            $viewCountsByScope[$scopeKey]['list'] += 1;
        }

        if ($startAt <= $weekEnd && $endAt >= $weekStart) {
            $viewCountsByScope[$scopeKey]['week'] += 1;
        }

        if ($startAt <= $dayEnd && $endAt >= $dayStart) {
            $viewCountsByScope[$scopeKey]['day'] += 1;
        }

        foreach ($timelineViewsByScope[$scopeKey]['week'] as $dayKey => &$timelineDay) {
            if ($startAt > $weekEnd || $endAt < $weekStart) {
                break;
            }

            $timelineDate = $timelineDay['date'];
            $timelineDayStart = $timelineDate->setTime(0, 0, 0);
            $timelineDayEndExclusive = $timelineDayStart->modify('+1 day');
            $segmentStartTimestamp = max($startAt->getTimestamp(), $timelineDayStart->getTimestamp());
            $segmentEndTimestamp = min($endAt->getTimestamp(), $timelineDayEndExclusive->getTimestamp());

            if ($segmentEndTimestamp <= $segmentStartTimestamp) {
                continue;
            }

            if ($isAllDay) {
                $timelineDay['allDay'][] = [
                    'id' => $eventId,
                    'title' => $eventTitle,
                    'timeLabel' => omoCalendarFormatTimeLabel($event, $timelineDate),
                    'status' => $eventStatus,
                    'holonLabel' => $eventHolonLabel,
                    'isFaded' => $isFadedInScope,
                    'isRouteTarget' => $openEventTargetId > 0 && $eventId === $openEventTargetId,
                ];
                continue;
            }

            $startMinute = max(0, (int)floor(($segmentStartTimestamp - $timelineDayStart->getTimestamp()) / 60));
            $endMinute = min(1440, (int)ceil(($segmentEndTimestamp - $timelineDayStart->getTimestamp()) / 60));
            $displayEndMinute = max($startMinute + 30, $endMinute);

            $timelineDay['timed'][] = [
                'id' => $eventId,
                'title' => $eventTitle,
                'description' => $eventDescription,
                'timeLabel' => omoCalendarFormatTimeLabel($event, $timelineDate),
                'status' => $eventStatus,
                'statusLabel' => $eventStatusLabel,
                'holonLabel' => $eventHolonLabel,
                'startMinute' => $startMinute,
                'endMinute' => min(1440, $displayEndMinute),
                'isFaded' => $isFadedInScope,
                'isRouteTarget' => $openEventTargetId > 0 && $eventId === $openEventTargetId,
            ];
        }
        unset($timelineDay);

        foreach ($timelineViewsByScope[$scopeKey]['day'] as $dayKey => &$timelineDay) {
            if ($startAt > $dayEnd || $endAt < $dayStart) {
                break;
            }

            $timelineDate = $timelineDay['date'];
            $timelineDayStart = $timelineDate->setTime(0, 0, 0);
            $timelineDayEndExclusive = $timelineDayStart->modify('+1 day');
            $segmentStartTimestamp = max($startAt->getTimestamp(), $timelineDayStart->getTimestamp());
            $segmentEndTimestamp = min($endAt->getTimestamp(), $timelineDayEndExclusive->getTimestamp());

            if ($segmentEndTimestamp <= $segmentStartTimestamp) {
                continue;
            }

            if ($isAllDay) {
                $timelineDay['allDay'][] = [
                    'id' => $eventId,
                    'title' => $eventTitle,
                    'timeLabel' => omoCalendarFormatTimeLabel($event, $timelineDate),
                    'status' => $eventStatus,
                    'holonLabel' => $eventHolonLabel,
                    'isFaded' => $isFadedInScope,
                    'isRouteTarget' => $openEventTargetId > 0 && $eventId === $openEventTargetId,
                ];
                continue;
            }

            $startMinute = max(0, (int)floor(($segmentStartTimestamp - $timelineDayStart->getTimestamp()) / 60));
            $endMinute = min(1440, (int)ceil(($segmentEndTimestamp - $timelineDayStart->getTimestamp()) / 60));
            $displayEndMinute = max($startMinute + 30, $endMinute);

            $timelineDay['timed'][] = [
                'id' => $eventId,
                'title' => $eventTitle,
                'description' => $eventDescription,
                'timeLabel' => omoCalendarFormatTimeLabel($event, $timelineDate),
                'status' => $eventStatus,
                'statusLabel' => $eventStatusLabel,
                'holonLabel' => $eventHolonLabel,
                'startMinute' => $startMinute,
                'endMinute' => min(1440, $displayEndMinute),
                'isFaded' => $isFadedInScope,
                'isRouteTarget' => $openEventTargetId > 0 && $eventId === $openEventTargetId,
            ];
        }
        unset($timelineDay);
    }
}

foreach ($calendarScopes as $scopeKey) {
    ksort($dayBucketsByScope[$scopeKey]);

    if (count($upcomingSectionsByScope[$scopeKey]) > 0) {
        foreach ($upcomingSectionsByScope[$scopeKey] as &$section) {
            usort($section['items'], static function (array $left, array $right) {
                $leftSort = (int)($left['sort'] ?? 0);
                $rightSort = (int)($right['sort'] ?? 0);
                if ($leftSort !== $rightSort) {
                    return $leftSort <=> $rightSort;
                }

                return (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
            });
        }
        unset($section);

        uasort($upcomingSectionsByScope[$scopeKey], static function (array $left, array $right) {
            return (int)($left['sort'] ?? 0) <=> (int)($right['sort'] ?? 0);
        });

        $sectionIndex = 0;
        $sectionCount = count($upcomingSectionsByScope[$scopeKey]);
        foreach ($upcomingSectionsByScope[$scopeKey] as $sectionKey => $section) {
            $layerBase = max(0, ($sectionCount - $sectionIndex) * 10);
            $upcomingSectionLayersByScope[$scopeKey][$sectionKey] = [
                'title' => $layerBase + 3,
                'list' => $layerBase + 2,
                'folder' => $layerBase + 1,
            ];
            $sectionIndex += 1;
        }
    }

    foreach ($timelineViewsByScope[$scopeKey]['week'] as &$timelineDay) {
        usort($timelineDay['allDay'], static function (array $left, array $right) {
            $leftFaded = !empty($left['isFaded']) ? 1 : 0;
            $rightFaded = !empty($right['isFaded']) ? 1 : 0;
            if ($leftFaded !== $rightFaded) {
                return $leftFaded <=> $rightFaded;
            }
            return (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
        });
        $timelineDay['timed'] = omoCalendarAssignTimelineColumns($timelineDay['timed']);
    }
    unset($timelineDay);

    foreach ($timelineViewsByScope[$scopeKey]['day'] as &$timelineDay) {
        usort($timelineDay['allDay'], static function (array $left, array $right) {
            $leftFaded = !empty($left['isFaded']) ? 1 : 0;
            $rightFaded = !empty($right['isFaded']) ? 1 : 0;
            if ($leftFaded !== $rightFaded) {
                return $leftFaded <=> $rightFaded;
            }
            return (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
        });
        $timelineDay['timed'] = omoCalendarAssignTimelineColumns($timelineDay['timed']);
    }
    unset($timelineDay);
}

$prevMonth = $monthStart->modify('-1 month');
$nextMonth = $monthStart->modify('+1 month');
$todayMonth = new \DateTimeImmutable('first day of this month 00:00:00');
$prevWeekDate = $weekStart->modify('-7 days');
$nextWeekDate = $weekStart->modify('+7 days');
$prevDayDate = $dayStart->modify('-1 day');
$nextDayDate = $dayStart->modify('+1 day');
$todayDate = new \DateTimeImmutable('today 00:00:00');
$contextLabel = $currentHolon instanceof Holon
    ? trim((string)$currentHolon->getDisplayName())
    : trim((string)$organization->get('name'));

$weekdayKeys = [
    'calendar.day.mon',
    'calendar.day.tue',
    'calendar.day.wed',
    'calendar.day.thu',
    'calendar.day.fri',
    'calendar.day.sat',
    'calendar.day.sun',
];

$days = [];
$cursor = $gridStart;
while ($cursor <= $gridEnd) {
    $days[] = $cursor;
    $cursor = $cursor->modify('+1 day');
}

$viewUrlsByScope = [];
foreach ($calendarScopes as $scopeKey) {
    $viewUrlsByScope[$scopeKey] = [
        'month' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $monthStart, 'month', $anchorDate, $scopeKey),
        'week' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $anchorDate->modify('first day of this month'), 'week', $anchorDate, $scopeKey),
        'day' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $anchorDate->modify('first day of this month'), 'day', $anchorDate, $scopeKey),
        'list' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $anchorDate->modify('first day of this month'), 'list', $anchorDate, $scopeKey),
    ];
}

$currentUrl = $viewUrlsByScope[$calendarScope][$viewMode] ?? $viewUrlsByScope['contextual']['month'];
$createUrl = '/omo/api/calendar/create.php?oid=' . rawurlencode((string)$organizationId);
$detailUrl = '/omo/api/calendar/detail.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolon instanceof Holon) {
    $createUrl .= '&cid=' . rawurlencode((string)(int)$currentHolon->getId());
    $detailUrl .= '&cid=' . rawurlencode((string)(int)$currentHolon->getId());
}

$viewSummariesByScope = [];
$timelineViewsByScopeConfig = [];
foreach ($calendarScopes as $scopeKey) {
    $scopeCounts = $viewCountsByScope[$scopeKey];
    $viewSummariesByScope[$scopeKey] = [
        'month' => $scopeCounts['month'] > 0 ? omoCalendarT('calendar.summary.month', ['count' => (string)$scopeCounts['month']]) : omoCalendarT('calendar.empty.month'),
        'week' => $scopeCounts['week'] > 0 ? omoCalendarT('calendar.summary.week', ['count' => (string)$scopeCounts['week']]) : omoCalendarT('calendar.empty.week'),
        'day' => $scopeCounts['day'] > 0 ? omoCalendarT('calendar.summary.day', ['count' => (string)$scopeCounts['day']]) : omoCalendarT('calendar.empty.day'),
        'list' => $scopeCounts['list'] > 0 ? omoCalendarT('calendar.summary.list', ['count' => (string)$scopeCounts['list']]) : omoCalendarT('calendar.empty.list'),
    ];

    $timelineViewsByScopeConfig[$scopeKey] = [
        'week' => [
            'title' => omoCalendarFormatWeekRangeLabel($weekStart, $weekEnd),
            'subtitle' => (string)$viewSummariesByScope[$scopeKey]['week'],
            'days' => $timelineViewsByScope[$scopeKey]['week'],
            'columnCount' => count($timelineViewsByScope[$scopeKey]['week']),
            'prevUrl' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $prevWeekDate->modify('first day of this month'), 'week', $prevWeekDate, $scopeKey),
            'nextUrl' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $nextWeekDate->modify('first day of this month'), 'week', $nextWeekDate, $scopeKey),
            'todayUrl' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $todayDate->modify('first day of this month'), 'week', $todayDate, $scopeKey),
        ],
        'day' => [
            'title' => omoCalendarFormatDayLabelWithYear($dayStart),
            'subtitle' => (string)$viewSummariesByScope[$scopeKey]['day'],
            'days' => $timelineViewsByScope[$scopeKey]['day'],
            'columnCount' => count($timelineViewsByScope[$scopeKey]['day']),
            'prevUrl' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $prevDayDate->modify('first day of this month'), 'day', $prevDayDate, $scopeKey),
            'nextUrl' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $nextDayDate->modify('first day of this month'), 'day', $nextDayDate, $scopeKey),
            'todayUrl' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $todayDate->modify('first day of this month'), 'day', $todayDate, $scopeKey),
        ],
    ];
}

$headerCount = (int)($viewCountsByScope[$calendarScope][$viewMode] ?? 0);
$headerSummary = (string)($viewSummariesByScope[$calendarScope][$viewMode] ?? '');
?>
<div
    class="omo-calendar omo-panel-view"
    id="omo-calendar-root"
    data-omo-calendar-current-url="<?= omoApiEscape($currentUrl) ?>"
    data-omo-calendar-create-url="<?= omoApiEscape($createUrl) ?>"
    data-omo-calendar-detail-url="<?= omoApiEscape($detailUrl) ?>"
    data-omo-calendar-can-create="<?= $canCreateEvent ? '1' : '0' ?>"
    data-omo-calendar-month="<?= omoApiEscape($monthStart->format('Y-m')) ?>"
    data-omo-calendar-view="<?= omoApiEscape($viewMode) ?>"
    data-omo-calendar-scope="<?= omoApiEscape($calendarScope) ?>"
    data-omo-calendar-open-event-id="<?= (int)$openEventTargetId ?>"
>
    <div class="omo-calendar__header omo-panel-view__header">
        <div class="omo-calendar__header-main">
            <div class="omo-calendar__title-cluster">
                <span class="omo-calendar__app-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <rect x="3.5" y="4.5" width="17" height="16" rx="4"></rect>
                        <path d="M7.5 2.75v3.5M16.5 2.75v3.5M3.5 9.25h17"></path>
                    </svg>
                </span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-calendar__title-row">
                        <h2 class="omo-panel-view__title"><?= omoApiEscape(omoCalendarT('calendar.page.title')) ?></h2>
                        <span class="omo-calendar__count omo-panel-view__count" data-omo-calendar-header-count><?= omoApiEscape((string)$headerCount) ?></span>
                    </div>
                </div>
            </div>
            <div class="omo-calendar__header-actions">
                <div class="omo-calendar__today-actions">
                    <button
                        type="button"
                        class="generic-action-button generic-action-button--secondary<?= $viewMode === 'month' ? '' : ' is-hidden' ?>"
                        data-omo-calendar-today-button="month"
                        <?php foreach ($calendarScopes as $scopeKey): ?>
                            data-omo-calendar-nav-url-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape(omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $todayMonth, 'month', $todayDate, $scopeKey)) ?>"
                        <?php endforeach; ?>
                    >
                        <?= omoApiEscape(omoCalendarT('calendar.action.today')) ?>
                    </button>
                    <button
                        type="button"
                        class="generic-action-button generic-action-button--secondary<?= $viewMode === 'week' ? '' : ' is-hidden' ?>"
                        data-omo-calendar-today-button="week"
                        <?php foreach ($calendarScopes as $scopeKey): ?>
                            data-omo-calendar-nav-url-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape($timelineViewsByScopeConfig[$scopeKey]['week']['todayUrl']) ?>"
                        <?php endforeach; ?>
                    >
                        <?= omoApiEscape(omoCalendarT('calendar.action.today')) ?>
                    </button>
                    <button
                        type="button"
                        class="generic-action-button generic-action-button--secondary<?= $viewMode === 'day' ? '' : ' is-hidden' ?>"
                        data-omo-calendar-today-button="day"
                        <?php foreach ($calendarScopes as $scopeKey): ?>
                            data-omo-calendar-nav-url-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape($timelineViewsByScopeConfig[$scopeKey]['day']['todayUrl']) ?>"
                        <?php endforeach; ?>
                    >
                        <?= omoApiEscape(omoCalendarT('calendar.action.today')) ?>
                    </button>
                </div>
                <?php if ($canCreateEvent): ?>
                    <button
                        type="button"
                        class="generic-action-button generic-action-button--main omo-calendar__new-button omo-mobile-corner-action"
                        aria-label="<?= omoApiEscape(omoCalendarT('calendar.action.add')) ?>"
                        data-omo-calendar-open-create
                    >
                        <span class="omo-mobile-corner-action__text"><?= omoApiEscape(omoCalendarT('calendar.action.add')) ?></span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="omo-calendar__header-secondary">
            <div class="omo-calendar__scope-slot">
                <?php if ($canToggleScope): ?>
                <div
                    class="omo-scope-toggle"
                    role="tablist"
                    aria-label="Portee des evenements"
                    data-omo-scope-switch="<?= omoApiEscape($calendarScope) ?>"
                    style="--omo-scope-option-count: <?= (int)count($calendarScopes) ?>; --omo-scope-active-index: <?= (int)$calendarScopeActiveIndex ?>;"
                >
                        <?php foreach ($calendarScopes as $scopeIndex => $scopeKey): ?>
                            <button
                                type="button"
                                class="omo-scope-toggle__button<?= $calendarScope === $scopeKey ? ' is-active' : '' ?>"
                                aria-label="<?= omoApiEscape(omoCalendarT('calendar.scope.' . $scopeKey)) ?>"
                                data-omo-calendar-scope-toggle="<?= omoApiEscape($scopeKey) ?>"
                                data-omo-scope-option="<?= omoApiEscape($scopeKey) ?>"
                                data-omo-scope-index="<?= (int)$scopeIndex ?>"
                                aria-pressed="<?= $calendarScope === $scopeKey ? 'true' : 'false' ?>"
                            ><span class="omo-scope-toggle__text"><?= omoApiEscape(omoCalendarT('calendar.scope.' . $scopeKey)) ?></span></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="omo-segmented omo-calendar__view-switch" role="group" aria-label="Affichage du calendrier">
                        <button
                            type="button"
                            class="omo-segmented__button<?= $viewMode === 'month' ? ' is-active' : '' ?>"
                            aria-label="<?= omoApiEscape(omoCalendarT('calendar.view.month')) ?>"
                            data-omo-calendar-set-view="month"
                            data-omo-segmented-option="calendar-month"
                            <?php foreach ($calendarScopes as $scopeKey): ?>
                                data-omo-calendar-view-url-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape($viewUrlsByScope[$scopeKey]['month']) ?>"
                                data-omo-calendar-view-count-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape((string)$viewCountsByScope[$scopeKey]['month']) ?>"
                                data-omo-calendar-view-summary-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape($viewSummariesByScope[$scopeKey]['month']) ?>"
                            <?php endforeach; ?>
                            aria-pressed="<?= $viewMode === 'month' ? 'true' : 'false' ?>"
                        ><span class="omo-segmented__text"><?= omoApiEscape(omoCalendarT('calendar.view.month')) ?></span></button>
                        <button
                            type="button"
                            class="omo-segmented__button<?= $viewMode === 'week' ? ' is-active' : '' ?>"
                            aria-label="<?= omoApiEscape(omoCalendarT('calendar.view.week')) ?>"
                            data-omo-calendar-set-view="week"
                            data-omo-segmented-option="calendar-week"
                            <?php foreach ($calendarScopes as $scopeKey): ?>
                                data-omo-calendar-view-url-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape($viewUrlsByScope[$scopeKey]['week']) ?>"
                                data-omo-calendar-view-count-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape((string)$viewCountsByScope[$scopeKey]['week']) ?>"
                                data-omo-calendar-view-summary-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape($viewSummariesByScope[$scopeKey]['week']) ?>"
                            <?php endforeach; ?>
                            aria-pressed="<?= $viewMode === 'week' ? 'true' : 'false' ?>"
                        ><span class="omo-segmented__text"><?= omoApiEscape(omoCalendarT('calendar.view.week')) ?></span></button>
                        <button
                            type="button"
                            class="omo-segmented__button<?= $viewMode === 'day' ? ' is-active' : '' ?>"
                            aria-label="<?= omoApiEscape(omoCalendarT('calendar.view.day')) ?>"
                            data-omo-calendar-set-view="day"
                            data-omo-segmented-option="calendar-day"
                            <?php foreach ($calendarScopes as $scopeKey): ?>
                                data-omo-calendar-view-url-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape($viewUrlsByScope[$scopeKey]['day']) ?>"
                                data-omo-calendar-view-count-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape((string)$viewCountsByScope[$scopeKey]['day']) ?>"
                                data-omo-calendar-view-summary-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape($viewSummariesByScope[$scopeKey]['day']) ?>"
                            <?php endforeach; ?>
                            aria-pressed="<?= $viewMode === 'day' ? 'true' : 'false' ?>"
                        ><span class="omo-segmented__text"><?= omoApiEscape(omoCalendarT('calendar.view.day')) ?></span></button>
                        <button
                            type="button"
                            class="omo-segmented__button<?= $viewMode === 'list' ? ' is-active' : '' ?>"
                            aria-label="<?= omoApiEscape(omoCalendarT('calendar.view.list')) ?>"
                            data-omo-calendar-set-view="list"
                            data-omo-segmented-option="calendar-list"
                            <?php foreach ($calendarScopes as $scopeKey): ?>
                                data-omo-calendar-view-url-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape($viewUrlsByScope[$scopeKey]['list']) ?>"
                                data-omo-calendar-view-count-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape((string)$viewCountsByScope[$scopeKey]['list']) ?>"
                                data-omo-calendar-view-summary-<?= omoApiEscape($scopeKey) ?>="<?= omoApiEscape($viewSummariesByScope[$scopeKey]['list']) ?>"
                            <?php endforeach; ?>
                            aria-pressed="<?= $viewMode === 'list' ? 'true' : 'false' ?>"
                        ><span class="omo-segmented__text"><?= omoApiEscape(omoCalendarT('calendar.view.list')) ?></span></button>
            </div>
            </div>
    </div>

    <div class="omo-panel-view__body">
        <?php foreach ($calendarScopes as $scopeKey): ?>
            <section class="omo-calendar__panel omo-calendar__panel--month omo-calendar__view-panel<?= $viewMode === 'month' && $calendarScope === $scopeKey ? ' is-active' : '' ?>" data-omo-calendar-view-panel="month" data-omo-calendar-view-scope="<?= omoApiEscape($scopeKey) ?>"<?= $viewMode === 'month' && $calendarScope === $scopeKey ? '' : ' hidden' ?>>
                <div class="omo-calendar__month-scroll">
                    <div class="omo-calendar__month-sticky">
                        <div class="omo-calendar__toolbar">
                            <button
                                type="button"
                                class="generic-action-button generic-action-button--secondary"
                                data-omo-calendar-nav-url="<?= omoApiEscape(omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $prevMonth, 'month', $prevMonth, $scopeKey)) ?>"
                            >
                                &larr;
                            </button>
                            <div class="omo-calendar__period-title">
                                <strong><?= omoApiEscape(omoCalendarFormatMonthLabel($monthStart)) ?></strong>
                                <span><?= omoApiEscape($viewSummariesByScope[$scopeKey]['month']) ?></span>
                            </div>
                            <button
                                type="button"
                                class="generic-action-button generic-action-button--secondary"
                                data-omo-calendar-nav-url="<?= omoApiEscape(omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $nextMonth, 'month', $nextMonth, $scopeKey)) ?>"
                            >
                                &rarr;
                            </button>
                        </div>

                        <div class="omo-calendar__weekday-row">
                            <?php foreach ($weekdayKeys as $weekdayKey): ?>
                                <div class="omo-calendar__weekday"><?= omoApiEscape(omoCalendarT($weekdayKey)) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="omo-calendar__grid">
                        <?php foreach ($days as $day): ?>
                    <?php
                    $dayKey = $day->format('Y-m-d');
                    $isCurrentMonth = $day->format('Y-m') === $monthStart->format('Y-m');
                    $isToday = $dayKey === $todayDayKey;
                    $items = $dayBucketsByScope[$scopeKey][$dayKey] ?? [];
                        $visibleItems = array_slice($items, 0, 3);
                        $hiddenCount = max(0, count($items) - count($visibleItems));
                        ?>
                            <div
                                class="omo-calendar__cell<?= $isCurrentMonth ? '' : ' is-outside' ?><?= $isToday ? ' is-today' : '' ?>"
                                data-omo-calendar-day="<?= omoApiEscape($dayKey) ?>"
                            >
                                <div class="omo-calendar__cell-head">
                                    <span class="omo-calendar__cell-day"><?= omoApiEscape($day->format('j')) ?></span>
                                </div>
                                <div class="omo-calendar__cell-items">
                                    <?php foreach ($visibleItems as $item): ?>
                                        <div
                                            class="omo-calendar__event-chip is-status-<?= omoApiEscape($item['status']) ?><?= !empty($item['isFaded']) ? ' is-faded' : '' ?><?= !empty($item['isRouteTarget']) ? ' is-route-target' : '' ?>"
                                            data-omo-calendar-event-id="<?= (int)$item['id'] ?>"
                                        >
                                            <?php if ($item['timeLabel'] !== ''): ?>
                                                <span class="omo-calendar__event-time"><?= omoApiEscape($item['timeLabel']) ?></span>
                                            <?php endif; ?>
                                            <span class="omo-calendar__event-title"><?= omoApiEscape($item['title']) ?></span>
                                            <?php if ($item['holonLabel'] !== ''): ?>
                                                <span class="omo-calendar__event-holon"><?= omoApiEscape($item['holonLabel']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($hiddenCount > 0): ?>
                                        <div class="omo-calendar__more"><?= omoApiEscape(omoCalendarT('calendar.day.more', ['count' => (string)$hiddenCount])) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>

        <?php foreach ($calendarScopes as $scopeKey): ?>
            <?php foreach ($timelineViewsByScopeConfig[$scopeKey] as $timelineViewKey => $timelineView): ?>
                <section class="omo-calendar__panel omo-calendar__panel--timeline omo-calendar__view-panel<?= $viewMode === $timelineViewKey && $calendarScope === $scopeKey ? ' is-active' : '' ?>" data-omo-calendar-view-panel="<?= omoApiEscape($timelineViewKey) ?>" data-omo-calendar-view-scope="<?= omoApiEscape($scopeKey) ?>" data-omo-calendar-timeline-panel="<?= omoApiEscape($timelineViewKey) ?>"<?= $viewMode === $timelineViewKey && $calendarScope === $scopeKey ? '' : ' hidden' ?>>
                    <div class="omo-calendar__toolbar">
                        <button
                            type="button"
                            class="generic-action-button generic-action-button--secondary"
                            data-omo-calendar-nav-url="<?= omoApiEscape((string)$timelineView['prevUrl']) ?>"
                        >
                            &larr;
                        </button>
                        <div class="omo-calendar__period-title">
                            <strong><?= omoApiEscape((string)$timelineView['title']) ?></strong>
                            <span><?= omoApiEscape((string)$timelineView['subtitle']) ?></span>
                        </div>
                        <button
                            type="button"
                            class="generic-action-button generic-action-button--secondary"
                            data-omo-calendar-nav-url="<?= omoApiEscape((string)$timelineView['nextUrl']) ?>"
                        >
                            &rarr;
                        </button>
                    </div>

                    <div class="omo-calendar__time-view" data-omo-calendar-time-view="<?= omoApiEscape($timelineViewKey) ?>" style="--omo-calendar-time-columns: <?= (int)($timelineView['columnCount'] ?? 1) ?>;">
                        <div class="omo-calendar__time-sticky" data-omo-calendar-time-sticky>
                            <div class="omo-calendar__time-head">
                                <div class="omo-calendar__time-axis-spacer"></div>
                                <?php foreach ($timelineView['days'] as $timelineDay): ?>
                                    <div
                                        class="omo-calendar__time-day-header<?= !empty($timelineDay['isToday']) ? ' is-today' : '' ?>"
                                        data-omo-calendar-day="<?= omoApiEscape($timelineDay['date']->format('Y-m-d')) ?>"
                                    >
                                        <strong><?= omoApiEscape((string)$timelineDay['label']) ?></strong>
                                        <span><?= omoApiEscape((string)(count($timelineDay['allDay']) + count($timelineDay['timed']))) ?> evenement(s)</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="omo-calendar__time-all-day">
                                <div class="omo-calendar__time-axis-label"><?= omoApiEscape(omoCalendarT('calendar.axis.all_day')) ?></div>
                                <?php foreach ($timelineView['days'] as $timelineDay): ?>
                                    <div
                                        class="omo-calendar__time-all-day-cell<?= !empty($timelineDay['isToday']) ? ' is-today' : '' ?>"
                                        data-omo-calendar-day="<?= omoApiEscape($timelineDay['date']->format('Y-m-d')) ?>"
                                    >
                                        <?php if (count($timelineDay['allDay']) > 0): ?>
                                            <?php foreach ($timelineDay['allDay'] as $item): ?>
                                                <div
                                                    class="omo-calendar__time-all-day-chip is-status-<?= omoApiEscape($item['status']) ?><?= !empty($item['isFaded']) ? ' is-faded' : '' ?><?= !empty($item['isRouteTarget']) ? ' is-route-target' : '' ?>"
                                                    data-omo-calendar-event-id="<?= (int)$item['id'] ?>"
                                                >
                                                    <strong><?= omoApiEscape($item['title']) ?></strong>
                                                    <?php if ($item['holonLabel'] !== ''): ?>
                                                        <span><?= omoApiEscape($item['holonLabel']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="omo-calendar__time-body">
                            <div class="omo-calendar__time-axis">
                                <?php foreach ($timelineHours as $hourIndex => $hourLabel): ?>
                                    <div class="omo-calendar__time-hour-label" data-omo-calendar-hour-index="<?= (int)$hourIndex ?>"><?= omoApiEscape($hourLabel) ?></div>
                                <?php endforeach; ?>
                            </div>

                            <?php foreach ($timelineView['days'] as $timelineDay): ?>
                                <div
                                    class="omo-calendar__time-column<?= !empty($timelineDay['isToday']) ? ' is-today' : '' ?>"
                                    data-omo-calendar-time-column-day="<?= omoApiEscape($timelineDay['date']->format('Y-m-d')) ?>"
                                >
                                    <div class="omo-calendar__time-column-grid"></div>
                                    <?php foreach ($timelineDay['timed'] as $item): ?>
                                        <?php
                                        $columnCount = max(1, (int)($item['columnCount'] ?? 1));
                                        $columnIndex = max(0, (int)($item['column'] ?? 0));
                                        $top = max(0, min(100, ((int)$item['startMinute'] / 1440) * 100));
                                        $height = max((30 / 1440) * 100, (((int)$item['endMinute'] - (int)$item['startMinute']) / 1440) * 100);
                                        $left = ($columnIndex / $columnCount) * 100;
                                        $width = 100 / $columnCount;
                                        ?>
                                        <article
                                            class="omo-calendar__time-event is-status-<?= omoApiEscape($item['status']) ?><?= !empty($item['isFaded']) ? ' is-faded' : '' ?><?= !empty($item['isRouteTarget']) ? ' is-route-target' : '' ?>"
                                            data-omo-calendar-event-id="<?= (int)$item['id'] ?>"
                                            style="top: <?= omoApiEscape(number_format($top, 4, '.', '')) ?>%; height: <?= omoApiEscape(number_format(min(100 - $top, $height), 4, '.', '')) ?>%; left: calc(<?= omoApiEscape(number_format($left, 4, '.', '')) ?>% + 4px); width: calc(<?= omoApiEscape(number_format($width, 4, '.', '')) ?>% - 8px);"
                                        >
                                            <?php if ($item['timeLabel'] !== ''): ?>
                                                <span class="omo-calendar__time-event-time"><?= omoApiEscape($item['timeLabel']) ?></span>
                                            <?php endif; ?>
                                            <strong class="omo-calendar__time-event-title"><?= omoApiEscape($item['title']) ?></strong>
                                            <?php if ($item['holonLabel'] !== ''): ?>
                                                <span class="omo-calendar__time-event-context"><?= omoApiEscape($item['holonLabel']) ?></span>
                                            <?php endif; ?>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php foreach ($calendarScopes as $scopeKey): ?>
            <div class="omo-calendar__view-panel omo-calendar__view-panel--list<?= $viewMode === 'list' && $calendarScope === $scopeKey ? ' is-active' : '' ?>" data-omo-calendar-view-panel="list" data-omo-calendar-view-scope="<?= omoApiEscape($scopeKey) ?>"<?= $viewMode === 'list' && $calendarScope === $scopeKey ? '' : ' hidden' ?>>
                <?php if (($viewCountsByScope[$scopeKey]['list'] ?? 0) === 0): ?>
                    <div class="omo-empty-state"><?= omoApiEscape(omoCalendarT('calendar.empty.list')) ?></div>
                <?php else: ?>
                    <div class="omo-calendar__results generic-file-list generic-file-list--structured generic-file-list--stacked-sticky">
                        <?php foreach ($upcomingSectionsByScope[$scopeKey] as $section): ?>
                            <?php $sectionLayers = $upcomingSectionLayersByScope[$scopeKey][(string)$section['key']] ?? ['title' => 5, 'list' => 4, 'folder' => 3]; ?>
                            <section
                                class="omo-calendar__group omo-panel-group generic-file-list__group"
                                style="--generic-file-list-group-title-z: <?= (int)$sectionLayers['title'] ?>; --generic-file-list-group-header-z: <?= (int)$sectionLayers['list'] ?>; --generic-file-list-group-folder-z: <?= (int)$sectionLayers['folder'] ?>;"
                            >
                                <h3 class="omo-panel-group__title generic-file-list__group-title"><?= omoApiEscape((string)$section['label']) ?></h3>
                                <div class="omo-calendar__list generic-file-list__table">
                                    <div class="omo-calendar__list-header generic-file-list__header">
                                        <div class="omo-calendar__list-header-cell generic-file-list__header-cell"><?= omoApiEscape(omoCalendarT('calendar.list.column.date')) ?></div>
                                        <div class="omo-calendar__list-header-cell generic-file-list__header-cell"><?= omoApiEscape(omoCalendarT('calendar.list.column.event')) ?></div>
                                        <div class="omo-calendar__list-header-cell generic-file-list__header-cell"><?= omoApiEscape(omoCalendarT('calendar.list.column.schedule')) ?></div>
                                        <div class="omo-calendar__list-header-cell generic-file-list__header-cell"><?= omoApiEscape(omoCalendarT('calendar.list.column.context')) ?></div>
                                    </div>
                                    <?php foreach ($section['items'] as $item): ?>
                                        <article class="omo-calendar__item-shell generic-file-list__item-shell is-status-<?= omoApiEscape($item['status']) ?><?= !empty($item['isFaded']) ? ' is-faded' : '' ?><?= !empty($item['isRouteTarget']) ? ' is-route-target' : '' ?>" data-omo-calendar-event-id="<?= (int)$item['id'] ?>">
                                            <div class="omo-calendar__list-item generic-file-list__row">
                                                <div class="omo-calendar__list-date generic-file-list__cell generic-file-list__cell--date" data-label="<?= omoApiEscape(omoCalendarT('calendar.list.column.date')) ?>">
                                                    <span class="omo-calendar__list-weekday"><?= omoApiEscape($item['weekdayLabel']) ?></span>
                                                    <strong><?= omoApiEscape($item['dateLabel']) ?></strong>
                                                </div>
                                                <div class="omo-calendar__list-cell omo-calendar__list-cell--name generic-file-list__cell generic-file-list__cell--name" data-label="<?= omoApiEscape(omoCalendarT('calendar.list.column.event')) ?>">
                                                    <div class="omo-calendar__list-name-main generic-file-list__name-main">
                                                        <div class="omo-calendar__list-title-block generic-file-list__title-block">
                                                            <div class="omo-calendar__list-title-row generic-file-list__title-row">
                                                                <strong class="omo-calendar__list-title generic-file-list__title"><?= omoApiEscape($item['title']) ?></strong>
                                                                <?php if ($item['statusLabel'] !== ''): ?>
                                                                    <span class="omo-calendar__list-status generic-file-list__count"><?= omoApiEscape($item['statusLabel']) ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php if ($item['description'] !== ''): ?>
                                                                <div class="omo-calendar__list-description generic-file-list__meta-line"><?= omoApiEscape($item['description']) ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="omo-calendar__list-cell generic-file-list__cell" data-label="<?= omoApiEscape(omoCalendarT('calendar.list.column.schedule')) ?>">
                                                    <?php if ($item['timeLabel'] !== ''): ?>
                                                        <span class="omo-calendar__list-time"><?= omoApiEscape($item['timeLabel']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="omo-calendar__list-cell generic-file-list__cell" data-label="<?= omoApiEscape(omoCalendarT('calendar.list.column.context')) ?>">
                                                    <div class="omo-calendar__list-context generic-file-list__meta-line">
                                                        <span class="omo-calendar__list-holon"><?= omoApiEscape($item['holonLabel'] !== '' ? $item['holonLabel'] : omoCalendarT('calendar.context.organization')) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if (!empty($item['canEdit']) || !empty($item['canDelete'])): ?>
                                                <div class="omo-calendar__event-menu generic-menu generic-file-list__menu" data-omo-calendar-event-menu>
                                                    <button type="button" class="generic-menu-toggle generic-file-list__menu-toggle" data-omo-calendar-event-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="<?= omoApiEscape(omoCalendarT('calendar.action.more')) ?>">...</button>
                                                    <div class="generic-menu-panel" data-omo-calendar-event-menu-panel role="menu" hidden>
                                                        <?php if (!empty($item['canEdit'])): ?>
                                                            <button type="button" class="generic-menu-item" data-omo-calendar-open-edit-url="<?= omoApiEscape($item['editUrl']) ?>" role="menuitem"><?= omoApiEscape(omoCalendarT('calendar.action.edit')) ?></button>
                                                        <?php endif; ?>
                                                        <?php if (!empty($item['canDelete'])): ?>
                                                            <button
                                                                type="button"
                                                                class="generic-menu-item generic-menu-item--danger"
                                                                data-omo-calendar-delete-url="<?= omoApiEscape($item['deleteUrl']) ?>"
                                                                data-omo-calendar-delete-confirm="<?= omoApiEscape(omoCalendarT('calendar.confirm.delete')) ?>"
                                                                data-omo-calendar-delete-error="<?= omoApiEscape(omoCalendarT('calendar.error.delete')) ?>"
                                                                data-omo-calendar-delete-has-documents="<?= !empty($item['hasAssociatedDocuments']) ? '1' : '0' ?>"
                                                                data-omo-calendar-delete-documents-title="<?= omoApiEscape(omoCalendarT('calendar.delete.documents.title')) ?>"
                                                                data-omo-calendar-delete-documents-question="<?= omoApiEscape(omoCalendarT('calendar.delete.documents.question')) ?>"
                                                                data-omo-calendar-delete-documents-yes="<?= omoApiEscape(omoCalendarT('calendar.delete.documents.yes')) ?>"
                                                                data-omo-calendar-delete-documents-no="<?= omoApiEscape(omoCalendarT('calendar.delete.documents.no')) ?>"
                                                                role="menuitem"
                                                            ><?= omoApiEscape(omoCalendarT('calendar.action.delete')) ?></button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="omo-overlay-drawer omo-calendar__editor-drawer" data-omo-calendar-editor-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-omo-calendar-editor-close></div>
        <div class="omo-overlay-drawer__panel">
            <div class="omo-overlay-drawer__header generic-drawer-header">
                <div class="omo-overlay-drawer__header-copy generic-drawer-header__copy">
                    <h3 class="omo-overlay-drawer__title" data-omo-calendar-editor-title><?= omoApiEscape(omoCalendarT('calendar.drawer.title')) ?></h3>
                    <p class="omo-overlay-drawer__description" data-omo-calendar-editor-description><?= omoApiEscape(omoCalendarT('calendar.drawer.description')) ?></p>
                </div>
                <div class="generic-drawer-header__actions">
                    <div class="omo-calendar__drawer-custom-actions" data-omo-calendar-editor-actions></div>
                    <button type="button" class="omo-overlay-drawer__close generic-action-button generic-action-button--secondary" data-omo-calendar-editor-close>Fermer</button>
                </div>
            </div>
            <div class="omo-overlay-drawer__body" data-omo-calendar-editor-body></div>
        </div>
    </div>
    <script>
    (function () {
        var root = document.getElementById('omo-calendar-root');
        if (!root || root.dataset.omoCalendarReady === '1') {
            return;
        }

        root.dataset.omoCalendarReady = '1';

        var drawer = root.querySelector('[data-omo-calendar-editor-drawer]');
        var drawerBody = root.querySelector('[data-omo-calendar-editor-body]');
        var drawerTitle = root.querySelector('[data-omo-calendar-editor-title]');
        var drawerDescription = root.querySelector('[data-omo-calendar-editor-description]');
        var drawerActions = root.querySelector('[data-omo-calendar-editor-actions]');
        var defaultDrawerTitle = drawerTitle ? drawerTitle.textContent : '';
        var defaultDrawerDescription = drawerDescription ? drawerDescription.textContent : '';
        var currentUrl = root.getAttribute('data-omo-calendar-current-url') || '';
        var currentView = root.getAttribute('data-omo-calendar-view') || 'month';
        function normalizeScopeName(scopeName) {
            var normalizedScope = String(scopeName || '').trim().toLowerCase();
            if (normalizedScope === 'global') {
                return 'descendants';
            }
            return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
        }

        var currentScope = normalizeScopeName(root.getAttribute('data-omo-calendar-scope') || 'contextual');
        var canCreateEvent = root.getAttribute('data-omo-calendar-can-create') === '1';
        var calendarPreferencesStorageKey = 'omoCalendarDisplayPreferences';
        var createUrl = root.getAttribute('data-omo-calendar-create-url') || '';
        var detailUrl = root.getAttribute('data-omo-calendar-detail-url') || '';
        var headerCount = root.querySelector('[data-omo-calendar-header-count]');
        var headerSummary = root.querySelector('[data-omo-calendar-header-summary]');
        var requestToken = 0;
        var initialOpenEventId = Number(root.getAttribute('data-omo-calendar-open-event-id') || '0');
        if (!Number.isInteger(initialOpenEventId) || initialOpenEventId <= 0) {
            initialOpenEventId = 0;
        }
        var initialOpenEventDrawerOpened = false;
        var calendarMenuOwnerDocument = root.ownerDocument || document;
        var floatingCalendarMenu = calendarMenuOwnerDocument.querySelector('[data-omo-calendar-floating-menu="1"]');
        if (!floatingCalendarMenu) {
            floatingCalendarMenu = calendarMenuOwnerDocument.createElement('div');
            floatingCalendarMenu.className = 'generic-menu-panel generic-menu-panel--floating';
            floatingCalendarMenu.setAttribute('data-omo-calendar-floating-menu', '1');
            floatingCalendarMenu.setAttribute('role', 'menu');
            floatingCalendarMenu.hidden = true;
            calendarMenuOwnerDocument.body.appendChild(floatingCalendarMenu);
        }
        var activeCalendarMenuToggle = null;

        function positionCalendarMenu(toggle) {
            if (!(toggle instanceof Element) || !toggle.isConnected) {
                closeCalendarMenus();
                return;
            }

            floatingCalendarMenu.hidden = false;
            floatingCalendarMenu.style.visibility = 'hidden';
            floatingCalendarMenu.style.top = '0px';
            floatingCalendarMenu.style.left = '0px';

            var toggleRect = toggle.getBoundingClientRect();
            var menuRect = floatingCalendarMenu.getBoundingClientRect();
            var viewportPadding = 12;
            var gap = 8;
            var top = toggleRect.bottom + gap;
            var left = toggleRect.right - menuRect.width;

            if (top + menuRect.height > window.innerHeight - viewportPadding) {
                top = Math.max(viewportPadding, toggleRect.top - menuRect.height - gap);
            }
            if (left + menuRect.width > window.innerWidth - viewportPadding) {
                left = Math.max(viewportPadding, window.innerWidth - menuRect.width - viewportPadding);
            }
            if (left < viewportPadding) {
                left = viewportPadding;
            }

            floatingCalendarMenu.style.top = String(Math.round(top)) + 'px';
            floatingCalendarMenu.style.left = String(Math.round(left)) + 'px';
            floatingCalendarMenu.style.visibility = '';
        }

        function closeCalendarMenus() {
            root.querySelectorAll('[data-omo-calendar-event-menu]').forEach(function (menu) {
                menu.classList.remove('is-open');
            });
            root.querySelectorAll('[data-omo-calendar-event-menu-toggle]').forEach(function (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            });
            activeCalendarMenuToggle = null;
            floatingCalendarMenu.hidden = true;
            floatingCalendarMenu.style.visibility = '';
            floatingCalendarMenu.replaceChildren();
        }

        function openCalendarMenu(toggle) {
            var menu = toggle ? toggle.closest('[data-omo-calendar-event-menu]') : null;
            var panel = menu ? menu.querySelector('[data-omo-calendar-event-menu-panel]') : null;
            var shouldOpen = !!toggle && !!panel && (activeCalendarMenuToggle !== toggle || floatingCalendarMenu.hidden);
            closeCalendarMenus();

            if (!shouldOpen) {
                return;
            }

            var fragment = calendarMenuOwnerDocument.createDocumentFragment();
            Array.prototype.forEach.call(panel.children, function (originalAction) {
                if (!(originalAction instanceof Element)) {
                    return;
                }

                var floatingAction = originalAction.cloneNode(true);
                floatingAction.setAttribute('data-omo-calendar-floating-menu-action', '1');
                fragment.appendChild(floatingAction);
            });
            floatingCalendarMenu.replaceChildren(fragment);
            if (!floatingCalendarMenu.childElementCount) {
                return;
            }

            activeCalendarMenuToggle = toggle;
            menu.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            positionCalendarMenu(toggle);
        }

        window.addEventListener('resize', function () {
            if (activeCalendarMenuToggle) {
                positionCalendarMenu(activeCalendarMenuToggle);
            }
        });
        calendarMenuOwnerDocument.addEventListener('scroll', function () {
            if (activeCalendarMenuToggle) {
                positionCalendarMenu(activeCalendarMenuToggle);
            }
        }, true);

        calendarMenuOwnerDocument.addEventListener('click', function (event) {
            var floatingAction = event.target.closest('[data-omo-calendar-floating-menu-action]');
            if (floatingAction) {
                event.preventDefault();
                event.stopPropagation();
                if (floatingAction.hasAttribute('data-omo-calendar-delete-url')) {
                    closeCalendarMenus();
                    deleteCalendarEvent(floatingAction);
                    return;
                }
                var editUrl = floatingAction.getAttribute('data-omo-calendar-open-edit-url') || '';
                closeCalendarMenus();
                if (editUrl) {
                    openDrawerWithUrl(editUrl);
                }
                return;
            }

            var toggle = event.target.closest('[data-omo-calendar-event-menu-toggle]');
            if (toggle && root.contains(toggle)) {
                event.preventDefault();
                event.stopPropagation();
                openCalendarMenu(toggle);
                return;
            }

            if (event.target.closest('[data-omo-calendar-floating-menu], [data-omo-calendar-event-menu]')) {
                return;
            }

            closeCalendarMenus();
        });

        function getCurrentRouteToken() {
            if (typeof window.omoParsePopupHashState !== 'function') {
                return '';
            }

            var hashState = window.omoParsePopupHashState();
            return hashState && hashState.routeToken
                ? String(hashState.routeToken)
                : '';
        }

        function buildEventRouteToken(eventId) {
            var resolvedEventId = Number(eventId || 0);
            if (!Number.isInteger(resolvedEventId) || resolvedEventId <= 0) {
                return null;
            }

            if (typeof window.omoBuildCalendarEventRouteToken === 'function') {
                return window.omoBuildCalendarEventRouteToken(resolvedEventId);
            }

            return 'calendar-e' + String(resolvedEventId);
        }

        function normalizeViewPreference(viewName) {
            var normalizedView = String(viewName || '').trim().toLowerCase();
            return normalizedView === 'week' || normalizedView === 'day' || normalizedView === 'list'
                ? normalizedView
                : 'month';
        }

        function readCalendarPreferences() {
            var rawValue = '';

            try {
                rawValue = window.localStorage
                    ? String(window.localStorage.getItem(calendarPreferencesStorageKey) || '')
                    : '';
            } catch (error) {
                rawValue = '';
            }

            if (rawValue === '') {
                return {
                    view: 'month'
                };
            }

            try {
                var parsed = JSON.parse(rawValue);
                return {
                    view: normalizeViewPreference(parsed && parsed.view ? parsed.view : null)
                };
            } catch (error) {
                return {
                    view: 'month'
                };
            }
        }

        function writeCalendarPreferences(preferences) {
            var normalizedPreferences = {
                view: normalizeViewPreference(preferences && preferences.view ? preferences.view : null)
            };

            try {
                if (window.localStorage) {
                    window.localStorage.setItem(
                        calendarPreferencesStorageKey,
                        JSON.stringify(normalizedPreferences)
                    );
                }
            } catch (error) {
            }
        }

        function resolveUrl(url) {
            if (!url) {
                return '';
            }

            if (typeof window.omoResolveAppUrl === 'function') {
                return window.omoResolveAppUrl(url);
            }

            return url;
        }

        function buildCreateUrl(dateValue, dateTimeValue) {
            var url = createUrl;
            if (!url) {
                return url;
            }

            if (dateTimeValue) {
                return url + (url.indexOf('?') === -1 ? '?' : '&') + 'datetime=' + encodeURIComponent(dateTimeValue);
            }

            if (!dateValue) {
                return url;
            }

            return url + (url.indexOf('?') === -1 ? '?' : '&') + 'date=' + encodeURIComponent(dateValue);
        }

        function buildDetailUrl(eventId) {
            var url = detailUrl;
            if (!url || !eventId) {
                return url;
            }

            return url + (url.indexOf('?') === -1 ? '?' : '&') + 'id=' + encodeURIComponent(String(eventId));
        }

        function buildEditUrl(eventId) {
            var url = createUrl;
            if (!url || !eventId) {
                return url;
            }

            return url + (url.indexOf('?') === -1 ? '?' : '&') + 'id=' + encodeURIComponent(String(eventId));
        }

        function resolveViewMeta(viewName, scopeName) {
            var viewButton = root.querySelector('[data-omo-calendar-set-view="' + viewName + '"]');
            var resolvedScope = normalizeScopeName(scopeName);

            if (!viewButton) {
                return {
                    url: currentUrl,
                    count: '',
                    summary: ''
                };
            }

            return {
                url: viewButton.getAttribute('data-omo-calendar-view-url-' + resolvedScope) || currentUrl,
                count: viewButton.getAttribute('data-omo-calendar-view-count-' + resolvedScope) || '',
                summary: viewButton.getAttribute('data-omo-calendar-view-summary-' + resolvedScope) || ''
            };
        }

        function parseLocalDateTime(value) {
            if (!value) {
                return null;
            }

            var parsed = new Date(value);
            return Number.isNaN(parsed.getTime()) ? null : parsed;
        }

        function formatLocalDateTimeValue(date) {
            if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
                return '';
            }

            var year = String(date.getFullYear());
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            var hours = String(date.getHours()).padStart(2, '0');
            var minutes = String(date.getMinutes()).padStart(2, '0');

            return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
        }

        function clampNumber(value, min, max) {
            return Math.min(Math.max(value, min), max);
        }

        function buildDateTimeFromColumnPosition(dayValue, clientY, columnNode) {
            if (!dayValue || !columnNode) {
                return '';
            }

            var dayDate = parseLocalDateTime(dayValue + 'T00:00');
            if (!dayDate) {
                return '';
            }

            var columnRect = columnNode.getBoundingClientRect();
            if (!columnRect || columnRect.height <= 0) {
                return '';
            }

            var offsetY = clampNumber(clientY - columnRect.top, 0, columnRect.height);
            var minuteRatio = offsetY / columnRect.height;
            var rawMinutes = Math.round(minuteRatio * 24 * 60);
            var roundedMinutes = Math.round(rawMinutes / 15) * 15;
            roundedMinutes = clampNumber(roundedMinutes, 0, (24 * 60) - 15);

            dayDate.setHours(Math.floor(roundedMinutes / 60), roundedMinutes % 60, 0, 0);
            return formatLocalDateTimeValue(dayDate);
        }

        function rememberScheduleState(form) {
            if (!form) {
                return;
            }

            var startField = form.querySelector('input[name="start_at"]');
            var endField = form.querySelector('input[name="end_at"]');
            if (!startField || !endField) {
                return;
            }

            form.dataset.omoCalendarLastStart = startField.value || '';
            form.dataset.omoCalendarLastEnd = endField.value || '';
        }

        function syncEndDateWithStart(form) {
            if (!form) {
                return;
            }

            var startField = form.querySelector('input[name="start_at"]');
            var endField = form.querySelector('input[name="end_at"]');
            if (!startField || !endField) {
                return;
            }

            var startDate = parseLocalDateTime(startField.value);
            if (!startDate) {
                return;
            }

            var previousStart = parseLocalDateTime(form.dataset.omoCalendarLastStart || '');
            var previousEnd = parseLocalDateTime(form.dataset.omoCalendarLastEnd || '');
            var durationMs = 0;

            if (previousStart && previousEnd) {
                durationMs = Math.max(0, previousEnd.getTime() - previousStart.getTime());
            } else {
                var currentEnd = parseLocalDateTime(endField.value);
                if (currentEnd) {
                    durationMs = Math.max(0, currentEnd.getTime() - startDate.getTime());
                }
            }

            var nextEnd = new Date(startDate.getTime() + durationMs);
            if (durationMs <= 0) {
                endField.value = startField.value;
            } else {
                endField.value = formatLocalDateTimeValue(nextEnd);
            }

            rememberScheduleState(form);
        }

        function syncLocationFields(form) {
            if (!form) {
                return;
            }

            var modeField = form.querySelector('[data-omo-calendar-location-mode]');
            var addressField = form.querySelector('[data-omo-calendar-location-address-field]');
            var videoField = form.querySelector('[data-omo-calendar-location-video-field]');
            var modeValue = modeField ? String(modeField.value || '').trim() : '';
            var showAddress = modeValue === 'in_person' || modeValue === 'hybrid';
            var showVideo = modeValue === 'virtual' || modeValue === 'hybrid';

            if (addressField) {
                addressField.hidden = !showAddress;
                var addressInput = addressField.querySelector('input[name="location_address"]');
                if (addressInput) {
                    addressInput.required = showAddress;
                }
            }

            if (videoField) {
                videoField.hidden = !showVideo;
                var videoInput = videoField.querySelector('input[name="video_meeting_url"]');
                if (videoInput) {
                    videoInput.required = showVideo;
                }
            }
        }

        function syncDocumentFields(form) {
            if (!form) {
                return;
            }

            var typeField = form.querySelector('[data-omo-calendar-document-type]');
            var allDocumentFields = form.querySelector('[data-omo-calendar-document-fields]');
            var pvTemplateField = form.querySelector('[data-omo-calendar-pv-template-field]');
            var documentType = typeField ? String(typeField.value || '').trim() : '';
            var hasDocument = documentType !== '';

            if (allDocumentFields) {
                allDocumentFields.hidden = !hasDocument;
            }

            if (pvTemplateField) {
                pvTemplateField.hidden = documentType !== 'pv';
            }
        }

        function syncInvitationDefaultHolonSelection(editor, nextHolonId) {
            if (!editor || editor.getAttribute('data-omo-calendar-uses-default-selection') !== '1') {
                return;
            }

            var holonId = Number(nextHolonId || '0');
            if (!Number.isInteger(holonId) || holonId < 0) {
                holonId = 0;
            }

            Array.prototype.forEach.call(editor.querySelectorAll('input[name="invitation_holon_ids[]"]'), function (field) {
                field.checked = holonId > 0 && Number(field.value || '0') === holonId;
            });

            editor.dataset.omoCalendarDefaultHolonId = String(holonId);
            syncInvitationEditorFilters(editor);
        }

        function normalizeInvitationFilterText(value) {
            var normalized = String(value || '').toLowerCase();

            if (typeof normalized.normalize === 'function') {
                normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            }

            return normalized.replace(/\s+/g, ' ').trim();
        }

        function getInvitationHolonChildrenContainer(node) {
            if (!node || !node.children) {
                return null;
            }

            for (var index = 0; index < node.children.length; index += 1) {
                var child = node.children[index];
                if (child && child.hasAttribute && child.hasAttribute('data-omo-calendar-holon-children')) {
                    return child;
                }
            }

            return null;
        }

        function filterInvitationHolonNode(node, query) {
            if (!node) {
                return false;
            }

            var searchText = normalizeInvitationFilterText(node.getAttribute('data-omo-calendar-search-text') || node.textContent || '');
            var row = node.querySelector('.omo-calendar-invitations-editor__tree-row');
            var checkbox = row ? row.querySelector('input[type="checkbox"]') : null;
            var toggle = row ? row.querySelector('[data-omo-calendar-holon-toggle]') : null;
            var childrenContainer = getInvitationHolonChildrenContainer(node);
            var keepChecked = !!(checkbox && checkbox.checked);
            var matchesSelf = query === '' || searchText.indexOf(query) !== -1;
            var hasVisibleChild = false;

            if (childrenContainer && childrenContainer.children) {
                Array.prototype.forEach.call(childrenContainer.children, function (childNode) {
                    if (!childNode || !childNode.hasAttribute || !childNode.hasAttribute('data-omo-calendar-holon-node')) {
                        return;
                    }

                    if (filterInvitationHolonNode(childNode, query)) {
                        hasVisibleChild = true;
                    }
                });
            }

            var isVisible = query === '' ? true : (matchesSelf || keepChecked || hasVisibleChild);
            node.hidden = !isVisible;

            if (childrenContainer) {
                if (query === '') {
                    var isExpanded = !toggle || toggle.getAttribute('aria-expanded') === 'true';
                    childrenContainer.hidden = !isExpanded;
                } else {
                    childrenContainer.hidden = !hasVisibleChild;
                }
            }

            return isVisible;
        }

        function syncInvitationHolonFilter(editor) {
            if (!editor) {
                return;
            }

            var filterField = editor.querySelector('[data-omo-calendar-holon-filter]');
            var holonList = editor.querySelector('[data-omo-calendar-holon-list]');
            var emptyState = editor.querySelector('[data-omo-calendar-holon-empty]');
            var query = normalizeInvitationFilterText(filterField ? filterField.value : '');
            var hasVisibleResult = false;

            if (holonList && holonList.children) {
                Array.prototype.forEach.call(holonList.children, function (node) {
                    if (!node || !node.hasAttribute || !node.hasAttribute('data-omo-calendar-holon-node')) {
                        return;
                    }

                    if (filterInvitationHolonNode(node, query)) {
                        hasVisibleResult = true;
                    }
                });
            }

            if (emptyState) {
                emptyState.hidden = query === '' || hasVisibleResult;
            }
        }

        function syncInvitationMemberFilter(editor) {
            if (!editor) {
                return;
            }

            var filterField = editor.querySelector('[data-omo-calendar-member-filter]');
            var memberList = editor.querySelector('[data-omo-calendar-member-list]');
            var emptyState = editor.querySelector('[data-omo-calendar-member-empty]');
            var query = normalizeInvitationFilterText(filterField ? filterField.value : '');
            var hasVisibleResult = false;

            Array.prototype.forEach.call(editor.querySelectorAll('[data-omo-calendar-member-item]'), function (item) {
                var checkbox = item.querySelector('input[type="checkbox"]');
                var searchText = normalizeInvitationFilterText(item.getAttribute('data-omo-calendar-search-text') || item.textContent || '');
                var keepChecked = !!(checkbox && checkbox.checked);
                var isVisible = query === '' || searchText.indexOf(query) !== -1 || keepChecked;

                item.hidden = !isVisible;
                if (isVisible) {
                    hasVisibleResult = true;
                }
            });

            if (memberList) {
                memberList.hidden = false;
            }

            if (emptyState) {
                emptyState.hidden = query === '' || hasVisibleResult;
            }
        }

        function syncInvitationEditorFilters(editor) {
            if (!editor) {
                return;
            }

            syncInvitationHolonFilter(editor);
            syncInvitationMemberFilter(editor);
        }

        function initCalendarInvitationEditors(scope) {
            var rootScope = scope && scope.querySelectorAll ? scope : document;

            if (typeof window.initGenericComponents === 'function') {
                window.initGenericComponents(rootScope);
            }

            Array.prototype.forEach.call(rootScope.querySelectorAll('[data-omo-calendar-invitations-editor]'), function (editor) {
                if (editor.dataset.omoCalendarInvitationsReady === '1') {
                    return;
                }

                editor.dataset.omoCalendarInvitationsReady = '1';

                Array.prototype.forEach.call(editor.querySelectorAll('[data-omo-calendar-holon-toggle]'), function (toggle) {
                    toggle.addEventListener('click', function (event) {
                        var node;
                        var children;
                        var isExpanded;

                        event.preventDefault();
                        event.stopPropagation();

                        node = toggle.closest('[data-omo-calendar-holon-node]');
                        children = node ? node.querySelector('[data-omo-calendar-holon-children]') : null;
                        if (!children) {
                            return;
                        }

                        isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                        toggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
                        children.hidden = isExpanded;
                        syncInvitationEditorFilters(editor);
                    });
                });

                Array.prototype.forEach.call(editor.querySelectorAll('[data-omo-calendar-holon-filter], [data-omo-calendar-member-filter]'), function (field) {
                    field.addEventListener('input', function () {
                        syncInvitationEditorFilters(editor);
                    });
                });

                editor.addEventListener('change', function () {
                    if (editor.getAttribute('data-omo-calendar-uses-default-selection') === '1') {
                        editor.setAttribute('data-omo-calendar-uses-default-selection', '0');
                    }

                    syncInvitationEditorFilters(editor);
                });

                syncInvitationEditorFilters(editor);
            });
        }

        function syncCalendarCreateFormState(form) {
            if (!form) {
                return;
            }

            syncLocationFields(form);
            syncDocumentFields(form);
            initCalendarInvitationEditors(form);
            rememberScheduleState(form);
        }

        function setDrawerHeader(options) {
            var settings = options && typeof options === 'object' ? options : {};
            var hasTitle = Object.prototype.hasOwnProperty.call(settings, 'title');
            var hasDescription = Object.prototype.hasOwnProperty.call(settings, 'description')
                || Object.prototype.hasOwnProperty.call(settings, 'subtitle');
            var hasActions = Object.prototype.hasOwnProperty.call(settings, 'actions');
            var description = Object.prototype.hasOwnProperty.call(settings, 'subtitle')
                ? settings.subtitle
                : settings.description;

            if (drawerTitle && hasTitle) {
                drawerTitle.textContent = settings.title || '';
            }

            if (drawerDescription && hasDescription) {
                drawerDescription.textContent = description || '';
                drawerDescription.hidden = !description;
            }

            if (!drawerActions || !hasActions) {
                return;
            }

            drawerActions.innerHTML = '';
            (Array.isArray(settings.actions) ? settings.actions : []).forEach(function (action) {
                var button;
                if (action instanceof HTMLElement) {
                    drawerActions.appendChild(action);
                    return;
                }

                if (!action || typeof action !== 'object' || !action.label) {
                    return;
                }

                button = document.createElement('button');
                button.type = action.type || 'button';
                button.className = action.className || 'generic-action-button';
                button.textContent = action.label;
                if (action.attributes && typeof action.attributes === 'object') {
                    Object.keys(action.attributes).forEach(function (name) {
                        button.setAttribute(name, String(action.attributes[name]));
                    });
                }
                if (typeof action.onClick === 'function') {
                    button.addEventListener('click', action.onClick);
                }
                drawerActions.appendChild(button);
            });
        }

        function resetDrawerHeader() {
            setDrawerHeader({
                title: defaultDrawerTitle,
                description: defaultDrawerDescription,
                actions: []
            });
        }

        function applyDrawerHeaderFromContent(content) {
            var header = content ? content.querySelector('[data-omo-calendar-drawer-header]') : null;
            if (!header) {
                resetDrawerHeader();
                return;
            }

            setDrawerHeader({
                title: header.getAttribute('data-omo-calendar-drawer-title') || defaultDrawerTitle,
                description: header.getAttribute('data-omo-calendar-drawer-description') || '',
                actions: Array.prototype.slice.call(header.querySelectorAll('[data-omo-calendar-drawer-action]'))
            });
        }

        window.omoCalendarDrawer = window.omoCalendarDrawer || {};
        window.omoCalendarDrawer.setHeader = setDrawerHeader;
        window.omoCalendarDrawer.setTitle = function (title) {
            setDrawerHeader({ title: title });
        };
        window.omoCalendarDrawer.setSubtitle = function (subtitle) {
            setDrawerHeader({ subtitle: subtitle });
        };
        window.omoCalendarDrawer.addButton = function (button) {
            if (!drawerActions) {
                return;
            }
            setDrawerHeader({
                actions: Array.prototype.slice.call(drawerActions.children).concat([button])
            });
        };
        window.omoCalendarDrawer.resetHeader = resetDrawerHeader;

        function setDrawerLoading() {
            if (!drawerBody) {
                return;
            }

            resetDrawerHeader();
            drawerBody.innerHTML = '<div class="generic-section"><?= omoApiEscape(omoCalendarT('calendar.loading')) ?></div>';
        }

        function setDrawerError() {
            if (!drawerBody) {
                return;
            }

            resetDrawerHeader();
            drawerBody.innerHTML = '<div class="generic-section"><?= omoApiEscape(omoCalendarT('calendar.error.load_form')) ?></div>';
        }

        function closeDrawer(options) {
            var settings = options && typeof options === 'object'
                ? options
                : {};

            if (
                settings.force !== true
                && /^calendar-(?:e\d+|event-\d+)$/i.test(getCurrentRouteToken())
                && typeof window.omoOpenDrawerHashState === 'function'
            ) {
                window.omoOpenDrawerHashState('calendar');
                return;
            }

            if (!drawer) {
                return;
            }

            drawer.classList.remove('is-open');
            window.setTimeout(function () {
                if (!drawer.classList.contains('is-open')) {
                    drawer.hidden = true;
                    if (drawerBody) {
                        drawerBody.innerHTML = '';
                    }
                }
            }, 180);
        }

        function openDrawerWithUrl(url) {
            if (!drawer || !drawerBody || !url) {
                return;
            }

            setDrawerLoading();
            drawer.hidden = false;
            window.requestAnimationFrame(function () {
                drawer.classList.add('is-open');
            });

            var localToken = ++requestToken;

            fetch(resolveUrl(url), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('load_failed');
                }

                return response.text();
            }).then(function (html) {
                if (localToken !== requestToken || !drawerBody) {
                    return;
                }

                drawerBody.innerHTML = html;
                applyDrawerHeaderFromContent(drawerBody);
                if (typeof window.initGenericComponents === 'function') {
                    window.initGenericComponents(drawerBody);
                }
                syncCalendarCreateFormState(drawerBody.querySelector('[data-omo-calendar-create-form]'));
            }).catch(function () {
                if (localToken !== requestToken) {
                    return;
                }

                setDrawerError();
            });
        }

        function refreshCalendar(url) {
            var targetUrl = url || currentUrl;
            if (!targetUrl) {
                return;
            }

            if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
                window.location.href = resolveUrl(targetUrl);
                return;
            }

            window.omoReplaceFetchedPanelRoot({
                rootSelector: '#omo-calendar-root',
                currentRoot: root,
                url: resolveUrl(targetUrl)
            });
        }

        function escapeCalendarHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function askDeleteAssociatedDocuments(button) {
            if (button.getAttribute('data-omo-calendar-delete-has-documents') !== '1') {
                return Promise.resolve(false);
            }

            var title = button.getAttribute('data-omo-calendar-delete-documents-title') || 'Documents associés';
            var question = button.getAttribute('data-omo-calendar-delete-documents-question') || 'Voulez-vous supprimer les documents associés ?';
            var yesLabel = button.getAttribute('data-omo-calendar-delete-documents-yes') || 'Oui';
            var noLabel = button.getAttribute('data-omo-calendar-delete-documents-no') || 'Non';

            if (typeof window.commonTopbarOpenModal !== 'function') {
                return Promise.resolve(window.confirm(question));
            }

            return new Promise(function (resolve) {
                var settled = false;
                var modalCloseHandler;

                function settle(value) {
                    if (settled) {
                        return;
                    }

                    settled = true;
                    if (modalCloseHandler) {
                        window.removeEventListener('common-topbar-modal-close', modalCloseHandler);
                    }
                    if (typeof window.commonTopbarCloseModal === 'function') {
                        window.commonTopbarCloseModal();
                    }
                    resolve(value === true);
                }

                window.commonTopbarOpenModal(
                    title,
                    '<div class="omo-calendar__delete-documents-dialog">'
                        + '<p data-omo-calendar-delete-documents-question></p>'
                        + '<div class="omo-calendar__delete-documents-actions">'
                        + '<button type="button" class="generic-action-button generic-action-button--main" data-omo-calendar-delete-documents-choice="yes">' + escapeCalendarHtml(yesLabel) + '</button>'
                        + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-calendar-delete-documents-choice="no">' + escapeCalendarHtml(noLabel) + '</button>'
                        + '</div>'
                        + '</div>',
                    'html'
                );

                var modalBody = document.getElementById('commonTopbarModalBody');
                if (!modalBody) {
                    settle(false);
                    return;
                }

                var questionNode = modalBody.querySelector('[data-omo-calendar-delete-documents-question]');
                if (questionNode) {
                    questionNode.textContent = question;
                }

                modalCloseHandler = function () {
                    settle(false);
                };
                window.addEventListener('common-topbar-modal-close', modalCloseHandler);
                modalBody.querySelectorAll('[data-omo-calendar-delete-documents-choice]').forEach(function (choiceButton) {
                    choiceButton.addEventListener('click', function () {
                        settle(choiceButton.getAttribute('data-omo-calendar-delete-documents-choice') === 'yes');
                    });
                });
            });
        }

        function deleteCalendarEvent(deleteButton) {
            if (!(deleteButton instanceof Element)) {
                return;
            }

            var deleteUrl = deleteButton.getAttribute('data-omo-calendar-delete-url') || '';
            var confirmationMessage = deleteButton.getAttribute('data-omo-calendar-delete-confirm') || '';
            var fallbackError = deleteButton.getAttribute('data-omo-calendar-delete-error') || 'Impossible de supprimer cet evenement.';
            if (!deleteUrl || (confirmationMessage !== '' && !window.confirm(confirmationMessage))) {
                return;
            }

            deleteButton.disabled = true;
            askDeleteAssociatedDocuments(deleteButton).then(function (deleteDocuments) {
                var requestBody = new URLSearchParams();
                requestBody.set('delete_documents', deleteDocuments ? '1' : '0');

                return fetch(resolveUrl(deleteUrl), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: requestBody.toString()
                });
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                if (!payload || payload.status !== true) {
                    throw new Error(payload && payload.message ? payload.message : fallbackError);
                }

                if (typeof window.omoInvalidateMainRightPanel === 'function') {
                    window.omoInvalidateMainRightPanel();
                }

                closeDrawer();
                refreshCalendar(currentUrl);
            }).catch(function (error) {
                window.alert(error && error.message ? error.message : fallbackError);
                deleteButton.disabled = false;
            });
        }

        window.omoCalendarOpenEventDrawer = function (url) {
            if (!url) {
                return;
            }

            openDrawerWithUrl(url);
        };

        window.omoCalendarRefreshCurrentView = function () {
            if (drawer && !drawer.hidden && drawer.classList.contains('is-open')) {
                return;
            }

            refreshCalendar(currentUrl);
        };
        window.omoCalendarInitInvitationEditors = initCalendarInvitationEditors;

        function syncViewButtons(nextView) {
            root.querySelectorAll('[data-omo-calendar-set-view]').forEach(function (button) {
                var isActive = (button.getAttribute('data-omo-calendar-set-view') || '') === nextView;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        function syncScopeButtons(nextScope) {
            var resolvedScope = normalizeScopeName(nextScope);
            var scopeSwitch = root.querySelector('[data-omo-scope-switch]');
            if (scopeSwitch) {
                scopeSwitch.setAttribute('data-omo-scope-switch', resolvedScope);
            }

            root.querySelectorAll('[data-omo-calendar-scope-toggle]').forEach(function (button) {
                var isActive = (button.getAttribute('data-omo-calendar-scope-toggle') || '') === resolvedScope;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                if (isActive && scopeSwitch) {
                    scopeSwitch.style.setProperty(
                        '--omo-scope-active-index',
                        String(parseInt(button.getAttribute('data-omo-scope-index') || '0', 10) || 0)
                    );
                }
            });
        }

        function syncTodayButtons(nextView) {
            root.querySelectorAll('[data-omo-calendar-today-button]').forEach(function (button) {
                var matches = (button.getAttribute('data-omo-calendar-today-button') || '') === nextView;
                button.classList.toggle('is-hidden', !matches);
                if (matches) {
                    var nextUrl = button.getAttribute('data-omo-calendar-nav-url-' + currentScope) || '';
                    if (nextUrl) {
                        button.setAttribute('data-omo-calendar-nav-url', nextUrl);
                    }
                }
            });
        }

        function scrollTimelineToBusinessStart(viewName) {
            if (viewName !== 'week' && viewName !== 'day') {
                return;
            }

            var panel = root.querySelector(
                '[data-omo-calendar-timeline-panel="' + viewName + '"][data-omo-calendar-view-scope="' + currentScope + '"]'
            );
            if (!panel) {
                return;
            }

            var timeView = panel.querySelector('[data-omo-calendar-time-view]');
            if (!timeView) {
                return;
            }

            var targetHour = timeView.querySelector('[data-omo-calendar-hour-index="7"]');
            if (!targetHour) {
                return;
            }

            var stickyBlock = timeView.querySelector('[data-omo-calendar-time-sticky]');
            var stickyHeight = stickyBlock ? stickyBlock.offsetHeight : 0;
            var targetTop = Math.max(0, targetHour.offsetTop - stickyHeight - 8);

            timeView.scrollTo({
                top: targetTop,
                behavior: 'auto'
            });
        }

        function findActiveViewPanel() {
            return root.querySelector(
                '[data-omo-calendar-view-panel="' + currentView + '"][data-omo-calendar-view-scope="' + currentScope + '"]'
            );
        }

        function findRouteTargetNode() {
            if (initialOpenEventId <= 0) {
                return null;
            }

            var activePanel = findActiveViewPanel();
            if (!activePanel) {
                return null;
            }

            return activePanel.querySelector('[data-omo-calendar-event-id="' + String(initialOpenEventId) + '"]');
        }

        function focusRouteTargetEvent(behavior) {
            var routeTargetNode = findRouteTargetNode();
            if (!routeTargetNode) {
                return false;
            }

            routeTargetNode.classList.remove('is-route-target-active');
            void routeTargetNode.offsetWidth;
            routeTargetNode.classList.add('is-route-target-active');

            try {
                routeTargetNode.scrollIntoView({
                    block: 'center',
                    inline: 'nearest',
                    behavior: behavior || 'smooth'
                });
            } catch (error) {
                routeTargetNode.scrollIntoView(true);
            }

            return true;
        }

        function maybeOpenInitialEventDetail() {
            if (initialOpenEventId <= 0 || initialOpenEventDrawerOpened) {
                return;
            }

            initialOpenEventDrawerOpened = true;
            window.setTimeout(function () {
                openDrawerWithUrl(buildDetailUrl(initialOpenEventId));
            }, 40);
        }

        function openEventFromRoute(eventId) {
            var resolvedEventId = Number(eventId || 0);
            if (!Number.isInteger(resolvedEventId) || resolvedEventId <= 0) {
                return false;
            }

            openDrawerWithUrl(buildDetailUrl(resolvedEventId));
            return true;
        }

        function setActiveView(nextView, nextUrl, nextCount, nextSummary) {
            if (!nextView) {
                return;
            }

            currentView = nextView;
            writeCalendarPreferences({
                view: nextView
            });
            if (nextUrl) {
                currentUrl = nextUrl;
            }

            root.setAttribute('data-omo-calendar-view', nextView);
            root.setAttribute('data-omo-calendar-scope', currentScope);

            root.querySelectorAll('[data-omo-calendar-view-panel]').forEach(function (panel) {
                var panelView = panel.getAttribute('data-omo-calendar-view-panel') || '';
                var panelScope = normalizeScopeName(panel.getAttribute('data-omo-calendar-view-scope') || 'contextual');
                var isActive = panelView === nextView && panelScope === currentScope;
                panel.classList.toggle('is-active', isActive);
                panel.toggleAttribute('hidden', !isActive);
                panel.style.display = isActive ? '' : 'none';
            });

            syncViewButtons(nextView);
            syncScopeButtons(currentScope);
            syncTodayButtons(nextView);

            if (headerCount && typeof nextCount === 'string') {
                headerCount.textContent = nextCount;
            }

            if (headerSummary && typeof nextSummary === 'string') {
                headerSummary.textContent = nextSummary;
            }

            window.requestAnimationFrame(function () {
                scrollTimelineToBusinessStart(nextView);
                if (initialOpenEventId > 0) {
                    focusRouteTargetEvent('auto');
                }
            });
        }

        root.querySelectorAll('[data-omo-calendar-editor-close]').forEach(function (button) {
            button.addEventListener('click', closeDrawer);
        });

        root.querySelectorAll('[data-omo-calendar-set-view]').forEach(function (button) {
            button.addEventListener('click', function () {
                var nextView = button.getAttribute('data-omo-calendar-set-view') || '';
                var nextMeta = resolveViewMeta(nextView, currentScope);
                setActiveView(nextView, nextMeta.url, nextMeta.count, nextMeta.summary);
            });
        });

        root.querySelectorAll('[data-omo-calendar-scope-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var nextScope = normalizeScopeName(button.getAttribute('data-omo-calendar-scope-toggle') || '');
                if (nextScope === currentScope) {
                    return;
                }

                currentScope = nextScope;
                var nextMeta = resolveViewMeta(currentView, currentScope);
                setActiveView(currentView, nextMeta.url, nextMeta.count, nextMeta.summary);
            });
        });

        root.querySelectorAll('[data-omo-calendar-nav-url], [data-omo-calendar-nav-url-contextual]').forEach(function (button) {
            button.addEventListener('click', function () {
                var url = button.getAttribute('data-omo-calendar-nav-url') || '';
                if (!url) {
                    return;
                }

                refreshCalendar(url);
            });
        });

        var openCreateButton = root.querySelector('[data-omo-calendar-open-create]');
        if (openCreateButton) {
            openCreateButton.addEventListener('click', function () {
                openDrawerWithUrl(createUrl);
            });
        }

        if (canCreateEvent) {
            root.querySelectorAll('[data-omo-calendar-day]').forEach(function (cell) {
                cell.addEventListener('dblclick', function () {
                    var day = cell.getAttribute('data-omo-calendar-day') || '';
                    openDrawerWithUrl(buildCreateUrl(day));
                });
            });

            root.querySelectorAll('[data-omo-calendar-time-column-day]').forEach(function (columnNode) {
                columnNode.addEventListener('dblclick', function (event) {
                    if (event.target && event.target.closest('[data-omo-calendar-event-id]')) {
                        return;
                    }

                    var day = columnNode.getAttribute('data-omo-calendar-time-column-day') || '';
                    var dateTimeValue = buildDateTimeFromColumnPosition(day, event.clientY, columnNode);
                    openDrawerWithUrl(buildCreateUrl(day, dateTimeValue));
                });
            });
        }

        root.querySelectorAll('[data-omo-calendar-event-id]').forEach(function (eventNode) {
            eventNode.addEventListener('dblclick', function (event) {
                event.preventDefault();
                event.stopPropagation();

                var eventId = eventNode.getAttribute('data-omo-calendar-event-id') || '';
                if (!eventId) {
                    return;
                }

                var routeToken = buildEventRouteToken(eventId);
                var currentRouteToken = getCurrentRouteToken();

                if (routeToken && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== currentRouteToken) {
                    window.omoOpenDrawerHashState(routeToken);
                    return;
                }

                openDrawerWithUrl(buildDetailUrl(eventId));
            });
        });

        if (drawer) {
            drawer.addEventListener('click', function (event) {
                var cancelButton = event.target.closest('[data-omo-calendar-open-detail-url]');
                if (cancelButton) {
                    event.preventDefault();
                    var cancelDetailUrl = cancelButton.getAttribute('data-omo-calendar-open-detail-url') || '';
                    if (cancelDetailUrl) {
                        openDrawerWithUrl(cancelDetailUrl);
                    }
                    return;
                }

                var deleteButton = event.target.closest('[data-omo-calendar-delete-url]');
                if (deleteButton) {
                    event.preventDefault();
                    deleteCalendarEvent(deleteButton);
                    return;
                }

                var editButton = event.target.closest('[data-omo-calendar-open-edit-url]');
                if (!editButton) {
                    var invitationButton = event.target.closest('[data-omo-calendar-open-invitations-url]');
                    if (invitationButton) {
                        event.preventDefault();
                        if (typeof window.commonTopbarOpenModal !== 'function') {
                            return;
                        }

                        var invitationUrl = invitationButton.getAttribute('data-omo-calendar-open-invitations-url') || '';
                        var invitationTitle = invitationButton.getAttribute('data-omo-calendar-open-invitations-title') || 'Invites';
                        if (!invitationUrl) {
                            return;
                        }

                        window.commonTopbarOpenModal(invitationTitle, resolveUrl(invitationUrl), 'fetch');
                        return;
                    }

                    var openUrlButton = event.target.closest('[data-omo-calendar-open-url]');
                    if (!openUrlButton) {
                        return;
                    }

                    event.preventDefault();
                    var targetUrl = openUrlButton.getAttribute('data-omo-calendar-open-url') || '';
                    var targetTitle = openUrlButton.getAttribute('data-omo-calendar-open-url-title') || 'Document';
                    var targetPvEditorUrl = openUrlButton.getAttribute('data-omo-calendar-open-pv-editor-url') || '';
                    if (!targetUrl) {
                        return;
                    }

                    if (targetPvEditorUrl && typeof window.omoOpenExternalPanelDrawer === 'function') {
                        window.omoOpenExternalPanelDrawer({
                            url: targetPvEditorUrl,
                            mode: 'fetch',
                            title: targetTitle,
                            description: 'Edition du PV.',
                            variant: 'top-sheet',
                            persistKey: 'omo-pv-preparation-calendar-' + targetPvEditorUrl,
                            keepMountedOnClose: true
                        });
                        return;
                    }

                    if (typeof window.omoOpenSearchDocumentResult === 'function') {
                        window.omoOpenSearchDocumentResult(targetUrl, targetTitle);
                        return;
                    }

                    openDrawerWithUrl(targetUrl);
                    return;
                }

                event.preventDefault();
                var editUrl = editButton.getAttribute('data-omo-calendar-open-edit-url') || '';
                if (!editUrl) {
                    return;
                }

                openDrawerWithUrl(editUrl);
            });

        }

        if (drawerBody) {
            drawerBody.addEventListener('change', function (event) {
                var form = event.target && event.target.form ? event.target.form : null;
                if (form && form.matches('[data-omo-calendar-create-form]')) {
                    syncLocationFields(form);
                    syncDocumentFields(form);
                    if (event.target.matches('[data-omo-calendar-context-holon]')) {
                        syncInvitationDefaultHolonSelection(
                            form.querySelector('[data-omo-calendar-invitations-editor]'),
                            event.target.value
                        );
                    }
                }

                var startField = event.target.closest('input[name="start_at"]');
                if (!startField) {
                    var scheduleField = event.target.closest('input[name="end_at"]');
                    if (scheduleField && scheduleField.form) {
                        rememberScheduleState(scheduleField.form);
                    }
                    return;
                }

                syncEndDateWithStart(startField.form);
            });

            drawerBody.addEventListener('submit', function (event) {
                var form = event.target.closest('[data-omo-calendar-create-form]');
                if (!form) {
                    return;
                }

                event.preventDefault();

                var feedback = form.querySelector('[data-omo-calendar-create-feedback]');
                var submitButton = form.querySelector('[data-omo-calendar-create-submit]');
                if (!submitButton && form.id) {
                    submitButton = drawer.querySelector('[data-omo-calendar-create-submit][form="' + form.id + '"]');
                }
                var formData = new FormData(form);

                if (submitButton) {
                    submitButton.disabled = true;
                }

                if (feedback) {
                    feedback.textContent = '';
                    feedback.className = 'omo-calendar-create__feedback';
                }

                fetch(resolveUrl(form.getAttribute('action') || createUrl), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                }).then(function (response) {
                    return response.json();
                }).then(function (payload) {
                    if (!payload || payload.status !== true) {
                        throw payload || new Error('save_failed');
                    }

                    if (typeof window.omoInvalidateMainRightPanel === 'function') {
                        window.omoInvalidateMainRightPanel();
                    }

                    if (payload.detailUrl) {
                        openDrawerWithUrl(payload.detailUrl);
                    } else {
                        closeDrawer();
                        refreshCalendar(currentUrl);
                    }
                }).catch(function (error) {
                    if (!feedback) {
                        return;
                    }

                    var message = error && typeof error.message === 'string' && error.message !== ''
                        ? error.message
                        : 'Impossible d enregistrer cet evenement.';
                    feedback.textContent = message;
                    feedback.className = 'omo-calendar-create__feedback is-error';
                }).finally(function () {
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                });
            });
        }

        if (initialOpenEventId <= 0) {
            var savedPreferences = readCalendarPreferences();
            var preferredView = normalizeViewPreference(savedPreferences.view);

            if (preferredView !== currentView && root.querySelector('[data-omo-calendar-set-view="' + preferredView + '"]')) {
                var preferredViewMeta = resolveViewMeta(preferredView, currentScope);
                setActiveView(preferredView, preferredViewMeta.url, preferredViewMeta.count, preferredViewMeta.summary);
            }
        }

        syncScopeButtons(currentScope);
        syncTodayButtons(currentView);

        if (!root.__omoCalendarRouteHandler) {
            root.__omoCalendarRouteHandler = function (routeEvent) {
                if (!document.body.contains(root)) {
                    return;
                }

                var detail = routeEvent && routeEvent.detail
                    ? routeEvent.detail
                    : {};
                var targetEventId = Number(detail.eventId || 0);

                if (targetEventId > 0) {
                    openEventFromRoute(targetEventId);
                    return;
                }

                closeDrawer({ force: true });
            };

            window.addEventListener('omo-calendar-route-change', root.__omoCalendarRouteHandler);
        }

        window.requestAnimationFrame(function () {
            scrollTimelineToBusinessStart(currentView);
            if (initialOpenEventId > 0) {
                focusRouteTargetEvent('auto');
                maybeOpenInitialEventDetail();
            }
        });
    })();
    </script>
</div>

<style>
.omo-calendar {
    display: flex;
    flex-direction: column;
    gap: 0;
    min-height: 100%;
}

.omo-calendar__delete-documents-dialog {
    display: grid;
    gap: 18px;
}

.omo-calendar__delete-documents-dialog p {
    margin: 0;
    color: var(--color-text, #1f2937);
    line-height: 1.5;
}

.omo-calendar__delete-documents-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

.omo-calendar > .omo-panel-view__body {
    gap: 0;
    overflow: hidden;
    min-height: 0;
}

.omo-calendar__header {
    display: block;
    width: 100%;
    min-width: 0;
    justify-content: stretch;
    align-items: initial;
}

.omo-calendar__header-main,
.omo-calendar__header-secondary {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    width: 100%;
    min-width: 0;
}

.omo-calendar__header-secondary {
    margin-top: 5px;
}

.omo-calendar__title-cluster {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.omo-calendar__app-icon {
    width: 38px;
    height: 38px;
    border-radius: var(--radius-md);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    background: color-mix(in srgb, var(--color-primary, #2563eb) 11%, var(--color-surface, #ffffff) 89%);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-primary, #2563eb) 16%, var(--color-border, #dbe2ea));
}

.omo-calendar__app-icon svg {
    width: 20px;
    height: 20px;
    stroke: var(--color-primary, #2563eb);
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
    fill: none;
}

.omo-calendar__title-row {
    display: flex;
    align-items: baseline;
    gap: 10px;
}

.omo-calendar__count {
    min-width: 0;
}

.omo-calendar__header-text {
    margin: 0;
    color: var(--color-text-light, #64748b);
    line-height: 1.6;
}

.omo-calendar__meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.omo-calendar__context-badge,
.omo-calendar__summary-badge {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 0.84rem;
    font-weight: 700;
}

.omo-calendar__context-badge {
    background: color-mix(in srgb, var(--color-primary, #2563eb) 12%, var(--color-surface, #ffffff));
    color: var(--color-text, #1f2937);
}

.omo-calendar__summary-badge {
    background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 82%, var(--color-surface, #ffffff) 18%);
    color: var(--color-text-light, #64748b);
}

.omo-calendar__header-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 12px;
}

.omo-calendar__scope-slot {
    min-width: 0;
}

.omo-calendar__view-switch {
    justify-self: end;
}

.omo-calendar__today-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.omo-calendar__new-button {
    flex: 0 0 auto;
}

.omo-calendar__panel {
    gap: 14px;
    min-width: 0;
    min-height: 0;
}

.omo-calendar__panel--month {
    display: flex;
    flex-direction: column;
    padding: 0;
    gap: 0;
}

.omo-calendar__month-scroll {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
    margin: 0;
    padding: 0;
}

.omo-calendar__month-sticky {
    position: sticky;
    top: 0;
    z-index: 14;
    display: grid;
    gap: 0;
    background: var(--color-surface, #ffffff);
}

.omo-calendar__panel--month .omo-calendar__toolbar {
    padding-block: 6px;
    background: var(--color-surface, #ffffff);
    box-shadow: 0 10px 18px -18px rgba(15, 23, 42, 0.4);
}

.omo-calendar__panel--timeline {
    display: flex;
    flex-direction: column;
    gap: 0;
    min-height: 0;
    overflow: hidden;
}

.omo-calendar__view-panel.is-active {
    display: block;
}

.omo-calendar__panel--month.omo-calendar__view-panel.is-active {
    display: flex;
    flex: 1 1 auto;
}

.omo-calendar__panel--timeline.omo-calendar__view-panel.is-active {
    display: flex;
    flex: 1 1 auto;
}

.omo-calendar__view-panel[hidden] {
    display: none !important;
}

.omo-calendar__view-panel--list {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
}

.omo-calendar__view-panel--list.omo-calendar__view-panel.is-active {
    display: block;
}

.omo-calendar__today-actions .is-hidden {
    display: none;
}

.omo-calendar__toolbar {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
}

.omo-calendar__panel--timeline .omo-calendar__toolbar {
    position: sticky;
    top: 0;
    z-index: 14;
    padding-block: 6px;
    background: var(--color-surface, #ffffff);
}

.omo-calendar__period-title,
.omo-calendar__month-title {
    display: grid;
    gap: 4px;
    text-align: center;
}

.omo-calendar__period-title strong,
.omo-calendar__month-title strong {
    font-size: 1.15rem;
    color: var(--color-text, #1f2937);
}

.omo-calendar__period-title span,
.omo-calendar__month-title span {
    color: var(--color-text-light, #64748b);
    font-size: 0.95rem;
}

.omo-calendar__weekday-row,
.omo-calendar__grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0;
}

.omo-calendar__weekday-row {
    border: 1px solid var(--color-border, #dbe2ea);
    border-bottom: 0;
    background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 86%, var(--color-surface, #ffffff) 14%);
}

.omo-calendar__grid {
    border: 1px solid var(--color-border, #dbe2ea);
    border-top: 0;
}

.omo-calendar__weekday {
    padding: 12px 8px;
    text-align: center;
    font-size: 0.84rem;
    font-weight: 700;
    color: var(--color-text-light, #64748b);
    border-right: 1px solid var(--color-border, #dbe2ea);
}

.omo-calendar__weekday:nth-child(7n) {
    border-right: 0;
}

.omo-calendar__cell {
    min-height: 138px;
    padding: 10px;
    border-right: 1px solid var(--color-border, #dbe2ea);
    border-bottom: 1px solid var(--color-border, #dbe2ea);
    border-radius: 0;
    background: var(--color-surface, #ffffff);
    display: grid;
    gap: 10px;
    align-content: start;
}

.omo-calendar__cell:nth-child(7n) {
    border-right: 0;
}

.omo-calendar__cell:nth-last-child(-n + 7) {
    border-bottom: 0;
}

.omo-calendar__cell.is-outside {
    opacity: 0.58;
    background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 78%, var(--color-surface, #ffffff) 22%);
}

.omo-calendar__cell.is-today {
    border-color: color-mix(in srgb, var(--color-primary, #2563eb) 32%, var(--color-border, #dbe2ea));
    box-shadow: inset 0 0 0 2px color-mix(in srgb, var(--color-primary, #2563eb) 12%, transparent);
}

.omo-calendar__cell-head {
    display: flex;
    justify-content: flex-end;
}

.omo-calendar__cell-day {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    min-height: 32px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-text-light, #64748b) 14%, transparent);
    color: var(--color-text, #1f2937);
    font-weight: 700;
}

.omo-calendar__cell.is-today .omo-calendar__cell-day {
    background: var(--color-primary, #2563eb);
    color: var(--color-text-inverse, #ffffff);
}

.omo-calendar__cell-items {
    display: grid;
    gap: 8px;
}

.omo-calendar__event-chip {
    display: grid;
    gap: 4px;
    padding: 8px 9px;
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-primary, #2563eb) 9%, var(--color-surface, #ffffff));
    border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 16%, var(--color-border, #dbe2ea));
}

.omo-calendar__event-chip.is-status-draft,
.omo-calendar__item-shell.is-status-draft .omo-calendar__list-item {
    background: color-mix(in srgb, #f59e0b 9%, var(--color-surface, #ffffff));
    border-color: color-mix(in srgb, #f59e0b 22%, var(--color-border, #dbe2ea));
}

.omo-calendar__event-chip.is-status-cancelled,
.omo-calendar__item-shell.is-status-cancelled .omo-calendar__list-item {
    background: color-mix(in srgb, #ef4444 8%, var(--color-surface, #ffffff));
    border-color: color-mix(in srgb, #ef4444 20%, var(--color-border, #dbe2ea));
}

.omo-calendar__event-chip.is-faded,
.omo-calendar__time-all-day-chip.is-faded,
.omo-calendar__time-event.is-faded,
.omo-calendar__item-shell.is-faded .omo-calendar__list-item {
    opacity: 0.58;
    filter: saturate(0.72);
}

.omo-calendar__event-chip.is-route-target,
.omo-calendar__time-all-day-chip.is-route-target,
.omo-calendar__time-event.is-route-target,
.omo-calendar__item-shell.is-route-target .omo-calendar__list-item {
    scroll-margin-top: 96px;
    scroll-margin-bottom: 32px;
    border-color: color-mix(in srgb, var(--color-primary, #2563eb) 42%, var(--color-border, #dbe2ea));
    box-shadow:
        0 0 0 2px color-mix(in srgb, var(--color-primary, #2563eb) 16%, transparent),
        0 14px 28px -24px rgba(37, 99, 235, 0.55);
}

.omo-calendar__event-chip.is-route-target-active,
.omo-calendar__time-all-day-chip.is-route-target-active,
.omo-calendar__time-event.is-route-target-active,
.omo-calendar__item-shell.is-route-target-active .omo-calendar__list-item {
    animation: omo-calendar-route-target-pulse 1.35s ease-out 1;
}

@keyframes omo-calendar-route-target-pulse {
    0% {
        box-shadow:
            0 0 0 0 color-mix(in srgb, var(--color-primary, #2563eb) 22%, transparent),
            0 14px 28px -24px rgba(37, 99, 235, 0.28);
    }
    100% {
        box-shadow:
            0 0 0 14px rgba(37, 99, 235, 0),
            0 14px 28px -24px rgba(37, 99, 235, 0);
    }
}

.omo-calendar__event-time,
.omo-calendar__event-holon,
.omo-calendar__more {
    color: var(--color-text-light, #64748b);
    font-size: 0.78rem;
}

.omo-calendar__event-title {
    color: var(--color-text, #1f2937);
    font-size: 0.9rem;
    font-weight: 700;
    line-height: 1.35;
}

.omo-calendar__time-view {
    --omo-calendar-hour-height: 58px;
    --omo-calendar-time-axis-width: 76px;
    --omo-calendar-time-day-min-width: 180px;
    display: grid;
    flex: 1 1 auto;
    gap: 0;
    min-height: 0;
    max-height: none;
    overflow: auto;
    overscroll-behavior: contain;
    border: 1px solid color-mix(in srgb, var(--color-border, #dbe2ea) 82%, white 18%);
    border-radius: var(--radius-md);
    background: var(--color-surface, #ffffff);
}

.omo-calendar__time-view[data-omo-calendar-time-view="week"] {
    --omo-calendar-time-day-min-width: 124px;
}

.omo-calendar__time-view[data-omo-calendar-time-view="day"] {
    --omo-calendar-time-day-min-width: 220px;
}

.omo-calendar__time-sticky {
    position: sticky;
    top: 0;
    z-index: 12;
    background: var(--color-surface, #ffffff);
    box-shadow: 0 10px 18px -18px rgba(15, 23, 42, 0.4);
}

.omo-calendar__time-head,
.omo-calendar__time-all-day,
.omo-calendar__time-body {
    display: grid;
    grid-template-columns:
        var(--omo-calendar-time-axis-width)
        repeat(var(--omo-calendar-time-columns, 1), minmax(var(--omo-calendar-time-day-min-width), 1fr));
}

.omo-calendar__time-axis-spacer,
.omo-calendar__time-axis-label {
    padding: 12px 10px;
    border-right: 1px solid color-mix(in srgb, var(--color-border, #dbe2ea) 82%, white 18%);
    background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 86%, white 14%);
    color: var(--color-text-light, #64748b);
    font-size: 0.78rem;
    font-weight: 700;
}

.omo-calendar__time-day-header {
    padding: 12px 14px;
    border-right: 1px solid color-mix(in srgb, var(--color-border, #dbe2ea) 82%, white 18%);
    border-bottom: 1px solid color-mix(in srgb, var(--color-border, #dbe2ea) 82%, white 18%);
    background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 82%, white 18%);
    display: grid;
    gap: 4px;
}

.omo-calendar__time-day-header strong {
    color: var(--color-text, #1f2937);
    font-size: 0.92rem;
}

.omo-calendar__time-day-header span {
    color: var(--color-text-light, #64748b);
    font-size: 0.78rem;
}

.omo-calendar__time-day-header.is-today,
.omo-calendar__time-all-day-cell.is-today,
.omo-calendar__time-column.is-today {
    background: color-mix(in srgb, var(--color-primary, #2563eb) 7%, var(--color-surface, #ffffff));
}

.omo-calendar__time-all-day-cell {
    min-height: 56px;
    padding: 10px 8px;
    border-right: 1px solid color-mix(in srgb, var(--color-border, #dbe2ea) 82%, white 18%);
    border-bottom: 1px solid color-mix(in srgb, var(--color-border, #dbe2ea) 82%, white 18%);
    display: grid;
    gap: 8px;
    align-content: start;
    background: var(--color-surface, #ffffff);
}

.omo-calendar__time-empty {
    color: var(--color-text-light, #94a3b8);
    font-size: 0.76rem;
}

.omo-calendar__time-all-day-chip {
    display: grid;
    gap: 3px;
    padding: 8px 9px;
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-primary, #2563eb) 9%, var(--color-surface, #ffffff));
    border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 16%, var(--color-border, #dbe2ea));
}

.omo-calendar__time-all-day-chip strong {
    font-size: 0.82rem;
    line-height: 1.3;
}

.omo-calendar__time-all-day-chip span {
    color: var(--color-text-light, #64748b);
    font-size: 0.74rem;
}

.omo-calendar__time-body {
    align-items: start;
}

.omo-calendar__time-axis {
    position: relative;
    height: calc(var(--omo-calendar-hour-height) * 24);
    background:
        repeating-linear-gradient(
            to bottom,
            color-mix(in srgb, var(--color-border, #dbe2ea) 82%, white 18%) 0,
            color-mix(in srgb, var(--color-border, #dbe2ea) 82%, white 18%) 1px,
            transparent 1px,
            transparent var(--omo-calendar-hour-height)
        );
    border-right: 1px solid color-mix(in srgb, var(--color-border, #dbe2ea) 82%, white 18%);
}

.omo-calendar__time-hour-label {
    height: var(--omo-calendar-hour-height);
    padding: 0 10px;
    transform: translateY(-0.5em);
    color: var(--color-text-light, #64748b);
    font-size: 0.76rem;
    font-weight: 700;
}

.omo-calendar__time-column {
    position: relative;
    height: calc(var(--omo-calendar-hour-height) * 24);
    border-right: 1px solid color-mix(in srgb, var(--color-border, #dbe2ea) 82%, white 18%);
    background: var(--color-surface, #ffffff);
    overflow: hidden;
}

.omo-calendar__time-column-grid {
    position: absolute;
    inset: 0;
    background:
        repeating-linear-gradient(
            to bottom,
            color-mix(in srgb, var(--color-border, #dbe2ea) 82%, white 18%) 0,
            color-mix(in srgb, var(--color-border, #dbe2ea) 82%, white 18%) 1px,
            transparent 1px,
            transparent var(--omo-calendar-hour-height)
        );
    pointer-events: none;
}

.omo-calendar__time-event {
    position: absolute;
    display: grid;
    gap: 4px;
    padding: 7px 8px;
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-primary, #2563eb) 9%, var(--color-surface, #ffffff));
    border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 18%, var(--color-border, #dbe2ea));
    box-shadow: 0 8px 20px -18px rgba(15, 23, 42, 0.32);
    overflow: hidden;
}

.omo-calendar__time-event.is-status-draft,
.omo-calendar__time-all-day-chip.is-status-draft {
    background: color-mix(in srgb, #f59e0b 9%, var(--color-surface, #ffffff));
    border-color: color-mix(in srgb, #f59e0b 22%, var(--color-border, #dbe2ea));
}

.omo-calendar__time-event.is-status-cancelled,
.omo-calendar__time-all-day-chip.is-status-cancelled {
    background: color-mix(in srgb, #ef4444 8%, var(--color-surface, #ffffff));
    border-color: color-mix(in srgb, #ef4444 20%, var(--color-border, #dbe2ea));
}

.omo-calendar__time-event-time,
.omo-calendar__time-event-context {
    color: var(--color-text-light, #64748b);
    font-size: 0.72rem;
    line-height: 1.25;
}

.omo-calendar__time-event-title {
    color: var(--color-text, #1f2937);
    font-size: 0.82rem;
    line-height: 1.28;
}

.omo-calendar__results {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.omo-calendar__results.generic-file-list {
    --generic-file-list-columns: minmax(106px, 0.78fr) minmax(0, 2.4fr) minmax(150px, 1.1fr) minmax(140px, 1fr);
    --generic-file-list-title-gap: 18px;
    --generic-file-list-table-margin-inline: 12px;
    --generic-file-list-padding-inline-start: 16px;
    --generic-file-list-padding-inline-end: 18px;
    --generic-file-list-header-padding-block: 14px;
    --generic-file-list-row-padding-block: 12px;
    --generic-file-list-menu-space: 44px;
    display: grid;
}

.omo-calendar__results.generic-file-list .generic-file-list__group-title {
    padding: 15px 12px;
}

.omo-calendar__group {
    display: grid;
    gap: 12px;
    position: relative;
}

.omo-calendar__list {
    display: grid;
    gap: 0;
}

.omo-calendar__list-header {
    display: grid;
}

.omo-calendar__list-header-cell {
    min-width: 0;
}

.omo-calendar__item-shell {
    position: relative;
}

.omo-calendar__list-item {
    display: grid;
    align-items: center;
    transition: background-color 140ms ease;
}

.omo-calendar__list-cell {
    min-width: 0;
}

.omo-calendar__list-cell--name {
    align-items: flex-start;
}

.omo-calendar__list-date {
    display: flex;
    flex-direction: column;
    gap: 4px;
    align-items: flex-end;
    min-width: 0;
}

.omo-calendar__list-weekday {
    color: var(--color-text-light, #64748b);
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.omo-calendar__list-date strong {
    color: var(--color-text, #1f2937);
    font-size: 1rem;
    line-height: 1.2;
}

.omo-calendar__list-name-main {
    display: flex;
    align-items: flex-start;
    gap: 0;
    min-width: 0;
}

.omo-calendar__list-title-block {
    display: grid;
    gap: 6px;
    min-width: 0;
}

.omo-calendar__list-title-row {
    display: flex;
    align-items: center;
    gap: 8px 10px;
    min-width: 0;
    flex-wrap: wrap;
}

.omo-calendar__list-time,
.omo-calendar__list-holon {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 82%, white 18%);
}

.omo-calendar__list-time {
    font-weight: 600;
}

.omo-calendar__list-status {
    white-space: nowrap;
}

.omo-calendar__list-context {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.omo-calendar__list-title {
    color: var(--color-text, #1f2937);
    font-size: 0.98rem;
}

.omo-calendar__list-description {
    color: var(--color-text-light, #64748b);
    line-height: 1.55;
}

.omo-calendar__editor-drawer .omo-overlay-drawer__body {
    padding: 0;
}

.omo-calendar__drawer-custom-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

@media (max-width: 920px) {
    .omo-calendar__weekday-row,
    .omo-calendar__grid {
        gap: 8px;
    }

    .omo-calendar__cell {
        min-height: 122px;
        padding: 8px;
    }

    .omo-calendar__time-view {
        --omo-calendar-time-axis-width: 62px;
        --omo-calendar-time-day-min-width: 150px;
    }

    .omo-calendar__time-view[data-omo-calendar-time-view="week"] {
        --omo-calendar-time-day-min-width: 112px;
    }

}

@media (max-width: 768px) {
    .omo-calendar__header-main,
    .omo-calendar__header-secondary {
        grid-template-columns: 1fr;
    }

    .omo-calendar__header-actions {
        justify-content: flex-start;
    }

    .omo-calendar__view-switch {
        justify-self: start;
    }

    .omo-calendar__header-actions,
    .omo-calendar__today-actions {
        width: 100%;
    }

    .omo-calendar__new-button,
    .omo-calendar__today-actions .generic-action-button {
        justify-self: stretch;
    }

    .omo-calendar__today-actions .generic-action-button {
        width: 100%;
    }

    .omo-calendar__weekday-row {
        display: none;
    }

    .omo-calendar__grid {
        grid-template-columns: 1fr;
    }

    .omo-calendar__cell {
        min-height: 0;
    }

    .omo-calendar__cell-head {
        justify-content: flex-start;
    }

    .omo-calendar__list-item {
        align-items: start;
    }

    .omo-calendar__list-date {
        align-items: flex-start;
    }

    .omo-calendar__results.generic-file-list {
        --generic-file-list-table-margin-inline: 0px;
    }

    .omo-calendar__time-view {
        --omo-calendar-time-axis-width: 56px;
        --omo-calendar-time-day-min-width: 160px;
    }

    .omo-calendar__toolbar {
        grid-template-columns: 16px minmax(0, 1fr) 16px;
        gap: 6px;
        align-items: stretch;
    }

    .omo-calendar__toolbar > .generic-action-button {
        min-width: 16px;
        width: 16px;
        min-height: 100%;
        padding: 0;
        border-radius: var(--radius-md);
        font-size: 14px;
        line-height: 1;
        overflow: hidden;
    }

    .omo-calendar__time-view[data-omo-calendar-time-view="week"] {
        --omo-calendar-time-day-min-width: 96px;
    }

    .omo-calendar__panel--timeline .omo-calendar__toolbar {
        z-index: 18;
    }

    .omo-calendar__time-day-header {
        padding: 10px 8px;
    }

    .omo-calendar__time-day-header strong {
        font-size: 0.84rem;
    }

    .omo-calendar__time-day-header span,
    .omo-calendar__time-hour-label,
    .omo-calendar__time-axis-label {
        font-size: 0.72rem;
    }

}

@media (max-width: 1024px) {
    .omo-calendar__header {
        position: sticky;
    }

    .omo-calendar__new-button.omo-mobile-corner-action {
        border-radius: 0 0 0 var(--radius-md) !important;
    }

    .omo-calendar__header-secondary {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: flex-start;
        gap: 10px;
    }

    .omo-calendar__view-switch {
        justify-self: end;
    }
}

@media (max-height: 560px) {
    .omo-calendar__month-sticky,
    .omo-calendar__panel--timeline .omo-calendar__toolbar,
    .omo-calendar__time-sticky {
        position: static;
        top: auto;
    }
}
</style>
