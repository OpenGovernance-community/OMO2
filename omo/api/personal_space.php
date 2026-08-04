<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/user_profile_ui.php';
require_once __DIR__ . '/projects/shared.php';
require_once __DIR__ . '/stats/shared.php';

use dbObject\ArrayDecisionProcess;
use dbObject\ArrayDocument;
use dbObject\ArrayEvent;
use dbObject\ArrayProject;
use dbObject\ArrayStatIndicator;
use dbObject\ArrayStatIndicatorImport;
use dbObject\ArrayUserOrganization;
use dbObject\Holon;
use dbObject\History;
use dbObject\ObjectVisibility;
use dbObject\Organization;
use dbObject\Project;
use dbObject\StatIndicator;

$sourceLang = [
    'personal_space.title' => [
        'text' => 'Tableau de pilotage',
        'context' => 'Main title of the dashboard panel shown on the right side of the OMO workspace.',
    ],
    'personal_space.filters.aria' => [
        'text' => 'Filtres du tableau de pilotage',
        'context' => 'Accessible label for the dashboard view filter control.',
    ],
    'personal_space.filters.context' => [
        'text' => 'Contexte',
        'context' => 'Heading for the dashboard context choices.',
    ],
    'personal_space.filters.apply' => [
        'text' => 'Appliquer',
        'context' => 'Button applying a temporary dashboard view without saving it.',
    ],
    'personal_space.filters.save_view' => [
        'text' => 'Enregistrer la vue',
        'context' => 'Button saving the dashboard view for the current holon.',
    ],
    'personal_space.filters.more_actions' => [
        'text' => 'Autres options de vue',
        'context' => 'Accessible label for additional dashboard view preference actions.',
    ],
    'personal_space.filters.apply_everywhere' => [
        'text' => 'Appliquer partout',
        'context' => 'Action setting the current dashboard view as the global default.',
    ],
    'personal_space.filters.set_default' => [
        'text' => 'Definir comme vue par defaut',
        'context' => 'Action saving the current dashboard view as the default view.',
    ],
    'personal_space.filters.restore_default' => [
        'text' => 'Restaurer la vue par defaut',
        'context' => 'Action removing the current holon-specific dashboard view.',
    ],
    'personal_space.scope.contextual' => [
        'text' => 'Local',
        'context' => 'Dashboard scope showing items attached to the current holon.',
    ],
    'personal_space.scope.children' => [
        'text' => 'Enfants directs',
        'context' => 'Dashboard scope showing items attached to the current holon and direct children.',
    ],
    'personal_space.scope.descendants' => [
        'text' => 'Descendants',
        'context' => 'Dashboard scope showing items attached to the current holon and descendants.',
    ],
    'personal_space.section.projects_overdue' => [
        'text' => 'Projets en retard',
        'context' => 'Title of the overdue projects dashboard card.',
    ],
    'personal_space.section.indicators_overdue' => [
        'text' => 'Indicateurs en retard',
        'context' => 'Title of the overdue indicators dashboard card.',
    ],
    'personal_space.overdue.days' => [
        'one' => '{count} jour de retard',
        'other' => '{count} jours de retard',
        'context' => 'Overdue duration displayed beside an overdue dashboard item.',
    ],
    'personal_space.overdue.to_complete' => [
        'text' => 'A completer',
        'context' => 'Status shown for an indicator that is overdue but still within its grace period.',
    ],
    'personal_space.heading' => [
        'text' => 'Espace personnel',
        'context' => 'Main title of the personal space panel shown on the right side of the OMO workspace.',
    ],
    'personal_space.empty' => [
        'text' => 'Aucun résumé personnel disponible avec les applications actives pour le moment.',
        'context' => 'Fallback empty state when no supported applications are enabled in the sidebar.',
    ],
    'personal_space.login_required' => [
        'text' => 'Connectez-vous pour afficher votre résumé personnel.',
        'context' => 'Message shown when the personal space is requested without a logged in user.',
    ],
    'personal_space.open_app' => [
        'text' => 'Ouvrir',
        'context' => 'Button label used to open the full application from the personal space card.',
    ],
    'personal_space.section.decisions' => [
        'text' => 'Décisions',
        'context' => 'Title of the decision summary card in the personal space panel.',
    ],
    'personal_space.section.documents_recent' => [
        'text' => 'Documents - dernières modifications',
        'context' => 'Title of the recent document activity card in the personal space panel.',
    ],
    'personal_space.section.calendar' => [
        'text' => 'Mes prochaines réunions',
        'context' => 'Title of the upcoming meetings summary card in the personal space panel.',
    ],
    'personal_space.section.team' => [
        'text' => 'Team',
        'context' => 'Title of the team summary card in the personal space panel.',
    ],
    'personal_space.section.structure' => [
        'text' => 'Structure',
        'context' => 'Title of the structure summary card in the personal space panel.',
    ],
    'personal_space.documents.empty' => [
        'text' => 'Aucun document récent dans ce contexte.',
        'context' => 'Empty state shown when no recent documents are available in the current context.',
    ],
    'personal_space.calendar.empty' => [
        'text' => 'Aucune date à venir pour vos contextes.',
        'context' => 'Empty state shown when no upcoming organization or member-holon event is available for the current user.',
    ],
    'personal_space.calendar.context.organization' => [
        'text' => 'Orga',
        'context' => 'Short fallback context label used for organization-wide events in the personal space panel.',
    ],
    'personal_space.team.empty' => [
        'text' => 'Aucun anniversaire proche à afficher.',
        'context' => 'Empty state shown when no upcoming personal or professional anniversaries are found.',
    ],
    'personal_space.structure.empty' => [
        'text' => 'Aucune modification récente à afficher.',
        'context' => 'Empty state shown when no recent structure history items are available.',
    ],
    'personal_space.decisions.empty' => [
        'text' => 'Aucune décision à suivre pour le moment.',
        'context' => 'Empty state shown when the user has no tracked decisions in the current organization.',
    ],
    'personal_space.decisions.finalize' => [
        'one' => '{count} décision en préparation à finaliser',
        'other' => '{count} décisions en préparation à finaliser',
        'context' => 'Decision summary line for draft or scheduled decisions managed by the user.',
    ],
    'personal_space.decisions.consultation' => [
        'one' => '{count} consultation en cours',
        'other' => '{count} consultations en cours',
        'context' => 'Decision summary line for consultation processes currently active.',
    ],
    'personal_space.decisions.action' => [
        'one' => '{count} décision à prendre',
        'other' => '{count} décisions à prendre',
        'context' => 'Decision summary line for active decisions the user can answer.',
    ],
    'personal_space.decisions.responded' => [
        'one' => 'dont {count} déjà répondue',
        'other' => 'dont {count} déjà répondues',
        'context' => 'Extra detail appended to the action summary line for already submitted responses.',
    ],
    'personal_space.decisions.results' => [
        'one' => '{count} décision terminée avec résultat à consulter',
        'other' => '{count} décisions terminées avec résultats à consulter',
        'context' => 'Decision summary line for finished decisions with available results.',
    ],
    'personal_space.team.tag.personal' => [
        'text' => 'Perso',
        'context' => 'Short badge shown for a personal birthday in the team card.',
    ],
    'personal_space.team.tag.pro' => [
        'text' => 'Pro',
        'context' => 'Short badge shown for a professional join-date anniversary in the team card.',
    ],
    'personal_space.team.pro.today' => [
        'text' => "Anniversaire pro aujourd'hui",
        'context' => 'Headline shown when a professional anniversary happens today.',
    ],
    'personal_space.team.pro.new' => [
        'text' => 'Nouveau',
        'context' => 'Headline shown for a new collaborator during the week after their arrival.',
    ],
    'personal_space.team.pro.new_detail_prefix' => [
        'text' => 'Arrivé le',
        'context' => 'Detail prefix shown with the arrival date for a new collaborator.',
    ],
    'personal_space.team.pro.soon_prefix' => [
        'text' => 'Anniversaire pro dans',
        'context' => 'Prefix used before the remaining day count for a nearby professional anniversary.',
    ],
    'personal_space.date.unknown' => [
        'text' => 'Date inconnue',
        'context' => 'Fallback label shown when no usable date is available for a listed item.',
    ],
];

$lang = omoLoadTranslationBundle('omo_personal_space_panel', $sourceLang);

$currentOrganizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$currentHolonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$currentUserId = (int)commonGetCurrentUserId();
$currentContextHolon = null;
$organizationRootHolon = null;

$organization = new Organization();
if ($currentOrganizationId <= 0 || !$organization->load($currentOrganizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-personal-space omo-personal-space--empty">
        <div class="omo-personal-space__scroll">
            <section class="generic-section generic-section--stack omo-personal-space__hero">
                <span class="generic-card-title generic-card-title--section"><?= omoApiEscape(t('personal_space.heading', [], $lang, $sourceLang)) ?></span>
                <p class="omo-personal-space__hero-text"><?= omoApiEscape(t('personal_space.empty', [], $lang, $sourceLang)) ?></p>
            </section>
        </div>
    </div>
    <?php
    exit;
}

$organizationRootHolon = $organization->getEnabledStructuralRootHolon();

$enabledAppHashes = array_fill_keys($organization->getEnabledApplicationHashes($currentUserId), true);
$supportedAppHashes = array('decision', 'documents', 'calendar', 'team', 'structure', 'projects', 'stats');
$hasSupportedApp = false;
foreach ($supportedAppHashes as $supportedAppHash) {
    if (!empty($enabledAppHashes[$supportedAppHash])) {
        $hasSupportedApp = true;
        break;
    }
}

$formatDateTime = static function ($value, $includeTime = false) use ($lang, $sourceLang): string {
    if (!$value instanceof DateTimeInterface) {
        return t('personal_space.date.unknown', [], $lang, $sourceLang);
    }

    return $value->format($includeTime ? 'd.m.Y H:i' : 'd.m.Y');
};

$documentShortDateFormatter = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter('fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE)
    : null;

if ($documentShortDateFormatter instanceof IntlDateFormatter) {
    $documentShortDateFormatter->setPattern('d MMMM');
}

$formatDocumentSummaryDate = static function ($value) use ($documentShortDateFormatter, $lang, $sourceLang): string {
    if (!$value instanceof DateTimeInterface) {
        return t('personal_space.date.unknown', [], $lang, $sourceLang);
    }

    if ($documentShortDateFormatter instanceof IntlDateFormatter) {
        $formatted = $documentShortDateFormatter->format($value);
        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }
    }

    $monthMap = array(
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
    );

    $monthNumber = (int)$value->format('n');
    $monthLabel = (string)($monthMap[$monthNumber] ?? $value->format('m'));
    return $value->format('j') . ' ' . $monthLabel;
};

$formatCalendarRange = static function ($startAt, $endAt, $isAllDay = false) use ($lang, $sourceLang): string {
    if (!($startAt instanceof DateTimeInterface) || !($endAt instanceof DateTimeInterface)) {
        return t('personal_space.date.unknown', [], $lang, $sourceLang);
    }

    if ($isAllDay) {
        if ($startAt->format('Y-m-d') === $endAt->format('Y-m-d')) {
            return $startAt->format('d.m.Y');
        }

        return $startAt->format('d.m.Y') . ' -> ' . $endAt->format('d.m.Y');
    }

    if ($startAt->format('Y-m-d') === $endAt->format('Y-m-d')) {
        return $startAt->format('d.m.Y H:i') . ' - ' . $endAt->format('H:i');
    }

    return $startAt->format('d.m.Y H:i') . ' -> ' . $endAt->format('d.m.Y H:i');
};

if ($currentHolonId > 0) {
    $candidateHolon = new Holon();
    if (
        $candidateHolon->load($currentHolonId)
        && $organizationRootHolon instanceof Holon
        && $candidateHolon->isDescendantOf((int)$organizationRootHolon->getId(), true)
        && $candidateHolon->canViewDetail()
    ) {
        $currentContextHolon = $candidateHolon;
    }
}

$scopeReferenceHolon = $currentContextHolon instanceof Holon
    ? $currentContextHolon
    : $organizationRootHolon;
$availableScopes = omoApiGetAvailableContextScopes(
    $scopeReferenceHolon instanceof Holon,
    $scopeReferenceHolon,
    $organizationRootHolon
);
$personalSpaceScope = omoApiNormalizeContextScope($_GET['dashboard_scope'] ?? 'contextual', $availableScopes);
$personalSpaceScopeHolonIds = [];
if ($personalSpaceScope === 'children' && $scopeReferenceHolon instanceof Holon) {
    $personalSpaceScopeHolonIds = omoApiGetDirectChildScopeHolonIds($scopeReferenceHolon);
} elseif ($personalSpaceScope === 'descendants' && $scopeReferenceHolon instanceof Holon) {
    $personalSpaceScopeHolonIds = omoApiGetDescendantHolonIds($scopeReferenceHolon);
}
$personalSpaceScopeHolonIdMap = count($personalSpaceScopeHolonIds) > 0
    ? array_fill_keys(array_map('intval', $personalSpaceScopeHolonIds), true)
    : [];
$personalSpaceDecisionHolonIds = $personalSpaceScope === 'contextual'
    ? ($currentHolonId > 0 ? [$currentHolonId] : null)
    : $personalSpaceScopeHolonIds;

$decisionProcesses = new ArrayDecisionProcess();
$decisionSummary = !empty($enabledAppHashes['decision']) && $currentUserId > 0
    ? $decisionProcesses->buildPersonalSpaceSummary(
        $currentOrganizationId,
        $currentUserId,
        $personalSpaceScope === 'contextual' ? $currentHolonId : 0,
        3,
        $personalSpaceDecisionHolonIds
    )
    : null;
$documentVisibilityIconMap = array(
    ObjectVisibility::TYPE_EVERYONE => '/omo/assets/images/documents/visibility/everyone.png',
    ObjectVisibility::TYPE_ORGANIZATION => '/omo/assets/images/documents/visibility/organization.png',
    ObjectVisibility::TYPE_CIRCLE => '/omo/assets/images/documents/visibility/circle.png',
    ObjectVisibility::TYPE_ROLE => '/omo/assets/images/documents/visibility/role.png',
    ObjectVisibility::TYPE_SELF => '/omo/assets/images/documents/visibility/me.png',
);
$documents = new ArrayDocument();
$recentDocuments = array();
if (!empty($enabledAppHashes['documents'])) {
    $documentScopeHolonIds = $personalSpaceScope === 'contextual'
        ? []
        : $personalSpaceScopeHolonIds;
    $documents->loadRecentForOrganizationContext(
        $currentOrganizationId,
        $currentHolonId,
        5,
        $personalSpaceScope,
        $documentScopeHolonIds
    );
    $recentDocuments = $documents->buildPersonalSpaceItems($currentOrganizationId);
}

$overdueProjects = [];
if (!empty($enabledAppHashes['projects'])) {
    $allProjects = new ArrayProject();
    $allProjects->loadForOrganization($currentOrganizationId);
    $today = new DateTimeImmutable('today');
    foreach ($allProjects as $project) {
        if (
            !($project instanceof Project)
            || Project::normalizeStatus($project->get('status')) === Project::STATUS_DONE
            || !omoProjectsScopeContainsProject(
                $project,
                $personalSpaceScope,
                $currentHolonId,
                $personalSpaceScopeHolonIds
            )
        ) {
            continue;
        }

        $plannedEnd = $project->get('planned_end_date');
        if (!($plannedEnd instanceof DateTimeInterface)) {
            continue;
        }

        $plannedEndDate = DateTimeImmutable::createFromInterface($plannedEnd);
        if ($plannedEndDate >= $today) {
            continue;
        }

        $projectHolon = $project->getHolon();
        $overdueProjects[] = [
            'id' => (int)$project->getId(),
            'title' => trim((string)$project->get('title')) !== ''
                ? trim((string)$project->get('title'))
                : 'Projet #' . (int)$project->getId(),
            'holonId' => (int)$project->get('IDholon'),
            'holonLabel' => $projectHolon instanceof Holon
                ? trim((string)$projectHolon->getDisplayName())
                : trim((string)$organization->get('name')),
            'plannedEnd' => $plannedEndDate,
            'overdueDays' => max(1, (int)$today->diff($plannedEndDate)->days),
        ];
    }
    usort($overdueProjects, static function (array $left, array $right): int {
        $dateComparison = $left['plannedEnd']->getTimestamp() <=> $right['plannedEnd']->getTimestamp();
        return $dateComparison !== 0
            ? $dateComparison
            : strcasecmp((string)$left['title'], (string)$right['title']);
    });
}

$overdueIndicators = [];
if (!empty($enabledAppHashes['stats'])) {
    $statsIndicators = new ArrayStatIndicator();
    $statsContextHolonId = $personalSpaceScope === 'contextual'
        ? $currentHolonId
        : ($scopeReferenceHolon instanceof Holon ? (int)$scopeReferenceHolon->getId() : 0);
    $statsIndicators->loadForContext(
        $currentOrganizationId,
        $statsContextHolonId,
        $personalSpaceScope,
        $personalSpaceScopeHolonIds
    );
    $indicatorById = [];
    foreach ($statsIndicators as $indicator) {
        if ($indicator instanceof StatIndicator) {
            $indicatorById[(int)$indicator->getId()] = $indicator;
        }
    }

    $indicatorImports = new ArrayStatIndicatorImport();
    $indicatorImports->loadForContext(
        $currentOrganizationId,
        $statsContextHolonId,
        $personalSpaceScope,
        $personalSpaceScopeHolonIds
    );
    foreach ($indicatorImports as $indicatorImport) {
        $indicator = $indicatorImport->getIndicator();
        if (
            !($indicator instanceof StatIndicator)
            || !$indicator->canView()
            || isset($indicatorById[(int)$indicator->getId()])
        ) {
            continue;
        }
        $indicatorById[(int)$indicator->getId()] = $indicator;
    }

    foreach ($indicatorById as $indicator) {
        $overdueInfo = omoStatsGetIndicatorOverdueInfo($indicator);
        if (empty($overdueInfo['is_overdue'])) {
            continue;
        }
        $overdueIndicators[] = [
            'id' => (int)$indicator->getId(),
            'title' => trim((string)$indicator->get('name')) !== ''
                ? trim((string)$indicator->get('name'))
                : 'Indicateur #' . (int)$indicator->getId(),
            'contextLabel' => omoStatsContextLabel($indicator),
            'severity' => (string)($overdueInfo['severity'] ?? 'error'),
            'overdueDays' => (int)($overdueInfo['overdue_days'] ?? 0),
        ];
    }
    usort($overdueIndicators, static function (array $left, array $right): int {
        $severityComparison = strcmp((string)$left['severity'], (string)$right['severity']);
        return $severityComparison !== 0
            ? $severityComparison
            : strcasecmp((string)$left['title'], (string)$right['title']);
    });
}

$calendarEvents = array();
if (!empty($enabledAppHashes['calendar']) && $currentUserId > 0) {
    $events = new ArrayEvent();
    $events->loadUpcomingForPersonalSpace($currentOrganizationId, $currentUserId, 5);
    $holonNameCache = array();
    $organizationContextLabel = t('personal_space.calendar.context.organization', [], $lang, $sourceLang);
    $limitCalendarToScope = $personalSpaceScope !== 'contextual'
        ? count($personalSpaceScopeHolonIdMap) > 0
        : $currentHolonId > 0;
    $calendarScopeHolonIdMap = $personalSpaceScope === 'contextual'
        ? ($currentHolonId > 0 ? [$currentHolonId => true] : [])
        : $personalSpaceScopeHolonIdMap;

    foreach ($events as $event) {
        if (!($event instanceof \dbObject\Event) || (int)$event->getId() <= 0) {
            continue;
        }

        $eventHolonId = (int)$event->get('IDholon');
        $eventHasExplicitInvitations = $event->hasExplicitInvitations();
        if (
            $limitCalendarToScope
            && ($eventHolonId <= 0 || !isset($calendarScopeHolonIdMap[$eventHolonId]))
            && !$eventHasExplicitInvitations
        ) {
            continue;
        }

        $contextLabel = $organizationContextLabel;

        if ($eventHolonId > 0) {
            if (!array_key_exists($eventHolonId, $holonNameCache)) {
                $holon = new Holon();
                $holonNameCache[$eventHolonId] = $holon->load($eventHolonId)
                    ? trim((string)$holon->get('name'))
                    : '';
            }

            if (trim((string)$holonNameCache[$eventHolonId]) !== '') {
                $contextLabel = (string)$holonNameCache[$eventHolonId];
            }
        }

        $calendarEvents[] = array(
            'id' => (int)$event->getId(),
            'holonId' => $eventHolonId,
            'title' => trim((string)$event->get('title')) !== ''
                ? trim((string)$event->get('title'))
                : 'Evenement #' . (int)$event->getId(),
            'description' => trim((string)$event->get('description')),
            'contextLabel' => $contextLabel,
            'rangeLabel' => $formatCalendarRange(
                $event->get('start_at'),
                $event->get('end_at'),
                (bool)$event->get('is_all_day')
            ),
        );
    }
}
$memberships = new ArrayUserOrganization();
$allowedTeamUserIds = null;
if ($personalSpaceScope === 'contextual' && $currentContextHolon instanceof Holon) {
    $allowedTeamUserIds = $currentContextHolon->getAssociatedMemberUserIds(array(
        'organizationId' => $currentOrganizationId,
    ));
} elseif ($personalSpaceScope !== 'contextual' && count($personalSpaceScopeHolonIds) > 0) {
    $allowedTeamUserIdMap = [];
    foreach ($personalSpaceScopeHolonIds as $scopeHolonId) {
        $scopeHolon = new Holon();
        if (!$scopeHolon->load((int)$scopeHolonId)) {
            continue;
        }
        foreach ($scopeHolon->getAssociatedMemberUserIds(array(
            'organizationId' => $currentOrganizationId,
            'includeDescendants' => false,
        )) as $userId) {
            $allowedTeamUserIdMap[(int)$userId] = (int)$userId;
        }
    }
    $allowedTeamUserIds = array_values($allowedTeamUserIdMap);
}
$teamEvents = !empty($enabledAppHashes['team']) && $currentUserId > 0
    ? $memberships->buildUpcomingCelebrations($currentOrganizationId, 6, null, array(
        'proNew' => t('personal_space.team.pro.new', [], $lang, $sourceLang),
        'proNewDetailPrefix' => t('personal_space.team.pro.new_detail_prefix', [], $lang, $sourceLang),
        'proToday' => t('personal_space.team.pro.today', [], $lang, $sourceLang),
        'proSoonPrefix' => t('personal_space.team.pro.soon_prefix', [], $lang, $sourceLang),
    ), $allowedTeamUserIds)
    : array();
$personalSpaceForcedOpenScope = $personalSpaceScope === 'contextual' ? '' : $personalSpaceScope;
$structureHistory = array('items' => array());
if (!empty($enabledAppHashes['structure'])) {
    if ($personalSpaceScope === 'contextual') {
        $structureHistory = History::fetchHolonFeedPage(
            $currentOrganizationId,
            $currentHolonId,
            5,
            0,
            $currentHolonId <= 0
        );
    } else {
        $historyById = [];
        foreach ($personalSpaceScopeHolonIds as $scopeHolonId) {
            $scopeHistory = History::fetchHolonFeedPage(
                $currentOrganizationId,
                (int)$scopeHolonId,
                5,
                0,
                false
            );
            foreach ((array)($scopeHistory['items'] ?? []) as $historyItem) {
                $historyId = (int)($historyItem['id'] ?? 0);
                if ($historyId > 0) {
                    $historyById[$historyId] = $historyItem;
                }
            }
        }
        $historyItems = array_values($historyById);
        usort($historyItems, static function (array $left, array $right): int {
            $dateComparison = strcmp(
                (string)($right['datecreation'] ?? ''),
                (string)($left['datecreation'] ?? '')
            );
            return $dateComparison !== 0
                ? $dateComparison
                : ((int)($right['id'] ?? 0) <=> (int)($left['id'] ?? 0));
        });
        $structureHistory['items'] = array_slice($historyItems, 0, 5);
    }
}
$historyItems = is_array($structureHistory['items'] ?? null) ? $structureHistory['items'] : array();
?>
<link rel="stylesheet" href="/common/view-filter/view-filter.css?v=20260803-dashboard">
<div
    class="omo-personal-space omo-panel-view<?= $currentUserId <= 0 ? ' omo-personal-space--guest' : '' ?>"
    id="omo-personal-space-root"
    data-omo-personal-space-oid="<?= (int)$currentOrganizationId ?>"
    data-omo-personal-space-cid="<?= (int)$currentHolonId ?>"
    data-omo-personal-space-scope="<?= omoApiEscape($personalSpaceScope) ?>"
    data-omo-personal-space-base-url="<?= omoApiEscape('/omo/api/personal_space.php?oid=' . (int)$currentOrganizationId . ($currentHolonId > 0 ? '&cid=' . (int)$currentHolonId : '')) ?>"
    data-omo-personal-space-available-scopes="<?= omoApiEscape(json_encode(array_values($availableScopes), JSON_UNESCAPED_SLASHES)) ?>"
    data-omo-view-filter-pending="1"
>
    <header class="omo-personal-space__header omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon omo-personal-space__app-icon" aria-hidden="true">
                    <img src="/omo/images/tools/alert.png" alt="">
                </span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-personal-space__title-row generic-title-row generic-title-row--center">
                        <h2 class="omo-panel-view__title"><?= omoApiEscape(t('personal_space.title', [], $lang, $sourceLang)) ?></h2>
                        <span class="omo-panel-view__count omo-panel-view__overdue-count"><?= count($overdueProjects) + count($overdueIndicators) ?></span>
                    </div>
                    <?php if ($currentUserId <= 0): ?>
                        <p class="omo-panel-view__description"><?= omoApiEscape(t('personal_space.login_required', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="omo-panel-view__header-secondary">
            <div class="omo-personal-space__filter-toolbar omo-view-filter" data-omo-personal-space-filter-control role="group" aria-label="<?= omoApiEscape(t('personal_space.filters.aria', [], $lang, $sourceLang)) ?>">
                <div class="omo-view-filter__input">
                    <div class="omo-view-filter__chips">
                        <button type="button" class="omo-view-filter__chip" data-omo-personal-space-filter-toggle aria-expanded="false" aria-controls="omo-personal-space-filter-panel"><?= omoApiEscape(t('personal_space.scope.' . $personalSpaceScope, [], $lang, $sourceLang)) ?></button>
                    </div>
                </div>
                <section id="omo-personal-space-filter-panel" class="omo-view-filter__panel generic-soft-panel generic-soft-panel--stack" data-omo-personal-space-filter-panel hidden>
                    <div class="omo-view-filter__panel-grid">
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(t('personal_space.filters.context', [], $lang, $sourceLang)) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(t('personal_space.filters.context', [], $lang, $sourceLang)) ?>">
                                <?php foreach ($availableScopes as $scopeKey): ?>
                                    <button type="button" class="omo-segmented__button<?= $personalSpaceScope === $scopeKey ? ' is-active' : '' ?>" data-omo-personal-space-scope="<?= omoApiEscape($scopeKey) ?>" aria-pressed="<?= $personalSpaceScope === $scopeKey ? 'true' : 'false' ?>"><?= omoApiEscape(t('personal_space.scope.' . $scopeKey, [], $lang, $sourceLang)) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="omo-view-filter__actions">
                        <button type="button" class="generic-action-button generic-action-button--main" data-omo-personal-space-filter-apply><?= omoApiEscape(t('personal_space.filters.apply', [], $lang, $sourceLang)) ?></button>
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-personal-space-filter-save><?= omoApiEscape(t('personal_space.filters.save_view', [], $lang, $sourceLang)) ?></button>
                        <div class="generic-menu omo-view-filter__actions-more" data-omo-personal-space-filter-more-menu>
                            <button type="button" class="generic-menu-toggle" data-omo-personal-space-filter-more-toggle aria-expanded="false" aria-label="<?= omoApiEscape(t('personal_space.filters.more_actions', [], $lang, $sourceLang)) ?>">&#8942;</button>
                            <div class="generic-menu-panel" data-omo-personal-space-filter-more-panel role="menu" hidden>
                                <button type="button" class="generic-menu-item" data-omo-personal-space-filter-more-action="apply-everywhere" role="menuitem"><?= omoApiEscape(t('personal_space.filters.apply_everywhere', [], $lang, $sourceLang)) ?></button>
                                <button type="button" class="generic-menu-item" data-omo-personal-space-filter-more-action="set-default" role="menuitem"><?= omoApiEscape(t('personal_space.filters.set_default', [], $lang, $sourceLang)) ?></button>
                                <button type="button" class="generic-menu-item" data-omo-personal-space-filter-more-action="restore-default" role="menuitem"><?= omoApiEscape(t('personal_space.filters.restore_default', [], $lang, $sourceLang)) ?></button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </header>

    <div class="omo-panel-view__body omo-personal-space__body">
        <div class="omo-panel-view__body_content omo-personal-space__scroll">

        <?php if ($currentUserId <= 0): ?>
            <section class="generic-section generic-section--stack omo-personal-space__card">
                <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.login_required', [], $lang, $sourceLang)) ?></p>
            </section>
        <?php elseif (!$hasSupportedApp): ?>
            <section class="generic-section generic-section--stack omo-personal-space__card">
                <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.empty', [], $lang, $sourceLang)) ?></p>
            </section>
        <?php else: ?>
            <?php $hasOverdueProjects = !empty($enabledAppHashes['projects']) && $overdueProjects !== array(); ?>
            <?php $hasOverdueIndicators = !empty($enabledAppHashes['stats']) && $overdueIndicators !== array(); ?>
            <?php if ($hasOverdueProjects || $hasOverdueIndicators): ?>
                <div class="omo-personal-space__overdue-grid">
            <?php endif; ?>

            <?php if ($hasOverdueProjects): ?>
                <section class="generic-section generic-section--stack omo-personal-space__card omo-personal-space__card--overdue">
                    <div class="omo-personal-space__section-head">
                        <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(t('personal_space.section.projects_overdue', [], $lang, $sourceLang)) ?></span>
                        <button type="button" class="omo-personal-space__section-action" data-omo-personal-space-route-token="projects"<?= $personalSpaceForcedOpenScope !== '' ? ' data-omo-personal-space-forced-scope="' . omoApiEscape($personalSpaceForcedOpenScope) . '"' : '' ?>><?= omoApiEscape(t('personal_space.open_app', [], $lang, $sourceLang)) ?></button>
                    </div>
                    <div class="omo-personal-space__item-list">
                            <?php foreach ($overdueProjects as $overdueProject): ?>
                                <button type="button" class="omo-personal-space__item-button omo-personal-space__item-button--overdue" data-omo-personal-space-project-id="<?= (int)$overdueProject['id'] ?>" data-omo-personal-space-project-holon-id="<?= (int)$overdueProject['holonId'] ?>">
                                    <span class="omo-personal-space__item-topline">
                                        <span class="omo-personal-space__item-title"><?= omoApiEscape($overdueProject['title']) ?></span>
                                        <span class="omo-personal-space__tag omo-personal-space__tag--danger"><?= omoApiEscape(t('personal_space.overdue.days', ['count' => (string)$overdueProject['overdueDays']], $lang, $sourceLang)) ?></span>
                                    </span>
                                    <span class="omo-personal-space__item-meta"><?= omoApiEscape($overdueProject['holonLabel']) ?> · <?= omoApiEscape($overdueProject['plannedEnd']->format('d.m.Y')) ?></span>
                                </button>
                            <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($hasOverdueIndicators): ?>
                <section class="generic-section generic-section--stack omo-personal-space__card omo-personal-space__card--overdue">
                    <div class="omo-personal-space__section-head">
                        <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(t('personal_space.section.indicators_overdue', [], $lang, $sourceLang)) ?></span>
                        <button type="button" class="omo-personal-space__section-action" data-omo-personal-space-route-token="stats"<?= $personalSpaceForcedOpenScope !== '' ? ' data-omo-personal-space-forced-scope="' . omoApiEscape($personalSpaceForcedOpenScope) . '"' : '' ?>><?= omoApiEscape(t('personal_space.open_app', [], $lang, $sourceLang)) ?></button>
                    </div>
                    <div class="omo-personal-space__item-list">
                            <?php foreach ($overdueIndicators as $overdueIndicator): ?>
                                <button type="button" class="omo-personal-space__item-button omo-personal-space__item-button--overdue" data-omo-personal-space-indicator-id="<?= (int)$overdueIndicator['id'] ?>" data-omo-personal-space-indicator-holon-id="<?= (int)$currentHolonId ?>">
                                    <span class="omo-personal-space__item-topline">
                                        <span class="omo-personal-space__item-title"><?= omoApiEscape($overdueIndicator['title']) ?></span>
                                        <span class="omo-personal-space__tag omo-personal-space__tag--danger"><?= omoApiEscape($overdueIndicator['severity'] === 'warning' ? t('personal_space.overdue.to_complete', [], $lang, $sourceLang) : t('personal_space.overdue.days', ['count' => (string)$overdueIndicator['overdueDays']], $lang, $sourceLang)) ?></span>
                                    </span>
                                    <span class="omo-personal-space__item-meta"><?= omoApiEscape($overdueIndicator['contextLabel']) ?></span>
                                </button>
                            <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
            <?php if ($hasOverdueProjects || $hasOverdueIndicators): ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($enabledAppHashes['decision']) && is_array($decisionSummary)): ?>
                <?php
                $decisionCounts = $decisionSummary['counts'] ?? array();
                $actionLine = t('personal_space.decisions.action', [
                    'count' => (string)(int)($decisionCounts['action'] ?? 0),
                ], $lang, $sourceLang);
                $respondedCount = (int)($decisionCounts['actionResponded'] ?? 0);
                if ($respondedCount > 0) {
                    $actionLine .= ' (' . t('personal_space.decisions.responded', [
                        'count' => (string)$respondedCount,
                    ], $lang, $sourceLang) . ')';
                }
                $decisionLines = array();
                if ((int)($decisionCounts['finalize'] ?? 0) > 0) {
                    $decisionLines[] = t('personal_space.decisions.finalize', ['count' => (string)(int)$decisionCounts['finalize']], $lang, $sourceLang);
                }
                if ((int)($decisionCounts['consultation'] ?? 0) > 0) {
                    $decisionLines[] = t('personal_space.decisions.consultation', ['count' => (string)(int)$decisionCounts['consultation']], $lang, $sourceLang);
                }
                if ((int)($decisionCounts['action'] ?? 0) > 0) {
                    $decisionLines[] = $actionLine;
                }
                if ((int)($decisionCounts['results'] ?? 0) > 0) {
                    $decisionLines[] = t('personal_space.decisions.results', ['count' => (string)(int)$decisionCounts['results']], $lang, $sourceLang);
                }
                $hasDecisionActivity = array_sum(array_map('intval', $decisionCounts)) > 0;
                ?>
                <section class="generic-section generic-section--stack omo-personal-space__card">
                    <div class="omo-personal-space__section-head">
                        <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(t('personal_space.section.decisions', [], $lang, $sourceLang)) ?></span>
                        <button type="button" class="omo-personal-space__section-action" data-omo-personal-space-route-token="decision"<?= $personalSpaceForcedOpenScope !== '' ? ' data-omo-personal-space-forced-scope="' . omoApiEscape($personalSpaceForcedOpenScope) . '"' : '' ?>><?= omoApiEscape(t('personal_space.open_app', [], $lang, $sourceLang)) ?></button>
                    </div>

                    <?php if ($hasDecisionActivity): ?>
                        <ul class="omo-personal-space__summary-list">
                            <?php foreach ($decisionLines as $decisionLine): ?>
                                <li><?= omoApiEscape($decisionLine) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.decisions.empty', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if (!empty($enabledAppHashes['documents'])): ?>
                <section class="generic-section generic-section--stack omo-personal-space__card">
                    <div class="omo-personal-space__section-head">
                        <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(t('personal_space.section.documents_recent', [], $lang, $sourceLang)) ?></span>
                        <button type="button" class="omo-personal-space__section-action" data-omo-personal-space-route-token="documents"<?= $personalSpaceForcedOpenScope !== '' ? ' data-omo-personal-space-forced-scope="' . omoApiEscape($personalSpaceForcedOpenScope) . '"' : '' ?>><?= omoApiEscape(t('personal_space.open_app', [], $lang, $sourceLang)) ?></button>
                    </div>

                    <?php if ($recentDocuments !== array()): ?>
                        <div class="omo-personal-space__item-list">
                            <?php foreach ($recentDocuments as $documentItem): ?>
                                <button
                                    type="button"
                                    class="omo-personal-space__item-button"
                                    data-omo-personal-space-document-url="<?= omoApiEscape($documentItem['contextUrl'] ?? '') ?>"
                                    data-omo-personal-space-document-title="<?= omoApiEscape($documentItem['title'] ?? '') ?>"
                                >
                                    <span class="omo-personal-space__item-topline">
                                        <span class="omo-personal-space__item-inline">
                                            <span class="omo-personal-space__item-meta omo-personal-space__item-meta--date"><?= omoApiEscape($formatDocumentSummaryDate($documentItem['datemodification'] ?? $documentItem['datecreation'] ?? null)) ?></span>
                                            <span class="omo-personal-space__item-title"><?= omoApiEscape($documentItem['title'] ?? '') ?></span>
                                        </span>
                                        <?php
                                        $documentVisibilityType = ObjectVisibility::normalizeVisibilityType((string)($documentItem['visibility']['type'] ?? ''));
                                        $documentVisibilityIconUrl = (string)($documentVisibilityIconMap[$documentVisibilityType] ?? $documentVisibilityIconMap[ObjectVisibility::TYPE_ORGANIZATION]);
                                        $documentVisibilityLabel = trim((string)($documentItem['visibility']['badgeText'] ?? ''));
                                        ?>
                                        <?php if ($documentVisibilityIconUrl !== '' && $documentVisibilityLabel !== ''): ?>
                                            <span class="omo-personal-space__visibility-icon" role="img" aria-label="<?= omoApiEscape($documentVisibilityLabel) ?>" title="<?= omoApiEscape($documentVisibilityLabel) ?>">
                                                <img src="<?= omoApiEscape($documentVisibilityIconUrl) ?>" alt="" loading="lazy">
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                    <?php if (trim((string)($documentItem['description'] ?? '')) !== ''): ?>
                                        <span class="omo-personal-space__item-copy"><?= omoApiEscape($documentItem['description']) ?></span>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.documents.empty', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if (!empty($enabledAppHashes['calendar'])): ?>
                <section class="generic-section generic-section--stack omo-personal-space__card">
                    <div class="omo-personal-space__section-head">
                        <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(t('personal_space.section.calendar', [], $lang, $sourceLang)) ?></span>
                        <button type="button" class="omo-personal-space__section-action" data-omo-personal-space-route-token="calendar"<?= $personalSpaceForcedOpenScope !== '' ? ' data-omo-personal-space-forced-scope="' . omoApiEscape($personalSpaceForcedOpenScope) . '"' : '' ?>><?= omoApiEscape(t('personal_space.open_app', [], $lang, $sourceLang)) ?></button>
                    </div>

                    <?php if ($calendarEvents !== array()): ?>
                        <div class="omo-personal-space__item-list">
                            <?php foreach ($calendarEvents as $eventItem): ?>
                                <button
                                    type="button"
                                    class="omo-personal-space__item-button"
                                    data-omo-personal-space-calendar-event-id="<?= (int)($eventItem['id'] ?? 0) ?>"
                                    data-omo-personal-space-calendar-holon-id="<?= (int)($eventItem['holonId'] ?? 0) ?>"
                                >
                                    <span class="omo-personal-space__item-title"><?= omoApiEscape($eventItem['title'] ?? '') ?></span>
                                    <span class="omo-personal-space__item-meta"><?= omoApiEscape($eventItem['rangeLabel'] ?? '') ?></span>
                                    <span class="omo-personal-space__item-meta"><?= omoApiEscape($eventItem['contextLabel'] ?? '') ?></span>
                                    <?php if (trim((string)($eventItem['description'] ?? '')) !== ''): ?>
                                        <span class="omo-personal-space__item-copy"><?= omoApiEscape($eventItem['description']) ?></span>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.calendar.empty', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if (!empty($enabledAppHashes['team'])): ?>
                <section class="generic-section generic-section--stack omo-personal-space__card">
                    <div class="omo-personal-space__section-head">
                        <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(t('personal_space.section.team', [], $lang, $sourceLang)) ?></span>
                        <button type="button" class="omo-personal-space__section-action" data-omo-personal-space-route-token="team"<?= $personalSpaceForcedOpenScope !== '' ? ' data-omo-personal-space-forced-scope="' . omoApiEscape($personalSpaceForcedOpenScope) . '"' : '' ?>><?= omoApiEscape(t('personal_space.open_app', [], $lang, $sourceLang)) ?></button>
                    </div>

                    <?php if ($teamEvents !== array()): ?>
                        <div class="omo-personal-space__item-list">
                            <?php foreach ($teamEvents as $event): ?>
                                <?php
                                $tagType = trim((string)($event['tagType'] ?? ''));
                                $tagLabel = $tagType === 'pro'
                                    ? t('personal_space.team.tag.pro', [], $lang, $sourceLang)
                                    : t('personal_space.team.tag.personal', [], $lang, $sourceLang);
                                ?>
                                <button
                                    type="button"
                                    class="omo-personal-space__item-button"
                                    data-omo-personal-space-user-id="<?= (int)($event['userId'] ?? 0) ?>"
                                >
                                    <span class="omo-personal-space__item-topline">
                                        <span class="omo-personal-space__item-title"><?= omoApiEscape($event['displayName'] ?? '') ?></span>
                                        <span class="omo-personal-space__tag"><?= omoApiEscape($tagLabel) ?></span>
                                    </span>
                                    <span class="omo-personal-space__item-meta"><?= omoApiEscape($event['headline'] ?? '') ?></span>
                                    <?php if (trim((string)($event['detail'] ?? '')) !== ''): ?>
                                        <span class="omo-personal-space__item-copy"><?= omoApiEscape($event['detail']) ?></span>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.team.empty', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if (!empty($enabledAppHashes['structure'])): ?>
                <section class="generic-section generic-section--stack omo-personal-space__card">
                    <div class="omo-personal-space__section-head">
                        <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(t('personal_space.section.structure', [], $lang, $sourceLang)) ?></span>
                        <button type="button" class="omo-personal-space__section-action" data-omo-personal-space-route-token="structure"><?= omoApiEscape(t('personal_space.open_app', [], $lang, $sourceLang)) ?></button>
                    </div>

                    <?php if ($historyItems !== array()): ?>
                        <div class="omo-personal-space__item-list">
                            <?php foreach ($historyItems as $historyItem): ?>
                                <?php
                                $historyDate = null;
                                $historyDateValue = trim((string)($historyItem['datecreation'] ?? ''));
                                $historyContentHtml = trim((string)($historyItem['contentHtml'] ?? ''));
                                if ($historyDateValue !== '') {
                                    try {
                                        $historyDate = new DateTimeImmutable($historyDateValue);
                                    } catch (Throwable $exception) {
                                        $historyDate = null;
                                    }
                                }
                                ?>
                                <div class="omo-personal-space__item-card">
                                    <span class="omo-personal-space__item-topline">
                                        <span class="omo-personal-space__item-title"><?= omoApiEscape($historyItem['actionLabel'] ?? '') ?></span>
                                        <span class="omo-personal-space__item-meta"><?= omoApiEscape($formatDateTime($historyDate, true)) ?></span>
                                    </span>
                                    <span class="omo-personal-space__item-copy"><?= $historyContentHtml !== '' ? nl2br($historyContentHtml) : omoApiEscape($historyItem['contentDisplay'] ?? '') ?></span>
                                    <?php if (trim((string)($historyItem['authorDisplayName'] ?? '')) !== ''): ?>
                                        <span class="omo-personal-space__item-meta"><?= omoApiEscape($historyItem['authorDisplayName']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.structure.empty', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</div>
<script>
(function () {
    var root = document.getElementById('omo-personal-space-root');
    if (!root) {
        return;
    }

    var filterControl = root.querySelector('[data-omo-personal-space-filter-control]');
    var filterPanel = root.querySelector('[data-omo-personal-space-filter-panel]');
    var scopeChip = root.querySelector('[data-omo-personal-space-filter-toggle]');
    var availableScopes = [];
    var storageKey = 'omo.personal-space.saved-views.v1';
    var temporaryStorageKey = 'omo.personal-space.session-views.v1';
    var currentScope = root.getAttribute('data-omo-personal-space-scope') || 'contextual';
    var pendingScope = null;

    try {
        availableScopes = JSON.parse(root.getAttribute('data-omo-personal-space-available-scopes') || '[]');
    } catch (error) {
        availableScopes = [];
    }
    if (!Array.isArray(availableScopes) || availableScopes.length === 0) {
        availableScopes = ['contextual'];
    }

    function contextKey() {
        return String(root.getAttribute('data-omo-personal-space-oid') || '0')
            + ':'
            + String(root.getAttribute('data-omo-personal-space-cid') || '0');
    }

    function normalizeScope(scope) {
        return availableScopes.indexOf(scope) !== -1 ? scope : 'contextual';
    }

    function readStore(key) {
        try {
            var raw = window[key === storageKey ? 'localStorage' : 'sessionStorage'].getItem(key);
            var parsed = raw ? JSON.parse(raw) : null;
            return parsed && typeof parsed === 'object'
                ? parsed
                : {defaultView: null, contexts: {}};
        } catch (error) {
            return {defaultView: null, contexts: {}};
        }
    }

    function writeStore(key, store) {
        try {
            window[key === storageKey ? 'localStorage' : 'sessionStorage'].setItem(key, JSON.stringify(store));
        } catch (error) {
        }
    }

    function clearTemporary() {
        try {
            window.sessionStorage.removeItem(temporaryStorageKey);
        } catch (error) {
        }
    }

    function getStoredView() {
        var temporary = readStore(temporaryStorageKey);
        var saved = readStore(storageKey);
        return temporary.contexts[contextKey()]
            || saved.contexts[contextKey()]
            || saved.defaultView
            || null;
    }

    function buildUrl(scope) {
        var url = root.getAttribute('data-omo-personal-space-base-url') || '';
        if (!url || scope === 'contextual') {
            return url;
        }
        return url + '&dashboard_scope=' + encodeURIComponent(scope);
    }

    function refresh(scope) {
        var nextScope = normalizeScope(scope);
        if (nextScope === currentScope) {
            root.removeAttribute('data-omo-view-filter-pending');
            return;
        }
        if (typeof window.loadContent === 'function') {
            window.loadContent('#panel-right', buildUrl(nextScope), 'panel');
        }
    }

    function syncChoices(scope) {
        var nextScope = normalizeScope(scope);
        root.querySelectorAll('[data-omo-personal-space-scope]').forEach(function (button) {
            var active = button.getAttribute('data-omo-personal-space-scope') === nextScope;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function closeMoreMenu() {
        var menu = root.querySelector('[data-omo-personal-space-filter-more-menu]');
        if (!menu) {
            return;
        }
        var panel = menu.querySelector('[data-omo-personal-space-filter-more-panel]');
        var toggle = menu.querySelector('[data-omo-personal-space-filter-more-toggle]');
        if (panel) {
            panel.hidden = true;
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    }

    function closeFilter(apply, save) {
        if (!filterPanel || filterPanel.hidden) {
            return;
        }
        filterPanel.hidden = true;
        if (scopeChip) {
            scopeChip.setAttribute('aria-expanded', 'false');
        }
        closeMoreMenu();
        if (!apply || pendingScope === null) {
            pendingScope = null;
            return;
        }
        var nextScope = normalizeScope(pendingScope);
        pendingScope = null;
        var store = readStore(storageKey);
        if (save) {
            store.contexts[contextKey()] = {scope: nextScope};
            writeStore(storageKey, store);
            clearTemporary();
        } else {
            var temporary = readStore(temporaryStorageKey);
            temporary.contexts[contextKey()] = {scope: nextScope};
            writeStore(temporaryStorageKey, temporary);
        }
        refresh(nextScope);
    }

    function openFilter() {
        if (!filterPanel || !scopeChip) {
            return;
        }
        pendingScope = currentScope;
        syncChoices(pendingScope);
        filterPanel.hidden = false;
        scopeChip.setAttribute('aria-expanded', 'true');
    }

    if (scopeChip) {
        scopeChip.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (filterPanel && filterPanel.hidden) {
                openFilter();
            } else {
                closeFilter(false, false);
            }
        });
    }

    root.querySelectorAll('[data-omo-personal-space-scope]').forEach(function (scopeButton) {
        scopeButton.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            pendingScope = normalizeScope(scopeButton.getAttribute('data-omo-personal-space-scope') || '');
            syncChoices(pendingScope);
        });
    });

    var applyButton = root.querySelector('[data-omo-personal-space-filter-apply]');
    if (applyButton) {
        applyButton.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            closeFilter(true, false);
        });
    }

    var saveButton = root.querySelector('[data-omo-personal-space-filter-save]');
    if (saveButton) {
        saveButton.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            closeFilter(true, true);
        });
    }

    var moreToggle = root.querySelector('[data-omo-personal-space-filter-more-toggle]');
    if (moreToggle) {
        moreToggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var morePanel = root.querySelector('[data-omo-personal-space-filter-more-panel]');
            var isOpen = morePanel && !morePanel.hidden;
            closeMoreMenu();
            if (morePanel && !isOpen) {
                morePanel.hidden = false;
                moreToggle.setAttribute('aria-expanded', 'true');
            }
        });
    }

    root.querySelectorAll('[data-omo-personal-space-filter-more-action]').forEach(function (moreAction) {
        moreAction.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var action = moreAction.getAttribute('data-omo-personal-space-filter-more-action') || '';
            var nextScope = normalizeScope(pendingScope === null ? currentScope : pendingScope);
            var active = readStore(storageKey);
            closeFilter(false, false);
            if (action === 'set-default') {
                active.defaultView = {scope: nextScope};
                delete active.contexts[contextKey()];
                writeStore(storageKey, active);
                clearTemporary();
                refresh(nextScope);
            } else if (action === 'apply-everywhere') {
                active.defaultView = {scope: nextScope};
                active.contexts = {};
                writeStore(storageKey, active);
                clearTemporary();
                refresh(nextScope);
            } else if (action === 'restore-default') {
                delete active.contexts[contextKey()];
                writeStore(storageKey, active);
                clearTemporary();
                refresh(active.defaultView && active.defaultView.scope ? active.defaultView.scope : 'contextual');
            }
        });
    });

    document.addEventListener('pointerdown', function (event) {
        if (filterControl && !filterControl.contains(event.target)) {
            closeFilter(true, false);
        }
    }, true);

    var storedView = getStoredView();
    var storedScope = storedView && storedView.scope ? normalizeScope(storedView.scope) : '';
    if (storedScope && storedScope !== currentScope) {
        refresh(storedScope);
        return;
    }
    root.removeAttribute('data-omo-view-filter-pending');
})();
</script>
