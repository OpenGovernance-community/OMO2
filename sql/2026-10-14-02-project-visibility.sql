-- @migration
-- Existing projects keep their organization visibility; recover their personal owner from history.
ALTER TABLE `project`
    ADD COLUMN IF NOT EXISTS `visibility_type` varchar(30) NOT NULL DEFAULT 'organization',
    ADD COLUMN IF NOT EXISTS `IDusercreation` int(11) DEFAULT NULL,
    ADD KEY IF NOT EXISTS `idx_project_creator` (`IDusercreation`);

UPDATE `project` p
INNER JOIN `history` h ON h.id = (
    SELECT MIN(hc.id) FROM `history` hc
    WHERE hc.target_type = 'project' AND hc.target_id = p.id AND hc.IDorganization = p.IDorganization AND hc.action = 'project_created'
)
INNER JOIN `user` u ON u.id = h.IDuser
SET p.IDusercreation = u.id
WHERE p.IDusercreation IS NULL;

SET @project_creator_fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'project' AND CONSTRAINT_NAME = 'fk_project_creator'
);
SET @project_creator_fk_sql := IF(@project_creator_fk_exists = 0,
    'ALTER TABLE `project` ADD CONSTRAINT `fk_project_creator` FOREIGN KEY (`IDusercreation`) REFERENCES `user` (`id`) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @project_creator_fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
