-- @migration
-- Project follow-up links

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `project_follower` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDproject` int(11) NOT NULL,
    `IDuser` int(11) NOT NULL,
    `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
    `active` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_project_follower` (`IDproject`, `IDuser`),
    KEY `idx_project_follower_project` (`IDproject`),
    KEY `idx_project_follower_user` (`IDuser`),
    KEY `idx_project_follower_active` (`active`),
    CONSTRAINT `fk_project_follower_project`
        FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_project_follower_user`
        FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
