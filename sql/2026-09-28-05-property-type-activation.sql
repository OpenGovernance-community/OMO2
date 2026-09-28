-- @migration

INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES
('CAN_CREATE_TYPE4_PROPERTIES', 'Creer les proprietes type4', 'Autorise la creation et la modification de la structure des proprietes type4.', 1, NOW(), NOW()),
('CAN_EDIT_TYPE4_PROPERTIES', 'Modifier les proprietes type4', 'Autorise uniquement la modification des valeurs des proprietes type4.', 1, NOW(), NOW()),
('CAN_DELETE_TYPE4_PROPERTIES', 'Supprimer les proprietes type4', 'Autorise la suppression des proprietes type4.', 1, NOW(), NOW()),
('CAN_CREATE_TYPE5_PROPERTIES', 'Creer les proprietes type5', 'Autorise la creation et la modification de la structure des proprietes type5.', 1, NOW(), NOW()),
('CAN_EDIT_TYPE5_PROPERTIES', 'Modifier les proprietes type5', 'Autorise uniquement la modification des valeurs des proprietes type5.', 1, NOW(), NOW()),
('CAN_DELETE_TYPE5_PROPERTIES', 'Supprimer les proprietes type5', 'Autorise la suppression des proprietes type5.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `iscontextual` = 1, `updated_at` = NOW();
