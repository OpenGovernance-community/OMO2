-- @migration
-- Remove the retired control list module while preserving flattened activities.

SET NAMES utf8mb4;

SET @fk_control_task_list_exists := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = 'fk_control_task_list'
);
SET @fk_control_task_list_sql := IF(
    @fk_control_task_list_exists > 0,
    'ALTER TABLE `control_task` DROP FOREIGN KEY `fk_control_task_list`',
    'SELECT 1'
);
PREPARE stmt FROM @fk_control_task_list_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `control_task`
    DROP INDEX IF EXISTS `idx_control_task_list_position`,
    DROP COLUMN IF EXISTS `IDcontrollist`;

DROP TABLE IF EXISTS `control_list`;

DELETE hp
FROM `holon_permission` hp
INNER JOIN `permission` p ON p.`id` = hp.`IDpermission`
WHERE p.`permission_key` IN ('CAN_CREATE_CONTROL_LIST', 'CAN_EDIT_CONTROL_LIST', 'CAN_DELETE_CONTROL_LIST');

DELETE FROM `permission`
WHERE `permission_key` IN ('CAN_CREATE_CONTROL_LIST', 'CAN_EDIT_CONTROL_LIST', 'CAN_DELETE_CONTROL_LIST');

DELETE dat
FROM `document_application_tab` dat
INNER JOIN `application` a ON a.`id` = dat.`IDapplication`
WHERE a.`hash` = 'checklists';

DELETE oa
FROM `organization_application` oa
INNER JOIN `application` a ON a.`id` = oa.`IDapplication`
WHERE a.`hash` = 'checklists';

DELETE FROM `application`
WHERE `hash` = 'checklists';
