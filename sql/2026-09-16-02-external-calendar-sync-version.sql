-- @migration
-- OpenMyOrganization
-- Lightweight CalDAV change detection

ALTER TABLE `external_calendar`
    ADD COLUMN `source_ctag` varchar(255) DEFAULT NULL AFTER `timezone`;
