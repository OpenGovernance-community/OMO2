-- @migration
ALTER TABLE `holon_permission`
    DROP INDEX `uniq_holon_permission`,
    ADD UNIQUE KEY `uniq_holon_permission_range` (`IDholon`, `IDpermission`, `range`);
