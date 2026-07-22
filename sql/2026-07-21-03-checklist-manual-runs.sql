-- @migration
-- Allow several manual checklist instances to share the same reference date.

ALTER TABLE `checklist_run`
    DROP INDEX `uniq_checklist_run_occurrence`,
    ADD KEY `idx_checklist_run_occurrence` (`IDchecklisttrigger`, `scheduled_for`);
