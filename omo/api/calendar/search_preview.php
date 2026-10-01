<?php
return static function (\dbObject\Event $event): array {
    $statuses = \dbObject\Event::getStatusCatalog();
    $holon = new \dbObject\Holon();
    $context = (int)$event->get('IDholon') > 0 && $holon->load((int)$event->get('IDholon')) ? $holon->getFullDisplayName() : '';
    return [
        'title' => $event->get('title'),
        'fields' => [
            'context' => $context,
            'start' => $event->get('start_at'), 'end' => $event->get('end_at'),
            'status' => $statuses[$event->get('status')]['label'] ?? $event->get('status'),
            'format' => $event->getLocationModeLabel(),
            'location' => $event->get('locationaddress'),
        ],
        'sections' => [omoSearchPreviewSection('summary', $event->get('description'))],
    ];
};
