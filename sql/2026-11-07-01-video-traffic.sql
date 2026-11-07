-- @migration
CREATE TABLE video_traffic (
 id bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
 endpoint_key char(64) CHARACTER SET ascii NOT NULL,
 room_code char(32) CHARACTER SET ascii NOT NULL,
 IDuser int NOT NULL,
 IDorganization int DEFAULT NULL,
 sampled_at datetime NOT NULL,
 received_total bigint unsigned NOT NULL,
 sent_total bigint unsigned NOT NULL,
 received_bytes bigint unsigned NOT NULL,
 sent_bytes bigint unsigned NOT NULL,
 KEY video_traffic_endpoint (endpoint_key, id),
 KEY video_traffic_period (sampled_at, IDorganization, IDuser)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE video_traffic_collector (
 id int NOT NULL PRIMARY KEY,
 last_success datetime DEFAULT NULL,
 last_attempt datetime DEFAULT NULL,
 last_error varchar(255) DEFAULT NULL,
 observed_endpoints int NOT NULL DEFAULT 0,
 unmatched_rooms int NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO video_traffic_collector (id) VALUES (1);
