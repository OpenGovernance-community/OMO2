<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ControlActivity;
use dbObject\ControlTaskCheck;
use dbObject\Holon;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = (int)($_GET['cid'] ?? 0);
$context = omoActivityResolveContext($organizationId, $currentHolonId);
$activity = new ControlActivity();
$activity = !empty($context['status']) && $activity->load((int)($_GET['id'] ?? 0)) ? $activity : null;
if (!($activity instanceof ControlActivity)
    || (int)$activity->get('IDorganization') !== $organizationId
    || !omoActivityCanView($activity)
) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoActivityT('activity.error.not_found')) . '</div>';
    exit;
}

$now = new DateTimeImmutable('now');
$state = $activity->getOccurrenceState($now);
$stateKey = (string)($state['state'] ?? 'upcoming');
$suffix = $currentHolonId > 0 ? '&cid=' . $currentHolonId : '';
$editUrl = '/omo/api/activities/edit.php?oid=' . $organizationId . '&id=' . (int)$activity->getId() . $suffix;
$history = $activity->getRegularity(12, $now);
$holon = $activity->getHolon();
$timelineEntries = [];
$timelineStart = null;
$timelineEnd = $now;

foreach ($history as $entry) {
    $occurrenceAt = $entry['occurrenceAt'] ?? null;
    if (!($occurrenceAt instanceof DateTimeInterface)) {
        continue;
    }
    $occurrenceAt = DateTimeImmutable::createFromInterface($occurrenceAt);
    $check = $entry['check'] ?? null;
    $checkedAt = $check instanceof ControlTaskCheck && $check->get('checked_at') instanceof DateTimeInterface
        ? DateTimeImmutable::createFromInterface($check->get('checked_at'))
        : null;
    $markerAt = $checkedAt ?? $occurrenceAt;
    $person = $check instanceof ControlTaskCheck ? $check->getUser() : null;
    $personName = $person ? $person->getScopedDisplayName($organizationId) : '';
    $timelineStart = !$timelineStart || $markerAt < $timelineStart ? $markerAt : $timelineStart;
    $timelineStart = $occurrenceAt < $timelineStart ? $occurrenceAt : $timelineStart;
    $timelineEnd = $markerAt > $timelineEnd ? $markerAt : $timelineEnd;
    $timelineEntries[] = [
        'state' => (string)($entry['state'] ?? 'upcoming'),
        'markerAt' => $markerAt,
        'occurrenceAt' => $occurrenceAt,
        'personName' => $personName,
    ];
}

if (!$timelineStart) {
    $timelineStart = $now->modify('-1 day');
}
if ($timelineEnd <= $timelineStart) {
    $timelineEnd = $timelineStart->modify('+1 day');
}
$timelineDuration = max(1, $timelineEnd->getTimestamp() - $timelineStart->getTimestamp());
foreach ($timelineEntries as $index => $entry) {
    $timelineEntries[$index]['position'] = max(0, min(100, (($entry['markerAt']->getTimestamp() - $timelineStart->getTimestamp()) / $timelineDuration) * 100));
}

$overdueLabel = omoActivityOverdueLabel($state, $now);
$stateDetail = $overdueLabel;
if ($stateKey === 'due') {
    $occurrenceAt = $state['occurrenceAt'] ?? null;
    $stateDetail = $occurrenceAt instanceof DateTimeInterface
        ? omoActivityT(
            DateTimeImmutable::createFromInterface($occurrenceAt) > $now ? 'activity.due_soon.for' : 'activity.due.for',
            ['date' => $occurrenceAt->format('d.m.Y à H:i')]
        )
        : omoActivityStateLabel($state, $now);
} elseif ($stateKey === 'checked') {
    $check = $state['check'] ?? null;
    if ($check instanceof ControlTaskCheck && $check->get('checked_at') instanceof DateTimeInterface) {
        $stateDetail = omoActivityT('activity.checked.on', ['date' => $check->get('checked_at')->format('d.m.Y à H:i')]);
    }
} elseif ($stateKey === 'late') {
    $check = $state['check'] ?? null;
    if ($check instanceof ControlTaskCheck && $check->get('checked_at') instanceof DateTimeInterface) {
        $stateDetail = omoActivityT('activity.checked.late_on', ['date' => $check->get('checked_at')->format('d.m.Y à H:i')]);
    }
} elseif ($stateKey === 'upcoming') {
    $occurrenceAt = $state['occurrenceAt'] ?? null;
    $stateDetail = $occurrenceAt instanceof DateTimeInterface
        ? omoActivityT('activity.upcoming.on', ['date' => $occurrenceAt->format('d.m.Y à H:i')])
        : omoActivityT('activity.state.upcoming');
}
?>
<div class="omo-activity-detail generic-drawer-content">
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape((string)$activity->get('title')) ?>"
        data-omo-subdrawer-description="<?= omoApiEscape(omoActivityFrequencyLabel($activity->get('frequency')) . ' · ' . omoActivityScheduleLabel($activity->get('frequency'), $activity->get('schedule'))) ?>"
    >
        <?php if (in_array($stateKey, ['due', 'missed'], true)): ?>
            <button type="button" class="generic-action-button generic-action-button--main" data-omo-subdrawer-action data-activity-post-action="check_activity" data-activity-id="<?= (int)$activity->getId() ?>"><?= omoApiEscape(omoActivityT('activity.done')) ?></button>
        <?php endif; ?>
        <?php if (omoActivityCanEdit($activity)): ?>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-subdrawer-action data-activity-open-url="<?= omoApiEscape($editUrl) ?>"><?= omoApiEscape(omoActivityT('activity.edit')) ?></button>
        <?php endif; ?>
        <?php if (omoActivityCanDelete($activity)): ?>
            <button type="button" class="generic-action-button generic-action-button--danger generic-action-button--icon-only" data-omo-subdrawer-action data-activity-post-action="delete_activity" data-activity-id="<?= (int)$activity->getId() ?>" data-activity-confirm="<?= omoApiEscape(omoActivityT('activity.confirm.delete')) ?>" title="<?= omoApiEscape(omoActivityT('activity.delete')) ?>" aria-label="<?= omoApiEscape(omoActivityT('activity.delete')) ?>">
                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M5 7h14M10 11v6M14 11v6M9 7V5h6v2m-9 0 1 13h10l1-13"></path></svg>
            </button>
        <?php endif; ?>
    </div>

    <section class="generic-hero-panel omo-activity-detail__hero">
        <div class="omo-activity-detail__hero-copy">
            <div class="omo-activity-detail__badges">
                <span class="omo-activity-badge omo-activity-badge--<?= omoApiEscape($stateKey) ?>"><?= omoApiEscape(omoActivityStateLabel($state, $now)) ?></span>
                <?php if ($holon instanceof Holon): ?><span class="omo-pill"><?= omoApiEscape($holon->getDisplayName()) ?></span><?php endif; ?>
            </div>
            <h3 class="generic-card-title generic-card-title--large"><?= omoApiEscape((string)$activity->get('title')) ?></h3>
            <?php if ($stateDetail !== ''): ?><p class="omo-activity-detail__state-copy omo-activity-detail__state-copy--<?= omoApiEscape($stateKey) ?>"><?= omoApiEscape($stateDetail) ?></p><?php endif; ?>
            <?php if (trim((string)$activity->get('description')) !== ''): ?><p class="generic-description generic-description--relaxed"><?= nl2br(omoApiEscape((string)$activity->get('description'))) ?></p><?php endif; ?>
        </div>
        <div class="omo-activity-detail__schedule">
            <span><?= omoApiEscape(omoActivityT('activity.frequency')) ?></span>
            <strong><?= omoApiEscape(omoActivityFrequencyLabel($activity->get('frequency'))) ?></strong>
            <small><?= omoApiEscape(omoActivityScheduleLabel($activity->get('frequency'), $activity->get('schedule'))) ?></small>
        </div>
    </section>

    <section class="generic-section generic-section--stack omo-activity-timeline-section">
        <div class="omo-activity-timeline__heading">
            <div>
                <h3 class="generic-card-title"><?= omoApiEscape(omoActivityT('activity.regularity')) ?></h3>
                <p class="generic-description"><?= omoApiEscape(omoActivityT('activity.timeline.description')) ?></p>
            </div>
            <div class="omo-activity-timeline__legend" aria-label="<?= omoApiEscape(omoActivityT('activity.timeline.legend_aria')) ?>">
                <span><i class="is-checked"></i><?= omoApiEscape(omoActivityT('activity.timeline.checked')) ?></span>
                <span><i class="is-late"></i><?= omoApiEscape(omoActivityT('activity.timeline.late')) ?></span>
                <span><i class="is-missed"></i><?= omoApiEscape(omoActivityT('activity.timeline.missed')) ?></span>
                <span><i class="is-due"></i><?= omoApiEscape(omoActivityT('activity.timeline.due')) ?></span>
                <span><i class="is-upcoming"></i><?= omoApiEscape(omoActivityT('activity.timeline.upcoming')) ?></span>
            </div>
        </div>
        <div class="omo-activity-timeline__viewport">
            <div class="omo-activity-timeline" role="list" aria-label="<?= omoApiEscape(omoActivityT('activity.regularity')) ?>">
                <div class="omo-activity-timeline__rail" aria-hidden="true"></div>
                <?php foreach ($timelineEntries as $entry): ?>
                    <?php
                    $markerTitle = in_array($entry['state'], ['checked', 'late'], true)
                        ? $entry['markerAt']->format('d.m.Y à H:i') . ($entry['personName'] !== '' ? ' ' . omoActivityT('activity.timeline.by') . ' ' . $entry['personName'] : '')
                        : $entry['occurrenceAt']->format('d.m.Y à H:i');
                    ?>
                    <span class="omo-activity-timeline__marker omo-activity-timeline__marker--<?= omoApiEscape($entry['state']) ?>" style="--param-activity-timeline-position: <?= number_format((float)$entry['position'], 3, '.', '') ?>%;" title="<?= omoApiEscape($markerTitle) ?>" role="listitem">
                        <i aria-hidden="true"></i>
                        <time datetime="<?= omoApiEscape($entry['markerAt']->format(DateTimeInterface::ATOM)) ?>"><?= omoApiEscape($entry['markerAt']->format('d.m.y')) ?></time>
                        <?php if (in_array($entry['state'], ['checked', 'late'], true) && $entry['personName'] !== ''): ?><small><?= omoApiEscape($entry['personName']) ?></small><?php endif; ?>
                    </span>
                <?php endforeach; ?>
                <div class="omo-activity-timeline__scale" aria-hidden="true">
                    <time><?= omoApiEscape($timelineStart->format('d.m.Y')) ?></time>
                    <time><?= omoApiEscape($timelineEnd->format('d.m.Y')) ?></time>
                </div>
            </div>
        </div>
    </section>
</div>
