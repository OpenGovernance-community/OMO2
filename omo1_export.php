<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

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
        'facilitation' => [
            'label' => 'Facilitation',
            'aliases' => ['facilitation', 'role facilitation'],
        ],
        'memory' => [
            'label' => 'Memoire / secretaire',
            'aliases' => ['memoire', 'role memoire', 'secretaire', 'role secretaire'],
        ],
        'first_link' => [
            'label' => 'Premier lien / pilotage',
            'aliases' => ['lien pilotage', 'pilotage', 'premier lien', '1er lien'],
        ],
        'second_link' => [
            'label' => 'Second lien / representation',
            'aliases' => ['representation', 'representant', 'second lien', '2nd lien', 'deuxieme lien'],
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
        'description' => cleanNullable($organisation['orga_description']),
        'purpose' => cleanNullable($organisation['orga_purpose']),
        'purpose_text' => cleanNullable($organisation['orga_purposetxt']),
        'website' => cleanNullable($organisation['orga_website']),
        'mission' => cleanNullable($organisation['orga_mission']),
        'mission_text' => cleanNullable($organisation['orga_missiontxt']),
        'vision' => cleanNullable($organisation['orga_vision']),
        'vision_text' => cleanNullable($organisation['orga_visiontxt']),
        'public' => (bool) $organisation['orga_public'],
        'active' => (bool) $organisation['orga_active'],
        'type' => toNullableInt($organisation['orga_type']),
        'holarchy_option' => (bool) $organisation['orga_opt_holarchy'],
        'created_at' => cleanNullable($organisation['orga_datecreation']),
    ];
}

function simplifyIdentity(array $row): array
{
    return [
        'user_id' => (int) $row['user_id'],
        'first_name' => cleanNullable($row['user_firstName'] ?? null),
        'last_name' => cleanNullable($row['user_lastName'] ?? null),
        'email' => cleanNullable($row['user_email'] ?? null),
        'username' => cleanNullable($row['user_userName'] ?? null),
        'lang' => cleanNullable($row['user_lang'] ?? null),
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

function simplifyRoleMetadata(array $role): array
{
    return [
        'group' => cleanNullable($role['role_group']),
        'active' => (bool) $role['role_active'],
        'project_id' => toNullableInt($role['proj_id']),
        'created_by_user_id' => toNullableInt($role['user_id']),
    ];
}

function buildHolonChildSummary(array $holon): array
{
    return [
        'id' => (int) $holon['source']['id'],
        'name' => $holon['name'],
        'kind' => $holon['kind'],
        'role_type' => $holon['role_type'],
    ];
}

function addChildRelation(array &$childIdsByParentId, ?int $parentId, int $childId): void
{
    if ($parentId === null || $parentId <= 0 || $parentId === $childId) {
        return;
    }

    if (!isset($childIdsByParentId[$parentId])) {
        $childIdsByParentId[$parentId] = [];
    }

    $childIdsByParentId[$parentId][$childId] = true;
}

function buildRoleContent(array $role, array $accountabilities, array $domains): array
{
    return [
        'raison_etre' => cleanNullable($role['role_purpose']),
        'strategy' => cleanNullable($role['role_strategy']),
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
        if (! isset($matches[$templateKey])) {
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
            'label' => $definition['label'],
            'source_role' => [
                'role_id' => (int) $bestRole['role_id'],
                'name' => cleanNullable($bestRole['role_name']),
                'orga_id' => toNullableInt($bestRole['orga_id']),
            ],
            'matching_role_ids' => array_values(array_unique(array_map('intval', $matches[$templateKey]))),
            'matching_role_names' => array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn(int $roleId): ?string => cleanNullable($rolesById[$roleId]['role_name'] ?? null),
                            $matches[$templateKey]
                        )
                    )
                )
            ),
            'content' => $roleContentsById[$bestRoleId],
        ];
    }

    return $templates;
}

function buildFieldSource(string $type, array $data): array
{
    return ['type' => $type] + $data;
}

function collectReferencedRoleIds(array $role): array
{
    $roleIds = [];

    foreach (['role_id_master', 'role_id_source', 'role_id_target'] as $field) {
        if ($role[$field] !== null) {
            $roleIds[] = (int) $role[$field];
        }
    }

    return array_values(array_unique(array_filter($roleIds)));
}

function resolveRoleContent(
    array $role,
    array $localContent,
    array $rolesById,
    array $roleContentsById,
    array $structuralTemplates
): array {
    $roleId = (int) $role['role_id'];
    $candidateSources = [];

    foreach (collectReferencedRoleIds($role) as $referenceRoleId) {
        if (! isset($roleContentsById[$referenceRoleId])) {
            continue;
        }

        $candidateSources[] = [
            'meta' => buildFieldSource(
                'role',
                [
                    'role_id' => $referenceRoleId,
                    'name' => cleanNullable($rolesById[$referenceRoleId]['role_name'] ?? null),
                ]
            ),
            'content' => $roleContentsById[$referenceRoleId],
        ];
    }

    $templateKey = inferStructuralTemplateKey($role['role_name'] ?? null);
    if ($templateKey !== null && isset($structuralTemplates[$templateKey])) {
        $candidateSources[] = [
            'meta' => buildFieldSource(
                'template',
                [
                    'template_key' => $templateKey,
                    'label' => $structuralTemplates[$templateKey]['label'],
                    'source_role_id' => $structuralTemplates[$templateKey]['source_role']['role_id'],
                ]
            ),
            'content' => $structuralTemplates[$templateKey]['content'],
        ];
    }

    $resolvedContent = $localContent;
    $fieldSources = [
        'raison_etre' => $localContent['raison_etre'] !== null
            ? buildFieldSource('local', ['role_id' => $roleId])
            : null,
        'strategy' => $localContent['strategy'] !== null
            ? buildFieldSource('local', ['role_id' => $roleId])
            : null,
        'redevabilities' => $localContent['redevabilities'] !== []
            ? buildFieldSource('local', ['role_id' => $roleId])
            : null,
        'domains' => $localContent['domains'] !== []
            ? buildFieldSource('local', ['role_id' => $roleId])
            : null,
    ];

    foreach ($candidateSources as $candidateSource) {
        $candidateContent = $candidateSource['content'];

        if ($resolvedContent['raison_etre'] === null && $candidateContent['raison_etre'] !== null) {
            $resolvedContent['raison_etre'] = $candidateContent['raison_etre'];
            $fieldSources['raison_etre'] = $candidateSource['meta'];
        }

        if ($resolvedContent['strategy'] === null && $candidateContent['strategy'] !== null) {
            $resolvedContent['strategy'] = $candidateContent['strategy'];
            $fieldSources['strategy'] = $candidateSource['meta'];
        }

        if ($resolvedContent['redevabilities'] === [] && $candidateContent['redevabilities'] !== []) {
            $resolvedContent['redevabilities'] = $candidateContent['redevabilities'];
            $fieldSources['redevabilities'] = $candidateSource['meta'];
        }

        if ($resolvedContent['domains'] === [] && $candidateContent['domains'] !== []) {
            $resolvedContent['domains'] = $candidateContent['domains'];
            $fieldSources['domains'] = $candidateSource['meta'];
        }
    }

    return [
        'structural_template_key' => $templateKey,
        'candidate_sources' => array_map(static fn(array $source): array => $source['meta'], $candidateSources),
        'content' => $resolvedContent,
        'field_sources' => $fieldSources,
    ];
}

function buildExport(PDO $pdo, int $organisationId, string $host, string $database): array
{
    $organisationStatement = $pdo->prepare(
        'SELECT *
         FROM t_organisation
         WHERE orga_id = ?'
    );
    $organisationStatement->execute([$organisationId]);
    $organisation = $organisationStatement->fetch();

    if (! $organisation) {
        throw new RuntimeException("Aucune organisation trouvee pour l'identifiant {$organisationId}.");
    }

    $roleTypes = $pdo->query(
        'SELECT roty_id, roty_label, roty_isElectedRole, roty_canBeCircle, roty_defaultPurpose, roty_defaultScope
         FROM to_roletype
         ORDER BY roty_id'
    )->fetchAll();
    $roleTypesById = [];
    foreach ($roleTypes as $roleType) {
        $roleTypesById[$roleType['roty_id']] = $roleType;
    }

    $organisationMembersStatement = $pdo->prepare(
        'SELECT
            om.user_id,
            u.user_firstName,
            u.user_lastName,
            u.user_email,
            u.user_userName,
            u.user_lang
         FROM t_organisationmember om
         LEFT JOIN t_user u ON u.user_id = om.user_id
         WHERE om.orga_id = ?
         ORDER BY om.user_id'
    );
    $organisationMembersStatement->execute([$organisationId]);
    $organisationMembers = array_map('simplifyIdentity', $organisationMembersStatement->fetchAll());

    $rolesStatement = $pdo->prepare(
        'SELECT *
         FROM t_role
         WHERE orga_id = ?
         ORDER BY COALESCE(role_id_superCircle, 0), role_id'
    );
    $rolesStatement->execute([$organisationId]);
    $currentRoles = $rolesStatement->fetchAll();

    $currentRoleIds = array_values(array_unique(array_map(static fn(array $row): int => (int) $row['role_id'], $currentRoles)));
    $referencedRoleIds = [];
    foreach ($currentRoles as $role) {
        $referencedRoleIds = array_merge($referencedRoleIds, collectReferencedRoleIds($role));
    }

    $missingReferencedRoleIds = array_values(array_diff(array_unique($referencedRoleIds), $currentRoleIds));
    $referencedRoles = fetchRowsByIds(
        $pdo,
        'SELECT *
         FROM t_role
         WHERE role_id IN (:ids)',
        $missingReferencedRoleIds
    );

    $allRoles = array_merge($currentRoles, $referencedRoles);
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

    $circleMembersByRole = groupRowsByKey(
        fetchRowsByIds(
            $pdo,
            'SELECT
                cm.role_id,
                cm.user_id,
                u.user_firstName,
                u.user_lastName,
                u.user_email,
                u.user_userName,
                u.user_lang
             FROM t_circlemember cm
             LEFT JOIN t_user u ON u.user_id = cm.user_id
             WHERE cm.role_id IN (:ids)
             ORDER BY cm.role_id, cm.user_id',
            $currentRoleIds
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

    $holonsById = [];
    foreach ($currentRoles as $role) {
        $roleId = (int) $role['role_id'];
        $roleType = $roleTypesById[$role['roty_id']] ?? null;
        $localContent = $roleContentsById[$roleId];

        $holonsById[$roleId] = [
            'source' => [
                'table' => 't_role',
                'id' => $roleId,
            ],
            'name' => cleanNullable($role['role_name']),
            'kind' => ($roleType && (int) $roleType['roty_canBeCircle'] === 1) ? 'circle' : 'role',
            'role_type' => simplifyRoleType($roleType),
            'data' => simplifyRoleMetadata($role),
            'hierarchy' => [
                'parent_role_id' => toNullableParentRoleId($role['role_id_superCircle']),
                'source_circle_id' => toNullableInt($role['circ_id_source']),
                'source_role_id' => toNullableInt($role['role_id_source']),
                'target_role_id' => toNullableInt($role['role_id_target']),
                'master_role_id' => toNullableInt($role['role_id_master']),
            ],
            'content' => $localContent,
            'resolved_content' => resolveRoleContent(
                $role,
                $localContent,
                $rolesById,
                $roleContentsById,
                $structuralTemplates
            ),
            'members' => array_map('simplifyIdentity', $circleMembersByRole[$roleId] ?? []),
            'children' => [],
        ];
    }

    $childIdsByParentId = [];
    foreach ($holonsById as $holonId => $holon) {
        addChildRelation($childIdsByParentId, $holon['hierarchy']['parent_role_id'], $holonId);

        // Dans OMO 1, les roles d'un cercle sont souvent relies via role_id_source
        // plutot que via role_id_superCircle.
        addChildRelation($childIdsByParentId, $holon['hierarchy']['source_role_id'], $holonId);
    }

    foreach ($holonsById as $holonId => &$holon) {
        $childHolons = [];
        foreach (array_keys($childIdsByParentId[$holonId] ?? []) as $childId) {
            if (!isset($holonsById[$childId])) {
                continue;
            }

            $childHolons[] = buildHolonChildSummary($holonsById[$childId]);
        }

        usort(
            $childHolons,
            static function (array $left, array $right): int {
                return strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
            }
        );

        $holon['children'] = $childHolons;
        $holon['child_holon_ids'] = array_map(
            static fn(array $childHolon): int => (int) $childHolon['id'],
            $childHolons
        );
    }
    unset($holon);

    return [
        'meta' => [
            'schema' => 'omo1-export-v3',
            'exported_at' => gmdate('c'),
            'source' => [
                'type' => 'mysql',
                'host' => $host,
                'database' => $database,
                'organisation_id' => $organisationId,
            ],
            'notes' => [
                'Export OMO 1 simplifie pour migration via JSON intermediaire.',
                'Les parametres d organisation et de cercle ont ete retires.',
                'Les raisons d etre, redevabilites et domaines sont conserves en detail.',
                'Les enfants combinent les liens structurels role_id_superCircle et les liens de composition role_id_source.',
                'Des templates structurels sont inferes quand possible, avec tentative de resolution par heritage.',
            ],
        ],
        'reference' => [
            'role_types' => array_map('simplifyRoleType', $roleTypes),
        ],
        'organisation' => [
            'source' => [
                'table' => 't_organisation',
                'id' => $organisationId,
            ],
            'data' => simplifyOrganisation($organisation),
            'members' => $organisationMembers,
        ],
        'holon_templates' => array_values($structuralTemplates),
        'holons' => array_values($holonsById),
    ];
}

$error = null;
$defaults = [
    'db_host' => (string)($GLOBALS['dbServer'] ?? ''),
    'db_name' => (string)($GLOBALS['dbName'] ?? ''),
    'db_user' => (string)($GLOBALS['dbUser'] ?? ''),
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

        if (! ctype_digit($defaults['organisation_id'])) {
            throw new RuntimeException("L'identifiant de l'organisation doit etre un entier positif.");
        }

        $organisationId = (int) $defaults['organisation_id'];
        $pdo = buildPdo(
            $defaults['db_host'],
            $defaults['db_name'],
            $defaults['db_user'],
            $defaults['db_password']
        );

        $payload = buildExport($pdo, $organisationId, $defaults['db_host'], $defaults['db_name']);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException("Impossible de serialiser le JSON d'export.");
        }

        $fileName = 'omo1_export_orga_' . $organisationId . '_' . gmdate('Ymd_His') . '.json';

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
    <title>Export OMO 1 vers JSON</title>
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
                <h1>Export OMO 1 vers JSON</h1>
                <p class="lead">
                    Cet outil se connecte directement a une base MySQL OMO 1,
                    exporte une organisation et telecharge un JSON recentre sur
                    les donnees de structure, les holons, les templates structurels
                    inferes et le contenu resolu par heritage quand c'est possible.
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
                        <input type="number" min="1" step="1" id="organisation_id" name="organisation_id" value="<?= h($defaults['organisation_id']) ?>" placeholder="ex: 12" required>
                    </div>
                </div>

                <div class="hint">
                    Les parametres d'organisation et de cercle ne sont plus exportes.
                    Le JSON conserve le detail des raisons d'etre, redevabilites et domaines,
                    ajoute des templates structurels inferes et tente de remplir le contenu
                    manquant via les references d'heritage detectees.
                </div>

                <div class="actions">
                    <button type="submit">Generer et telecharger le JSON</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
