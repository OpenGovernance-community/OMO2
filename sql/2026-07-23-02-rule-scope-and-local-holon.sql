-- @migration
-- Rename authority rules and support local holon rules with a display scope.

RENAME TABLE `authority_rule` TO `rule`;

ALTER TABLE `rule`
    DROP FOREIGN KEY `fk_authority_rule_authority`,
    DROP INDEX `idx_authority_rule_authority`,
    DROP INDEX `idx_authority_rule_review`,
    DROP INDEX `idx_authority_rule_expiration`,
    MODIFY COLUMN `IDauthority` int(11) DEFAULT NULL,
    ADD COLUMN `IDholon` int(11) DEFAULT NULL AFTER `IDauthority`,
    ADD COLUMN `scope` varchar(20) NOT NULL DEFAULT 'local' AFTER `IDholon`,
    ADD COLUMN `intention` mediumtext NOT NULL AFTER `title`,
    ADD KEY `idx_rule_authority` (`IDauthority`),
    ADD KEY `idx_rule_holon` (`IDholon`),
    ADD KEY `idx_rule_scope` (`scope`),
    ADD KEY `idx_rule_review` (`review_date`),
    ADD KEY `idx_rule_expiration` (`expiration_date`),
    ADD CONSTRAINT `fk_rule_authority`
        FOREIGN KEY (`IDauthority`) REFERENCES `authority` (`id`) ON DELETE RESTRICT,
    ADD CONSTRAINT `fk_rule_holon`
        FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `chk_rule_source`
        CHECK ((`IDauthority` IS NULL) <> (`IDholon` IS NULL)),
    ADD CONSTRAINT `chk_rule_scope`
        CHECK (`scope` IN ('global', 'descendants', 'local'));
