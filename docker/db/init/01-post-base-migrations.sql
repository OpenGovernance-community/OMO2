-- Docker post-seed updates for schema and seed data missing from 00-base.seed.sql.
-- Keep this file intentionally minimal and idempotent.

SET NAMES utf8mb4;

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
    ('CAN_ADD_ADMIN', 'Definir un admin de contexte', 'Autorise l attribution ou le retrait du statut admin dans le contexte cible.', NOW(), NOW())
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

INSERT INTO `organization_application` (`IDorganization`, `IDapplication`, `position`, `active`)
SELECT o.id, 1, 10, 1
FROM `organization` o
ON DUPLICATE KEY UPDATE
  `position` = VALUES(`position`),
  `active` = VALUES(`active`);

-- Defensive guard for fresh Docker databases
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
