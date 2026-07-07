-- @migration

SET NAMES utf8mb4;

ALTER TABLE `parcours_mission`
  ADD COLUMN IF NOT EXISTS `position` int(11) DEFAULT NULL AFTER `IDmission`;

UPDATE `parcours_mission` pm
SET pm.`position` = (
  SELECT COUNT(*)
  FROM `parcours_mission` pm2
  WHERE pm2.`IDparcours` = pm.`IDparcours`
    AND pm2.`id` <= pm.`id`
)
WHERE pm.`position` IS NULL
   OR pm.`position` <= 0;
