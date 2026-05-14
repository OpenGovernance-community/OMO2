-- @migration
-- OpenMyOrganization
-- Suivi des vraies modifications de valeur sur holonproperty

SET NAMES utf8mb4;

ALTER TABLE `holonproperty`
  ADD COLUMN IF NOT EXISTS `datemodification` datetime DEFAULT NULL AFTER `position`,
  ADD COLUMN IF NOT EXISTS `IDusermodification` int(11) DEFAULT NULL AFTER `datemodification`,
  ADD KEY IF NOT EXISTS `idx_holonproperty_user_modification` (`IDusermodification`);

ALTER TABLE `holonproperty`
  ADD CONSTRAINT `fk_holonproperty_user_modification`
  FOREIGN KEY (`IDusermodification`) REFERENCES `user` (`id`) ON DELETE SET NULL;
