-- @migration
ALTER TABLE `faq`
  MODIFY COLUMN `answer` text DEFAULT NULL;
