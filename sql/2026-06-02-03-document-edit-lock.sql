-- @migration

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `dateedition` datetime DEFAULT NULL AFTER `datemodification`,
  ADD COLUMN IF NOT EXISTS `IDuseredition` int(11) DEFAULT NULL AFTER `dateedition`;

ALTER TABLE `document`
  ADD KEY IF NOT EXISTS `idx_document_editing_user` (`IDuseredition`),
  ADD KEY IF NOT EXISTS `idx_document_editing_date` (`dateedition`);

SET @fk_document_user_editing_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_user_editing'
);

SET @fk_document_user_editing_sql := IF(
  @fk_document_user_editing_exists = 0,
  'ALTER TABLE `document` ADD CONSTRAINT `fk_document_user_editing` FOREIGN KEY (`IDuseredition`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_user_editing_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
