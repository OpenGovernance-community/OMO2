-- @migration
-- OpenMyOrganization
-- Nested groups for PV agenda points

SET NAMES utf8mb4;

ALTER TABLE `document_pv_point`
  ADD COLUMN IF NOT EXISTS `item_type` varchar(20) NOT NULL DEFAULT 'point' AFTER `IDdocument`,
  ADD COLUMN IF NOT EXISTS `IDparent` int(11) DEFAULT NULL AFTER `item_type`,
  ADD KEY IF NOT EXISTS `idx_document_pv_point_parent` (`IDdocument`, `IDparent`, `position`),
  ADD KEY IF NOT EXISTS `idx_document_pv_point_item_type` (`item_type`);

SET @fk_document_pv_point_parent_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_pv_point_parent'
);

SET @fk_document_pv_point_parent_sql := IF(
  @fk_document_pv_point_parent_exists = 0,
  'ALTER TABLE `document_pv_point` ADD CONSTRAINT `fk_document_pv_point_parent` FOREIGN KEY (`IDparent`) REFERENCES `document_pv_point` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_pv_point_parent_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
