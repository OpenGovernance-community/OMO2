-- @migration
CREATE TABLE IF NOT EXISTS `control_list` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDorganization` int(11) NOT NULL,
    `IDholon` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` mediumtext DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_control_list_organization_holon` (`IDorganization`, `IDholon`),
    KEY `idx_control_list_active` (`active`),
    CONSTRAINT `fk_control_list_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_control_list_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `control_task` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDcontrollist` int(11) NOT NULL,
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
    KEY `idx_control_task_list_position` (`IDcontrollist`, `position`),
    KEY `idx_control_task_active` (`active`),
    CONSTRAINT `fk_control_task_list` FOREIGN KEY (`IDcontrollist`) REFERENCES `control_list` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `control_task_check` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDcontroltask` int(11) NOT NULL,
    `IDuser` int(11) NOT NULL,
    `scheduled_for` datetime NOT NULL,
    `checked_at` datetime NOT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_control_task_check_occurrence` (`IDcontroltask`, `scheduled_for`),
    KEY `idx_control_task_check_user` (`IDuser`),
    KEY `idx_control_task_check_checked_at` (`checked_at`),
    CONSTRAINT `fk_control_task_check_task` FOREIGN KEY (`IDcontroltask`) REFERENCES `control_task` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_control_task_check_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `application` (`label`, `hash`, `directory`, `icon`, `drawer`, `url`, `navigationmode`, `position`, `requires_login`, `active`)
VALUES ('Listes de controle', 'checklists', 'checklists', 'images/tools/control-list.png', 'drawer_checklists', 'api/checklists/index.php', 'drawer', 45, 1, 1)
ON DUPLICATE KEY UPDATE
    `label` = VALUES(`label`),
    `directory` = VALUES(`directory`),
    `icon` = VALUES(`icon`),
    `drawer` = VALUES(`drawer`),
    `url` = VALUES(`url`),
    `navigationmode` = VALUES(`navigationmode`),
    `position` = VALUES(`position`),
    `requires_login` = VALUES(`requires_login`),
    `active` = VALUES(`active`);

INSERT IGNORE INTO `organization_application` (`IDorganization`, `IDapplication`, `position`, `active`)
SELECT `o`.`id`, `a`.`id`, `a`.`position`, 1
FROM `organization` `o`
INNER JOIN `application` `a` ON `a`.`hash` = 'checklists';

INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES
    ('CAN_CREATE_CONTROL_LIST', 'Creer des listes de controle', 'Autorise la creation de listes de controle dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_EDIT_CONTROL_LIST', 'Modifier des listes de controle', 'Autorise la modification des listes de controle et de leurs activites dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_DELETE_CONTROL_LIST', 'Supprimer des listes de controle', 'Autorise la suppression de listes de controle dans le contexte cible.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `iscontextual` = VALUES(`iscontextual`),
    `updated_at` = NOW();
