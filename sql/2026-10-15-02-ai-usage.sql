-- @migration
CREATE TABLE IF NOT EXISTS `ai_usage` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `IDuser` int DEFAULT NULL,
  `IDorganization` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `origin` varchar(20) NOT NULL,
  `kind` varchar(20) NOT NULL,
  `feature` varchar(190) NOT NULL,
  `provider` varchar(40) DEFAULT NULL,
  `model` varchar(120) DEFAULT NULL,
  `input_tokens` bigint DEFAULT NULL,
  `output_tokens` bigint DEFAULT NULL,
  `total_tokens` bigint DEFAULT NULL,
  `input_chars` bigint NOT NULL DEFAULT 0,
  `output_chars` bigint NOT NULL DEFAULT 0,
  `audio_bytes` bigint NOT NULL DEFAULT 0,
  `success` tinyint NOT NULL DEFAULT 0,
  `http_status` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ai_usage_org_date` (`IDorganization`, `created_at`),
  KEY `idx_ai_usage_user_date` (`IDuser`, `created_at`),
  KEY `idx_ai_usage_origin_date` (`origin`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
