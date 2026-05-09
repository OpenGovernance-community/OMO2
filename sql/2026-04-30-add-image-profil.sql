-- @migration

ALTER TABLE `user_organization`
  ADD COLUMN IF NOT EXISTS `image` varchar(100) DEFAULT NULL AFTER `username`;
ALTER TABLE `user`
  ADD COLUMN IF NOT EXISTS `image` varchar(100) DEFAULT NULL AFTER `username`;
