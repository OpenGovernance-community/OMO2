-- @migration
-- One immutable announcement per installed revision, shared across organizations.
CREATE TABLE IF NOT EXISTS `site_update_news` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `revision` varchar(64) NOT NULL,
    `previous_revision` varchar(64) NOT NULL,
    `title` varchar(250) NOT NULL,
    `description` mediumtext NOT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_site_update_news_revision` (`revision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
