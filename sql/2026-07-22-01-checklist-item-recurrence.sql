-- @migration
-- Independent recurrence for simple checklist items inside container checklists.

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

ALTER TABLE `checklist_item_recurrence`
    ADD COLUMN IF NOT EXISTS `display_lead_value` int(11) NOT NULL DEFAULT 0 AFTER `schedule`,
    ADD COLUMN IF NOT EXISTS `display_lead_unit` varchar(20) DEFAULT NULL AFTER `display_lead_value`,
    ADD COLUMN IF NOT EXISTS `execution_duration_value` int(11) NOT NULL DEFAULT 0 AFTER `display_lead_unit`,
    ADD COLUMN IF NOT EXISTS `execution_duration_unit` varchar(20) DEFAULT NULL AFTER `execution_duration_value`;
