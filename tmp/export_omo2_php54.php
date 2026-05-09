<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/include.php');

define('OMO2_EXPORT_FORMAT', 'openmyorganization-structure-export');
define('OMO2_EXPORT_VERSION', 4);
define('OMO2_ROOT_HOLON_ID', 1);
define('OMO2_ROLE_TEMPLATE_ID', 1001);
define('OMO2_CIRCLE_TEMPLATE_ID', 1002);
define('OMO2_GROUP_TEMPLATE_ID', 1003);
define('OMO2_FIRST_LINK_TEMPLATE_ID', 1004);
define('OMO2_SECOND_LINK_TEMPLATE_ID', 1005);
define('OMO2_FACILITATION_TEMPLATE_ID', 1006);
define('OMO2_MEMORY_TEMPLATE_ID', 1007);
define('OMO2_REAL_HOLON_ID_OFFSET', 100000);
define('OMO2_GROUP_HOLON_ID_OFFSET', 200000);
define('OMO2_DEFAULT_ORGANIZATION_COLOR', '#3da8a9');
define('OMO2_PROPERTY_RAISON_ETRE', 101);
define('OMO2_PROPERTY_ATTENDUS', 102);
define('OMO2_PROPERTY_DOMAINES', 103);
define('OMO2_PROPERTY_STRATEGIE', 104);

function cleanNullable($value)
{
	$value = trim((string)$value);
	return $value === '' ? null : $value;
}

function isValidUtf8String($value)
{
	if (!is_string($value)) {
		return false;
	}

	if (function_exists('mb_check_encoding')) {
		return mb_check_encoding($value, 'UTF-8');
	}

	return preg_match('//u', $value) === 1;
}

function normalizeUtf8String($value)
{
	if (!is_string($value) || $value === '') {
		return $value;
	}

	if (isValidUtf8String($value)) {
		return $value;
	}

	$candidates = array();
	if (function_exists('iconv')) {
		$candidates[] = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
		$candidates[] = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $value);
		$candidates[] = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
	}
	if (function_exists('utf8_encode')) {
		$candidates[] = @utf8_encode($value);
	}

	foreach ($candidates as $candidate) {
		if (is_string($candidate) && $candidate !== '' && isValidUtf8String($candidate)) {
			return $candidate;
		}
	}

	return preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $value);
}

function normalizeValueForJson($value)
{
	if (is_string($value)) {
		return normalizeUtf8String($value);
	}

	if (is_array($value)) {
		$normalized = array();
		foreach ($value as $key => $item) {
			$normalized[$key] = normalizeValueForJson($item);
		}

		return $normalized;
	}

	return $value;
}

function toNullableInt($value)
{
	return $value === null || $value === '' ? null : (int)$value;
}

function toNullableParentRoleId($value)
{
	if ($value === null || $value === '' || (int)$value === 0) {
		return null;
	}

	return (int)$value;
}

function buildPdo($host, $database, $user, $password)
{
	$dsn = 'mysql:host=' . $host . ';dbname=' . $database . ';charset=utf8mb4';
	return new PDO(
		$dsn,
		$user,
		$password,
		array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		)
	);
}

function buildPdoFromLocalConfig()
{
	global $mysqlServer, $mysqlUser, $mysqlPassword, $mysqlDatabase, $mysqlDemoDatabase;

	$prefix = explode('.', $_SERVER['HTTP_HOST'])[0];
	$database = ($prefix === 'demo' && !empty($mysqlDemoDatabase)) ? (string)$mysqlDemoDatabase : (string)$mysqlDatabase;

	return array(
		buildPdo((string)$mysqlServer, $database, (string)$mysqlUser, (string)$mysqlPassword),
		(string)$mysqlServer,
		$database,
	);
}

function fetchRowsByIds(PDO $pdo, $sql, array $ids)
{
	if ($ids === array()) {
		return array();
	}

	$placeholders = implode(',', array_fill(0, count($ids), '?'));
	$statement = $pdo->prepare(str_replace(':ids', $placeholders, $sql));
	$statement->execute($ids);

	return $statement->fetchAll();
}

function fetchReachableRoles(PDO $pdo, $organisationId)
{
	$rootStatement = $pdo->prepare(
		'SELECT *
		 FROM t_role
		 WHERE role_active = 1
		   AND orga_id = ?
		   AND COALESCE(role_id_superCircle, 0) = 0
		 ORDER BY role_id'
	);
	$rootStatement->execute(array((int)$organisationId));
	$rootRoles = $rootStatement->fetchAll();
	if ($rootRoles === array()) {
		return array();
	}

	$rolesById = array();
	$frontierRoleIds = array();
	foreach ($rootRoles as $role) {
		$roleId = (int)$role['role_id'];
		$rolesById[$roleId] = $role;
		$frontierRoleIds[$roleId] = $roleId;
	}

	while ($frontierRoleIds !== array()) {
		$children = fetchRowsByIds(
			$pdo,
			'SELECT *
			 FROM t_role
			 WHERE role_active = 1
			   AND role_id_superCircle IN (:ids)
			 ORDER BY role_id_superCircle, role_id',
			array_values($frontierRoleIds)
		);

		$frontierRoleIds = array();
		foreach ($children as $childRole) {
			$childRoleId = (int)$childRole['role_id'];
			if (isset($rolesById[$childRoleId])) {
				continue;
			}

			$rolesById[$childRoleId] = $childRole;
			$frontierRoleIds[$childRoleId] = $childRoleId;
		}
	}

	return array_values($rolesById);
}

function groupRowsByKey(array $rows, $key)
{
	$grouped = array();
	foreach ($rows as $row) {
		$grouped[$row[$key]][] = $row;
	}

	return $grouped;
}

function normalizeLabel($value)
{
	$value = trim((string)$value);
	$value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
	$asciiValue = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
	if (is_string($asciiValue) && $asciiValue !== '') {
		$value = $asciiValue;
	}
	$value = preg_replace('/[^a-z0-9]+/', ' ', $value);
	$value = preg_replace('/\s+/', ' ', $value);

	return trim((string)$value);
}

function structuralTemplateDefinitions()
{
	return array(
		'first_link' => array(
			'id' => OMO2_FIRST_LINK_TEMPLATE_ID,
			'label' => 'Premier lien',
			'color' => '#FF2200',
			'aliases' => array('premier lien', '1er lien', 'lien pilotage', 'pilotage'),
			'link' => true,
		),
		'second_link' => array(
			'id' => OMO2_SECOND_LINK_TEMPLATE_ID,
			'label' => 'Second lien',
			'color' => '#FF4400',
			'aliases' => array('second lien', '2nd lien', 'deuxieme lien', 'lien representation', 'representation', 'representant'),
			'link' => true,
		),
		'facilitation' => array(
			'id' => OMO2_FACILITATION_TEMPLATE_ID,
			'label' => 'Facilitation',
			'color' => '#FF8800',
			'aliases' => array('facilitation', 'role facilitation'),
			'link' => false,
		),
		'memory' => array(
			'id' => OMO2_MEMORY_TEMPLATE_ID,
			'label' => 'Memoire',
			'color' => '#FF6600',
			'aliases' => array('memoire', 'role memoire', 'secretaire', 'role secretaire'),
			'link' => false,
		),
	);
}

function inferStructuralTemplateKey($roleName)
{
	if ($roleName === null || trim((string)$roleName) === '') {
		return null;
	}

	$normalizedRoleName = normalizeLabel((string)$roleName);
	foreach (structuralTemplateDefinitions() as $templateKey => $definition) {
		foreach ($definition['aliases'] as $alias) {
			$normalizedAlias = normalizeLabel($alias);
			if ($normalizedRoleName === $normalizedAlias || strpos($normalizedRoleName, $normalizedAlias) !== false) {
				return $templateKey;
			}
		}
	}

	return null;
}

function simplifyRoleType($roleType)
{
	if ($roleType === null) {
		return null;
	}

	return array(
		'roty_id' => (int)$roleType['roty_id'],
		'label' => cleanNullable($roleType['roty_label']),
		'is_elected_role' => (bool)$roleType['roty_isElectedRole'],
		'can_be_circle' => (bool)$roleType['roty_canBeCircle'],
		'default_purpose' => cleanNullable($roleType['roty_defaultPurpose']),
		'default_scope' => cleanNullable($roleType['roty_defaultScope']),
	);
}

function simplifyOrganisation(array $organisation)
{
	return array(
		'orga_id' => (int)$organisation['orga_id'],
		'name' => cleanNullable($organisation['orga_name']),
		'short_name' => cleanNullable($organisation['orga_shortname']),
		'description' => cleanNullable(isset($organisation['orga_description']) ? $organisation['orga_description'] : null),
		'purpose' => cleanNullable(isset($organisation['orga_purpose']) ? $organisation['orga_purpose'] : null),
		'mission' => cleanNullable(isset($organisation['orga_mission']) ? $organisation['orga_mission'] : null),
		'vision' => cleanNullable(isset($organisation['orga_vision']) ? $organisation['orga_vision'] : null),
	);
}

function simplifyAccountability(array $row)
{
	return array(
		'acco_id' => (int)$row['acco_id'],
		'description' => cleanNullable($row['acco_description']),
		'active' => (bool)$row['acco_active'],
	);
}

function simplifyDomain(array $row)
{
	return array(
		'scop_id' => (int)$row['scop_id'],
		'description' => cleanNullable($row['scop_description']),
		'policies' => cleanNullable($row['scop_politiques']),
		'parent_scope_id' => toNullableInt($row['scop_id_parent']),
	);
}

function collectReferencedRoleIds(array $role)
{
	$roleIds = array();
	foreach (array('role_id_master', 'role_id_source', 'role_id_target') as $field) {
		if (isset($role[$field]) && $role[$field] !== null && $role[$field] !== '') {
			$roleIds[] = (int)$role[$field];
		}
	}

	return array_values(array_unique(array_filter($roleIds)));
}

function buildRoleContent(array $role, array $accountabilities, array $domains)
{
	return array(
		'raison_etre' => cleanNullable(isset($role['role_purpose']) ? $role['role_purpose'] : null),
		'strategy' => cleanNullable(isset($role['role_strategy']) ? $role['role_strategy'] : null),
		'redevabilities' => array_map('simplifyAccountability', $accountabilities),
		'domains' => array_map('simplifyDomain', $domains),
	);
}

function scoreRoleContent(array $content)
{
	$score = 0;
	if ($content['raison_etre'] !== null) {
		$score += 10;
	}
	if ($content['strategy'] !== null) {
		$score += 4;
	}

	$score += count($content['redevabilities']) * 3;
	$score += count($content['domains']) * 3;

	return $score;
}

function buildStructuralTemplates(array $rolesById, array $roleContentsById)
{
	$matches = array();
	foreach ($rolesById as $roleId => $role) {
		$templateKey = inferStructuralTemplateKey(isset($role['role_name']) ? $role['role_name'] : null);
		if ($templateKey !== null) {
			$matches[$templateKey][] = (int)$roleId;
		}
	}

	$templates = array();
	foreach (structuralTemplateDefinitions() as $templateKey => $definition) {
		if (!isset($matches[$templateKey])) {
			continue;
		}

		$bestRoleId = null;
		$bestScore = -1;
		foreach ($matches[$templateKey] as $candidateRoleId) {
			$candidateContent = isset($roleContentsById[$candidateRoleId]) ? $roleContentsById[$candidateRoleId] : array(
				'raison_etre' => null,
				'strategy' => null,
				'redevabilities' => array(),
				'domains' => array(),
			);
			$score = scoreRoleContent($candidateContent);
			if ($score > $bestScore) {
				$bestScore = $score;
				$bestRoleId = $candidateRoleId;
			}
		}

		if ($bestRoleId === null) {
			continue;
		}

		$bestRole = $rolesById[$bestRoleId];
		$resolvedLabel = cleanNullable($bestRole['role_name']);
		if ($resolvedLabel === null) {
			$resolvedLabel = $definition['label'];
		}

		$templates[$templateKey] = array(
			'template_key' => $templateKey,
			'template_id' => (int)$definition['id'],
			'label' => $resolvedLabel,
			'link' => (bool)$definition['link'],
			'color' => (string)$definition['color'],
			'source_role' => array(
				'role_id' => (int)$bestRole['role_id'],
				'name' => cleanNullable($bestRole['role_name']),
			),
			'content' => $roleContentsById[$bestRoleId],
		);
	}

	return $templates;
}

function resolveRoleContent(array $role, array $localContent, array $roleContentsById, array $structuralTemplates)
{
	$candidateSources = array();
	foreach (collectReferencedRoleIds($role) as $referenceRoleId) {
		if (isset($roleContentsById[$referenceRoleId])) {
			$candidateSources[] = $roleContentsById[$referenceRoleId];
		}
	}

	$templateKey = inferStructuralTemplateKey(isset($role['role_name']) ? $role['role_name'] : null);
	if ($templateKey !== null && isset($structuralTemplates[$templateKey])) {
		$candidateSources[] = $structuralTemplates[$templateKey]['content'];
	}

	$resolvedContent = $localContent;
	foreach ($candidateSources as $candidateSource) {
		if ($resolvedContent['raison_etre'] === null && $candidateSource['raison_etre'] !== null) {
			$resolvedContent['raison_etre'] = $candidateSource['raison_etre'];
		}
		if ($resolvedContent['strategy'] === null && $candidateSource['strategy'] !== null) {
			$resolvedContent['strategy'] = $candidateSource['strategy'];
		}
		if ($resolvedContent['redevabilities'] === array() && $candidateSource['redevabilities'] !== array()) {
			$resolvedContent['redevabilities'] = $candidateSource['redevabilities'];
		}
		if ($resolvedContent['domains'] === array() && $candidateSource['domains'] !== array()) {
			$resolvedContent['domains'] = $candidateSource['domains'];
		}
	}

	return array(
		'structural_template_key' => $templateKey,
		'content' => $resolvedContent,
	);
}

function buildOmo2PropertyDefinitions()
{
	return array(
		array(
			'id' => OMO2_PROPERTY_RAISON_ETRE,
			'name' => "Raison d'etre",
			'shortname' => 'raison_etre',
			'formatId' => 1,
			'position' => 1,
		),
		array(
			'id' => OMO2_PROPERTY_ATTENDUS,
			'name' => 'Attendus',
			'shortname' => 'attendus',
			'formatId' => 2,
			'listItemType' => 'text',
			'position' => 2,
		),
		array(
			'id' => OMO2_PROPERTY_DOMAINES,
			'name' => "Domaines d'autorite",
			'shortname' => 'domaines_autorite',
			'formatId' => 2,
			'listItemType' => 'text',
			'position' => 3,
		),
		array(
			'id' => OMO2_PROPERTY_STRATEGIE,
			'name' => 'Strategie',
			'shortname' => 'strategie',
			'formatId' => 1,
			'position' => 4,
		),
	);
}

function buildListValue(array $items)
{
	$normalized = array();
	foreach ($items as $item) {
		$item = trim((string)$item);
		if ($item !== '') {
			$normalized[] = $item;
		}
	}

	if ($normalized === array()) {
		return null;
	}

	$json = json_encode(array_values($normalized), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	return is_string($json) ? $json : null;
}

function stringifyDomain(array $domain)
{
	$description = cleanNullable(isset($domain['description']) ? $domain['description'] : null);
	$policies = cleanNullable(isset($domain['policies']) ? $domain['policies'] : null);

	if ($description === null && $policies === null) {
		return null;
	}
	if ($description !== null && $policies !== null) {
		return $description . "\nPolitiques: " . $policies;
	}

	return $description !== null ? $description : $policies;
}

function buildContentValueMap(array $content)
{
	$attendus = array();
	$redevabilities = isset($content['redevabilities']) ? $content['redevabilities'] : array();
	foreach ($redevabilities as $accountability) {
		$description = cleanNullable(isset($accountability['description']) ? $accountability['description'] : null);
		if ($description !== null) {
			$attendus[] = $description;
		}
	}

	$domaines = array();
	$domains = isset($content['domains']) ? $content['domains'] : array();
	foreach ($domains as $domain) {
		$domainValue = stringifyDomain($domain);
		if ($domainValue !== null) {
			$domaines[] = $domainValue;
		}
	}

	return array(
		OMO2_PROPERTY_RAISON_ETRE => cleanNullable(isset($content['raison_etre']) ? $content['raison_etre'] : null),
		OMO2_PROPERTY_ATTENDUS => buildListValue($attendus),
		OMO2_PROPERTY_DOMAINES => buildListValue($domaines),
		OMO2_PROPERTY_STRATEGIE => cleanNullable(isset($content['strategy']) ? $content['strategy'] : null),
	);
}

function buildTemplateSchemaRows()
{
	return array(
		array('propertyId' => OMO2_PROPERTY_RAISON_ETRE, 'position' => 1),
		array('propertyId' => OMO2_PROPERTY_ATTENDUS, 'position' => 2),
		array('propertyId' => OMO2_PROPERTY_DOMAINES, 'position' => 3),
		array('propertyId' => OMO2_PROPERTY_STRATEGIE, 'position' => 4),
	);
}

function buildContentPropertyRows(array $content, $baselineContent)
{
	$values = buildContentValueMap($content);
	$baselineValues = is_array($baselineContent) ? buildContentValueMap($baselineContent) : array();
	$rows = array();

	$positions = array(
		OMO2_PROPERTY_RAISON_ETRE => 1,
		OMO2_PROPERTY_ATTENDUS => 2,
		OMO2_PROPERTY_DOMAINES => 3,
		OMO2_PROPERTY_STRATEGIE => 4,
	);

	foreach ($positions as $propertyId => $position) {
		$value = isset($values[$propertyId]) ? $values[$propertyId] : null;
		$baselineValue = isset($baselineValues[$propertyId]) ? $baselineValues[$propertyId] : null;
		if ($value === null || $value === '' || $value === $baselineValue) {
			continue;
		}

		$rows[] = array(
			'propertyId' => $propertyId,
			'position' => $position,
			'value' => $value,
		);
	}

	return $rows;
}

function roleIsCircle(array $role, array $roleIdsWithChildren)
{
	$roleId = isset($role['role_id']) ? (int)$role['role_id'] : 0;
	if (inferStructuralTemplateKey(isset($role['role_name']) ? $role['role_name'] : null) !== null) {
		return false;
	}

	return $roleId > 0 && isset($roleIdsWithChildren[$roleId]);
}

function resolveProvisionalParentRoleId(array $role, array $rolesById)
{
	$parentRoleId = toNullableParentRoleId(isset($role['role_id_superCircle']) ? $role['role_id_superCircle'] : null);
	if ($parentRoleId !== null && isset($rolesById[$parentRoleId])) {
		return $parentRoleId;
	}

	return null;
}

function resolveParentRoleId(array $role, array $provisionalParentRoleIds)
{
	$roleId = isset($role['role_id']) ? (int)$role['role_id'] : 0;
	if ($roleId <= 0) {
		return null;
	}

	return isset($provisionalParentRoleIds[$roleId]) ? $provisionalParentRoleIds[$roleId] : null;
}

function buildRecordSortName(array $record)
{
	return normalizeLabel(isset($record['name']) ? $record['name'] : '');
}

function buildTemplateRecord($id, $name, $typeId, $templateId, array $properties, array $overrides)
{
	$record = array(
		'id' => (int)$id,
		'typeId' => (int)$typeId,
		'name' => (string)$name,
		'templateName' => (string)$name,
		'parentId' => OMO2_ROOT_HOLON_ID,
		'visible' => false,
		'_sortWeight' => 5,
		'_sortOrder' => 0,
		'_sortName' => buildRecordSortName(array('name' => $name)),
	);

	if ($templateId !== null) {
		$record['templateId'] = (int)$templateId;
	}
	if ($properties !== array()) {
		$record['properties'] = $properties;
	}
	foreach ($overrides as $key => $value) {
		$record[$key] = $value;
	}

	return $record;
}

function buildRealHolonRecord($exportId, $parentId, $typeId, $name, $templateId, array $properties, array $source, $sortWeight, array $overrides)
{
	$record = array(
		'id' => (int)$exportId,
		'typeId' => (int)$typeId,
		'name' => (string)$name,
		'parentId' => (int)$parentId,
		'visible' => true,
		'sourceRoleId' => (int)$source['role_id'],
		'_sortWeight' => (int)$sortWeight,
		'_sortOrder' => 0,
		'_sortGroupName' => '',
		'_sortName' => buildRecordSortName(array('name' => $name)),
	);

	if ($templateId !== null) {
		$record['templateId'] = (int)$templateId;
	}
	if ($properties !== array()) {
		$record['properties'] = $properties;
	}
	foreach ($overrides as $key => $value) {
		$record[$key] = $value;
	}

	return $record;
}

function buildGroupRecord($exportId, $parentId, $name, $sortOrder)
{
	return array(
		'id' => (int)$exportId,
		'typeId' => 3,
		'name' => (string)$name,
		'parentId' => (int)$parentId,
		'visible' => true,
		'templateId' => OMO2_GROUP_TEMPLATE_ID,
		'_sortWeight' => 15,
		'_sortOrder' => (int)$sortOrder,
		'_sortGroupName' => '',
		'_sortName' => buildRecordSortName(array('name' => $name)),
	);
}

function buildHolonTree(array $recordsById)
{
	$childrenByParentId = array();
	foreach ($recordsById as $recordId => $record) {
		$parentId = isset($record['parentId']) ? (int)$record['parentId'] : 0;
		if ($parentId <= 0 || !isset($recordsById[$parentId])) {
			continue;
		}
		$childrenByParentId[$parentId][] = (int)$recordId;
	}

	foreach ($childrenByParentId as $parentId => $childIds) {
		usort($childIds, function ($leftId, $rightId) use ($recordsById) {
			$left = $recordsById[$leftId];
			$right = $recordsById[$rightId];
			$leftWeight = isset($left['_sortWeight']) ? (int)$left['_sortWeight'] : 99;
			$rightWeight = isset($right['_sortWeight']) ? (int)$right['_sortWeight'] : 99;
			if ($leftWeight === $rightWeight) {
				$leftOrder = isset($left['_sortOrder']) ? (int)$left['_sortOrder'] : 0;
				$rightOrder = isset($right['_sortOrder']) ? (int)$right['_sortOrder'] : 0;
				if ($leftOrder !== $rightOrder) {
					return $leftOrder < $rightOrder ? -1 : 1;
				}

				$leftGroupName = isset($left['_sortGroupName']) ? (string)$left['_sortGroupName'] : '';
				$rightGroupName = isset($right['_sortGroupName']) ? (string)$right['_sortGroupName'] : '';
				if ($leftGroupName !== $rightGroupName) {
					return strcmp($leftGroupName, $rightGroupName);
				}

				$leftSortName = isset($left['_sortName']) ? (string)$left['_sortName'] : '';
				$rightSortName = isset($right['_sortName']) ? (string)$right['_sortName'] : '';
				return strcmp($leftSortName, $rightSortName);
			}

			return $leftWeight < $rightWeight ? -1 : 1;
		});

		$childrenByParentId[$parentId] = $childIds;
	}

	$buildNode = null;
	$buildNode = function ($recordId) use (&$buildNode, $recordsById, $childrenByParentId) {
		$node = $recordsById[$recordId];
		unset($node['_sortWeight'], $node['_sortOrder'], $node['_sortGroupName'], $node['_sortName'], $node['parentId']);

		if (isset($childrenByParentId[$recordId])) {
			$children = array();
			foreach ($childrenByParentId[$recordId] as $childId) {
				$children[] = $buildNode($childId);
			}
			if ($children !== array()) {
				$node['children'] = $children;
			}
		}

		return $node;
	};

	return array($buildNode(OMO2_ROOT_HOLON_ID));
}

function countVisibleRecords(array $recordsById)
{
	$count = 0;
	foreach ($recordsById as $record) {
		if (!array_key_exists('visible', $record) || (bool)$record['visible']) {
			$count += 1;
		}
	}

	return $count;
}

function buildCompatibleExport(PDO $pdo, $organisationId, $host, $database)
{
	$organisationStatement = $pdo->prepare(
		'SELECT *
		 FROM t_organisation
		 WHERE orga_id = ?'
	);
	$organisationStatement->execute(array((int)$organisationId));
	$organisation = $organisationStatement->fetch();
	if (!$organisation) {
		throw new RuntimeException("Aucune organisation trouvee pour l'identifiant {$organisationId}.");
	}

	$roleTypes = $pdo->query(
		'SELECT roty_id, roty_label, roty_isElectedRole, roty_canBeCircle, roty_defaultPurpose, roty_defaultScope
		 FROM to_roletype
		 ORDER BY roty_id'
	)->fetchAll();
	$roleTypesById = array();
	foreach ($roleTypes as $roleType) {
		$roleTypesById[(int)$roleType['roty_id']] = $roleType;
	}

	$currentRoles = fetchReachableRoles($pdo, $organisationId);
	if ($currentRoles === array()) {
		throw new RuntimeException("Cette organisation OMO 1 ne contient aucun role exportable.");
	}

	$rolesById = array();
	foreach ($currentRoles as $role) {
		$rolesById[(int)$role['role_id']] = $role;
	}

	$provisionalParentRoleIds = array();
	$roleIdsWithChildren = array();
	foreach ($currentRoles as $role) {
		$roleId = (int)$role['role_id'];
		$parentRoleId = resolveProvisionalParentRoleId($role, $rolesById);
		if ($parentRoleId === $roleId) {
			$parentRoleId = null;
		}
		$provisionalParentRoleIds[$roleId] = $parentRoleId;
		if ($parentRoleId !== null) {
			$roleIdsWithChildren[$parentRoleId] = true;
		}
	}

	$allRoleIds = array_keys($rolesById);
	$accountabilitiesByRole = groupRowsByKey(
		fetchRowsByIds(
			$pdo,
			'SELECT acco_id, acco_description, roty_id, role_id, acco_active
			 FROM t_accountability
			 WHERE role_id IN (:ids)
			 ORDER BY role_id, acco_id',
			$allRoleIds
		),
		'role_id'
	);
	$domainsByRole = groupRowsByKey(
		fetchRowsByIds(
			$pdo,
			'SELECT scop_id, scop_description, role_id, scop_politiques, scop_id_parent
			 FROM t_scope
			 WHERE role_id IN (:ids)
			 ORDER BY role_id, scop_id',
			$allRoleIds
		),
		'role_id'
	);

	$roleContentsById = array();
	foreach ($rolesById as $roleId => $role) {
		$roleContentsById[$roleId] = buildRoleContent(
			$role,
			isset($accountabilitiesByRole[$roleId]) ? $accountabilitiesByRole[$roleId] : array(),
			isset($domainsByRole[$roleId]) ? $domainsByRole[$roleId] : array()
		);
	}

	$structuralTemplates = buildStructuralTemplates($rolesById, $roleContentsById);
	$organisationData = simplifyOrganisation($organisation);
	$organizationName = isset($organisationData['name']) && $organisationData['name'] !== null ? $organisationData['name'] : ('Organisation ' . $organisationId);
	$organizationShortName = isset($organisationData['short_name']) && $organisationData['short_name'] !== null ? $organisationData['short_name'] : $organizationName;

	$recordsById = array();
	$recordsById[OMO2_ROOT_HOLON_ID] = array(
		'id' => OMO2_ROOT_HOLON_ID,
		'typeId' => 4,
		'name' => $organizationName,
		'color' => OMO2_DEFAULT_ORGANIZATION_COLOR,
		'_sortWeight' => 0,
		'_sortOrder' => 0,
		'_sortName' => buildRecordSortName(array('name' => $organizationName)),
	);

	$recordsById[OMO2_ROLE_TEMPLATE_ID] = buildTemplateRecord(OMO2_ROLE_TEMPLATE_ID, 'Roles', 1, null, buildTemplateSchemaRows(), array());
	$recordsById[OMO2_CIRCLE_TEMPLATE_ID] = buildTemplateRecord(OMO2_CIRCLE_TEMPLATE_ID, 'Cercles', 2, null, buildTemplateSchemaRows(), array());
	$recordsById[OMO2_GROUP_TEMPLATE_ID] = buildTemplateRecord(OMO2_GROUP_TEMPLATE_ID, 'Template groupe', 3, null, array(), array());

	foreach (structuralTemplateDefinitions() as $templateKey => $templateDefinition) {
		$templateContent = isset($structuralTemplates[$templateKey]['content']) ? $structuralTemplates[$templateKey]['content'] : array(
			'raison_etre' => null,
			'strategy' => null,
			'redevabilities' => array(),
			'domains' => array(),
		);
		$templateLabel = isset($structuralTemplates[$templateKey]['label']) ? $structuralTemplates[$templateKey]['label'] : $templateDefinition['label'];
		$templateColor = isset($structuralTemplates[$templateKey]['color']) ? $structuralTemplates[$templateKey]['color'] : (isset($templateDefinition['color']) ? $templateDefinition['color'] : '');

		$recordsById[(int)$templateDefinition['id']] = buildTemplateRecord(
			(int)$templateDefinition['id'],
			$templateLabel,
			1,
			OMO2_ROLE_TEMPLATE_ID,
			buildContentPropertyRows($templateContent, null),
			array(
				'mandatory' => true,
				'unique' => true,
				'link' => (bool)$templateDefinition['link'],
				'color' => (string)$templateColor,
			)
		);
	}

	$exportIdByRoleId = array();
	foreach ($currentRoles as $role) {
		$exportIdByRoleId[(int)$role['role_id']] = OMO2_REAL_HOLON_ID_OFFSET + (int)$role['role_id'];
	}

	$groupExportIdByKey = array();
	$nextGroupExportId = OMO2_GROUP_HOLON_ID_OFFSET;
	foreach ($currentRoles as $roleIndex => $role) {
		$roleId = (int)$role['role_id'];
		$exportId = $exportIdByRoleId[$roleId];
		$isCircle = roleIsCircle($role, $roleIdsWithChildren);
		$typeId = $isCircle ? 2 : 1;
		$parentRoleId = resolveParentRoleId($role, $provisionalParentRoleIds);
		$containerParentId = ($parentRoleId !== null && isset($exportIdByRoleId[$parentRoleId])) ? $exportIdByRoleId[$parentRoleId] : OMO2_ROOT_HOLON_ID;

		$groupLabel = !$isCircle ? cleanNullable(isset($role['role_group']) ? $role['role_group'] : null) : null;
		$parentId = $containerParentId;
		if ($groupLabel !== null) {
			$groupKey = $containerParentId . '|' . normalizeLabel($groupLabel);
			if (!isset($groupExportIdByKey[$groupKey])) {
				$groupExportIdByKey[$groupKey] = $nextGroupExportId;
				$recordsById[$nextGroupExportId] = buildGroupRecord($nextGroupExportId, $containerParentId, $groupLabel, ($roleIndex + 1) * 10);
				$nextGroupExportId += 1;
			}
			$parentId = $groupExportIdByKey[$groupKey];
		}

		$resolved = resolveRoleContent($role, $roleContentsById[$roleId], $roleContentsById, $structuralTemplates);
		$resolvedContent = $resolved['content'];
		$templateKey = !$isCircle && isset($resolved['structural_template_key']) ? $resolved['structural_template_key'] : null;
		$templateId = $isCircle ? OMO2_CIRCLE_TEMPLATE_ID : OMO2_ROLE_TEMPLATE_ID;
		$templateBaselineContent = null;
		if ($templateKey !== null && isset($structuralTemplates[$templateKey])) {
			$templateId = (int)$structuralTemplates[$templateKey]['template_id'];
			$templateBaselineContent = $structuralTemplates[$templateKey]['content'];
		}

		$properties = buildContentPropertyRows($resolvedContent, $templateBaselineContent);
		$sortWeight = $isCircle ? 10 : ($templateKey !== null ? 20 : 30);
		$overrides = array(
			'_sortOrder' => ($roleIndex + 1) * 10,
		);
		if ($templateKey !== null && !empty($structuralTemplates[$templateKey]['link'])) {
			$overrides['link'] = true;
		}
		if ($groupLabel !== null) {
			$overrides['groupName'] = $groupLabel;
			$overrides['_sortGroupName'] = normalizeLabel($groupLabel);
		}

		$recordsById[$exportId] = buildRealHolonRecord(
			$exportId,
			$parentId,
			$typeId,
			(string)($role['role_name'] ? $role['role_name'] : ('Role ' . $roleId)),
			$templateId,
			$properties,
			$role,
			$sortWeight,
			$overrides
		);
	}

	$holonTree = buildHolonTree($recordsById);

	return array(
		'format' => OMO2_EXPORT_FORMAT,
		'version' => OMO2_EXPORT_VERSION,
		'exportedAt' => gmdate('c'),
		'scope' => array(
			'organizationId' => (int)$organisationId,
			'organizationName' => $organizationName,
			'organizationRootHolonId' => OMO2_ROOT_HOLON_ID,
			'navigationRootHolonId' => OMO2_ROOT_HOLON_ID,
			'exportRootHolonId' => OMO2_ROOT_HOLON_ID,
			'exportRootHolonName' => $organizationName,
			'holonCount' => countVisibleRecords($recordsById),
		),
		'organization' => array(
			'sourceId' => (int)$organisationId,
			'name' => $organizationName,
			'shortname' => $organizationShortName,
			'color' => OMO2_DEFAULT_ORGANIZATION_COLOR,
		),
		'source' => array(
			'system' => 'omo1',
			'host' => $host,
			'database' => $database,
			'organisation' => $organisationData,
			'roleTypes' => array_values(array_map('simplifyRoleType', $roleTypes)),
		),
		'holons' => $holonTree,
		'propertyDefinitions' => buildOmo2PropertyDefinitions(),
	);
}

$organisationId = isset($_GET['org']) && ctype_digit((string)$_GET['org']) ? (int)$_GET['org'] : 0;
if ($organisationId <= 0) {
	http_response_code(400);
	echo "Organisation invalide.";
	exit;
}

$organization = $_SESSION['currentManager']->loadOrganisation($organisationId);
if (!$organization) {
	http_response_code(404);
	echo "Organisation introuvable.";
	exit;
}

$pdoConfig = buildPdoFromLocalConfig();
$pdo = $pdoConfig[0];
$databaseHost = $pdoConfig[1];
$databaseName = $pdoConfig[2];

$payload = buildCompatibleExport($pdo, $organisationId, $databaseHost, $databaseName);
$payload = normalizeValueForJson($payload);
$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
	http_response_code(500);
	echo "Impossible de serialiser le JSON d'export (json_last_error=" . json_last_error() . ").";
	exit;
}

$fileName = 'omo1_to_omo2_v4_orga_' . $organisationId . '_' . gmdate('Ymd_His') . '.json';
header('Content-Type: application/json; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . strlen($json));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

echo $json;
exit;
