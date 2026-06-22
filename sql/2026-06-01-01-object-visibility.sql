-- @migration

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `object_visibility` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `version` int(11) NOT NULL DEFAULT 1,
    `object_type` varchar(60) NOT NULL,
    `object_id` int(11) NOT NULL,
    `IDorganization` int(11) DEFAULT NULL,
    `visibility_type` varchar(30) NOT NULL DEFAULT 'organization',
    `IDholon` int(11) DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `datecreation` datetime DEFAULT NULL,
    `datemodification` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_object_visibility_object` (`object_type`, `object_id`, `active`),
    KEY `idx_object_visibility_org` (`IDorganization`, `active`),
    KEY `idx_object_visibility_holon` (`IDholon`),
    KEY `idx_object_visibility_type` (`visibility_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `object_visibility`
    ADD CONSTRAINT `fk_object_visibility_organization`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_object_visibility_holon`
        FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL;
