-- @migration
-- Historical completion times are unknown and must remain NULL.
ALTER TABLE `document_pv_point` ADD COLUMN IF NOT EXISTS `handled_at` datetime DEFAULT NULL AFTER `is_handled`;
