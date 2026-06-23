-- @migration

SET NAMES utf8mb4;

ALTER TABLE `organization_parcours`
  ADD COLUMN IF NOT EXISTS `anonymous` tinyint(1) NOT NULL DEFAULT 0 AFTER `everybody`;
