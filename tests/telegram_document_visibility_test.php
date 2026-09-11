<?php
declare(strict_types=1);

function assertTelegramDocumentVisibility(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$documentSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/document.class.php');
$telegramSource = (string)file_get_contents(dirname(__DIR__) . '/telegram/memo_hook.php');
$migrationSource = (string)file_get_contents(dirname(__DIR__) . '/sql/2026-09-11-01-document-edit-visibility.sql');

assertTelegramDocumentVisibility(
    str_contains($documentSource, 'function ensureOrganizationVisibilityRules(): array')
        && str_contains($documentSource, 'self::getEditVisibilityObjectType()')
        && str_contains($documentSource, 'self::getDefaultEditVisibilityTypeForOrganization($organizationId)')
        && str_contains($documentSource, '$this->saveEditVisibilityRule($editVisibilityType)'),
    'Documents assigned outside the editor must receive their missing edit visibility rule.'
);
assertTelegramDocumentVisibility(
    str_contains($documentSource, 'return $this->ensureOrganizationVisibilityRules();')
        && str_contains($telegramSource, '$doc->ensureOrganizationVisibilityRules();'),
    'Both Telegram classification flows must initialize document visibility rules.'
);
assertTelegramDocumentVisibility(
    str_contains($migrationSource, "'document_edit'")
        && str_contains($migrationSource, "WHEN `holon`.`IDtypeholon` = 1 THEN 'role'")
        && str_contains($migrationSource, 'THEN `holon`.`id`'),
    'The migration must restore role-targeted edit rules for existing documents.'
);

echo "telegram_document_visibility_test: OK\n";
