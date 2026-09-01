-- @migration
-- Track when a project is archived so archive views can distinguish it from its closure date.

ALTER TABLE `project`
    ADD COLUMN IF NOT EXISTS `closed_at` datetime DEFAULT NULL AFTER `planned_end_date`;

ALTER TABLE `project`
    ADD COLUMN IF NOT EXISTS `archived_at` datetime DEFAULT NULL AFTER `active`;

CREATE INDEX IF NOT EXISTS `idx_project_archived_at` ON `project` (`archived_at`);
CREATE INDEX IF NOT EXISTS `idx_project_closed_at` ON `project` (`closed_at`);
