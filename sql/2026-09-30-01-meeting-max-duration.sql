-- @migration
ALTER TABLE `meeting_profile`
    ADD COLUMN IF NOT EXISTS `max_duration_minutes` smallint unsigned NOT NULL DEFAULT 60 AFTER `timezone`;
