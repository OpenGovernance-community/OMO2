-- @migration
-- OpenMyOrganization
-- Optional cumulative display for steering indicators

SET NAMES utf8mb4;

ALTER TABLE `stat_indicator`
    ADD COLUMN IF NOT EXISTS `show_cumulative` tinyint(1) NOT NULL DEFAULT 0 AFTER `chart_min_value`;
