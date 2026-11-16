-- @migration
CREATE TABLE IF NOT EXISTS `user_email_change` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDuser` int(11) NOT NULL,
    `old_email` varchar(150) NOT NULL,
    `new_email` varchar(150) NOT NULL,
    `request_hash` char(64) NOT NULL,
    `old_code_hash` varchar(255) NOT NULL,
    `new_code_hash` varchar(255) NOT NULL,
    `security_version` int unsigned NOT NULL,
    `created_at` datetime NOT NULL,
    `expires_at` datetime NOT NULL,
    `attempt_count` int unsigned NOT NULL DEFAULT 0,
    `ready` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_email_change_user` (`IDuser`),
    CONSTRAINT `user_email_change_owner` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
