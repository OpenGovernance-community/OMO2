-- @migration
-- OpenMyOrganization
-- Suivi des vraies modifications de valeur sur holonproperty

SET NAMES utf8mb4;

ALTER TABLE `holonproperty`
  ADD COLUMN IF NOT EXISTS `datemodification` datetime DEFAULT NULL AFTER `position`,
  ADD COLUMN IF NOT EXISTS `IDusermodification` int(11) DEFAULT NULL AFTER `datemodification`;

SET @idx_holonproperty_user_modification_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'holonproperty'
    AND INDEX_NAME = 'idx_holonproperty_user_modification'
);

SET @idx_holonproperty_user_modification_sql := IF(
  @idx_holonproperty_user_modification_exists = 0,
  'ALTER TABLE `holonproperty` ADD KEY `idx_holonproperty_user_modification` (`IDusermodification`)',
  'SELECT 1'
);
PREPARE stmt FROM @idx_holonproperty_user_modification_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_holonproperty_user_modification_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'holonproperty'
    AND CONSTRAINT_NAME = 'fk_holonproperty_user_modification'
);

SET @fk_holonproperty_user_modification_sql := IF(
  @fk_holonproperty_user_modification_exists = 0,
  'ALTER TABLE `holonproperty` ADD CONSTRAINT `fk_holonproperty_user_modification` FOREIGN KEY (`IDusermodification`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @fk_holonproperty_user_modification_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'holonproperty'
  AND COLUMN_NAME IN ('datemodification', 'IDusermodification')
ORDER BY ORDINAL_POSITION ASC;
