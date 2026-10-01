-- @migration
ALTER TABLE `user`
    ADD COLUMN IF NOT EXISTS `phone` varchar(50) DEFAULT NULL AFTER `email`;

ALTER TABLE `user_organization`
    ADD COLUMN IF NOT EXISTS `phone` varchar(50) DEFAULT NULL AFTER `email`;
