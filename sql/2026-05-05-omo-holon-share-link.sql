-- @migration
-- OpenMyOrganization
-- Liens de partage de structure

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `holon_share_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDholon` int(11) NOT NULL,
  `IDuser` int(11) NOT NULL,
  `label` varchar(150) DEFAULT NULL,
  `token` varchar(80) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `allow_structure` tinyint(1) NOT NULL DEFAULT 1,
  `allow_people` tinyint(1) NOT NULL DEFAULT 0,
  `allow_people_detail` tinyint(1) NOT NULL DEFAULT 0,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateexpiration` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_holon_share_link_token` (`token`),
  KEY `idx_holon_share_link_org_holon` (`IDorganization`, `IDholon`),
  KEY `idx_holon_share_link_user` (`IDuser`),
  KEY `idx_holon_share_link_active` (`active`),
  KEY `idx_holon_share_link_expiration` (`dateexpiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
