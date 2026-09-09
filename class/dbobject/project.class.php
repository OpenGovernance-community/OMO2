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

    public const PROPOSAL_NONE = 'normal';
    public const PROPOSAL_PENDING = 'pending';
    public const PROPOSAL_ACCEPTED = 'accepted';
    public const PROPOSAL_REFUSED = 'refused';

    public const SAVE_ERROR_PARENT_SOMEDAY = 'parent_someday';
    public const SAVE_ERROR_PARENT_END_DATE = 'parent_end_date';
    public const SAVE_ERROR_BLOCKED_DETAILS = 'blocked_details';
    public const HISTORY_ACTION_AUTO_REACTIVATED = 'project_auto_reactivated';

    public const CAPTURE_MULTIPLE_DOCUMENTS = 'multiple_documents';
    public const CAPTURE_SINGLE_JOURNAL = 'single_journal';

    public const SIZE_S = 'S';
    public const SIZE_M = 'M';
    public const SIZE_L = 'L';
    public const SIZE_XL = 'XL';
    public const SIZE_XXL = 'XXL';

    private $historyActionOverride = '';

    public static function tableName()
    {
        return 'project';
    }

    public static function rules()
    {
        return [
            [['IDorganization', 'title'], 'required'],
            [['id', 'priority', 'importance'], 'integer'],
            [['calculated_importance'], 'float'],
            [['IDorganization', 'IDholon', 'IDuser', 'IDuser_proposed', 'IDproject_parent', 'IDdocument_journal', 'IDproject_template'], 'fk'],
            [['title', 'status', 'capture_mode', 'project_size', 'project_kind', 'proposal_status', 'blocked_reactivate_status'], 'string'],
            [['blocked_reason'], 'text'],
            [['description'], 'html'],
            [['planned_start_date', 'planned_end_date', 'blocked_until'], 'date'],
            [['created_at', 'updated_at', 'closed_at', 'archived_at', 'proposed_at', 'proposal_decided_at'], 'datetime'],
            [['active'], 'boolean'],
            [['blocked_auto_reactivate'], 'boolean'],
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
            'IDuser_proposed' => 'Proposé par',
            'IDproject_parent' => 'Projet parent',
            'IDdocument_journal' => 'Document journal',
            'project_kind' => 'Type de projet',
            'proposal_status' => 'État de la proposition',
            'IDproject_template' => 'Projet modele',
            'title' => 'Titre',
            'description' => 'Description',
            'status' => 'Statut',
            'blocked_reason' => 'Motif du blocage',
            'blocked_until' => 'Date de relance',
            'blocked_auto_reactivate' => 'Réactiver automatiquement',
            'blocked_reactivate_status' => 'Statut de réactivation',
            'planned_start_date' => 'Debut planifie',
            'planned_end_date' => 'Fin planifiee',
            'priority' => 'Priorite',
            'importance' => 'Importance strategique',
            'calculated_importance' => 'Importance strategique calculee',
            'capture_mode' => 'Mode de capture Telegram',
            'project_size' => 'Taille du projet',
            'active' => 'Actif',
            'created_at' => 'Date de creation',
            'updated_at' => 'Date de modification',
            'closed_at' => 'Date de cloture',
            'archived_at' => 'Date d archivage',
            'proposed_at' => 'Date de proposition',
            'proposal_decided_at' => 'Date de décision de la proposition',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'description' => 'Description HTML simple avec paragraphes, listes et mise en forme, y compris pour les modeles de processus.',
            'status' => 'Etat du projet pour une future vue kanban.',
            'blocked_reason' => 'Ce qui empêche actuellement le projet d avancer.',
            'blocked_until' => 'Date à partir de laquelle le blocage doit être réévalué.',
            'blocked_auto_reactivate' => 'Réactive automatiquement le projet à la date de relance.',
            'blocked_reactivate_status' => 'Etat appliqué lors de la réactivation automatique.',
            'planned_start_date' => 'Date a laquelle le projet devrait commencer.',
            'planned_end_date' => 'Date a laquelle le projet devrait etre termine.',
            'closed_at' => 'Date a laquelle le projet est passe a l etat termine.',
            'archived_at' => 'Date a laquelle le projet a ete retire de la vue active.',
            'priority' => 'Niveau a considerer tant que les dates ne sont pas renseignees.',
            'importance' => 'Niveau strategique a considerer quand le temps disponible manque.',
            'calculated_importance' => 'Score calcule automatiquement a partir de l importance strategique declaree, de la chaine de projets et de la position holarchique.',
            'capture_mode' => 'Indique si les captures Telegram doivent creer plusieurs documents ou alimenter un journal unique.',
            'project_size' => 'Taille relative du projet, utilisee pour ponderer sa place dans les barres de synthese.',
            'project_kind' => 'Distingue un projet operationnel d un projet utilise comme modele de processus.',
            'IDproject_template' => 'Projet modele a l origine de cette instance.',
            'proposal_status' => 'Conserve le cycle de validation d un projet proposé avant qu il devienne un projet normal.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'title' => 255,
            'status' => 20,
            'blocked_reactivate_status' => 20,
            'capture_mode' => 30,
            'project_size' => 3,
            'project_kind' => 30,
            'proposal_status' => 20,
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
            $priorityValues[] = [(string)$level, 'P' . (string)$level];
        }

        $importanceValues = [['', 'Non definie']];
        for ($level = 1; $level <= 5; $level++) {
            $importanceValues[] = [(string)$level, (string)$level . '/5'];
        }

        return [
            'status' => $statusValues,
            'priority' => $priorityValues,
            'importance' => $importanceValues,
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
                [self::KIND_CHECKLIST_TEMPLATE, 'Modele de processus'],
            ],
        ];
    }

    public static function getOrder()
    {
        return 'created_at DESC, id DESC';
    }

    public static function handleUserDeparture($organizationId, $userId, $ghostUserId)
    {
        return self::execute(
            "UPDATE project SET IDuser = CASE WHEN active = 1 AND COALESCE(status, '') != :done_status THEN NULL ELSE :ghost_user_id END WHERE IDorganization = :organization_id AND IDuser = :user_id",
            array('done_status' => self::STATUS_DONE, 'ghost_user_id' => (int)$ghostUserId, 'organization_id' => (int)$organizationId, 'user_id' => (int)$userId)
        );
    }

    public static function getIdsReferencedByProjectProperties(int $organizationId): array
    {
        if ($organizationId <= 0) {
            return [];
        }

        $rows = self::fetchAll(
            'SELECT hp.value
             FROM holonproperty hp
             INNER JOIN holon h ON h.id = hp.IDholon
             INNER JOIN property p ON p.id = hp.IDproperty
             WHERE h.IDorganization = :organization_id
               AND hp.active = 1
               AND p.active = 1
               AND p.listitemtype = :list_item_type',
            [
                'organization_id' => $organizationId,
                'list_item_type' => Property::LIST_ITEM_PROJECT,
            ]
        );
        if (!is_array($rows)) {
            return [];
        }
        $projectIds = [];
        $collectIds = static function ($value) use (&$collectIds, &$projectIds): void {
            if (is_array($value)) {
                if (array_key_exists('id', $value)) {
                    $projectId = (int)$value['id'];
                    if ($projectId > 0) {
                        $projectIds[$projectId] = true;
                    }
                    return;
                }
                foreach ($value as $item) {
                    $collectIds($item);
                }
                return;
            }

            if (is_scalar($value) && ctype_digit(trim((string)$value))) {
                $projectId = (int)$value;
                if ($projectId > 0) {
                    $projectIds[$projectId] = true;
                }
            }
        };

        foreach ($rows as $row) {
            $rawValue = trim((string)($row['value'] ?? ''));
            if ($rawValue === '') {
                continue;
            }
            $decodedValue = json_decode($rawValue, true);
            $collectIds(json_last_error() === JSON_ERROR_NONE ? $decodedValue : $rawValue);
        }

        return array_map('intval', array_keys($projectIds));
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

    public static function getWorkTimeStatuses()
    {
        return [
            self::STATUS_READY,
            self::STATUS_IN_PROGRESS,
            self::STATUS_BLOCKED,
            self::STATUS_REVIEW,
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

    public static function proposalStatuses()
    {
        return [
            self::PROPOSAL_NONE,
            self::PROPOSAL_PENDING,
            self::PROPOSAL_ACCEPTED,
            self::PROPOSAL_REFUSED,
        ];
    }

    public static function normalizeProposalStatus($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, self::proposalStatuses(), true) ? $value : self::PROPOSAL_NONE;
    }

    public function isPendingProposal(): bool
    {
        return self::normalizeProposalStatus($this->get('proposal_status')) === self::PROPOSAL_PENDING;
    }

    public function isPrivateProposal(): bool
    {
        return in_array(self::normalizeProposalStatus($this->get('proposal_status')), [
            self::PROPOSAL_PENDING,
            self::PROPOSAL_REFUSED,
        ], true);
    }

    public static function normalizeBlockedReactivateStatus($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, [self::STATUS_READY, self::STATUS_IN_PROGRESS], true)
            ? $value
            : self::STATUS_READY;
    }

    public static function normalizeBlockedUntil($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return new \DateTime($value->format('Y-m-d'));
        }

        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $date = \DateTime::createFromFormat('!Y-m-d', $value);
        $errors = \DateTime::getLastErrors();
        return $date instanceof \DateTime
            && ($errors === false || ((int)$errors['warning_count'] === 0 && (int)$errors['error_count'] === 0))
            ? $date
            : null;
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

    public function saveWithHistoryAction($action)
    {
        $previousAction = $this->historyActionOverride;
        $this->historyActionOverride = trim((string)$action);
        try {
            return $this->save();
        } finally {
            $this->historyActionOverride = $previousAction;
        }
    }

    private static function formatHistoryDate($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d.m.Y');
        }

        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date instanceof \DateTimeInterface ? $date->format('d.m.Y') : $value;
    }

    private static function getHistoryStatusLabel($status)
    {
        $status = self::normalizeStatus($status);
        $catalog = self::getStatusCatalog();
        return (string)($catalog[$status]['label'] ?? $status);
    }

    private static function getHistoryProposalStatusLabel($status)
    {
        return match (self::normalizeProposalStatus($status)) {
            self::PROPOSAL_PENDING => 'En attente',
            self::PROPOSAL_ACCEPTED => 'Acceptee',
            self::PROPOSAL_REFUSED => 'Refusee',
            default => '',
        };
    }

    private static function buildHistoryState(array $values)
    {
        return [
            'title' => trim((string)($values['title'] ?? '')),
            'status' => self::getHistoryStatusLabel($values['status'] ?? self::STATUS_SOMEDAY),
            'proposal_status' => self::getHistoryProposalStatusLabel($values['proposal_status'] ?? self::PROPOSAL_NONE),
            'planned_start_date' => self::formatHistoryDate($values['planned_start_date'] ?? null),
            'planned_end_date' => self::formatHistoryDate($values['planned_end_date'] ?? null),
            'blocked_reason' => trim((string)($values['blocked_reason'] ?? '')),
            'blocked_until' => self::formatHistoryDate($values['blocked_until'] ?? null),
            'blocked_auto_reactivate' => (int)($values['blocked_auto_reactivate'] ?? 0) === 1 ? 'Oui' : 'Non',
            'blocked_reactivate_status' => self::getHistoryStatusLabel($values['blocked_reactivate_status'] ?? self::STATUS_READY),
            '_status' => self::normalizeStatus($values['status'] ?? self::STATUS_SOMEDAY),
        ];
    }

    private static function getStoredHistoryState($projectId)
    {
        $row = self::fetchRow(
            'SELECT title, status, proposal_status, planned_start_date, planned_end_date, blocked_reason, blocked_until, blocked_auto_reactivate, blocked_reactivate_status
             FROM project
             WHERE id = :id
             LIMIT 1',
            ['id' => (int)$projectId]
        );
        return is_array($row) ? self::buildHistoryState($row) : null;
    }

    private function getCurrentHistoryState()
    {
        return self::buildHistoryState([
            'title' => $this->get('title'),
            'status' => $this->get('status'),
            'proposal_status' => $this->get('proposal_status'),
            'planned_start_date' => $this->get('planned_start_date'),
            'planned_end_date' => $this->get('planned_end_date'),
            'blocked_reason' => $this->get('blocked_reason'),
            'blocked_until' => $this->get('blocked_until'),
            'blocked_auto_reactivate' => $this->get('blocked_auto_reactivate'),
            'blocked_reactivate_status' => $this->get('blocked_reactivate_status'),
        ]);
    }

    private static function buildHistoryChanges(array $before, array $after)
    {
        $labels = [
            'title' => 'Intitulé',
            'status' => 'Statut',
            'proposal_status' => 'Etat de la proposition',
            'planned_start_date' => 'Début planifié',
            'planned_end_date' => 'Fin planifiée',
            'blocked_reason' => 'Motif du blocage',
            'blocked_until' => 'Date de réexamen',
            'blocked_auto_reactivate' => 'Réactivation automatique',
            'blocked_reactivate_status' => 'État après réactivation',
        ];
        $changes = [];
        $isOrWasBlocked = (string)($before['_status'] ?? '') === self::STATUS_BLOCKED
            || (string)($after['_status'] ?? '') === self::STATUS_BLOCKED;
        foreach ($labels as $field => $label) {
            if (str_starts_with($field, 'blocked_') && !$isOrWasBlocked) {
                continue;
            }
            $beforeValue = (string)($before[$field] ?? '');
            $afterValue = (string)($after[$field] ?? '');
            if ($beforeValue === $afterValue) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'label' => $label,
                'before' => $beforeValue,
                'after' => $afterValue,
                'status' => $beforeValue === '' ? 'added' : ($afterValue === '' ? 'removed' : 'changed'),
            ];
        }
        return $changes;
    }

    private static function getHistoryActionForChanges(array $changes)
    {
        $fields = array_values(array_unique(array_filter(array_map(static function (array $change) {
            return trim((string)($change['field'] ?? ''));
        }, $changes))));
        if (in_array('status', $fields, true)) {
            return 'project_status_updated';
        }
        if (count($fields) === 1 && $fields[0] === 'title') {
            return 'project_title_updated';
        }
        if (count($fields) > 0 && count(array_diff($fields, ['planned_start_date', 'planned_end_date'])) === 0) {
            return 'project_schedule_updated';
        }
        if (count($fields) > 0 && count(array_diff($fields, [
            'blocked_reason',
            'blocked_until',
            'blocked_auto_reactivate',
            'blocked_reactivate_status',
        ])) === 0) {
            return 'project_block_details_updated';
        }
        return 'project_updated';
    }

    private static function buildHistoryChangeMessage(array $change, $projectToken)
    {
        $field = trim((string)($change['field'] ?? ''));
        $before = trim((string)($change['before'] ?? ''));
        $after = trim((string)($change['after'] ?? ''));
        $label = trim((string)($change['label'] ?? 'Cette information'));
        $transition = static function ($subject) use ($before, $after) {
            if ($before === '') {
                return $subject . ' a été défini à ' . $after . '.';
            }
            if ($after === '') {
                return $subject . ' a été retiré.';
            }
            return $subject . ' est passé de ' . $before . ' à ' . $after . '.';
        };

        switch ($field) {
            case 'status':
                return $transition('Le statut du projet ' . $projectToken);
            case 'proposal_status':
                return $transition('L etat de la proposition du projet ' . $projectToken);
            case 'title':
                return $transition('L’intitulé du projet ' . $projectToken);
            case 'planned_start_date':
                return $transition('La date de début planifiée du projet ' . $projectToken);
            case 'planned_end_date':
                return $transition('La date de fin planifiée du projet ' . $projectToken);
            case 'blocked_reason':
                return $transition('Le motif du blocage du projet ' . $projectToken);
            case 'blocked_until':
                return $transition('La date de réexamen du projet ' . $projectToken);
            case 'blocked_auto_reactivate':
                return $transition('La réactivation automatique du projet ' . $projectToken);
            case 'blocked_reactivate_status':
                return $transition('L’état après réactivation du projet ' . $projectToken);
            default:
                return $transition($label . ' du projet ' . $projectToken);
        }
    }

    private static function buildHistoryChangeContent(array $changes, $projectToken)
    {
        $messages = [];
        foreach ($changes as $change) {
            $message = trim(self::buildHistoryChangeMessage($change, $projectToken));
            if ($message !== '') {
                $messages[] = $message;
            }
        }
        return implode("\n", $messages);
    }

    private static function getHistoryStateWithoutMetadata(array $state)
    {
        unset($state['_status']);
        return $state;
    }

    private function recordHistory($beforeState, $historyActionOverride = '')
    {
        $afterState = $this->getCurrentHistoryState();
        $isNew = !is_array($beforeState);
        $changes = self::buildHistoryChanges($isNew ? [] : $beforeState, $afterState);
        if (!$isNew && count($changes) === 0) {
            return;
        }

        $projectId = (int)$this->getId();
        $organizationId = (int)$this->get('IDorganization');
        if ($projectId <= 0 || $organizationId <= 0) {
            return;
        }

        $projectToken = History::buildReferenceToken('project', $projectId, (string)$afterState['title']);
        $beforeStatus = is_array($beforeState) ? (string)($beforeState['_status'] ?? '') : '';
        $afterStatus = (string)($afterState['_status'] ?? '');
        $historyActionOverride = trim((string)$historyActionOverride);
        $action = self::getHistoryActionForChanges($changes);
        $content = self::buildHistoryChangeContent($changes, $projectToken);

        if ($isNew) {
            $action = 'project_created';
            $content = 'Création du projet ' . $projectToken . '.';
        } elseif ($historyActionOverride === self::HISTORY_ACTION_AUTO_REACTIVATED) {
            $action = self::HISTORY_ACTION_AUTO_REACTIVATED;
            $content = 'Réactivation automatique du projet ' . $projectToken
                . ' à l état ' . (string)$afterState['status'] . '.';
            $blockedReason = trim((string)($beforeState['blocked_reason'] ?? ''));
            if ($blockedReason !== '') {
                $content .= ' Motif du blocage : ' . $blockedReason . '.';
            }
        } elseif ($beforeStatus !== self::STATUS_BLOCKED && $afterStatus === self::STATUS_BLOCKED) {
            $action = 'project_blocked';
            $content = 'Blocage du projet ' . $projectToken . '.';
            $blockedReason = trim((string)($afterState['blocked_reason'] ?? ''));
            if ($blockedReason !== '') {
                $content .= ' Motif du blocage : ' . $blockedReason . '.';
            }
        }

        $authorUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;

        try {
            $result = History::createEntry(
                $organizationId,
                $authorUserId,
                $action,
                $content,
                [
                    'targetType' => 'project',
                    'targetId' => $projectId,
                    'before' => $isNew ? [] : self::getHistoryStateWithoutMetadata($beforeState),
                    'after' => self::getHistoryStateWithoutMetadata($afterState),
                    'changes' => $changes,
                ],
                'project',
                $projectId
            );
            if (!is_array($result) || empty($result['status'])) {
                error_log('Project history entry could not be saved for project ' . $projectId . '.');
            }
        } catch (\Throwable $exception) {
            error_log('Project history entry failed for project ' . $projectId . ': ' . $exception->getMessage());
        }
    }

    public function save()
    {
        $historyBeforeState = (int)$this->getId() > 0 ? self::getStoredHistoryState((int)$this->getId()) : null;
        $historyActionOverride = $this->historyActionOverride;
        $storedInputs = (int)$this->getId() > 0 ? self::getStoredImportanceInputs((int)$this->getId()) : null;
        if (is_array($storedInputs) && array_key_exists('calculated_importance', $storedInputs)) {
            // This field is server-owned. A value posted by a client must never replace it.
            $this->set('calculated_importance', (float)$storedInputs['calculated_importance']);
        } elseif ((int)$this->getId() <= 0) {
            $this->set('calculated_importance', 0.0);
        }
        $this->set('project_kind', self::normalizeKind($this->get('project_kind')));
        $this->set('proposal_status', self::normalizeProposalStatus($this->get('proposal_status')));
        $this->set('status', self::normalizeStatus($this->get('status')));
        $this->set('capture_mode', self::normalizeCaptureMode($this->get('capture_mode')));
        $this->set('project_size', self::normalizeSize($this->get('project_size')));
        $this->set('priority', self::normalizeLevel($this->get('priority')));
        $this->set('importance', self::normalizeLevel($this->get('importance')));

        $status = self::normalizeStatus($this->get('status'));
        if ($status === self::STATUS_BLOCKED) {
            $blockedReason = trim((string)$this->get('blocked_reason'));
            $blockedUntil = self::normalizeBlockedUntil($this->get('blocked_until'));
            if ($blockedReason === '' || !($blockedUntil instanceof \DateTimeInterface)) {
                return ['status' => false, 'errorCode' => self::SAVE_ERROR_BLOCKED_DETAILS];
            }
            $this->set('blocked_reason', mb_substr($blockedReason, 0, 4000, 'UTF-8'));
            $this->set('blocked_until', $blockedUntil);
            $this->set('blocked_auto_reactivate', (int)(bool)$this->get('blocked_auto_reactivate'));
            $this->set('blocked_reactivate_status', self::normalizeBlockedReactivateStatus($this->get('blocked_reactivate_status')));
        } else {
            $this->set('blocked_reason', null);
            $this->set('blocked_until', null);
            $this->set('blocked_auto_reactivate', 0);
            $this->set('blocked_reactivate_status', self::STATUS_READY);
        }

        $parentId = (int)$this->get('IDproject_parent');
        $parent = null;
        if ($parentId > 0) {
            $parent = new self();
            if (!$parent->load($parentId) || !$this->canUseAsParent($parent)) {
                return ['status' => false, 'text' => 'Invalid project parent.'];
            }
        }

        $status = self::normalizeStatus($this->get('status'));
        $parentEndDate = $parent instanceof self ? $parent->get('planned_end_date') : null;
        if ($parentEndDate instanceof \DateTimeInterface) {
            if ($status === self::STATUS_SOMEDAY) {
                return ['status' => false, 'errorCode' => self::SAVE_ERROR_PARENT_SOMEDAY];
            }

            $plannedEndDate = $this->get('planned_end_date');
            if (!$plannedEndDate) {
                $this->set('planned_end_date', $parentEndDate->format('Y-m-d'));
            } elseif ($plannedEndDate instanceof \DateTimeInterface && $plannedEndDate > $parentEndDate) {
                return ['status' => false, 'errorCode' => self::SAVE_ERROR_PARENT_END_DATE];
            }
        }

        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        if ($status === self::STATUS_SOMEDAY) {
            $this->set('planned_start_date', null);
            $this->set('planned_end_date', null);
        } elseif ($status === self::STATUS_IN_PROGRESS && !$this->get('planned_start_date')) {
            $this->set('planned_start_date', $today);
        } elseif ($status === self::STATUS_DONE && !$this->get('planned_end_date')) {
            $this->set('planned_end_date', $today);
        }

        $now = new \DateTime();
        if (self::normalizeStatus($this->get('status')) === self::STATUS_DONE && !($this->get('closed_at') instanceof \DateTimeInterface)) {
            $this->set('closed_at', $now);
        }
        if ((int)$this->get('active') !== 1 && !($this->get('archived_at') instanceof \DateTimeInterface)) {
            $this->set('archived_at', $now);
        }
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        $this->set('updated_at', $now);

        $result = parent::save();
        if (!is_array($result) || empty($result['status']) || (int)$this->getId() <= 0) {
            return $result;
        }

        $requiresRecalculation = !is_array($storedInputs)
            || (int)($storedInputs['importance'] ?? 0) !== (int)$this->get('importance')
            || (int)($storedInputs['IDproject_parent'] ?? 0) !== (int)$this->get('IDproject_parent')
            || (int)($storedInputs['IDholon'] ?? 0) !== (int)$this->get('IDholon');
        if ($requiresRecalculation && self::normalizeKind($this->get('project_kind')) === self::KIND_STANDARD) {
            $scores = ProjectImportanceCalculator::recalculateBranch(
                (int)$this->get('IDorganization'),
                (int)$this->getId()
            );
            if (array_key_exists((int)$this->getId(), $scores)) {
                $this->set('calculated_importance', (float)$scores[(int)$this->getId()]);
            }
        }

        $this->recordHistory($historyBeforeState, $historyActionOverride);
        return $result;
    }

    public static function reactivateDueBlockedBatch($limit = 100)
    {
        $limit = max(1, min(500, (int)$limit));
        $rows = self::fetchAll(
            'SELECT id
             FROM project
             WHERE active = 1
               AND status = :status
               AND blocked_auto_reactivate = 1
               AND blocked_until IS NOT NULL
               AND blocked_until <= CURRENT_DATE
             ORDER BY blocked_until ASC, id ASC
             LIMIT ' . $limit,
            ['status' => self::STATUS_BLOCKED]
        );
        if (!is_array($rows)) {
            return 0;
        }

        $reactivated = 0;
        foreach ($rows as $row) {
            $project = new self();
            $projectId = (int)($row['id'] ?? 0);
            if ($projectId <= 0 || !$project->load($projectId) || (int)$project->get('active') !== 1) {
                continue;
            }
            if (self::normalizeStatus($project->get('status')) !== self::STATUS_BLOCKED) {
                continue;
            }

            $project->set('status', self::normalizeBlockedReactivateStatus($project->get('blocked_reactivate_status')));
            $saveResult = $project->saveWithHistoryAction(self::HISTORY_ACTION_AUTO_REACTIVATED);
            if (is_array($saveResult) && !empty($saveResult['status'])) {
                $reactivated++;
            }
        }

        return $reactivated;
    }

    private static function getStoredImportanceInputs($projectId): ?array
    {
        $row = self::fetchRow(
            'SELECT IDorganization, IDproject_parent, IDholon, importance, calculated_importance FROM project WHERE id = :id LIMIT 1',
            ['id' => (int)$projectId]
        );
        return is_array($row) ? $row : null;
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

    public function getReviewMetadata()
    {
        $holon = $this->getHolon();
        $responsible = $this->getResponsible();
        $responsibleLabel = '';
        if ($responsible instanceof User) {
            $responsibleLabel = trim(trim((string)$responsible->get('firstname')) . ' ' . trim((string)$responsible->get('lastname')));
            if ($responsibleLabel === '') {
                $responsibleLabel = trim((string)$responsible->get('username'));
            }
            if ($responsibleLabel === '') {
                $responsibleLabel = trim((string)$responsible->get('email'));
            }
        }
        return [
            'holonLabel' => $holon instanceof Holon ? trim((string)$holon->getDisplayName()) : '',
            'responsibleLabel' => $responsibleLabel,
        ];
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

    public function getEvents()
    {
        $events = new ArrayEvent();
        $events->loadForProject((int)$this->getId());
        return $events;
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

    public function completeAndArchiveActiveTree()
    {
        if ((int)$this->getId() <= 0 || (int)$this->get('active') !== 1) {
            return ['status' => false, 'affectedCount' => 0, 'projectIds' => []];
        }

        $tree = [];
        $visited = [];
        $collect = function (Project $project) use (&$collect, &$tree, &$visited): void {
            $projectId = (int)$project->getId();
            if ($projectId <= 0 || isset($visited[$projectId])) {
                return;
            }
            $visited[$projectId] = true;
            foreach ($project->getChildren() as $child) {
                if ($child instanceof Project) {
                    $collect($child);
                }
            }
            $tree[] = $project;
        };
        $collect($this);

        $pdo = DbObject::getPdo();
        $startedTransaction = false;
        try {
            if ($pdo instanceof \PDO && !$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $startedTransaction = true;
            }
            foreach ($tree as $treeProject) {
                $treeProject->set('status', self::STATUS_DONE);
                $treeProject->set('active', 0);
                $result = $treeProject->save();
                if (!is_array($result) || empty($result['status'])) {
                    throw new \RuntimeException('Unable to complete and archive project tree.');
                }
            }
            if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->commit();
            }
            return [
                'status' => true,
                'affectedCount' => count($tree),
                'projectIds' => array_map(static fn (Project $treeProject): int => (int)$treeProject->getId(), $tree),
            ];
        } catch (\Throwable $exception) {
            if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['status' => false, 'affectedCount' => 0, 'projectIds' => []];
        }
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

    public function delete()
    {
        $projectId = (int)$this->getId();
        $organizationId = (int)$this->get('IDorganization');
        $childIds = self::fetchAll(
            'SELECT id FROM project WHERE IDproject_parent = :parent_id',
            ['parent_id' => $projectId]
        );
        $result = parent::delete();
        if (!$result || $organizationId <= 0 || !is_array($childIds)) {
            return $result;
        }
        foreach ($childIds as $childRow) {
            $childId = (int)($childRow['id'] ?? 0);
            if ($childId > 0) {
                ProjectImportanceCalculator::recalculateBranch($organizationId, $childId);
            }
        }
        return $result;
    }
}
?>
