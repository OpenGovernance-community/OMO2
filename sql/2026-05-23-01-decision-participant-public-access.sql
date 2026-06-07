-- @migration

SET NAMES utf8mb4;

ALTER TABLE `decision_participant`
    ADD COLUMN `access_token` varchar(64) DEFAULT NULL AFTER `status`,
    ADD COLUMN `invitation_sent_at` datetime DEFAULT NULL AFTER `active`,
    ADD COLUMN `invitation_opened_at` datetime DEFAULT NULL AFTER `invitation_sent_at`,
    ADD UNIQUE KEY `uniq_decision_participant_access_token` (`access_token`);
