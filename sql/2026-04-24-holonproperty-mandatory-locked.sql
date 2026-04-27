-- @migration
-- OpenMyOrganization
-- Déplacement des attributs mandatory et locked vers holonproperty
--
-- Usage conseillé:
--   mariadb -u <user> -p <database> < sql/2026-04-24-holonproperty-mandatory-locked.sql
--
-- Sauvegarde recommandée avant exécution.

SET NAMES utf8mb4;

ALTER TABLE `property`
  DROP COLUMN IF EXISTS `mandatory`,
  DROP COLUMN IF EXISTS `locked`;

ALTER TABLE `holonproperty`
  ADD COLUMN IF NOT EXISTS `mandatory` tinyint(1) NOT NULL DEFAULT 0 AFTER `position`,
  ADD COLUMN IF NOT EXISTS `locked` tinyint(1) NOT NULL DEFAULT 0 AFTER `mandatory`;

SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND (
    (TABLE_NAME = 'property' AND COLUMN_NAME IN ('mandatory', 'locked'))
    OR (TABLE_NAME = 'holonproperty' AND COLUMN_NAME IN ('mandatory', 'locked'))
  )
ORDER BY TABLE_NAME ASC, ORDINAL_POSITION ASC;
