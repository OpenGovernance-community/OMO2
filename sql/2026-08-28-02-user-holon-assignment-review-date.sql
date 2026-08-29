-- @migration
ALTER TABLE `user_holon`
  ADD COLUMN IF NOT EXISTS `assignment_review_date` date DEFAULT NULL AFTER `money_budget_recurrence`;
