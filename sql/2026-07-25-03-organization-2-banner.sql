-- @migration
-- Use the versioned PNG banner for the example organization.

UPDATE `organization`
SET `banner` = '/img/org2-banner.png'
WHERE `id` = 2;
