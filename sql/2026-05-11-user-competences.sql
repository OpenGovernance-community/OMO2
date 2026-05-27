-- @migration

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `competence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) DEFAULT NULL,
  `name` varchar(190) NOT NULL,
  `normalized_name` varchar(190) NOT NULL,
  `category` varchar(30) NOT NULL DEFAULT 'technical',
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_competence_scope_name` (`IDorganization`, `normalized_name`),
  KEY `idx_competence_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_competence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `IDcompetence` int(11) NOT NULL,
  `IDorganization` int(11) DEFAULT NULL,
  `level` tinyint(4) NOT NULL DEFAULT 1,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_competence_user_scope` (`IDuser`, `IDorganization`),
  KEY `idx_user_competence_competence` (`IDcompetence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_competence_validation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser_competence` int(11) NOT NULL,
  `IDvalidator_user` int(11) NOT NULL,
  `IDorganization` int(11) NOT NULL,
  `level` tinyint(4) NOT NULL DEFAULT 1,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_competence_validation` (`IDuser_competence`, `IDvalidator_user`, `IDorganization`),
  KEY `idx_user_competence_validation_org` (`IDorganization`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
