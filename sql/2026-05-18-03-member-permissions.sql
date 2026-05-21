-- @migration
INSERT INTO `permission` (`permission_key`, `title`, `description`, `created_at`, `updated_at`)
VALUES
    ('CAN_ADD_MEMBER', 'Ajouter un membre', 'Autorise l ajout d un membre dans le contexte cible.', NOW(), NOW()),
    ('CAN_ADD_ADMIN', 'Definir un admin de contexte', 'Autorise l attribution ou le retrait du statut admin dans le contexte cible.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `updated_at` = NOW();
