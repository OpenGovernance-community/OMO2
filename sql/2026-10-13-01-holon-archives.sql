-- @migration
-- Soft archives keep the physical parent and every dependent record intact.
ALTER TABLE `holon`
    ADD COLUMN IF NOT EXISTS `archived_at` datetime DEFAULT NULL AFTER `active`;

CREATE INDEX IF NOT EXISTS `idx_holon_archives` ON `holon` (`IDholon_org`, `active`, `archived_at`);
