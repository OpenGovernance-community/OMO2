-- @migration
ALTER TABLE `faq`
  ADD COLUMN IF NOT EXISTS `request_relayed_at` datetime DEFAULT NULL;
