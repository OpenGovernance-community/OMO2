<?php
require_once __DIR__ . '/bootstrap.php';

if (!function_exists('omoSearchPopupGetScopeLabels')) {
    function omoSearchPopupGetScopeLabels()
    {
        return array(
            'structure' => 'Structure',
            'team' => 'Team',
            'documents' => 'Documents',
        );
    }
}

if (!function_exists('omoSearchPopupResolveScopes')) {
    function omoSearchPopupResolveScopes($rawScopes)
    {
        $scopeLabels = omoSearchPopupGetScopeLabels();
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
            $selectedScopes['structure'] = 'structure';
        }

        return $selectedScopes;
    }
}

if (!function_exists('omoSearchPopupRenderStyles')) {
    function omoSearchPopupRenderStyles()
    {
        ?>
        <style>
        .omo-search-popup {
            display: grid;
            gap: 16px;
            padding: 18px;
            background: var(--color-bg, #f8fafc);
            color: var(--color-text, #0f172a);
        }

        .omo-search-popup__hero,
        .omo-search-popup__result,
        .omo-search-popup__status-card {
            --generic-section-padding-block: 18px;
            --generic-section-padding-inline: 18px;
        }

        .omo-search-popup__head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
        }

        .omo-search-popup__summary {
            margin: 6px 0 0;
            color: var(--color-text-light, #475569);
            line-height: 1.5;
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
            background: rgba(148, 163, 184, 0.14);
            color: var(--color-text-light, #475569);
            font-size: 0.85rem;
        }

        .omo-search-popup__stats {
            margin-top: 14px;
        }

        .omo-search-popup__stat {
            min-width: 92px;
            padding: 10px 12px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(148, 163, 184, 0.18);
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
            color: var(--color-text-light, #475569);
        }

        .omo-search-popup__list {
            display: grid;
            gap: 12px;
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
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.7);
            color: var(--color-text-light, #475569);
        }

        .omo-search-popup__status-card.is-error {
            border: 1px solid rgba(220, 38, 38, 0.18);
            background: rgba(254, 242, 242, 0.88);
        }

        .omo-search-popup__spinner {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 2px solid rgba(148, 163, 184, 0.25);
            border-top-color: rgba(15, 23, 42, 0.72);
            animation: omo-search-popup-spin 0.9s linear infinite;
        }

        @keyframes omo-search-popup-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 720px) {
            .omo-search-popup__head,
            .omo-search-popup__result-head {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        </style>
        <?php
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
            <div class="omo-search-popup__stat">
                <strong><?= (int)($counts[$scopeId] ?? 0) ?></strong>
                <span><?= $escape($scopeLabel) ?></span>
            </div>
            <?php
        }
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
                <div class="omo-search-popup__list">
                    <?php foreach ($results as $index => $result): ?>
                        <?php
                        $module = (string)($result['module'] ?? '');
                        $action = is_array($result['action'] ?? null) ? $result['action'] : array();
                        $buttonAttributes = '';
                        if ($module === 'structure' && !empty($action['holonId'])) {
                            $buttonAttributes = ' data-omo-search-open-structure="' . (int)$action['holonId'] . '"';
                        } elseif ($module === 'team' && !empty($action['userId'])) {
                            $buttonAttributes = ' data-omo-search-open-user="' . (int)$action['userId'] . '"';
                        } elseif ($module === 'documents' && !empty($action['documentUrl'])) {
                            $buttonAttributes = ' data-omo-search-open-document="' . htmlspecialchars((string)$action['documentUrl'], ENT_QUOTES, 'UTF-8') . '"'
                                . ' data-omo-search-document-title="' . htmlspecialchars((string)($result['title'] ?? 'Document'), ENT_QUOTES, 'UTF-8') . '"';
                        }
                        ?>
                        <article class="omo-search-popup__result generic-section">
                            <div class="omo-search-popup__result-head">
                                <div class="omo-search-popup__result-meta">
                                    <span class="omo-search-popup__badge"><?= $escape((string)($result['moduleLabel'] ?? $module)) ?></span>
                                    <span class="omo-search-popup__score">score <?= (int)($result['relevance'] ?? 0) ?></span>
                                </div>
                                <span class="omo-search-popup__rank">#<?= $index + 1 ?></span>
                            </div>

                            <div class="omo-search-popup__result-body">
                                <h4><?= $escape((string)($result['title'] ?? 'Resultat')) ?></h4>
                                <?php if (trim((string)($result['subtitle'] ?? '')) !== ''): ?>
                                    <div class="omo-search-popup__subtitle"><?= $escape((string)$result['subtitle']) ?></div>
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
            <?php endif; ?>
        </div>
        <?php
    }
}

$organizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$currentHolonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$query = trim((string)($_GET['q'] ?? ''));
$selectedScopes = omoSearchPopupResolveScopes($_GET['scopes'] ?? array());
$scopeLabels = omoSearchPopupGetScopeLabels();
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

$viewerContext = \dbObject\SearchJob::buildViewerContextFromGlobals($organizationId, $currentHolonId);

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
    $selectedScopes = omoSearchPopupResolveScopes($job->getScopes());

    $jobPayload = array(
        'status' => (string)$job->get('status'),
        'error' => (string)$job->get('errormessage'),
    );
    if ((string)$job->get('status') === 'completed') {
        $jobPayload = array_merge($job->getResultPayload(), array(
            'status' => 'completed',
        ));
    }

    omoSearchPopupRenderContent($query, $selectedScopes, $scopeLabels, $jobPayload, $escape);
    exit;
}

omoSearchPopupRenderStyles();
?>
<div class="omo-search-popup" data-omo-search-popup-root="1">
    <div data-omo-search-popup-content>
        <?php
        if ($query === '') {
            omoSearchPopupRenderContent($query, $selectedScopes, $scopeLabels, array(
                'status' => 'completed',
                'results' => array(),
                'counts' => array(
                    'structure' => 0,
                    'team' => 0,
                    'documents' => 0,
                ),
            ), $escape);
        } else {
            $job = \dbObject\SearchJob::createTopbarJob($organization, $query, array_values($selectedScopes), $viewerContext, array(
                'currentHolonId' => $currentHolonId,
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
                ?>
                <script>
                (function () {
                    var root = document.querySelector('[data-omo-search-popup-root="1"]');
                    if (!root) {
                        return;
                    }

                    var content = root.querySelector('[data-omo-search-popup-content]');
                    var statusUrl = <?= json_encode($statusUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
                    var timerId = 0;
                    var stopped = false;

                    function bindResultActions() {
                        if (root.dataset.omoSearchPopupBound === '1') {
                            return;
                        }

                        root.dataset.omoSearchPopupBound = '1';
                        root.addEventListener('click', function (event) {
                            var structureButton = event.target.closest('[data-omo-search-open-structure]');
                            if (structureButton && typeof window.omoOpenSearchStructureResult === 'function') {
                                window.omoOpenSearchStructureResult(Number(structureButton.getAttribute('data-omo-search-open-structure') || '0'));
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
                            }
                        });
                    }

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
                                bindResultActions();

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

                    bindResultActions();
                    window.__omoPopupCleanup = cleanup;
                    loadState();
                })();
                </script>
                <?php
            }
        }
        ?>
    </div>
</div>
