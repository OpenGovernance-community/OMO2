<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

use dbObject\ArrayOrganization;
use dbObject\ArrayProject;
use dbObject\Authority;

const OMO_STRUCTURE_CACHE_VERSION = 1;

function omoStructureBuildCacheKey($organizationId, $navigationRootId, $includeMemberUserIds)
{
    $currentUserId = function_exists('commonGetCurrentUserId')
        ? (int)commonGetCurrentUserId()
        : (int)($_SESSION['currentUser'] ?? 0);
    $shareToken = function_exists('commonGetCurrentShareToken')
        ? trim((string)commonGetCurrentShareToken())
        : '';

    return hash('sha256', implode(':', array(
        OMO_STRUCTURE_CACHE_VERSION,
        (int)$organizationId,
        (int)$navigationRootId,
        $includeMemberUserIds ? 1 : 0,
        $currentUserId,
        $shareToken,
    )));
}

function omoStructureReadSessionCache($cacheKey, $latestHistoryId)
{
    $entry = $_SESSION['omoStructureRepresentationCache'][$cacheKey] ?? null;
    if (!is_array($entry)) {
        return null;
    }

    if ((int)($entry['version'] ?? 0) !== OMO_STRUCTURE_CACHE_VERSION) {
        return null;
    }

    if ((int)($entry['historyId'] ?? -1) !== (int)$latestHistoryId) {
        return null;
    }

    return is_array($entry['representation'] ?? null) ? $entry['representation'] : null;
}

function omoStructureWriteSessionCache($cacheKey, $latestHistoryId, array $representation)
{
    if (session_status() !== PHP_SESSION_ACTIVE && !@session_start()) {
        return;
    }

    if (!isset($_SESSION['omoStructureRepresentationCache']) || !is_array($_SESSION['omoStructureRepresentationCache'])) {
        $_SESSION['omoStructureRepresentationCache'] = array();
    }

    $_SESSION['omoStructureRepresentationCache'][$cacheKey] = array(
        'version' => OMO_STRUCTURE_CACHE_VERSION,
        'historyId' => (int)$latestHistoryId,
        'cachedAt' => time(),
        'representation' => $representation,
    );

    if (count($_SESSION['omoStructureRepresentationCache']) > 8) {
        uasort($_SESSION['omoStructureRepresentationCache'], static function ($left, $right) {
            return (int)($left['cachedAt'] ?? 0) <=> (int)($right['cachedAt'] ?? 0);
        });
        $_SESSION['omoStructureRepresentationCache'] = array_slice(
            $_SESSION['omoStructureRepresentationCache'],
            -8,
            null,
            true
        );
    }

    session_write_close();
}

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
        if (is_array($decoded)) {
            $items = array_values($decoded);
        } else {
            $items = array();
            foreach (preg_split('/\r\n|\r|\n|\|/', $rawValue) as $segment) {
                $segment = trim((string)$segment);
                if ($segment === '') {
                    continue;
                }

                $decodedSegment = json_decode($segment, true);
                if (is_array($decodedSegment)) {
                    foreach ($decodedSegment as $item) {
                        $items[] = $item;
                    }
                } else {
                    $items[] = $segment;
                }
            }
        }
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

$organizationId = (int)($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
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

$includeMemberUserIds = !(
    function_exists('commonGetCurrentShareToken')
    && commonGetCurrentShareToken() !== ''
    && !commonCurrentShareAllowsPeople()
);
$latestHistoryId = \dbObject\History::getLatestOrganizationEntryId($organizationId);
$cacheKey = omoStructureBuildCacheKey(
    $organizationId,
    (int)$navigationRoot->getId(),
    $includeMemberUserIds
);
$forceRefresh = (int)($_GET['structure_refresh'] ?? 0) === 1;
$cachedRepresentation = $forceRefresh
    ? null
    : omoStructureReadSessionCache($cacheKey, $latestHistoryId);

if (is_array($cachedRepresentation)) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    header('X-OMO-Structure-Cache: hit');
    echo json_encode($cachedRepresentation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$representation = $navigationRoot->toBulkStructureRepresentationArray(array(
    'representation' => 'circle',
    'includeMemberUserIds' => $includeMemberUserIds,
    'organizationId' => $organizationId,
    'organizationRootHolonId' => (int)$root->getId(),
));

if (count($representation) === 0) {
    http_response_code(500);
    echo json_encode(
        array(
            'error' => true,
            'message' => "Structure indisponible.",
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

$representation['projectTitles'] = ArrayProject::fetchTitlesForOrganization($organizationId);

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

omoStructureWriteSessionCache($cacheKey, $latestHistoryId, $representation);
header('X-OMO-Structure-Cache: miss');
echo json_encode($representation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
