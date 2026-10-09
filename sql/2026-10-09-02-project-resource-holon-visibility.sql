-- @migration
ALTER TABLE `stat_indicator`
  ADD COLUMN IF NOT EXISTS `project_visible_in_holon` tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE `recurring_task`
  ADD COLUMN IF NOT EXISTS `project_visible_in_holon` tinyint(1) NOT NULL DEFAULT 0;
