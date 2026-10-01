-- @migration
ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `spacedeckspaceid` varchar(255) DEFAULT NULL AFTER `ethercalcroomid`;
