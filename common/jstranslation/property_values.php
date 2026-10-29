<?php
require_once dirname(__DIR__, 2) . '/omo/api/bootstrap.php';
$sourceLang = [
    'title' => ['text' => 'Valeur', 'context' => 'Title of an organizational value'],
    'principle' => ['text' => 'Principe', 'context' => 'HTML description of an organizational value'],
    'add' => ['text' => 'Ajouter une valeur', 'context' => 'Add a value to a holon property'],
    'remove' => ['text' => 'Retirer', 'context' => 'Remove a value from a list'],
    'up' => ['text' => 'Monter', 'context' => 'Move a value up in a list'],
    'down' => ['text' => 'Descendre', 'context' => 'Move a value down in a list'],
];
$bundle = omoLoadTranslationBundle('common_property_values', $sourceLang);
$payload = [];
foreach ($sourceLang as $key => $definition) $payload[$key] = t($key, [], $bundle, $sourceLang);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
