-- @migration
-- OpenMyOrganization
-- Event invitations

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `event_invitation` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDevent` int(11) NOT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `email` varchar(250) DEFAULT NULL,
    `display_name` varchar(190) DEFAULT NULL,
    `invitation_type` varchar(30) NOT NULL,
    `status` varchar(30) NOT NULL DEFAULT 'invited',
    `parameters` mediumtext DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_event_invitation_holon` (`IDevent`, `IDholon`),
    UNIQUE KEY `uniq_event_invitation_user` (`IDevent`, `IDuser`),
    UNIQUE KEY `uniq_event_invitation_email` (`IDevent`, `email`),
    KEY `idx_event_invitation_type` (`invitation_type`),
    KEY `idx_event_invitation_status` (`status`),
    KEY `idx_event_invitation_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @fk_event_invitation_event_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_event_invitation_event'
);

SET @fk_event_invitation_holon_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_event_invitation_holon'
);

SET @fk_event_invitation_user_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_event_invitation_user'
);

SET @fk_event_invitation_event_sql := IF(
  @fk_event_invitation_event_exists = 0,
  'ALTER TABLE `event_invitation` ADD CONSTRAINT `fk_event_invitation_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE CASCADE',
  'SELECT 1'
);

SET @fk_event_invitation_holon_sql := IF(
  @fk_event_invitation_holon_exists = 0,
  'ALTER TABLE `event_invitation` ADD CONSTRAINT `fk_event_invitation_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE',
  'SELECT 1'
);

SET @fk_event_invitation_user_sql := IF(
  @fk_event_invitation_user_exists = 0,
  'ALTER TABLE `event_invitation` ADD CONSTRAINT `fk_event_invitation_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE',
  'SELECT 1'
);

PREPARE stmt FROM @fk_event_invitation_event_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

PREPARE stmt FROM @fk_event_invitation_holon_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

PREPARE stmt FROM @fk_event_invitation_user_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
