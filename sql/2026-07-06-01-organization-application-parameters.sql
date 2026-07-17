-- @migration
-- OpenMyOrganization
-- Ajout d un stockage de parametres par application installee dans une organisation
--
-- Usage conseille:
--   mariadb -u <user> -p <database> < sql/2026-07-06-01-organization-application-parameters.sql
--
-- Sauvegarde recommandee avant execution.

SET NAMES utf8mb4;

ALTER TABLE `organization_application`
  ADD COLUMN IF NOT EXISTS `parameters` mediumtext DEFAULT NULL AFTER `active`;

INSERT IGNORE INTO `organization_application` (`IDorganization`, `IDapplication`, `position`, `active`)
SELECT o.id, a.id, a.position, 1
FROM `organization` o
INNER JOIN `application` a ON 1 = 1;

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

SELECT id, IDorganization, IDapplication, active, parameters
FROM `organization_application`
ORDER BY IDorganization ASC, position ASC, IDapplication ASC;
