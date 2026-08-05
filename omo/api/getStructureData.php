<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

use dbObject\ArrayOrganization;
use dbObject\ArrayProject;
use dbObject\Authority;

function omoStructureCollectAuthorityIdsFromValue($rawValue, $formatId, array &$authorityIds)
{
    $rawValue = trim((string)$rawValue);
    if ($rawValue === '') {
        return;
    }

    if ((int)$formatId === \dbObject\PropertyFormat::FORMAT_HTML_LIST) {
        $items = \dbObject\PropertyFormat::getHtmlListParts($rawValue)['items'];
    } else {
        $decoded = json_decode($rawValue, true);
        $items = is_array($decoded)
            ? array_values($decoded)
            : preg_split('/\r\n|\r|\n|\|/', $rawValue);
    }

    foreach ($items as $item) {
        $authorityId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
        if ($authorityId > 0) {
            $authorityIds[$authorityId] = true;
        }
    }
}

function omoStructureCollectAuthorityIds(array $node, array &$authorityIds)
{
    $data = is_array($node['data'] ?? null) ? $node['data'] : array();
    foreach ($data as $entry) {
        if (
            !is_array($entry)
            || (string)($entry['listItemType'] ?? '') !== \dbObject\Property::LIST_ITEM_AUTHORITY
        ) {
            continue;
        }

        $formatId = (int)($entry['formatId'] ?? 0);
        foreach (array('value', 'ancestor', 'effectiveValue') as $field) {
            omoStructureCollectAuthorityIdsFromValue($entry[$field] ?? '', $formatId, $authorityIds);
        }
    }

    foreach ((array)($node['children'] ?? array()) as $child) {
        if (is_array($child)) {
            omoStructureCollectAuthorityIds($child, $authorityIds);
        }
    }
}

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

$authorityIds = array();
omoStructureCollectAuthorityIds($representation, $authorityIds);
$representation['authorityLabels'] = Authority::getLabelsByIds(array_keys($authorityIds));

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
