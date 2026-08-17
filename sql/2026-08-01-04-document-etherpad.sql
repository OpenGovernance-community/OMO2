-- @migration

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `etherpadpadid` varchar(255) DEFAULT NULL AFTER `storedfilesize`;
