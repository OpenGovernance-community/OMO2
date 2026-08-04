-- @migration
-- OpenMyOrganization
-- Link calendar events to projects

SET NAMES utf8mb4;

ALTER TABLE `event`
  ADD COLUMN IF NOT EXISTS `IDproject` int(11) DEFAULT NULL AFTER `IDholon`,
  ADD KEY IF NOT EXISTS `idx_event_project` (`IDproject`);

SET @fk_event_project_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_event_project'
);

SET @fk_event_project_sql := IF(
  @fk_event_project_exists = 0,
  'ALTER TABLE `event` ADD CONSTRAINT `fk_event_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_event_project_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
