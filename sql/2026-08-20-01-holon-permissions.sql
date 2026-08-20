-- @migration
INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES
    ('CAN_ADD_HOLON', 'Ajouter un holon', 'Autorise l ajout d un holon dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_EDIT_HOLON', 'Modifier des holons', 'Autorise la modification de holons dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_DELETE_HOLON', 'Supprimer des holons', 'Autorise la suppression de holons dans le contexte cible.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `iscontextual` = VALUES(`iscontextual`),
    `updated_at` = NOW();
