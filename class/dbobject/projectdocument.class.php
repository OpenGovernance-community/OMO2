<?php
namespace dbObject;

class ProjectDocument extends DbObject
{
    public static function tableName()
    {
        return 'project_document';
    }

    public static function rules()
    {
        return [
            [['IDproject', 'IDdocument'], 'required'],
            [['id'], 'integer'],
            [['IDproject', 'IDdocument'], 'fk'],
            [['datecreation'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDproject' => 'Projet',
            'IDdocument' => 'Document',
            'datecreation' => 'Date d ajout',
        ];
    }

    public static function getOrder()
    {
        return 'datecreation ASC, id ASC';
    }

    public function save()
    {
        if ((int)$this->getId() <= 0 && !($this->get('datecreation') instanceof \DateTimeInterface)) {
            $this->set('datecreation', new \DateTime());
        }

        return parent::save();
    }

    public function getProject()
    {
        $project = new Project();
        return $project->load((int)$this->get('IDproject')) ? $project : null;
    }

    public function getDocument()
    {
        $document = new Document();
        return $document->load((int)$this->get('IDdocument')) ? $document : null;
    }
}
?>
