<?php // Shared by the calendar and project event editors; translations belong to create.php. ?>
<div class="calendar-availability__loading" data-calendar-availability-loading hidden role="status" aria-live="polite">
    <span class="generic-loading-indicator">
        <svg class="generic-loading-indicator__spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <circle class="generic-loading-indicator__track" cx="12" cy="12" r="9" />
            <path class="generic-loading-indicator__arc" d="M12 3a9 9 0 0 1 9 9" />
        </svg>
        <?= omoApiEscape(omoCalendarCreateT('calendar.availability.waiting')) ?>
    </span>
    <span class="generic-help-text"><?= omoApiEscape(omoCalendarCreateT('calendar.availability.waiting_hint')) ?></span>
</div>
<section class="generic-section generic-section--stack calendar-availability" data-calendar-availability hidden role="status" tabindex="-1">
    <div class="calendar-availability__heading">
        <span class="calendar-availability__symbol" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 10h18m-9 3v3m0 2v1"/></svg>
        </span>
        <div>
            <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoCalendarCreateT('calendar.availability.warning')) ?></h3>
            <p class="generic-help-text"><?= omoApiEscape(omoCalendarCreateT('calendar.availability.note')) ?></p>
        </div>
    </div>
    <div class="calendar-availability__list" data-calendar-availability-messages role="list"></div>
    <div class="calendar-availability__actions">
        <button type="button" class="generic-action-button generic-action-button--secondary" data-calendar-availability-adjust><?= omoApiEscape(omoCalendarCreateT('calendar.availability.adjust')) ?></button>
        <button type="button" class="generic-action-button generic-action-button--main" data-calendar-availability-confirm><?= omoApiEscape(omoCalendarCreateT('calendar.availability.confirm')) ?></button>
    </div>
</section>
