-- @migration
ALTER TABLE `organizational_maturity_invitation`
  ADD COLUMN IF NOT EXISTS `public_access` tinyint(1) NOT NULL DEFAULT 0 AFTER `token`,
  ADD COLUMN IF NOT EXISTS `access_code_hash` varchar(255) DEFAULT NULL AFTER `public_access`,
  ADD COLUMN IF NOT EXISTS `access_code_expires_at` datetime DEFAULT NULL AFTER `access_code_hash`;
