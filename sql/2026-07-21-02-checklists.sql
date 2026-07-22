-- @migration
-- Executable checklists based on project templates

SET NAMES utf8mb4;

ALTER TABLE `project`
    ADD COLUMN IF NOT EXISTS `project_kind` varchar(30) NOT NULL DEFAULT 'standard' AFTER `IDdocument_journal`,
    ADD COLUMN IF NOT EXISTS `IDproject_template` int(11) DEFAULT NULL AFTER `project_kind`,
    ADD KEY IF NOT EXISTS `idx_project_kind` (`project_kind`),
    ADD KEY IF NOT EXISTS `idx_project_template` (`IDproject_template`);

SET @fk_project_template_exists := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = 'fk_project_template'
);

SET @fk_project_template_sql := IF(
    @fk_project_template_exists = 0,
    'ALTER TABLE `project` ADD CONSTRAINT `fk_project_template` FOREIGN KEY (`IDproject_template`) REFERENCES `project` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);

PREPARE stmt FROM @fk_project_template_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `checklist` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDorganization` int(11) NOT NULL,
    `IDchecklist_previous` int(11) DEFAULT NULL,
    `IDproject_template_root` int(11) NOT NULL,
    `IDdocument` int(11) DEFAULT NULL,
    `status` varchar(20) NOT NULL DEFAULT 'draft',
    `revision_note` mediumtext DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `published_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_checklist_previous` (`IDchecklist_previous`),
    UNIQUE KEY `uniq_checklist_template_root` (`IDproject_template_root`),
    KEY `idx_checklist_organization` (`IDorganization`),
    KEY `idx_checklist_document` (`IDdocument`),
    KEY `idx_checklist_status_active` (`status`, `active`),
    CONSTRAINT `fk_checklist_organization`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_checklist_previous`
        FOREIGN KEY (`IDchecklist_previous`) REFERENCES `checklist` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_checklist_template_root`
        FOREIGN KEY (`IDproject_template_root`) REFERENCES `project` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_checklist_document`
        FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `checklist_item` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDchecklist` int(11) NOT NULL,
    `IDproject_template` int(11) NOT NULL,
    `stable_key` varchar(64) NOT NULL,
    `activation_type` varchar(30) NOT NULL DEFAULT 'immediate',
    `delay_value` int(11) NOT NULL DEFAULT 0,
    `delay_unit` varchar(20) DEFAULT NULL,
    `position` int(11) NOT NULL DEFAULT 0,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_checklist_item_key` (`IDchecklist`, `stable_key`),
    UNIQUE KEY `uniq_checklist_item_project` (`IDproject_template`),
    KEY `idx_checklist_item_position` (`IDchecklist`, `position`),
    KEY `idx_checklist_item_activation` (`activation_type`, `active`),
    CONSTRAINT `fk_checklist_item_checklist`
        FOREIGN KEY (`IDchecklist`) REFERENCES `checklist` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_checklist_item_project`
        FOREIGN KEY (`IDproject_template`) REFERENCES `project` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `checklist_item_dependency` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDchecklistitem` int(11) NOT NULL,
    `IDchecklistitem_required` int(11) NOT NULL,
    `delay_value` int(11) NOT NULL DEFAULT 0,
    `delay_unit` varchar(20) DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_checklist_item_dependency` (`IDchecklistitem`, `IDchecklistitem_required`),
    KEY `idx_checklist_dependency_required` (`IDchecklistitem_required`),
    CONSTRAINT `fk_checklist_dependency_item`
        FOREIGN KEY (`IDchecklistitem`) REFERENCES `checklist_item` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_checklist_dependency_required`
        FOREIGN KEY (`IDchecklistitem_required`) REFERENCES `checklist_item` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `checklist_item_recurrence` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDchecklistitem` int(11) NOT NULL,
    `frequency` varchar(20) NOT NULL,
    `schedule` varchar(20) NOT NULL,
    `display_lead_value` int(11) NOT NULL DEFAULT 0,
    `display_lead_unit` varchar(20) DEFAULT NULL,
    `execution_duration_value` int(11) NOT NULL DEFAULT 0,
    `execution_duration_unit` varchar(20) DEFAULT NULL,
    `next_trigger_at` datetime DEFAULT NULL,
    `enabled` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_checklist_item_recurrence` (`IDchecklistitem`),
    KEY `idx_checklist_item_recurrence_due` (`enabled`, `next_trigger_at`),
    CONSTRAINT `fk_checklist_item_recurrence_item`
        FOREIGN KEY (`IDchecklistitem`) REFERENCES `checklist_item` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `checklist_item_occurrence` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDchecklistitem` int(11) NOT NULL,
    `scheduled_for` datetime NOT NULL,
    `IDproject` int(11) DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_checklist_item_occurrence` (`IDchecklistitem`, `scheduled_for`),
    UNIQUE KEY `uniq_checklist_item_occurrence_project` (`IDproject`),
    KEY `idx_checklist_item_occurrence_project` (`IDproject`),
    CONSTRAINT `fk_checklist_item_occurrence_item`
        FOREIGN KEY (`IDchecklistitem`) REFERENCES `checklist_item` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_checklist_item_occurrence_project`
        FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `checklist_trigger` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDchecklist` int(11) NOT NULL,
    `stable_key` varchar(64) NOT NULL,
    `trigger_type` varchar(20) NOT NULL DEFAULT 'manual',
    `frequency` varchar(20) DEFAULT NULL,
    `schedule` varchar(20) DEFAULT NULL,
    `next_trigger_at` datetime DEFAULT NULL,
    `overlap_policy` varchar(20) NOT NULL DEFAULT 'create_new',
    `enabled` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_checklist_trigger_key` (`IDchecklist`, `stable_key`),
    KEY `idx_checklist_trigger_due` (`trigger_type`, `enabled`, `next_trigger_at`),
    KEY `idx_checklist_trigger_frequency` (`frequency`),
    CONSTRAINT `fk_checklist_trigger_checklist`
        FOREIGN KEY (`IDchecklist`) REFERENCES `checklist` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `checklist_run` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDchecklist` int(11) NOT NULL,
    `IDchecklisttrigger` int(11) DEFAULT NULL,
    `IDorganization` int(11) NOT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDproject_root` int(11) DEFAULT NULL,
    `IDuser_created` int(11) DEFAULT NULL,
    `scheduled_for` datetime DEFAULT NULL,
    `status` varchar(20) NOT NULL DEFAULT 'running',
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `completed_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_checklist_run_occurrence` (`IDchecklisttrigger`, `scheduled_for`),
    KEY `idx_checklist_run_checklist` (`IDchecklist`, `created_at`),
    KEY `idx_checklist_run_context` (`IDorganization`, `IDholon`),
    KEY `idx_checklist_run_project` (`IDproject_root`),
    KEY `idx_checklist_run_user` (`IDuser_created`),
    KEY `idx_checklist_run_status` (`status`),
    CONSTRAINT `fk_checklist_run_checklist`
        FOREIGN KEY (`IDchecklist`) REFERENCES `checklist` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_checklist_run_trigger`
        FOREIGN KEY (`IDchecklisttrigger`) REFERENCES `checklist_trigger` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_checklist_run_organization`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_checklist_run_holon`
        FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_checklist_run_project`
        FOREIGN KEY (`IDproject_root`) REFERENCES `project` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_checklist_run_user`
        FOREIGN KEY (`IDuser_created`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `checklist_run_item` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDchecklistrun` int(11) NOT NULL,
    `IDchecklistitem` int(11) NOT NULL,
    `IDproject` int(11) DEFAULT NULL,
    `activation_at` datetime DEFAULT NULL,
    `state` varchar(20) NOT NULL DEFAULT 'waiting',
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `activated_at` datetime DEFAULT NULL,
    `completed_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_checklist_run_item` (`IDchecklistrun`, `IDchecklistitem`),
    UNIQUE KEY `uniq_checklist_run_item_project` (`IDproject`),
    KEY `idx_checklist_run_item_state` (`state`, `activation_at`),
    KEY `idx_checklist_run_item_template` (`IDchecklistitem`),
    CONSTRAINT `fk_checklist_run_item_run`
        FOREIGN KEY (`IDchecklistrun`) REFERENCES `checklist_run` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_checklist_run_item_template`
        FOREIGN KEY (`IDchecklistitem`) REFERENCES `checklist_item` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_checklist_run_item_project`
        FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `application` (
    `id`, `label`, `hash`, `directory`, `icon`, `drawer`, `url`, `navigationmode`, `position`, `requires_login`, `active`
) VALUES (
    4, 'Checklist', 'checklist', 'checklist', 'images/tools/checklist.png', 'drawer_checklist', 'api/checklist/index.php', 'drawer', 40, 1, 1
)
ON DUPLICATE KEY UPDATE
    `label` = VALUES(`label`),
    `hash` = VALUES(`hash`),
    `directory` = VALUES(`directory`),
    `icon` = VALUES(`icon`),
    `drawer` = VALUES(`drawer`),
    `url` = VALUES(`url`),
    `navigationmode` = VALUES(`navigationmode`),
    `position` = VALUES(`position`),
    `requires_login` = VALUES(`requires_login`),
    `active` = VALUES(`active`);

INSERT IGNORE INTO `organization_application` (`IDorganization`, `IDapplication`, `position`, `active`)
SELECT o.id, a.id, a.position, 1
FROM `organization` o
INNER JOIN `application` a ON a.id = 4;
