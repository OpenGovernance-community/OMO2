<?php
namespace dbObject;

class Authority extends DbObject
{
    public const DELETION_DELETE = 'delete';
    public const DELETION_REASSIGN = 'reassign';

    public static function tableName()
    {
        return 'authority';
    }

    public static function rules()
    {
        return [
            [['IDholon', 'label'], 'required'],
            [['id'], 'integer'],
            [['IDholon', 'IDauthority_parent'], 'fk'],
            [['label', 'description'], 'string'],
            [['is_shell'], 'boolean'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDholon' => 'Holon responsable',
            'IDauthority_parent' => 'Autorite parente',
            'label' => 'Autorite',
            'description' => 'Description',
            'is_shell' => 'Coquille de delegation complete',
            'created_at' => 'Date de creation',
            'updated_at' => 'Date de modification',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'IDholon' => 'Holon auquel cette autorite est confiee.',
            'IDauthority_parent' => 'Domaine plus large dont cette autorite est issue.',
            'label' => 'Domaine precis sur lequel le holon peut exercer son autorite.',
            'description' => 'Description plus detaillee du domaine couvert par cette autorite.',
            'is_shell' => 'Marque une autorite conservee uniquement comme jalon apres une delegation complete.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'label' => 255,
        ];
    }

    public static function getOrder()
    {
        return 'label ASC, id ASC';
    }

    public static function getEditorCatalogForOrganization($organizationId)
    {
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return [];
        }

        $rows = self::fetchAll(
            'SELECT a.`id`, a.`label`, a.`description`, a.`is_shell`, a.`IDauthority_parent`, a.`IDholon`,
                    (SELECT COUNT(*) FROM `rule` r WHERE r.`IDauthority` = a.`id`) AS rule_count,
                    h.`name` AS holon_name, h.`nomcomplet` AS holon_full_name
             FROM `authority` a
             INNER JOIN `holon` h ON h.`id` = a.`IDholon`
             LEFT JOIN `holon` root ON root.`id` = h.`IDholon_org`
             WHERE COALESCE(NULLIF(h.`IDorganization`, 0), root.`IDorganization`, 0) = :organization_id
             ORDER BY a.`label` ASC, a.`id` ASC',
            ['organization_id' => $organizationId]
        );
        if (!is_array($rows)) {
            return [];
        }

        $catalogById = [];
        foreach ($rows as $row) {
            $authorityId = (int)($row['id'] ?? 0);
            if ($authorityId <= 0) {
                continue;
            }

            $catalogById[$authorityId] = [
                'id' => $authorityId,
                'label' => trim((string)($row['label'] ?? '')),
                'description' => trim((string)($row['description'] ?? '')),
                'isShell' => !empty($row['is_shell']),
                'parentId' => (int)($row['IDauthority_parent'] ?? 0),
                'holonId' => (int)($row['IDholon'] ?? 0),
                'ruleCount' => (int)($row['rule_count'] ?? 0),
                'holonLabel' => trim((string)($row['holon_full_name'] ?? '')) ?: trim((string)($row['holon_name'] ?? '')),
            ];
        }

        foreach ($catalogById as $authorityId => $entry) {
            $labels = [];
            $visited = [];
            $currentId = $authorityId;
            while ($currentId > 0 && isset($catalogById[$currentId]) && !isset($visited[$currentId])) {
                $visited[$currentId] = true;
                array_unshift($labels, (string)$catalogById[$currentId]['label']);
                $currentId = (int)$catalogById[$currentId]['parentId'];
            }

            $catalogById[$authorityId]['pathLabel'] = implode(' > ', array_filter($labels, static function ($label) {
                return trim((string)$label) !== '';
            }));
        }

        return array_values($catalogById);
    }

    public function save()
    {
        $holonId = (int)$this->get('IDholon');
        $parentId = (int)$this->get('IDauthority_parent');
        $authorityId = (int)$this->getId();
        $label = trim((string)$this->get('label'));
        $description = trim((string)$this->get('description'));

        if ($holonId <= 0 || $label === '') {
            return ['status' => false, 'text' => 'An authority requires a holon and a label.'];
        }

        $this->set('label', $label);
        $this->set('description', $description !== '' ? $description : null);
        $this->set('IDauthority_parent', $parentId > 0 ? $parentId : null);
        $this->set('is_shell', !empty($this->get('is_shell')) ? 1 : 0);

        if ($parentId > 0) {
            if ($authorityId > 0 && $parentId === $authorityId) {
                return ['status' => false, 'text' => 'An authority cannot be its own parent.'];
            }

            $parent = new self();
            if (!$parent->load($parentId)) {
                return ['status' => false, 'text' => 'The selected parent authority does not exist.'];
            }

            if (!$this->canUseAsParent($parent)) {
                return ['status' => false, 'text' => 'The selected parent would create an invalid authority tree.'];
            }

            $authorityOrganizationId = self::resolveOrganizationIdForHolon($holonId);
            $parentOrganizationId = self::resolveOrganizationIdForHolon((int)$parent->get('IDholon'));
            if (
                $authorityOrganizationId <= 0
                || $parentOrganizationId <= 0
                || $authorityOrganizationId !== $parentOrganizationId
            ) {
                return ['status' => false, 'text' => 'An authority parent must belong to the same organization.'];
            }
        }

        $now = new \DateTime();
        if ($authorityId <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        $this->set('updated_at', $now);

        return parent::save();
    }

    public function getHolon()
    {
        $holon = new Holon();
        return $holon->load((int)$this->get('IDholon')) ? $holon : null;
    }

    public function getParent()
    {
        $parentId = (int)$this->get('IDauthority_parent');
        if ($parentId <= 0) {
            return null;
        }

        $parent = new self();
        return $parent->load($parentId) ? $parent : null;
    }

    public function getChildren()
    {
        $children = new ArrayAuthority();
        $children->loadForParent((int)$this->getId());
        return $children;
    }

    public function getRules()
    {
        $rules = new ArrayRule();
        $rules->loadForAuthority((int)$this->getId());
        return $rules;
    }

    public function getOrganizationId()
    {
        return self::resolveOrganizationIdForHolon((int)$this->get('IDholon'));
    }

    public function canEdit()
    {
        $holon = $this->getHolon();
        return $holon instanceof Holon && $holon->canEdit();
    }

    public function canUseAsParent(Authority $parent)
    {
        $authorityId = (int)$this->getId();
        $parentId = (int)$parent->getId();
        if ($parentId <= 0 || ($authorityId > 0 && $parentId === $authorityId)) {
            return false;
        }

        $visited = [];
        $current = $parent;
        while ($current instanceof self && (int)$current->getId() > 0) {
            $currentId = (int)$current->getId();
            if (isset($visited[$currentId]) || ($authorityId > 0 && $currentId === $authorityId)) {
                return false;
            }
            $visited[$currentId] = true;
            $current = $current->getParent();
        }

        return true;
    }

    public function isShell()
    {
        return !empty($this->get('is_shell'));
    }

    public function delegateCompletelyToHolon(Holon $targetHolon)
    {
        $authorityId = (int)$this->getId();
        $sourceHolonId = (int)$this->get('IDholon');
        $targetHolonId = (int)$targetHolon->getId();
        $targetParent = $targetHolon->getParentHolon();
        if ($authorityId <= 0 || $sourceHolonId <= 0 || $targetHolonId <= 0) {
            return ['status' => false, 'text' => 'The authority delegation is incomplete.'];
        }
        if (!$targetParent instanceof Holon || (int)$targetParent->getId() !== $sourceHolonId) {
            return ['status' => false, 'text' => 'A complete delegation must target a direct child holon.'];
        }

        $parentAuthority = $this->getParent();
        $canMoveAuthority = $parentAuthority instanceof self
            && (int)$parentAuthority->get('IDholon') === $sourceHolonId;
        $subtreeRows = $this->getSubtreeRows();
        if ($subtreeRows === false) {
            return ['status' => false, 'text' => 'The delegated authority tree could not be loaded.'];
        }
        $descendantIds = array_values(array_filter(array_map(static function ($row) {
            return (int)($row['id'] ?? 0);
        }, $subtreeRows)));
        $pdo = self::getPdo();
        if (!$pdo) {
            return ['status' => false, 'text' => 'The database connection is unavailable.'];
        }

        try {
            $pdo->beginTransaction();
            if ($canMoveAuthority) {
                $this->set('IDholon', $targetHolonId);
                $this->set('is_shell', 0);
                $saveResult = $this->save();
                if (empty($saveResult['status'])) {
                    throw new \RuntimeException((string)($saveResult['text'] ?? 'The authority could not be moved.'));
                }
                foreach ($descendantIds as $descendantId) {
                    $descendant = new self();
                    if (!$descendant->load($descendantId)) {
                        throw new \RuntimeException('A delegated sub-authority no longer exists.');
                    }
                    $descendant->set('IDholon', $targetHolonId);
                    $saveResult = $descendant->save();
                    if (empty($saveResult['status'])) {
                        throw new \RuntimeException((string)($saveResult['text'] ?? 'A delegated sub-authority could not be moved.'));
                    }
                }
                $referenceResult = $this->moveAuthorityPropertyReferences(array_merge([$authorityId], $descendantIds), $targetHolonId);
                if (empty($referenceResult['status'])) {
                    throw new \RuntimeException((string)($referenceResult['text'] ?? 'The authority property references could not be moved.'));
                }
                $delegatedAuthority = $this;
                $createdShell = false;
                $delegatedAuthorityIds = array_merge([$authorityId], $descendantIds);
            } else {
                $this->set('is_shell', 1);
                $saveResult = $this->save();
                if (empty($saveResult['status'])) {
                    throw new \RuntimeException((string)($saveResult['text'] ?? 'The authority shell could not be saved.'));
                }

                $delegatedAuthority = new self();
                $delegatedAuthority->set('IDholon', $targetHolonId);
                $delegatedAuthority->set('IDauthority_parent', $authorityId);
                $delegatedAuthority->set('label', (string)$this->get('label'));
                $delegatedAuthority->set('description', $this->get('description'));
                $delegatedAuthority->set('is_shell', 0);
                $saveResult = $delegatedAuthority->save();
                if (empty($saveResult['status']) || (int)$delegatedAuthority->getId() <= 0) {
                    throw new \RuntimeException((string)($saveResult['text'] ?? 'The delegated authority could not be created.'));
                }
                $referenceResult = $this->moveAuthorityPropertyReferences(
                    [$authorityId],
                    $targetHolonId,
                    [$authorityId => (int)$delegatedAuthority->getId()],
                    false
                );
                if (empty($referenceResult['status'])) {
                    throw new \RuntimeException((string)($referenceResult['text'] ?? 'The authority property references could not be copied.'));
                }
                $movedRuleIds = [];
                $shellRules = new ArrayRule();
                $shellRules->loadForAuthority($authorityId);
                foreach ($shellRules as $shellRule) {
                    if (!$shellRule instanceof Rule) {
                        continue;
                    }
                    $shellRule->set('IDauthority', (int)$delegatedAuthority->getId());
                    $shellRule->set('IDholon', null);
                    $saveResult = $shellRule->save();
                    if (empty($saveResult['status'])) {
                        throw new \RuntimeException((string)($saveResult['text'] ?? 'A rule could not be moved to the delegated authority.'));
                    }
                    $movedRuleIds[] = (int)$shellRule->getId();
                }
                foreach ($subtreeRows as $subtreeRow) {
                    $descendantId = (int)($subtreeRow['id'] ?? 0);
                    if ($descendantId <= 0) {
                        continue;
                    }
                    $descendant = new self();
                    if (!$descendant->load($descendantId)) {
                        throw new \RuntimeException('A delegated sub-authority no longer exists.');
                    }
                    $descendant->set('IDholon', $targetHolonId);
                    if ((int)$descendant->get('IDauthority_parent') === $authorityId) {
                        $descendant->set('IDauthority_parent', (int)$delegatedAuthority->getId());
                    }
                    $saveResult = $descendant->save();
                    if (empty($saveResult['status'])) {
                        throw new \RuntimeException((string)($saveResult['text'] ?? 'A delegated sub-authority could not be moved.'));
                    }
                }
                if (!empty($descendantIds)) {
                    $descendantReferenceResult = $this->moveAuthorityPropertyReferences($descendantIds, $targetHolonId);
                    if (empty($descendantReferenceResult['status'])) {
                        throw new \RuntimeException((string)($descendantReferenceResult['text'] ?? 'The sub-authority property references could not be moved.'));
                    }
                    $referenceResult['movedCount'] = (int)($referenceResult['movedCount'] ?? 0) + (int)($descendantReferenceResult['movedCount'] ?? 0);
                }
                $createdShell = true;
                $delegatedAuthorityIds = array_merge([(int)$delegatedAuthority->getId()], $descendantIds);
            }

            if (!isset($movedRuleIds)) {
                $movedRuleIds = [];
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            self::registerDbError('authority_complete_delegation', ['authority_id' => $authorityId], $exception);
            return ['status' => false, 'text' => $exception->getMessage()];
        }

        unset(self::$preload['authority_' . $authorityId]);
        unset(self::$preload['authority_' . (int)$delegatedAuthority->getId()]);
        return [
            'status' => true,
            'authorityId' => (int)$delegatedAuthority->getId(),
            'authorityIds' => array_values(array_unique(array_map('intval', $delegatedAuthorityIds))),
            'sourceAuthorityId' => $authorityId,
            'createdShell' => $createdShell,
            'movedDescendantCount' => count($descendantIds),
            'movedPropertyReferenceCount' => (int)($referenceResult['movedCount'] ?? 0),
            'movedRuleIds' => $movedRuleIds,
        ];
    }

    public static function deletionDispositions()
    {
        return [self::DELETION_DELETE, self::DELETION_REASSIGN];
    }

    public static function normalizeDeletionDisposition($value, $fallback = self::DELETION_DELETE)
    {
        $value = trim((string)$value);
        $fallback = in_array($fallback, self::deletionDispositions(), true) ? $fallback : self::DELETION_DELETE;
        return in_array($value, self::deletionDispositions(), true) ? $value : $fallback;
    }

    /**
     * Preserve authorities and rules when their owning holon is removed.
     * Authorities move to the structural parent, while an active copy created
     * by a complete delegation is collapsed back into its source shell.
     */
    public static function reassignForHolonDeletion(Holon $sourceHolon, Holon $targetHolon)
    {
        $sourceHolonId = (int)$sourceHolon->getId();
        $targetHolonId = (int)$targetHolon->getId();
        if ($sourceHolonId <= 0 || $targetHolonId <= 0 || $sourceHolonId === $targetHolonId) {
            return ['status' => false, 'text' => 'The authority transfer requires two distinct holons.'];
        }

        $authorityRows = self::fetchAll(
            'SELECT `id`, `IDauthority_parent`
             FROM `authority`
             WHERE `IDholon` = :source_holon_id
             ORDER BY `id` ASC',
            ['source_holon_id' => $sourceHolonId]
        );
        if (!is_array($authorityRows)) {
            return ['status' => false, 'text' => 'The holon authorities could not be loaded.'];
        }

        $localRuleRows = self::fetchAll(
            'SELECT `id`
             FROM `rule`
             WHERE `IDholon` = :source_holon_id
             ORDER BY `id` ASC',
            ['source_holon_id' => $sourceHolonId]
        );
        if (!is_array($localRuleRows)) {
            return ['status' => false, 'text' => 'The holon local rules could not be loaded.'];
        }

        if (count($authorityRows) === 0 && count($localRuleRows) === 0) {
            return ['status' => true, 'movedAuthorityIds' => [], 'movedLocalRuleIds' => [], 'reactivatedShellIds' => []];
        }

        $authoritiesById = [];
        $completeDelegationCopies = [];
        foreach ($authorityRows as $row) {
            $authorityId = (int)($row['id'] ?? 0);
            if ($authorityId <= 0) {
                continue;
            }

            $authority = new self();
            if (!$authority->load($authorityId)) {
                return ['status' => false, 'text' => 'An authority to preserve no longer exists.'];
            }
            $authoritiesById[$authorityId] = $authority;

            $parent = $authority->getParent();
            if (
                $parent instanceof self
                && $parent->isShell()
                && (int)$parent->get('IDholon') === $targetHolonId
            ) {
                $completeDelegationCopies[$authorityId] = $parent;
            }
        }

        $pdo = self::getPdo();
        if (!$pdo) {
            return ['status' => false, 'text' => 'The database connection is unavailable.'];
        }

        $ownsTransaction = !$pdo->inTransaction();
        $propertyReferenceResult = ['movedCount' => 0];
        try {
            if ($ownsTransaction) {
                $pdo->beginTransaction();
            }

            $replacementParentIds = [];
            $reactivatedShellIds = [];
            $movedAuthorityIds = [];
            $movedLocalRuleIds = [];
            foreach ($completeDelegationCopies as $copyAuthorityId => $shellAuthority) {
                $shellAuthority->set('is_shell', 0);
                $saveResult = $shellAuthority->save();
                if (empty($saveResult['status'])) {
                    throw new \RuntimeException((string)($saveResult['text'] ?? 'The delegated authority source could not be restored.'));
                }

                $replacementParentIds[(int)$copyAuthorityId] = (int)$shellAuthority->getId();
                $reactivatedShellIds[] = (int)$shellAuthority->getId();

                $copyRules = self::fetchAll(
                    'SELECT `id` FROM `rule` WHERE `IDauthority` = :authority_id ORDER BY `id` ASC',
                    ['authority_id' => (int)$copyAuthorityId]
                );
                if (!is_array($copyRules)) {
                    throw new \RuntimeException('The delegated authority rules could not be loaded.');
                }
                foreach ($copyRules as $copyRuleRow) {
                    $rule = new Rule();
                    if (!$rule->load((int)($copyRuleRow['id'] ?? 0))) {
                        throw new \RuntimeException('A delegated authority rule no longer exists.');
                    }
                    $rule->set('IDauthority', (int)$shellAuthority->getId());
                    $rule->set('IDholon', null);
                    $saveResult = $rule->save();
                    if (empty($saveResult['status'])) {
                        throw new \RuntimeException((string)($saveResult['text'] ?? 'A delegated authority rule could not be preserved.'));
                    }
                }
            }

            foreach ($authoritiesById as $authorityId => $authority) {
                if (isset($completeDelegationCopies[$authorityId])) {
                    continue;
                }

                $parentAuthorityId = (int)$authority->get('IDauthority_parent');
                $authority->set('IDholon', $targetHolonId);
                if (isset($replacementParentIds[$parentAuthorityId])) {
                    $authority->set('IDauthority_parent', $replacementParentIds[$parentAuthorityId]);
                }
                $saveResult = $authority->save();
                if (empty($saveResult['status'])) {
                    throw new \RuntimeException((string)($saveResult['text'] ?? 'An authority could not be preserved.'));
                }
                $movedAuthorityIds[] = (int)$authorityId;
            }

            $helperAuthority = new self();
            $propertyReferenceResult = $helperAuthority->moveAuthorityPropertyReferences($movedAuthorityIds, $targetHolonId);
            if (empty($propertyReferenceResult['status'])) {
                throw new \RuntimeException((string)($propertyReferenceResult['text'] ?? 'The authority property references could not be preserved.'));
            }

            foreach ($localRuleRows as $localRuleRow) {
                $ruleId = (int)($localRuleRow['id'] ?? 0);
                $rule = new Rule();
                if ($ruleId <= 0 || !$rule->load($ruleId)) {
                    throw new \RuntimeException('A local rule to preserve no longer exists.');
                }
                $rule->set('IDauthority', null);
                $rule->set('IDholon', $targetHolonId);
                $saveResult = $rule->save();
                if (empty($saveResult['status'])) {
                    throw new \RuntimeException((string)($saveResult['text'] ?? 'A local rule could not be preserved.'));
                }
                $movedLocalRuleIds[] = $ruleId;
            }

            foreach ($completeDelegationCopies as $copyAuthorityId => $shellAuthority) {
                if (!self::execute(
                    'DELETE FROM `authority` WHERE `id` = :authority_id',
                    ['authority_id' => (int)$copyAuthorityId]
                )) {
                    throw new \RuntimeException('The delegated authority copy could not be removed.');
                }
            }

            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            self::registerDbError('authority_holon_delete_transfer', ['source_holon_id' => $sourceHolonId, 'target_holon_id' => $targetHolonId], $exception);
            return ['status' => false, 'text' => $exception->getMessage()];
        }

        foreach (array_keys($authoritiesById) as $authorityId) {
            unset(self::$preload['authority_' . (int)$authorityId]);
        }
        foreach ($reactivatedShellIds as $shellAuthorityId) {
            unset(self::$preload['authority_' . (int)$shellAuthorityId]);
        }
        foreach ($movedLocalRuleIds as $ruleId) {
            unset(self::$preload['rule_' . (int)$ruleId]);
        }

        return [
            'status' => true,
            'movedAuthorityIds' => $movedAuthorityIds,
            'movedLocalRuleIds' => $movedLocalRuleIds,
            'reactivatedShellIds' => array_values(array_unique($reactivatedShellIds)),
            'movedPropertyReferenceCount' => (int)($propertyReferenceResult['movedCount'] ?? 0),
        ];
    }

    public function applyDeletionPlan(array $plan)
    {
        $authorityId = (int)$this->getId();
        $ownerHolonId = (int)$this->get('IDholon');
        if ($authorityId <= 0 || $ownerHolonId <= 0) {
            return ['status' => false, 'text' => 'The authority to process does not exist.'];
        }

        $authorityDisposition = self::normalizeDeletionDisposition($plan['authority'] ?? self::DELETION_REASSIGN, self::DELETION_REASSIGN);
        $childrenDisposition = self::normalizeDeletionDisposition($plan['children'] ?? self::DELETION_REASSIGN, self::DELETION_REASSIGN);
        $rulesDisposition = self::normalizeDeletionDisposition($plan['rules'] ?? self::DELETION_REASSIGN, self::DELETION_REASSIGN);

        $ownerHolon = $this->getHolon();
        $parentHolon = $ownerHolon instanceof Holon ? $ownerHolon->getParentHolon() : null;
        $parentHolonId = $parentHolon instanceof Holon ? (int)$parentHolon->getId() : 0;
        $parentAuthorityId = (int)$this->get('IDauthority_parent');
        $parentAuthority = $this->getParent();
        $reversesCompleteDelegation = $parentAuthority instanceof self
            && $parentAuthority->isShell()
            && $parentHolonId > 0
            && (int)$parentAuthority->get('IDholon') === $parentHolonId;

        // A direct child of a shell is the active copy created by a complete
        // delegation. Removing it must restore the source authority, rather
        // than leave an inactive shell at the parent level.
        if ($reversesCompleteDelegation) {
            $authorityDisposition = self::DELETION_DELETE;
        }

        $subtreeRows = $this->getSubtreeRows();
        if ($subtreeRows === false) {
            return ['status' => false, 'text' => 'The authority tree could not be loaded.'];
        }

        $mustMoveAuthorities = $authorityDisposition === self::DELETION_REASSIGN
            || ($childrenDisposition === self::DELETION_REASSIGN && !empty($subtreeRows));
        if ($mustMoveAuthorities && $parentHolonId <= 0) {
            return ['status' => false, 'text' => 'An authority can only be moved to an existing parent holon.'];
        }

        $allRows = [[
            'id' => $authorityId,
            'IDauthority_parent' => $parentAuthorityId,
        ]];
        foreach ($subtreeRows as $row) {
            $allRows[] = $row;
        }

        $rowsById = [];
        foreach ($allRows as $row) {
            $rowId = (int)($row['id'] ?? 0);
            if ($rowId > 0) {
                $rowsById[$rowId] = [
                    'id' => $rowId,
                    'IDauthority_parent' => (int)($row['IDauthority_parent'] ?? 0),
                ];
            }
        }

        $deletedAuthorityIds = [];
        $movedAuthorityIds = [];
        if ($authorityDisposition === self::DELETION_DELETE) {
            $deletedAuthorityIds[$authorityId] = true;
        } else {
            $movedAuthorityIds[$authorityId] = true;
        }
        foreach ($subtreeRows as $row) {
            $rowId = (int)($row['id'] ?? 0);
            if ($rowId <= 0) {
                continue;
            }
            if ($childrenDisposition === self::DELETION_DELETE) {
                $deletedAuthorityIds[$rowId] = true;
            } else {
                $movedAuthorityIds[$rowId] = true;
            }
        }

        $deletedAuthorityIds = array_keys($deletedAuthorityIds);
        $movedAuthorityIds = array_keys($movedAuthorityIds);
        $ruleRows = $this->getRulesForAuthorities($deletedAuthorityIds);
        if ($ruleRows === false) {
            return ['status' => false, 'text' => 'The rules attached to this authority could not be loaded.'];
        }

        if ($rulesDisposition === self::DELETION_REASSIGN && !empty($ruleRows) && $parentHolonId <= 0 && $parentAuthorityId <= 0) {
            return ['status' => false, 'text' => 'Rules can only be moved when a parent holon or authority exists.'];
        }

        $pdo = self::getPdo();
        if (!$pdo) {
            return ['status' => false, 'text' => 'The database connection is unavailable.'];
        }

        $nearestSurvivingAuthority = static function ($sourceAuthorityId) use ($rowsById, $movedAuthorityIds) {
            $movedById = array_fill_keys($movedAuthorityIds, true);
            $currentId = (int)$sourceAuthorityId;
            $visited = [];
            while ($currentId > 0 && !isset($visited[$currentId])) {
                $visited[$currentId] = true;
                $parentId = isset($rowsById[$currentId]) ? (int)$rowsById[$currentId]['IDauthority_parent'] : 0;
                if ($parentId <= 0) {
                    return 0;
                }
                if (isset($movedById[$parentId])) {
                    return $parentId;
                }
                if (!isset($rowsById[$parentId])) {
                    return $parentId;
                }
                $currentId = $parentId;
            }

            return 0;
        };

        try {
            $pdo->beginTransaction();

            if ($reversesCompleteDelegation) {
                $parentAuthority->set('is_shell', 0);
                $saveResult = $parentAuthority->save();
                if (empty($saveResult['status'])) {
                    throw new \RuntimeException((string)($saveResult['text'] ?? 'The delegated authority source could not be restored.'));
                }
            }

            foreach ($movedAuthorityIds as $movedAuthorityId) {
                $movedAuthority = new self();
                if (!$movedAuthority->load((int)$movedAuthorityId)) {
                    throw new \RuntimeException('The authority to move no longer exists.');
                }

                $movedAuthority->set('IDholon', $parentHolonId);
                $movedAuthority->set('IDauthority_parent', $nearestSurvivingAuthority($movedAuthorityId) ?: null);
                $saveResult = $movedAuthority->save();
                if (empty($saveResult['status'])) {
                    throw new \RuntimeException((string)($saveResult['text'] ?? 'The authority could not be moved.'));
                }
            }

            $propertyReferenceResult = $this->moveAuthorityPropertyReferences($movedAuthorityIds, $parentHolonId);
            if (empty($propertyReferenceResult['status'])) {
                throw new \RuntimeException((string)($propertyReferenceResult['text'] ?? 'The authority property references could not be moved.'));
            }

            $deletedRuleIds = [];
            $movedRuleIds = [];
            foreach ($ruleRows as $ruleRow) {
                $ruleId = (int)($ruleRow['id'] ?? 0);
                $ruleAuthorityId = (int)($ruleRow['IDauthority'] ?? 0);
                if ($ruleId <= 0) {
                    continue;
                }

                if ($rulesDisposition === self::DELETION_DELETE) {
                    $rule = new Rule();
                    if (!$rule->load($ruleId) || !$rule->delete()) {
                        throw new \RuntimeException('A rule could not be deleted.');
                    }
                    $deletedRuleIds[] = $ruleId;
                    continue;
                }

                $rule = new Rule();
                if (!$rule->load($ruleId)) {
                    throw new \RuntimeException('A rule to move no longer exists.');
                }

                $targetAuthorityId = $nearestSurvivingAuthority($ruleAuthorityId);
                if ($targetAuthorityId > 0) {
                    $rule->set('IDauthority', $targetAuthorityId);
                    $rule->set('IDholon', null);
                } else {
                    $rule->set('IDauthority', null);
                    $rule->set('IDholon', $parentHolonId);
                }
                $reviewDate = new \DateTimeImmutable('today');
                $rule->set('review_date', $reviewDate->format('Y-m-d'));
                $rule->set('expiration_date', $reviewDate->modify('+2 months')->format('Y-m-d'));
                $saveResult = $rule->save();
                if (empty($saveResult['status'])) {
                    throw new \RuntimeException((string)($saveResult['text'] ?? 'A rule could not be moved.'));
                }
                $movedRuleIds[] = $ruleId;
            }

            if (!empty($deletedAuthorityIds)) {
                // Delete deepest descendants first because the parent foreign key is restrictive.
                foreach (array_reverse($deletedAuthorityIds) as $deletedAuthorityId) {
                    if (!self::execute(
                        'DELETE FROM `authority` WHERE `id` = :authority_id',
                        ['authority_id' => (int)$deletedAuthorityId]
                    )) {
                        throw new \RuntimeException('An authority could not be deleted.');
                    }
                }
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            self::registerDbError('authority_delete_transaction', ['authority_id' => $authorityId], $exception);
            return ['status' => false, 'text' => $exception->getMessage()];
        }

        foreach ($allRows as $row) {
            unset(self::$preload['authority_' . (int)($row['id'] ?? 0)]);
        }
        if ($reversesCompleteDelegation) {
            unset(self::$preload['authority_' . (int)$parentAuthority->getId()]);
        }
        foreach ($ruleRows as $ruleRow) {
            unset(self::$preload['rule_' . (int)($ruleRow['id'] ?? 0)]);
        }

        return [
            'status' => true,
            'authorityRetained' => $authorityDisposition === self::DELETION_REASSIGN,
            'deletedAuthorityIds' => $deletedAuthorityIds,
            'movedAuthorityIds' => $movedAuthorityIds,
            'deletedRuleIds' => $deletedRuleIds,
            'movedRuleIds' => $movedRuleIds,
            'movedPropertyReferenceCount' => (int)($propertyReferenceResult['movedCount'] ?? 0),
            'reactivatedShellId' => $reversesCompleteDelegation ? (int)$parentAuthority->getId() : 0,
            'plan' => [
                'authority' => $authorityDisposition,
                'children' => $childrenDisposition,
                'rules' => $rulesDisposition,
            ],
        ];
    }

    public function delete()
    {
        $result = $this->applyDeletionPlan([
            'authority' => self::DELETION_DELETE,
            'children' => self::DELETION_REASSIGN,
            'rules' => self::DELETION_REASSIGN,
        ]);

        return !empty($result['status']);
    }

    protected function getSubtreeRows()
    {
        $rootId = (int)$this->getId();
        if ($rootId <= 0) {
            return [];
        }

        $rows = [];
        $pendingIds = [$rootId];
        $visited = [$rootId => true];
        while (!empty($pendingIds)) {
            $currentId = (int)array_shift($pendingIds);
            $children = self::fetchAll(
                'SELECT `id`, `IDauthority_parent` FROM `authority` WHERE `IDauthority_parent` = :authority_id ORDER BY `id` ASC',
                ['authority_id' => $currentId]
            );
            if (!is_array($children)) {
                return false;
            }

            foreach ($children as $child) {
                $childId = (int)($child['id'] ?? 0);
                if ($childId <= 0 || isset($visited[$childId])) {
                    continue;
                }
                $visited[$childId] = true;
                $rows[] = $child;
                $pendingIds[] = $childId;
            }
        }

        return $rows;
    }

    protected function getRulesForAuthorities(array $authorityIds)
    {
        $authorityIds = array_values(array_unique(array_filter(array_map('intval', $authorityIds))));
        if (empty($authorityIds)) {
            return [];
        }

        $params = [];
        $placeholders = [];
        foreach ($authorityIds as $index => $authorityId) {
            $key = 'authority_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $authorityId;
        }

        return self::fetchAll(
            'SELECT `id`, `IDauthority` FROM `rule` WHERE `IDauthority` IN (' . implode(', ', $placeholders) . ') ORDER BY `id` ASC',
            $params
        );
    }

    protected function moveAuthorityPropertyReferences(array $authorityIds, $targetHolonId, array $replacementAuthorityIds = [], $removeFromSources = true)
    {
        $authorityIds = array_values(array_unique(array_filter(array_map('intval', $authorityIds))));
        $targetHolonId = (int)$targetHolonId;
        if (empty($authorityIds) || $targetHolonId <= 0) {
            return ['status' => true, 'movedCount' => 0];
        }

        $rows = self::fetchAll(
            'SELECT hp.`id`, hp.`IDholon`, hp.`IDproperty`, hp.`value`, hp.`position`, hp.`mandatory`, hp.`locked`,
                    p.`IDpropertyformat`
             FROM `holonproperty` hp
             INNER JOIN `property` p ON p.`id` = hp.`IDproperty`
             WHERE hp.`active` = 1
               AND p.`listitemtype` = :list_item_type
               AND p.`IDpropertyformat` IN (:format_list, :format_html_list)',
            [
                'list_item_type' => Property::LIST_ITEM_AUTHORITY,
                'format_list' => PropertyFormat::FORMAT_LIST,
                'format_html_list' => PropertyFormat::FORMAT_HTML_LIST,
            ]
        );
        if (!is_array($rows)) {
            return ['status' => false, 'text' => 'The authority property references could not be loaded.'];
        }

        $authorityLookup = array_fill_keys($authorityIds, true);
        $replacementAuthorityIds = array_filter(array_map('intval', $replacementAuthorityIds));
        $targetItemsByPropertyId = [];
        $sourceUpdates = [];
        $movedCount = 0;
        foreach ($rows as $row) {
            $sourceHolonId = (int)($row['IDholon'] ?? 0);
            if ($sourceHolonId <= 0 || $sourceHolonId === $targetHolonId) {
                continue;
            }

            $formatId = (int)($row['IDpropertyformat'] ?? 0);
            $items = $this->parseAuthorityPropertyItems($formatId, $row['value'] ?? '');
            $remainingItems = [];
            $movedItems = [];
            foreach ($items as $item) {
                $itemAuthorityId = $this->getAuthorityPropertyItemId($item);
                if ($itemAuthorityId > 0 && isset($authorityLookup[$itemAuthorityId])) {
                    $movedItems[] = $this->replaceAuthorityPropertyItemId(
                        $item,
                        $replacementAuthorityIds[$itemAuthorityId] ?? $itemAuthorityId
                    );
                    $movedCount += 1;
                } else {
                    $remainingItems[] = $item;
                }
            }
            if (empty($movedItems)) {
                continue;
            }

            $propertyId = (int)($row['IDproperty'] ?? 0);
            if ($propertyId <= 0) {
                continue;
            }
            if (!isset($targetItemsByPropertyId[$propertyId])) {
                $targetItemsByPropertyId[$propertyId] = [
                    'formatId' => $formatId,
                    'items' => [],
                    'source' => $row,
                ];
            }
            foreach ($movedItems as $movedItem) {
                $targetItemsByPropertyId[$propertyId]['items'][] = $movedItem;
            }
            if ($removeFromSources) {
                $sourceUpdates[] = [
                    'row' => $row,
                    'formatId' => $formatId,
                    'items' => $remainingItems,
                ];
            }
        }

        foreach ($sourceUpdates as $sourceUpdate) {
            $sourceProperty = new HolonProperty();
            if (!$sourceProperty->load((int)$sourceUpdate['row']['id'])) {
                return ['status' => false, 'text' => 'An authority property reference no longer exists.'];
            }
            $value = $this->serializeAuthorityPropertyItems((int)$sourceUpdate['formatId'], $sourceUpdate['items'], $sourceProperty->get('value'));
            if (PropertyFormat::isEmptyValue((int)$sourceUpdate['formatId'], $value)) {
                $sourceProperty->set('active', false);
                $sourceProperty->set('value', null);
            } else {
                $sourceProperty->set('value', $value);
            }
            $saveResult = $sourceProperty->save();
            if (empty($saveResult['status'])) {
                return ['status' => false, 'text' => 'An authority property reference could not be removed.'];
            }
        }

        foreach ($targetItemsByPropertyId as $propertyId => $targetData) {
            $targetRow = self::fetchRow(
                'SELECT `id` FROM `holonproperty` WHERE `IDholon` = :holon_id AND `IDproperty` = :property_id LIMIT 1',
                ['holon_id' => $targetHolonId, 'property_id' => (int)$propertyId]
            );
            $targetProperty = new HolonProperty();
            $targetItems = [];
            if (is_array($targetRow) && (int)($targetRow['id'] ?? 0) > 0) {
                if (!$targetProperty->load((int)$targetRow['id'])) {
                    return ['status' => false, 'text' => 'The target authority property could not be loaded.'];
                }
                $targetItems = $this->parseAuthorityPropertyItems((int)$targetData['formatId'], $targetProperty->get('value'));
            } else {
                $sourceRow = $targetData['source'];
                $targetProperty->set('IDholon', $targetHolonId);
                $targetProperty->set('IDproperty', (int)$propertyId);
                $targetProperty->set('position', (int)($sourceRow['position'] ?? 0));
                $targetProperty->set('mandatory', false);
                $targetProperty->set('locked', false);
            }

            foreach ($targetData['items'] as $movedItem) {
                $movedItemId = $this->getAuthorityPropertyItemId($movedItem);
                $alreadyPresent = false;
                foreach ($targetItems as $targetItem) {
                    if ($movedItemId > 0 && $this->getAuthorityPropertyItemId($targetItem) === $movedItemId) {
                        $alreadyPresent = true;
                        break;
                    }
                }
                if (!$alreadyPresent) {
                    $targetItems[] = $movedItem;
                }
            }

            $targetProperty->set('value', $this->serializeAuthorityPropertyItems((int)$targetData['formatId'], $targetItems, $targetProperty->get('value')));
            $targetProperty->set('active', true);
            $saveResult = $targetProperty->save();
            if (empty($saveResult['status'])) {
                return ['status' => false, 'text' => 'An authority property reference could not be assigned to the parent holon.'];
            }
        }

        return ['status' => true, 'movedCount' => $movedCount];
    }

    protected function parseAuthorityPropertyItems($formatId, $value)
    {
        if ((int)$formatId === PropertyFormat::FORMAT_HTML_LIST) {
            return PropertyFormat::getHtmlListParts($value)['items'];
        }

        $decoded = is_array($value) ? $value : json_decode((string)$value, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }

    protected function serializeAuthorityPropertyItems($formatId, array $items, $existingValue = '')
    {
        if ((int)$formatId === PropertyFormat::FORMAT_HTML_LIST) {
            $parts = PropertyFormat::getHtmlListParts($existingValue);
            $parts['items'] = array_values($items);
            return PropertyFormat::normalizeValueForStorage($formatId, $parts);
        }

        return json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function getAuthorityPropertyItemId($item)
    {
        return is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
    }

    protected function replaceAuthorityPropertyItemId($item, $authorityId)
    {
        $authorityId = (int)$authorityId;
        if (is_array($item)) {
            $item['id'] = $authorityId;
            return $item;
        }

        return $authorityId;
    }

    protected static function resolveOrganizationIdForHolon($holonId)
    {
        $holonId = (int)$holonId;
        if ($holonId <= 0) {
            return 0;
        }

        return (int)self::fetchValue(
            'SELECT COALESCE(NULLIF(h.`IDorganization`, 0), root.`IDorganization`, 0)
             FROM `holon` h
             LEFT JOIN `holon` root ON root.`id` = h.`IDholon_org`
             WHERE h.`id` = :holon_id
             LIMIT 1',
            ['holon_id' => $holonId]
        );
    }
}

?>
