-- @migration
-- OpenMyOrganization
-- Rattachement optionnel des entrees d'historique au cercle englobant

SET NAMES utf8mb4;

ALTER TABLE `history`
  ADD COLUMN IF NOT EXISTS `IDholon_circle` int(11) DEFAULT NULL AFTER `IDuser`,
  ADD KEY IF NOT EXISTS `idx_history_holon_circle` (`IDholon_circle`);
