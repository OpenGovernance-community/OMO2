<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/topbar.php';
require_once dirname(__DIR__, 2) . '/common/search_text.php';
require_once dirname(__DIR__) . '/topbar.php';
require_once __DIR__ . '/stats/shared.php';
require_once __DIR__ . '/search/preview_shared.php';

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
            'processus' => 'Processus',
            'activities' => 'Taches recurrentes',
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
            'processus' => 'processus',
            'activities' => 'activities',
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
        <link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/search_popup.css') ?>">
        <link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/stats/stats.css') ?>">
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
                class="omo-search-popup__stat<?= (int)($counts[$scopeId] ?? 0) > 0 ? ' has-results' : '' ?>"
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

if (!function_exists('omoSearchPopupHighlightTerms')) {
    function omoSearchPopupHighlightTerms($value, $query, $escape)
    {
        $value = (string)$value;
        $query = trim((string)$query);
        if ($value === '' || $query === '') {
            return $escape($value);
        }

        $terms = commonSearchQueryTerms($query);
        if (!$terms) { return $escape($value); }
        $pattern = '/(' . implode('|', array_map('commonBuildSearchMatchPattern', $terms)) . ')/iu';
        $parts = preg_split($pattern, $value, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $escape($value);
        }

        $html = '';
        foreach ($parts as $index => $part) {
            $html .= $index % 2 === 1
                ? '<mark class="omo-search-popup__match">' . $escape($part) . '</mark>'
                : $escape($part);
        }
        return $html;
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
            <?php if ($status === 'completed'): ?>
                <div class="omo-search-popup__stats">
                    <?php omoSearchPopupRenderStats($selectedScopes, $scopeLabels, $counts, $escape); ?>
                </div>
            <?php endif; ?>

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
                        } elseif ($module === 'processus' && !empty($action['checklistId'])) {
                            $buttonAttributes = ' data-omo-search-open-checklist-id="' . (int)$action['checklistId'] . '"'
                                . ' data-omo-search-open-checklist-holon="' . (int)($action['holonId'] ?? 0) . '"';
                        } elseif ($module === 'activities' && !empty($action['activityId'])) {
                            $buttonAttributes = ' data-omo-search-open-activity-id="' . (int)$action['activityId'] . '"'
                                . ' data-omo-search-open-activity-holon="' . (int)($action['holonId'] ?? 0) . '"';
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
                                <h4><?= omoSearchPopupHighlightTerms((string)($result['title'] ?? 'Resultat'), $query, $escape) ?></h4>
                                <?php if ($subtitle !== ''): ?>
                                    <div class="omo-search-popup__subtitle"><?= omoSearchPopupHighlightTerms($subtitle, $query, $escape) ?></div>
                                <?php endif; ?>
                                <?php if (trim((string)($result['excerpt'] ?? '')) !== ''): ?>
                                    <p class="omo-search-popup__excerpt"><?= omoSearchPopupHighlightTerms((string)$result['excerpt'], $query, $escape) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="omo-search-popup__actions">
                                <?php
                                $previewIdKeys = ['structure' => 'holonId', 'team' => 'userId', 'calendar' => 'eventId',
                                    'documents' => 'documentId', 'pv' => 'documentId', 'rules' => 'ruleId',
                                    'decision' => 'decisionId', 'projects' => 'projectId', 'stats' => 'indicatorId',
                                    'processus' => 'checklistId', 'activities' => 'activityId', 'faq' => 'faqId', 'tutorials' => 'parcoursId'];
                                ?>
                                <button type="button" class="generic-action-button generic-action-button--main"
                                    data-omo-search-preview="<?= $escape($module) ?>"
                                    data-omo-search-preview-id="<?= (int)($action[$previewIdKeys[$module] ?? ''] ?? 0) ?>"
                                    data-omo-search-preview-mission="<?= (int)($action['missionId'] ?? 0) ?>"
                                    <?= $buttonAttributes ?>><?= $escape(omoSearchPreviewT('preview')) ?></button>
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
    data-omo-search-preview-loading="<?= $escape(omoSearchPreviewT('loading')) ?>"
    data-omo-search-preview-error="<?= $escape(omoSearchPreviewT('error')) ?>"
    data-omo-search-preview-open-label="<?= $escape(omoSearchPreviewT('open')) ?>"
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
    <div class="omo-overlay-drawer omo-overlay-drawer--detail-panel" data-omo-search-preview-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-omo-search-preview-close></div>
        <section class="omo-overlay-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="omo-search-preview-title" tabindex="-1">
            <header class="omo-overlay-drawer__header generic-drawer-header generic-drawer-header--sticky">
                <div class="generic-drawer-header__copy">
                    <h3 class="omo-overlay-drawer__title" id="omo-search-preview-title" data-omo-subdrawer-title><?= $escape(omoSearchPreviewT('preview')) ?></h3>
                </div>
                <div class="generic-drawer-header__actions">
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-search-preview-close><?= $escape(omoSearchPreviewT('close')) ?></button>
                </div>
            </header>
            <div class="omo-overlay-drawer__body" data-omo-search-preview-body aria-live="polite"></div>
            <footer class="generic-drawer-footer">
                <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-search-preview-retry hidden><?= $escape(omoSearchPreviewT('retry')) ?></button>
                <div data-omo-search-preview-action></div>
            </footer>
        </section>
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
<script src="<?= commonAssetUrl('/omo/api/search_popup.js') ?>"></script>
