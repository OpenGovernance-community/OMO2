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
            [['IDdecision_process', 'status'], 'required'],
            [['id'], 'integer'],
            [['IDdecision_process'], 'fk'],
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

    public static function findByDecisionProcessId($decisionProcessId)
    {
        $row = self::fetchRow(
            'SELECT * FROM `decision_result` WHERE `IDdecision_process` = :decision_process_id LIMIT 1',
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

        if (
            (string)$this->get('status') !== self::STATUS_PENDING
            && !($this->get('computed_at') instanceof \DateTimeInterface)
        ) {
            $this->set('computed_at', new \DateTime());
        }

        return parent::save();
    }
}

?>
