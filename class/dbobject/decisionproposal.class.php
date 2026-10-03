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
            [['IDdecision_group'], 'required'],
            [['id', 'position'], 'integer'],
            [['IDdecision_process', 'IDdecision_group', 'IDuser_author'], 'fk'],
            [['title'], 'string'],
            [['description'], 'html'],
            [['info_url'], 'string'],
            [['parameters'], 'parameters'],
            [['active'], 'boolean'],
            [['start_at', 'end_at', 'created_at', 'updated_at'], 'datetime'],
            [['timezone'], 'string'],
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
            'start_at' => 'Debut',
            'end_at' => 'Fin',
            'timezone' => 'Fuseau horaire',
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
            'timezone' => 64,
        ];
    }

    public static function getOrder()
    {
        return 'position ASC, id ASC';
    }

    public static function handleUserDeparture($organizationId, $userId, $ghostUserId)
    {
        return self::execute(
            'UPDATE decision_proposal proposal
             INNER JOIN decision_process process ON process.id = proposal.IDdecision_process
             SET proposal.IDuser_author = :ghost_user_id
             WHERE process.IDorganization = :organization_id AND proposal.IDuser_author = :user_id',
            array('ghost_user_id' => (int)$ghostUserId, 'organization_id' => (int)$organizationId, 'user_id' => (int)$userId)
        );
    }

    public function save()
    {
        $range = self::normalizeCalendarRange($this->get('start_at'), $this->get('end_at'), $this->get('timezone'));
        if (empty($range['status'])) return $range;
        foreach ($range['values'] as $field => $value) $this->set($field, $value);
        $pdo = self::getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        try {
            if ($ownsTransaction) $pdo->beginTransaction();
            $result = $this->saveProposalContent();
            if (empty($result['status'])) throw new \RuntimeException((string)($result['text'] ?? 'proposal_save_failed'));
            $decision = $this->getDecisionProcess();
            if ($decision && empty($decision->syncParticipantsFromInvitations()['status'])) throw new \RuntimeException('calendar_sync_failed');
            if ($ownsTransaction) $pdo->commit();
            return $result;
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
            error_log('decision_proposal_save_failed: ' . $exception->getMessage());
            return ['status' => false, 'text' => 'Impossible de sauvegarder la proposition et son agenda.'];
        }
    }

    public static function normalizeCalendarRange($start, $end, $timezone = null): array
    {
        $emptyStart = !$start;
        $emptyEnd = !$end;
        if ($emptyStart && $emptyEnd) return ['status' => true, 'values' => ['start_at' => null, 'end_at' => null, 'timezone' => null]];
        try {
            if ($emptyStart || $emptyEnd) throw new \InvalidArgumentException();
            $zone = new \DateTimeZone(trim((string)$timezone) ?: date_default_timezone_get());
            $parse = static function ($value) use ($zone) {
                if ($value instanceof \DateTimeInterface) return \DateTimeImmutable::createFromInterface($value);
                $value = str_replace('T', ' ', trim((string)$value));
                if (strlen($value) === 16) $value .= ':00';
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, $zone);
                if (!$date || $date->format('Y-m-d H:i:s') !== $value) throw new \InvalidArgumentException();
                return $date;
            };
            $start = $parse($start);
            $end = $parse($end);
            if ($end <= $start) throw new \InvalidArgumentException();
            $storageZone = new \DateTimeZone(date_default_timezone_get());
            return ['status' => true, 'values' => ['start_at' => $start->setTimezone($storageZone), 'end_at' => $end->setTimezone($storageZone), 'timezone' => $zone->getName()]];
        } catch (\Throwable $exception) {
            return ['status' => false, 'reason' => 'invalid_dates', 'text' => 'Indiquez un debut et une fin valides, avec une fin apres le debut.', 'message' => 'Indiquez un debut et une fin valides, avec une fin apres le debut.'];
        }
    }

    public function getCalendarData(): array
    {
        $zone = new \DateTimeZone((string)$this->get('timezone') ?: date_default_timezone_get());
        $local = static fn($value) => $value instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($value)->setTimezone($zone) : null;
        $format = static fn($value) => $local($value)?->format('Y-m-d\TH:i') ?? '';
        $event = Event::findByDecisionProposal((int)$this->getId());
        return [
            'startAt' => $format($this->get('start_at')),
            'endAt' => $format($this->get('end_at')),
            'timezone' => (string)$this->get('timezone'),
            'calendarStatus' => $event ? $event->get('status') : '',
            'dateLabel' => $this->get('start_at') ? $local($this->get('start_at'))->format('d.m.Y H:i') . ' - ' . $local($this->get('end_at'))->format('d.m.Y H:i') . ' (' . $this->get('timezone') . ')' : '',
        ];
    }

    public function delete()
    {
        $pdo = self::getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        try {
            if ($ownsTransaction) $pdo->beginTransaction();
            $event = Event::findByDecisionProposal((int)$this->getId());
            if ($event) {
                $event->set('status', Event::STATUS_CANCELLED);
                if (empty($event->save()['status'])) throw new \RuntimeException('calendar_cancellation_failed');
            }
            if (!parent::delete()) throw new \RuntimeException('proposal_delete_failed');
            if ($ownsTransaction) $pdo->commit();
            return true;
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
            error_log('decision_proposal_delete_failed: ' . $exception->getMessage());
            return false;
        }
    }

    protected function saveProposalContent()
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

    public function getGovernanceActions()
    {
        return \dbObject\DecisionGovernanceAction::getForProposal((int)$this->getId());
    }

    public function getDeferredProposals(bool $pendingOnly = false)
    {
        return \dbObject\DeferredProposal::getForDecisionProposal((int)$this->getId(), $pendingOnly);
    }

    public function hasGovernanceActions()
    {
        foreach ($this->getGovernanceActions() as $action) {
            if ($action instanceof \dbObject\DecisionGovernanceAction) {
                return true;
            }
        }
        return false;
    }

    public function hasDeferredProposals(): bool
    {
        foreach ($this->getDeferredProposals() as $proposal) {
            if ($proposal instanceof \dbObject\DeferredProposal
                && (string)$proposal->get('status') !== \dbObject\DeferredProposal::STATUS_REMOVED) {
                return true;
            }
        }
        return false;
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

    public function canBeEditedByParticipant($participantId)
    {
        return (int)$participantId > 0 && $this->getAuthorParticipantId() === (int)$participantId;
    }

    public function canBeEditedByActor($userId, $participantId = 0)
    {
        return $this->canBeEditedByUser($userId)
            || $this->canBeEditedByParticipant($participantId);
    }

    public function archiveByAuthor($userId, $participantId = 0)
    {
        $userId = (int)$userId;
        $participantId = (int)$participantId;
        if (!$this->canBeEditedByActor($userId, $participantId)) {
            return [
                'status' => false,
                'reason' => 'forbidden',
                'message' => 'Vous ne pouvez supprimer que vos propres propositions.',
            ];
        }

        if ((int)$this->get('active') !== 1) {
            return [
                'status' => false,
                'reason' => 'not_found',
                'message' => 'Cette proposition n est plus active.',
            ];
        }

        $this->set('active', 0);
        $this->set('updated_at', new \DateTimeImmutable('now'));
        $saveResult = $this->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            return [
                'status' => false,
                'reason' => 'save_failed',
                'message' => 'La proposition ne peut pas être supprimée pour le moment.',
            ];
        }

        if ($this->hasGovernanceActions() || $this->hasDeferredProposals()) {
            \dbObject\DecisionGovernanceAction::setProposalActionStatus(
                $this,
                \dbObject\DecisionGovernanceAction::STATUS_REMOVED,
                'Proposition retiree pendant la consultation.',
                \dbObject\DecisionGovernanceAction::STATUS_PENDING
            );
            foreach ($this->getDeferredProposals(true) as $deferredProposal) {
                if (!$deferredProposal instanceof \dbObject\DeferredProposal) continue;
                $deferredProposal->set('status', \dbObject\DeferredProposal::STATUS_REMOVED);
                $deferredProposal->set('status_message', 'Proposition retirée pendant la consultation.');
                $deferredProposal->set('updated_at', new \DateTimeImmutable('now'));
                $deferredProposal->save();
            }
        }

        return [
            'status' => true,
            'message' => 'Proposition supprimée.',
        ];
    }

    public function updateContentByAuthor($userId, $title, $description, $infoUrl, $participantId = 0, ?array $calendarRange = null)
    {
        $userId = (int)$userId;
        $participantId = (int)$participantId;
        $title = trim((string)$title);
        $description = \dbObject\PropertyFormat::sanitizeHtml((string)$description);
        $infoUrl = trim((string)$infoUrl);
        if ($this->hasGovernanceActions() || $this->hasDeferredProposals()) {
            return [
                'status' => false,
                'reason' => 'governance_editor_required',
                'message' => 'Cette proposition doit etre modifiee depuis son editeur de gouvernance.',
            ];
        }
        if (!$this->canBeEditedByActor($userId, $participantId)) {
            return [
                'status' => false,
                'reason' => 'forbidden',
                'message' => 'Vous ne pouvez modifier que vos propres propositions.',
            ];
        }
        if (mb_strlen($title, 'UTF-8') > 190) {
            return [
                'status' => false,
                'reason' => 'invalid_title',
                'message' => 'Le titre ne peut pas depasser 190 caracteres.',
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
        if ($title === '' && $description === '' && $infoUrl === '' && empty($calendarRange['start_at'])) {
            return [
                'status' => false,
                'reason' => 'invalid_content',
                'message' => 'La proposition doit contenir un titre, une description ou un lien.',
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
        if ($calendarRange !== null) {
            $range = self::normalizeCalendarRange($calendarRange['start_at'] ?? null, $calendarRange['end_at'] ?? null, $calendarRange['timezone'] ?? null);
            if (empty($range['status'])) return $range;
            foreach ($range['values'] as $field => $value) {
                $old = $this->get($field);
                $oldValues[$field] = $old instanceof \DateTimeInterface ? $old->format('Y-m-d H:i:s') : $old;
                $newValues[$field] = $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value;
            }
        }
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
        $participant = null;
        if ($participantId > 0) {
            $participant = new \dbObject\DecisionParticipant();
            if (
                !$participant->load($participantId)
                || (int)$participant->get('IDdecision_process') !== (int)$this->get('IDdecision_process')
            ) {
                $participant = null;
            }
        }
        if (
            $organizationId <= 0
            || ($userId > 0 && !$user->load($userId))
            || ($userId <= 0 && !($participant instanceof \dbObject\DecisionParticipant))
        ) {
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
            if ($calendarRange !== null) {
                foreach ($range['values'] as $field => $value) $this->set($field, $value);
            }
            if ($userId > 0) {
                $this->set('IDuser_author', $userId);
            }
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

                $displayName = $userId > 0
                    ? trim((string)$user->getScopedDisplayName($organizationId))
                    : trim((string)$participant->getIdentityLabel($organizationId));
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
                    ],
                    $participantId
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
            error_log('decision_proposal_update_by_author_failed: ' . $exception->getMessage());
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
