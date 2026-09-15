-- @migration
CREATE TABLE IF NOT EXISTS `organizational_maturity_public_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `token` char(32) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_organizational_maturity_public_link_organization` (`IDorganization`),
  UNIQUE KEY `uniq_organizational_maturity_public_link_token` (`token`),
  CONSTRAINT `fk_organizational_maturity_public_link_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
