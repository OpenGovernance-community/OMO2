-- @migration
-- Store deferred governance changes attached to decision proposals.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `decision_governance_action` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDdecision_proposal` int(11) NOT NULL,
    `action_type` varchar(60) NOT NULL,
    `target_type` varchar(40) NOT NULL,
    `target_id` int(11) DEFAULT NULL,
    `before_state` mediumtext DEFAULT NULL,
    `after_state` mediumtext DEFAULT NULL,
    `parameters` mediumtext DEFAULT NULL,
    `position` int(11) NOT NULL DEFAULT 0,
    `status` varchar(30) NOT NULL DEFAULT 'pending',
    `status_message` text DEFAULT NULL,
    `applied_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_decision_governance_action_proposal` (`IDdecision_proposal`, `position`),
    KEY `idx_decision_governance_action_target` (`target_type`, `target_id`),
    KEY `idx_decision_governance_action_status` (`status`),
    CONSTRAINT `fk_decision_governance_action_proposal`
        FOREIGN KEY (`IDdecision_proposal`) REFERENCES `decision_proposal` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
