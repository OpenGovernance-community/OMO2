-- @migration
-- Track local authorities defined by templates and their concrete instances.

ALTER TABLE `authority`
    ADD COLUMN IF NOT EXISTS `IDauthority_template` int(11) DEFAULT NULL AFTER `IDholon`;

ALTER TABLE `authority`
    ADD COLUMN IF NOT EXISTS `is_local` tinyint(1) NOT NULL DEFAULT 0 AFTER `IDauthority_parent`;

ALTER TABLE `authority`
    ADD COLUMN IF NOT EXISTS `template_origin_lost` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_local`;

ALTER TABLE `authority`
    ADD KEY `idx_authority_template` (`IDauthority_template`);

ALTER TABLE `authority`
    ADD KEY `idx_authority_template_origin_lost` (`template_origin_lost`);
