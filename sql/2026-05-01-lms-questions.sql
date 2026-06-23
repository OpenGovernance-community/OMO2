-- @migration
-- LMS quiz storage must stay separate from the FAQ module.
-- This migration now creates dedicated `question*` tables without renaming or
-- altering any `faq*` table.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDhowto` int(11) DEFAULT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `detail` text DEFAULT NULL,
  `displayorder` int(11) DEFAULT 0,
  `isactive` tinyint(1) DEFAULT 1,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_question_displayorder` (`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `question`
  ADD COLUMN IF NOT EXISTS `IDhowto` int(11) DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `question` varchar(255) NOT NULL AFTER `IDhowto`,
  ADD COLUMN IF NOT EXISTS `answer` text DEFAULT NULL AFTER `question`,
  ADD COLUMN IF NOT EXISTS `detail` text DEFAULT NULL AFTER `answer`,
  ADD COLUMN IF NOT EXISTS `displayorder` int(11) DEFAULT 0 AFTER `detail`,
  ADD COLUMN IF NOT EXISTS `isactive` tinyint(1) DEFAULT 1 AFTER `displayorder`,
  ADD COLUMN IF NOT EXISTS `created` datetime NOT NULL DEFAULT current_timestamp() AFTER `isactive`,
  ADD COLUMN IF NOT EXISTS `updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created`;

ALTER TABLE `question`
  ADD KEY IF NOT EXISTS `idx_question_displayorder` (`displayorder`);

CREATE TABLE IF NOT EXISTS `question_choice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDquestion` int(11) DEFAULT NULL,
  `label` mediumtext DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_question_choice_question` (`IDquestion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `question_choice`
  ADD COLUMN IF NOT EXISTS `IDquestion` int(11) DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `label` mediumtext DEFAULT NULL AFTER `IDquestion`,
  ADD COLUMN IF NOT EXISTS `is_correct` tinyint(1) DEFAULT 0 AFTER `label`;

ALTER TABLE `question_choice`
  ADD KEY IF NOT EXISTS `idx_question_choice_question` (`IDquestion`);

CREATE TABLE IF NOT EXISTS `mission_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDmission` int(11) DEFAULT NULL,
  `IDquestion` int(11) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mission_question` (`IDmission`, `IDquestion`),
  KEY `idx_mission_question_position` (`IDmission`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `mission_question`
  ADD COLUMN IF NOT EXISTS `IDmission` int(11) DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `IDquestion` int(11) DEFAULT NULL AFTER `IDmission`,
  ADD COLUMN IF NOT EXISTS `position` int(11) DEFAULT NULL AFTER `IDquestion`;

ALTER TABLE `mission_question`
  ADD UNIQUE KEY IF NOT EXISTS `uniq_mission_question` (`IDmission`, `IDquestion`),
  ADD KEY IF NOT EXISTS `idx_mission_question_position` (`IDmission`, `position`);

CREATE TABLE IF NOT EXISTS `user_question_response` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) DEFAULT NULL,
  `IDquestion` int(11) DEFAULT NULL,
  `IDchoice` int(11) DEFAULT NULL,
  `IDmission` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_question_response_lookup` (`IDuser`, `IDmission`, `IDquestion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `user_question_response`
  ADD COLUMN IF NOT EXISTS `IDuser` int(11) DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `IDquestion` int(11) DEFAULT NULL AFTER `IDuser`,
  ADD COLUMN IF NOT EXISTS `IDchoice` int(11) DEFAULT NULL AFTER `IDquestion`,
  ADD COLUMN IF NOT EXISTS `IDmission` int(11) DEFAULT NULL AFTER `IDchoice`,
  ADD COLUMN IF NOT EXISTS `created_at` datetime DEFAULT current_timestamp() AFTER `IDmission`;

ALTER TABLE `user_question_response`
  ADD KEY IF NOT EXISTS `idx_user_question_response_lookup` (`IDuser`, `IDmission`, `IDquestion`);
