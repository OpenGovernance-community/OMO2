<?php
return static function (\dbObject\Holon $holon): array {
    $sections = [];
    foreach ($holon->getPropertiesValue() as $property) {
        if (!\dbObject\Property::isTypeEnabled($property->get('type'), $holon->getPropertyTypeLexicon())) continue;
        $value = $property->get('value');
        if (omoSearchPreviewText($value) !== '') {
            $sections[] = ['title' => (string)$property->get('name'), 'text' => $value];
        }
    }
    return ['title' => $holon->getDisplayName(), 'fields' => ['context' => $holon->getFullDisplayName()], 'sections' => $sections];
};
