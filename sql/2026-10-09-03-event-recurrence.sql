-- @migration
CREATE TABLE IF NOT EXISTS `event_recurrence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDreference_event` int(11) DEFAULT NULL,
  `frequency` varchar(24) NOT NULL,
  `interval_days` smallint unsigned NOT NULL DEFAULT 7,
  `month_day` tinyint unsigned NOT NULL DEFAULT 1,
  `weekday` tinyint unsigned NOT NULL DEFAULT 1,
  `ordinal` tinyint unsigned NOT NULL DEFAULT 1,
  `weekend_shift` varchar(16) NOT NULL DEFAULT 'none',
  `horizon_months` tinyint unsigned NOT NULL DEFAULT 3,
  `timezone` varchar(64) NOT NULL DEFAULT 'Europe/Zurich',
  `anchor_date` date NOT NULL,
  `anchor_position` int unsigned NOT NULL DEFAULT 0,
  `next_position` int unsigned NOT NULL DEFAULT 1,
  `parameters` longtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_recurrence_active` (`active`, `id`),
  KEY `idx_event_recurrence_organization` (`IDorganization`),
  CONSTRAINT `fk_event_recurrence_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_recurrence_reference` FOREIGN KEY (`IDreference_event`) REFERENCES `event` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `event`
  ADD COLUMN IF NOT EXISTS `IDeventrecurrence` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `recurrence_position` int unsigned DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `recurrence_exception` tinyint(1) NOT NULL DEFAULT 0,
  ADD UNIQUE KEY IF NOT EXISTS `uq_event_recurrence_position` (`IDeventrecurrence`, `recurrence_position`);

ALTER TABLE `event`
  ADD CONSTRAINT `fk_event_recurrence` FOREIGN KEY (`IDeventrecurrence`) REFERENCES `event_recurrence` (`id`) ON DELETE SET NULL;
