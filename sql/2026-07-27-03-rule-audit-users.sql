-- @migration
-- Keep the creator and latest editor of every rule.

ALTER TABLE `rule`
    ADD COLUMN `IDuser_creation` int(11) DEFAULT NULL AFTER `updated_at`,
    ADD COLUMN `IDuser_modification` int(11) DEFAULT NULL AFTER `IDuser_creation`,
    ADD KEY `idx_rule_user_creation` (`IDuser_creation`),
    ADD KEY `idx_rule_user_modification` (`IDuser_modification`),
    ADD CONSTRAINT `fk_rule_user_creation`
        FOREIGN KEY (`IDuser_creation`) REFERENCES `user` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_rule_user_modification`
        FOREIGN KEY (`IDuser_modification`) REFERENCES `user` (`id`) ON DELETE SET NULL;
