-- @migration
CREATE TABLE IF NOT EXISTS `auth_rate_limit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(64) NOT NULL,
  `key_hash` char(64) NOT NULL,
  `window_started_at` datetime NOT NULL,
  `attempt_count` int(10) unsigned NOT NULL DEFAULT 0,
  `blocked_until` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_auth_rate_limit_scope_key` (`scope`,`key_hash`),
  KEY `idx_auth_rate_limit_updated` (`updated_at`),
  KEY `idx_auth_rate_limit_blocked` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
