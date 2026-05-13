-- @migration
SET @user_has_presentation = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'user'
      AND COLUMN_NAME = 'presentation'
);

SET @user_add_presentation_sql = IF(
    @user_has_presentation = 0,
    'ALTER TABLE `user` ADD COLUMN `presentation` text DEFAULT NULL AFTER `lastname`',
    'SELECT 1'
);

PREPARE user_add_presentation_stmt FROM @user_add_presentation_sql;
EXECUTE user_add_presentation_stmt;
DEALLOCATE PREPARE user_add_presentation_stmt;

SET @user_has_birthdate = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'user'
      AND COLUMN_NAME = 'birthdate'
);

SET @user_add_birthdate_sql = IF(
    @user_has_birthdate = 0,
    'ALTER TABLE `user` ADD COLUMN `birthdate` date DEFAULT NULL AFTER `presentation`',
    'SELECT 1'
);

PREPARE user_add_birthdate_stmt FROM @user_add_birthdate_sql;
EXECUTE user_add_birthdate_stmt;
DEALLOCATE PREPARE user_add_birthdate_stmt;

SET @user_organization_has_presentation = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'user_organization'
      AND COLUMN_NAME = 'presentation'
);

SET @user_organization_add_presentation_sql = IF(
    @user_organization_has_presentation = 0,
    'ALTER TABLE `user_organization` ADD COLUMN `presentation` text DEFAULT NULL AFTER `email`',
    'SELECT 1'
);

PREPARE user_organization_add_presentation_stmt FROM @user_organization_add_presentation_sql;
EXECUTE user_organization_add_presentation_stmt;
DEALLOCATE PREPARE user_organization_add_presentation_stmt;
