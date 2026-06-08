-- @migration

SET NAMES utf8mb4;

ALTER TABLE `organization`
  ADD COLUMN IF NOT EXISTS `parameters` mediumtext DEFAULT NULL AFTER `color`;
