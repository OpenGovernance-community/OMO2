-- @migration
-- OpenMyOrganization
-- External PV point authors

SET NAMES utf8mb4;

ALTER TABLE `document_pv_point`
  ADD COLUMN IF NOT EXISTS `author_email` varchar(250) DEFAULT NULL AFTER `IDuser_author`,
  ADD KEY IF NOT EXISTS `idx_document_pv_point_author_email` (`author_email`);
