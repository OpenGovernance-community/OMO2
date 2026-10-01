-- @migration
ALTER TABLE `holon`
    ADD COLUMN `admin_min` int(11) NOT NULL DEFAULT 0 AFTER `link`,
    ADD COLUMN `admin_max` int(11) DEFAULT NULL AFTER `admin_min`;
