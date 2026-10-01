-- @migration
-- EtherCalc-backed indicator source configuration.

ALTER TABLE `stat_indicator`
    ADD COLUMN IF NOT EXISTS `source_type` varchar(30) NOT NULL DEFAULT 'manual' AFTER `source_url`,
    ADD COLUMN IF NOT EXISTS `IDdocument` int(11) DEFAULT NULL AFTER `IDuser`,
    ADD COLUMN IF NOT EXISTS `ethercalc_cell` varchar(20) DEFAULT NULL AFTER `source_type`,
    ADD COLUMN IF NOT EXISTS `ethercalc_frequency` varchar(20) DEFAULT NULL AFTER `ethercalc_cell`,
    ADD COLUMN IF NOT EXISTS `ethercalc_range` varchar(40) DEFAULT NULL AFTER `ethercalc_frequency`,
    ADD COLUMN IF NOT EXISTS `ethercalc_date_column` varchar(10) DEFAULT NULL AFTER `ethercalc_range`,
    ADD COLUMN IF NOT EXISTS `ethercalc_value_column` varchar(10) DEFAULT NULL AFTER `ethercalc_date_column`,
    ADD COLUMN IF NOT EXISTS `ethercalc_last_sync_at` datetime DEFAULT NULL AFTER `ethercalc_value_column`,
    ADD KEY `idx_stat_indicator_source_sync` (`source_type`, `active`, `ethercalc_last_sync_at`),
    ADD KEY `idx_stat_indicator_document` (`IDdocument`),
    ADD CONSTRAINT `fk_stat_indicator_document`
        FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE SET NULL;
