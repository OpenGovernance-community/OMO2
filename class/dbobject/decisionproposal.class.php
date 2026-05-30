<?php
namespace dbObject;

class DecisionProposal extends DbObject
{
    public static function tableName()
    {
        return 'decision_proposal';
    }

    public static function rules()
    {
        return [
            [['IDdecision_group', 'title'], 'required'],
            [['id', 'position'], 'integer'],
            [['IDdecision_process', 'IDdecision_group'], 'fk'],
            [['title'], 'string'],
            [['description'], 'text'],
            [['info_url'], 'string'],
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
            'IDdecision_group' => 'Groupe de decision',
            'title' => 'Titre',
            'description' => 'Description',
            'info_url' => 'Lien d information',
            'position' => 'Ordre',
            'parameters' => 'Parametres',
            'active' => 'Active',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'parameters' => 'Metadonnees specifiques a une proposition ou a une methode.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'title' => 190,
            'info_url' => 500,
        ];
    }

    public static function getOrder()
    {
        return 'position ASC, id ASC';
    }

    public function save()
    {
        $decisionGroupId = (int)$this->get('IDdecision_group');
        $decisionProcessId = (int)$this->get('IDdecision_process');

        if ($decisionGroupId <= 0 && $decisionProcessId > 0) {
            $decision = new \dbObject\DecisionProcess();
            if ($decision->load($decisionProcessId)) {
                $group = $decision->ensurePrimaryGroup();
                if ($group) {
                    $decisionGroupId = (int)$group->getId();
                    $this->set('IDdecision_group', $decisionGroupId);
                    $this->set('IDdecision_process', (int)$group->get('IDdecision_process'));
                }
            }
        }

        if ($decisionGroupId > 0) {
            $group = new \dbObject\DecisionGroup();
            if (!$group->load($decisionGroupId)) {
                return [
                    'status' => false,
                    'text' => 'The linked decision group could not be found.',
                ];
            }

            $groupProcessId = (int)$group->get('IDdecision_process');
            if ($decisionProcessId > 0 && $groupProcessId !== $decisionProcessId) {
                return [
                    'status' => false,
                    'text' => 'The linked decision process does not match the decision group.',
                ];
            }

            $this->set('IDdecision_process', $groupProcessId);
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
