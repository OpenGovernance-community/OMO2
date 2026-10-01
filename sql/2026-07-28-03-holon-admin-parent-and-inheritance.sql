-- @migration
ALTER TABLE `holon`
    ADD COLUMN `adminparent` tinyint(1) NOT NULL DEFAULT 0 AFTER `link`,
    MODIFY COLUMN `admin_min` int(11) DEFAULT NULL;

UPDATE `holon`
SET `admin_min` = NULL
WHERE `IDholon_template` IS NOT NULL
  AND `admin_min` = 0
  AND `adminminoverride` = 0;
