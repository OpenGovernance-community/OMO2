-- @migration
-- Project size used for recursive project bar weighting

SET NAMES utf8mb4;

ALTER TABLE `project`
    ADD COLUMN IF NOT EXISTS `project_size` varchar(3) NOT NULL DEFAULT 'M' AFTER `importance`;
