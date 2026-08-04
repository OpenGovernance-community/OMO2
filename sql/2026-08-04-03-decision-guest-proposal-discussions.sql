-- @migration
-- Attribute proposal discussion messages to invited decision participants.

SET NAMES utf8mb4;

ALTER TABLE `chat_message`
    ADD COLUMN `IDdecision_participant` int(11) DEFAULT NULL AFTER `IDuser`,
    ADD KEY `idx_chat_message_decision_participant` (`IDdecision_participant`),
    ADD CONSTRAINT `fk_chat_message_decision_participant`
        FOREIGN KEY (`IDdecision_participant`) REFERENCES `decision_participant` (`id`) ON DELETE SET NULL;
