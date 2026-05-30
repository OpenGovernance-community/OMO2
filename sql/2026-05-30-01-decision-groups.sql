-- @migration

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `decision_group` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDdecision_process` int(11) NOT NULL,
    `decision_type` varchar(20) NOT NULL DEFAULT 'decision',
    `evaluation_method` varchar(40) NOT NULL DEFAULT 'simple_vote',
    `title` varchar(190) NOT NULL,
    `description` mediumtext DEFAULT NULL,
    `parameters` mediumtext DEFAULT NULL,
    `position` int(11) NOT NULL DEFAULT 1,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_decision_group_process` (`IDdecision_process`),
    KEY `idx_decision_group_position` (`IDdecision_process`, `position`),
    KEY `idx_decision_group_active` (`active`),
    KEY `idx_decision_group_type` (`decision_type`),
    KEY `idx_decision_group_method` (`evaluation_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @decision_group_process_fk_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`TABLE_CONSTRAINTS`
    WHERE `CONSTRAINT_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_group'
      AND `CONSTRAINT_NAME` = 'fk_decision_group_process'
      AND `CONSTRAINT_TYPE` = 'FOREIGN KEY'
);
SET @decision_group_process_fk_sql := IF(
    @decision_group_process_fk_exists = 0,
    'ALTER TABLE `decision_group` ADD CONSTRAINT `fk_decision_group_process` FOREIGN KEY (`IDdecision_process`) REFERENCES `decision_process` (`id`) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE decision_group_process_fk_stmt FROM @decision_group_process_fk_sql;
EXECUTE decision_group_process_fk_stmt;
DEALLOCATE PREPARE decision_group_process_fk_stmt;

SET @decision_proposal_group_column_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_proposal'
      AND `COLUMN_NAME` = 'IDdecision_group'
);
SET @decision_proposal_group_column_sql := IF(
    @decision_proposal_group_column_exists = 0,
    'ALTER TABLE `decision_proposal` ADD COLUMN `IDdecision_group` int(11) DEFAULT NULL AFTER `IDdecision_process`',
    'SELECT 1'
);
PREPARE decision_proposal_group_column_stmt FROM @decision_proposal_group_column_sql;
EXECUTE decision_proposal_group_column_stmt;
DEALLOCATE PREPARE decision_proposal_group_column_stmt;

SET @decision_proposal_group_index_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_proposal'
      AND `INDEX_NAME` = 'idx_decision_proposal_group'
);
SET @decision_proposal_group_index_sql := IF(
    @decision_proposal_group_index_exists = 0,
    'ALTER TABLE `decision_proposal` ADD KEY `idx_decision_proposal_group` (`IDdecision_group`)',
    'SELECT 1'
);
PREPARE decision_proposal_group_index_stmt FROM @decision_proposal_group_index_sql;
EXECUTE decision_proposal_group_index_stmt;
DEALLOCATE PREPARE decision_proposal_group_index_stmt;

SET @decision_proposal_group_position_index_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_proposal'
      AND `INDEX_NAME` = 'idx_decision_proposal_group_position'
);
SET @decision_proposal_group_position_index_sql := IF(
    @decision_proposal_group_position_index_exists = 0,
    'ALTER TABLE `decision_proposal` ADD KEY `idx_decision_proposal_group_position` (`IDdecision_group`, `position`)',
    'SELECT 1'
);
PREPARE decision_proposal_group_position_index_stmt FROM @decision_proposal_group_position_index_sql;
EXECUTE decision_proposal_group_position_index_stmt;
DEALLOCATE PREPARE decision_proposal_group_position_index_stmt;

SET @decision_response_group_column_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_response'
      AND `COLUMN_NAME` = 'IDdecision_group'
);
SET @decision_response_group_column_sql := IF(
    @decision_response_group_column_exists = 0,
    'ALTER TABLE `decision_response` ADD COLUMN `IDdecision_group` int(11) DEFAULT NULL AFTER `IDdecision_process`',
    'SELECT 1'
);
PREPARE decision_response_group_column_stmt FROM @decision_response_group_column_sql;
EXECUTE decision_response_group_column_stmt;
DEALLOCATE PREPARE decision_response_group_column_stmt;

SET @decision_response_group_index_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_response'
      AND `INDEX_NAME` = 'idx_decision_response_group'
);
SET @decision_response_group_index_sql := IF(
    @decision_response_group_index_exists = 0,
    'ALTER TABLE `decision_response` ADD KEY `idx_decision_response_group` (`IDdecision_group`)',
    'SELECT 1'
);
PREPARE decision_response_group_index_stmt FROM @decision_response_group_index_sql;
EXECUTE decision_response_group_index_stmt;
DEALLOCATE PREPARE decision_response_group_index_stmt;

SET @decision_result_group_column_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_result'
      AND `COLUMN_NAME` = 'IDdecision_group'
);
SET @decision_result_group_column_sql := IF(
    @decision_result_group_column_exists = 0,
    'ALTER TABLE `decision_result` ADD COLUMN `IDdecision_group` int(11) DEFAULT NULL AFTER `IDdecision_process`',
    'SELECT 1'
);
PREPARE decision_result_group_column_stmt FROM @decision_result_group_column_sql;
EXECUTE decision_result_group_column_stmt;
DEALLOCATE PREPARE decision_result_group_column_stmt;

SET @decision_result_group_index_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_result'
      AND `INDEX_NAME` = 'idx_decision_result_group'
);
SET @decision_result_group_index_sql := IF(
    @decision_result_group_index_exists = 0,
    'ALTER TABLE `decision_result` ADD KEY `idx_decision_result_group` (`IDdecision_group`)',
    'SELECT 1'
);
PREPARE decision_result_group_index_stmt FROM @decision_result_group_index_sql;
EXECUTE decision_result_group_index_stmt;
DEALLOCATE PREPARE decision_result_group_index_stmt;

INSERT INTO `decision_group` (
    `IDdecision_process`,
    `decision_type`,
    `evaluation_method`,
    `title`,
    `description`,
    `parameters`,
    `position`,
    `active`
)
SELECT
    dp.`id`,
    dp.`decision_type`,
    dp.`evaluation_method`,
    dp.`title`,
    dp.`description`,
    dp.`parameters`,
    1,
    1
FROM `decision_process` dp
LEFT JOIN `decision_group` dg
    ON dg.`IDdecision_process` = dp.`id`
WHERE dg.`id` IS NULL;

UPDATE `decision_proposal` proposal
INNER JOIN (
    SELECT grp1.`IDdecision_process`, MIN(grp1.`id`) AS `primary_group_id`
    FROM `decision_group` grp1
    GROUP BY grp1.`IDdecision_process`
) grp
    ON grp.`IDdecision_process` = proposal.`IDdecision_process`
SET proposal.`IDdecision_group` = grp.`primary_group_id`
WHERE proposal.`IDdecision_group` IS NULL;

UPDATE `decision_response` response
INNER JOIN (
    SELECT grp1.`IDdecision_process`, MIN(grp1.`id`) AS `primary_group_id`
    FROM `decision_group` grp1
    GROUP BY grp1.`IDdecision_process`
) grp
    ON grp.`IDdecision_process` = response.`IDdecision_process`
SET response.`IDdecision_group` = grp.`primary_group_id`
WHERE response.`IDdecision_group` IS NULL;

UPDATE `decision_result` result_item
INNER JOIN (
    SELECT grp1.`IDdecision_process`, MIN(grp1.`id`) AS `primary_group_id`
    FROM `decision_group` grp1
    GROUP BY grp1.`IDdecision_process`
) grp
    ON grp.`IDdecision_process` = result_item.`IDdecision_process`
SET result_item.`IDdecision_group` = grp.`primary_group_id`
WHERE result_item.`IDdecision_group` IS NULL;

ALTER TABLE `decision_proposal`
    MODIFY COLUMN `IDdecision_group` int(11) NOT NULL;

SET @decision_proposal_group_fk_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`TABLE_CONSTRAINTS`
    WHERE `CONSTRAINT_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_proposal'
      AND `CONSTRAINT_NAME` = 'fk_decision_proposal_group'
      AND `CONSTRAINT_TYPE` = 'FOREIGN KEY'
);
SET @decision_proposal_group_fk_sql := IF(
    @decision_proposal_group_fk_exists = 0,
    'ALTER TABLE `decision_proposal` ADD CONSTRAINT `fk_decision_proposal_group` FOREIGN KEY (`IDdecision_group`) REFERENCES `decision_group` (`id`) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE decision_proposal_group_fk_stmt FROM @decision_proposal_group_fk_sql;
EXECUTE decision_proposal_group_fk_stmt;
DEALLOCATE PREPARE decision_proposal_group_fk_stmt;

SET @decision_response_old_unique_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_response'
      AND `INDEX_NAME` = 'uniq_decision_response_participant'
);
SET @decision_response_process_index_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_response'
      AND `INDEX_NAME` = 'idx_decision_response_process'
);
SET @decision_response_process_index_sql := IF(
    @decision_response_process_index_exists = 0,
    'ALTER TABLE `decision_response` ADD KEY `idx_decision_response_process` (`IDdecision_process`)',
    'SELECT 1'
);
PREPARE decision_response_process_index_stmt FROM @decision_response_process_index_sql;
EXECUTE decision_response_process_index_stmt;
DEALLOCATE PREPARE decision_response_process_index_stmt;

SET @decision_response_old_unique_sql := IF(
    @decision_response_old_unique_exists > 0,
    'ALTER TABLE `decision_response` DROP INDEX `uniq_decision_response_participant`',
    'SELECT 1'
);
PREPARE decision_response_old_unique_stmt FROM @decision_response_old_unique_sql;
EXECUTE decision_response_old_unique_stmt;
DEALLOCATE PREPARE decision_response_old_unique_stmt;

ALTER TABLE `decision_response`
    MODIFY COLUMN `IDdecision_group` int(11) NOT NULL;

SET @decision_response_group_unique_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_response'
      AND `INDEX_NAME` = 'uniq_decision_response_group_participant'
);
SET @decision_response_group_unique_sql := IF(
    @decision_response_group_unique_exists = 0,
    'ALTER TABLE `decision_response` ADD UNIQUE KEY `uniq_decision_response_group_participant` (`IDdecision_group`, `IDdecision_participant`)',
    'SELECT 1'
);
PREPARE decision_response_group_unique_stmt FROM @decision_response_group_unique_sql;
EXECUTE decision_response_group_unique_stmt;
DEALLOCATE PREPARE decision_response_group_unique_stmt;

SET @decision_response_group_fk_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`TABLE_CONSTRAINTS`
    WHERE `CONSTRAINT_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_response'
      AND `CONSTRAINT_NAME` = 'fk_decision_response_group'
      AND `CONSTRAINT_TYPE` = 'FOREIGN KEY'
);
SET @decision_response_group_fk_sql := IF(
    @decision_response_group_fk_exists = 0,
    'ALTER TABLE `decision_response` ADD CONSTRAINT `fk_decision_response_group` FOREIGN KEY (`IDdecision_group`) REFERENCES `decision_group` (`id`) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE decision_response_group_fk_stmt FROM @decision_response_group_fk_sql;
EXECUTE decision_response_group_fk_stmt;
DEALLOCATE PREPARE decision_response_group_fk_stmt;

SET @decision_result_old_unique_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_result'
      AND `INDEX_NAME` = 'uniq_decision_result_process'
);
SET @decision_result_process_index_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_result'
      AND `INDEX_NAME` = 'idx_decision_result_process'
);
SET @decision_result_process_index_sql := IF(
    @decision_result_process_index_exists = 0,
    'ALTER TABLE `decision_result` ADD KEY `idx_decision_result_process` (`IDdecision_process`)',
    'SELECT 1'
);
PREPARE decision_result_process_index_stmt FROM @decision_result_process_index_sql;
EXECUTE decision_result_process_index_stmt;
DEALLOCATE PREPARE decision_result_process_index_stmt;

SET @decision_result_old_unique_sql := IF(
    @decision_result_old_unique_exists > 0,
    'ALTER TABLE `decision_result` DROP INDEX `uniq_decision_result_process`',
    'SELECT 1'
);
PREPARE decision_result_old_unique_stmt FROM @decision_result_old_unique_sql;
EXECUTE decision_result_old_unique_stmt;
DEALLOCATE PREPARE decision_result_old_unique_stmt;

ALTER TABLE `decision_result`
    MODIFY COLUMN `IDdecision_group` int(11) NOT NULL;

SET @decision_result_group_unique_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_result'
      AND `INDEX_NAME` = 'uniq_decision_result_group'
);
SET @decision_result_group_unique_sql := IF(
    @decision_result_group_unique_exists = 0,
    'ALTER TABLE `decision_result` ADD UNIQUE KEY `uniq_decision_result_group` (`IDdecision_group`)',
    'SELECT 1'
);
PREPARE decision_result_group_unique_stmt FROM @decision_result_group_unique_sql;
EXECUTE decision_result_group_unique_stmt;
DEALLOCATE PREPARE decision_result_group_unique_stmt;

SET @decision_result_group_fk_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`TABLE_CONSTRAINTS`
    WHERE `CONSTRAINT_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'decision_result'
      AND `CONSTRAINT_NAME` = 'fk_decision_result_group'
      AND `CONSTRAINT_TYPE` = 'FOREIGN KEY'
);
SET @decision_result_group_fk_sql := IF(
    @decision_result_group_fk_exists = 0,
    'ALTER TABLE `decision_result` ADD CONSTRAINT `fk_decision_result_group` FOREIGN KEY (`IDdecision_group`) REFERENCES `decision_group` (`id`) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE decision_result_group_fk_stmt FROM @decision_result_group_fk_sql;
EXECUTE decision_result_group_fk_stmt;
DEALLOCATE PREPARE decision_result_group_fk_stmt;
