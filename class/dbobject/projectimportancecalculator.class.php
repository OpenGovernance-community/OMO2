<?php
namespace dbObject;

class ProjectImportanceCalculator
{
    public const PARAMETERS_KEY = 'importanceCalculation';
    public const DEFAULT_PARENT_WEIGHT = 0.7;
    public const DEFAULT_DEPTH_PENALTY = 0.15;
    public const CALCULATION_VERSION = 3;

    public static function getDefaultConfig(): array
    {
        return [
            'parentWeight' => self::DEFAULT_PARENT_WEIGHT,
            'depthPenalty' => self::DEFAULT_DEPTH_PENALTY,
        ];
    }

    public static function normalizeConfig($value): array
    {
        $value = is_array($value) ? $value : [];
        $defaults = self::getDefaultConfig();
        $parentWeight = array_key_exists('parentWeight', $value) ? (float)$value['parentWeight'] : $defaults['parentWeight'];
        $depthPenalty = array_key_exists('depthPenalty', $value) ? (float)$value['depthPenalty'] : $defaults['depthPenalty'];

        return [
            'parentWeight' => max(0.0, min(1.0, $parentWeight)),
            'depthPenalty' => max(0.0, $depthPenalty),
        ];
    }

    public static function getConfigForOrganization($organizationId): array
    {
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return self::getDefaultConfig();
        }

        $link = OrganizationApplication::loadByOrganizationAndDirectory($organizationId, 'projects', false);
        $parameters = $link instanceof OrganizationApplication ? $link->getParametersArray() : [];
        return self::normalizeConfig($parameters[self::PARAMETERS_KEY] ?? []);
    }

    public static function normalizeDeclaredImportance($value): ?float
    {
        $value = Project::normalizeLevel($value);
        return $value === null ? null : (($value - 1.0) / 4.0);
    }

    public static function toBusinessScale($score): float
    {
        $score = max(0.0, min(1.0, (float)$score));
        return $score <= 0.0 ? 0.0 : $score * 4.0 + 1.0;
    }

    public static function calculateScore($declaredImportance, ?float $parentCalculatedImportance, bool $isRootAnchored, int $holonDepth, array $config = [], ?bool $parentHasDeclaredImportance = null): float
    {
        $config = self::normalizeConfig($config);
        $parentScore = $parentCalculatedImportance === null
            ? null
            : max(0.0, min(1.0, $parentCalculatedImportance));
        $localScore = self::normalizeDeclaredImportance($declaredImportance);
        $hasLocalImportance = $localScore !== null;
        $parentHasDeclaredImportance = $parentHasDeclaredImportance ?? ($parentScore !== null);

        if (!$hasLocalImportance && !$parentHasDeclaredImportance) {
            $chainScore = 0.0;
        } elseif (!$parentHasDeclaredImportance) {
            $chainScore = $localScore;
        } elseif (!$hasLocalImportance) {
            $chainScore = $parentScore;
        } else {
            $weight = $config['parentWeight'];
            if ($weight <= 0.0) {
                $transmittedScore = $localScore;
            } elseif ($weight >= 1.0) {
                $transmittedScore = $parentScore;
            } elseif ($parentScore <= 0.0 || $localScore <= 0.0) {
                $transmittedScore = 0.0;
            } else {
                $transmittedScore = pow($parentScore, $weight) * pow($localScore, 1.0 - $weight);
            }
            $chainScore = min($parentScore, $transmittedScore);
        }

        $holarchyFactor = $parentScore !== null || $isRootAnchored
            ? 1.0
            : exp(-$config['depthPenalty'] * max(0, $holonDepth));
        return max(0.0, min(1.0, $chainScore * $holarchyFactor));
    }

    public static function recalculateBranch($organizationId, $projectId): array
    {
        return self::recalculate($organizationId, (int)$projectId);
    }

    public static function recalculateOrganization($organizationId): array
    {
        return self::recalculate($organizationId, 0);
    }

    public static function recalculateForHolonHierarchyChange($organizationId): array
    {
        return self::recalculateOrganization($organizationId);
    }

    public static function ensureOrganizationInitialized($organizationId): bool
    {
        $organizationId = (int)$organizationId;
        $link = OrganizationApplication::loadByOrganizationAndDirectory($organizationId, 'projects', false);
        if (!($link instanceof OrganizationApplication)) {
            return false;
        }
        $parameters = $link->getParametersArray();
        $version = (int)($parameters['importanceCalculationVersion'] ?? 0);
        $storedConfig = $parameters[self::PARAMETERS_KEY] ?? [];
        $requiresConfigCleanup = is_array($storedConfig) && array_key_exists('rootNeutralImportance', $storedConfig);
        if ($version >= self::CALCULATION_VERSION && !$requiresConfigCleanup) {
            return false;
        }

        $pdo = DbObject::getPdo();
        $startedTransaction = false;
        try {
            if ($pdo instanceof \PDO && !$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $startedTransaction = true;
            }
            self::recalculateOrganization($organizationId);
            $parameters[self::PARAMETERS_KEY] = self::normalizeConfig($storedConfig);
            $parameters['importanceCalculationVersion'] = self::CALCULATION_VERSION;
            $link->setParametersArray($parameters);
            $result = $link->save();
            if (!is_array($result) || empty($result['status'])) {
                throw new \RuntimeException('Unable to store importance calculation version.');
            }
            if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->commit();
            }
            return true;
        } catch (\Throwable $exception) {
            if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }

    private static function recalculate($organizationId, int $branchRootId): array
    {
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return [];
        }

        $rows = DbObject::fetchAll(
            'SELECT id, IDproject_parent, IDholon, importance, calculated_importance
             FROM project
             WHERE IDorganization = :organization_id
               AND project_kind = :project_kind',
            [
                'organization_id' => $organizationId,
                'project_kind' => Project::KIND_STANDARD,
            ]
        );
        if (!is_array($rows) || count($rows) === 0) {
            return [];
        }

        $projects = [];
        $childrenByParent = [];
        foreach ($rows as $row) {
            $projectId = (int)($row['id'] ?? 0);
            if ($projectId <= 0) {
                continue;
            }
            $projects[$projectId] = $row;
        }
        foreach ($projects as $projectId => $row) {
            $parentId = (int)($row['IDproject_parent'] ?? 0);
            if ($parentId > 0 && isset($projects[$parentId])) {
                $childrenByParent[$parentId][] = $projectId;
            }
        }

        $organization = new Organization();
        $rootHolon = $organization->load($organizationId) ? $organization->getStructuralRootHolon() : null;
        $rootHolonId = $rootHolon instanceof Holon ? (int)$rootHolon->getId() : 0;
        $holonRows = DbObject::fetchAll(
            'SELECT id, IDholon_parent
             FROM holon
             WHERE IDorganization = :organization_id
                OR (:root_holon_id > 0 AND IDholon_org = :root_holon_id_match)',
            [
                'organization_id' => $organizationId,
                'root_holon_id' => $rootHolonId,
                'root_holon_id_match' => $rootHolonId,
            ]
        );
        $holonParents = [];
        if (is_array($holonRows)) {
            foreach ($holonRows as $holonRow) {
                $holonId = (int)($holonRow['id'] ?? 0);
                if ($holonId > 0) {
                    $holonParents[$holonId] = (int)($holonRow['IDholon_parent'] ?? 0);
                }
            }
        }
        $depthCache = [];
        $getHolonDepth = static function ($holonId) use (&$getHolonDepth, &$depthCache, $holonParents, $rootHolonId): int {
            $holonId = (int)$holonId;
            if ($holonId <= 0 || $rootHolonId <= 0 || !isset($holonParents[$holonId])) {
                return 0;
            }
            if (isset($depthCache[$holonId])) {
                return $depthCache[$holonId];
            }
            $path = [];
            $currentId = $holonId;
            while ($currentId > 0 && !isset($path[$currentId])) {
                if ($currentId === $rootHolonId) {
                    $depth = 0;
                    foreach (array_reverse(array_keys($path)) as $pathId) {
                        $depth++;
                        $depthCache[$pathId] = $depth;
                    }
                    return $depthCache[$holonId] ?? 0;
                }
                $path[$currentId] = true;
                $currentId = (int)($holonParents[$currentId] ?? 0);
            }
            foreach (array_keys($path) as $pathId) {
                $depthCache[$pathId] = 0;
            }
            return 0;
        };

        $anchorCache = [];
        $getRootAnchor = static function ($projectId) use (&$getRootAnchor, &$anchorCache, $projects, $rootHolonId): bool {
            $projectId = (int)$projectId;
            if ($projectId <= 0 || !isset($projects[$projectId]) || $rootHolonId <= 0) {
                return false;
            }
            if (isset($anchorCache[$projectId])) {
                return $anchorCache[$projectId];
            }
            $path = [];
            $currentId = $projectId;
            $anchored = false;
            while ($currentId > 0 && isset($projects[$currentId]) && !isset($path[$currentId])) {
                $path[$currentId] = true;
                if ((int)($projects[$currentId]['IDholon'] ?? 0) === $rootHolonId) {
                    $anchored = true;
                    break;
                }
                $currentId = (int)($projects[$currentId]['IDproject_parent'] ?? 0);
            }
            foreach (array_keys($path) as $pathId) {
                $anchorCache[$pathId] = $anchored;
            }
            return $anchored;
        };

        $config = self::getConfigForOrganization($organizationId);
        $scores = [];
        foreach ($projects as $projectId => $row) {
            $scores[$projectId] = max(0.0, min(1.0, (float)($row['calculated_importance'] ?? 0.0)));
        }
        $importancePresenceCache = [];
        $hasDeclaredImportanceInChain = static function ($projectId) use (&$importancePresenceCache, $projects): bool {
            $projectId = (int)$projectId;
            if ($projectId <= 0 || !isset($projects[$projectId])) {
                return false;
            }
            if (isset($importancePresenceCache[$projectId])) {
                return $importancePresenceCache[$projectId];
            }

            $path = [];
            $currentId = $projectId;
            $hasImportance = false;
            while ($currentId > 0 && isset($projects[$currentId]) && !isset($path[$currentId])) {
                if (isset($importancePresenceCache[$currentId])) {
                    $hasImportance = $importancePresenceCache[$currentId];
                    break;
                }
                $path[$currentId] = true;
                if (Project::normalizeLevel($projects[$currentId]['importance'] ?? null) !== null) {
                    $hasImportance = true;
                    break;
                }
                $currentId = (int)($projects[$currentId]['IDproject_parent'] ?? 0);
            }
            foreach (array_keys($path) as $pathId) {
                $importancePresenceCache[$pathId] = $hasImportance;
            }
            return $hasImportance;
        };

        $roots = [];
        if ($branchRootId > 0 && isset($projects[$branchRootId])) {
            $roots[] = $branchRootId;
        } else {
            foreach ($projects as $projectId => $row) {
                $parentId = (int)($row['IDproject_parent'] ?? 0);
                if ($parentId <= 0 || !isset($projects[$parentId])) {
                    $roots[] = $projectId;
                }
            }
        }

        $ordered = [];
        $visited = [];
        $queue = $roots;
        while (count($queue) > 0) {
            $projectId = (int)array_shift($queue);
            if ($projectId <= 0 || !isset($projects[$projectId]) || isset($visited[$projectId])) {
                continue;
            }
            $visited[$projectId] = true;
            $ordered[] = $projectId;
            foreach ($childrenByParent[$projectId] ?? [] as $childId) {
                $queue[] = $childId;
            }
        }
        if ($branchRootId === 0) {
            foreach (array_keys($projects) as $projectId) {
                if (!isset($visited[$projectId])) {
                    $ordered[] = $projectId;
                }
            }
        }

        $updates = [];
        foreach ($ordered as $projectId) {
            $row = $projects[$projectId];
            $parentId = (int)($row['IDproject_parent'] ?? 0);
            $parentScore = $parentId > 0 && isset($scores[$parentId]) ? $scores[$parentId] : null;
            $parentHasDeclaredImportance = $parentId > 0 && $hasDeclaredImportanceInChain($parentId);
            $score = self::calculateScore(
                $row['importance'] ?? null,
                $parentScore,
                $getRootAnchor($projectId),
                $getHolonDepth((int)($row['IDholon'] ?? 0)),
                $config,
                $parentHasDeclaredImportance
            );
            $scores[$projectId] = $score;
            $updates[$projectId] = $score;
        }

        return self::persistScores($updates) ? $updates : [];
    }

    private static function persistScores(array $scores): bool
    {
        if (count($scores) === 0) {
            return true;
        }

        $caseParts = [];
        $params = [];
        $ids = [];
        foreach ($scores as $projectId => $score) {
            $projectId = (int)$projectId;
            if ($projectId <= 0) {
                continue;
            }
            $scoreKey = 'score_' . $projectId;
            $caseParts[] = 'WHEN ' . $projectId . ' THEN :' . $scoreKey;
            $params[$scoreKey] = number_format(max(0.0, min(1.0, (float)$score)), 8, '.', '');
            $ids[] = (string)$projectId;
        }
        if (count($caseParts) === 0) {
            return true;
        }

        return DbObject::execute(
            'UPDATE project SET calculated_importance = CASE id ' . implode(' ', $caseParts) . ' ELSE calculated_importance END WHERE id IN (' . implode(', ', $ids) . ')',
            $params
        );
    }
}
