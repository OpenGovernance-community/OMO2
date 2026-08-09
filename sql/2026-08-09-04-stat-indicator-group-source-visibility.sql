-- @migration
-- Source visibility is owned by groups and applies only inside their own holon.

ALTER TABLE `stat_indicator_group`
    ADD COLUMN IF NOT EXISTS `hide_same_holon_sources` tinyint(1) NOT NULL DEFAULT 0 AFTER `chart_min_value`,
    ADD KEY `idx_stat_indicator_group_source_visibility` (`active`, `hide_same_holon_sources`, `IDholon`);

UPDATE `stat_indicator_group` g
INNER JOIN `stat_indicator_group_item` gi ON gi.`IDstatindicatorgroup` = g.`id`
INNER JOIN `stat_indicator` i ON i.`id` = gi.`IDstatindicator`
SET g.`hide_same_holon_sources` = 1
WHERE i.`hide_from_catalog` = 1
  AND g.`IDorganization` = i.`IDorganization`
  AND g.`IDholon` <=> i.`IDholon`;

UPDATE `stat_indicator`
SET `hide_from_catalog` = 0
WHERE `hide_from_catalog` <> 0;
