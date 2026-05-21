-- @migration
ALTER TABLE `user`
  ADD COLUMN IF NOT EXISTS `latlong` varchar(100) DEFAULT NULL AFTER `presentation`;

UPDATE `user` u
JOIN `user_organization` uo ON uo.IDuser = u.id
SET u.latlong = uo.latlong
WHERE (u.latlong IS NULL OR u.latlong = '')
  AND uo.latlong IS NOT NULL
  AND uo.latlong <> '';

