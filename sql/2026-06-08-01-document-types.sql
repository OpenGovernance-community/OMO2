-- @migration

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `documenttype` varchar(30) NOT NULL DEFAULT 'html' AFTER `estDossier`,
  ADD COLUMN IF NOT EXISTS `externalurl` varchar(2000) DEFAULT NULL AFTER `documenttype`,
  ADD COLUMN IF NOT EXISTS `openinnewwindow` tinyint(1) NOT NULL DEFAULT 0 AFTER `externalurl`;

UPDATE `document`
SET `documenttype` = 'folder'
WHERE `estDossier` = 1
  AND (`documenttype` IS NULL OR `documenttype` = '' OR `documenttype` = 'html');

UPDATE `document`
SET `documenttype` = 'html'
WHERE `estDossier` = 0
  AND (`documenttype` IS NULL OR `documenttype` = '');

ALTER TABLE `document`
  ADD KEY IF NOT EXISTS `idx_document_type` (`documenttype`);
