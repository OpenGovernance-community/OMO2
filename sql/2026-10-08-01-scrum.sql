-- @migration
CREATE TABLE IF NOT EXISTS scrum_sprint (
    id int(11) NOT NULL AUTO_INCREMENT,
    IDorganization int(11) NOT NULL,
    IDholon int(11) NOT NULL,
    title varchar(255) NOT NULL,
    objective text DEFAULT NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    state varchar(20) NOT NULL DEFAULT 'scheduled',
    started_at datetime DEFAULT NULL,
    finished_at datetime DEFAULT NULL,
    archived tinyint(1) NOT NULL DEFAULT 0,
    baseline int NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_scrum_context (IDorganization, IDholon, archived, start_date),
    CONSTRAINT fk_scrum_org FOREIGN KEY (IDorganization) REFERENCES organization(id) ON DELETE CASCADE,
    CONSTRAINT fk_scrum_holon FOREIGN KEY (IDholon) REFERENCES holon(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scrum_project (
    id int(11) NOT NULL AUTO_INCREMENT,
    IDsprint int(11) NOT NULL,
    IDproject int(11) DEFAULT NULL,
    project_key int NOT NULL,
    parent_key int NOT NULL DEFAULT 0,
    points int NOT NULL,
    own_points int NOT NULL DEFAULT 0,
    snapshot mediumtext NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_scrum_project (IDsprint, project_key),
    KEY idx_scrum_project (IDproject),
    CONSTRAINT fk_scrum_link_sprint FOREIGN KEY (IDsprint) REFERENCES scrum_sprint(id) ON DELETE CASCADE,
    CONSTRAINT fk_scrum_link_project FOREIGN KEY (IDproject) REFERENCES project(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scrum_sample (
    id int(11) NOT NULL AUTO_INCREMENT,
    IDsprint int(11) NOT NULL,
    sampled_at datetime NOT NULL,
    remaining int NOT NULL,
    source varchar(20) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_scrum_sample (IDsprint, sampled_at, id),
    CONSTRAINT fk_scrum_sample_sprint FOREIGN KEY (IDsprint) REFERENCES scrum_sprint(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO application (label, hash, directory, icon, drawer, url, navigationmode, position, requires_login, active)
VALUES ('Scrum', 'scrum', 'scrum', 'images/tools/product.png', 'drawer_scrum', 'api/scrum/index.php', 'drawer', 46, 1, 1)
ON DUPLICATE KEY UPDATE directory=VALUES(directory), url=VALUES(url), active=VALUES(active);
