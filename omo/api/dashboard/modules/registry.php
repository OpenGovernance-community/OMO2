<?php

if (!function_exists('omoDashboardGetModuleDefinitions')) {
    function omoDashboardGetModuleDefinitions(): array
    {
        $basePath = __DIR__;
        $dataPath = $basePath . '/data';
        return array(
            'video' => array('id' => 'video', 'object' => 'video', 'variant' => 'embedded', 'standalone' => true, 'loader' => $dataPath . '/video.php', 'template' => $basePath . '/video.php'),
            'rules' => array('id' => 'rules', 'object' => 'rule', 'variant' => 'attention', 'app' => 'policy', 'route' => 'policy', 'loader' => $dataPath . '/rules.php', 'template' => $basePath . '/rules.php'),
            'projects' => array('id' => 'projects', 'object' => 'project', 'variant' => 'priority', 'app' => 'projects', 'route' => 'projects', 'loader' => $dataPath . '/projects.php', 'template' => $basePath . '/projects.php'),
            'team' => array('id' => 'team', 'object' => 'user', 'variant' => 'celebrations', 'app' => 'team', 'route' => 'team', 'loader' => $dataPath . '/team.php', 'template' => $basePath . '/team.php'),
            'documents' => array('id' => 'documents', 'object' => 'document', 'variant' => 'recent', 'app' => 'documents', 'route' => 'documents', 'loader' => $dataPath . '/documents.php', 'template' => $basePath . '/documents.php'),
            'event' => array('id' => 'event', 'object' => 'event', 'variant' => 'upcoming', 'app' => 'calendar', 'route' => 'calendar', 'loader' => $dataPath . '/event.php', 'template' => $basePath . '/event.php'),
            'structure' => array('id' => 'structure', 'object' => 'history', 'variant' => 'recent', 'app' => 'structure', 'route' => 'structure', 'loader' => $dataPath . '/structure.php', 'template' => $basePath . '/structure.php'),
            'stats' => array('id' => 'stats', 'object' => 'indicator', 'variant' => 'overdue', 'app' => 'stats', 'route' => 'stats', 'loader' => $dataPath . '/stats.php', 'template' => $basePath . '/stats.php'),
            'checklist' => array('id' => 'checklist', 'object' => 'checklist', 'variant' => 'assigned', 'app' => 'checklist', 'route' => 'checklist', 'loader' => $dataPath . '/checklist.php', 'template' => $basePath . '/checklist.php'),
            'activities' => array('id' => 'activities', 'object' => 'control_activity', 'variant' => 'upcoming', 'app' => 'activities', 'route' => 'activities', 'loader' => $dataPath . '/activities.php', 'template' => $basePath . '/activities.php'),
        );
    }
}

if (!function_exists('omoDashboardUserIsAssociatedWithHolon')) {
    function omoDashboardUserIsAssociatedWithHolon($userId, $organizationId, \dbObject\Holon $holon): bool
    {
        static $cache = array();

        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        $holonId = (int)$holon->getId();
        if ($userId <= 0 || $organizationId <= 0 || $holonId <= 0) {
            return false;
        }

        $cacheKey = $organizationId . ':' . $userId . ':' . $holonId;
        if (!array_key_exists($cacheKey, $cache)) {
            $cache[$cacheKey] = in_array(
                $userId,
                $holon->getAssociatedMemberUserIds(array(
                    'organizationId' => $organizationId,
                    'skipPermissionFilter' => true,
                )),
                true
            );
        }

        return $cache[$cacheKey];
    }
}

if (!function_exists('omoDashboardMatchesResponsibleAudience')) {
    function omoDashboardMatchesResponsibleAudience($audience, $responsibleUserId, ?\dbObject\Holon $holon, $userId, $organizationId): bool
    {
        $audience = trim(mb_strtolower((string)$audience, 'UTF-8'));
        if ($audience === 'all') {
            return true;
        }

        $userId = (int)$userId;
        $responsibleUserId = (int)$responsibleUserId;
        $isAssociated = $holon instanceof \dbObject\Holon
            && omoDashboardUserIsAssociatedWithHolon($userId, $organizationId, $holon);

        if ($audience === 'mine') {
            return $userId > 0 && ($responsibleUserId === $userId || ($responsibleUserId <= 0 && $isAssociated));
        }

        return $audience === 'roles' && $isAssociated;
    }
}
