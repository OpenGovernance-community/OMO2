-- @migration
CREATE TABLE IF NOT EXISTS `translation_bundles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bundle_key` varchar(190) NOT NULL,
    `locale` varchar(10) NOT NULL,
    `source_hash` char(64) NOT NULL,
    `translated_json` longtext NOT NULL,
    `status` enum('machine_translated', 'approved', 'outdated') NOT NULL DEFAULT 'machine_translated',
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_bundle_locale` (`bundle_key`, `locale`),
    KEY `idx_bundle_locale_hash` (`bundle_key`, `locale`, `source_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
