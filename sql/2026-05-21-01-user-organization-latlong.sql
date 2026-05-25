-- @migration
ALTER TABLE `user_organization`
  ADD COLUMN IF NOT EXISTS `latlong` varchar(100) DEFAULT NULL AFTER `presentation`;

