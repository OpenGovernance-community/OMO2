-- @migration
ALTER TABLE `video_room`
  ADD COLUMN `IDorganization` int(11) DEFAULT NULL,
  ADD COLUMN `settings_version` int NOT NULL DEFAULT 1,
  ADD COLUMN `member_audio_muted` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN `member_video_muted` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN `member_camera_restricted` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN `member_screen_restricted` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN `member_lobby` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN `guest_audio_muted` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN `guest_video_muted` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN `guest_camera_restricted` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN `guest_screen_restricted` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN `guest_lobby` tinyint(1) NOT NULL DEFAULT 1,
  ADD CONSTRAINT `video_room_organization_fk` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE SET NULL;
