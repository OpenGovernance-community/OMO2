-- @migration
ALTER TABLE `authority`
    ADD COLUMN `description` mediumtext DEFAULT NULL AFTER `label`;
