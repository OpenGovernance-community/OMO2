-- @migration
-- OpenMyOrganization
-- Tensions de gouvernance partagee

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `tension` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDuser` int(11) NOT NULL,
  `title` varchar(80) NOT NULL,
  `description` text NOT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_tension_organization` (`IDorganization`),
  KEY `idx_tension_holon` (`IDholon`),
  KEY `idx_tension_user` (`IDuser`),
  KEY `idx_tension_creation` (`datecreation`),
  KEY `idx_tension_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
