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
                'message' => 'Impossible d enregistrer la selection et l ordre des applications.',
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    $messageParts = array();
    if ($addedCount > 0) {
        $messageParts[] = $addedCount === 1 ? '1 application ajoutee.' : $addedCount . ' applications ajoutees.';
    }
    if ($removedCount > 0) {
        $messageParts[] = $removedCount === 1 ? '1 application retiree.' : $removedCount . ' applications retirees.';
    }
    if ($reorderedCount > 0) {
        $messageParts[] = $reorderedCount === 1 ? 'Ordre mis a jour.' : 'Ordre des applications mis a jour.';
    }

    echo json_encode(array(
        'status' => true,
        'message' => count($messageParts) > 0
            ? 'Configuration enregistree. ' . implode(' ', $messageParts)
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
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .omo-app-picker__card {
        display: flex;
        align-items: stretch;
        gap: 12px;
        padding: 12px 14px;
        border: 1px solid var(--topbar-panel-border, #e2e8f0);
        border-radius: 14px;
        background: var(--topbar-panel-bg, #ffffff);
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
        border-radius: 14px;
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
        border-radius: 10px;
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

<?php if (count($orderedApplications) === 0): ?>
    <div class="omo-app-picker__empty">
        Aucune application n est disponible pour le moment.
    </div>
<?php else: ?>
    <form id="omoApplicationPickerForm" class="omo-app-picker" action="api/organization_applications_popup.php" method="post">
        <p class="omo-app-picker__intro">
            Cochez les applications a afficher dans la barre de gauche, puis glissez-deposez les lignes pour definir leur ordre dans cette organisation.
        </p>

        <div id="omoApplicationPickerList" class="omo-app-picker__list">
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
                    class="omo-app-picker__card<?= $isActive ? ' is-active' : '' ?>"
                    data-omo-app-picker-card="1"
                    data-omo-app-id="<?= $applicationId ?>"
                    draggable="true"
                >
                    <button
                        type="button"
                        class="omo-app-picker__drag"
                        data-omo-app-picker-drag="1"
                        title="Reordonner"
                        aria-label="Reordonner <?= htmlspecialchars($applicationLabel, ENT_QUOTES, 'UTF-8') ?>"
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
                </div>
            <?php endforeach; ?>
        </div>

        <div id="omoApplicationPickerFeedback" class="omo-app-picker__feedback"></div>

        <div class="omo-app-picker__actions">
            <button type="submit" id="omoApplicationPickerSubmit" class="omo-app-picker__button generic-action-button generic-action-button--main">
                Enregistrer la configuration
            </button>
        </div>
    </form>

    <script>
        (function () {
            var form = document.getElementById('omoApplicationPickerForm');
            var list = document.getElementById('omoApplicationPickerList');
            var feedback = document.getElementById('omoApplicationPickerFeedback');
            var submitButton = document.getElementById('omoApplicationPickerSubmit');

            if (!form || !list || !feedback || !submitButton) {
                return;
            }

            var draggedCard = null;
            var placeholderCard = null;

            var clearFeedback = function () {
                feedback.textContent = '';
                feedback.classList.remove('is-success');
            };

            var updateCardState = function (checkbox) {
                var card = checkbox.closest('[data-omo-app-picker-card]');
                var state = card ? card.querySelector('[data-omo-app-picker-state]') : null;

                if (card) {
                    card.classList.toggle('is-active', checkbox.checked);
                }

                if (state) {
                    state.textContent = checkbox.checked ? 'Visible' : 'Masquee';
                    state.classList.toggle('omo-app-picker__state--active', checkbox.checked);
                }
            };

            var clearDropTargets = function () {
                Array.prototype.forEach.call(list.querySelectorAll('.omo-app-picker__card.is-drop-target'), function (card) {
                    card.classList.remove('is-drop-target');
                });
            };

            var removePlaceholder = function () {
                if (placeholderCard && placeholderCard.parentNode) {
                    placeholderCard.parentNode.removeChild(placeholderCard);
                }

                placeholderCard = null;
            };

            var getInsertionTarget = function (clientY) {
                var cards = Array.prototype.slice.call(list.querySelectorAll('[data-omo-app-picker-card]')).filter(function (card) {
                    return card !== draggedCard;
                });

                if (cards.length === 0) {
                    return null;
                }

                for (var index = 0; index < cards.length; index++) {
                    var card = cards[index];
                    var bounds = card.getBoundingClientRect();
                    var centerY = bounds.top + (bounds.height / 2);
                    if (clientY < centerY) {
                        return {
                            card: card,
                            placeAfter: false
                        };
                    }
                }

                return {
                    card: cards[cards.length - 1],
                    placeAfter: true
                };
            };

            Array.prototype.forEach.call(form.querySelectorAll('.omo-app-picker__checkbox'), function (checkbox) {
                updateCardState(checkbox);
                checkbox.addEventListener('change', function () {
                    updateCardState(checkbox);
                    clearFeedback();
                });
            });

            Array.prototype.forEach.call(list.querySelectorAll('[data-omo-app-picker-card]'), function (card) {
                var dragHandle = card.querySelector('[data-omo-app-picker-drag]');

                if (dragHandle) {
                    dragHandle.addEventListener('mousedown', function () {
                        card.setAttribute('data-omo-drag-ready', '1');
                    });

                    dragHandle.addEventListener('mouseup', function () {
                        card.removeAttribute('data-omo-drag-ready');
                    });

                    dragHandle.addEventListener('mouseleave', function () {
                        card.removeAttribute('data-omo-drag-ready');
                    });
                }

                card.addEventListener('dragstart', function (event) {
                    if (card.getAttribute('data-omo-drag-ready') !== '1') {
                        event.preventDefault();
                        return;
                    }

                    draggedCard = card;
                    card.classList.add('is-dragging');
                    clearFeedback();

                    placeholderCard = document.createElement('div');
                    placeholderCard.className = 'omo-app-picker__placeholder';
                    placeholderCard.style.height = card.getBoundingClientRect().height + 'px';
                    list.insertBefore(placeholderCard, card.nextSibling);

                    if (event.dataTransfer) {
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', card.getAttribute('data-omo-app-id') || '');
                    }

                    window.setTimeout(function () {
                        if (draggedCard === card) {
                            card.style.display = 'none';
                        }
                    }, 0);
                });

                card.addEventListener('dragend', function () {
                    card.classList.remove('is-dragging');
                    card.removeAttribute('data-omo-drag-ready');
                    card.style.display = '';

                    if (placeholderCard && placeholderCard.parentNode) {
                        placeholderCard.parentNode.insertBefore(card, placeholderCard);
                    }

                    removePlaceholder();
                    draggedCard = null;
                    clearDropTargets();
                });
            });

            list.addEventListener('dragover', function (event) {
                if (!draggedCard) {
                    return;
                }

                event.preventDefault();
                clearDropTargets();

                if (!placeholderCard) {
                    return;
                }

                var target = getInsertionTarget(event.clientY);
                if (!target || !target.card) {
                    list.appendChild(placeholderCard);
                    return;
                }

                target.card.classList.add('is-drop-target');

                if (target.placeAfter) {
                    list.insertBefore(placeholderCard, target.card.nextSibling);
                    return;
                }

                list.insertBefore(placeholderCard, target.card);
            });

            list.addEventListener('drop', function (event) {
                if (!draggedCard) {
                    return;
                }

                event.preventDefault();
                clearDropTargets();
                clearFeedback();

                if (placeholderCard && placeholderCard.parentNode) {
                    placeholderCard.parentNode.insertBefore(draggedCard, placeholderCard);
                }
            });

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
                            feedback.textContent = data && data.message ? data.message : 'Une erreur est survenue.';
                            submitButton.disabled = false;
                            return;
                        }

                        feedback.textContent = data.message || 'Configuration enregistree.';
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
