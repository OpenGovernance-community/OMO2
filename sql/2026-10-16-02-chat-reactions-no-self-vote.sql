-- @migration
-- Remove legacy self-reactions and rebuild only the affected message totals.
CREATE TEMPORARY TABLE `chat_self_reaction_messages` (`id` int(11) NOT NULL PRIMARY KEY) ENGINE=InnoDB;

INSERT INTO `chat_self_reaction_messages` (`id`)
SELECT DISTINCT m.id
FROM chat_message m
INNER JOIN chat_message_reaction r ON r.IDchat_message = m.id
LEFT JOIN decision_participant p ON p.id = m.IDdecision_participant
LEFT JOIN document_share_link l ON l.id = m.IDdocument_share_link
WHERE r.actor_key = CAST(CASE
    WHEN m.IDuser > 0 THEN CONCAT('user:', m.IDuser)
    WHEN m.IDdecision_participant > 0 THEN
        CASE WHEN p.IDuser > 0 THEN CONCAT('user:', p.IDuser)
             ELSE CONCAT('participant:', m.IDdecision_participant) END
    WHEN l.recipient_user_id > 0 THEN CONCAT('user:', l.recipient_user_id)
    WHEN TRIM(COALESCE(l.recipient_email, '')) <> '' THEN CONCAT('email:', SHA2(LOWER(TRIM(l.recipient_email)), 256))
    ELSE ''
END AS BINARY);

START TRANSACTION;

-- Acquire the same message locks as the reaction writer before touching votes.
UPDATE chat_message m
INNER JOIN chat_self_reaction_messages affected ON affected.id = m.id
SET m.reactions = m.reactions;

DELETE r FROM chat_message_reaction r
INNER JOIN chat_self_reaction_messages affected ON affected.id = r.IDchat_message
INNER JOIN chat_message m ON m.id = r.IDchat_message
LEFT JOIN decision_participant p ON p.id = m.IDdecision_participant
LEFT JOIN document_share_link l ON l.id = m.IDdocument_share_link
WHERE r.actor_key = CAST(CASE
    WHEN m.IDuser > 0 THEN CONCAT('user:', m.IDuser)
    WHEN m.IDdecision_participant > 0 THEN
        CASE WHEN p.IDuser > 0 THEN CONCAT('user:', p.IDuser)
             ELSE CONCAT('participant:', m.IDdecision_participant) END
    WHEN l.recipient_user_id > 0 THEN CONCAT('user:', l.recipient_user_id)
    WHEN TRIM(COALESCE(l.recipient_email, '')) <> '' THEN CONCAT('email:', SHA2(LOWER(TRIM(l.recipient_email)), 256))
    ELSE ''
END AS BINARY);

UPDATE chat_message m
INNER JOIN chat_self_reaction_messages affected ON affected.id = m.id
LEFT JOIN (
    SELECT IDchat_message,
        SUM(reaction = 'up') AS up_count,
        SUM(reaction = 'down') AS down_count,
        SUM(reaction = 'clap') AS clap_count
    FROM chat_message_reaction
    GROUP BY IDchat_message
) totals ON totals.IDchat_message = m.id
SET m.reactions = JSON_OBJECT(
    'up', COALESCE(totals.up_count, 0),
    'down', COALESCE(totals.down_count, 0),
    'clap', COALESCE(totals.clap_count, 0),
    '_version', COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(m.reactions, '$._version')) AS UNSIGNED), 0) + 1
);

COMMIT;
DROP TEMPORARY TABLE `chat_self_reaction_messages`;
