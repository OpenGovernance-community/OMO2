-- @migration

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `dateconsultation` datetime DEFAULT NULL AFTER `datemodification`;

ALTER TABLE `document`
  ADD KEY IF NOT EXISTS `idx_document_consultation_date` (`dateconsultation`);
