<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/team/translations.php';

use dbObject\Holon;
use dbObject\Organization;
use dbObject\UserHolon;

$sourceLang = omoTeamSourceLang();
$lang = omoTeamLoadTranslationBundle();
$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_REQUEST['oid'] ?? 0));
$holonId = (int)($_REQUEST['hid'] ?? 0);
$userId = (int)($_REQUEST['user_id'] ?? 0);

$renderError = static function (int $statusCode, string $message): void {
    http_response_code($statusCode);
    echo '<div class="generic-section"><div class="omo-empty-state">' . omoApiEscape($message) . '</div></div>';
    exit;
};

if ($organizationId <= 0 || $holonId <= 0 || $userId <= 0) {
    $renderError(400, omoTeamT('team.popup.invalid_member_context', [], $lang, $sourceLang));
}

$organization = new Organization();
$holon = new Holon();
if (!$organization->load($organizationId) || !$holon->load($holonId) || !$organization->containsHolon($holon)) {
    $renderError(404, omoTeamT('team.popup.context_not_found', [], $lang, $sourceLang));
}

$canEditAssignment = $holon->isAllowed('CAN_EDIT_MEMBER_ASSIGNMENT', false);
$canEditAdmin = $holon->isAllowed('CAN_ADD_ADMIN', false);
if (!$holon->canViewDetail() || (!$canEditAssignment && !$canEditAdmin && !$holon->isAllowed('CAN_EDIT_AFFECTATION_BUDGET', false))) {
    $renderError(403, omoTeamT('team.api.no_right_modify_context', [], $lang, $sourceLang));
}

$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$hasBudgetApplication = $organization->isApplicationEnabled('budget', $currentUserId);
$canEditAssignmentBudget = $hasBudgetApplication && $holon->isAllowed('CAN_EDIT_AFFECTATION_BUDGET');

if ($holon->isOrganizationHolon()) {
    $renderError(400, omoTeamT('team.assignment_popup.invalid_assignment', [], $lang, $sourceLang));
}

$assignment = new UserHolon();
if (!$assignment->load(array(
    array('IDuser', $userId),
    array('IDholon', $holonId),
    array('is_membership', 1),
))) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && (int)$holon->get('IDtypeholon') === 2) {
        $roleAssignments = array_values(array_filter(
            $holon->getVisibleRoleAssignmentsForUser($userId, ['organizationId' => $organizationId]),
            static fn(array $item): bool => !empty($item['canEditAssignment'])
        ));
        if ($roleAssignments !== []) {
            ?>
            <div class="generic-section generic-section--plain generic-section--stack">
                <p class="generic-description"><?= omoApiEscape(omoTeamT('team.assignment_popup.choose_role', [], $lang, $sourceLang)) ?></p>
                <?php foreach ($roleAssignments as $roleAssignment): ?>
                    <?php $editorUrl = '/omo/api/team/member_assignment_popup.php?' . http_build_query([
                        'oid' => $organizationId,
                        'hid' => (int)$roleAssignment['holonId'],
                        'user_id' => $userId,
                        'team_context_hid' => $holonId,
                        'team_scope' => (string)($_GET['team_scope'] ?? 'contextual'),
                        'team_query' => (string)($_GET['team_query'] ?? ''),
                        'return_popup_url' => (string)($_GET['return_popup_url'] ?? ''),
                    ]); ?>
                    <a class="generic-action-button generic-action-button--secondary" href="<?= omoApiEscape($editorUrl) ?>" data-team-assignment-choice><?= omoApiEscape((string)$roleAssignment['displayName']) ?></a>
                <?php endforeach; ?>
            </div>
            <script>
            document.querySelectorAll('[data-team-assignment-choice]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    if (typeof window.commonTopbarRefreshModalContent !== 'function') return;
                    event.preventDefault();
                    window.commonTopbarRefreshModalContent(link.getAttribute('href'));
                });
            });
            </script>
            <?php
            exit;
        }
    }
    $renderError(404, omoTeamT('team.assignment_popup.invalid_assignment', [], $lang, $sourceLang));
}

$reasonMessages = array(
    'focus_too_long' => omoTeamT('team.assignment_popup.focus_too_long', [], $lang, $sourceLang),
    'invalid_time_budget' => omoTeamT('team.assignment_popup.invalid_budget', [], $lang, $sourceLang),
    'invalid_money_budget' => omoTeamT('team.assignment_popup.invalid_budget', [], $lang, $sourceLang),
    'invalid_time_recurrence' => omoTeamT('team.assignment_popup.invalid_recurrence', [], $lang, $sourceLang),
    'invalid_money_recurrence' => omoTeamT('team.assignment_popup.invalid_recurrence', [], $lang, $sourceLang),
    'invalid_assignment_review_date' => omoTeamT('team.assignment_popup.invalid_review_date', [], $lang, $sourceLang),
    'save_failed' => omoTeamT('team.assignment_popup.save_failed', [], $lang, $sourceLang),
    'admin_forbidden' => omoTeamT('team.api.no_right_modify_context', [], $lang, $sourceLang),
    'admin_stale' => omoTeamT('team.assignment_popup.admin_stale', [], $lang, $sourceLang),
    'admin_replacement_required' => omoTeamT('team.assignment_popup.admin_stale', [], $lang, $sourceLang),
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    $assignmentDetails = array(
        'focus' => $canEditAssignment ? ($_POST['focus'] ?? '') : $assignment->get('focus'),
        'assignment_review_date' => $canEditAssignment ? ($_POST['assignment_review_date'] ?? '') : $assignment->get('assignment_review_date'),
        'time_budget_hours' => $canEditAssignmentBudget ? ($_POST['time_budget_hours'] ?? '') : $assignment->get('time_budget_hours'),
        'time_budget_recurrence' => $canEditAssignmentBudget ? ($_POST['time_budget_recurrence'] ?? '') : $assignment->get('time_budget_recurrence'),
        'money_budget' => $canEditAssignmentBudget ? ($_POST['money_budget'] ?? '') : $assignment->get('money_budget'),
        'money_budget_recurrence' => $canEditAssignmentBudget ? ($_POST['money_budget_recurrence'] ?? '') : $assignment->get('money_budget_recurrence'),
    );
    $result = $assignment->saveAssignmentWithAdmin(
        $assignmentDetails,
        isset($_POST['admin_initial']) ? !empty($_POST['is_admin']) : null,
        is_array($_POST['admin_replacements'] ?? null) ? $_POST['admin_replacements'] : [],
        isset($_POST['admin_initial']) ? (bool)$_POST['admin_initial'] : null
    );

    $isSuccess = !empty($result['status']);
    if (!$isSuccess) {
        http_response_code(422);
    }

    echo json_encode(array(
        'status' => $isSuccess,
        'adminChanged' => $isSuccess && !empty($result['adminChanged']),
        'message' => $isSuccess
            ? omoTeamT('team.assignment_popup.save_success', [], $lang, $sourceLang)
            : ($reasonMessages[(string)($result['reason'] ?? '')] ?? omoTeamT('team.assignment_popup.save_failed', [], $lang, $sourceLang)),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$recurrences = array(
    UserHolon::BUDGET_RECURRENCE_DAY => omoTeamT('team.assignment_popup.recurrence.day', [], $lang, $sourceLang),
    UserHolon::BUDGET_RECURRENCE_WEEK => omoTeamT('team.assignment_popup.recurrence.week', [], $lang, $sourceLang),
    UserHolon::BUDGET_RECURRENCE_MONTH => omoTeamT('team.assignment_popup.recurrence.month', [], $lang, $sourceLang),
    UserHolon::BUDGET_RECURRENCE_YEAR => omoTeamT('team.assignment_popup.recurrence.year', [], $lang, $sourceLang),
);
$adminOptions = $assignment->getAdminTransitionOptions();
$showAdminCheckbox = $canEditAdmin && !empty($adminOptions['eligible']) && $adminOptions['max'] !== 0;
$organizationLexicon = $organization->getLexicon();
$adminLabel = trim((string)($organizationLexicon['admin']['label'] ?? '')) ?: 'Admin';
$formatBudget = static function ($value): string {
    if (!is_numeric($value)) {
        return '';
    }

    return rtrim(rtrim(number_format((float)$value, 2, '.', ''), '0'), '.');
};
$formatDateInput = static function ($value): string {
    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d');
    }

    $value = trim((string)$value);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
};
$teamScope = trim((string)($_GET['team_scope'] ?? 'contextual'));
$teamScope = in_array($teamScope, array('contextual', 'children', 'descendants'), true) ? $teamScope : 'contextual';
$teamQuery = trim((string)($_GET['team_query'] ?? ''));
$teamContextHolonId = $holonId;
$requestedTeamContextId = (int)($_GET['team_context_hid'] ?? 0);
if ($requestedTeamContextId > 0 && $requestedTeamContextId !== $holonId) {
    $teamContextHolon = new Holon();
    if ($teamContextHolon->load($requestedTeamContextId)
        && $organization->containsHolon($teamContextHolon)
        && $teamContextHolon->canViewDetail()) {
        $teamContextHolonId = $requestedTeamContextId;
    }
}
$refreshUrl = '/omo/api/team/index.php?oid=' . $organizationId
    . '&cid=' . $teamContextHolonId
    . '&team_scope=' . rawurlencode($teamScope)
    . ($teamQuery !== '' ? '&team_query=' . rawurlencode($teamQuery) : '');
$returnPopupUrl = trim((string)($_GET['return_popup_url'] ?? ''));
$returnPopupParts = $returnPopupUrl !== '' ? parse_url($returnPopupUrl) : false;
$returnPopupQuery = array();
if (is_array($returnPopupParts) && isset($returnPopupParts['query'])) {
    parse_str((string)$returnPopupParts['query'], $returnPopupQuery);
}
$canReturnToUserPopup = is_array($returnPopupParts)
    && (string)($returnPopupParts['path'] ?? '') === '/popup/user.php'
    && (int)($returnPopupQuery['id'] ?? 0) === $userId
    && (int)($returnPopupQuery['oid'] ?? 0) === $organizationId;
?>
<form class="omo-team-assignment-editor generic-section generic-section--plain" method="post" action="<?= omoApiEscape('/omo/api/team/member_assignment_popup.php?oid=' . $organizationId . '&hid=' . $holonId . '&user_id=' . $userId) ?>">
    <div class="omo-team-assignment-editor__heading">
        <span class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape(omoTeamT('team.assignment_popup.for_member', ['name' => $assignment->getUserDisplayName($organizationId)], $lang, $sourceLang)) ?></span>
    </div>

    <?php if ($showAdminCheckbox): ?>
    <div class="generic-setting-row generic-setting-row--top">
        <label class="generic-checkbox">
            <input type="hidden" name="admin_initial" value="<?= $adminOptions['isAdmin'] ? '1' : '0' ?>">
            <input type="checkbox" name="is_admin" value="1"<?= $adminOptions['isAdmin'] ? ' checked' : '' ?>>
            <span><?= omoApiEscape(omoTeamT('team.assignment_popup.admin_label', ['adminLabel' => $adminLabel], $lang, $sourceLang)) ?></span>
        </label>
        <?php foreach (['grant', 'revoke'] as $transition): ?>
        <?php $plan = $adminOptions[$transition]; ?>
        <div class="generic-form-field" data-admin-transition="<?= $transition ?>" data-required="<?= (int)$plan['required'] ?>" data-choices="<?= (int)$plan['choices'] ?>" data-blocked="<?= !empty($plan['blocked']) ? '1' : '0' ?>" hidden>
            <?php if (!empty($plan['blocked'])): ?>
                <small class="generic-help-text"><?= omoApiEscape(omoTeamT('team.assignment_popup.admin_' . ($transition === 'grant' ? 'blocked' : 'successor_required'), [], $lang, $sourceLang)) ?></small>
            <?php elseif ($plan['choices'] > 0): ?>
                <?php for ($choice = 0; $choice < $plan['choices']; $choice++): ?>
                <div class="generic-form-field">
                    <div class="generic-heading-with-help">
                        <label class="generic-form-label" for="assignment-admin-<?= $transition ?>-<?= $choice ?>"><?= omoApiEscape(omoTeamT('team.assignment_popup.admin_select_' . $transition, [], $lang, $sourceLang)) ?></label>
                        <?php if ($choice === 0): ?>
                        <details class="generic-context-help generic-context-help--compact">
                            <summary aria-label="<?= omoApiEscape(omoTeamT('team.assignment_popup.admin_replacement_help', [], $lang, $sourceLang)) ?>">?</summary>
                            <div class="generic-context-help__content"><?= omoApiEscape(omoTeamT('team.assignment_popup.admin_' . (!empty($plan['vacant']) ? 'vacant' : $transition), ['count' => (int)$plan['required']], $lang, $sourceLang)) ?></div>
                        </details>
                        <?php endif; ?>
                    </div>
                    <select name="admin_replacements[]" id="assignment-admin-<?= $transition ?>-<?= $choice ?>" class="generic-form-control" disabled>
                        <option value=""><?= omoApiEscape(omoTeamT('team.assignment_popup.admin_' . ($plan['required'] > 0 ? 'choose' : 'none'), [], $lang, $sourceLang)) ?></option>
                        <?php foreach (['context', 'organization'] as $group): ?>
                        <?php $candidates = array_filter($plan['candidates'], static fn(array $candidate): bool => $candidate['group'] === $group); ?>
                        <?php if ($candidates !== []): ?>
                        <optgroup label="<?= omoApiEscape(omoTeamT('team.assignment_popup.admin_group_' . $group, [], $lang, $sourceLang)) ?>">
                            <?php foreach ($candidates as $candidate): ?>
                            <option value="<?= (int)$candidate['userId'] ?>"><?= omoApiEscape($candidate['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="omo-team-assignment-editor__focus-deadline">
        <label class="omo-team-assignment-editor__field generic-form-label">
            <span><?= omoApiEscape(omoTeamT('team.member.focus', [], $lang, $sourceLang)) ?></span>
            <input type="text" name="focus" <?= $canEditAssignment ? '' : 'readonly' ?> class="generic-form-control" maxlength="<?= (int)(UserHolon::attributeLength()['focus'] ?? 250) ?>" value="<?= omoApiEscape((string)$assignment->get('focus')) ?>">
            <small><?= omoApiEscape(omoTeamT('team.assignment_popup.focus.help', [], $lang, $sourceLang)) ?></small>
        </label>

        <label class="omo-team-assignment-editor__field generic-form-label">
            <span><?= omoApiEscape(omoTeamT('team.assignment_popup.review_date', [], $lang, $sourceLang)) ?></span>
            <input type="date" name="assignment_review_date" <?= $canEditAssignment ? '' : 'readonly' ?> class="generic-form-control" value="<?= omoApiEscape($formatDateInput($assignment->get('assignment_review_date'))) ?>">
            <small><?= omoApiEscape(omoTeamT('team.assignment_popup.review_date.help', [], $lang, $sourceLang)) ?></small>
        </label>
    </div>

    <?php if ($canEditAssignmentBudget): ?>
    <div class="omo-team-assignment-editor__budget-grid">
        <label class="omo-team-assignment-editor__field generic-form-label">
            <span><?= omoApiEscape(omoTeamT('team.assignment_popup.time_budget', [], $lang, $sourceLang)) ?></span>
            <input type="number" name="time_budget_hours" class="generic-form-control" min="0" max="9999999999.99" step="0.01" inputmode="decimal" value="<?= omoApiEscape($formatBudget($assignment->get('time_budget_hours'))) ?>">
            <small><?= omoApiEscape(omoTeamT('team.assignment_popup.time_budget.help', [], $lang, $sourceLang)) ?></small>
        </label>
        <label class="omo-team-assignment-editor__field generic-form-label">
            <span><?= omoApiEscape(omoTeamT('team.assignment_popup.recurrence', [], $lang, $sourceLang)) ?></span>
            <select name="time_budget_recurrence" class="generic-form-control">
                <?php foreach ($recurrences as $value => $label): ?>
                    <option value="<?= omoApiEscape($value) ?>"<?= (string)$assignment->get('time_budget_recurrence') === $value ? ' selected' : '' ?>><?= omoApiEscape($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="omo-team-assignment-editor__field generic-form-label">
            <span><?= omoApiEscape(omoTeamT('team.assignment_popup.money_budget', [], $lang, $sourceLang)) ?></span>
            <input type="number" name="money_budget" class="generic-form-control" min="0" max="9999999999.99" step="0.01" inputmode="decimal" value="<?= omoApiEscape($formatBudget($assignment->get('money_budget'))) ?>">
        </label>
        <label class="omo-team-assignment-editor__field generic-form-label">
            <span><?= omoApiEscape(omoTeamT('team.assignment_popup.recurrence', [], $lang, $sourceLang)) ?></span>
            <select name="money_budget_recurrence" class="generic-form-control">
                <?php foreach ($recurrences as $value => $label): ?>
                    <option value="<?= omoApiEscape($value) ?>"<?= (string)$assignment->get('money_budget_recurrence') === $value ? ' selected' : '' ?>><?= omoApiEscape($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <?php endif; ?>

    <div class="omo-team-assignment-editor__actions">
        <span class="omo-team-assignment-editor__feedback" aria-live="polite"></span>
        <div class="omo-team-assignment-editor__buttons">
            <button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape(omoTeamT('team.assignment_popup.save', [], $lang, $sourceLang)) ?></button>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-assignment-cancel><?= omoApiEscape(omoTeamT('team.assignment_popup.cancel', [], $lang, $sourceLang)) ?></button>
        </div>
    </div>
</form>

<link rel="stylesheet" href="<?= commonAssetUrl('/common/team/member-assignment.css') ?>">

<script>
(function () {
    const form = document.querySelector('.omo-team-assignment-editor');
    if (!form) return;

    const feedback = form.querySelector('.omo-team-assignment-editor__feedback');
    const submitButton = form.querySelector('button[type="submit"]');
    const cancelButton = form.querySelector('[data-assignment-cancel]');
    const refreshUrl = <?= json_encode($refreshUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const returnPopupUrl = <?= json_encode($canReturnToUserPopup ? $returnPopupUrl : '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const adminCheckbox = form.querySelector('[name="is_admin"]');
    const initialAdmin = <?= !empty($adminOptions['isAdmin']) ? 'true' : 'false' ?>;
    const updateAdminTransition = function () {
        if (!adminCheckbox) return true;
        let valid = true;
        form.querySelectorAll('[data-admin-transition]').forEach(function (panel) {
            const active = adminCheckbox.checked !== initialAdmin
                && panel.dataset.adminTransition === (adminCheckbox.checked ? 'grant' : 'revoke');
            panel.hidden = !active;
            const selects = Array.from(panel.querySelectorAll('select'));
            const selected = selects.map(function (select) { return select.value; }).filter(Boolean);
            selects.forEach(function (select, index) {
                select.disabled = !active;
                select.required = active && index < Number(panel.dataset.required);
                Array.from(select.options).forEach(function (option) {
                    option.disabled = option.value !== '' && option.value !== select.value && selected.includes(option.value);
                });
            });
            if (active && (panel.dataset.blocked === '1'
                || selected.length < Number(panel.dataset.required)
                || (selected.length > 0 && selected.length !== Number(panel.dataset.choices))
                || new Set(selected).size !== selected.length)) valid = false;
        });
        submitButton.disabled = !valid;
        return valid;
    };
    if (adminCheckbox) {
        adminCheckbox.addEventListener('change', updateAdminTransition);
        form.querySelectorAll('[name="admin_replacements[]"]').forEach(function (input) {
            input.addEventListener('change', updateAdminTransition);
        });
        updateAdminTransition();
    }
    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            if (returnPopupUrl !== '' && typeof window.commonTopbarRefreshModalContent === 'function') {
                window.commonTopbarRefreshModalContent(returnPopupUrl);
                return;
            }
            if (typeof window.commonTopbarCloseModal === 'function') {
                window.commonTopbarCloseModal();
            }
        });
    }
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!updateAdminTransition()) return;
        if (submitButton) submitButton.disabled = true;
        if (feedback) { feedback.textContent = ''; feedback.classList.remove('is-error'); }

        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'} })
            .then(function (response) {
                return response.json().catch(function () { return null; }).then(function (data) { return {ok: response.ok, data: data}; });
            })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.status) {
                    window.omoNotify(result.data && result.data.message ? result.data.message : <?= json_encode(omoTeamT('team.assignment_popup.save_failed', [], $lang, $sourceLang)) ?>, 'error');
                    return;
                }
                if (typeof window.omoNotify === 'function') window.omoNotify(result.data.message, 'success');
                if (result.data.adminChanged) {
                    if (typeof loadContent === 'function') {
                        loadContent(typeof omoGetLeftPanelContentSelector === 'function' ? omoGetLeftPanelContentSelector() : '#panel-left',
                            'api/getOrg.php?oid=<?= $organizationId ?>&cid=<?= $teamContextHolonId ?>');
                    }
                    if (typeof window.omoReloadStructureAndFocus === 'function') {
                        window.omoReloadStructureAndFocus(<?= $teamContextHolonId ?>, {quickZoom: true});
                    }
                }
                if (returnPopupUrl !== '' && typeof window.commonTopbarRefreshModalContent === 'function') {
                    window.commonTopbarRefreshModalContent(returnPopupUrl);
                    return;
                }
                if (typeof refreshDrawer === 'function') refreshDrawer('drawer_team', refreshUrl);
                if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
            })
            .catch(function () { window.omoNotify(<?= json_encode(omoTeamT('team.assignment_popup.save_failed', [], $lang, $sourceLang)) ?>, 'error'); })
            .finally(function () { if (submitButton) submitButton.disabled = false; updateAdminTransition(); });
    });
}());
</script>
