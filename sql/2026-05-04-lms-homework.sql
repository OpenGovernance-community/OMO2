-- @migration
-- LMS homework support

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `homework` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `detail` text DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateupdate` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_homework_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mission_homework` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDmission` int(11) NOT NULL,
  `IDhomework` int(11) NOT NULL,
  `position` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mission_homework` (`IDmission`, `IDhomework`),
  KEY `idx_mission_homework_mission` (`IDmission`),
  KEY `idx_mission_homework_homework` (`IDhomework`),
  KEY `idx_mission_homework_position` (`IDmission`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_homework` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `IDmission` int(11) NOT NULL,
  `IDhomework` int(11) NOT NULL,
  `IDparcours` int(11) NOT NULL,
  `done` datetime DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateupdate` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_homework` (`IDuser`, `IDmission`, `IDhomework`, `IDparcours`),
  KEY `idx_user_homework_user` (`IDuser`),
  KEY `idx_user_homework_mission` (`IDmission`),
  KEY `idx_user_homework_homework` (`IDhomework`),
  KEY `idx_user_homework_parcours` (`IDparcours`),
  KEY `idx_user_homework_done` (`done`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
