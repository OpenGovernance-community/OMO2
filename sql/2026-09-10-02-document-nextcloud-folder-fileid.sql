-- @migration

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `nextcloudfolderfileid` varchar(64) DEFAULT NULL AFTER `nextcloudfolderpath`;

ALTER TABLE `document`
  ADD KEY IF NOT EXISTS `idx_document_nextcloud_folder_fileid` (`nextcloudfolderfileid`);
