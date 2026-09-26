<?php
require_once dirname(__DIR__, 2) . '/omo/api/bootstrap.php';

$sourceLang = [
    'toAuthority' => ['text' => 'Les textes de cette liste vont etre convertis en nouveaux objets autorites. Est-ce ce que vous souhaitez ?', 'context' => 'Confirmation before converting a text list to authority objects'],
    'toText' => ['text' => 'Les autorites de cette liste seront remplacees par leurs noms. Les objets autorites et leurs instances de modele seront supprimes, avec leurs descriptions. Leurs regles seront conservees dans leur holon et detachees des autorites. Les sous-autorites seront conservees et detachees. Est-ce ce que vous souhaitez ?', 'context' => 'Confirmation of authority deletion when converting a list to text'],
    'scope' => ['text' => 'La conversion sera appliquee a l enregistrement, dans tous les holons qui utilisent cette definition de liste.', 'context' => 'Scope and timing of the list conversion'],
    'pending' => ['text' => 'Conversion confirmee pour le prochain enregistrement. Les valeurs ci-dessous sont conservees jusque-la. Revenez au type initial pour annuler.', 'context' => 'Pending list conversion; values are read only until saving or reverting the type'],
];
$bundle = omoLoadTranslationBundle('common_property_list_conversion', $sourceLang);
$payload = [];
foreach ($sourceLang as $key => $definition) $payload[$key] = t($key, [], $bundle, $sourceLang);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
