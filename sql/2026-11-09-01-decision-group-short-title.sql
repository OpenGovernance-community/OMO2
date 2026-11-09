-- @migration
ALTER TABLE `decision_group`
    ADD COLUMN IF NOT EXISTS `short_title` varchar(190) DEFAULT NULL AFTER `title`;
