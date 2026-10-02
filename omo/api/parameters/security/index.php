<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

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
?>
<div data-omo-security data-organization-id="<?= $organizationId ?>" data-csrf="<?= omoSecurityEscape($_SESSION['omo_security_csrf']) ?>">
    <div class="generic-drawer-content">
    <div class="generic-form-section__copy">
        <h2 class="generic-card-title generic-card-title--medium"><?= omoSecurityEscape(omoSecurityT('backup_title')) ?></h2>
        <p class="generic-description"><?= omoSecurityEscape(omoSecurityT('description')) ?></p>
    </div>
    <?php $backup->display('adminEdit.php', [
        'buttons' => false,
        'bindFormScripts' => false,
        'compact' => true,
        'action' => '/omo/api/parameters/security/save.php',
        'sections' => [
            ['title' => omoSecurityT('automation'), 'fields' => ['enabled'], 'description' => omoSecurityT('schedule')],
            ['title' => omoSecurityT('delivery'), 'fields' => ['email', 'frequency'], 'layout' => 'pair'],
        ],
    ]); ?>
    <section class="generic-soft-panel generic-soft-panel--tinted generic-soft-panel--stack" aria-labelledby="security-backup-tracking">
        <h3 class="generic-card-title generic-card-title--small" id="security-backup-tracking"><?= omoSecurityEscape(omoSecurityT('tracking')) ?></h3>
        <p class="generic-help-text" data-omo-security-last><?= omoSecurityEscape($last instanceof DateTimeInterface ? omoSecurityT('last', ['date' => $last->format('d.m.Y H:i')]) : omoSecurityT('never')) ?></p>
        <p class="generic-help-text" data-omo-security-next <?= $backup->get('enabled') ? '' : 'hidden' ?>><?= omoSecurityEscape($next ? omoSecurityT('next', ['date' => $next->format('d.m.Y H:i')]) : omoSecurityT('pending')) ?></p>
    </section>
    <details class="generic-accordion">
        <summary><?= omoSecurityEscape(omoSecurityT('scope')) ?></summary>
        <div class="generic-accordion__content"><p class="generic-help-text"><?= omoSecurityEscape(omoSecurityT('notice')) ?></p></div>
    </details>
    <p class="generic-help-text"><?= omoSecurityEscape(omoSecurityT('manual_help')) ?></p>
    <p class="generic-feedback generic-feedback--collapse-empty" role="status" aria-live="polite" data-omo-security-feedback></p>
    </div>
    <div class="generic-drawer-footer generic-drawer-footer--sticky">
        <button type="submit" form="formulaire-edit" name="backup_now" value="1" class="generic-action-button generic-action-button--secondary" data-omo-security-backup-now><?= omoSecurityEscape(omoSecurityT('backup_now')) ?></button>
        <button type="submit" form="formulaire-edit" class="generic-action-button generic-action-button--main" data-omo-security-save><?= omoSecurityEscape(omoSecurityT('save')) ?></button>
    </div>
</div>
<?= commonPageScriptTags('/omo/api/parameters/security/index.js', ['error' => omoSecurityT('error'), 'sending' => omoSecurityT('sending')]) ?>
