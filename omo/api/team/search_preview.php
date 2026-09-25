<?php
return static function (\dbObject\User $user, \dbObject\Organization $organization): array {
    $organizationId = (int)$organization->getId();
    $skills = [];
    foreach ($user->getVisibleCompetenceRows($organizationId, commonGetCurrentUserId()) as $skill) {
        $skills[] = trim((string)($skill['name'] ?? '') . ' ' . (string)($skill['description'] ?? ''));
    }
    return [
        'title' => $user->getScopedDisplayName($organizationId),
        'fields' => ['context' => $organization->get('name'), 'email' => $user->getScopedEmail($organizationId)],
        'sections' => [
            omoSearchPreviewSection('presentation', $user->getScopedPresentation($organizationId)),
            omoSearchPreviewSection('skills', implode("\n", $skills)),
        ],
    ];
};
