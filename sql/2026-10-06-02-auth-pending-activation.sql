-- @migration
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS activation_pending TINYINT(1) NOT NULL DEFAULT 0;
