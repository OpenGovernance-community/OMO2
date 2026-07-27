-- @migration
-- Repair the invitation request origin column when its migration was recorded without the schema change.

SET NAMES utf8mb4;

ALTER TABLE `invitation`
    ADD COLUMN IF NOT EXISTS `request_origin` varchar(20) NOT NULL DEFAULT 'admin' AFTER `token`,
    ADD KEY IF NOT EXISTS `idx_invitation_request_origin` (`request_origin`);

UPDATE `invitation`
SET `request_origin` = 'admin'
WHERE `request_origin` IS NULL OR `request_origin` = '';
