-- @migration
-- Projects, project teams and project document references

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `project` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDorganization` int(11) NOT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `IDproject_parent` int(11) DEFAULT NULL,
    `IDdocument_journal` int(11) DEFAULT NULL,
    `title` varchar(255) NOT NULL,
    `description` mediumtext DEFAULT NULL,
    `status` varchar(20) NOT NULL DEFAULT 'someday',
    `planned_start_date` date DEFAULT NULL,
    `planned_end_date` date DEFAULT NULL,
    `priority` tinyint(3) DEFAULT NULL,
    `importance` tinyint(3) DEFAULT NULL,
    `capture_mode` varchar(30) NOT NULL DEFAULT 'multiple_documents',
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_project_organization` (`IDorganization`),
    KEY `idx_project_holon` (`IDholon`),
    KEY `idx_project_user` (`IDuser`),
    KEY `idx_project_parent` (`IDproject_parent`),
    KEY `idx_project_journal` (`IDdocument_journal`),
    KEY `idx_project_status` (`status`),
    KEY `idx_project_active` (`active`),
    CONSTRAINT `fk_project_organization`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_project_holon`
        FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_project_user`
        FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_project_parent`
        FOREIGN KEY (`IDproject_parent`) REFERENCES `project` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_project_journal`
        FOREIGN KEY (`IDdocument_journal`) REFERENCES `document` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_user` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDproject` int(11) NOT NULL,
    `IDuser` int(11) NOT NULL,
    `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
    `active` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_project_user` (`IDproject`, `IDuser`),
    KEY `idx_project_user_project` (`IDproject`),
    KEY `idx_project_user_user` (`IDuser`),
    KEY `idx_project_user_active` (`active`),
    CONSTRAINT `fk_project_user_project`
        FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_project_user_user`
        FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_document` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDproject` int(11) NOT NULL,
    `IDdocument` int(11) NOT NULL,
    `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_project_document` (`IDproject`, `IDdocument`),
    KEY `idx_project_document_project` (`IDproject`),
    KEY `idx_project_document_document` (`IDdocument`),
    CONSTRAINT `fk_project_document_project`
        FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_project_document_document`
        FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
