<?php
require_once __DIR__ . '/shared.php';
return static function (\dbObject\StatIndicator $indicator, $organization = null, int $currentHolonId = 0,
    string $query = '', int $missionId = 0, bool $includeChart = true): array {
    $preview = [
        'title' => $indicator->get('name'),
        'fields' => [
            'context' => omoStatsContextLabel($indicator),
            'responsible' => omoStatsResponsibleAssignmentLabel($indicator),
            'frequency' => omoStatsMeasurementFrequencyLabel($indicator->getEffectiveMeasurementFrequency()),
        ],
        'sections' => [omoSearchPreviewSection('summary', $indicator->get('description'))],
    ];
    if ($includeChart) {
        $values = omoStatsCollectionItems($indicator->getMeasurements(), \dbObject\StatIndicatorValue::class);
        $references = omoStatsCollectionItems($indicator->getReferencePoints(), \dbObject\StatIndicatorReferencePoint::class);
        $preview['chart'] = omoStatsRenderChart($indicator, $values, $references, 'large');
    }
    return $preview;
};
