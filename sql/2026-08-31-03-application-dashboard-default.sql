-- @migration
CREATE TABLE IF NOT EXISTS `application_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(120) NOT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_application_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
