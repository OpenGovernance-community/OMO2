<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/calendar/upcoming_sections.php';
require_once dirname(__DIR__, 3) . '/common/external_calendar.php';
require_once __DIR__ . '/permissions_shared.php';

use dbObject\ArrayEvent;
use dbObject\ArrayExternalCalendarEvent;
use dbObject\ExternalCalendar;
use dbObject\ExternalCalendarEvent;
use dbObject\ArrayDocument;
use dbObject\Document;
use dbObject\Event;
use dbObject\Holon;
use dbObject\Organization;

$sourceLang = [
    'calendar.action.meeting_hint' => ['text' => 'Choisir vos horaires et votre agenda de reservation.', 'context' => 'Help below the calendar meeting menu action.'],
    'calendar.action.share_hint' => ['text' => 'Creer et gerer vos liens d abonnement.', 'context' => 'Help below the calendar share menu action.'],
    'calendar.action.connect_hint' => ['text' => 'Synchroniser OMO ou ajouter un agenda externe.', 'context' => 'Help below the calendar connect menu action.'],
    'calendar.page.title' => [
        'text' => 'Calendrier',
        'context' => 'Main title of the calendar application.',
    ],
    'calendar.page.description' => [
        'text' => 'Visualisez les événements de votre organisation et ajoutez-en de nouveaux.',
        'context' => 'Introductory text shown in the calendar application.',
    ],
    'calendar.scope.contextual' => [
        'text' => 'Local',
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
        'text' => 'Nouvel événement',
        'context' => 'Primary button used to open the event creation drawer.',
    ],
    'calendar.action.today' => [
        'text' => "Aujourd'hui",
        'context' => 'Button used to return to the current month in the calendar application.',
    ],
    'calendar.navigation.previous' => [
        'text' => 'Période précédente',
        'context' => 'Accessible label for the previous period button in timeline calendar views.',
    ],
    'calendar.navigation.next' => [
        'text' => 'Période suivante',
        'context' => 'Accessible label for the next period button in timeline calendar views.',
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
    'calendar.action.open_document' => [
        'text' => 'Ouvrir le document associe',
        'context' => 'Accessible label for the document icon shown next to a calendar event.',
    ],
    'calendar.action.connect' => [
        'text' => 'Connecter',
        'context' => 'Action opening the CalDAV connection popup for the current calendar scope.',
    ],
    'calendar.action.meeting' => [
        'text' => 'Prise de rendez-vous',
        'context' => 'Open personal public booking settings.',
    ],
    'calendar.action.share' => [
        'text' => 'Partager',
        'context' => 'Manage personal calendar subscription links across all organizations and external calendars.',
    ],
    'calendar.confirm.delete' => [
        'text' => 'Supprimer cet événement ?',
        'context' => 'Confirmation shown before deleting an event from the compact menu.',
    ],
    'calendar.error.delete' => [
        'text' => 'Impossible de supprimer cet événement.',
        'context' => 'Fallback error shown when deleting an event from the compact menu fails.',
    ],
    'calendar.delete.documents.title' => [
        'text' => 'Documents associés',
        'context' => 'Title of the choice dialog shown before deleting documents linked to an event.',
    ],
    'calendar.delete.documents.question' => [
        'text' => 'Voulez-vous supprimer les documents associés ?',
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
    'calendar.filters.aria' => [
        'text' => 'Filtres du calendrier',
        'context' => 'Accessible label for the compact calendar filters control.',
    ],
    'calendar.filters.scope' => [
        'text' => 'Contexte',
        'context' => 'Heading for calendar scope choices in the filters panel.',
    ],
    'calendar.filters.view' => [
        'text' => 'Représentation',
        'context' => 'Heading for calendar representation choices in the filters panel.',
    ],
    'calendar.filters.apply' => [
        'text' => 'Appliquer',
        'context' => 'Button applying temporary calendar filter choices.',
    ],
    'calendar.filters.save_view' => [
        'text' => 'Enregistrer la vue',
        'context' => 'Button saving calendar filter choices for the current context.',
    ],
    'calendar.filters.more_actions' => [
        'text' => 'Autres options de vue',
        'context' => 'Accessible label for additional calendar view preference actions.',
    ],
    'calendar.filters.apply_everywhere' => [
        'text' => 'Appliquer partout',
        'context' => 'Action setting the current calendar view as the default and clearing specific views.',
    ],
    'calendar.filters.set_default' => [
        'text' => 'Définir comme vue par défaut',
        'context' => 'Action saving the current calendar view as the default view.',
    ],
    'calendar.filters.restore_default' => [
        'text' => 'Restaurer la vue par défaut',
        'context' => 'Action removing the current holon specific calendar view.',
    ],
    'calendar.search.aria' => [
        'text' => 'Filtrer les événements affichés',
        'context' => 'Accessible label for the calendar quick search.',
    ],
    'calendar.search.placeholder' => [
        'text' => 'Filtrer les événements',
        'context' => 'Placeholder for the calendar quick search.',
    ],
    'calendar.search.empty' => [
        'text' => 'Aucun événement ne correspond à cette recherche.',
        'context' => 'Empty state when the calendar quick search has no result.',
    ],
    'calendar.axis.all_day' => [
        'text' => 'Journée',
        'context' => 'Label used for the all-day row in week and day views.',
    ],
    'calendar.axis.now' => [
        'text' => 'Maintenant',
        'context' => 'Accessible label for the current-time indicator in week and day views.',
    ],
    'calendar.drawer.title' => [
        'text' => 'Événement',
        'context' => 'Title of the internal drawer used to inspect or edit an event from the calendar application.',
    ],
    'calendar.drawer.description' => [
        'text' => 'Consultez les détails puis modifiez si besoin.',
        'context' => 'Description shown in the internal event drawer.',
    ],
    'calendar.external_drawer.title' => [
        'text' => 'Événement importé',
        'context' => 'Title of the read-only drawer for an event from a connected calendar.',
    ],
    'calendar.external_drawer.description' => [
        'text' => 'Consultation en lecture seule depuis un calendrier connecté.',
        'context' => 'Description of the read-only drawer for an event from a connected calendar.',
    ],
    'calendar.external_drawer.calendar' => [
        'text' => 'Agenda externe',
        'context' => 'Label of the source calendar in an imported event drawer.',
    ],
    'calendar.external_drawer.schedule' => [
        'text' => 'Horaire',
        'context' => 'Label of the schedule in an imported event drawer.',
    ],
    'calendar.external_drawer.location' => [
        'text' => 'Lieu',
        'context' => 'Label of the location in an imported event drawer.',
    ],
    'calendar.external_drawer.description_label' => [
        'text' => 'Description',
        'context' => 'Label of the description in an imported event drawer.',
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
        'one' => '{count} événement ce mois',
        'other' => '{count} événements ce mois',
        'context' => 'Summary badge for the monthly calendar view.',
    ],
    'calendar.summary.week' => [
        'one' => '{count} événement cette semaine',
        'other' => '{count} événements cette semaine',
        'context' => 'Summary badge for the weekly calendar view.',
    ],
    'calendar.summary.day' => [
        'one' => '{count} événement ce jour',
        'other' => '{count} événements ce jour',
        'context' => 'Summary badge for the daily calendar view.',
    ],
    'calendar.summary.list' => [
        'one' => '{count} événement à venir',
        'other' => '{count} événements à venir',
        'context' => 'Summary badge for the upcoming list view.',
    ],
    'calendar.summary.day_column' => [
        'one' => '{count} événement',
        'other' => '{count} événements',
        'context' => 'Summary shown below a day title in the timeline view.',
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

function omoCalendarFormatTimelineDayLabel(\DateTimeInterface $date)
{
    return omoCalendarFormatWeekdayLabel($date) . ' ' . $date->format('j');
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
        return 'Journée';
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
            return 'Journée';
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
    $section = omoCalendarGetUpcomingSectionMetadata($anchorDate, $todayStart);
    $labels = [
        'today' => omoCalendarT('calendar.section.today'),
        'tomorrow' => omoCalendarT('calendar.section.tomorrow'),
        'this_week' => omoCalendarT('calendar.section.this_week'),
        'next_week' => omoCalendarT('calendar.section.next_week'),
        'this_month' => omoCalendarT('calendar.section.this_month'),
        'next_month' => omoCalendarT('calendar.section.next_month'),
    ];
    $section['label'] = isset($section['month']) && $section['month'] instanceof \DateTimeInterface
        ? omoCalendarFormatMonthLabel($section['month'])
        : (string)($labels[$section['key']] ?? '');
    return $section;
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
    <div class="omo-calendar omo-empty-state">Accès refusé à cette organisation.</div>
    <?php
    exit;
}

$rootHolon = $organization->getEnabledStructuralRootHolon();
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$openedEvent = null;
$openedEventHolonId = 0;
if ($openEventId > 0) {
    $candidateEvent = new Event();
    if (
        $candidateEvent->load($openEventId)
        && (int)$candidateEvent->get('IDorganization') === $organizationId
        && (int)$candidateEvent->get('active') === 1
        && Event::normalizeStatus($candidateEvent->get('status')) !== Event::STATUS_CANCELLED
        && $candidateEvent->isDraftVisibleToViewer($currentUserId)
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

$applicationViewPreferences = omoApplicationViewPreferencesGetContext('calendar', $organization, $currentHolon, $currentUserId);
commonReleaseReadOnlySession();
\dbObject\DbObject::enableReadOnlyMemoization();
$browserRestore = omoApplicationViewPreferencesGetBrowserRestore($applicationViewPreferences);
if ($openEventId === 0 && !isset($_GET['date']) && !isset($_GET['month'])) {
    $restoredDate = omoCalendarParseDate($browserRestore['position']['date'] ?? '');
    $restoredMonth = omoCalendarParseMonth($browserRestore['position']['month'] ?? '');
    if ($restoredDate || $restoredMonth) {
        $anchorDate = ($restoredDate ?: $restoredMonth)->setTime(0, 0, 0);
        $monthStart = $anchorDate->modify('first day of this month');
    }
}

$viewMode = omoCalendarParseView(
    $_GET['view'] ?? $browserRestore['view']['view']
        ?? omoApplicationViewPreferencesGetInitialValue($applicationViewPreferences, 'view', 'view', 'month')
);
$requestedScopeRaw = trim((string)($_GET['scope'] ?? $browserRestore['view']['scope'] ?? omoApplicationViewPreferencesGetInitialValue(
    $applicationViewPreferences,
    'scope',
    'scope',
    ''
)));

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
$calendarEarliestEventEndAt = $gridStart <= $todayStart ? $gridStart : $todayStart;
$events->loadForOrganizationDateRange($organizationId, $calendarEarliestEventEndAt, null, false, true);
$externalEventMetaByVirtualId = [];
if (ExternalCalendar::isStorageAvailable()) {
    $externalEvents = new ArrayExternalCalendarEvent();
    $externalEvents->loadActiveForUserDateRange($currentUserId, $calendarEarliestEventEndAt, new \DateTimeImmutable('+400 days'));
    $virtualEventId = 1000000000;
    foreach ($externalEvents as $externalEvent) {
        if (!($externalEvent instanceof ExternalCalendarEvent)) {
            continue;
        }
        $externalCalendar = new ExternalCalendar();
        if (!$externalCalendar->load((int)$externalEvent->get('IDexternalcalendar'))) {
            continue;
        }
        $virtualEvent = new Event();
        // This display-only event has no database row to load lazily.
        $virtualEvent->hydrateFromDatabaseRow(['id' => $virtualEventId], true);
        unset(\dbObject\DbObject::$preload[Event::tableName() . '_' . $virtualEventId]);
        foreach (['title', 'description', 'start_at', 'end_at', 'is_all_day'] as $field) {
            $virtualEvent->set($field, $externalEvent->get($field));
        }
        $virtualEvent->set('IDorganization', $organizationId);
        $virtualEvent->set('IDuser', $currentUserId);
        $virtualEvent->set('status', Event::STATUS_CONFIRMED);
        $virtualEvent->set('active', 1);
        $externalEventMetaByVirtualId[$virtualEventId] = [
            'title' => trim((string)$externalCalendar->get('title')),
            'color' => ExternalCalendar::normalizeColor($externalCalendar->get('color')),
            'location' => trim((string)$externalEvent->get('location')),
        ];
        $events[] = $virtualEvent;
        $virtualEventId++;
    }
}
$eventIds = [];
foreach ($events as $event) {
    if ($event instanceof Event && (int)$event->getId() > 0 && $event->isDraftVisibleToViewer($currentUserId)) {
        $eventIds[] = (int)$event->getId();
    }
}
$associatedDocumentsByEventId = [];
if ($eventIds !== []) {
    $associatedDocuments = new ArrayDocument();
    $associatedDocuments->load([
        'where' => [
            ['field' => 'IDorganization', 'value' => $organizationId],
            ['field' => 'IDevent', 'op' => 'in', 'value' => array_values(array_unique($eventIds))],
        ],
        'orderBy' => [
            ['field' => 'id', 'dir' => 'ASC'],
        ],
        'hydrate' => true,
    ]);
    foreach ($associatedDocuments as $document) {
        if (!($document instanceof Document) || (int)$document->getId() <= 0) {
            continue;
        }

        $documentEventId = (int)$document->get('IDevent');
        if ($documentEventId > 0) {
            $associatedDocumentsByEventId[$documentEventId][] = $document;
        }
    }
}
$openEventTargetId = $openedEvent instanceof Event ? (int)$openedEvent->getId() : 0;

$holonLabelsById = [];
$dayBucketsByScope = [];
$viewCountsByScope = [];
$upcomingSectionsByScope = [];
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

$buildTimelineDays = static function (\DateTimeImmutable $rangeStart, int $dayCount) use ($todayDayKey) {
    $days = [];
    $cursor = $rangeStart;
    for ($index = 0; $index < $dayCount; $index += 1) {
        $dayKey = $cursor->format('Y-m-d');
        $days[$dayKey] = [
            'date' => $cursor,
            'dayKey' => $dayKey,
            'label' => omoCalendarFormatTimelineDayLabel($cursor),
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
    $timelineViewsByScope[$scopeKey] = [
        'week' => $buildTimelineDays($weekStart, 7),
        'day' => $buildTimelineDays($dayStart, 1),
    ];
}

foreach ($events as $event) {
    if (
        !($event instanceof Event)
        || (int)$event->getId() <= 0
        || !$event->isDraftVisibleToViewer($currentUserId)
    ) {
        continue;
    }

    $startAt = $event->get('start_at');
    $endAt = $event->get('end_at');
    if (!($startAt instanceof \DateTimeInterface) || !($endAt instanceof \DateTimeInterface)) {
        continue;
    }

    $eventId = (int)$event->getId();
    $externalMeta = $externalEventMetaByVirtualId[$eventId] ?? null;
    $isExternalEvent = is_array($externalMeta);
    $eventTitle = trim((string)$event->get('title')) !== '' ? trim((string)$event->get('title')) : ('Événement #' . $eventId);
    $eventDescription = trim((string)$event->get('description'));
    $eventStatus = Event::normalizeStatus($event->get('status'));
    $eventStatusLabel = trim((string)($eventStatusCatalog[$eventStatus]['label'] ?? ''));
    $eventHolonId = (int)$event->get('IDholon');
    $eventHolonLabel = $isExternalEvent ? (string)($externalMeta['title'] ?? '') : $resolveHolonLabel($eventHolonId);
    $externalDrawerData = $isExternalEvent ? [
        'title' => $eventTitle,
        'description' => $eventDescription,
        'schedule' => omoCalendarFormatUpcomingRangeLabel($event),
        'calendar' => $eventHolonLabel,
        'location' => trim((string)($externalMeta['location'] ?? '')),
        'color' => (string)($externalMeta['color'] ?? ''),
    ] : [];
    $isAllDay = (bool)$event->get('is_all_day');
    $isInCurrentContext = !$canToggleScope || $eventHolonId === 0 || $eventHolonId === $currentHolonId;
    $isInDirectChildContext = $isInCurrentContext || ($eventHolonId > 0 && isset($directChildHolonIdMap[$eventHolonId]));
    $isInDescendantContext = $isInCurrentContext || ($eventHolonId > 0 && isset($descendantHolonIdMap[$eventHolonId]));
    $isPersonallyRelevant = $isExternalEvent || $event->isPersonallyRelevantToViewer($currentUserId, $organizationId);
    $isInvitedOrOwner = $currentUserId > 0 && !$isExternalEvent && (
        (int)$event->get('IDuser') === $currentUserId
        || $event->isVisibleToInvitationViewer($currentUserId, $organizationId)
    );
    $canEditEvent = !$isExternalEvent && omoCalendarCanEditEvent($event, $organizationId, $currentUserId, $rootHolon, false);
    $deletePermissionHolon = $rootHolon;
    if ($eventHolonId > 0) {
        $eventPermissionHolon = new Holon();
        if ($eventPermissionHolon->load($eventHolonId)) {
            $deletePermissionHolon = $eventPermissionHolon;
        }
    }
    $canDeleteEvent = !$isExternalEvent && $currentUserId > 0
        && (
            $deletePermissionHolon instanceof Holon
                ? $deletePermissionHolon->isAllowed('CAN_DELETE_EVENT', false, $currentUserId)
                : commonCurrentUserHasOrganizationAccess($organizationId)
        );
    $eventHasAssociatedDocuments = $canDeleteEvent && count($event->getAssociatedDocuments()) > 0;
    $associatedDocumentOpenData = omoCalendarBuildAssociatedDocumentOpenData(
        $event,
        $associatedDocumentsByEventId[$eventId] ?? [],
        $currentUserId,
        $organizationId,
        $eventHolonId > 0 ? $eventHolonId : $currentHolonId
    );
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
        $isTimelineOnlyInvitation = !$includeEvent && $isInvitedOrOwner;
        if (!$includeEvent && !$isTimelineOnlyInvitation) {
            continue;
        }

        $isFadedInScope = !$isTimelineOnlyInvitation && !$isPersonallyRelevant;

        if (!$isTimelineOnlyInvitation && $startAt <= $monthEnd && $endAt >= $monthStart) {
            $viewCountsByScope[$scopeKey]['month'] += 1;
        }

        $cursor = new \DateTimeImmutable(max($startAt->format('Y-m-d 00:00:00'), $gridStart->format('Y-m-d 00:00:00')));
        $cursorEnd = new \DateTimeImmutable(min($endAt->format('Y-m-d 00:00:00'), $gridEnd->format('Y-m-d 00:00:00')));

        while (!$isTimelineOnlyInvitation && $cursor <= $cursorEnd) {
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
                'documentUrl' => $associatedDocumentOpenData['url'],
                'documentTitle' => $associatedDocumentOpenData['title'],
                'documentPvEditorUrl' => $associatedDocumentOpenData['pvEditorUrl'],
                'isExternal' => $isExternalEvent,
                'externalDrawerData' => $externalDrawerData,
                'externalColor' => (string)($externalMeta['color'] ?? ''),
            ];

            $cursor = $cursor->modify('+1 day');
        }

        if (!$isTimelineOnlyInvitation && !$isExternalEvent && $endAt >= $todayStart) {
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
                'documentUrl' => $associatedDocumentOpenData['url'],
                'documentTitle' => $associatedDocumentOpenData['title'],
                'documentPvEditorUrl' => $associatedDocumentOpenData['pvEditorUrl'],
                'sort' => (int)$startAt->format('U'),
                'isFaded' => $isFadedInScope,
                'isRouteTarget' => $openEventTargetId > 0 && $eventId === $openEventTargetId,
                'isExternal' => $isExternalEvent,
                'externalDrawerData' => $externalDrawerData,
                'externalColor' => (string)($externalMeta['color'] ?? ''),
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
                    'documentUrl' => $associatedDocumentOpenData['url'],
                    'documentTitle' => $associatedDocumentOpenData['title'],
                    'documentPvEditorUrl' => $associatedDocumentOpenData['pvEditorUrl'],
                    'isExternal' => $isExternalEvent,
                    'externalDrawerData' => $externalDrawerData,
                    'externalColor' => (string)($externalMeta['color'] ?? ''),
                    'isOutsideScope' => $isTimelineOnlyInvitation,
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
                'documentUrl' => $associatedDocumentOpenData['url'],
                'documentTitle' => $associatedDocumentOpenData['title'],
                'documentPvEditorUrl' => $associatedDocumentOpenData['pvEditorUrl'],
                'isExternal' => $isExternalEvent,
                'externalDrawerData' => $externalDrawerData,
                'externalColor' => (string)($externalMeta['color'] ?? ''),
                'isOutsideScope' => $isTimelineOnlyInvitation,
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
                    'documentUrl' => $associatedDocumentOpenData['url'],
                    'documentTitle' => $associatedDocumentOpenData['title'],
                    'documentPvEditorUrl' => $associatedDocumentOpenData['pvEditorUrl'],
                    'isExternal' => $isExternalEvent,
                    'externalDrawerData' => $externalDrawerData,
                    'externalColor' => (string)($externalMeta['color'] ?? ''),
                    'isOutsideScope' => $isTimelineOnlyInvitation,
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
                'documentUrl' => $associatedDocumentOpenData['url'],
                'documentTitle' => $associatedDocumentOpenData['title'],
                'documentPvEditorUrl' => $associatedDocumentOpenData['pvEditorUrl'],
                'isExternal' => $isExternalEvent,
                'externalDrawerData' => $externalDrawerData,
                'externalColor' => (string)($externalMeta['color'] ?? ''),
                'isOutsideScope' => $isTimelineOnlyInvitation,
            ];
        }
        unset($timelineDay);
    }
}

// Personal cross-organization availability belongs only in the week/day timelines.
$otherOrganizationBlocks = ArrayEvent::otherOrganizationBusyBlocks($currentUserId, $organizationId, $weekStart, $weekEnd->modify('+1 second'));
foreach ($otherOrganizationBlocks as $blockIndex => $block) {
    foreach ($calendarScopes as $scopeKey) {
        foreach (['week', 'day'] as $timelineKey) {
            $hasBlock = false;
            foreach ($timelineViewsByScope[$scopeKey][$timelineKey] as &$timelineDay) {
                $dayBegin = $timelineDay['date']->setTime(0, 0);
                $dayEndExclusive = $dayBegin->modify('+1 day');
                $segmentStart = max($block['start'], $dayBegin);
                $segmentEnd = min($block['end'], $dayEndExclusive);
                if ($segmentEnd <= $segmentStart) { continue; }
                $hasBlock = true;
                $item = [
                    'id' => 2000000000 + $blockIndex, 'title' => $block['title'],
                    'description' => '', 'timeLabel' => '', 'status' => Event::STATUS_CONFIRMED,
                    'statusLabel' => '', 'holonLabel' => '', 'isFaded' => false, 'isRouteTarget' => false,
                    'documentUrl' => '', 'documentTitle' => '', 'documentPvEditorUrl' => '',
                    'isExternal' => false, 'externalColor' => '', 'isOtherOrganization' => true,
                ];
                if ($block['allDay']) {
                    $timelineDay['allDay'][] = $item;
                } else {
                    $item['startMinute'] = (int)$segmentStart->format('H') * 60 + (int)$segmentStart->format('i');
                    $item['endMinute'] = $segmentEnd == $dayEndExclusive ? 1440 : (int)$segmentEnd->format('H') * 60 + (int)$segmentEnd->format('i');
                    $timelineDay['timed'][] = $item;
                }
            }
            unset($timelineDay);
            if ($hasBlock) { $viewCountsByScope[$scopeKey][$timelineKey]++; }
        }
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
$connectUrl = '/omo/api/calendar/connect.php?oid=' . rawurlencode((string)$organizationId)
    . '&cid=' . rawurlencode((string)($currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0));
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
            'count' => (int)$scopeCounts['week'],
            'days' => $timelineViewsByScope[$scopeKey]['week'],
            'columnCount' => count($timelineViewsByScope[$scopeKey]['week']),
            'prevUrl' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $prevWeekDate->modify('first day of this month'), 'week', $prevWeekDate, $scopeKey),
            'nextUrl' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $nextWeekDate->modify('first day of this month'), 'week', $nextWeekDate, $scopeKey),
            'todayUrl' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $todayDate->modify('first day of this month'), 'week', $todayDate, $scopeKey),
        ],
        'day' => [
            'title' => omoCalendarFormatDayLabelWithYear($dayStart),
            'subtitle' => (string)$viewSummariesByScope[$scopeKey]['day'],
            'count' => (int)$scopeCounts['day'],
            'days' => $timelineViewsByScope[$scopeKey]['day'],
            'columnCount' => count($timelineViewsByScope[$scopeKey]['day']),
            'prevUrl' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $prevDayDate->modify('first day of this month'), 'day', $prevDayDate, $scopeKey),
            'nextUrl' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $nextDayDate->modify('first day of this month'), 'day', $nextDayDate, $scopeKey),
            'todayUrl' => omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $todayDate->modify('first day of this month'), 'day', $todayDate, $scopeKey),
        ],
    ];
}

$calendarClientViews = [];
$calendarClientItems = [];
$calendarClientItemIds = [];
$packCalendarItems = static function (array $items) use (&$calendarClientItems, &$calendarClientItemIds): array {
    return array_map(static function (array $item) use (&$calendarClientItems, &$calendarClientItemIds): int {
        $key = json_encode($item, JSON_INVALID_UTF8_SUBSTITUTE);
        if (!isset($calendarClientItemIds[$key])) {
            $calendarClientItemIds[$key] = count($calendarClientItems);
            $calendarClientItems[] = $item;
        }
        return $calendarClientItemIds[$key];
    }, array_values($items));
};
foreach ($calendarScopes as $scopeKey) {
    $monthDays = [];
    foreach ($days as $day) {
        $dayKey = $day->format('Y-m-d');
        $items = $dayBucketsByScope[$scopeKey][$dayKey] ?? [];
        $monthDays[] = [
            'dayKey' => $dayKey, 'label' => $day->format('j'),
            'outside' => $day->format('Y-m') !== $monthStart->format('Y-m'),
            'isToday' => $dayKey === $todayDayKey,
            'items' => $packCalendarItems($items),
            'more' => count($items) > 3 ? omoCalendarT('calendar.day.more', ['count' => (string)(count($items) - 3)]) : '',
        ];
    }
    $calendarClientViews[$scopeKey]['month'] = [
        'title' => omoCalendarFormatMonthLabel($monthStart),
        'subtitle' => $viewSummariesByScope[$scopeKey]['month'],
        'prevUrl' => omoCalendarBuildUrl($organizationId, $currentHolonId, $prevMonth, 'month', $prevMonth, $scopeKey),
        'nextUrl' => omoCalendarBuildUrl($organizationId, $currentHolonId, $nextMonth, 'month', $nextMonth, $scopeKey),
        'days' => $monthDays,
    ];
    foreach ($timelineViewsByScopeConfig[$scopeKey] as $viewKey => $timeline) {
        $timeline['days'] = array_values($timeline['days']);
        foreach ($timeline['days'] as &$timelineDay) {
            unset($timelineDay['date']);
            $dayCount = count($timelineDay['allDay']) + count($timelineDay['timed']);
            $timelineDay['count'] = $dayCount;
            $timelineDay['countLabel'] = omoCalendarT('calendar.summary.day_column', ['count' => (string)$dayCount]);
            $timelineDay['allDay'] = $packCalendarItems($timelineDay['allDay']);
            $timelineDay['timed'] = $packCalendarItems($timelineDay['timed']);
        }
        unset($timelineDay);
        $calendarClientViews[$scopeKey][$viewKey] = $timeline;
    }
    $sections = [];
    foreach ($upcomingSectionsByScope[$scopeKey] as $section) {
        $sections[] = ['label' => $section['label'], 'items' => $packCalendarItems($section['items'])];
    }
    $calendarClientViews[$scopeKey]['list'] = ['sections' => $sections];
}
$calendarClientLabels = [];
foreach ([
    'calendar.navigation.previous', 'calendar.navigation.next', 'calendar.action.open_document',
    'calendar.axis.all_day', 'calendar.axis.now', 'calendar.empty.list', 'calendar.context.organization',
    'calendar.list.column.date', 'calendar.list.column.event', 'calendar.list.column.schedule', 'calendar.list.column.context',
    'calendar.action.more', 'calendar.action.edit', 'calendar.action.delete', 'calendar.confirm.delete', 'calendar.error.delete',
    'calendar.delete.documents.title', 'calendar.delete.documents.question', 'calendar.delete.documents.yes', 'calendar.delete.documents.no',
    'calendar.action.connect', 'calendar.action.meeting', 'calendar.action.share', 'calendar.error.load_form', 'calendar.loading',
] as $key) {
    $calendarClientLabels[$key] = omoCalendarT($key);
}
$calendarClientData = [
    'views' => $calendarClientViews, 'items' => $calendarClientItems, 'labels' => $calendarClientLabels,
    'weekdays' => array_map('omoCalendarT', $weekdayKeys), 'hours' => $timelineHours,
    'connectUrl' => $connectUrl,
    'externalEventDrawerText' => [
            'title' => omoCalendarT('calendar.external_drawer.title'),
            'description' => omoCalendarT('calendar.external_drawer.description'),
            'calendar' => omoCalendarT('calendar.external_drawer.calendar'),
            'schedule' => omoCalendarT('calendar.external_drawer.schedule'),
            'location' => omoCalendarT('calendar.external_drawer.location'),
            'descriptionLabel' => omoCalendarT('calendar.external_drawer.description_label'),
    ],
];
$headerCount = (int)($viewCountsByScope[$calendarScope][$viewMode] ?? 0);
$headerSummary = (string)($viewSummariesByScope[$calendarScope][$viewMode] ?? '');
?>
<link rel="stylesheet" href="/omo/api/calendar/calendar.css?v=20260917-calendar-new-event">
<link rel="stylesheet" href="/common/view-filter/view-filter.css?v=20260902-save-menu">
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
    data-omo-calendar-timezone="<?= omoApiEscape(date_default_timezone_get()) ?>"
    data-omo-calendar-oid="<?= (int)$organizationId ?>"
    data-omo-calendar-cid="<?= $currentHolon ? (int)$currentHolon->getId() : 0 ?>"
    data-omo-app-view-preferences="<?= omoApiEscape(json_encode($applicationViewPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    data-omo-view-filter-pending="1"
    aria-busy="true"
    data-omo-calendar-open-event-id="<?= (int)$openEventTargetId ?>"
>
    <div class="omo-calendar__header omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-calendar__header-main omo-panel-view__header-main">
            <div class="omo-calendar__title-cluster">
                <span class="omo-panel-view__app-icon omo-calendar__app-icon" aria-hidden="true">
                    <img src="images/tools/calendar.png" alt="">
                </span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-calendar__title-row generic-title-row generic-title-row--center">
                        <h2 class="omo-panel-view__title"><?= omoApiEscape(omoCalendarT('calendar.page.title')) ?></h2>
                        <span class="omo-calendar__count omo-panel-view__count" data-omo-calendar-header-count><?= omoApiEscape((string)$headerCount) ?></span>
                    </div>
                </div>
            </div>
            <div class="omo-calendar__header-actions" data-omo-header-actions>
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
                <div class="generic-menu generic-menu--expanded-mobile omo-calendar__header-menu" data-omo-calendar-header-menu>
                    <button
                        type="button"
                        class="generic-menu-toggle omo-calendar__header-menu-toggle"
                        data-omo-calendar-header-menu-toggle
                        aria-expanded="false"
                        aria-haspopup="menu"
                        aria-label="<?= omoApiEscape(omoCalendarT('calendar.action.more')) ?>"
                        title="<?= omoApiEscape(omoCalendarT('calendar.action.more')) ?>"
                    >&#8942;</button>
                    <div class="generic-menu-panel generic-menu-panel--descriptive omo-calendar__header-menu-panel" data-omo-calendar-header-menu-panel role="menu" hidden>
                        <button type="button" class="generic-menu-item generic-menu-item--descriptive" data-omo-calendar-open-connect role="menuitem"><strong><?= omoApiEscape(omoCalendarT('calendar.action.connect')) ?></strong><span class="generic-menu-item__description"><?= omoApiEscape(omoCalendarT('calendar.action.connect_hint')) ?></span></button>
                        <button type="button" class="generic-menu-item generic-menu-item--descriptive" data-omo-calendar-open-share role="menuitem"><strong><?= omoApiEscape(omoCalendarT('calendar.action.share')) ?></strong><span class="generic-menu-item__description"><?= omoApiEscape(omoCalendarT('calendar.action.share_hint')) ?></span></button>
                        <button type="button" class="generic-menu-item generic-menu-item--descriptive" data-omo-calendar-open-meeting role="menuitem"><strong><?= omoApiEscape(omoCalendarT('calendar.action.meeting')) ?></strong><span class="generic-menu-item__description"><?= omoApiEscape(omoCalendarT('calendar.action.meeting_hint')) ?></span></button>
                    </div>
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
        <div class="omo-calendar__header-secondary omo-panel-view__header-secondary">
            <div class="omo-calendar__filter-toolbar omo-view-filter" data-omo-calendar-filter-control role="group" aria-label="<?= omoApiEscape(omoCalendarT('calendar.filters.aria')) ?>">
                <div class="omo-view-filter__input">
                    <div class="omo-view-filter__chips">
                        <button type="button" class="omo-view-filter__chip" data-omo-calendar-filter-toggle data-omo-calendar-scope-chip aria-expanded="false" aria-controls="omo-calendar-filter-panel"><?= omoApiEscape(omoCalendarT('calendar.scope.' . $calendarScope)) ?></button>
                        <button type="button" class="omo-view-filter__chip" data-omo-calendar-filter-toggle data-omo-calendar-view-chip aria-expanded="false" aria-controls="omo-calendar-filter-panel"><?= omoApiEscape(omoCalendarT('calendar.view.' . $viewMode)) ?></button>
                    </div>
                    <label class="omo-view-filter__search">
                        <input type="search" class="generic-form-control" data-omo-calendar-quick-search placeholder="<?= omoApiEscape(omoCalendarT('calendar.search.placeholder')) ?>" aria-label="<?= omoApiEscape(omoCalendarT('calendar.search.aria')) ?>" autocomplete="off">
                    </label>
                </div>
                <section id="omo-calendar-filter-panel" class="omo-view-filter__panel generic-soft-panel generic-soft-panel--stack" data-omo-calendar-filter-panel hidden>
                    <div class="omo-view-filter__panel-grid">
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarT('calendar.filters.scope')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoCalendarT('calendar.filters.scope')) ?>">
                                <?php foreach ($calendarScopes as $scopeKey): ?>
                                    <button type="button" class="omo-segmented__button<?= $calendarScope === $scopeKey ? ' is-active' : '' ?>" data-omo-calendar-scope-toggle="<?= omoApiEscape($scopeKey) ?>" aria-pressed="<?= $calendarScope === $scopeKey ? 'true' : 'false' ?>"><?= omoApiEscape(omoCalendarT('calendar.scope.' . $scopeKey)) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarT('calendar.filters.view')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoCalendarT('calendar.filters.view')) ?>">
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
                    <div class="omo-view-filter__actions">
                        <button type="button" class="generic-action-button generic-action-button--main" data-omo-calendar-filter-apply><?= omoApiEscape(omoCalendarT('calendar.filters.apply')) ?></button>
                        <?php if (!empty($applicationViewPreferences['canSavePersonal']) || !empty($applicationViewPreferences['canSaveTemporary'])): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary"<?= !empty($applicationViewPreferences['canSavePersonal']) ? ' data-omo-calendar-filter-save' : '' ?> data-omo-app-view-save-scope="<?= omoApiEscape($applicationViewPreferences['primarySaveScope']) ?>"><?= omoApiEscape(omoCalendarT('calendar.filters.save_view')) ?></button>
                        <?php elseif (($applicationViewPreferences['primarySaveScope'] ?? '') !== ''): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-app-view-save-scope="<?= omoApiEscape($applicationViewPreferences['primarySaveScope']) ?>"><?= omoApiEscape($applicationViewPreferences['primarySaveLabel'] ?? '') ?></button>
                        <?php endif; ?>
                        <?= omoApplicationViewPreferencesRenderMenu($applicationViewPreferences) ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <div class="omo-panel-view__body" data-omo-calendar-views>
        <div class="omo-empty-state omo-calendar__search-empty" data-omo-calendar-search-empty hidden><?= omoApiEscape(omoCalendarT('calendar.search.empty')) ?></div>
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
    <link rel="stylesheet" href="/common/calendar/availability.css?v=20260916-conflict">
    <script src="/common/calendar/availability.js?v=20260916-conflict"></script>
    <script src="/common/calendar/share.js?v=20260916"></script>
    <script src="/omo/assets/js/application-view-preferences.js?v=20260917-filter-hierarchy"></script>
    <script type="application/json" data-omo-calendar-data><?= json_encode($calendarClientData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
    <script>
    (function () {
        const root = typeof window.omoFindApplicationRoot === 'function'
            ? window.omoFindApplicationRoot('omo-calendar-root')
            : document.getElementById('omo-calendar-root');
        const url = <?= json_encode('/omo/api/calendar/calendar.js?v=' . substr(hash_file('sha256', __DIR__ . '/calendar.js'), 0, 16)) ?>;
        const loaded = typeof window.omoLoadScript === 'function'
            ? window.omoLoadScript(url)
            : new Promise(function (resolve, reject) {
                const script = document.createElement('script');
                script.src = url;
                script.onload = resolve;
                script.onerror = function () { reject(new Error('calendar_script_load')); };
                document.head.appendChild(script);
            });
        loaded.then(function () {
            if (root && root.isConnected) window.omoInitCalendar(root);
        }).catch(function (error) {
            if (root && root.isConnected) {
                root.querySelector('[data-omo-calendar-views]').textContent = <?= json_encode(omoCalendarT('calendar.error.load_form')) ?>;
                root.removeAttribute('data-omo-view-filter-pending');
                root.removeAttribute('aria-busy');
            }
            console.error(error);
        });
    })();
    </script>
</div>
