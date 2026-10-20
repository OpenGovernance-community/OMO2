-- @migration
-- Also refresh old automatic summaries when the event title has since changed.
UPDATE `document` d
JOIN `event` e ON e.`id` = d.`IDevent`
SET d.`description` = CONCAT(
    CASE WHEN d.`documenttype` = 'pv' THEN 'Compte rendu de « ' ELSE 'Document lié à « ' END,
    TRIM(e.`title`), ' » du ', DATE_FORMAT(e.`start_at`, '%d.%m.%Y'),
    CASE WHEN e.`is_all_day` = 1 THEN '' ELSE CONCAT(' à ', DATE_FORMAT(e.`start_at`, '%H:%i')) END,
    '.'
)
WHERE BINARY LEFT(d.`description`, CHAR_LENGTH('Document associé à l''événement "'))
    = BINARY 'Document associé à l''événement "'
  AND RIGHT(d.`description`, 2) = '".';
