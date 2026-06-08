-- @migration

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `event` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDorganization` int(11) NOT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDuser` int(11) NOT NULL,
    `title` varchar(190) NOT NULL,
    `description` mediumtext DEFAULT NULL,
    `status` varchar(20) NOT NULL DEFAULT 'draft',
    `timezone` varchar(64) DEFAULT NULL,
    `start_at` datetime NOT NULL,
    `end_at` datetime NOT NULL,
    `is_all_day` tinyint(1) NOT NULL DEFAULT 0,
    `parameters` mediumtext DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_event_org` (`IDorganization`),
    KEY `idx_event_holon` (`IDholon`),
    KEY `idx_event_user` (`IDuser`),
    KEY `idx_event_status` (`status`),
    KEY `idx_event_active` (`active`),
    KEY `idx_event_start` (`start_at`),
    KEY `idx_event_org_start` (`IDorganization`, `start_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `event`
    ADD CONSTRAINT `fk_event_org`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_event_holon`
        FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL;
