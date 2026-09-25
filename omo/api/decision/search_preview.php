<?php
return static function (\dbObject\DecisionProcess $decision): array {
    $statuses = \dbObject\DecisionProcess::getStatusCatalog();
    return [
        'title' => $decision->get('title'),
        'fields' => [
            'context' => $decision->getHolonObject()?->getFullDisplayName(),
            'status' => $statuses[$decision->get('status')]['label'] ?? $decision->get('status'),
            'created' => $decision->get('created_at'),
        ],
        'sections' => [omoSearchPreviewSection('summary', $decision->get('description'))],
    ];
};
