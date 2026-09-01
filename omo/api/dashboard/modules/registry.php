<?php

if (!function_exists('omoDashboardGetModuleDefinitions')) {
    function omoDashboardGetModuleDefinitions(): array
    {
        $basePath = __DIR__;
        $dataPath = $basePath . '/data';
        return array(
            'rules' => array('id' => 'rules', 'object' => 'rule', 'variant' => 'attention', 'app' => 'policy', 'route' => 'policy', 'loader' => $dataPath . '/rules.php', 'template' => $basePath . '/rules.php'),
            'projects' => array('id' => 'projects', 'object' => 'project', 'variant' => 'priority', 'app' => 'projects', 'route' => 'projects', 'loader' => $dataPath . '/projects.php', 'template' => $basePath . '/projects.php'),
            'team' => array('id' => 'team', 'object' => 'user', 'variant' => 'celebrations', 'app' => 'team', 'route' => 'team', 'loader' => $dataPath . '/team.php', 'template' => $basePath . '/team.php'),
            'documents' => array('id' => 'documents', 'object' => 'document', 'variant' => 'recent', 'app' => 'documents', 'route' => 'documents', 'loader' => $dataPath . '/documents.php', 'template' => $basePath . '/documents.php'),
            'event' => array('id' => 'event', 'object' => 'event', 'variant' => 'upcoming', 'app' => 'calendar', 'route' => 'calendar', 'loader' => $dataPath . '/event.php', 'template' => $basePath . '/event.php'),
            'structure' => array('id' => 'structure', 'object' => 'history', 'variant' => 'recent', 'app' => 'structure', 'route' => 'structure', 'loader' => $dataPath . '/structure.php', 'template' => $basePath . '/structure.php'),
            'stats' => array('id' => 'stats', 'object' => 'indicator', 'variant' => 'overdue', 'app' => 'stats', 'route' => 'stats', 'loader' => $dataPath . '/stats.php', 'template' => $basePath . '/stats.php'),
            'activities' => array('id' => 'activities', 'object' => 'control_activity', 'variant' => 'upcoming', 'app' => 'activities', 'route' => 'activities', 'loader' => $dataPath . '/activities.php', 'template' => $basePath . '/activities.php'),
        );
    }
}
