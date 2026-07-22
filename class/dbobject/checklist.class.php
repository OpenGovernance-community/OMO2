<?php
namespace dbObject;

class Checklist extends DbObject
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_RETIRED = 'retired';

    public static function tableName()
    {
        return 'checklist';
    }

    public static function rules()
    {
        return [
            [['IDorganization', 'IDproject_template_root', 'status'], 'required'],
            [['id'], 'integer'],
            [['IDorganization', 'IDchecklist_previous', 'IDproject_template_root', 'IDdocument'], 'fk'],
            [['status'], 'string'],
            [['revision_note'], 'text'],
            [['active'], 'boolean'],
            [['created_at', 'updated_at', 'published_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDorganization' => 'Organisation',
            'IDchecklist_previous' => 'Checklist precedente',
            'IDproject_template_root' => 'Projet modele racine',
            'IDdocument' => 'Documentation',
            'status' => 'Statut',
            'revision_note' => 'Note de revision',
            'active' => 'Active',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
            'published_at' => 'Publication',
        ];
    }

    public static function attributeLength()
    {
        return ['status' => 20];
    }

    public static function attributeValues()
    {
        return [
            'status' => [
                [self::STATUS_DRAFT, 'Brouillon'],
                [self::STATUS_PUBLISHED, 'Publiee'],
                [self::STATUS_RETIRED, 'Desactivee'],
            ],
        ];
    }

    public static function statuses()
    {
        return [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_RETIRED];
    }

    public static function normalizeStatus($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, self::statuses(), true) ? $value : self::STATUS_DRAFT;
    }

    public static function getOrder()
    {
        return 'updated_at DESC, id DESC';
    }

    public function save()
    {
        $this->set('status', self::normalizeStatus($this->get('status')));
        $now = new \DateTime();
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        if ($this->get('status') === self::STATUS_PUBLISHED && !($this->get('published_at') instanceof \DateTimeInterface)) {
            $this->set('published_at', $now);
        }
        $this->set('updated_at', $now);
        return parent::save();
    }

    public function getPrevious()
    {
        $id = (int)$this->get('IDchecklist_previous');
        $item = new self();
        return $id > 0 && $item->load($id) ? $item : null;
    }

    public function getOrganization()
    {
        $organization = new Organization();
        return $organization->load((int)$this->get('IDorganization')) ? $organization : null;
    }

    public function getDocument()
    {
        $documentId = (int)$this->get('IDdocument');
        $document = new Document();
        return $documentId > 0 && $document->load($documentId) ? $document : null;
    }

    public function getTemplateRoot()
    {
        $project = new Project();
        return $project->load((int)$this->get('IDproject_template_root')) ? $project : null;
    }

    public function getItems($activeOnly = true)
    {
        $items = new ArrayChecklistItem();
        $items->loadForChecklist((int)$this->getId(), (bool)$activeOnly);
        return $items;
    }

    public function getTriggers($enabledOnly = false)
    {
        $items = new ArrayChecklistTrigger();
        $items->loadForChecklist((int)$this->getId(), (bool)$enabledOnly);
        return $items;
    }

    public function getHolon()
    {
        $templateRoot = $this->getTemplateRoot();
        return $templateRoot instanceof Project ? $templateRoot->getHolon() : null;
    }

    public function getRuns()
    {
        $items = new ArrayChecklistRun();
        $items->loadForChecklist((int)$this->getId());
        return $items;
    }

    public function getOpenRuns()
    {
        $items = new ArrayChecklistRun();
        $items->loadOpenForChecklist((int)$this->getId());
        $statusChanged = false;
        foreach ($items as $run) {
            if ($run instanceof ChecklistRun && $run->syncStatusFromRootProject()) {
                $statusChanged = true;
            }
        }
        if ($statusChanged) {
            $items = new ArrayChecklistRun();
            $items->loadOpenForChecklist((int)$this->getId());
        }
        return $items;
    }
}
?>
