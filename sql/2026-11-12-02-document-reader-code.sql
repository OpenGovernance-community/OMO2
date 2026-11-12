-- @migration

ALTER TABLE `document`
    ADD COLUMN IF NOT EXISTS `readercode` varchar(48) DEFAULT NULL;

UPDATE `document`
SET `readercode` = LOWER(HEX(RANDOM_BYTES(24)))
WHERE `readercode` IS NULL OR `readercode` = '';

CREATE UNIQUE INDEX IF NOT EXISTS `idx_document_readercode` ON `document` (`readercode`);
