<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/api/projects.php';
require_once dirname(__DIR__) . '/omo/translations.php';
require_once dirname(__DIR__) . '/omo/api/projects/params/shared.php';

function omoApiEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

use dbObject\{Project, ArrayApplication, OrganizationApplication};

$before = $_SESSION;
$items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId();
    $_SESSION = ['currentUser' => (int)$items['user']->getId(), 'currentOrganization' => $oid];
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'value' => 'projects']], 'limit' => 1]);
    mcpCheck(count($apps) === 1, 'Projects app exists');
    $link = $items['projects_app'] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $oid, 'IDapplication' => $apps[0]->getId(), 'active' => 1]);
    mcpCheck(Project::getOrganizationBlockingConfig($oid) === ['requireReason' => true, 'requireReviewDate' => true], 'Both fields required by default');
    $project = new Project();
    $project->set('IDorganization', $oid);
    $project->set('IDholon', $items['role']->getId());
    $project->set('title', 'Blocking settings test');
    $project->set('active', 1);
    $items['project'] = $project;
    $base = ['save_display' => '1', 'save_blocking' => '1', 'parent_weight' => '0.7', 'depth_penalty' => '0.15',
        'enabled_statuses' => Project::statuses(), 'use_priority' => '1', 'use_importance' => '1', 'use_size' => '1'];

    foreach ([true, false] as $requireReason) {
        foreach ([true, false] as $requireDate) {
            $values = $base;
            if ($requireReason) $values['require_blocked_reason'] = '1';
            if ($requireDate) $values['require_blocked_review_date'] = '1';
            $saved = omoProjectsParamsStoreConfig($items['org'], $values);
            mcpCheck(!empty($saved['status']), 'Save blocking settings through settings form');
            mcpCheck(Project::getOrganizationBlockingConfig($oid) === ['requireReason' => $requireReason, 'requireReviewDate' => $requireDate], 'Settings persist independently');
            mcpCheck(Project::getOrganizationBlockingConfig((int)$items['other_org']->getId()) === ['requireReason' => true, 'requireReviewDate' => true], 'Organization settings remain isolated');
            foreach ([null, '', 'Waiting'] as $reason) {
                foreach ([null, '', '2030-01-04'] as $date) {
                    $expected = (!$requireReason || $reason === 'Waiting') && (!$requireDate || $date === '2030-01-04');
                    mcpCheck(Project::hasValidBlockedDetails($oid, $reason, $date) === $expected, 'Validate each optional field independently');
                    $project->set('status', Project::STATUS_BLOCKED);
                    $project->set('blocked_reason', $reason);
                    $project->set('blocked_until', $date);
                    $project->set('blocked_auto_reactivate', 1);
                    $result = $project->save();
                    mcpCheck(!empty($result['status']) === $expected, 'Native save follows blocking requirements');
                    if (!$expected) continue;
                    mcpCheck($project->load($project->getId(), true), 'Reload blocked project');
                    mcpCheck($project->get('blocked_reason') === ($reason === 'Waiting' ? $reason : null), 'Empty reasons are SQL null');
                    mcpCheck(($project->get('blocked_until') instanceof DateTimeInterface) === ($date !== null && $date !== ''), 'Empty dates are SQL null');
                    if (!$date) mcpCheck(!(bool)$project->get('blocked_auto_reactivate'), 'No automatic reactivation without date');
                    $html = omoProjectsRenderBlockedInfo($project);
                    mcpCheck($html !== '' && !str_contains($html, '1970') && !str_contains($html, '0000'), 'Render blocked info without bogus date');
                    if (!$reason) mcpCheck(str_contains($html, omoProjectsT('projects.blocked.display_no_reason')), 'Missing reason placeholder');
                    if (!$date) {
                        mcpCheck(str_contains($html, omoProjectsT('projects.blocked.display_manual')), 'Missing date placeholder');
                        mcpCheck(!omoProjectsIsBlockedOverdue($project), 'Undated blocking is never overdue');
                    }
                }
            }
        }
    }
    mcpCheck(!Project::hasValidBlockedDetails($oid, null, '2030-02-30'), 'Invalid optional date rejected');
    foreach ([null, ''] as $empty) {
        $args = omoApiProjectValidate('omo_update_project', ['project_id' => (int)$project->getId(),
            'expected_version' => str_repeat('a', 64), 'request_key' => 'blocking-settings-api',
            'status' => 'blocked', 'blocked_reason' => $empty, 'blocked_until' => $empty]);
        mcpCheck(array_key_exists('blocked_until', $args), 'API accepts optional empty and null details');
    }
    $project->set('status', Project::STATUS_READY);
    mcpCheck(!empty($project->save()['status']), 'Leave blocked status');
    mcpCheck($project->get('blocked_reason') === null && $project->get('blocked_until') === null, 'Leaving blocked clears details');
    $link->load($link->getId(), true);
    $parameters = $link->getParametersArray();
    mcpCheck(isset($parameters['display']) && $parameters['blocking'] === ['requireReason' => false, 'requireReviewDate' => false], 'Existing display config preserved');
    unset($base['save_blocking']);
    mcpCheck(!empty(omoProjectsParamsStoreConfig($items['org'], $base)['status']), 'Older settings form can save');
    mcpCheck(Project::getOrganizationBlockingConfig($oid) === ['requireReason' => false, 'requireReviewDate' => false], 'Older forms preserve blocking settings');
    echo "Project blocking settings tests passed.\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $before;
}
