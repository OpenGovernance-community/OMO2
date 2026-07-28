<?php
require_once __DIR__ . '/bootstrap.php';

use dbObject\Holon;
use dbObject\Organization;

function omoStructureExportJsonError($statusCode, $message)
{
    http_response_code((int)$statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(
        array(
            'error' => true,
            'message' => (string)$message,
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function omoExportSlugify($value)
{
    $value = trim(mb_strtolower((string)$value, 'UTF-8'));
    $value = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = preg_replace('/[^a-z0-9]+/', '-', (string)$value);
    $value = trim((string)$value, '-');

    return $value !== '' ? $value : 'structure';
}

function omoExportFlattenHolonRows(array $nodes, array &$rows)
{
    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }

        $record = $node;
        $children = array();
        if (isset($record['children']) && is_array($record['children'])) {
            $children = $record['children'];
        }
        unset($record['children']);

        $rows[] = $record;

        if (count($children) > 0) {
            omoExportFlattenHolonRows($children, $rows);
        }
    }
}

function omoExportEncodePermissionsForCsv(array $permissionRows)
{
    $items = array();

    foreach ($permissionRows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $permissionKey = trim((string)($row['permissionKey'] ?? ''));
        if ($permissionKey === '') {
            continue;
        }

        $range = trim((string)($row['range'] ?? ''));
        $memberType = trim((string)($row['memberType'] ?? 'member'));
        if ($memberType === '') {
            $memberType = 'member';
        }

        $permissionValue = $memberType . ':' . $permissionKey;
        $items[] = $range !== ''
            ? ($permissionValue . '@' . $range)
            : $permissionValue;
    }

    return implode('|', $items);
}

function omoExportEncodeCsv(array $header, array $rows)
{
    $handle = fopen('php://temp', 'r+');
    if ($handle === false) {
        return false;
    }

    fputcsv($handle, $header);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    rewind($handle);
    $content = stream_get_contents($handle);
    fclose($handle);

    return is_string($content) ? $content : false;
}

function omoExportBuildCsvContent(array $payload)
{
    $header = array(
        'row_type',
        'meta_key',
        'meta_value',
        'id',
        'parent_id',
        'template_id',
        'role',
        'scope_root',
        'template_node',
        'type_id',
        'name',
        'full_name',
        'template_name',
        'visible',
        'mandatory',
        'locked_name',
        'locked_icon',
        'locked_banner',
        'unique_flag',
        'link_flag',
        'color',
        'icon',
        'banner',
        'access_key',
        'properties_json',
        'permissions',
        'property_definition_id',
        'property_name',
        'property_shortname',
        'property_format_id',
        'property_list_item_type',
        'property_list_holon_type_ids',
        'property_position',
        'property_active',
    );

    $rows = array();
    $metadataRows = array(
        'format' => (string)($payload['format'] ?? ''),
        'version' => (string)($payload['version'] ?? ''),
        'exportedAt' => (string)($payload['exportedAt'] ?? ''),
        'organizationId' => (string)($payload['organization']['sourceId'] ?? ''),
        'organizationName' => (string)($payload['organization']['name'] ?? ''),
        'organizationShortname' => (string)($payload['organization']['shortname'] ?? ''),
        'organizationColor' => (string)($payload['organization']['color'] ?? ''),
        'organizationRootHolonId' => (string)($payload['scope']['organizationRootHolonId'] ?? ''),
        'navigationRootHolonId' => (string)($payload['scope']['navigationRootHolonId'] ?? ''),
        'exportRootHolonId' => (string)($payload['scope']['exportRootHolonId'] ?? ''),
        'exportRootHolonName' => (string)($payload['scope']['exportRootHolonName'] ?? ''),
        'holonCount' => (string)($payload['scope']['holonCount'] ?? ''),
    );

    foreach ($metadataRows as $key => $value) {
        $rows[] = array(
            'meta',
            $key,
            $value,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        );
    }

    foreach ((array)($payload['propertyDefinitions'] ?? array()) as $definition) {
        if (!is_array($definition)) {
            continue;
        }

        $rows[] = array(
            'property_definition',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            (string)($definition['id'] ?? ''),
            (string)($definition['name'] ?? ''),
            (string)($definition['shortname'] ?? ''),
            (string)($definition['formatId'] ?? ''),
            (string)($definition['listItemType'] ?? ''),
            implode('|', array_map('strval', (array)($definition['listHolonTypeIds'] ?? array()))),
            (string)($definition['position'] ?? ''),
            array_key_exists('active', $definition) ? (!empty($definition['active']) ? '1' : '0') : '',
        );
    }

    $holonRows = array();
    omoExportFlattenHolonRows((array)($payload['holons'] ?? array()), $holonRows);

    foreach ($holonRows as $record) {
        $propertiesJson = '';
        if (!empty($record['properties']) && is_array($record['properties'])) {
            $encodedProperties = json_encode($record['properties'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $propertiesJson = is_string($encodedProperties) ? $encodedProperties : '';
        }

        $rows[] = array(
            'holon',
            '',
            '',
            (string)($record['id'] ?? ''),
            (string)($record['parentId'] ?? ''),
            (string)($record['templateId'] ?? ''),
            (string)($record['role'] ?? ''),
            !empty($record['scopeRoot']) ? '1' : '0',
            !empty($record['templateNode']) ? '1' : '0',
            (string)($record['typeId'] ?? ''),
            (string)($record['name'] ?? ''),
            (string)($record['fullName'] ?? ''),
            (string)($record['templateName'] ?? ''),
            array_key_exists('visible', $record) ? (!empty($record['visible']) ? '1' : '0') : '',
            !empty($record['mandatory']) ? '1' : '0',
            !empty($record['lockedName']) ? '1' : '0',
            !empty($record['lockedIcon']) ? '1' : '0',
            !empty($record['lockedBanner']) ? '1' : '0',
            !empty($record['unique']) ? '1' : '0',
            !empty($record['link']) ? '1' : '0',
            (string)($record['color'] ?? ''),
            (string)($record['icon'] ?? ''),
            (string)($record['banner'] ?? ''),
            (string)($record['accessKey'] ?? ''),
            $propertiesJson,
            omoExportEncodePermissionsForCsv((array)($record['permissions'] ?? array())),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        );
    }

    return omoExportEncodeCsv($header, $rows);
}

function omoExportAppendXmlScalarNode(DOMDocument $document, DOMElement $parent, $name, $value)
{
    if ($value === null) {
        return;
    }

    if (is_bool($value)) {
        $value = $value ? '1' : '0';
    } else {
        $value = (string)$value;
    }

    $node = $document->createElement((string)$name);
    $node->appendChild($document->createTextNode($value));
    $parent->appendChild($node);
}

function omoExportAppendXmlRowNodes(DOMDocument $document, DOMElement $parent, array $record, array $fieldOrder)
{
    foreach ($fieldOrder as $fieldName) {
        if (!array_key_exists($fieldName, $record)) {
            continue;
        }

        omoExportAppendXmlScalarNode($document, $parent, $fieldName, $record[$fieldName]);
    }
}

function omoExportAppendXmlHolonNode(DOMDocument $document, DOMElement $parent, array $record)
{
    $holonNode = $document->createElement('holon');

    omoExportAppendXmlRowNodes(
        $document,
        $holonNode,
        $record,
        array(
            'id',
            'typeId',
            'name',
            'fullName',
            'role',
            'scopeRoot',
            'templateNode',
            'templateName',
            'parentId',
            'templateId',
            'visible',
            'mandatory',
            'lockedName',
            'lockedIcon',
            'lockedBanner',
            'unique',
            'link',
            'adminParent',
            'adminMin',
            'adminMax',
            'lockedAdminMin',
            'lockedAdminMax',
            'adminMinOverride',
            'adminMaxOverride',
            'color',
            'icon',
            'banner',
            'accessKey',
        )
    );

    if (!empty($record['properties']) && is_array($record['properties'])) {
        $propertiesNode = $document->createElement('properties');
        foreach ($record['properties'] as $propertyRow) {
            if (!is_array($propertyRow)) {
                continue;
            }

            $propertyNode = $document->createElement('property');
            omoExportAppendXmlRowNodes(
                $document,
                $propertyNode,
                $propertyRow,
                array('propertyId', 'value', 'position', 'mandatory', 'locked', 'active')
            );
            $propertiesNode->appendChild($propertyNode);
        }
        $holonNode->appendChild($propertiesNode);
    }

    if (!empty($record['permissions']) && is_array($record['permissions'])) {
        $permissionsNode = $document->createElement('permissions');
        foreach ($record['permissions'] as $permissionRow) {
            if (!is_array($permissionRow)) {
                continue;
            }

            $permissionNode = $document->createElement('permission');
            omoExportAppendXmlRowNodes(
                $document,
                $permissionNode,
                $permissionRow,
                array('permissionKey', 'range', 'memberType')
            );
            $permissionsNode->appendChild($permissionNode);
        }
        $holonNode->appendChild($permissionsNode);
    }

    if (!empty($record['children']) && is_array($record['children'])) {
        $childrenNode = $document->createElement('children');
        foreach ($record['children'] as $childRecord) {
            if (!is_array($childRecord)) {
                continue;
            }

            omoExportAppendXmlHolonNode($document, $childrenNode, $childRecord);
        }
        $holonNode->appendChild($childrenNode);
    }

    $parent->appendChild($holonNode);
}

function omoExportBuildXmlContent(array $payload)
{
    if (!class_exists('DOMDocument')) {
        return false;
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    $document->formatOutput = true;

    $root = $document->createElement('structureExport');
    $document->appendChild($root);

    omoExportAppendXmlRowNodes(
        $document,
        $root,
        $payload,
        array('format', 'version', 'exportedAt')
    );

    $scopeNode = $document->createElement('scope');
    omoExportAppendXmlRowNodes(
        $document,
        $scopeNode,
        (array)($payload['scope'] ?? array()),
        array(
            'organizationId',
            'organizationName',
            'organizationRootHolonId',
            'navigationRootHolonId',
            'exportRootHolonId',
            'exportRootHolonName',
            'holonCount',
        )
    );
    $root->appendChild($scopeNode);

    $organizationNode = $document->createElement('organization');
    omoExportAppendXmlRowNodes(
        $document,
        $organizationNode,
        (array)($payload['organization'] ?? array()),
        array('sourceId', 'name', 'shortname', 'color')
    );
    $root->appendChild($organizationNode);

    $definitionsNode = $document->createElement('propertyDefinitions');
    foreach ((array)($payload['propertyDefinitions'] ?? array()) as $definition) {
        if (!is_array($definition)) {
            continue;
        }

        $definitionNode = $document->createElement('propertyDefinition');
        omoExportAppendXmlRowNodes(
            $document,
            $definitionNode,
            $definition,
            array('id', 'name', 'shortname', 'formatId', 'listItemType', 'position', 'active')
        );

        if (!empty($definition['listHolonTypeIds']) && is_array($definition['listHolonTypeIds'])) {
            $listHolonTypeIdsNode = $document->createElement('listHolonTypeIds');
            foreach ($definition['listHolonTypeIds'] as $typeId) {
                omoExportAppendXmlScalarNode($document, $listHolonTypeIdsNode, 'typeId', $typeId);
            }
            $definitionNode->appendChild($listHolonTypeIdsNode);
        }

        $definitionsNode->appendChild($definitionNode);
    }
    $root->appendChild($definitionsNode);

    $holonsNode = $document->createElement('holons');
    foreach ((array)($payload['holons'] ?? array()) as $record) {
        if (!is_array($record)) {
            continue;
        }

        omoExportAppendXmlHolonNode($document, $holonsNode, $record);
    }
    $root->appendChild($holonsNode);

    return $document->saveXML();
}

$format = strtolower(trim((string)($_GET['format'] ?? 'json')));
if (!in_array($format, array('json', 'xml', 'csv'), true)) {
    omoStructureExportJsonError(400, 'Format d export invalide.');
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
if ($organizationId <= 0) {
    omoStructureExportJsonError(400, 'Organisation invalide.');
}

if ((int)commonGetCurrentUserId() <= 0 || !commonCurrentUserHasOrganizationAccess($organizationId)) {
    omoStructureExportJsonError(403, 'Acces refuse a cet export.');
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    omoStructureExportJsonError(404, 'Organisation introuvable.');
}

if (!$organization->canViewDetail()) {
    omoStructureExportJsonError(403, 'Acces refuse a cette organisation.');
}

$organizationRoot = $organization->getStructuralRootHolon();
if ($organizationRoot === null) {
    omoStructureExportJsonError(404, "Aucun holon racine de type organisation n'a ete trouve pour cette organisation.");
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
        omoStructureExportJsonError(404, 'Holon introuvable.');
    }

    if (!$currentHolon->canViewDetail() || !$currentHolon->isDescendantOf($navigationRoot, true)) {
        omoStructureExportJsonError(403, 'Acces refuse a ce holon.');
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

$filenameBase = sprintf(
    'omo-structure-%s-%s-%s',
    omoExportSlugify((string)$organization->get('shortname') ?: (string)$organization->get('name')),
    omoExportSlugify($exportRoot->getDisplayName()),
    date('Ymd-His')
);

if ($format === 'csv') {
    $content = omoExportBuildCsvContent($payload);
    if (!is_string($content)) {
        omoStructureExportJsonError(500, 'Impossible de generer le CSV de structure.');
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filenameBase . '.csv"');
    echo $content;
    exit;
}

if ($format === 'xml') {
    $content = omoExportBuildXmlContent($payload);
    if (!is_string($content) || $content === '') {
        omoStructureExportJsonError(500, 'Impossible de generer le XML de structure.');
    }

    header('Content-Type: application/xml; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filenameBase . '.xml"');
    echo $content;
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filenameBase . '.json"');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
