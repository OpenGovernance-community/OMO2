-- @migration

SET NAMES utf8mb4;

ALTER TABLE `document_share_link`
    ADD COLUMN IF NOT EXISTS `allow_pv_contribution` tinyint(1) NOT NULL DEFAULT 0 AFTER `allow_live_follow`,
    ADD COLUMN IF NOT EXISTS `recipient_email` varchar(250) DEFAULT NULL AFTER `allow_pv_contribution`,
    ADD COLUMN IF NOT EXISTS `recipient_user_id` int(11) DEFAULT NULL AFTER `recipient_email`;

CREATE INDEX IF NOT EXISTS `idx_document_share_link_pv_recipient`
    ON `document_share_link` (`IDdocument`, `recipient_email`, `active`);
