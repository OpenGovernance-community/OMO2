<?php
require_once dirname(__DIR__, 2) . '/omo/api/bootstrap.php';

$sourceLang = [
    'toAuthority' => ['text' => 'Les textes de cette liste seront convertis en nouveaux objets Autorité. Souhaitez-vous continuer ?', 'context' => 'Confirmation before converting a text list to authority objects'],
    'toText' => ['text' => 'Les autorités de cette liste seront remplacées par leurs noms. Les objets Autorité et leurs instances de modèle seront supprimés avec leurs descriptions. Leurs règles seront conservées dans leur holon et détachées des autorités. Les sous-autorités seront conservées et détachées. Souhaitez-vous continuer ?', 'context' => 'Confirmation of authority deletion when converting a list to text'],
    'scope' => ['text' => 'La conversion sera appliquée à l’enregistrement, dans tous les holons qui utilisent cette définition de liste.', 'context' => 'Scope and timing of the list conversion'],
    'pending' => ['text' => 'Conversion confirmee pour le prochain enregistrement. Les valeurs ci-dessous sont conservees jusque-la. Revenez au type initial pour annuler.', 'context' => 'Pending list conversion; values are read only until saving or reverting the type'],
];
$bundle = omoLoadTranslationBundle('common_property_list_conversion', $sourceLang);
$payload = [];
foreach ($sourceLang as $key => $definition) $payload[$key] = t($key, [], $bundle, $sourceLang);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
