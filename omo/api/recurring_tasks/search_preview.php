<?php
require_once dirname(__DIR__) . '/activities/shared.php';
return static function (\dbObject\ControlActivity $activity): array {
    $now = new DateTimeImmutable();
    return [
        'title' => $activity->get('title'),
        'fields' => [
            'context' => $activity->getHolon()?->getFullDisplayName(),
            'responsible' => omoActivityResponsibleAssignmentLabel($activity),
            'frequency' => omoActivityFrequencyLabel($activity->get('frequency')),
            'schedule' => omoActivityScheduleLabel($activity->get('frequency'), $activity->get('schedule')),
            'status' => omoActivityStateLabel($activity->getOccurrenceState($now), $now),
        ],
        'sections' => [omoSearchPreviewSection('summary', $activity->get('description'))],
    ];
};
