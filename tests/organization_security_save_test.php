<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{Organization, OrganizationBackup};

class FailingSecurityBackup extends OrganizationBackup
{
    public function save() { return ['status' => false]; }
}

$items = mcpFixtures();
try {
    $org = $items['org']; $oid = (int)$org->getId();
    $backup = OrganizationBackup::forOrganization($oid);
    $backup->set('last_sent_at', new DateTimeImmutable('2026-01-01 12:00:00'));
    mcpCheck(!empty($backup->save()['status']), 'Initial backup preferences saved');
    $items['backup'] = $backup;
    $org->setAiBlocked(true); $org->setApiEnabled(false); $org->setMcpEnabled(false);
    $failure = new FailingSecurityBackup(); $failure->load($backup->getId(), true);
    $rejected = false;
    try { $failure->saveSecuritySettings($org); } catch (RuntimeException $error) { $rejected = true; }
    $stored = new Organization(); $stored->load($oid, true);
    mcpCheck($rejected && !$stored->isAiBlocked() && $stored->isApiEnabled() && $stored->isMcpEnabled(), 'Failed backup save rolls back AI/API/MCP changes');
    $backup->set('email', 'security@example.invalid'); $backup->set('frequency', '2w');
    $backup->saveSecuritySettings($org);
    $stored->load($oid, true); $backup->load($backup->getId(), true);
    mcpCheck($stored->isAiBlocked() && !$stored->isApiEnabled() && !$stored->isMcpEnabled()
        && $backup->get('email') === 'security@example.invalid' && $backup->get('frequency') === '2w', 'Global save persists both objects together');
    mcpCheck($backup->get('last_sent_at')->format('Y-m-d H:i:s') === '2026-01-01 12:00:00', 'Saving security preferences preserves backup delivery timestamps');
    $rejected = false;
    try { $backup->saveSecuritySettings($items['other_org']); } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpCheck($rejected, 'Cannot mix organizations in a global save');
    echo "PASS atomic security save, rollback on storage failure, organization boundaries and preserved backup timestamps\n";
} finally { mcpCleanup($items); }
