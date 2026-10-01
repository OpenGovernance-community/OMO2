-- @migration
-- CalDAV event synchronization journal, including deletion tombstones.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `caldav_sync_change` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `IDorganization` int(11) NOT NULL,
    `event_id` int(11) NOT NULL,
    `change_type` varchar(20) NOT NULL,
    `changed_at` datetime NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_caldav_sync_change_organization_id` (`IDorganization`, `id`),
    KEY `idx_caldav_sync_change_event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @fk_caldav_sync_change_organization_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_caldav_sync_change_organization'
);

SET @fk_caldav_sync_change_organization_sql := IF(
  @fk_caldav_sync_change_organization_exists = 0,
  'ALTER TABLE `caldav_sync_change` ADD CONSTRAINT `fk_caldav_sync_change_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE',
  'SELECT 1'
);

PREPARE stmt FROM @fk_caldav_sync_change_organization_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
