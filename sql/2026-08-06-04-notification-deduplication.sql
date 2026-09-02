-- @migration
-- Avoid repeated unread notifications for the same grouped event.

ALTER TABLE `notification`
    ADD COLUMN IF NOT EXISTS `dedupe_key` varchar(190) DEFAULT NULL AFTER `source_key`;

ALTER TABLE `notification`
    ADD KEY IF NOT EXISTS `idx_notification_unread_dedupe` (`IDuser`, `IDorganization`, `dedupe_key`, `read_at`);
