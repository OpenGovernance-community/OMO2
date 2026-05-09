-- @migration
SET @rename_question_table = (
	SELECT IF(
		EXISTS (
			SELECT 1
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'faq'
		)
		AND NOT EXISTS (
			SELECT 1
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'question'
		),
		'RENAME TABLE `faq` TO `question`',
		'SELECT 1'
	)
);
PREPARE stmt FROM @rename_question_table;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_question_choice_table = (
	SELECT IF(
		EXISTS (
			SELECT 1
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'faq_choice'
		)
		AND NOT EXISTS (
			SELECT 1
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'question_choice'
		),
		'RENAME TABLE `faq_choice` TO `question_choice`',
		'SELECT 1'
	)
);
PREPARE stmt FROM @rename_question_choice_table;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_mission_question_table = (
	SELECT IF(
		EXISTS (
			SELECT 1
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'mission_faq'
		)
		AND NOT EXISTS (
			SELECT 1
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'mission_question'
		),
		'RENAME TABLE `mission_faq` TO `mission_question`',
		'SELECT 1'
	)
);
PREPARE stmt FROM @rename_mission_question_table;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_user_question_response_table = (
	SELECT IF(
		EXISTS (
			SELECT 1
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'user_faq_response'
		)
		AND NOT EXISTS (
			SELECT 1
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'user_question_response'
		),
		'RENAME TABLE `user_faq_response` TO `user_question_response`',
		'SELECT 1'
	)
);
PREPARE stmt FROM @rename_user_question_response_table;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_question_choice_column = (
	SELECT IF(
		EXISTS (
			SELECT 1
			FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'question_choice'
			  AND COLUMN_NAME = 'IDfaq'
		)
		AND NOT EXISTS (
			SELECT 1
			FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'question_choice'
			  AND COLUMN_NAME = 'IDquestion'
		),
		'ALTER TABLE `question_choice` CHANGE `IDfaq` `IDquestion` int(11) DEFAULT NULL',
		'SELECT 1'
	)
);
PREPARE stmt FROM @rename_question_choice_column;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_mission_question_column = (
	SELECT IF(
		EXISTS (
			SELECT 1
			FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'mission_question'
			  AND COLUMN_NAME = 'IDfaq'
		)
		AND NOT EXISTS (
			SELECT 1
			FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'mission_question'
			  AND COLUMN_NAME = 'IDquestion'
		),
		'ALTER TABLE `mission_question` CHANGE `IDfaq` `IDquestion` int(11) DEFAULT NULL',
		'SELECT 1'
	)
);
PREPARE stmt FROM @rename_mission_question_column;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_user_question_response_column = (
	SELECT IF(
		EXISTS (
			SELECT 1
			FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'user_question_response'
			  AND COLUMN_NAME = 'IDfaq'
		)
		AND NOT EXISTS (
			SELECT 1
			FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'user_question_response'
			  AND COLUMN_NAME = 'IDquestion'
		),
		'ALTER TABLE `user_question_response` CHANGE `IDfaq` `IDquestion` int(11) DEFAULT NULL',
		'SELECT 1'
	)
);
PREPARE stmt FROM @rename_user_question_response_column;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
