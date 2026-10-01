-- @migration
ALTER TABLE `user_holon`
  ADD COLUMN IF NOT EXISTS `is_membership` tinyint(1) NOT NULL DEFAULT 1 AFTER `active`;

UPDATE `user_holon`
SET `is_membership` = 1
WHERE `is_membership` IS NULL;
