-- @migration
ALTER TABLE `holon_permission`
    DROP INDEX `uniq_holon_permission_range`,
    ADD COLUMN `member_type` varchar(20) NOT NULL DEFAULT 'member' AFTER `IDpermission`,
    ADD UNIQUE KEY `uniq_holon_permission_profile_range` (`IDholon`, `IDpermission`, `member_type`, `range`);
