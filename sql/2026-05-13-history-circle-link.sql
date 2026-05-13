-- @migration
-- OpenMyOrganization
-- Rattachement optionnel des entrees d'historique au cercle englobant

SET NAMES utf8mb4;

ALTER TABLE `history`
  ADD COLUMN IF NOT EXISTS `IDholon_circle` int(11) DEFAULT NULL AFTER `IDuser`;

SET @idx_history_holon_circle_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'history'
    AND INDEX_NAME = 'idx_history_holon_circle'
);

SET @idx_history_holon_circle_sql := IF(
  @idx_history_holon_circle_exists = 0,
  'ALTER TABLE `history` ADD KEY `idx_history_holon_circle` (`IDholon_circle`)',
  'SELECT 1'
);
PREPARE stmt FROM @idx_history_holon_circle_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'history'
  AND COLUMN_NAME = 'IDholon_circle';
