-- @migration
-- Use the versioned PNG logo for the system organization.

UPDATE `organization`
SET `logo` = '/img/org1-logo.png'
WHERE `id` = 1;
