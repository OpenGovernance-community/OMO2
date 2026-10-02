-- @migration
-- Read-only MCP clients and organization-scoped OAuth grants. Tokens are hashed.
CREATE TABLE IF NOT EXISTS `mcp_oauth_client` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(150) NOT NULL,
  `redirect_uris` text NOT NULL,
  `created_at` bigint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mcp_client_identifier` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mcp_oauth_grant` (
  `id` int NOT NULL AUTO_INCREMENT,
  `IDclient` int NOT NULL,
  `IDuser` int NOT NULL,
  `IDorganization` int NOT NULL,
  `resource` varchar(512) NOT NULL,
  `scope` varchar(100) NOT NULL,
  `redirect_uri` varchar(2048) NOT NULL,
  `code_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code_challenge` varchar(43) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code_expires_at` bigint NOT NULL,
  `code_used_at` bigint DEFAULT NULL,
  `access_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `access_expires_at` bigint DEFAULT NULL,
  `refresh_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `refresh_expires_at` bigint DEFAULT NULL,
  `revoked_at` bigint DEFAULT NULL,
  `created_at` bigint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mcp_grant_code` (`code_hash`),
  UNIQUE KEY `mcp_grant_access` (`access_hash`),
  UNIQUE KEY `mcp_grant_refresh` (`refresh_hash`),
  KEY `mcp_grant_owner` (`IDuser`, `created_at`),
  CONSTRAINT `mcp_grant_client_fk` FOREIGN KEY (`IDclient`) REFERENCES `mcp_oauth_client` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mcp_grant_user_fk` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mcp_grant_organization_fk` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
