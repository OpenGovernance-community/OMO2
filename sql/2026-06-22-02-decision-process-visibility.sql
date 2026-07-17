-- @migration

SET NAMES utf8mb4;

ALTER TABLE `decision_process`
    ADD COLUMN IF NOT EXISTS `visibility_type` varchar(30) NOT NULL DEFAULT 'organization' AFTER `evaluation_method`;
