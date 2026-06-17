-- @migration
SET @faq_has_image = (
	SELECT COUNT(*)
	FROM information_schema.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'faq'
	  AND COLUMN_NAME = 'image'
);

SET @faq_add_image_sql = IF(
	@faq_has_image = 0,
	'ALTER TABLE `faq` ADD COLUMN `image` varchar(1000) DEFAULT NULL AFTER `answer`',
	'SELECT 1'
);

PREPARE faq_add_image_stmt FROM @faq_add_image_sql;
EXECUTE faq_add_image_stmt;
DEALLOCATE PREPARE faq_add_image_stmt;

SET @faq_has_video = (
	SELECT COUNT(*)
	FROM information_schema.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'faq'
	  AND COLUMN_NAME = 'video'
);

SET @faq_add_video_sql = IF(
	@faq_has_video = 0,
	'ALTER TABLE `faq` ADD COLUMN `video` varchar(1000) DEFAULT NULL AFTER `image`',
	'SELECT 1'
);

PREPARE faq_add_video_stmt FROM @faq_add_video_sql;
EXECUTE faq_add_video_stmt;
DEALLOCATE PREPARE faq_add_video_stmt;
