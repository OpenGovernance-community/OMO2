-- @migration
-- OpenMyOrganization
-- Event attendance checklist

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `event_attendance` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDevent` int(11) NOT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `email` varchar(250) DEFAULT NULL,
    `display_name` varchar(190) DEFAULT NULL,
    `is_present` tinyint(1) NOT NULL DEFAULT 0,
    `IDuser_checked_by` int(11) DEFAULT NULL,
    `checked_at` datetime DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_event_attendance_user` (`IDevent`, `IDuser`),
    UNIQUE KEY `uniq_event_attendance_email` (`IDevent`, `email`),
    KEY `idx_event_attendance_present` (`is_present`),
    KEY `idx_event_attendance_checked_by` (`IDuser_checked_by`),
    KEY `idx_event_attendance_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @fk_event_attendance_event_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_event_attendance_event'
);

SET @fk_event_attendance_user_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_event_attendance_user'
);

SET @fk_event_attendance_checked_by_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_event_attendance_checked_by'
);

SET @fk_event_attendance_event_sql := IF(
  @fk_event_attendance_event_exists = 0,
  'ALTER TABLE `event_attendance` ADD CONSTRAINT `fk_event_attendance_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE CASCADE',
  'SELECT 1'
);

SET @fk_event_attendance_user_sql := IF(
  @fk_event_attendance_user_exists = 0,
  'ALTER TABLE `event_attendance` ADD CONSTRAINT `fk_event_attendance_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE',
  'SELECT 1'
);

SET @fk_event_attendance_checked_by_sql := IF(
  @fk_event_attendance_checked_by_exists = 0,
  'ALTER TABLE `event_attendance` ADD CONSTRAINT `fk_event_attendance_checked_by` FOREIGN KEY (`IDuser_checked_by`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_event_attendance_event_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

PREPARE stmt FROM @fk_event_attendance_user_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

PREPARE stmt FROM @fk_event_attendance_checked_by_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
