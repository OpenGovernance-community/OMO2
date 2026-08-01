-- @migration
-- A regular Telegram group has no message_thread_id. Keep a non-empty sentinel
-- because dbObject normalizes empty strings to NULL before an insert.

ALTER TABLE `telegram_chat_destination`
    MODIFY `telegram_thread_id` varchar(32) NOT NULL DEFAULT '__main__';
