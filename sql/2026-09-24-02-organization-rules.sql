-- @migration
-- Allow rules attached directly to an organization without a holon.

ALTER TABLE `rule`
    ADD COLUMN `IDorganization` int(11) DEFAULT NULL AFTER `IDholon`,
    ADD KEY `idx_rule_organization` (`IDorganization`);

UPDATE `rule` r
LEFT JOIN `authority` a ON a.`id` = r.`IDauthority`
LEFT JOIN `holon` h ON h.`id` = COALESCE(r.`IDholon`, a.`IDholon`)
LEFT JOIN `holon` root ON root.`id` = h.`IDholon_org`
SET r.`IDorganization` = COALESCE(NULLIF(h.`IDorganization`, 0), root.`IDorganization`)
WHERE r.`IDorganization` IS NULL;

ALTER TABLE `rule`
    DROP CONSTRAINT `chk_rule_source`,
    ADD CONSTRAINT `chk_rule_source`
        CHECK ((`IDauthority` IS NULL AND `IDholon` IS NULL AND `IDorganization` IS NOT NULL)
            OR ((`IDauthority` IS NULL) <> (`IDholon` IS NULL))),
    ADD CONSTRAINT `fk_rule_organization`
        FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE;
