-- @migration
-- Authority tree and its time-limited rules.

CREATE TABLE IF NOT EXISTS `authority` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDholon` int(11) NOT NULL,
    `IDauthority_parent` int(11) DEFAULT NULL,
    `label` varchar(255) NOT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_authority_holon` (`IDholon`),
    KEY `idx_authority_parent` (`IDauthority_parent`),
    KEY `idx_authority_label` (`label`),
    CONSTRAINT `fk_authority_holon`
        FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_authority_parent`
        FOREIGN KEY (`IDauthority_parent`) REFERENCES `authority` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `authority_rule` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDauthority` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` mediumtext NOT NULL,
    `review_date` date NOT NULL,
    `expiration_date` date NOT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_authority_rule_authority` (`IDauthority`),
    KEY `idx_authority_rule_review` (`review_date`),
    KEY `idx_authority_rule_expiration` (`expiration_date`),
    CONSTRAINT `fk_authority_rule_authority`
        FOREIGN KEY (`IDauthority`) REFERENCES `authority` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
