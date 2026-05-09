<?php
require_once __DIR__ . '/bootstrap.php';

$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$currentUserId = (int)commonGetCurrentUserId();

if ($currentOrganizationId <= 0 || $currentUserId <= 0) {
    http_response_code(403);
    ?>
    <div class="omo-app-picker__empty">Vous devez etre connecte a une organisation pour gerer les applications.</div>
    <?php
    exit;
}

$allApplications = new \dbObject\ArrayApplication();
$allApplications->load(array(
    'where' => array(
        array('active', 1),
    ),
    'order' => array(
        array('position', 'ASC'),
        array('label', 'ASC'),
    ),
));

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

    $availableApplicationsById = array();
    foreach ($allApplications as $application) {
        $availableApplicationsById[(int)$application->getId()] = $application;
    }

    $addedCount = 0;
    $removedCount = 0;

    foreach ($linksByApplicationId as $applicationId => $organizationApplication) {
        $shouldBeActive = isset($selectedApplicationIds[$applicationId]) && isset($availableApplicationsById[$applicationId]);
        $wasActive = (bool)$organizationApplication->get('active');

        if ($wasActive === $shouldBeActive) {
            continue;
        }

        $organizationApplication->set('active', $shouldBeActive);

        $saveResult = $organizationApplication->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            echo json_encode(array(
                'status' => false,
                'message' => 'Impossible d enregistrer la selection.',
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if ($shouldBeActive) {
            $addedCount++;
        } else {
            $removedCount++;
        }
    }

    foreach ($selectedApplicationIds as $applicationId) {
        if (isset($linksByApplicationId[$applicationId]) || !isset($availableApplicationsById[$applicationId])) {
            continue;
        }

        $application = $availableApplicationsById[$applicationId];
        $organizationApplication = new \dbObject\OrganizationApplication();
        $organizationApplication->set('IDorganization', $currentOrganizationId);
        $organizationApplication->set('IDapplication', $applicationId);

        if ((int)$organizationApplication->get('position') <= 0 && (int)$application->get('position') > 0) {
            $organizationApplication->set('position', (int)$application->get('position'));
        }

        $organizationApplication->set('active', true);

        $saveResult = $organizationApplication->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            echo json_encode(array(
                'status' => false,
                'message' => 'Impossible d enregistrer la selection.',
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $addedCount++;
    }

    $messageParts = array();
    if ($addedCount > 0) {
        $messageParts[] = $addedCount === 1 ? '1 application ajoutee.' : $addedCount . ' applications ajoutees.';
    }
    if ($removedCount > 0) {
        $messageParts[] = $removedCount === 1 ? '1 application retiree.' : $removedCount . ' applications retirees.';
    }

    echo json_encode(array(
        'status' => true,
        'message' => count($messageParts) > 0
            ? 'Selection enregistree. ' . implode(' ', $messageParts)
            : 'Aucun changement.',
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
    .omo-app-picker {
        display: flex;
        flex-direction: column;
        gap: 16px;
        padding: 8px 4px 4px 4px;
        color: var(--color-text, #1f2937);
    }

    .omo-app-picker__intro {
        color: var(--topbar-panel-muted, #64748b);
        line-height: 1.5;
    }

    .omo-app-picker__list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
    }

    .omo-app-picker__card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        border: 1px solid var(--topbar-panel-border, #e2e8f0);
        border-radius: 14px;
        background: var(--topbar-panel-bg, #ffffff);
        cursor: pointer;
        transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
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
        border-radius: 10px;
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

    .omo-app-picker__title {
        font-weight: 700;
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

    .omo-app-picker__meta {
        color: var(--topbar-panel-muted, #64748b);
        font-size: 0.92rem;
        word-break: break-word;
    }

    .omo-app-picker__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .omo-app-picker__feedback {
        min-height: 22px;
        color: #b91c1c;
        font-weight: 600;
    }

    .omo-app-picker__feedback.is-success {
        color: #15803d;
    }

    .omo-app-picker__empty {
        padding: 18px 6px;
        color: var(--topbar-panel-muted, #64748b);
        line-height: 1.5;
    }
</style>

<?php if (count($allApplications) === 0): ?>
    <div class="omo-app-picker__empty">
        Aucune application n est disponible pour le moment.
    </div>
<?php else: ?>
    <form id="omoApplicationPickerForm" class="omo-app-picker" action="api/organization_applications_popup.php" method="post">
        <p class="omo-app-picker__intro">
            Cochez les applications a afficher dans la barre de gauche. Decochez une application pour la retirer.
        </p>

        <div class="omo-app-picker__list">
            <?php foreach ($allApplications as $application): ?>
                <?php
                $applicationLabel = trim((string)$application->get('label'));
                $applicationHash = trim((string)$application->getRouteHash());
                $applicationIcon = trim((string)$application->get('icon'));
                $applicationMode = trim((string)$application->getNavigationMode());
                $isActive = isset($activeApplicationIds[(int)$application->getId()]);
                ?>
                <label class="omo-app-picker__card<?= $isActive ? ' is-active' : '' ?>">
                    <input
                        class="omo-app-picker__checkbox"
                        type="checkbox"
                        name="applications[]"
                        value="<?= (int)$application->getId() ?>"
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
                            <span class="omo-app-picker__title"><?= htmlspecialchars($applicationLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="omo-app-picker__state<?= $isActive ? ' omo-app-picker__state--active' : '' ?>" data-omo-app-picker-state>
                                <?= $isActive ? 'Visible' : 'Masquee' ?>
                            </span>
                        </span>
                        <span class="omo-app-picker__meta">
                            <?= htmlspecialchars($applicationHash !== '' ? '#' . $applicationHash : $applicationMode, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>

        <div id="omoApplicationPickerFeedback" class="omo-app-picker__feedback"></div>

        <div class="omo-app-picker__actions">
            <button type="submit" id="omoApplicationPickerSubmit" class="omo-app-picker__button generic-action-button generic-action-button--main">
                Enregistrer la selection
            </button>
        </div>
    </form>

    <script>
        (function () {
            var form = document.getElementById('omoApplicationPickerForm');
            var feedback = document.getElementById('omoApplicationPickerFeedback');
            var submitButton = document.getElementById('omoApplicationPickerSubmit');

            if (!form || !feedback || !submitButton) {
                return;
            }

            var updateCardState = function (checkbox) {
                var card = checkbox.closest('.omo-app-picker__card');
                var state = card ? card.querySelector('[data-omo-app-picker-state]') : null;

                if (card) {
                    card.classList.toggle('is-active', checkbox.checked);
                }

                if (state) {
                    state.textContent = checkbox.checked ? 'Visible' : 'Masquee';
                    state.classList.toggle('omo-app-picker__state--active', checkbox.checked);
                }
            };

            Array.prototype.forEach.call(form.querySelectorAll('.omo-app-picker__checkbox'), function (checkbox) {
                updateCardState(checkbox);
                checkbox.addEventListener('change', function () {
                    updateCardState(checkbox);
                    feedback.textContent = '';
                    feedback.classList.remove('is-success');
                });
            });

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                feedback.textContent = '';
                feedback.classList.remove('is-success');
                submitButton.disabled = true;

                fetch(form.getAttribute('action'), {
                    method: 'POST',
                    body: new FormData(form),
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
                            feedback.textContent = data && data.message ? data.message : 'Une erreur est survenue.';
                            submitButton.disabled = false;
                            return;
                        }

                        feedback.textContent = data.message || 'Selection enregistree.';
                        feedback.classList.add('is-success');

                        if (typeof window.omoRefreshSidebar === 'function') {
                            window.omoRefreshSidebar(function () {
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
                        feedback.textContent = 'Impossible d enregistrer les applications pour le moment.';
                        submitButton.disabled = false;
                    });
            });
        })();
    </script>
<?php endif; ?>
