-- @migration
-- OpenMyOrganization
-- Ajout des metadonnees pour les proprietes de type liste
--
-- Usage conseille:
--   mariadb -u <user> -p <database> < sql/2026-04-24-property-list-metadata.sql
--
-- Sauvegarde recommandee avant execution.

SET NAMES utf8mb4;

ALTER TABLE `property`
  ADD COLUMN IF NOT EXISTS `listitemtype` varchar(20) DEFAULT NULL AFTER `IDpropertyformat`,
  ADD COLUMN IF NOT EXISTS `listholontypeids` varchar(255) DEFAULT NULL AFTER `listitemtype`;

SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'property'
  AND COLUMN_NAME IN ('listitemtype', 'listholontypeids')
ORDER BY ORDINAL_POSITION ASC;
