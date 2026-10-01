-- @migration
ALTER TABLE `holon`
  ADD COLUMN IF NOT EXISTS `parameters` mediumtext DEFAULT NULL AFTER `accesskey`;
