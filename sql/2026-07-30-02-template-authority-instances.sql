-- @migration
-- Track local authorities defined by templates and their concrete instances.

ALTER TABLE `authority`
    ADD COLUMN `IDauthority_template` int(11) DEFAULT NULL AFTER `IDholon`,
    ADD COLUMN `is_local` tinyint(1) NOT NULL DEFAULT 0 AFTER `IDauthority_parent`,
    ADD COLUMN `template_origin_lost` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_local`,
    ADD KEY `idx_authority_template` (`IDauthority_template`),
    ADD KEY `idx_authority_template_origin_lost` (`template_origin_lost`);
