-- @migration
-- Cached totals and one reaction per authenticated discussion identity.
ALTER TABLE `chat_message` ADD COLUMN `reactions` JSON DEFAULT NULL AFTER `parameters`;

CREATE TABLE `chat_message_reaction` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDchat_message` int(11) NOT NULL,
    `actor_key` varchar(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `reaction` varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_chat_reaction_actor` (`IDchat_message`, `actor_key`),
    CONSTRAINT `fk_chat_reaction_message` FOREIGN KEY (`IDchat_message`)
        REFERENCES `chat_message` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_chat_reaction_kind` CHECK (`reaction` IN ('up', 'down', 'clap'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
