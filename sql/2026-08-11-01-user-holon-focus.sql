-- @migration
ALTER TABLE `user_holon`
  ADD COLUMN IF NOT EXISTS `focus` varchar(250) DEFAULT NULL AFTER `parameters`;
