-- OpenMyOrganization
-- Ajout de la gestion des applications activables par organisation
--
-- Usage conseille:
--   mariadb -u <user> -p <database> < sql/2026-04-23-organization-applications.sql
--
-- Sauvegarde recommandee avant execution.

-- @migration

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `application` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(100) NOT NULL,
  `hash` varchar(100) DEFAULT NULL,
  `directory` varchar(100) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `drawer` varchar(100) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `navigationmode` varchar(20) NOT NULL DEFAULT 'drawer',
  `position` int(11) DEFAULT NULL,
  `requires_login` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_application_hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `organization_application` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDapplication` int(11) NOT NULL,
  `position` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_organization_application` (`IDorganization`, `IDapplication`),
  KEY `idx_organization_application_organization` (`IDorganization`),
  KEY `idx_organization_application_application` (`IDapplication`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `application` (
  `id`, `label`, `hash`, `directory`, `icon`, `drawer`, `url`, `navigationmode`, `position`, `requires_login`, `active`
) VALUES
  (1, 'Structure', NULL, NULL, 'images/tools/connection.png', NULL, NULL, 'panel', 10, 0, 1),
  (2, 'Projets', 'projects', 'projects', 'images/tools/product.png', 'drawer_projects', 'api/projects/index.php', 'drawer', 20, 0, 1),
  (3, 'Règlement', 'policy', 'policy', 'images/tools/policy.png', 'drawer_policy', 'api/policy/index.php', 'drawer', 30, 0, 1),
  (4, 'Checklistes', 'checklists', 'checklists', 'images/tools/bucket-list.png', 'drawer_checklists', 'api/checklists/index.php', 'drawer', 40, 0, 1),
  (5, 'Indicateurs', 'stats', 'stats', 'images/tools/stats.png', 'drawer_stats', 'api/stats/index.php', 'drawer', 50, 0, 1),
  (6, 'Documents', 'documents', 'documents', 'images/tools/documents-folder.png', 'drawer_documents', 'api/documents/index.php', 'drawer', 60, 1, 1),
  (7, 'Team', 'team', 'team', 'images/tools/team.png', 'drawer_team', 'api/team/index.php', 'drawer', 70, 1, 1)
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

INSERT IGNORE INTO `organization_application` (`IDorganization`, `IDapplication`, `position`, `active`)
SELECT o.id, a.id, a.position, 1
FROM `organization` o
INNER JOIN `application` a ON a.id IN (1, 2, 3, 4, 5, 6, 7);

SELECT id, label, hash, directory, navigationmode, position, requires_login, active
FROM application
ORDER BY position ASC, id ASC;

SELECT IDorganization, IDapplication, position, active
FROM organization_application
ORDER BY IDorganization ASC, position ASC, IDapplication ASC;
