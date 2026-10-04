-- @migration
-- Idempotent MCP document creation. Never store signed URLs or file contents here.
CREATE TABLE IF NOT EXISTS `mcp_document_creation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `IDuser` int NOT NULL,
  `IDorganization` int NOT NULL,
  `IDdocument` int DEFAULT NULL,
  `key_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `payload_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `completed` tinyint NOT NULL DEFAULT 0,
  `created_at` bigint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mcp_document_request` (`IDuser`, `IDorganization`, `key_hash`),
  CONSTRAINT `mcp_document_user_fk` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mcp_document_org_fk` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mcp_document_result_fk` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
