-- @migration

ALTER TABLE `organization_parcours`
  ADD COLUMN IF NOT EXISTS `anonymous` TINYINT(1) NOT NULL DEFAULT 0 AFTER `everybody`;
