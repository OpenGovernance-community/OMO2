<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/organization_applications_access.php';

$sourceLang = [
    'organization_applications.error.no_access' => [
        'text' => 'Vous devez être connecté à une organisation pour gérer les applications.',
        'context' => 'Error shown when the current user cannot edit organization applications.',
    ],
    'organization_applications.error.save_failed' => [
        'text' => 'Impossible d’enregistrer la sélection et l’ordre des applications.',
        'context' => 'Error returned when the organization application selection cannot be saved.',
    ],
    'organization_applications.status.added_one' => [
        'text' => '1 application ajoutée.',
        'context' => 'Summary shown after enabling a single application in the left sidebar editor.',
    ],
    'organization_applications.status.added_other' => [
        'text' => '{count} applications ajoutées.',
        'context' => 'Summary shown after enabling multiple applications in the left sidebar editor.',
    ],
    'organization_applications.status.removed_one' => [
        'text' => '1 application retirée.',
        'context' => 'Summary shown after disabling a single application in the left sidebar editor.',
    ],
    'organization_applications.status.removed_other' => [
        'text' => '{count} applications retirées.',
        'context' => 'Summary shown after disabling multiple applications in the left sidebar editor.',
    ],
    'organization_applications.status.reordered_one' => [
        'text' => 'Ordre mis à jour.',
        'context' => 'Summary shown when a single ordering change happened in the left sidebar editor.',
    ],
    'organization_applications.status.reordered_other' => [
        'text' => 'Ordre des applications mis à jour.',
        'context' => 'Summary shown when ordering changes happened in the left sidebar editor.',
    ],
    'organization_applications.status.saved' => [
        'text' => 'Configuration enregistrée. {details}',
        'context' => 'Success message returned after saving organization applications with change details.',
    ],
    'organization_applications.status.no_changes' => [
        'text' => 'Aucun changement.',
        'context' => 'Success message returned when saving organization applications without any change.',
    ],
    'organization_applications.empty' => [
        'text' => 'Aucune application n’est disponible pour le moment.',
        'context' => 'Empty state shown when no application can be configured in the left sidebar editor.',
    ],
    'organization_applications.intro' => [
        'text' => 'Cochez les applications à afficher dans la barre de gauche, puis glissez-déposez les lignes pour définir leur ordre dans cette organisation.',
        'context' => 'Intro text shown in the left sidebar application editor popup.',
    ],
    'organization_applications.action.reorder' => [
        'text' => 'Réordonner',
        'context' => 'Button title used for the drag handle in the left sidebar application editor.',
    ],
    'organization_applications.state.visible' => [
        'text' => 'Visible',
        'context' => 'State label shown for active applications in the left sidebar editor.',
    ],
    'organization_applications.state.hidden' => [
        'text' => 'Masquée',
        'context' => 'State label shown for inactive applications in the left sidebar editor.',
    ],
    'organization_applications.action.save' => [
        'text' => 'Enregistrer la configuration',
        'context' => 'Primary action shown in the left sidebar application editor.',
    ],
    'organization_applications.error.generic' => [
        'text' => 'Une erreur est survenue.',
        'context' => 'Generic error shown in the left sidebar editor when the server reply is invalid.',
    ],
    'organization_applications.status.saved_simple' => [
        'text' => 'Configuration enregistrée.',
        'context' => 'Simple success message shown in the left sidebar editor.',
    ],
    'organization_applications.error.save_later' => [
        'text' => 'Impossible d’enregistrer les applications pour le moment.',
        'context' => 'Error shown in the left sidebar editor when the save request fails.',
    ],
];

$lang = omoLoadTranslationBundle('omo_organization_applications_popup', $sourceLang);

function omoOrganizationApplicationsT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$currentUserId = (int)commonGetCurrentUserId();

if (!omoCurrentUserCanManageOrganizationApplications($currentOrganizationId, $currentUserId)) {
    http_response_code(403);
    ?>
    <div class="omo-app-picker__empty"><?= htmlspecialchars(omoOrganizationApplicationsT('organization_applications.error.no_access'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php
    exit;
}

$allApplications = new \dbObject\ArrayApplication();
$allApplications->loadAvailableForOrganization($currentOrganizationId, $currentUserId);

$orderedApplications = array();
foreach ($allApplications as $application) {
    if ($application->getNavigationMode() === 'panel') {
        continue;
    }

    $orderedApplications[] = $application;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    $selectedApplicationIds = array();
    if (isset($_POST['applications']) && is_array($_POST['applications'])) {
        foreach ($_POST['applications'] as $applicationId) {
            $applicationId = (int)$applicationId;
            if ($applicationId > 0) {
                $selectedApplicationIds[$applicationId] = $applicationId;
            }
        }
    }

    $availableApplicationsById = array();
    foreach ($orderedApplications as $application) {
        $availableApplicationsById[(int)$application->getId()] = $application;
    }

    $orderedApplicationIds = array();
    if (isset($_POST['order']) && is_array($_POST['order'])) {
        foreach ($_POST['order'] as $applicationId) {
            $applicationId = (int)$applicationId;
            if ($applicationId > 0 && isset($availableApplicationsById[$applicationId])) {
                $orderedApplicationIds[$applicationId] = $applicationId;
            }
        }
    }

    foreach ($orderedApplications as $application) {
        $applicationId = (int)$application->getId();
        if (!isset($orderedApplicationIds[$applicationId])) {
            $orderedApplicationIds[$applicationId] = $applicationId;
        }
    }

    $desiredPositions = array();
    $position = 1;
    foreach (array_values($orderedApplicationIds) as $applicationId) {
        $desiredPositions[$applicationId] = $position++;
    }

    $organizationApplications = new \dbObject\ArrayOrganizationApplication();
    $organizationApplications->load(array(
        'where' => array(
            array('IDorganization', $currentOrganizationId),
        ),
    ));

    $linksByApplicationId = array();
    foreach ($organizationApplications as $organizationApplication) {
        $linksByApplicationId[(int)$organizationApplication->get('IDapplication')] = $organizationApplication;
    }

    $addedCount = 0;
    $removedCount = 0;
    $reorderedCount = 0;

    foreach ($availableApplicationsById as $applicationId => $application) {
        $shouldBeActive = isset($selectedApplicationIds[$applicationId]);
        $desiredPosition = (int)($desiredPositions[$applicationId] ?? 0);
        $organizationApplication = $linksByApplicationId[$applicationId] ?? null;
        $wasActive = $organizationApplication ? (bool)$organizationApplication->get('active') : false;
        $previousPosition = $organizationApplication ? (int)$organizationApplication->get('position') : 0;

        if (!$organizationApplication) {
            $organizationApplication = new \dbObject\OrganizationApplication();
            $organizationApplication->set('IDorganization', $currentOrganizationId);
            $organizationApplication->set('IDapplication', $applicationId);
        }

        $organizationApplication->set('active', $shouldBeActive ? 1 : 0);
        $organizationApplication->set('position', $desiredPosition);

        if ($wasActive !== $shouldBeActive) {
            if ($shouldBeActive) {
                $addedCount++;
            } else {
                $removedCount++;
            }
        }

        if ($previousPosition !== $desiredPosition) {
            $reorderedCount++;
        }

        if ($wasActive === $shouldBeActive && $previousPosition === $desiredPosition && $organizationApplication->getId() > 0) {
            continue;
        }

        $saveResult = $organizationApplication->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            echo json_encode(array(
                'status' => false,
                'message' => omoOrganizationApplicationsT('organization_applications.error.save_failed'),
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    $messageParts = array();
    if ($addedCount > 0) {
        $messageParts[] = $addedCount === 1
            ? omoOrganizationApplicationsT('organization_applications.status.added_one')
            : omoOrganizationApplicationsT('organization_applications.status.added_other', ['count' => $addedCount]);
    }
    if ($removedCount > 0) {
        $messageParts[] = $removedCount === 1
            ? omoOrganizationApplicationsT('organization_applications.status.removed_one')
            : omoOrganizationApplicationsT('organization_applications.status.removed_other', ['count' => $removedCount]);
    }
    if ($reorderedCount > 0) {
        $messageParts[] = $reorderedCount === 1
            ? omoOrganizationApplicationsT('organization_applications.status.reordered_one')
            : omoOrganizationApplicationsT('organization_applications.status.reordered_other');
    }

    echo json_encode(array(
        'status' => true,
        'message' => count($messageParts) > 0
            ? omoOrganizationApplicationsT('organization_applications.status.saved', ['details' => implode(' ', $messageParts)])
            : omoOrganizationApplicationsT('organization_applications.status.no_changes'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$activeOrganizationApplications = new \dbObject\ArrayOrganizationApplication();
$activeOrganizationApplications->load(array(
    'where' => array(
        array('IDorganization', $currentOrganizationId),
        array('active', 1),
    ),
));

$activeApplicationIds = array();
foreach ($activeOrganizationApplications as $organizationApplication) {
    $activeApplicationIds[(int)$organizationApplication->get('IDapplication')] = true;
}
?>
<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/organization_applications_popup.css') ?>">

<?php if (count($orderedApplications) === 0): ?>
    <div class="omo-app-picker__empty">
        <?= htmlspecialchars(omoOrganizationApplicationsT('organization_applications.empty'), ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php else: ?>
    <form id="omoApplicationPickerForm" class="omo-app-picker generic-stack generic-stack--flush" action="api/organization_applications_popup.php" method="post">
        <div class="omo-app-picker__header generic-drawer-header generic-drawer-header--sticky">
            <div class="generic-drawer-header__copy omo-app-picker__header-copy">
                <div class="generic-card-title generic-card-title--eyebrow">Organisation</div>
                <h3 class="generic-card-title generic-card-title--medium">Configurer les applications</h3>
            </div>
        </div>
        <div class="omo-app-picker__shell generic-drawer-content">
        <p class="omo-app-picker__intro generic-description">
            <?= htmlspecialchars(omoOrganizationApplicationsT('organization_applications.intro'), ENT_QUOTES, 'UTF-8') ?>
        </p>

        <div id="omoApplicationPickerList" class="omo-app-picker__list generic-stack">
            <?php foreach ($orderedApplications as $application): ?>
                <?php
                $applicationId = (int)$application->getId();
                $applicationLabel = trim((string)$application->get('label'));
                $applicationHash = trim((string)$application->getRouteHash());
                $applicationIcon = trim((string)$application->get('icon'));
                $applicationMode = trim((string)$application->getNavigationMode());
                $isActive = isset($activeApplicationIds[$applicationId]);
                ?>
                <div
                    class="omo-app-picker__card generic-section<?= $isActive ? ' is-active' : '' ?>"
                    data-omo-app-picker-card="1"
                    data-omo-app-id="<?= $applicationId ?>"
                    draggable="true"
                >
                    <button
                        type="button"
                        class="omo-app-picker__drag"
                        data-omo-app-picker-drag="1"
                        title="<?= htmlspecialchars(omoOrganizationApplicationsT('organization_applications.action.reorder'), ENT_QUOTES, 'UTF-8') ?>"
                        aria-label="<?= htmlspecialchars(omoOrganizationApplicationsT('organization_applications.action.reorder') . ' ' . $applicationLabel, ENT_QUOTES, 'UTF-8') ?>"
                    >&#8942;&#8942;</button>

                    <label class="omo-app-picker__card-main">
                        <input
                            class="omo-app-picker__checkbox"
                            type="checkbox"
                            name="applications[]"
                            value="<?= $applicationId ?>"
                            <?= $isActive ? 'checked' : '' ?>
                        >

                        <?php if ($applicationIcon !== ''): ?>
                            <img class="omo-app-picker__icon" src="<?= htmlspecialchars($applicationIcon, ENT_QUOTES, 'UTF-8') ?>" alt="">
                        <?php else: ?>
                            <span class="omo-app-picker__fallback-icon">
                                <?= htmlspecialchars(function_exists('mb_strtoupper') ? mb_strtoupper(mb_substr($applicationLabel, 0, 1)) : strtoupper(substr($applicationLabel, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>

                        <span class="omo-app-picker__content">
                            <span class="omo-app-picker__content-head">
                                <span class="omo-app-picker__title generic-title generic-title--item"><?= htmlspecialchars($applicationLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="omo-app-picker__state<?= $isActive ? ' omo-app-picker__state--active' : '' ?>" data-omo-app-picker-state>
                                    <?= htmlspecialchars($isActive ? omoOrganizationApplicationsT('organization_applications.state.visible') : omoOrganizationApplicationsT('organization_applications.state.hidden'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </span>
                            <span class="omo-app-picker__meta generic-meta">
                                <?= htmlspecialchars($applicationHash !== '' ? '#' . $applicationHash : $applicationMode, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="omoApplicationPickerFeedback" class="omo-app-picker__feedback generic-feedback"></div>

        <div class="omo-app-picker__actions generic-action-row">
            <button type="submit" id="omoApplicationPickerSubmit" class="omo-app-picker__button generic-action-button generic-action-button--main">
                <?= htmlspecialchars(omoOrganizationApplicationsT('organization_applications.action.save'), ENT_QUOTES, 'UTF-8') ?>
            </button>
        </div>
        </div>
    </form>

    <?= commonPageScriptTags('/omo/api/organization_applications_popup.js', [
    'appPickerText' => [
                'visible' => omoOrganizationApplicationsT('organization_applications.state.visible'),
                'hidden' => omoOrganizationApplicationsT('organization_applications.state.hidden'),
                'genericError' => omoOrganizationApplicationsT('organization_applications.error.generic'),
                'savedSimple' => omoOrganizationApplicationsT('organization_applications.status.saved_simple'),
                'saveLater' => omoOrganizationApplicationsT('organization_applications.error.save_later'),
            ],
]) ?>
<?php endif; ?>
