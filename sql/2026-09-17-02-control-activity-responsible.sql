-- @migration
-- Direct responsibility for recurring activities

SET NAMES utf8mb4;

ALTER TABLE `control_task`
    ADD COLUMN IF NOT EXISTS `IDuser_responsible` int(11) DEFAULT NULL AFTER `IDholon`,
    ADD KEY IF NOT EXISTS `idx_control_task_responsible` (`IDuser_responsible`);

SET @fk_control_task_responsible_exists := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = 'fk_control_task_responsible'
);
SET @fk_control_task_responsible_sql := IF(
    @fk_control_task_responsible_exists = 0,
    'ALTER TABLE `control_task` ADD CONSTRAINT `fk_control_task_responsible` FOREIGN KEY (`IDuser_responsible`) REFERENCES `user` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fk_control_task_responsible_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
