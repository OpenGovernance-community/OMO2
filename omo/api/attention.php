<?php
require_once __DIR__ . '/bootstrap.php';

use dbObject\DecisionProcess;
use dbObject\ControlActivity;
use dbObject\Holon;
use dbObject\Organization;

$userId = (int)commonGetCurrentUserId();
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
if ($userId <= 0 || commonGetCurrentShareLink() || !commonUserHasOrganizationAccess($userId, $organizationId)) {
    http_response_code(403);
    exit;
}
// This background request must never hold up another request from the same browser.
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
header('Content-Type: application/json; charset=UTF-8');

$sourceLang = [
    'decision.pending' => ['text' => 'Une decision sur laquelle se prononcer dans {spaceType} {spaceName}.', 'context' => 'Persistent Applications badge tooltip for an unanswered decision invitation.'],
    'decision.more' => ['one' => ' Et {count} autre ailleurs.', 'other' => ' Et {count} autres ailleurs.', 'context' => 'Additional unanswered decisions after the first one in the Applications badge tooltip.'],
    'activity.overdue' => ['text' => 'Une tache recurrente en retard dans {spaceType} {spaceName} : {title}.', 'context' => 'Recurring tasks badge tooltip, naming the first overdue task assigned to the current user.'],
    'activity.more' => ['one' => ' Et {count} autre dans {spaces}.', 'other' => ' Et {count} autres dans {spaces}.', 'context' => 'Other overdue recurring tasks assigned to the current user, with their number of distinct spaces.'],
    'activity.spaces' => ['one' => '{count} espace', 'other' => '{count} espaces', 'context' => 'Number of distinct spaces containing the remaining overdue recurring tasks.'],
    'scope.organization' => ['text' => "l'organisation", 'context' => 'Decision invitation tooltip scope, including the article.'],
    'scope.circle' => ['text' => 'le cercle', 'context' => 'Decision invitation tooltip scope, including the article.'],
    'scope.role' => ['text' => 'le role', 'context' => 'Decision invitation tooltip scope, including the article.'],
    'scope.group' => ['text' => 'le groupe', 'context' => 'Decision invitation tooltip scope, including the article.'],
    'scope.space' => ['text' => "l'espace", 'context' => 'Decision invitation tooltip scope, including the article.'],
];
$bundle = loadTranslationBundle('omo_attention', omoGetTranslationLocale(), $sourceLang);

try {
    $organization = new Organization();
    $signals = [];
    $organizationLoaded = $organization->load($organizationId);
    $spaces = [];
    $resolveSpace = static function (int $holonId) use (&$spaces, $organization): ?Holon {
        if ($holonId <= 0) return null;
        if (!array_key_exists($holonId, $spaces)) {
            $holon = new Holon();
            $spaces[$holonId] = $holon->load($holonId) && $organization->containsHolon($holon)
                && $holon->canViewDetail() ? $holon : null;
        }
        return $spaces[$holonId];
    };
    if ($organizationLoaded && $organization->isApplicationEnabled('decision', $userId)) {
        $decisionSignals = [];
        foreach (DecisionProcess::getPendingEvaluationsForUser($organizationId, $userId) as $row) {
            $decision = new DecisionProcess();
            $decision->hydrateFromDatabaseRow($row, true);
            if ($decision->isGovernanceWorkflow()
                && $organization->getInterfaceLevel() < Organization::INTERFACE_LEVEL_AUTONOMOUS) continue;
            $holonId = (int)($row['IDholon'] ?? 0);
            $holon = $resolveSpace($holonId);
            if ($holonId > 0 && !$holon) continue;
            $decisionSignals[] = [
                'type' => 'decision',
                'id' => (int)$row['id'],
                'oid' => $organizationId,
                'cid' => $organization->isStructureApplicationEnabled($userId) ? $holonId : 0,
                'routeToken' => 'decision-p' . (int)$row['id'],
                'message' => t('decision.pending', [
                    'spaceType' => t('scope.' . ($holon ? $holon->getTypeLexiconKey() : 'organization'), [], $bundle, $sourceLang),
                    'spaceName' => $holon ? $holon->get('name') : $organization->get('name'),
                ], $bundle, $sourceLang),
            ];
        }
        if ($decisionSignals) {
            $signal = $decisionSignals[0];
            $signal['count'] = count($decisionSignals);
            if ($signal['count'] > 1) $signal['message'] .= t('decision.more', ['count' => $signal['count'] - 1], $bundle, $sourceLang);
            $signals[] = $signal;
        }
    }
    if ($organizationLoaded && $organization->isApplicationEnabled('activities', $userId)) {
        $activitySignals = [];
        $activitySpaces = [];
        foreach (ControlActivity::getOverdueForUser($organizationId, $userId) as $row) {
            $activity = $row['activity'];
            $holonId = (int)$activity->get('IDholon');
            $holon = $resolveSpace($holonId);
            if ($holonId > 0 && !$holon) continue;
            $activitySpaces[] = $holonId;
            $activitySignals[] = [
                'type' => 'activities',
                'id' => (int)$activity->getId(),
                'oid' => $organizationId,
                'cid' => $organization->isStructureApplicationEnabled($userId) ? $holonId : 0,
                'routeToken' => 'activities-d' . (int)$activity->getId(),
                'message' => t('activity.overdue', [
                    'spaceType' => t('scope.' . ($holon ? $holon->getTypeLexiconKey() : 'organization'), [], $bundle, $sourceLang),
                    'spaceName' => $holon ? $holon->get('name') : $organization->get('name'),
                    'title' => $activity->get('title'),
                ], $bundle, $sourceLang),
            ];
        }
        if ($activitySignals) {
            $signal = $activitySignals[0];
            $signal['count'] = count($activitySignals);
            $signal['spaceCount'] = count(array_unique($activitySpaces));
            if ($signal['count'] > 1) {
                $signal['message'] .= t('activity.more', [
                    'count' => $signal['count'] - 1,
                    'spaces' => t('activity.spaces', ['count' => count(array_unique(array_slice($activitySpaces, 1)))], $bundle, $sourceLang),
                ], $bundle, $sourceLang);
            }
            $signals[] = $signal;
        }
    }
    echo json_encode(['signals' => $signals], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    error_log('omo_attention_failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'attention_unavailable']);
}
