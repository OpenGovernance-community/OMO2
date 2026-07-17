-- @migration
-- OpenMyOrganization
-- Distinction entre invitation admin et demande membre pour les organisations

SET NAMES utf8mb4;

ALTER TABLE `invitation`
  ADD COLUMN IF NOT EXISTS `request_origin` varchar(20) NOT NULL DEFAULT 'admin' AFTER `token`;

UPDATE `invitation`
SET `request_origin` = 'admin'
WHERE `request_origin` IS NULL OR `request_origin` = '';

ALTER TABLE `invitation`
  ADD KEY IF NOT EXISTS `idx_invitation_request_origin` (`request_origin`);
