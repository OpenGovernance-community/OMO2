-- @migration
CREATE TABLE IF NOT EXISTS `control_task` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDorganization` int(11) DEFAULT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDuser_responsible` int(11) DEFAULT NULL,
    `title` varchar(255) NOT NULL,
    `description` mediumtext DEFAULT NULL,
    `frequency` varchar(20) NOT NULL,
    `schedule` varchar(20) NOT NULL,
    `display_lead_value` int(11) NOT NULL DEFAULT 0,
    `display_lead_unit` varchar(20) DEFAULT NULL,
    `execution_duration_value` int(11) NOT NULL DEFAULT 1,
    `execution_duration_unit` varchar(20) NOT NULL DEFAULT 'day',
    `position` int(11) NOT NULL DEFAULT 0,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_control_task_active` (`active`),
    KEY `idx_control_task_context` (`IDorganization`, `IDholon`),
    KEY `idx_control_task_holon` (`IDholon`),
    KEY `idx_control_task_responsible` (`IDuser_responsible`),
    CONSTRAINT `fk_control_task_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_control_task_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_control_task_responsible` FOREIGN KEY (`IDuser_responsible`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `application` (`id`, `label`, `hash`, `directory`, `icon`, `drawer`, `url`, `navigationmode`, `position`, `requires_login`, `active`)
VALUES (10, 'Activites', 'activities', 'activities', 'images/tools/control-list.png', 'drawer_activities', 'api/activities/index.php', 'drawer', 45, 1, 1)
ON DUPLICATE KEY UPDATE
    `label` = VALUES(`label`), `hash` = VALUES(`hash`), `directory` = VALUES(`directory`), `icon` = VALUES(`icon`),
    `drawer` = VALUES(`drawer`), `url` = VALUES(`url`), `navigationmode` = VALUES(`navigationmode`), `position` = VALUES(`position`),
    `requires_login` = VALUES(`requires_login`), `active` = VALUES(`active`);

INSERT IGNORE INTO `organization_application` (`IDorganization`, `IDapplication`, `position`, `active`)
SELECT `o`.`id`, `a`.`id`, `a`.`position`, 1
FROM `organization` `o`
INNER JOIN `application` `a` ON `a`.`hash` = 'activities';

INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES
    ('CAN_CREATE_CONTROL_ACTIVITY', 'Creer des activites recurrentes', 'Autorise la creation d activites recurrentes dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_EDIT_CONTROL_ACTIVITY', 'Modifier des activites recurrentes', 'Autorise la modification d activites recurrentes dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_DELETE_CONTROL_ACTIVITY', 'Supprimer des activites recurrentes', 'Autorise la suppression d activites recurrentes dans le contexte cible.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`), `description` = VALUES(`description`), `iscontextual` = VALUES(`iscontextual`), `updated_at` = NOW();
