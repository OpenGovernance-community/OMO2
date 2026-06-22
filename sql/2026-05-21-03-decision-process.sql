-- @migration

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `decision_process` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDorganization` int(11) DEFAULT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `title` varchar(190) NOT NULL,
    `description` mediumtext DEFAULT NULL,
    `decision_type` varchar(20) NOT NULL DEFAULT 'decision',
    `status` varchar(20) NOT NULL DEFAULT 'draft',
    `evaluation_method` varchar(40) NOT NULL DEFAULT 'simple_vote',
    `visibility_type` varchar(30) NOT NULL DEFAULT 'organization',
    `parameters` mediumtext DEFAULT NULL,
    `consultation_start_at` datetime DEFAULT NULL,
    `consultation_end_at` datetime DEFAULT NULL,
    `evaluation_start_at` datetime DEFAULT NULL,
    `evaluation_end_at` datetime DEFAULT NULL,
    `results_published_at` datetime DEFAULT NULL,
    `archived_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_decision_process_org` (`IDorganization`),
    KEY `idx_decision_process_holon` (`IDholon`),
    KEY `idx_decision_process_status` (`status`),
    KEY `idx_decision_process_method` (`evaluation_method`),
    KEY `idx_decision_process_type` (`decision_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `decision_proposal` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDdecision_process` int(11) NOT NULL,
    `title` varchar(190) NOT NULL,
    `description` mediumtext DEFAULT NULL,
    `position` int(11) NOT NULL DEFAULT 0,
    `parameters` mediumtext DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_decision_proposal_process` (`IDdecision_process`),
    KEY `idx_decision_proposal_position` (`IDdecision_process`, `position`),
    KEY `idx_decision_proposal_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `decision_participant` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDdecision_process` int(11) NOT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `email` varchar(250) DEFAULT NULL,
    `display_name` varchar(190) DEFAULT NULL,
    `role` varchar(30) NOT NULL DEFAULT 'participant',
    `status` varchar(30) NOT NULL DEFAULT 'invited',
    `parameters` mediumtext DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_decision_participant_user` (`IDdecision_process`, `IDuser`),
    UNIQUE KEY `uniq_decision_participant_email` (`IDdecision_process`, `email`),
    KEY `idx_decision_participant_status` (`status`),
    KEY `idx_decision_participant_role` (`role`),
    KEY `idx_decision_participant_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `decision_response` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDdecision_process` int(11) NOT NULL,
    `IDdecision_participant` int(11) NOT NULL,
    `status` varchar(30) NOT NULL DEFAULT 'draft',
    `parameters` mediumtext DEFAULT NULL,
    `submitted_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_decision_response_participant` (`IDdecision_process`, `IDdecision_participant`),
    KEY `idx_decision_response_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `decision_result` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDdecision_process` int(11) NOT NULL,
    `status` varchar(30) NOT NULL DEFAULT 'pending',
    `summary` mediumtext DEFAULT NULL,
    `parameters` mediumtext DEFAULT NULL,
    `computed_at` datetime DEFAULT NULL,
    `published_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_decision_result_process` (`IDdecision_process`),
    KEY `idx_decision_result_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `decision_process`
    ADD CONSTRAINT `fk_decision_process_org`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_decision_process_holon`
        FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL;

ALTER TABLE `decision_proposal`
    ADD CONSTRAINT `fk_decision_proposal_process`
        FOREIGN KEY (`IDdecision_process`) REFERENCES `decision_process` (`id`) ON DELETE CASCADE;

ALTER TABLE `decision_participant`
    ADD CONSTRAINT `fk_decision_participant_process`
        FOREIGN KEY (`IDdecision_process`) REFERENCES `decision_process` (`id`) ON DELETE CASCADE;

ALTER TABLE `decision_response`
    ADD CONSTRAINT `fk_decision_response_process`
        FOREIGN KEY (`IDdecision_process`) REFERENCES `decision_process` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_decision_response_participant`
        FOREIGN KEY (`IDdecision_participant`) REFERENCES `decision_participant` (`id`) ON DELETE CASCADE;

ALTER TABLE `decision_result`
    ADD CONSTRAINT `fk_decision_result_process`
        FOREIGN KEY (`IDdecision_process`) REFERENCES `decision_process` (`id`) ON DELETE CASCADE;
