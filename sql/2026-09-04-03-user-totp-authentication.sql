-- @migration
ALTER TABLE `user`
    ADD COLUMN IF NOT EXISTS `totp_enabled` tinyint(1) NOT NULL DEFAULT 0 AFTER `allow_password_login`,
    ADD COLUMN IF NOT EXISTS `totp_secret` varchar(255) DEFAULT NULL AFTER `totp_enabled`;

ALTER TABLE `user_login_token`
    ADD COLUMN IF NOT EXISTS `mfa_pending` tinyint(1) NOT NULL DEFAULT 0 AFTER `remember`,
    ADD COLUMN IF NOT EXISTS `mfa_attempt_count` int(11) NOT NULL DEFAULT 0 AFTER `mfa_pending`;
