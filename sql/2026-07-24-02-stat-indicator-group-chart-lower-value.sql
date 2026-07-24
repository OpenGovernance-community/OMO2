-- @migration
-- OpenMyOrganization
-- Optional lower chart value for steering indicator groups

SET NAMES utf8mb4;

ALTER TABLE `stat_indicator_group`
    ADD COLUMN IF NOT EXISTS `chart_min_value` decimal(20,6) DEFAULT NULL AFTER `reference_type`;
