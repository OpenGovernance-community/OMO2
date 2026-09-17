-- @migration
ALTER TABLE `holon`
  DROP COLUMN IF EXISTS `lockedbanner`,
  DROP COLUMN IF EXISTS `banner`;
