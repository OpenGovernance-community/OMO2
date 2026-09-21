-- @migration
-- Replace the legacy checklist and control task storage names with the domain names
-- used by the application. RENAME TABLE preserves the rows, indexes and foreign keys.

RENAME TABLE
    `checklist` TO `process`,
    `checklist_item` TO `process_item`,
    `checklist_item_dependency` TO `process_item_dependency`,
    `checklist_item_occurrence` TO `process_item_occurrence`,
    `checklist_item_recurrence` TO `process_item_recurrence`,
    `checklist_trigger` TO `process_trigger`,
    `checklist_run` TO `process_run`,
    `checklist_run_item` TO `process_run_item`,
    `control_task` TO `recurring_task`,
    `control_task_check` TO `recurring_task_check`;

UPDATE `application`
SET `directory` = 'processes', `url` = 'api/processes/index.php'
WHERE `id` = 4;

UPDATE `application`
SET `directory` = 'recurring_tasks', `url` = 'api/recurring_tasks/index.php'
WHERE `directory` = 'activities';
