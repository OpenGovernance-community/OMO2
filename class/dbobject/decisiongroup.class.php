<?php
namespace dbObject;

class DecisionGroup extends DbObject
{
    public static function tableName()
    {
        return 'decision_group';
    }

    public static function rules()
    {
        return [
            [['IDdecision_process', 'decision_type', 'evaluation_method', 'title'], 'required'],
            [['id', 'position'], 'integer'],
            [['IDdecision_process'], 'fk'],
            [['decision_type', 'evaluation_method', 'title'], 'string'],
            [['description'], 'text'],
            [['parameters'], 'parameters'],
            [['active'], 'boolean'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDdecision_process' => 'Prise de decision',
            'decision_type' => 'Type',
            'evaluation_method' => 'Methode',
            'title' => 'Titre',
            'description' => 'Description',
            'parameters' => 'Parametres',
            'position' => 'Ordre',
            'active' => 'Actif',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'decision_type' => 'Nature du groupe: decision engageante ou consultation.',
            'evaluation_method' => 'Methode de participation utilisee par ce groupe.',
            'parameters' => 'Configuration propre a ce groupe et a sa methode.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'decision_type' => 20,
            'evaluation_method' => 40,
            'title' => 190,
        ];
    }

    public static function getOrder()
    {
        return 'position ASC, id ASC';
    }

    public static function findPrimaryByDecisionProcessId($decisionProcessId)
    {
        $row = self::fetchRow(
            'SELECT * FROM `decision_group`
             WHERE `IDdecision_process` = :decision_process_id
             ORDER BY `position` ASC, `id` ASC
             LIMIT 1',
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
        $this->set('decision_type', DecisionProcess::normalizeDecisionType($this->get('decision_type')));
        $this->set('evaluation_method', DecisionProcess::normalizeEvaluationMethod($this->get('evaluation_method')));

        return parent::save();
    }

    public function getDecisionProcess()
    {
        $decision = new \dbObject\DecisionProcess();
        return $decision->load((int)$this->get('IDdecision_process')) ? $decision : null;
    }

    public function getMethodDefinition()
    {
        $catalog = DecisionProcess::getEvaluationMethodCatalog();
        $method = DecisionProcess::normalizeEvaluationMethod($this->get('evaluation_method'));
        return isset($catalog[$method]) ? $catalog[$method] : $catalog[DecisionProcess::METHOD_SIMPLE_VOTE];
    }

    public function getProposals($activeOnly = false)
    {
        $items = new \dbObject\ArrayDecisionProposal();
        $params = [
            'where' => [
                ['field' => 'IDdecision_group', 'value' => (int)$this->getId()],
            ],
            'orderBy' => [
                ['field' => 'position', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ];

        if ($activeOnly) {
            $params['where'][] = ['field' => 'active', 'value' => 1];
        }

        $items->load($params);
        return $items;
    }

    public function getResponses($status = '')
    {
        $items = new \dbObject\ArrayDecisionResponse();
        $params = [
            'where' => [
                ['field' => 'IDdecision_group', 'value' => (int)$this->getId()],
            ],
            'orderBy' => [
                ['field' => 'updated_at', 'dir' => 'DESC'],
                ['field' => 'id', 'dir' => 'DESC'],
            ],
        ];

        $status = trim((string)$status);
        if ($status !== '') {
            $params['where'][] = ['field' => 'status', 'value' => DecisionResponse::normalizeStatus($status)];
        }

        $items->load($params);
        return $items;
    }

    public function getResult()
    {
        return \dbObject\DecisionResult::findByDecisionGroupId((int)$this->getId());
    }
}

?>
