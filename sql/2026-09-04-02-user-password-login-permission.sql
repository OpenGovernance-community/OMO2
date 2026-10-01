-- @migration
ALTER TABLE `user`
    ADD COLUMN IF NOT EXISTS `allow_password_login` tinyint(1) NOT NULL DEFAULT 0 AFTER `password`;
