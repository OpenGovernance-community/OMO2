-- @migration
SET @faq_has_reliability = (
	SELECT COUNT(*)
	FROM information_schema.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'faq'
	  AND COLUMN_NAME = 'reliability'
);

SET @faq_add_reliability_sql = IF(
	@faq_has_reliability = 0,
	'ALTER TABLE `faq` ADD COLUMN `reliability` float NOT NULL DEFAULT 0 AFTER `total_votes`',
	'SELECT 1'
);

PREPARE faq_add_reliability_stmt FROM @faq_add_reliability_sql;
EXECUTE faq_add_reliability_stmt;
DEALLOCATE PREPARE faq_add_reliability_stmt;

SET @faq_has_reliability_updated_at = (
	SELECT COUNT(*)
	FROM information_schema.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'faq'
	  AND COLUMN_NAME = 'reliability_updated_at'
);

SET @faq_add_reliability_updated_at_sql = IF(
	@faq_has_reliability_updated_at = 0,
	'ALTER TABLE `faq` ADD COLUMN `reliability_updated_at` datetime DEFAULT NULL AFTER `reliability`',
	'SELECT 1'
);

PREPARE faq_add_reliability_updated_at_stmt FROM @faq_add_reliability_updated_at_sql;
EXECUTE faq_add_reliability_updated_at_stmt;
DEALLOCATE PREPARE faq_add_reliability_updated_at_stmt;

SET @faq_has_score_decayed_at = (
	SELECT COUNT(*)
	FROM information_schema.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'faq'
	  AND COLUMN_NAME = 'score_decayed_at'
);

SET @faq_add_score_decayed_at_sql = IF(
	@faq_has_score_decayed_at = 0,
	'ALTER TABLE `faq` ADD COLUMN `score_decayed_at` datetime DEFAULT NULL AFTER `reliability_updated_at`',
	'SELECT 1'
);

PREPARE faq_add_score_decayed_at_stmt FROM @faq_add_score_decayed_at_sql;
EXECUTE faq_add_score_decayed_at_stmt;
DEALLOCATE PREPARE faq_add_score_decayed_at_stmt;

SET @faq_has_reliability_index = (
	SELECT COUNT(*)
	FROM information_schema.STATISTICS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'faq'
	  AND INDEX_NAME = 'idx_faq_reliability'
);

SET @faq_add_reliability_index_sql = IF(
	@faq_has_reliability_index = 0,
	'ALTER TABLE `faq` ADD INDEX `idx_faq_reliability` (`reliability`)',
	'SELECT 1'
);

PREPARE faq_add_reliability_index_stmt FROM @faq_add_reliability_index_sql;
EXECUTE faq_add_reliability_index_stmt;
DEALLOCATE PREPARE faq_add_reliability_index_stmt;

SET @faq_has_reliability_updated_index = (
	SELECT COUNT(*)
	FROM information_schema.STATISTICS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'faq'
	  AND INDEX_NAME = 'idx_faq_reliability_updated_at'
);

SET @faq_add_reliability_updated_index_sql = IF(
	@faq_has_reliability_updated_index = 0,
	'ALTER TABLE `faq` ADD INDEX `idx_faq_reliability_updated_at` (`reliability_updated_at`)',
	'SELECT 1'
);

PREPARE faq_add_reliability_updated_index_stmt FROM @faq_add_reliability_updated_index_sql;
EXECUTE faq_add_reliability_updated_index_stmt;
DEALLOCATE PREPARE faq_add_reliability_updated_index_stmt;

UPDATE `faq`
SET `reliability` = COALESCE(`reliability`, 0)
WHERE `reliability` IS NULL;
