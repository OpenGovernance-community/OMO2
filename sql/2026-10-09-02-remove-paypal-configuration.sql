-- @migration
-- Remove the retired payment field from cached server configuration translations.
UPDATE `translation_bundles`
SET `translated_json` = JSON_REMOVE(`translated_json`, '$."parameters.server_env.field.PAYPAL_CLIENT_ID.label"'),
    `status` = 'outdated'
WHERE `bundle_key` = 'omo_parameters_server_env'
  AND JSON_VALID(`translated_json`)
  AND JSON_CONTAINS_PATH(`translated_json`, 'one', '$."parameters.server_env.field.PAYPAL_CLIENT_ID.label"');
