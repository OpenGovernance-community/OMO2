-- @migration

SET NAMES utf8mb4;

ALTER TABLE `parcours`
  ADD COLUMN IF NOT EXISTS `IDorganization` int(11) DEFAULT NULL AFTER `image`,
  ADD COLUMN IF NOT EXISTS `IDusercreation` int(11) DEFAULT NULL AFTER `IDorganization`,
  ADD COLUMN IF NOT EXISTS `IDusermodification` int(11) DEFAULT NULL AFTER `IDusercreation`,
  ADD COLUMN IF NOT EXISTS `datecreation` datetime DEFAULT NULL AFTER `IDusermodification`,
  ADD COLUMN IF NOT EXISTS `datemodification` datetime DEFAULT NULL AFTER `datecreation`,
  ADD COLUMN IF NOT EXISTS `ispublic` tinyint(1) NOT NULL DEFAULT 0 AFTER `datemodification`,
  ADD COLUMN IF NOT EXISTS `isbasic` tinyint(1) NOT NULL DEFAULT 0 AFTER `ispublic`;

UPDATE `parcours` p
LEFT JOIN (
  SELECT `IDparcours`, MIN(`IDorganization`) AS `owner_organization_id`
  FROM `organization_parcours`
  GROUP BY `IDparcours`
) op
  ON op.`IDparcours` = p.`id`
SET p.`IDorganization` = COALESCE(p.`IDorganization`, op.`owner_organization_id`)
WHERE p.`IDorganization` IS NULL;

UPDATE `parcours`
SET `datecreation` = COALESCE(`datecreation`, NOW()),
    `datemodification` = COALESCE(`datemodification`, `datecreation`, NOW())
WHERE `datecreation` IS NULL
   OR `datemodification` IS NULL;

UPDATE `parcours`
SET `ispublic` = 1
WHERE `id` IN (1, 2, 3, 7101, 7102);

UPDATE `parcours`
SET `isbasic` = 1
WHERE `id` IN (7101, 7102);
