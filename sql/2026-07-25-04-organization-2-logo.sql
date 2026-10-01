-- @migration
-- Use the versioned PNG logo for the example organization.

UPDATE `organization`
SET `logo` = '/img/org2-logo.png'
WHERE `id` = 2;
