-- @migration
-- Allow circle scope without changing the scope of existing rules.
ALTER TABLE `rule`
    DROP CONSTRAINT `chk_rule_scope`,
    ADD CONSTRAINT `chk_rule_scope` CHECK (`scope` IN ('global', 'descendants', 'circle', 'local'));
