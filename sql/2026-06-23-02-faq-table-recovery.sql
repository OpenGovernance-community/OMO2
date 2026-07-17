-- @migration

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `faq` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `IDhowto` int(10) UNSIGNED DEFAULT NULL,
  `IDorganization` int(10) UNSIGNED DEFAULT NULL,
  `IDholon` int(10) UNSIGNED DEFAULT NULL,
  `IDparcours` int(10) UNSIGNED DEFAULT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `image` varchar(1000) DEFAULT NULL,
  `video` varchar(1000) DEFAULT NULL,
  `detail` text DEFAULT NULL,
  `displayorder` int(11) DEFAULT 0,
  `isactive` tinyint(1) DEFAULT 1,
  `viewcount` int(11) DEFAULT 0,
  `positive_score` float NOT NULL DEFAULT 0,
  `negative_score` float NOT NULL DEFAULT 0,
  `total_votes` int(11) NOT NULL DEFAULT 0,
  `reliability` float NOT NULL DEFAULT 0,
  `reliability_updated_at` datetime DEFAULT NULL,
  `score_decayed_at` datetime DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_faq_reliability` (`reliability`),
  KEY `idx_faq_reliability_updated_at` (`reliability_updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `faq`
  ADD COLUMN IF NOT EXISTS `IDorganization` int(10) UNSIGNED DEFAULT NULL AFTER `IDhowto`,
  ADD COLUMN IF NOT EXISTS `IDholon` int(10) UNSIGNED DEFAULT NULL AFTER `IDorganization`,
  ADD COLUMN IF NOT EXISTS `IDparcours` int(10) UNSIGNED DEFAULT NULL AFTER `IDholon`,
  ADD COLUMN IF NOT EXISTS `image` varchar(1000) DEFAULT NULL AFTER `answer`,
  ADD COLUMN IF NOT EXISTS `video` varchar(1000) DEFAULT NULL AFTER `image`,
  ADD COLUMN IF NOT EXISTS `displayorder` int(11) DEFAULT 0 AFTER `detail`,
  ADD COLUMN IF NOT EXISTS `isactive` tinyint(1) DEFAULT 1 AFTER `displayorder`,
  ADD COLUMN IF NOT EXISTS `viewcount` int(11) DEFAULT 0 AFTER `isactive`,
  ADD COLUMN IF NOT EXISTS `positive_score` float NOT NULL DEFAULT 0 AFTER `viewcount`,
  ADD COLUMN IF NOT EXISTS `negative_score` float NOT NULL DEFAULT 0 AFTER `positive_score`,
  ADD COLUMN IF NOT EXISTS `total_votes` int(11) NOT NULL DEFAULT 0 AFTER `negative_score`,
  ADD COLUMN IF NOT EXISTS `reliability` float NOT NULL DEFAULT 0 AFTER `total_votes`,
  ADD COLUMN IF NOT EXISTS `reliability_updated_at` datetime DEFAULT NULL AFTER `reliability`,
  ADD COLUMN IF NOT EXISTS `score_decayed_at` datetime DEFAULT NULL AFTER `reliability_updated_at`;

ALTER TABLE `faq`
  ADD KEY IF NOT EXISTS `idx_faq_reliability` (`reliability`),
  ADD KEY IF NOT EXISTS `idx_faq_reliability_updated_at` (`reliability_updated_at`);

SET @faq_recover_from_question_sql = (
  SELECT IF(
    EXISTS (
      SELECT 1
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'question'
    ),
    IF(
      (
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'question'
          AND COLUMN_NAME IN ('IDorganization', 'IDholon', 'image', 'video', 'reliability_updated_at')
      ) >= 5,
    'INSERT INTO `faq` (
        `id`,
        `IDhowto`,
        `IDorganization`,
        `IDholon`,
        `question`,
        `answer`,
        `image`,
        `video`,
        `detail`,
        `displayorder`,
        `isactive`,
        `viewcount`,
        `positive_score`,
        `negative_score`,
        `total_votes`,
        `reliability`,
        `reliability_updated_at`,
        `score_decayed_at`,
        `created`,
        `updated`
      )
      SELECT
        q.`id`,
        q.`IDhowto`,
        q.`IDorganization`,
        q.`IDholon`,
        q.`question`,
        q.`answer`,
        q.`image`,
        q.`video`,
        q.`detail`,
        COALESCE(q.`displayorder`, 0),
        COALESCE(q.`isactive`, 1),
        COALESCE(q.`viewcount`, 0),
        COALESCE(q.`positive_score`, 0),
        COALESCE(q.`negative_score`, 0),
        COALESCE(q.`total_votes`, 0),
        COALESCE(q.`reliability`, 0),
        q.`reliability_updated_at`,
        q.`score_decayed_at`,
        q.`created`,
        q.`updated`
      FROM `question` q
      LEFT JOIN `mission_question` mq
        ON mq.`IDquestion` = q.`id`
      LEFT JOIN `faq` f
        ON f.`id` = q.`id`
      WHERE mq.`IDquestion` IS NULL
        AND f.`id` IS NULL',
      'SELECT 1'
    ),
    'SELECT 1'
  )
);
PREPARE stmt FROM @faq_recover_from_question_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
