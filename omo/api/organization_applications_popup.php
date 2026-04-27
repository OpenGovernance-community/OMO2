<?php
require_once __DIR__ . '/bootstrap.php';

$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$currentUserId = (int)commonGetCurrentUserId();

if ($currentOrganizationId <= 0 || $currentUserId <= 0) {
    http_response_code(403);
    ?>
    <div class="omo-app-picker__empty">Vous devez être connecté à une organisation pour ajouter des applications.</div>
    <?php
    exit;
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

    if (count($selectedApplicationIds) === 0) {
        echo json_encode(array(
            'status' => false,
            'message' => 'Sélectionnez au moins une application.',
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
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

    $savedCount = 0;

    foreach ($selectedApplicationIds as $applicationId) {
        $application = new \dbObject\Application();
        if (!$application->load($applicationId) || !(bool)$application->get('active')) {
            continue;
        }

        if (isset($linksByApplicationId[$applicationId])) {
            $organizationApplication = $linksByApplicationId[$applicationId];
        } else {
            $organizationApplication = new \dbObject\OrganizationApplication();
            $organizationApplication->set('IDorganization', $currentOrganizationId);
            $organizationApplication->set('IDapplication', $applicationId);
        }

        if ((int)$organizationApplication->get('position') <= 0 && (int)$application->get('position') > 0) {
            $organizationApplication->set('position', (int)$application->get('position'));
        }

        $organizationApplication->set('active', true);

        $saveResult = $organizationApplication->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            echo json_encode(array(
                'status' => false,
                'message' => 'Impossible d’enregistrer les applications sélectionnées.',
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $savedCount++;
    }

    if ($savedCount === 0) {
        echo json_encode(array(
            'status' => false,
            'message' => 'Aucune application valide n’a pu être ajoutée.',
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode(array(
        'status' => true,
        'message' => $savedCount === 1 ? '1 application ajoutée.' : $savedCount . ' applications ajoutées.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

$availableApplications = array();
foreach ($allApplications as $application) {
    $applicationId = (int)$application->getId();
    if (!isset($activeApplicationIds[$applicationId])) {
        $availableApplications[] = $application;
    }
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
    }

    .omo-app-picker__title {
        font-weight: 700;
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

    .omo-app-picker__button {
        border: 0;
        border-radius: 10px;
        padding: 12px 16px;
        font-weight: 700;
        cursor: pointer;
    }

    .omo-app-picker__button--primary {
        background: var(--color-primary, #4f46e5);
        color: #ffffff;
    }

    .omo-app-picker__button--primary:disabled {
        opacity: 0.6;
        cursor: wait;
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

<?php if (count($availableApplications) === 0): ?>
    <div class="omo-app-picker__empty">
        Toutes les applications actives sont déjà affichées pour cette organisation.
    </div>
<?php else: ?>
    <form id="omoApplicationPickerForm" class="omo-app-picker" action="api/organization_applications_popup.php" method="post">
        <p class="omo-app-picker__intro">
            Sélectionnez les applications à ajouter dans la barre de gauche. La liste sera rafraîchie immédiatement après validation.
        </p>

        <div class="omo-app-picker__list">
            <?php foreach ($availableApplications as $application): ?>
                <?php
                $applicationLabel = trim((string)$application->get('label'));
                $applicationHash = trim((string)$application->getRouteHash());
                $applicationIcon = trim((string)$application->get('icon'));
                $applicationMode = trim((string)$application->getNavigationMode());
                ?>
                <label class="omo-app-picker__card">
                    <input
                        class="omo-app-picker__checkbox"
                        type="checkbox"
                        name="applications[]"
                        value="<?= (int)$application->getId() ?>"
                    >

                    <?php if ($applicationIcon !== ''): ?>
                        <img class="omo-app-picker__icon" src="<?= htmlspecialchars($applicationIcon, ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <?php else: ?>
                        <span class="omo-app-picker__fallback-icon">
                            <?= htmlspecialchars(function_exists('mb_strtoupper') ? mb_strtoupper(mb_substr($applicationLabel, 0, 1)) : strtoupper(substr($applicationLabel, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endif; ?>

                    <span class="omo-app-picker__content">
                        <span class="omo-app-picker__title"><?= htmlspecialchars($applicationLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="omo-app-picker__meta">
                            <?= htmlspecialchars($applicationHash !== '' ? '#' . $applicationHash : $applicationMode, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>

        <div id="omoApplicationPickerFeedback" class="omo-app-picker__feedback"></div>

        <div class="omo-app-picker__actions">
            <button type="submit" id="omoApplicationPickerSubmit" class="omo-app-picker__button omo-app-picker__button--primary">
                Ajouter la sélection
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

                        feedback.textContent = data.message || 'Applications ajoutées.';
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
                        feedback.textContent = 'Impossible d’enregistrer les applications pour le moment.';
                        submitButton.disabled = false;
                    });
            });
        })();
    </script>
<?php endif; ?>
