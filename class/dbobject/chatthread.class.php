<?php
namespace dbObject;

class ChatThread extends DbObject
{
    const SUBJECT_DECISION_PROPOSAL = 'decision_proposal';
    const SUBJECT_DOCUMENT_PV = 'document_pv';
    const SUBJECT_DOCUMENT_PV_POINT = 'document_pv_point';

    public static function tableName()
    {
        return 'chat_thread';
    }

    public static function rules()
    {
        return [
            [['IDorganization', 'subject_type', 'subject_id'], 'required'],
            [['id', 'subject_id'], 'integer'],
            [['IDorganization', 'IDuser_created'], 'fk'],
            [['subject_type', 'title'], 'string'],
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
            'IDorganization' => 'Organisation',
            'IDuser_created' => 'Créateur',
            'subject_type' => 'Type d objet',
            'subject_id' => 'Objet',
            'title' => 'Titre',
            'parameters' => 'Paramètres',
            'active' => 'Actif',
            'created_at' => 'Création',
            'updated_at' => 'Mise à jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'subject_type' => 'Type générique de l objet auquel cette discussion est liée.',
            'subject_id' => 'Identifiant de l objet dans son type.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'subject_type' => 60,
            'title' => 190,
        ];
    }

    public static function getOrder()
    {
        return 'updated_at DESC, id DESC';
    }

    public static function normalizeSubjectType($subjectType)
    {
        $subjectType = strtolower(trim((string)$subjectType));
        return preg_match('/^[a-z][a-z0-9_]{0,59}$/', $subjectType) ? $subjectType : '';
    }

    public static function findBySubject($organizationId, $subjectType, $subjectId)
    {
        $organizationId = (int)$organizationId;
        $subjectType = self::normalizeSubjectType($subjectType);
        $subjectId = (int)$subjectId;
        if ($organizationId <= 0 || $subjectType === '' || $subjectId <= 0) {
            return null;
        }

        $row = self::fetchRow(
            'SELECT * FROM `chat_thread`
             WHERE `IDorganization` = :organization_id
               AND `subject_type` = :subject_type
               AND `subject_id` = :subject_id
             LIMIT 1',
            [
                'organization_id' => $organizationId,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
            ]
        );

        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }

        $thread = new self();
        $thread->loadFromArray($row);
        $thread->setId((int)$row['id']);
        return $thread;
    }

    public static function getOrCreateForSubject($organizationId, $subjectType, $subjectId, $creatorUserId = 0, $title = '')
    {
        $organizationId = (int)$organizationId;
        $subjectType = self::normalizeSubjectType($subjectType);
        $subjectId = (int)$subjectId;
        if ($organizationId <= 0 || $subjectType === '' || $subjectId <= 0) {
            return null;
        }

        $existing = self::findBySubject($organizationId, $subjectType, $subjectId);
        if ($existing instanceof self) {
            return $existing;
        }

        $thread = new self();
        $thread->set('IDorganization', $organizationId);
        $thread->set('IDuser_created', (int)$creatorUserId > 0 ? (int)$creatorUserId : null);
        $thread->set('subject_type', $subjectType);
        $thread->set('subject_id', $subjectId);
        $thread->set('title', trim((string)$title));
        $thread->set('active', 1);
        $saveResult = $thread->save();
        if (is_array($saveResult) && !empty($saveResult['status'])) {
            return $thread;
        }

        // A concurrent request may have created the unique thread first.
        return self::findBySubject($organizationId, $subjectType, $subjectId);
    }

    public static function getSubjectDiscussionSummaries($organizationId, $subjectType, array $subjectIds, $viewerUserId = 0, $viewerParticipantId = 0, $viewerDocumentShareLinkId = 0)
    {
        $organizationId = (int)$organizationId;
        $subjectType = self::normalizeSubjectType($subjectType);
        $viewerUserId = max(0, (int)$viewerUserId);
        $viewerParticipantId = max(0, (int)$viewerParticipantId);
        $viewerDocumentShareLinkId = max(0, (int)$viewerDocumentShareLinkId);
        $subjectIds = array_values(array_unique(array_filter(array_map('intval', $subjectIds), function ($subjectId) {
            return $subjectId > 0;
        })));
        if ($organizationId <= 0 || $subjectType === '' || count($subjectIds) === 0) {
            return [];
        }

        $parameters = [
            'organization_id' => $organizationId,
            'subject_type' => $subjectType,
            'viewer_user_id_case' => $viewerUserId,
            'viewer_user_id_subquery' => $viewerUserId,
            'viewer_participant_id_case' => $viewerParticipantId,
            'viewer_participant_id_subquery' => $viewerParticipantId,
            'viewer_document_share_link_id_case' => $viewerDocumentShareLinkId,
            'viewer_document_share_link_id_subquery' => $viewerDocumentShareLinkId,
        ];
        $subjectPlaceholders = [];
        foreach ($subjectIds as $index => $subjectId) {
            $placeholder = 'subject_id_' . $index;
            $subjectPlaceholders[] = ':' . $placeholder;
            $parameters[$placeholder] = $subjectId;
        }

        $rows = self::fetchAll(
            'SELECT thread.`subject_id`,
                    COUNT(message.`id`) AS `total_messages`,
                    MAX(message.`id`) AS `last_message_id`,
                    MAX(CASE
                        WHEN (
                            message.`IDuser` = :viewer_user_id_case
                            OR message.`IDdecision_participant` = :viewer_participant_id_case
                            OR message.`IDdocument_share_link` = :viewer_document_share_link_id_case
                        ) AND message.`message_type` = \'user\'
                        THEN message.`id`
                        ELSE 0
                    END) AS `last_viewer_message_id`,
                    SUM(CASE
                        WHEN message.`id` > COALESCE((
                            SELECT MAX(viewer_message.`id`)
                            FROM `chat_message` viewer_message
                            WHERE viewer_message.`IDchat_thread` = thread.`id`
                              AND (
                                  viewer_message.`IDuser` = :viewer_user_id_subquery
                                  OR viewer_message.`IDdecision_participant` = :viewer_participant_id_subquery
                                  OR viewer_message.`IDdocument_share_link` = :viewer_document_share_link_id_subquery
                              )
                              AND viewer_message.`message_type` = \'user\'
                        ), 0)
                        THEN 1
                        ELSE 0
                    END) AS `messages_since_viewer`
             FROM `chat_thread` thread
             LEFT JOIN `chat_message` message ON message.`IDchat_thread` = thread.`id`
             WHERE thread.`IDorganization` = :organization_id
               AND thread.`subject_type` = :subject_type
               AND thread.`subject_id` IN (' . implode(', ', $subjectPlaceholders) . ')
               AND thread.`active` = 1
             GROUP BY thread.`id`, thread.`subject_id`',
            $parameters
        );
        if (!is_array($rows) || count($rows) === 0) {
            return [];
        }

        $summaries = [];
        $lastMessageIds = [];
        foreach ($rows as $row) {
            $subjectId = (int)($row['subject_id'] ?? 0);
            $lastMessageId = (int)($row['last_message_id'] ?? 0);
            $lastViewerMessageId = (int)($row['last_viewer_message_id'] ?? 0);
            if ($subjectId <= 0) {
                continue;
            }
            $summaries[$subjectId] = [
                'total_messages' => (int)($row['total_messages'] ?? 0),
                'last_message_id' => $lastMessageId,
                'last_viewer_message_id' => $lastViewerMessageId,
                'messages_since_viewer' => $lastViewerMessageId > 0 ? (int)($row['messages_since_viewer'] ?? 0) : null,
                'last_message_user_id' => 0,
                'last_message_participant_id' => 0,
                'last_message_document_share_link_id' => 0,
                'last_message_author_name' => '',
                'last_message_type' => '',
                'last_message_at' => '',
            ];
            if ($lastMessageId > 0) {
                $lastMessageIds[] = $lastMessageId;
            }
        }

        if (count($lastMessageIds) === 0) {
            return $summaries;
        }

        $lastParameters = [];
        $lastPlaceholders = [];
        foreach (array_values(array_unique($lastMessageIds)) as $index => $messageId) {
            $placeholder = 'message_id_' . $index;
            $lastPlaceholders[] = ':' . $placeholder;
            $lastParameters[$placeholder] = $messageId;
        }
        $lastRows = self::fetchAll(
            'SELECT `id`, `IDuser`, `IDdecision_participant`, `IDdocument_share_link`, `author_name`, `message_type`, `created_at`
             FROM `chat_message`
             WHERE `id` IN (' . implode(', ', $lastPlaceholders) . ')',
            $lastParameters
        );
        $lastMessages = [];
        foreach (is_array($lastRows) ? $lastRows : [] as $lastRow) {
            $lastMessages[(int)($lastRow['id'] ?? 0)] = $lastRow;
        }
        foreach ($summaries as &$summary) {
            $lastRow = $lastMessages[(int)$summary['last_message_id']] ?? null;
            if (!is_array($lastRow)) {
                continue;
            }
            $summary['last_message_user_id'] = (int)($lastRow['IDuser'] ?? 0);
            $summary['last_message_participant_id'] = (int)($lastRow['IDdecision_participant'] ?? 0);
            $summary['last_message_document_share_link_id'] = (int)($lastRow['IDdocument_share_link'] ?? 0);
            $summary['last_message_author_name'] = trim((string)($lastRow['author_name'] ?? ''));
            $summary['last_message_type'] = trim((string)($lastRow['message_type'] ?? ''));
            $summary['last_message_at'] = trim((string)($lastRow['created_at'] ?? ''));
        }
        unset($summary);

        return $summaries;
    }

    public function save()
    {
        $organizationId = (int)$this->get('IDorganization');
        $subjectType = self::normalizeSubjectType($this->get('subject_type'));
        $subjectId = (int)$this->get('subject_id');
        if ($organizationId <= 0 || $subjectType === '' || $subjectId <= 0) {
            return [
                'status' => false,
                'text' => 'A chat thread needs an organization and a valid subject.',
            ];
        }

        $this->set('subject_type', $subjectType);
        $this->set('subject_id', $subjectId);
        if ((int)$this->get('IDuser_created') <= 0) {
            $this->set('IDuser_created', null);
        }

        return parent::save();
    }

    public function getMessages($limit = 200, $afterMessageId = 0)
    {
        return ChatMessage::findByThreadId((int)$this->getId(), $limit, $afterMessageId);
    }
}

?>
