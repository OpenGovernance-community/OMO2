-- @migration
CREATE TABLE IF NOT EXISTS `project_indicator` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDproject` int(11) NOT NULL,
  `IDstatindicator` int(11) NOT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_indicator` (`IDproject`, `IDstatindicator`),
  KEY `idx_project_indicator_indicator` (`IDstatindicator`),
  CONSTRAINT `fk_project_indicator_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_indicator_indicator` FOREIGN KEY (`IDstatindicator`) REFERENCES `stat_indicator` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_recurring_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDproject` int(11) NOT NULL,
  `IDrecurringtask` int(11) NOT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_recurring_task` (`IDproject`, `IDrecurringtask`),
  KEY `idx_project_recurring_task_task` (`IDrecurringtask`),
  CONSTRAINT `fk_project_recurring_task_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_recurring_task_task` FOREIGN KEY (`IDrecurringtask`) REFERENCES `recurring_task` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
