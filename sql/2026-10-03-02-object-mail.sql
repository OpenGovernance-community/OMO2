-- @migration
-- Object-scoped outbox: no arbitrary recipient lists, per-recipient delivery tracking.
CREATE TABLE IF NOT EXISTS `object_mail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `IDuser` int NOT NULL,
  `IDorganization` int NOT NULL,
  `IDmcp_oauth_grant` int DEFAULT NULL,
  `object_type` varchar(20) CHARACTER SET ascii NOT NULL,
  `object_id` int NOT NULL,
  `request_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `payload_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `subject` varchar(250) NOT NULL,
  `message` mediumtext NOT NULL,
  `recipient_count` int NOT NULL,
  `created_at` bigint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `object_mail_request` (`IDuser`, `IDorganization`, `request_hash`),
  KEY `object_mail_quota` (`IDorganization`, `created_at`),
  CONSTRAINT `object_mail_user_fk` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `object_mail_org_fk` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `object_mail_grant_fk` FOREIGN KEY (`IDmcp_oauth_grant`) REFERENCES `mcp_oauth_grant` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `object_mail_recipient` (
  `id` int NOT NULL AUTO_INCREMENT,
  `IDobject_mail` int NOT NULL,
  `member_id` varchar(80) CHARACTER SET ascii NOT NULL,
  `email` varchar(254) NOT NULL,
  `status` varchar(20) CHARACTER SET ascii NOT NULL DEFAULT 'queued',
  `updated_at` bigint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `object_mail_address` (`IDobject_mail`, `email`),
  KEY `object_mail_pending` (`status`, `id`),
  CONSTRAINT `object_mail_recipient_fk` FOREIGN KEY (`IDobject_mail`) REFERENCES `object_mail` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
