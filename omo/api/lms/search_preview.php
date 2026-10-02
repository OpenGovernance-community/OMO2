<?php
return static function (\dbObject\Parcours $parcours, $organization = null, int $currentHolonId = 0,
    string $query = '', int $missionId = 0): array {
    $object = $parcours;
    if ($missionId > 0) {
        $link = new \dbObject\ParcoursMission();
        $mission = new \dbObject\Mission();
        if (!$link->load([['IDparcours', (int)$parcours->getId()], ['IDmission', $missionId]]) || !$mission->load($missionId)) {
            throw new RuntimeException('Preview unavailable');
        }
        $object = $mission;
    }
    return [
        'title' => $object->get('title'),
        'fields' => ['tutorial' => $parcours->get('title')],
        'sections' => [
            omoSearchPreviewSection('summary', $object->get($missionId > 0 ? 'resume' : 'description')),
            omoSearchPreviewSection('content', $missionId > 0 ? $object->get('html') : ''),
        ],
    ];
};
