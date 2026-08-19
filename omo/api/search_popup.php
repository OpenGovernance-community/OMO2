<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/topbar.php';
require_once dirname(__DIR__) . '/topbar.php';
require_once __DIR__ . '/stats/shared.php';

if (!function_exists('omoSearchPopupGetScopeLabels')) {
    function omoSearchPopupGetScopeLabels(?\dbObject\Organization $organization = null)
    {
        $scopeLabels = array(
            'structure' => 'Structure',
            'team' => 'Team',
            'calendar' => 'Calendrier',
            'documents' => 'Documents',
            'pv' => 'PV',
            'rules' => 'Regles',
            'decision' => 'Decisions',
            'projects' => 'Projets',
            'stats' => 'Indicateurs',
            'faq' => 'FAQ',
            'tutorials' => 'Tutoriels',
        );

        if (!$organization instanceof \dbObject\Organization || (int)$organization->getId() <= 0) {
            return $scopeLabels;
        }

        $scopeAppHashes = array(
            'structure' => 'structure',
            'team' => 'team',
            'calendar' => 'calendar',
            'rules' => 'policy',
            'documents' => 'documents',
            'pv' => 'documents',
            'decision' => 'decision',
            'projects' => 'projects',
            'stats' => 'stats',
        );

        foreach ($scopeAppHashes as $scopeId => $hash) {
            if (!$organization->isApplicationEnabled($hash)) {
                unset($scopeLabels[$scopeId]);
            }
        }

        return $scopeLabels;
    }
}

if (!function_exists('omoSearchPopupResolveScopes')) {
    function omoSearchPopupResolveScopes($rawScopes, array $scopeLabels)
    {
        if (!is_array($rawScopes)) {
            $rawScopes = array($rawScopes);
        }

        $selectedScopes = array();
        foreach ($rawScopes as $scope) {
            $scope = trim((string)$scope);
            if ($scope === '__structure__') {
                $scope = 'structure';
            }

            if (isset($scopeLabels[$scope])) {
                $selectedScopes[$scope] = $scope;
            }
        }

        if (count($selectedScopes) === 0) {
            $selectedScopes = $scopeLabels;
        }

        return $selectedScopes;
    }
}

if (!function_exists('omoSearchPopupResolveDateRange')) {
    function omoSearchPopupResolveDateRange($startDate, $endDate, \dbObject\Organization $organization)
    {
        $organizationCreatedAt = $organization->get('datecreation');
        $minDate = $organizationCreatedAt instanceof \DateTimeInterface
            ? $organizationCreatedAt->format('Y-m-d')
            : date('Y-m-d');
        $maxDate = date('Y-m-d');
        $startDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$startDate) ? (string)$startDate : $minDate;
        $endDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$endDate) ? (string)$endDate : $maxDate;
        $startDate = max($minDate, min($maxDate, $startDate));
        $endDate = max($minDate, min($maxDate, $endDate));
        if ($startDate > $endDate) {
            $endDate = $startDate;
        }

        return array(
            'minDate' => $minDate,
            'maxDate' => $maxDate,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'label' => omoTopbarTranslate('topbar.search.period'),
            'startLabel' => omoTopbarTranslate('topbar.search.period_start'),
            'endLabel' => omoTopbarTranslate('topbar.search.period_end'),
        );
    }
}

if (!function_exists('omoSearchPopupRenderStyles')) {
    function omoSearchPopupRenderStyles()
    {
        ?>
        <style>
        .omo-search-popup {
            color: var(--color-text, #0f172a);
            --omo-search-popup-muted-text: color-mix(in srgb, var(--color-text, #0f172a) 62%, var(--color-text-light, #475569));
            --omo-search-popup-summary-text: color-mix(in srgb, var(--color-text, #0f172a) 82%, var(--color-text-light, #475569));
            --omo-search-popup-chip-background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface-alt, #f8fafc));
            --omo-search-popup-chip-border: color-mix(in srgb, var(--color-primary, #2563eb) 18%, var(--color-border, #d1d5db));
            --omo-search-popup-card-background: color-mix(in srgb, var(--color-surface-raised, #ffffff) 90%, var(--color-surface-alt, #f8fafc));
            --omo-search-popup-card-background-hover: color-mix(in srgb, var(--color-surface-raised, #ffffff) 96%, var(--color-surface-alt, #f8fafc));
            --omo-search-popup-card-border: color-mix(in srgb, var(--color-border, #d1d5db) 88%, transparent);
            --omo-search-popup-empty-background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 94%, var(--color-surface, #ffffff));
            --omo-search-popup-empty-border: color-mix(in srgb, var(--color-border, #d1d5db) 78%, transparent);
            --omo-search-popup-error-background: color-mix(in srgb, var(--color-danger, #dc2626) 10%, var(--color-surface, #ffffff));
            --omo-search-popup-error-border: color-mix(in srgb, var(--color-danger, #dc2626) 28%, var(--color-border, #d1d5db));
            --omo-search-popup-spinner-track: color-mix(in srgb, var(--color-border, #d1d5db) 78%, transparent);
            --omo-search-popup-spinner-head: var(--color-primary, #2563eb);
        }

        .omo-search-popup__hero,
        .omo-search-popup__result,
        .omo-search-popup__status-card {
            --generic-section-padding-block: 18px;
        }

        .omo-search-popup__search-card {
            --topbar-menu-item-bg: color-mix(in srgb, var(--color-primary, #2563eb) 8%, var(--color-surface-alt, #f8fafc));
            --topbar-menu-item-bg-hover: color-mix(in srgb, var(--color-primary, #2563eb) 14%, var(--color-surface-alt, #f8fafc));
            --topbar-menu-border: color-mix(in srgb, var(--color-border, #d1d5db) 88%, transparent);
            --topbar-menu-text: var(--color-text, #0f172a);
            --topbar-menu-text-muted: var(--omo-search-popup-muted-text);
            --topbar-input-bg: color-mix(in srgb, var(--color-surface-raised, #ffffff) 94%, var(--color-surface-alt, #f8fafc));
            --topbar-input-border: color-mix(in srgb, var(--color-border, #d1d5db) 88%, transparent);
            --topbar-input-text: var(--color-text, #0f172a);
            margin: 0;
            border-radius: 0;
        }

        .omo-search-popup__search-form {
            display: grid;
            gap: 10px;
            width: 100%;
        }

        .omo-search-popup__search-form .common-topbar__search-panel-row {
            align-items: stretch;
        }

        .omo-search-popup__search-form .common-topbar__search-input {
            min-width: 0;
            flex: 1 1 auto;
        }

        .omo-search-popup__search-form .common-topbar__search-button {
            flex: 0 0 auto;
            padding-inline: 16px;
            white-space: nowrap;
        }

        .omo-search-popup__search-form .common-topbar__search-scopes {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .omo-search-popup__search-form .common-topbar__search-scope-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .omo-search-popup__content-state {
            display: grid;
            gap: 16px;
            padding: 16px 18px 18px;
        }

        .omo-search-popup__head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
        }

        .omo-search-popup__summary {
            margin: 6px 0 0;
            color: var(--omo-search-popup-summary-text);
            line-height: 1.5;
        }

        .omo-search-popup__summary strong {
            color: var(--color-text, #0f172a);
        }

        .omo-search-popup__scopes,
        .omo-search-popup__stats,
        .omo-search-popup__result-meta,
        .omo-search-popup__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .omo-search-popup__scope,
        .omo-search-popup__badge,
        .omo-search-popup__score {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid var(--omo-search-popup-chip-border);
            background: var(--omo-search-popup-chip-background);
            color: var(--omo-search-popup-muted-text);
            font-size: 0.85rem;
        }

        .omo-search-popup__stats {
            margin-top: 14px;
        }

        .omo-search-popup__stat {
            min-width: 92px;
            padding: 10px 12px;
            border-radius: var(--radius-md);
            background: var(--omo-search-popup-card-background);
            border: 1px solid var(--omo-search-popup-card-border);
            appearance: none;
            color: var(--color-text, #0f172a);
            font: inherit;
            text-align: left;
            cursor: pointer;
            transition: background 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
        }

        .omo-search-popup__stat:hover {
            background: var(--omo-search-popup-card-background-hover);
            border-color: color-mix(in srgb, var(--color-primary, #2563eb) 30%, var(--omo-search-popup-card-border));
            box-shadow: 0 12px 24px -20px rgba(15, 23, 42, 0.65);
            transform: translateY(-1px);
        }

        .omo-search-popup__stat:focus-visible {
            outline: 2px solid color-mix(in srgb, var(--color-primary, #2563eb) 38%, transparent);
            outline-offset: 2px;
        }

        .omo-search-popup__stat.is-active {
            background: color-mix(in srgb, var(--color-primary, #2563eb) 14%, var(--color-surface, #ffffff));
            border-color: color-mix(in srgb, var(--color-primary, #2563eb) 34%, var(--color-border, #d1d5db));
        }

        .omo-search-popup__stat strong,
        .omo-search-popup__result-body h4,
        .omo-search-popup__status-title {
            display: block;
            margin: 0;
        }

        .omo-search-popup__stat span,
        .omo-search-popup__subtitle,
        .omo-search-popup__excerpt,
        .omo-search-popup__rank,
        .omo-search-popup__status-text {
            color: var(--omo-search-popup-muted-text);
        }

        .omo-search-popup__list {
            display: grid;
            gap: 12px;
        }

        .omo-search-popup__result.is-filtered-out,
        .omo-search-popup__empty[hidden] {
            display: none !important;
        }

        .omo-search-popup__result-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }

        .omo-search-popup__result-body,
        .omo-search-popup__status-card {
            display: grid;
            gap: 8px;
        }

        .omo-search-popup__subtitle {
            font-size: 0.92rem;
        }

        .omo-search-popup__excerpt,
        .omo-search-popup__status-text {
            margin: 0;
            line-height: 1.6;
        }

        .omo-search-popup__empty,
        .omo-search-popup__status-card {
            padding: 18px;
            border-radius: var(--radius-md);
            border: 1px solid var(--omo-search-popup-card-border);
            background: var(--omo-search-popup-card-background);
            color: var(--omo-search-popup-muted-text);
        }

        .omo-search-popup__empty {
            border-color: var(--omo-search-popup-empty-border);
            background: var(--omo-search-popup-empty-background);
        }

        .omo-search-popup__status-card.is-error {
            border-color: var(--omo-search-popup-error-border);
            background: var(--omo-search-popup-error-background);
        }

        .omo-search-popup__spinner {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 2px solid var(--omo-search-popup-spinner-track);
            border-top-color: var(--omo-search-popup-spinner-head);
            animation: omo-search-popup-spin 0.9s linear infinite;
        }

        @keyframes omo-search-popup-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 720px) {
            .omo-search-popup__search-form .common-topbar__search-panel-row,
            .omo-search-popup__head,
            .omo-search-popup__result-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .omo-search-popup__search-form .common-topbar__search-button {
                width: 100%;
                min-height: 42px;
            }
        }
        </style>
        <?php
    }
}

if (!function_exists('omoSearchPopupGetUiStrings')) {
    function omoSearchPopupGetUiStrings()
    {
        return array(
            'searchAriaLabel' => 'Recherche',
            'searchSubmit' => 'Lancer',
        );
    }
}

if (!function_exists('omoSearchPopupRenderScopeBadges')) {
    function omoSearchPopupRenderScopeBadges(array $selectedScopes, array $scopeLabels, $escape)
    {
        foreach (array_values($selectedScopes) as $scope) {
            ?>
            <span class="omo-search-popup__scope"><?= $escape($scopeLabels[$scope] ?? $scope) ?></span>
            <?php
        }
    }
}

if (!function_exists('omoSearchPopupRenderStats')) {
    function omoSearchPopupRenderStats(array $selectedScopes, array $scopeLabels, array $counts, $escape)
    {
        foreach ($scopeLabels as $scopeId => $scopeLabel) {
            if (!isset($selectedScopes[$scopeId])) {
                continue;
            }
            ?>
            <button
                type="button"
                class="omo-search-popup__stat"
                data-omo-search-popup-stat-filter="<?= $escape($scopeId) ?>"
                data-omo-search-popup-stat-active="0"
                aria-pressed="false"
            >
                <strong><?= (int)($counts[$scopeId] ?? 0) ?></strong>
                <span><?= $escape($scopeLabel) ?></span>
            </button>
            <?php
        }
    }
}

if (!function_exists('omoSearchPopupRenderSearchForm')) {
    function omoSearchPopupRenderSearchForm($query, array $selectedScopes, array $scopeLabels, array $dateRange, $escape)
    {
        $ui = omoSearchPopupGetUiStrings();
        ?>
        <div class="omo-search-popup__search-card generic-drawer-header generic-drawer-header--sticky">
            <form class="omo-search-popup__search-form common-topbar__search-panel" data-omo-search-popup-form>
                <div class="common-topbar__search-panel-row">
                    <input
                        type="search"
                        id="omoSearchPopupInput"
                        class="common-topbar__search-input generic-form-control"
                        data-omo-search-popup-input
                        value="<?= $escape($query) ?>"
                        aria-label="<?= $escape($ui['searchAriaLabel']) ?>"
                    >
                    <button type="submit" class="common-topbar__search-button generic-action-button generic-action-button--main"><?= $escape($ui['searchSubmit']) ?></button>
                </div>

                <div class="common-topbar__search-scopes">
                    <div class="common-topbar__search-scope-list">
                        <?php foreach ($scopeLabels as $scopeId => $scopeLabel): ?>
                            <label class="common-topbar__search-scope">
                                <input
                                    type="checkbox"
                                    class="common-topbar__search-scope-input"
                                    data-omo-search-popup-scope-input
                                    value="<?= $escape($scopeId) ?>"
                                    <?= isset($selectedScopes[$scopeId]) ? 'checked' : '' ?>
                                >
                                <span class="common-topbar__search-scope-label"><?= $escape($scopeLabel) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php commonRenderTopbarSearchPeriod($dateRange, 'omoSearchPopup'); ?>
            </form>
        </div>
        <?php
    }
}

if (!function_exists('omoSearchPopupRenderContent')) {
    function omoSearchPopupRenderContent($query, array $selectedScopes, array $scopeLabels, array $payload, $escape)
    {
        $status = trim((string)($payload['status'] ?? 'completed'));
        $results = is_array($payload['results'] ?? null) ? $payload['results'] : array();
        $counts = is_array($payload['counts'] ?? null) ? $payload['counts'] : array();
        $error = trim((string)($payload['error'] ?? ''));
        ?>
        <div class="omo-search-popup__content-state" data-omo-search-job-status="<?= $escape($status) ?>">
            <div class="omo-search-popup__hero generic-section">
                <div class="omo-search-popup__head">
                    <div>
                        <h3 class="generic-card-title">Resultats de recherche</h3>
                        <p class="omo-search-popup__summary">
                            <?php if ($query === ''): ?>
                                Saisissez une recherche dans la topbar pour lancer l exploration.
                            <?php elseif ($status === 'running' || $status === 'queued'): ?>
                                Recherche en cours pour <strong><?= $escape($query) ?></strong>
                            <?php elseif ($status === 'failed'): ?>
                                La recherche pour <strong><?= $escape($query) ?></strong> a rencontre un probleme.
                            <?php else: ?>
                                Recherche pour <strong><?= $escape($query) ?></strong>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="omo-search-popup__scopes">
                        <?php omoSearchPopupRenderScopeBadges($selectedScopes, $scopeLabels, $escape); ?>
                    </div>
                </div>

                <?php if ($status === 'completed'): ?>
                    <div class="omo-search-popup__stats">
                        <?php omoSearchPopupRenderStats($selectedScopes, $scopeLabels, $counts, $escape); ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($query === ''): ?>
                <div class="omo-search-popup__empty">Aucune recherche demandee.</div>
            <?php elseif ($status === 'queued' || $status === 'running'): ?>
                <div class="omo-search-popup__status-card generic-section">
                    <div class="omo-search-popup__spinner" aria-hidden="true"></div>
                    <strong class="omo-search-popup__status-title">Recherche en attente</strong>
                    <p class="omo-search-popup__status-text">La recherche s execute dans un worker separe. Les resultats arrivent des qu ils sont prets.</p>
                </div>
            <?php elseif ($status === 'failed'): ?>
                <div class="omo-search-popup__status-card generic-section is-error">
                    <strong class="omo-search-popup__status-title">Recherche indisponible</strong>
                    <p class="omo-search-popup__status-text"><?= $escape($error !== '' ? $error : 'Le worker de recherche n a pas pu terminer correctement.') ?></p>
                </div>
            <?php elseif (count($results) === 0): ?>
                <div class="omo-search-popup__empty">Aucun resultat trouve pour cette selection de modules.</div>
            <?php else: ?>
                <div class="omo-search-popup__list" data-omo-search-popup-results-list>
                    <?php foreach ($results as $index => $result): ?>
                        <?php
                        $module = (string)($result['module'] ?? '');
                        $action = is_array($result['action'] ?? null) ? $result['action'] : array();
                        $subtitle = trim((string)($result['subtitle'] ?? ''));
                        if ($module === 'stats' && !empty($action['measurementFrequency'])) {
                            $frequencyLabel = omoStatsMeasurementFrequencyLabel($action['measurementFrequency']);
                            if ($frequencyLabel !== '') {
                                $subtitle = $subtitle !== '' ? $subtitle . ' | ' . $frequencyLabel : $frequencyLabel;
                            }
                        }
                        $buttonAttributes = '';
                        if ($module === 'structure' && !empty($action['holonId'])) {
                            $buttonAttributes = ' data-omo-search-open-structure="' . (int)$action['holonId'] . '"';
                        } elseif ($module === 'rules' && !empty($action['holonId'])) {
                            $buttonAttributes = ' data-omo-search-open-rules-holon="' . (int)$action['holonId'] . '"';
                        } elseif ($module === 'team' && !empty($action['userId'])) {
                            $buttonAttributes = ' data-omo-search-open-user="' . (int)$action['userId'] . '"';
                        } elseif ($module === 'calendar' && !empty($action['eventId'])) {
                            $buttonAttributes = ' data-omo-search-open-calendar-event-id="' . (int)$action['eventId'] . '"'
                                . ' data-omo-search-open-calendar-event-holon="' . (int)($action['holonId'] ?? 0) . '"';
                        } elseif (in_array($module, array('documents', 'pv'), true) && !empty($action['documentUrl'])) {
                            $buttonAttributes = ' data-omo-search-open-document="' . htmlspecialchars((string)$action['documentUrl'], ENT_QUOTES, 'UTF-8') . '"'
                                . ' data-omo-search-document-title="' . htmlspecialchars((string)($result['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8') . '"';
                        } elseif ($module === 'decision' && !empty($action['decisionId'])) {
                            $buttonAttributes = ' data-omo-search-open-decision-id="' . (int)$action['decisionId'] . '"'
                                . ' data-omo-search-open-decision-holon="' . (int)($action['holonId'] ?? 0) . '"';
                        } elseif ($module === 'projects' && !empty($action['projectId'])) {
                            $buttonAttributes = ' data-omo-search-open-project-id="' . (int)$action['projectId'] . '"'
                                . ' data-omo-search-open-project-holon="' . (int)($action['holonId'] ?? 0) . '"';
                        } elseif ($module === 'stats' && !empty($action['indicatorId'])) {
                            $buttonAttributes = ' data-omo-search-open-stat-indicator-id="' . (int)$action['indicatorId'] . '"'
                                . ' data-omo-search-open-stat-indicator-holon="' . (int)($action['holonId'] ?? 0) . '"';
                        } elseif ($module === 'faq' && !empty($action['faqId'])) {
                            $buttonAttributes = ' data-omo-search-open-faq="' . (int)$action['faqId'] . '"';
                        } elseif ($module === 'tutorials' && !empty($action['parcoursId'])) {
                            $buttonAttributes = ' data-omo-search-open-tutorial-parcours="' . (int)$action['parcoursId'] . '"'
                                . ' data-omo-search-open-tutorial-mission="' . (int)($action['missionId'] ?? 0) . '"';
                        }
                        ?>
                        <article class="omo-search-popup__result generic-section" data-omo-search-popup-result-module="<?= $escape($module) ?>">
                            <div class="omo-search-popup__result-head">
                                <div class="omo-search-popup__result-meta">
                                    <span class="omo-search-popup__badge"><?= $escape((string)($result['moduleLabel'] ?? $module)) ?></span>
                                    <span class="omo-search-popup__score">score <?= (int)($result['relevance'] ?? 0) ?></span>
                                </div>
                                <span class="omo-search-popup__rank">#<?= $index + 1 ?></span>
                            </div>

                            <div class="omo-search-popup__result-body">
                                <h4><?= $escape((string)($result['title'] ?? 'Resultat')) ?></h4>
                                <?php if ($subtitle !== ''): ?>
                                    <div class="omo-search-popup__subtitle"><?= $escape($subtitle) ?></div>
                                <?php endif; ?>
                                <?php if (trim((string)($result['excerpt'] ?? '')) !== ''): ?>
                                    <p class="omo-search-popup__excerpt"><?= $escape((string)$result['excerpt']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="omo-search-popup__actions">
                                <button type="button" class="generic-action-button generic-action-button--main"<?= $buttonAttributes ?>>Ouvrir</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="omo-search-popup__empty" data-omo-search-popup-filter-empty hidden>Aucun resultat visible pour ce filtre.</div>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('omoSearchPopupBuildJobPayload')) {
    function omoSearchPopupBuildJobPayload(\dbObject\SearchJob $job)
    {
        $jobPayload = array(
            'status' => (string)$job->get('status'),
            'error' => (string)$job->get('errormessage'),
        );

        if ((string)$job->get('status') === 'completed') {
            $jobPayload = array_merge($job->getResultPayload(), array(
                'status' => 'completed',
            ));
        }

        return $jobPayload;
    }
}

if (!function_exists('omoSearchPopupRenderPollingScript')) {
    function omoSearchPopupRenderPollingScript($statusUrl)
    {
        ?>
        <script>
        (function () {
            var root = document.querySelector('[data-omo-search-popup-root="1"]');
            if (!root) {
                return;
            }

            var content = root.querySelector('[data-omo-search-popup-content]');
            var statusUrl = <?= json_encode((string)$statusUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            var timerId = 0;
            var stopped = false;

            function cleanup() {
                stopped = true;
                if (timerId) {
                    window.clearTimeout(timerId);
                    timerId = 0;
                }
            }

            function scheduleNext(delay) {
                if (stopped) {
                    return;
                }

                timerId = window.setTimeout(loadState, delay);
            }

            function loadState() {
                if (stopped || !content) {
                    return;
                }

                fetch(statusUrl, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Erreur de chargement');
                        }

                        return response.text();
                    })
                    .then(function (html) {
                        if (stopped || !content) {
                            return;
                        }

                        content.innerHTML = html;

                        var stateNode = content.querySelector('[data-omo-search-job-status]');
                        var status = stateNode ? String(stateNode.getAttribute('data-omo-search-job-status') || '') : '';
                        if (status !== 'completed' && status !== 'failed') {
                            scheduleNext(900);
                        }
                    })
                    .catch(function () {
                        if (!stopped && content) {
                            content.innerHTML = '<div class="omo-search-popup__status-card generic-section is-error" data-omo-search-job-status="failed"><strong class="omo-search-popup__status-title">Recherche indisponible</strong><p class="omo-search-popup__status-text">Le suivi du job de recherche a echoue.</p></div>';
                        }
                    });
            }

            window.__omoPopupCleanup = cleanup;
            loadState();
        })();
        </script>
        <?php
    }
}

$organizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$currentHolonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$query = trim((string)($_GET['q'] ?? ''));
$escape = 'omoApiEscape';
$isPartial = !empty($_GET['partial']);

if ($organizationId <= 0) {
    http_response_code(400);
    omoSearchPopupRenderStyles();
    ?>
    <div class="omo-search-popup">
        <div class="omo-search-popup__empty">Organisation invalide.</div>
    </div>
    <?php
    exit;
}

$organization = new \dbObject\Organization();
if (!$organization->load($organizationId) || !$organization->canViewDetail()) {
    http_response_code(403);
    omoSearchPopupRenderStyles();
    ?>
    <div class="omo-search-popup">
        <div class="omo-search-popup__empty">Acces refuse a cette organisation.</div>
    </div>
    <?php
    exit;
}

$scopeLabels = omoSearchPopupGetScopeLabels($organization);
$selectedScopes = omoSearchPopupResolveScopes($_GET['scopes'] ?? array(), $scopeLabels);
$dateRange = omoSearchPopupResolveDateRange($_GET['date_start'] ?? '', $_GET['date_end'] ?? '', $organization);
$viewerContext = \dbObject\SearchJob::buildViewerContextFromGlobals($organizationId, $currentHolonId);
$restoreJobId = isset($_GET['restore_job_id']) ? (int)$_GET['restore_job_id'] : 0;
$restoreJobToken = trim((string)($_GET['restore_job_token'] ?? ''));
$restoredJob = false;
$restoredPayload = null;
$clientJobState = null;

if (!$isPartial && $restoreJobId > 0 && $restoreJobToken !== '') {
    $restoredJob = \dbObject\SearchJob::findByIdAndToken($restoreJobId, $restoreJobToken);
    if ($restoredJob && $restoredJob->matchesViewerContext($viewerContext)) {
        $query = trim((string)$restoredJob->get('query'));
        $selectedScopes = omoSearchPopupResolveScopes($restoredJob->getScopes(), $scopeLabels);
        $dateRange = omoSearchPopupResolveDateRange($restoredJob->getDateRange()['startDate'] ?? '', $restoredJob->getDateRange()['endDate'] ?? '', $organization);
        $restoredPayload = omoSearchPopupBuildJobPayload($restoredJob);
        $clientJobState = array(
            'jobId' => (int)$restoredJob->getId(),
            'jobToken' => (string)$restoredJob->get('requesttoken'),
            'query' => $query,
            'scopes' => array_values($selectedScopes),
            'startDate' => $dateRange['startDate'],
            'endDate' => $dateRange['endDate'],
            'organizationId' => (int)$organizationId,
            'currentHolonId' => (int)$currentHolonId,
            'syncHash' => false,
        );
    } else {
        $restoredJob = false;
        $restoredPayload = array(
            'status' => 'failed',
            'error' => 'Cette recherche n est plus accessible dans le contexte courant.',
        );
    }
}

if ($isPartial) {
    $jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
    $jobToken = trim((string)($_GET['job_token'] ?? ''));
    $job = \dbObject\SearchJob::findByIdAndToken($jobId, $jobToken);

    if (!$job || !$job->matchesViewerContext($viewerContext)) {
        http_response_code(403);
        omoSearchPopupRenderContent($query, $selectedScopes, $scopeLabels, array(
            'status' => 'failed',
            'error' => 'Cette recherche n est plus accessible dans le contexte courant.',
        ), $escape);
        exit;
    }

    $query = trim((string)$job->get('query'));
    $selectedScopes = omoSearchPopupResolveScopes($job->getScopes(), $scopeLabels);
    $dateRange = omoSearchPopupResolveDateRange($job->getDateRange()['startDate'] ?? '', $job->getDateRange()['endDate'] ?? '', $organization);
    $jobPayload = omoSearchPopupBuildJobPayload($job);

    omoSearchPopupRenderContent($query, $selectedScopes, $scopeLabels, $jobPayload, $escape);
    exit;
}

omoSearchPopupRenderStyles();
?>
<div
    class="omo-search-popup"
    data-omo-search-popup-root="1"
    data-omo-search-popup-oid="<?= (int)$organizationId ?>"
    data-omo-search-popup-cid="<?= (int)$currentHolonId ?>"
>
    <?php omoSearchPopupRenderSearchForm($query, $selectedScopes, $scopeLabels, $dateRange, $escape); ?>
    <div data-omo-search-popup-content>
        <?php
        if ($restoredPayload !== null) {
            omoSearchPopupRenderContent($query, $selectedScopes, $scopeLabels, $restoredPayload, $escape);
            if ($restoredJob instanceof \dbObject\SearchJob) {
                $restoredStatus = (string)$restoredJob->get('status');
                if ($restoredStatus !== 'completed' && $restoredStatus !== 'failed') {
                    $statusUrl = '/omo/api/search_popup.php'
                        . '?partial=1'
                        . '&oid=' . rawurlencode((string)$organizationId)
                        . '&cid=' . rawurlencode((string)$currentHolonId)
                        . '&q=' . rawurlencode($query)
                        . '&job_id=' . rawurlencode((string)$restoredJob->getId())
                        . '&job_token=' . rawurlencode((string)$restoredJob->get('requesttoken'));
                    foreach (array_values($selectedScopes) as $scope) {
                        $statusUrl .= '&scopes[]=' . rawurlencode((string)$scope);
                    }
                    omoSearchPopupRenderPollingScript($statusUrl);
                }
            }
        } elseif ($query === '') {
            omoSearchPopupRenderContent($query, $selectedScopes, $scopeLabels, array(
                'status' => 'completed',
                'results' => array(),
                'counts' => array(
                    'structure' => 0,
                    'team' => 0,
                    'calendar' => 0,
                    'rules' => 0,
                    'documents' => 0,
                    'decision' => 0,
                    'projects' => 0,
                    'stats' => 0,
                    'faq' => 0,
                    'tutorials' => 0,
                ),
            ), $escape);
        } else {
            $job = \dbObject\SearchJob::createTopbarJob($organization, $query, array_values($selectedScopes), $viewerContext, array(
                'currentHolonId' => $currentHolonId,
                'dateRange' => $dateRange,
            ));

            if (!$job) {
                omoSearchPopupRenderContent($query, $selectedScopes, $scopeLabels, array(
                    'status' => 'failed',
                    'error' => 'Impossible de creer le job de recherche.',
                ), $escape);
            } else {
                $jobDispatched = $job->dispatchAsync();
                if (!$jobDispatched) {
                    \dbObject\SearchJob::processJobById((int)$job->getId());
                }

                omoSearchPopupRenderContent($query, $selectedScopes, $scopeLabels, array(
                    'status' => $jobDispatched ? 'queued' : (string)$job->get('status'),
                ), $escape);
                $clientJobState = array(
                    'jobId' => (int)$job->getId(),
                    'jobToken' => (string)$job->get('requesttoken'),
                    'query' => $query,
                    'scopes' => array_values($selectedScopes),
                    'startDate' => $dateRange['startDate'],
                    'endDate' => $dateRange['endDate'],
                    'organizationId' => (int)$organizationId,
                    'currentHolonId' => (int)$currentHolonId,
                    'syncHash' => true,
                );

                $statusUrl = '/omo/api/search_popup.php'
                    . '?partial=1'
                    . '&oid=' . rawurlencode((string)$organizationId)
                    . '&cid=' . rawurlencode((string)$currentHolonId)
                    . '&q=' . rawurlencode($query)
                    . '&job_id=' . rawurlencode((string)$job->getId())
                    . '&job_token=' . rawurlencode((string)$job->get('requesttoken'));
                foreach (array_values($selectedScopes) as $scope) {
                    $statusUrl .= '&scopes[]=' . rawurlencode((string)$scope);
                }
                omoSearchPopupRenderPollingScript($statusUrl);
            }
        }
        ?>
    </div>
</div>
<?php if (is_array($clientJobState)): ?>
<script>
(function () {
    if (typeof window.omoRegisterSearchPopupJobState !== 'function') {
        return;
    }

    window.omoRegisterSearchPopupJobState(
        <?= json_encode($clientJobState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    );
})();
</script>
<?php endif; ?>
<script>
(function () {
    var root = document.querySelector('[data-omo-search-popup-root="1"]');
    if (!root || root.dataset.omoSearchPopupUiBound === '1') {
        return;
    }

    var searchForm = root.querySelector('[data-omo-search-popup-form]');
    var searchInput = root.querySelector('[data-omo-search-popup-input]');
    var organizationId = Number(root.getAttribute('data-omo-search-popup-oid') || '0');
    var currentHolonId = Number(root.getAttribute('data-omo-search-popup-cid') || '0');
    var previousCleanup = typeof window.__omoPopupCleanup === 'function'
        ? window.__omoPopupCleanup
        : null;

    root.dataset.omoSearchPopupUiBound = '1';

    if (typeof window.commonTopbarInitializeSearchPeriod === 'function') {
        window.commonTopbarInitializeSearchPeriod(searchForm);
    }

    function buildPopupUrl(query, scopes) {
        var queryParts = [
            'q=' + encodeURIComponent(String(query || '').trim())
        ];

        if (Number.isInteger(organizationId) && organizationId > 0) {
            queryParts.push('oid=' + encodeURIComponent(organizationId));
        }

        if (Number.isInteger(currentHolonId) && currentHolonId > 0) {
            queryParts.push('cid=' + encodeURIComponent(currentHolonId));
        }

        var startDateInput = root.querySelector('[data-topbar-search-period-start]');
        var endDateInput = root.querySelector('[data-topbar-search-period-end]');
        if (startDateInput && startDateInput.value) {
            queryParts.push('date_start=' + encodeURIComponent(startDateInput.value));
        }
        if (endDateInput && endDateInput.value) {
            queryParts.push('date_end=' + encodeURIComponent(endDateInput.value));
        }

        (Array.isArray(scopes) ? scopes : []).forEach(function (scopeId) {
            var normalizedScopeId = String(scopeId || '').trim();
            if (normalizedScopeId !== '') {
                queryParts.push('scopes[]=' + encodeURIComponent(normalizedScopeId));
            }
        });

        return '/omo/api/search_popup.php?' + queryParts.join('&');
    }

    function getSelectedScopes() {
        if (!searchForm) {
            return [];
        }

        return Array.prototype.map.call(
            searchForm.querySelectorAll('[data-omo-search-popup-scope-input]:checked'),
            function (input) {
                return String(input.value || '').trim();
            }
        ).filter(function (scopeId) {
            return scopeId !== '';
        });
    }

    function relaunchSearch(event) {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        if (!searchForm || !searchInput) {
            return;
        }

        var query = String(searchInput.value || '').trim();
        var scopes = getSelectedScopes();
        var startDateInput = root.querySelector('[data-topbar-search-period-start]');
        var endDateInput = root.querySelector('[data-topbar-search-period-end]');
        var dateRange = {
            startDate: startDateInput ? String(startDateInput.value || '') : '',
            endDate: endDateInput ? String(endDateInput.value || '') : ''
        };

        if (typeof window.omoOpenSearchPopupHashState === 'function' && window.omoOpenSearchPopupHashState(query, scopes, dateRange)) {
            return;
        }

        if (typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        window.commonTopbarOpenModal(
            'Recherche',
            buildPopupUrl(query, scopes),
            'fetch'
        );
    }

    function applyClientSideModuleFilter(scopeId) {
        var normalizedScopeId = String(scopeId || '').trim();
        var activeScopeId = String(root.getAttribute('data-omo-search-popup-active-filter') || '').trim();
        var nextScopeId = normalizedScopeId !== '' && normalizedScopeId !== activeScopeId ? normalizedScopeId : '';
        var results = root.querySelectorAll('[data-omo-search-popup-result-module]');
        var statButtons = root.querySelectorAll('[data-omo-search-popup-stat-filter]');
        var filterEmpty = root.querySelector('[data-omo-search-popup-filter-empty]');
        var visibleCount = 0;

        Array.prototype.forEach.call(results, function (resultNode) {
            var resultScopeId = String(resultNode.getAttribute('data-omo-search-popup-result-module') || '').trim();
            var isVisible = nextScopeId === '' || resultScopeId === nextScopeId;
            resultNode.classList.toggle('is-filtered-out', !isVisible);
            resultNode.hidden = !isVisible;
            if (isVisible) {
                visibleCount += 1;
            }
        });

        Array.prototype.forEach.call(statButtons, function (button) {
            var buttonScopeId = String(button.getAttribute('data-omo-search-popup-stat-filter') || '').trim();
            var isActive = nextScopeId !== '' && buttonScopeId === nextScopeId;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('data-omo-search-popup-stat-active', isActive ? '1' : '0');
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (filterEmpty) {
            filterEmpty.hidden = visibleCount !== 0 || nextScopeId === '';
        }

        if (nextScopeId === '') {
            root.removeAttribute('data-omo-search-popup-active-filter');
        } else {
            root.setAttribute('data-omo-search-popup-active-filter', nextScopeId);
        }
    }

    function handleStatFilterClick(event) {
        var statButton = event.target.closest('[data-omo-search-popup-stat-filter]');
        if (!statButton) {
            return false;
        }

        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        var scopeId = String(statButton.getAttribute('data-omo-search-popup-stat-filter') || '').trim();
        if (scopeId === '') {
            return true;
        }

        applyClientSideModuleFilter(scopeId);
        return true;
    }

    function handleResultClick(event) {
        if (handleStatFilterClick(event)) {
            return;
        }

        var structureButton = event.target.closest('[data-omo-search-open-structure]');
        if (structureButton && typeof window.omoOpenSearchStructureResult === 'function') {
            window.omoOpenSearchStructureResult(Number(structureButton.getAttribute('data-omo-search-open-structure') || '0'));
            return;
        }

        var rulesButton = event.target.closest('[data-omo-search-open-rules-holon]');
        if (rulesButton && typeof window.omoOpenSearchRulesResult === 'function') {
            window.omoOpenSearchRulesResult(Number(rulesButton.getAttribute('data-omo-search-open-rules-holon') || '0'));
            return;
        }

        var userButton = event.target.closest('[data-omo-search-open-user]');
        if (userButton && typeof window.omoOpenSearchUserResult === 'function') {
            window.omoOpenSearchUserResult(Number(userButton.getAttribute('data-omo-search-open-user') || '0'));
            return;
        }

        var documentButton = event.target.closest('[data-omo-search-open-document]');
        if (documentButton && typeof window.omoOpenSearchDocumentResult === 'function') {
            window.omoOpenSearchDocumentResult(
                documentButton.getAttribute('data-omo-search-open-document') || '',
                documentButton.getAttribute('data-omo-search-document-title') || 'Document'
            );
            return;
        }

        var calendarEventButton = event.target.closest('[data-omo-search-open-calendar-event-id]');
        if (calendarEventButton && typeof window.omoOpenSearchCalendarEventResult === 'function') {
            window.omoOpenSearchCalendarEventResult(
                Number(calendarEventButton.getAttribute('data-omo-search-open-calendar-event-id') || '0'),
                Number(calendarEventButton.getAttribute('data-omo-search-open-calendar-event-holon') || '0')
            );
            return;
        }

        var decisionButton = event.target.closest('[data-omo-search-open-decision-id]');
        if (decisionButton && typeof window.omoOpenSearchDecisionResult === 'function') {
            window.omoOpenSearchDecisionResult(
                Number(decisionButton.getAttribute('data-omo-search-open-decision-id') || '0'),
                Number(decisionButton.getAttribute('data-omo-search-open-decision-holon') || '0')
            );
            return;
        }

        var projectButton = event.target.closest('[data-omo-search-open-project-id]');
        if (projectButton && typeof window.omoOpenSearchProjectResult === 'function') {
            window.omoOpenSearchProjectResult(
                Number(projectButton.getAttribute('data-omo-search-open-project-id') || '0'),
                Number(projectButton.getAttribute('data-omo-search-open-project-holon') || '0')
            );
            return;
        }

        var indicatorButton = event.target.closest('[data-omo-search-open-stat-indicator-id]');
        if (indicatorButton && typeof window.omoOpenSearchStatIndicatorResult === 'function') {
            window.omoOpenSearchStatIndicatorResult(
                Number(indicatorButton.getAttribute('data-omo-search-open-stat-indicator-id') || '0'),
                Number(indicatorButton.getAttribute('data-omo-search-open-stat-indicator-holon') || '0')
            );
            return;
        }

        var faqButton = event.target.closest('[data-omo-search-open-faq]');
        if (faqButton && typeof window.omoOpenFaqHashState === 'function') {
            window.omoOpenFaqHashState(Number(faqButton.getAttribute('data-omo-search-open-faq') || '0'));
            return;
        }

        var tutorialButton = event.target.closest('[data-omo-search-open-tutorial-parcours]');
        if (tutorialButton && typeof window.omoOpenSearchTutorialResult === 'function') {
            window.omoOpenSearchTutorialResult(
                Number(tutorialButton.getAttribute('data-omo-search-open-tutorial-parcours') || '0'),
                Number(tutorialButton.getAttribute('data-omo-search-open-tutorial-mission') || '0')
            );
        }
    }

    if (searchForm) {
        searchForm.addEventListener('submit', relaunchSearch);
    }

    root.addEventListener('click', handleResultClick);

    window.__omoPopupCleanup = function () {
        if (searchForm) {
            searchForm.removeEventListener('submit', relaunchSearch);
        }

        root.removeEventListener('click', handleResultClick);
        root.dataset.omoSearchPopupUiBound = '0';

        if (previousCleanup) {
            previousCleanup();
        }
    };
})();
</script>
