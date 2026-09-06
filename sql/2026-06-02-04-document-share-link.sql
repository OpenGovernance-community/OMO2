-- @migration

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `document_share_link` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `version` int(11) NOT NULL DEFAULT 1,
    `IDorganization` int(11) NOT NULL,
    `IDdocument` int(11) NOT NULL,
    `IDuser` int(11) NOT NULL,
    `label` varchar(150) DEFAULT NULL,
    `token` varchar(80) NOT NULL,
    `password_hash` varchar(255) DEFAULT NULL,
    `allow_live_follow` tinyint(1) NOT NULL DEFAULT 0,
    `allow_pv_contribution` tinyint(1) NOT NULL DEFAULT 0,
    `recipient_email` varchar(250) DEFAULT NULL,
    `recipient_user_id` int(11) DEFAULT NULL,
    `datecreation` datetime DEFAULT NULL,
    `dateexpiration` datetime DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_document_share_link_token` (`token`),
    KEY `idx_document_share_link_document` (`IDdocument`, `active`),
    KEY `idx_document_share_link_organization` (`IDorganization`, `active`),
    KEY `idx_document_share_link_user` (`IDuser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @fk_document_share_link_organization_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_share_link_organization'
);

SET @fk_document_share_link_organization_sql := IF(
  @fk_document_share_link_organization_exists = 0,
  'ALTER TABLE `document_share_link` ADD CONSTRAINT `fk_document_share_link_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_share_link_organization_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_document_share_link_document_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_share_link_document'
);

SET @fk_document_share_link_document_sql := IF(
  @fk_document_share_link_document_exists = 0,
  'ALTER TABLE `document_share_link` ADD CONSTRAINT `fk_document_share_link_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_share_link_document_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_document_share_link_user_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_share_link_user'
);

SET @fk_document_share_link_user_sql := IF(
  @fk_document_share_link_user_exists = 0,
  'ALTER TABLE `document_share_link` ADD CONSTRAINT `fk_document_share_link_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_share_link_user_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
