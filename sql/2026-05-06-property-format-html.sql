-- @migration
-- OpenMyOrganization
-- Ajout du format de propriete HTML
--
-- Usage conseille:
--   mariadb -u <user> -p <database> < sql/2026-05-06-property-format-html.sql
--
-- Sauvegarde recommandee avant execution.

SET NAMES utf8mb4;

INSERT INTO `propertyformat` (`id`, `name`) VALUES
  (5, 'HTML')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`);

SELECT `id`, `name`
FROM `propertyformat`
WHERE `id` = 5;
