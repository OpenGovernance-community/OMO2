<?php
namespace dbObject;

class DecisionResponse extends DbObject
{
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_INVALIDATED = 'invalidated';

    public static function tableName()
    {
        return 'decision_response';
    }

    public static function rules()
    {
        return [
            [['IDdecision_group', 'IDdecision_participant', 'status'], 'required'],
            [['id'], 'integer'],
            [['IDdecision_process', 'IDdecision_group', 'IDdecision_participant'], 'fk'],
            [['status'], 'string'],
            [['parameters'], 'parameters'],
            [['submitted_at', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDdecision_process' => 'Prise de decision',
            'IDdecision_group' => 'Groupe de decision',
            'IDdecision_participant' => 'Participant',
            'status' => 'Statut',
            'parameters' => 'Parametres',
            'submitted_at' => 'Soumis le',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'parameters' => 'Charge utile libre dependant de la methode d evaluation.',
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
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_SUBMITTED => 'Soumise',
            self::STATUS_INVALIDATED => 'Invalidee',
        ];
    }

    public static function isValidStatus($status)
    {
        return array_key_exists((string)$status, self::getStatusCatalog());
    }

    public static function normalizeStatus($status)
    {
        $status = trim((string)$status);
        return self::isValidStatus($status) ? $status : self::STATUS_DRAFT;
    }

    public static function findByDecisionGroupAndParticipant($decisionGroupId, $participantId)
    {
        $row = self::fetchRow(
            'SELECT * FROM `decision_response`
             WHERE `IDdecision_group` = :decision_group_id AND `IDdecision_participant` = :participant_id
             LIMIT 1',
            [
                'decision_group_id' => (int)$decisionGroupId,
                'participant_id' => (int)$participantId,
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

    public static function findByDecisionAndParticipant($decisionProcessId, $participantId, $decisionGroupId = 0)
    {
        $decisionGroupId = (int)$decisionGroupId;
        if ($decisionGroupId <= 0) {
            $group = \dbObject\DecisionGroup::findPrimaryByDecisionProcessId((int)$decisionProcessId);
            $decisionGroupId = $group ? (int)$group->getId() : 0;
        }

        if ($decisionGroupId > 0) {
            return self::findByDecisionGroupAndParticipant($decisionGroupId, $participantId);
        }

        $row = self::fetchRow(
            'SELECT * FROM `decision_response`
             WHERE `IDdecision_process` = :decision_process_id AND `IDdecision_participant` = :participant_id
             ORDER BY `id` ASC
             LIMIT 1',
            [
                'decision_process_id' => (int)$decisionProcessId,
                'participant_id' => (int)$participantId,
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

        $participant = new \dbObject\DecisionParticipant();
        if (!$participant->load((int)$this->get('IDdecision_participant'))) {
            return [
                'status' => false,
                'text' => 'The linked participant could not be found.',
            ];
        }

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
        if ((int)$participant->get('IDdecision_process') !== $groupProcessId) {
            return [
                'status' => false,
                'text' => 'The linked participant does not belong to the decision process.',
            ];
        }

        if ($decisionProcessId > 0 && $decisionProcessId !== $groupProcessId) {
            return [
                'status' => false,
                'text' => 'The linked decision process does not match the decision group.',
            ];
        }

        $this->set('IDdecision_process', $groupProcessId);

        if (
            (string)$this->get('status') === self::STATUS_SUBMITTED
            && !($this->get('submitted_at') instanceof \DateTimeInterface)
        ) {
            $this->set('submitted_at', new \DateTime());
        }

        return parent::save();
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

    public function getDecisionGroup()
    {
        $group = new \dbObject\DecisionGroup();
        return $group->load((int)$this->get('IDdecision_group')) ? $group : null;
    }

    public function getParticipant()
    {
        $participant = new \dbObject\DecisionParticipant();
        return $participant->load((int)$this->get('IDdecision_participant')) ? $participant : null;
    }
}

?>
