-- @migration
CREATE TABLE IF NOT EXISTS `translation_languages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `locale` varchar(10) NOT NULL,
    `name` varchar(120) NOT NULL,
    `native_name` varchar(120) NOT NULL,
    `sort_order` int(11) NOT NULL DEFAULT 100,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `is_source` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_translation_language_locale` (`locale`),
    KEY `idx_translation_language_active_order` (`active`, `is_source`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `translation_languages` (`locale`, `name`, `native_name`, `sort_order`, `active`, `is_source`) VALUES
    ('fr', 'Francais', 'Francais', 10, 1, 1),
    ('en', 'Anglais', 'English', 20, 1, 0),
    ('de', 'Allemand', 'Deutsch', 30, 1, 0),
    ('es', 'Espagnol', 'Espanol', 40, 1, 0),
    ('it', 'Italien', 'Italiano', 50, 1, 0),
    ('pt', 'Portugais', 'Portugues', 60, 1, 0),
    ('nl', 'Neerlandais', 'Nederlands', 70, 1, 0),
    ('pl', 'Polonais', 'Polski', 80, 1, 0)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `native_name` = VALUES(`native_name`),
    `sort_order` = VALUES(`sort_order`),
    `active` = VALUES(`active`),
    `is_source` = VALUES(`is_source`);
