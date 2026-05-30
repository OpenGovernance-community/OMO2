<?php
namespace dbObject;

class DecisionResult extends DbObject
{
    const STATUS_PENDING = 'pending';
    const STATUS_PROVISIONAL = 'provisional';
    const STATUS_FINAL = 'final';
    const STATUS_ARCHIVED = 'archived';

    public static function tableName()
    {
        return 'decision_result';
    }

    public static function rules()
    {
        return [
            [['IDdecision_group', 'status'], 'required'],
            [['id'], 'integer'],
            [['IDdecision_process', 'IDdecision_group'], 'fk'],
            [['status'], 'string'],
            [['summary'], 'text'],
            [['parameters'], 'parameters'],
            [['computed_at', 'published_at', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDdecision_process' => 'Prise de decision',
            'IDdecision_group' => 'Groupe de decision',
            'status' => 'Statut',
            'summary' => 'Resume',
            'parameters' => 'Parametres',
            'computed_at' => 'Calcule le',
            'published_at' => 'Publie le',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'parameters' => 'Structure de resultat libre dependant de la methode d evaluation.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'status' => 30,
        ];
    }

    public static function getOrder()
    {
        return 'updated_at DESC, id DESC';
    }

    public static function getStatusCatalog()
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROVISIONAL => 'Provisoire',
            self::STATUS_FINAL => 'Final',
            self::STATUS_ARCHIVED => 'Archive',
        ];
    }

    public static function isValidStatus($status)
    {
        return array_key_exists((string)$status, self::getStatusCatalog());
    }

    public static function normalizeStatus($status)
    {
        $status = trim((string)$status);
        return self::isValidStatus($status) ? $status : self::STATUS_PENDING;
    }

    public static function findByDecisionGroupId($decisionGroupId)
    {
        $row = self::fetchRow(
            'SELECT * FROM `decision_result` WHERE `IDdecision_group` = :decision_group_id LIMIT 1',
            [
                'decision_group_id' => (int)$decisionGroupId,
            ]
        );

        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }

        $item = new self();
        $item->loadFromArray($row);
        $item->setId((int)$row['id']);
        return $item;
    }

    public static function findByDecisionProcessId($decisionProcessId, $decisionGroupId = 0)
    {
        $decisionGroupId = (int)$decisionGroupId;
        if ($decisionGroupId <= 0) {
            $group = \dbObject\DecisionGroup::findPrimaryByDecisionProcessId((int)$decisionProcessId);
            $decisionGroupId = $group ? (int)$group->getId() : 0;
        }

        if ($decisionGroupId > 0) {
            return self::findByDecisionGroupId($decisionGroupId);
        }

        $row = self::fetchRow(
            'SELECT * FROM `decision_result` WHERE `IDdecision_process` = :decision_process_id ORDER BY `id` ASC LIMIT 1',
            [
                'decision_process_id' => (int)$decisionProcessId,
            ]
        );

        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }

        $item = new self();
        $item->loadFromArray($row);
        $item->setId((int)$row['id']);
        return $item;
    }

    public function save()
    {
        $this->set('status', self::normalizeStatus($this->get('status')));

        $decisionGroupId = (int)$this->get('IDdecision_group');
        $decisionProcessId = (int)$this->get('IDdecision_process');

        if ($decisionGroupId <= 0 && $decisionProcessId > 0) {
            $decision = new \dbObject\DecisionProcess();
            if ($decision->load($decisionProcessId)) {
                $group = $decision->ensurePrimaryGroup();
                if ($group) {
                    $decisionGroupId = (int)$group->getId();
                    $this->set('IDdecision_group', $decisionGroupId);
                }
            }
        }

        $group = new \dbObject\DecisionGroup();
        if ($decisionGroupId <= 0 || !$group->load($decisionGroupId)) {
            return [
                'status' => false,
                'text' => 'The linked decision group does not exist.',
            ];
        }

        $groupProcessId = (int)$group->get('IDdecision_process');
        if ($decisionProcessId > 0 && $decisionProcessId !== $groupProcessId) {
            return [
                'status' => false,
                'text' => 'The linked decision process does not match the decision group.',
            ];
        }

        $this->set('IDdecision_process', $groupProcessId);

        if (
            (string)$this->get('status') !== self::STATUS_PENDING
            && !($this->get('computed_at') instanceof \DateTimeInterface)
        ) {
            $this->set('computed_at', new \DateTime());
        }

        return parent::save();
    }

    public function getDecisionGroup()
    {
        $group = new \dbObject\DecisionGroup();
        return $group->load((int)$this->get('IDdecision_group')) ? $group : null;
    }

    public function getDecisionProcess()
    {
        $group = $this->getDecisionGroup();
        if ($group instanceof \dbObject\DecisionGroup) {
            return $group->getDecisionProcess();
        }

        $decision = new \dbObject\DecisionProcess();
        return $decision->load((int)$this->get('IDdecision_process')) ? $decision : null;
    }
}

?>
