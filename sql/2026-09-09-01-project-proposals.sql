-- @migration
-- Track project proposals and allow a contextual proposal permission.

ALTER TABLE `project`
    ADD COLUMN IF NOT EXISTS `IDuser_proposed` int(11) DEFAULT NULL AFTER `IDuser`,
    ADD COLUMN IF NOT EXISTS `proposal_status` varchar(20) NOT NULL DEFAULT 'normal' AFTER `project_kind`,
    ADD COLUMN IF NOT EXISTS `proposed_at` datetime DEFAULT NULL AFTER `proposal_status`,
    ADD COLUMN IF NOT EXISTS `proposal_decided_at` datetime DEFAULT NULL AFTER `proposed_at`,
    ADD KEY IF NOT EXISTS `idx_project_proposer` (`IDuser_proposed`),
    ADD KEY IF NOT EXISTS `idx_project_proposal_status` (`proposal_status`, `active`);

SET @fk_project_proposer_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'project'
      AND CONSTRAINT_NAME = 'fk_project_proposer'
);
SET @fk_project_proposer_sql := IF(
    @fk_project_proposer_exists = 0,
    'ALTER TABLE `project` ADD CONSTRAINT `fk_project_proposer` FOREIGN KEY (`IDuser_proposed`) REFERENCES `user` (`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @fk_project_proposer_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES
    ('CAN_PROPOSE_PROJECT', 'Proposer des projets', 'Autorise la proposition de projets au role ou cercle cible.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `iscontextual` = VALUES(`iscontextual`),
    `updated_at` = NOW();
