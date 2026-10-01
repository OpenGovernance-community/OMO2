-- @migration
CREATE TABLE IF NOT EXISTS `organizational_maturity_assessment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) DEFAULT NULL,
  `IDorganization` int(11) DEFAULT NULL,
  `public_token` char(48) NOT NULL,
  `private_token_hash` char(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_organizational_maturity_assessment_public_token` (`public_token`),
  UNIQUE KEY `uniq_organizational_maturity_assessment_private_token_hash` (`private_token_hash`),
  KEY `idx_organizational_maturity_assessment_organization` (`IDorganization`,`updated_at`),
  KEY `idx_organizational_maturity_assessment_user` (`IDuser`,`updated_at`),
  CONSTRAINT `fk_organizational_maturity_assessment_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_organizational_maturity_assessment_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `organizational_maturity_assessment_response` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDassessment` int(11) NOT NULL,
  `principle_number` tinyint(3) unsigned NOT NULL,
  `affinity_score` tinyint(3) unsigned NOT NULL,
  `today_score` tinyint(3) unsigned NOT NULL,
  `tomorrow_score` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_organizational_maturity_assessment_response_principle` (`IDassessment`,`principle_number`),
  KEY `idx_organizational_maturity_response_principle_today` (`principle_number`,`today_score`),
  KEY `idx_organizational_maturity_response_principle_tomorrow` (`principle_number`,`tomorrow_score`),
  CONSTRAINT `fk_organizational_maturity_assessment_response_assessment` FOREIGN KEY (`IDassessment`) REFERENCES `organizational_maturity_assessment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
