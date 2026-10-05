-- @migration
-- Shared project write retry ledger; native project data remains in project.
CREATE TABLE IF NOT EXISTS `mcp_project_write` (
  `id` int NOT NULL AUTO_INCREMENT,
  `IDuser` int NOT NULL,
  `IDorganization` int NOT NULL,
  `IDproject` int DEFAULT NULL,
  `operation` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `key_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `payload_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `completed` tinyint NOT NULL DEFAULT 0,
  `created_at` bigint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mcp_project_request` (`IDuser`, `IDorganization`, `key_hash`),
  CONSTRAINT `mcp_project_user_fk` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mcp_project_org_fk` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mcp_project_result_fk` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
