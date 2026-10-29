<?php
return static function (\dbObject\Holon $holon, \dbObject\Organization $organization): array {
    $sections = [];
    foreach ($holon->getPropertiesValue() as $property) {
        if (!\dbObject\Property::isTypeEnabled($property->get('type'), $holon->getPropertyTypeLexicon())) continue;
        if ($property->get('listitemtype') === \dbObject\Property::LIST_ITEM_VALUE
            && \dbObject\PropertyFormat::isListFormat((int)$property->get('IDpropertyformat'))) {
            $sections[] = ['title' => (string)$property->get('name'),
                'items' => \dbObject\McpValues::propertySections($property, $organization)];
            continue;
        }
        $value = $property->get('value');
        if (omoSearchPreviewText($value) !== '') {
            $sections[] = ['title' => (string)$property->get('name'), 'text' => $value];
        }
    }
    return ['title' => $holon->getDisplayName(), 'fields' => ['context' => $holon->getFullDisplayName()], 'sections' => $sections];
};
