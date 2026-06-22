-- @migration

SET NAMES utf8mb4;

ALTER TABLE `organization`
  ADD COLUMN IF NOT EXISTS `latlong` varchar(100) DEFAULT NULL AFTER `color`;
