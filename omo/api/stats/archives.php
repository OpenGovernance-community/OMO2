<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayStatIndicator;
use dbObject\Holon;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = (int)($_GET['cid'] ?? 0);
$context = omoStatsResolveContext($organizationId, $currentHolonId);
if (empty($context['status'])) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoStatsT('stats.error.context')) . '</div>';
    exit;
}
$holon = $context['currentHolon'];
$root = $context['rootHolon'];
$scope = omoApiNormalizeContextScope($_GET['stats_scope'] ?? 'contextual', omoApiGetAvailableContextScopes($holon instanceof Holon, $holon, $root));
$assignment = strtolower(trim((string)($_GET['stats_assignment'] ?? 'all')));
$assignment = in_array($assignment, ['mine', 'roles'], true) ? $assignment : 'all';
$holonIds = $scope === 'children' ? omoApiGetDirectChildScopeHolonIds($holon)
    : ($scope === 'descendants' ? omoApiGetDescendantHolonIds($holon) : []);
$includeOrganization = $holon instanceof Holon && $root instanceof Holon && $holon->getId() === $root->getId();
$indicators = new ArrayStatIndicator();
$indicators->loadForContext($organizationId, $holon instanceof Holon ? $holon->getId() : 0, $scope, $holonIds, $includeOrganization, true);
$query = omoApiNormalizeLabel($_GET['archive_query'] ?? '');
$items = [];
foreach ($indicators as $indicator) {
    if (!omoStatsMatchesAssignment($indicator, $assignment, commonGetCurrentUserId(), $organizationId)) continue;
    $metadata = [omoStatsContextLabel($indicator), omoStatsResponsibleAssignmentNames($indicator)['person']];
    $title = (string)$indicator->get('name');
    if ($query !== '' && !str_contains(omoApiNormalizeLabel($title . ' ' . implode(' ', $metadata)), $query)) continue;
    $items[] = ['id' => $indicator->getId(), 'title' => $title, 'date' => $indicator->get('archived_at'), 'metadata' => $metadata,
        'url' => '#stats-i' . $indicator->getId()];
}
omoRenderResourceArchives($items, omoStatsT('stats.archives.empty'));
