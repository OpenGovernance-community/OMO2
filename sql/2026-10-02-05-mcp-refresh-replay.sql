-- @migration
-- Remember consumed refresh-token hashes to revoke a grant on token replay.
ALTER TABLE `mcp_oauth_grant` ADD COLUMN IF NOT EXISTS `used_refresh_hashes` mediumtext DEFAULT NULL;
