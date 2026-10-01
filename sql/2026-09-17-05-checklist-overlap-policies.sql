-- @migration
-- Consolidate legacy process overlap policies into the explicit blocking behavior.
SET NAMES utf8mb4;

UPDATE `checklist_trigger`
SET `overlap_policy` = 'block'
WHERE `overlap_policy` IN ('reuse_open', 'skip', 'ask');
