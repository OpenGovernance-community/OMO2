-- @migration

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `decision_invitation` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDdecision_process` int(11) NOT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `email` varchar(250) DEFAULT NULL,
    `display_name` varchar(190) DEFAULT NULL,
    `invitation_type` varchar(30) NOT NULL DEFAULT 'email',
    `status` varchar(30) NOT NULL DEFAULT 'invited',
    `parameters` mediumtext DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_decision_invitation_holon` (`IDdecision_process`, `IDholon`),
    UNIQUE KEY `uniq_decision_invitation_user` (`IDdecision_process`, `IDuser`),
    UNIQUE KEY `uniq_decision_invitation_email` (`IDdecision_process`, `email`),
    KEY `idx_decision_invitation_type` (`invitation_type`),
    KEY `idx_decision_invitation_status` (`status`),
    KEY `idx_decision_invitation_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `decision_invitation`
    ADD CONSTRAINT `fk_decision_invitation_process`
        FOREIGN KEY (`IDdecision_process`) REFERENCES `decision_process` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_decision_invitation_holon`
        FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_decision_invitation_user`
        FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE;
