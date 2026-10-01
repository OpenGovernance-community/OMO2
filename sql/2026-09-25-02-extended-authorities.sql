-- @migration
ALTER TABLE `holon_permission`
    ADD COLUMN IF NOT EXISTS `is_extended` tinyint(1) NOT NULL DEFAULT 0 AFTER `member_type`;
