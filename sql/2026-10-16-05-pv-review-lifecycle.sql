-- @migration
SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `pv_review_started_at` datetime DEFAULT NULL AFTER `pvstage`,
  ADD KEY IF NOT EXISTS `idx_document_pv_review_due` (`documenttype`, `pvstage`, `active`, `pv_review_started_at`);

-- Legacy reviews start at the end of their meeting when no review date was retained.
UPDATE `document` d
JOIN `event` e ON e.id = d.IDevent AND e.IDorganization = d.IDorganization
SET d.pv_review_started_at = e.end_at
WHERE d.documenttype = 'pv' AND d.pvstage = 'review'
  AND d.pv_review_started_at IS NULL AND e.end_at IS NOT NULL;

CREATE TABLE IF NOT EXISTS `document_pv_notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdocument` int(11) NOT NULL,
  `stage` varchar(30) NOT NULL,
  `parameters` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_document_pv_notification_pending` (`processed_at`, `id`),
  CONSTRAINT `fk_document_pv_notification_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
