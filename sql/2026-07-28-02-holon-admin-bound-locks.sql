-- @migration
ALTER TABLE `holon`
    ADD COLUMN `lockedadminmin` tinyint(1) NOT NULL DEFAULT 0 AFTER `admin_min`,
    ADD COLUMN `adminminoverride` tinyint(1) NOT NULL DEFAULT 0 AFTER `lockedadminmin`,
    ADD COLUMN `lockedadminmax` tinyint(1) NOT NULL DEFAULT 0 AFTER `admin_max`,
    ADD COLUMN `adminmaxoverride` tinyint(1) NOT NULL DEFAULT 0 AFTER `lockedadminmax`;
