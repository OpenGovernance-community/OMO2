-- @migration

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `IDusercreation` int(11) DEFAULT NULL AFTER `IDuser`,
  ADD COLUMN IF NOT EXISTS `IDusermodification` int(11) DEFAULT NULL AFTER `datemodification`;

UPDATE `document`
SET `IDusercreation` = `IDuser`
WHERE `IDusercreation` IS NULL
  AND `IDuser` IS NOT NULL;

UPDATE `document`
SET `IDusermodification` = COALESCE(`IDusercreation`, `IDuser`)
WHERE `IDusermodification` IS NULL;

ALTER TABLE `document`
  ADD KEY IF NOT EXISTS `idx_document_user_creation` (`IDusercreation`),
  ADD KEY IF NOT EXISTS `idx_document_user_modification` (`IDusermodification`);

SET @fk_document_user_creation_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_user_creation'
);

SET @fk_document_user_creation_sql := IF(
  @fk_document_user_creation_exists = 0,
  'ALTER TABLE `document` ADD CONSTRAINT `fk_document_user_creation` FOREIGN KEY (`IDusercreation`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_user_creation_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_document_user_modification_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_user_modification'
);

SET @fk_document_user_modification_sql := IF(
  @fk_document_user_modification_exists = 0,
  'ALTER TABLE `document` ADD CONSTRAINT `fk_document_user_modification` FOREIGN KEY (`IDusermodification`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_user_modification_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
