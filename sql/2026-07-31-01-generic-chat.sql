-- @migration
-- Add organization-scoped discussion threads and proposal authorship.

SET NAMES utf8mb4;

ALTER TABLE `decision_proposal`
    ADD COLUMN `IDuser_author` int(11) DEFAULT NULL AFTER `IDdecision_group`,
    ADD KEY `idx_decision_proposal_author` (`IDuser_author`),
    ADD CONSTRAINT `fk_decision_proposal_author`
        FOREIGN KEY (`IDuser_author`) REFERENCES `user` (`id`) ON DELETE SET NULL;

UPDATE `decision_proposal` proposal
INNER JOIN `decision_participant` participant
    ON participant.`id` = CAST(JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(proposal.`parameters`), proposal.`parameters`, '{}'), '$.added_by_participant_id')) AS UNSIGNED)
    AND participant.`IDdecision_process` = proposal.`IDdecision_process`
SET proposal.`IDuser_author` = participant.`IDuser`
WHERE proposal.`IDuser_author` IS NULL
  AND participant.`IDuser` IS NOT NULL
  AND JSON_VALID(proposal.`parameters`)
  AND JSON_EXTRACT(IF(JSON_VALID(proposal.`parameters`), proposal.`parameters`, '{}'), '$.added_by_participant_id') IS NOT NULL;

CREATE TABLE IF NOT EXISTS `chat_thread` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDorganization` int(11) NOT NULL,
    `IDuser_created` int(11) DEFAULT NULL,
    `subject_type` varchar(60) NOT NULL,
    `subject_id` int(11) NOT NULL,
    `title` varchar(190) DEFAULT NULL,
    `parameters` mediumtext DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_chat_thread_subject` (`IDorganization`, `subject_type`, `subject_id`),
    KEY `idx_chat_thread_creator` (`IDuser_created`),
    KEY `idx_chat_thread_active` (`IDorganization`, `active`),
    CONSTRAINT `fk_chat_thread_organization`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_chat_thread_creator`
        FOREIGN KEY (`IDuser_created`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_message` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDchat_thread` int(11) NOT NULL,
    `IDorganization` int(11) NOT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `message_type` varchar(20) NOT NULL DEFAULT 'user',
    `content` mediumtext NOT NULL,
    `author_name` varchar(190) DEFAULT NULL,
    `parameters` mediumtext DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_chat_message_thread` (`IDchat_thread`, `id`),
    KEY `idx_chat_message_organization` (`IDorganization`),
    KEY `idx_chat_message_user` (`IDuser`),
    KEY `idx_chat_message_type` (`message_type`),
    CONSTRAINT `fk_chat_message_thread`
        FOREIGN KEY (`IDchat_thread`) REFERENCES `chat_thread` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_chat_message_organization`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_chat_message_user`
        FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
