-- @migration
-- Shared PV timer: server timestamp, elapsed/remaining milliseconds and playback state.
SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN IF NOT EXISTS `pv_timer` text DEFAULT NULL;
