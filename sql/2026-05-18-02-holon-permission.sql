-- @migration
CREATE TABLE IF NOT EXISTS `holon_permission` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDholon` int(11) NOT NULL,
    `IDpermission` int(11) NOT NULL,
    `range` varchar(40) NOT NULL DEFAULT 'self',
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_holon_permission` (`IDholon`, `IDpermission`),
    KEY `idx_holon_permission_permission` (`IDpermission`),
    KEY `idx_holon_permission_range` (`range`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
