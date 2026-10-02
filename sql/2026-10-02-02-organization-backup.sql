-- @migration
-- Organization backup configuration and delivery state.
CREATE TABLE IF NOT EXISTS `organization_backup` (
  `id` int NOT NULL AUTO_INCREMENT,
  `IDorganization` int NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `email` varchar(254) NOT NULL DEFAULT '',
  `frequency` varchar(3) NOT NULL DEFAULT '1m',
  `last_sent_at` datetime DEFAULT NULL,
  `last_attempt_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organization_backup_organization` (`IDorganization`),
  CONSTRAINT `organization_backup_organization_fk` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
