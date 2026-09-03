<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/topbar.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/omo/translations.php';

$sourceLang = [
    'timer.page.title' => [
        'text' => 'Suivi du temps',
        'context' => 'Browser title for the work time tracker.',
    ],
    'timer.page.heading' => [
        'text' => 'Temps de travail',
        'context' => 'Main heading for the work time tracker.',
    ],
    'timer.page.intro' => [
        'text' => 'Choisissez une organisation et le holon dans lequel vous travaillez, puis lancez le chronometre.',
        'context' => 'Introductory text for the work time tracker.',
    ],
    'timer.organization.heading' => [
        'text' => 'Organisation',
        'context' => 'Heading above the organization choices.',
    ],
    'timer.organization.empty' => [
        'text' => 'Aucune organisation accessible.',
        'context' => 'Message shown when the user has no accessible organization.',
    ],
    'timer.structure.heading' => [
        'text' => 'Ou travaillez-vous ?',
        'context' => 'Heading above the holon structure picker.',
    ],
    'timer.structure.loading' => [
        'text' => 'Chargement de la structure...',
        'context' => 'Loading label for the holon structure picker.',
    ],
    'timer.structure.select_holon' => [
        'text' => 'Touchez un holon pour le choisir.',
        'context' => 'Instruction shown below the holon structure picker.',
    ],
    'timer.project.heading' => [
        'text' => 'Projets',
        'context' => 'Heading above the projects for the selected holon.',
    ],
    'timer.project.status_label' => [
        'text' => 'Statut du projet',
        'context' => 'Label for the project status filter.',
    ],
    'timer.project.status.ready' => [
        'text' => 'Pret',
        'context' => 'Project status available in the work time filter.',
    ],
    'timer.project.status.in_progress' => [
        'text' => 'En cours',
        'context' => 'Project status available in the work time filter.',
    ],
    'timer.project.status.blocked' => [
        'text' => 'Bloque',
        'context' => 'Project status available in the work time filter.',
    ],
    'timer.project.status.review' => [
        'text' => 'A verifier',
        'context' => 'Project status available in the work time filter.',
    ],
    'timer.project.loading' => [
        'text' => 'Chargement des projets...',
        'context' => 'Loading message for the project list.',
    ],
    'timer.project.empty' => [
        'text' => 'Aucun projet pour ce holon avec ce statut.',
        'context' => 'Empty message for the project list.',
    ],
    'timer.project.none' => [
        'text' => 'Sans projet',
        'context' => 'Choice that records time only on the selected holon.',
    ],
    'timer.navigation.organization' => [
        'text' => 'Organisations',
        'context' => 'Label for the organization selection view.',
    ],
    'timer.navigation.holon' => [
        'text' => 'Holons',
        'context' => 'Label for the holon selection view.',
    ],
    'timer.navigation.project' => [
        'text' => 'Projets',
        'context' => 'Label for the project selection view.',
    ],
    'timer.selection.none' => [
        'text' => 'Aucune cible selectionnee',
        'context' => 'Shown in the timer control before any work target is selected.',
    ],
    'timer.selection.organization' => [
        'text' => 'Organisation : {organizationName}',
        'context' => 'Current selected organization in the timer control.',
    ],
    'timer.selection.holon' => [
        'text' => '{organizationName} / {holonName}',
        'context' => 'Current selected holon in the timer control.',
    ],
    'timer.selection.project' => [
        'text' => '{organizationName} / {holonName} / {projectName}',
        'context' => 'Current selected project in the timer control.',
    ],
    'timer.navigation.need_organization' => [
        'text' => 'Choisissez d abord une organisation.',
        'context' => 'Feedback shown when the holon view is unavailable.',
    ],
    'timer.navigation.need_holon' => [
        'text' => 'Choisissez d abord un holon.',
        'context' => 'Feedback shown when the project view is unavailable.',
    ],
    'timer.control.start' => [
        'text' => 'Commencer',
        'context' => 'Button label used to start the work timer.',
    ],
    'timer.control.stop' => [
        'text' => 'Arreter',
        'context' => 'Button label used to stop the work timer.',
    ],
    'timer.control.no_holon' => [
        'text' => 'Choisissez d abord un holon.',
        'context' => 'Message shown when the user tries to start without a holon.',
    ],
    'timer.status.ready' => [
        'text' => 'Pret a commencer',
        'context' => 'Status shown when no work timer is active.',
    ],
    'timer.status.active' => [
        'text' => 'Travail en cours',
        'context' => 'Status shown while a work timer is active.',
    ],
    'timer.status.recovered' => [
        'text' => 'Suivi repris apres interruption',
        'context' => 'Notice shown when an open timer is recovered after a page or phone interruption.',
    ],
    'timer.status.last_signal' => [
        'text' => 'Dernier enregistrement : {date}',
        'context' => 'Status line showing the last timer heartbeat.',
    ],
    'timer.error.generic' => [
        'text' => 'Le suivi du temps est momentanement indisponible.',
        'context' => 'Generic error shown when a timer request fails.',
    ],
    'timer.error.network' => [
        'text' => 'Connexion interrompue. Le dernier signal conserve reste enregistre.',
        'context' => 'Error shown after a failed heartbeat request.',
    ],
    'timer.organization.aria' => [
        'text' => 'Choisir {organizationName}',
        'context' => 'Accessible label for an organization choice.',
    ],
    'timer.app.label' => [
        'text' => 'Timer',
        'context' => 'Application label shown in the shared top bar.',
    ],
];

$lang = translationBundleInit('timer_page', omoGetTranslationLocale(), $sourceLang);
commonRestoreRememberedUser();
$currentUserId = commonGetCurrentUserId();

if ($currentUserId <= 0) {
    $loginOrganization = commonResolveOrganizationContext(1);
    commonRenderMagicLoginPage([
        'title' => t('timer.page.title'),
        'appName' => t('timer.app.label'),
        'intro' => t('timer.page.intro'),
        'returnTo' => commonNormalizeLocalPath($_SERVER['REQUEST_URI'] ?? '/timer/', '/timer/'),
        'organization' => $loginOrganization,
        'topbar' => [
            'appKey' => 'timer',
            'appLabel' => t('timer.app.label'),
            'organization' => $loginOrganization,
            'logoutReturnTo' => '/timer/',
        ],
    ]);
}

if (empty($_SESSION['timer_csrf'])) {
    $_SESSION['timer_csrf'] = bin2hex(random_bytes(32));
}

$user = new \dbObject\User();
$accessibleOrganizations = $user->load($currentUserId)
    ? $user->getAccessibleOrganizations()
    : [];
$organizationData = [];
$organizationById = [];
foreach ($accessibleOrganizations as $organization) {
    if (!($organization instanceof \dbObject\Organization)) {
        continue;
    }

    $organizationId = (int)$organization->getId();
    if ($organizationId <= 0 || isset($organizationById[$organizationId])) {
        continue;
    }

    $organizationData[] = [
        'id' => $organizationId,
        'name' => trim((string)$organization->get('name')),
        'shortname' => trim((string)$organization->get('shortname')),
        'logo' => trim((string)$organization->get('logo')),
        'color' => trim((string)$organization->get('color')),
    ];
    $organizationById[$organizationId] = $organization;
}

$activeEntry = \dbObject\WorkTime::findOpenForUser($currentUserId);
$requestedOrganizationId = (int)($_GET['oid'] ?? 0);
$selectedOrganizationId = $requestedOrganizationId > 0 && isset($organizationById[$requestedOrganizationId])
    ? $requestedOrganizationId
    : (int)($activeEntry ? $activeEntry->get('IDorganization') : 0);
if ($selectedOrganizationId <= 0 || !isset($organizationById[$selectedOrganizationId])) {
    $selectedOrganizationId = !empty($organizationData) ? (int)$organizationData[0]['id'] : 0;
}

$selectedOrganization = $selectedOrganizationId > 0 ? $organizationById[$selectedOrganizationId] : null;
$organizationContext = [
    'isValid' => $selectedOrganization instanceof \dbObject\Organization,
    'id' => $selectedOrganizationId,
    'name' => $selectedOrganization instanceof \dbObject\Organization ? (string)$selectedOrganization->get('name') : t('timer.app.label'),
    'shortname' => $selectedOrganization instanceof \dbObject\Organization ? (string)$selectedOrganization->get('shortname') : '',
    'domain' => $selectedOrganization instanceof \dbObject\Organization ? (string)$selectedOrganization->get('domain') : '',
    'logo' => $selectedOrganization instanceof \dbObject\Organization ? (string)$selectedOrganization->get('logo') : '/img/logo-OGC.png',
    'banner' => $selectedOrganization instanceof \dbObject\Organization ? (string)$selectedOrganization->get('banner') : '',
    'color' => $selectedOrganization instanceof \dbObject\Organization ? (string)$selectedOrganization->get('color') : '',
    'interface_level' => $selectedOrganization instanceof \dbObject\Organization ? $selectedOrganization->getInterfaceLevel() : 1,
    'lexicon' => $selectedOrganization instanceof \dbObject\Organization ? $selectedOrganization->getLexicon() : [],
    'host' => commonGetRequestHost(),
    'routeMode' => 'host',
    'datecreation' => '',
    'error' => null,
];

if ($selectedOrganization instanceof \dbObject\Organization) {
    $_SESSION['currentOrganization'] = $selectedOrganizationId;
}

$activeEntryData = $activeEntry instanceof \dbObject\WorkTime ? $activeEntry->toTimerArray() : null;
$currentUserName = commonGetCurrentUserDisplayName();
$profileData = commonResolveTopbarProfileData($organizationContext, []);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(omoGetTranslationLocale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= htmlspecialchars(t('timer.page.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/shared_css.css">
    <link rel="stylesheet" href="/common/assets/topbar.css">
    <link rel="stylesheet" href="/timer/assets/timer.css?v=20260903-compact-swipe-fix">
</head>
<body class="timer-page">
<?php
commonRenderTopbar([
    'appKey' => 'timer',
    'appLabel' => t('timer.app.label'),
    'userName' => $currentUserName,
    'brandLabel' => '',
    'organization' => $organizationContext,
    'logoutReturnTo' => '/timer/',
    'profile' => ['data' => $profileData],
]);
?>
<main class="timer-layout generic-page-shell">
    <?php if (count($organizationData) === 0): ?>
    <section class="timer-empty">
        <p><?= htmlspecialchars(t('timer.organization.empty')) ?></p>
    </section>
    <?php else: ?>
    <section class="timer-selector" aria-label="<?= htmlspecialchars(t('timer.organization.heading'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="timer-selector__tabs" role="tablist">
            <button type="button" class="timer-selector__tab is-active" role="tab" aria-selected="true" data-timer-pane="0"><?= htmlspecialchars(t('timer.navigation.organization')) ?></button>
            <button type="button" class="timer-selector__tab" role="tab" aria-selected="false" data-timer-pane="1"><?= htmlspecialchars(t('timer.navigation.holon')) ?></button>
            <button type="button" class="timer-selector__tab" role="tab" aria-selected="false" data-timer-pane="2"><?= htmlspecialchars(t('timer.navigation.project')) ?></button>
        </div>
        <div class="timer-selector__viewport" data-timer-swipe-viewport>
            <div class="timer-selector__track" data-timer-swipe-track>
                <section class="timer-selector__pane" data-timer-pane-content="0" aria-label="<?= htmlspecialchars(t('timer.organization.heading'), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="timer-organization-list" role="list">
                        <?php foreach ($organizationData as $organization): ?>
                        <button
                            type="button"
                            class="timer-organization-choice<?= (int)$organization['id'] === $selectedOrganizationId ? ' is-selected' : '' ?>"
                            data-timer-organization-id="<?= (int)$organization['id'] ?>"
                            aria-label="<?= htmlspecialchars(t('timer.organization.aria', ['organizationName' => $organization['name']]), ENT_QUOTES, 'UTF-8') ?>"
                            aria-pressed="<?= (int)$organization['id'] === $selectedOrganizationId ? 'true' : 'false' ?>"
                            style="--timer-organization-accent: <?= htmlspecialchars($organization['color'] !== '' ? $organization['color'] : '#2563eb', ENT_QUOTES, 'UTF-8') ?>;"
                        >
                            <?php if ($organization['logo'] !== ''): ?>
                            <img src="<?= htmlspecialchars($organization['logo'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                            <?php endif; ?>
                            <span><?= htmlspecialchars($organization['name'] !== '' ? $organization['name'] : 'Organisation') ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </section>
                <section class="timer-selector__pane timer-selector__pane--holons" data-timer-pane-content="1" aria-label="<?= htmlspecialchars(t('timer.structure.heading'), ENT_QUOTES, 'UTF-8') ?>">
                    <p class="timer-selected-holon" data-timer-selected-holon><?= htmlspecialchars(t('timer.structure.loading')) ?></p>
                    <div id="timer-holon-picker" class="timer-holon-picker" data-timer-holon-picker></div>
                </section>
                <section class="timer-selector__pane" data-timer-pane-content="2" aria-label="<?= htmlspecialchars(t('timer.project.heading'), ENT_QUOTES, 'UTF-8') ?>">
                    <label class="timer-project-filter">
                        <span><?= htmlspecialchars(t('timer.project.status_label')) ?></span>
                        <select class="generic-form-control" data-timer-project-status>
                            <option value="ready"><?= htmlspecialchars(t('timer.project.status.ready')) ?></option>
                            <option value="in_progress" selected><?= htmlspecialchars(t('timer.project.status.in_progress')) ?></option>
                            <option value="blocked"><?= htmlspecialchars(t('timer.project.status.blocked')) ?></option>
                            <option value="review"><?= htmlspecialchars(t('timer.project.status.review')) ?></option>
                        </select>
                    </label>
                    <div class="timer-project-list" data-timer-project-list aria-live="polite">
                        <p class="timer-project-message"><?= htmlspecialchars(t('timer.navigation.need_holon')) ?></p>
                    </div>
                </section>
            </div>
        </div>
    </section>

    <section class="timer-control" aria-live="polite">
        <div class="timer-status-line">
            <span class="timer-status-dot" data-timer-status-dot></span>
            <strong data-timer-status><?= htmlspecialchars($activeEntry ? t('timer.status.active') : t('timer.status.ready')) ?></strong>
        </div>
        <p class="timer-current-target" data-timer-current-target><?= htmlspecialchars(t('timer.selection.none')) ?></p>
        <div class="timer-clock" data-timer-clock>00:00:00</div>
        <button type="button" class="generic-action-button generic-action-button--main timer-toggle" data-timer-toggle>
            <span class="timer-toggle-icon" aria-hidden="true">▶</span>
            <span data-timer-toggle-label><?= htmlspecialchars($activeEntry ? t('timer.control.stop') : t('timer.control.start')) ?></span>
        </button>
        <p class="timer-feedback generic-feedback" data-timer-feedback aria-live="polite"></p>
    </section>
    <?php endif; ?>
</main>
<script>
window.timerConfig = <?= json_encode([
    'csrfToken' => (string)$_SESSION['timer_csrf'],
    'apiUrl' => '/timer/api/track.php',
    'structureUrl' => '/omo/api/getStructureData.php',
    'selectedOrganizationId' => $selectedOrganizationId,
    'organizations' => $organizationData,
    'activeEntry' => $activeEntryData,
    'translations' => [
        'active' => t('timer.status.active'),
        'ready' => t('timer.status.ready'),
        'recovered' => t('timer.status.recovered'),
        'start' => t('timer.control.start'),
        'stop' => t('timer.control.stop'),
        'noHolon' => t('timer.control.no_holon'),
        'genericError' => t('timer.error.generic'),
        'networkError' => t('timer.error.network'),
        'structureLoading' => t('timer.structure.loading'),
        'selectHolon' => t('timer.structure.select_holon'),
        'lastSignal' => t('timer.status.last_signal', ['date' => '{date}']),
        'projectLoading' => t('timer.project.loading'),
        'projectEmpty' => t('timer.project.empty'),
        'projectNone' => t('timer.project.none'),
        'selectionNone' => t('timer.selection.none'),
        'selectionOrganization' => t('timer.selection.organization', ['organizationName' => '{organizationName}']),
        'selectionHolon' => t('timer.selection.holon', ['organizationName' => '{organizationName}', 'holonName' => '{holonName}']),
        'selectionProject' => t('timer.selection.project', ['organizationName' => '{organizationName}', 'holonName' => '{holonName}', 'projectName' => '{projectName}']),
        'needOrganization' => t('timer.navigation.need_organization'),
        'needHolon' => t('timer.navigation.need_holon'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="/timer/assets/timer.js?v=20260903-swipe-panels-fix" defer></script>
</body>
</html>
