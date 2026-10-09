-- @migration
CREATE TABLE IF NOT EXISTS `project_external_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDproject` int(11) NOT NULL,
  `IDexternalcalendarevent` int(11) DEFAULT NULL,
  `calendar_title` varchar(190) NOT NULL,
  `title` varchar(1000) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `location` varchar(1000) DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `is_all_day` tinyint(1) NOT NULL DEFAULT 0,
  `source_missing` tinyint(1) NOT NULL DEFAULT 0,
  `last_seen_at` datetime NOT NULL DEFAULT current_timestamp(),
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_external_event` (`IDproject`, `IDexternalcalendarevent`),
  KEY `idx_project_external_event_source` (`IDexternalcalendarevent`),
  CONSTRAINT `fk_project_external_event_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_external_event_source` FOREIGN KEY (`IDexternalcalendarevent`) REFERENCES `external_calendar_event` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
