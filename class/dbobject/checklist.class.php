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
            'IDchecklist_previous' => 'Processus precedent',
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

    public function deleteWithRelatedData()
    {
        if ((int)$this->getId() <= 0) {
            return false;
        }

        $nextChecklists = new ArrayChecklist();
        $nextChecklists->load([
            'where' => [['field' => 'IDchecklist_previous', 'value' => (int)$this->getId()]],
        ]);
        foreach ($nextChecklists as $nextChecklist) {
            if (!($nextChecklist instanceof self)) {
                continue;
            }
            $nextChecklist->set('IDchecklist_previous', null);
            $result = $nextChecklist->save();
            if (!is_array($result) || empty($result['status'])) {
                return false;
            }
        }

        foreach ($this->getRuns() as $run) {
            if ($run instanceof ChecklistRun && !$run->delete()) {
                return false;
            }
        }

        foreach ($this->getItems(false) as $item) {
            if (!($item instanceof ChecklistItem)) {
                continue;
            }
            $occurrences = new ArrayChecklistItemOccurrence();
            $occurrences->loadForItem((int)$item->getId());
            foreach ($occurrences as $occurrence) {
                if ($occurrence instanceof ChecklistItemOccurrence && !$occurrence->delete()) {
                    return false;
                }
            }
            $templateProject = $item->getProjectTemplate();
            if ($templateProject instanceof Project && (int)$templateProject->get('active') !== 0) {
                $templateProject->set('active', 0);
                $result = $templateProject->save();
                if (!is_array($result) || empty($result['status'])) {
                    return false;
                }
            }
        }

        $templateRoot = $this->getTemplateRoot();
        if ($templateRoot instanceof Project && (int)$templateRoot->get('active') !== 0) {
            $templateRoot->set('active', 0);
            $result = $templateRoot->save();
            if (!is_array($result) || empty($result['status'])) {
                return false;
            }
        }

        return $this->delete();
    }

    public function getPvReviewSummary()
    {
        $templateRoot = $this->getTemplateRoot();
        $title = $templateRoot instanceof Project ? trim((string)$templateRoot->get('title')) : '';
        $trigger = null;
        foreach ($this->getTriggers(false) as $candidate) {
            if ($candidate instanceof ChecklistTrigger) {
                $trigger = $candidate;
                break;
            }
        }
        $isContainer = $trigger instanceof ChecklistTrigger
            && ChecklistTrigger::normalizeTriggerType($trigger->get('trigger_type')) === ChecklistTrigger::TYPE_CONTAINER;
        $projects = [];
        if ($isContainer) {
            foreach ($this->getItems(true) as $item) {
                if (!($item instanceof ChecklistItem)) {
                    continue;
                }
                $occurrences = new ArrayChecklistItemOccurrence();
                $occurrences->loadForItem((int)$item->getId());
                foreach ($occurrences as $occurrence) {
                    $project = $occurrence instanceof ChecklistItemOccurrence ? $occurrence->getProject() : null;
                    if ($project instanceof Project && (int)$project->get('active') === 1) {
                        $projects[] = ['project' => $project, 'runId' => 0];
                    }
                }
            }
        } else {
            foreach ($this->getOpenRuns() as $run) {
                $project = $run instanceof ChecklistRun ? $run->getRootProject() : null;
                if ($project instanceof Project && (int)$project->get('active') === 1) {
                    $projects[] = ['project' => $project, 'runId' => (int)$run->getId()];
                }
            }
        }
        $counts = array_fill_keys([
            Project::STATUS_READY, Project::STATUS_IN_PROGRESS, Project::STATUS_BLOCKED,
            Project::STATUS_REVIEW, Project::STATUS_DONE, Project::STATUS_SOMEDAY,
        ], 0);
        $overdueCount = 0;
        $today = new \DateTimeImmutable('today');
        $statusCatalog = Project::getStatusCatalog();
        $entries = [];
        foreach ($projects as $projectEntry) {
            $project = $projectEntry['project'];
            $metadata = $project->getReviewMetadata();
            $status = Project::normalizeStatus($project->get('status'));
            $counts[$status] = (int)($counts[$status] ?? 0) + 1;
            $deadline = $project->get('planned_end_date');
            if ($status !== Project::STATUS_DONE && $deadline instanceof \DateTimeInterface && $deadline < $today) {
                $overdueCount++;
            }
            $entries[] = [
                'projectId' => (int)$project->getId(),
                'runId' => (int)($projectEntry['runId'] ?? 0),
                'title' => trim((string)$project->get('title')),
                'status' => $status,
                'statusLabel' => (string)($statusCatalog[$status]['label'] ?? $status),
                'size' => Project::normalizeSize($project->get('project_size')),
                'weight' => Project::getSizeWeight($project->get('project_size')),
                'holonLabel' => (string)($metadata['holonLabel'] ?? ''),
                'responsibleLabel' => (string)($metadata['responsibleLabel'] ?? ''),
            ];
        }
        return [
            'title' => $title,
            'isContainer' => $isContainer,
            'total' => count($entries),
            'counts' => $counts,
            'overdueCount' => $overdueCount,
            'entries' => $entries,
        ];
    }
}
?>
