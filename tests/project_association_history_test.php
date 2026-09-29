<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\DbObject;
use dbObject\History;
use dbObject\Organization;
use dbObject\Project;
use dbObject\User;

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
register_shutdown_function(static function () use ($pdo): void {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
});

try {
    $nonce = bin2hex(random_bytes(5));
    $organization = new Organization();
    $organization->set('name', 'History test ' . $nonce);
    $organization->set('shortname', 'history-' . $nonce);
    $organizationResult = $organization->save();
    if (!is_array($organizationResult) || empty($organizationResult['status'])) {
        throw new RuntimeException('Organization fixture failed.');
    }

    $author = new User();
    $author->set('firstname', 'Auteur');
    $author->set('email', 'history-' . $nonce . '@example.invalid');
    $author->set('active', 1);
    $authorResult = $author->save();
    if (!is_array($authorResult) || empty($authorResult['status'])) {
        throw new RuntimeException('Author fixture failed.');
    }

    $project = new Project();
    $project->set('IDorganization', (int)$organization->getId());
    $project->set('title', 'Projet historique');
    $project->set('active', 1);
    $projectResult = $project->save();
    if (!is_array($projectResult) || empty($projectResult['status'])) {
        throw new RuntimeException('Project fixture failed: ' . json_encode($projectResult));
    }

    $authorId = (int)$author->getId();
    $project->recordAssociationHistory('document', 11, 'Compte rendu', 'added', $authorId);
    $project->recordAssociationHistory('document', 11, 'Compte rendu', 'removed', $authorId);
    $project->recordAssociationHistory('event', 12, 'Réunion', 'deleted', $authorId);
    $project->recordAssociationHistory('indicator', 13, 'Progression', 'added', $authorId);
    $project->recordAssociationHistory('recurring_task', 14, 'Contrôle mensuel', 'removed', $authorId);

    $page = History::fetchProjectFeedPage((int)$organization->getId(), (int)$project->getId(), 20);
    $items = is_array($page['items'] ?? null) ? $page['items'] : [];
    $expected = [
        'project_document_added' => 'Compte rendu',
        'project_document_removed' => 'Compte rendu',
        'project_event_deleted' => 'Réunion',
        'project_indicator_added' => 'Progression',
        'project_recurring_task_removed' => 'Contrôle mensuel',
    ];
    foreach ($items as $item) {
        $action = (string)($item['action'] ?? '');
        if (!isset($expected[$action])) {
            continue;
        }
        if (!str_contains((string)($item['contentDisplay'] ?? ''), $expected[$action])
            || (int)($item['targetId'] ?? 0) !== (int)$project->getId()
            || (int)($item['IDuser'] ?? 0) !== $authorId
            || (string)($item['actionLabel'] ?? '') === '') {
            throw new RuntimeException('Incomplete project association history: ' . $action);
        }
        unset($expected[$action]);
    }
    if ($expected !== []) {
        throw new RuntimeException('Missing project association history: ' . implode(', ', array_keys($expected)));
    }

    echo "project_association_history_test: OK\n";
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
