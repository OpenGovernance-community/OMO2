-- @migration
-- Browser subscriptions used for OMO Web Push notifications.

CREATE TABLE IF NOT EXISTS `notification_push_subscription` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDuser` int(11) NOT NULL,
    `endpoint_hash` char(64) NOT NULL,
    `endpoint` text NOT NULL,
    `p256dh_key` varchar(200) NOT NULL,
    `auth_key` varchar(100) NOT NULL,
    `user_agent` varchar(1000) DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `last_error` text DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `last_seen_at` datetime DEFAULT NULL,
    `last_sent_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_notification_push_endpoint_hash` (`endpoint_hash`),
    KEY `idx_notification_push_user_active` (`IDuser`, `active`),
    CONSTRAINT `fk_notification_push_subscription_user`
        FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
