-- @migration
-- Personal booking notifications belong to a user, independently of organizations.
ALTER TABLE notification MODIFY IDorganization int(11) NULL;
ALTER TABLE notification_preference
    MODIFY IDorganization int(11) NULL,
    ADD COLUMN personal_user_id int(11) GENERATED ALWAYS AS (IF(IDorganization IS NULL, IDuser, NULL)) STORED,
    ADD UNIQUE KEY uniq_notification_preference_personal (personal_user_id, event_key);
