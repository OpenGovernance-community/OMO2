-- @migration
-- Non-authenticating browser recognition, independent of remember-me tokens.
CREATE TABLE IF NOT EXISTS `user_login_device` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDuser` int(11) NOT NULL,
    `token_hash` char(64) NOT NULL,
    `expires_at` datetime NOT NULL,
    `created_at` datetime NOT NULL,
    `last_login_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_user_login_device` (`IDuser`, `token_hash`),
    CONSTRAINT `fk_user_login_device_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
