-- @migration

SET NAMES utf8mb4;

ALTER TABLE `user_competence`
  ADD COLUMN IF NOT EXISTS `description` varchar(500) DEFAULT NULL AFTER `level`;
