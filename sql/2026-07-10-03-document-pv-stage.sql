-- @migration
-- OpenMyOrganization
-- Add PV workflow stage on documents

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `pvstage` varchar(30) DEFAULT NULL AFTER `documenttype`;

ALTER TABLE `document`
  ADD KEY IF NOT EXISTS `idx_document_pvstage` (`pvstage`);

UPDATE `document`
SET `pvstage` = 'preparation'
WHERE `documenttype` = 'pv'
  AND (`pvstage` IS NULL OR TRIM(`pvstage`) = '');
