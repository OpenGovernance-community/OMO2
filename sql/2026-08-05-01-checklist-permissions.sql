-- @migration
INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES
    ('CAN_CREATE_CHECKLIST', 'Creer des checklists', 'Autorise la creation de checklists dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_EDIT_CHECKLIST', 'Modifier des checklists', 'Autorise l ajout, la modification et la suppression des elements de checklists dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_DELETE_CHECKLIST', 'Supprimer des checklists', 'Autorise la suppression de checklists dans le contexte cible.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `iscontextual` = VALUES(`iscontextual`),
    `updated_at` = NOW();
