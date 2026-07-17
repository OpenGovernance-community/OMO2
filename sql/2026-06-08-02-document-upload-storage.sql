-- @migration

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `storedfilepath` varchar(1000) DEFAULT NULL AFTER `openinnewwindow`,
  ADD COLUMN IF NOT EXISTS `storedfilename` varchar(255) DEFAULT NULL AFTER `storedfilepath`,
  ADD COLUMN IF NOT EXISTS `storedfilemime` varchar(255) DEFAULT NULL AFTER `storedfilename`,
  ADD COLUMN IF NOT EXISTS `storedfilesize` int(11) DEFAULT NULL AFTER `storedfilemime`;

ALTER TABLE `document`
  ADD KEY IF NOT EXISTS `idx_document_stored_file_path` (`storedfilepath`(255));
