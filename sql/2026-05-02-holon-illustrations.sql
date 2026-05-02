-- @migration
ALTER TABLE `holon`
  ADD COLUMN IF NOT EXISTS `icon` varchar(255) DEFAULT NULL AFTER `color`,
  ADD COLUMN IF NOT EXISTS `banner` varchar(255) DEFAULT NULL AFTER `icon`,
  ADD COLUMN IF NOT EXISTS `lockedicon` tinyint(1) NOT NULL DEFAULT 0 AFTER `lockedname`,
  ADD COLUMN IF NOT EXISTS `lockedbanner` tinyint(1) NOT NULL DEFAULT 0 AFTER `lockedicon`;
