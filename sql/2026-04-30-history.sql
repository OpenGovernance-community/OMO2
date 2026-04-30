-- @migration
-- OpenMyOrganization
-- Table d'historique générique des actions

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) DEFAULT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `content` mediumtext NOT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_history_organization` (`IDorganization`),
  KEY `idx_history_user` (`IDuser`),
  KEY `idx_history_action` (`action`),
  KEY `idx_history_datecreation` (`datecreation`),
  FULLTEXT KEY `ft_history_content` (`content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
