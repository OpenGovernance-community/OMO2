-- @migration
-- Keep the visitor calendar target stable across retries, including uncertain writes.
ALTER TABLE meeting_booking
    ADD COLUMN IDuser_guest int(11) DEFAULT NULL,
    ADD COLUMN IDexternalcalendar_guest int(11) DEFAULT NULL,
    ADD COLUMN guest_resource_url varchar(2100) DEFAULT NULL,
    ADD COLUMN guest_calendar_status varchar(16) DEFAULT NULL,
    ADD CONSTRAINT meeting_guest_user_fk FOREIGN KEY (IDuser_guest) REFERENCES user (id) ON DELETE SET NULL,
    ADD CONSTRAINT meeting_guest_calendar_fk FOREIGN KEY (IDexternalcalendar_guest) REFERENCES external_calendar (id) ON DELETE SET NULL;
