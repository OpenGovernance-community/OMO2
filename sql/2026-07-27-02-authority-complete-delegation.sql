-- @migration
-- Preserve structural authority markers after a complete delegation.

ALTER TABLE `authority`
    ADD COLUMN `is_shell` tinyint(1) NOT NULL DEFAULT 0 AFTER `description`,
    ADD KEY `idx_authority_shell` (`is_shell`);
