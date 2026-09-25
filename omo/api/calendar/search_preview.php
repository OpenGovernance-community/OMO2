<?php
return static function (\dbObject\Event $event): array {
    $statuses = \dbObject\Event::getStatusCatalog();
    return [
        'title' => $event->get('title'),
        'fields' => [
            'start' => $event->get('start_at'), 'end' => $event->get('end_at'),
            'status' => $statuses[$event->get('status')]['label'] ?? $event->get('status'),
            'format' => $event->getLocationModeLabel(),
            'location' => $event->get('locationaddress'),
        ],
        'sections' => [omoSearchPreviewSection('summary', $event->get('description'))],
    ];
};
