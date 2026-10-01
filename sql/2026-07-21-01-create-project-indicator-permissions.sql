-- @migration

INSERT INTO `permission` (`permission_key`, `title`, `description`, `created_at`, `updated_at`)
VALUES
    ('CAN_CREATE_PROJECT', 'Creer des projets', 'Autorise la creation de projets dans le contexte cible.', NOW(), NOW()),
    ('CAN_CREATE_INDICATOR', 'Creer des indicateurs', 'Autorise la creation d indicateurs dans le contexte cible.', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `updated_at` = NOW();
