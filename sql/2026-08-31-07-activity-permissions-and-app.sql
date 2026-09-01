-- @migration
UPDATE `application`
SET `label` = 'Activites', `hash` = 'activities', `directory` = 'activities', `icon` = 'images/tools/control-list.png', `drawer` = 'drawer_activities', `url` = 'api/activities/index.php', `position` = 45, `requires_login` = 1, `active` = 1
WHERE `id` = 10;

INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES
    ('CAN_CREATE_CONTROL_ACTIVITY', 'Creer des activites recurrentes', 'Autorise la creation d activites recurrentes dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_EDIT_CONTROL_ACTIVITY', 'Modifier des activites recurrentes', 'Autorise la modification des activites recurrentes dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_DELETE_CONTROL_ACTIVITY', 'Supprimer des activites recurrentes', 'Autorise la suppression des activites recurrentes dans le contexte cible.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`), `description` = VALUES(`description`), `iscontextual` = VALUES(`iscontextual`), `updated_at` = NOW();

UPDATE `permission`
SET `title` = REPLACE(`title`, 'listes de controle', 'activites recurrentes'),
    `description` = REPLACE(`description`, 'listes de controle', 'activites recurrentes'),
    `updated_at` = NOW()
WHERE `permission_key` IN ('CAN_CREATE_CONTROL_LIST', 'CAN_EDIT_CONTROL_LIST', 'CAN_DELETE_CONTROL_LIST');
