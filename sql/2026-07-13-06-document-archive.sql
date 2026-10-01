-- @migration
-- OpenMyOrganization
-- Add document archive state

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `active` tinyint(1) NOT NULL DEFAULT 1 AFTER `estDossier`;

UPDATE `document`
SET `active` = 1
WHERE `active` IS NULL;

ALTER TABLE `document`
  ADD KEY IF NOT EXISTS `idx_document_active` (`active`);
