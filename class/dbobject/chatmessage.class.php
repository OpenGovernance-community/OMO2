<?php
namespace dbObject;

class ChatMessage extends DbObject
{
    const TYPE_USER = 'user';
    const TYPE_SYSTEM = 'system';

    public static function tableName()
    {
        return 'chat_message';
    }

    public static function rules()
    {
        return [
            [['IDchat_thread', 'IDorganization', 'message_type', 'content'], 'required'],
            [['id'], 'integer'],
            [['IDchat_thread', 'IDorganization', 'IDuser', 'IDdecision_participant'], 'fk'],
            [['message_type', 'author_name'], 'string'],
            [['content'], 'text'],
            [['parameters'], 'parameters'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDchat_thread' => 'Discussion',
            'IDorganization' => 'Organisation',
            'IDuser' => 'Auteur',
            'IDdecision_participant' => 'Participant au scrutin',
            'message_type' => 'Type',
            'content' => 'Message',
            'author_name' => 'Nom de l auteur',
            'parameters' => 'Paramètres',
            'created_at' => 'Création',
            'updated_at' => 'Mise à jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'author_name' => 'Copie du nom affiché au moment de l envoi.',
            'parameters' => 'Métadonnées du message système ou de son objet source.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'message_type' => 20,
            'author_name' => 190,
        ];
    }

    public static function getOrder()
    {
        return 'id ASC';
    }

    public static function normalizeMessageType($messageType)
    {
        return (string)$messageType === self::TYPE_SYSTEM ? self::TYPE_SYSTEM : self::TYPE_USER;
    }

    public static function findByThreadId($threadId, $limit = 200, $afterMessageId = 0)
    {
        $threadId = (int)$threadId;
        $limit = max(1, min(500, (int)$limit));
        $afterMessageId = max(0, (int)$afterMessageId);
        if ($threadId <= 0) {
            return [];
        }

        if ($afterMessageId > 0) {
            $rows = self::fetchAll(
                'SELECT * FROM `chat_message`
                 WHERE `IDchat_thread` = :thread_id
                   AND `id` > :after_message_id
                 ORDER BY `id` ASC
                 LIMIT ' . $limit,
                [
                    'thread_id' => $threadId,
                    'after_message_id' => $afterMessageId,
                ]
            );
        } else {
            $rows = self::fetchAll(
                'SELECT * FROM `chat_message`
                 WHERE `IDchat_thread` = :thread_id
                 ORDER BY `id` DESC
                 LIMIT ' . $limit,
                ['thread_id' => $threadId]
            );
            if (is_array($rows)) {
                $rows = array_reverse($rows);
            }
        }
        if (!is_array($rows)) {
            return [];
        }

        $messages = [];
        foreach ($rows as $row) {
            if (!isset($row['id'])) {
                continue;
            }
            $message = new self();
            $message->loadFromArray($row);
            $message->setId((int)$row['id']);
            $messages[] = $message;
        }
        return $messages;
    }

    public static function getParticipantUserIdsForThread($threadId)
    {
        $rows = self::fetchAll(
            'SELECT DISTINCT `IDuser` FROM `chat_message`
             WHERE `IDchat_thread` = :thread_id
               AND `message_type` = :message_type
               AND `IDuser` IS NOT NULL
               AND `IDuser` > 0',
            [
                'thread_id' => (int)$threadId,
                'message_type' => self::TYPE_USER,
            ]
        );
        return array_values(array_unique(array_filter(array_map(static function ($row) {
            return is_array($row) ? (int)($row['IDuser'] ?? 0) : 0;
        }, is_array($rows) ? $rows : []), static function ($userId) {
            return $userId > 0;
        })));
    }

    public static function createUserMessage(ChatThread $thread, $userId, $content, $isAnonymous = false, $anonymousByAuthor = false, $participantId = 0)
    {
        $message = new self();
        $message->set('IDchat_thread', (int)$thread->getId());
        $message->set('IDorganization', (int)$thread->get('IDorganization'));
        $message->set('IDuser', (int)$userId > 0 ? (int)$userId : null);
        $message->set('IDdecision_participant', (int)$participantId > 0 ? (int)$participantId : null);
        $message->set('message_type', self::TYPE_USER);
        $message->set('content', trim((string)$content));
        $message->set('parameters', [
            'is_anonymous' => !empty($isAnonymous) ? 1 : 0,
            'anonymous_by_author' => !empty($anonymousByAuthor) ? 1 : 0,
        ]);
        return $message->saveAndReload() ? $message : null;
    }

    public static function createSystemMessage(ChatThread $thread, $content, $userId = 0, array $parameters = [], $participantId = 0)
    {
        $message = new self();
        $message->set('IDchat_thread', (int)$thread->getId());
        $message->set('IDorganization', (int)$thread->get('IDorganization'));
        $message->set('IDuser', (int)$userId > 0 ? (int)$userId : null);
        $message->set('IDdecision_participant', (int)$participantId > 0 ? (int)$participantId : null);
        $message->set('message_type', self::TYPE_SYSTEM);
        $message->set('content', trim((string)$content));
        $message->set('parameters', $parameters);
        return $message->saveAndReload() ? $message : null;
    }

    protected function saveAndReload()
    {
        $saveResult = $this->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            return false;
        }

        return $this->load((int)$this->getId(), true);
    }

    public function save()
    {
        $threadId = (int)$this->get('IDchat_thread');
        $organizationId = (int)$this->get('IDorganization');
        $messageType = self::normalizeMessageType($this->get('message_type'));
        $content = trim((string)$this->get('content'));
        $userId = (int)$this->get('IDuser');
        $participantId = (int)$this->get('IDdecision_participant');

        $thread = new ChatThread();
        if ($threadId <= 0 || !$thread->load($threadId) || (int)$thread->get('IDorganization') !== $organizationId) {
            return [
                'status' => false,
                'text' => 'The chat message organization does not match its thread.',
            ];
        }
        if ($content === '' || mb_strlen($content, 'UTF-8') > 4000) {
            return [
                'status' => false,
                'text' => 'A chat message must contain between 1 and 4000 characters.',
            ];
        }
        if ($messageType === self::TYPE_USER && $userId <= 0 && $participantId <= 0) {
            return [
                'status' => false,
                'text' => 'A user chat message needs an account or a decision participant.',
            ];
        }

        $this->set('message_type', $messageType);
        $this->set('content', $content);
        if ($userId > 0) {
            $user = new User();
            if (!$user->load($userId)) {
                return [
                    'status' => false,
                    'text' => 'The chat message author could not be found.',
                ];
            }
            if (trim((string)$this->get('author_name')) === '') {
                $this->set('author_name', trim((string)$user->getScopedDisplayName($organizationId)));
            }
        } else {
            $this->set('IDuser', null);
        }
        if ($participantId > 0) {
            $participant = new DecisionParticipant();
            $participantDecision = null;
            if (
                !$participant->load($participantId)
                || !(($participantDecision = $participant->getDecisionProcess()) instanceof DecisionProcess)
                || (int)$participantDecision->get('IDorganization') !== $organizationId
            ) {
                return [
                    'status' => false,
                    'text' => 'The chat message participant does not match its organization.',
                ];
            }
            if (trim((string)$this->get('author_name')) === '') {
                $authorName = trim((string)$participant->get('display_name'));
                if ($authorName === '') {
                    $authorName = trim((string)$participant->get('email'));
                    $atPosition = strrpos($authorName, '@');
                    if ($atPosition !== false) {
                        $authorName = substr($authorName, 0, $atPosition);
                    }
                }
                $this->set('author_name', $authorName);
            }
        } else {
            $this->set('IDdecision_participant', null);
        }

        return parent::save();
    }

    protected function getParametersArray()
    {
        $parameters = $this->get('parameters');
        if (is_array($parameters)) {
            return $parameters;
        }

        $decoded = json_decode((string)$parameters, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function isAnonymous()
    {
        return !empty($this->getParametersArray()['is_anonymous']);
    }

    public function isAnonymousByAuthor()
    {
        return !empty($this->getParametersArray()['anonymous_by_author']);
    }

    public static function normalizeProposalUpdateValue($value)
    {
        $value = preg_replace('/&#(?:0*13|x0*d);/i', "\r", (string)$value);
        $value = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return str_replace(["\r\n", "\r"], "\n", $value);
    }

    public function getProposalUpdateChanges()
    {
        if (self::normalizeMessageType($this->get('message_type')) !== self::TYPE_SYSTEM) {
            return [];
        }

        $parameters = $this->getParametersArray();
        if ((string)($parameters['action'] ?? '') !== 'decision_proposal_updated') {
            return [];
        }

        $beforeValues = is_array($parameters['old'] ?? null) ? $parameters['old'] : [];
        $afterValues = is_array($parameters['new'] ?? null) ? $parameters['new'] : [];
        $fieldLabels = [
            'title' => 'Titre',
            'description' => 'Description',
            'info_url' => 'Lien d information',
        ];
        $changes = [];
        foreach ($fieldLabels as $field => $label) {
            $before = trim(self::normalizeProposalUpdateValue($beforeValues[$field] ?? ''));
            $after = trim(self::normalizeProposalUpdateValue($afterValues[$field] ?? ''));
            if ($before === $after) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'label' => $label,
                'before' => $before,
                'after' => $after,
                'status' => $before === '' ? 'added' : ($after === '' ? 'removed' : 'changed'),
            ];
        }

        return $changes;
    }

    public function toClientArray($viewerUserId, $viewerParticipantId = 0)
    {
        $organizationId = (int)$this->get('IDorganization');
        $authorUserId = (int)$this->get('IDuser');
        $authorParticipantId = (int)$this->get('IDdecision_participant');
        $authorName = trim((string)$this->get('author_name'));
        $photoUrl = '';
        $initials = '';

        if ($authorUserId > 0) {
            $user = new User();
            if ($user->load($authorUserId)) {
                $currentName = trim((string)$user->getScopedDisplayName($organizationId));
                if ($currentName !== '') {
                    $authorName = $currentName;
                }
                $photoUrl = trim((string)$user->getScopedProfilePhotoUrl($organizationId));
                $initials = trim((string)$user->getScopedInitials($organizationId));
            }
        }
        if ($initials === '' && $authorName !== '') {
            $initials = User::buildInitials($authorName);
        }

        $createdAtValue = $this->get('created_at');
        $createdAt = $createdAtValue instanceof \DateTimeInterface
            ? $createdAtValue->format('Y-m-d H:i:s')
            : trim((string)$createdAtValue);
        $createdAtLabel = $createdAt;
        if ($createdAt !== '') {
            try {
                $createdAtLabel = (new \DateTimeImmutable($createdAt))->format('d.m.Y H:i');
            } catch (\Throwable $exception) {
                $createdAtLabel = $createdAt;
            }
        }

        return [
            'id' => (int)$this->getId(),
            'type' => self::normalizeMessageType($this->get('message_type')),
            'content' => (string)$this->get('content'),
            'authorUserId' => $authorUserId,
            'authorParticipantId' => $authorParticipantId,
            'authorName' => $authorName,
            'photoUrl' => $photoUrl,
            'initials' => $initials,
            'isOwn' => ($authorUserId > 0 && $authorUserId === (int)$viewerUserId)
                || ($authorParticipantId > 0 && $authorParticipantId === (int)$viewerParticipantId),
            'createdAt' => $createdAt,
            'createdAtLabel' => $createdAtLabel,
            'changes' => $this->getProposalUpdateChanges(),
        ];
    }
}

?>
