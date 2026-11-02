-- @migration
INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES ('CAN_CREATE_TENSION', 'Creer des tensions', 'Autorise la creation de tensions dans le contexte cible.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `iscontextual` = VALUES(`iscontextual`),
    `updated_at` = NOW();
