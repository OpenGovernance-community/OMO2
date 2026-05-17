-- @migration
ALTER TABLE `user`
    ADD COLUMN IF NOT EXISTS `presentation` text DEFAULT NULL AFTER `lastname`,
    ADD COLUMN IF NOT EXISTS `birthdate` date DEFAULT NULL AFTER `presentation`;

ALTER TABLE `user_organization`
    ADD COLUMN IF NOT EXISTS `presentation` text DEFAULT NULL AFTER `email`;
