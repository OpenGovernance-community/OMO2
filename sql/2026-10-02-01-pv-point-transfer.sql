-- @migration
-- Preserve the destination of an agenda point carried to a later meeting.
ALTER TABLE `document_pv_point`
  ADD COLUMN IF NOT EXISTS `IDpoint_moved_to` int(11) DEFAULT NULL AFTER `is_handled`,
  ADD COLUMN IF NOT EXISTS `date_moved` datetime DEFAULT NULL AFTER `IDpoint_moved_to`,
  DROP FOREIGN KEY IF EXISTS `fk_pv_point_moved_to`,
  ADD INDEX IF NOT EXISTS `idx_pv_point_import` (`active`, `is_handled`, `IDpoint_moved_to`, `IDdocument`);

ALTER TABLE `document_pv_point`
  ADD CONSTRAINT `fk_pv_point_moved_to` FOREIGN KEY (`IDpoint_moved_to`)
    REFERENCES `document_pv_point` (`id`) ON DELETE SET NULL;
