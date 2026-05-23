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

        return parent::save();
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
        $catalog = self::getEvaluationMethodCatalog();
        $method = self::normalizeEvaluationMethod($this->get('evaluation_method'));
        return isset($catalog[$method]) ? $catalog[$method] : $catalog[self::METHOD_SIMPLE_VOTE];
    }

    public function getProposals($activeOnly = false)
    {
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
               AND `status` = :submitted_status',
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

    public function syncParticipantsFromInvitations()
    {
        $decisionId = (int)$this->getId();
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
            $participant->set('IDdecision_process', $decisionId);
            $participant->set('IDuser', (int)$participantData['IDuser'] > 0 ? (int)$participantData['IDuser'] : null);
            $participant->set('email', trim((string)$participantData['email']) !== '' ? trim((string)$participantData['email']) : null);
            $participant->set('display_name', trim((string)$participantData['display_name']) !== '' ? trim((string)$participantData['display_name']) : null);
            $participant->set('role', (string)$participantData['role']);
            $participant->set('status', (string)$participantData['status']);
            $participant->set('active', (int)$participantData['active']);
            $participant->set('parameters', $participantData['parameters']);

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

        return $this->hasConsultationStarted($referenceDateTime);
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
                      AND response_count.`status` = :submitted_status
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
}

?>
