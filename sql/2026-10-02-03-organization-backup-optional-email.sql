-- @migration
-- dbObject stores an empty optional string as NULL.
ALTER TABLE `organization_backup`
  MODIFY COLUMN `email` varchar(254) NULL DEFAULT NULL;
