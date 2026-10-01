-- @migration
-- OpenMyOrganization
-- Expected measurement frequency for steering indicators

SET NAMES utf8mb4;

ALTER TABLE `stat_indicator`
  ADD COLUMN IF NOT EXISTS `measurement_frequency` varchar(20) DEFAULT NULL AFTER `reference_type`,
  ADD COLUMN IF NOT EXISTS `measurement_schedule` varchar(20) DEFAULT NULL AFTER `measurement_frequency`,
  ADD KEY IF NOT EXISTS `idx_stat_indicator_measurement_frequency` (`measurement_frequency`);
