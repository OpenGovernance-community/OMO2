-- @migration
-- Reference curves shared by indicators and combined steering indicator groups

SET NAMES utf8mb4;

ALTER TABLE `stat_indicator_group`
    ADD COLUMN IF NOT EXISTS `reference_type` varchar(20) NOT NULL DEFAULT 'none' AFTER `display_mode`;

ALTER TABLE `stat_indicator_reference_point`
    MODIFY COLUMN `IDstatindicator` int(11) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `IDstatindicatorgroup` int(11) DEFAULT NULL AFTER `IDstatindicator`,
    ADD UNIQUE KEY IF NOT EXISTS `uniq_stat_indicator_reference_group_position` (`IDstatindicatorgroup`, `position_percent`),
    ADD KEY IF NOT EXISTS `idx_stat_indicator_reference_group` (`IDstatindicatorgroup`),
    ADD CONSTRAINT `fk_stat_indicator_reference_point_group`
        FOREIGN KEY (`IDstatindicatorgroup`) REFERENCES `stat_indicator_group` (`id`) ON DELETE CASCADE;
