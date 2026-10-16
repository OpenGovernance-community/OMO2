-- @migration

CREATE TABLE IF NOT EXISTS `helloasso_connection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `configuration_key` varchar(64) NOT NULL,
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_helloasso_configuration` (`configuration_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_helloasso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `configuration_key` varchar(64) NOT NULL,
  `payments` mediumtext DEFAULT NULL,
  `last_attempt_at` datetime DEFAULT NULL,
  `last_sync_at` datetime DEFAULT NULL,
  `last_sync_error` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_helloasso_user` (`IDuser`),
  KEY `idx_user_helloasso_attempt` (`last_attempt_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
