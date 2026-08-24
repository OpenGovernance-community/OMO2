-- @migration
UPDATE `application`
SET `label` = 'Processus',
    `icon` = 'images/tools/checklist.png'
WHERE `id` = 4
  AND `hash` = 'checklist';
