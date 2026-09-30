-- @migration
ALTER TABLE `meeting_profile`
    ADD COLUMN IF NOT EXISTS `meeting_methods` text DEFAULT NULL AFTER `max_duration_minutes`;
ALTER TABLE `meeting_booking`
    ADD COLUMN IF NOT EXISTS `meeting_method` text DEFAULT NULL AFTER `reason`;
