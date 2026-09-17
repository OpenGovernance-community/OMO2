-- @migration
-- Separate object operations while retaining existing configured scopes.
INSERT INTO `permission` (`permission_key`, `title`, `description`, `iscontextual`, `created_at`, `updated_at`)
VALUES
    ('CAN_EDIT_PROJECT', 'Modifier des projets', 'Autorise la modification des projets et de leurs taches.', 1, NOW(), NOW()),
    ('CAN_CREATE_RULE', 'Creer des regles', 'Autorise la creation de regles dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_EDIT_RULE', 'Modifier des regles', 'Autorise la modification des regles dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_DELETE_RULE', 'Supprimer des regles', 'Autorise la suppression des regles dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_EDIT_INDICATOR', 'Modifier des indicateurs', 'Autorise la modification des indicateurs, de leurs valeurs, groupes et imports.', 1, NOW(), NOW()),
    ('CAN_DELETE_INDICATOR', 'Supprimer des indicateurs', 'Autorise le retrait des indicateurs, groupes et imports du contexte cible.', 1, NOW(), NOW()),
    ('CAN_EDIT_DOCUMENT', 'Modifier des documents', 'Autorise la modification des documents dans le respect de leur portee d edition.', 1, NOW(), NOW()),
    ('CAN_DELETE_DOCUMENT', 'Supprimer des documents', 'Autorise la suppression des documents dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_EDIT_MEMBER_ASSIGNMENT', 'Modifier les affectations', 'Autorise la modification du focus et de la date de revue des affectations.', 1, NOW(), NOW()),
    ('CAN_DELETE_MEMBER', 'Retirer des membres', 'Autorise le retrait des membres et l annulation de leurs invitations, sans supprimer leur compte.', 1, NOW(), NOW()),
    ('CAN_EDIT_DECISION', 'Modifier des decisions', 'Autorise la gestion des prises de decision dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_DELETE_DECISION', 'Supprimer des decisions', 'Autorise la suppression des prises de decision dans le respect de leur cycle de vie.', 1, NOW(), NOW()),
    ('CAN_EDIT_FAQ', 'Modifier des FAQ', 'Autorise la modification des FAQ dans le contexte cible.', 1, NOW(), NOW()),
    ('CAN_DELETE_FAQ', 'Supprimer des FAQ', 'Autorise la suppression des FAQ dans le contexte cible.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `title` = VALUES(`title`), `description` = VALUES(`description`),
    `iscontextual` = VALUES(`iscontextual`), `updated_at` = NOW();

INSERT IGNORE INTO `holon_permission` (`IDholon`, `IDpermission`, `member_type`, `range`, `created_at`, `updated_at`)
SELECT hp.`IDholon`, target.`id`, hp.`member_type`, hp.`range`, NOW(), NOW()
FROM `holon_permission` hp
INNER JOIN `permission` source ON source.`id` = hp.`IDpermission`
INNER JOIN (
    SELECT 'CAN_CREATE_PROJECT' AS source_key, 'CAN_EDIT_PROJECT' AS target_key
    UNION ALL SELECT 'CAN_CREATE_INDICATOR' AS source_key, 'CAN_EDIT_INDICATOR' AS target_key
    UNION ALL SELECT 'CAN_CREATE_INDICATOR' AS source_key, 'CAN_DELETE_INDICATOR' AS target_key
    UNION ALL SELECT 'CAN_CREATE_DOCUMENT' AS source_key, 'CAN_EDIT_DOCUMENT' AS target_key
    UNION ALL SELECT 'CAN_CREATE_DOCUMENT' AS source_key, 'CAN_DELETE_DOCUMENT' AS target_key
    UNION ALL SELECT 'CAN_CREATE_DECISION' AS source_key, 'CAN_EDIT_DECISION' AS target_key
    UNION ALL SELECT 'CAN_CREATE_DECISION' AS source_key, 'CAN_DELETE_DECISION' AS target_key
    UNION ALL SELECT 'CAN_CREATE_FAQ' AS source_key, 'CAN_EDIT_FAQ' AS target_key
    UNION ALL SELECT 'CAN_CREATE_FAQ' AS source_key, 'CAN_DELETE_FAQ' AS target_key
) mapping ON mapping.source_key = source.`permission_key`
INNER JOIN `permission` target ON target.`permission_key` = mapping.target_key;

-- Unconfigured rights retain the existing organization-member default.
-- Do not infer rule/member scopes from the unrelated structural-edit permission.
UPDATE `permission`
SET `title` = CASE `permission_key`
    WHEN 'CAN_CREATE_CONTROL_LIST' THEN 'Creer des listes de controle (ancien module)'
    WHEN 'CAN_EDIT_CONTROL_LIST' THEN 'Modifier des listes de controle (ancien module)'
    WHEN 'CAN_DELETE_CONTROL_LIST' THEN 'Supprimer des listes de controle (ancien module)'
END
WHERE `permission_key` IN ('CAN_CREATE_CONTROL_LIST', 'CAN_EDIT_CONTROL_LIST', 'CAN_DELETE_CONTROL_LIST');
