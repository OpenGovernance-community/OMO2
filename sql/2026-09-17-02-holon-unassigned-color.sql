-- @migration
ALTER TABLE `holon`
  ADD COLUMN IF NOT EXISTS `color_unassigned` varchar(10) DEFAULT NULL AFTER `color`;
