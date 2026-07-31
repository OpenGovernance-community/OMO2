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
<style>
    .omo-app-picker__card {
        display: flex;
        align-items: stretch;
        gap: 12px;
        transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
    }

    .omo-app-picker__card:hover {
        border-color: var(--color-primary, #4f46e5);
        transform: translateY(-1px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    .omo-app-picker__card.is-active {
        border-color: rgba(21, 128, 61, 0.25);
        background: rgba(21, 128, 61, 0.04);
    }

    .omo-app-picker__card.is-dragging {
        opacity: 0.55;
        transform: scale(0.99);
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
    }

    .omo-app-picker__card.is-drop-target {
        border-color: var(--color-primary, #4f46e5);
        box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.14);
    }

    .omo-app-picker__placeholder {
        min-height: 78px;
        border: 2px dashed color-mix(in srgb, var(--color-primary, #4f46e5) 46%, var(--topbar-panel-border, #e2e8f0));
        border-radius: var(--radius-md);
        background:
            linear-gradient(
                135deg,
                rgba(79, 70, 229, 0.08),
                rgba(79, 70, 229, 0.03)
            );
        box-sizing: border-box;
    }

    .omo-app-picker__drag {
        width: 34px;
        min-width: 34px;
        border: 0;
        border-radius: var(--radius-md);
        background: rgba(148, 163, 184, 0.14);
        color: #475569;
        cursor: grab;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        line-height: 1;
        touch-action: none;
    }

    .omo-app-picker__drag:active {
        cursor: grabbing;
    }

    .omo-app-picker__card-main {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
        width: 100%;
        cursor: pointer;
    }

    .omo-app-picker__checkbox {
        width: 18px;
        height: 18px;
        flex: 0 0 auto;
    }

    .omo-app-picker__icon {
        width: 34px;
        height: 34px;
        object-fit: contain;
        flex: 0 0 34px;
    }

    .omo-app-picker__fallback-icon {
        width: 34px;
        height: 34px;
        border-radius: var(--radius-md);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(79, 70, 229, 0.12);
        color: var(--color-primary, #4f46e5);
        font-weight: 700;
        flex: 0 0 34px;
    }

    .omo-app-picker__content {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
        width: 100%;
    }

    .omo-app-picker__content-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .omo-app-picker__state {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 8px;
        border-radius: 999px;
        background: rgba(100, 116, 139, 0.12);
        color: #475569;
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .omo-app-picker__state--active {
        background: rgba(21, 128, 61, 0.14);
        color: #15803d;
    }

    .omo-app-picker__empty {
        padding: 18px;
        color: var(--topbar-panel-muted, #64748b);
        line-height: 1.5;
    }
</style>

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

    <script>
        (function () {
            var appPickerText = <?= json_encode([
                'visible' => omoOrganizationApplicationsT('organization_applications.state.visible'),
                'hidden' => omoOrganizationApplicationsT('organization_applications.state.hidden'),
                'genericError' => omoOrganizationApplicationsT('organization_applications.error.generic'),
                'savedSimple' => omoOrganizationApplicationsT('organization_applications.status.saved_simple'),
                'saveLater' => omoOrganizationApplicationsT('organization_applications.error.save_later'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            var form = document.getElementById('omoApplicationPickerForm');
            var list = document.getElementById('omoApplicationPickerList');
            var feedback = document.getElementById('omoApplicationPickerFeedback');
            var submitButton = document.getElementById('omoApplicationPickerSubmit');

            if (!form || !list || !feedback || !submitButton) {
                return;
            }

            var clearFeedback = function () {
                feedback.textContent = '';
                feedback.classList.remove('is-success');
            };
            var showFeedback = function (message, isError) {
                if (typeof window.commonNotify === 'function') {
                    window.commonNotify(String(message || ''), isError ? 'error' : 'success');
                    clearFeedback();
                    return;
                }

                feedback.textContent = String(message || '');
                feedback.classList.toggle('is-success', !isError);
            };

            var updateCardState = function (checkbox) {
                var card = checkbox.closest('[data-omo-app-picker-card]');
                var state = card ? card.querySelector('[data-omo-app-picker-state]') : null;

                if (card) {
                    card.classList.toggle('is-active', checkbox.checked);
                }

                if (state) {
                    state.textContent = checkbox.checked ? appPickerText.visible : appPickerText.hidden;
                    state.classList.toggle('omo-app-picker__state--active', checkbox.checked);
                }
            };

            Array.prototype.forEach.call(form.querySelectorAll('.omo-app-picker__checkbox'), function (checkbox) {
                updateCardState(checkbox);
                checkbox.addEventListener('change', function () {
                    updateCardState(checkbox);
                    clearFeedback();
                });
            });

            if (typeof window.commonCreateVerticalSortableList === 'function') {
                window.commonCreateVerticalSortableList({
                    list: list,
                    itemSelector: '[data-omo-app-picker-card]',
                    handleSelector: '[data-omo-app-picker-drag]',
                    draggingClass: 'is-dragging',
                    dropTargetClass: 'is-drop-target',
                    placeholderClass: 'omo-app-picker__placeholder',
                    createPlaceholder: function (card) {
                        var placeholderCard = document.createElement('div');
                        placeholderCard.style.height = card.getBoundingClientRect().height + 'px';
                        return placeholderCard;
                    },
                    onDragStart: function () {
                        clearFeedback();
                    },
                    onDrop: function () {
                        clearFeedback();
                    }
                });
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                clearFeedback();
                submitButton.disabled = true;

                var formData = new FormData(form);
                Array.prototype.forEach.call(list.querySelectorAll('[data-omo-app-picker-card]'), function (card) {
                    var applicationId = card.getAttribute('data-omo-app-id');
                    if (!applicationId) {
                        return;
                    }

                    formData.append('order[]', applicationId);
                });

                fetch(form.getAttribute('action'), {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (!data || !data.status) {
                            showFeedback(data && data.message ? data.message : appPickerText.genericError, true);
                            submitButton.disabled = false;
                            return;
                        }

                        showFeedback(data.message || appPickerText.savedSimple, false);

                        if (typeof window.omoRefreshSidebar === 'function') {
                            window.omoRefreshSidebar(function () {
                                if (typeof window.omoRefreshMainRightPanel === 'function') {
                                    window.omoRefreshMainRightPanel();
                                }
                                if (typeof window.commonTopbarCloseModal === 'function') {
                                    window.commonTopbarCloseModal();
                                }
                            });
                            return;
                        }

                        if (typeof window.commonTopbarCloseModal === 'function') {
                            window.commonTopbarCloseModal();
                        }
                    })
                    .catch(function () {
                        showFeedback(appPickerText.saveLater, true);
                        submitButton.disabled = false;
                    });
            });
        })();
    </script>
<?php endif; ?>
