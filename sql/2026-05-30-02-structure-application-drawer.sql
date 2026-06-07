-- @migration
-- OpenMyOrganization
-- Remet l'application Structure dans le catalogue d'applications sous forme de drawer

SET NAMES utf8mb4;

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

SELECT id, label, hash, drawer, url, navigationmode, position, active
FROM `application`
WHERE id = 1;

SELECT IDorganization, IDapplication, position, active
FROM `organization_application`
WHERE IDapplication = 1
ORDER BY IDorganization ASC;
