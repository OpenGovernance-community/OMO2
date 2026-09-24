<?php
namespace dbObject;

/**
 * A change captured for collective validation before it affects its target.
 * The payload is deliberately versioned: object editors can evolve without
 * making already submitted proposals ambiguous.
 */
class DeferredProposal extends DbObject
{
    public const OPERATION_CREATE = 'create';
    public const OPERATION_UPDATE = 'update';
    public const OPERATION_DELETE = 'delete';

    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REMOVED = 'removed';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_CONFLICT = 'conflict';
    public const STATUS_FAILED = 'failed';

    public const TARGET_RULE = 'rule';
    public const TARGET_HOLON = 'holon';
    public const TARGET_PROJECT = 'project';
    public const TARGET_RECURRING_TASK = 'recurring_task';
    public const TARGET_INDICATOR = 'indicator';

    public static function tableName()
    {
        return 'deferred_proposal';
    }

    public static function rules()
    {
        return [
            [['IDorganization', 'IDuser_author', 'target_type', 'operation', 'status'], 'required'],
            [['id', 'IDorganization', 'IDholon', 'IDuser_author', 'target_id', 'IDdecision_proposal', 'IDdocument_pv_point', 'IDuser_validated', 'IDuser_applied', 'position'], 'integer'],
            [['IDorganization', 'IDholon', 'IDuser_author', 'IDdecision_proposal', 'IDdocument_pv_point', 'IDuser_validated', 'IDuser_applied'], 'fk'],
            [['target_type', 'operation', 'status'], 'string'],
            [['before_state', 'after_state', 'parameters'], 'parameters'],
            [['validation_context', 'status_message'], 'text'],
            [['validated_at', 'applied_at', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'target_type' => 'Objet', 'operation' => 'Operation', 'target_id' => 'Cible',
            'before_state' => 'Etat avant', 'after_state' => 'Etat propose',
            'IDdecision_proposal' => 'Alternative de decision', 'IDdocument_pv_point' => 'Point de PV',
            'status' => 'Statut', 'validation_context' => 'Contexte de validation',
        ];
    }

    public static function getOrder()
    {
        return 'position ASC, id ASC';
    }

    public static function handleUserDeparture($organizationId, $userId, $ghostUserId)
    {
        return self::execute(
            'UPDATE deferred_proposal
             SET IDuser_author = CASE WHEN IDuser_author = :source_author THEN :ghost_user_id ELSE IDuser_author END,
                 IDuser_validated = CASE WHEN IDuser_validated = :source_validated THEN :ghost_user_id ELSE IDuser_validated END,
                 IDuser_applied = CASE WHEN IDuser_applied = :source_applied THEN :ghost_user_id ELSE IDuser_applied END
             WHERE IDorganization = :organization_id',
            array(
                'source_author' => (int)$userId,
                'source_validated' => (int)$userId,
                'source_applied' => (int)$userId,
                'ghost_user_id' => (int)$ghostUserId,
                'organization_id' => (int)$organizationId,
            )
        );
    }

    public static function getTargetCatalog(): array
    {
        return [
            self::TARGET_RULE => ['label' => 'Règle', 'implemented' => true],
            self::TARGET_HOLON => ['label' => 'Rôle ou espace', 'implemented' => true],
            self::TARGET_PROJECT => ['label' => 'Projet', 'implemented' => true],
            self::TARGET_RECURRING_TASK => ['label' => 'Tâche récurrente', 'implemented' => true],
            self::TARGET_INDICATOR => ['label' => 'Indicateur', 'implemented' => true],
        ];
    }

    public static function getRuleOperationPermissionKey(string $operation): string
    {
        return match ($operation) {
            self::OPERATION_CREATE => 'CAN_CREATE_RULE',
            self::OPERATION_UPDATE => 'CAN_EDIT_RULE',
            self::OPERATION_DELETE => 'CAN_DELETE_RULE',
            default => '',
        };
    }

    public static function getHolonOperationPermissionKey(string $operation): string
    {
        return match ($operation) {
            self::OPERATION_CREATE => 'CAN_ADD_HOLON',
            self::OPERATION_UPDATE => 'CAN_EDIT_HOLON',
            self::OPERATION_DELETE => 'CAN_DELETE_HOLON',
            default => '',
        };
    }

    public static function getProjectOperationPermissionKey(string $operation): string
    {
        return match ($operation) {
            self::OPERATION_CREATE => 'CAN_CREATE_PROJECT',
            self::OPERATION_UPDATE => 'CAN_EDIT_PROJECT',
            self::OPERATION_DELETE => 'CAN_DELETE_PROJECT',
            default => '',
        };
    }

    public static function getRecurringTaskOperationPermissionKey(string $operation): string
    {
        return match ($operation) {
            self::OPERATION_CREATE => 'CAN_CREATE_RECURRING_TASK',
            self::OPERATION_UPDATE => 'CAN_EDIT_RECURRING_TASK',
            self::OPERATION_DELETE => 'CAN_DELETE_RECURRING_TASK',
            default => '',
        };
    }

    public static function getIndicatorOperationPermissionKey(string $operation): string
    {
        return match ($operation) {
            self::OPERATION_CREATE => 'CAN_CREATE_INDICATOR',
            self::OPERATION_UPDATE => 'CAN_EDIT_INDICATOR',
            self::OPERATION_DELETE => 'CAN_DELETE_INDICATOR',
            default => '',
        };
    }

    public static function loadAllowedObjectTargetHolon(
        int $organizationId,
        int $holonId,
        string $operation,
        int $collectiveHolonId,
        string $targetType
    ): ?Holon {
        $permissionKey = $targetType === self::TARGET_RECURRING_TASK
            ? self::getRecurringTaskOperationPermissionKey($operation)
            : ($targetType === self::TARGET_INDICATOR ? self::getIndicatorOperationPermissionKey($operation) : '');
        if ($organizationId <= 0 || $holonId <= 0 || $collectiveHolonId <= 0 || $permissionKey === '') return null;
        $organization = new Organization();
        $rootHolon = $organization->load($organizationId) ? $organization->getEnabledStructuralRootHolon() : null;
        $targetHolon = new Holon();
        if (!$rootHolon instanceof Holon
            || !$targetHolon->load($holonId)
            || !$targetHolon->isDescendantOf($rootHolon, true)
            || !HolonPermission::holonHasCollectivePermissionForHolonContext(
                $organizationId,
                $collectiveHolonId,
                $permissionKey,
                $holonId
            )) return null;
        return $targetHolon;
    }

    public static function getObjectTargetHolonCatalog(int $organizationId, int $collectiveHolonId, string $targetType): array
    {
        if ($organizationId <= 0 || $collectiveHolonId <= 0) return [];
        $permissionKeys = [];
        foreach ([self::OPERATION_CREATE, self::OPERATION_UPDATE, self::OPERATION_DELETE] as $operation) {
            $permissionKeys[$operation] = $targetType === self::TARGET_RECURRING_TASK
                ? self::getRecurringTaskOperationPermissionKey($operation)
                : ($targetType === self::TARGET_INDICATOR ? self::getIndicatorOperationPermissionKey($operation) : '');
        }
        if (in_array('', $permissionKeys, true)) return [];
        $organization = new Organization();
        if (!$organization->load($organizationId)) return [];
        $rootHolon = $organization->getEnabledStructuralRootHolon();
        if (!$rootHolon instanceof Holon) return [];
        $permissionSet = HolonPermission::buildHolonCollectivePermissionSetForOrganization(
            $organizationId,
            $collectiveHolonId,
            array_values($permissionKeys)
        );
        $catalog = [];
        $visited = [];
        $collect = static function (Holon $holon, array $ancestorIds = []) use (&$collect, &$catalog, &$visited, $permissionKeys, $permissionSet): void {
            $holonId = (int)$holon->getId();
            if ($holonId <= 0 || isset($visited[$holonId])) return;
            $visited[$holonId] = true;
            $lineageIds = array_merge($ancestorIds, [$holonId]);
            $permissions = [];
            foreach ($permissionKeys as $operation => $permissionKey) {
                $scope = $permissionSet['permissions'][$permissionKey] ?? [];
                $allowedBySubtree = false;
                foreach ($lineageIds as $lineageId) {
                    if (!empty($scope['subtree'][$lineageId])) { $allowedBySubtree = true; break; }
                }
                $permissions[$operation] = !empty($scope['organization'])
                    || !empty($scope['exact'][$holonId])
                    || $allowedBySubtree;
            }
            $catalog[$holonId] = ['id' => $holonId, 'label' => (string)$holon->getFullDisplayName(), 'permissions' => $permissions];
            foreach ($holon->getChildren() as $child) if ($child instanceof Holon) $collect($child, $lineageIds);
        };
        $collect($rootHolon);
        return $catalog;
    }

    public static function loadAllowedProjectTargetHolon(
        int $organizationId,
        int $holonId,
        string $operation,
        int $collectiveHolonId
    ): ?Holon {
        $permissionKey = self::getProjectOperationPermissionKey($operation);
        if ($organizationId <= 0 || $holonId <= 0 || $collectiveHolonId <= 0 || $permissionKey === '') {
            return null;
        }

        $organization = new Organization();
        $rootHolon = $organization->load($organizationId)
            ? $organization->getEnabledStructuralRootHolon()
            : null;
        $targetHolon = new Holon();
        if (!$rootHolon instanceof Holon
            || !$targetHolon->load($holonId)
            || !$targetHolon->isDescendantOf($rootHolon, true)) {
            return null;
        }

        $allowed = HolonPermission::holonHasCollectivePermissionForHolonContext(
            $organizationId,
            $collectiveHolonId,
            $permissionKey,
            $holonId
        );
        if (!$allowed && $operation === self::OPERATION_CREATE) {
            $allowed = HolonPermission::holonHasCollectivePermissionForHolonContext(
                $organizationId,
                $collectiveHolonId,
                'CAN_PROPOSE_PROJECT',
                $holonId
            );
        }
        if (!$allowed) return null;

        return $targetHolon;
    }

    /**
     * Load a holon target permitted to the collective that owns the PV.
     * A creation targets its parent context; an update or deletion targets
     * the actual structural element. Personal permissions are not involved.
     */
    public static function loadAllowedHolonTargetHolon(
        int $organizationId,
        int $holonId,
        string $operation,
        int $collectiveHolonId
    ): ?Holon {
        $permissionKey = self::getHolonOperationPermissionKey($operation);
        if ($organizationId <= 0 || $holonId <= 0 || $collectiveHolonId <= 0 || $permissionKey === '') {
            return null;
        }

        $organization = new Organization();
        $rootHolon = $organization->load($organizationId)
            ? $organization->getEnabledStructuralRootHolon()
            : null;
        $targetHolon = new Holon();
        if (!$rootHolon instanceof Holon
            || !$targetHolon->load($holonId)
            || !$targetHolon->isDescendantOf($rootHolon, true)
            || !HolonPermission::holonHasCollectivePermissionForHolonContext(
                $organizationId,
                $collectiveHolonId,
                $permissionKey,
                $holonId
            )) {
            return null;
        }

        $typeId = (int)$targetHolon->get('IDtypeholon');
        if ($operation === self::OPERATION_CREATE) {
            return in_array($typeId, [2, 3, 4], true) ? $targetHolon : null;
        }

        return !$targetHolon->isTemplateNode((int)$rootHolon->getId())
            && in_array($typeId, [1, 2, 3], true)
            ? $targetHolon
            : null;
    }

    /**
     * Structural picker catalog for deferred holon actions. Nodes stay in
     * the catalog when disabled so the collective permission boundary is
     * visible instead of silently hiding the structure.
     */
    public static function getHolonTargetHolonCatalog(int $organizationId, int $collectiveHolonId): array
    {
        if ($organizationId <= 0 || $collectiveHolonId <= 0) return [];
        $organization = new Organization();
        if (!$organization->load($organizationId)) return [];
        $rootHolon = $organization->getEnabledStructuralRootHolon();
        if (!$rootHolon instanceof Holon) return [];

        $permissionKeys = [
            self::OPERATION_CREATE => self::getHolonOperationPermissionKey(self::OPERATION_CREATE),
            self::OPERATION_UPDATE => self::getHolonOperationPermissionKey(self::OPERATION_UPDATE),
            self::OPERATION_DELETE => self::getHolonOperationPermissionKey(self::OPERATION_DELETE),
        ];
        $permissionSet = HolonPermission::buildHolonCollectivePermissionSetForOrganization(
            $organizationId,
            $collectiveHolonId,
            array_values($permissionKeys)
        );
        $catalog = [];
        $visited = [];
        $collect = static function (Holon $holon, array $ancestorIds = []) use (
            &$collect,
            &$catalog,
            &$visited,
            $permissionKeys,
            $permissionSet,
            $rootHolon
        ): void {
            $holonId = (int)$holon->getId();
            if ($holonId <= 0 || isset($visited[$holonId])) return;
            $visited[$holonId] = true;
            $lineageIds = array_merge($ancestorIds, [$holonId]);
            $permissions = [];
            foreach ($permissionKeys as $operation => $permissionKey) {
                $scope = $permissionSet['permissions'][$permissionKey] ?? [];
                $allowedBySubtree = false;
                foreach ($lineageIds as $lineageId) {
                    if (!empty($scope['subtree'][$lineageId])) {
                        $allowedBySubtree = true;
                        break;
                    }
                }
                $allowed = !empty($scope['organization'])
                    || !empty($scope['exact'][$holonId])
                    || $allowedBySubtree;
                $typeId = (int)$holon->get('IDtypeholon');
                if ($operation === self::OPERATION_CREATE) {
                    $allowed = $allowed && in_array($typeId, [2, 3, 4], true);
                } else {
                    $allowed = $allowed
                        && !$holon->isTemplateNode((int)$rootHolon->getId())
                        && in_array($typeId, [1, 2, 3], true);
                }
                $permissions[$operation] = $allowed;
            }
            $catalog[$holonId] = [
                'id' => $holonId,
                'label' => (string)$holon->getFullDisplayName(),
                'permissions' => $permissions,
            ];
            foreach ($holon->getChildren() as $child) {
                if ($child instanceof Holon) $collect($child, $lineageIds);
            }
        };
        $collect($rootHolon);
        return $catalog;
    }

    /**
     * Load a rule target only when it belongs to the organization's enabled
     * structure and the collective holding the PV has the requested right
     * there. Personal grants are deliberately ignored.
     */
    public static function loadAllowedRuleTargetHolon(
        int $organizationId,
        int $holonId,
        string $operation,
        int $collectiveHolonId
    ): ?Holon {
        $permissionKey = self::getRuleOperationPermissionKey($operation);
        if ($organizationId <= 0 || $holonId <= 0 || $collectiveHolonId <= 0 || $permissionKey === '') {
            return null;
        }

        $organization = new Organization();
        $rootHolon = $organization->load($organizationId)
            ? $organization->getEnabledStructuralRootHolon()
            : null;
        $targetHolon = new Holon();
        if (!$rootHolon instanceof Holon
            || !$targetHolon->load($holonId)
            || !$targetHolon->isDescendantOf($rootHolon, true)
            || !HolonPermission::holonHasCollectivePermissionForHolonContext(
                $organizationId,
                $collectiveHolonId,
                $permissionKey,
                $holonId
            )) {
            return null;
        }

        return $targetHolon;
    }

    /**
     * Return every visible structural holon and the rule actions available
     * through the collective holding the PV. Personal grants are not included
     * and non-authorized nodes remain present for the picker.
     */
    public static function getRuleTargetHolonCatalog(int $organizationId, int $collectiveHolonId): array
    {
        if ($organizationId <= 0 || $collectiveHolonId <= 0) return [];
        $organization = new Organization();
        if (!$organization->load($organizationId)) return [];
        $rootHolon = $organization->getEnabledStructuralRootHolon();
        if (!$rootHolon instanceof Holon) return [];

        $permissionKeys = [
            self::OPERATION_CREATE => 'CAN_CREATE_RULE',
            self::OPERATION_UPDATE => 'CAN_EDIT_RULE',
            self::OPERATION_DELETE => 'CAN_DELETE_RULE',
        ];
        $permissionSet = HolonPermission::buildHolonCollectivePermissionSetForOrganization(
            $organizationId,
            $collectiveHolonId,
            array_values($permissionKeys)
        );
        $catalog = [];
        $visited = [];
        $collect = static function (Holon $holon, array $ancestorIds = []) use (
            &$collect,
            &$catalog,
            &$visited,
            $permissionKeys,
            $permissionSet
        ): void {
            $holonId = (int)$holon->getId();
            if ($holonId <= 0 || isset($visited[$holonId])) return;
            $visited[$holonId] = true;
            $lineageIds = array_merge($ancestorIds, [$holonId]);
            $permissions = [];
            foreach ($permissionKeys as $operation => $permissionKey) {
                $scope = $permissionSet['permissions'][$permissionKey] ?? [];
                $allowedBySubtree = false;
                foreach ($lineageIds as $lineageId) {
                    if (!empty($scope['subtree'][$lineageId])) {
                        $allowedBySubtree = true;
                        break;
                    }
                }
                $permissions[$operation] = !empty($scope['organization'])
                    || !empty($scope['exact'][$holonId])
                    || $allowedBySubtree;
            }
            $catalog[$holonId] = [
                'id' => $holonId,
                'label' => (string)$holon->getFullDisplayName(),
                'permissions' => $permissions,
            ];
            foreach ($holon->getChildren() as $child) {
                if ($child instanceof Holon) $collect($child, $lineageIds);
            }
        };
        $collect($rootHolon);

        return $catalog;
    }

    public static function getProjectTargetHolonCatalog(int $organizationId, int $collectiveHolonId): array
    {
        if ($organizationId <= 0 || $collectiveHolonId <= 0) return [];
        $organization = new Organization();
        if (!$organization->load($organizationId)) return [];
        $rootHolon = $organization->getEnabledStructuralRootHolon();
        if (!$rootHolon instanceof Holon) return [];

        $permissionKeys = [
            self::OPERATION_CREATE => self::getProjectOperationPermissionKey(self::OPERATION_CREATE),
            self::OPERATION_UPDATE => self::getProjectOperationPermissionKey(self::OPERATION_UPDATE),
            self::OPERATION_DELETE => self::getProjectOperationPermissionKey(self::OPERATION_DELETE),
            'propose' => 'CAN_PROPOSE_PROJECT',
        ];
        $permissionSet = HolonPermission::buildHolonCollectivePermissionSetForOrganization(
            $organizationId,
            $collectiveHolonId,
            array_values($permissionKeys)
        );
        $catalog = [];
        $visited = [];
        $collect = static function (Holon $holon, array $ancestorIds = []) use (&$collect, &$catalog, &$visited, $permissionKeys, $permissionSet): void {
            $holonId = (int)$holon->getId();
            if ($holonId <= 0 || isset($visited[$holonId])) return;
            $visited[$holonId] = true;
            $lineageIds = array_merge($ancestorIds, [$holonId]);
            $permissions = [];
            foreach ($permissionKeys as $operation => $permissionKey) {
                $scope = $permissionSet['permissions'][$permissionKey] ?? [];
                $allowedBySubtree = false;
                foreach ($lineageIds as $lineageId) {
                    if (!empty($scope['subtree'][$lineageId])) {
                        $allowedBySubtree = true;
                        break;
                    }
                }
                $permissions[$operation] = !empty($scope['organization'])
                    || !empty($scope['exact'][$holonId])
                    || $allowedBySubtree;
            }
            $createAllowed = !empty($permissions[self::OPERATION_CREATE]);
            $canPropose = !empty($permissions['propose']);
            $permissions[self::OPERATION_CREATE] = $createAllowed || $canPropose;
            unset($permissions['propose']);
            $catalog[$holonId] = [
                'id' => $holonId,
                'label' => (string)$holon->getFullDisplayName(),
                'permissions' => $permissions,
                'project_creation_mode' => $createAllowed ? 'create' : ($canPropose ? 'propose' : ''),
            ];
            foreach ($holon->getChildren() as $child) {
                if ($child instanceof Holon) $collect($child, $lineageIds);
            }
        };
        $collect($rootHolon);
        return $catalog;
    }

    public static function captureProjectState(Project $project): array
    {
        $date = static function ($value): string {
            if ($value instanceof \DateTimeInterface) return $value->format('Y-m-d');
            $value = trim((string)$value);
            return preg_match('/^\d{4}-\d{2}-\d{2}/', $value) === 1 ? substr($value, 0, 10) : '';
        };
        return [
            'IDholon' => (int)$project->get('IDholon'),
            'IDuser' => (int)$project->get('IDuser') ?: null,
            'IDproject_parent' => (int)$project->get('IDproject_parent') ?: null,
            'title' => trim((string)$project->get('title')),
            'description' => PropertyFormat::sanitizeHtml((string)$project->get('description')),
            'status' => Project::normalizeStatus($project->get('status')),
            'blocked_reason' => trim((string)$project->get('blocked_reason')),
            'blocked_until' => $date($project->get('blocked_until')),
            'blocked_auto_reactivate' => (int)(bool)$project->get('blocked_auto_reactivate'),
            'blocked_reactivate_status' => Project::normalizeBlockedReactivateStatus($project->get('blocked_reactivate_status')),
            'capture_mode' => Project::normalizeCaptureMode($project->get('capture_mode')),
            'project_size' => Project::normalizeSize($project->get('project_size')),
            'priority' => Project::normalizeLevel($project->get('priority')),
            'importance' => Project::normalizeLevel($project->get('importance')),
            'planned_start_date' => $date($project->get('planned_start_date')),
            'planned_end_date' => $date($project->get('planned_end_date')),
        ];
    }

    public static function normalizeProjectState(array $state, ?Project $baseProject = null): array
    {
        $base = $baseProject instanceof Project ? self::captureProjectState($baseProject) : [];
        $state = array_merge($base, $state);
        $date = static function ($value): string {
            $value = trim((string)$value);
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : '';
        };
        $normalized = [
            'IDholon' => (int)($state['IDholon'] ?? 0),
            'IDuser' => (int)($state['IDuser'] ?? 0) ?: null,
            'IDproject_parent' => (int)($state['IDproject_parent'] ?? 0) ?: null,
            'title' => mb_substr(trim((string)($state['title'] ?? '')), 0, 255, 'UTF-8'),
            'description' => PropertyFormat::sanitizeHtml((string)($state['description'] ?? '')),
            'status' => Project::normalizeStatus($state['status'] ?? Project::STATUS_IN_PROGRESS),
            'blocked_reason' => mb_substr(trim((string)($state['blocked_reason'] ?? '')), 0, 4000, 'UTF-8'),
            'blocked_until' => $date($state['blocked_until'] ?? ''),
            'blocked_auto_reactivate' => !empty($state['blocked_auto_reactivate']) ? 1 : 0,
            'blocked_reactivate_status' => Project::normalizeBlockedReactivateStatus($state['blocked_reactivate_status'] ?? Project::STATUS_READY),
            'capture_mode' => Project::normalizeCaptureMode($state['capture_mode'] ?? Project::CAPTURE_MULTIPLE_DOCUMENTS),
            'project_size' => Project::normalizeSize($state['project_size'] ?? Project::SIZE_M),
            'priority' => Project::normalizeLevel($state['priority'] ?? null),
            'importance' => Project::normalizeLevel($state['importance'] ?? null),
            'planned_start_date' => $date($state['planned_start_date'] ?? ''),
            'planned_end_date' => $date($state['planned_end_date'] ?? ''),
        ];
        if ($normalized['status'] !== Project::STATUS_BLOCKED) {
            $normalized['blocked_reason'] = '';
            $normalized['blocked_until'] = '';
            $normalized['blocked_auto_reactivate'] = 0;
            $normalized['blocked_reactivate_status'] = Project::STATUS_READY;
        }
        return $normalized;
    }

    public static function captureRecurringTaskState(ControlActivity $task): array
    {
        return [
            'IDholon' => (int)$task->get('IDholon'),
            'IDuser_responsible' => (int)$task->get('IDuser_responsible') ?: null,
            'title' => trim((string)$task->get('title')),
            'description' => PropertyFormat::sanitizeHtml((string)$task->get('description')),
            'frequency' => (string)$task->get('frequency'),
            'schedule' => (string)$task->get('schedule'),
            'display_lead_value' => (int)$task->get('display_lead_value'),
            'display_lead_unit' => (string)$task->get('display_lead_unit'),
            'execution_duration_value' => (int)$task->get('execution_duration_value'),
            'execution_duration_unit' => (string)$task->get('execution_duration_unit'),
        ];
    }

    public static function normalizeRecurringTaskState(array $state, ?ControlActivity $baseTask = null): array
    {
        $state = array_merge($baseTask instanceof ControlActivity ? self::captureRecurringTaskState($baseTask) : [], $state);
        $frequency = RecurrenceSchedule::normalizeFrequency($state['frequency'] ?? '') ?: RecurrenceSchedule::FREQUENCY_WEEKLY;
        $schedule = RecurrenceSchedule::normalizeSchedule($frequency, $state['schedule'] ?? '');
        if ($schedule === null) $schedule = $frequency === RecurrenceSchedule::FREQUENCY_DAILY ? '09:00' : '1';
        $units = ControlTask::delayUnits();
        $displayUnit = in_array((string)($state['display_lead_unit'] ?? ''), $units, true) ? (string)$state['display_lead_unit'] : 'day';
        $durationUnit = in_array((string)($state['execution_duration_unit'] ?? ''), $units, true) ? (string)$state['execution_duration_unit'] : 'day';
        return [
            'IDholon' => (int)($state['IDholon'] ?? 0),
            'IDuser_responsible' => (int)($state['IDuser_responsible'] ?? 0) ?: null,
            'title' => mb_substr(trim((string)($state['title'] ?? '')), 0, 255, 'UTF-8'),
            'description' => PropertyFormat::sanitizeHtml((string)($state['description'] ?? '')),
            'frequency' => $frequency,
            'schedule' => $schedule,
            'display_lead_value' => max(0, min(3650, (int)($state['display_lead_value'] ?? 0))),
            'display_lead_unit' => $displayUnit,
            'execution_duration_value' => max(1, min(3650, (int)($state['execution_duration_value'] ?? 1))),
            'execution_duration_unit' => $durationUnit,
        ];
    }

    public static function captureIndicatorState(StatIndicator $indicator): array
    {
        $referencePoints = [];
        foreach ($indicator->getReferencePoints() as $point) {
            if (!$point instanceof StatIndicatorReferencePoint) continue;
            $pointAt = $point->get('point_at');
            $referencePoints[] = [
                'position_percent' => (float)$point->get('position_percent'),
                'value' => (float)$point->get('value'),
                'point_at' => $pointAt instanceof \DateTimeInterface ? $pointAt->format('Y-m-d H:i:s') : null,
            ];
        }
        $state = ['IDholon' => (int)$indicator->get('IDholon'), 'reference_points' => $referencePoints];
        foreach (['IDuser_responsible', 'IDdocument', 'name', 'description', 'source_url', 'reference_type', 'reference_scale', 'measurement_frequency', 'measurement_schedule', 'source_type', 'ethercalc_cell', 'ethercalc_frequency', 'ethercalc_range', 'ethercalc_date_column', 'ethercalc_value_column', 'spreadsheet_sheet', 'spreadsheet_cell', 'spreadsheet_frequency', 'spreadsheet_range', 'spreadsheet_date_column', 'spreadsheet_value_column', 'chart_min_value', 'show_cumulative'] as $field) {
            $state[$field] = $indicator->get($field);
        }
        return self::normalizeIndicatorState($state);
    }

    public static function normalizeIndicatorState(array $state, ?StatIndicator $baseIndicator = null): array
    {
        $state = array_merge($baseIndicator instanceof StatIndicator ? self::captureIndicatorState($baseIndicator) : [], $state);
        $sourceType = StatIndicator::normalizeSourceType($state['source_type'] ?? StatIndicator::SOURCE_MANUAL);
        $frequency = StatIndicator::normalizeMeasurementFrequency($state['measurement_frequency'] ?? '');
        $sourceUrl = StatIndicator::sanitizeSourceUrl($state['source_url'] ?? '');
        $referenceType = StatIndicator::normalizeReferenceType($state['reference_type'] ?? StatIndicator::REFERENCE_NONE);
        $points = [];
        foreach ((array)($state['reference_points'] ?? []) as $point) {
            if (!is_array($point) || !is_numeric($point['position_percent'] ?? null) || !is_numeric($point['value'] ?? null)) continue;
            $position = max(0, min(100, (float)$point['position_percent']));
            $points[] = ['position_percent' => $position, 'value' => (float)$point['value'], 'point_at' => trim((string)($point['point_at'] ?? '')) ?: null];
        }
        if ($referenceType === StatIndicator::REFERENCE_CEILING && is_numeric($state['ceiling_value'] ?? null)) {
            $ceiling = (float)$state['ceiling_value'];
            $points = [['position_percent' => 0.0, 'value' => $ceiling, 'point_at' => null], ['position_percent' => 100.0, 'value' => $ceiling, 'point_at' => null]];
        }
        usort($points, static fn (array $left, array $right): int => $left['position_percent'] <=> $right['position_percent']);
        $ceilingValue = $referenceType === StatIndicator::REFERENCE_CEILING && isset($points[0]['value'])
            ? (float)$points[0]['value']
            : null;
        return [
            'IDholon' => (int)($state['IDholon'] ?? 0),
            'IDuser_responsible' => (int)($state['IDuser_responsible'] ?? 0) ?: null,
            'IDdocument' => (int)($state['IDdocument'] ?? 0) ?: null,
            'name' => mb_substr(trim((string)($state['name'] ?? '')), 0, 190, 'UTF-8'),
            'description' => mb_substr(trim((string)($state['description'] ?? '')), 0, 10000, 'UTF-8'),
            'source_url' => $sourceUrl !== '' ? $sourceUrl : null,
            'source_type' => $sourceType,
            'reference_type' => $referenceType,
            'ceiling_value' => $ceilingValue,
            'reference_scale' => StatIndicator::normalizeReferenceScale($state['reference_scale'] ?? StatIndicator::REFERENCE_SCALE_CUMULATIVE),
            'measurement_frequency' => $frequency,
            'measurement_schedule' => StatIndicator::normalizeMeasurementSchedule($frequency, $state['measurement_schedule'] ?? ''),
            'chart_min_value' => is_numeric($state['chart_min_value'] ?? null) ? (float)$state['chart_min_value'] : null,
            'show_cumulative' => !empty($state['show_cumulative']) ? 1 : 0,
            'ethercalc_cell' => $state['ethercalc_cell'] ?? null,
            'ethercalc_frequency' => $state['ethercalc_frequency'] ?? null,
            'ethercalc_range' => $state['ethercalc_range'] ?? null,
            'ethercalc_date_column' => $state['ethercalc_date_column'] ?? null,
            'ethercalc_value_column' => $state['ethercalc_value_column'] ?? null,
            'spreadsheet_sheet' => $state['spreadsheet_sheet'] ?? null,
            'spreadsheet_cell' => $state['spreadsheet_cell'] ?? null,
            'spreadsheet_frequency' => $state['spreadsheet_frequency'] ?? null,
            'spreadsheet_range' => $state['spreadsheet_range'] ?? null,
            'spreadsheet_date_column' => $state['spreadsheet_date_column'] ?? null,
            'spreadsheet_value_column' => $state['spreadsheet_value_column'] ?? null,
            'reference_points' => $points,
        ];
    }

    public static function normalizeIndicatorEditorState(array $input, int $organizationId, ?StatIndicator $baseIndicator = null): array
    {
        $sourceType = StatIndicator::normalizeSourceType($input['source_type'] ?? StatIndicator::SOURCE_MANUAL);
        $input['source_type'] = $sourceType;
        $input['show_cumulative'] = !empty($input['show_cumulative']) ? 1 : 0;
        $rawSourceUrl = trim((string)($input['source_url'] ?? ''));
        if ($rawSourceUrl !== '' && StatIndicator::sanitizeSourceUrl($rawSourceUrl) === '') {
            throw new \InvalidArgumentException('Le lien vers la source est invalide.');
        }
        $isEthercalc = in_array($sourceType, [StatIndicator::SOURCE_ETHERCALC_CELL, StatIndicator::SOURCE_ETHERCALC_TABLE], true);
        $isSpreadsheet = in_array($sourceType, [StatIndicator::SOURCE_SPREADSHEET_CELL, StatIndicator::SOURCE_SPREADSHEET_TABLE], true);
        if ($isEthercalc || $isSpreadsheet) {
            $documentId = (int)($input[$isEthercalc ? 'ethercalc_document_id' : 'spreadsheet_document_id'] ?? ($input['IDdocument'] ?? 0));
            $document = new Document();
            if ($documentId <= 0 || !$document->load($documentId)
                || (int)$document->get('IDorganization') !== $organizationId
                || (int)$document->get('active') !== 1
                || ($isEthercalc && !$document->isEthercalcDocument())
                || ($isSpreadsheet && (!$document->isUploadedFile() || !$document->hasStoredFile()))) {
                throw new \InvalidArgumentException('Le document source de l’indicateur est invalide.');
            }
            $input['IDdocument'] = $documentId;
            $input['source_url'] = null;
            $input['measurement_frequency'] = null;
            $input['measurement_schedule'] = null;
            if ($sourceType === StatIndicator::SOURCE_ETHERCALC_CELL) {
                $input['ethercalc_cell'] = StatIndicator::normalizeEthercalcCell($input['ethercalc_cell'] ?? '');
                if ($input['ethercalc_cell'] === '') throw new \InvalidArgumentException('La cellule source est invalide.');
            } elseif ($sourceType === StatIndicator::SOURCE_SPREADSHEET_CELL) {
                $input['spreadsheet_cell'] = StatIndicator::normalizeEthercalcCell($input['spreadsheet_cell'] ?? '');
                if ($input['spreadsheet_cell'] === '') throw new \InvalidArgumentException('La cellule source est invalide.');
            } else {
                $prefix = $isEthercalc ? 'ethercalc' : 'spreadsheet';
                $columns = array_values(array_filter(array_map('trim', explode(',', (string)($input[$prefix . '_value_columns'] ?? ($input[$prefix . '_value_column'] ?? ''))))));
                if (count($columns) !== 1) throw new \InvalidArgumentException('Une modification différée d’indicateur doit désigner une seule colonne de valeurs.');
                $input[$prefix . '_value_column'] = StatIndicator::normalizeEthercalcColumn($columns[0]);
                $input[$prefix . '_date_column'] = StatIndicator::normalizeEthercalcColumn($input[$prefix . '_date_column'] ?? '');
                $input[$prefix . '_range'] = StatIndicator::normalizeEthercalcRange($input[$prefix . '_range'] ?? '');
                if (empty($input[$prefix . '_value_column']) || empty($input[$prefix . '_date_column']) || empty($input[$prefix . '_range'])) {
                    throw new \InvalidArgumentException('La plage ou les colonnes de la source sont invalides.');
                }
            }
        } else {
            $input['IDdocument'] = null;
        }
        $ethercalcFields = ['ethercalc_cell', 'ethercalc_frequency', 'ethercalc_range', 'ethercalc_date_column', 'ethercalc_value_column'];
        $spreadsheetFields = ['spreadsheet_sheet', 'spreadsheet_cell', 'spreadsheet_frequency', 'spreadsheet_range', 'spreadsheet_date_column', 'spreadsheet_value_column'];
        if ($isEthercalc) foreach ($spreadsheetFields as $field) $input[$field] = null;
        if ($isSpreadsheet) foreach ($ethercalcFields as $field) $input[$field] = null;
        if (!$isEthercalc && !$isSpreadsheet) foreach (array_merge($ethercalcFields, $spreadsheetFields) as $field) $input[$field] = null;
        if ($sourceType === StatIndicator::SOURCE_ETHERCALC_CELL) foreach (['ethercalc_range', 'ethercalc_date_column', 'ethercalc_value_column'] as $field) $input[$field] = null;
        if ($sourceType === StatIndicator::SOURCE_ETHERCALC_TABLE) $input['ethercalc_cell'] = null;
        if ($sourceType === StatIndicator::SOURCE_SPREADSHEET_CELL) foreach (['spreadsheet_range', 'spreadsheet_date_column', 'spreadsheet_value_column'] as $field) $input[$field] = null;
        if ($sourceType === StatIndicator::SOURCE_SPREADSHEET_TABLE) $input['spreadsheet_cell'] = null;
        $referenceType = StatIndicator::normalizeReferenceType($input['reference_type'] ?? StatIndicator::REFERENCE_NONE);
        if ($referenceType === StatIndicator::REFERENCE_NONE) $input['reference_points'] = [];
        if ($referenceType === StatIndicator::REFERENCE_CEILING && !is_numeric($input['ceiling_value'] ?? null)) {
            throw new \InvalidArgumentException('La valeur du plafond est obligatoire.');
        }
        if ($referenceType === StatIndicator::REFERENCE_OBJECTIVE) {
            $points = [];
            foreach ((array)($input['reference_points'] ?? []) as $point) {
                if (!is_array($point) || !is_numeric($point['position_percent'] ?? null) || !is_numeric($point['value'] ?? null)) {
                    throw new \InvalidArgumentException('Les points de référence sont invalides.');
                }
                $position = (float)$point['position_percent'];
                if ($position < 0 || $position > 100) throw new \InvalidArgumentException('Les positions de référence doivent être comprises entre 0 et 100 %.');
                $points[] = ['position_percent' => $position, 'value' => (float)$point['value'], 'point_at' => $point['point_at'] ?? null];
            }
            usort($points, static fn (array $left, array $right): int => $left['position_percent'] <=> $right['position_percent']);
            if (count($points) < 2 || abs($points[0]['position_percent']) > 0.0001 || abs(end($points)['position_percent'] - 100) > 0.0001) {
                throw new \InvalidArgumentException('Les points de référence doivent commencer à 0 % et finir à 100 %.');
            }
            for ($index = 1; $index < count($points); $index++) {
                if (abs($points[$index]['position_percent'] - $points[$index - 1]['position_percent']) < 0.0001) {
                    throw new \InvalidArgumentException('Chaque point de référence doit avoir une position différente.');
                }
            }
            if (trim((string)$points[0]['point_at']) === '' || trim((string)$points[count($points) - 1]['point_at']) === '') {
                throw new \InvalidArgumentException('Les dates des extrémités de la référence sont obligatoires.');
            }
            try {
                $startAt = new \DateTimeImmutable((string)$points[0]['point_at']);
                $endAt = new \DateTimeImmutable((string)$points[count($points) - 1]['point_at']);
            } catch (\Throwable $exception) {
                throw new \InvalidArgumentException('Les dates des extrémités de la référence sont obligatoires.');
            }
            if ($endAt <= $startAt) throw new \InvalidArgumentException('La date de fin de la référence doit suivre la date de début.');
            $startTimestamp = $startAt->getTimestamp();
            $duration = $endAt->getTimestamp() - $startTimestamp;
            foreach ($points as &$point) {
                $timestamp = $startTimestamp + (int)round($duration * $point['position_percent'] / 100);
                $point['point_at'] = (new \DateTimeImmutable())->setTimestamp($timestamp)->format('Y-m-d H:i:s');
            }
            unset($point);
            $input['reference_points'] = $points;
        }
        return self::normalizeIndicatorState($input, $baseIndicator);
    }

    public static function normalizeState($value): array
    {
        if (is_array($value)) return $value;
        $decoded = json_decode(trim((string)$value), true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function decorateHolonListDisplayState(array $state, Organization $organization): array
    {
        if (!is_array($state['editor_payload']['properties'] ?? null)) return $state;
        foreach ($state['editor_payload']['properties'] as &$property) {
            if (!is_array($property)) continue;
            $formatId = (int)($property['formatId'] ?? 0);
            if (!PropertyFormat::isListFormat($formatId)) continue;
            $property['displayItems'] = $organization->getHolonEditorListDisplayItems(
                $property['value'] ?? '', $formatId, $property['listItemType'] ?? ''
            );
        }
        unset($property);
        return $state;
    }

    /**
     * Return neutral display data so collective-space renderers can present a
     * proposal without having to understand every target payload.
     */
    public function buildPresentationData(): array
    {
        $beforeState = self::normalizeState($this->get('before_state'));
        $afterState = self::normalizeState($this->get('after_state'));
        $operation = trim((string)$this->get('operation'));
        $displayState = $operation === self::OPERATION_DELETE ? $beforeState : $afterState;
        $catalog = self::getTargetCatalog();
        $targetType = trim((string)$this->get('target_type'));
        if ($targetType === self::TARGET_HOLON) {
            $organization = new Organization();
            if ($organization->load((int)$this->get('IDorganization'))) {
                $beforeState = self::decorateHolonListDisplayState($beforeState, $organization);
                $afterState = self::decorateHolonListDisplayState($afterState, $organization);
            }
        }
        $targetLabel = trim((string)($catalog[$targetType]['label'] ?? $targetType));
        $objectTypeLabel = '';
        if ($targetType === self::TARGET_HOLON) {
            $typeHolon = new Holon();
            $targetId = (int)$this->get('target_id');
            $templateId = (int)($displayState['template_id'] ?? ($displayState['editor_payload']['templateId'] ?? 0));
            if (($targetId > 0 && $typeHolon->load($targetId))
                || ($targetId <= 0 && $templateId > 0 && $typeHolon->load($templateId))) {
                $objectTypeLabel = trim((string)$typeHolon->getTypeLabel());
            }
        }
        $holonId = (int)$this->get('IDholon');
        $holonLabel = '';
        if ($holonId > 0) {
            $holon = new Holon();
            if ($holon->load($holonId)) $holonLabel = (string)$holon->getFullDisplayName();
        }

        $title = '';
        foreach (['title', 'name', 'full_name', 'label'] as $field) {
            $candidate = trim(strip_tags((string)($displayState[$field] ?? '')));
            if ($candidate !== '') {
                $title = $candidate;
                break;
            }
        }
        if ($title === '') {
            $targetId = (int)$this->get('target_id');
            $title = $targetId > 0 ? '#' . $targetId : $targetLabel;
        }

        $excerpt = '';
        foreach (['description', 'intention', 'purpose', 'content'] as $field) {
            $candidate = trim(preg_replace('/\s+/u', ' ', strip_tags((string)($displayState[$field] ?? ''))) ?? '');
            if ($candidate !== '') {
                $excerpt = mb_strlen($candidate) > 180 ? rtrim(mb_substr($candidate, 0, 177)) . '…' : $candidate;
                break;
            }
        }

        $changedFieldCount = 0;
        if ($operation === self::OPERATION_UPDATE) {
            foreach (array_unique(array_merge(array_keys($beforeState), array_keys($afterState))) as $field) {
                if ($field === 'editor_payload') continue;
                $beforeValue = $beforeState[$field] ?? null;
                $afterValue = $afterState[$field] ?? null;
                if (json_encode($beforeValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    !== json_encode($afterValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) {
                    $changedFieldCount++;
                }
            }
            if (json_encode($beforeState['editor_payload'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                !== json_encode($afterState['editor_payload'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) {
                $changedFieldCount++;
            }
        }

        $authorities = [];
        if ($targetType === self::TARGET_RULE) {
            $authorityIds = array_values(array_unique(array_filter([
                (int)($beforeState['IDauthority'] ?? 0),
                (int)($afterState['IDauthority'] ?? 0),
            ])));
            if ($authorityIds) {
                foreach (Authority::getLabelsByIds($authorityIds) as $authorityId => $authorityLabel) {
                    $authorities[] = ['id' => (int)$authorityId, 'label' => (string)$authorityLabel];
                }
            }
        }

        $responsibleLabels = [];
        if (in_array($targetType, [self::TARGET_INDICATOR, self::TARGET_RECURRING_TASK], true)) {
            foreach (array_unique(array_filter([
                (int)($beforeState['IDuser_responsible'] ?? 0),
                (int)($afterState['IDuser_responsible'] ?? 0),
            ])) as $responsibleUserId) {
                $responsibleLabels[$responsibleUserId] = DocumentPvPoint::getUserDisplayNameForOrganization(
                    $responsibleUserId, (int)$this->get('IDorganization')
                );
            }
        }

        return [
            'id' => (int)$this->getId(),
            'targetType' => $targetType,
            'targetLabel' => $targetLabel,
            'objectTypeLabel' => $objectTypeLabel,
            'targetId' => (int)$this->get('target_id'),
            'holonId' => $holonId,
            'holonLabel' => $holonLabel,
            'operation' => $operation,
            'status' => trim((string)$this->get('status')),
            'statusMessage' => trim((string)$this->get('status_message')),
            'title' => $title,
            'excerpt' => $excerpt,
            'changedFieldCount' => $changedFieldCount,
            'changeType' => $targetType . '.' . $operation,
            'beforeState' => $beforeState,
            'afterState' => $afterState,
            'authorities' => $authorities,
            'responsibleLabels' => $responsibleLabels,
        ];
    }

    public static function getForDecisionProposal(int $decisionProposalId, bool $pendingOnly = false)
    {
        return self::getForValidationContext('IDdecision_proposal', $decisionProposalId, $pendingOnly);
    }

    public static function getForPvPoint(int $pointId, bool $pendingOnly = false)
    {
        return self::getForValidationContext('IDdocument_pv_point', $pointId, $pendingOnly);
    }

    protected static function getForValidationContext(string $field, int $id, bool $pendingOnly)
    {
        $items = new ArrayDeferredProposal();
        $where = [['field' => $field, 'value' => $id]];
        if ($pendingOnly) $where[] = ['field' => 'status', 'value' => self::STATUS_PENDING];
        $items->load(['where' => $where, 'orderBy' => [['field' => 'position', 'dir' => 'ASC'], ['field' => 'id', 'dir' => 'ASC']]]);
        return $items;
    }

    public static function applyForPvPoint(DocumentPvPoint $point, int $userId): array
    {
        $proposals = [];
        foreach (self::getForPvPoint((int)$point->getId(), true) as $proposal) {
            if ($proposal instanceof self) $proposals[] = $proposal;
        }
        return self::applyBatch($proposals, (int)$point->get('IDholon_concerned'), $userId, 'pv_point:' . (int)$point->getId());
    }

    public static function applyAcceptedForDecision(DecisionProcess $decision): array
    {
        if (DecisionProcess::getStatusRank($decision->get('status')) < DecisionProcess::getStatusRank(DecisionProcess::STATUS_RESULTS)) {
            return ['status' => true, 'applied' => 0];
        }
        $summary = ['status' => true, 'applied' => 0, 'conflicts' => 0, 'failed' => 0];
        foreach ($decision->getDecisionGroups(true) as $group) {
            if (!$group instanceof DecisionGroup) continue;
            $method = DecisionProcess::normalizeEvaluationMethod($group->get('evaluation_method'));
            if (!in_array($method, [DecisionProcess::METHOD_CONSENT, DecisionProcess::METHOD_SIMPLE_VOTE], true)) continue;
            $responses = [];
            foreach ($group->getResponses(DecisionResponse::STATUS_SUBMITTED) as $response) $responses[] = $response;
            $winnerIds = $method === DecisionProcess::METHOD_SIMPLE_VOTE
                ? array_fill_keys(DecisionGovernanceAction::getSimpleVoteWinningProposalIds($responses), true)
                : [];
            foreach ($group->getProposals(true) as $decisionProposal) {
                if (!$decisionProposal instanceof DecisionProposal) continue;
                $proposals = [];
                foreach (self::getForDecisionProposal((int)$decisionProposal->getId(), true) as $proposal) {
                    if ($proposal instanceof self) $proposals[] = $proposal;
                }
                if (!$proposals) continue;
                $accepted = $method === DecisionProcess::METHOD_CONSENT
                    ? !DecisionGovernanceAction::proposalHasObjection($decisionProposal, $responses)
                    : isset($winnerIds[(int)$decisionProposal->getId()]);
                if (!$accepted) {
                    foreach ($proposals as $proposal) {
                        $proposal->set('status', self::STATUS_REJECTED);
                        $proposal->set('status_message', 'Proposition non retenue par la décision collective.');
                        $proposal->save();
                    }
                    continue;
                }
                $result = self::applyBatch($proposals, (int)$decision->get('IDholon'), (int)$decision->get('IDuser'), 'decision:' . (int)$decision->getId());
                $summary['applied'] += (int)($result['applied'] ?? 0);
                $summary['conflicts'] += !empty($result['conflict']) ? 1 : 0;
                $summary['failed'] += empty($result['status']) && empty($result['conflict']) ? 1 : 0;
            }
        }
        return $summary;
    }

    public static function applyBatch(array $proposals, int $contextHolonId, int $userId, string $validationContext): array
    {
        if (!$proposals) return ['status' => true, 'applied' => 0];
        $pdo = self::getPdo();
        if (!$pdo) return ['status' => false, 'message' => 'Connexion a la base impossible.'];
        try {
            $ownsTransaction = !$pdo->inTransaction();
            if ($ownsTransaction) $pdo->beginTransaction();
            foreach ($proposals as $proposal) {
                if (!$proposal instanceof self || (string)$proposal->get('status') !== self::STATUS_PENDING) continue;
                $result = self::applyOne($proposal, $contextHolonId);
                if (empty($result['status'])) throw new \RuntimeException((!empty($result['conflict']) ? 'conflict:' : 'failure:') . (string)($result['message'] ?? 'Application impossible.'));
                $proposal->set('status', self::STATUS_APPLIED);
                $proposal->set('validation_context', $validationContext);
                $proposal->set('IDuser_validated', $userId);
                $proposal->set('IDuser_applied', $userId);
                $proposal->set('validated_at', new \DateTimeImmutable('now'));
                $proposal->set('applied_at', new \DateTimeImmutable('now'));
                if (!empty($result['target_id'])) $proposal->set('target_id', (int)$result['target_id']);
                $proposal->set('status_message', null);
                $proposal->save();
            }
            if ($ownsTransaction) $pdo->commit();
            return ['status' => true, 'applied' => count($proposals)];
        } catch (\Throwable $exception) {
            if (isset($ownsTransaction) && $ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
            $message = preg_replace('/^(conflict|failure):/', '', trim((string)$exception->getMessage())) ?: 'Application impossible.';
            $status = str_starts_with((string)$exception->getMessage(), 'conflict:') ? self::STATUS_CONFLICT : self::STATUS_FAILED;
            foreach ($proposals as $proposal) {
                if ($proposal instanceof self && (string)$proposal->get('status') === self::STATUS_PENDING) {
                    $proposal->set('status', $status);
                    $proposal->set('status_message', $message);
                    $proposal->set('updated_at', new \DateTimeImmutable('now'));
                    $proposal->save();
                }
            }
            return ['status' => false, 'conflict' => $status === self::STATUS_CONFLICT, 'message' => $message];
        }
    }

    protected static function applyOne(self $proposal, int $contextHolonId): array
    {
        // The existing governance executor remains the single source of truth
        // for rules and roles while the other editors receive capture modes.
        if ((string)$proposal->get('target_type') === self::TARGET_PROJECT) {
            return self::applyProjectProposal($proposal);
        }
        if ((string)$proposal->get('target_type') === self::TARGET_RECURRING_TASK) {
            return self::applyRecurringTaskProposal($proposal);
        }
        if ((string)$proposal->get('target_type') === self::TARGET_INDICATOR) {
            return self::applyIndicatorProposal($proposal);
        }
        if (!in_array((string)$proposal->get('target_type'), [self::TARGET_RULE, self::TARGET_HOLON], true)) {
            return ['status' => false, 'message' => 'Ce type de modification n’est pas encore exécutable.'];
        }
        $proposalHolonId = (int)$proposal->get('IDholon');
        return DecisionGovernanceAction::applyDeferredProposal(
            $proposal,
            $proposalHolonId > 0 ? $proposalHolonId : $contextHolonId
        );
    }

    protected static function applyProjectProposal(self $proposal): array
    {
        $organizationId = (int)$proposal->get('IDorganization');
        $contextHolonId = (int)$proposal->get('IDholon');
        $operation = (string)$proposal->get('operation');
        $targetId = (int)$proposal->get('target_id');
        if ($organizationId <= 0 || $contextHolonId <= 0) {
            return ['status' => false, 'conflict' => true, 'message' => 'Le contexte du projet est invalide.'];
        }
        if ($operation === self::OPERATION_CREATE) {
            $state = self::normalizeProjectState(self::normalizeState($proposal->get('after_state')));
            $state['IDholon'] = $contextHolonId;
            if ($state['title'] === '') return ['status' => false, 'message' => 'Le titre du projet est obligatoire.'];
            if ($targetId > 0) return ['status' => false, 'conflict' => true, 'message' => 'La création du projet est dans un état incohérent.'];
            $project = new Project();
            self::applyProjectState($project, $state, $organizationId);
            $result = $project->save();
            if (!is_array($result) || empty($result['status']) || (int)$project->getId() <= 0) {
                return ['status' => false, 'message' => 'Le projet ne peut pas être créé.'];
            }
            return ['status' => true, 'target_id' => (int)$project->getId()];
        }

        $project = new Project();
        if (!$project->load($targetId)
            || (int)$project->get('IDorganization') !== $organizationId
            || (int)$project->get('IDholon') !== $contextHolonId) {
            return ['status' => false, 'conflict' => true, 'message' => 'Le projet cible a changé de contexte.'];
        }
        $before = self::normalizeProjectState(self::normalizeState($proposal->get('before_state')), $project);
        $current = self::captureProjectState($project);
        if ($operation === self::OPERATION_DELETE) {
            $children = new ArrayProject();
            $children->loadForParent($targetId, true);
            if (count($children) > 0) {
                return ['status' => false, 'conflict' => true, 'message' => 'Le projet contient encore des sous-projets.'];
            }
            if ($current !== $before) return ['status' => false, 'conflict' => true, 'message' => 'Le projet a été modifié depuis la préparation de cette modification.'];
            return $project->delete()
                ? ['status' => true, 'target_id' => $targetId]
                : ['status' => false, 'message' => 'Le projet ne peut pas être supprimé.'];
        }
        $after = self::normalizeProjectState(self::normalizeState($proposal->get('after_state')), $project);
        $after['IDholon'] = $contextHolonId;
        if ($current === $after) return ['status' => true, 'target_id' => $targetId];
        if ($current !== $before) return ['status' => false, 'conflict' => true, 'message' => 'Le projet a été modifié depuis la préparation de cette modification.'];
        if ($after['title'] === '') return ['status' => false, 'message' => 'Le titre du projet est obligatoire.'];
        self::applyProjectState($project, $after, $organizationId);
        $result = $project->save();
        return is_array($result) && !empty($result['status'])
            ? ['status' => true, 'target_id' => $targetId]
            : ['status' => false, 'message' => 'Le projet ne peut pas être modifié.'];
    }

    protected static function applyProjectState(Project $project, array $state, int $organizationId): void
    {
        $project->set('IDorganization', $organizationId);
        foreach (['IDholon', 'IDuser', 'IDproject_parent', 'title', 'description', 'status', 'blocked_reason', 'blocked_auto_reactivate', 'blocked_reactivate_status', 'capture_mode', 'project_size', 'priority', 'importance'] as $field) {
            $project->set($field, $state[$field] ?? null);
        }
        foreach (['planned_start_date', 'planned_end_date', 'blocked_until'] as $field) {
            $value = trim((string)($state[$field] ?? ''));
            $project->set($field, $value !== '' ? $value : null);
        }
        $project->set('project_kind', Project::KIND_STANDARD);
        $project->set('proposal_status', Project::PROPOSAL_NONE);
        $project->set('active', 1);
    }

    protected static function applyRecurringTaskProposal(self $proposal): array
    {
        $organizationId = (int)$proposal->get('IDorganization');
        $holonId = (int)$proposal->get('IDholon');
        $operation = (string)$proposal->get('operation');
        $targetId = (int)$proposal->get('target_id');
        if ($organizationId <= 0 || $holonId <= 0) return ['status' => false, 'conflict' => true, 'message' => 'Le contexte de la tâche récurrente est invalide.'];
        $task = new ControlActivity();
        if ($operation === self::OPERATION_CREATE) {
            if ($targetId > 0) return ['status' => false, 'conflict' => true, 'message' => 'La création de la tâche récurrente est incohérente.'];
            $state = self::normalizeRecurringTaskState(self::normalizeState($proposal->get('after_state')));
            if ($state['title'] === '') return ['status' => false, 'message' => 'Le titre de la tâche récurrente est obligatoire.'];
        } else {
            if (!$task->load($targetId) || (int)$task->get('IDorganization') !== $organizationId || (int)$task->get('IDholon') !== $holonId) {
                return ['status' => false, 'conflict' => true, 'message' => 'La tâche récurrente cible a changé de contexte.'];
            }
            $before = self::normalizeRecurringTaskState(self::normalizeState($proposal->get('before_state')), $task);
            $current = self::captureRecurringTaskState($task);
            if ($current !== $before) return ['status' => false, 'conflict' => true, 'message' => 'La tâche récurrente a été modifiée depuis la préparation de cette modification.'];
            if ($operation === self::OPERATION_DELETE) {
                return $task->deleteWithRelatedData()
                    ? ['status' => true, 'target_id' => $targetId]
                    : ['status' => false, 'message' => 'La tâche récurrente ne peut pas être supprimée.'];
            }
            $state = self::normalizeRecurringTaskState(self::normalizeState($proposal->get('after_state')), $task);
            if ($state['title'] === '') return ['status' => false, 'message' => 'Le titre de la tâche récurrente est obligatoire.'];
            if ($current === $state) return ['status' => true, 'target_id' => $targetId];
        }
        $task->set('IDorganization', $organizationId);
        $task->set('IDholon', $holonId);
        if ((int)($state['IDuser_responsible'] ?? 0) > 0 && !UserOrganization::hasActiveMembership((int)$state['IDuser_responsible'], $organizationId)) $state['IDuser_responsible'] = null;
        foreach (['IDuser_responsible', 'title', 'description', 'frequency', 'schedule', 'display_lead_value', 'display_lead_unit', 'execution_duration_value', 'execution_duration_unit'] as $field) $task->set($field, $state[$field] ?? null);
        if ($operation === self::OPERATION_CREATE) { $task->set('position', 0); $task->set('active', 1); }
        $result = $task->save();
        return is_array($result) && !empty($result['status']) && (int)$task->getId() > 0
            ? ['status' => true, 'target_id' => (int)$task->getId()]
            : ['status' => false, 'message' => 'La tâche récurrente ne peut pas être enregistrée.'];
    }

    protected static function applyIndicatorProposal(self $proposal): array
    {
        $organizationId = (int)$proposal->get('IDorganization');
        $holonId = (int)$proposal->get('IDholon');
        $operation = (string)$proposal->get('operation');
        $targetId = (int)$proposal->get('target_id');
        if ($organizationId <= 0 || $holonId <= 0) return ['status' => false, 'conflict' => true, 'message' => 'Le contexte de l’indicateur est invalide.'];
        $indicator = new StatIndicator();
        if ($operation === self::OPERATION_CREATE) {
            if ($targetId > 0) return ['status' => false, 'conflict' => true, 'message' => 'La création de l’indicateur est incohérente.'];
            try {
                $state = self::normalizeIndicatorEditorState(self::normalizeState($proposal->get('after_state')), $organizationId);
            } catch (\InvalidArgumentException $exception) {
                return ['status' => false, 'conflict' => true, 'message' => $exception->getMessage()];
            }
            if ($state['name'] === '') return ['status' => false, 'message' => 'Le nom de l’indicateur est obligatoire.'];
            $indicator->set('IDuser', (int)$proposal->get('IDuser_author') ?: null);
        } else {
            if (!$indicator->load($targetId) || (int)$indicator->get('IDorganization') !== $organizationId || (int)$indicator->get('IDholon') !== $holonId) {
                return ['status' => false, 'conflict' => true, 'message' => 'L’indicateur cible a changé de contexte.'];
            }
            $before = self::normalizeIndicatorState(self::normalizeState($proposal->get('before_state')), $indicator);
            $current = self::captureIndicatorState($indicator);
            if ($current !== $before) return ['status' => false, 'conflict' => true, 'message' => 'L’indicateur a été modifié depuis la préparation de cette modification.'];
            if ($operation === self::OPERATION_DELETE) {
                $indicator->set('active', 0);
                $result = $indicator->save();
                return is_array($result) && !empty($result['status'])
                    ? ['status' => true, 'target_id' => $targetId]
                    : ['status' => false, 'message' => 'L’indicateur ne peut pas être supprimé.'];
            }
            try {
                $state = self::normalizeIndicatorEditorState(self::normalizeState($proposal->get('after_state')), $organizationId, $indicator);
            } catch (\InvalidArgumentException $exception) {
                return ['status' => false, 'conflict' => true, 'message' => $exception->getMessage()];
            }
            if ($state['name'] === '') return ['status' => false, 'message' => 'Le nom de l’indicateur est obligatoire.'];
            if ($current === $state) return ['status' => true, 'target_id' => $targetId];
        }
        $indicator->set('IDorganization', $organizationId);
        $indicator->set('IDholon', $holonId);
        if ((int)($state['IDuser_responsible'] ?? 0) > 0 && !UserOrganization::hasActiveMembership((int)$state['IDuser_responsible'], $organizationId)) $state['IDuser_responsible'] = null;
        $sourceFields = ['source_type', 'IDdocument', 'ethercalc_cell', 'ethercalc_frequency', 'ethercalc_range', 'ethercalc_date_column', 'ethercalc_value_column', 'spreadsheet_sheet', 'spreadsheet_cell', 'spreadsheet_frequency', 'spreadsheet_range', 'spreadsheet_date_column', 'spreadsheet_value_column'];
        $sourceChanged = $operation === self::OPERATION_CREATE;
        foreach ($sourceFields as $field) {
            if ((string)$indicator->get($field) !== (string)($state[$field] ?? '')) { $sourceChanged = true; break; }
        }
        foreach (['IDuser_responsible', 'IDdocument', 'name', 'description', 'source_url', 'reference_type', 'reference_scale', 'measurement_frequency', 'measurement_schedule', 'source_type', 'ethercalc_cell', 'ethercalc_frequency', 'ethercalc_range', 'ethercalc_date_column', 'ethercalc_value_column', 'spreadsheet_sheet', 'spreadsheet_cell', 'spreadsheet_frequency', 'spreadsheet_range', 'spreadsheet_date_column', 'spreadsheet_value_column', 'chart_min_value', 'show_cumulative'] as $field) $indicator->set($field, $state[$field] ?? null);
        if ($sourceChanged) {
            $indicator->set('ethercalc_last_sync_at', null);
            $indicator->set('spreadsheet_last_sync_at', null);
        }
        $indicator->set('active', 1);
        $result = $indicator->save();
        if (!is_array($result) || empty($result['status']) || (int)$indicator->getId() <= 0) return ['status' => false, 'message' => 'L’indicateur ne peut pas être enregistré.'];
        foreach ($indicator->getReferencePoints() as $point) if ($point instanceof StatIndicatorReferencePoint && !$point->delete()) return ['status' => false, 'message' => 'Les références de l’indicateur ne peuvent pas être actualisées.'];
        foreach ($state['reference_points'] as $pointState) {
            $point = new StatIndicatorReferencePoint();
            $point->set('IDstatindicator', (int)$indicator->getId());
            $point->set('position_percent', $pointState['position_percent']);
            $point->set('value', $pointState['value']);
            $point->set('point_at', !empty($pointState['point_at']) ? new \DateTimeImmutable((string)$pointState['point_at']) : null);
            $pointResult = $point->save();
            if (!is_array($pointResult) || empty($pointResult['status'])) return ['status' => false, 'message' => 'Les références de l’indicateur ne peuvent pas être enregistrées.'];
        }
        return ['status' => true, 'target_id' => (int)$indicator->getId()];
    }
}
