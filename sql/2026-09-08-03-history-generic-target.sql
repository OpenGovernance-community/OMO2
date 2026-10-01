-- @migration
-- OpenMyOrganization
-- Generic target for history entries outside the structural holon scope.

SET NAMES utf8mb4;

ALTER TABLE `history`
  ADD COLUMN IF NOT EXISTS `target_type` varchar(50) DEFAULT NULL AFTER `IDholon_circle`,
  ADD COLUMN IF NOT EXISTS `target_id` int(11) DEFAULT NULL AFTER `target_type`,
  ADD KEY IF NOT EXISTS `idx_history_target` (`IDorganization`, `target_type`, `target_id`, `datecreation`);
