-- @migration
-- OpenMyOrganization
-- Contextual imports and groups for steering indicators

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `stat_indicator_import` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDorganization` int(11) NOT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDstatindicator` int(11) NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_stat_indicator_import_context` (`IDorganization`, `IDholon`, `active`),
    KEY `idx_stat_indicator_import_indicator` (`IDstatindicator`),
    CONSTRAINT `fk_stat_indicator_import_organization`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_stat_indicator_import_holon`
        FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_stat_indicator_import_indicator`
        FOREIGN KEY (`IDstatindicator`) REFERENCES `stat_indicator` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stat_indicator_group` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDorganization` int(11) NOT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `name` varchar(190) NOT NULL,
    `display_mode` varchar(20) NOT NULL DEFAULT 'overlay',
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_stat_indicator_group_context` (`IDorganization`, `IDholon`, `active`),
    KEY `idx_stat_indicator_group_user` (`IDuser`),
    CONSTRAINT `fk_stat_indicator_group_organization`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_stat_indicator_group_holon`
        FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_stat_indicator_group_user`
        FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stat_indicator_group_item` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDstatindicatorgroup` int(11) NOT NULL,
    `IDstatindicator` int(11) NOT NULL,
    `position` int(11) NOT NULL DEFAULT 0,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_stat_indicator_group_item` (`IDstatindicatorgroup`, `IDstatindicator`),
    KEY `idx_stat_indicator_group_item_position` (`IDstatindicatorgroup`, `position`),
    KEY `idx_stat_indicator_group_item_indicator` (`IDstatindicator`),
    CONSTRAINT `fk_stat_indicator_group_item_group`
        FOREIGN KEY (`IDstatindicatorgroup`) REFERENCES `stat_indicator_group` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_stat_indicator_group_item_indicator`
        FOREIGN KEY (`IDstatindicator`) REFERENCES `stat_indicator` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
