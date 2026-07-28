-- @migration
INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES
    ('CAN_EDIT_TEMPLATE_PROPERTIES', 'Modifier les proprietes de templates', 'Autorise la modification des proprietes definies par les templates dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_ADD_TEMPLATE_PROPERTIES', 'Ajouter des proprietes de templates', 'Autorise l ajout de proprietes definies par les templates dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_DELETE_TEMPLATE_PROPERTIES', 'Supprimer les proprietes de templates', 'Autorise le retrait des proprietes definies par les templates dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_EDIT_HOLON_PROPERTIES', 'Modifier les proprietes de holons', 'Autorise la modification des proprietes ajoutees directement a un holon dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_ADD_HOLON_PROPERTIES', 'Ajouter des proprietes de holons', 'Autorise l ajout de proprietes directement sur un holon dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_DELETE_HOLON_PROPERTIES', 'Supprimer les proprietes de holons', 'Autorise le retrait des proprietes ajoutees directement a un holon dans le contexte cible.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `iscontextual` = VALUES(`iscontextual`),
    `updated_at` = NOW();
