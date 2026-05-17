-- @migration
CREATE TABLE IF NOT EXISTS `translation_bundle_refresh_jobs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bundle_key` varchar(190) NOT NULL,
    `locale` varchar(10) NOT NULL,
    `source_hash` char(64) NOT NULL,
    `source_json` longtext NOT NULL,
    `status` enum('pending', 'running', 'failed', 'completed') NOT NULL DEFAULT 'pending',
    `attempts` int(11) NOT NULL DEFAULT 0,
    `last_error` longtext DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `started_at` datetime DEFAULT NULL,
    `finished_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_bundle_locale_hash` (`bundle_key`, `locale`, `source_hash`),
    KEY `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
