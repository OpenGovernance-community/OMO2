-- @migration

ALTER TABLE `parcours`
    ADD COLUMN IF NOT EXISTS `isarchived` tinyint(1) NOT NULL DEFAULT 0;

INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES ('CAN_DELETE_PARCOURS', 'Supprimer un parcours', 'Autorise la suppression des parcours de l organisation. Les parcours encore utilises sont retires du partage et conserves pour leurs utilisateurs existants.', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `iscontextual` = 0;

UPDATE `permission`
SET `description` = 'Autorise la creation, l import et le detachement de parcours dans le contexte cible.'
WHERE `permission_key` = 'CAN_CREATE_PARCOURS';
