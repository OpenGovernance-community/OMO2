-- @migration
-- OpenMyOrganization
-- Ajout des formats de propriete Chiffre et Date
--
-- Usage conseille:
--   mariadb -u <user> -p <database> < sql/2026-04-24-property-format-number-date.sql
--
-- Sauvegarde recommandee avant execution.

SET NAMES utf8mb4;

INSERT INTO `propertyformat` (`id`, `name`) VALUES
  (1, 'Texte libre'),
  (2, 'Liste'),
  (3, 'Chiffre'),
  (4, 'Date')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`);

SELECT `id`, `name`
FROM `propertyformat`
WHERE `id` IN (1, 2, 3, 4)
ORDER BY `id` ASC;
