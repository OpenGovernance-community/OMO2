-- @migration
CREATE TABLE IF NOT EXISTS `event_shared_document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDevent` int(11) NOT NULL,
  `IDdocument` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_shared_document` (`IDevent`, `IDdocument`),
  CONSTRAINT `fk_event_shared_document_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_shared_document_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
