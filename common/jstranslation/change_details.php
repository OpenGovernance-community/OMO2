<?php
require_once dirname(__DIR__, 2) . '/omo/api/bootstrap.php';

$sourceLang = [
    'notExisting' => ['text' => 'Cet élément n’existait pas.', 'context' => 'Neutral before panel for the creation of an object'],
    'deleted' => ['text' => 'Supprimé', 'context' => 'Single after panel for the deletion of an object'],
];
$bundle = omoLoadTranslationBundle('common_choice_change_details', $sourceLang);
$payload = [];
foreach ($sourceLang as $key => $definition) $payload[$key] = t($key, [], $bundle, $sourceLang);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
