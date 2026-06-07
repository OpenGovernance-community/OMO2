-- @migration
SET @faq_has_positive_score = (
	SELECT COUNT(*)
	FROM information_schema.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'faq'
	  AND COLUMN_NAME = 'positive_score'
);

SET @faq_add_positive_score_sql = IF(
	@faq_has_positive_score = 0,
	'ALTER TABLE `faq` ADD COLUMN `positive_score` float NOT NULL DEFAULT 0 AFTER `viewcount`',
	'SELECT 1'
);

PREPARE faq_add_positive_score_stmt FROM @faq_add_positive_score_sql;
EXECUTE faq_add_positive_score_stmt;
DEALLOCATE PREPARE faq_add_positive_score_stmt;

SET @faq_has_negative_score = (
	SELECT COUNT(*)
	FROM information_schema.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'faq'
	  AND COLUMN_NAME = 'negative_score'
);

SET @faq_add_negative_score_sql = IF(
	@faq_has_negative_score = 0,
	'ALTER TABLE `faq` ADD COLUMN `negative_score` float NOT NULL DEFAULT 0 AFTER `positive_score`',
	'SELECT 1'
);

PREPARE faq_add_negative_score_stmt FROM @faq_add_negative_score_sql;
EXECUTE faq_add_negative_score_stmt;
DEALLOCATE PREPARE faq_add_negative_score_stmt;

SET @faq_has_total_votes = (
	SELECT COUNT(*)
	FROM information_schema.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'faq'
	  AND COLUMN_NAME = 'total_votes'
);

SET @faq_add_total_votes_sql = IF(
	@faq_has_total_votes = 0,
	'ALTER TABLE `faq` ADD COLUMN `total_votes` int(11) NOT NULL DEFAULT 0 AFTER `negative_score`',
	'SELECT 1'
);

PREPARE faq_add_total_votes_stmt FROM @faq_add_total_votes_sql;
EXECUTE faq_add_total_votes_stmt;
DEALLOCATE PREPARE faq_add_total_votes_stmt;

UPDATE `faq`
SET
	`positive_score` = COALESCE(`positive_score`, 0),
	`negative_score` = COALESCE(`negative_score`, 0),
	`total_votes` = COALESCE(`total_votes`, 0);
