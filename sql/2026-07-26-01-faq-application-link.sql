-- @migration

SET NAMES utf8mb4;

ALTER TABLE `faq`
  ADD COLUMN IF NOT EXISTS `IDparcours` int(10) UNSIGNED DEFAULT NULL AFTER `IDholon`,
  ADD KEY IF NOT EXISTS `idx_faq_parcours` (`IDparcours`);

ALTER TABLE `faq`
  ADD COLUMN IF NOT EXISTS `IDapplication` int(10) UNSIGNED DEFAULT NULL AFTER `IDparcours`,
  ADD KEY IF NOT EXISTS `idx_faq_application` (`IDapplication`);
