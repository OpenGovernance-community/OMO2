<?php
namespace dbObject;

class DecisionProcess extends DbObject
{
    const TYPE_DECISION = 'decision';
    const TYPE_CONSULTATION = 'consultation';

    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_CONSULTATION = 'consultation';
    const STATUS_EVALUATION = 'evaluation';
    const STATUS_RESULTS = 'results';
    const STATUS_ARCHIVED = 'archived';

    const METHOD_SIMPLE_VOTE = 'simple_vote';
    const METHOD_MAJORITY_JUDGMENT = 'majority_judgment';
    const METHOD_CONSENT = 'consent';

    public static function tableName()
    {
        return 'decision_process';
    }

    public static function rules()
    {
        return [
            [['title', 'decision_type', 'status', 'evaluation_method'], 'required'],
            [['id'], 'integer'],
            [['IDorganization', 'IDholon', 'IDuser'], 'fk'],
            [['title', 'decision_type', 'status', 'evaluation_method'], 'string'],
            [['description'], 'text'],
            [['parameters'], 'parameters'],
            [[
                'consultation_start_at',
                'consultation_end_at',
                'evaluation_start_at',
                'evaluation_end_at',
                'results_published_at',
                'archived_at',
                'created_at',
                'updated_at',
            ], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDorganization' => 'Organisation',
            'IDholon' => 'Holon',
            'IDuser' => 'Createur',
            'title' => 'Titre',
            'description' => 'Description',
            'decision_type' => 'Type',
            'status' => 'Statut',
            'evaluation_method' => 'Methode',
            'parameters' => 'Parametres',
            'consultation_start_at' => 'Debut consultation',
            'consultation_end_at' => 'Fin consultation',
            'evaluation_start_at' => 'Debut evaluation',
            'evaluation_end_at' => 'Fin evaluation',
            'results_published_at' => 'Publication des resultats',
            'archived_at' => 'Archivage',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'decision_type' => 'Distingue une prise de decision engageante d une consultation.',
            'status' => 'Cycle de vie commun independant de la methode d evaluation.',
            'evaluation_method' => 'Cle technique de la methode modulaire utilisee.',
            'parameters' => 'Configuration method-specific et options complementaires.',
            'IDholon' => 'Contexte holon optionnel si la prise de decision est rattachee a un groupe.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'title' => 190,
            'decision_type' => 20,
            'status' => 20,
            'evaluation_method' => 40,
        ];
    }

    public static function getOrder()
    {
        return 'created_at DESC, id DESC';
    }

    public static function getDecisionTypeCatalog()
    {
        return [
            self::TYPE_DECISION => [
                'label' => 'Decision',
                'description' => 'Le resultat produit une decision.',
            ],
            self::TYPE_CONSULTATION => [
                'label' => 'Consultation',
                'description' => 'Le resultat sert de base de consultation.',
            ],
        ];
    }

    public static function getStatusCatalog()
    {
        return [
            self::STATUS_DRAFT => [
                'label' => 'Preparation',
                'description' => 'La prise de decision est encore en brouillon.',
            ],
            self::STATUS_SCHEDULED => [
                'label' => 'Planifiee',
                'description' => 'La prise de decision est preparee et planifiee pour plus tard.',
            ],
            self::STATUS_CONSULTATION => [
                'label' => 'Consultation',
                'description' => 'Les participants peuvent consulter et preparer leurs reponses.',
            ],
            self::STATUS_EVALUATION => [
                'label' => 'Evaluation',
                'description' => 'Les reponses sont en cours de saisie ou de consolidation.',
            ],
            self::STATUS_RESULTS => [
                'label' => 'Resultats',
                'description' => 'Les resultats sont disponibles.',
            ],
            self::STATUS_ARCHIVED => [
                'label' => 'Archive',
                'description' => 'La prise de decision est cloturee et archivee.',
            ],
        ];
    }

    public static function getEvaluationMethodCatalog()
    {
        return [
            self::METHOD_SIMPLE_VOTE => [
                'label' => 'Vote simple',
                'description' => 'Une personne choisit une proposition parmi N.',
                'response_shape' => 'single_selection',
                'supports_multiple_proposals' => true,
            ],
            self::METHOD_MAJORITY_JUDGMENT => [
                'label' => 'Jugement majoritaire',
                'description' => 'Chaque proposition recoit une mention ou une valeur.',
                'response_shape' => 'per_proposal_scale',
                'supports_multiple_proposals' => true,
            ],
            self::METHOD_CONSENT => [
                'label' => 'Consentement',
                'description' => 'Une proposition est acceptee sauf objections bloquantes.',
                'response_shape' => 'consent_objection',
                'supports_multiple_proposals' => true,
            ],
        ];
    }

    public static function isValidDecisionType($decisionType)
    {
        return array_key_exists((string)$decisionType, self::getDecisionTypeCatalog());
    }

    public static function normalizeDecisionType($decisionType)
    {
        $decisionType = trim((string)$decisionType);
        return self::isValidDecisionType($decisionType) ? $decisionType : self::TYPE_DECISION;
    }

    public static function isValidStatus($status)
    {
        return array_key_exists((string)$status, self::getStatusCatalog());
    }

    public static function normalizeStatus($status)
    {
        $status = trim((string)$status);
        return self::isValidStatus($status) ? $status : self::STATUS_DRAFT;
    }

    public static function getStatusRank($status)
    {
        static $ranks = [
            self::STATUS_DRAFT => 10,
            self::STATUS_SCHEDULED => 20,
            self::STATUS_CONSULTATION => 30,
            self::STATUS_EVALUATION => 40,
            self::STATUS_RESULTS => 50,
            self::STATUS_ARCHIVED => 60,
        ];

        $status = self::normalizeStatus($status);
        return (int)($ranks[$status] ?? 0);
    }

    public static function isValidEvaluationMethod($method)
    {
        return array_key_exists((string)$method, self::getEvaluationMethodCatalog());
    }

    public static function normalizeEvaluationMethod($method)
    {
        $method = trim((string)$method);
        return self::isValidEvaluationMethod($method) ? $method : self::METHOD_SIMPLE_VOTE;
    }

    public function save()
    {
        $this->set('decision_type', self::normalizeDecisionType($this->get('decision_type')));
        $this->set('status', self::normalizeStatus($this->get('status')));
        $this->set('evaluation_method', self::normalizeEvaluationMethod($this->get('evaluation_method')));

        $saveResult = parent::save();
        if (empty($saveResult['status']) || (int)$this->getId() <= 0) {
            return $saveResult;
        }

        $groups = $this->getDecisionGroups(false);
        if (count($groups) <= 1) {
            $this->syncPrimaryGroupFromProcess();
        }

        return $saveResult;
    }

    public function resolveAutomaticStatus($referenceDateTime = null)
    {
        $currentStatus = self::normalizeStatus($this->get('status'));
        $derivedStatus = $currentStatus;
        $referenceDateTime = self::normalizeDateTimeValue($referenceDateTime);
        if (!$referenceDateTime instanceof \DateTimeInterface) {
            $referenceDateTime = new \DateTimeImmutable('now');
        }

        $consultationStart = self::normalizeDateTimeValue($this->get('consultation_start_at'));
        $evaluationStart = self::normalizeDateTimeValue($this->get('evaluation_start_at'));
        $evaluationEnd = self::normalizeDateTimeValue($this->get('evaluation_end_at'));
        $resultsPublishedAt = self::normalizeDateTimeValue($this->get('results_published_at'));
        $archivedAt = self::normalizeDateTimeValue($this->get('archived_at'));

        $hasFutureOrCurrentSchedule = $consultationStart instanceof \DateTimeInterface
            || $evaluationStart instanceof \DateTimeInterface
            || $evaluationEnd instanceof \DateTimeInterface
            || $resultsPublishedAt instanceof \DateTimeInterface
            || $archivedAt instanceof \DateTimeInterface;

        if ($hasFutureOrCurrentSchedule) {
            $derivedStatus = self::STATUS_SCHEDULED;
        }

        if ($consultationStart instanceof \DateTimeInterface && $consultationStart <= $referenceDateTime) {
            $derivedStatus = self::STATUS_CONSULTATION;
        }

        if ($evaluationStart instanceof \DateTimeInterface && $evaluationStart <= $referenceDateTime) {
            $derivedStatus = self::STATUS_EVALUATION;
        }

        if (
            ($evaluationEnd instanceof \DateTimeInterface && $evaluationEnd <= $referenceDateTime)
            || ($resultsPublishedAt instanceof \DateTimeInterface && $resultsPublishedAt <= $referenceDateTime)
        ) {
            $derivedStatus = self::STATUS_RESULTS;
        }

        if ($archivedAt instanceof \DateTimeInterface && $archivedAt <= $referenceDateTime) {
            $derivedStatus = self::STATUS_ARCHIVED;
        }

        return self::getStatusRank($derivedStatus) > self::getStatusRank($currentStatus)
            ? $derivedStatus
            : $currentStatus;
    }

    public function syncLifecycleStatus($referenceDateTime = null)
    {
        $referenceDateTime = self::normalizeDateTimeValue($referenceDateTime);
        if (!$referenceDateTime instanceof \DateTimeInterface) {
            $referenceDateTime = new \DateTimeImmutable('now');
        }

        $nextStatus = $this->resolveAutomaticStatus($referenceDateTime);
        $currentStatus = self::normalizeStatus($this->get('status'));
        if ($nextStatus === $currentStatus) {
            return false;
        }

        $this->set('status', $nextStatus);

        if ($nextStatus === self::STATUS_RESULTS && !($this->get('results_published_at') instanceof \DateTimeInterface)) {
            $this->set('results_published_at', $referenceDateTime);
        }

        if ($nextStatus === self::STATUS_ARCHIVED && !($this->get('archived_at') instanceof \DateTimeInterface)) {
            $this->set('archived_at', $referenceDateTime);
        }

        $saveResult = $this->save();
        return !empty($saveResult['status']);
    }

    public static function syncLifecycleStatusesForOrganization($organizationId, $referenceDateTime = null)
    {
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return 0;
        }

        $referenceDateTime = self::normalizeDateTimeValue($referenceDateTime);
        if (!$referenceDateTime instanceof \DateTimeInterface) {
            $referenceDateTime = new \DateTimeImmutable('now');
        }

        $rows = self::fetchAll(
            'SELECT *
             FROM `decision_process`
             WHERE `IDorganization` = :organization_id',
            [
                'organization_id' => $organizationId,
            ]
        );

        if (!is_array($rows) || count($rows) === 0) {
            return 0;
        }

        $updatedCount = 0;
        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row['id'])) {
                continue;
            }

            $decision = new self();
            $decision->loadFromArray($row);
            $decision->setId((int)$row['id']);

            if ($decision->syncLifecycleStatus($referenceDateTime)) {
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    public function getMethodDefinition()
    {
        $primaryGroup = $this->getPrimaryGroup(false);
        if ($primaryGroup instanceof \dbObject\DecisionGroup) {
            return $primaryGroup->getMethodDefinition();
        }

        $catalog = self::getEvaluationMethodCatalog();
        $method = self::normalizeEvaluationMethod($this->get('evaluation_method'));
        return isset($catalog[$method]) ? $catalog[$method] : $catalog[self::METHOD_SIMPLE_VOTE];
    }

    public function getDecisionGroups($activeOnly = false)
    {
        $items = new \dbObject\ArrayDecisionGroup();
        $params = [
            'where' => [
                ['field' => 'IDdecision_process', 'value' => (int)$this->getId()],
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

    public function getPrimaryGroup($createIfMissing = false)
    {
        $group = \dbObject\DecisionGroup::findPrimaryByDecisionProcessId((int)$this->getId());
        if ($group instanceof \dbObject\DecisionGroup || !$createIfMissing) {
            return $group;
        }

        return $this->ensurePrimaryGroup();
    }

    protected function applyLegacyProcessFieldsToGroup(\dbObject\DecisionGroup $group, $preservePresentation = false)
    {
        $group->set('IDdecision_process', (int)$this->getId());
        $group->set('decision_type', self::normalizeDecisionType($this->get('decision_type')));
        $group->set('evaluation_method', self::normalizeEvaluationMethod($this->get('evaluation_method')));
        $currentGroupTitle = trim((string)$group->get('title'));
        if (!$preservePresentation || $currentGroupTitle === '') {
            $group->set('title', trim((string)$this->get('title')));
        }

        $currentGroupDescription = trim((string)$group->get('description'));
        if (!$preservePresentation || $currentGroupDescription === '') {
            $group->set('description', $this->get('description'));
        }

        $group->set('parameters', $this->get('parameters'));
        $group->set('position', max(1, (int)$group->get('position')));
        $group->set('active', 1);
    }

    public function ensurePrimaryGroup()
    {
        if ((int)$this->getId() <= 0) {
            return null;
        }

        $group = $this->getPrimaryGroup(false);
        if ($group instanceof \dbObject\DecisionGroup) {
            return $group;
        }

        $group = new \dbObject\DecisionGroup();
        $group->set('position', 1);
        $this->applyLegacyProcessFieldsToGroup($group);
        $saveResult = $group->save();
        return !empty($saveResult['status']) ? $group : null;
    }

    public function buildNextDecisionGroupTitle()
    {
        $groups = $this->getDecisionGroups(false);
        return 'Bloc ' . (string)(count($groups) + 1);
    }

    public function addDecisionGroup($evaluationMethod, $decisionType = '', $title = '', $description = null)
    {
        if ((int)$this->getId() <= 0) {
            return null;
        }

        $group = new \dbObject\DecisionGroup();
        $group->set('IDdecision_process', (int)$this->getId());
        $group->set('decision_type', self::normalizeDecisionType($decisionType !== '' ? $decisionType : $this->get('decision_type')));
        $group->set('evaluation_method', self::normalizeEvaluationMethod($evaluationMethod));
        $group->set('title', trim((string)$title) !== '' ? trim((string)$title) : $this->buildNextDecisionGroupTitle());
        $group->set('description', $description);
        $group->set('parameters', []);
        $group->set('position', count($this->getDecisionGroups(false)) + 1);
        $group->set('active', 1);
        $saveResult = $group->save();
        return !empty($saveResult['status']) ? $group : null;
    }

    public function syncPrimaryGroupFromProcess()
    {
        if ((int)$this->getId() <= 0) {
            return false;
        }

        $group = $this->getPrimaryGroup(false);
        $preservePresentation = $group instanceof \dbObject\DecisionGroup;
        if (!$group instanceof \dbObject\DecisionGroup) {
            $group = new \dbObject\DecisionGroup();
            $group->set('position', 1);
            $preservePresentation = false;
        }

        $this->applyLegacyProcessFieldsToGroup($group, $preservePresentation);
        $saveResult = $group->save();
        return !empty($saveResult['status']);
    }

    public function getProposals($activeOnly = false)
    {
        $primaryGroup = $this->getPrimaryGroup(false);
        if ($primaryGroup instanceof \dbObject\DecisionGroup) {
            return $primaryGroup->getProposals($activeOnly);
        }

        $items = new \dbObject\ArrayDecisionProposal();
        $params = [
            'where' => [
                ['field' => 'IDdecision_process', 'value' => (int)$this->getId()],
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

    public function getParticipants($activeOnly = false)
    {
        $items = new \dbObject\ArrayDecisionParticipant();
        $params = [
            'where' => [
                ['field' => 'IDdecision_process', 'value' => (int)$this->getId()],
            ],
            'orderBy' => [
                ['field' => 'created_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ];

        if ($activeOnly) {
            $params['where'][] = ['field' => 'active', 'value' => 1];
        }

        $items->load($params);
        return $items;
    }

    public function getInvitations($activeOnly = false)
    {
        $items = new \dbObject\ArrayDecisionInvitation();
        $params = [
            'where' => [
                ['field' => 'IDdecision_process', 'value' => (int)$this->getId()],
            ],
            'orderBy' => [
                ['field' => 'created_at', 'dir' => 'ASC'],
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
        $primaryGroup = $this->getPrimaryGroup(false);
        if ($primaryGroup instanceof \dbObject\DecisionGroup) {
            return $primaryGroup->getResponses($status);
        }

        $items = new \dbObject\ArrayDecisionResponse();
        $params = [
            'where' => [
                ['field' => 'IDdecision_process', 'value' => (int)$this->getId()],
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
        $primaryGroup = $this->getPrimaryGroup(false);
        if ($primaryGroup instanceof \dbObject\DecisionGroup) {
            return $primaryGroup->getResult();
        }

        return \dbObject\DecisionResult::findByDecisionProcessId((int)$this->getId());
    }

    protected static function normalizeDateTimeValue($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function getSubmittedResponseCount()
    {
        return (int)self::fetchValue(
            'SELECT COUNT(*)
             FROM `decision_response`
             WHERE `IDdecision_process` = :decision_process_id
               AND (
                    `status` = :submitted_status
                    OR `submitted_at` IS NOT NULL
               )',
            [
                'decision_process_id' => (int)$this->getId(),
                'submitted_status' => \dbObject\DecisionResponse::STATUS_SUBMITTED,
            ]
        );
    }

    public function hasSubmittedResponses()
    {
        return $this->getSubmittedResponseCount() > 0;
    }

    public function resolveManagerCloseAction()
    {
        return $this->hasSubmittedResponses() ? 'archive' : 'delete';
    }

    public function archiveForManager($referenceDateTime = null)
    {
        if ((int)$this->getId() <= 0) {
            return [
                'status' => false,
                'action' => 'archive',
                'message' => 'Prise de decision introuvable.',
            ];
        }

        $referenceDateTime = self::normalizeDateTimeValue($referenceDateTime);
        if (!$referenceDateTime instanceof \DateTimeInterface) {
            $referenceDateTime = new \DateTimeImmutable('now');
        }

        $pdo = self::getPdo();
        if (!$pdo) {
            return [
                'status' => false,
                'action' => 'archive',
                'message' => 'Connexion a la base impossible.',
            ];
        }

        try {
            $startedTransaction = !$pdo->inTransaction();
            if ($startedTransaction) {
                $pdo->beginTransaction();
            }

            $this->set('status', self::STATUS_ARCHIVED);
            $this->set('archived_at', $referenceDateTime);
            $saveResult = $this->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                throw new \RuntimeException('decision_archive_save_failed');
            }

            $resultUpdate = self::execute(
                'UPDATE `decision_result`
                 SET `status` = :archived_status
                 WHERE `IDdecision_process` = :decision_process_id
                   AND `status` != :current_status',
                [
                    'archived_status' => \dbObject\DecisionResult::STATUS_ARCHIVED,
                    'current_status' => \dbObject\DecisionResult::STATUS_ARCHIVED,
                    'decision_process_id' => (int)$this->getId(),
                ]
            );
            if ($resultUpdate === false) {
                throw new \RuntimeException('decision_archive_result_update_failed');
            }

            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }

            return [
                'status' => true,
                'action' => 'archive',
                'message' => 'Prise de decision archivee.',
            ];
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                'status' => false,
                'action' => 'archive',
                'message' => 'Impossible d archiver cette prise de decision pour le moment.',
            ];
        }
    }

    public function deleteWithRelations()
    {
        if ((int)$this->getId() <= 0) {
            return [
                'status' => false,
                'action' => 'delete',
                'message' => 'Prise de decision introuvable.',
            ];
        }

        $pdo = self::getPdo();
        if (!$pdo) {
            return [
                'status' => false,
                'action' => 'delete',
                'message' => 'Connexion a la base impossible.',
            ];
        }

        $decisionId = (int)$this->getId();

        try {
            $startedTransaction = !$pdo->inTransaction();
            if ($startedTransaction) {
                $pdo->beginTransaction();
            }

            $deleteQueries = [
                'DELETE FROM `decision_result` WHERE `IDdecision_process` = :decision_process_id',
                'DELETE FROM `decision_response` WHERE `IDdecision_process` = :decision_process_id',
                'DELETE FROM `decision_participant` WHERE `IDdecision_process` = :decision_process_id',
                'DELETE FROM `decision_invitation` WHERE `IDdecision_process` = :decision_process_id',
                'DELETE FROM `decision_proposal` WHERE `IDdecision_process` = :decision_process_id',
                'DELETE FROM `decision_group` WHERE `IDdecision_process` = :decision_process_id',
            ];

            foreach ($deleteQueries as $query) {
                $deleteResult = self::execute($query, [
                    'decision_process_id' => $decisionId,
                ]);
                if ($deleteResult === false) {
                    throw new \RuntimeException('decision_related_delete_failed');
                }
            }

            if (!parent::delete()) {
                throw new \RuntimeException('decision_delete_failed');
            }

            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }

            return [
                'status' => true,
                'action' => 'delete',
                'message' => 'Prise de decision supprimee.',
            ];
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                'status' => false,
                'action' => 'delete',
                'message' => 'Impossible de supprimer cette prise de decision pour le moment.',
            ];
        }
    }

    public function closeForManager($referenceDateTime = null)
    {
        $action = $this->resolveManagerCloseAction();
        if ($action === 'archive') {
            return $this->archiveForManager($referenceDateTime);
        }

        return $this->deleteWithRelations();
    }

    public function hasExplicitInvitations()
    {
        return (int)self::fetchValue(
            'SELECT COUNT(*)
             FROM `decision_invitation`
             WHERE `IDdecision_process` = :decision_process_id
               AND `active` = 1
               AND `status` != :revoked_status',
            [
                'decision_process_id' => (int)$this->getId(),
                'revoked_status' => \dbObject\DecisionInvitation::STATUS_REVOKED,
            ]
        ) > 0;
    }

    protected function getRootParametersArray()
    {
        $parameters = $this->get('parameters');
        if (is_array($parameters)) {
            return $parameters;
        }

        $parameters = trim((string)$parameters);
        if ($parameters === '') {
            return [];
        }

        $decoded = json_decode($parameters, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function persistRootParametersArray(array $parameters)
    {
        $this->set('parameters', $parameters);
        $saveResult = $this->save();
        return is_array($saveResult) && !empty($saveResult['status']);
    }

    public function getPublicAccessSettings()
    {
        $parameters = $this->getRootParametersArray();
        $settings = $parameters['decision_public_access'] ?? [];
        if (!is_array($settings)) {
            $settings = [];
        }

        return [
            'allow_self_registration' => !empty($settings['allow_self_registration']),
        ];
    }

    public function isPublicSelfRegistrationEnabled()
    {
        $settings = $this->getPublicAccessSettings();
        return !empty($settings['allow_self_registration']);
    }

    public function savePublicAccessSettings(array $settings)
    {
        $parameters = $this->getRootParametersArray();
        $parameters['decision_public_access'] = [
            'allow_self_registration' => !empty($settings['allow_self_registration']) ? 1 : 0,
        ];

        return $this->persistRootParametersArray($parameters);
    }

    public function setPublicSelfRegistrationEnabled($enabled)
    {
        return $this->savePublicAccessSettings([
            'allow_self_registration' => !empty($enabled),
        ]);
    }

    protected function findActiveOrganizationMemberByEmail($email)
    {
        $organizationId = (int)$this->get('IDorganization');
        $email = trim(mb_strtolower((string)$email, 'UTF-8'));
        if ($organizationId <= 0 || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $row = self::fetchRow(
            "SELECT
                uo.`IDuser` AS `user_id`,
                COALESCE(NULLIF(uo.`email`, ''), u.`email`) AS `scoped_email`,
                TRIM(CONCAT(COALESCE(u.`firstname`, ''), ' ', COALESCE(u.`lastname`, ''))) AS `display_name`,
                u.`username` AS `username`
             FROM `user_organization` uo
             INNER JOIN `user` u ON u.`id` = uo.`IDuser`
             WHERE uo.`IDorganization` = :organization_id
               AND uo.`active` = 1
               AND LOWER(COALESCE(NULLIF(uo.`email`, ''), u.`email`)) = :email
             ORDER BY uo.`id` DESC
             LIMIT 1",
            [
                'organization_id' => $organizationId,
                'email' => $email,
            ]
        );

        if (!is_array($row) || (int)($row['user_id'] ?? 0) <= 0) {
            return null;
        }

        $displayName = trim((string)($row['display_name'] ?? ''));
        if ($displayName === '') {
            $displayName = trim((string)($row['username'] ?? ''));
        }
        if ($displayName === '') {
            $displayName = trim((string)($row['scoped_email'] ?? ''));
        }

        return [
            'user_id' => (int)$row['user_id'],
            'email' => trim((string)($row['scoped_email'] ?? '')),
            'display_name' => $displayName,
        ];
    }

    public function getOrganizationObject()
    {
        $organizationId = (int)$this->get('IDorganization');
        if ($organizationId <= 0) {
            return null;
        }

        $organization = new \dbObject\Organization();
        return $organization->load($organizationId) ? $organization : null;
    }

    public function getHolonObject()
    {
        $holonId = (int)$this->get('IDholon');
        if ($holonId <= 0) {
            return null;
        }

        $holon = new \dbObject\Holon();
        return $holon->load($holonId) ? $holon : null;
    }

    public function getAccessUrl($intent = 'view')
    {
        $organization = $this->getOrganizationObject();
        $organizationId = (int)$this->get('IDorganization');
        $path = '/omo/api/decision/edit.php?' . http_build_query([
            'oid' => $organizationId,
            'cid' => (int)$this->get('IDholon'),
            'id' => (int)$this->getId(),
            'method' => self::normalizeEvaluationMethod($this->get('evaluation_method')),
            'intent' => trim((string)$intent) !== '' ? trim((string)$intent) : 'view',
        ]);

        $targetHost = \commonGetRequestHost();
        if ($organization) {
            $shortname = trim((string)$organization->get('shortname'));
            if (\commonUseOrganizationSubdomains() && $shortname !== '') {
                $targetHost = \commonBuildOrganizationHost($shortname, \commonGetRootHost($targetHost));
            } else {
                $targetHost = \commonGetRootHost($targetHost);
            }
        }

        return \commonBuildUrl($path, $targetHost);
    }

    public static function buildGenericPublicAccessPath($organizationId, $decisionId, $holonId = 0, $intent = 'view')
    {
        $path = '/decision/public/' . (int)$organizationId . '/' . (int)$decisionId;
        $holonId = (int)$holonId;
        if ($holonId > 0) {
            $path .= '/c/' . $holonId;
        }

        $intent = trim((string)$intent);
        if ($intent === 'participate') {
            $path .= '/participate';
        }

        return $path;
    }

    public function getGenericPublicAccessUrl($intent = 'view')
    {
        $organization = $this->getOrganizationObject();
        $organizationId = (int)$this->get('IDorganization');
        $holonId = (int)$this->get('IDholon');
        $path = self::buildGenericPublicAccessPath($organizationId, (int)$this->getId(), $holonId, $intent);

        $targetHost = \commonGetRequestHost();
        if ($organization) {
            $shortname = trim((string)$organization->get('shortname'));
            if (\commonUseOrganizationSubdomains() && $shortname !== '') {
                $targetHost = \commonBuildOrganizationHost($shortname, \commonGetRootHost($targetHost));
            } else {
                $targetHost = \commonGetRootHost($targetHost);
            }
        }

        return \commonBuildUrl($path, $targetHost);
    }

    public function getInvitationEmailState()
    {
        $parameters = $this->getRootParametersArray();
        $state = $parameters['decision_invitation_mail'] ?? [];
        return is_array($state) ? $state : [];
    }

    public function saveInvitationEmailState(array $state)
    {
        $parameters = $this->getRootParametersArray();
        $parameters['decision_invitation_mail'] = $state;
        return $this->persistRootParametersArray($parameters);
    }

    public function buildDefaultInvitationEmailMessage()
    {
        $organization = $this->getOrganizationObject();
        $holon = $this->getHolonObject();
        $title = trim((string)$this->get('title'));
        $organizationName = $organization ? trim((string)$organization->get('name')) : 'cette organisation';
        $messageLines = [
            'Bonjour,',
            '',
            'Vous etes invite a participer a la prise de decision "' . ($title !== '' ? $title : 'sans titre') . '" dans ' . $organizationName . '.',
        ];

        if ($holon) {
            $messageLines[] = 'Contexte: ' . trim((string)$holon->getTemplateLabel(true)) . ' ' . trim((string)$holon->getDisplayName()) . '.';
        }

        $consultationStart = self::normalizeDateTimeValue($this->get('consultation_start_at'));
        $consultationEnd = self::normalizeDateTimeValue($this->get('consultation_end_at'));

        if ($consultationStart instanceof \DateTimeInterface) {
            $messageLines[] = 'Debut: ' . $consultationStart->format('d.m.Y H:i') . '.';
        }
        if ($consultationEnd instanceof \DateTimeInterface) {
            $messageLines[] = 'Fin: ' . $consultationEnd->format('d.m.Y H:i') . '.';
        }

        $messageLines[] = '';
        $messageLines[] = 'Vous pouvez consulter les details du scrutin en ouvrant le lien ci-dessous.';
        $messageLines[] = '';
        $messageLines[] = 'A bientot,';
        $messageLines[] = $organizationName;

        return implode("\n", $messageLines);
    }

    public function buildDefaultInvitationEmailSubject()
    {
        $title = trim((string)$this->get('title'));
        $subject = 'Acces a la prise de decision';
        if ($title !== '') {
            $subject .= ' : ' . $title;
        }

        return $subject;
    }

    public function getSubmittedResponseParticipantIds()
    {
        if ((int)$this->getId() <= 0) {
            return [];
        }

        $rows = self::fetchAll(
            'SELECT DISTINCT `IDdecision_participant`
             FROM `decision_response`
             WHERE `IDdecision_process` = :decision_process_id
               AND (
                    `status` = :submitted_status
                    OR `submitted_at` IS NOT NULL
               )',
            [
                'decision_process_id' => (int)$this->getId(),
                'submitted_status' => \dbObject\DecisionResponse::STATUS_SUBMITTED,
            ]
        );

        if (!is_array($rows)) {
            return [];
        }

        $participantIds = [];
        foreach ($rows as $row) {
            $participantId = (int)($row['IDdecision_participant'] ?? 0);
            if ($participantId > 0) {
                $participantIds[$participantId] = $participantId;
            }
        }

        return array_values($participantIds);
    }

    public function getInvitationEmailRecipients($includeOwner = false, $onlyPendingResponses = false)
    {
        $organizationId = (int)$this->get('IDorganization');
        $ownerUserId = (int)$this->get('IDuser');
        $includeOwner = (bool)$includeOwner;
        $onlyPendingResponses = (bool)$onlyPendingResponses;
        $recipients = [];
        $submittedParticipantIds = $onlyPendingResponses
            ? array_fill_keys($this->getSubmittedResponseParticipantIds(), true)
            : [];

        foreach ($this->getParticipants(true) as $participant) {
            if (!($participant instanceof \dbObject\DecisionParticipant)) {
                continue;
            }

            $participantStatus = \dbObject\DecisionParticipant::normalizeStatus($participant->get('status'));
            if (in_array($participantStatus, [
                \dbObject\DecisionParticipant::STATUS_DECLINED,
                \dbObject\DecisionParticipant::STATUS_REVOKED,
            ], true)) {
                continue;
            }

            $participantId = (int)$participant->getId();
            if ($onlyPendingResponses && isset($submittedParticipantIds[$participantId])) {
                continue;
            }

            $userId = (int)$participant->get('IDuser');
            if (!$includeOwner && $userId > 0 && $userId === $ownerUserId) {
                continue;
            }

            $email = '';
            $displayName = trim((string)$participant->get('display_name'));

            if ($userId > 0) {
                $user = null;
                $membership = new \dbObject\UserOrganization();
                if ($membership->load([
                    ['IDorganization', $organizationId],
                    ['IDuser', $userId],
                ]) && (bool)$membership->get('active')) {
                    $email = trim(mb_strtolower((string)$membership->getScopedEmail(), 'UTF-8'));
                    if ($displayName === '') {
                        $displayName = trim((string)$membership->getUserDisplayName());
                    }
                }

                if ($email === '' || $displayName === '') {
                    $user = new \dbObject\User();
                    if ($user->load($userId)) {
                        if ($email === '') {
                            $email = trim(mb_strtolower((string)$user->getScopedEmail($organizationId), 'UTF-8'));
                        }
                        if ($displayName === '') {
                            $firstname = trim((string)$user->get('firstname'));
                            $lastname = trim((string)$user->get('lastname'));
                            $displayName = trim($firstname . ' ' . $lastname);
                            if ($displayName === '') {
                                $displayName = trim((string)$user->get('username'));
                            }
                        }
                    }
                }
            } else {
                $email = trim(mb_strtolower((string)$participant->get('email'), 'UTF-8'));
            }

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if ($displayName === '') {
                $displayName = $email;
            }

            if (!isset($recipients[$email])) {
                $recipients[$email] = [
                    'email' => $email,
                    'display_name' => $displayName,
                    'participant_ids' => [],
                ];
            }

            $recipients[$email]['participant_ids'][] = $participantId;
        }

        return array_values($recipients);
    }

    public function getParticipantInvitationRecipientData($participant)
    {
        if (!($participant instanceof \dbObject\DecisionParticipant) || (int)$participant->getId() <= 0) {
            return null;
        }

        foreach ($this->getInvitationEmailRecipients(true) as $recipient) {
            $participantIds = array_map('intval', (array)($recipient['participant_ids'] ?? []));
            if (!in_array((int)$participant->getId(), $participantIds, true)) {
                continue;
            }

            return [
                'email' => trim((string)($recipient['email'] ?? '')),
                'display_name' => trim((string)($recipient['display_name'] ?? '')),
                'participant_ids' => $participantIds,
            ];
        }

        $email = trim(mb_strtolower((string)$participant->get('email'), 'UTF-8'));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return [
            'email' => $email,
            'display_name' => trim((string)$participant->get('display_name')),
            'participant_ids' => [(int)$participant->getId()],
        ];
    }

    public function findAccessibleParticipantByEmail($email)
    {
        $email = trim(mb_strtolower((string)$email, 'UTF-8'));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $syncResult = $this->syncParticipantsFromInvitations();
        if (!is_array($syncResult) || empty($syncResult['status'])) {
            return null;
        }

        foreach ($this->getInvitationEmailRecipients(true) as $recipient) {
            if (trim(mb_strtolower((string)($recipient['email'] ?? ''), 'UTF-8')) !== $email) {
                continue;
            }

            foreach ((array)($recipient['participant_ids'] ?? []) as $participantId) {
                $participant = new \dbObject\DecisionParticipant();
                if (!$participant->load((int)$participantId) || (int)$participant->get('active') !== 1) {
                    continue;
                }

                $participantStatus = \dbObject\DecisionParticipant::normalizeStatus($participant->get('status'));
                if (in_array($participantStatus, [
                    \dbObject\DecisionParticipant::STATUS_DECLINED,
                    \dbObject\DecisionParticipant::STATUS_REVOKED,
                ], true)) {
                    continue;
                }

                return $participant;
            }
        }

        return null;
    }

    public function resolvePublicRequestParticipantByEmail($email, $allowCreateIfMissing = true)
    {
        $email = trim(mb_strtolower((string)$email, 'UTF-8'));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => false,
                'reason' => 'invalid_email',
            ];
        }

        $syncResult = $this->syncParticipantsFromInvitations();
        if (!is_array($syncResult) || empty($syncResult['status'])) {
            return [
                'status' => false,
                'reason' => 'sync_failed',
            ];
        }

        $participantCandidates = [];
        $participantCandidateIds = [];
        $hasUnavailableExistingParticipant = false;
        $registerParticipantCandidate = static function ($participant) use (&$participantCandidates, &$participantCandidateIds) {
            if (!($participant instanceof \dbObject\DecisionParticipant)) {
                return;
            }

            $participantId = (int)$participant->getId();
            if ($participantId <= 0 || isset($participantCandidateIds[$participantId])) {
                return;
            }

            if ((int)$participant->get('active') !== 1) {
                return;
            }

            $participantStatus = \dbObject\DecisionParticipant::normalizeStatus($participant->get('status'));
            if (in_array($participantStatus, [
                \dbObject\DecisionParticipant::STATUS_DECLINED,
                \dbObject\DecisionParticipant::STATUS_REVOKED,
            ], true)) {
                return;
            }

            $participantCandidates[] = $participant;
            $participantCandidateIds[$participantId] = true;
        };

        foreach ($this->getInvitationEmailRecipients(true) as $recipient) {
            if (trim(mb_strtolower((string)($recipient['email'] ?? ''), 'UTF-8')) !== $email) {
                continue;
            }

            foreach ((array)($recipient['participant_ids'] ?? []) as $participantId) {
                $participant = new \dbObject\DecisionParticipant();
                if (!$participant->load((int)$participantId) || (int)$participant->get('active') !== 1) {
                    continue;
                }

                $registerParticipantCandidate($participant);
            }
        }

        $organizationMember = $this->findActiveOrganizationMemberByEmail($email);
        if (is_array($organizationMember)) {
            $userParticipant = \dbObject\DecisionParticipant::findByDecisionAndUser((int)$this->getId(), (int)$organizationMember['user_id']);
            if ($userParticipant instanceof \dbObject\DecisionParticipant) {
                $participantStatus = \dbObject\DecisionParticipant::normalizeStatus($userParticipant->get('status'));
                if ((int)$userParticipant->get('active') !== 1 || in_array($participantStatus, [
                    \dbObject\DecisionParticipant::STATUS_DECLINED,
                    \dbObject\DecisionParticipant::STATUS_REVOKED,
                ], true)) {
                    $hasUnavailableExistingParticipant = true;
                } else {
                    $registerParticipantCandidate($userParticipant);
                }
            }
        }

        $emailParticipant = \dbObject\DecisionParticipant::findByDecisionAndEmail((int)$this->getId(), $email);
        if ($emailParticipant instanceof \dbObject\DecisionParticipant) {
            $participantStatus = \dbObject\DecisionParticipant::normalizeStatus($emailParticipant->get('status'));
            if ((int)$emailParticipant->get('active') !== 1 || in_array($participantStatus, [
                \dbObject\DecisionParticipant::STATUS_DECLINED,
                \dbObject\DecisionParticipant::STATUS_REVOKED,
            ], true)) {
                $hasUnavailableExistingParticipant = true;
            } else {
                $registerParticipantCandidate($emailParticipant);
            }
        }

        if (count($participantCandidates) > 0) {
            if (!$allowCreateIfMissing) {
                foreach ($participantCandidates as $participantCandidate) {
                    if ($participantCandidate->hasPublicAccessCode(true)) {
                        return [
                            'status' => true,
                            'created' => false,
                            'participant' => $participantCandidate,
                        ];
                    }
                }

                foreach ($participantCandidates as $participantCandidate) {
                    if ($participantCandidate->hasPublicAccessCode(false)) {
                        return [
                            'status' => true,
                            'created' => false,
                            'participant' => $participantCandidate,
                        ];
                    }
                }
            }

            return [
                'status' => true,
                'created' => false,
                'participant' => $participantCandidates[0],
            ];
        }

        if (!$allowCreateIfMissing || !$this->isPublicSelfRegistrationEnabled()) {
            return [
                'status' => false,
                'reason' => $hasUnavailableExistingParticipant ? 'participant_unavailable' : 'not_allowed',
            ];
        }

        $participant = new \dbObject\DecisionParticipant();
        $participant->set('IDdecision_process', (int)$this->getId());
        if (is_array($organizationMember)) {
            $participant->set('IDuser', (int)$organizationMember['user_id']);
            $participant->set('email', null);
            $participant->set('display_name', trim((string)$organizationMember['display_name']) !== '' ? trim((string)$organizationMember['display_name']) : $email);
        } else {
            $participant->set('IDuser', null);
            $participant->set('email', $email);
            $participant->set('display_name', $email);
        }
        $participant->set('role', \dbObject\DecisionParticipant::ROLE_PARTICIPANT);
        $participant->set('status', \dbObject\DecisionParticipant::STATUS_INVITED);
        $participant->set('active', 1);
        $participant->set('parameters', [
            'sync_source' => 'public_opt_in',
            'created_from' => 'public_request',
            'created_at' => (new \DateTimeImmutable('now'))->format('c'),
            'identity_mode' => is_array($organizationMember) ? 'user' : 'email',
        ]);

        $saveResult = $participant->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            return [
                'status' => false,
                'reason' => 'create_failed',
            ];
        }

        return [
            'status' => true,
            'created' => true,
            'participant' => $participant,
        ];
    }

    public function buildPublicAccessRequestEmailMessage()
    {
        $organization = $this->getOrganizationObject();
        $organizationName = $organization ? trim((string)$organization->get('name')) : 'cette organisation';
        $title = trim((string)$this->get('title'));

        $messageLines = [
            'Bonjour,',
            '',
            'Vous avez demande un acces a la prise de decision "' . ($title !== '' ? $title : 'sans titre') . '" dans ' . $organizationName . '.',
            'Utilisez le lien ci-dessous pour ouvrir directement la page de participation.',
            '',
            'A bientot,',
            $organizationName,
        ];

        return implode("\n", $messageLines);
    }

    public function syncParticipantsFromInvitations()
    {
        $decisionId = (int)$this->getId();
        $organizationId = (int)$this->get('IDorganization');
        if ($decisionId <= 0) {
            return [
                'status' => false,
                'message' => 'Decision process must be saved before syncing participants.',
            ];
        }

        $desiredParticipants = [];
        $ownerUserId = (int)$this->get('IDuser');
        if ($ownerUserId > 0) {
            $desiredParticipants['user:' . $ownerUserId] = [
                'IDuser' => $ownerUserId,
                'email' => '',
                'display_name' => '',
                'role' => \dbObject\DecisionParticipant::ROLE_OWNER,
                'status' => \dbObject\DecisionParticipant::STATUS_ACTIVE,
                'active' => 1,
                'parameters' => [
                    'sync_source' => 'owner',
                ],
            ];
        }

        $activeInvitations = [];
        foreach ($this->getInvitations(true) as $invitation) {
            if (!($invitation instanceof \dbObject\DecisionInvitation)) {
                continue;
            }

            $invitationStatus = \dbObject\DecisionInvitation::normalizeStatus($invitation->get('status'));
            if ($invitationStatus === \dbObject\DecisionInvitation::STATUS_REVOKED) {
                continue;
            }

            $activeInvitations[] = $invitation;
        }

        $holonIds = [];
        if (count($activeInvitations) === 0) {
            $defaultHolonId = (int)$this->get('IDholon');
            if ($defaultHolonId > 0) {
                $holonIds[] = $defaultHolonId;
            }
        } else {
            foreach ($activeInvitations as $invitation) {
                $type = \dbObject\DecisionInvitation::normalizeType($invitation->get('invitation_type'));
                if ($type === \dbObject\DecisionInvitation::TYPE_HOLON) {
                    $holonId = (int)$invitation->get('IDholon');
                    if ($holonId > 0) {
                        $holonIds[] = $holonId;
                    }
                    continue;
                }

                if ($type === \dbObject\DecisionInvitation::TYPE_USER) {
                    $userId = (int)$invitation->get('IDuser');
                    if ($userId > 0) {
                        $desiredParticipants['user:' . $userId] = [
                            'IDuser' => $userId,
                            'email' => '',
                            'display_name' => trim((string)$invitation->get('display_name')),
                            'role' => \dbObject\DecisionParticipant::ROLE_PARTICIPANT,
                            'status' => \dbObject\DecisionParticipant::STATUS_ACTIVE,
                            'active' => 1,
                            'parameters' => [
                                'sync_source' => 'invited_user',
                                'invitation_id' => (int)$invitation->getId(),
                            ],
                        ];
                    }
                    continue;
                }

                $email = trim((string)$invitation->get('email'));
                if ($email !== '') {
                    $desiredParticipants['email:' . $email] = [
                        'IDuser' => 0,
                        'email' => $email,
                        'display_name' => trim((string)$invitation->get('display_name')),
                        'role' => \dbObject\DecisionParticipant::ROLE_PARTICIPANT,
                        'status' => \dbObject\DecisionParticipant::STATUS_INVITED,
                        'active' => 1,
                        'parameters' => [
                            'sync_source' => 'invited_email',
                            'invitation_id' => (int)$invitation->getId(),
                        ],
                    ];
                }
            }
        }

        $holonIds = array_values(array_unique(array_filter(array_map('intval', $holonIds), function ($holonId) {
            return $holonId > 0;
        })));

        $organizationRootHolonId = 0;
        if ($organizationId > 0) {
            $organization = $this->getOrganizationObject();
            $rootHolon = $organization ? $organization->getEnabledStructuralRootHolon() : null;
            $organizationRootHolonId = $rootHolon instanceof \dbObject\Holon ? (int)$rootHolon->getId() : 0;
        }

        $appendOrganizationMembers = function ($syncSource) use (&$desiredParticipants, $ownerUserId, $organizationId) {
            if ($organizationId <= 0) {
                return;
            }

            $organizationMembers = new \dbObject\ArrayUserOrganization();
            $organizationMembers->loadActiveForOrganization($organizationId);
            foreach ($organizationMembers as $membership) {
                $userId = (int)$membership->get('IDuser');
                if ($userId <= 0) {
                    continue;
                }

                $key = 'user:' . $userId;
                if (isset($desiredParticipants[$key]) && $desiredParticipants[$key]['role'] === \dbObject\DecisionParticipant::ROLE_OWNER) {
                    continue;
                }

                $desiredParticipants[$key] = [
                    'IDuser' => $userId,
                    'email' => '',
                    'display_name' => trim((string)$membership->getUserDisplayName()),
                    'role' => \dbObject\DecisionParticipant::ROLE_PARTICIPANT,
                    'status' => \dbObject\DecisionParticipant::STATUS_ACTIVE,
                    'active' => 1,
                    'parameters' => [
                        'sync_source' => (string)$syncSource,
                    ],
                ];
            }
        };

        if (count($holonIds) > 0) {
            $holonMembers = new \dbObject\ArrayUserHolon();
            $holonMembers->loadActiveForHolonIds($holonIds);
            foreach ($holonMembers as $membership) {
                $userId = (int)$membership->get('IDuser');
                if ($userId <= 0) {
                    continue;
                }

                $key = 'user:' . $userId;
                if (isset($desiredParticipants[$key]) && $desiredParticipants[$key]['role'] === \dbObject\DecisionParticipant::ROLE_OWNER) {
                    continue;
                }

                $desiredParticipants[$key] = [
                    'IDuser' => $userId,
                    'email' => '',
                    'display_name' => trim((string)$membership->getUserDisplayName((int)$this->get('IDorganization'))),
                    'role' => \dbObject\DecisionParticipant::ROLE_PARTICIPANT,
                    'status' => \dbObject\DecisionParticipant::STATUS_ACTIVE,
                    'active' => 1,
                    'parameters' => [
                        'sync_source' => count($activeInvitations) === 0 ? 'implicit_holon' : 'invited_holon',
                        'holon_ids' => $holonIds,
                    ],
                ];
            }
            if ($organizationRootHolonId > 0 && in_array($organizationRootHolonId, $holonIds, true)) {
                $appendOrganizationMembers(count($activeInvitations) === 0 ? 'implicit_organization' : 'invited_organization');
            }
        } elseif (count($activeInvitations) === 0 && $organizationId > 0) {
            $appendOrganizationMembers('implicit_organization');
        }

        $existingByKey = [];
        foreach ($this->getParticipants(false) as $participant) {
            if (!($participant instanceof \dbObject\DecisionParticipant)) {
                continue;
            }

            $key = '';
            $userId = (int)$participant->get('IDuser');
            $email = trim((string)$participant->get('email'));
            if ($userId > 0) {
                $key = 'user:' . $userId;
            } elseif ($email !== '') {
                $key = 'email:' . mb_strtolower($email, 'UTF-8');
            }

            if ($key !== '') {
                $existingByKey[$key] = $participant;
            }
        }

        foreach ($desiredParticipants as $key => $participantData) {
            $participant = $existingByKey[$key] ?? new \dbObject\DecisionParticipant();
            $existingParameters = $participant->get('parameters');
            if (!is_array($existingParameters)) {
                $existingParameters = json_decode(trim((string)$existingParameters), true);
            }
            $existingParameters = is_array($existingParameters) ? $existingParameters : [];
            $mergedParameters = is_array($participantData['parameters']) ? $participantData['parameters'] : [];
            if (isset($existingParameters['public_access_code']) && !isset($mergedParameters['public_access_code'])) {
                $mergedParameters['public_access_code'] = $existingParameters['public_access_code'];
            }

            $participant->set('IDdecision_process', $decisionId);
            $participant->set('IDuser', (int)$participantData['IDuser'] > 0 ? (int)$participantData['IDuser'] : null);
            $participant->set('email', trim((string)$participantData['email']) !== '' ? trim((string)$participantData['email']) : null);
            $participant->set('display_name', trim((string)$participantData['display_name']) !== '' ? trim((string)$participantData['display_name']) : null);
            $participant->set('role', (string)$participantData['role']);
            $participant->set('status', (string)$participantData['status']);
            $participant->set('active', (int)$participantData['active']);
            $participant->set('parameters', $mergedParameters);

            $saveResult = $participant->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                return [
                    'status' => false,
                    'message' => 'Unable to sync decision participants.',
                ];
            }
        }

        foreach ($existingByKey as $key => $participant) {
            if (isset($desiredParticipants[$key])) {
                continue;
            }

            if ((int)$participant->get('IDuser') === $ownerUserId) {
                continue;
            }

            $participantParameters = $participant->get('parameters');
            if (!is_array($participantParameters)) {
                $participantParameters = json_decode(trim((string)$participantParameters), true);
            }
            $participantParameters = is_array($participantParameters) ? $participantParameters : [];
            if (trim((string)($participantParameters['sync_source'] ?? '')) === 'public_opt_in') {
                continue;
            }

            $participant->set('active', 0);
            $participant->set('status', \dbObject\DecisionParticipant::STATUS_REVOKED);
            $saveResult = $participant->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                return [
                    'status' => false,
                    'message' => 'Unable to deactivate obsolete decision participants.',
                ];
            }
        }

        return [
            'status' => true,
            'count' => count($desiredParticipants),
        ];
    }

    public function hasConsultationStarted($referenceDateTime = null)
    {
        $status = self::normalizeStatus($this->get('status'));
        if (in_array($status, [
            self::STATUS_CONSULTATION,
            self::STATUS_EVALUATION,
            self::STATUS_RESULTS,
            self::STATUS_ARCHIVED,
        ], true)) {
            return true;
        }

        $consultationStart = self::normalizeDateTimeValue($this->get('consultation_start_at'));
        if (!$consultationStart instanceof \DateTimeInterface) {
            return false;
        }

        $referenceDateTime = self::normalizeDateTimeValue($referenceDateTime);
        if (!$referenceDateTime instanceof \DateTimeInterface) {
            $referenceDateTime = new \DateTimeImmutable('now');
        }

        return $consultationStart <= $referenceDateTime;
    }

    public function isParticipationOpen($referenceDateTime = null)
    {
        $status = self::normalizeStatus($this->get('status'));
        if (in_array($status, [self::STATUS_RESULTS, self::STATUS_ARCHIVED], true)) {
            return false;
        }

        return $this->hasEvaluationStarted($referenceDateTime);
    }

    public function hasEvaluationStarted($referenceDateTime = null)
    {
        $status = self::normalizeStatus($this->get('status'));
        if (in_array($status, [
            self::STATUS_EVALUATION,
            self::STATUS_RESULTS,
            self::STATUS_ARCHIVED,
        ], true)) {
            return true;
        }

        $evaluationStart = self::normalizeDateTimeValue($this->get('evaluation_start_at'));
        if (!$evaluationStart instanceof \DateTimeInterface) {
            return false;
        }

        $referenceDateTime = self::normalizeDateTimeValue($referenceDateTime);
        if (!$referenceDateTime instanceof \DateTimeInterface) {
            $referenceDateTime = new \DateTimeImmutable('now');
        }

        return $evaluationStart <= $referenceDateTime;
    }

    public static function fetchListRowsForOrganization($organizationId, $userId = 0, $userEmail = '')
    {
        $organizationId = (int)$organizationId;
        $userId = (int)$userId;
        $userEmail = trim(mb_strtolower((string)$userEmail, 'UTF-8'));

        if ($organizationId <= 0) {
            return [];
        }

        self::syncLifecycleStatusesForOrganization($organizationId);

        $rows = self::fetchAll(
            'SELECT
                dp.*,
                h.`name` AS `holon_name`,
                EXISTS(
                    SELECT 1
                    FROM `decision_participant` participant_user
                    WHERE participant_user.`IDdecision_process` = dp.`id`
                      AND participant_user.`active` = 1
                      AND participant_user.`IDuser` = :user_id
                ) AS `has_user_participation`,
                EXISTS(
                    SELECT 1
                    FROM `decision_participant` participant_email
                    WHERE participant_email.`IDdecision_process` = dp.`id`
                      AND participant_email.`active` = 1
                      AND participant_email.`email` = :user_email
                ) AS `has_email_participation`,
                (
                    SELECT COUNT(*)
                    FROM `decision_proposal` proposal
                    WHERE proposal.`IDdecision_process` = dp.`id`
                      AND proposal.`active` = 1
                ) AS `proposal_count`,
                (
                    SELECT COUNT(*)
                    FROM `decision_participant` participant_count
                    WHERE participant_count.`IDdecision_process` = dp.`id`
                      AND participant_count.`active` = 1
                ) AS `participant_count`,
                (
                    SELECT COUNT(*)
                    FROM `decision_response` response_count
                    WHERE response_count.`IDdecision_process` = dp.`id`
                      AND (
                            response_count.`status` = :submitted_status
                            OR response_count.`submitted_at` IS NOT NULL
                      )
                ) AS `response_count`,
                (
                    SELECT MAX(proposal_activity.`updated_at`)
                    FROM `decision_proposal` proposal_activity
                    WHERE proposal_activity.`IDdecision_process` = dp.`id`
                ) AS `proposals_updated_at`,
                (
                    SELECT MAX(participant_activity.`updated_at`)
                    FROM `decision_participant` participant_activity
                    WHERE participant_activity.`IDdecision_process` = dp.`id`
                ) AS `participants_updated_at`,
                (
                    SELECT MAX(response_activity.`updated_at`)
                    FROM `decision_response` response_activity
                    WHERE response_activity.`IDdecision_process` = dp.`id`
                ) AS `responses_updated_at`,
                (
                    SELECT MAX(response_submission.`submitted_at`)
                    FROM `decision_response` response_submission
                    WHERE response_submission.`IDdecision_process` = dp.`id`
                ) AS `responses_submitted_at`
            FROM `decision_process` dp
            LEFT JOIN `holon` h ON h.`id` = dp.`IDholon`
            WHERE dp.`IDorganization` = :organization_id
            ORDER BY dp.`updated_at` DESC, dp.`id` DESC',
            [
                'organization_id' => $organizationId,
                'user_id' => $userId,
                'user_email' => $userEmail,
                'submitted_status' => \dbObject\DecisionResponse::STATUS_SUBMITTED,
            ]
        );

        return is_array($rows) ? $rows : [];
    }

    public static function fetchRelevantRowsForUser($userId, $userEmail = '')
    {
        $userId = (int)$userId;
        $userEmail = trim(mb_strtolower((string)$userEmail, 'UTF-8'));

        if ($userId <= 0 && $userEmail === '') {
            return [];
        }

        $rows = self::fetchAll(
            'SELECT
                dp.*,
                o.`name` AS `organization_name`,
                o.`color` AS `organization_color`,
                h.`name` AS `holon_name`,
                COALESCE(
                    (
                        SELECT participant_user_match.`id`
                        FROM `decision_participant` participant_user_match
                        WHERE participant_user_match.`IDdecision_process` = dp.`id`
                          AND participant_user_match.`active` = 1
                          AND participant_user_match.`IDuser` = :user_id_participant_match
                          AND participant_user_match.`status` NOT IN (:declined_status_participant_match, :revoked_status_participant_match)
                        ORDER BY participant_user_match.`id` DESC
                        LIMIT 1
                    ),
                    (
                        SELECT participant_email_match.`id`
                        FROM `decision_participant` participant_email_match
                        WHERE participant_email_match.`IDdecision_process` = dp.`id`
                          AND participant_email_match.`active` = 1
                          AND participant_email_match.`email` = :user_email_participant_match
                          AND participant_email_match.`status` NOT IN (:declined_status_email_match, :revoked_status_email_match)
                        ORDER BY participant_email_match.`id` DESC
                        LIMIT 1
                    )
                ) AS `participant_id`,
                EXISTS(
                    SELECT 1
                    FROM `decision_participant` participant_user
                    WHERE participant_user.`IDdecision_process` = dp.`id`
                      AND participant_user.`active` = 1
                      AND participant_user.`IDuser` = :user_id_has_user
                      AND participant_user.`status` NOT IN (:declined_status_has_user, :revoked_status_has_user)
                ) AS `has_user_participation`,
                EXISTS(
                    SELECT 1
                    FROM `decision_participant` participant_email
                    WHERE participant_email.`IDdecision_process` = dp.`id`
                      AND participant_email.`active` = 1
                      AND participant_email.`email` = :user_email_has_email
                      AND participant_email.`status` NOT IN (:declined_status_has_email, :revoked_status_has_email)
                ) AS `has_email_participation`,
                (
                    SELECT COUNT(*)
                    FROM `decision_proposal` proposal
                    WHERE proposal.`IDdecision_process` = dp.`id`
                      AND proposal.`active` = 1
                ) AS `proposal_count`,
                (
                    SELECT COUNT(*)
                    FROM `decision_participant` participant_count
                    WHERE participant_count.`IDdecision_process` = dp.`id`
                      AND participant_count.`active` = 1
                      AND participant_count.`status` NOT IN (:declined_status_count, :revoked_status_count)
                ) AS `participant_count`,
                (
                    SELECT COUNT(*)
                    FROM `decision_response` response_count
                    WHERE response_count.`IDdecision_process` = dp.`id`
                      AND (
                            response_count.`status` = :submitted_status_count
                            OR response_count.`submitted_at` IS NOT NULL
                      )
                ) AS `response_count`,
                (
                    SELECT MAX(proposal_activity.`updated_at`)
                    FROM `decision_proposal` proposal_activity
                    WHERE proposal_activity.`IDdecision_process` = dp.`id`
                ) AS `proposals_updated_at`,
                (
                    SELECT MAX(participant_activity.`updated_at`)
                    FROM `decision_participant` participant_activity
                    WHERE participant_activity.`IDdecision_process` = dp.`id`
                ) AS `participants_updated_at`,
                (
                    SELECT MAX(response_activity.`updated_at`)
                    FROM `decision_response` response_activity
                    WHERE response_activity.`IDdecision_process` = dp.`id`
                ) AS `responses_updated_at`,
                (
                    SELECT MAX(response_submission.`submitted_at`)
                    FROM `decision_response` response_submission
                    WHERE response_submission.`IDdecision_process` = dp.`id`
                ) AS `responses_submitted_at`
            FROM `decision_process` dp
            LEFT JOIN `organization` o ON o.`id` = dp.`IDorganization`
            LEFT JOIN `holon` h ON h.`id` = dp.`IDholon`
            WHERE dp.`IDuser` = :user_id_owner
               OR EXISTS(
                    SELECT 1
                    FROM `decision_participant` participant_user
                    WHERE participant_user.`IDdecision_process` = dp.`id`
                      AND participant_user.`active` = 1
                      AND participant_user.`IDuser` = :user_id_access
                      AND participant_user.`status` NOT IN (:declined_status_access, :revoked_status_access)
                )
               OR EXISTS(
                    SELECT 1
                    FROM `decision_participant` participant_email
                    WHERE participant_email.`IDdecision_process` = dp.`id`
                      AND participant_email.`active` = 1
                      AND participant_email.`email` = :user_email_access
                      AND participant_email.`status` NOT IN (:declined_status_email_access, :revoked_status_email_access)
                )
            ORDER BY dp.`updated_at` DESC, dp.`id` DESC',
            [
                'user_id_participant_match' => $userId,
                'declined_status_participant_match' => \dbObject\DecisionParticipant::STATUS_DECLINED,
                'revoked_status_participant_match' => \dbObject\DecisionParticipant::STATUS_REVOKED,
                'user_email_participant_match' => $userEmail,
                'declined_status_email_match' => \dbObject\DecisionParticipant::STATUS_DECLINED,
                'revoked_status_email_match' => \dbObject\DecisionParticipant::STATUS_REVOKED,
                'user_id_has_user' => $userId,
                'declined_status_has_user' => \dbObject\DecisionParticipant::STATUS_DECLINED,
                'revoked_status_has_user' => \dbObject\DecisionParticipant::STATUS_REVOKED,
                'user_email_has_email' => $userEmail,
                'declined_status_has_email' => \dbObject\DecisionParticipant::STATUS_DECLINED,
                'revoked_status_has_email' => \dbObject\DecisionParticipant::STATUS_REVOKED,
                'declined_status_count' => \dbObject\DecisionParticipant::STATUS_DECLINED,
                'revoked_status_count' => \dbObject\DecisionParticipant::STATUS_REVOKED,
                'submitted_status_count' => \dbObject\DecisionResponse::STATUS_SUBMITTED,
                'user_id_owner' => $userId,
                'user_id_access' => $userId,
                'declined_status_access' => \dbObject\DecisionParticipant::STATUS_DECLINED,
                'revoked_status_access' => \dbObject\DecisionParticipant::STATUS_REVOKED,
                'user_email_access' => $userEmail,
                'declined_status_email_access' => \dbObject\DecisionParticipant::STATUS_DECLINED,
                'revoked_status_email_access' => \dbObject\DecisionParticipant::STATUS_REVOKED,
            ]
        );

        if (!is_array($rows) || count($rows) === 0) {
            return [];
        }

        foreach ($rows as $index => $row) {
            if (!is_array($row) || !isset($row['id'])) {
                continue;
            }

            $decision = new self();
            $decision->loadFromArray($row);
            $decision->setId((int)$row['id']);
            $decision->syncLifecycleStatus();

            $rows[$index]['status'] = (string)$decision->get('status');
            $rows[$index]['results_published_at'] = $decision->get('results_published_at');
            $rows[$index]['archived_at'] = $decision->get('archived_at');
        }

        return $rows;
    }
}

?>
