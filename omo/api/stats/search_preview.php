<?php
require_once __DIR__ . '/shared.php';
return static function (\dbObject\StatIndicator $indicator): array {
    $values = omoStatsCollectionItems($indicator->getMeasurements(), \dbObject\StatIndicatorValue::class);
    $references = omoStatsCollectionItems($indicator->getReferencePoints(), \dbObject\StatIndicatorReferencePoint::class);
    return [
        'title' => $indicator->get('name'),
        'fields' => [
            'context' => omoStatsContextLabel($indicator),
            'responsible' => omoStatsResponsibleAssignmentLabel($indicator),
            'frequency' => omoStatsMeasurementFrequencyLabel($indicator->getEffectiveMeasurementFrequency()),
        ],
        'sections' => [omoSearchPreviewSection('summary', $indicator->get('description'))],
        'chart' => omoStatsRenderChart($indicator, $values, $references, 'large'),
    ];
};
