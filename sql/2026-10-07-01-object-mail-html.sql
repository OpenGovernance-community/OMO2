-- @migration
ALTER TABLE object_mail
    ADD COLUMN message_format varchar(5) NOT NULL DEFAULT 'plain' AFTER message;
