-- @migration
-- Use the versioned PNG banner for the system organization.

UPDATE `organization`
SET `banner` = '/img/org1-banner.png'
WHERE `id` = 1;
