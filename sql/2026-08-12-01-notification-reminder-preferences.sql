-- @migration
-- Store selected reminder days for notification preferences.

ALTER TABLE `notification_preference`
    ADD COLUMN `parameters` text DEFAULT NULL AFTER `channel_email`;
