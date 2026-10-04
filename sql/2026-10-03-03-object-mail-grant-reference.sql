-- @migration
-- Keep the original OAuth grant ID even after its deletion, so queued MCP mail
-- fails closed instead of becoming native mail. Do not block account/client deletion.
ALTER TABLE `object_mail` DROP FOREIGN KEY `object_mail_grant_fk`;
