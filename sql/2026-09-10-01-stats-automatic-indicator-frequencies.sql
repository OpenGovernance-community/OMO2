-- @migration
-- English labels for automatic indicator sources and their extended frequencies.

UPDATE `translation_bundles`
SET `translated_json` = JSON_SET(
    COALESCE(NULLIF(`translated_json`, ''), '{}'),
    '$."stats.import.ethercalc.frequency_monthly"', JSON_OBJECT('text', 'Monthly'),
    '$."stats.import.ethercalc.frequency_quarterly"', JSON_OBJECT('text', 'Quarterly'),
    '$."stats.import.ethercalc.frequency_semiannual"', JSON_OBJECT('text', 'Semiannual'),
    '$."stats.import.ethercalc.frequency_yearly"', JSON_OBJECT('text', 'Yearly'),
    '$."stats.import.spreadsheet.frequency_monthly"', JSON_OBJECT('text', 'Monthly'),
    '$."stats.import.spreadsheet.frequency_quarterly"', JSON_OBJECT('text', 'Quarterly'),
    '$."stats.import.spreadsheet.frequency_semiannual"', JSON_OBJECT('text', 'Semiannual'),
    '$."stats.import.spreadsheet.frequency_yearly"', JSON_OBJECT('text', 'Yearly'),
    '$."stats.form.source_title"', JSON_OBJECT('text', 'Value source'),
    '$."stats.form.source_help"', JSON_OBJECT('text', 'Choose manual entry or an automatic source.'),
    '$."stats.form.source_type"', JSON_OBJECT('text', 'Source type'),
    '$."stats.form.source_manual"', JSON_OBJECT('text', 'Manual entry'),
    '$."stats.form.source_ethercalc_cell"', JSON_OBJECT('text', 'Framacalc: cell'),
    '$."stats.form.source_ethercalc_table"', JSON_OBJECT('text', 'Framacalc: table'),
    '$."stats.form.source_spreadsheet_cell"', JSON_OBJECT('text', 'Spreadsheet document: cell'),
    '$."stats.form.source_spreadsheet_table"', JSON_OBJECT('text', 'Spreadsheet document: table'),
    '$."stats.detail.source_document"', JSON_OBJECT('text', 'Show source document'),
    '$."stats.detail.source_document_new_window"', JSON_OBJECT('text', 'Open in a new tab'),
    '$."stats.detail.source_document_title"', JSON_OBJECT('text', 'Indicator source document')
), `updated_at` = CURRENT_TIMESTAMP()
WHERE `bundle_key` = 'omo_stats' AND `locale` = 'en';
