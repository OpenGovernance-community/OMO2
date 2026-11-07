<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__, 4) . '/common/mcp/protocol.php';

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = new \dbObject\Organization();
if (!omoSecurityCanManage($organizationId)) {
    http_response_code(403);
    echo '<div class="generic-drawer-content"><p class="generic-soft-panel">' . omoSecurityEscape(omoSecurityT('forbidden')) . '</p></div>';
    return;
}
if (!$organization->load($organizationId)) {
    http_response_code(404);
    echo '<div class="generic-drawer-content"><p class="generic-soft-panel">' . omoSecurityEscape(omoSecurityT('organization')) . '</p></div>';
    return;
}
$backup = \dbObject\OrganizationBackup::forOrganization($organizationId);
$_SESSION['omo_security_csrf'] ??= bin2hex(random_bytes(32));
$last = $backup->get('last_sent_at');
$next = $backup->nextDueAt();
$integrationBase = ''; $mcpEndpoint = '';
try { $mcpEndpoint = omoMcpPublicUrl(); $integrationBase = omoMcpIssuer(); }
catch (RuntimeException $error) { /* Policies remain editable before external services are configured. */ }
$integrations = [
    'api' => ['id' => 'omoOrganizationApiEnabled', 'enabled' => $organization->isApiEnabled(),
        'endpoint' => $integrationBase !== '' ? $integrationBase . '/api/v1' : '', 'anchor' => 'overview'],
    'mcp' => ['id' => 'omoOrganizationMcpEnabled', 'enabled' => $organization->isMcpEnabled(),
        'endpoint' => $mcpEndpoint, 'anchor' => 'mcp'],
];
?>
<div data-omo-security data-organization-id="<?= $organizationId ?>" data-csrf="<?= omoSecurityEscape($_SESSION['omo_security_csrf']) ?>">
    <div class="generic-drawer-content">
    <section class="generic-section generic-stack generic-stack--roomy" data-omo-mcp-settings>
        <div class="generic-heading-with-help">
            <h2 class="generic-card-title generic-card-title--medium"><?= omoSecurityEscape(omoSecurityT('mcp_title')) ?></h2>
            <?= omoSecurityHelp('mcp_title', ['mcp_description']) ?>
        </div>
        <div class="generic-form-field">
            <div class="generic-heading-with-help">
                <label class="generic-form-label"><input type="checkbox" name="ai_blocked" form="formulaire-edit" value="1"<?= $organization->isAiBlocked() ? ' checked' : '' ?> aria-describedby="omoOrganizationAiBlockedHelp"> <?= omoSecurityEscape(omoSecurityT('ai_blocked_label')) ?></label>
                <?= omoSecurityHelp('ai_blocked_label', ['ai_blocked_help'], 'omoOrganizationAiBlockedHelp') ?>
            </div>
        </div>
        <?php foreach ($integrations as $key => $integration): ?>
        <div class="generic-form-field">
            <div class="generic-heading-with-help">
                <label class="generic-form-label" for="<?= $integration['id'] ?>"><?= omoSecurityEscape(omoSecurityT($key . '_label')) ?></label>
                <?= omoSecurityHelp($key . '_label', [$key . '_help']) ?>
            </div>
            <select id="<?= $integration['id'] ?>" name="<?= $key ?>_enabled" form="formulaire-edit" class="generic-form-control">
                <option value="0"<?= !$integration['enabled'] ? ' selected' : '' ?>><?= omoSecurityEscape(omoSecurityT('mcp_blocked')) ?></option>
                <option value="1"<?= $integration['enabled'] ? ' selected' : '' ?>><?= omoSecurityEscape(omoSecurityT('mcp_allowed')) ?></option>
            </select>
            <div class="generic-stack generic-stack--compact" data-omo-integration-info="<?= $key ?>"<?= !$integration['enabled'] ? ' hidden' : '' ?>>
                <?php if ($integration['endpoint'] !== ''): ?>
                    <p class="generic-meta"><a href="<?= omoSecurityEscape($integrationBase . '/developer/#' . $integration['anchor']) ?>" target="_blank" rel="noopener noreferrer"><?= omoSecurityEscape(omoSecurityT($key . '_documentation')) ?></a></p>
                    <p class="generic-meta"><?= omoSecurityEscape(omoSecurityT($key . '_endpoint')) ?> : <code><?= omoSecurityEscape($integration['endpoint']) ?></code></p>
                <?php else: ?>
                    <p class="generic-meta"><?= omoSecurityEscape(omoSecurityT('endpoint_unconfigured')) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </section>
    <div class="generic-heading-with-help">
        <h2 class="generic-card-title generic-card-title--medium"><?= omoSecurityEscape(omoSecurityT('backup_title')) ?></h2>
        <?= omoSecurityHelp('backup_title', ['description', 'schedule', 'notice']) ?>
    </div>
    <?php $backup->display('adminEdit.php', [
        'buttons' => false,
        'bindFormScripts' => false,
        'action' => '/omo/api/parameters/security/save.php',
        'sections' => [
            ['title' => omoSecurityT('automation'), 'fields' => ['enabled']],
            ['title' => omoSecurityT('delivery'), 'fields' => ['email', 'frequency'], 'layout' => 'pair'],
        ],
    ]); ?>
    <section class="generic-soft-panel generic-soft-panel--tinted generic-soft-panel--stack" aria-labelledby="security-backup-tracking">
        <h3 class="generic-card-title generic-card-title--small" id="security-backup-tracking"><?= omoSecurityEscape(omoSecurityT('tracking')) ?></h3>
        <p class="generic-help-text" data-omo-security-last><?= omoSecurityEscape($last instanceof DateTimeInterface ? omoSecurityT('last', ['date' => $last->format('d.m.Y H:i')]) : omoSecurityT('never')) ?></p>
        <p class="generic-help-text" data-omo-security-next <?= $backup->get('enabled') ? '' : 'hidden' ?>><?= omoSecurityEscape($next ? omoSecurityT('next', ['date' => $next->format('d.m.Y H:i')]) : omoSecurityT('pending')) ?></p>
    </section>
    <p class="generic-feedback generic-feedback--collapse-empty" role="status" aria-live="polite" data-omo-security-feedback></p>
    </div>
    <div class="generic-drawer-footer generic-drawer-footer--sticky">
        <div class="generic-action-row generic-action-row--nowrap">
            <button type="submit" form="formulaire-edit" name="backup_now" value="1" class="generic-action-button generic-action-button--secondary" data-omo-security-backup-now><?= omoSecurityEscape(omoSecurityT('backup_now')) ?></button>
            <?= omoSecurityHelp('backup_now', ['manual_help']) ?>
        </div>
        <button type="submit" form="formulaire-edit" class="generic-action-button generic-action-button--main" data-omo-security-save><?= omoSecurityEscape(omoSecurityT('save')) ?></button>
    </div>
</div>
<?= commonPageScriptTags('/omo/api/parameters/security/index.js', ['error' => omoSecurityT('error'), 'sending' => omoSecurityT('sending')]) ?>
