-- @migration
-- OpenMyOrganization
-- PV editor handover state

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `pv_editor_handover_open` tinyint(1) NOT NULL DEFAULT 0 AFTER `IDuser_pv_editor`,
  ADD KEY IF NOT EXISTS `idx_document_pv_editor_handover` (`pv_editor_handover_open`);

UPDATE `document`
SET `pv_editor_handover_open` = 0
WHERE `pv_editor_handover_open` IS NULL;
