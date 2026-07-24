-- @migration
-- Shared visibility and completion timing for every checklist item.

ALTER TABLE `checklist_item`
    ADD COLUMN IF NOT EXISTS `display_lead_value` int(11) NOT NULL DEFAULT 0 AFTER `delay_unit`,
    ADD COLUMN IF NOT EXISTS `display_lead_unit` varchar(20) DEFAULT NULL AFTER `display_lead_value`,
    ADD COLUMN IF NOT EXISTS `execution_duration_value` int(11) NOT NULL DEFAULT 0 AFTER `display_lead_unit`,
    ADD COLUMN IF NOT EXISTS `execution_duration_unit` varchar(20) DEFAULT NULL AFTER `execution_duration_value`;

UPDATE `checklist_item` item
INNER JOIN `checklist_item_recurrence` recurrence
    ON recurrence.`IDchecklistitem` = item.`id`
SET
    item.`display_lead_value` = recurrence.`display_lead_value`,
    item.`display_lead_unit` = recurrence.`display_lead_unit`,
    item.`execution_duration_value` = recurrence.`execution_duration_value`,
    item.`execution_duration_unit` = recurrence.`execution_duration_unit`
WHERE item.`display_lead_value` = 0
  AND item.`execution_duration_value` = 0;
