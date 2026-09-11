-- @migration
-- Add P1 to P5 priority to PV agenda points.

ALTER TABLE `document_pv_point`
  ADD COLUMN IF NOT EXISTS `priority` tinyint(3) unsigned DEFAULT 3 AFTER `position`;

UPDATE `document_pv_point`
SET `priority` = 3
WHERE `priority` IS NULL
   OR `priority` < 1
   OR `priority` > 5;
