-- @migration
-- OpenMyOrganization
-- Reusable PV templates

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `is_template` tinyint(1) NOT NULL DEFAULT 0 AFTER `pvstage`,
  ADD KEY IF NOT EXISTS `idx_document_pv_template` (`IDorganization`, `is_template`, `active`, `documenttype`);

UPDATE `document`
SET `is_template` = 0
WHERE `is_template` IS NULL;
