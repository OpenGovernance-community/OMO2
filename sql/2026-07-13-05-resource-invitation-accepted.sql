-- @migration
-- OpenMyOrganization
-- Invitation acceptance state

SET NAMES utf8mb4;

ALTER TABLE `resource_invitation`
  ADD COLUMN IF NOT EXISTS `accepted` tinyint(1) DEFAULT NULL AFTER `status`;

UPDATE `resource_invitation`
SET `accepted` = CASE
    WHEN `status` = 'accepted' THEN 1
    WHEN `status` = 'declined' THEN 0
    ELSE NULL
END
WHERE `accepted` IS NULL
  AND `status` IN ('accepted', 'declined');

