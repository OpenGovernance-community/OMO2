-- @migration

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `nextcloudfolderpath` varchar(1000) DEFAULT NULL AFTER `storedfilesize`;

ALTER TABLE `document`
  ADD KEY IF NOT EXISTS `idx_document_nextcloud_folder_path` (`nextcloudfolderpath`(255));
