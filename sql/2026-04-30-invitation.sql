-- @migration
-- OpenMyOrganization
-- Invitations d'adhésion à une organisation

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `invitation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDuser` int(11) NOT NULL,
  `IDuser_sender` int(11) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `token` varchar(64) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `parameters` mediumtext DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateexpiration` datetime DEFAULT NULL,
  `dateresponse` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_invitation_token` (`token`),
  KEY `idx_invitation_org_user` (`IDorganization`, `IDuser`),
  KEY `idx_invitation_status` (`status`),
  KEY `idx_invitation_active` (`active`),
  KEY `idx_invitation_expiration` (`dateexpiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
