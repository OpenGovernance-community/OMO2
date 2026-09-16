-- @migration
CREATE TABLE IF NOT EXISTS `calendar_share` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `token` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `months` tinyint unsigned NOT NULL DEFAULT 3,
  `details` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `calendar_share_token` (`token`),
  KEY `calendar_share_owner` (`IDuser`, `active`),
  FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
