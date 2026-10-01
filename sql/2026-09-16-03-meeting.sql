-- @migration
CREATE TABLE IF NOT EXISTS `meeting_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `IDexternalcalendar` int(11) DEFAULT NULL,
  `slug` varchar(48) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `timezone` varchar(64) NOT NULL DEFAULT 'Europe/Zurich',
  `weekly_hours` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_user` (`IDuser`),
  UNIQUE KEY `meeting_slug` (`slug`),
  FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`IDexternalcalendar`) REFERENCES `external_calendar` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `meeting_booking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `IDexternalcalendar` int(11) DEFAULT NULL,
  `token` char(64) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `guest_name` varchar(190) NOT NULL,
  `guest_email` varchar(254) NOT NULL,
  `reason` text NOT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `resource_url` varchar(2100) NOT NULL,
  `calendar_data` mediumtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `email_sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_token` (`token`),
  KEY `meeting_busy` (`IDuser`, `status`, `start_at`, `end_at`),
  FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`IDexternalcalendar`) REFERENCES `external_calendar` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `external_calendar_event` ADD COLUMN IF NOT EXISTS `is_busy` tinyint(1) NOT NULL DEFAULT 1;
-- Refresh old cached events once to read transparency as well.
UPDATE `external_calendar` SET `source_ctag` = NULL;
