-- @migration
-- Scoped ICS subscriptions used by Google Calendar and similar readers.

SET NAMES utf8mb4;

ALTER TABLE `calendar_share`
    ADD COLUMN IF NOT EXISTS `scope_key` varchar(100) DEFAULT NULL AFTER `token`,
    ADD UNIQUE KEY IF NOT EXISTS `calendar_share_owner_scope` (`IDuser`, `scope_key`);
