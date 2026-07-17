-- @migration
-- OpenMyOrganization
-- Add sync and edit lock metadata on PV points

SET NAMES utf8mb4;

ALTER TABLE `document_pv_point`
  ADD COLUMN IF NOT EXISTS `IDuser_modification` int(11) DEFAULT NULL AFTER `IDuser_author`,
  ADD COLUMN IF NOT EXISTS `IDuser_editing` int(11) DEFAULT NULL AFTER `IDuser_modification`,
  ADD COLUMN IF NOT EXISTS `edit_lock_token` varchar(80) DEFAULT NULL AFTER `IDuser_editing`,
  ADD COLUMN IF NOT EXISTS `dateedition` datetime DEFAULT NULL AFTER `datemodification`;

ALTER TABLE `document_pv_point`
  ADD KEY IF NOT EXISTS `idx_document_pv_point_modification_user` (`IDuser_modification`),
  ADD KEY IF NOT EXISTS `idx_document_pv_point_editing_user` (`IDuser_editing`),
  ADD KEY IF NOT EXISTS `idx_document_pv_point_dateedition` (`dateedition`);

SET @fk_document_pv_point_modification_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_pv_point_modification_user'
);

SET @fk_document_pv_point_editing_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_pv_point_editing_user'
);

SET @fk_document_pv_point_modification_sql := IF(
  @fk_document_pv_point_modification_exists = 0,
  'ALTER TABLE `document_pv_point` ADD CONSTRAINT `fk_document_pv_point_modification_user` FOREIGN KEY (`IDuser_modification`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

SET @fk_document_pv_point_editing_sql := IF(
  @fk_document_pv_point_editing_exists = 0,
  'ALTER TABLE `document_pv_point` ADD CONSTRAINT `fk_document_pv_point_editing_user` FOREIGN KEY (`IDuser_editing`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_pv_point_modification_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

PREPARE stmt FROM @fk_document_pv_point_editing_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
