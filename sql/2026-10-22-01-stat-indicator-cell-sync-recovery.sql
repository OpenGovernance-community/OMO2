-- @migration
-- Older cell synchronizers advanced last_sync_at even when an unchanged value
-- was not saved as a measurement. Let the next cron read those cells again.
-- Do not fabricate historical values or reset dated table imports.

UPDATE stat_indicator AS i
SET spreadsheet_last_sync_at = NULL
WHERE i.active = 1
  AND i.source_type = 'spreadsheet_cell'
  AND i.spreadsheet_last_sync_at IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM stat_indicator_value AS v
      WHERE v.IDstatindicator = i.id
        AND v.measured_at >= i.spreadsheet_last_sync_at
  );

UPDATE stat_indicator AS i
SET ethercalc_last_sync_at = NULL
WHERE i.active = 1
  AND i.source_type = 'ethercalc_cell'
  AND i.ethercalc_last_sync_at IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM stat_indicator_value AS v
      WHERE v.IDstatindicator = i.id
        AND v.measured_at >= i.ethercalc_last_sync_at
  );
