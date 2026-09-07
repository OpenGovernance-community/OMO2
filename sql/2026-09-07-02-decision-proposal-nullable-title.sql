-- @migration
-- Allow description-only decision proposals.

SET NAMES utf8mb4;

ALTER TABLE `decision_proposal`
    MODIFY COLUMN `title` varchar(190) DEFAULT NULL;
