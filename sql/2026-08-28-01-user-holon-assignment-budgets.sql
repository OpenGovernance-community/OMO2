-- @migration
ALTER TABLE `user_holon`
  ADD COLUMN IF NOT EXISTS `time_budget_hours` decimal(12,2) DEFAULT NULL AFTER `focus`,
  ADD COLUMN IF NOT EXISTS `time_budget_recurrence` varchar(10) DEFAULT NULL AFTER `time_budget_hours`,
  ADD COLUMN IF NOT EXISTS `money_budget` decimal(12,2) DEFAULT NULL AFTER `time_budget_recurrence`,
  ADD COLUMN IF NOT EXISTS `money_budget_recurrence` varchar(10) DEFAULT NULL AFTER `money_budget`;
