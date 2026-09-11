-- @migration

SET NAMES utf8mb4;

INSERT INTO `object_visibility` (
    `object_type`,
    `object_id`,
    `IDorganization`,
    `visibility_type`,
    `IDholon`,
    `active`,
    `datecreation`,
    `datemodification`
)
SELECT
    'document_edit',
    `document`.`id`,
    `document`.`IDorganization`,
    CASE
        WHEN `holon`.`IDtypeholon` = 1 THEN 'role'
        WHEN `holon`.`IDtypeholon` = 2 THEN 'circle'
        ELSE 'self'
    END,
    CASE
        WHEN `holon`.`IDtypeholon` IN (1, 2) THEN `holon`.`id`
        ELSE NULL
    END,
    1,
    CURRENT_TIMESTAMP(),
    CURRENT_TIMESTAMP()
FROM `document`
LEFT JOIN `holon`
    ON `holon`.`id` = `document`.`IDholon`
LEFT JOIN `object_visibility` AS `existing_edit_rule`
    ON `existing_edit_rule`.`object_type` = 'document_edit'
    AND `existing_edit_rule`.`object_id` = `document`.`id`
    AND `existing_edit_rule`.`IDorganization` = `document`.`IDorganization`
    AND `existing_edit_rule`.`active` = 1
WHERE `document`.`IDorganization` IS NOT NULL
  AND `document`.`IDorganization` > 0
  AND `existing_edit_rule`.`id` IS NULL;
