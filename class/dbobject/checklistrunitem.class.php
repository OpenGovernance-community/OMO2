<?php
namespace dbObject;

class ChecklistRunItem extends DbObject
{
    public const STATE_WAITING = 'waiting';
    public const STATE_AVAILABLE = 'available';
    public const STATE_CREATED = 'created';
    public const STATE_COMPLETED = 'completed';
    public const STATE_SKIPPED = 'skipped';
    public const STATE_BLOCKED = 'blocked';
    public const STATE_ERROR = 'error';

    public static function tableName()
    {
        return 'checklist_run_item';
    }

    public static function rules()
    {
        return [
            [['IDchecklistrun', 'IDchecklistitem', 'state'], 'required'],
            [['id'], 'integer'],
            [['IDchecklistrun', 'IDchecklistitem', 'IDproject'], 'fk'],
            [['state'], 'string'],
            [['activation_at', 'created_at', 'updated_at', 'activated_at', 'completed_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDchecklistrun' => 'Execution',
            'IDchecklistitem' => 'Etape modele',
            'IDproject' => 'Projet genere',
            'activation_at' => 'Activation prevue',
            'state' => 'Etat',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
            'activated_at' => 'Activation',
            'completed_at' => 'Terminee le',
        ];
    }

    public static function attributeLength()
    {
        return ['state' => 20];
    }

    public static function attributeValues()
    {
        return [
            'state' => [
                [self::STATE_WAITING, 'En attente'],
                [self::STATE_AVAILABLE, 'Disponible'],
                [self::STATE_CREATED, 'Projet cree'],
                [self::STATE_COMPLETED, 'Terminee'],
                [self::STATE_SKIPPED, 'Ignoree'],
                [self::STATE_BLOCKED, 'Bloquee'],
                [self::STATE_ERROR, 'Erreur'],
            ],
        ];
    }

    public static function states()
    {
        return [
            self::STATE_WAITING,
            self::STATE_AVAILABLE,
            self::STATE_CREATED,
            self::STATE_COMPLETED,
            self::STATE_SKIPPED,
            self::STATE_BLOCKED,
            self::STATE_ERROR,
        ];
    }

    public static function normalizeState($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, self::states(), true) ? $value : self::STATE_WAITING;
    }

    public static function getOrder()
    {
        return 'activation_at ASC, id ASC';
    }

    public function save()
    {
        $state = self::normalizeState($this->get('state'));
        $this->set('state', $state);
        $now = new \DateTime();
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        if (in_array($state, [self::STATE_AVAILABLE, self::STATE_CREATED], true) && !($this->get('activated_at') instanceof \DateTimeInterface)) {
            $this->set('activated_at', $now);
        }
        if ($state === self::STATE_COMPLETED && !($this->get('completed_at') instanceof \DateTimeInterface)) {
            $this->set('completed_at', $now);
        }
        $this->set('updated_at', $now);
        return parent::save();
    }

    public function getRun()
    {
        $run = new ChecklistRun();
        return $run->load((int)$this->get('IDchecklistrun')) ? $run : null;
    }

    public function getTemplateItem()
    {
        $item = new ChecklistItem();
        return $item->load((int)$this->get('IDchecklistitem')) ? $item : null;
    }

    public function getProject()
    {
        $projectId = (int)$this->get('IDproject');
        $project = new Project();
        return $projectId > 0 && $project->load($projectId) ? $project : null;
    }

    public function activateIfDue(\DateTimeImmutable $now)
    {
        if (self::normalizeState($this->get('state')) !== self::STATE_WAITING) {
            return false;
        }
        $activationAt = $this->get('activation_at');
        if (!($activationAt instanceof \DateTimeInterface) || $activationAt > $now) {
            return false;
        }
        $run = $this->getRun();
        $templateItem = $this->getTemplateItem();
        $templateProject = $templateItem instanceof ChecklistItem ? $templateItem->getProjectTemplate() : null;
        $rootProject = $run instanceof ChecklistRun ? $run->getRootProject() : null;
        $checklist = $run instanceof ChecklistRun ? $run->getChecklist() : null;
        $rootTemplate = $checklist instanceof Checklist ? $checklist->getTemplateRoot() : null;
        if (
            !($run instanceof ChecklistRun)
            || ChecklistRun::normalizeStatus($run->get('status')) !== ChecklistRun::STATUS_RUNNING
            || !($templateProject instanceof Project)
            || !($rootProject instanceof Project)
            || !($rootTemplate instanceof Project)
        ) {
            return false;
        }

        $parentTemplateId = (int)$templateProject->get('IDproject_parent');
        $parentProjectId = $parentTemplateId === (int)$rootTemplate->getId()
            ? (int)$rootProject->getId()
            : 0;
        if ($parentProjectId <= 0) {
            foreach ($run->getItems() as $candidateRunItem) {
                if (!($candidateRunItem instanceof self)) {
                    continue;
                }
                $freshCandidateRunItem = new self();
                if (!$freshCandidateRunItem->load((int)$candidateRunItem->getId(), true)) {
                    continue;
                }
                $candidateRunItem = $freshCandidateRunItem;
                if ((int)$candidateRunItem->get('IDproject') <= 0) {
                    continue;
                }
                $candidateTemplateItem = $candidateRunItem->getTemplateItem();
                if (
                    $candidateTemplateItem instanceof ChecklistItem
                    && (int)$candidateTemplateItem->get('IDproject_template') === $parentTemplateId
                ) {
                    $parentProjectId = (int)$candidateRunItem->get('IDproject');
                    break;
                }
            }
        }
        if ($parentProjectId <= 0) {
            return false;
        }

        $pdo = DbObject::getPdo();
        $startedTransaction = false;
        try {
            if ($pdo && !$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $startedTransaction = true;
            }
            $referenceAt = $run->getReferenceAt();
            $referenceDate = $referenceAt instanceof \DateTimeInterface
                ? \DateTimeImmutable::createFromInterface($referenceAt)
                : $activationAt;
            $plannedStartAt = $templateItem->calculatePlannedStartAt($referenceDate);
            if (!($plannedStartAt instanceof \DateTimeImmutable)) {
                $plannedStartAt = ChecklistItem::shiftDate(
                    $activationAt,
                    $templateItem->getDisplayLeadValue(),
                    $templateItem->getDisplayLeadUnit()
                );
            }
            $project = Project::createFromChecklistTemplate(
                $templateProject,
                $parentProjectId,
                $plannedStartAt,
                null,
                $templateItem->getDeadlineAt($plannedStartAt)
            );
            if (!($project instanceof Project)) {
                throw new \RuntimeException('Unable to instantiate checklist project.');
            }
            $this->set('IDproject', (int)$project->getId());
            $this->set('state', self::STATE_CREATED);
            $result = $this->save();
            if (!is_array($result) || empty($result['status'])) {
                throw new \RuntimeException('Unable to activate checklist run item.');
            }
            if ($startedTransaction && $pdo && $pdo->inTransaction()) {
                $pdo->commit();
            }
            return true;
        } catch (\Throwable $exception) {
            if ($startedTransaction && $pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }

    public static function activatePendingBatch($limit = 50, $referenceDateTime = null)
    {
        $now = $referenceDateTime instanceof \DateTimeImmutable
            ? $referenceDateTime
            : new \DateTimeImmutable($referenceDateTime === null ? 'now' : (string)$referenceDateTime);
        $pdo = DbObject::getPdo();
        if (!($pdo instanceof \PDO)) {
            return 0;
        }
        $lockStatement = $pdo->query("SELECT GET_LOCK('omo_checklist_due_items', 0)");
        if (!$lockStatement || (int)$lockStatement->fetchColumn() !== 1) {
            return 0;
        }
        try {
            $pendingItems = new ArrayChecklistRunItem();
            $pendingItems->loadPending($now, $limit);
            $processedCount = 0;
            $processedIds = [];
            $madeProgress = true;
            $remainingPasses = max(1, count($pendingItems));
            while ($madeProgress && $remainingPasses-- > 0) {
                $madeProgress = false;
                foreach ($pendingItems as $pendingItem) {
                    if (!($pendingItem instanceof self)) {
                        continue;
                    }
                    $pendingItemId = (int)$pendingItem->getId();
                    if ($pendingItemId <= 0 || isset($processedIds[$pendingItemId])) {
                        continue;
                    }
                    $freshItem = new self();
                    if ($freshItem->load($pendingItemId, true) && $freshItem->activateIfDue($now)) {
                        $processedIds[$pendingItemId] = true;
                        $processedCount++;
                        $madeProgress = true;
                    }
                }
            }
            return $processedCount;
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('omo_checklist_due_items')");
        }
    }
}
?>
