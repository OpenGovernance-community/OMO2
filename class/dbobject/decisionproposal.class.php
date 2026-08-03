<?php
namespace dbObject;

class DecisionProposal extends DbObject
{
    protected $decisionMethodParametersCache = null;

    public static function tableName()
    {
        return 'decision_proposal';
    }

    public static function rules()
    {
        return [
            [['IDdecision_group', 'title'], 'required'],
            [['id', 'position'], 'integer'],
            [['IDdecision_process', 'IDdecision_group', 'IDuser_author'], 'fk'],
            [['title'], 'string'],
            [['description'], 'html'],
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
            'IDdecision_process' => 'Prise de décision',
            'IDdecision_group' => 'Groupe de décision',
            'IDuser_author' => 'Auteur',
            'title' => 'Titre',
            'description' => 'Description',
            'info_url' => 'Lien d’information',
            'position' => 'Ordre',
            'parameters' => 'Paramètres',
            'active' => 'Activée',
            'created_at' => 'Création',
            'updated_at' => 'Mise à jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'parameters' => 'Métadonnées spécifiques à une proposition ou à une méthode.',
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

    public function save()
    {
        $this->set('description', \dbObject\PropertyFormat::sanitizeHtml((string)$this->get('description')));
        $decisionGroupId = (int)$this->get('IDdecision_group');
        $decisionProcessId = (int)$this->get('IDdecision_process');

        if ($decisionGroupId <= 0 && $decisionProcessId > 0) {
            $decision = new \dbObject\DecisionProcess();
            if ($decision->load($decisionProcessId)) {
                $group = $decision->ensurePrimaryGroup();
                if ($group) {
                    $decisionGroupId = (int)$group->getId();
                    $this->set('IDdecision_group', $decisionGroupId);
                    $this->set('IDdecision_process', (int)$group->get('IDdecision_process'));
                }
            }
        }

        if ($decisionGroupId > 0) {
            $group = new \dbObject\DecisionGroup();
            if (!$group->load($decisionGroupId)) {
                return [
                    'status' => false,
                    'text' => 'The linked decision group could not be found.',
                ];
            }

            $groupProcessId = (int)$group->get('IDdecision_process');
            if ($decisionProcessId > 0 && $groupProcessId !== $decisionProcessId) {
                return [
                    'status' => false,
                    'text' => 'The linked decision process does not match the decision group.',
                ];
            }

            $this->set('IDdecision_process', $groupProcessId);
        }

        return parent::save();
    }

    public function getDecisionGroup()
    {
        $group = new \dbObject\DecisionGroup();
        return $group->load((int)$this->get('IDdecision_group')) ? $group : null;
    }

    public function getDecisionProcess()
    {
        $group = $this->getDecisionGroup();
        if ($group instanceof \dbObject\DecisionGroup) {
            return $group->getDecisionProcess();
        }

        $decision = new \dbObject\DecisionProcess();
        return $decision->load((int)$this->get('IDdecision_process')) ? $decision : null;
    }

    protected function getDecisionMethodParameters()
    {
        if (is_array($this->decisionMethodParametersCache)) {
            return $this->decisionMethodParametersCache;
        }

        $group = $this->getDecisionGroup();
        $container = $group instanceof \dbObject\DecisionGroup ? $group : $this->getDecisionProcess();
        if (!$container || !method_exists($container, 'get')) {
            $this->decisionMethodParametersCache = [];
            return $this->decisionMethodParametersCache;
        }

        $parameters = $container->get('parameters');
        if (!is_array($parameters)) {
            $parameters = json_decode(trim((string)$parameters), true);
        }
        $parameters = is_array($parameters) ? $parameters : [];
        $method = \dbObject\DecisionProcess::normalizeEvaluationMethod($container->get('evaluation_method'));
        $methodParameters = $parameters[$method] ?? null;
        $this->decisionMethodParametersCache = is_array($methodParameters) ? $methodParameters : $parameters;
        return $this->decisionMethodParametersCache;
    }

    public function isAnonymous()
    {
        $parameters = $this->getDecisionMethodParameters();
        return !empty($parameters['is_anonymous']);
    }

    public function areDiscussionsEnabled()
    {
        $parameters = $this->getDecisionMethodParameters();
        return !array_key_exists('allow_proposal_discussions', $parameters)
            || !empty($parameters['allow_proposal_discussions']);
    }

    public function getAuthorUserId()
    {
        $authorUserId = (int)$this->get('IDuser_author');
        if ($authorUserId > 0) {
            return $authorUserId;
        }

        $participantId = $this->getAuthorParticipantId();
        if ($participantId <= 0) {
            return 0;
        }

        $participant = new \dbObject\DecisionParticipant();
        if (!$participant->load($participantId)
            || (int)$participant->get('IDdecision_process') !== (int)$this->get('IDdecision_process')) {
            return 0;
        }

        return (int)$participant->get('IDuser');
    }

    public function getAuthorParticipantId()
    {
        $parameters = $this->get('parameters');
        if (!is_array($parameters)) {
            $parameters = json_decode((string)$parameters, true);
        }

        return is_array($parameters) ? (int)($parameters['added_by_participant_id'] ?? 0) : 0;
    }

    public function getAuthorParticipant()
    {
        $participantId = $this->getAuthorParticipantId();
        if ($participantId <= 0) {
            return null;
        }

        $participant = new \dbObject\DecisionParticipant();
        if (!$participant->load($participantId)
            || (int)$participant->get('IDdecision_process') !== (int)$this->get('IDdecision_process')) {
            return null;
        }

        return $participant;
    }

    public function getAuthorUser()
    {
        $authorUserId = $this->getAuthorUserId();
        $user = new \dbObject\User();
        return $authorUserId > 0 && $user->load($authorUserId) ? $user : null;
    }

    public function canBeEditedByUser($userId)
    {
        return (int)$userId > 0 && $this->getAuthorUserId() === (int)$userId;
    }

    public function updateContentByAuthor($userId, $title, $description, $infoUrl)
    {
        $userId = (int)$userId;
        $title = trim((string)$title);
        $description = \dbObject\PropertyFormat::sanitizeHtml((string)$description);
        $infoUrl = trim((string)$infoUrl);
        if (!$this->canBeEditedByUser($userId)) {
            return [
                'status' => false,
                'reason' => 'forbidden',
                'message' => 'Vous ne pouvez modifier que vos propres propositions.',
            ];
        }
        if ($title === '' || mb_strlen($title, 'UTF-8') > 190) {
            return [
                'status' => false,
                'reason' => 'invalid_title',
                'message' => 'Le titre doit contenir entre 1 et 190 caractères.',
            ];
        }
        if (mb_strlen($description, 'UTF-8') > 10000) {
            return [
                'status' => false,
                'reason' => 'invalid_description',
                'message' => 'La description est trop longue.',
            ];
        }
        if ($infoUrl !== '' && (mb_strlen($infoUrl, 'UTF-8') > 500 || !filter_var($infoUrl, FILTER_VALIDATE_URL))) {
            return [
                'status' => false,
                'reason' => 'invalid_url',
                'message' => 'Le lien d’information n’est pas valide.',
            ];
        }

        $oldValues = [
            'title' => trim((string)$this->get('title')),
            'description' => trim((string)$this->get('description')),
            'info_url' => trim((string)$this->get('info_url')),
        ];
        $newValues = [
            'title' => $title,
            'description' => $description,
            'info_url' => $infoUrl,
        ];
        if ($oldValues === $newValues) {
            return [
                'status' => true,
                'changed' => false,
                'message' => 'Aucune modification à enregistrer.',
            ];
        }

        $decision = $this->getDecisionProcess();
        $organizationId = $decision instanceof \dbObject\DecisionProcess
            ? (int)$decision->get('IDorganization')
            : 0;
        $user = new \dbObject\User();
        if ($organizationId <= 0 || !$user->load($userId)) {
            return [
                'status' => false,
                'reason' => 'invalid_context',
                'message' => 'Le contexte de la proposition est invalide.',
            ];
        }

        $pdo = self::getPdo();
        if (!$pdo) {
            return [
                'status' => false,
                'reason' => 'database_unavailable',
                'message' => 'La proposition ne peut pas être enregistrée pour le moment.',
            ];
        }

        try {
            $pdo->beginTransaction();
            $this->set('title', $title);
            $this->set('description', $description !== '' ? $description : null);
            $this->set('info_url', $infoUrl !== '' ? $infoUrl : null);
            $this->set('IDuser_author', $userId);
            $this->set('updated_at', new \DateTimeImmutable('now'));
            $saveResult = $this->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                throw new \RuntimeException('proposal_save_failed');
            }

            if ($this->areDiscussionsEnabled()) {
                $thread = $this->getChatThread(true, $userId);
                if (!$thread instanceof \dbObject\ChatThread) {
                    throw new \RuntimeException('chat_thread_save_failed');
                }
                if (trim((string)$thread->get('title')) !== $title) {
                    $thread->set('title', $title);
                    $thread->set('updated_at', new \DateTimeImmutable('now'));
                    $threadSaveResult = $thread->save();
                    if (!is_array($threadSaveResult) || empty($threadSaveResult['status'])) {
                        throw new \RuntimeException('chat_thread_update_failed');
                    }
                }

                $displayName = trim((string)$user->getScopedDisplayName($organizationId));
                $systemContent = !$this->isAnonymous() && $displayName !== ''
                    ? $displayName . ' a modifié la proposition.'
                    : 'La proposition a été modifiée.';
                $message = \dbObject\ChatMessage::createSystemMessage(
                    $thread,
                    $systemContent,
                    $userId,
                    [
                        'action' => 'decision_proposal_updated',
                        'proposal_id' => (int)$this->getId(),
                        'old' => $oldValues,
                        'new' => $newValues,
                    ]
                );
                if (!$message instanceof \dbObject\ChatMessage) {
                    throw new \RuntimeException('chat_message_save_failed');
                }
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [
                'status' => false,
                'reason' => 'save_failed',
                'message' => 'La proposition ne peut pas être enregistrée pour le moment.',
            ];
        }

        return [
            'status' => true,
            'changed' => true,
            'message' => 'Proposition modifiée.',
        ];
    }

    public function getChatThread($create = false, $creatorUserId = 0)
    {
        $decision = $this->getDecisionProcess();
        $organizationId = $decision instanceof \dbObject\DecisionProcess
            ? (int)$decision->get('IDorganization')
            : 0;
        if ($organizationId <= 0 || (int)$this->getId() <= 0) {
            return null;
        }

        if ($create) {
            return \dbObject\ChatThread::getOrCreateForSubject(
                $organizationId,
                \dbObject\ChatThread::SUBJECT_DECISION_PROPOSAL,
                (int)$this->getId(),
                (int)$creatorUserId,
                trim((string)$this->get('title'))
            );
        }

        return \dbObject\ChatThread::findBySubject(
            $organizationId,
            \dbObject\ChatThread::SUBJECT_DECISION_PROPOSAL,
            (int)$this->getId()
        );
    }
}

?>
