<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

const OMO2_EXPORT_FORMAT = 'openmyorganization-structure-export';
const OMO2_EXPORT_VERSION = 4;
const OMO2_ROOT_HOLON_ID = 1;
const OMO2_ROLE_TEMPLATE_ID = 1001;
const OMO2_CIRCLE_TEMPLATE_ID = 1002;
const OMO2_FIRST_LINK_TEMPLATE_ID = 1003;
const OMO2_SECOND_LINK_TEMPLATE_ID = 1004;
const OMO2_FACILITATION_TEMPLATE_ID = 1005;
const OMO2_MEMORY_TEMPLATE_ID = 1006;
const OMO2_REAL_HOLON_ID_OFFSET = 100000;
const OMO2_DEFAULT_ORGANIZATION_COLOR = '#3da8a9';
const OMO2_PROPERTY_RAISON_ETRE = 101;
const OMO2_PROPERTY_ATTENDUS = 102;
const OMO2_PROPERTY_DOMAINES = 103;
const OMO2_PROPERTY_STRATEGIE = 104;

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cleanNullable(mixed $value): ?string
{
    $value = trim((string) $value);

    return $value === '' ? null : $value;
}

function toNullableInt(mixed $value): ?int
{
    return $value === null || $value === '' ? null : (int) $value;
}

function toNullableParentRoleId(mixed $value): ?int
{
    if ($value === null || $value === '' || (int) $value === 0) {
        return null;
    }

    return (int) $value;
}

function buildPdo(string $host, string $database, string $user, string $password): PDO
{
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";

    return new PDO(
        $dsn,
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function fetchRowsByIds(PDO $pdo, string $sql, array $ids): array
{
    if ($ids === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare(str_replace(':ids', $placeholders, $sql));
    $statement->execute($ids);

    return $statement->fetchAll();
}

function groupRowsByKey(array $rows, string $key): array
{
    $grouped = [];

    foreach ($rows as $row) {
        $grouped[$row[$key]][] = $row;
    }

    return $grouped;
}

function normalizeLabel(string $value): string
{
    $value = trim($value);
    $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    $asciiValue = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if (is_string($asciiValue) && $asciiValue !== '') {
        $value = $asciiValue;
    }
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;
    $value = preg_replace('/\s+/', ' ', $value) ?? $value;

    return trim($value);
}

function structuralTemplateDefinitions(): array
{
    return [
        'first_link' => [
            'id' => OMO2_FIRST_LINK_TEMPLATE_ID,
            'label' => 'Premier lien',
            'aliases' => ['premier lien', '1er lien', 'lien pilotage', 'pilotage'],
            'link' => true,
        ],
        'second_link' => [
            'id' => OMO2_SECOND_LINK_TEMPLATE_ID,
            'label' => 'Second lien',
            'aliases' => ['second lien', '2nd lien', 'deuxieme lien', 'lien representation', 'representation', 'representant'],
            'link' => true,
        ],
        'facilitation' => [
            'id' => OMO2_FACILITATION_TEMPLATE_ID,
            'label' => 'Facilitation',
            'aliases' => ['facilitation', 'role facilitation'],
            'link' => false,
        ],
        'memory' => [
            'id' => OMO2_MEMORY_TEMPLATE_ID,
            'label' => 'Memoire',
            'aliases' => ['memoire', 'role memoire', 'secretaire', 'secretaire', 'role secretaire'],
            'link' => false,
        ],
    ];
}

function inferStructuralTemplateKey(?string $roleName): ?string
{
    if ($roleName === null || trim($roleName) === '') {
        return null;
    }

    $normalizedRoleName = normalizeLabel($roleName);

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

function simplifyRoleType(?array $roleType): ?array
{
    if ($roleType === null) {
        return null;
    }

    return [
        'roty_id' => (int) $roleType['roty_id'],
        'label' => cleanNullable($roleType['roty_label']),
        'is_elected_role' => (bool) $roleType['roty_isElectedRole'],
        'can_be_circle' => (bool) $roleType['roty_canBeCircle'],
        'default_purpose' => cleanNullable($roleType['roty_defaultPurpose']),
        'default_scope' => cleanNullable($roleType['roty_defaultScope']),
    ];
}

function simplifyOrganisation(array $organisation): array
{
    return [
        'orga_id' => (int) $organisation['orga_id'],
        'name' => cleanNullable($organisation['orga_name']),
        'short_name' => cleanNullable($organisation['orga_shortname']),
        'description' => cleanNullable($organisation['orga_description'] ?? null),
        'purpose' => cleanNullable($organisation['orga_purpose'] ?? null),
        'mission' => cleanNullable($organisation['orga_mission'] ?? null),
        'vision' => cleanNullable($organisation['orga_vision'] ?? null),
    ];
}

function simplifyAccountability(array $row): array
{
    return [
        'acco_id' => (int) $row['acco_id'],
        'description' => cleanNullable($row['acco_description']),
        'active' => (bool) $row['acco_active'],
    ];
}

function simplifyDomain(array $row): array
{
    return [
        'scop_id' => (int) $row['scop_id'],
        'description' => cleanNullable($row['scop_description']),
        'policies' => cleanNullable($row['scop_politiques']),
        'parent_scope_id' => toNullableInt($row['scop_id_parent']),
    ];
}

function collectReferencedRoleIds(array $role): array
{
    $roleIds = [];

    foreach (['role_id_master', 'role_id_source', 'role_id_target'] as $field) {
        if (($role[$field] ?? null) !== null) {
            $roleIds[] = (int) $role[$field];
        }
    }

    return array_values(array_unique(array_filter($roleIds)));
}

function buildRoleContent(array $role, array $accountabilities, array $domains): array
{
    return [
        'raison_etre' => cleanNullable($role['role_purpose'] ?? null),
        'strategy' => cleanNullable($role['role_strategy'] ?? null),
        'redevabilities' => array_map('simplifyAccountability', $accountabilities),
        'domains' => array_map('simplifyDomain', $domains),
    ];
}

function scoreRoleContent(array $content): int
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

function buildStructuralTemplates(array $rolesById, array $roleContentsById): array
{
    $matches = [];

    foreach ($rolesById as $roleId => $role) {
        $templateKey = inferStructuralTemplateKey($role['role_name'] ?? null);
        if ($templateKey !== null) {
            $matches[$templateKey][] = (int) $roleId;
        }
    }

    $templates = [];
    foreach (structuralTemplateDefinitions() as $templateKey => $definition) {
        if (!isset($matches[$templateKey])) {
            continue;
        }

        $bestRoleId = null;
        $bestScore = -1;
        foreach ($matches[$templateKey] as $candidateRoleId) {
            $score = scoreRoleContent($roleContentsById[$candidateRoleId] ?? [
                'raison_etre' => null,
                'strategy' => null,
                'redevabilities' => [],
                'domains' => [],
            ]);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRoleId = $candidateRoleId;
            }
        }

        if ($bestRoleId === null) {
            continue;
        }

        $bestRole = $rolesById[$bestRoleId];
        $templates[$templateKey] = [
            'template_key' => $templateKey,
            'template_id' => (int) $definition['id'],
            'label' => $definition['label'],
            'link' => (bool) $definition['link'],
            'source_role' => [
                'role_id' => (int) $bestRole['role_id'],
                'name' => cleanNullable($bestRole['role_name']),
            ],
            'content' => $roleContentsById[$bestRoleId],
        ];
    }

    return $templates;
}

function resolveRoleContent(
    array $role,
    array $localContent,
    array $roleContentsById,
    array $structuralTemplates
): array {
    $candidateSources = [];

    foreach (collectReferencedRoleIds($role) as $referenceRoleId) {
        if (!isset($roleContentsById[$referenceRoleId])) {
            continue;
        }

        $candidateSources[] = $roleContentsById[$referenceRoleId];
    }

    $templateKey = inferStructuralTemplateKey($role['role_name'] ?? null);
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

        if ($resolvedContent['redevabilities'] === [] && $candidateSource['redevabilities'] !== []) {
            $resolvedContent['redevabilities'] = $candidateSource['redevabilities'];
        }

        if ($resolvedContent['domains'] === [] && $candidateSource['domains'] !== []) {
            $resolvedContent['domains'] = $candidateSource['domains'];
        }
    }

    return [
        'structural_template_key' => $templateKey,
        'content' => $resolvedContent,
    ];
}

function buildOmo2PropertyDefinitions(): array
{
    return [
        [
            'id' => OMO2_PROPERTY_RAISON_ETRE,
            'name' => "Raison d'etre",
            'shortname' => 'raison_etre',
            'formatId' => 1,
            'position' => 1,
        ],
        [
            'id' => OMO2_PROPERTY_ATTENDUS,
            'name' => 'Attendus',
            'shortname' => 'attendus',
            'formatId' => 2,
            'listItemType' => 'text',
            'position' => 2,
        ],
        [
            'id' => OMO2_PROPERTY_DOMAINES,
            'name' => "Domaines d'autorite",
            'shortname' => 'domaines_autorite',
            'formatId' => 2,
            'listItemType' => 'text',
            'position' => 3,
        ],
        [
            'id' => OMO2_PROPERTY_STRATEGIE,
            'name' => 'Strategie',
            'shortname' => 'strategie',
            'formatId' => 1,
            'position' => 4,
        ],
    ];
}

function buildListValue(array $items): ?string
{
    $normalized = [];

    foreach ($items as $item) {
        $item = trim((string) $item);
        if ($item === '') {
            continue;
        }

        $normalized[] = $item;
    }

    if ($normalized === []) {
        return null;
    }

    $json = json_encode(array_values($normalized), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return is_string($json) ? $json : null;
}

function stringifyDomain(array $domain): ?string
{
    $description = cleanNullable($domain['description'] ?? null);
    $policies = cleanNullable($domain['policies'] ?? null);

    if ($description === null && $policies === null) {
        return null;
    }

    if ($description !== null && $policies !== null) {
        return $description . "\nPolitiques: " . $policies;
    }

    return $description ?? $policies;
}

function buildContentValueMap(array $content): array
{
    $attendus = [];
    foreach (($content['redevabilities'] ?? []) as $accountability) {
        $description = cleanNullable($accountability['description'] ?? null);
        if ($description !== null) {
            $attendus[] = $description;
        }
    }

    $domaines = [];
    foreach (($content['domains'] ?? []) as $domain) {
        $domainValue = stringifyDomain($domain);
        if ($domainValue !== null) {
            $domaines[] = $domainValue;
        }
    }

    return [
        OMO2_PROPERTY_RAISON_ETRE => cleanNullable($content['raison_etre'] ?? null),
        OMO2_PROPERTY_ATTENDUS => buildListValue($attendus),
        OMO2_PROPERTY_DOMAINES => buildListValue($domaines),
        OMO2_PROPERTY_STRATEGIE => cleanNullable($content['strategy'] ?? null),
    ];
}

function buildTemplateSchemaRows(): array
{
    return [
        ['propertyId' => OMO2_PROPERTY_RAISON_ETRE, 'position' => 1],
        ['propertyId' => OMO2_PROPERTY_ATTENDUS, 'position' => 2],
        ['propertyId' => OMO2_PROPERTY_DOMAINES, 'position' => 3],
        ['propertyId' => OMO2_PROPERTY_STRATEGIE, 'position' => 4],
    ];
}

function buildContentPropertyRows(array $content, ?array $baselineContent = null): array
{
    $values = buildContentValueMap($content);
    $baselineValues = $baselineContent !== null ? buildContentValueMap($baselineContent) : [];
    $rows = [];

    foreach ([
        OMO2_PROPERTY_RAISON_ETRE => 1,
        OMO2_PROPERTY_ATTENDUS => 2,
        OMO2_PROPERTY_DOMAINES => 3,
        OMO2_PROPERTY_STRATEGIE => 4,
    ] as $propertyId => $position) {
        $value = $values[$propertyId] ?? null;
        $baselineValue = $baselineValues[$propertyId] ?? null;

        if ($value === null || $value === '' || $value === $baselineValue) {
            continue;
        }

        $rows[] = [
            'propertyId' => $propertyId,
            'position' => $position,
            'value' => $value,
        ];
    }

    return $rows;
}

function roleIsCircle(array $roleTypesById, array $role): bool
{
    $roleTypeId = (int) ($role['roty_id'] ?? 0);
    $roleType = $roleTypesById[$roleTypeId] ?? null;

    return $roleType !== null && (int) ($roleType['roty_canBeCircle'] ?? 0) === 1;
}

function resolveParentRoleId(array $role, array $rolesById, array $roleTypesById): ?int
{
    $parentRoleId = toNullableParentRoleId($role['role_id_superCircle'] ?? null);
    if ($parentRoleId !== null && isset($rolesById[$parentRoleId])) {
        return $parentRoleId;
    }

    $sourceRoleId = toNullableInt($role['role_id_source'] ?? null);
    if ($sourceRoleId !== null && isset($rolesById[$sourceRoleId]) && roleIsCircle($roleTypesById, $rolesById[$sourceRoleId])) {
        return $sourceRoleId;
    }

    return null;
}

function buildRecordSortName(array $record): string
{
    return normalizeLabel((string) ($record['name'] ?? ''));
}

function buildTemplateRecord(
    int $id,
    string $name,
    int $typeId,
    ?int $templateId = null,
    array $properties = [],
    array $overrides = []
): array {
    $record = [
        'id' => $id,
        'typeId' => $typeId,
        'name' => $name,
        'templateName' => $name,
        'parentId' => OMO2_ROOT_HOLON_ID,
        'visible' => false,
        '_sortWeight' => 5,
        '_sortName' => buildRecordSortName(['name' => $name]),
    ];

    if ($templateId !== null) {
        $record['templateId'] = $templateId;
    }

    if ($properties !== []) {
        $record['properties'] = $properties;
    }

    foreach ($overrides as $key => $value) {
        $record[$key] = $value;
    }

    return $record;
}

function buildRealHolonRecord(
    int $exportId,
    int $parentId,
    int $typeId,
    string $name,
    ?int $templateId,
    array $properties,
    array $source,
    int $sortWeight,
    array $overrides = []
): array {
    $record = [
        'id' => $exportId,
        'typeId' => $typeId,
        'name' => $name,
        'parentId' => $parentId,
        'visible' => true,
        'sourceRoleId' => (int) $source['role_id'],
        '_sortWeight' => $sortWeight,
        '_sortGroupName' => '',
        '_sortName' => buildRecordSortName(['name' => $name]),
    ];

    if ($templateId !== null) {
        $record['templateId'] = $templateId;
    }

    if ($properties !== []) {
        $record['properties'] = $properties;
    }

    foreach ($overrides as $key => $value) {
        $record[$key] = $value;
    }

    return $record;
}

function buildHolonTree(array $recordsById): array
{
    $childrenByParentId = [];

    foreach ($recordsById as $recordId => $record) {
        $parentId = (int) ($record['parentId'] ?? 0);
        if ($parentId <= 0 || !isset($recordsById[$parentId])) {
            continue;
        }

        $childrenByParentId[$parentId][] = (int) $recordId;
    }

    foreach ($childrenByParentId as $parentId => $childIds) {
        usort($childIds, static function (int $leftId, int $rightId) use ($recordsById): int {
            $left = $recordsById[$leftId];
            $right = $recordsById[$rightId];
            $leftWeight = (int) ($left['_sortWeight'] ?? 99);
            $rightWeight = (int) ($right['_sortWeight'] ?? 99);
            if ($leftWeight === $rightWeight) {
                $leftGroupName = (string) ($left['_sortGroupName'] ?? '');
                $rightGroupName = (string) ($right['_sortGroupName'] ?? '');
                if ($leftGroupName !== $rightGroupName) {
                    return strcmp($leftGroupName, $rightGroupName);
                }

                return strcmp((string) ($left['_sortName'] ?? ''), (string) ($right['_sortName'] ?? ''));
            }

            return $leftWeight <=> $rightWeight;
        });

        $childrenByParentId[$parentId] = $childIds;
    }

    $buildNode = static function (int $recordId) use (&$buildNode, $recordsById, $childrenByParentId): array {
        $node = $recordsById[$recordId];
        unset($node['_sortWeight'], $node['_sortGroupName'], $node['_sortName'], $node['parentId']);

        if (isset($childrenByParentId[$recordId])) {
            $children = [];
            foreach ($childrenByParentId[$recordId] as $childId) {
                $children[] = $buildNode($childId);
            }

            if ($children !== []) {
                $node['children'] = $children;
            }
        }

        return $node;
    };

    return [$buildNode(OMO2_ROOT_HOLON_ID)];
}

function countVisibleRecords(array $recordsById): int
{
    $count = 0;

    foreach ($recordsById as $record) {
        if (!array_key_exists('visible', $record) || (bool) $record['visible']) {
            $count += 1;
        }
    }

    return $count;
}

function buildCompatibleExport(PDO $pdo, int $organisationId, string $host, string $database): array
{
    $organisationStatement = $pdo->prepare(
        'SELECT *
         FROM t_organisation
         WHERE orga_id = ?'
    );
    $organisationStatement->execute([$organisationId]);
    $organisation = $organisationStatement->fetch();

    if (!$organisation) {
        throw new RuntimeException("Aucune organisation trouvee pour l'identifiant {$organisationId}.");
    }

    $roleTypes = $pdo->query(
        'SELECT roty_id, roty_label, roty_isElectedRole, roty_canBeCircle, roty_defaultPurpose, roty_defaultScope
         FROM to_roletype
         ORDER BY roty_id'
    )->fetchAll();
    $roleTypesById = [];
    foreach ($roleTypes as $roleType) {
        $roleTypesById[(int) $roleType['roty_id']] = $roleType;
    }

    $rolesStatement = $pdo->prepare(
        'SELECT *
         FROM t_role
         WHERE orga_id = ?
         ORDER BY COALESCE(role_id_superCircle, 0), role_id'
    );
    $rolesStatement->execute([$organisationId]);
    $currentRoles = $rolesStatement->fetchAll();

    if ($currentRoles === []) {
        throw new RuntimeException("Cette organisation OMO 1 ne contient aucun role exportable.");
    }

    $currentRoleIds = array_values(array_unique(array_map(static fn(array $row): int => (int) $row['role_id'], $currentRoles)));
    $allRoles = $currentRoles;
    $rolesById = [];
    foreach ($allRoles as $role) {
        $rolesById[(int) $role['role_id']] = $role;
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

    $roleContentsById = [];
    foreach ($rolesById as $roleId => $role) {
        $roleContentsById[$roleId] = buildRoleContent(
            $role,
            $accountabilitiesByRole[$roleId] ?? [],
            $domainsByRole[$roleId] ?? []
        );
    }

    $structuralTemplates = buildStructuralTemplates($rolesById, $roleContentsById);
    $organisationData = simplifyOrganisation($organisation);
    $organizationName = $organisationData['name'] ?? ('Organisation ' . $organisationId);
    $organizationShortName = $organisationData['short_name'] ?? $organizationName;

    $recordsById = [];
    $recordsById[OMO2_ROOT_HOLON_ID] = [
        'id' => OMO2_ROOT_HOLON_ID,
        'typeId' => 4,
        'name' => $organizationName,
        'color' => OMO2_DEFAULT_ORGANIZATION_COLOR,
        '_sortWeight' => 0,
        '_sortName' => buildRecordSortName(['name' => $organizationName]),
    ];

    $recordsById[OMO2_ROLE_TEMPLATE_ID] = buildTemplateRecord(
        OMO2_ROLE_TEMPLATE_ID,
        'Template role',
        1,
        null,
        buildTemplateSchemaRows()
    );
    $recordsById[OMO2_CIRCLE_TEMPLATE_ID] = buildTemplateRecord(
        OMO2_CIRCLE_TEMPLATE_ID,
        'Template cercle',
        2,
        null,
        buildTemplateSchemaRows()
    );

    foreach (structuralTemplateDefinitions() as $templateKey => $templateDefinition) {
        $templateContent = $structuralTemplates[$templateKey]['content'] ?? [
            'raison_etre' => null,
            'strategy' => null,
            'redevabilities' => [],
            'domains' => [],
        ];

        $recordsById[(int) $templateDefinition['id']] = buildTemplateRecord(
            (int) $templateDefinition['id'],
            $templateDefinition['label'],
            1,
            OMO2_ROLE_TEMPLATE_ID,
            buildContentPropertyRows($templateContent),
            [
                'mandatory' => true,
                'unique' => true,
                'link' => (bool) $templateDefinition['link'],
            ]
        );
    }

    $exportIdByRoleId = [];
    foreach ($currentRoles as $role) {
        $exportIdByRoleId[(int) $role['role_id']] = OMO2_REAL_HOLON_ID_OFFSET + (int) $role['role_id'];
    }

    foreach ($currentRoles as $role) {
        $roleId = (int) $role['role_id'];
        $exportId = $exportIdByRoleId[$roleId];
        $isCircle = roleIsCircle($roleTypesById, $role);
        $typeId = $isCircle ? 2 : 1;
        $parentRoleId = resolveParentRoleId($role, $rolesById, $roleTypesById);
        $containerParentId = $parentRoleId !== null && isset($exportIdByRoleId[$parentRoleId])
            ? $exportIdByRoleId[$parentRoleId]
            : OMO2_ROOT_HOLON_ID;

        $groupLabel = !$isCircle ? cleanNullable($role['role_group'] ?? null) : null;
        $parentId = $containerParentId;

        $resolved = resolveRoleContent(
            $role,
            $roleContentsById[$roleId],
            $roleContentsById,
            $structuralTemplates
        );
        $resolvedContent = $resolved['content'];
        $templateKey = !$isCircle ? ($resolved['structural_template_key'] ?? null) : null;
        $templateId = $isCircle ? OMO2_CIRCLE_TEMPLATE_ID : OMO2_ROLE_TEMPLATE_ID;
        $templateBaselineContent = null;

        if ($templateKey !== null && isset($structuralTemplates[$templateKey])) {
            $templateId = (int) $structuralTemplates[$templateKey]['template_id'];
            $templateBaselineContent = $structuralTemplates[$templateKey]['content'];
        }

        $properties = buildContentPropertyRows($resolvedContent, $templateBaselineContent);
        $sortWeight = $isCircle
            ? 10
            : ($templateKey !== null ? 20 : 30);
        $overrides = [];
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
            (string) ($role['role_name'] ?: ('Role ' . $roleId)),
            $templateId,
            $properties,
            $role,
            $sortWeight,
            $overrides
        );
    }

    $holonTree = buildHolonTree($recordsById);

    return [
        'format' => OMO2_EXPORT_FORMAT,
        'version' => OMO2_EXPORT_VERSION,
        'exportedAt' => gmdate('c'),
        'scope' => [
            'organizationId' => $organisationId,
            'organizationName' => $organizationName,
            'organizationRootHolonId' => OMO2_ROOT_HOLON_ID,
            'navigationRootHolonId' => OMO2_ROOT_HOLON_ID,
            'exportRootHolonId' => OMO2_ROOT_HOLON_ID,
            'exportRootHolonName' => $organizationName,
            'holonCount' => countVisibleRecords($recordsById),
        ],
        'organization' => [
            'sourceId' => $organisationId,
            'name' => $organizationName,
            'shortname' => $organizationShortName,
            'color' => OMO2_DEFAULT_ORGANIZATION_COLOR,
        ],
        'source' => [
            'system' => 'omo1',
            'host' => $host,
            'database' => $database,
            'organisation' => $organisationData,
            'roleTypes' => array_values(array_map('simplifyRoleType', $roleTypes)),
        ],
        'holons' => $holonTree,
        'propertyDefinitions' => buildOmo2PropertyDefinitions(),
    ];
}

$error = null;
$defaults = [
    'db_host' => (string) ($GLOBALS['dbServer'] ?? ''),
    'db_name' => (string) ($GLOBALS['dbName'] ?? ''),
    'db_user' => (string) ($GLOBALS['dbUser'] ?? ''),
    'db_password' => '',
    'organisation_id' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $defaults = [
        'db_host' => trim((string) ($_POST['db_host'] ?? '')),
        'db_name' => trim((string) ($_POST['db_name'] ?? '')),
        'db_user' => trim((string) ($_POST['db_user'] ?? '')),
        'db_password' => (string) ($_POST['db_password'] ?? ''),
        'organisation_id' => trim((string) ($_POST['organisation_id'] ?? '')),
    ];

    try {
        if (
            $defaults['db_host'] === '' ||
            $defaults['db_name'] === '' ||
            $defaults['db_user'] === '' ||
            $defaults['organisation_id'] === ''
        ) {
            throw new RuntimeException("Merci de remplir le serveur MySQL, la base, le user et l'id de l'organisation.");
        }

        if (!ctype_digit($defaults['organisation_id'])) {
            throw new RuntimeException("L'identifiant de l'organisation doit etre un entier positif.");
        }

        $organisationId = (int) $defaults['organisation_id'];
        $pdo = buildPdo(
            $defaults['db_host'],
            $defaults['db_name'],
            $defaults['db_user'],
            $defaults['db_password']
        );

        $payload = buildCompatibleExport($pdo, $organisationId, $defaults['db_host'], $defaults['db_name']);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException("Impossible de serialiser le JSON d'export.");
        }

        $fileName = 'omo1_to_omo2_orga_' . $organisationId . '_' . gmdate('Ymd_His') . '.json';

        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($json));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        echo $json;
        exit;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Export OMO 1 vers OMO 2</title>
    <style>
        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            background:
                radial-gradient(circle at top left, #f7f1e8 0%, transparent 35%),
                linear-gradient(135deg, #f6f0e7 0%, #e8ded0 100%);
            color: #2f241d;
        }

        .page {
            max-width: 900px;
            margin: 48px auto;
            padding: 0 20px;
        }

        .card {
            background: rgba(255, 252, 247, 0.92);
            border: 1px solid #d8c7b3;
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(82, 56, 34, 0.12);
            overflow: hidden;
        }

        .hero {
            padding: 28px 30px 18px;
            border-bottom: 1px solid #eadbcc;
            background: linear-gradient(135deg, rgba(116, 75, 39, 0.08), rgba(196, 154, 108, 0.12));
        }

        h1 {
            margin: 0 0 10px;
            font-size: 32px;
            line-height: 1.1;
        }

        .lead {
            margin: 0;
            font-size: 17px;
            line-height: 1.5;
            color: #5b4638;
        }

        form {
            padding: 26px 30px 30px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            font-weight: bold;
            font-size: 15px;
        }

        input {
            border: 1px solid #bfa48a;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 15px;
            background: #fffdfa;
            color: #2f241d;
        }

        input:focus {
            outline: none;
            border-color: #8a5a31;
            box-shadow: 0 0 0 3px rgba(138, 90, 49, 0.12);
        }

        .hint {
            margin-top: 20px;
            padding: 15px 16px;
            border-radius: 12px;
            background: #f5ede3;
            color: #5b4638;
            font-size: 14px;
            line-height: 1.5;
        }

        .error {
            margin: 0 30px 20px;
            padding: 14px 16px;
            border-radius: 12px;
            background: #fff0ec;
            border: 1px solid #db9a8a;
            color: #892c1d;
        }

        .actions {
            margin-top: 22px;
            display: flex;
            justify-content: flex-end;
        }

        button {
            border: 0;
            border-radius: 999px;
            padding: 13px 22px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            color: #fffaf5;
            background: linear-gradient(135deg, #6d4424, #9c673e);
            box-shadow: 0 10px 20px rgba(109, 68, 36, 0.18);
        }

        button:hover {
            filter: brightness(1.03);
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="hero">
                <h1>Export OMO 1 vers OMO 2</h1>
                <p class="lead">
                    Cet outil conserve l'interface actuelle, mais genere maintenant
                    un JSON directement compatible avec l'import compact OMO 2 :
                    racine d'organisation, templates a la racine, groupes, cercles,
                    roles structurels et proprietes generiques.
                </p>
            </div>

            <?php if ($error !== null): ?>
                <div class="error"><?= h($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="grid">
                    <div class="field">
                        <label for="db_host">Serveur MySQL</label>
                        <input type="text" id="db_host" name="db_host" value="<?= h($defaults['db_host']) ?>" placeholder="ex: localhost ou ni6tx.myd.infomaniak.com" required>
                    </div>

                    <div class="field">
                        <label for="db_name">Base de donnees</label>
                        <input type="text" id="db_name" name="db_name" value="<?= h($defaults['db_name']) ?>" placeholder="ex: ni6tx_omo" required>
                    </div>

                    <div class="field">
                        <label for="db_user">Utilisateur</label>
                        <input type="text" id="db_user" name="db_user" value="<?= h($defaults['db_user']) ?>" placeholder="ex: root" required>
                    </div>

                    <div class="field">
                        <label for="db_password">Mot de passe</label>
                        <input type="password" id="db_password" name="db_password" value="<?= h($defaults['db_password']) ?>" placeholder="Mot de passe MySQL">
                    </div>

                    <div class="field full">
                        <label for="organisation_id">ID de l'organisation a exporter</label>
                        <input type="number" min="1" step="1" id="organisation_id" name="organisation_id" value="<?= h($defaults['organisation_id']) ?>" placeholder="ex: 22" required>
                    </div>
                </div>

                <div class="hint">
                    Le JSON genere cree a la racine un template de role, un template
                    de cercle et les 4 templates structurels. Les raisons d'etre,
                    attendus, domaines d'autorite et strategies OMO 1 sont convertis
                    en proprietes generiques OMO 2. Les groupes visuels OMO 1 sont
                    conserves comme metadata de regroupement sur les roles, sans
                    recreer une fausse hierarchie de parents.
                </div>

                <div class="actions">
                    <button type="submit">Generer un export compatible OMO 2</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
