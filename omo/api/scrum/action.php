<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';
use dbObject\ScrumSprint;
use dbObject\Project;

header('Content-Type: application/json; charset=UTF-8');
try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !commonBrowserMutationIsAllowed($_SERVER, $_POST, $_SESSION)) { throw new RuntimeException('access'); }
    $context = omoScrumContext();
    if (!omoProjectsCanManageContext($context)) { throw new RuntimeException('access'); }
    $oid = (int)$context['organization']->getId();
    $id = (int)($_POST['id'] ?? 0); $action = (string)($_POST['action'] ?? '');
    $result = ScrumSprint::locked($oid, function () use ($context, $oid, $id, $action) {
        $sprint = $id > 0 ? omoScrumLoad($context, $id) : new ScrumSprint();
        if ($id > 0) { $sprint->sync(); }
        if ($id <= 0 && $action !== 'save') { throw new RuntimeException('missing'); }
        if ($action === 'save') {
            if ($id && !$sprint->editable()) { throw new RuntimeException('locked'); }
            $title = trim((string)($_POST['title'] ?? ''));
            $start = DateTimeImmutable::createFromFormat('!Y-m-d', (string)($_POST['start_date'] ?? ''), new DateTimeZone('Europe/Zurich'));
            $end = DateTimeImmutable::createFromFormat('!Y-m-d', (string)($_POST['end_date'] ?? ''), new DateTimeZone('Europe/Zurich'));
            if ($title === '' || mb_strlen($title) > 255 || !$start || !$end || $start->format('Y-m-d') !== $_POST['start_date']
                || $end->format('Y-m-d') !== $_POST['end_date'] || $end < $start || $end->format('Y-m-d') < ScrumSprint::now()->format('Y-m-d')) { throw new RuntimeException('dates'); }
            foreach (['title' => $title, 'start_date' => $start, 'end_date' => $end, 'objective' => trim((string)($_POST['objective'] ?? ''))] as $key => $value) { $sprint->set($key, $value); }
            if (!$id) {
                $sprint->set('IDorganization', $oid); $sprint->set('IDholon', (int)$context['currentHolon']->getId());
                $sprint->set('state', 'scheduled');
                $sprint->set('archived', 0); $sprint->set('baseline', 0);
            }
            ScrumSprint::checkedSave($sprint);
        } elseif ($action === 'import') {
            $sprint->import(array_map('intval', (array)($_POST['projects'] ?? [])), (array)($_POST['sizes'] ?? []),
                static fn (Project $p) => omoProjectsCanViewProject($p, $context), static fn (Project $p) => omoProjectsCanManageProject($p, $context));
        } elseif ($action === 'remove') {
            $sprint->removeTree((int)($_POST['project_id'] ?? 0));
        } elseif ($action === 'stop') {
            if ($sprint->get('state') === 'finished') { throw new RuntimeException('locked'); }
            $sprint->sample('stop'); $sprint->set('state', 'stopped'); ScrumSprint::checkedSave($sprint);
        } elseif ($action === 'resume') {
            if (!$sprint->editable()) { throw new RuntimeException('locked'); }
            // Revalidate visibility, permissions and the entire hierarchy before restarting.
            $sprint->import([], [], static fn (Project $p) => omoProjectsCanViewProject($p, $context), static fn (Project $p) => omoProjectsCanManageProject($p, $context));
            if ($sprint->get('start_date')->format('Y-m-d') > ScrumSprint::now()->format('Y-m-d')) {
                $sprint->set('state', 'scheduled'); ScrumSprint::checkedSave($sprint);
            } else { $sprint->start(); }
        } elseif ($action === 'archive' || $action === 'restore') {
            if ($sprint->get('state') !== 'finished') { throw new RuntimeException('locked'); }
            $sprint->set('archived', $action === 'archive' ? 1 : 0); ScrumSprint::checkedSave($sprint);
        } elseif ($action === 'delete') {
            if ($sprint->get('state') === 'running') { throw new RuntimeException('locked'); }
            if (!$sprint->delete()) { throw new RuntimeException('save'); }
        } else { throw new RuntimeException('missing'); }
        return ['id' => (int)$sprint->getId(), 'url' => omoScrumUrl($context, in_array($action, ['delete', 'archive', 'restore'], true) ? [] : ['id' => (int)$sprint->getId()])];
    });
    echo json_encode(['status' => true, 'message' => omoScrumT('saved')] + $result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    $key = $error->getMessage();
    if (!isset(omoScrumSourceLang()[$key])) { error_log('Scrum action: ' . $key); $key = 'save'; }
    http_response_code($key === 'access' ? 403 : ($key === 'missing' ? 404 : 422));
    echo json_encode(['status' => false, 'message' => omoScrumT($key)], JSON_UNESCAPED_UNICODE);
}
