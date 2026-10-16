<?php
namespace dbObject;

class ChatMessageReaction extends DbObject
{
    const KINDS = ['up', 'down', 'clap'];

    public static function tableName() { return 'chat_message_reaction'; }
    public static function rules()
    {
        return [
            [['IDchat_message', 'actor_key', 'reaction'], 'required'],
            [['id'], 'integer'],
            [['IDchat_message'], 'fk'],
            [['actor_key', 'reaction'], 'string'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'IDchat_message' => 'Message', 'actor_key' => 'Participant',
            'reaction' => 'Reaction', 'created_at' => 'Creation', 'updated_at' => 'Mise a jour'];
    }
    public static function attributeLength() { return ['actor_key' => 80, 'reaction' => 16]; }

    /** Only pass identities resolved by the discussion's access checks, never request IDs. */
    public static function actorKey(int $userId, int $participantId = 0, int $shareLinkId = 0): string
    {
        if ($userId > 0) return 'user:' . $userId;
        if ($participantId > 0) {
            $participant = new DecisionParticipant();
            if (!$participant->load($participantId)) return '';
            $linkedUserId = (int)$participant->get('IDuser');
            return $linkedUserId > 0 ? 'user:' . $linkedUserId : 'participant:' . $participantId;
        }
        if ($shareLinkId > 0) {
            $link = new DocumentShareLink();
            if (!$link->load($shareLinkId)) return '';
            if ($link->getRecipientUserId() > 0) return 'user:' . $link->getRecipientUserId();
            // Reissuing an invitation must not grant another vote on the same message.
            $email = $link->getRecipientEmail();
            return $email !== '' ? 'email:' . hash('sha256', $email) : '';
        }
        return '';
    }

    public static function clientState(int $messageId, $cached, string $own = '', bool $canReact = true): array
    {
        $cached = is_array($cached) ? $cached : json_decode((string)$cached, true);
        $counts = [];
        foreach (self::KINDS as $kind) $counts[$kind] = max(0, (int)($cached[$kind] ?? 0));
        return ['id' => $messageId, 'counts' => $counts, 'own' => $own, 'canReact' => $canReact,
            'version' => max(0, (int)($cached['_version'] ?? 0))];
    }

    /** Idempotent desired state: retrying a request cannot add or toggle a second vote. */
    public static function setForMessage(ChatThread $thread, int $messageId, string $actorKey, string $reaction): array
    {
        if ($actorKey === '' || strlen($actorKey) > 80 || ($reaction !== '' && !in_array($reaction, self::KINDS, true))) {
            throw new \InvalidArgumentException('Invalid reaction or identity.');
        }
        $pdo = self::getPdo();
        // Own the transaction so a failed counter update can never leave a saved vote behind.
        if ($pdo->inTransaction()) throw new \LogicException('Reaction mutation needs its own transaction.');
        $pdo->beginTransaction();
        try {
            $message = self::fetchRow(
                'SELECT id, reactions, IDuser, IDdecision_participant, IDdocument_share_link
                 FROM chat_message WHERE id = :id AND IDchat_thread = :thread
                 AND IDorganization = :organization AND message_type = :type FOR UPDATE',
                ['id' => $messageId, 'thread' => (int)$thread->getId(),
                    'organization' => (int)$thread->get('IDorganization'), 'type' => ChatMessage::TYPE_USER]
            );
            if (!$message) throw new \InvalidArgumentException('Message outside the discussion.');
            $authorKey = self::actorKey((int)$message['IDuser'], (int)$message['IDdecision_participant'], (int)$message['IDdocument_share_link']);
            if ($actorKey === $authorKey) throw new \DomainException('self_reaction');
            $existing = self::fetchRow(
                'SELECT id, reaction FROM chat_message_reaction WHERE IDchat_message = :message AND actor_key = :actor FOR UPDATE',
                ['message' => $messageId, 'actor' => $actorKey]
            );
            $previous = (string)($existing['reaction'] ?? '');
            $state = self::clientState($messageId, $message['reactions'], $reaction);
            if ($previous !== $reaction) {
                $vote = new self();
                if ($existing && !$vote->load((int)$existing['id'], true)) throw new \RuntimeException('Cannot load reaction.');
                if ($reaction === '') {
                    $saved = $vote->delete();
                } else {
                    $vote->set('IDchat_message', $messageId);
                    $vote->set('actor_key', $actorKey);
                    $vote->set('reaction', $reaction);
                    $saved = !empty($vote->save()['status']);
                }
                if (!$saved) throw new \RuntimeException('Cannot save reaction.');
                if ($previous !== '') $state['counts'][$previous] = max(0, $state['counts'][$previous] - 1);
                if ($reaction !== '') $state['counts'][$reaction]++;
                $state['version']++;
                if (!ChatMessage::saveReactionCounts($messageId, $state['counts'] + ['_version' => $state['version']])) {
                    throw new \RuntimeException('Cannot save reaction totals.');
                }
            }
            $pdo->commit();
            return $state;
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    /** One indexed read for all displayed messages; no per-message count queries. */
    public static function forMessages(ChatThread $thread, array $messageIds, string $actorKey): array
    {
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', $messageIds),
            static function ($id) { return $id > 0; }))), -500);
        if (!$ids) return [];
        $rows = self::fetchAll(
            'SELECT m.id, m.reactions, r.reaction,
                CASE
                    WHEN m.IDuser > 0 THEN CONCAT("user:", m.IDuser)
                    WHEN m.IDdecision_participant > 0 THEN
                        CASE WHEN p.IDuser > 0 THEN CONCAT("user:", p.IDuser)
                             ELSE CONCAT("participant:", m.IDdecision_participant) END
                    WHEN l.recipient_user_id > 0 THEN CONCAT("user:", l.recipient_user_id)
                    WHEN TRIM(COALESCE(l.recipient_email, "")) <> "" THEN
                        CONCAT("email:", SHA2(LOWER(TRIM(l.recipient_email)), 256))
                    ELSE ""
                END AS author_actor
             FROM chat_message m
             LEFT JOIN decision_participant p ON p.id = m.IDdecision_participant
             LEFT JOIN document_share_link l ON l.id = m.IDdocument_share_link
             LEFT JOIN chat_message_reaction r ON r.IDchat_message = m.id AND r.actor_key = :actor
             WHERE m.IDchat_thread = :thread AND m.IDorganization = :organization
               AND m.message_type = :type AND m.id IN (' . implode(',', $ids) . ')',
            ['actor' => $actorKey, 'thread' => (int)$thread->getId(),
                'organization' => (int)$thread->get('IDorganization'), 'type' => ChatMessage::TYPE_USER]
        );
        return array_map(static function ($row) use ($actorKey) {
            return self::clientState((int)$row['id'], $row['reactions'], (string)($row['reaction'] ?? ''),
                $actorKey !== '' && $actorKey !== (string)$row['author_actor']);
        }, is_array($rows) ? $rows : []);
    }
}
