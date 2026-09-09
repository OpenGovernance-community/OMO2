<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\History;
use dbObject\Holon;
use dbObject\Project;

function omoProjectsHistoryFormatDate($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    try {
        return (new DateTimeImmutable($value, new DateTimeZone('UTC')))->format('d.m.Y H:i') . ' UTC';
    } catch (Throwable $exception) {
        return $value;
    }
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$projectId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($organizationId <= 0 || $projectId <= 0) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

$context = omoProjectsResolveContext($organizationId, isset($_GET['cid']) ? (int)$_GET['cid'] : 0);
if (empty($context['status'])) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape((string)$context['message']) . '</div>';
    exit;
}

$project = new Project();
if (!$project->load($projectId) || (int)$project->get('IDorganization') !== $organizationId || !omoProjectsCanViewProject($project, $context)) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

$projectHolon = $project->getHolon();
$rootHolon = $context['rootHolon'] ?? null;
if (
    $projectHolon instanceof Holon
    && (
        !($rootHolon instanceof Holon)
        || !$projectHolon->isDescendantOf((int)$rootHolon->getId(), true)
        || !$projectHolon->canViewDetail()
    )
) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

$historyPage = History::fetchProjectFeedPage($organizationId, $projectId, 100);
$historyItems = is_array($historyPage['items'] ?? null) ? $historyPage['items'] : [];
?>
<div class="omo-project-history">
    <?php if (count($historyItems) === 0): ?>
        <p class="omo-project-detail__muted generic-description generic-description--small"><?= omoApiEscape(omoProjectsT('projects.history.empty')) ?></p>
    <?php else: ?>
        <div class="omo-project-history__list">
            <?php foreach ($historyItems as $historyItem): ?>
                <?php
                $parameters = is_array($historyItem['parameters'] ?? null) ? $historyItem['parameters'] : [];
                $changes = is_array($parameters['changes'] ?? null) ? $parameters['changes'] : [];
                $payload = count($changes) > 0
                    ? base64_encode((string)json_encode(['changes' => $changes], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                    : '';
                $authorLabel = trim((string)($historyItem['authorDisplayName'] ?? ''));
                $contentHtml = trim((string)($historyItem['contentHtml'] ?? ''));
                ?>
                <article class="omo-project-history__item generic-soft-panel">
                    <div class="omo-project-history__meta generic-meta generic-meta--compact">
                        <time><?= omoApiEscape(omoProjectsHistoryFormatDate($historyItem['datecreation'] ?? '')) ?></time>
                        <span><?= omoApiEscape($authorLabel !== '' ? $authorLabel : omoProjectsT('projects.history.system')) ?></span>
                        <strong><?= omoApiEscape((string)($historyItem['actionLabel'] ?? '')) ?></strong>
                    </div>
                    <p class="omo-project-history__content"><?= $contentHtml !== '' ? nl2br($contentHtml) : nl2br(omoApiEscape((string)($historyItem['contentDisplay'] ?? ''))) ?></p>
                    <?php if ($payload !== ''): ?>
                        <details class="omo-project-history__details omo-change-details" data-omo-change-details-payload="<?= omoApiEscape($payload) ?>">
                            <summary><?= omoApiEscape(omoProjectsT('projects.history.detail')) ?></summary>
                            <div class="omo-project-history__changes" data-omo-change-details-container></div>
                        </details>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
