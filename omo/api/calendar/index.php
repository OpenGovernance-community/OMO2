<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\ArrayEvent;
use dbObject\Event;
use dbObject\Holon;
use dbObject\Organization;

$sourceLang = [
    'calendar.page.title' => [
        'text' => 'Calendrier',
        'context' => 'Main title of the calendar application drawer.',
    ],
    'calendar.page.description' => [
        'text' => 'Visualisez les evenements de votre organisation et ajoutez-en de nouveaux.',
        'context' => 'Introductory text shown in the calendar application.',
    ],
    'calendar.action.add' => [
        'text' => 'Ajouter un evenement',
        'context' => 'Primary button used to open the event creation drawer.',
    ],
    'calendar.action.today' => [
        'text' => 'Aujourd hui',
        'context' => 'Button used to return to the current month in the calendar application.',
    ],
    'calendar.drawer.title' => [
        'text' => 'Nouvel evenement',
        'context' => 'Title of the internal drawer used to create an event from the calendar application.',
    ],
    'calendar.drawer.description' => [
        'text' => 'Creation d un evenement dans le contexte courant.',
        'context' => 'Description shown in the internal event creation drawer.',
    ],
    'calendar.empty.month' => [
        'text' => 'Aucun evenement sur cette periode.',
        'context' => 'Empty state shown when the current month contains no event.',
    ],
    'calendar.context.organization' => [
        'text' => 'Organisation',
        'context' => 'Fallback context label when the organization root is displayed in the calendar application.',
    ],
    'calendar.day.more' => [
        'one' => '+{count} autre',
        'other' => '+{count} autres',
        'context' => 'Label shown inside a day cell when additional events are hidden.',
    ],
    'calendar.loading' => [
        'text' => 'Chargement...',
        'context' => 'Loading label shown while fetching the event creation form.',
    ],
    'calendar.error.load_form' => [
        'text' => 'Impossible de charger le formulaire.',
        'context' => 'Error shown inside the drawer when the event creation form could not be loaded.',
    ],
    'calendar.day.mon' => [
        'text' => 'Lun',
        'context' => 'Short weekday label in the monthly calendar view.',
    ],
    'calendar.day.tue' => [
        'text' => 'Mar',
        'context' => 'Short weekday label in the monthly calendar view.',
    ],
    'calendar.day.wed' => [
        'text' => 'Mer',
        'context' => 'Short weekday label in the monthly calendar view.',
    ],
    'calendar.day.thu' => [
        'text' => 'Jeu',
        'context' => 'Short weekday label in the monthly calendar view.',
    ],
    'calendar.day.fri' => [
        'text' => 'Ven',
        'context' => 'Short weekday label in the monthly calendar view.',
    ],
    'calendar.day.sat' => [
        'text' => 'Sam',
        'context' => 'Short weekday label in the monthly calendar view.',
    ],
    'calendar.day.sun' => [
        'text' => 'Dim',
        'context' => 'Short weekday label in the monthly calendar view.',
    ],
];

$lang = omoLoadTranslationBundle('omo_calendar_index', $sourceLang);

function omoCalendarT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

function omoCalendarParseMonth($rawValue)
{
    $rawValue = trim((string)$rawValue);
    if (!preg_match('/^\d{4}-\d{2}$/', $rawValue)) {
        return null;
    }

    $month = \DateTime::createFromFormat('!Y-m', $rawValue);
    return $month instanceof \DateTime ? $month : null;
}

function omoCalendarBuildUrl($organizationId, $holonId, \DateTimeInterface $month)
{
    $query = [
        'oid=' . rawurlencode((string)(int)$organizationId),
        'month=' . rawurlencode($month->format('Y-m')),
    ];

    if ((int)$holonId > 0) {
        $query[] = 'cid=' . rawurlencode((string)(int)$holonId);
    }

    return '/omo/api/calendar/index.php?' . implode('&', $query);
}

function omoCalendarFormatMonthLabel(\DateTimeInterface $month)
{
    $monthNames = [
        1 => 'janvier',
        2 => 'fevrier',
        3 => 'mars',
        4 => 'avril',
        5 => 'mai',
        6 => 'juin',
        7 => 'juillet',
        8 => 'aout',
        9 => 'septembre',
        10 => 'octobre',
        11 => 'novembre',
        12 => 'decembre',
    ];

    return ucfirst((string)($monthNames[(int)$month->format('n')] ?? $month->format('F'))) . ' ' . $month->format('Y');
}

function omoCalendarFormatTimeLabel(Event $event, \DateTimeInterface $day)
{
    $startAt = $event->get('start_at');
    $endAt = $event->get('end_at');
    if (!($startAt instanceof \DateTimeInterface) || !($endAt instanceof \DateTimeInterface)) {
        return '';
    }

    if ((bool)$event->get('is_all_day')) {
        return 'Journee';
    }

    $dayKey = $day->format('Y-m-d');
    $startKey = $startAt->format('Y-m-d');
    $endKey = $endAt->format('Y-m-d');

    if ($startKey === $dayKey && $endKey === $dayKey) {
        return $startAt->format('H:i') . ' - ' . $endAt->format('H:i');
    }

    if ($startKey === $dayKey) {
        return $startAt->format('H:i') . ' ->';
    }

    if ($endKey === $dayKey) {
        return '<- ' . $endAt->format('H:i');
    }

    return 'En cours';
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$month = omoCalendarParseMonth($_GET['month'] ?? '') ?: new \DateTime('first day of this month 00:00:00');

if ($organizationId <= 0) {
    http_response_code(400);
    ?>
    <div class="omo-calendar omo-empty-state">Organisation invalide.</div>
    <?php
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-calendar omo-empty-state">Organisation introuvable.</div>
    <?php
    exit;
}

if (!$organization->canViewDetail()) {
    http_response_code(403);
    ?>
    <div class="omo-calendar omo-empty-state">Acces refuse a cette organisation.</div>
    <?php
    exit;
}

$rootHolon = $organization->getEnabledStructuralRootHolon();
$currentHolon = null;

if ($currentHolonId > 0) {
    $candidateHolon = new Holon();
    if (
        !$candidateHolon->load($currentHolonId)
        || !($rootHolon instanceof Holon)
        || !$candidateHolon->isDescendantOf((int)$rootHolon->getId(), true)
        || !$candidateHolon->canViewDetail()
    ) {
        http_response_code(404);
        ?>
        <div class="omo-calendar omo-empty-state">Holon introuvable pour cette organisation.</div>
        <?php
        exit;
    }

    $currentHolon = $candidateHolon;
}

$monthStart = (clone $month)->modify('first day of this month')->setTime(0, 0, 0);
$monthEnd = (clone $month)->modify('last day of this month')->setTime(23, 59, 59);
$gridStart = (clone $monthStart)->modify('monday this week')->setTime(0, 0, 0);
$gridEnd = (clone $monthEnd)->modify('sunday this week')->setTime(23, 59, 59);

$events = new ArrayEvent();
$events->loadForCalendarMonth($organizationId, $gridStart, $gridEnd, $currentHolon ? (int)$currentHolon->getId() : 0, false);

$holonLabelsById = [];
$dayBuckets = [];
$monthEventCount = 0;

foreach ($events as $event) {
    if (!($event instanceof Event) || (int)$event->getId() <= 0) {
        continue;
    }

    $startAt = $event->get('start_at');
    $endAt = $event->get('end_at');
    if (!($startAt instanceof \DateTimeInterface) || !($endAt instanceof \DateTimeInterface)) {
        continue;
    }

    if ($startAt <= $monthEnd && $endAt >= $monthStart) {
        $monthEventCount += 1;
    }

    $eventHolonId = (int)$event->get('IDholon');
    if ($eventHolonId > 0 && !isset($holonLabelsById[$eventHolonId])) {
        $eventHolon = new Holon();
        $holonLabelsById[$eventHolonId] = $eventHolon->load($eventHolonId)
            ? trim((string)$eventHolon->getDisplayName())
            : '';
    }

    $cursor = new \DateTime(max($startAt->format('Y-m-d 00:00:00'), $gridStart->format('Y-m-d 00:00:00')));
    $cursorEnd = new \DateTime(min($endAt->format('Y-m-d 00:00:00'), $gridEnd->format('Y-m-d 00:00:00')));

    while ($cursor <= $cursorEnd) {
        $dayKey = $cursor->format('Y-m-d');
        if (!isset($dayBuckets[$dayKey])) {
            $dayBuckets[$dayKey] = [];
        }

        $dayBuckets[$dayKey][] = [
            'id' => (int)$event->getId(),
            'title' => trim((string)$event->get('title')) !== '' ? trim((string)$event->get('title')) : ('Evenement #' . (int)$event->getId()),
            'timeLabel' => omoCalendarFormatTimeLabel($event, $cursor),
            'status' => Event::normalizeStatus($event->get('status')),
            'holonLabel' => $eventHolonId > 0 ? (string)($holonLabelsById[$eventHolonId] ?? '') : '',
        ];

        $cursor->modify('+1 day');
    }
}

ksort($dayBuckets);

$prevMonth = (clone $monthStart)->modify('-1 month');
$nextMonth = (clone $monthStart)->modify('+1 month');
$todayMonth = new \DateTime('first day of this month 00:00:00');
$contextLabel = $currentHolon instanceof Holon
    ? trim((string)$currentHolon->getDisplayName())
    : trim((string)$organization->get('name'));

$weekdayKeys = [
    'calendar.day.mon',
    'calendar.day.tue',
    'calendar.day.wed',
    'calendar.day.thu',
    'calendar.day.fri',
    'calendar.day.sat',
    'calendar.day.sun',
];

$days = [];
$cursor = clone $gridStart;
while ($cursor <= $gridEnd) {
    $days[] = clone $cursor;
    $cursor->modify('+1 day');
}

$currentUrl = omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $monthStart);
$createUrl = '/omo/api/calendar/create.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolon instanceof Holon) {
    $createUrl .= '&cid=' . rawurlencode((string)(int)$currentHolon->getId());
}
?>
<div
    class="omo-calendar"
    id="omo-calendar-root"
    data-omo-calendar-current-url="<?= omoApiEscape($currentUrl) ?>"
    data-omo-calendar-create-url="<?= omoApiEscape($createUrl) ?>"
    data-omo-calendar-month="<?= omoApiEscape($monthStart->format('Y-m')) ?>"
>
    <section class="generic-hero-panel generic-hero-panel--accent omo-calendar__hero">
        <div class="omo-calendar__hero-copy">
            <div class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape(omoCalendarT('calendar.context.organization')) ?></div>
            <h1 class="generic-card-title generic-card-title--large"><?= omoApiEscape(omoCalendarT('calendar.page.title')) ?></h1>
            <p class="omo-calendar__hero-text"><?= omoApiEscape(omoCalendarT('calendar.page.description')) ?></p>
            <div class="omo-calendar__context-badge"><?= omoApiEscape($contextLabel !== '' ? $contextLabel : omoCalendarT('calendar.context.organization')) ?></div>
        </div>
        <div class="omo-calendar__hero-actions">
            <button
                type="button"
                class="generic-action-button generic-action-button--secondary"
                data-omo-calendar-nav-url="<?= omoApiEscape(omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $todayMonth)) ?>"
            >
                <?= omoApiEscape(omoCalendarT('calendar.action.today')) ?>
            </button>
            <button
                type="button"
                class="generic-action-button generic-action-button--main"
                data-omo-calendar-open-create
            >
                <?= omoApiEscape(omoCalendarT('calendar.action.add')) ?>
            </button>
        </div>
    </section>

    <section class="generic-section generic-section--stack omo-calendar__panel">
        <div class="omo-calendar__toolbar">
            <button
                type="button"
                class="generic-action-button generic-action-button--secondary"
                data-omo-calendar-nav-url="<?= omoApiEscape(omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $prevMonth)) ?>"
            >
                &larr;
            </button>
            <div class="omo-calendar__month-title">
                <strong><?= omoApiEscape(omoCalendarFormatMonthLabel($monthStart)) ?></strong>
                <span><?= omoApiEscape($monthEventCount > 0 ? ($monthEventCount . ' evenement(s)') : omoCalendarT('calendar.empty.month')) ?></span>
            </div>
            <button
                type="button"
                class="generic-action-button generic-action-button--secondary"
                data-omo-calendar-nav-url="<?= omoApiEscape(omoCalendarBuildUrl($organizationId, $currentHolon ? (int)$currentHolon->getId() : 0, $nextMonth)) ?>"
            >
                &rarr;
            </button>
        </div>

        <div class="omo-calendar__weekday-row">
            <?php foreach ($weekdayKeys as $weekdayKey): ?>
                <div class="omo-calendar__weekday"><?= omoApiEscape(omoCalendarT($weekdayKey)) ?></div>
            <?php endforeach; ?>
        </div>

        <div class="omo-calendar__grid">
            <?php foreach ($days as $day): ?>
                <?php
                $dayKey = $day->format('Y-m-d');
                $isCurrentMonth = $day->format('Y-m') === $monthStart->format('Y-m');
                $isToday = $day->format('Y-m-d') === (new \DateTime())->format('Y-m-d');
                $items = $dayBuckets[$dayKey] ?? [];
                $visibleItems = array_slice($items, 0, 3);
                $hiddenCount = max(0, count($items) - count($visibleItems));
                ?>
                <div class="omo-calendar__cell<?= $isCurrentMonth ? '' : ' is-outside' ?><?= $isToday ? ' is-today' : '' ?>">
                    <div class="omo-calendar__cell-head">
                        <span class="omo-calendar__cell-day"><?= omoApiEscape($day->format('j')) ?></span>
                    </div>
                    <div class="omo-calendar__cell-items">
                        <?php foreach ($visibleItems as $item): ?>
                            <div class="omo-calendar__event-chip is-status-<?= omoApiEscape($item['status']) ?>">
                                <?php if ($item['timeLabel'] !== ''): ?>
                                    <span class="omo-calendar__event-time"><?= omoApiEscape($item['timeLabel']) ?></span>
                                <?php endif; ?>
                                <span class="omo-calendar__event-title"><?= omoApiEscape($item['title']) ?></span>
                                <?php if ($item['holonLabel'] !== ''): ?>
                                    <span class="omo-calendar__event-holon"><?= omoApiEscape($item['holonLabel']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($hiddenCount > 0): ?>
                            <div class="omo-calendar__more"><?= omoApiEscape(omoCalendarT('calendar.day.more', ['count' => (string)$hiddenCount])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="omo-overlay-drawer omo-calendar__editor-drawer" data-omo-calendar-editor-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-omo-calendar-editor-close></div>
        <div class="omo-overlay-drawer__panel">
            <div class="omo-overlay-drawer__header">
                <div class="omo-overlay-drawer__header-copy">
                    <h3 class="omo-overlay-drawer__title" data-omo-calendar-editor-title><?= omoApiEscape(omoCalendarT('calendar.drawer.title')) ?></h3>
                    <p class="omo-overlay-drawer__description" data-omo-calendar-editor-description><?= omoApiEscape(omoCalendarT('calendar.drawer.description')) ?></p>
                </div>
                <button type="button" class="omo-overlay-drawer__close" data-omo-calendar-editor-close>Fermer</button>
            </div>
            <div class="omo-overlay-drawer__body" data-omo-calendar-editor-body></div>
        </div>
    </div>
    <script>
    (function () {
        var root = document.getElementById('omo-calendar-root');
        if (!root || root.dataset.omoCalendarReady === '1') {
            return;
        }

        root.dataset.omoCalendarReady = '1';

        var drawer = root.querySelector('[data-omo-calendar-editor-drawer]');
        var drawerBody = root.querySelector('[data-omo-calendar-editor-body]');
        var currentUrl = root.getAttribute('data-omo-calendar-current-url') || '';
        var createUrl = root.getAttribute('data-omo-calendar-create-url') || '';
        var requestToken = 0;

        function resolveUrl(url) {
            if (!url) {
                return '';
            }

            if (typeof window.omoResolveAppUrl === 'function') {
                return window.omoResolveAppUrl(url);
            }

            return url;
        }

        function setDrawerLoading() {
            if (!drawerBody) {
                return;
            }

            drawerBody.innerHTML = '<div class="generic-section"><?= omoApiEscape(omoCalendarT('calendar.loading')) ?></div>';
        }

        function setDrawerError() {
            if (!drawerBody) {
                return;
            }

            drawerBody.innerHTML = '<div class="generic-section"><?= omoApiEscape(omoCalendarT('calendar.error.load_form')) ?></div>';
        }

        function closeDrawer() {
            if (!drawer) {
                return;
            }

            drawer.classList.remove('is-open');
            window.setTimeout(function () {
                if (!drawer.classList.contains('is-open')) {
                    drawer.hidden = true;
                    if (drawerBody) {
                        drawerBody.innerHTML = '';
                    }
                }
            }, 180);
        }

        function openDrawerWithUrl(url) {
            if (!drawer || !drawerBody || !url) {
                return;
            }

            setDrawerLoading();
            drawer.hidden = false;
            window.requestAnimationFrame(function () {
                drawer.classList.add('is-open');
            });

            var localToken = ++requestToken;

            fetch(resolveUrl(url), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('load_failed');
                }

                return response.text();
            }).then(function (html) {
                if (localToken !== requestToken || !drawerBody) {
                    return;
                }

                drawerBody.innerHTML = html;
            }).catch(function () {
                if (localToken !== requestToken) {
                    return;
                }

                setDrawerError();
            });
        }

        function refreshCalendar(url) {
            var targetUrl = url || currentUrl;
            if (!targetUrl) {
                return;
            }

            if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
                window.location.href = resolveUrl(targetUrl);
                return;
            }

            window.omoReplaceFetchedPanelRoot({
                rootSelector: '#omo-calendar-root',
                currentRoot: root,
                url: resolveUrl(targetUrl)
            });
        }

        root.querySelectorAll('[data-omo-calendar-editor-close]').forEach(function (button) {
            button.addEventListener('click', closeDrawer);
        });

        root.querySelectorAll('[data-omo-calendar-nav-url]').forEach(function (button) {
            button.addEventListener('click', function () {
                var url = button.getAttribute('data-omo-calendar-nav-url') || '';
                if (!url) {
                    return;
                }

                refreshCalendar(url);
            });
        });

        var openCreateButton = root.querySelector('[data-omo-calendar-open-create]');
        if (openCreateButton) {
            openCreateButton.addEventListener('click', function () {
                openDrawerWithUrl(createUrl);
            });
        }

        if (drawerBody) {
            drawerBody.addEventListener('submit', function (event) {
                var form = event.target.closest('[data-omo-calendar-create-form]');
                if (!form) {
                    return;
                }

                event.preventDefault();

                var feedback = form.querySelector('[data-omo-calendar-create-feedback]');
                var submitButton = form.querySelector('[data-omo-calendar-create-submit]');
                var formData = new FormData(form);

                if (submitButton) {
                    submitButton.disabled = true;
                }

                if (feedback) {
                    feedback.textContent = '';
                    feedback.className = 'omo-calendar-create__feedback';
                }

                fetch(resolveUrl(form.getAttribute('action') || createUrl), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                }).then(function (response) {
                    return response.json();
                }).then(function (payload) {
                    if (!payload || payload.status !== true) {
                        throw payload || new Error('save_failed');
                    }

                    closeDrawer();
                    refreshCalendar(currentUrl);
                }).catch(function (error) {
                    if (!feedback) {
                        return;
                    }

                    var message = error && typeof error.message === 'string' && error.message !== ''
                        ? error.message
                        : 'Impossible d enregistrer cet evenement.';
                    feedback.textContent = message;
                    feedback.className = 'omo-calendar-create__feedback is-error';
                }).finally(function () {
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                });
            });
        }
    })();
    </script>
</div>

<style>
.omo-calendar {
    display: grid;
    gap: 16px;
    min-height: 100%;
}

.omo-calendar__hero {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    align-items: flex-start;
}

.omo-calendar__hero-copy {
    display: grid;
    gap: 8px;
}

.omo-calendar__hero-text {
    margin: 0;
    color: var(--color-text-light, #475569);
    line-height: 1.6;
}

.omo-calendar__context-badge {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 0 12px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-primary, #2563eb) 12%, var(--color-surface, #ffffff));
    color: var(--color-text, #1f2937);
    font-weight: 700;
}

.omo-calendar__hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: flex-end;
}

.omo-calendar__panel {
    gap: 14px;
}

.omo-calendar__toolbar {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
}

.omo-calendar__month-title {
    display: grid;
    gap: 4px;
    text-align: center;
}

.omo-calendar__month-title strong {
    font-size: 1.15rem;
    color: var(--color-text, #1f2937);
}

.omo-calendar__month-title span {
    color: var(--color-text-light, #64748b);
    font-size: 0.95rem;
}

.omo-calendar__weekday-row,
.omo-calendar__grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 10px;
}

.omo-calendar__weekday {
    padding: 0 4px;
    text-align: center;
    font-size: 0.84rem;
    font-weight: 700;
    color: var(--color-text-light, #64748b);
}

.omo-calendar__cell {
    min-height: 138px;
    padding: 10px;
    border: 1px solid var(--color-border, #dbe2ea);
    border-radius: 16px;
    background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.96));
    display: grid;
    gap: 10px;
    align-content: start;
}

.omo-calendar__cell.is-outside {
    opacity: 0.58;
}

.omo-calendar__cell.is-today {
    border-color: color-mix(in srgb, var(--color-primary, #2563eb) 32%, var(--color-border, #dbe2ea));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary, #2563eb) 10%, transparent);
}

.omo-calendar__cell-head {
    display: flex;
    justify-content: flex-end;
}

.omo-calendar__cell-day {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    min-height: 32px;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.14);
    color: var(--color-text, #1f2937);
    font-weight: 700;
}

.omo-calendar__cell.is-today .omo-calendar__cell-day {
    background: var(--color-primary, #2563eb);
    color: #ffffff;
}

.omo-calendar__cell-items {
    display: grid;
    gap: 8px;
}

.omo-calendar__event-chip {
    display: grid;
    gap: 4px;
    padding: 8px 9px;
    border-radius: 12px;
    background: color-mix(in srgb, var(--color-primary, #2563eb) 9%, var(--color-surface, #ffffff));
    border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 16%, var(--color-border, #dbe2ea));
}

.omo-calendar__event-chip.is-status-draft {
    background: color-mix(in srgb, #f59e0b 9%, var(--color-surface, #ffffff));
    border-color: color-mix(in srgb, #f59e0b 22%, var(--color-border, #dbe2ea));
}

.omo-calendar__event-time,
.omo-calendar__event-holon,
.omo-calendar__more {
    color: var(--color-text-light, #64748b);
    font-size: 0.78rem;
}

.omo-calendar__event-title {
    color: var(--color-text, #1f2937);
    font-size: 0.9rem;
    font-weight: 700;
    line-height: 1.35;
}

.omo-calendar__editor-drawer .omo-overlay-drawer__body {
    padding: 0;
}

@media (max-width: 920px) {
    .omo-calendar__weekday-row,
    .omo-calendar__grid {
        gap: 8px;
    }

    .omo-calendar__cell {
        min-height: 122px;
        padding: 8px;
    }
}

@media (max-width: 720px) {
    .omo-calendar__hero,
    .omo-calendar__toolbar {
        grid-template-columns: 1fr;
        display: grid;
    }

    .omo-calendar__hero-actions {
        justify-content: stretch;
    }

    .omo-calendar__hero-actions .generic-action-button {
        width: 100%;
    }

    .omo-calendar__weekday-row {
        display: none;
    }

    .omo-calendar__grid {
        grid-template-columns: 1fr;
    }

    .omo-calendar__cell {
        min-height: 0;
    }

    .omo-calendar__cell-head {
        justify-content: flex-start;
    }
}
</style>
