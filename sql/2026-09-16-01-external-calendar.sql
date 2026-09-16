-- @migration
-- OpenMyOrganization
-- Personal read-only CalDAV calendars

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `external_calendar` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDuser` int(11) NOT NULL,
    `provider` varchar(32) NOT NULL DEFAULT 'caldav',
    `title` varchar(190) NOT NULL,
    `calendar_url` varchar(2000) NOT NULL,
    `username` varchar(250) NOT NULL,
    `password_encrypted` text NOT NULL,
    `color` varchar(7) NOT NULL DEFAULT '#0f766e',
    `timezone` varchar(64) DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `last_sync_at` datetime DEFAULT NULL,
    `last_sync_error` text DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_external_calendar_user_active` (`IDuser`, `active`),
    CONSTRAINT `fk_external_calendar_user`
        FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `external_calendar_event` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDexternalcalendar` int(11) NOT NULL,
    `source_key` varchar(512) NOT NULL,
    `source_etag` varchar(255) DEFAULT NULL,
    `title` varchar(1000) NOT NULL,
    `description` mediumtext DEFAULT NULL,
    `location` varchar(1000) DEFAULT NULL,
    `timezone` varchar(64) DEFAULT NULL,
    `start_at` datetime NOT NULL,
    `end_at` datetime NOT NULL,
    `is_all_day` tinyint(1) NOT NULL DEFAULT 0,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_external_calendar_event_source` (`IDexternalcalendar`, `source_key`),
    KEY `idx_external_calendar_event_range` (`IDexternalcalendar`, `active`, `start_at`, `end_at`),
    CONSTRAINT `fk_external_calendar_event_calendar`
        FOREIGN KEY (`IDexternalcalendar`) REFERENCES `external_calendar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
