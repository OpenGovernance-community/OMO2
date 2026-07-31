-- @migration
-- Allow rules without an intention.

ALTER TABLE `rule`
    MODIFY COLUMN `intention` mediumtext DEFAULT NULL AFTER `title`;
