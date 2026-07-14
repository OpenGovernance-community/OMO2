-- @migration
-- OpenMyOrganization
-- Invert linked event/document relationship

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `IDevent` int(11) DEFAULT NULL AFTER `IDholon`;

UPDATE `document` d
INNER JOIN `event` e
  ON e.`IDdocument` = d.`id`
SET d.`IDevent` = e.`id`
WHERE d.`IDevent` IS NULL;

ALTER TABLE `document`
  ADD KEY IF NOT EXISTS `idx_document_event` (`IDevent`);

SET @fk_document_event_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_event'
);

SET @fk_document_event_sql := IF(
  @fk_document_event_exists = 0,
  'ALTER TABLE `document` ADD CONSTRAINT `fk_document_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_event_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `event`
  DROP FOREIGN KEY IF EXISTS `fk_event_document`;

ALTER TABLE `event`
  DROP INDEX IF EXISTS `uniq_event_document`,
  DROP INDEX IF EXISTS `idx_event_document`;

ALTER TABLE `event`
  DROP COLUMN IF EXISTS `IDdocument`;
