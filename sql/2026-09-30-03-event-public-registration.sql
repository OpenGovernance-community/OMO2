-- @migration
CREATE TABLE IF NOT EXISTS `event_public_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDevent` int(11) NOT NULL,
  `token` char(64) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_public_link_event` (`IDevent`),
  UNIQUE KEY `event_public_link_token` (`token`),
  CONSTRAINT `fk_event_public_link_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_public_registration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDevent` int(11) NOT NULL,
  `name` varchar(190) NOT NULL,
  `email` varchar(254) NOT NULL,
  `token` char(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_public_registration_email` (`IDevent`, `email`),
  UNIQUE KEY `event_public_registration_token` (`token`),
  KEY `event_public_registration_confirmed` (`IDevent`, `confirmed_at`),
  CONSTRAINT `fk_event_public_registration_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
