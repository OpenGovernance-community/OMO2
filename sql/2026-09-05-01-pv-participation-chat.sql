-- @migration
-- Attribute PV review discussion messages to their individual participation link.

SET NAMES utf8mb4;

ALTER TABLE `chat_message`
    ADD COLUMN `IDdocument_share_link` int(11) DEFAULT NULL AFTER `IDdecision_participant`,
    ADD KEY `idx_chat_message_document_share_link` (`IDdocument_share_link`),
    ADD CONSTRAINT `fk_chat_message_document_share_link`
        FOREIGN KEY (`IDdocument_share_link`) REFERENCES `document_share_link` (`id`) ON DELETE SET NULL;
