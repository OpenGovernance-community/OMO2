-- @migration
-- dbObject stores empty optional HTML fields as NULL.
ALTER TABLE `valeur` MODIFY COLUMN `principle` mediumtext DEFAULT NULL;
