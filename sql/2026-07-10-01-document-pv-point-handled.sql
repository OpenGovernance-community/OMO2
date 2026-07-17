-- @migration
-- OpenMyOrganization
-- Add handled flag on PV points

SET NAMES utf8mb4;

ALTER TABLE `document_pv_point`
  ADD COLUMN IF NOT EXISTS `is_handled` tinyint(1) NOT NULL DEFAULT 0 AFTER `pointtype`;

UPDATE `document_pv_point`
SET `is_handled` = COALESCE(`is_handled`, 0)
WHERE `is_handled` IS NULL;
