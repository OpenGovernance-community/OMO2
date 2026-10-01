<?php
return static function (\dbObject\User $user, \dbObject\Organization $organization): array {
    $organizationId = (int)$organization->getId();
    $skills = [];
    foreach ($user->getVisibleCompetenceRows($organizationId, commonGetCurrentUserId()) as $skill) {
        $skills[] = ['title' => $skill['name'] ?? '', 'text' => $skill['description'] ?? ''];
    }
    return [
        'title' => $user->getScopedDisplayName($organizationId),
        'fields' => ['context' => $organization->get('name'), 'email' => $user->getScopedEmail($organizationId), 'username' => $user->getScopedUsername($organizationId)],
        'sections' => [
            omoSearchPreviewSection('presentation', $user->getScopedPresentation($organizationId)),
        ],
        'collections' => [['title' => omoSearchPreviewT('skills'), 'items' => $skills]],
    ];
};
