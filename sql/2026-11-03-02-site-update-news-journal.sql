-- @migration
-- Keep the prepared Markdown separate from the optional technical journal.
ALTER TABLE `site_update_news` ADD COLUMN `journal` mediumtext DEFAULT NULL AFTER `description`;
