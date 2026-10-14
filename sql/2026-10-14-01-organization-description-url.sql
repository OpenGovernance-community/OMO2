-- @migration
ALTER TABLE `organization`
    ADD COLUMN IF NOT EXISTS `description` text DEFAULT NULL AFTER `name`,
    ADD COLUMN IF NOT EXISTS `url` varchar(2048) DEFAULT NULL AFTER `description`;
