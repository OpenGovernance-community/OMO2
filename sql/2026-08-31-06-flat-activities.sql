-- @migration
ALTER TABLE `control_task`
    MODIFY COLUMN `IDcontrollist` int(11) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `IDorganization` int(11) DEFAULT NULL AFTER `IDcontrollist`,
    ADD COLUMN IF NOT EXISTS `IDholon` int(11) DEFAULT NULL AFTER `IDorganization`;

UPDATE `control_task` t
INNER JOIN `control_list` l ON l.`id` = t.`IDcontrollist`
SET t.`IDorganization` = l.`IDorganization`, t.`IDholon` = l.`IDholon`
WHERE t.`IDorganization` IS NULL;

ALTER TABLE `control_task`
    ADD KEY IF NOT EXISTS `idx_control_task_context` (`IDorganization`, `IDholon`),
    ADD KEY IF NOT EXISTS `fk_control_task_holon` (`IDholon`),
    ADD CONSTRAINT `fk_control_task_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_control_task_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE;

INSERT INTO `application` (`id`, `label`, `hash`, `directory`, `icon`, `drawer`, `url`, `navigationmode`, `position`, `requires_login`, `active`)
VALUES (10, 'Activites', 'activities', 'activities', 'images/tools/control-list.png', 'drawer_activities', 'api/activities/index.php', 'drawer', 45, 1, 1)
ON DUPLICATE KEY UPDATE
    `label` = VALUES(`label`), `hash` = VALUES(`hash`), `directory` = VALUES(`directory`), `icon` = VALUES(`icon`),
    `drawer` = VALUES(`drawer`), `url` = VALUES(`url`), `navigationmode` = VALUES(`navigationmode`), `position` = VALUES(`position`),
    `requires_login` = VALUES(`requires_login`), `active` = VALUES(`active`);

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
