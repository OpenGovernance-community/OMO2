-- @migration

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `project_visible_in_holon` tinyint(1) NOT NULL DEFAULT 0 AFTER `openinnewwindow`;
