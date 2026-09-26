-- @migration
-- Preserve complete list texts when they become authority labels.
ALTER TABLE `authority`
    DROP INDEX `idx_authority_label`,
    MODIFY COLUMN `label` mediumtext NOT NULL,
    ADD INDEX `idx_authority_label` (`label`(255));
