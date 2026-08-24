<?php
namespace dbObject;

class ChecklistRun extends DbObject
{
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ERROR = 'error';

    public static function tableName()
    {
        return 'checklist_run';
    }

    public static function rules()
    {
        return [
            [['IDchecklist', 'IDorganization', 'status'], 'required'],
            [['id'], 'integer'],
            [['IDchecklist', 'IDchecklisttrigger', 'IDorganization', 'IDholon', 'IDproject_root', 'IDuser_created'], 'fk'],
            [['status'], 'string'],
            [['scheduled_for', 'created_at', 'updated_at', 'completed_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDchecklist' => 'Processus',
            'IDchecklisttrigger' => 'Declencheur',
            'IDorganization' => 'Organisation',
            'IDholon' => 'Contexte',
            'IDproject_root' => 'Projet racine',
            'IDuser_created' => 'Declenchee par',
            'scheduled_for' => 'Date de reference',
            'status' => 'Statut',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
            'completed_at' => 'Terminee le',
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
                [self::STATUS_RUNNING, 'En cours'],
                [self::STATUS_COMPLETED, 'Terminee'],
                [self::STATUS_CANCELLED, 'Annulee'],
                [self::STATUS_ERROR, 'Erreur'],
            ],
        ];
    }

    public static function statuses()
    {
        return [self::STATUS_RUNNING, self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_ERROR];
    }

    public static function normalizeStatus($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, self::statuses(), true) ? $value : self::STATUS_RUNNING;
    }

    public static function getOrder()
    {
        return 'created_at DESC, id DESC';
    }

    public function save()
    {
        $status = self::normalizeStatus($this->get('status'));
        $this->set('status', $status);
        $now = new \DateTime();
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        if ($status === self::STATUS_COMPLETED && !($this->get('completed_at') instanceof \DateTimeInterface)) {
            $this->set('completed_at', $now);
        }
        $this->set('updated_at', $now);
        return parent::save();
    }

    public function getItems()
    {
        $items = new ArrayChecklistRunItem();
        $items->loadForRun((int)$this->getId());
        return $items;
    }

    public function getPvReviewItems()
    {
        $statusCatalog = Project::getStatusCatalog();
        $reviewItems = [];
        foreach ($this->getItems() as $runItem) {
            if (!($runItem instanceof ChecklistRunItem)) {
                continue;
            }
            $templateItem = $runItem->getTemplateItem();
            $templateProject = $templateItem instanceof ChecklistItem ? $templateItem->getProjectTemplate() : null;
            $project = $runItem->getProject();
            if ($project instanceof Project && (int)$project->get('active') !== 1) {
                continue;
            }
            $status = $project instanceof Project ? Project::normalizeStatus($project->get('status')) : '';
            $state = ChecklistRunItem::normalizeState($runItem->get('state'));
            $sizedProject = $project instanceof Project ? $project : $templateProject;
            $size = $sizedProject instanceof Project ? Project::normalizeSize($sizedProject->get('project_size')) : Project::SIZE_M;
            $metadata = $sizedProject instanceof Project ? $sizedProject->getReviewMetadata() : [];
            $reviewItems[] = [
                'title' => $project instanceof Project ? trim((string)$project->get('title')) : ($templateProject instanceof Project ? trim((string)$templateProject->get('title')) : ''),
                'projectId' => $project instanceof Project ? (int)$project->getId() : 0,
                'status' => $status,
                'statusLabel' => $status !== '' ? (string)($statusCatalog[$status]['label'] ?? $status) : ($state === ChecklistRunItem::STATE_BLOCKED ? 'Bloque' : 'En attente'),
                'size' => $size,
                'weight' => Project::getSizeWeight($size),
                'holonLabel' => (string)($metadata['holonLabel'] ?? ''),
                'responsibleLabel' => (string)($metadata['responsibleLabel'] ?? ''),
            ];
        }
        return $reviewItems;
    }

    public function getChecklist()
    {
        $checklist = new Checklist();
        return $checklist->load((int)$this->get('IDchecklist')) ? $checklist : null;
    }

    public function getTrigger()
    {
        $triggerId = (int)$this->get('IDchecklisttrigger');
        $trigger = new ChecklistTrigger();
        return $triggerId > 0 && $trigger->load($triggerId) ? $trigger : null;
    }

    public function getRootProject()
    {
        $projectId = (int)$this->get('IDproject_root');
        $project = new Project();
        return $projectId > 0 && $project->load($projectId) ? $project : null;
    }

    public function getReferenceAt()
    {
        return $this->get('scheduled_for');
    }

    public function synchronizeItemActivations($referenceDateTime = null)
    {
        if (self::normalizeStatus($this->get('status')) !== self::STATUS_RUNNING) {
            return ['completedCount' => 0, 'releasedCount' => 0, 'activatedCount' => 0];
        }

        $now = $referenceDateTime instanceof \DateTimeImmutable
            ? $referenceDateTime
            : new \DateTimeImmutable($referenceDateTime === null ? 'now' : (string)$referenceDateTime);
        $counts = ['completedCount' => 0, 'releasedCount' => 0, 'activatedCount' => 0];
        $remainingPasses = 100;
        $madeProgress = true;

        while ($madeProgress && $remainingPasses-- > 0) {
            $madeProgress = false;
            $itemsByTemplateId = [];
            foreach ($this->getItems() as $runItem) {
                if ($runItem instanceof ChecklistRunItem) {
                    $itemsByTemplateId[(int)$runItem->get('IDchecklistitem')] = $runItem;
                }
            }

            foreach ($itemsByTemplateId as $runItem) {
                if (ChecklistRunItem::normalizeState($runItem->get('state')) !== ChecklistRunItem::STATE_CREATED) {
                    continue;
                }
                $project = $runItem->getProject();
                if (!($project instanceof Project) || Project::normalizeStatus($project->get('status')) !== Project::STATUS_DONE) {
                    continue;
                }
                $runItem->set('state', ChecklistRunItem::STATE_COMPLETED);
                $result = $runItem->save();
                if (is_array($result) && !empty($result['status'])) {
                    $counts['completedCount']++;
                    $madeProgress = true;
                }
            }

            if ($madeProgress) {
                continue;
            }

            foreach ($itemsByTemplateId as $runItem) {
                if (ChecklistRunItem::normalizeState($runItem->get('state')) !== ChecklistRunItem::STATE_BLOCKED) {
                    continue;
                }
                $templateItem = $runItem->getTemplateItem();
                if (!($templateItem instanceof ChecklistItem)) {
                    continue;
                }

                $activationAt = null;
                $hasDependency = false;
                $dependenciesCompleted = true;
                foreach ($templateItem->getDependencies() as $dependency) {
                    if (!($dependency instanceof ChecklistItemDependency)) {
                        continue;
                    }
                    $hasDependency = true;
                    $requiredRunItem = $itemsByTemplateId[(int)$dependency->get('IDchecklistitem_required')] ?? null;
                    if (!($requiredRunItem instanceof ChecklistRunItem) || ChecklistRunItem::normalizeState($requiredRunItem->get('state')) !== ChecklistRunItem::STATE_COMPLETED) {
                        $dependenciesCompleted = false;
                        break;
                    }
                    $completedAt = $requiredRunItem->get('completed_at');
                    $completionDate = $completedAt instanceof \DateTimeInterface
                        ? \DateTimeImmutable::createFromInterface($completedAt)
                        : $now;
                    $plannedStartAt = ChecklistItem::shiftDate(
                        $completionDate,
                        (int)$dependency->get('delay_value'),
                        $dependency->get('delay_unit')
                    );
                    $candidateActivationAt = ChecklistItem::shiftDate(
                        $plannedStartAt,
                        -$templateItem->getDisplayLeadValue(),
                        $templateItem->getDisplayLeadUnit()
                    );
                    if (!($activationAt instanceof \DateTimeImmutable) || $candidateActivationAt > $activationAt) {
                        $activationAt = $candidateActivationAt;
                    }
                }
                if (!$hasDependency || !$dependenciesCompleted || !($activationAt instanceof \DateTimeImmutable)) {
                    continue;
                }

                $runItem->set('activation_at', $activationAt);
                $runItem->set('state', ChecklistRunItem::STATE_WAITING);
                $result = $runItem->save();
                if (is_array($result) && !empty($result['status'])) {
                    $counts['releasedCount']++;
                    $madeProgress = true;
                }
            }

            if ($madeProgress) {
                continue;
            }

            foreach ($itemsByTemplateId as $runItem) {
                if ($runItem->activateIfDue($now)) {
                    $counts['activatedCount']++;
                    $madeProgress = true;
                }
            }
        }

        return $counts;
    }

    public function syncStatusFromRootProject()
    {
        if (self::normalizeStatus($this->get('status')) !== self::STATUS_RUNNING) {
            return false;
        }
        $rootProject = $this->getRootProject();
        if (!($rootProject instanceof Project) || Project::normalizeStatus($rootProject->get('status')) !== Project::STATUS_DONE) {
            return false;
        }
        $this->set('status', self::STATUS_COMPLETED);
        $result = $this->save();
        return is_array($result) && !empty($result['status']);
    }

    public static function syncRunningBatch($limit = 100)
    {
        $runs = new ArrayChecklistRun();
        $runs->loadRunning($limit);
        $updatedCount = 0;
        foreach ($runs as $run) {
            if (!($run instanceof self)) {
                continue;
            }
            if ($run->syncStatusFromRootProject()) {
                $updatedCount++;
                continue;
            }
            $sync = $run->synchronizeItemActivations();
            $updatedCount += (int)($sync['completedCount'] ?? 0)
                + (int)($sync['releasedCount'] ?? 0)
                + (int)($sync['activatedCount'] ?? 0);
        }
        return $updatedCount;
    }
}
?>
