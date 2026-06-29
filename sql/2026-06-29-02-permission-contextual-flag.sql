-- @migration

SET NAMES utf8mb4;

ALTER TABLE `permission`
  ADD COLUMN IF NOT EXISTS `iscontextual` tinyint(1) NOT NULL DEFAULT 1 AFTER `description`;

UPDATE `permission`
SET `iscontextual` = 1
WHERE `permission_key` IN (
  'CAN_ADD_MEMBER',
  'CAN_ADD_ADMIN',
  'CAN_CREATE_DOCUMENT',
  'CAN_CREATE_DECISION',
  'CAN_CREATE_EVENT',
  'CAN_CREATE_FAQ'
);

UPDATE `permission`
SET `iscontextual` = 0
WHERE `permission_key` IN (
  'CAN_CREATE_PARCOURS',
  'CAN_EDIT_PARCOURS'
);
