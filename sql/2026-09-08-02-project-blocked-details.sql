-- @migration
-- Store the reason, review date and optional automatic reactivation of blocked projects.

SET NAMES utf8mb4;

ALTER TABLE `project`
    ADD COLUMN IF NOT EXISTS `blocked_reason` mediumtext DEFAULT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `blocked_until` date DEFAULT NULL AFTER `blocked_reason`,
    ADD COLUMN IF NOT EXISTS `blocked_auto_reactivate` tinyint(1) NOT NULL DEFAULT 0 AFTER `blocked_until`,
    ADD COLUMN IF NOT EXISTS `blocked_reactivate_status` varchar(20) NOT NULL DEFAULT 'ready' AFTER `blocked_auto_reactivate`;

CREATE INDEX IF NOT EXISTS `idx_project_blocked_until` ON `project` (`status`, `blocked_auto_reactivate`, `blocked_until`);
