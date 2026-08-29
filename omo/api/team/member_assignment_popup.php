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

if (!$holon->canViewDetail() || !$holon->canEdit()) {
    $renderError(403, omoTeamT('team.api.no_right_modify_context', [], $lang, $sourceLang));
}

if ($holon->isOrganizationHolon()) {
    $renderError(400, omoTeamT('team.assignment_popup.invalid_assignment', [], $lang, $sourceLang));
}

$assignment = new UserHolon();
if (!$assignment->load(array(
    array('IDuser', $userId),
    array('IDholon', $holonId),
))) {
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
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    $result = $assignment->updateAssignmentDetails(array(
        'focus' => $_POST['focus'] ?? '',
        'time_budget_hours' => $_POST['time_budget_hours'] ?? '',
        'time_budget_recurrence' => $_POST['time_budget_recurrence'] ?? '',
        'money_budget' => $_POST['money_budget'] ?? '',
        'money_budget_recurrence' => $_POST['money_budget_recurrence'] ?? '',
        'assignment_review_date' => $_POST['assignment_review_date'] ?? '',
    ));

    $isSuccess = !empty($result['status']);
    if (!$isSuccess) {
        http_response_code(422);
    }

    echo json_encode(array(
        'status' => $isSuccess,
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
$refreshUrl = '/omo/api/team/index.php?oid=' . $organizationId
    . '&cid=' . $holonId
    . '&team_scope=' . rawurlencode($teamScope)
    . ($teamQuery !== '' ? '&team_query=' . rawurlencode($teamQuery) : '');
?>
<form class="omo-team-assignment-editor generic-section generic-section--plain" method="post" action="<?= omoApiEscape('/omo/api/team/member_assignment_popup.php?hid=' . $holonId . '&user_id=' . $userId) ?>">
    <div class="omo-team-assignment-editor__heading">
        <span class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape(omoTeamT('team.assignment_popup.for_member', ['name' => $assignment->getUserDisplayName($organizationId)], $lang, $sourceLang)) ?></span>
    </div>

    <div class="omo-team-assignment-editor__focus-deadline">
        <label class="omo-team-assignment-editor__field generic-form-label">
            <span><?= omoApiEscape(omoTeamT('team.member.focus', [], $lang, $sourceLang)) ?></span>
            <input type="text" name="focus" class="generic-form-control" maxlength="<?= (int)(UserHolon::attributeLength()['focus'] ?? 250) ?>" value="<?= omoApiEscape((string)$assignment->get('focus')) ?>">
            <small><?= omoApiEscape(omoTeamT('team.assignment_popup.focus.help', [], $lang, $sourceLang)) ?></small>
        </label>

        <label class="omo-team-assignment-editor__field generic-form-label">
            <span><?= omoApiEscape(omoTeamT('team.assignment_popup.review_date', [], $lang, $sourceLang)) ?></span>
            <input type="date" name="assignment_review_date" class="generic-form-control" value="<?= omoApiEscape($formatDateInput($assignment->get('assignment_review_date'))) ?>">
            <small><?= omoApiEscape(omoTeamT('team.assignment_popup.review_date.help', [], $lang, $sourceLang)) ?></small>
        </label>
    </div>

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

    <div class="omo-team-assignment-editor__actions">
        <button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape(omoTeamT('team.assignment_popup.save', [], $lang, $sourceLang)) ?></button>
        <span class="omo-team-assignment-editor__feedback" aria-live="polite"></span>
    </div>
</form>

<style>
.omo-team-assignment-editor { display: grid; gap: var(--generic-space-3, 12px); }
.omo-team-assignment-editor__heading, .omo-team-assignment-editor__field, .omo-team-assignment-editor__actions { display: grid; gap: var(--generic-space-1, 4px); }
.omo-team-assignment-editor__field small, .omo-team-assignment-editor__feedback { color: var(--color-text-light); font-size: 0.82rem; }
.omo-team-assignment-editor__focus-deadline { display: grid; grid-template-columns: minmax(0, 1fr) minmax(180px, 0.55fr); gap: var(--generic-space-3, 12px); }
.omo-team-assignment-editor__budget-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(130px, 0.72fr) minmax(0, 1fr) minmax(130px, 0.72fr); gap: var(--generic-space-3, 12px); }
.omo-team-assignment-editor__actions { grid-template-columns: auto minmax(0, 1fr); align-items: center; }
.omo-team-assignment-editor__feedback.is-error { color: #b91c1c; }
@media (max-width: 560px) { .omo-team-assignment-editor__focus-deadline, .omo-team-assignment-editor__budget-grid, .omo-team-assignment-editor__actions { grid-template-columns: 1fr; } }
</style>

<script>
(function () {
    const form = document.querySelector('.omo-team-assignment-editor');
    if (!form) return;

    const feedback = form.querySelector('.omo-team-assignment-editor__feedback');
    const submitButton = form.querySelector('button[type="submit"]');
    const refreshUrl = <?= json_encode($refreshUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (submitButton) submitButton.disabled = true;
        if (feedback) { feedback.textContent = ''; feedback.classList.remove('is-error'); }

        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'} })
            .then(function (response) {
                return response.json().catch(function () { return null; }).then(function (data) { return {ok: response.ok, data: data}; });
            })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.status) {
                    if (feedback) { feedback.textContent = result.data && result.data.message ? result.data.message : ''; feedback.classList.add('is-error'); }
                    return;
                }
                if (typeof window.omoNotify === 'function') window.omoNotify(result.data.message, 'success');
                if (typeof refreshDrawer === 'function') refreshDrawer('drawer_team', refreshUrl);
                if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
            })
            .catch(function () { if (feedback) feedback.classList.add('is-error'); })
            .finally(function () { if (submitButton) submitButton.disabled = false; });
    });
}());
</script>
