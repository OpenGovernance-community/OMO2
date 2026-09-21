-- @migration

ALTER TABLE `organization`
  ADD COLUMN IF NOT EXISTS `isModel` tinyint(1) NOT NULL DEFAULT 0 AFTER `interface_level`;

UPDATE `organization` o
INNER JOIN `holon` h ON h.`IDorganization` = o.`id`
  AND h.`IDtypeholon` = 4
  AND (h.`IDholon_parent` IS NULL OR h.`IDholon_parent` = 0)
  AND h.`templatename` IS NOT NULL
  AND h.`templatename` <> ''
SET o.`isModel` = 1;

UPDATE `holon` h
INNER JOIN `organization` o ON o.`id` = h.`IDorganization`
SET h.`templatename` = NULL
WHERE h.`IDtypeholon` = 4
  AND (h.`IDholon_parent` IS NULL OR h.`IDholon_parent` = 0)
  AND o.`isModel` = 1;
