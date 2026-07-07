-- @migration

SET NAMES utf8mb4;

ALTER TABLE `parcours`
  ADD COLUMN IF NOT EXISTS `ispack` tinyint(1) NOT NULL DEFAULT 0 AFTER `isbasic`;

CREATE TABLE IF NOT EXISTS `parcours_parcours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDparcours_parent` int(11) NOT NULL,
  `IDparcours_child` int(11) NOT NULL,
  `position` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_parcours_pack_child` (`IDparcours_parent`, `IDparcours_child`),
  KEY `idx_parcours_pack_parent_position` (`IDparcours_parent`, `position`),
  KEY `idx_parcours_pack_child` (`IDparcours_child`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `parcours_parcours`
  ADD COLUMN IF NOT EXISTS `IDparcours_parent` int(11) NOT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `IDparcours_child` int(11) NOT NULL AFTER `IDparcours_parent`,
  ADD COLUMN IF NOT EXISTS `position` int(11) DEFAULT NULL AFTER `IDparcours_child`;

ALTER TABLE `parcours_parcours`
  ADD UNIQUE KEY IF NOT EXISTS `uniq_parcours_pack_child` (`IDparcours_parent`, `IDparcours_child`),
  ADD KEY IF NOT EXISTS `idx_parcours_pack_parent_position` (`IDparcours_parent`, `position`),
  ADD KEY IF NOT EXISTS `idx_parcours_pack_child` (`IDparcours_child`);
