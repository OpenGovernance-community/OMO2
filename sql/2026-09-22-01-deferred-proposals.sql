-- @migration
-- Generic deferred changes, attachable to a decision alternative or a PV point.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `deferred_proposal` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDorganization` int(11) NOT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDuser_author` int(11) NOT NULL,
    `target_type` varchar(40) NOT NULL,
    `operation` varchar(20) NOT NULL,
    `target_id` int(11) DEFAULT NULL,
    `before_state` mediumtext DEFAULT NULL,
    `after_state` mediumtext DEFAULT NULL,
    `parameters` mediumtext DEFAULT NULL,
    `IDdecision_proposal` int(11) DEFAULT NULL,
    `IDdocument_pv_point` int(11) DEFAULT NULL,
    `position` int(11) NOT NULL DEFAULT 0,
    `status` varchar(30) NOT NULL DEFAULT 'pending',
    `validation_context` text DEFAULT NULL,
    `status_message` text DEFAULT NULL,
    `IDuser_validated` int(11) DEFAULT NULL,
    `validated_at` datetime DEFAULT NULL,
    `IDuser_applied` int(11) DEFAULT NULL,
    `applied_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_deferred_proposal_decision` (`IDdecision_proposal`, `position`),
    KEY `idx_deferred_proposal_pv_point` (`IDdocument_pv_point`, `position`),
    KEY `idx_deferred_proposal_target` (`target_type`, `target_id`),
    KEY `idx_deferred_proposal_status` (`status`),
    CONSTRAINT `fk_deferred_proposal_decision` FOREIGN KEY (`IDdecision_proposal`) REFERENCES `decision_proposal` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_deferred_proposal_pv_point` FOREIGN KEY (`IDdocument_pv_point`) REFERENCES `document_pv_point` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
