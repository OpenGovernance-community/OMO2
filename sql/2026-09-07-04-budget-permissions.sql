-- @migration
INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES
    ('CAN_EDIT_HOLON_BUDGET', 'Modifier les budgets de holons', 'Autorise la modification des budgets temps et argent des holons dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_EDIT_AFFECTATION_BUDGET', 'Modifier les budgets des affectations', 'Autorise la modification des budgets temps et argent des affectations dans le contexte cible.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `iscontextual` = VALUES(`iscontextual`),
    `updated_at` = NOW();
