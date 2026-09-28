-- @migration

INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES
    ('CAN_CREATE_PARCOURS', 'Creer des parcours', 'Autorise la creation, l import, la suppression et le detachement de parcours dans le contexte cible.', 0, NOW(), NOW()),
    ('CAN_EDIT_PARCOURS', 'Editer des parcours', 'Autorise la modification du contenu des parcours proprietaires et de leurs missions dans le contexte cible.', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE `permission_key` = VALUES(`permission_key`);
