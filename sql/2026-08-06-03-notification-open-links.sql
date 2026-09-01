-- @migration
-- Add opaque links used to open and mark a notification as read.

ALTER TABLE `notification`
    ADD COLUMN IF NOT EXISTS `open_token` char(64) DEFAULT NULL AFTER `url`;

ALTER TABLE `notification`
    ADD UNIQUE KEY IF NOT EXISTS `uniq_notification_open_token` (`open_token`);
