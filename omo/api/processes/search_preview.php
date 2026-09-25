<?php
require_once dirname(__DIR__) . '/checklist/shared.php';
return static function (\dbObject\Checklist $process): array {
    $root = $process->getTemplateRoot();
    $steps = [];
    foreach ($process->getItems(true) as $item) {
        $template = $item->getProjectTemplate();
        if ($template) {
            $steps[] = (count($steps) + 1) . '. ' . omoSearchPreviewText($template->get('title'));
        }
    }
    return [
        'title' => $root ? $root->get('title') : '',
        'fields' => [
            'context' => $process->getHolon()?->getFullDisplayName(),
            'status' => omoChecklistStatusLabel($process->get('status')),
            'responsible' => omoChecklistResponsibleAssignmentLabel($process),
            'trigger' => omoChecklistTriggerLabel(omoChecklistGetPrimaryTrigger($process)),
        ],
        'sections' => [
            omoSearchPreviewSection('summary', $root ? $root->get('description') : ''),
            omoSearchPreviewSection('steps', implode("\n", $steps)),
        ],
    ];
};
