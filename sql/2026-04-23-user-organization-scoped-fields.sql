-- @migration
-- OpenMyOrganization
-- Ajout de champs spécifiques à l'organisation pour les profils utilisateurs
--
-- Usage conseille:
--   mariadb -u <user> -p <database> < sql/2026-04-23-user-organization-scoped-fields.sql
--
-- Sauvegarde recommandee avant execution.

SET NAMES utf8mb4;

ALTER TABLE `user_organization`
  ADD COLUMN IF NOT EXISTS `username` varchar(250) DEFAULT NULL AFTER `IDorganization`,
  ADD COLUMN IF NOT EXISTS `email` varchar(250) DEFAULT NULL AFTER `username`;

SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'user_organization'
  AND COLUMN_NAME IN ('username', 'email', 'datecreation', 'dateconnexion')
ORDER BY ORDINAL_POSITION ASC;
