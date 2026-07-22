-- Docker post-seed updates for schema and seed data missing from 00-base.seed.sql.
-- Keep this file intentionally minimal and idempotent.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `project` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDorganization` int(11) NOT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `IDproject_parent` int(11) DEFAULT NULL,
    `IDdocument_journal` int(11) DEFAULT NULL,
    `title` varchar(255) NOT NULL,
    `description` mediumtext DEFAULT NULL,
    `status` varchar(20) NOT NULL DEFAULT 'someday',
    `planned_start_date` date DEFAULT NULL,
    `planned_end_date` date DEFAULT NULL,
    `priority` tinyint(3) DEFAULT NULL,
    `importance` tinyint(3) DEFAULT NULL,
    `project_size` varchar(3) NOT NULL DEFAULT 'M',
    `capture_mode` varchar(30) NOT NULL DEFAULT 'multiple_documents',
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_project_organization` (`IDorganization`),
    KEY `idx_project_holon` (`IDholon`),
    KEY `idx_project_user` (`IDuser`),
    KEY `idx_project_parent` (`IDproject_parent`),
    KEY `idx_project_journal` (`IDdocument_journal`),
    KEY `idx_project_status` (`status`),
    KEY `idx_project_active` (`active`),
    CONSTRAINT `fk_project_organization`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_project_holon`
        FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_project_user`
        FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_project_parent`
        FOREIGN KEY (`IDproject_parent`) REFERENCES `project` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_project_journal`
        FOREIGN KEY (`IDdocument_journal`) REFERENCES `document` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_user` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDproject` int(11) NOT NULL,
    `IDuser` int(11) NOT NULL,
    `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
    `active` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_project_user` (`IDproject`, `IDuser`),
    KEY `idx_project_user_project` (`IDproject`),
    KEY `idx_project_user_user` (`IDuser`),
    KEY `idx_project_user_active` (`active`),
    CONSTRAINT `fk_project_user_project`
        FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_project_user_user`
        FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_document` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDproject` int(11) NOT NULL,
    `IDdocument` int(11) NOT NULL,
    `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_project_document` (`IDproject`, `IDdocument`),
    KEY `idx_project_document_project` (`IDproject`),
    KEY `idx_project_document_document` (`IDdocument`),
    CONSTRAINT `fk_project_document_project`
        FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_project_document_document`
        FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `organization`
  ADD COLUMN IF NOT EXISTS `latlong` varchar(100) DEFAULT NULL AFTER `color`,
  ADD COLUMN IF NOT EXISTS `parameters` mediumtext DEFAULT NULL AFTER `latlong`;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `pvstage` varchar(30) DEFAULT NULL AFTER `documenttype`,
  ADD COLUMN IF NOT EXISTS `IDuser_pv_editor` int(11) DEFAULT NULL AFTER `IDuseredition`,
  ADD KEY IF NOT EXISTS `idx_document_pvstage` (`pvstage`),
  ADD KEY IF NOT EXISTS `idx_document_pv_editor` (`IDuser_pv_editor`);

UPDATE `document`
SET `pvstage` = 'preparation'
WHERE `documenttype` = 'pv'
  AND (`pvstage` IS NULL OR TRIM(`pvstage`) = '');

SET @document_pv_point_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'document_pv_point'
);

SET @document_pv_point_handled_sql := IF(
  @document_pv_point_exists = 1,
  'ALTER TABLE `document_pv_point` ADD COLUMN IF NOT EXISTS `is_handled` tinyint(1) NOT NULL DEFAULT 0 AFTER `pointtype`',
  'SELECT 1'
);

PREPARE stmt FROM @document_pv_point_handled_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @document_pv_point_sync_sql := IF(
  @document_pv_point_exists = 1,
  'ALTER TABLE `document_pv_point`
    ADD COLUMN IF NOT EXISTS `IDuser_modification` int(11) DEFAULT NULL AFTER `IDuser_author`,
    ADD COLUMN IF NOT EXISTS `author_email` varchar(250) DEFAULT NULL AFTER `IDuser_author`,
    ADD COLUMN IF NOT EXISTS `IDuser_editing` int(11) DEFAULT NULL AFTER `IDuser_modification`,
    ADD COLUMN IF NOT EXISTS `edit_lock_token` varchar(80) DEFAULT NULL AFTER `IDuser_editing`,
    ADD COLUMN IF NOT EXISTS `dateedition` datetime DEFAULT NULL AFTER `datemodification`,
    ADD KEY IF NOT EXISTS `idx_document_pv_point_modification_user` (`IDuser_modification`),
    ADD KEY IF NOT EXISTS `idx_document_pv_point_author_email` (`author_email`),
    ADD KEY IF NOT EXISTS `idx_document_pv_point_editing_user` (`IDuser_editing`),
    ADD KEY IF NOT EXISTS `idx_document_pv_point_dateedition` (`dateedition`)',
  'SELECT 1'
);

PREPARE stmt FROM @document_pv_point_sync_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_document_pv_point_modification_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_pv_point_modification_user'
);

SET @fk_document_pv_point_editing_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_pv_point_editing_user'
);

SET @fk_document_pv_point_modification_sql := IF(
  @document_pv_point_exists = 1 AND @fk_document_pv_point_modification_exists = 0,
  'ALTER TABLE `document_pv_point` ADD CONSTRAINT `fk_document_pv_point_modification_user` FOREIGN KEY (`IDuser_modification`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

SET @fk_document_pv_point_editing_sql := IF(
  @document_pv_point_exists = 1 AND @fk_document_pv_point_editing_exists = 0,
  'ALTER TABLE `document_pv_point` ADD CONSTRAINT `fk_document_pv_point_editing_user` FOREIGN KEY (`IDuser_editing`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_pv_point_modification_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

PREPARE stmt FROM @fk_document_pv_point_editing_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Translation bundle storage
CREATE TABLE IF NOT EXISTS `translation_bundles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bundle_key` varchar(190) NOT NULL,
    `locale` varchar(10) NOT NULL,
    `source_hash` char(64) NOT NULL,
    `translated_json` longtext NOT NULL,
    `status` enum('machine_translated', 'approved', 'outdated') NOT NULL DEFAULT 'machine_translated',
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_bundle_locale` (`bundle_key`, `locale`),
    KEY `idx_bundle_locale_hash` (`bundle_key`, `locale`, `source_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `translation_bundle_refresh_jobs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bundle_key` varchar(190) NOT NULL,
    `locale` varchar(10) NOT NULL,
    `source_hash` char(64) NOT NULL,
    `source_json` longtext NOT NULL,
    `status` enum('pending', 'running', 'failed', 'completed') NOT NULL DEFAULT 'pending',
    `attempts` int(11) NOT NULL DEFAULT 0,
    `last_error` longtext DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `started_at` datetime DEFAULT NULL,
    `finished_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_bundle_locale_hash` (`bundle_key`, `locale`, `source_hash`),
    KEY `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `translation_languages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `locale` varchar(10) NOT NULL,
    `name` varchar(120) NOT NULL,
    `native_name` varchar(120) NOT NULL,
    `sort_order` int(11) NOT NULL DEFAULT 100,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `is_source` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_translation_language_locale` (`locale`),
    KEY `idx_translation_language_active_order` (`active`, `is_source`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `search_job` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `jobtype` varchar(40) NOT NULL DEFAULT 'topbar_search',
    `status` varchar(20) NOT NULL DEFAULT 'queued',
    `query` text NOT NULL,
    `scopesjson` mediumtext DEFAULT NULL,
    `timerangejson` mediumtext DEFAULT NULL,
    `viewercontextjson` mediumtext DEFAULT NULL,
    `resultjson` longtext DEFAULT NULL,
    `errormessage` text DEFAULT NULL,
    `requesttoken` varchar(80) NOT NULL,
    `IDorganization` int(11) NOT NULL,
    `currentholonid` int(11) DEFAULT NULL,
    `viewertype` varchar(20) NOT NULL DEFAULT 'user',
    `viewerref` int(11) DEFAULT NULL,
    `attempts` int(11) NOT NULL DEFAULT 0,
    `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
    `datemodification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `datestarted` datetime DEFAULT NULL,
    `datefinished` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_search_job_requesttoken` (`requesttoken`),
    KEY `idx_search_job_status` (`status`),
    KEY `idx_search_job_org_status` (`IDorganization`, `status`),
    KEY `idx_search_job_creation` (`datecreation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `faq`
  ADD COLUMN IF NOT EXISTS `image` varchar(1000) DEFAULT NULL AFTER `answer`,
  ADD COLUMN IF NOT EXISTS `video` varchar(1000) DEFAULT NULL AFTER `image`;

-- LMS quiz storage kept separate from FAQ storage
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

CREATE TABLE IF NOT EXISTS `question_choice` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDquestion` int(11) DEFAULT NULL,
    `label` mediumtext DEFAULT NULL,
    `is_correct` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_question_choice_question` (`IDquestion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mission_question` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDmission` int(11) DEFAULT NULL,
    `IDquestion` int(11) DEFAULT NULL,
    `position` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_mission_question` (`IDmission`, `IDquestion`),
    KEY `idx_mission_question_position` (`IDmission`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

ALTER TABLE `question_choice`
  ADD COLUMN IF NOT EXISTS `IDquestion` int(11) DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `label` mediumtext DEFAULT NULL AFTER `IDquestion`,
  ADD COLUMN IF NOT EXISTS `is_correct` tinyint(1) DEFAULT 0 AFTER `label`;

ALTER TABLE `question_choice`
  ADD KEY IF NOT EXISTS `idx_question_choice_question` (`IDquestion`);

ALTER TABLE `mission_question`
  ADD COLUMN IF NOT EXISTS `IDmission` int(11) DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `IDquestion` int(11) DEFAULT NULL AFTER `IDmission`,
  ADD COLUMN IF NOT EXISTS `position` int(11) DEFAULT NULL AFTER `IDquestion`;

ALTER TABLE `mission_question`
  ADD UNIQUE KEY IF NOT EXISTS `uniq_mission_question` (`IDmission`, `IDquestion`),
  ADD KEY IF NOT EXISTS `idx_mission_question_position` (`IDmission`, `position`);

ALTER TABLE `user_question_response`
  ADD COLUMN IF NOT EXISTS `IDuser` int(11) DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `IDquestion` int(11) DEFAULT NULL AFTER `IDuser`,
  ADD COLUMN IF NOT EXISTS `IDchoice` int(11) DEFAULT NULL AFTER `IDquestion`,
  ADD COLUMN IF NOT EXISTS `IDmission` int(11) DEFAULT NULL AFTER `IDchoice`,
  ADD COLUMN IF NOT EXISTS `created_at` datetime DEFAULT current_timestamp() AFTER `IDmission`;

ALTER TABLE `user_question_response`
  ADD KEY IF NOT EXISTS `idx_user_question_response_lookup` (`IDuser`, `IDmission`, `IDquestion`);

INSERT INTO `translation_languages` (`locale`, `name`, `native_name`, `sort_order`, `active`, `is_source`) VALUES
    ('fr', 'Francais', 'Francais', 10, 1, 1),
    ('en', 'Anglais', 'English', 20, 1, 0),
    ('de', 'Allemand', 'Deutsch', 30, 1, 0),
    ('es', 'Espagnol', 'Espanol', 40, 1, 0),
    ('it', 'Italien', 'Italiano', 50, 1, 0),
    ('pt', 'Portugais', 'Portugues', 60, 1, 0),
    ('nl', 'Neerlandais', 'Nederlands', 70, 1, 0),
    ('pl', 'Polonais', 'Polski', 80, 1, 0)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `native_name` = VALUES(`native_name`),
    `sort_order` = VALUES(`sort_order`),
    `active` = VALUES(`active`),
    `is_source` = VALUES(`is_source`);

-- Legacy translation table removal
DROP TABLE IF EXISTS `translation`;

-- Parcours metadata and sharing flags
ALTER TABLE `parcours`
    ADD COLUMN IF NOT EXISTS `IDorganization` int(11) DEFAULT NULL AFTER `image`,
    ADD COLUMN IF NOT EXISTS `IDusercreation` int(11) DEFAULT NULL AFTER `IDorganization`,
    ADD COLUMN IF NOT EXISTS `IDusermodification` int(11) DEFAULT NULL AFTER `IDusercreation`,
    ADD COLUMN IF NOT EXISTS `datecreation` datetime DEFAULT NULL AFTER `IDusermodification`,
    ADD COLUMN IF NOT EXISTS `datemodification` datetime DEFAULT NULL AFTER `datecreation`,
    ADD COLUMN IF NOT EXISTS `ispublic` tinyint(1) NOT NULL DEFAULT 0 AFTER `datemodification`,
    ADD COLUMN IF NOT EXISTS `isbasic` tinyint(1) NOT NULL DEFAULT 0 AFTER `ispublic`;

UPDATE `parcours` p
LEFT JOIN (
    SELECT `IDparcours`, MIN(`IDorganization`) AS `owner_organization_id`
    FROM `organization_parcours`
    GROUP BY `IDparcours`
) op
    ON op.`IDparcours` = p.`id`
SET p.`IDorganization` = COALESCE(p.`IDorganization`, op.`owner_organization_id`)
WHERE p.`IDorganization` IS NULL;

UPDATE `parcours`
SET `IDusercreation` = COALESCE(`IDusercreation`, 1),
    `IDusermodification` = COALESCE(`IDusermodification`, `IDusercreation`, 1),
    `datecreation` = COALESCE(`datecreation`, NOW()),
    `datemodification` = COALESCE(`datemodification`, `datecreation`, NOW())
WHERE `IDusercreation` IS NULL
   OR `IDusermodification` IS NULL
   OR `datecreation` IS NULL
   OR `datemodification` IS NULL;

UPDATE `parcours`
SET `ispublic` = 1
WHERE `id` IN (1, 2, 3, 7101, 7102);

UPDATE `parcours`
SET `isbasic` = 1
WHERE `id` IN (7101, 7102);

-- Permission catalog and assignments
CREATE TABLE IF NOT EXISTS `permission` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `permission_key` varchar(190) NOT NULL,
    `title` varchar(190) NOT NULL,
    `description` text NOT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_permission_key` (`permission_key`),
    KEY `idx_permission_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `holon_permission` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDholon` int(11) NOT NULL,
    `IDpermission` int(11) NOT NULL,
    `range` varchar(40) NOT NULL DEFAULT 'self',
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_holon_permission_range` (`IDholon`, `IDpermission`, `range`),
    KEY `idx_holon_permission_permission` (`IDpermission`),
    KEY `idx_holon_permission_range` (`range`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permission` (`permission_key`, `title`, `description`, `created_at`, `updated_at`)
VALUES
    ('CAN_ADD_MEMBER', 'Ajouter un membre', 'Autorise l ajout d un membre dans le contexte cible.', NOW(), NOW()),
    ('CAN_ADD_ADMIN', 'Definir un admin de contexte', 'Autorise l attribution ou le retrait du statut admin dans le contexte cible.', NOW(), NOW()),
    ('CAN_CREATE_DOCUMENT', 'Creer des fichiers', 'Autorise la creation de fichiers dans le contexte cible.', NOW(), NOW()),
    ('CAN_CREATE_DECISION', 'Creer des prises de decision', 'Autorise la creation de prises de decision dans le contexte cible.', NOW(), NOW()),
    ('CAN_CREATE_EVENT', 'Creer des dates', 'Autorise la creation de dates dans le contexte cible.', NOW(), NOW()),
    ('CAN_DELETE_EVENT', 'Supprimer des dates', 'Autorise la suppression de dates dans le contexte cible.', NOW(), NOW()),
    ('CAN_CLAIM_PV', 'Devenir secretaire de PV', 'Autorise a prendre le role de secretaire pendant une reunion associee a un PV.', NOW(), NOW()),
    ('CAN_CREATE_FAQ', 'Creer des FAQ', 'Autorise la creation de FAQ dans le contexte cible.', NOW(), NOW()),
    ('CAN_CREATE_PROJECT', 'Creer des projets', 'Autorise la creation de projets dans le contexte cible.', NOW(), NOW()),
    ('CAN_CREATE_INDICATOR', 'Creer des indicateurs', 'Autorise la creation d indicateurs dans le contexte cible.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `updated_at` = NOW();

-- Structure app current drawer configuration
INSERT INTO `application` (
  `id`, `label`, `hash`, `directory`, `icon`, `drawer`, `url`, `navigationmode`, `position`, `requires_login`, `active`
) VALUES
  (1, 'Structure', 'structure', NULL, 'images/tools/connection.png', 'drawer_structure', 'api/getStructure.php?drawer=1', 'drawer', 10, 0, 1)
ON DUPLICATE KEY UPDATE
  `label` = VALUES(`label`),
  `hash` = VALUES(`hash`),
  `directory` = VALUES(`directory`),
  `icon` = VALUES(`icon`),
  `drawer` = VALUES(`drawer`),
  `url` = VALUES(`url`),
  `navigationmode` = VALUES(`navigationmode`),
  `position` = VALUES(`position`),
  `requires_login` = VALUES(`requires_login`),
  `active` = VALUES(`active`);

ALTER TABLE `organization_application`
  ADD COLUMN IF NOT EXISTS `parameters` mediumtext DEFAULT NULL AFTER `active`;

CREATE TABLE IF NOT EXISTS `event_invitation` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDevent` int(11) NOT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `email` varchar(250) DEFAULT NULL,
    `display_name` varchar(190) DEFAULT NULL,
    `invitation_type` varchar(30) NOT NULL,
    `status` varchar(30) NOT NULL DEFAULT 'invited',
    `parameters` mediumtext DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_event_invitation_holon` (`IDevent`, `IDholon`),
    UNIQUE KEY `uniq_event_invitation_user` (`IDevent`, `IDuser`),
    UNIQUE KEY `uniq_event_invitation_email` (`IDevent`, `email`),
    KEY `idx_event_invitation_type` (`invitation_type`),
    KEY `idx_event_invitation_status` (`status`),
    KEY `idx_event_invitation_active` (`active`),
    CONSTRAINT `fk_event_invitation_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_event_invitation_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_event_invitation_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_attendance` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDevent` int(11) NOT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `email` varchar(250) DEFAULT NULL,
    `display_name` varchar(190) DEFAULT NULL,
    `is_present` tinyint(1) NOT NULL DEFAULT 0,
    `IDuser_checked_by` int(11) DEFAULT NULL,
    `checked_at` datetime DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_event_attendance_user` (`IDevent`, `IDuser`),
    UNIQUE KEY `uniq_event_attendance_email` (`IDevent`, `email`),
    KEY `idx_event_attendance_present` (`is_present`),
    KEY `idx_event_attendance_checked_by` (`IDuser_checked_by`),
    KEY `idx_event_attendance_active` (`active`),
    CONSTRAINT `fk_event_attendance_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_event_attendance_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_event_attendance_checked_by` FOREIGN KEY (`IDuser_checked_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resource_invitation` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `resource_type` varchar(50) NOT NULL,
    `resource_id` int(11) NOT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `email` varchar(250) DEFAULT NULL,
    `display_name` varchar(190) DEFAULT NULL,
    `invitation_type` varchar(30) NOT NULL,
    `status` varchar(30) NOT NULL DEFAULT 'invited',
    `accepted` tinyint(1) DEFAULT NULL,
    `parameters` mediumtext DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_resource_invitation_holon` (`resource_type`, `resource_id`, `IDholon`),
    UNIQUE KEY `uniq_resource_invitation_user` (`resource_type`, `resource_id`, `IDuser`),
    UNIQUE KEY `uniq_resource_invitation_email` (`resource_type`, `resource_id`, `email`),
    KEY `idx_resource_invitation_resource` (`resource_type`, `resource_id`, `active`),
    KEY `idx_resource_invitation_type` (`invitation_type`),
    KEY `idx_resource_invitation_status` (`status`),
    CONSTRAINT `fk_resource_invitation_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_resource_invitation_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resource_attendance` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `resource_type` varchar(50) NOT NULL,
    `resource_id` int(11) NOT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `email` varchar(250) DEFAULT NULL,
    `display_name` varchar(190) DEFAULT NULL,
    `is_present` tinyint(1) NOT NULL DEFAULT 0,
    `IDuser_checked_by` int(11) DEFAULT NULL,
    `checked_at` datetime DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_resource_attendance_user` (`resource_type`, `resource_id`, `IDuser`),
    UNIQUE KEY `uniq_resource_attendance_email` (`resource_type`, `resource_id`, `email`),
    KEY `idx_resource_attendance_resource` (`resource_type`, `resource_id`, `active`),
    KEY `idx_resource_attendance_present` (`is_present`),
    CONSTRAINT `fk_resource_attendance_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_resource_attendance_checked_by` FOREIGN KEY (`IDuser_checked_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `resource_attendance` (`resource_type`, `resource_id`, `IDuser`, `email`, `display_name`, `is_present`, `IDuser_checked_by`, `checked_at`, `active`, `created_at`, `updated_at`)
SELECT 'event', `IDevent`, `IDuser`, `email`, `display_name`, `is_present`, `IDuser_checked_by`, `checked_at`, `active`, `created_at`, `updated_at`
FROM `event_attendance`
ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`), `is_present` = VALUES(`is_present`), `IDuser_checked_by` = VALUES(`IDuser_checked_by`), `checked_at` = VALUES(`checked_at`), `active` = VALUES(`active`), `updated_at` = VALUES(`updated_at`);

INSERT INTO `resource_invitation` (`resource_type`, `resource_id`, `IDholon`, `IDuser`, `email`, `display_name`, `invitation_type`, `status`, `parameters`, `active`, `created_at`, `updated_at`)
SELECT 'event', `IDevent`, `IDholon`, `IDuser`, `email`, `display_name`, `invitation_type`, `status`, `parameters`, `active`, `created_at`, `updated_at`
FROM `event_invitation`
ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`), `status` = VALUES(`status`), `parameters` = VALUES(`parameters`), `active` = VALUES(`active`), `updated_at` = VALUES(`updated_at`);

INSERT INTO `resource_invitation` (`resource_type`, `resource_id`, `IDholon`, `IDuser`, `email`, `display_name`, `invitation_type`, `status`, `parameters`, `active`, `created_at`, `updated_at`)
SELECT 'decision_process', `IDdecision_process`, `IDholon`, `IDuser`, `email`, `display_name`, `invitation_type`, `status`, `parameters`, `active`, `created_at`, `updated_at`
FROM `decision_invitation`
ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`), `status` = VALUES(`status`), `parameters` = VALUES(`parameters`), `active` = VALUES(`active`), `updated_at` = VALUES(`updated_at`);

INSERT INTO `organization_application` (`IDorganization`, `IDapplication`, `position`, `active`)
SELECT o.id, a.id, a.position, 1
FROM `organization` o
INNER JOIN `application` a ON 1 = 1
ON DUPLICATE KEY UPDATE
  `position` = VALUES(`position`),
  `active` = VALUES(`active`);

UPDATE `organization_application` oa
INNER JOIN `application` a
  ON a.id = oa.IDapplication
INNER JOIN `organization` o
  ON o.id = oa.IDorganization
SET oa.`parameters` = CONCAT('{"nextcloud":', JSON_EXTRACT(o.`parameters`, '$.nextcloudDocuments'), '}')
WHERE a.`directory` = 'documents'
  AND (oa.`parameters` IS NULL OR oa.`parameters` = '')
  AND JSON_VALID(o.`parameters`)
  AND JSON_EXTRACT(o.`parameters`, '$.nextcloudDocuments') IS NOT NULL;

-- Defensive guard for fresh Docker databases
ALTER TABLE `user`
  ADD COLUMN IF NOT EXISTS `presentation` text DEFAULT NULL AFTER `lastname`,
  ADD COLUMN IF NOT EXISTS `birthdate` date DEFAULT NULL AFTER `presentation`,
  ADD COLUMN IF NOT EXISTS `latlong` varchar(100) DEFAULT NULL AFTER `presentation`,
  ADD COLUMN IF NOT EXISTS `image` varchar(100) DEFAULT NULL AFTER `username`,
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `user_organization`
  ADD COLUMN IF NOT EXISTS `image` varchar(100) DEFAULT NULL AFTER `username`,
  ADD COLUMN IF NOT EXISTS `presentation` text DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `latlong` varchar(100) DEFAULT NULL AFTER `presentation`;

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

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `IDusercreation` int(11) DEFAULT NULL AFTER `IDuser`,
  ADD COLUMN IF NOT EXISTS `contentedition` longtext DEFAULT NULL AFTER `content`,
  ADD COLUMN IF NOT EXISTS `estDossier` tinyint(1) NOT NULL DEFAULT 0 AFTER `IDholon`,
  ADD COLUMN IF NOT EXISTS `IDevent` int(11) DEFAULT NULL AFTER `IDholon`,
  ADD COLUMN IF NOT EXISTS `IDdocument_parent` int(11) DEFAULT NULL AFTER `estDossier`,
  ADD COLUMN IF NOT EXISTS `datecontentedition` datetime DEFAULT NULL AFTER `contentedition`,
  ADD COLUMN IF NOT EXISTS `IDusermodification` int(11) DEFAULT NULL AFTER `datemodification`,
  ADD COLUMN IF NOT EXISTS `dateedition` datetime DEFAULT NULL AFTER `datemodification`,
  ADD COLUMN IF NOT EXISTS `IDuseredition` int(11) DEFAULT NULL AFTER `dateedition`,
  ADD COLUMN IF NOT EXISTS `documenttype` varchar(30) NOT NULL DEFAULT 'html' AFTER `estDossier`,
  ADD COLUMN IF NOT EXISTS `externalurl` varchar(2000) DEFAULT NULL AFTER `documenttype`,
  ADD COLUMN IF NOT EXISTS `openinnewwindow` tinyint(1) NOT NULL DEFAULT 0 AFTER `externalurl`,
  ADD COLUMN IF NOT EXISTS `storedfilepath` varchar(1000) DEFAULT NULL AFTER `openinnewwindow`,
  ADD COLUMN IF NOT EXISTS `storedfilename` varchar(255) DEFAULT NULL AFTER `storedfilepath`,
  ADD COLUMN IF NOT EXISTS `storedfilemime` varchar(255) DEFAULT NULL AFTER `storedfilename`,
  ADD COLUMN IF NOT EXISTS `storedfilesize` int(11) DEFAULT NULL AFTER `storedfilemime`;

UPDATE `document`
SET `IDusercreation` = `IDuser`
WHERE `IDusercreation` IS NULL
  AND `IDuser` IS NOT NULL;

UPDATE `document`
SET `IDusermodification` = COALESCE(`IDusercreation`, `IDuser`)
WHERE `IDusermodification` IS NULL;

UPDATE `document`
SET `documenttype` = 'folder'
WHERE `estDossier` = 1
  AND (`documenttype` IS NULL OR `documenttype` = '' OR `documenttype` = 'html');

UPDATE `document`
SET `documenttype` = 'html'
WHERE `estDossier` = 0
  AND (`documenttype` IS NULL OR `documenttype` = '');

ALTER TABLE `document`
  ADD KEY IF NOT EXISTS `idx_document_event` (`IDevent`),
  ADD KEY IF NOT EXISTS `idx_document_parent` (`IDdocument_parent`),
  ADD KEY IF NOT EXISTS `idx_document_folder` (`estDossier`),
  ADD KEY IF NOT EXISTS `idx_document_user_creation` (`IDusercreation`),
  ADD KEY IF NOT EXISTS `idx_document_user_modification` (`IDusermodification`),
  ADD KEY IF NOT EXISTS `idx_document_draft_date` (`datecontentedition`),
  ADD KEY IF NOT EXISTS `idx_document_editing_user` (`IDuseredition`),
  ADD KEY IF NOT EXISTS `idx_document_editing_date` (`dateedition`),
  ADD KEY IF NOT EXISTS `idx_document_type` (`documenttype`),
  ADD KEY IF NOT EXISTS `idx_document_stored_file_path` (`storedfilepath`(255));

SET @fk_document_parent_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_parent'
);

SET @fk_document_parent_sql := IF(
  @fk_document_parent_exists = 0,
  'ALTER TABLE `document` ADD CONSTRAINT `fk_document_parent` FOREIGN KEY (`IDdocument_parent`) REFERENCES `document` (`id`) ON DELETE CASCADE',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_parent_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_document_user_creation_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_user_creation'
);

SET @fk_document_user_creation_sql := IF(
  @fk_document_user_creation_exists = 0,
  'ALTER TABLE `document` ADD CONSTRAINT `fk_document_user_creation` FOREIGN KEY (`IDusercreation`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_user_creation_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_document_user_modification_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_user_modification'
);

SET @fk_document_user_modification_sql := IF(
  @fk_document_user_modification_exists = 0,
  'ALTER TABLE `document` ADD CONSTRAINT `fk_document_user_modification` FOREIGN KEY (`IDusermodification`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_user_modification_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_document_user_editing_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_user_editing'
);

SET @fk_document_user_editing_sql := IF(
  @fk_document_user_editing_exists = 0,
  'ALTER TABLE `document` ADD CONSTRAINT `fk_document_user_editing` FOREIGN KEY (`IDuseredition`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_user_editing_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDuser` int(11) NOT NULL,
  `title` varchar(190) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `timezone` varchar(64) DEFAULT NULL,
  `locationmode` varchar(20) DEFAULT NULL,
  `locationaddress` varchar(1000) DEFAULT NULL,
  `videomeetingurl` varchar(2000) DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `is_all_day` tinyint(1) NOT NULL DEFAULT 0,
  `parameters` mediumtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_org` (`IDorganization`),
  KEY `idx_event_holon` (`IDholon`),
  KEY `idx_event_user` (`IDuser`),
  KEY `idx_event_status` (`status`),
  KEY `idx_event_active` (`active`),
  KEY `idx_event_start` (`start_at`),
  KEY `idx_event_org_start` (`IDorganization`, `start_at`),
  KEY `idx_event_location_mode` (`locationmode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `event`
  ADD COLUMN IF NOT EXISTS `locationmode` varchar(20) DEFAULT NULL AFTER `timezone`,
  ADD COLUMN IF NOT EXISTS `locationaddress` varchar(1000) DEFAULT NULL AFTER `locationmode`,
  ADD COLUMN IF NOT EXISTS `videomeetingurl` varchar(2000) DEFAULT NULL AFTER `locationaddress`;

ALTER TABLE `event`
  ADD KEY IF NOT EXISTS `idx_event_location_mode` (`locationmode`);

SET @fk_event_org_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_event_org'
);

SET @fk_event_org_sql := IF(
  @fk_event_org_exists = 0,
  'ALTER TABLE `event` ADD CONSTRAINT `fk_event_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE',
  'SELECT 1'
);

PREPARE stmt FROM @fk_event_org_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_event_holon_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_event_holon'
);

SET @fk_event_holon_sql := IF(
  @fk_event_holon_exists = 0,
  'ALTER TABLE `event` ADD CONSTRAINT `fk_event_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_event_holon_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_document_event_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_event'
);

SET @fk_document_event_sql := IF(
  @fk_document_event_exists = 0,
  'ALTER TABLE `document` ADD CONSTRAINT `fk_document_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_event_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_document_pv_editor_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_pv_editor'
);

SET @fk_document_pv_editor_sql := IF(
  @fk_document_pv_editor_exists = 0,
  'ALTER TABLE `document` ADD CONSTRAINT `fk_document_pv_editor` FOREIGN KEY (`IDuser_pv_editor`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_pv_editor_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `document_pv_point` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdocument` int(11) NOT NULL,
  `title` varchar(80) NOT NULL,
  `IDuser_author` int(11) DEFAULT NULL,
  `author_email` varchar(250) DEFAULT NULL,
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
  KEY `idx_document_pv_point_author_email` (`author_email`),
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

DELETE FROM `document_pv_point_tension`
WHERE `id` BETWEEN 2331 AND 2333
   OR `IDdocument_pv_point` BETWEEN 2311 AND 2313;

DELETE FROM `document_pv_point_holon`
WHERE `id` BETWEEN 2321 AND 2324
   OR `IDdocument_pv_point` BETWEEN 2311 AND 2313;

DELETE FROM `document_pv_point`
WHERE `id` BETWEEN 2311 AND 2313
   OR `IDdocument` = 2301;

DELETE FROM `document`
WHERE `id` = 2301;

DELETE FROM `tension`
WHERE `id` BETWEEN 9301 AND 9302;

INSERT INTO `tension` (
  `id`,
  `IDorganization`,
  `IDholon`,
  `IDuser`,
  `title`,
  `description`,
  `datecreation`,
  `datemodification`,
  `active`
) VALUES
  (
    9301,
    1,
    687,
    1,
    'Suivi budget',
    'Besoin de clarifier la projection budgetaire du prochain trimestre et les arbitrages a venir.',
    '2026-07-09 08:40:00',
    '2026-07-09 08:40:00',
    1
  ),
  (
    9302,
    1,
    686,
    1,
    'Charge equipe',
    'Question ouverte sur la charge de travail actuelle et la repartition entre marketing et administration.',
    '2026-07-09 08:45:00',
    '2026-07-09 08:45:00',
    1
  );

INSERT INTO `document` (
  `id`,
  `title`,
  `description`,
  `content`,
  `keywords`,
  `IDuser`,
  `IDorganization`,
  `IDholon`,
  `datecreation`,
  `datemodification`,
  `version`,
  `codeview`,
  `codeedit`,
  `documenttype`
) VALUES (
  2301,
  'PV gouvernance 09.07.2026',
  'Premier PV de demonstration pour verifier le viewer des points a l ordre du jour.',
  '',
  'pv,reunion,gouvernance',
  1,
  1,
  678,
  '2026-07-09 09:00:00',
  '2026-07-09 11:15:00',
  1,
  '',
  '',
  'pv'
);

INSERT INTO `document_pv_point` (
  `id`,
  `IDdocument`,
  `title`,
  `IDuser_author`,
  `IDholon_concerned`,
  `content`,
  `position`,
  `desired_duration_minutes`,
  `actual_duration_minutes`,
  `pointtype`,
  `active`,
  `datecreation`,
  `datemodification`
) VALUES
  (
    2311,
    2301,
    'Budget',
    1,
    686,
    '<p>Presentation rapide du cadrage budgetaire du trimestre.</p><p>Un point de vigilance reste ouvert sur la marge de securite disponible.</p>',
    1,
    10,
    8,
    'information',
    1,
    '2026-07-09 09:05:00',
    '2026-07-09 09:15:00'
  ),
  (
    2312,
    2301,
    'Campagne ete',
    1,
    687,
    '<p>Consultation sur le rythme de diffusion et le niveau d effort soutenable pour l equipe.</p><ul><li>Besoin de sequence courte</li><li>Besoin de relais internes</li></ul>',
    2,
    20,
    24,
    'consultation',
    1,
    '2026-07-09 09:20:00',
    '2026-07-09 09:50:00'
  ),
  (
    2313,
    2301,
    'Validation',
    1,
    678,
    '<p>Decision prise: valider le lancement d un test de deux semaines avec suivi budgetaire hebdomadaire.</p>',
    3,
    15,
    12,
    'decision',
    1,
    '2026-07-09 10:00:00',
    '2026-07-09 10:20:00'
  );

INSERT INTO `document_pv_point_holon` (
  `id`,
  `IDdocument_pv_point`,
  `IDholon`,
  `position`
) VALUES
  (2321, 2311, 679, 1),
  (2322, 2312, 686, 1),
  (2323, 2312, 679, 2),
  (2324, 2313, 687, 1);

INSERT INTO `document_pv_point_tension` (
  `id`,
  `IDdocument_pv_point`,
  `IDtension`,
  `position`
) VALUES
  (2331, 2311, 9301, 1),
  (2332, 2312, 9302, 1),
  (2333, 2313, 9301, 1);

-- Local Docker dev account bootstrap
-- User 1 password plaintext: LocalAdmin123!
UPDATE `user`
SET
  `password` = '$2y$10$IbIoX862O2WQD/baKTL8x.CfqU9ArcphckXpsg7UqD9d6cPMc2M4i',
  `active` = 1,
  `siteadmin` = 1
WHERE `id` = 1;

UPDATE `user_organization`
SET `parameters` = '{"isAdmin":true}'
WHERE `IDuser` = 1;

INSERT INTO `user_patreon` (
  `IDuser`,
  `access_token`,
  `refresh_token`,
  `token_expires_at`,
  `scope`,
  `token_type`,
  `patreon_user_id`,
  `patreon_member_id`,
  `campaign_id`,
  `full_name`,
  `email`,
  `patron_status`,
  `last_charge_status`,
  `currently_entitled_amount_cents`,
  `campaign_lifetime_support_cents`,
  `tier_titles`,
  `is_connected`,
  `connected_at`,
  `last_sync_at`,
  `last_sync_status`,
  `created_at`,
  `updated_at`
) VALUES (
  1,
  'docker-local-access-token',
  'docker-local-refresh-token',
  DATE_ADD(NOW(), INTERVAL 1 YEAR),
  'identity identity[email] campaigns.members',
  'Bearer',
  'docker-local-user-1',
  'docker-local-member-1',
  'docker-local-campaign-1',
  'Open Organization Admin',
  'admin@omo.test',
  'active_patron',
  'Paid',
  500,
  500,
  '["Local Dev"]',
  1,
  NOW(),
  NOW(),
  'ok',
  NOW(),
  NOW()
)
ON DUPLICATE KEY UPDATE
  `access_token` = VALUES(`access_token`),
  `refresh_token` = VALUES(`refresh_token`),
  `token_expires_at` = VALUES(`token_expires_at`),
  `scope` = VALUES(`scope`),
  `token_type` = VALUES(`token_type`),
  `patreon_user_id` = VALUES(`patreon_user_id`),
  `patreon_member_id` = VALUES(`patreon_member_id`),
  `campaign_id` = VALUES(`campaign_id`),
  `full_name` = VALUES(`full_name`),
  `email` = VALUES(`email`),
  `patron_status` = VALUES(`patron_status`),
  `last_charge_status` = VALUES(`last_charge_status`),
  `currently_entitled_amount_cents` = VALUES(`currently_entitled_amount_cents`),
  `campaign_lifetime_support_cents` = VALUES(`campaign_lifetime_support_cents`),
  `tier_titles` = VALUES(`tier_titles`),
  `is_connected` = 1,
  `connected_at` = NOW(),
  `last_sync_at` = NOW(),
  `last_sync_status` = 'ok',
  `updated_at` = NOW();
