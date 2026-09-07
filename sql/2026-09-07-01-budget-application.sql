-- @migration
INSERT INTO `application` (`label`, `hash`, `directory`, `icon`, `drawer`, `url`, `navigationmode`, `position`, `requires_login`, `active`)
VALUES ('Budget', 'budget', 'budget', 'images/tools/budget.png', 'drawer_budget', 'api/budget/index.php', 'drawer', 55, 1, 1)
ON DUPLICATE KEY UPDATE
    `label` = VALUES(`label`),
    `directory` = VALUES(`directory`),
    `icon` = VALUES(`icon`),
    `drawer` = VALUES(`drawer`),
    `url` = VALUES(`url`),
    `navigationmode` = VALUES(`navigationmode`),
    `position` = VALUES(`position`),
    `requires_login` = VALUES(`requires_login`),
    `active` = VALUES(`active`);

INSERT IGNORE INTO `organization_application` (`IDorganization`, `IDapplication`, `position`, `active`)
SELECT `o`.`id`, `a`.`id`, `a`.`position`, 1
FROM `organization` `o`
INNER JOIN `application` `a` ON `a`.`hash` = 'budget';
