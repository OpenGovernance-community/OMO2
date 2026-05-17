-- @migration
ALTER TABLE `user`
    ADD COLUMN IF NOT EXISTS `siteadmin` tinyint(1) NOT NULL DEFAULT 0 AFTER `active`;

UPDATE `user`
SET `siteadmin` = 1
WHERE `siteadmin` = 0
  AND `parameters` IS NOT NULL
  AND `parameters` LIKE '%"isSiteAdmin":true%';
