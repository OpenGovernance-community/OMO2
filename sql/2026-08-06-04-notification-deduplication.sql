-- @migration
-- Avoid repeated unread notifications for the same grouped event.

ALTER TABLE `notification`
    ADD COLUMN `dedupe_key` varchar(190) DEFAULT NULL AFTER `source_key`,
    ADD KEY `idx_notification_unread_dedupe` (`IDuser`, `IDorganization`, `dedupe_key`, `read_at`);
