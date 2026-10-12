-- @migration
-- Preserve an unfinished agenda point while explicitly closing further follow-up.
ALTER TABLE `document_pv_point`
  ADD COLUMN IF NOT EXISTS `date_abandoned` datetime DEFAULT NULL AFTER `date_moved`;
