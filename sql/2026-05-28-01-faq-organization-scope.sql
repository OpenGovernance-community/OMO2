-- @migration
SET @faq_has_idorganization = (
	SELECT COUNT(*)
	FROM information_schema.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'faq'
	  AND COLUMN_NAME = 'IDorganization'
);

SET @faq_add_idorganization_sql = IF(
	@faq_has_idorganization = 0,
	'ALTER TABLE `faq` ADD COLUMN `IDorganization` int(10) UNSIGNED DEFAULT NULL AFTER `IDhowto`',
	'SELECT 1'
);

PREPARE faq_add_idorganization_stmt FROM @faq_add_idorganization_sql;
EXECUTE faq_add_idorganization_stmt;
DEALLOCATE PREPARE faq_add_idorganization_stmt;

UPDATE `faq` AS `f`
LEFT JOIN `holon` AS `h`
	ON `h`.`id` = `f`.`IDholon`
SET `f`.`IDorganization` = `h`.`IDorganization`
WHERE `f`.`IDorganization` IS NULL
  AND `f`.`IDholon` IS NOT NULL
  AND `h`.`IDorganization` IS NOT NULL;
