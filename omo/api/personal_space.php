<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/user_profile_ui.php';
require_once __DIR__ . '/projects/shared.php';
require_once __DIR__ . '/stats/shared.php';
require_once __DIR__ . '/dashboard/modules/registry.php';

use dbObject\Holon;
use dbObject\Organization;
use dbObject\UserHolon;
use dbObject\ApplicationSetting;

$sourceLang = [
    'personal_space.title' => [
        'text' => 'Tableau de pilotage',
        'context' => 'Main title of the dashboard panel shown on the right side of the OMO workspace.',
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
    'personal_space.edit' => [
        'text' => 'Éditer',
        'context' => 'Action opening the dashboard layout editor.',
    ],
    'personal_space.editor.title' => [
        'text' => 'Éditer le tableau de pilotage',
        'context' => 'Dashboard layout editor title.',
    ],
    'personal_space.editor.description' => [
        'text' => 'Sélectionnez une case, puis Maj + clic sur une case voisine pour créer un module double.',
        'context' => 'Dashboard layout editor instructions.',
    ],
    'personal_space.editor.save' => ['text' => 'Enregistrer', 'context' => 'Save dashboard layout action.'],
	'personal_space.editor.save_personal' => ['text' => 'Enregistrer ma vue', 'context' => 'Save the current dashboard layout as the member personal preference.'],
	'personal_space.editor.reset_personal' => ['text' => 'Revenir aux vues par défaut', 'context' => 'Remove the member personal dashboard preference and restore configured defaults.'],
	'personal_space.editor.reset_options' => ['text' => 'Autres vues par défaut à effacer', 'context' => 'Accessible label for the dashboard default layout removal menu.'],
	'personal_space.editor.reset_default' => ['text' => 'Effacer une vue par défaut', 'context' => 'Fallback action label when no personal dashboard preference can be removed.'],
	'personal_space.editor.reset_holon_default' => ['text' => 'Effacer la vue par défaut de ce holon', 'context' => 'Remove the default dashboard layout stored on the current holon.'],
	'personal_space.editor.reset_organization_template_default' => ['text' => 'Effacer la vue par défaut du modèle {templateName}', 'context' => 'Remove the organization dashboard layout stored for the directly inherited template.'],
	'personal_space.editor.reset_application_type_default' => ['text' => 'Effacer la vue par défaut de tous les {typeName}', 'context' => 'Remove the application dashboard layout stored for the current base holon type.'],
    'personal_space.editor.save_options' => ['text' => 'Autres options d enregistrement', 'context' => 'Accessible label for dashboard default save options.'],
    'personal_space.editor.save_holon_default' => ['text' => 'Enregistrer par défaut pour ce holon', 'context' => 'Save the current dashboard layout as the default for this holon.'],
    'personal_space.editor.save_organization_template_default' => ['text' => 'Enregistrer par défaut pour le modèle {templateName}', 'context' => 'Save the current dashboard layout as the organization default for the template inherited by the current holon.'],
    'personal_space.editor.save_application_type_default' => ['text' => 'Enregistrer par défaut pour tous les {typeName}', 'context' => 'Save the current dashboard layout as the application default for the current base holon type.'],
    'personal_space.editor.close' => ['text' => 'Fermer', 'context' => 'Close dashboard layout editor action.'],
    'personal_space.editor.add_row' => ['text' => 'Ajouter une ligne', 'context' => 'Add one row to dashboard layout editor.'],
    'personal_space.editor.choose' => ['text' => 'Choisir un module', 'context' => 'Choose module action in an empty dashboard grid selection.'],
    'personal_space.editor.replace' => ['text' => 'Remplacer', 'context' => 'Replace a dashboard module action.'],
    'personal_space.editor.configure' => ['text' => 'Configurer', 'context' => 'Open the dashboard module configuration dialog.'],
    'personal_space.editor.configure_title' => ['text' => 'Configurer {module}', 'context' => 'Dashboard module configuration dialog title.'],
    'personal_space.editor.configure_scope' => ['text' => 'Portée', 'context' => 'Label for the dashboard module scope setting.'],
    'personal_space.editor.configure_apply' => ['text' => 'Appliquer', 'context' => 'Apply dashboard module configuration changes.'],
    'personal_space.editor.delete' => ['text' => 'Supprimer', 'context' => 'Delete a dashboard module action.'],
    'personal_space.editor.catalog' => ['text' => 'Modules disponibles', 'context' => 'Dashboard module picker title.'],
    'personal_space.editor.cancel' => ['text' => 'Annuler', 'context' => 'Cancel dashboard module picker action.'],
    'personal_space.editor.save_error' => ['text' => 'Impossible d’enregistrer le tableau de pilotage.', 'context' => 'Dashboard layout save error.'],
    'personal_space.module.rules' => ['text' => 'Règles', 'context' => 'Dashboard rules module title.'],
    'personal_space.module.projects' => ['text' => 'Projets', 'context' => 'Dashboard projects module title.'],
    'personal_space.module.team' => ['text' => 'Team', 'context' => 'Dashboard team module title.'],
    'personal_space.module.documents' => ['text' => 'Documents', 'context' => 'Dashboard documents module title.'],
    'personal_space.module.event' => ['text' => 'Événements', 'context' => 'Dashboard events module title.'],
    'personal_space.module.structure' => ['text' => 'Structure', 'context' => 'Dashboard structure module title.'],
    'personal_space.module.stats' => ['text' => 'Indicateurs', 'context' => 'Dashboard overdue stats module title.'],
    'personal_space.module.activities' => ['text' => 'Activités', 'context' => 'Dashboard recurring activities module title.'],
    'personal_space.metric.modified' => ['text' => 'Modifiées', 'context' => 'Recently modified rules metric.'],
    'personal_space.metric.review' => ['text' => 'À revoir', 'context' => 'Rules due for review metric.'],
    'personal_space.metric.obsolete' => ['text' => 'Obsolètes', 'context' => 'Expired rules metric.'],
    'personal_space.metric.total' => ['text' => 'Total', 'context' => 'Total projects metric.'],
    'personal_space.metric.in_progress' => ['text' => 'En cours', 'context' => 'In-progress projects metric.'],
    'personal_space.metric.late' => ['text' => 'En retard', 'context' => 'Overdue projects metric.'],
    'personal_space.metric.created' => ['text' => 'Créés', 'context' => 'Recently created documents metric.'],
    'personal_space.metric.documents_modified' => ['text' => 'Modifiés', 'context' => 'Recently modified documents metric.'],
    'personal_space.metric.events_all' => ['text' => 'Planifiés sur 30 jours', 'context' => 'All planned events in the next thirty days dashboard metric.'],
    'personal_space.metric.events_mine' => ['text' => 'Pour moi', 'context' => 'Planned events visible to the current user dashboard metric.'],
    'personal_space.metric.activities_soon' => ['text' => 'Bientôt à faire', 'context' => 'Recurring activities displayed before their scheduled time.'],
    'personal_space.metric.activities_due' => ['text' => 'À faire', 'context' => 'Recurring activities that are currently due.'],
    'personal_space.metric.activities_overdue' => ['text' => 'En retard', 'context' => 'Recurring activities with an uncompleted missed occurrence.'],
    'personal_space.activities.soon_for' => ['text' => 'Bientôt à faire le {date}', 'context' => 'Date shown for an activity displayed before its planned time.'],
    'personal_space.activities.due_for' => ['text' => 'À faire depuis le {date}', 'context' => 'Date shown for an activity currently due.'],
    'personal_space.activities.overdue_for' => ['text' => 'En retard depuis le {date}', 'context' => 'Date shown for an overdue recurring activity.'],
    'personal_space.module.empty' => ['text' => 'Aucun élément à afficher.', 'context' => 'Empty dashboard module fallback.'],
    'personal_space.module.unavailable' => ['text' => 'Cette application n’est pas active dans ce contexte.', 'context' => 'Unavailable dashboard module message.'],
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
$supportedAppHashes = array('policy', 'documents', 'calendar', 'team', 'structure', 'projects', 'stats', 'activities');
$hasSupportedApp = false;
foreach ($supportedAppHashes as $supportedAppHash) {
    if (!empty($enabledAppHashes[$supportedAppHash])) {
        $hasSupportedApp = true;
        break;
    }
}

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
$dashboardAvailableScopes = omoApiGetAvailableContextScopes(
    $scopeReferenceHolon instanceof Holon,
    $scopeReferenceHolon,
    $organizationRootHolon
);
$dashboardHolonId = $scopeReferenceHolon instanceof Holon ? (int)$scopeReferenceHolon->getId() : 0;
$dashboardInterfaceLevel = $organization->getInterfaceLevel();
$dashboardSettings = $currentUserId > 0 && $dashboardHolonId > 0
    ? UserHolon::loadDashboardSettings($currentUserId, $dashboardHolonId)
    : null;
$dashboardPersonalLayout = $dashboardInterfaceLevel >= Organization::INTERFACE_LEVEL_EXPERT && $dashboardSettings instanceof UserHolon
    ? $dashboardSettings->getDashboardLayoutPreference()
    : null;
$dashboardHolonDefaultLayout = $scopeReferenceHolon instanceof Holon
    ? $scopeReferenceHolon->getDashboardDefaultLayout()
    : null;
$dashboardTemplateKey = $scopeReferenceHolon instanceof Holon
    ? $scopeReferenceHolon->getDashboardDirectTemplateLayoutKey()
    : '';
$dashboardTemplateLabel = $scopeReferenceHolon instanceof Holon
    ? $scopeReferenceHolon->getDashboardTemplateLayoutLabel()
    : '';
$dashboardBaseTypeKey = $scopeReferenceHolon instanceof Holon
    ? $scopeReferenceHolon->getDashboardBaseTypeLayoutKey()
    : '';
$dashboardBaseTypeLabel = $scopeReferenceHolon instanceof Holon
    ? $scopeReferenceHolon->getTypeLabel()
    : '';
$dashboardOrganizationTemplateLayout = $scopeReferenceHolon instanceof Holon
    ? $organization->getDashboardTemplateDefaultLayoutForHolon($scopeReferenceHolon)
    : null;
$dashboardApplicationBaseTypeLayout = $scopeReferenceHolon instanceof Holon
    ? ApplicationSetting::getDashboardBaseTypeDefaultLayoutForHolon($scopeReferenceHolon)
    : null;
$dashboardLayout = $dashboardPersonalLayout !== null
    ? $dashboardPersonalLayout
    : ($dashboardHolonDefaultLayout !== null
        ? $dashboardHolonDefaultLayout
        : ($dashboardOrganizationTemplateLayout !== null
            ? $dashboardOrganizationTemplateLayout
            : ($dashboardApplicationBaseTypeLayout !== null
                ? $dashboardApplicationBaseTypeLayout
                : UserHolon::getDefaultDashboardLayout())));
$dashboardAccess = omoDashboardViewPreferencesGetAccess($currentUserId, $organization, $scopeReferenceHolon);
$dashboardInterfaceLevel = (int)$dashboardAccess['interfaceLevel'];
$canEditDashboard = !empty($dashboardAccess['canEdit']);
$dashboardPrimarySaveScope = '';
$dashboardPrimarySaveTextKey = 'personal_space.editor.save';
$canResetDashboardPersonalLayout = false;
$canResetDashboardHolonDefault = false;
$canResetDashboardOrganizationTemplateDefault = false;
$canResetDashboardApplicationBaseTypeDefault = false;
$dashboardPrimaryResetScope = '';
$dashboardPrimaryResetTextKey = 'personal_space.editor.reset_default';
$dashboardPrimaryResetKey = '';
$canSaveDashboardHolonDefault = !empty($dashboardAccess['canSaveHolon']);
$canSaveDashboardOrganizationTemplateDefault = !empty($dashboardAccess['canSaveOrganizationTemplate']);
$canSaveDashboardApplicationBaseTypeDefault = !empty($dashboardAccess['canSaveApplicationType']);

if ($dashboardInterfaceLevel === Organization::INTERFACE_LEVEL_DISCOVERY) {
    $dashboardPrimarySaveScope = $canSaveDashboardOrganizationTemplateDefault ? 'organization_template' : 'application_type';
    $dashboardPrimarySaveTextKey = $canSaveDashboardOrganizationTemplateDefault
        ? 'personal_space.editor.save_organization_template_default'
        : 'personal_space.editor.save_application_type_default';
} elseif ($dashboardInterfaceLevel === Organization::INTERFACE_LEVEL_AUTONOMOUS) {
    $dashboardPrimarySaveScope = $canSaveDashboardHolonDefault ? 'holon' : ($canSaveDashboardOrganizationTemplateDefault ? 'organization_template' : 'application_type');
    $dashboardPrimarySaveTextKey = $dashboardPrimarySaveScope === 'holon'
        ? 'personal_space.editor.save_holon_default'
        : ($dashboardPrimarySaveScope === 'organization_template'
            ? 'personal_space.editor.save_organization_template_default'
            : 'personal_space.editor.save_application_type_default');
} else {
    $dashboardPrimarySaveScope = 'personal';
    $dashboardPrimarySaveTextKey = 'personal_space.editor.save_personal';
}
$canResetDashboardPersonalLayout = $dashboardInterfaceLevel === Organization::INTERFACE_LEVEL_EXPERT
    && !empty($dashboardAccess['canSavePersonal'])
    && $dashboardPersonalLayout !== null;
$canResetDashboardHolonDefault = $canSaveDashboardHolonDefault && $dashboardHolonDefaultLayout !== null;
$canResetDashboardOrganizationTemplateDefault = $canSaveDashboardOrganizationTemplateDefault
    && $dashboardOrganizationTemplateLayout !== null;
$canResetDashboardApplicationBaseTypeDefault = $canSaveDashboardApplicationBaseTypeDefault
    && $dashboardApplicationBaseTypeLayout !== null;

if ($canResetDashboardPersonalLayout) {
    $dashboardPrimaryResetScope = 'personal_reset';
    $dashboardPrimaryResetTextKey = 'personal_space.editor.reset_personal';
} elseif ($canResetDashboardHolonDefault) {
    $dashboardPrimaryResetScope = 'holon_reset';
    $dashboardPrimaryResetTextKey = 'personal_space.editor.reset_holon_default';
} elseif ($canResetDashboardOrganizationTemplateDefault) {
    $dashboardPrimaryResetScope = 'organization_template_reset';
    $dashboardPrimaryResetTextKey = 'personal_space.editor.reset_organization_template_default';
    $dashboardPrimaryResetKey = $dashboardTemplateKey;
} elseif ($canResetDashboardApplicationBaseTypeDefault) {
    $dashboardPrimaryResetScope = 'application_type_reset';
    $dashboardPrimaryResetTextKey = 'personal_space.editor.reset_application_type_default';
    $dashboardPrimaryResetKey = $dashboardBaseTypeKey;
}
$hasDashboardSaveOptions = ($canSaveDashboardHolonDefault && $dashboardPrimarySaveScope !== 'holon')
    || ($canSaveDashboardOrganizationTemplateDefault && $dashboardPrimarySaveScope !== 'organization_template')
    || ($canSaveDashboardApplicationBaseTypeDefault && $dashboardPrimarySaveScope !== 'application_type');
$hasDashboardResetOptions = ($canResetDashboardPersonalLayout && $dashboardPrimaryResetScope !== 'personal_reset')
    || ($canResetDashboardHolonDefault && $dashboardPrimaryResetScope !== 'holon_reset')
    || ($canResetDashboardOrganizationTemplateDefault && $dashboardPrimaryResetScope !== 'organization_template_reset')
    || ($canResetDashboardApplicationBaseTypeDefault && $dashboardPrimaryResetScope !== 'application_type_reset');
$hasDashboardMenuOptions = $hasDashboardSaveOptions || $dashboardPrimaryResetScope !== '' || $hasDashboardResetOptions;
$dashboardModuleCatalog = UserHolon::getDashboardModuleCatalog();
$dashboardModuleDefinitions = omoDashboardGetModuleDefinitions();
$dashboardCsrfToken = '';
if ($currentUserId > 0) {
    if (empty($_SESSION['omo_dashboard_layout_csrf'])) {
        $_SESSION['omo_dashboard_layout_csrf'] = bin2hex(random_bytes(32));
    }
    $dashboardCsrfToken = (string)$_SESSION['omo_dashboard_layout_csrf'];
}
$dashboardModuleLabels = array();
foreach (array_keys($dashboardModuleCatalog) as $dashboardModuleType) {
    $dashboardModuleLabels[$dashboardModuleType] = t('personal_space.module.' . $dashboardModuleType, [], $lang, $sourceLang);
}

$dashboardRouteTokens = array_map(static function (array $definition): string {
    return (string)($definition['route'] ?? '');
}, $dashboardModuleDefinitions);
$dashboardMetricLabels = array(
    'rules' => array(
        'modified' => t('personal_space.metric.modified', [], $lang, $sourceLang),
        'review' => t('personal_space.metric.review', [], $lang, $sourceLang),
        'obsolete' => t('personal_space.metric.obsolete', [], $lang, $sourceLang),
    ),
    'projects' => array(
        'total' => t('personal_space.metric.total', [], $lang, $sourceLang),
        'in_progress' => t('personal_space.metric.in_progress', [], $lang, $sourceLang),
        'late' => t('personal_space.metric.late', [], $lang, $sourceLang),
    ),
    'documents' => array(
        'created' => t('personal_space.metric.created', [], $lang, $sourceLang),
        'modified' => t('personal_space.metric.documents_modified', [], $lang, $sourceLang),
    ),
    'event' => array(
        'all' => t('personal_space.metric.events_all', [], $lang, $sourceLang),
        'mine' => t('personal_space.metric.events_mine', [], $lang, $sourceLang),
    ),
    'activities' => array(
        'soon' => t('personal_space.metric.activities_soon', [], $lang, $sourceLang),
        'due' => t('personal_space.metric.activities_due', [], $lang, $sourceLang),
        'overdue' => t('personal_space.metric.activities_overdue', [], $lang, $sourceLang),
    ),
);
?>
<div
    class="omo-personal-space omo-panel-view<?= $currentUserId <= 0 ? ' omo-personal-space--guest' : '' ?>"
    id="omo-personal-space-root"
    data-omo-personal-space-oid="<?= (int)$currentOrganizationId ?>"
    data-omo-personal-space-cid="<?= (int)$currentHolonId ?>"
    data-omo-dashboard-holon-id="<?= (int)$dashboardHolonId ?>"
    data-omo-dashboard-layout="<?= omoApiEscape(json_encode(array_values($dashboardLayout), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    data-omo-dashboard-available-scopes="<?= omoApiEscape(json_encode(array_values($dashboardAvailableScopes), JSON_UNESCAPED_SLASHES)) ?>"
    data-omo-dashboard-catalog="<?= omoApiEscape(json_encode(array_map(static function (array $catalogItem, $moduleType) use ($dashboardModuleLabels, $enabledAppHashes): array {
        return array(
            'type' => (string)$moduleType,
            'label' => (string)($dashboardModuleLabels[$moduleType] ?? $moduleType),
            'enabled' => !empty($enabledAppHashes[$catalogItem['app'] ?? '']),
            'settings' => is_array($catalogItem['settings'] ?? null) ? $catalogItem['settings'] : array(),
        );
    }, $dashboardModuleCatalog, array_keys($dashboardModuleCatalog)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    data-omo-dashboard-save-url="/omo/api/personal_space_layout.php"
    data-omo-dashboard-csrf="<?= omoApiEscape($dashboardCsrfToken) ?>"
    data-omo-dashboard-texts="<?= omoApiEscape(json_encode(array(
        'choose' => t('personal_space.editor.choose', [], $lang, $sourceLang),
        'replace' => t('personal_space.editor.replace', [], $lang, $sourceLang),
        'delete' => t('personal_space.editor.delete', [], $lang, $sourceLang),
        'configure' => t('personal_space.editor.configure', [], $lang, $sourceLang),
        'configureTitle' => t('personal_space.editor.configure_title', array('module' => '{module}'), $lang, $sourceLang),
        'configureScope' => t('personal_space.editor.configure_scope', [], $lang, $sourceLang),
        'configureApply' => t('personal_space.editor.configure_apply', [], $lang, $sourceLang),
        'cancel' => t('personal_space.editor.cancel', [], $lang, $sourceLang),
        'scopeLabels' => array(
            'contextual' => t('personal_space.scope.contextual', [], $lang, $sourceLang),
            'children' => t('personal_space.scope.children', [], $lang, $sourceLang),
            'descendants' => t('personal_space.scope.descendants', [], $lang, $sourceLang),
        ),
        'saveError' => t('personal_space.editor.save_error', [], $lang, $sourceLang),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
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
                    </div>
                    <?php if ($currentUserId <= 0): ?>
                        <p class="omo-panel-view__description"><?= omoApiEscape(t('personal_space.login_required', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($canEditDashboard && $dashboardHolonId > 0): ?>
                <div class="omo-panel-view__header-actions" data-omo-header-actions>
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-dashboard-edit><?= omoApiEscape(t('personal_space.edit', [], $lang, $sourceLang)) ?></button>
                </div>
            <?php endif; ?>
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
            <div class="omo-dashboard-grid" data-omo-dashboard-grid>
                <?php foreach ($dashboardLayout as $dashboardModule): ?>
                    <?php
                    $dashboardModuleType = (string)($dashboardModule['type'] ?? '');
                    $dashboardModuleDefinition = $dashboardModuleDefinitions[$dashboardModuleType] ?? null;
                    if (!is_array($dashboardModuleDefinition)) {
                        continue;
                    }
                    $dashboardModuleSettings = is_array($dashboardModule['settings'] ?? null)
                        ? $dashboardModule['settings']
                        : array();
                    $dashboardModuleScope = !empty($dashboardModuleCatalog[$dashboardModuleType]['settings']['scope'])
                        ? omoApiNormalizeContextScope($dashboardModuleSettings['scope'] ?? 'contextual', $dashboardAvailableScopes)
                        : 'contextual';
                    $dashboardModuleScopeHolonIds = $dashboardModuleScope === 'children'
                        ? omoApiGetDirectChildScopeHolonIds($scopeReferenceHolon)
                        : ($dashboardModuleScope === 'descendants'
                            ? omoApiGetDescendantHolonIds($scopeReferenceHolon)
                            : array($dashboardHolonId));
                    $dashboardModuleScopeHolonIdMap = count($dashboardModuleScopeHolonIds) > 0
                        ? array_fill_keys(array_map('intval', $dashboardModuleScopeHolonIds), true)
                        : array();
                    $dashboardModuleContextHolonId = $dashboardHolonId;
                    $dashboardModuleForcedOpenScope = $dashboardModuleScope === 'contextual' ? '' : $dashboardModuleScope;
                    $dashboardModuleScopeLabel = t('personal_space.scope.' . $dashboardModuleScope, [], $lang, $sourceLang);
                    $dashboardModuleEnabled = !empty($enabledAppHashes[$dashboardModuleDefinition['app'] ?? '']);
                    $dashboardModuleRouteToken = (string)($dashboardRouteTokens[$dashboardModuleType] ?? '');
                    $dashboardModuleIsTall = (int)($dashboardModule['rowSpan'] ?? 1) > 1;
                    $dashboardModuleStyle = '--omo-dashboard-row:' . ((int)$dashboardModule['row'] + 1)
                        . ';--omo-dashboard-column:' . ((int)$dashboardModule['column'] + 1)
                        . ';--omo-dashboard-row-span:' . (int)$dashboardModule['rowSpan']
                        . ';--omo-dashboard-column-span:' . (int)$dashboardModule['columnSpan'] . ';';
                    ?>
                    <section class="generic-section generic-section--stack omo-personal-space__card omo-dashboard-module omo-dashboard-module--<?= omoApiEscape($dashboardModuleType) ?><?= $dashboardModuleIsTall ? ' omo-dashboard-module--tall' : '' ?>" style="<?= omoApiEscape($dashboardModuleStyle) ?>" data-omo-dashboard-module="<?= omoApiEscape($dashboardModuleType) ?>">
                        <div class="omo-personal-space__section-head">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape($dashboardModuleLabels[$dashboardModuleType] ?? $dashboardModuleType) ?></span>
                            <span class="omo-personal-space__tag omo-dashboard-module__scope"><?= omoApiEscape($dashboardModuleScopeLabel) ?></span>
                            <?php if ($dashboardModuleEnabled && $dashboardModuleRouteToken !== ''): ?>
                                <button type="button" class="omo-personal-space__section-action" data-omo-personal-space-route-token="<?= omoApiEscape($dashboardModuleRouteToken) ?>"<?= $dashboardModuleForcedOpenScope !== '' && $dashboardModuleType !== 'structure' ? ' data-omo-personal-space-forced-scope="' . omoApiEscape($dashboardModuleForcedOpenScope) . '"' : '' ?>><?= omoApiEscape(t('personal_space.open_app', [], $lang, $sourceLang)) ?></button>
                            <?php endif; ?>
                        </div>

                        <?php if (!$dashboardModuleEnabled): ?>
                            <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.module.unavailable', [], $lang, $sourceLang)) ?></p>
                        <?php else: ?>
                            <?php include (string)$dashboardModuleDefinition['loader']; ?>
                            <?php include (string)$dashboardModuleDefinition['template']; ?>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
    <?php if ($canEditDashboard): ?>
    <div class="omo-overlay-drawer omo-dashboard-editor" data-omo-dashboard-editor hidden>
        <div class="omo-overlay-drawer__backdrop" data-omo-dashboard-editor-close></div>
        <div class="omo-overlay-drawer__panel">
            <div class="omo-overlay-drawer__header generic-drawer-header generic-drawer-header--sticky">
                <div class="generic-drawer-header__copy">
                    <h3 class="omo-overlay-drawer__title"><?= omoApiEscape(t('personal_space.editor.title', [], $lang, $sourceLang)) ?></h3>
                    <p class="omo-overlay-drawer__description"><?= omoApiEscape(t('personal_space.editor.description', [], $lang, $sourceLang)) ?></p>
                </div>
                <div class="generic-drawer-header__actions">
                    <div class="omo-dashboard-save-actions">
                        <button type="button" class="generic-action-button generic-action-button--compact generic-action-button--main" data-omo-dashboard-editor-save="<?= omoApiEscape($dashboardPrimarySaveScope) ?>"<?= str_ends_with($dashboardPrimarySaveScope, '_template') ? ' data-omo-dashboard-template-key="' . omoApiEscape($dashboardTemplateKey) . '"' : '' ?>><?= omoApiEscape(t($dashboardPrimarySaveTextKey, ['templateName' => $dashboardTemplateLabel, 'typeName' => $dashboardBaseTypeLabel], $lang, $sourceLang)) ?></button>
                        <?php if ($hasDashboardMenuOptions): ?>
                            <div class="generic-menu omo-dashboard-save-menu" data-omo-dashboard-save-menu>
                                <button type="button" class="generic-menu-toggle" data-omo-dashboard-save-menu-toggle aria-expanded="false" aria-label="<?= omoApiEscape(t('personal_space.editor.save_options', [], $lang, $sourceLang)) ?>">&#9662;</button>
                                <div class="generic-menu-panel omo-dashboard-save-menu__panel" data-omo-dashboard-save-menu-panel role="menu" hidden>
                                    <?php if ($canSaveDashboardHolonDefault && $dashboardPrimarySaveScope !== 'holon'): ?>
                                        <button type="button" class="generic-menu-item" data-omo-dashboard-save-scope="holon" role="menuitem"><?= omoApiEscape(t('personal_space.editor.save_holon_default', [], $lang, $sourceLang)) ?></button>
                                    <?php endif; ?>
                                    <?php if ($canSaveDashboardOrganizationTemplateDefault): ?>
                                        <?php if ($dashboardPrimarySaveScope !== 'organization_template'): ?>
                                            <button type="button" class="generic-menu-item" data-omo-dashboard-save-scope="organization_template" data-omo-dashboard-template-key="<?= omoApiEscape($dashboardTemplateKey) ?>" role="menuitem"><?= omoApiEscape(t('personal_space.editor.save_organization_template_default', ['templateName' => $dashboardTemplateLabel], $lang, $sourceLang)) ?></button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($canSaveDashboardApplicationBaseTypeDefault): ?>
                                        <?php if ($dashboardPrimarySaveScope !== 'application_type'): ?>
                                            <button type="button" class="generic-menu-item" data-omo-dashboard-save-scope="application_type" data-omo-dashboard-template-key="<?= omoApiEscape($dashboardBaseTypeKey) ?>" role="menuitem"><?= omoApiEscape(t('personal_space.editor.save_application_type_default', ['typeName' => $dashboardBaseTypeLabel], $lang, $sourceLang)) ?></button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($hasDashboardSaveOptions && ($dashboardPrimaryResetScope !== '' || $hasDashboardResetOptions)): ?>
                                        <div class="omo-dashboard-save-menu__separator" role="separator"></div>
                                    <?php endif; ?>
                                    <?php if ($dashboardPrimaryResetScope !== ''): ?>
                                        <button type="button" class="generic-menu-item" data-omo-dashboard-editor-reset="<?= omoApiEscape($dashboardPrimaryResetScope) ?>"<?= $dashboardPrimaryResetKey !== '' ? ' data-omo-dashboard-template-key="' . omoApiEscape($dashboardPrimaryResetKey) . '"' : '' ?> role="menuitem"><?= omoApiEscape(t($dashboardPrimaryResetTextKey, ['templateName' => $dashboardTemplateLabel, 'typeName' => $dashboardBaseTypeLabel], $lang, $sourceLang)) ?></button>
                                    <?php endif; ?>
                                    <?php if ($canResetDashboardPersonalLayout && $dashboardPrimaryResetScope !== 'personal_reset'): ?>
                                        <button type="button" class="generic-menu-item" data-omo-dashboard-editor-reset="personal_reset" role="menuitem"><?= omoApiEscape(t('personal_space.editor.reset_personal', [], $lang, $sourceLang)) ?></button>
                                    <?php endif; ?>
                                    <?php if ($canResetDashboardHolonDefault && $dashboardPrimaryResetScope !== 'holon_reset'): ?>
                                        <button type="button" class="generic-menu-item" data-omo-dashboard-editor-reset="holon_reset" role="menuitem"><?= omoApiEscape(t('personal_space.editor.reset_holon_default', [], $lang, $sourceLang)) ?></button>
                                    <?php endif; ?>
                                    <?php if ($canResetDashboardOrganizationTemplateDefault && $dashboardPrimaryResetScope !== 'organization_template_reset'): ?>
                                        <button type="button" class="generic-menu-item" data-omo-dashboard-editor-reset="organization_template_reset" data-omo-dashboard-template-key="<?= omoApiEscape($dashboardTemplateKey) ?>" role="menuitem"><?= omoApiEscape(t('personal_space.editor.reset_organization_template_default', ['templateName' => $dashboardTemplateLabel], $lang, $sourceLang)) ?></button>
                                    <?php endif; ?>
                                    <?php if ($canResetDashboardApplicationBaseTypeDefault && $dashboardPrimaryResetScope !== 'application_type_reset'): ?>
                                        <button type="button" class="generic-menu-item" data-omo-dashboard-editor-reset="application_type_reset" data-omo-dashboard-template-key="<?= omoApiEscape($dashboardBaseTypeKey) ?>" role="menuitem"><?= omoApiEscape(t('personal_space.editor.reset_application_type_default', ['typeName' => $dashboardBaseTypeLabel], $lang, $sourceLang)) ?></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="generic-action-button generic-action-button--compact generic-action-button--secondary" data-omo-dashboard-editor-close><?= omoApiEscape(t('personal_space.editor.close', [], $lang, $sourceLang)) ?></button>
                </div>
            </div>
            <div class="omo-overlay-drawer__body omo-dashboard-editor__body">
                <div class="omo-dashboard-editor__grid" data-omo-dashboard-editor-grid></div>
                <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-dashboard-add-row><?= omoApiEscape(t('personal_space.editor.add_row', [], $lang, $sourceLang)) ?></button>
            </div>
        </div>
        <div class="omo-dashboard-picker" data-omo-dashboard-picker hidden>
            <div class="omo-dashboard-picker__panel generic-soft-panel generic-soft-panel--stack" role="dialog" aria-modal="true">
                <h4 class="generic-card-title"><?= omoApiEscape(t('personal_space.editor.catalog', [], $lang, $sourceLang)) ?></h4>
                <div class="omo-dashboard-picker__list" data-omo-dashboard-picker-list></div>
                <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-dashboard-picker-close><?= omoApiEscape(t('personal_space.editor.cancel', [], $lang, $sourceLang)) ?></button>
            </div>
        </div>
        <div class="omo-dashboard-picker" data-omo-dashboard-configurator hidden>
            <div class="omo-dashboard-picker__panel generic-soft-panel generic-soft-panel--stack" role="dialog" aria-modal="true">
                <h4 class="generic-card-title" data-omo-dashboard-configurator-title></h4>
                <div class="generic-form-field">
                    <span class="generic-form-label"><?= omoApiEscape(t('personal_space.editor.configure_scope', [], $lang, $sourceLang)) ?></span>
                    <div class="omo-segmented" data-omo-dashboard-configurator-scopes></div>
                </div>
                <div class="generic-form-actions">
                    <button type="button" class="generic-action-button generic-action-button--main" data-omo-dashboard-configurator-apply><?= omoApiEscape(t('personal_space.editor.configure_apply', [], $lang, $sourceLang)) ?></button>
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-dashboard-configurator-close><?= omoApiEscape(t('personal_space.editor.cancel', [], $lang, $sourceLang)) ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<script src="/omo/assets/js/personal-space-dashboard.js?v=20260902-simple-hierarchy"></script>
