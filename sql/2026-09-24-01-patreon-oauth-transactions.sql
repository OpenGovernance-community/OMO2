-- @migration

CREATE TABLE IF NOT EXISTS `patreon_oauth_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `handoff_token_hash` char(64) DEFAULT NULL,
  `oauth_state_hash` char(64) DEFAULT NULL,
  `claim_token` char(64) DEFAULT NULL,
  `return_origin` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_patreon_oauth_handoff_hash` (`handoff_token_hash`),
  UNIQUE KEY `uniq_patreon_oauth_state_hash` (`oauth_state_hash`),
  KEY `idx_patreon_oauth_expiration` (`status`, `expires_at`),
  KEY `idx_patreon_oauth_user` (`IDuser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
