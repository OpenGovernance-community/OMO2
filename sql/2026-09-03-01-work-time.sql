-- @migration
CREATE TABLE IF NOT EXISTS `work_time` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDuser` int(11) NOT NULL,
    `IDorganization` int(11) NOT NULL,
    `IDholon` int(11) NOT NULL,
    `IDproject` int(11) DEFAULT NULL,
    `started_at` datetime NOT NULL,
    `ended_at` datetime DEFAULT NULL,
    `last_heartbeat_at` datetime DEFAULT NULL,
    `end_reason` varchar(20) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_work_time_user_open` (`IDuser`, `end_reason`, `id`),
    KEY `idx_work_time_organization_holon` (`IDorganization`, `IDholon`, `started_at`),
    KEY `idx_work_time_project` (`IDproject`),
    CONSTRAINT `fk_work_time_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_work_time_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_work_time_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_work_time_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
