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
            [['IDdecision_process', 'title'], 'required'],
            [['id', 'position'], 'integer'],
            [['IDdecision_process'], 'fk'],
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

    public function getDecisionProcess()
    {
        $decision = new \dbObject\DecisionProcess();
        return $decision->load((int)$this->get('IDdecision_process')) ? $decision : null;
    }
}

?>
