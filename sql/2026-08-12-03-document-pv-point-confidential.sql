-- @migration
-- Allow a PV point to be restricted to people marked present at the meeting.

SET NAMES utf8mb4;

ALTER TABLE `document_pv_point`
    ADD COLUMN IF NOT EXISTS `is_confidential` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_handled`;
