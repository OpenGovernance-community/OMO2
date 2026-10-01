-- @migration
ALTER TABLE `faq`
  ADD COLUMN IF NOT EXISTS `request_ai_draft` tinyint(1) NOT NULL DEFAULT 0;
