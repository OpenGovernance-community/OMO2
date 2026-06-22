-- @migration
INSERT INTO `permission` (`permission_key`, `title`, `description`, `created_at`, `updated_at`)
VALUES
    ('CAN_CREATE_DOCUMENT', 'Creer des fichiers', 'Autorise la creation de fichiers dans le contexte cible.', NOW(), NOW()),
    ('CAN_CREATE_DECISION', 'Creer des prises de decision', 'Autorise la creation de prises de decision dans le contexte cible.', NOW(), NOW()),
    ('CAN_CREATE_EVENT', 'Creer des dates', 'Autorise la creation de dates dans le contexte cible.', NOW(), NOW()),
    ('CAN_CREATE_FAQ', 'Creer des FAQ', 'Autorise la creation de FAQ dans le contexte cible.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `updated_at` = NOW();
