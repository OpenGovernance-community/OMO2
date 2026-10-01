-- @migration
-- Date source and persisted period for the OMO topbar search.

ALTER TABLE `organization`
  ADD COLUMN IF NOT EXISTS `datecreation` datetime DEFAULT NULL AFTER `parameters`;

UPDATE `organization` o
LEFT JOIN (
  SELECT `IDorganization`, MIN(`datecreation`) AS `first_holon_created_at`
  FROM `holon`
  WHERE `IDorganization` IS NOT NULL
  GROUP BY `IDorganization`
) h ON h.`IDorganization` = o.`id`
SET o.`datecreation` = COALESCE(h.`first_holon_created_at`, NOW())
WHERE o.`datecreation` IS NULL;

ALTER TABLE `organization`
  MODIFY COLUMN `datecreation` datetime NOT NULL DEFAULT current_timestamp();

ALTER TABLE `search_job`
  ADD COLUMN IF NOT EXISTS `timerangejson` mediumtext DEFAULT NULL AFTER `scopesjson`;
