-- @migration

SET NAMES utf8mb4;

ALTER TABLE `decision_proposal`
    ADD COLUMN `info_url` varchar(500) DEFAULT NULL AFTER `description`;
