-- @migration
ALTER TABLE `faq`
  ADD COLUMN IF NOT EXISTS `request_user_id` int(10) UNSIGNED DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `request_author_name` varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `request_author_email` varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `request_description` text DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `request_answered_at` datetime DEFAULT NULL,
  ADD KEY IF NOT EXISTS `idx_faq_request_user_id` (`request_user_id`);
