-- @migration
-- OpenMyOrganization
-- Structure des points de proces verbal pour le module Documents

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `document_pv_point` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdocument` int(11) NOT NULL,
  `title` varchar(80) NOT NULL,
  `IDuser_author` int(11) DEFAULT NULL,
  `IDholon_concerned` int(11) DEFAULT NULL,
  `content` mediumtext DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 1,
  `desired_duration_minutes` int(11) DEFAULT NULL,
  `actual_duration_minutes` int(11) DEFAULT NULL,
  `pointtype` varchar(20) NOT NULL DEFAULT 'information',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_document_pv_point_document` (`IDdocument`),
  KEY `idx_document_pv_point_author` (`IDuser_author`),
  KEY `idx_document_pv_point_holon` (`IDholon_concerned`),
  KEY `idx_document_pv_point_position` (`IDdocument`, `position`),
  KEY `idx_document_pv_point_type` (`pointtype`),
  KEY `idx_document_pv_point_active` (`active`),
  CONSTRAINT `fk_document_pv_point_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_pv_point_holon_concerned` FOREIGN KEY (`IDholon_concerned`) REFERENCES `holon` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `document_pv_point_holon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdocument_pv_point` int(11) NOT NULL,
  `IDholon` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_document_pv_point_holon` (`IDdocument_pv_point`, `IDholon`),
  KEY `idx_document_pv_point_holon_holon` (`IDholon`),
  KEY `idx_document_pv_point_holon_position` (`IDdocument_pv_point`, `position`),
  CONSTRAINT `fk_document_pv_point_holon_point` FOREIGN KEY (`IDdocument_pv_point`) REFERENCES `document_pv_point` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_pv_point_holon_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `document_pv_point_tension` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdocument_pv_point` int(11) NOT NULL,
  `IDtension` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_document_pv_point_tension` (`IDdocument_pv_point`, `IDtension`),
  KEY `idx_document_pv_point_tension_tension` (`IDtension`),
  KEY `idx_document_pv_point_tension_position` (`IDdocument_pv_point`, `position`),
  CONSTRAINT `fk_document_pv_point_tension_point` FOREIGN KEY (`IDdocument_pv_point`) REFERENCES `document_pv_point` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_pv_point_tension_tension` FOREIGN KEY (`IDtension`) REFERENCES `tension` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
