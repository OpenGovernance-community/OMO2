-- @migration
-- Persistent server-calculated project importance, normalized from 0 to 1.

ALTER TABLE `project`
    ADD COLUMN IF NOT EXISTS `calculated_importance` decimal(10,8) NOT NULL DEFAULT 0 AFTER `importance`,
    ADD KEY IF NOT EXISTS `idx_project_calculated_importance` (`calculated_importance`);
