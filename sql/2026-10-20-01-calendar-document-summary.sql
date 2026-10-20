-- @migration
-- Remove only the extra closing-quote escape in generated event summaries.
UPDATE `document`
SET `description` = CONCAT(LEFT(`description`, CHAR_LENGTH(`description`) - 3), '".')
WHERE BINARY LEFT(`description`, CHAR_LENGTH('Document associé à l''événement "'))
    = BINARY 'Document associé à l''événement "'
  AND RIGHT(`description`, 3) = CONCAT(CHAR(92), '".');

-- Enrich only unchanged automatic summaries of events that still exist.
UPDATE `document` d
JOIN `event` e ON e.`id` = d.`IDevent`
SET d.`description` = CONCAT(
    CASE WHEN d.`documenttype` = 'pv' THEN 'Compte rendu de « ' ELSE 'Document lié à « ' END,
    TRIM(e.`title`), ' » du ', DATE_FORMAT(e.`start_at`, '%d.%m.%Y'),
    CASE WHEN e.`is_all_day` = 1 THEN '' ELSE CONCAT(' à ', DATE_FORMAT(e.`start_at`, '%H:%i')) END,
    '.'
)
WHERE BINARY d.`description` = BINARY CONCAT('Document associé à l''événement "', TRIM(e.`title`), '".');
