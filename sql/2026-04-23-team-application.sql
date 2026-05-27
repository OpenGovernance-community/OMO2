-- @migration
-- OpenMyOrganization
-- Ajout de l'application Team sur les bases deja migrees
--
-- Usage conseille:
--   mariadb -u <user> -p <database> < sql/2026-04-23-team-application.sql
--
-- Sauvegarde recommandee avant execution.

SET NAMES utf8mb4;

INSERT INTO `application` (
  `id`, `label`, `hash`, `directory`, `icon`, `drawer`, `url`, `navigationmode`, `position`, `requires_login`, `active`
) VALUES
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
SELECT o.id, 7, 70, 1
FROM `organization` o;

SELECT id, label, hash, directory, navigationmode, position, requires_login, active
FROM application
WHERE id = 7;

SELECT IDorganization, IDapplication, position, active
FROM organization_application
WHERE IDapplication = 7
ORDER BY IDorganization ASC;
