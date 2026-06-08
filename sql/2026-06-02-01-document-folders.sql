-- @migration

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `estDossier` tinyint(1) NOT NULL DEFAULT 0 AFTER `IDholon`,
  ADD COLUMN IF NOT EXISTS `IDdocument_parent` int(11) DEFAULT NULL AFTER `estDossier`;

ALTER TABLE `document`
  ADD KEY IF NOT EXISTS `idx_document_parent` (`IDdocument_parent`),
  ADD KEY IF NOT EXISTS `idx_document_folder` (`estDossier`);

ALTER TABLE `document`
  ADD CONSTRAINT `fk_document_parent`
    FOREIGN KEY (`IDdocument_parent`) REFERENCES `document` (`id`) ON DELETE CASCADE;
