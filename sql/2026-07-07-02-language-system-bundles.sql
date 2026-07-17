-- @migration
UPDATE `translation_bundles`
SET
    `translated_json` = JSON_SET(
        COALESCE(NULLIF(`translated_json`, ''), '{}'),
        '$."topbar.help.privacy.label"', JSON_OBJECT('text', 'Privacy policy'),
        '$."topbar.help.terms.label"', JSON_OBJECT('text', 'Terms and conditions'),
        '$."topbar.profile.admin_mode.active"', JSON_OBJECT('text', 'Organization admin mode active'),
        '$."topbar.profile.admin_mode.disable"', JSON_OBJECT('text', 'Leave organization admin mode'),
        '$."topbar.profile.admin_mode.enable"', JSON_OBJECT('text', 'Enable organization admin mode'),
        '$."topbar.profile.admin_mode.inactive"', JSON_OBJECT('text', 'Organization admin mode inactive'),
        '$."topbar.profile.preferences.color_style_default"', JSON_OBJECT('text', 'Black and white'),
        '$."topbar.profile.preferences.color_style_label"', JSON_OBJECT('text', 'Color'),
        '$."topbar.profile.preferences.color_style_ocean_blue"', JSON_OBJECT('text', 'Ocean Blue'),
        '$."topbar.profile.preferences.color_style_turquoise"', JSON_OBJECT('text', 'Turquoise'),
        '$."topbar.profile.preferences.language_label"', JSON_OBJECT('text', 'Language'),
        '$."topbar.profile.preferences.language_system"', JSON_OBJECT('text', 'System'),
        '$."topbar.profile.preferences.theme_dark"', JSON_OBJECT('text', 'Dark'),
        '$."topbar.profile.preferences.theme_label"', JSON_OBJECT('text', 'Theme'),
        '$."topbar.profile.preferences.theme_light"', JSON_OBJECT('text', 'Light'),
        '$."topbar.profile.preferences.theme_system"', JSON_OBJECT('text', 'System'),
        '$."topbar.profile.site_admin_mode.active"', JSON_OBJECT('text', 'Super admin mode active'),
        '$."topbar.profile.site_admin_mode.disable"', JSON_OBJECT('text', 'Leave super admin mode'),
        '$."topbar.profile.site_admin_mode.enable"', JSON_OBJECT('text', 'Enable super admin mode'),
        '$."topbar.profile.site_admin_mode.inactive"', JSON_OBJECT('text', 'Super admin mode inactive'),
        '$."topbar.tension.button"', JSON_OBJECT('text', 'Tension'),
        '$."topbar.tension.title"', JSON_OBJECT('text', 'Declare a tension'),
        '$."topbar.tension.unavailable_html"', JSON_OBJECT('text', '<p>Form unavailable.</p>')
    ),
    `source_hash` = 'd9916126db0787ce32ffe43a48181df01aca670676fcae751afe71c486ef93be',
    `status` = 'machine_translated',
    `updated_at` = CURRENT_TIMESTAMP()
WHERE `bundle_key` = 'omo_topbar' AND `locale` = 'en';

UPDATE `translation_bundles`
SET
    `translated_json` = JSON_SET(
        COALESCE(NULLIF(`translated_json`, ''), '{}'),
        '$."topbar.help.privacy.label"', JSON_OBJECT('text', 'Datenschutzerklarung'),
        '$."topbar.help.terms.label"', JSON_OBJECT('text', 'Allgemeine Bedingungen'),
        '$."topbar.profile.admin_mode.active"', JSON_OBJECT('text', 'Organisations-Admin-Modus aktiv'),
        '$."topbar.profile.admin_mode.disable"', JSON_OBJECT('text', 'Organisations-Admin-Modus beenden'),
        '$."topbar.profile.admin_mode.enable"', JSON_OBJECT('text', 'Organisations-Admin-Modus aktivieren'),
        '$."topbar.profile.admin_mode.inactive"', JSON_OBJECT('text', 'Organisations-Admin-Modus inaktiv'),
        '$."topbar.profile.preferences.color_style_default"', JSON_OBJECT('text', 'Schwarz-Weiss'),
        '$."topbar.profile.preferences.color_style_label"', JSON_OBJECT('text', 'Farbe'),
        '$."topbar.profile.preferences.color_style_ocean_blue"', JSON_OBJECT('text', 'Ozeanblau'),
        '$."topbar.profile.preferences.color_style_turquoise"', JSON_OBJECT('text', 'Turkis'),
        '$."topbar.profile.preferences.language_label"', JSON_OBJECT('text', 'Sprache'),
        '$."topbar.profile.preferences.language_system"', JSON_OBJECT('text', 'System'),
        '$."topbar.profile.preferences.theme_dark"', JSON_OBJECT('text', 'Dunkel'),
        '$."topbar.profile.preferences.theme_label"', JSON_OBJECT('text', 'Thema'),
        '$."topbar.profile.preferences.theme_light"', JSON_OBJECT('text', 'Hell'),
        '$."topbar.profile.preferences.theme_system"', JSON_OBJECT('text', 'System'),
        '$."topbar.profile.site_admin_mode.active"', JSON_OBJECT('text', 'Super-Admin-Modus aktiv'),
        '$."topbar.profile.site_admin_mode.disable"', JSON_OBJECT('text', 'Super-Admin-Modus beenden'),
        '$."topbar.profile.site_admin_mode.enable"', JSON_OBJECT('text', 'Super-Admin-Modus aktivieren'),
        '$."topbar.profile.site_admin_mode.inactive"', JSON_OBJECT('text', 'Super-Admin-Modus inaktiv'),
        '$."topbar.tension.button"', JSON_OBJECT('text', 'Spannung'),
        '$."topbar.tension.title"', JSON_OBJECT('text', 'Spannung melden'),
        '$."topbar.tension.unavailable_html"', JSON_OBJECT('text', '<p>Formular nicht verfugbar.</p>')
    ),
    `source_hash` = 'd9916126db0787ce32ffe43a48181df01aca670676fcae751afe71c486ef93be',
    `status` = 'machine_translated',
    `updated_at` = CURRENT_TIMESTAMP()
WHERE `bundle_key` = 'omo_topbar' AND `locale` = 'de';

UPDATE `translation_bundles`
SET
    `translated_json` = JSON_SET(
        COALESCE(NULLIF(`translated_json`, ''), '{}'),
        '$."app.access_denied.request_action"', JSON_OBJECT('text', 'Request access'),
        '$."app.access_denied.request_modal_title"', JSON_OBJECT('text', 'Request access to the organization'),
        '$."app.access_denied.request_pending"', JSON_OBJECT('text', 'Request already sent'),
        '$."app.access_denied.request_pending_notice"', JSON_OBJECT('text', 'A request is already pending with this organization''s administrators.'),
        '$."app.directory.cta.view_invitation"', JSON_OBJECT('text', 'View invitation'),
        '$."app.directory.description.empty.patreon_connect"', JSON_OBJECT('text', 'Your account is connected, but it is not linked to any organization yet. Connect Patreon below to be able to create a new one.'),
        '$."app.directory.invitation.badge"', JSON_OBJECT('text', 'Invitation pending'),
        '$."app.directory.invitation.pending_holons"', JSON_OBJECT('one', '{count} holon pending', 'other', '{count} holons pending'),
        '$."app.directory.invitation.pending_organization"', JSON_OBJECT('text', 'Access to confirm'),
        '$."app.directory.patreon_connect.action"', JSON_OBJECT('text', 'Connect with Patreon'),
        '$."app.directory.patreon_connect.aria_label"', JSON_OBJECT('text', 'Connect your Patreon profile'),
        '$."app.directory.patreon_connect.badge"', JSON_OBJECT('text', 'Patreon required'),
        '$."app.directory.patreon_connect.description"', JSON_OBJECT('text', 'Connect Patreon to unlock organization creation'),
        '$."app.directory.patreon_connect.title"', JSON_OBJECT('text', 'Connect Patreon'),
        '$."app.directory.template.badge"', JSON_OBJECT('text', 'Shared template'),
        '$."app.directory.templates.heading"', JSON_OBJECT('text', 'Your organization templates'),
        '$."app.mobile.right_panel"', JSON_OBJECT('text', 'Summary')
    ),
    `source_hash` = 'fba865ce9ccd2d6f84e73cefca5f30d79ceda9dbf2724a573e7cbc07f8859037',
    `status` = 'machine_translated',
    `updated_at` = CURRENT_TIMESTAMP()
WHERE `bundle_key` = 'omo_index_page' AND `locale` = 'en';

UPDATE `translation_bundles`
SET
    `translated_json` = JSON_SET(
        COALESCE(NULLIF(`translated_json`, ''), '{}'),
        '$."app.access_denied.request_action"', JSON_OBJECT('text', 'Zugriff anfragen'),
        '$."app.access_denied.request_modal_title"', JSON_OBJECT('text', 'Zugriff auf die Organisation anfragen'),
        '$."app.access_denied.request_pending"', JSON_OBJECT('text', 'Anfrage bereits gesendet'),
        '$."app.access_denied.request_pending_notice"', JSON_OBJECT('text', 'Eine Anfrage wartet bereits bei den Administratoren dieser Organisation.'),
        '$."app.directory.cta.view_invitation"', JSON_OBJECT('text', 'Einladung ansehen'),
        '$."app.directory.description.empty.patreon_connect"', JSON_OBJECT('text', 'Ihr Konto ist verbunden, aber derzeit keiner Organisation zugeordnet. Verbinden Sie unten Patreon, um eine neue Organisation erstellen zu konnen.'),
        '$."app.directory.invitation.badge"', JSON_OBJECT('text', 'Einladung ausstehend'),
        '$."app.directory.invitation.pending_holons"', JSON_OBJECT('one', '{count} Holon ausstehend', 'other', '{count} Holons ausstehend'),
        '$."app.directory.invitation.pending_organization"', JSON_OBJECT('text', 'Zugriff zu bestatigen'),
        '$."app.directory.patreon_connect.action"', JSON_OBJECT('text', 'Mit Patreon verbinden'),
        '$."app.directory.patreon_connect.aria_label"', JSON_OBJECT('text', 'Ihr Patreon-Profil verbinden'),
        '$."app.directory.patreon_connect.badge"', JSON_OBJECT('text', 'Patreon erforderlich'),
        '$."app.directory.patreon_connect.description"', JSON_OBJECT('text', 'Verbinden Sie Patreon, um die Erstellung einer Organisation freizuschalten'),
        '$."app.directory.patreon_connect.title"', JSON_OBJECT('text', 'Patreon verbinden'),
        '$."app.directory.template.badge"', JSON_OBJECT('text', 'Geteilte Vorlage'),
        '$."app.directory.templates.heading"', JSON_OBJECT('text', 'Ihre Organisationsvorlagen'),
        '$."app.mobile.right_panel"', JSON_OBJECT('text', 'Ubersicht')
    ),
    `source_hash` = 'fba865ce9ccd2d6f84e73cefca5f30d79ceda9dbf2724a573e7cbc07f8859037',
    `status` = 'machine_translated',
    `updated_at` = CURRENT_TIMESTAMP()
WHERE `bundle_key` = 'omo_index_page' AND `locale` = 'de';

UPDATE `translation_bundles`
SET
    `translated_json` = JSON_SET(
        COALESCE(NULLIF(`translated_json`, ''), '{}'),
        '$."structure.message.disabled"', JSON_OBJECT('text', 'The Structure app is disabled for this organization.'),
        '$."structure.placeholder.action"', JSON_OBJECT('text', 'Open the Structure app'),
        '$."structure.placeholder.text"', JSON_OBJECT('text', 'No structure has been defined for this organization yet. Open the Structure app in the leftbar to create an empty structure, import an export, or start from a template.'),
        '$."structure.placeholder.title"', JSON_OBJECT('text', 'No structure')
    ),
    `source_hash` = '696a50977d1b4f741b510e76428e7c51de04d407419501da9686a8c1e3683684',
    `status` = 'machine_translated',
    `updated_at` = CURRENT_TIMESTAMP()
WHERE `bundle_key` = 'omo_get_structure_panel' AND `locale` = 'en';

UPDATE `translation_bundles`
SET
    `translated_json` = JSON_SET(
        COALESCE(NULLIF(`translated_json`, ''), '{}'),
        '$."structure.message.disabled"', JSON_OBJECT('text', 'Die App Struktur ist fur diese Organisation deaktiviert.'),
        '$."structure.placeholder.action"', JSON_OBJECT('text', 'App Struktur offnen'),
        '$."structure.placeholder.text"', JSON_OBJECT('text', 'Fur diese Organisation ist noch keine Struktur definiert. Offnen Sie die App Struktur in der linken Leiste, um eine leere Struktur zu erstellen, einen Export zu importieren oder von einer Vorlage zu starten.'),
        '$."structure.placeholder.title"', JSON_OBJECT('text', 'Keine Struktur')
    ),
    `source_hash` = '696a50977d1b4f741b510e76428e7c51de04d407419501da9686a8c1e3683684',
    `status` = 'machine_translated',
    `updated_at` = CURRENT_TIMESTAMP()
WHERE `bundle_key` = 'omo_get_structure_panel' AND `locale` = 'de';
