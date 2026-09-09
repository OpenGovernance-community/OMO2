-- @migration
-- Use an explicit persisted value for projects which were not proposed.

ALTER TABLE `project`
    MODIFY COLUMN `proposal_status` varchar(20) NOT NULL DEFAULT 'normal';

UPDATE `project`
SET `proposal_status` = 'normal'
WHERE `proposal_status` IS NULL OR `proposal_status` = '';
