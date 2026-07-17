-- @migration
-- OpenMyOrganization
-- Generic attendance for events and documents

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `resource_attendance` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `resource_type` varchar(50) NOT NULL,
    `resource_id` int(11) NOT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `email` varchar(250) DEFAULT NULL,
    `display_name` varchar(190) DEFAULT NULL,
    `is_present` tinyint(1) NOT NULL DEFAULT 0,
    `IDuser_checked_by` int(11) DEFAULT NULL,
    `checked_at` datetime DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_resource_attendance_user` (`resource_type`, `resource_id`, `IDuser`),
    UNIQUE KEY `uniq_resource_attendance_email` (`resource_type`, `resource_id`, `email`),
    KEY `idx_resource_attendance_resource` (`resource_type`, `resource_id`, `active`),
    KEY `idx_resource_attendance_present` (`is_present`),
    CONSTRAINT `fk_resource_attendance_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_resource_attendance_checked_by` FOREIGN KEY (`IDuser_checked_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `resource_attendance` (
    `resource_type`, `resource_id`, `IDuser`, `email`, `display_name`, `is_present`,
    `IDuser_checked_by`, `checked_at`, `active`, `created_at`, `updated_at`
)
SELECT
    'event', `IDevent`, `IDuser`, `email`, `display_name`, `is_present`,
    `IDuser_checked_by`, `checked_at`, `active`, `created_at`, `updated_at`
FROM `event_attendance`
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `is_present` = VALUES(`is_present`),
    `IDuser_checked_by` = VALUES(`IDuser_checked_by`),
    `checked_at` = VALUES(`checked_at`),
    `active` = VALUES(`active`),
    `updated_at` = VALUES(`updated_at`);

