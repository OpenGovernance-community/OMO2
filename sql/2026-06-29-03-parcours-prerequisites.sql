-- @migration

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `parcours_prerequisite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDparcours` int(11) NOT NULL,
  `IDparcours_required` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_parcours_prerequisite` (`IDparcours`, `IDparcours_required`),
  KEY `idx_parcours_prerequisite_target` (`IDparcours`),
  KEY `idx_parcours_prerequisite_required` (`IDparcours_required`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `parcours_prerequisite`
  ADD COLUMN IF NOT EXISTS `IDparcours` int(11) NOT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `IDparcours_required` int(11) NOT NULL AFTER `IDparcours`;

ALTER TABLE `parcours_prerequisite`
  ADD UNIQUE KEY IF NOT EXISTS `uniq_parcours_prerequisite` (`IDparcours`, `IDparcours_required`),
  ADD KEY IF NOT EXISTS `idx_parcours_prerequisite_target` (`IDparcours`),
  ADD KEY IF NOT EXISTS `idx_parcours_prerequisite_required` (`IDparcours_required`);
