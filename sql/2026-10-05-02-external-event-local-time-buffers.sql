-- @migration
ALTER TABLE `external_calendar_event`
    ADD COLUMN IF NOT EXISTS `time_buffers_local` TINYINT(1) NOT NULL DEFAULT 0;
