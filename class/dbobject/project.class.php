<?php
namespace dbObject;

class Project extends DbObject
{
    public const KIND_STANDARD = 'standard';
    public const KIND_CHECKLIST_TEMPLATE = 'checklist_template';

    public const STATUS_SOMEDAY = 'someday';
    public const STATUS_READY = 'ready';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_REVIEW = 'review';
    public const STATUS_DONE = 'done';

    public const CAPTURE_MULTIPLE_DOCUMENTS = 'multiple_documents';
    public const CAPTURE_SINGLE_JOURNAL = 'single_journal';

    public const SIZE_S = 'S';
    public const SIZE_M = 'M';
    public const SIZE_L = 'L';
    public const SIZE_XL = 'XL';
    public const SIZE_XXL = 'XXL';

    public static function tableName()
    {
        return 'project';
    }

    public static function rules()
    {
        return [
            [['IDorganization', 'title'], 'required'],
            [['id', 'priority', 'importance'], 'integer'],
            [['IDorganization', 'IDholon', 'IDuser', 'IDproject_parent', 'IDdocument_journal', 'IDproject_template'], 'fk'],
            [['title', 'status', 'capture_mode', 'project_size', 'project_kind'], 'string'],
            [['description'], 'html'],
            [['planned_start_date', 'planned_end_date'], 'date'],
            [['created_at', 'updated_at'], 'datetime'],
            [['active'], 'boolean'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDorganization' => 'Organisation',
            'IDholon' => 'Holon',
            'IDuser' => 'Responsable',
            'IDproject_parent' => 'Projet parent',
            'IDdocument_journal' => 'Document journal',
            'project_kind' => 'Type de projet',
            'IDproject_template' => 'Projet modele',
            'title' => 'Titre',
            'description' => 'Description',
            'status' => 'Statut',
            'planned_start_date' => 'Debut planifie',
            'planned_end_date' => 'Fin planifiee',
            'priority' => 'Priorite',
            'importance' => 'Importance',
            'capture_mode' => 'Mode de capture Telegram',
            'project_size' => 'Taille du projet',
            'active' => 'Actif',
            'created_at' => 'Date de creation',
            'updated_at' => 'Date de modification',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'description' => 'Description HTML simple avec paragraphes, listes et mise en forme.',
            'status' => 'Etat du projet pour une future vue kanban.',
            'planned_start_date' => 'Date a laquelle le projet devrait commencer.',
            'planned_end_date' => 'Date a laquelle le projet devrait etre termine.',
            'priority' => 'Niveau a considerer tant que les dates ne sont pas renseignees.',
            'importance' => 'Niveau a considerer quand le temps disponible manque.',
            'capture_mode' => 'Indique si les captures Telegram doivent creer plusieurs documents ou alimenter un journal unique.',
            'project_size' => 'Taille relative du projet, utilisee pour ponderer sa place dans les barres de synthese.',
            'project_kind' => 'Distingue un projet operationnel d un projet utilise comme modele de checklist.',
            'IDproject_template' => 'Projet modele a l origine de cette instance.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'title' => 255,
            'status' => 20,
            'capture_mode' => 30,
            'project_size' => 3,
            'project_kind' => 30,
        ];
    }

    public static function attributeValues()
    {
        $statusValues = [];
        foreach (self::getStatusCatalog() as $status => $catalog) {
            $statusValues[] = [$status, (string)$catalog['label']];
        }

        $priorityValues = [['', 'Non definie']];
        for ($level = 1; $level <= 5; $level++) {
            $priorityValues[] = [(string)$level, (string)$level . '/5'];
        }

        return [
            'status' => $statusValues,
            'priority' => $priorityValues,
            'importance' => $priorityValues,
            'capture_mode' => [
                [self::CAPTURE_MULTIPLE_DOCUMENTS, 'Documents multiples'],
                [self::CAPTURE_SINGLE_JOURNAL, 'Journal unique'],
            ],
            'project_size' => [
                [self::SIZE_S, self::SIZE_S],
                [self::SIZE_M, self::SIZE_M],
                [self::SIZE_L, self::SIZE_L],
                [self::SIZE_XL, self::SIZE_XL],
                [self::SIZE_XXL, self::SIZE_XXL],
            ],
            'project_kind' => [
                [self::KIND_STANDARD, 'Projet'],
                [self::KIND_CHECKLIST_TEMPLATE, 'Modele de checklist'],
            ],
        ];
    }

    public static function getOrder()
    {
        return 'created_at DESC, id DESC';
    }

    public static function statuses()
    {
        return [
            self::STATUS_SOMEDAY,
            self::STATUS_READY,
            self::STATUS_IN_PROGRESS,
            self::STATUS_BLOCKED,
            self::STATUS_REVIEW,
            self::STATUS_DONE,
        ];
    }

    public static function getStatusCatalog()
    {
        return [
            self::STATUS_SOMEDAY => [
                'label' => 'Un jour peut-etre',
                'description' => 'Projet conserve pour plus tard.',
            ],
            self::STATUS_READY => [
                'label' => 'Pret',
                'description' => 'Projet pret a demarrer.',
            ],
            self::STATUS_IN_PROGRESS => [
                'label' => 'En cours',
                'description' => 'Projet actuellement travaille.',
            ],
            self::STATUS_BLOCKED => [
                'label' => 'Bloque',
                'description' => 'Projet bloque par un obstacle.',
            ],
            self::STATUS_REVIEW => [
                'label' => 'A verifier',
                'description' => 'Projet en attente de verification.',
            ],
            self::STATUS_DONE => [
                'label' => 'Termine',
                'description' => 'Projet acheve.',
            ],
        ];
    }

    public static function getCaptureModeCatalog()
    {
        return [
            self::CAPTURE_MULTIPLE_DOCUMENTS => [
                'label' => 'Documents multiples',
                'description' => 'Chaque capture cree un document distinct.',
            ],
            self::CAPTURE_SINGLE_JOURNAL => [
                'label' => 'Journal unique',
                'description' => 'Les captures alimentent un document journal.',
            ],
        ];
    }

    public static function normalizeStatus($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, self::statuses(), true)
            ? $value
            : self::STATUS_SOMEDAY;
    }

    public static function kinds()
    {
        return [self::KIND_STANDARD, self::KIND_CHECKLIST_TEMPLATE];
    }

    public static function normalizeKind($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, self::kinds(), true) ? $value : self::KIND_STANDARD;
    }

    public static function normalizeCaptureMode($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, [
            self::CAPTURE_MULTIPLE_DOCUMENTS,
            self::CAPTURE_SINGLE_JOURNAL,
        ], true)
            ? $value
            : self::CAPTURE_MULTIPLE_DOCUMENTS;
    }

    public static function sizes()
    {
        return [
            self::SIZE_S,
            self::SIZE_M,
            self::SIZE_L,
            self::SIZE_XL,
            self::SIZE_XXL,
        ];
    }

    public static function normalizeSize($value)
    {
        $value = strtoupper(trim((string)$value));
        return in_array($value, self::sizes(), true) ? $value : self::SIZE_M;
    }

    public static function getSizeWeight($value)
    {
        return array_search(self::normalizeSize($value), self::sizes(), true) + 1;
    }

    public static function createEmptyStatusSummary()
    {
        return [
            'total' => 0,
            'counts' => array_fill_keys(self::statuses(), 0),
            'weights' => array_fill_keys(self::statuses(), 0.0),
            'leaves' => [],
        ];
    }

    public static function mergeStatusSummaries(array $summary, array $addition)
    {
        foreach (self::statuses() as $status) {
            $summary['counts'][$status] = (int)($summary['counts'][$status] ?? 0) + (int)($addition['counts'][$status] ?? 0);
            $summary['weights'][$status] = (float)($summary['weights'][$status] ?? 0) + (float)($addition['weights'][$status] ?? 0);
        }
        $summary['total'] += (int)($addition['total'] ?? 0);
        if (!empty($addition['leaves']) && is_array($addition['leaves'])) {
            $summary['leaves'] = array_merge($summary['leaves'], $addition['leaves']);
        }
        return $summary;
    }

    public static function scaleStatusSummary(array $summary, $factor)
    {
        $factor = (float)$factor;
        foreach ($summary['weights'] as $status => $weight) {
            $summary['weights'][$status] = (float)$weight * $factor;
        }
        foreach ($summary['leaves'] as &$leaf) {
            $leaf['weight'] = (float)($leaf['weight'] ?? 0) * $factor;
        }
        unset($leaf);
        return $summary;
    }

    public static function getChildrenWeight(array $children)
    {
        $weight = 0;
        foreach ($children as $child) {
            if ($child instanceof self) {
                $weight += self::getSizeWeight($child->get('project_size'));
            }
        }
        return $weight > 0 ? $weight : 1;
    }

    public static function createLeafStatusSummary(Project $project)
    {
        $status = self::normalizeStatus($project->get('status'));
        $summary = self::createEmptyStatusSummary();
        $summary['total'] = 1;
        $summary['counts'][$status] = 1;
        $summary['weights'][$status] = 1.0;
        $summary['leaves'][] = ['status' => $status, 'weight' => 1.0];
        return $summary;
    }

    public static function getLeafStatusSummary(Project $project, array $childrenByParent, array &$memo = [], array &$path = [])
    {
        $projectId = (int)$project->getId();
        if ($projectId <= 0) {
            return self::createEmptyStatusSummary();
        }
        if (isset($memo[$projectId])) {
            return $memo[$projectId];
        }
        if (isset($path[$projectId])) {
            return self::createEmptyStatusSummary();
        }

        $path[$projectId] = true;
        $children = $childrenByParent[$projectId] ?? [];
        $summary = self::createEmptyStatusSummary();
        if (count($children) === 0) {
            $summary = self::createLeafStatusSummary($project);
        } else {
            $childrenWeight = self::getChildrenWeight($children);
            foreach ($children as $child) {
                if ($child instanceof self) {
                    $summary = self::mergeStatusSummaries(
                        $summary,
                        self::scaleStatusSummary(
                            self::getLeafStatusSummary($child, $childrenByParent, $memo, $path),
                            self::getSizeWeight($child->get('project_size')) / $childrenWeight
                        )
                    );
                }
            }
        }
        unset($path[$projectId]);
        $memo[$projectId] = $summary;
        return $summary;
    }

    public static function buildChildrenStatusSummary(Project $project, array $childrenByParent, array &$memo = [], $includeSelfWhenLeaf = false)
    {
        $children = $childrenByParent[(int)$project->getId()] ?? [];
        if (count($children) === 0) {
            return $includeSelfWhenLeaf
                ? self::createLeafStatusSummary($project)
                : self::createEmptyStatusSummary();
        }

        $summary = self::createEmptyStatusSummary();
        $childrenWeight = self::getChildrenWeight($children);
        $path = [(int)$project->getId() => true];
        foreach ($children as $child) {
            if ($child instanceof self) {
                $summary = self::mergeStatusSummaries(
                    $summary,
                    self::scaleStatusSummary(
                        self::getLeafStatusSummary($child, $childrenByParent, $memo, $path),
                        self::getSizeWeight($child->get('project_size')) / $childrenWeight
                    )
                );
            }
        }
        return $summary;
    }

    public static function normalizeLevel($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int)$value;
        return $value >= 1 && $value <= 5 ? $value : null;
    }

    public function save()
    {
        $this->set('project_kind', self::normalizeKind($this->get('project_kind')));
        $this->set('status', self::normalizeStatus($this->get('status')));
        $this->set('capture_mode', self::normalizeCaptureMode($this->get('capture_mode')));
        $this->set('project_size', self::normalizeSize($this->get('project_size')));
        $this->set('priority', self::normalizeLevel($this->get('priority')));
        $this->set('importance', self::normalizeLevel($this->get('importance')));

        $now = new \DateTime();
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        $this->set('updated_at', $now);

        return parent::save();
    }

    public static function createFromChecklistTemplate(Project $template, $parentProjectId, \DateTimeInterface $plannedStart, $titleOverride = null, $plannedEnd = null)
    {
        $project = new self();
        $project->set('IDorganization', (int)$template->get('IDorganization'));
        $project->set('IDholon', (int)$template->get('IDholon') ?: null);
        $project->set('IDuser', null);
        $project->set('IDproject_parent', (int)$parentProjectId > 0 ? (int)$parentProjectId : null);
        $project->set('IDdocument_journal', null);
        $project->set('project_kind', self::KIND_STANDARD);
        $project->set('IDproject_template', (int)$template->getId());
        $title = trim((string)$titleOverride);
        $project->set('title', $title !== '' ? $title : (string)$template->get('title'));
        $project->set('description', (string)$template->get('description'));
        $project->set('status', self::STATUS_READY);
        $project->set('planned_start_date', $plannedStart->format('Y-m-d'));
        $project->set('planned_end_date', $plannedEnd instanceof \DateTimeInterface ? $plannedEnd->format('Y-m-d') : null);
        $project->set('priority', $template->get('priority'));
        $project->set('importance', $template->get('importance'));
        $project->set('capture_mode', $template->get('capture_mode'));
        $project->set('project_size', $template->get('project_size'));
        $project->set('active', 1);
        $result = $project->save();
        return is_array($result) && !empty($result['status']) && (int)$project->getId() > 0
            ? $project
            : null;
    }

    public function getOrganization()
    {
        $organization = new Organization();
        return $organization->load((int)$this->get('IDorganization')) ? $organization : null;
    }

    public function getHolon()
    {
        $holonId = (int)$this->get('IDholon');
        if ($holonId <= 0) {
            return null;
        }

        $holon = new Holon();
        return $holon->load($holonId) ? $holon : null;
    }

    public function getResponsible()
    {
        $userId = (int)$this->get('IDuser');
        if ($userId <= 0) {
            return null;
        }

        $user = new User();
        return $user->load($userId) ? $user : null;
    }

    public function getParent()
    {
        $parentId = (int)$this->get('IDproject_parent');
        if ($parentId <= 0) {
            return null;
        }

        $parent = new self();
        return $parent->load($parentId) ? $parent : null;
    }

    public function getTeam()
    {
        $team = new ArrayProjectUser();
        $team->loadForProject((int)$this->getId());
        return $team;
    }

    public function getDocuments()
    {
        $documents = new ArrayProjectDocument();
        $documents->loadForProject((int)$this->getId());
        return $documents;
    }

    public function getJournalDocument()
    {
        $documentId = (int)$this->get('IDdocument_journal');
        if ($documentId <= 0) {
            return null;
        }

        $document = new Document();
        return $document->load($documentId) ? $document : null;
    }

    public function getChildren()
    {
        $projects = new ArrayProject();
        $projects->loadForParent(
            (int)$this->getId(),
            true,
            self::normalizeKind($this->get('project_kind'))
        );
        return $projects;
    }

    public function getTemplateSource()
    {
        $templateId = (int)$this->get('IDproject_template');
        if ($templateId <= 0) {
            return null;
        }

        $project = new self();
        return $project->load($templateId) ? $project : null;
    }

    public function canUseAsParent(Project $parent)
    {
        if ((int)$this->getId() > 0 && (int)$this->getId() === (int)$parent->getId()) {
            return false;
        }

        if (
            (int)$this->get('IDorganization') > 0
            && (int)$parent->get('IDorganization') > 0
            && (int)$this->get('IDorganization') !== (int)$parent->get('IDorganization')
        ) {
            return false;
        }

        $visited = [];
        $current = $parent;
        while ($current instanceof Project && (int)$current->getId() > 0) {
            $currentId = (int)$current->getId();
            if (isset($visited[$currentId])) {
                return false;
            }
            $visited[$currentId] = true;

            if ((int)$this->getId() > 0 && $currentId === (int)$this->getId()) {
                return false;
            }

            $current = $current->getParent();
        }

        return true;
    }
}
?>
