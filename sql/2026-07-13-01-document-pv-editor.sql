-- @migration
-- OpenMyOrganization
-- PV secretary assignment and claim permission

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `IDuser_pv_editor` int(11) DEFAULT NULL AFTER `IDuseredition`,
  ADD KEY IF NOT EXISTS `idx_document_pv_editor` (`IDuser_pv_editor`);

SET @fk_document_pv_editor_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_pv_editor'
);

SET @fk_document_pv_editor_sql := IF(
  @fk_document_pv_editor_exists = 0,
  'ALTER TABLE `document` ADD CONSTRAINT `fk_document_pv_editor` FOREIGN KEY (`IDuser_pv_editor`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_pv_editor_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `permission` (`permission_key`, `title`, `description`, `created_at`, `updated_at`)
VALUES (
  'CAN_CLAIM_PV',
  'Devenir secretaire de PV',
  'Autorise a prendre le role de secretaire pendant une reunion associee a un PV.',
  NOW(),
  NOW()
)
ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`),
  `description` = VALUES(`description`),
  `updated_at` = NOW();
