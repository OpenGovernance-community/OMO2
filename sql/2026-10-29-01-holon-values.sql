-- @migration
CREATE TABLE IF NOT EXISTS `valeur` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `IDorganization` int(11) NOT NULL,
    `IDholon` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `principle` mediumtext NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_valeur_organization` (`IDorganization`),
    CONSTRAINT `fk_valeur_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_valeur_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `rule` ADD COLUMN IF NOT EXISTS `IDvaleur` int(11) DEFAULT NULL;
ALTER TABLE `rule` ADD CONSTRAINT `fk_rule_valeur` FOREIGN KEY (`IDvaleur`) REFERENCES `valeur` (`id`) ON DELETE SET NULL;
