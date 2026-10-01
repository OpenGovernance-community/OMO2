-- @migration

-- Niveau global de complexite de l interface par organisation.
ALTER TABLE `organization`
  ADD COLUMN IF NOT EXISTS `interface_level` tinyint(1) unsigned NOT NULL DEFAULT 1 AFTER `parameters`;

