-- @migration
-- OpenMyOrganization
-- Ajout du nom complet optionnel sur les holons
--
-- Usage conseille:
--   mariadb -u <user> -p <database> < sql/2026-07-07-01-holon-nomcomplet.sql
--
-- Sauvegarde recommandee avant execution.

SET NAMES utf8mb4;

ALTER TABLE `holon`
  ADD COLUMN IF NOT EXISTS `nomcomplet` varchar(255) DEFAULT NULL AFTER `name`;
