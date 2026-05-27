<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

use dbObject\Holon;
use dbObject\Organization;

function omoExportSlugify($value)
{
    $value = trim(mb_strtolower((string)$value, 'UTF-8'));
    $value = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = preg_replace('/[^a-z0-9]+/', '-', (string)$value);
    $value = trim((string)$value, '-');

    return $value !== '' ? $value : 'structure';
}

function omoExportBuildRepresentation(Holon $exportRoot, Holon $organizationRoot, Organization $organization, $organizationId)
{
    $representation = $exportRoot->toRepresentationArray(array(
        'representation' => 'circle',
        'includeMemberUserIds' => false,
        'organizationId' => (int)$organizationId,
    ));

    if ((int)$exportRoot->getId() !== (int)$organizationRoot->getId() && (int)$exportRoot->get('IDtypeholon') !== 4) {
        $representation['type'] = '4';

        $rootColor = trim((string)$organizationRoot->getEffectiveColor());
        if ($rootColor === '') {
            $rootColor = trim((string)$organization->get('color'));
        }

        if ($rootColor !== '') {
            $representation['mycolor'] = $rootColor;
        }
    }

    return $representation;
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
if ($organizationId <= 0) {
    http_response_code(400);
    echo json_encode(
        array(
            'error' => true,
            'message' => 'Organisation invalide.',
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

if ((int)commonGetCurrentUserId() <= 0 || !commonCurrentUserHasOrganizationAccess($organizationId)) {
    http_response_code(403);
    echo json_encode(
        array(
            'error' => true,
            'message' => 'Acces refuse a cet export.',
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    echo json_encode(
        array(
            'error' => true,
            'message' => 'Organisation introuvable.',
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

if (!$organization->canViewDetail()) {
    http_response_code(403);
    echo json_encode(
        array(
            'error' => true,
            'message' => 'Acces refuse a cette organisation.',
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$organizationRoot = $organization->getStructuralRootHolon();
if ($organizationRoot === null) {
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

$navigationRoot = $organizationRoot;
$shareLink = function_exists('commonGetCurrentShareLink') ? commonGetCurrentShareLink() : null;
if ($shareLink && $shareLink->canViewOrganization($organizationId)) {
    $shareScopeHolon = $shareLink->getScopeHolon();
    if ($shareScopeHolon instanceof Holon) {
        $navigationRoot = $shareScopeHolon;
    }
}

$exportRoot = $navigationRoot;
$currentHolonId = (int)($_GET['cid'] ?? 0);
if ($currentHolonId > 0 && $currentHolonId !== (int)$navigationRoot->getId()) {
    $currentHolon = new Holon();
    if (!$currentHolon->load($currentHolonId)) {
        http_response_code(404);
        echo json_encode(
            array(
                'error' => true,
                'message' => 'Holon introuvable.',
            ),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    if (!$currentHolon->canViewDetail() || !$currentHolon->isDescendantOf($navigationRoot, true)) {
        http_response_code(403);
        echo json_encode(
            array(
                'error' => true,
                'message' => 'Acces refuse a ce holon.',
            ),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    $exportRoot = $currentHolon;
}

$compactExportData = $organization->getStructureCompactExportData($exportRoot);

$payload = array(
    'format' => 'openmyorganization-structure-export',
    'version' => 4,
    'exportedAt' => date('c'),
    'scope' => array(
        'organizationId' => (int)$organization->getId(),
        'organizationName' => (string)$organization->get('name'),
        'organizationRootHolonId' => (int)$organizationRoot->getId(),
        'navigationRootHolonId' => (int)$navigationRoot->getId(),
        'exportRootHolonId' => (int)$exportRoot->getId(),
        'exportRootHolonName' => $exportRoot->getDisplayName(),
        'holonCount' => 1 + (int)$exportRoot->countVisibleDescendants(),
    ),
    'organization' => array(
        'sourceId' => (int)$organization->getId(),
        'name' => (string)$organization->get('name'),
        'shortname' => (string)$organization->get('shortname'),
        'color' => (string)$organization->get('color'),
    ),
    'holons' => $compactExportData['holons'],
    'propertyDefinitions' => $compactExportData['propertyDefinitions'],
);

$filename = sprintf(
    'omo-structure-%s-%s-%s.json',
    omoExportSlugify((string)$organization->get('shortname') ?: (string)$organization->get('name')),
    omoExportSlugify($exportRoot->getDisplayName()),
    date('Ymd-His')
);

header('Content-Disposition: attachment; filename="' . $filename . '"');

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
