-- @migration
ALTER TABLE `video_room`
  CHANGE COLUMN `lobby_password_hash` `lobby_password_encrypted` varchar(255) DEFAULT NULL,
  DROP COLUMN `lobby_password_version`;
