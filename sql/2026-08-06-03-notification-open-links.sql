-- @migration
-- Add opaque links used to open and mark a notification as read.

ALTER TABLE `notification`
    ADD COLUMN `open_token` char(64) DEFAULT NULL AFTER `url`,
    ADD UNIQUE KEY `uniq_notification_open_token` (`open_token`);
