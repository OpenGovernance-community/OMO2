-- @migration
-- OpenMyOrganization
-- Event locations and linked documents

SET NAMES utf8mb4;

ALTER TABLE `event`
  ADD COLUMN IF NOT EXISTS `IDdocument` int(11) DEFAULT NULL AFTER `IDuser`,
  ADD COLUMN IF NOT EXISTS `locationmode` varchar(20) DEFAULT NULL AFTER `timezone`,
  ADD COLUMN IF NOT EXISTS `locationaddress` varchar(1000) DEFAULT NULL AFTER `locationmode`,
  ADD COLUMN IF NOT EXISTS `videomeetingurl` varchar(2000) DEFAULT NULL AFTER `locationaddress`;

ALTER TABLE `event`
  ADD KEY IF NOT EXISTS `idx_event_document` (`IDdocument`),
  ADD KEY IF NOT EXISTS `idx_event_location_mode` (`locationmode`),
  ADD UNIQUE KEY IF NOT EXISTS `uniq_event_document` (`IDdocument`);

SET @fk_event_document_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_event_document'
);

SET @fk_event_document_sql := IF(
  @fk_event_document_exists = 0,
  'ALTER TABLE `event` ADD CONSTRAINT `fk_event_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_event_document_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
