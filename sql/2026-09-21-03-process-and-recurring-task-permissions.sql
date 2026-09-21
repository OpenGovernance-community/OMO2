-- @migration
-- Rename legacy permission keys while keeping their ids and existing assignments.
UPDATE `permission`
SET
    `permission_key` = CASE `permission_key`
        WHEN 'CAN_CREATE_CHECKLIST' THEN 'CAN_CREATE_PROCESS'
        WHEN 'CAN_EDIT_CHECKLIST' THEN 'CAN_EDIT_PROCESS'
        WHEN 'CAN_DELETE_CHECKLIST' THEN 'CAN_DELETE_PROCESS'
        WHEN 'CAN_CREATE_CONTROL_ACTIVITY' THEN 'CAN_CREATE_RECURRING_TASK'
        WHEN 'CAN_EDIT_CONTROL_ACTIVITY' THEN 'CAN_EDIT_RECURRING_TASK'
        WHEN 'CAN_DELETE_CONTROL_ACTIVITY' THEN 'CAN_DELETE_RECURRING_TASK'
        ELSE `permission_key`
    END,
    `title` = CASE `permission_key`
        WHEN 'CAN_CREATE_CHECKLIST' THEN 'Creer des processus'
        WHEN 'CAN_EDIT_CHECKLIST' THEN 'Modifier des processus'
        WHEN 'CAN_DELETE_CHECKLIST' THEN 'Supprimer des processus'
        WHEN 'CAN_CREATE_CONTROL_ACTIVITY' THEN 'Creer des taches recurrentes'
        WHEN 'CAN_EDIT_CONTROL_ACTIVITY' THEN 'Modifier des taches recurrentes'
        WHEN 'CAN_DELETE_CONTROL_ACTIVITY' THEN 'Supprimer des taches recurrentes'
        ELSE `title`
    END,
    `description` = CASE `permission_key`
        WHEN 'CAN_CREATE_CHECKLIST' THEN 'Autorise la creation de processus dans le contexte cible.'
        WHEN 'CAN_EDIT_CHECKLIST' THEN 'Autorise la modification des processus, de leurs etapes et de leurs activites dans le contexte cible.'
        WHEN 'CAN_DELETE_CHECKLIST' THEN 'Autorise la suppression de processus dans le contexte cible.'
        WHEN 'CAN_CREATE_CONTROL_ACTIVITY' THEN 'Autorise la creation de taches recurrentes dans le contexte cible.'
        WHEN 'CAN_EDIT_CONTROL_ACTIVITY' THEN 'Autorise la modification de taches recurrentes dans le contexte cible.'
        WHEN 'CAN_DELETE_CONTROL_ACTIVITY' THEN 'Autorise la suppression de taches recurrentes dans le contexte cible.'
        ELSE `description`
    END,
    `updated_at` = NOW()
WHERE `permission_key` IN (
    'CAN_CREATE_CHECKLIST', 'CAN_EDIT_CHECKLIST', 'CAN_DELETE_CHECKLIST',
    'CAN_CREATE_CONTROL_ACTIVITY', 'CAN_EDIT_CONTROL_ACTIVITY', 'CAN_DELETE_CONTROL_ACTIVITY'
);
