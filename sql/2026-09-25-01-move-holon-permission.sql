-- @migration
INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES ('CAN_MOVE_HOLON', 'Deplacer des holons', 'Autorise le deplacement des holons couverts par ce droit vers les destinations couvertes par ce meme droit.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`),
    `description` = VALUES(`description`),
    `iscontextual` = VALUES(`iscontextual`),
    `updated_at` = NOW();
