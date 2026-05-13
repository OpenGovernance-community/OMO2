-- @migration
SET @faq_has_idholon = (
	SELECT COUNT(*)
	FROM information_schema.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'faq'
	  AND COLUMN_NAME = 'IDholon'
);

SET @faq_add_idholon_sql = IF(
	@faq_has_idholon = 0,
	'ALTER TABLE `faq` ADD COLUMN `IDholon` int(10) UNSIGNED DEFAULT NULL AFTER `IDhowto`',
	'SELECT 1'
);

PREPARE faq_add_idholon_stmt FROM @faq_add_idholon_sql;
EXECUTE faq_add_idholon_stmt;
DEALLOCATE PREPARE faq_add_idholon_stmt;
