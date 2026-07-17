-- @migration
ALTER TABLE `holonproperty`
  MODIFY COLUMN `value` mediumtext DEFAULT NULL;
