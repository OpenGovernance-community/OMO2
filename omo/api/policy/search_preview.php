<?php
return static function (\dbObject\Rule $rule): array {
    return [
        'title' => $rule->get('title'),
        'fields' => [
            'context' => $rule->getHolon()?->getFullDisplayName(),
            'authority' => $rule->getAuthority()?->get('label'),
            'updated' => $rule->get('updated_at'),
        ],
        'sections' => [
            omoSearchPreviewSection('text', $rule->get('description')),
            omoSearchPreviewSection('intention', $rule->get('intention')),
        ],
    ];
};
