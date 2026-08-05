<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

use dbObject\ArrayOrganization;
use dbObject\ArrayProject;
use dbObject\Authority;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
if ($organizationId <= 0) {
    http_response_code(400);
    echo json_encode(
        array(
            'error' => true,
            'message' => "Organisation invalide.",
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$organizations = new ArrayOrganization();
$organizations->load(
    array(
        'where' => array(
            array('field' => 'id', 'value' => $organizationId),
        ),
        'limit' => 1,
    )
);

$organization = $organizations->get($organizationId);
if ($organization === null) {
    http_response_code(404);
    echo json_encode(
        array(
            'error' => true,
            'message' => "Organisation introuvable.",
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$canViewOrganization = $organization->canViewDetail();
if (!$canViewOrganization) {
    http_response_code(403);
    echo json_encode(
        array(
            'error' => true,
            'message' => "Acces refuse a cette organisation.",
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$root = $organization->getEnabledStructuralRootHolon();
if ($root === null) {
    http_response_code(404);
    echo json_encode(
        array(
            'error' => true,
            'message' => "Aucun holon racine de type organisation n'a ete trouve pour cette organisation.",
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$navigationRoot = $root;
$shareLink = function_exists('commonGetCurrentShareLink') ? commonGetCurrentShareLink() : null;
if ($shareLink && $shareLink->canViewOrganization($organizationId)) {
    $shareScopeHolon = $shareLink->getScopeHolon();
    if ($shareScopeHolon instanceof \dbObject\Holon) {
        $navigationRoot = $shareScopeHolon;
    }
}

if (!$navigationRoot->canViewDetail()) {
    http_response_code(403);
    echo json_encode(
        array(
            'error' => true,
            'message' => "Acces refuse a ce holon.",
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$representation = $navigationRoot->toRepresentationArray(array(
    'representation' => 'circle',
    'includeMemberUserIds' => !(function_exists('commonGetCurrentShareToken') && commonGetCurrentShareToken() !== '' && !commonCurrentShareAllowsPeople()),
    'organizationId' => $organizationId,
));

$projectTitles = array();
$projects = new ArrayProject();
$projects->loadForOrganization($organizationId);
foreach ($projects as $project) {
    $projectId = (int)$project->getId();
    if ($projectId > 0) {
        $projectTitles[$projectId] = trim((string)$project->get('title'));
    }
}
$representation['projectTitles'] = $projectTitles;

$authorityLabels = array();
foreach (Authority::getEditorCatalogForOrganization($organizationId) as $authority) {
    $authorityId = (int)($authority['id'] ?? 0);
    $authorityLabel = trim((string)($authority['label'] ?? ''));
    if ($authorityId > 0 && $authorityLabel !== '') {
        $authorityLabels[$authorityId] = $authorityLabel;
    }
}
$representation['authorityLabels'] = $authorityLabels;

if ((int)$navigationRoot->getId() !== (int)$root->getId() && (int)$navigationRoot->get('IDtypeholon') !== 4) {
    $representation['type'] = '4';

    $rootColor = trim((string)$root->getEffectiveColor());
    if ($rootColor === '') {
        $rootColor = trim((string)$organization->get('color'));
    }

    if ($rootColor !== '') {
        $representation['mycolor'] = $rootColor;
    }
}

echo json_encode($representation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
