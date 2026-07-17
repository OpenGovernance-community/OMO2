-- @migration
CREATE TABLE IF NOT EXISTS `permission` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `permission_key` varchar(190) NOT NULL,
    `title` varchar(190) NOT NULL,
    `description` text NOT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_permission_key` (`permission_key`),
    KEY `idx_permission_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
