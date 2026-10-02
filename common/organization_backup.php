<?php

use dbObject\Organization;
use dbObject\OrganizationBackup;
use dbObject\OrganizationExport;

require_once __DIR__ . '/email_layout.php';
require_once __DIR__ . '/translation_bundles.php';

/** Send one backup; callers serialize this operation with the shared maintenance lock. */
function omoSendOrganizationBackup(OrganizationBackup $backup): void
{
    $sourceLang = [
        'subject' => ['text' => 'Sauvegarde OMO - {name}', 'context' => 'Organization backup email subject.'],
        'body' => ['text' => 'Le fichier JSON joint contient une sauvegarde de toute la structure de votre organisation et de ses modules, ainsi que les références aux fichiers externes. Le contenu de ces fichiers et la configuration des serveurs ne sont pas inclus. Vous devrez reconfigurer les serveurs après la restauration.', 'context' => 'Organization backup email body.'],
    ];
    $now = new DateTimeImmutable();
    $backup->recordAttempt($now);
    $organization = new Organization();
    $organizationId = (int)$backup->get('IDorganization');
    if (!$organization->load($organizationId)) { throw new RuntimeException('Organization unavailable.'); }
    $recipients = $backup->getRecipients();
    if ($recipients === []) { throw new RuntimeException('No valid backup recipient.'); }
    $json = json_encode(OrganizationExport::buildBackup($organization), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $name = trim((string)$organization->get('name')) ?: 'OMO';
    $lang = loadTranslationBundle('omo_organization_backup_mail', 'fr', $sourceLang);
    $subject = t('subject', ['name' => $name], $lang, $sourceLang);
    $body = t('body', [], $lang, $sourceLang);
    $html = commonRenderMailLayout(['brand_name' => $name, 'heading' => $subject, 'body_html' => commonMailTextToHtml($body)]);
    $from = trim((string)($GLOBALS['mailUser'] ?? ''));
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $host = (string)parse_url(appBuildAbsoluteUrl('/'), PHP_URL_HOST);
        if ($host === 'localhost') { $host = 'localhost.localdomain'; }
        $from = 'noreply@' . ($host !== '' ? $host : 'localhost.localdomain');
    }
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) { throw new RuntimeException('Backup sender unavailable.'); }
    if (!myHTMLMail([$from, $name], $recipients, $subject, $html, null, null, [[
        'name' => 'omo2-backup-' . $organizationId . '-' . $now->format('Ymd-His') . '.json',
        'type' => 'application/json', 'content' => $json,
    ]], $subject . "\n\n" . $body)) { throw new RuntimeException('Backup email delivery failed.'); }
    $backup->recordAttempt(new DateTimeImmutable(), true);
}

/** Called only inside the shared maintenance lock. */
function omoProcessOrganizationBackups(string $source): int
{
    $serverCron = in_array($source, ['server_cron_http', 'server_cron_cli'], true);
    $userId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
    $sent = 0;
    $failed = [];
    foreach (OrganizationBackup::loadEnabledForCron($userId, $serverCron) as $backup) {
        $organizationId = (int)$backup->get('IDorganization');
        try {
            if (!$backup->isDue(new DateTimeImmutable())) { continue; }
            omoSendOrganizationBackup($backup);
            $sent++;
        } catch (Throwable $exception) {
            $failed[] = $organizationId;
            error_log('OMO organization backup failed for ' . $organizationId . ': ' . $exception->getMessage());
        }
    }
    if ($failed !== []) { throw new RuntimeException('Backup failed for organizations: ' . implode(', ', $failed)); }
    return $sent;
}
