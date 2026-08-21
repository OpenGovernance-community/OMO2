-- @migration
-- OpenMyOrganization
-- Remember the last official PV editor for the review stage

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `IDuser_pv_official_editor` int(11) DEFAULT NULL AFTER `IDuser_pv_editor`,
  ADD KEY IF NOT EXISTS `idx_document_pv_official_editor` (`IDuser_pv_official_editor`);

SET @fk_document_pv_official_editor_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_pv_official_editor'
);

SET @fk_document_pv_official_editor_sql := IF(
  @fk_document_pv_official_editor_exists = 0,
  'ALTER TABLE `document` ADD CONSTRAINT `fk_document_pv_official_editor` FOREIGN KEY (`IDuser_pv_official_editor`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_pv_official_editor_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
