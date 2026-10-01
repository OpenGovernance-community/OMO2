-- @migration
-- OpenMyOrganization
-- Coordinate PV point lock takeover between browser sessions

SET NAMES utf8mb4;

ALTER TABLE `document_pv_point`
  ADD COLUMN IF NOT EXISTS `IDuser_edit_takeover_request` int(11) DEFAULT NULL AFTER `edit_lock_token`,
  ADD COLUMN IF NOT EXISTS `edit_takeover_request_token` varchar(80) DEFAULT NULL AFTER `IDuser_edit_takeover_request`,
  ADD COLUMN IF NOT EXISTS `edit_takeover_target_token` varchar(80) DEFAULT NULL AFTER `edit_takeover_request_token`,
  ADD COLUMN IF NOT EXISTS `date_edit_takeover_request` datetime DEFAULT NULL AFTER `edit_takeover_target_token`;

ALTER TABLE `document_pv_point`
  ADD KEY IF NOT EXISTS `idx_document_pv_point_takeover_user` (`IDuser_edit_takeover_request`),
  ADD KEY IF NOT EXISTS `idx_document_pv_point_takeover_date` (`date_edit_takeover_request`);

SET @fk_document_pv_point_takeover_user_exists := (
  SELECT COUNT(*)
  FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_document_pv_point_takeover_user'
);

SET @fk_document_pv_point_takeover_user_sql := IF(
  @fk_document_pv_point_takeover_user_exists = 0,
  'ALTER TABLE `document_pv_point` ADD CONSTRAINT `fk_document_pv_point_takeover_user` FOREIGN KEY (`IDuser_edit_takeover_request`) REFERENCES `user` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE stmt FROM @fk_document_pv_point_takeover_user_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
