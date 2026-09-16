<?php
// Public booking presentation, rendered only through index.php.
if (!isset($csrf, $monthKeys)) { http_response_code(404); exit; }
function meetingIcon(string $name): string
{
    $paths = [
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18"/>',
        'repeat' => '<path d="m17 2 4 4-4 4M3 11V9a3 3 0 0 1 3-3h15M7 22l-4-4 4-4m14-1v2a3 3 0 0 1-3 3H3"/>',
        'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'previous' => '<path d="m14 6-6 6 6 6"/>', 'next' => '<path d="m10 6 6 6-6 6"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'user' => '<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2Z"/>',
        'note' => '<path d="M14 2H5v20h14V7Zm0 0v5h5M8 12h8m-8 4h6"/>',
    ];
    return '<svg class="meeting-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($paths[$name] ?? $paths['calendar']) . '</svg>';
}
$formatDay = static fn(DateTimeInterface $date): string => $date->format('j') . ' ' . mb_strtolower(meetingT($monthKeys[(int)$date->format('n')])) . ' ' . $date->format('Y');
$displayName = $ownerName === '' ? '' : mb_strtoupper(mb_substr($ownerName, 0, 1)) . mb_substr($ownerName, 1);
$activeStep = $receipt || $draft ? 3 : ($selectedSlot ? 2 : 1);
$currentMonth = $now->modify('first day of this month')->setTime(0, 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow">
    <title><?= meetingEscape(meetingT('title')) ?> - OMO</title>
    <link rel="stylesheet" href="/common/assets/components.css?v=<?= (int)filemtime(dirname(__DIR__) . '/common/assets/components.css') ?>"><link rel="stylesheet" href="/meeting/meeting.css?v=<?= (int)filemtime(__DIR__ . '/meeting.css') ?>">
</head>
<body class="meeting-page">
<main class="generic-page-shell meeting-shell">
    <div class="meeting-topbar">
        <a class="meeting-brand" href="<?= meetingEscape($path) ?>" aria-label="<?= meetingEscape(meetingT('title')) ?>"><?= meetingIcon('calendar') ?><span>OMO<span class="meeting-brand__label"><?= meetingEscape(meetingT('title')) ?></span></span></a>
        <?php if ($profile): ?>
        <ol class="meeting-steps" aria-label="<?= meetingEscape(meetingT('steps')) ?>">
            <?php foreach ([1 => 'step_time', 2 => 'step_details', 3 => 'step_confirm'] as $step => $label): ?>
                <li <?= $activeStep === $step ? 'aria-current="step"' : '' ?> data-complete="<?= $activeStep > $step || $receipt ? 'true' : 'false' ?>"><span class="meeting-steps__number"><?= $activeStep > $step || $receipt ? meetingIcon('check') : $step ?></span><span><?= meetingEscape(meetingT($label)) ?></span></li>
            <?php endforeach; ?>
        </ol>
        <?php endif; ?>
    </div>
    <header class="generic-soft-panel generic-soft-panel--elevated meeting-host">
        <?php if ($ownerName): ?>
            <div class="meeting-avatar" aria-hidden="true"><span><?= meetingEscape(\dbObject\User::buildInitials($ownerName)) ?></span><?php if ($ownerPhoto): ?><img src="<?= meetingEscape($ownerPhoto) ?>" alt="" width="96" height="96" data-meeting-avatar referrerpolicy="no-referrer"><?php endif; ?></div>
        <?php endif; ?>
        <div class="meeting-host__copy">
            <h1 class="generic-card-title generic-card-title--display"><?= meetingEscape($displayName ? meetingT('book_with', ['name' => $displayName]) : meetingT('title')) ?></h1>
            <div class="meeting-meta"><span><?= meetingIcon('globe') ?>Europe/Zurich</span><span><?= meetingIcon('clock') ?><?= meetingEscape(meetingT('duration_short')) ?></span><span><?= meetingIcon('repeat') ?><?= meetingEscape(meetingT('interval_short')) ?></span></div>
        </div>
        <div class="meeting-host__note"><span class="meeting-icon-disc"><?= meetingIcon('calendar') ?></span><span><?= meetingEscape(meetingT('tagline')) ?><small><?= meetingEscape(meetingT('tagline_hint')) ?></small></span></div>
    </header>
    <?php if ($error): ?><p class="generic-feedback is-error" role="alert"><?= meetingEscape($error) ?></p><?php endif; ?>
    <?php if ($receipt || $draft): ?>
        <?php
        $summaryStart = $receipt ? DateTimeImmutable::createFromInterface($receipt->get('start_at'))->setTimezone($zone) : new DateTimeImmutable($draft['date'] . ' ' . $draft['time'], $zone);
        $summaryEnd = $receipt ? DateTimeImmutable::createFromInterface($receipt->get('end_at'))->setTimezone($zone) : $summaryStart->modify('+1 hour');
        $summaryName = $receipt ? $receipt->get('guest_name') : $draft['name'];
        $summaryEmail = $receipt ? $receipt->get('guest_email') : $draft['email'];
        $summaryReason = $receipt ? $receipt->get('reason') : $draft['reason'];
        ?>
        <section class="generic-soft-panel generic-soft-panel--elevated generic-soft-panel--stack meeting-summary <?= $receipt ? 'meeting-summary--confirmed' : '' ?>">
            <div class="meeting-summary__heading">
                <?php if ($receipt): ?><span class="meeting-success-mark"><?= meetingIcon('check') ?></span><?php endif; ?>
                <h2 class="generic-card-title generic-card-title--display"><?= meetingEscape(meetingT($receipt ? 'confirmed_heading' : 'summary')) ?></h2>
                <p class="meeting-muted"><?= meetingEscape(meetingT($receipt ? 'confirmed_hint' : 'summary_hint', ['name' => $displayName])) ?></p>
            </div>
            <div class="meeting-summary__details">
                <div class="generic-soft-panel generic-soft-panel--tinted meeting-detail-row"><span class="meeting-icon-disc"><?= meetingIcon('calendar') ?></span><div><span class="meeting-muted"><?= meetingEscape(meetingT('date_time')) ?></span><strong><?= meetingEscape($formatDay($summaryStart)) ?> &middot; <?= meetingEscape($summaryStart->format('H:i') . ' - ' . $summaryEnd->format('H:i')) ?></strong><span class="meeting-muted">Europe/Zurich</span></div></div>
                <div class="generic-soft-panel generic-soft-panel--tinted meeting-detail-row"><span class="meeting-icon-disc"><?= meetingIcon('user') ?></span><div><span class="meeting-muted"><?= meetingEscape(meetingT('your_details')) ?></span><strong><?= meetingEscape($summaryName) ?></strong><span class="meeting-muted"><?= meetingEscape($summaryEmail) ?></span></div></div>
                <div class="generic-soft-panel generic-soft-panel--tinted meeting-detail-row"><span class="meeting-icon-disc"><?= meetingIcon('note') ?></span><div><span class="meeting-muted"><?= meetingEscape(meetingT('reason')) ?></span><strong><?= nl2br(meetingEscape($summaryReason)) ?></strong></div></div>
            </div>
            <?php if ($receipt): ?>
                <p class="<?= $receipt->get('email_sent_at') ? 'meeting-muted' : 'generic-feedback is-error' ?>" role="status"><?= meetingEscape(meetingT($receipt->get('email_sent_at') ? 'email_sent' : 'email_failed')) ?></p>
                <a class="generic-action-button generic-action-button--main generic-action-button--wide" href="<?= meetingEscape($path . '?receipt=' . $receipt->get('token') . '&download=1') ?>"><?= meetingIcon('calendar') ?><?= meetingEscape(meetingT('download')) ?></a>
                <?php if (!$receipt->get('email_sent_at')): ?>
                    <form method="post" action="<?= meetingEscape($path) ?>"><input type="hidden" name="csrf" value="<?= meetingEscape($csrf) ?>"><input type="hidden" name="action" value="confirm"><input type="hidden" name="token" value="<?= meetingEscape($receipt->get('token')) ?>"><button class="generic-action-button generic-action-button--secondary"><?= meetingEscape(meetingT('retry_email')) ?></button></form>
                <?php endif; ?>
            <?php else: ?>
                <form method="post" action="<?= meetingEscape($path) ?>" data-meeting-confirm><input type="hidden" name="csrf" value="<?= meetingEscape($csrf) ?>"><input type="hidden" name="action" value="confirm"><input type="hidden" name="token" value="<?= meetingEscape($draft['token']) ?>"><button class="generic-action-button generic-action-button--main generic-action-button--wide"><?= meetingEscape(meetingT('confirm')) ?><?= meetingIcon('arrow') ?></button></form>
                <a class="meeting-back" href="<?= meetingEscape($path . '?date=' . $draft['date']) ?>"><?= meetingEscape(meetingT('back')) ?></a>
            <?php endif; ?>
        </section>
    <?php elseif ($profile && $dayResults): ?>
        <div class="meeting-layout">
            <section class="generic-soft-panel generic-soft-panel--elevated generic-soft-panel--stack" aria-labelledby="meeting-month">
                <div class="meeting-month-nav">
                    <h2 id="meeting-month" class="generic-card-title generic-card-title--large"><?= meetingEscape(meetingT($monthKeys[(int)$month->format('n')]) . ' ' . $month->format('Y')) ?></h2>
                    <nav aria-label="<?= meetingEscape(meetingT('availability')) ?>">
                        <?php if ($month > $currentMonth): ?><a class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" href="<?= meetingEscape($path . '?month=' . $month->modify('-1 month')->format('Y-m')) ?>" aria-label="<?= meetingEscape(meetingT('previous')) ?>"><?= meetingIcon('previous') ?></a><?php else: ?><button class="generic-action-button generic-action-button--secondary generic-action-button--icon-only generic-action-button--unavailable" disabled aria-label="<?= meetingEscape(meetingT('previous')) ?>"><?= meetingIcon('previous') ?></button><?php endif; ?>
                        <a class="generic-action-button generic-action-button--secondary" href="<?= meetingEscape($path . '?date=' . $now->format('Y-m-d') . '#meeting-times') ?>"><?= meetingEscape(meetingT('today')) ?></a>
                        <?php if ($month->modify('+1 month') <= $now->modify('+365 days')): ?><a class="generic-action-button generic-action-button--secondary generic-action-button--icon-only" href="<?= meetingEscape($path . '?month=' . $month->modify('+1 month')->format('Y-m')) ?>" aria-label="<?= meetingEscape(meetingT('next')) ?>"><?= meetingIcon('next') ?></a><?php else: ?><button class="generic-action-button generic-action-button--secondary generic-action-button--icon-only generic-action-button--unavailable" disabled aria-label="<?= meetingEscape(meetingT('next')) ?>"><?= meetingIcon('next') ?></button><?php endif; ?>
                    </nav>
                </div>
                <div class="meeting-calendar">
                    <?php foreach (meetingWeekdayKeys() as $label): ?><span class="meeting-calendar__weekday" title="<?= meetingEscape(meetingT($label)) ?>"><?= meetingEscape(mb_substr(meetingT($label), 0, 3)) ?></span><?php endforeach; ?>
                    <?php for ($i = 1; $i < (int)$month->format('N'); $i++): ?><span aria-hidden="true"></span><?php endfor; ?>
                    <?php foreach ($dayResults as $date => $data): ?>
                        <?php $dateLabel = $formatDay(new DateTimeImmutable($date, $zone)) . ' : ' . meetingT($data['state']); ?>
                        <?php if ($data['state'] === 'closed'): ?><span class="meeting-calendar__day" data-state="closed" aria-label="<?= meetingEscape($dateLabel) ?>"><strong><?= (int)substr($date, -2) ?></strong></span>
                        <?php else: ?><a class="meeting-calendar__day" data-state="<?= $data['state'] ?>" href="<?= meetingEscape($path . '?date=' . $date . '#meeting-times') ?>" aria-label="<?= meetingEscape($dateLabel) ?>" <?= $selectedDay && $selectedDay->format('Y-m-d') === $date ? 'aria-current="date"' : '' ?>><strong><?= (int)substr($date, -2) ?></strong></a><?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <ul class="meeting-legend" aria-label="<?= meetingEscape(meetingT('availability')) ?>"><?php foreach (['free', 'partial', 'full', 'closed'] as $state): ?><li data-state="<?= $state ?>"><span class="meeting-status-dot" aria-hidden="true"></span><?= meetingEscape(meetingT($state === 'closed' ? 'closed_short' : $state)) ?></li><?php endforeach; ?></ul>
            </section>
            <aside id="meeting-times" class="generic-soft-panel generic-soft-panel--elevated generic-soft-panel--stack meeting-times" aria-labelledby="meeting-day">
                <?php if (!$selectedDay): ?>
                    <div class="meeting-empty"><span class="meeting-icon-disc"><?= meetingIcon('calendar') ?></span><h2 id="meeting-day" class="generic-card-title generic-card-title--large"><?= meetingEscape(meetingT('select_day')) ?></h2><p class="meeting-muted"><?= meetingEscape(meetingT('day_hint')) ?></p></div>
                <?php else: ?>
                    <div><p class="meeting-eyebrow"><?= meetingEscape(meetingT(meetingWeekdayKeys()[(int)$selectedDay->format('N')])) ?></p><h2 id="meeting-day" class="generic-card-title generic-card-title--large"><?= meetingEscape($formatDay($selectedDay)) ?></h2></div>
                    <?php if ($selectedSlot): ?>
                        <div class="meeting-selection"><span><?= meetingIcon('clock') ?><strong><?= meetingEscape($selectedSlot['time'] . ' - ' . $selectedSlot['end']->format('H:i')) ?></strong></span><a href="<?= meetingEscape($path . '?date=' . $selectedDay->format('Y-m-d') . '#meeting-times') ?>"><?= meetingEscape(meetingT('change_time')) ?></a></div>
                        <form class="generic-form-stack generic-form-stack--compact" method="post" action="<?= meetingEscape($path . '#meeting-times') ?>">
                            <input type="hidden" name="csrf" value="<?= meetingEscape($csrf) ?>"><input type="hidden" name="date" value="<?= meetingEscape($selectedDay->format('Y-m-d')) ?>"><input type="hidden" name="time" value="<?= meetingEscape($selectedSlot['time']) ?>">
                            <label class="meeting-honeypot" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>
                            <div class="generic-form-grid generic-form-grid--pair"><?php foreach (['name', 'email'] as $field): ?><label class="generic-form-field"><span><?= meetingEscape(meetingT($field)) ?></span><input class="generic-form-control generic-form-control--compact" name="<?= $field ?>" type="<?= $field === 'email' ? 'email' : 'text' ?>" autocomplete="<?= $field ?>" maxlength="<?= $field === 'email' ? 254 : 190 ?>" required value="<?= meetingEscape($guest[$field]) ?>"></label><?php endforeach; ?></div>
                            <label class="generic-form-field"><span><?= meetingEscape(meetingT('reason')) ?></span><textarea class="generic-form-control generic-form-control--compact" name="reason" rows="3" maxlength="4000" required placeholder="<?= meetingEscape(meetingT('reason_placeholder')) ?>"><?= meetingEscape($guest['reason']) ?></textarea></label>
                            <p class="generic-help-text"><?= meetingEscape(meetingT('privacy')) ?></p>
                            <button class="generic-action-button generic-action-button--main generic-action-button--wide"><?= meetingEscape(meetingT('review')) ?><?= meetingIcon('arrow') ?></button>
                        </form>
                    <?php else: ?>
                        <p class="meeting-muted"><?= meetingEscape(meetingT('select_time')) ?></p>
                        <?php if ($dayResults[$selectedDay->format('Y-m-d')]['state'] === 'full' || !$dayResults[$selectedDay->format('Y-m-d')]['slots']): ?><p class="generic-help-text"><?= meetingEscape(meetingT('no_slots')) ?></p><?php endif; ?>
                        <div class="meeting-slots">
                            <?php $inPause = false; foreach ($dayResults[$selectedDay->format('Y-m-d')]['slots'] as $slot): ?>
                                <?php if ($slot['pause']): ?>
                                    <?php if (!$inPause): ?><div class="meeting-slots__pause" role="separator" aria-label="<?= meetingEscape(meetingT('pause')) ?>"></div><?php endif; ?>
                                    <?php $inPause = true; continue; ?>
                                <?php endif; ?>
                                <?php $inPause = false; ?>
                                <?php if ($slot['free']): ?><a class="generic-action-button generic-action-button--choice meeting-slot" href="<?= meetingEscape($path . '?date=' . $selectedDay->format('Y-m-d') . '&time=' . $slot['time'] . '#meeting-times') ?>"><?= meetingEscape($slot['time'] . ' - ' . $slot['end']->format('H:i')) ?></a>
                                <?php else: ?><button class="generic-action-button generic-action-button--secondary generic-action-button--unavailable meeting-slot" disabled aria-label="<?= meetingEscape($slot['time'] . ' - ' . $slot['end']->format('H:i') . ' : ' . meetingT('occupied')) ?>"><?= meetingEscape($slot['time'] . ' - ' . $slot['end']->format('H:i')) ?></button><?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </aside>
        </div>
    <?php elseif ($profile): ?><a class="meeting-back" href="<?= meetingEscape($path) ?>"><?= meetingEscape(meetingT('back')) ?></a><?php endif; ?>
    <footer class="meeting-footer"><?= meetingEscape(meetingT('powered_by')) ?> <a href="/">OMO2</a> &middot; <a href="/">OpenMyOrganization</a></footer>
</main>
<script>
document.querySelectorAll('[data-meeting-avatar]').forEach(function (image) {
    function fallback() { image.hidden = true; }
    image.addEventListener('error', fallback);
    if (image.complete && !image.naturalWidth) { fallback(); }
});
document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
        var button = form.querySelector('button');
        if (button) { button.disabled = true; button.textContent = <?= json_encode(meetingT('wait')) ?>; }
    });
});
</script>
</body>
</html>
