-- @migration
-- OpenMyOrganization
-- Centralize history references on a generic target type and identifier.

SET NAMES utf8mb4;

UPDATE `history`
SET
  `target_type` = 'holon',
  `target_id` = CAST(JSON_UNQUOTE(JSON_EXTRACT(`parameters`, '$.IDholon')) AS UNSIGNED)
WHERE (`target_type` IS NULL OR `target_id` IS NULL)
  AND JSON_VALID(`parameters`)
  AND CAST(JSON_UNQUOTE(JSON_EXTRACT(`parameters`, '$.IDholon')) AS UNSIGNED) > 0;

UPDATE `history`
SET
  `target_type` = 'organization',
  `target_id` = CAST(JSON_UNQUOTE(JSON_EXTRACT(`parameters`, '$.IDorganization')) AS UNSIGNED)
WHERE (`target_type` IS NULL OR `target_id` IS NULL)
  AND JSON_VALID(`parameters`)
  AND CAST(JSON_UNQUOTE(JSON_EXTRACT(`parameters`, '$.IDorganization')) AS UNSIGNED) > 0;

ALTER TABLE `history`
  DROP INDEX IF EXISTS `idx_history_holon_circle`;

ALTER TABLE `history`
  DROP COLUMN IF EXISTS `IDholon_circle`;
