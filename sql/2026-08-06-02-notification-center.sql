-- @migration
-- Per-organization notification preferences and inbox entries.

CREATE TABLE IF NOT EXISTS `notification_preference` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDuser` int(11) NOT NULL,
    `IDorganization` int(11) NOT NULL,
    `event_key` varchar(80) NOT NULL,
    `channel_push` tinyint(1) NOT NULL DEFAULT 0,
    `channel_telegram` tinyint(1) NOT NULL DEFAULT 0,
    `channel_email` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_notification_preference_scope` (`IDuser`, `IDorganization`, `event_key`),
    KEY `idx_notification_preference_organization` (`IDorganization`),
    CONSTRAINT `fk_notification_preference_user`
        FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notification_preference_organization`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notification` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDuser` int(11) NOT NULL,
    `IDorganization` int(11) NOT NULL,
    `event_key` varchar(80) NOT NULL,
    `source_key` varchar(190) NOT NULL,
    `title` varchar(250) NOT NULL,
    `body` text DEFAULT NULL,
    `url` varchar(1000) DEFAULT NULL,
    `read_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_notification_user_source` (`IDuser`, `source_key`),
    KEY `idx_notification_inbox` (`IDuser`, `IDorganization`, `read_at`, `created_at`),
    CONSTRAINT `fk_notification_user`
        FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notification_organization`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
