-- @migration
-- OpenMyOrganization
-- Select the axis used by an indicator reference in cumulative charts

SET NAMES utf8mb4;

ALTER TABLE `stat_indicator`
    ADD COLUMN IF NOT EXISTS `reference_scale` varchar(20) NOT NULL DEFAULT 'cumulative' AFTER `reference_type`;
