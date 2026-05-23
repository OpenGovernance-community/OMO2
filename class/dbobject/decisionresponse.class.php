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
            [['IDdecision_process', 'IDdecision_participant', 'status'], 'required'],
            [['id'], 'integer'],
            [['IDdecision_process', 'IDdecision_participant'], 'fk'],
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

    public static function findByDecisionAndParticipant($decisionProcessId, $participantId)
    {
        $row = self::fetchRow(
            'SELECT * FROM `decision_response`
             WHERE `IDdecision_process` = :decision_process_id AND `IDdecision_participant` = :participant_id
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
        if (
            !$participant->load((int)$this->get('IDdecision_participant'))
            || (int)$participant->get('IDdecision_process') !== (int)$this->get('IDdecision_process')
        ) {
            return [
                'status' => false,
                'text' => 'The linked participant does not belong to the decision process.',
            ];
        }

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
        $decision = new \dbObject\DecisionProcess();
        return $decision->load((int)$this->get('IDdecision_process')) ? $decision : null;
    }

    public function getParticipant()
    {
        $participant = new \dbObject\DecisionParticipant();
        return $participant->load((int)$this->get('IDdecision_participant')) ? $participant : null;
    }
}

?>
