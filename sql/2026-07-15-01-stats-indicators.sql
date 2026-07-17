-- @migration
-- OpenMyOrganization
-- Contextual steering indicators, dated values and reference curves

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `stat_indicator` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDorganization` int(11) NOT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `name` varchar(190) NOT NULL,
    `description` mediumtext DEFAULT NULL,
    `source_url` varchar(2000) DEFAULT NULL,
    `reference_type` varchar(20) NOT NULL DEFAULT 'none',
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_stat_indicator_organization` (`IDorganization`),
    KEY `idx_stat_indicator_holon` (`IDholon`),
    KEY `idx_stat_indicator_user` (`IDuser`),
    KEY `idx_stat_indicator_active` (`active`),
    CONSTRAINT `fk_stat_indicator_organization`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_stat_indicator_holon`
        FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_stat_indicator_user`
        FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stat_indicator_value` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDstatindicator` int(11) NOT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `value` decimal(20,6) NOT NULL,
    `measured_at` datetime NOT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_stat_indicator_value_indicator_date` (`IDstatindicator`, `measured_at`),
    KEY `idx_stat_indicator_value_user` (`IDuser`),
    CONSTRAINT `fk_stat_indicator_value_indicator`
        FOREIGN KEY (`IDstatindicator`) REFERENCES `stat_indicator` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_stat_indicator_value_user`
        FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stat_indicator_reference_point` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDstatindicator` int(11) NOT NULL,
    `position_percent` decimal(7,4) NOT NULL,
    `value` decimal(20,6) NOT NULL,
    `point_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_stat_indicator_reference_position` (`IDstatindicator`, `position_percent`),
    KEY `idx_stat_indicator_reference_indicator` (`IDstatindicator`),
    CONSTRAINT `fk_stat_indicator_reference_indicator`
        FOREIGN KEY (`IDstatindicator`) REFERENCES `stat_indicator` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
