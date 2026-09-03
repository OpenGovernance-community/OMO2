<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__, 2) . '/common/auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

commonRestoreRememberedUser();

function timerApiReply(array $payload, $statusCode = 200)
{
    http_response_code((int)$statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function timerApiInput()
{
    $rawInput = file_get_contents('php://input');
    if (is_string($rawInput) && trim($rawInput) !== '') {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return is_array($_POST) ? $_POST : array();
}

function timerApiRequireCsrf(array $input)
{
    $providedToken = trim((string)($input['csrf_token'] ?? ''));
    $expectedToken = trim((string)($_SESSION['timer_csrf'] ?? ''));
    if ($providedToken === '' || $expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
        timerApiReply(array('error' => true, 'message' => 'Session de securite invalide.'), 403);
    }
}

function timerApiLoadOrganization($userId, $organizationId)
{
    $userId = (int)$userId;
    $organizationId = (int)$organizationId;
    if ($userId <= 0 || $organizationId <= 0 || !commonUserHasOrganizationMembership($userId, $organizationId)) {
        return null;
    }

    $organization = new \dbObject\Organization();
    return $organization->load($organizationId) ? $organization : null;
}

function timerApiLoadHolon(\dbObject\Organization $organization, $userId, $holonId)
{
    $holonId = (int)$holonId;
    if ($holonId <= 0) {
        return null;
    }

    $rootHolon = $organization->getEnabledStructuralRootHolon();
    if (!($rootHolon instanceof \dbObject\Holon)) {
        return null;
    }

    $holon = new \dbObject\Holon();
    if (!$holon->load($holonId) || !$holon->canViewDetail()) {
        return null;
    }

    $rootId = (int)$rootHolon->getId();
    if ((int)$holon->getId() !== $rootId && (int)$holon->get('IDholon_org') !== $rootId) {
        return null;
    }

    return $holon;
}

function timerApiValidateTarget($userId, $organizationId, $holonId, $projectId = 0)
{
    $organization = timerApiLoadOrganization($userId, $organizationId);
    if (!($organization instanceof \dbObject\Organization)) {
        return null;
    }

    $holon = timerApiLoadHolon($organization, $userId, $holonId);
    if (!($holon instanceof \dbObject\Holon)) {
        return null;
    }

    $projectId = (int)$projectId;
    if ($projectId > 0) {
        $project = new \dbObject\Project();
        if (!$project->load($projectId)
            || (int)$project->get('IDorganization') !== (int)$organization->getId()
            || (int)$project->get('IDholon') !== (int)$holon->getId()
            || !in_array((string)$project->get('status'), \dbObject\Project::getWorkTimeStatuses(), true)
            || !(bool)$project->get('active')
            || (string)$project->get('project_kind') !== \dbObject\Project::KIND_STANDARD) {
            return null;
        }
    }

    return array('organization' => $organization, 'holon' => $holon);
}

$userId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
if ($userId <= 0) {
    timerApiReply(array('error' => true, 'message' => 'Connexion requise.'), 401);
}

$action = trim((string)($_GET['action'] ?? $_POST['action'] ?? 'state'));
$input = timerApiInput();

if ($action === 'state') {
    $active = \dbObject\WorkTime::findOpenForUser($userId);
    timerApiReply(array(
        'error' => false,
        'active' => $active instanceof \dbObject\WorkTime,
        'entry' => $active instanceof \dbObject\WorkTime ? $active->toTimerArray() : null,
        'serverNow' => time(),
    ));
}

if ($action === 'projects') {
    $organizationId = (int)($_GET['organization_id'] ?? 0);
    $holonId = (int)($_GET['holon_id'] ?? 0);
    $status = \dbObject\Project::normalizeStatus($_GET['status'] ?? \dbObject\Project::STATUS_IN_PROGRESS);
    if (!timerApiValidateTarget($userId, $organizationId, $holonId)) {
        timerApiReply(array('error' => true, 'message' => 'Le holon choisi n est pas accessible.'), 422);
    }
    if (!in_array($status, \dbObject\Project::getWorkTimeStatuses(), true)) {
        timerApiReply(array('error' => true, 'message' => 'Le statut choisi ne peut pas etre suivi.'), 422);
    }

    $projects = new \dbObject\ArrayProject();
    $projects->loadForWorkTimeHolon($organizationId, $holonId, $status);
    $projectData = array();
    foreach ($projects as $project) {
        if (!($project instanceof \dbObject\Project)) {
            continue;
        }
        $projectData[] = array(
            'id' => (int)$project->getId(),
            'title' => trim((string)$project->get('title')),
        );
    }

    timerApiReply(array(
        'error' => false,
        'projects' => $projectData,
        'serverNow' => time(),
    ));
}

timerApiRequireCsrf($input);

if (in_array($action, array('start', 'switch'), true)) {
    $organizationId = (int)($input['organization_id'] ?? 0);
    $holonId = (int)($input['holon_id'] ?? 0);
    $projectId = (int)($input['project_id'] ?? 0);
    if (!timerApiValidateTarget($userId, $organizationId, $holonId, $projectId)) {
        timerApiReply(array('error' => true, 'message' => 'Le holon choisi n est pas accessible.'), 422);
    }

    $active = \dbObject\WorkTime::startOrSwitch($userId, $organizationId, $holonId, $projectId);
    if (!($active instanceof \dbObject\WorkTime)) {
        timerApiReply(array('error' => true, 'message' => 'Impossible d enregistrer le temps de travail.'), 500);
    }

    timerApiReply(array(
        'error' => false,
        'active' => true,
        'entry' => $active->toTimerArray(),
        'serverNow' => time(),
    ));
}

$entryId = (int)($input['entry_id'] ?? 0);
if ($action === 'heartbeat') {
    $active = \dbObject\WorkTime::touchOpenForUser($userId, $entryId);
    if (!($active instanceof \dbObject\WorkTime)) {
        timerApiReply(array('error' => true, 'message' => 'Aucun suivi actif n a ete trouve.'), 409);
    }

    timerApiReply(array(
        'error' => false,
        'active' => true,
        'entry' => $active->toTimerArray(),
        'serverNow' => time(),
    ));
}

if ($action === 'stop') {
    $closed = \dbObject\WorkTime::closeOpenForUser($userId, $entryId, \dbObject\WorkTime::END_REASON_STOP);
    if (!($closed instanceof \dbObject\WorkTime)) {
        timerApiReply(array('error' => true, 'message' => 'Aucun suivi actif n a ete trouve.'), 409);
    }

    timerApiReply(array(
        'error' => false,
        'active' => false,
        'entry' => $closed->toTimerArray(),
        'serverNow' => time(),
    ));
}

timerApiReply(array('error' => true, 'message' => 'Action inconnue.'), 400);
