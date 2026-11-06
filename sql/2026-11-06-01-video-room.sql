-- @migration
CREATE TABLE IF NOT EXISTS `video_room` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `code` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `title` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `video_room_code` (`code`),
  KEY `video_room_owner` (`IDuser`, `created_at`),
  CONSTRAINT `video_room_user_fk` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
