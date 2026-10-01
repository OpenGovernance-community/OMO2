-- @migration
CREATE TABLE IF NOT EXISTS `organizational_maturity_invitation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `email` varchar(250) NOT NULL,
  `token` char(32) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_organizational_maturity_invitation_org_email` (`IDorganization`,`email`),
  UNIQUE KEY `uniq_organizational_maturity_invitation_token` (`token`),
  CONSTRAINT `fk_organizational_maturity_invitation_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_organizational_maturity_invitation_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `organizational_maturity_assessment`
  ADD COLUMN IF NOT EXISTS `IDinvitation` int(11) DEFAULT NULL AFTER `IDorganization`,
  ADD UNIQUE KEY `uniq_organizational_maturity_assessment_invitation` (`IDinvitation`),
  ADD CONSTRAINT `fk_organizational_maturity_assessment_invitation` FOREIGN KEY (`IDinvitation`) REFERENCES `organizational_maturity_invitation` (`id`) ON DELETE SET NULL;
