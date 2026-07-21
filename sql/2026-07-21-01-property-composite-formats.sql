-- @migration
-- Composite property formats: text with HTML detail and HTML around a list

SET NAMES utf8mb4;

INSERT INTO `propertyformat` (`id`, `name`) VALUES
  (6, 'Texte avec detail HTML'),
  (7, 'HTML et liste')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`);
