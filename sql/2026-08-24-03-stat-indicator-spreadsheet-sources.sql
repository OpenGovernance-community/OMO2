-- @migration
-- Spreadsheet-backed indicator sources for uploaded and Collabora documents.

ALTER TABLE `stat_indicator`
    ADD COLUMN IF NOT EXISTS `spreadsheet_sheet` varchar(190) DEFAULT NULL AFTER `ethercalc_last_sync_at`,
    ADD COLUMN IF NOT EXISTS `spreadsheet_cell` varchar(20) DEFAULT NULL AFTER `spreadsheet_sheet`,
    ADD COLUMN IF NOT EXISTS `spreadsheet_frequency` varchar(20) DEFAULT NULL AFTER `spreadsheet_cell`,
    ADD COLUMN IF NOT EXISTS `spreadsheet_range` varchar(40) DEFAULT NULL AFTER `spreadsheet_frequency`,
    ADD COLUMN IF NOT EXISTS `spreadsheet_date_column` varchar(10) DEFAULT NULL AFTER `spreadsheet_range`,
    ADD COLUMN IF NOT EXISTS `spreadsheet_value_column` varchar(10) DEFAULT NULL AFTER `spreadsheet_date_column`,
    ADD COLUMN IF NOT EXISTS `spreadsheet_last_sync_at` datetime DEFAULT NULL AFTER `spreadsheet_value_column`,
    ADD KEY IF NOT EXISTS `idx_stat_indicator_spreadsheet_sync` (`source_type`, `active`, `spreadsheet_last_sync_at`);
