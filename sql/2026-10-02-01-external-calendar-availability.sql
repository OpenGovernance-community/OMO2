-- @migration
ALTER TABLE `external_calendar` ADD COLUMN IF NOT EXISTS `availability_only` tinyint(1) NOT NULL DEFAULT 0;
