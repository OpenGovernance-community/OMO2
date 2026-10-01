-- @migration
-- Allow source indicators to remain available to groups without appearing in the catalogue.

ALTER TABLE `stat_indicator`
    ADD COLUMN IF NOT EXISTS `hide_from_catalog` tinyint(1) NOT NULL DEFAULT 0 AFTER `show_cumulative`,
    ADD KEY `idx_stat_indicator_catalog_visibility` (`active`, `hide_from_catalog`);
