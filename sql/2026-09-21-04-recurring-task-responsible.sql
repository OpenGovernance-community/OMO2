-- @migration
-- Keep the responsible user column when the legacy control task table was renamed.
ALTER TABLE `recurring_task`
    ADD COLUMN IF NOT EXISTS `IDuser_responsible` int(11) DEFAULT NULL AFTER `IDholon`,
    ADD KEY IF NOT EXISTS `idx_recurring_task_responsible` (`IDuser_responsible`);

SET @fk_recurring_task_responsible_exists := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = 'fk_recurring_task_responsible'
);
SET @fk_recurring_task_responsible_sql := IF(
    @fk_recurring_task_responsible_exists = 0,
    'ALTER TABLE `recurring_task` ADD CONSTRAINT `fk_recurring_task_responsible` FOREIGN KEY (`IDuser_responsible`) REFERENCES `user` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fk_recurring_task_responsible_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
