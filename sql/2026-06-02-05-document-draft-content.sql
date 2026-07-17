-- @migration

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `contentedition` longtext DEFAULT NULL AFTER `content`,
  ADD COLUMN IF NOT EXISTS `datecontentedition` datetime DEFAULT NULL AFTER `contentedition`;

ALTER TABLE `document`
  ADD KEY IF NOT EXISTS `idx_document_draft_date` (`datecontentedition`);
