-- @migration

ALTER TABLE `user_login_token`
  ADD COLUMN IF NOT EXISTS `code_hash` varchar(255) DEFAULT NULL AFTER `token`,
  ADD COLUMN IF NOT EXISTS `request_ip` varchar(45) DEFAULT NULL AFTER `expires_at`,
  ADD COLUMN IF NOT EXISTS `attempt_count` int(11) NOT NULL DEFAULT 0 AFTER `request_ip`,
  ADD COLUMN IF NOT EXISTS `created_at` datetime DEFAULT NULL AFTER `remember`,
  ADD COLUMN IF NOT EXISTS `last_attempt_at` datetime DEFAULT NULL AFTER `created_at`;
