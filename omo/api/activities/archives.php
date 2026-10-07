<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayControlActivity;
use dbObject\Holon;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = (int)($_GET['cid'] ?? 0);
$context = omoActivityResolveContext($organizationId, $currentHolonId);
if (empty($context['status'])) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoActivityT('activity.error.context')) . '</div>';
    exit;
}
$holon = $context['currentHolon'];
$root = $context['rootHolon'];
$scope = omoApiNormalizeContextScope($_GET['activity_scope'] ?? 'contextual', omoApiGetAvailableContextScopes(true, $holon, $root));
$assignment = omoActivityNormalizeAssignment($_GET['activity_assignment'] ?? 'all');
$holonIds = $scope === 'children' ? omoApiGetDirectChildScopeHolonIds($holon)
    : ($scope === 'descendants' ? omoApiGetDescendantHolonIds($holon) : ($holon instanceof Holon ? [(int)$holon->getId()] : []));
$includeOrganization = !($holon instanceof Holon) || ($root instanceof Holon && $holon->getId() === $root->getId());
$activities = new ArrayControlActivity();
$activities->loadForContext($organizationId, $holonIds, false, $includeOrganization, true);
$query = omoApiNormalizeLabel($_GET['archive_query'] ?? '');
$items = [];
foreach ($activities as $activity) {
    if (!omoActivityCanView($activity) || !omoActivityMatchesAssignment($activity, $assignment, commonGetCurrentUserId(), $organizationId)) continue;
    $space = $activity->getHolon();
    $metadata = [
        $space instanceof Holon ? $space->getDisplayName() : (string)$context['organization']->get('name'),
        omoActivityResponsibleAssignmentNames($activity)['person'],
        omoActivityFrequencyLabel($activity->get('frequency')),
    ];
    $title = (string)$activity->get('title');
    if ($query !== '' && !str_contains(omoApiNormalizeLabel($title . ' ' . implode(' ', $metadata)), $query)) continue;
    $items[] = ['id' => $activity->getId(), 'title' => $title, 'date' => $activity->get('archived_at'), 'metadata' => $metadata,
        'url' => '#activities-d' . $activity->getId()];
}
omoRenderResourceArchives($items, omoActivityT('activity.archives.empty'));
