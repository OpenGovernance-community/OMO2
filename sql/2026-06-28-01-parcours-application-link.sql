-- @migration

SET NAMES utf8mb4;

ALTER TABLE `parcours`
  ADD COLUMN IF NOT EXISTS `IDapplication` int(11) DEFAULT NULL AFTER `IDorganization`,
  ADD KEY IF NOT EXISTS `idx_parcours_application` (`IDapplication`);
