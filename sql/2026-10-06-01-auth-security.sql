-- @migration
ALTER TABLE `user` MODIFY COLUMN `code` VARCHAR(64) NULL;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `security_version` INT UNSIGNED NOT NULL DEFAULT 0;
-- Old recovery links are invalidated; persistent login cookies remain valid via their hashes.
UPDATE `user` SET `code` = NULL, `codeexpiration` = NULL;
UPDATE `user_remember` SET `token` = SHA2(`token`, 256);
