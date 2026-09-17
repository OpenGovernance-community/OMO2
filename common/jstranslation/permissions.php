<?php
require_once dirname(__DIR__, 2) . '/omo/api/bootstrap.php';
require_once dirname(__DIR__) . '/permissions/translations.php';

$sourceLang = commonPermissionEditorSourceLang();
$lang = omoLoadTranslationBundle('common_permission_editor', $sourceLang);
$payload = [];
foreach ($sourceLang as $key => $definition) {
    $payload[$key] = t($key, [], $lang, $sourceLang);
}
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
