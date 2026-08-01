-- @migration
-- Persistent Telegram group or topic destinations for voice memo capture.

CREATE TABLE IF NOT EXISTS `telegram_chat_destination` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `telegram_chat_id` varchar(32) NOT NULL,
    `telegram_thread_id` varchar(32) NOT NULL DEFAULT '',
    `IDorganization` int(11) NOT NULL,
    `destination_type` varchar(20) NOT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDproject` int(11) DEFAULT NULL,
    `IDuser_configured` int(11) DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_telegram_chat_destination` (`telegram_chat_id`, `telegram_thread_id`),
    KEY `idx_telegram_destination_organization` (`IDorganization`),
    KEY `idx_telegram_destination_holon` (`IDholon`),
    KEY `idx_telegram_destination_project` (`IDproject`),
    KEY `idx_telegram_destination_user` (`IDuser_configured`),
    CONSTRAINT `fk_telegram_destination_organization`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_telegram_destination_holon`
        FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_telegram_destination_project`
        FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_telegram_destination_user`
        FOREIGN KEY (`IDuser_configured`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
