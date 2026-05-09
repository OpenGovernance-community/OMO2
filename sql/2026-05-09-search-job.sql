-- @migration
-- OpenMyOrganization
-- File de jobs pour la recherche asynchrone de la topbar

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `search_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jobtype` varchar(40) NOT NULL DEFAULT 'topbar_search',
  `status` varchar(20) NOT NULL DEFAULT 'queued',
  `query` text NOT NULL,
  `scopesjson` mediumtext DEFAULT NULL,
  `viewercontextjson` mediumtext DEFAULT NULL,
  `resultjson` longtext DEFAULT NULL,
  `errormessage` text DEFAULT NULL,
  `requesttoken` varchar(80) NOT NULL,
  `IDorganization` int(11) NOT NULL,
  `currentholonid` int(11) DEFAULT NULL,
  `viewertype` varchar(20) NOT NULL DEFAULT 'user',
  `viewerref` int(11) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `datestarted` datetime DEFAULT NULL,
  `datefinished` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_search_job_requesttoken` (`requesttoken`),
  KEY `idx_search_job_status` (`status`),
  KEY `idx_search_job_org_status` (`IDorganization`, `status`),
  KEY `idx_search_job_creation` (`datecreation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
