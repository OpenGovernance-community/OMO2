-- @migration
-- Store application tabs and their meeting-specific views on PV documents.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `document_application_tab` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDdocument` int(11) NOT NULL,
    `IDapplication` int(11) NOT NULL,
    `position` int(11) NOT NULL DEFAULT 10,
    `view_parameters` mediumtext DEFAULT NULL,
    `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
    `datemodification` datetime DEFAULT NULL ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_document_application_tab` (`IDdocument`, `IDapplication`),
    KEY `idx_document_application_tab_document` (`IDdocument`, `position`),
    KEY `idx_document_application_tab_application` (`IDapplication`),
    CONSTRAINT `fk_document_application_tab_document`
        FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_document_application_tab_application`
        FOREIGN KEY (`IDapplication`) REFERENCES `application` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
