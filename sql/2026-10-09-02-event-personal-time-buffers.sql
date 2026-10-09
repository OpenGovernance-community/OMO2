-- @migration
CREATE TABLE IF NOT EXISTS `event_time_buffer` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDevent` int(11) NOT NULL,
    `IDuser` int(11) NOT NULL,
    `preparation_minutes` smallint unsigned NOT NULL DEFAULT 0,
    `closing_minutes` smallint unsigned NOT NULL DEFAULT 0,
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_event_time_buffer_user` (`IDevent`, `IDuser`),
    KEY `idx_event_time_buffer_user` (`IDuser`),
    CONSTRAINT `fk_event_time_buffer_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_event_time_buffer_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Previous event-wide choices belong to their creator. Invitees start with
-- no personal buffer until they choose their own travel/preparation durations.
INSERT IGNORE INTO `event_time_buffer` (`IDevent`, `IDuser`, `preparation_minutes`, `closing_minutes`)
SELECT e.id, e.IDuser, e.preparation_minutes, e.closing_minutes
FROM `event` e INNER JOIN `user` u ON u.id = e.IDuser
WHERE e.preparation_minutes > 0 OR e.closing_minutes > 0;

ALTER TABLE `event`
    DROP COLUMN `preparation_minutes`,
    DROP COLUMN `closing_minutes`;
