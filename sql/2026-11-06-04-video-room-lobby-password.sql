-- @migration
ALTER TABLE `video_room`
  ADD COLUMN `lobby_password_hash` varchar(255) DEFAULT NULL,
  ADD COLUMN `lobby_password_version` int NOT NULL DEFAULT 0;
