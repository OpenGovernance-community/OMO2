-- @migration
UPDATE `application`
SET
    `label` = 'Processus',
    `hash` = 'processus'
WHERE `id` = 4;

UPDATE `permission`
SET
    `title` = CASE `permission_key`
        WHEN 'CAN_CREATE_CHECKLIST' THEN 'Creer des processus'
        WHEN 'CAN_EDIT_CHECKLIST' THEN 'Modifier des processus'
        WHEN 'CAN_DELETE_CHECKLIST' THEN 'Supprimer des processus'
        ELSE `title`
    END,
    `description` = CASE `permission_key`
        WHEN 'CAN_CREATE_CHECKLIST' THEN 'Autorise la creation de processus dans le contexte cible.'
        WHEN 'CAN_EDIT_CHECKLIST' THEN 'Autorise l ajout, la modification et la suppression des etapes et activites de processus dans le contexte cible.'
        WHEN 'CAN_DELETE_CHECKLIST' THEN 'Autorise la suppression de processus dans le contexte cible.'
        ELSE `description`
    END,
    `updated_at` = NOW()
WHERE `permission_key` IN ('CAN_CREATE_CHECKLIST', 'CAN_EDIT_CHECKLIST', 'CAN_DELETE_CHECKLIST');

UPDATE `document`
SET `content` = REPLACE(`content`, '#checklist-c', '#processus-c')
WHERE `content` LIKE '%#checklist-c%';
