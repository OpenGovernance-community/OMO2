-- @migration
-- OpenMyOrganization
-- Generic invitations for events, decisions, documents and future resources

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `resource_invitation` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `resource_type` varchar(50) NOT NULL,
    `resource_id` int(11) NOT NULL,
    `IDholon` int(11) DEFAULT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `email` varchar(250) DEFAULT NULL,
    `display_name` varchar(190) DEFAULT NULL,
    `invitation_type` varchar(30) NOT NULL,
    `status` varchar(30) NOT NULL DEFAULT 'invited',
    `accepted` tinyint(1) DEFAULT NULL,
    `parameters` mediumtext DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_resource_invitation_holon` (`resource_type`, `resource_id`, `IDholon`),
    UNIQUE KEY `uniq_resource_invitation_user` (`resource_type`, `resource_id`, `IDuser`),
    UNIQUE KEY `uniq_resource_invitation_email` (`resource_type`, `resource_id`, `email`),
    KEY `idx_resource_invitation_resource` (`resource_type`, `resource_id`, `active`),
    KEY `idx_resource_invitation_type` (`invitation_type`),
    KEY `idx_resource_invitation_status` (`status`),
    CONSTRAINT `fk_resource_invitation_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_resource_invitation_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `resource_invitation` (
    `resource_type`, `resource_id`, `IDholon`, `IDuser`, `email`, `display_name`,
    `invitation_type`, `status`, `accepted`, `parameters`, `active`, `created_at`, `updated_at`
)
SELECT
    'event', `IDevent`, `IDholon`, `IDuser`, `email`, `display_name`,
    `invitation_type`, `status`, CASE WHEN `status` = 'accepted' THEN 1 WHEN `status` = 'declined' THEN 0 ELSE NULL END, `parameters`, `active`, `created_at`, `updated_at`
FROM `event_invitation`
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `status` = VALUES(`status`),
    `parameters` = VALUES(`parameters`),
    `active` = VALUES(`active`),
    `accepted` = VALUES(`accepted`),
    `updated_at` = VALUES(`updated_at`);

INSERT INTO `resource_invitation` (
    `resource_type`, `resource_id`, `IDholon`, `IDuser`, `email`, `display_name`,
    `invitation_type`, `status`, `accepted`, `parameters`, `active`, `created_at`, `updated_at`
)
SELECT
    'decision_process', `IDdecision_process`, `IDholon`, `IDuser`, `email`, `display_name`,
    `invitation_type`, `status`, CASE WHEN `status` = 'accepted' THEN 1 WHEN `status` = 'declined' THEN 0 ELSE NULL END, `parameters`, `active`, `created_at`, `updated_at`
FROM `decision_invitation`
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `status` = VALUES(`status`),
    `parameters` = VALUES(`parameters`),
    `active` = VALUES(`active`),
    `accepted` = VALUES(`accepted`),
    `updated_at` = VALUES(`updated_at`);
