-- @migration
INSERT INTO `application` (`id`, `label`, `hash`, `directory`, `icon`, `drawer`, `url`, `navigationmode`, `position`, `requires_login`, `active`)
VALUES (3, 'Reglement', 'policy', 'policy', 'images/tools/policy.png', 'drawer_policy', 'api/policy/index.php', 'drawer', 30, 1, 1)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `hash` = VALUES(`hash`), `directory` = VALUES(`directory`), `icon` = VALUES(`icon`), `drawer` = VALUES(`drawer`), `url` = VALUES(`url`), `navigationmode` = VALUES(`navigationmode`), `position` = VALUES(`position`), `requires_login` = VALUES(`requires_login`), `active` = VALUES(`active`);

INSERT IGNORE INTO `organization_application` (`IDorganization`, `IDapplication`, `position`, `active`)
SELECT o.id, a.id, a.position, 1 FROM `organization` o INNER JOIN `application` a ON a.id = 3;
