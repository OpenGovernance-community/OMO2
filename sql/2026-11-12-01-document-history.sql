-- @migration

SET NAMES utf8mb4;

ALTER TABLE `document`
    ADD COLUMN IF NOT EXISTS `history_enabled` tinyint(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `document_version` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDdocument` int(11) NOT NULL,
    `IDuser` int(11) DEFAULT NULL,
    `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
    `reason` varchar(30) NOT NULL,
    `documenttype` varchar(50) NOT NULL,
    `storedfilepath` varchar(1000) NOT NULL,
    `storedfilename` varchar(255) NOT NULL,
    `storedfilemime` varchar(255) NOT NULL,
    `storedfilesize` bigint unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_document_version_document` (`IDdocument`, `datecreation`, `id`),
    CONSTRAINT `fk_document_version_document`
        FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
