-- @migration
ALTER TABLE `property` ADD COLUMN IF NOT EXISTS `type` VARCHAR(20) NOT NULL DEFAULT 'type1' AFTER `shortname`;

INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES
('CAN_CREATE_TYPE1_PROPERTIES', 'Creer les proprietes type1', 'Autorise a creer les proprietes type1 dans le contexte cible.', 1, NOW(), NOW()),
('CAN_EDIT_TYPE1_PROPERTIES', 'Modifier les proprietes type1', 'Autorise a modifier les proprietes type1 dans le contexte cible.', 1, NOW(), NOW()),
('CAN_DELETE_TYPE1_PROPERTIES', 'Supprimer les proprietes type1', 'Autorise a supprimer les proprietes type1 dans le contexte cible.', 1, NOW(), NOW()),
('CAN_CREATE_TYPE2_PROPERTIES', 'Creer les proprietes type2', 'Autorise a creer les proprietes type2 dans le contexte cible.', 1, NOW(), NOW()),
('CAN_EDIT_TYPE2_PROPERTIES', 'Modifier les proprietes type2', 'Autorise a modifier les proprietes type2 dans le contexte cible.', 1, NOW(), NOW()),
('CAN_DELETE_TYPE2_PROPERTIES', 'Supprimer les proprietes type2', 'Autorise a supprimer les proprietes type2 dans le contexte cible.', 1, NOW(), NOW()),
('CAN_CREATE_TYPE3_PROPERTIES', 'Creer les proprietes type3', 'Autorise a creer les proprietes type3 dans le contexte cible.', 1, NOW(), NOW()),
('CAN_EDIT_TYPE3_PROPERTIES', 'Modifier les proprietes type3', 'Autorise a modifier les proprietes type3 dans le contexte cible.', 1, NOW(), NOW()),
('CAN_DELETE_TYPE3_PROPERTIES', 'Supprimer les proprietes type3', 'Autorise a supprimer les proprietes type3 dans le contexte cible.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `iscontextual` = VALUES(`iscontextual`), `updated_at` = NOW();
