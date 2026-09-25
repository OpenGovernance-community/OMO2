<?php
return static function (\dbObject\Parcours $parcours): array {
    $missionId = (int)($_GET['mission_id'] ?? 0);
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
        'sections' => [omoSearchPreviewSection('summary', $object->get($missionId > 0 ? 'resume' : 'description'))],
    ];
};
