-- @migration
ALTER TABLE `document`
    ADD COLUMN IF NOT EXISTS `ethercalcroomid` varchar(255) DEFAULT NULL AFTER `etherpadpadid`;
