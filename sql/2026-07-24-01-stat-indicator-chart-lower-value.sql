-- @migration
-- OpenMyOrganization
-- Optional lower chart value for steering indicators

SET NAMES utf8mb4;

ALTER TABLE `stat_indicator`
  ADD COLUMN IF NOT EXISTS `chart_min_value` decimal(20,6) DEFAULT NULL AFTER `measurement_schedule`;
