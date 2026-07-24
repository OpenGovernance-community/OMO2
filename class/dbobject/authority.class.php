<?php
namespace dbObject;

class Authority extends DbObject
{
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
            [['label'], 'string'],
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

    public function save()
    {
        $holonId = (int)$this->get('IDholon');
        $parentId = (int)$this->get('IDauthority_parent');
        $authorityId = (int)$this->getId();
        $label = trim((string)$this->get('label'));

        if ($holonId <= 0 || $label === '') {
            return ['status' => false, 'text' => 'An authority requires a holon and a label.'];
        }

        $this->set('label', $label);
        $this->set('IDauthority_parent', $parentId > 0 ? $parentId : null);

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

    public function delete()
    {
        $authorityId = (int)$this->getId();
        if ($authorityId <= 0) {
            return false;
        }

        $parentId = (int)$this->get('IDauthority_parent');
        $childRows = self::fetchAll(
            'SELECT `id` FROM `authority` WHERE `IDauthority_parent` = :authority_id',
            ['authority_id' => $authorityId]
        );
        $ruleRows = self::fetchAll(
            'SELECT `id` FROM `rule` WHERE `IDauthority` = :authority_id',
            ['authority_id' => $authorityId]
        );

        if ($childRows === false || $ruleRows === false) {
            return false;
        }

        $childCount = count($childRows);
        $ruleCount = count($ruleRows);

        if ($parentId <= 0 && ($childCount > 0 || $ruleCount > 0)) {
            return false;
        }

        $pdo = self::getPdo();
        if (!$pdo) {
            return false;
        }

        try {
            $pdo->beginTransaction();

            if (!self::execute(
                'UPDATE `authority` SET `IDauthority_parent` = :parent_id WHERE `IDauthority_parent` = :authority_id',
                ['parent_id' => $parentId > 0 ? $parentId : null, 'authority_id' => $authorityId]
            )) {
                $pdo->rollBack();
                return false;
            }

            if (!self::execute(
                'UPDATE `rule` SET `IDauthority` = :parent_id WHERE `IDauthority` = :authority_id',
                ['parent_id' => $parentId > 0 ? $parentId : null, 'authority_id' => $authorityId]
            )) {
                $pdo->rollBack();
                return false;
            }

            if (!parent::delete()) {
                $pdo->rollBack();
                return false;
            }

            $pdo->commit();
            foreach ($childRows as $childRow) {
                unset(self::$preload['authority_' . (int)($childRow['id'] ?? 0)]);
            }
            foreach ($ruleRows as $ruleRow) {
                unset(self::$preload['rule_' . (int)($ruleRow['id'] ?? 0)]);
            }
            unset(self::$preload['authority_' . $authorityId]);
            return true;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            self::registerDbError('authority_delete_transaction', ['authority_id' => $authorityId], $exception);
            return false;
        }
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
