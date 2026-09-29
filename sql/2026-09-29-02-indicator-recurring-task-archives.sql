-- @migration
-- Keep archive dates distinct from existing deletion behavior.

ALTER TABLE `stat_indicator`
    ADD COLUMN IF NOT EXISTS `archived_at` datetime DEFAULT NULL AFTER `active`;

ALTER TABLE `recurring_task`
    ADD COLUMN IF NOT EXISTS `archived_at` datetime DEFAULT NULL AFTER `active`;

CREATE INDEX IF NOT EXISTS `idx_stat_indicator_archived_at` ON `stat_indicator` (`archived_at`);
CREATE INDEX IF NOT EXISTS `idx_recurring_task_archived_at` ON `recurring_task` (`archived_at`);
