-- @migration
-- Direct responsibility for indicators and processes

SET NAMES utf8mb4;

ALTER TABLE `stat_indicator`
    ADD COLUMN IF NOT EXISTS `IDuser_responsible` int(11) DEFAULT NULL AFTER `IDuser`,
    ADD KEY IF NOT EXISTS `idx_stat_indicator_responsible` (`IDuser_responsible`);

ALTER TABLE `checklist`
    ADD COLUMN IF NOT EXISTS `IDuser_responsible` int(11) DEFAULT NULL AFTER `IDorganization`,
    ADD KEY IF NOT EXISTS `idx_checklist_responsible` (`IDuser_responsible`);

SET @fk_stat_indicator_responsible_exists := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = 'fk_stat_indicator_responsible'
);
SET @fk_stat_indicator_responsible_sql := IF(
    @fk_stat_indicator_responsible_exists = 0,
    'ALTER TABLE `stat_indicator` ADD CONSTRAINT `fk_stat_indicator_responsible` FOREIGN KEY (`IDuser_responsible`) REFERENCES `user` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fk_stat_indicator_responsible_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_checklist_responsible_exists := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = 'fk_checklist_responsible'
);
SET @fk_checklist_responsible_sql := IF(
    @fk_checklist_responsible_exists = 0,
    'ALTER TABLE `checklist` ADD CONSTRAINT `fk_checklist_responsible` FOREIGN KEY (`IDuser_responsible`) REFERENCES `user` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fk_checklist_responsible_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
