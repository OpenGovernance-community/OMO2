-- @migration
ALTER TABLE `work_time`
    ADD COLUMN IF NOT EXISTS `label` varchar(1000) DEFAULT NULL AFTER `IDproject`;
