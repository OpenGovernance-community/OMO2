/*M!999999\- enable the sandbox mode */
-- MariaDB dump 10.19-11.4.12-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: omodev
-- ------------------------------------------------------
-- Server version	11.4.12-MariaDB-ubu2404

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `aiprompt`
--

DROP TABLE IF EXISTS `aiprompt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aiprompt` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `prompt` mediumtext NOT NULL,
  `ispublic` bit(1) NOT NULL DEFAULT b'0',
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `IDuser` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aiprompt`
--

LOCK TABLES `aiprompt` WRITE;
/*!40000 ALTER TABLE `aiprompt` DISABLE KEYS */;
INSERT INTO `aiprompt` VALUES
(0,'Mise en page neutre','une mise en page lisible, exhaustive, optimisée pour la lecture et structurée du texte (si nécessaire avec des titres ou des listes à puce)','\0','2025-07-31 09:44:41',NULL),
(1,'Tutoriel','Adapte ce texte (sans le résumer ou le raccourcir) pour servir de base à un tutoriel vidéo, avec une intro (Dans cette capsule, nous allons voir...), le texte structuré pour qu\'il soit facilement lisible avec un prompteur et facilement compréhensible en le structurant si nécessaire avec de l\'HTML, des titres et des paragraphes, et une conclusion qui vient rappeler les notions importants à la fin.','','2024-11-16 16:00:07',NULL),
(2,'Résumé (200 mots)','Un résumé de maximum 200 mots.','','2024-11-17 12:02:16',NULL);
/*!40000 ALTER TABLE `aiprompt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alttext`
--

DROP TABLE IF EXISTS `alttext`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alttext` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdocument` int(11) NOT NULL,
  `IDaiprompt` int(11) NOT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `text` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alttext_document` (`IDdocument`),
  CONSTRAINT `fk_alttext_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alttext`
--

LOCK TABLES `alttext` WRITE;
/*!40000 ALTER TABLE `alttext` DISABLE KEYS */;
/*!40000 ALTER TABLE `alttext` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `application`
--

DROP TABLE IF EXISTS `application`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `application` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(100) NOT NULL,
  `hash` varchar(100) DEFAULT NULL,
  `directory` varchar(100) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `drawer` varchar(100) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `navigationmode` varchar(20) NOT NULL DEFAULT 'drawer',
  `position` int(11) DEFAULT NULL,
  `requires_login` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_application_hash` (`hash`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application`
--

LOCK TABLES `application` WRITE;
/*!40000 ALTER TABLE `application` DISABLE KEYS */;
INSERT INTO `application` VALUES
(1,'Structure','structure',NULL,'images/tools/connection.png','drawer_structure','api/getStructure.php?drawer=1','drawer',10,0,1),
(2,'Projets','projects','projects','images/tools/product.png','drawer_projects','api/projects/index.php','drawer',20,0,1),
(3,'Reglement','policy','policy','images/tools/policy.png','drawer_policy','api/policy/index.php','drawer',30,1,1),
(4,'Processus','processus','processes','images/tools/checklist.png','drawer_checklist','api/processes/index.php','drawer',40,1,1),
(5,'Indicateurs','stats','stats','images/tools/stats.png','drawer_stats','api/stats/index.php','drawer',50,0,1),
(6,'Documents','documents','documents','images/tools/documents-folder.png','drawer_documents','api/documents/index.php','drawer',60,1,1),
(7,'Team','team','team','images/tools/team.png','drawer_team','api/team/index.php','drawer',8,1,1),
(8,'Calendrier','calendar','calendar','images/tools/calendar.png','drawer_calendar','api/calendar/index.php','drawer',9,1,1),
(9,'Decisions','decision','decision','images/tools/decision.png','drawer_decisions','api/decision/index.php','drawer',65,1,1),
(10,'Activites','activities','recurring_tasks','images/tools/control-list.png','drawer_activities','api/recurring_tasks/index.php','drawer',45,1,1),
(12,'Budget','budget','budget','images/tools/budget.png','drawer_budget','api/budget/index.php','drawer',55,1,1);
/*!40000 ALTER TABLE `application` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `application_setting`
--

DROP TABLE IF EXISTS `application_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(120) NOT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_application_setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application_setting`
--

LOCK TABLES `application_setting` WRITE;
/*!40000 ALTER TABLE `application_setting` DISABLE KEYS */;
INSERT INTO `application_setting` VALUES
(1,'omo.dashboard','{\"dashboardDefaultLayoutV1\":[{\"id\":\"rules-1\",\"type\":\"rules\",\"row\":0,\"column\":0,\"rowSpan\":1,\"columnSpan\":1},{\"id\":\"projects-1788164049201\",\"type\":\"projects\",\"row\":0,\"column\":1,\"rowSpan\":2,\"columnSpan\":1},{\"id\":\"team-1\",\"type\":\"team\",\"row\":1,\"column\":0,\"rowSpan\":1,\"columnSpan\":1},{\"id\":\"event-1\",\"type\":\"event\",\"row\":2,\"column\":0,\"rowSpan\":1,\"columnSpan\":1},{\"id\":\"structure-1\",\"type\":\"structure\",\"row\":2,\"column\":1,\"rowSpan\":1,\"columnSpan\":1},{\"id\":\"stats-1\",\"type\":\"stats\",\"row\":3,\"column\":0,\"rowSpan\":1,\"columnSpan\":2}],\"dashboardTemplateLayoutsV1\":{\"template:2:fd767133766ae386838c3970\":[{\"id\":\"rules-1\",\"type\":\"rules\",\"row\":0,\"column\":0,\"rowSpan\":1,\"columnSpan\":1,\"settings\":{\"scope\":\"contextual\"}},{\"id\":\"projects-1788164049201\",\"type\":\"projects\",\"row\":0,\"column\":1,\"rowSpan\":2,\"columnSpan\":1,\"settings\":{\"scope\":\"children\"}},{\"id\":\"team-1\",\"type\":\"team\",\"row\":1,\"column\":0,\"rowSpan\":1,\"columnSpan\":1,\"settings\":{\"scope\":\"contextual\"}},{\"id\":\"event-1\",\"type\":\"event\",\"row\":2,\"column\":0,\"rowSpan\":1,\"columnSpan\":1,\"settings\":{\"scope\":\"contextual\"}},{\"id\":\"structure-1\",\"type\":\"structure\",\"row\":2,\"column\":1,\"rowSpan\":1,\"columnSpan\":1,\"settings\":{\"scope\":\"contextual\"}},{\"id\":\"stats-1\",\"type\":\"stats\",\"row\":3,\"column\":0,\"rowSpan\":1,\"columnSpan\":2,\"settings\":{\"scope\":\"contextual\"}}]},\"dashboardBaseTypeLayoutsV1\":{\"type:4\":[{\"id\":\"video-1789637879990\",\"type\":\"video\",\"row\":0,\"column\":0,\"rowSpan\":1,\"columnSpan\":2,\"settings\":{\"video\":\"https://player.vimeo.com/video/1216421439\"}}]},\"dashboardGlobalLayoutV1\":[{\"id\":\"video-1789637879990\",\"type\":\"video\",\"row\":0,\"column\":0,\"rowSpan\":1,\"columnSpan\":2,\"settings\":{\"video\":\"https://player.vimeo.com/video/1216421439\"}}]}','2026-08-31 12:46:42',NULL);
/*!40000 ALTER TABLE `application_setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auth_rate_limit`
--

DROP TABLE IF EXISTS `auth_rate_limit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_rate_limit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(64) NOT NULL,
  `key_hash` char(64) NOT NULL,
  `window_started_at` datetime NOT NULL,
  `attempt_count` int(10) unsigned NOT NULL DEFAULT 0,
  `blocked_until` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_auth_rate_limit_scope_key` (`scope`,`key_hash`),
  KEY `idx_auth_rate_limit_updated` (`updated_at`),
  KEY `idx_auth_rate_limit_blocked` (`blocked_until`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_rate_limit`
--

LOCK TABLES `auth_rate_limit` WRITE;
/*!40000 ALTER TABLE `auth_rate_limit` DISABLE KEYS */;
/*!40000 ALTER TABLE `auth_rate_limit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `authority`
--

DROP TABLE IF EXISTS `authority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `authority` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDholon` int(11) NOT NULL,
  `IDauthority_template` int(11) DEFAULT NULL,
  `IDauthority_parent` int(11) DEFAULT NULL,
  `is_local` tinyint(1) NOT NULL DEFAULT 0,
  `template_origin_lost` tinyint(1) NOT NULL DEFAULT 0,
  `label` mediumtext NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `is_shell` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_authority_holon` (`IDholon`),
  KEY `idx_authority_parent` (`IDauthority_parent`),
  KEY `idx_authority_shell` (`is_shell`),
  KEY `idx_authority_template` (`IDauthority_template`),
  KEY `idx_authority_template_origin_lost` (`template_origin_lost`),
  KEY `idx_authority_label` (`label`(255)),
  CONSTRAINT `fk_authority_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_authority_parent` FOREIGN KEY (`IDauthority_parent`) REFERENCES `authority` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3756 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authority`
--

LOCK TABLES `authority` WRITE;
/*!40000 ALTER TABLE `authority` DISABLE KEYS */;
/*!40000 ALTER TABLE `authority` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `caldav_sync_change`
--

DROP TABLE IF EXISTS `caldav_sync_change`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `caldav_sync_change` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `change_type` varchar(20) NOT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_caldav_sync_change_organization_id` (`IDorganization`,`id`),
  KEY `idx_caldav_sync_change_event_id` (`event_id`),
  CONSTRAINT `fk_caldav_sync_change_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=246 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caldav_sync_change`
--

LOCK TABLES `caldav_sync_change` WRITE;
/*!40000 ALTER TABLE `caldav_sync_change` DISABLE KEYS */;
/*!40000 ALTER TABLE `caldav_sync_change` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendar_share`
--

DROP TABLE IF EXISTS `calendar_share`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_share` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `token` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `scope_key` varchar(100) DEFAULT NULL,
  `months` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `details` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `calendar_share_token` (`token`),
  UNIQUE KEY `calendar_share_owner_scope` (`IDuser`,`scope_key`),
  KEY `calendar_share_owner` (`IDuser`,`active`),
  CONSTRAINT `calendar_share_ibfk_1` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_share`
--

LOCK TABLES `calendar_share` WRITE;
/*!40000 ALTER TABLE `calendar_share` DISABLE KEYS */;
/*!40000 ALTER TABLE `calendar_share` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_message`
--

DROP TABLE IF EXISTS `chat_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDchat_thread` int(11) NOT NULL,
  `IDorganization` int(11) NOT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `IDdecision_participant` int(11) DEFAULT NULL,
  `IDdocument_share_link` int(11) DEFAULT NULL,
  `message_type` varchar(20) NOT NULL DEFAULT 'user',
  `content` mediumtext NOT NULL,
  `author_name` varchar(190) DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chat_message_thread` (`IDchat_thread`,`id`),
  KEY `idx_chat_message_organization` (`IDorganization`),
  KEY `idx_chat_message_user` (`IDuser`),
  KEY `idx_chat_message_type` (`message_type`),
  KEY `idx_chat_message_decision_participant` (`IDdecision_participant`),
  KEY `idx_chat_message_document_share_link` (`IDdocument_share_link`),
  CONSTRAINT `fk_chat_message_decision_participant` FOREIGN KEY (`IDdecision_participant`) REFERENCES `decision_participant` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_chat_message_document_share_link` FOREIGN KEY (`IDdocument_share_link`) REFERENCES `document_share_link` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_chat_message_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_message_thread` FOREIGN KEY (`IDchat_thread`) REFERENCES `chat_thread` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_message_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_message`
--

LOCK TABLES `chat_message` WRITE;
/*!40000 ALTER TABLE `chat_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_thread`
--

DROP TABLE IF EXISTS `chat_thread`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_thread` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDuser_created` int(11) DEFAULT NULL,
  `subject_type` varchar(60) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(190) DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_chat_thread_subject` (`IDorganization`,`subject_type`,`subject_id`),
  KEY `idx_chat_thread_creator` (`IDuser_created`),
  KEY `idx_chat_thread_active` (`IDorganization`,`active`),
  CONSTRAINT `fk_chat_thread_creator` FOREIGN KEY (`IDuser_created`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_chat_thread_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_thread`
--

LOCK TABLES `chat_thread` WRITE;
/*!40000 ALTER TABLE `chat_thread` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_thread` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competence`
--

DROP TABLE IF EXISTS `competence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `competence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) DEFAULT NULL,
  `name` varchar(190) NOT NULL,
  `normalized_name` varchar(190) NOT NULL,
  `category` varchar(30) NOT NULL DEFAULT 'technical',
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_competence_scope_name` (`IDorganization`,`normalized_name`),
  KEY `idx_competence_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competence`
--

LOCK TABLES `competence` WRITE;
/*!40000 ALTER TABLE `competence` DISABLE KEYS */;
/*!40000 ALTER TABLE `competence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `decision_governance_action`
--

DROP TABLE IF EXISTS `decision_governance_action`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `decision_governance_action` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdecision_proposal` int(11) NOT NULL,
  `action_type` varchar(60) NOT NULL,
  `target_type` varchar(40) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `before_state` mediumtext DEFAULT NULL,
  `after_state` mediumtext DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `status_message` text DEFAULT NULL,
  `applied_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_decision_governance_action_proposal` (`IDdecision_proposal`,`position`),
  KEY `idx_decision_governance_action_target` (`target_type`,`target_id`),
  KEY `idx_decision_governance_action_status` (`status`),
  CONSTRAINT `fk_decision_governance_action_proposal` FOREIGN KEY (`IDdecision_proposal`) REFERENCES `decision_proposal` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decision_governance_action`
--

LOCK TABLES `decision_governance_action` WRITE;
/*!40000 ALTER TABLE `decision_governance_action` DISABLE KEYS */;
/*!40000 ALTER TABLE `decision_governance_action` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `decision_group`
--

DROP TABLE IF EXISTS `decision_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `decision_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdecision_process` int(11) NOT NULL,
  `decision_type` varchar(20) NOT NULL DEFAULT 'decision',
  `evaluation_method` varchar(40) NOT NULL DEFAULT 'simple_vote',
  `title` varchar(190) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_decision_group_process` (`IDdecision_process`),
  KEY `idx_decision_group_position` (`IDdecision_process`,`position`),
  KEY `idx_decision_group_active` (`active`),
  KEY `idx_decision_group_type` (`decision_type`),
  KEY `idx_decision_group_method` (`evaluation_method`),
  CONSTRAINT `fk_decision_group_process` FOREIGN KEY (`IDdecision_process`) REFERENCES `decision_process` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decision_group`
--

LOCK TABLES `decision_group` WRITE;
/*!40000 ALTER TABLE `decision_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `decision_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `decision_invitation`
--

DROP TABLE IF EXISTS `decision_invitation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `decision_invitation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdecision_process` int(11) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `display_name` varchar(190) DEFAULT NULL,
  `invitation_type` varchar(30) NOT NULL DEFAULT 'email',
  `status` varchar(30) NOT NULL DEFAULT 'invited',
  `parameters` mediumtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_decision_invitation_holon` (`IDdecision_process`,`IDholon`),
  UNIQUE KEY `uniq_decision_invitation_user` (`IDdecision_process`,`IDuser`),
  UNIQUE KEY `uniq_decision_invitation_email` (`IDdecision_process`,`email`),
  KEY `idx_decision_invitation_type` (`invitation_type`),
  KEY `idx_decision_invitation_status` (`status`),
  KEY `idx_decision_invitation_active` (`active`),
  KEY `fk_decision_invitation_holon` (`IDholon`),
  KEY `fk_decision_invitation_user` (`IDuser`),
  CONSTRAINT `fk_decision_invitation_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_decision_invitation_process` FOREIGN KEY (`IDdecision_process`) REFERENCES `decision_process` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_decision_invitation_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decision_invitation`
--

LOCK TABLES `decision_invitation` WRITE;
/*!40000 ALTER TABLE `decision_invitation` DISABLE KEYS */;
/*!40000 ALTER TABLE `decision_invitation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `decision_participant`
--

DROP TABLE IF EXISTS `decision_participant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `decision_participant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdecision_process` int(11) NOT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `display_name` varchar(190) DEFAULT NULL,
  `role` varchar(30) NOT NULL DEFAULT 'participant',
  `status` varchar(30) NOT NULL DEFAULT 'invited',
  `access_token` varchar(64) DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `invitation_sent_at` datetime DEFAULT NULL,
  `invitation_opened_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_decision_participant_user` (`IDdecision_process`,`IDuser`),
  UNIQUE KEY `uniq_decision_participant_email` (`IDdecision_process`,`email`),
  UNIQUE KEY `uniq_decision_participant_access_token` (`access_token`),
  KEY `idx_decision_participant_status` (`status`),
  KEY `idx_decision_participant_role` (`role`),
  KEY `idx_decision_participant_active` (`active`),
  CONSTRAINT `fk_decision_participant_process` FOREIGN KEY (`IDdecision_process`) REFERENCES `decision_process` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decision_participant`
--

LOCK TABLES `decision_participant` WRITE;
/*!40000 ALTER TABLE `decision_participant` DISABLE KEYS */;
/*!40000 ALTER TABLE `decision_participant` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `decision_process`
--

DROP TABLE IF EXISTS `decision_process`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `decision_process` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) DEFAULT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `title` varchar(190) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `decision_type` varchar(20) NOT NULL DEFAULT 'decision',
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `evaluation_method` varchar(40) NOT NULL DEFAULT 'simple_vote',
  `visibility_type` varchar(30) NOT NULL DEFAULT 'organization',
  `parameters` mediumtext DEFAULT NULL,
  `consultation_start_at` datetime DEFAULT NULL,
  `consultation_end_at` datetime DEFAULT NULL,
  `evaluation_start_at` datetime DEFAULT NULL,
  `evaluation_end_at` datetime DEFAULT NULL,
  `results_published_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_decision_process_org` (`IDorganization`),
  KEY `idx_decision_process_holon` (`IDholon`),
  KEY `idx_decision_process_status` (`status`),
  KEY `idx_decision_process_method` (`evaluation_method`),
  KEY `idx_decision_process_type` (`decision_type`),
  CONSTRAINT `fk_decision_process_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_decision_process_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decision_process`
--

LOCK TABLES `decision_process` WRITE;
/*!40000 ALTER TABLE `decision_process` DISABLE KEYS */;
/*!40000 ALTER TABLE `decision_process` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `decision_proposal`
--

DROP TABLE IF EXISTS `decision_proposal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `decision_proposal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdecision_process` int(11) NOT NULL,
  `IDdecision_group` int(11) NOT NULL,
  `IDuser_author` int(11) DEFAULT NULL,
  `title` varchar(190) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `info_url` varchar(500) DEFAULT NULL,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `timezone` varchar(64) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `parameters` mediumtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_decision_proposal_process` (`IDdecision_process`),
  KEY `idx_decision_proposal_group` (`IDdecision_group`),
  KEY `idx_decision_proposal_position` (`IDdecision_process`,`position`),
  KEY `idx_decision_proposal_group_position` (`IDdecision_group`,`position`),
  KEY `idx_decision_proposal_active` (`active`),
  KEY `idx_decision_proposal_author` (`IDuser_author`),
  CONSTRAINT `fk_decision_proposal_author` FOREIGN KEY (`IDuser_author`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_decision_proposal_group` FOREIGN KEY (`IDdecision_group`) REFERENCES `decision_group` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_decision_proposal_process` FOREIGN KEY (`IDdecision_process`) REFERENCES `decision_process` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decision_proposal`
--

LOCK TABLES `decision_proposal` WRITE;
/*!40000 ALTER TABLE `decision_proposal` DISABLE KEYS */;
/*!40000 ALTER TABLE `decision_proposal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `decision_response`
--

DROP TABLE IF EXISTS `decision_response`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `decision_response` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdecision_process` int(11) NOT NULL,
  `IDdecision_group` int(11) NOT NULL,
  `IDdecision_participant` int(11) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `parameters` mediumtext DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_decision_response_group_participant` (`IDdecision_group`,`IDdecision_participant`),
  KEY `idx_decision_response_group` (`IDdecision_group`),
  KEY `idx_decision_response_status` (`status`),
  KEY `fk_decision_response_process` (`IDdecision_process`),
  KEY `fk_decision_response_participant` (`IDdecision_participant`),
  CONSTRAINT `fk_decision_response_group` FOREIGN KEY (`IDdecision_group`) REFERENCES `decision_group` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_decision_response_participant` FOREIGN KEY (`IDdecision_participant`) REFERENCES `decision_participant` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_decision_response_process` FOREIGN KEY (`IDdecision_process`) REFERENCES `decision_process` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decision_response`
--

LOCK TABLES `decision_response` WRITE;
/*!40000 ALTER TABLE `decision_response` DISABLE KEYS */;
/*!40000 ALTER TABLE `decision_response` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `decision_result`
--

DROP TABLE IF EXISTS `decision_result`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `decision_result` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdecision_process` int(11) NOT NULL,
  `IDdecision_group` int(11) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `summary` mediumtext DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `computed_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_decision_result_group` (`IDdecision_group`),
  KEY `idx_decision_result_group` (`IDdecision_group`),
  KEY `idx_decision_result_status` (`status`),
  KEY `fk_decision_result_process` (`IDdecision_process`),
  CONSTRAINT `fk_decision_result_group` FOREIGN KEY (`IDdecision_group`) REFERENCES `decision_group` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_decision_result_process` FOREIGN KEY (`IDdecision_process`) REFERENCES `decision_process` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decision_result`
--

LOCK TABLES `decision_result` WRITE;
/*!40000 ALTER TABLE `decision_result` DISABLE KEYS */;
/*!40000 ALTER TABLE `decision_result` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deferred_proposal`
--

DROP TABLE IF EXISTS `deferred_proposal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `deferred_proposal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDuser_author` int(11) NOT NULL,
  `target_type` varchar(40) NOT NULL,
  `operation` varchar(20) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `before_state` mediumtext DEFAULT NULL,
  `after_state` mediumtext DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `IDdecision_proposal` int(11) DEFAULT NULL,
  `IDdocument_pv_point` int(11) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `validation_context` text DEFAULT NULL,
  `status_message` text DEFAULT NULL,
  `IDuser_validated` int(11) DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `IDuser_applied` int(11) DEFAULT NULL,
  `applied_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_deferred_proposal_decision` (`IDdecision_proposal`,`position`),
  KEY `idx_deferred_proposal_pv_point` (`IDdocument_pv_point`,`position`),
  KEY `idx_deferred_proposal_target` (`target_type`,`target_id`),
  KEY `idx_deferred_proposal_status` (`status`),
  CONSTRAINT `fk_deferred_proposal_decision` FOREIGN KEY (`IDdecision_proposal`) REFERENCES `decision_proposal` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_deferred_proposal_pv_point` FOREIGN KEY (`IDdocument_pv_point`) REFERENCES `document_pv_point` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deferred_proposal`
--

LOCK TABLES `deferred_proposal` WRITE;
/*!40000 ALTER TABLE `deferred_proposal` DISABLE KEYS */;
/*!40000 ALTER TABLE `deferred_proposal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document`
--

DROP TABLE IF EXISTS `document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `content` mediumtext DEFAULT NULL,
  `contentedition` longtext DEFAULT NULL,
  `datecontentedition` datetime DEFAULT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `IDuser` int(11) NOT NULL,
  `IDusercreation` int(11) DEFAULT NULL,
  `IDorganization` int(11) DEFAULT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDevent` int(11) DEFAULT NULL,
  `estDossier` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `documenttype` varchar(30) NOT NULL DEFAULT 'html',
  `pvstage` varchar(30) DEFAULT NULL,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `externalurl` varchar(2000) DEFAULT NULL,
  `openinnewwindow` tinyint(1) NOT NULL DEFAULT 0,
  `project_visible_in_holon` tinyint(1) NOT NULL DEFAULT 0,
  `storedfilepath` varchar(1000) DEFAULT NULL,
  `storedfilename` varchar(255) DEFAULT NULL,
  `storedfilemime` varchar(255) DEFAULT NULL,
  `storedfilesize` int(11) DEFAULT NULL,
  `nextcloudfolderpath` varchar(1000) DEFAULT NULL,
  `nextcloudfolderfileid` varchar(64) DEFAULT NULL,
  `etherpadpadid` varchar(255) DEFAULT NULL,
  `ethercalcroomid` varchar(255) DEFAULT NULL,
  `spacedeckspaceid` varchar(255) DEFAULT NULL,
  `IDdocument_parent` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL,
  `dateconsultation` datetime DEFAULT NULL,
  `dateedition` datetime DEFAULT NULL,
  `IDuseredition` int(11) DEFAULT NULL,
  `IDuser_pv_editor` int(11) DEFAULT NULL,
  `IDuser_pv_official_editor` int(11) DEFAULT NULL,
  `pv_editor_handover_open` tinyint(1) NOT NULL DEFAULT 0,
  `IDusermodification` int(11) DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `codeview` varchar(150) DEFAULT NULL,
  `codeedit` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_document_organization` (`IDorganization`),
  KEY `idx_document_holon` (`IDholon`),
  KEY `idx_document_pvstage` (`pvstage`),
  KEY `idx_document_pv_editor` (`IDuser_pv_editor`),
  KEY `idx_document_event` (`IDevent`),
  KEY `idx_document_parent` (`IDdocument_parent`),
  KEY `idx_document_folder` (`estDossier`),
  KEY `idx_document_user_creation` (`IDusercreation`),
  KEY `idx_document_user_modification` (`IDusermodification`),
  KEY `idx_document_draft_date` (`datecontentedition`),
  KEY `idx_document_editing_user` (`IDuseredition`),
  KEY `idx_document_editing_date` (`dateedition`),
  KEY `idx_document_type` (`documenttype`),
  KEY `idx_document_stored_file_path` (`storedfilepath`(255)),
  KEY `idx_document_active` (`active`),
  KEY `idx_document_pv_template` (`IDorganization`,`is_template`,`active`,`documenttype`),
  KEY `idx_document_pv_editor_handover` (`pv_editor_handover_open`),
  KEY `idx_document_etherpad_pad` (`etherpadpadid`),
  KEY `idx_document_pv_official_editor` (`IDuser_pv_official_editor`),
  KEY `idx_document_consultation_date` (`dateconsultation`),
  KEY `idx_document_nextcloud_folder_path` (`nextcloudfolderpath`(255)),
  KEY `idx_document_nextcloud_folder_fileid` (`nextcloudfolderfileid`),
  CONSTRAINT `fk_document_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_parent` FOREIGN KEY (`IDdocument_parent`) REFERENCES `document` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_pv_editor` FOREIGN KEY (`IDuser_pv_editor`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_pv_official_editor` FOREIGN KEY (`IDuser_pv_official_editor`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_user_creation` FOREIGN KEY (`IDusercreation`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_user_editing` FOREIGN KEY (`IDuseredition`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_user_modification` FOREIGN KEY (`IDusermodification`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20340 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document`
--

LOCK TABLES `document` WRITE;
/*!40000 ALTER TABLE `document` DISABLE KEYS */;
/*!40000 ALTER TABLE `document` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_application_tab`
--

DROP TABLE IF EXISTS `document_application_tab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_application_tab` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdocument` int(11) NOT NULL,
  `IDapplication` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 10,
  `view_parameters` mediumtext DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_document_application_tab` (`IDdocument`,`IDapplication`),
  KEY `idx_document_application_tab_document` (`IDdocument`,`position`),
  KEY `idx_document_application_tab_application` (`IDapplication`),
  CONSTRAINT `fk_document_application_tab_application` FOREIGN KEY (`IDapplication`) REFERENCES `application` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_application_tab_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_application_tab`
--

LOCK TABLES `document_application_tab` WRITE;
/*!40000 ALTER TABLE `document_application_tab` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_application_tab` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_pv_point`
--

DROP TABLE IF EXISTS `document_pv_point`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_pv_point` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdocument` int(11) NOT NULL,
  `item_type` varchar(20) NOT NULL DEFAULT 'point',
  `IDparent` int(11) DEFAULT NULL,
  `title` varchar(80) NOT NULL,
  `IDuser_author` int(11) DEFAULT NULL,
  `IDuser_modification` int(11) DEFAULT NULL,
  `IDuser_editing` int(11) DEFAULT NULL,
  `edit_lock_token` varchar(80) DEFAULT NULL,
  `IDuser_edit_takeover_request` int(11) DEFAULT NULL,
  `edit_takeover_request_token` varchar(80) DEFAULT NULL,
  `edit_takeover_target_token` varchar(80) DEFAULT NULL,
  `date_edit_takeover_request` datetime DEFAULT NULL,
  `author_email` varchar(250) DEFAULT NULL,
  `IDholon_concerned` int(11) DEFAULT NULL,
  `content` mediumtext DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 1,
  `priority` tinyint(3) unsigned DEFAULT 3,
  `desired_duration_minutes` int(11) DEFAULT NULL,
  `actual_duration_minutes` int(11) DEFAULT NULL,
  `pointtype` varchar(20) NOT NULL DEFAULT 'information',
  `is_handled` tinyint(1) NOT NULL DEFAULT 0,
  `is_confidential` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dateedition` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_document_pv_point_document` (`IDdocument`),
  KEY `idx_document_pv_point_author` (`IDuser_author`),
  KEY `idx_document_pv_point_author_email` (`author_email`),
  KEY `idx_document_pv_point_holon` (`IDholon_concerned`),
  KEY `idx_document_pv_point_position` (`IDdocument`,`position`),
  KEY `idx_document_pv_point_type` (`pointtype`),
  KEY `idx_document_pv_point_active` (`active`),
  KEY `idx_document_pv_point_parent` (`IDdocument`,`IDparent`,`position`),
  KEY `idx_document_pv_point_item_type` (`item_type`),
  KEY `fk_document_pv_point_parent` (`IDparent`),
  KEY `idx_document_pv_point_modification_user` (`IDuser_modification`),
  KEY `idx_document_pv_point_editing_user` (`IDuser_editing`),
  KEY `idx_document_pv_point_dateedition` (`dateedition`),
  KEY `idx_document_pv_point_takeover_user` (`IDuser_edit_takeover_request`),
  KEY `idx_document_pv_point_takeover_date` (`date_edit_takeover_request`),
  CONSTRAINT `fk_document_pv_point_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_pv_point_editing_user` FOREIGN KEY (`IDuser_editing`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_pv_point_holon_concerned` FOREIGN KEY (`IDholon_concerned`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_pv_point_modification_user` FOREIGN KEY (`IDuser_modification`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_pv_point_parent` FOREIGN KEY (`IDparent`) REFERENCES `document_pv_point` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_pv_point_takeover_user` FOREIGN KEY (`IDuser_edit_takeover_request`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=62651 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_pv_point`
--

LOCK TABLES `document_pv_point` WRITE;
/*!40000 ALTER TABLE `document_pv_point` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_pv_point` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_pv_point_holon`
--

DROP TABLE IF EXISTS `document_pv_point_holon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_pv_point_holon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdocument_pv_point` int(11) NOT NULL,
  `IDholon` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_document_pv_point_holon` (`IDdocument_pv_point`,`IDholon`),
  KEY `idx_document_pv_point_holon_holon` (`IDholon`),
  KEY `idx_document_pv_point_holon_position` (`IDdocument_pv_point`,`position`),
  CONSTRAINT `fk_document_pv_point_holon_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_pv_point_holon_point` FOREIGN KEY (`IDdocument_pv_point`) REFERENCES `document_pv_point` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2325 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_pv_point_holon`
--

LOCK TABLES `document_pv_point_holon` WRITE;
/*!40000 ALTER TABLE `document_pv_point_holon` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_pv_point_holon` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_pv_point_tension`
--

DROP TABLE IF EXISTS `document_pv_point_tension`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_pv_point_tension` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdocument_pv_point` int(11) NOT NULL,
  `IDtension` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_document_pv_point_tension` (`IDdocument_pv_point`,`IDtension`),
  KEY `idx_document_pv_point_tension_tension` (`IDtension`),
  KEY `idx_document_pv_point_tension_position` (`IDdocument_pv_point`,`position`),
  CONSTRAINT `fk_document_pv_point_tension_point` FOREIGN KEY (`IDdocument_pv_point`) REFERENCES `document_pv_point` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_pv_point_tension_tension` FOREIGN KEY (`IDtension`) REFERENCES `tension` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2334 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_pv_point_tension`
--

LOCK TABLES `document_pv_point_tension` WRITE;
/*!40000 ALTER TABLE `document_pv_point_tension` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_pv_point_tension` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_share_link`
--

DROP TABLE IF EXISTS `document_share_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_share_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` int(11) NOT NULL DEFAULT 1,
  `IDorganization` int(11) NOT NULL,
  `IDdocument` int(11) NOT NULL,
  `IDuser` int(11) NOT NULL,
  `label` varchar(150) DEFAULT NULL,
  `token` varchar(80) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `allow_live_follow` tinyint(1) NOT NULL DEFAULT 0,
  `allow_pv_contribution` tinyint(1) NOT NULL DEFAULT 0,
  `recipient_email` varchar(250) DEFAULT NULL,
  `recipient_user_id` int(11) DEFAULT NULL,
  `datecreation` datetime DEFAULT NULL,
  `dateexpiration` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_document_share_link_token` (`token`),
  KEY `idx_document_share_link_document` (`IDdocument`,`active`),
  KEY `idx_document_share_link_organization` (`IDorganization`,`active`),
  KEY `idx_document_share_link_user` (`IDuser`),
  KEY `idx_document_share_link_pv_recipient` (`IDdocument`,`recipient_email`,`active`),
  CONSTRAINT `fk_document_share_link_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_share_link_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_share_link_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_share_link`
--

LOCK TABLES `document_share_link` WRITE;
/*!40000 ALTER TABLE `document_share_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_share_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDdecision_proposal` int(11) DEFAULT NULL,
  `IDorganization` int(11) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDproject` int(11) DEFAULT NULL,
  `IDuser` int(11) NOT NULL,
  `title` varchar(190) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `timezone` varchar(64) DEFAULT NULL,
  `locationmode` varchar(20) DEFAULT NULL,
  `locationaddress` varchar(1000) DEFAULT NULL,
  `videomeetingurl` varchar(2000) DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `is_all_day` tinyint(1) NOT NULL DEFAULT 0,
  `parameters` mediumtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_org` (`IDorganization`),
  KEY `idx_event_holon` (`IDholon`),
  KEY `idx_event_user` (`IDuser`),
  KEY `idx_event_status` (`status`),
  KEY `idx_event_active` (`active`),
  KEY `idx_event_start` (`start_at`),
  KEY `idx_event_org_start` (`IDorganization`,`start_at`),
  KEY `idx_event_location_mode` (`locationmode`),
  KEY `idx_event_project` (`IDproject`),
  UNIQUE KEY `uq_event_decision_proposal` (`IDdecision_proposal`),
  CONSTRAINT `fk_event_decision_proposal` FOREIGN KEY (`IDdecision_proposal`) REFERENCES `decision_proposal` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_event_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_event_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14221 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event`
--

LOCK TABLES `event` WRITE;
/*!40000 ALTER TABLE `event` DISABLE KEYS */;
/*!40000 ALTER TABLE `event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_attendance`
--

DROP TABLE IF EXISTS `event_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDevent` int(11) NOT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `display_name` varchar(190) DEFAULT NULL,
  `is_present` tinyint(1) NOT NULL DEFAULT 0,
  `IDuser_checked_by` int(11) DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_event_attendance_user` (`IDevent`,`IDuser`),
  UNIQUE KEY `uniq_event_attendance_email` (`IDevent`,`email`),
  KEY `idx_event_attendance_present` (`is_present`),
  KEY `idx_event_attendance_checked_by` (`IDuser_checked_by`),
  KEY `idx_event_attendance_active` (`active`),
  KEY `fk_event_attendance_user` (`IDuser`),
  CONSTRAINT `fk_event_attendance_checked_by` FOREIGN KEY (`IDuser_checked_by`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_event_attendance_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_attendance_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_attendance`
--

LOCK TABLES `event_attendance` WRITE;
/*!40000 ALTER TABLE `event_attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_invitation`
--

DROP TABLE IF EXISTS `event_invitation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_invitation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDevent` int(11) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `display_name` varchar(190) DEFAULT NULL,
  `invitation_type` varchar(30) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'invited',
  `parameters` mediumtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_event_invitation_holon` (`IDevent`,`IDholon`),
  UNIQUE KEY `uniq_event_invitation_user` (`IDevent`,`IDuser`),
  UNIQUE KEY `uniq_event_invitation_email` (`IDevent`,`email`),
  KEY `idx_event_invitation_type` (`invitation_type`),
  KEY `idx_event_invitation_status` (`status`),
  KEY `idx_event_invitation_active` (`active`),
  KEY `fk_event_invitation_holon` (`IDholon`),
  KEY `fk_event_invitation_user` (`IDuser`),
  CONSTRAINT `fk_event_invitation_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_invitation_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_invitation_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_invitation`
--

LOCK TABLES `event_invitation` WRITE;
/*!40000 ALTER TABLE `event_invitation` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_invitation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `external_calendar`
--

DROP TABLE IF EXISTS `external_calendar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `external_calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `provider` varchar(32) NOT NULL DEFAULT 'caldav',
  `title` varchar(190) NOT NULL,
  `calendar_url` varchar(2000) NOT NULL,
  `username` varchar(250) NOT NULL,
  `password_encrypted` text NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#0f766e',
  `timezone` varchar(64) DEFAULT NULL,
  `source_ctag` varchar(255) DEFAULT NULL,
  `availability_only` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `last_sync_at` datetime DEFAULT NULL,
  `last_sync_error` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_external_calendar_user_active` (`IDuser`,`active`),
  CONSTRAINT `fk_external_calendar_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `external_calendar`
--

LOCK TABLES `external_calendar` WRITE;
/*!40000 ALTER TABLE `external_calendar` DISABLE KEYS */;
/*!40000 ALTER TABLE `external_calendar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `external_calendar_event`
--

DROP TABLE IF EXISTS `external_calendar_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `external_calendar_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDexternalcalendar` int(11) NOT NULL,
  `source_key` varchar(512) NOT NULL,
  `source_etag` varchar(255) DEFAULT NULL,
  `title` varchar(1000) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `location` varchar(1000) DEFAULT NULL,
  `timezone` varchar(64) DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `is_all_day` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_busy` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_external_calendar_event_source` (`IDexternalcalendar`,`source_key`),
  KEY `idx_external_calendar_event_range` (`IDexternalcalendar`,`active`,`start_at`,`end_at`),
  CONSTRAINT `fk_external_calendar_event_calendar` FOREIGN KEY (`IDexternalcalendar`) REFERENCES `external_calendar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=228 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `external_calendar_event`
--

LOCK TABLES `external_calendar_event` WRITE;
/*!40000 ALTER TABLE `external_calendar_event` DISABLE KEYS */;
/*!40000 ALTER TABLE `external_calendar_event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faq`
--

DROP TABLE IF EXISTS `faq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faq` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `IDhowto` int(10) unsigned DEFAULT NULL,
  `IDorganization` int(10) unsigned DEFAULT NULL,
  `IDholon` int(10) unsigned DEFAULT NULL,
  `IDparcours` int(10) unsigned DEFAULT NULL,
  `IDapplication` int(10) unsigned DEFAULT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text DEFAULT NULL,
  `image` varchar(1000) DEFAULT NULL,
  `video` varchar(1000) DEFAULT NULL,
  `detail` text DEFAULT NULL,
  `displayorder` int(11) DEFAULT 0,
  `isactive` tinyint(1) DEFAULT 1,
  `viewcount` int(11) DEFAULT 0,
  `positive_score` float NOT NULL DEFAULT 0,
  `negative_score` float NOT NULL DEFAULT 0,
  `total_votes` int(11) NOT NULL DEFAULT 0,
  `reliability` float NOT NULL DEFAULT 0,
  `reliability_updated_at` datetime DEFAULT NULL,
  `score_decayed_at` datetime DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `request_user_id` int(10) unsigned DEFAULT NULL,
  `request_author_name` varchar(255) DEFAULT NULL,
  `request_author_email` varchar(255) DEFAULT NULL,
  `request_description` text DEFAULT NULL,
  `request_answered_at` datetime DEFAULT NULL,
  `request_relayed_at` datetime DEFAULT NULL,
  `request_ai_draft` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_faq_parcours` (`IDparcours`),
  KEY `idx_faq_application` (`IDapplication`),
  KEY `idx_faq_reliability` (`reliability`),
  KEY `idx_faq_reliability_updated_at` (`reliability_updated_at`),
  KEY `idx_faq_request_user_id` (`request_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3254 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faq`
--

LOCK TABLES `faq` WRITE;
/*!40000 ALTER TABLE `faq` DISABLE KEYS */;
INSERT INTO `faq` VALUES
(3201,NULL,NULL,NULL,NULL,NULL,'Comment ouvrir les outils d aide dans OMO ?','Ouvrez le bouton Aide dans la topbar pour retrouver la FAQ, les tutoriels et la visite guidee.',NULL,NULL,'<p>La zone Aide centralise les ressources utiles quand vous avez un doute ou que vous decouvrez un ecran.</p><p>La FAQ donne des reponses rapides, les tutoriels vont plus loin et la visite guidee explique les boutons visibles sur la page en cours.</p>',10,1,8,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 09:00:00','2026-06-22 09:00:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3202,NULL,NULL,NULL,NULL,NULL,'Comment utiliser la recherche de la topbar ?','Tapez quelques mots cles dans la recherche puis ouvrez le resultat qui correspond a votre besoin.',NULL,NULL,'<p>La recherche de la topbar sert a retrouver rapidement un cercle, un role, un outil ou un acces utile.</p><p>Si plusieurs modules sont proposes, commencez par ceux qui correspondent a votre besoin puis affinez avec des mots simples et precis.</p>',20,1,1,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 09:05:00','2026-06-22 09:05:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3203,NULL,NULL,NULL,NULL,NULL,'Comment changer ma langue ou mon theme ?','Ouvrez le menu Profil dans la topbar pour regler la langue et le theme d affichage.',NULL,NULL,'<p>Le menu Profil permet de retrouver les reglages personnels les plus utiles sans quitter votre espace de travail.</p><p>Vous pouvez y adapter la langue de l interface et choisir le theme qui vous convient le mieux pour votre usage quotidien.</p>',30,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 09:10:00','2026-06-22 09:10:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3204,NULL,NULL,NULL,NULL,NULL,'A quoi sert le switch Local / Enfants directs / Descendants ?','Il permet d afficher le holon courant seul, avec ses enfants directs ou avec tous ses descendants.',NULL,NULL,'<p>Le mode Local affiche uniquement les elements lies au holon courant.</p><p>Enfants directs ajoute les elements lies a ses enfants, tandis que Descendants couvre tout son sous-arbre.</p>',40,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 09:15:00','2026-08-31 14:05:21',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3205,NULL,NULL,NULL,NULL,NULL,'Comment passer du tri Date au tri Alphabetique ?','Utilisez le controle de tri dans l entete du drawer pour choisir l ordre qui vous aide le plus.',NULL,NULL,'<p>Le tri par date est utile pour revoir ce qui vient d etre cree ou modifie recemment.</p><p>Le tri alphabetique est souvent plus confortable quand vous cherchez un nom connu dans une longue liste.</p>',50,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 09:20:00','2026-06-22 09:20:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3206,NULL,NULL,NULL,NULL,NULL,'A quoi sert le mode Detail / Compact ?','Le mode Detail montre plus d informations par carte, tandis que Compact affiche plus d elements a l ecran.',NULL,NULL,'<p>Choisissez Detail quand vous voulez lire les resumes, les metadonnees ou mieux comparer plusieurs cartes.</p><p>Choisissez Compact quand vous voulez parcourir beaucoup d elements rapidement, en particulier sur mobile ou dans une colonne etroite.</p>',60,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 09:25:00','2026-06-22 09:25:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3207,NULL,NULL,NULL,NULL,NULL,'Comment creer un document dans OMO ?','Ouvrez l app Documents puis utilisez le bouton Ajouter si votre role vous y autorise.',NULL,NULL,'<p>Sur grand ecran, le bouton de creation apparait dans l entete du module. Sur mobile, il peut etre reduit a une icone en haut a droite.</p><p>Si vous ne voyez pas ce bouton, cela signifie en general que votre contexte actuel ou vos droits ne permettent pas cette creation.</p>',70,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 09:30:00','2026-06-22 09:30:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3208,NULL,NULL,NULL,NULL,NULL,'Comment modifier un document existant ?','Ouvrez le document puis lancez l action Editer depuis le drawer ou le menu prevu.',NULL,NULL,'<p>L edition passe par le formulaire du document et enregistre les changements dans le contexte de ce document.</p><p>Si un document appartient deja a une organisation ou a un holon, les droits de ce contexte continuent a s appliquer au moment de la sauvegarde.</p>',80,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 09:35:00','2026-06-22 09:35:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3209,NULL,NULL,NULL,NULL,NULL,'A quoi sert l app Memo ?','Memo rassemble vos documents personnels et vos notes dans une vue simple a parcourir.',NULL,NULL,'<p>La liste Memo peut regrouper les documents dont vous etes l auteur, y compris quand ils proviennent de plusieurs holons.</p><p>Le detail se consulte ensuite dans un drawer interne, ce qui permet de rester dans le meme espace sans ouvrir une nouvelle page.</p>',90,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 09:40:00','2026-06-22 09:40:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3210,NULL,NULL,NULL,NULL,NULL,'Comment reediter un memo depuis l app Memo ?','Ouvrez le menu ... sur un memo puis choisissez Editer.',NULL,NULL,'<p>L action Editer ouvre le formulaire du memo dans un drawer, avec un parcours proche de celui du module Documents.</p><p>Les memos sans contexte d organisation peuvent etre reedites par leur auteur, alors que les documents deja classes gardent les droits de leur contexte habituel.</p>',100,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 09:45:00','2026-06-22 09:45:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3211,NULL,NULL,NULL,NULL,NULL,'Comment terminer ou classer un memo depuis Telegram ?','Utilisez les boutons proposes par le bot pour choisir une destination autorisee ou terminer dans le contexte courant.',NULL,NULL,'<p>Le bot ne propose que les destinations de classement qui restent autorisees pour votre contexte et vos droits.</p><p>Si le bouton Terminer ici ou certaines destinations ne sont pas visibles, cela signifie simplement que cette action nest pas disponible pour vous a cet endroit.</p>',110,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 09:50:00','2026-06-22 09:50:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3212,NULL,NULL,NULL,NULL,NULL,'Comment creer une prise de decision ?','Ouvrez l app Decisions puis utilisez le bouton de creation disponible dans l entete si vous avez le droit necessaire.',NULL,NULL,'<p>La creation se fait dans le contexte courant, par exemple pour une organisation, un cercle ou un autre niveau de structure.</p><p>Prenez le temps de definir un titre clair, une description utile et les dates importantes avant de lancer la participation.</p>',120,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 09:55:00','2026-06-22 09:55:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3213,NULL,NULL,NULL,NULL,NULL,'Comment participer a une decision avec un lien public ou un acces personnel ?','Ouvrez la page de participation recue par lien ou demandez votre acces personnel depuis l ecran public du scrutin.',NULL,NULL,'<p>Certaines decisions peuvent accepter la participation sans invitation classique, directement depuis un lien public partage par l organisateur.</p><p>Si ce nest pas le cas, utilisez la page Recevoir mon acces personnel pour demander un lien individuel avant de voter.</p>',130,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 10:00:00','2026-06-22 10:00:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3214,NULL,NULL,NULL,NULL,NULL,'Comment noter des propositions en jugement majoritaire ?','Attribuez une mention a chaque proposition selon l echelle affichee, de la plus favorable a la moins favorable.',NULL,NULL,'<p>Le jugement majoritaire ne consiste pas a choisir une seule proposition. Vous evaluez chaque option avec la meme echelle.</p><p>Le resultat final compare ensuite la repartition des mentions pour aider a faire ressortir la proposition la plus solide.</p>',140,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 10:05:00','2026-06-22 10:05:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3215,NULL,NULL,NULL,NULL,NULL,'Comment ajouter un evenement dans le calendrier ?','Ouvrez le calendrier puis utilisez le bouton Ajouter si votre contexte vous autorise a creer des dates.',NULL,NULL,'<p>Comme pour les autres modules, le bouton peut etre plein texte sur grand ecran ou reduit a une icone sur mobile.</p><p>Si vous ne pouvez pas creer de date a cet endroit, changez de contexte ou demandez a une personne administratrice de verifier vos droits.</p>',150,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 10:10:00','2026-06-22 10:10:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3216,NULL,NULL,NULL,NULL,NULL,'Comment changer de vue dans le calendrier ?','Utilisez le selecteur Mois, Semaine, Jour ou Liste pour choisir la lecture la plus pratique.',NULL,NULL,'<p>Chaque vue repond a un besoin different: Mois pour la planification generale, Semaine ou Jour pour le detail, Liste pour un balayage rapide.</p><p>Sur mobile, ces vues peuvent apparaitre sous forme d icones plus compactes afin de laisser davantage de place au contenu.</p>',160,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 10:15:00','2026-06-22 10:15:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3217,NULL,NULL,NULL,NULL,NULL,'Pourquoi je ne vois pas toujours le bouton Ajouter ?','Le bouton apparait seulement si vous avez la permission de creation dans le contexte ouvert.',NULL,NULL,'<p>Ce principe vaut notamment pour les documents, les prises de decision, les dates et la creation de FAQ.</p><p>Si vous pensez que ce bouton devrait etre disponible, verifiez le contexte courant ou demandez une verification des permissions sur le holon concerne.</p>',170,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 10:20:00','2026-06-22 10:20:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3218,NULL,NULL,NULL,NULL,NULL,'Comment ajouter une question dans la FAQ ?','Ouvrez la FAQ du contexte voulu puis utilisez le bouton Ajouter une question si cette action est disponible.',NULL,NULL,'<p>Selon votre ecran, la nouvelle question peut etre creee au niveau du contexte courant, du niveau organisation ou dans un scope plus global.</p><p>Si aucun bouton de creation ne saffiche, cela signifie que la permission de creation de FAQ nest pas accordee dans ce contexte.</p>',180,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 10:25:00','2026-06-22 10:25:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3219,NULL,NULL,NULL,NULL,NULL,'A quoi servent les votes sur les reponses de la FAQ ?','Les boutons de vote permettent de signaler si une reponse est utile afin de mieux mettre en avant les bonnes explications.',NULL,NULL,'<p>Quand une reponse vous aide vraiment, un vote positif aide a la faire remonter dans la FAQ.</p><p>Ces retours servent a rendre les questions les plus utiles plus visibles pour les autres membres de l organisation.</p>',190,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-06-22 10:30:00','2026-06-22 10:30:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3220,NULL,NULL,NULL,NULL,NULL,'Comment faire apparaitre mon organisation sur la carte publique ?','Renseignez un emplacement dans les parametres de l organisation et verifiez que les informations utiles sont lisibles sans connexion.',NULL,NULL,'<p>La carte publique utilise un emplacement facultatif, generalement saisi en latitude et longitude dans les parametres de l organisation.</p><p>Seules les informations explicitement exposees comme publiques sont reprises sur cette carte, ce qui permet de garder le controle sur ce qui est visible sans connexion.</p>',200,1,0,0,0,0,0,'2026-09-21 09:47:59','2026-09-21 09:47:59','2026-06-22 10:35:00','2026-06-22 10:35:00',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3221,NULL,NULL,NULL,NULL,NULL,'Est-ce que je peux ajouter les contacts de OMO a mon telephone ou mon ordinateur ?','Oui. Ajoutez un compte CardDAV avec votre identifiant OMO pour synchroniser les contacts auxquels vous avez acces.',NULL,NULL,'<p>OMO propose un annuaire CardDAV utilisable par la plupart des telephones et applications de contacts.</p><ol><li>Dans OMO, ouvrez votre profil et definissez un mot de passe si ce n est pas deja fait.</li><li>Dans les reglages de votre appareil ou de votre application de contacts, ajoutez un compte <strong>CardDAV</strong>.</li><li>Comme adresse du serveur, saisissez <em>url_serveur</em>/omo/api/carddav/.</li><li>Utilisez votre adresse e-mail OMO, ou votre identifiant de connexion, ainsi que votre mot de passe OMO.</li></ol><p>Seuls les contacts des membres que vous etes autorise a voir sont proposes. La synchronisation est actuellement en lecture seule : les modifications faites sur votre appareil ne sont pas renvoyees dans OMO.</p>',210,1,0,0,0,0,0,'2026-09-21 09:47:59','2026-09-21 09:47:59','2026-08-31 14:05:21','2026-08-31 14:05:21',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3222,NULL,NULL,NULL,NULL,NULL,'Est-ce que je peux ajouter les rendez-vous et reunions de OMO a mon telephone ou mon ordinateur ?','Oui. Ajoutez un compte CalDAV pour consulter dans votre calendrier les reunions OMO auxquelles vous avez acces.',NULL,NULL,'<p>OMO propose un calendrier CalDAV utilisable par la plupart des telephones et applications de calendrier.</p><ol><li>Dans OMO, ouvrez votre profil et definissez un mot de passe si ce n est pas deja fait.</li><li>Dans les reglages de votre appareil ou de votre application de calendrier, ajoutez un compte <strong>CalDAV</strong>.</li><li>Comme adresse du serveur, saisissez <em>url_serveur</em>/omo/api/caldav/.</li><li>Utilisez votre adresse e-mail OMO, ou votre identifiant de connexion, ainsi que votre mot de passe OMO.</li></ol><p>Les calendriers des organisations dont vous etes membre et dont le calendrier est actif sont proposes automatiquement. La synchronisation est actuellement en lecture seule : creez ou modifiez les reunions directement dans OMO.</p>',220,1,0,0,0,0,0,'2026-09-21 09:47:59','2026-09-21 09:47:59','2026-08-31 14:05:21','2026-08-31 14:05:21',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3223,NULL,NULL,NULL,NULL,NULL,'Peut-on utiliser OMO sans avoir créé de structure ?','Oui. Plusieurs fonctions restent utilisables directement au niveau de l’organisation, même sans espace ni cercle.',NULL,NULL,'<p>Vous pouvez commencer dans une organisation qui ne possède pas encore de structure. Selon vos droits, créez directement à sa racine des règles, projets, documents, indicateurs, tâches récurrentes ou processus.</p><p>Si vous ajoutez des espaces plus tard, ces éléments restent rattachés à l’organisation et visibles depuis sa racine. Vous n’avez donc pas besoin de créer une structure provisoire pour démarrer.</p>',300,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3224,NULL,NULL,NULL,NULL,NULL,'Comment créer une règle dans une organisation sans structure ?','Ouvrez les règles de l’organisation et créez une règle à sa racine ; aucun espace n’est nécessaire.',NULL,NULL,'<ol><li>Ouvrez votre organisation, puis la rubrique des règles.</li><li>Lancez la création d’une règle et renseignez son contenu.</li><li>Enregistrez-la au niveau de l’organisation, selon les droits dont vous disposez.</li></ol><p>Le choix d’une autorité n’est proposé que si des autorités existent dans le contexte courant. La règle restera visible à la racine si une structure est ajoutée plus tard.</p>',310,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3225,NULL,NULL,NULL,NULL,NULL,'Faut-il choisir un espace pour créer un projet ?','Non. Dans une organisation sans structure, un projet peut être rattaché directement à l’organisation.',NULL,NULL,'<p>Ouvrez les projets de l’organisation, créez votre projet et complétez les informations demandées. Si l’organisation ne possède pas de structure, aucun espace n’est à choisir : le projet est enregistré à la racine.</p><p>Il reste accessible depuis cette racine même si vous créez des espaces par la suite.</p>',320,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3226,NULL,NULL,NULL,NULL,NULL,'Peut-on exécuter un processus sans avoir défini d’espaces ?','Oui. Un processus peut être créé et exécuté directement dans une organisation sans structure, selon vos droits.',NULL,NULL,'<ol><li>Depuis l’organisation, ouvrez les processus.</li><li>Créez un processus ou ouvrez-en un auquel vous avez accès.</li><li>Lancez son exécution et suivez ses étapes comme d’habitude.</li></ol><p>Lorsqu’aucune structure n’existe, les étapes ne demandent pas de choisir un espace. Les projets modèles et les projets produits par le processus restent rattachés à l’organisation.</p>',330,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3227,NULL,NULL,NULL,NULL,NULL,'Où apparaissent les tâches récurrentes d’une organisation sans structure ?','Elles apparaissent au niveau de l’organisation, dont le nom est indiqué dans la liste et le détail.',NULL,NULL,'<p>Ouvrez les tâches récurrentes depuis la racine de l’organisation. Selon vos droits, vous pouvez y créer, modifier, valider ou supprimer une tâche, sans créer d’espace au préalable.</p><p>Si une structure est ajoutée ensuite, les tâches déjà rattachées à l’organisation restent visibles depuis sa racine.</p>',340,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3228,NULL,NULL,NULL,NULL,NULL,'Comment fusionner deux de mes comptes ?','La fusion se lance depuis l’onglet Outils du profil, avec une vérification du second compte.',NULL,NULL,'<ol><li>Ouvrez votre profil, puis l’onglet <strong>Outils</strong>.</li><li>Dépliez l’action de fusion des comptes et indiquez le second compte.</li><li>Vérifiez que vous en êtes propriétaire avec le code reçu par e-mail ou son mot de passe, puis la double authentification si elle est activée.</li><li>Consultez la confirmation avant de lancer la fusion.</li></ol><p>La fusion conserve notamment les droits d’administration et le statut de superadmin détenus par l’un ou l’autre profil.</p>',350,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3229,NULL,NULL,NULL,NULL,NULL,'Que se passe-t-il si je supprime mon profil ?','Un récapitulatif précise les conséquences avant confirmation ; certains cas sont bloqués pour protéger les organisations.',NULL,NULL,'<ol><li>Dans votre profil, ouvrez <strong>Outils</strong>, puis l’action de suppression du profil.</li><li>Lisez le récapitulatif : il distingue les organisations conservées de celles qui seraient supprimées avec leur historique parce que vous en êtes le seul membre.</li><li>Résolvez les éventuels blocages, par exemple si vous êtes le dernier administrateur d’une organisation.</li><li>Si vous souhaitez toujours poursuivre, saisissez la confirmation textuelle demandée.</li></ol><p>Cette action est définitive. Les organisations conservées gardent les références historiques nécessaires grâce à un profil technique.</p>',360,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3230,NULL,NULL,NULL,NULL,NULL,'Puis-je connecter Patreon depuis n’importe quel site OMO ?','Oui, depuis un site autorisé : la connexion s’effectue dans une fenêtre dédiée, puis la page d’origine se met à jour.',NULL,NULL,'<ol><li>Depuis votre profil sur le site OMO que vous utilisez, lancez la connexion à Patreon.</li><li>Terminez l’autorisation dans la fenêtre de connexion qui s’ouvre.</li><li>Revenez à la page d’origine : elle est actualisée après la liaison du compte.</li></ol><p>La fenêtre peut utiliser un autre domaine que la page de départ. Si elle ne s’ouvre pas, vérifiez le blocage des fenêtres surgissantes et relancez la connexion depuis votre profil.</p>',370,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3231,NULL,NULL,NULL,NULL,NULL,'Que peut-on proposer comme modification depuis un point de PV ?','Un point de PV peut préparer plusieurs types de changements, qui ne prennent effet qu’après leur validation.',NULL,NULL,'<p>Dans un point de procès-verbal, ajoutez une modification différée, puis choisissez l’objet concerné, l’action à effectuer et le contexte. Selon vos droits collectifs, vous pouvez notamment proposer de créer, modifier ou supprimer une règle, un élément de structure, un projet, un indicateur ou une tâche récurrente.</p><p>Relisez la proposition dans le PV : préparer une modification ne l’applique pas immédiatement.</p>',380,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3232,NULL,NULL,NULL,NULL,NULL,'Peut-on proposer un changement d’indicateur ou de tâche récurrente dans un PV ?','Oui. Leur création, modification ou suppression peut être préparée dans un PV si les droits collectifs le permettent.',NULL,NULL,'<ol><li>Ouvrez le point de PV concerné et ajoutez une modification différée.</li><li>Choisissez <strong>Indicateur</strong> ou <strong>Tâche récurrente</strong>, puis l’action souhaitée.</li><li>Renseignez les champs dans l’éditeur proposé, relisez le résultat et enregistrez le point.</li></ol><p>Le changement n’est appliqué qu’après validation. Pour un indicateur issu d’un tableau, la modification porte sur une seule colonne de valeurs.</p>',390,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3233,NULL,NULL,NULL,NULL,NULL,'Comment proposer un projet pendant la rédaction d’un PV ?','Ajoutez une modification différée de type Projet au point de PV, puis choisissez la création, la modification ou la suppression.',NULL,NULL,'<ol><li>Dans le point de PV, ouvrez l’ajout de modification.</li><li>Sélectionnez <strong>Projet</strong>, l’action voulue et le contexte concerné.</li><li>Complétez ou relisez le formulaire du projet, puis enregistrez le point.</li></ol><p>L’option <strong>Proposer un projet</strong> apparaît lorsque vous disposez du droit collectif correspondant. Le projet proposé ne change réellement qu’après validation.</p>',400,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3234,NULL,NULL,NULL,NULL,NULL,'Puis-je proposer une règle pour un autre espace ?','Oui, si le contexte et vos droits collectifs permettent de proposer cette règle dans l’espace choisi.',NULL,NULL,'<p>Lors de l’ajout d’une modification de règle depuis un PV ou une décision, choisissez d’abord le contexte visé, puis l’action à proposer. Le formulaire adapte les choix disponibles à ce contexte et aux droits collectifs correspondants.</p><p>Avant d’enregistrer, vérifiez bien le nom de l’espace ou de l’organisation cible : c’est là que la règle sera créée ou modifiée après validation.</p>',410,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3235,NULL,NULL,NULL,NULL,NULL,'Comment revoir ou corriger une proposition faite dans un PV ?','Rouvrez la modification différée du point de PV, vérifiez son contexte et ajustez-la avant validation.',NULL,NULL,'<ol><li>Ouvrez le point de PV contenant la proposition.</li><li>Dépliez ses modifications différées pour retrouver l’objet, l’action et le contexte concernés.</li><li>Modifiez les champs nécessaires et enregistrez le point.</li></ol><p>Les vues de détail montrent les créations, suppressions et comparaisons avant/après. Servez-vous-en pour contrôler le résultat envisagé avant que la décision ne soit validée.</p>',420,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3236,NULL,NULL,NULL,NULL,NULL,'Comment savoir si une modification décidée a réellement été appliquée ?','Consultez le résultat de chaque modification : il indique si elle est appliquée, en attente, non retenue ou en échec.',NULL,NULL,'<p>Après la décision, ouvrez son détail et regardez le message affiché pour chaque modification. Une proposition préparée ne signifie pas à elle seule que le changement a été exécuté.</p><p>Le résultat distingue les modifications <strong>appliquées</strong>, celles qui sont <strong>en attente</strong>, celles qui n’ont <strong>pas été retenues</strong> et les éventuels <strong>échecs</strong>. En cas d’échec, vérifiez le contexte et les droits avant une nouvelle action.</p>',430,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3237,NULL,NULL,NULL,NULL,NULL,'Une option de scrutin peut-elle contenir plusieurs modifications ?','Oui. Une proposition de scrutin regroupe un titre, une description et autant de modifications nécessaires.',NULL,NULL,'<p>Dans le scrutin, créez une proposition, renseignez son titre et sa description, puis ajoutez les modifications une par une. Vous pouvez ainsi présenter un ensemble cohérent de changements dans une seule option.</p><p>Relisez toutes les modifications avant l’enregistrement : elles appartiennent à la même proposition et leur application dépendra du résultat du scrutin.</p>',440,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3238,NULL,NULL,NULL,NULL,NULL,'Quelles modifications d’un scrutin sont appliquées à la clôture ?','Seules les modifications de la proposition retenue peuvent être appliquées à la clôture ; les autres ne le sont pas.',NULL,NULL,'<p>À la clôture du scrutin, ouvrez le détail du résultat. La proposition retenue détermine les modifications à appliquer. Celles des propositions non retenues restent sans effet.</p><p>Contrôlez le statut de chaque modification : une modification retenue peut encore être en attente ou avoir échoué, ce que son message de résultat indique séparément.</p>',450,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3239,NULL,NULL,NULL,NULL,NULL,'L’IA peut-elle résumer une proposition de décision ?','Oui. Dans une décision hors réorganisation, elle peut préparer un texte que vous relisez et modifiez avant enregistrement.',NULL,NULL,'<ol><li>Préparez d’abord les modifications de votre proposition de décision.</li><li>Utilisez l’action de rédaction par l’IA pour obtenir une description ou un résumé.</li><li>Relisez le brouillon, corrigez-le si nécessaire et enregistrez votre proposition.</li></ol><p>Le texte est rédigé dans la langue de votre interface. Il présente les modifications comme des actions proposées, et non comme des changements déjà réalisés.</p>',460,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3240,NULL,NULL,NULL,NULL,NULL,'À quoi sert le champ « Intention et contexte » d’une proposition ?','Ce champ facultatif explique pourquoi vous proposez une décision et donne du contexte à sa rédaction.',NULL,NULL,'<p>Dans une décision hors réorganisation, affichez le champ <strong>Intention et contexte</strong> si vous souhaitez préciser l’objectif, le problème à résoudre ou les éléments utiles à la discussion.</p><p>Vous pouvez ensuite demander à l’IA de préparer une description à partir des modifications déjà saisies, puis reprendre manuellement le texte avant l’enregistrement. Le champ reste facultatif.</p>',470,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3241,NULL,NULL,NULL,NULL,NULL,'Pourquoi les boutons de vote ne sont-ils pas encore visibles ?','Pendant l’élaboration d’une décision, le vote n’est pas encore ouvert ; seules les contributions autorisées sont disponibles.',NULL,NULL,'<p>Vérifiez la phase et la date de début du vote affichées pour la décision. Tant qu’elle est en <strong>élaboration</strong>, les choix et boutons de vote simple ou de consentement ne s’affichent pas.</p><p>Vous pouvez néanmoins contribuer si vos droits le permettent. Les commandes de vote apparaissent lorsque la phase de vote commence effectivement ; un statut incohérent ne permet pas de l’ouvrir en avance.</p>',480,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3242,NULL,NULL,NULL,NULL,NULL,'Quels documents peut-on enregistrer comme modèles ?','Les documents HTML, liens, PV, fichiers, outils collaboratifs et dossiers peuvent servir de modèles.',NULL,NULL,'<p>Dans Documents, ouvrez la fiche d’un document compatible et ajoutez-le à la liste des modèles. Cela concerne notamment un document HTML, un lien externe, un PV, un fichier tel qu’un ODT, un Etherpad ou EtherCalc, ainsi qu’un dossier.</p><p>Une étoile signale les modèles. Ils sont regroupés par espace et identifiés par leur icône de type pour faciliter leur choix.</p>',490,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3243,NULL,NULL,NULL,NULL,NULL,'Comment créer un document à partir d’un modèle ?','Dans Documents, utilisez la flèche à côté de Nouveau et choisissez un modèle compatible avec le contexte courant.',NULL,NULL,'<ol><li>Ouvrez Documents dans le contexte où vous voulez placer la nouvelle copie.</li><li>Cliquez sur la flèche à côté de <strong>Nouveau</strong>.</li><li>Choisissez le modèle souhaité parmi ceux proposés, puis vérifiez le duplicata créé.</li></ol><p>La copie est visible dans le contexte courant. Les réunions et les projets proposent également des modèles compatibles avec leur type et leur portée.</p>',500,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3244,NULL,NULL,NULL,NULL,NULL,'La copie d’un modèle dépend-elle encore du document d’origine ?','Non pour les fichiers et outils collaboratifs : leur contenu est copié dans une ressource indépendante.',NULL,NULL,'<p>Quand vous créez une copie depuis un modèle, OMO duplique le document. Pour un fichier ou un outil collaboratif, le contenu est recopié dans une ressource indépendante : vous pouvez donc travailler sur la copie sans modifier la ressource du modèle.</p><p>Si le modèle est un dossier, toute son arborescence est reprise. Vérifiez ensuite les éléments copiés dans le contexte de destination.</p>',510,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3245,NULL,NULL,NULL,NULL,NULL,'Que retrouve-t-on après un export puis un import OMO 2 ?','Les suivis et équipes de projets, validations de tâches, exécutions de processus et types de points de PV sont préservés.',NULL,NULL,'<p>L’export OMO 2 conserve les informations de suivi des projets et leurs équipes, les validations des tâches récurrentes, les exécutions de processus et les types des points de procès-verbal. Ces éléments sont restaurés à l’import.</p><p>Avant d’exporter, lisez l’avertissement de la fenêtre d’export : les fichiers téléversés et les documents liés à des services externes ne sont pas inclus. Prévoyez leur conservation séparément si vous en avez besoin.</p>',520,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3246,NULL,NULL,NULL,NULL,NULL,'Puis-je partager mon organisation comme modèle public ?','Oui, si elle possède une structure. Le modèle conserve les réglages utiles, sans publier les membres ni l’historique privé.',NULL,NULL,'<p>Une organisation structurée peut être partagée comme modèle public. Depuis ses options de partage, vérifiez les informations présentées avant de confirmer. Le modèle pourra notamment servir de point de départ pour créer un nouvel espace.</p><p>Il conserve les applications activées et leurs réglages, mais exclut les membres, l’historique, les rendez-vous, les valeurs d’indicateurs et l’historique budgétaire. Vérifiez malgré tout les textes et documents que vous choisissez de partager.</p>',530,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3247,NULL,NULL,NULL,NULL,NULL,'Que puis-je personnaliser en créant une organisation depuis un modèle ?','Vous pouvez ajuster son nom, ses images, sa couleur, sa position et plusieurs réglages avant la création.',NULL,NULL,'<ol><li>Choisissez le modèle d’organisation que vous souhaitez utiliser.</li><li>Dans le formulaire de création, personnalisez le nom, les images, la couleur, la position et les réglages proposés.</li><li>Vérifiez les choix avant de créer l’organisation.</li></ol><p>Le modèle sert de point de départ ; vous pourrez ensuite compléter votre organisation et définir ses autres informations, notamment ses routes.</p>',540,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3248,NULL,NULL,NULL,NULL,NULL,'Peut-on changer les mots « espace », « cercle », « rôle » ou « groupe » ?','Oui. Le vocabulaire de ces éléments peut être adapté à chaque organisation.',NULL,NULL,'<p>Dans les réglages de l’organisation, ouvrez la personnalisation du vocabulaire et choisissez les termes qui correspondent à votre fonctionnement pour les espaces, cercles, rôles ou groupes.</p><p>Cette personnalisation modifie les libellés affichés dans l’organisation, pas la nature des objets ni les droits associés. Chaque organisation peut employer son propre vocabulaire.</p>',550,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3249,NULL,NULL,NULL,NULL,NULL,'Comment suivre mon calendrier OMO dans Google Calendar ?','Copiez le lien ICS proposé dans le menu Connecter du calendrier, puis ajoutez-le à Google Calendar.',NULL,NULL,'<ol><li>Dans OMO, ouvrez Calendrier et choisissez le contexte que vous souhaitez suivre.</li><li>Ouvrez le menu <strong>Connecter</strong> et copiez le lien ICS proposé pour cette portée.</li><li>Dans Google Calendar, ajoutez un calendrier à partir de son URL et collez ce lien.</li></ol><p>Le panneau d’aide du menu Connecter détaille les étapes. Le lien correspond au contexte choisi ; une actualisation externe peut prendre du temps selon Google Calendar.</p>',560,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3250,NULL,NULL,NULL,NULL,NULL,'Documents et Calendrier retrouvent-ils ma dernière vue ?','Oui. Leur portée et leur période sont mémorisées dans le navigateur pour retrouver directement la vue utilisée.',NULL,NULL,'<p>Après avoir choisi une portée dans Documents ou une portée et une période dans Calendrier, revenez plus tard à l’application : la préférence enregistrée dans ce navigateur est reprise dès l’ouverture.</p><p>Un lien direct vers une autre vue garde la priorité. Les préférences sont locales au navigateur ; elles peuvent donc différer sur un autre appareil ou après effacement des données du site.</p>',570,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3251,NULL,NULL,NULL,NULL,NULL,'Comment limiter le tableau de pilotage à ce qui me concerne ?','Réglez la portée des modules Projets, Indicateurs ou Tâches récurrentes sur Moi ou Mes espaces.',NULL,NULL,'<p>Dans le tableau de pilotage, ouvrez la configuration du module concerné. Pour les projets, les indicateurs et les tâches récurrentes, choisissez <strong>Moi</strong> pour privilégier les éléments qui vous sont attribués, <strong>Mes espaces</strong> pour élargir la vue à vos espaces, ou <strong>Tous</strong> pour la portée la plus large.</p><p>Les éléments effectivement visibles restent soumis à vos droits d’accès. Dans l’application Projets, le filtre distinct <strong>Suivi</strong> montre les projets suivis par au moins une personne ; il ne signifie pas « suivis par moi ».</p>',580,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0),
(3252,NULL,NULL,NULL,NULL,NULL,'Où sont passés les onglets sur mobile ?','Sur petit écran, les onglets sont regroupés dans un menu déroulant qui affiche le nom complet de l’onglet actif.',NULL,NULL,'<p>Si les onglets côte à côte ne sont plus visibles sur votre téléphone, touchez le menu qui porte le nom de l’onglet actif, puis sélectionnez celui que vous voulez ouvrir. Les mêmes contenus et actions restent disponibles.</p><p>Sur un écran plus large, les onglets réapparaissent côte à côte. Dans la navigation mobile, le tableau de bord porte désormais le nom <strong>Pilotage</strong>.</p>',590,1,0,0,0,0,0,'2026-09-28 21:15:49','2026-09-28 21:15:49','2026-09-28 16:40:04','2026-09-28 16:40:04',NULL,NULL,NULL,NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `faq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faq_choice`
--

DROP TABLE IF EXISTS `faq_choice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faq_choice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDfaq` int(11) DEFAULT NULL,
  `label` mediumtext DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faq_choice`
--

LOCK TABLES `faq_choice` WRITE;
/*!40000 ALTER TABLE `faq_choice` DISABLE KEYS */;
/*!40000 ALTER TABLE `faq_choice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `history`
--

DROP TABLE IF EXISTS `history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) DEFAULT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `content` mediumtext NOT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_history_organization` (`IDorganization`),
  KEY `idx_history_user` (`IDuser`),
  KEY `idx_history_action` (`action`),
  KEY `idx_history_datecreation` (`datecreation`),
  KEY `idx_history_target` (`IDorganization`,`target_type`,`target_id`,`datecreation`),
  FULLTEXT KEY `ft_history_content` (`content`),
  CONSTRAINT `fk_history_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=285 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `history`
--

LOCK TABLES `history` WRITE;
/*!40000 ALTER TABLE `history` DISABLE KEYS */;
/*!40000 ALTER TABLE `history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holon`
--

DROP TABLE IF EXISTS `holon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `holon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `nomcomplet` varchar(255) DEFAULT NULL,
  `color` varchar(10) DEFAULT NULL COMMENT 'Couleur du noeud, qui peut être héritée du template.',
  `color_unassigned` varchar(10) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL COMMENT 'Illustration carre du holon ou du template.',
  `IDholon_org` int(11) DEFAULT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Est supprimé ? Peut éventuellement être sorti d''une corbeille ou consulté pour archivage, mais sinon n''est plus utilisé',
  `visible` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Est visible? Ou plutôt caché pour pouvoir être réaffiché plus tard ou pour servir de template invisible',
  `mandatory` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Est obligatoire, et est ajouté à tout cercle nouvellement créé',
  `lockedname` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Le nom est impose par le template pour toutes ses instances',
  `lockedicon` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'L''icone est imposee par le template pour toutes ses instances',
  `unique` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Est unique dans le cercle de rattachement, groupes compris',
  `link` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Se comporte comme un lien, en étant représenté également dans le cercle englobant',
  `adminparent` tinyint(1) NOT NULL DEFAULT 0,
  `admin_min` int(11) DEFAULT NULL,
  `lockedadminmin` tinyint(1) NOT NULL DEFAULT 0,
  `adminminoverride` tinyint(1) NOT NULL DEFAULT 0,
  `admin_max` int(11) DEFAULT NULL,
  `lockedadminmax` tinyint(1) NOT NULL DEFAULT 0,
  `adminmaxoverride` tinyint(1) NOT NULL DEFAULT 0,
  `templatename` varchar(150) DEFAULT NULL,
  `IDtypeholon` int(11) DEFAULT NULL,
  `IDholon_parent` int(11) DEFAULT NULL,
  `IDholon_template` int(11) DEFAULT NULL,
  `accesskey` varchar(200) DEFAULT NULL,
  `time_budget_hours` decimal(12,2) DEFAULT NULL,
  `time_budget_recurrence` varchar(10) DEFAULT NULL,
  `money_budget` decimal(12,2) DEFAULT NULL,
  `money_budget_recurrence` varchar(10) DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_holon_organization` (`IDorganization`),
  KEY `idx_holon_root` (`IDholon_org`),
  KEY `idx_holon_parent` (`IDholon_parent`),
  KEY `idx_holon_template` (`IDholon_template`),
  CONSTRAINT `fk_holon_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_holon_parent` FOREIGN KEY (`IDholon_parent`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_holon_root` FOREIGN KEY (`IDholon_org`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_holon_template` FOREIGN KEY (`IDholon_template`) REFERENCES `holon` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5772 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holon`
--

LOCK TABLES `holon` WRITE;
/*!40000 ALTER TABLE `holon` DISABLE KEYS */;
INSERT INTO `holon` VALUES
(1,1,'Org1',NULL,NULL,NULL,NULL,NULL,1,'2026-09-28 19:22:04',NULL,1,1,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(2,2,'Org2',NULL,NULL,NULL,NULL,NULL,3,'2026-09-28 19:22:04',NULL,1,1,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `holon` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holon_permission`
--

DROP TABLE IF EXISTS `holon_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `holon_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDholon` int(11) NOT NULL,
  `IDpermission` int(11) NOT NULL,
  `member_type` varchar(20) NOT NULL DEFAULT 'member',
  `is_extended` tinyint(1) NOT NULL DEFAULT 0,
  `range` varchar(40) NOT NULL DEFAULT 'self',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_holon_permission_profile_range` (`IDholon`,`IDpermission`,`member_type`,`range`),
  KEY `idx_holon_permission_permission` (`IDpermission`),
  KEY `idx_holon_permission_range` (`range`)
) ENGINE=InnoDB AUTO_INCREMENT=4681 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holon_permission`
--

LOCK TABLES `holon_permission` WRITE;
/*!40000 ALTER TABLE `holon_permission` DISABLE KEYS */;
/*!40000 ALTER TABLE `holon_permission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holon_share_link`
--

DROP TABLE IF EXISTS `holon_share_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `holon_share_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDholon` int(11) NOT NULL,
  `IDuser` int(11) NOT NULL,
  `label` varchar(150) DEFAULT NULL,
  `token` varchar(80) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `allow_structure` tinyint(1) NOT NULL DEFAULT 1,
  `allow_people` tinyint(1) NOT NULL DEFAULT 0,
  `allow_people_detail` tinyint(1) NOT NULL DEFAULT 0,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateexpiration` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_holon_share_link_token` (`token`),
  KEY `idx_holon_share_link_org_holon` (`IDorganization`,`IDholon`),
  KEY `idx_holon_share_link_user` (`IDuser`),
  KEY `idx_holon_share_link_active` (`active`),
  KEY `idx_holon_share_link_expiration` (`dateexpiration`),
  KEY `fk_holon_share_link_holon` (`IDholon`),
  CONSTRAINT `fk_holon_share_link_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_holon_share_link_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holon_share_link`
--

LOCK TABLES `holon_share_link` WRITE;
/*!40000 ALTER TABLE `holon_share_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `holon_share_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holonproperty`
--

DROP TABLE IF EXISTS `holonproperty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `holonproperty` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDholon` int(11) NOT NULL,
  `IDproperty` int(11) NOT NULL,
  `value` mediumtext DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `datemodification` datetime DEFAULT NULL,
  `IDusermodification` int(11) DEFAULT NULL,
  `mandatory` tinyint(1) NOT NULL DEFAULT 0,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_holonproperty_holon` (`IDholon`),
  KEY `idx_holonproperty_property` (`IDproperty`),
  KEY `idx_holonproperty_user_modification` (`IDusermodification`),
  CONSTRAINT `fk_holonproperty_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_holonproperty_property` FOREIGN KEY (`IDproperty`) REFERENCES `property` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_holonproperty_user_modification` FOREIGN KEY (`IDusermodification`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7945 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holonproperty`
--

LOCK TABLES `holonproperty` WRITE;
/*!40000 ALTER TABLE `holonproperty` DISABLE KEYS */;
/*!40000 ALTER TABLE `holonproperty` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `homework`
--

DROP TABLE IF EXISTS `homework`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `homework` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `detail` text DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateupdate` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_homework_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `homework`
--

LOCK TABLES `homework` WRITE;
/*!40000 ALTER TABLE `homework` DISABLE KEYS */;
/*!40000 ALTER TABLE `homework` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invitation`
--

DROP TABLE IF EXISTS `invitation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invitation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDuser` int(11) NOT NULL,
  `IDuser_sender` int(11) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `token` varchar(64) NOT NULL,
  `request_origin` varchar(20) NOT NULL DEFAULT 'admin',
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `parameters` mediumtext DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateexpiration` datetime DEFAULT NULL,
  `dateresponse` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_invitation_token` (`token`),
  KEY `idx_invitation_org_user` (`IDorganization`,`IDuser`),
  KEY `idx_invitation_status` (`status`),
  KEY `idx_invitation_active` (`active`),
  KEY `idx_invitation_expiration` (`dateexpiration`),
  KEY `idx_invitation_request_origin` (`request_origin`),
  CONSTRAINT `fk_invitation_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=333 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invitation`
--

LOCK TABLES `invitation` WRITE;
/*!40000 ALTER TABLE `invitation` DISABLE KEYS */;
/*!40000 ALTER TABLE `invitation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `contenttype` varchar(255) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `IDtype` int(11) NOT NULL,
  `IDstorage` int(11) NOT NULL,
  `accesskey` varchar(255) NOT NULL,
  `IDdocument` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_media_document` (`IDdocument`),
  CONSTRAINT `fk_media_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meeting_booking`
--

DROP TABLE IF EXISTS `meeting_booking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_booking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `IDexternalcalendar` int(11) DEFAULT NULL,
  `token` char(64) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `guest_name` varchar(190) NOT NULL,
  `guest_email` varchar(254) NOT NULL,
  `reason` text NOT NULL,
  `meeting_method` text DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `resource_url` varchar(2100) NOT NULL,
  `calendar_data` mediumtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `email_sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_token` (`token`),
  KEY `meeting_busy` (`IDuser`,`status`,`start_at`,`end_at`),
  KEY `IDexternalcalendar` (`IDexternalcalendar`),
  CONSTRAINT `meeting_booking_ibfk_1` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_booking_ibfk_2` FOREIGN KEY (`IDexternalcalendar`) REFERENCES `external_calendar` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_booking`
--

LOCK TABLES `meeting_booking` WRITE;
/*!40000 ALTER TABLE `meeting_booking` DISABLE KEYS */;
/*!40000 ALTER TABLE `meeting_booking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meeting_profile`
--

DROP TABLE IF EXISTS `meeting_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `IDexternalcalendar` int(11) DEFAULT NULL,
  `slug` varchar(48) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `timezone` varchar(64) NOT NULL DEFAULT 'Europe/Zurich',
  `max_duration_minutes` smallint unsigned NOT NULL DEFAULT 60,
  `meeting_methods` text DEFAULT NULL,
  `weekly_hours` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_user` (`IDuser`),
  UNIQUE KEY `meeting_slug` (`slug`),
  KEY `IDexternalcalendar` (`IDexternalcalendar`),
  CONSTRAINT `meeting_profile_ibfk_1` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_profile_ibfk_2` FOREIGN KEY (`IDexternalcalendar`) REFERENCES `external_calendar` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_profile`
--

LOCK TABLES `meeting_profile` WRITE;
/*!40000 ALTER TABLE `meeting_profile` DISABLE KEYS */;
/*!40000 ALTER TABLE `meeting_profile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mission`
--

DROP TABLE IF EXISTS `mission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `resume` text NOT NULL,
  `video` varchar(1000) DEFAULT NULL,
  `html` text DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateupdate` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Missions d''un parcours de formation';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mission`
--

LOCK TABLES `mission` WRITE;
/*!40000 ALTER TABLE `mission` DISABLE KEYS */;
/*!40000 ALTER TABLE `mission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mission_dependencies`
--

DROP TABLE IF EXISTS `mission_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mission_dependencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDmission_parent` int(11) NOT NULL,
  `IDmission_child` int(11) NOT NULL,
  `IDparcours` int(11) NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mission_dependencies`
--

LOCK TABLES `mission_dependencies` WRITE;
/*!40000 ALTER TABLE `mission_dependencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `mission_dependencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mission_faq`
--

DROP TABLE IF EXISTS `mission_faq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mission_faq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDmission` int(11) DEFAULT NULL,
  `IDfaq` int(11) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mission_faq`
--

LOCK TABLES `mission_faq` WRITE;
/*!40000 ALTER TABLE `mission_faq` DISABLE KEYS */;
/*!40000 ALTER TABLE `mission_faq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mission_homework`
--

DROP TABLE IF EXISTS `mission_homework`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mission_homework` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDmission` int(11) NOT NULL,
  `IDhomework` int(11) NOT NULL,
  `position` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mission_homework` (`IDmission`,`IDhomework`),
  KEY `idx_mission_homework_mission` (`IDmission`),
  KEY `idx_mission_homework_homework` (`IDhomework`),
  KEY `idx_mission_homework_position` (`IDmission`,`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mission_homework`
--

LOCK TABLES `mission_homework` WRITE;
/*!40000 ALTER TABLE `mission_homework` DISABLE KEYS */;
/*!40000 ALTER TABLE `mission_homework` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mission_question`
--

DROP TABLE IF EXISTS `mission_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mission_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDmission` int(11) DEFAULT NULL,
  `IDquestion` int(11) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mission_question` (`IDmission`,`IDquestion`),
  KEY `idx_mission_question_position` (`IDmission`,`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mission_question`
--

LOCK TABLES `mission_question` WRITE;
/*!40000 ALTER TABLE `mission_question` DISABLE KEYS */;
/*!40000 ALTER TABLE `mission_question` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `IDorganization` int(11) NOT NULL,
  `event_key` varchar(80) NOT NULL,
  `source_key` varchar(190) NOT NULL,
  `dedupe_key` varchar(190) DEFAULT NULL,
  `title` varchar(250) NOT NULL,
  `body` text DEFAULT NULL,
  `url` varchar(1000) DEFAULT NULL,
  `open_token` char(64) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_notification_user_source` (`IDuser`,`source_key`),
  UNIQUE KEY `uniq_notification_open_token` (`open_token`),
  KEY `idx_notification_inbox` (`IDuser`,`IDorganization`,`read_at`,`created_at`),
  KEY `fk_notification_organization` (`IDorganization`),
  KEY `idx_notification_unread_dedupe` (`IDuser`,`IDorganization`,`dedupe_key`,`read_at`),
  CONSTRAINT `fk_notification_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=123 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification`
--

LOCK TABLES `notification` WRITE;
/*!40000 ALTER TABLE `notification` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_preference`
--

DROP TABLE IF EXISTS `notification_preference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_preference` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `IDorganization` int(11) NOT NULL,
  `event_key` varchar(80) NOT NULL,
  `channel_push` tinyint(1) NOT NULL DEFAULT 0,
  `channel_telegram` tinyint(1) NOT NULL DEFAULT 0,
  `channel_email` tinyint(1) NOT NULL DEFAULT 0,
  `parameters` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_notification_preference_scope` (`IDuser`,`IDorganization`,`event_key`),
  KEY `idx_notification_preference_organization` (`IDorganization`),
  CONSTRAINT `fk_notification_preference_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notification_preference_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_preference`
--

LOCK TABLES `notification_preference` WRITE;
/*!40000 ALTER TABLE `notification_preference` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_preference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_push_subscription`
--

DROP TABLE IF EXISTS `notification_push_subscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_push_subscription` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `endpoint_hash` char(64) NOT NULL,
  `endpoint` text NOT NULL,
  `p256dh_key` varchar(200) NOT NULL,
  `auth_key` varchar(100) NOT NULL,
  `user_agent` varchar(1000) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `last_error` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_seen_at` datetime DEFAULT NULL,
  `last_sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_notification_push_endpoint_hash` (`endpoint_hash`),
  KEY `idx_notification_push_user_active` (`IDuser`,`active`),
  CONSTRAINT `fk_notification_push_subscription_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_push_subscription`
--

LOCK TABLES `notification_push_subscription` WRITE;
/*!40000 ALTER TABLE `notification_push_subscription` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_push_subscription` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `object_visibility`
--

DROP TABLE IF EXISTS `object_visibility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `object_visibility` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` int(11) NOT NULL DEFAULT 1,
  `object_type` varchar(60) NOT NULL,
  `object_id` int(11) NOT NULL,
  `IDorganization` int(11) DEFAULT NULL,
  `visibility_type` varchar(30) NOT NULL DEFAULT 'organization',
  `IDholon` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `datecreation` datetime DEFAULT NULL,
  `datemodification` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_object_visibility_object` (`object_type`,`object_id`,`active`),
  KEY `idx_object_visibility_org` (`IDorganization`,`active`),
  KEY `idx_object_visibility_holon` (`IDholon`),
  KEY `idx_object_visibility_type` (`visibility_type`),
  CONSTRAINT `fk_object_visibility_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_object_visibility_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24376 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `object_visibility`
--

LOCK TABLES `object_visibility` WRITE;
/*!40000 ALTER TABLE `object_visibility` DISABLE KEYS */;
/*!40000 ALTER TABLE `object_visibility` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organization`
--

DROP TABLE IF EXISTS `organization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `organization` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `shortname` varchar(50) DEFAULT NULL,
  `domain` varchar(100) DEFAULT NULL,
  `logo` varchar(100) DEFAULT NULL,
  `banner` varchar(100) DEFAULT NULL,
  `color` varchar(10) DEFAULT NULL,
  `latlong` varchar(100) DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `interface_level` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `isModel` tinyint(1) NOT NULL DEFAULT 0,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization`
--

LOCK TABLES `organization` WRITE;
/*!40000 ALTER TABLE `organization` DISABLE KEYS */;
INSERT INTO `organization` VALUES
(1,'Org1','org1','org1.opengov.tools',NULL,NULL,'#1769aa',NULL,NULL,1,0,'2026-09-28 19:22:04'),
(2,'Org2','org2','org2.opengov.tools',NULL,NULL,'#984ea2',NULL,NULL,1,0,'2026-09-28 19:22:04');
/*!40000 ALTER TABLE `organization` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organization_application`
--

DROP TABLE IF EXISTS `organization_application`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `organization_application` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDapplication` int(11) NOT NULL,
  `position` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `parameters` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_organization_application` (`IDorganization`,`IDapplication`),
  KEY `idx_organization_application_organization` (`IDorganization`),
  KEY `idx_organization_application_application` (`IDapplication`),
  CONSTRAINT `fk_organization_application_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=970 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization_application`
--

LOCK TABLES `organization_application` WRITE;
/*!40000 ALTER TABLE `organization_application` DISABLE KEYS */;
INSERT INTO `organization_application` VALUES
(940,1,1,10,1,NULL),
(941,1,2,20,1,NULL),
(942,1,3,30,1,NULL),
(943,1,4,40,1,NULL),
(944,1,5,50,1,NULL),
(945,1,6,60,1,NULL),
(946,1,7,8,1,NULL),
(947,1,8,9,1,NULL),
(948,1,9,65,1,NULL),
(949,1,10,45,1,NULL),
(950,1,12,55,1,NULL),
(955,2,1,10,1,NULL),
(956,2,2,20,1,NULL),
(957,2,3,30,1,NULL),
(958,2,4,40,1,NULL),
(959,2,5,50,1,NULL),
(960,2,6,60,1,NULL),
(961,2,7,8,1,NULL),
(962,2,8,9,1,NULL),
(963,2,9,65,1,NULL),
(964,2,10,45,1,NULL),
(965,2,12,55,1,NULL);
/*!40000 ALTER TABLE `organization_application` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organization_parcours`
--

DROP TABLE IF EXISTS `organization_parcours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `organization_parcours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDparcours` int(11) NOT NULL,
  `position` int(11) DEFAULT NULL,
  `everybody` tinyint(1) NOT NULL DEFAULT 1,
  `anonymous` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_organization_parcours_organization` (`IDorganization`),
  CONSTRAINT `fk_organization_parcours_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization_parcours`
--

LOCK TABLES `organization_parcours` WRITE;
/*!40000 ALTER TABLE `organization_parcours` DISABLE KEYS */;
/*!40000 ALTER TABLE `organization_parcours` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organizational_maturity_assessment`
--

DROP TABLE IF EXISTS `organizational_maturity_assessment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `organizational_maturity_assessment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) DEFAULT NULL,
  `IDorganization` int(11) DEFAULT NULL,
  `IDinvitation` int(11) DEFAULT NULL,
  `public_token` char(48) NOT NULL,
  `private_token_hash` char(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_organizational_maturity_assessment_public_token` (`public_token`),
  UNIQUE KEY `uniq_organizational_maturity_assessment_private_token_hash` (`private_token_hash`),
  UNIQUE KEY `uniq_organizational_maturity_assessment_invitation` (`IDinvitation`),
  KEY `idx_organizational_maturity_assessment_organization` (`IDorganization`,`updated_at`),
  KEY `idx_organizational_maturity_assessment_user` (`IDuser`,`updated_at`),
  CONSTRAINT `fk_organizational_maturity_assessment_invitation` FOREIGN KEY (`IDinvitation`) REFERENCES `organizational_maturity_invitation` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_organizational_maturity_assessment_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_organizational_maturity_assessment_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organizational_maturity_assessment`
--

LOCK TABLES `organizational_maturity_assessment` WRITE;
/*!40000 ALTER TABLE `organizational_maturity_assessment` DISABLE KEYS */;
/*!40000 ALTER TABLE `organizational_maturity_assessment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organizational_maturity_assessment_response`
--

DROP TABLE IF EXISTS `organizational_maturity_assessment_response`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `organizational_maturity_assessment_response` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDassessment` int(11) NOT NULL,
  `principle_number` tinyint(3) unsigned NOT NULL,
  `affinity_score` tinyint(3) unsigned NOT NULL,
  `today_score` tinyint(3) unsigned NOT NULL,
  `tomorrow_score` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_organizational_maturity_assessment_response_principle` (`IDassessment`,`principle_number`),
  KEY `idx_organizational_maturity_response_principle_today` (`principle_number`,`today_score`),
  KEY `idx_organizational_maturity_response_principle_tomorrow` (`principle_number`,`tomorrow_score`),
  CONSTRAINT `fk_organizational_maturity_assessment_response_assessment` FOREIGN KEY (`IDassessment`) REFERENCES `organizational_maturity_assessment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=291 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organizational_maturity_assessment_response`
--

LOCK TABLES `organizational_maturity_assessment_response` WRITE;
/*!40000 ALTER TABLE `organizational_maturity_assessment_response` DISABLE KEYS */;
/*!40000 ALTER TABLE `organizational_maturity_assessment_response` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organizational_maturity_invitation`
--

DROP TABLE IF EXISTS `organizational_maturity_invitation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `organizational_maturity_invitation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `email` varchar(250) NOT NULL,
  `token` char(32) NOT NULL,
  `public_access` tinyint(1) NOT NULL DEFAULT 0,
  `access_code_hash` varchar(255) DEFAULT NULL,
  `access_code_expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_organizational_maturity_invitation_org_email` (`IDorganization`,`email`),
  UNIQUE KEY `uniq_organizational_maturity_invitation_token` (`token`),
  KEY `fk_organizational_maturity_invitation_user` (`IDuser`),
  CONSTRAINT `fk_organizational_maturity_invitation_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_organizational_maturity_invitation_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organizational_maturity_invitation`
--

LOCK TABLES `organizational_maturity_invitation` WRITE;
/*!40000 ALTER TABLE `organizational_maturity_invitation` DISABLE KEYS */;
/*!40000 ALTER TABLE `organizational_maturity_invitation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organizational_maturity_public_link`
--

DROP TABLE IF EXISTS `organizational_maturity_public_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `organizational_maturity_public_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `token` char(32) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_organizational_maturity_public_link_organization` (`IDorganization`),
  UNIQUE KEY `uniq_organizational_maturity_public_link_token` (`token`),
  CONSTRAINT `fk_organizational_maturity_public_link_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organizational_maturity_public_link`
--

LOCK TABLES `organizational_maturity_public_link` WRITE;
/*!40000 ALTER TABLE `organizational_maturity_public_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `organizational_maturity_public_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parameter`
--

DROP TABLE IF EXISTS `parameter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `parameter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` mediumtext NOT NULL,
  `type` varchar(30) NOT NULL,
  `format` varchar(255) DEFAULT NULL COMMENT 'Validation du format, par exemple avec une REGEXP',
  `value` mediumtext DEFAULT NULL,
  `typeobject` varchar(30) NOT NULL,
  `family` varchar(100) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parameter`
--

LOCK TABLES `parameter` WRITE;
/*!40000 ALTER TABLE `parameter` DISABLE KEYS */;
INSERT INTO `parameter` VALUES
(1,'basic','Paramètre basic','Exemple de paramètre basic de type texte','string',NULL,NULL,'dbObject\\user',NULL,1),
(2,'numeric','Paramètre numérique','Exemple de paramètre de type numérique','integer',NULL,'20','dbObject\\user',NULL,1),
(3,'check','Case à cocher','Exemple de paramètre de type case à cocher','checkbox',NULL,'1','dbObject\\user',NULL,1),
(4,'select','Select box','Exemple de paramètre de type select','select',NULL,'Valeur 1;Valeur 2;Valeur 3','dbObject\\user',NULL,1),
(5,'isAdmin','est administrateur','Donne des droits d\'administration sur l\'organisation','checkbox',NULL,'','dbObject\\user-organization',NULL,1),
(6,'select','Qualité retranscription','Défini comment chatGPT retranscrits les propos: plutôt fidèle au texte original, ou plutôt en réécrivant en tournure de phrases plus littéraire?','select',NULL,'Fidèle au texte original;Réécriture littéraire light;Réécriture littéraire avancée;Réécriture littéraire et formatage HTML','dbObject\\user','easymemo',1);
/*!40000 ALTER TABLE `parameter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parcours`
--

DROP TABLE IF EXISTS `parcours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `parcours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `IDorganization` int(11) DEFAULT NULL,
  `IDusercreation` int(11) DEFAULT NULL,
  `IDusermodification` int(11) DEFAULT NULL,
  `datecreation` datetime DEFAULT NULL,
  `datemodification` datetime DEFAULT NULL,
  `ispublic` tinyint(1) NOT NULL DEFAULT 0,
  `isbasic` tinyint(1) NOT NULL DEFAULT 0,
  `ispack` tinyint(1) NOT NULL DEFAULT 0,
  `isarchived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcours`
--

LOCK TABLES `parcours` WRITE;
/*!40000 ALTER TABLE `parcours` DISABLE KEYS */;
/*!40000 ALTER TABLE `parcours` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parcours_mission`
--

DROP TABLE IF EXISTS `parcours_mission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `parcours_mission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDparcours` int(11) NOT NULL,
  `IDmission` int(11) NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT 1,
  `branch` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcours_mission`
--

LOCK TABLES `parcours_mission` WRITE;
/*!40000 ALTER TABLE `parcours_mission` DISABLE KEYS */;
/*!40000 ALTER TABLE `parcours_mission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parcours_parcours`
--

DROP TABLE IF EXISTS `parcours_parcours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `parcours_parcours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDparcours_parent` int(11) NOT NULL,
  `IDparcours_child` int(11) NOT NULL,
  `position` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_parcours_pack_child` (`IDparcours_parent`,`IDparcours_child`),
  KEY `idx_parcours_pack_parent_position` (`IDparcours_parent`,`position`),
  KEY `idx_parcours_pack_child` (`IDparcours_child`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcours_parcours`
--

LOCK TABLES `parcours_parcours` WRITE;
/*!40000 ALTER TABLE `parcours_parcours` DISABLE KEYS */;
/*!40000 ALTER TABLE `parcours_parcours` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patreon_oauth_transaction`
--

DROP TABLE IF EXISTS `patreon_oauth_transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patreon_oauth_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `handoff_token_hash` char(64) DEFAULT NULL,
  `oauth_state_hash` char(64) DEFAULT NULL,
  `claim_token` char(64) DEFAULT NULL,
  `return_origin` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_patreon_oauth_handoff_hash` (`handoff_token_hash`),
  UNIQUE KEY `uniq_patreon_oauth_state_hash` (`oauth_state_hash`),
  KEY `idx_patreon_oauth_expiration` (`status`,`expires_at`),
  KEY `idx_patreon_oauth_user` (`IDuser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patreon_oauth_transaction`
--

LOCK TABLES `patreon_oauth_transaction` WRITE;
/*!40000 ALTER TABLE `patreon_oauth_transaction` DISABLE KEYS */;
/*!40000 ALTER TABLE `patreon_oauth_transaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permission`
--

DROP TABLE IF EXISTS `permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(190) NOT NULL,
  `title` varchar(190) NOT NULL,
  `description` text NOT NULL,
  `iscontextual` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_permission_key` (`permission_key`),
  KEY `idx_permission_title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permission`
--

LOCK TABLES `permission` WRITE;
/*!40000 ALTER TABLE `permission` DISABLE KEYS */;
INSERT INTO `permission` VALUES
(1,'CAN_ADD_MEMBER','Ajouter un membre','Autorise l ajout d un membre dans le contexte cible.',1,'2026-07-23 11:51:18','2026-07-23 11:51:18'),
(2,'CAN_ADD_ADMIN','Definir un admin de contexte','Autorise l attribution ou le retrait du statut admin dans le contexte cible.',1,'2026-07-23 11:51:18','2026-07-23 11:51:18'),
(3,'CAN_CREATE_DOCUMENT','Creer des fichiers','Autorise la creation de fichiers dans le contexte cible.',1,'2026-07-23 11:51:18','2026-07-23 11:51:18'),
(4,'CAN_CREATE_DECISION','Creer des prises de decision','Autorise la creation de prises de decision dans le contexte cible.',1,'2026-07-23 11:51:18','2026-07-23 11:51:18'),
(5,'CAN_CREATE_EVENT','Creer des dates','Autorise la creation de dates dans le contexte cible.',1,'2026-07-23 11:51:18','2026-07-23 11:51:18'),
(6,'CAN_DELETE_EVENT','Supprimer des dates','Autorise la suppression de dates dans le contexte cible.',1,'2026-07-23 11:51:18','2026-07-23 11:51:23'),
(7,'CAN_CLAIM_PV','Devenir secretaire de PV','Autorise a prendre le role de secretaire pendant une reunion associee a un PV.',1,'2026-07-23 11:51:18','2026-07-23 11:51:22'),
(8,'CAN_CREATE_FAQ','Creer des FAQ','Autorise la creation de FAQ dans le contexte cible.',1,'2026-07-23 11:51:18','2026-07-23 11:51:18'),
(9,'CAN_CREATE_PROJECT','Creer des projets','Autorise la creation de projets dans le contexte cible.',1,'2026-07-23 11:51:18','2026-07-23 11:51:24'),
(10,'CAN_CREATE_INDICATOR','Creer des indicateurs','Autorise la creation d indicateurs dans le contexte cible.',1,'2026-07-23 11:51:18','2026-07-23 11:51:24'),
(11,'CAN_ADD_APP','Gerer les applications','Autorise la gestion des applications actives et de leur ordre dans l organisation.',0,'2026-07-23 11:51:22','2026-07-23 11:51:22'),
(12,'CAN_EDIT_TEMPLATE_PROPERTIES','Modifier les proprietes de templates','Autorise la modification des proprietes definies par les templates dans le contexte cible.',1,'2026-07-27 12:00:00','2026-07-28 08:32:05'),
(13,'CAN_ADD_TEMPLATE_PROPERTIES','Ajouter des proprietes de templates','Autorise l ajout de proprietes definies par les templates dans le contexte cible.',1,'2026-07-27 12:00:00','2026-07-28 08:32:05'),
(14,'CAN_DELETE_TEMPLATE_PROPERTIES','Supprimer les proprietes de templates','Autorise le retrait des proprietes definies par les templates dans le contexte cible.',1,'2026-07-27 12:00:00','2026-07-28 08:32:05'),
(15,'CAN_EDIT_HOLON_PROPERTIES','Modifier les proprietes de holons','Autorise la modification des proprietes ajoutees directement a un holon dans le contexte cible.',1,'2026-07-27 12:00:00','2026-07-28 08:32:05'),
(16,'CAN_ADD_HOLON_PROPERTIES','Ajouter des proprietes de holons','Autorise l ajout de proprietes directement sur un holon dans le contexte cible.',1,'2026-07-27 12:00:00','2026-07-28 08:32:05'),
(17,'CAN_DELETE_HOLON_PROPERTIES','Supprimer les proprietes de holons','Autorise le retrait des proprietes ajoutees directement a un holon dans le contexte cible.',1,'2026-07-27 12:00:00','2026-07-28 08:32:05'),
(19,'CAN_ADD_HOLON','Ajouter un holon','Autorise l ajout d un holon dans le contexte cible.',1,'2026-08-20 06:52:15','2026-08-31 14:07:28'),
(20,'CAN_EDIT_HOLON','Modifier des holons','Autorise la modification de holons dans le contexte cible.',1,'2026-08-20 06:52:15','2026-08-31 14:07:28'),
(21,'CAN_DELETE_HOLON','Supprimer des holons','Autorise la suppression de holons dans le contexte cible.',1,'2026-08-20 06:52:15','2026-08-31 14:07:28'),
(23,'CAN_CREATE_PROCESS','Creer des processus','Autorise la creation de processus dans le contexte cible.',1,'2026-08-31 14:05:21','2026-09-21 15:38:46'),
(24,'CAN_EDIT_PROCESS','Modifier des processus','Autorise l ajout, la modification et la suppression des etapes et activites de processus dans le contexte cible.',1,'2026-08-31 14:05:21','2026-09-21 15:38:46'),
(25,'CAN_DELETE_PROCESS','Supprimer des processus','Autorise la suppression de processus dans le contexte cible.',1,'2026-08-31 14:05:21','2026-09-21 15:38:46'),
(26,'CAN_DELETE_PROJECT','Supprimer des projets','Autorise la suppression de projets dans le contexte cible.',1,'2026-08-31 14:06:34','2026-08-31 14:06:34'),
(30,'CAN_EDIT_EVENT','Modifier des dates','Autorise la modification de dates dans le contexte cible.',1,'2026-08-31 14:07:28','2026-08-31 14:07:28'),
(37,'CAN_CREATE_RECURRING_TASK','Creer des activites recurrentes','Autorise la creation d activites recurrentes dans le contexte cible.',1,'2026-08-31 14:19:31','2026-09-21 15:38:46'),
(38,'CAN_EDIT_RECURRING_TASK','Modifier des activites recurrentes','Autorise la modification d activites recurrentes dans le contexte cible.',1,'2026-08-31 14:19:31','2026-09-21 15:38:46'),
(39,'CAN_DELETE_RECURRING_TASK','Supprimer des activites recurrentes','Autorise la suppression d activites recurrentes dans le contexte cible.',1,'2026-08-31 14:19:31','2026-09-21 15:38:46'),
(40,'CAN_EDIT_HOLON_BUDGET','Modifier les budgets de holons','Autorise la modification des budgets temps et argent des holons dans le contexte cible.',1,'2026-09-07 09:35:34','2026-09-08 09:10:16'),
(41,'CAN_EDIT_AFFECTATION_BUDGET','Modifier les budgets des affectations','Autorise la modification des budgets temps et argent des affectations dans le contexte cible.',1,'2026-09-07 09:35:34','2026-09-08 09:10:16'),
(43,'CAN_PROPOSE_PROJECT','Proposer des projets','Autorise la proposition de projets au role ou cercle cible.',1,'2026-09-09 09:32:21','2026-09-09 09:49:20'),
(45,'CAN_EDIT_PROJECT','Modifier des projets','Autorise la modification des projets et de leurs taches.',1,'2026-09-17 08:28:39','2026-09-17 08:28:39'),
(46,'CAN_CREATE_RULE','Creer des regles','Autorise la creation de regles dans le contexte cible.',1,'2026-09-17 08:28:39','2026-09-17 08:28:39'),
(47,'CAN_EDIT_RULE','Modifier des regles','Autorise la modification des regles dans le contexte cible.',1,'2026-09-17 08:28:39','2026-09-17 08:28:39'),
(48,'CAN_DELETE_RULE','Supprimer des regles','Autorise la suppression des regles dans le contexte cible.',1,'2026-09-17 08:28:39','2026-09-17 08:28:39'),
(49,'CAN_EDIT_INDICATOR','Modifier des indicateurs','Autorise la modification des indicateurs, de leurs valeurs, groupes et imports.',1,'2026-09-17 08:28:39','2026-09-17 08:28:39'),
(50,'CAN_DELETE_INDICATOR','Supprimer des indicateurs','Autorise le retrait des indicateurs, groupes et imports du contexte cible.',1,'2026-09-17 08:28:39','2026-09-17 08:28:39'),
(51,'CAN_EDIT_DOCUMENT','Modifier des documents','Autorise la modification des documents dans le respect de leur portee d edition.',1,'2026-09-17 08:28:39','2026-09-17 08:28:39'),
(52,'CAN_DELETE_DOCUMENT','Supprimer des documents','Autorise la suppression des documents dans le contexte cible.',1,'2026-09-17 08:28:39','2026-09-17 08:28:39'),
(53,'CAN_EDIT_MEMBER_ASSIGNMENT','Modifier les affectations','Autorise la modification du focus et de la date de revue des affectations.',1,'2026-09-17 08:28:39','2026-09-17 08:28:39'),
(54,'CAN_DELETE_MEMBER','Retirer des membres','Autorise le retrait des membres et l annulation de leurs invitations, sans supprimer leur compte.',1,'2026-09-17 08:28:39','2026-09-17 08:28:39'),
(55,'CAN_EDIT_DECISION','Modifier des decisions','Autorise la gestion des prises de decision dans le contexte cible.',1,'2026-09-17 08:28:39','2026-09-17 08:28:39'),
(56,'CAN_DELETE_DECISION','Supprimer des decisions','Autorise la suppression des prises de decision dans le respect de leur cycle de vie.',1,'2026-09-17 08:28:39','2026-09-17 08:28:39'),
(57,'CAN_EDIT_FAQ','Modifier des FAQ','Autorise la modification des FAQ dans le contexte cible.',1,'2026-09-17 08:28:39','2026-09-17 08:28:39'),
(58,'CAN_DELETE_FAQ','Supprimer des FAQ','Autorise la suppression des FAQ dans le contexte cible.',1,'2026-09-17 08:28:39','2026-09-17 08:28:39'),
(60,'CAN_MOVE_HOLON','Deplacer des holons','Autorise le deplacement des holons couverts par ce droit vers les destinations couvertes par ce meme droit.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(61,'CAN_CREATE_TYPE1_PROPERTIES','Creer les proprietes type1','Autorise a creer les proprietes type1 et a modifier leur structure (nom, format, type et configuration) dans le contexte cible.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(62,'CAN_EDIT_TYPE1_PROPERTIES','Modifier les proprietes type1','Autorise uniquement a modifier les valeurs des proprietes type1 (textes, listes et elements de liste), sans changer leur structure.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(63,'CAN_DELETE_TYPE1_PROPERTIES','Supprimer les proprietes type1','Autorise a supprimer les proprietes type1 dans le contexte cible.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(64,'CAN_CREATE_TYPE2_PROPERTIES','Creer les proprietes type2','Autorise a creer les proprietes type2 et a modifier leur structure (nom, format, type et configuration) dans le contexte cible.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(65,'CAN_EDIT_TYPE2_PROPERTIES','Modifier les proprietes type2','Autorise uniquement a modifier les valeurs des proprietes type2 (textes, listes et elements de liste), sans changer leur structure.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(66,'CAN_DELETE_TYPE2_PROPERTIES','Supprimer les proprietes type2','Autorise a supprimer les proprietes type2 dans le contexte cible.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(67,'CAN_CREATE_TYPE3_PROPERTIES','Creer les proprietes type3','Autorise a creer les proprietes type3 et a modifier leur structure (nom, format, type et configuration) dans le contexte cible.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(68,'CAN_EDIT_TYPE3_PROPERTIES','Modifier les proprietes type3','Autorise uniquement a modifier les valeurs des proprietes type3 (textes, listes et elements de liste), sans changer leur structure.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(69,'CAN_DELETE_TYPE3_PROPERTIES','Supprimer les proprietes type3','Autorise a supprimer les proprietes type3 dans le contexte cible.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(70,'CAN_CREATE_PARCOURS','Creer des parcours','Autorise la creation, l import et le detachement de parcours dans le contexte cible.',0,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(71,'CAN_EDIT_PARCOURS','Editer des parcours','Autorise la modification du contenu des parcours proprietaires et de leurs missions dans le contexte cible.',0,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(72,'CAN_DELETE_PARCOURS','Supprimer un parcours','Autorise la suppression des parcours de l organisation. Les parcours encore utilises sont retires du partage et conserves pour leurs utilisateurs existants.',0,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(73,'CAN_CREATE_TYPE4_PROPERTIES','Creer les proprietes type4','Autorise la creation et la modification de la structure des proprietes type4.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(74,'CAN_EDIT_TYPE4_PROPERTIES','Modifier les proprietes type4','Autorise uniquement la modification des valeurs des proprietes type4.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(75,'CAN_DELETE_TYPE4_PROPERTIES','Supprimer les proprietes type4','Autorise la suppression des proprietes type4.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(76,'CAN_CREATE_TYPE5_PROPERTIES','Creer les proprietes type5','Autorise la creation et la modification de la structure des proprietes type5.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(77,'CAN_EDIT_TYPE5_PROPERTIES','Modifier les proprietes type5','Autorise uniquement la modification des valeurs des proprietes type5.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05'),
(78,'CAN_DELETE_TYPE5_PROPERTIES','Supprimer les proprietes type5','Autorise la suppression des proprietes type5.',1,'2026-09-28 16:40:05','2026-09-28 16:40:05');
/*!40000 ALTER TABLE `permission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `process`
--

DROP TABLE IF EXISTS `process`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `process` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDuser_responsible` int(11) DEFAULT NULL,
  `IDchecklist_previous` int(11) DEFAULT NULL,
  `IDproject_template_root` int(11) NOT NULL,
  `IDdocument` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `revision_note` mediumtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `published_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_checklist_template_root` (`IDproject_template_root`),
  UNIQUE KEY `uniq_checklist_previous` (`IDchecklist_previous`),
  KEY `idx_checklist_organization` (`IDorganization`),
  KEY `idx_checklist_document` (`IDdocument`),
  KEY `idx_checklist_status_active` (`status`,`active`),
  KEY `idx_checklist_responsible` (`IDuser_responsible`),
  CONSTRAINT `fk_checklist_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_checklist_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checklist_previous` FOREIGN KEY (`IDchecklist_previous`) REFERENCES `process` (`id`),
  CONSTRAINT `fk_checklist_responsible` FOREIGN KEY (`IDuser_responsible`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_checklist_template_root` FOREIGN KEY (`IDproject_template_root`) REFERENCES `project` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=769 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `process`
--

LOCK TABLES `process` WRITE;
/*!40000 ALTER TABLE `process` DISABLE KEYS */;
/*!40000 ALTER TABLE `process` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `process_item`
--

DROP TABLE IF EXISTS `process_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `process_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDchecklist` int(11) NOT NULL,
  `IDproject_template` int(11) NOT NULL,
  `stable_key` varchar(64) NOT NULL,
  `activation_type` varchar(30) NOT NULL DEFAULT 'immediate',
  `delay_value` int(11) NOT NULL DEFAULT 0,
  `delay_unit` varchar(20) DEFAULT NULL,
  `display_lead_value` int(11) NOT NULL DEFAULT 0,
  `display_lead_unit` varchar(20) DEFAULT NULL,
  `execution_duration_value` int(11) NOT NULL DEFAULT 0,
  `execution_duration_unit` varchar(20) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_checklist_item_key` (`IDchecklist`,`stable_key`),
  UNIQUE KEY `uniq_checklist_item_project` (`IDproject_template`),
  KEY `idx_checklist_item_position` (`IDchecklist`,`position`),
  KEY `idx_checklist_item_activation` (`activation_type`,`active`),
  CONSTRAINT `fk_checklist_item_checklist` FOREIGN KEY (`IDchecklist`) REFERENCES `process` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checklist_item_project` FOREIGN KEY (`IDproject_template`) REFERENCES `project` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1768 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `process_item`
--

LOCK TABLES `process_item` WRITE;
/*!40000 ALTER TABLE `process_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `process_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `process_item_dependency`
--

DROP TABLE IF EXISTS `process_item_dependency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `process_item_dependency` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDchecklistitem` int(11) NOT NULL,
  `IDchecklistitem_required` int(11) NOT NULL,
  `delay_value` int(11) NOT NULL DEFAULT 0,
  `delay_unit` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_checklist_item_dependency` (`IDchecklistitem`,`IDchecklistitem_required`),
  KEY `idx_checklist_dependency_required` (`IDchecklistitem_required`),
  CONSTRAINT `fk_checklist_dependency_item` FOREIGN KEY (`IDchecklistitem`) REFERENCES `process_item` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checklist_dependency_required` FOREIGN KEY (`IDchecklistitem_required`) REFERENCES `process_item` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `process_item_dependency`
--

LOCK TABLES `process_item_dependency` WRITE;
/*!40000 ALTER TABLE `process_item_dependency` DISABLE KEYS */;
/*!40000 ALTER TABLE `process_item_dependency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `process_item_occurrence`
--

DROP TABLE IF EXISTS `process_item_occurrence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `process_item_occurrence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDchecklistitem` int(11) NOT NULL,
  `scheduled_for` datetime NOT NULL,
  `IDproject` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_checklist_item_occurrence` (`IDchecklistitem`,`scheduled_for`),
  UNIQUE KEY `uniq_checklist_item_occurrence_project` (`IDproject`),
  KEY `idx_checklist_item_occurrence_project` (`IDproject`),
  CONSTRAINT `fk_checklist_item_occurrence_item` FOREIGN KEY (`IDchecklistitem`) REFERENCES `process_item` (`id`),
  CONSTRAINT `fk_checklist_item_occurrence_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=466 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `process_item_occurrence`
--

LOCK TABLES `process_item_occurrence` WRITE;
/*!40000 ALTER TABLE `process_item_occurrence` DISABLE KEYS */;
/*!40000 ALTER TABLE `process_item_occurrence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `process_item_recurrence`
--

DROP TABLE IF EXISTS `process_item_recurrence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `process_item_recurrence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDchecklistitem` int(11) NOT NULL,
  `frequency` varchar(20) NOT NULL,
  `schedule` varchar(20) NOT NULL,
  `display_lead_value` int(11) NOT NULL DEFAULT 0,
  `display_lead_unit` varchar(20) DEFAULT NULL,
  `execution_duration_value` int(11) NOT NULL DEFAULT 0,
  `execution_duration_unit` varchar(20) DEFAULT NULL,
  `next_trigger_at` datetime DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_checklist_item_recurrence` (`IDchecklistitem`),
  KEY `idx_checklist_item_recurrence_due` (`enabled`,`next_trigger_at`),
  CONSTRAINT `fk_checklist_item_recurrence_item` FOREIGN KEY (`IDchecklistitem`) REFERENCES `process_item` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1762 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `process_item_recurrence`
--

LOCK TABLES `process_item_recurrence` WRITE;
/*!40000 ALTER TABLE `process_item_recurrence` DISABLE KEYS */;
/*!40000 ALTER TABLE `process_item_recurrence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `process_run`
--

DROP TABLE IF EXISTS `process_run`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `process_run` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDchecklist` int(11) NOT NULL,
  `IDchecklisttrigger` int(11) DEFAULT NULL,
  `IDorganization` int(11) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDproject_root` int(11) DEFAULT NULL,
  `IDuser_created` int(11) DEFAULT NULL,
  `scheduled_for` datetime DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'running',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_checklist_run_occurrence` (`IDchecklisttrigger`,`scheduled_for`),
  KEY `idx_checklist_run_checklist` (`IDchecklist`,`created_at`),
  KEY `idx_checklist_run_context` (`IDorganization`,`IDholon`),
  KEY `idx_checklist_run_project` (`IDproject_root`),
  KEY `idx_checklist_run_user` (`IDuser_created`),
  KEY `idx_checklist_run_status` (`status`),
  KEY `fk_checklist_run_holon` (`IDholon`),
  CONSTRAINT `fk_checklist_run_checklist` FOREIGN KEY (`IDchecklist`) REFERENCES `process` (`id`),
  CONSTRAINT `fk_checklist_run_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_checklist_run_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checklist_run_project` FOREIGN KEY (`IDproject_root`) REFERENCES `project` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_checklist_run_trigger` FOREIGN KEY (`IDchecklisttrigger`) REFERENCES `process_trigger` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_checklist_run_user` FOREIGN KEY (`IDuser_created`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `process_run`
--

LOCK TABLES `process_run` WRITE;
/*!40000 ALTER TABLE `process_run` DISABLE KEYS */;
/*!40000 ALTER TABLE `process_run` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `process_run_item`
--

DROP TABLE IF EXISTS `process_run_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `process_run_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDchecklistrun` int(11) NOT NULL,
  `IDchecklistitem` int(11) NOT NULL,
  `IDproject` int(11) DEFAULT NULL,
  `activation_at` datetime DEFAULT NULL,
  `state` varchar(20) NOT NULL DEFAULT 'waiting',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activated_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_checklist_run_item` (`IDchecklistrun`,`IDchecklistitem`),
  UNIQUE KEY `uniq_checklist_run_item_project` (`IDproject`),
  KEY `idx_checklist_run_item_state` (`state`,`activation_at`),
  KEY `idx_checklist_run_item_template` (`IDchecklistitem`),
  CONSTRAINT `fk_checklist_run_item_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_checklist_run_item_run` FOREIGN KEY (`IDchecklistrun`) REFERENCES `process_run` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checklist_run_item_template` FOREIGN KEY (`IDchecklistitem`) REFERENCES `process_item` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `process_run_item`
--

LOCK TABLES `process_run_item` WRITE;
/*!40000 ALTER TABLE `process_run_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `process_run_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `process_trigger`
--

DROP TABLE IF EXISTS `process_trigger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `process_trigger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDchecklist` int(11) NOT NULL,
  `stable_key` varchar(64) NOT NULL,
  `trigger_type` varchar(20) NOT NULL DEFAULT 'manual',
  `frequency` varchar(20) DEFAULT NULL,
  `schedule` varchar(20) DEFAULT NULL,
  `next_trigger_at` datetime DEFAULT NULL,
  `overlap_policy` varchar(20) NOT NULL DEFAULT 'create_new',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_checklist_trigger_key` (`IDchecklist`,`stable_key`),
  KEY `idx_checklist_trigger_due` (`trigger_type`,`enabled`,`next_trigger_at`),
  KEY `idx_checklist_trigger_frequency` (`frequency`),
  CONSTRAINT `fk_checklist_trigger_checklist` FOREIGN KEY (`IDchecklist`) REFERENCES `process` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=769 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `process_trigger`
--

LOCK TABLES `process_trigger` WRITE;
/*!40000 ALTER TABLE `process_trigger` DISABLE KEYS */;
/*!40000 ALTER TABLE `process_trigger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project`
--

DROP TABLE IF EXISTS `project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `IDuser_proposed` int(11) DEFAULT NULL,
  `IDproject_parent` int(11) DEFAULT NULL,
  `IDdocument_journal` int(11) DEFAULT NULL,
  `project_kind` varchar(30) NOT NULL DEFAULT 'standard',
  `proposal_status` varchar(20) NOT NULL DEFAULT 'normal',
  `proposed_at` datetime DEFAULT NULL,
  `proposal_decided_at` datetime DEFAULT NULL,
  `IDproject_template` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'someday',
  `blocked_reason` mediumtext DEFAULT NULL,
  `blocked_until` date DEFAULT NULL,
  `blocked_auto_reactivate` tinyint(1) NOT NULL DEFAULT 0,
  `blocked_reactivate_status` varchar(20) NOT NULL DEFAULT 'ready',
  `planned_start_date` date DEFAULT NULL,
  `planned_end_date` date DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `priority` tinyint(3) DEFAULT NULL,
  `importance` tinyint(3) DEFAULT NULL,
  `calculated_importance` decimal(10,8) NOT NULL DEFAULT 0.00000000,
  `project_size` varchar(3) NOT NULL DEFAULT 'M',
  `capture_mode` varchar(30) NOT NULL DEFAULT 'multiple_documents',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `archived_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project_organization` (`IDorganization`),
  KEY `idx_project_holon` (`IDholon`),
  KEY `idx_project_user` (`IDuser`),
  KEY `idx_project_parent` (`IDproject_parent`),
  KEY `idx_project_journal` (`IDdocument_journal`),
  KEY `idx_project_status` (`status`),
  KEY `idx_project_active` (`active`),
  KEY `idx_project_kind` (`project_kind`),
  KEY `idx_project_template` (`IDproject_template`),
  KEY `idx_project_calculated_importance` (`calculated_importance`),
  KEY `idx_project_archived_at` (`archived_at`),
  KEY `idx_project_closed_at` (`closed_at`),
  KEY `idx_project_blocked_until` (`status`,`blocked_auto_reactivate`,`blocked_until`),
  KEY `idx_project_proposer` (`IDuser_proposed`),
  KEY `idx_project_proposal_status` (`proposal_status`,`active`),
  CONSTRAINT `fk_project_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_project_journal` FOREIGN KEY (`IDdocument_journal`) REFERENCES `document` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_project_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_parent` FOREIGN KEY (`IDproject_parent`) REFERENCES `project` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_project_proposer` FOREIGN KEY (`IDuser_proposed`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_project_template` FOREIGN KEY (`IDproject_template`) REFERENCES `project` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_project_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=52242 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project`
--

LOCK TABLES `project` WRITE;
/*!40000 ALTER TABLE `project` DISABLE KEYS */;
/*!40000 ALTER TABLE `project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_document`
--

DROP TABLE IF EXISTS `project_document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDproject` int(11) NOT NULL,
  `IDdocument` int(11) NOT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_document` (`IDproject`,`IDdocument`),
  KEY `idx_project_document_project` (`IDproject`),
  KEY `idx_project_document_document` (`IDdocument`),
  CONSTRAINT `fk_project_document_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_document_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5293 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_document`
--

LOCK TABLES `project_document` WRITE;
/*!40000 ALTER TABLE `project_document` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_document` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_follower`
--

DROP TABLE IF EXISTS `project_follower`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_follower` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDproject` int(11) NOT NULL,
  `IDuser` int(11) NOT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_follower` (`IDproject`,`IDuser`),
  KEY `idx_project_follower_project` (`IDproject`),
  KEY `idx_project_follower_user` (`IDuser`),
  KEY `idx_project_follower_active` (`active`),
  CONSTRAINT `fk_project_follower_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_follower_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_follower`
--

LOCK TABLES `project_follower` WRITE;
/*!40000 ALTER TABLE `project_follower` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_follower` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_user`
--

DROP TABLE IF EXISTS `project_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDproject` int(11) NOT NULL,
  `IDuser` int(11) NOT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_user` (`IDproject`,`IDuser`),
  KEY `idx_project_user_project` (`IDproject`),
  KEY `idx_project_user_user` (`IDuser`),
  KEY `idx_project_user_active` (`active`),
  CONSTRAINT `fk_project_user_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_user_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_user`
--

LOCK TABLES `project_user` WRITE;
/*!40000 ALTER TABLE `project_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property`
--

DROP TABLE IF EXISTS `property`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `property` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shortname` varchar(20) NOT NULL COMMENT 'Clé utilisée dans les JSON',
  `type` varchar(20) NOT NULL DEFAULT 'type1',
  `name` varchar(255) NOT NULL,
  `IDpropertyformat` int(11) NOT NULL,
  `listitemtype` varchar(20) DEFAULT NULL,
  `listholontypeids` varchar(255) DEFAULT NULL,
  `IDholon_organization` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `position` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_property_root_holon` (`IDholon_organization`),
  CONSTRAINT `fk_property_root_holon` FOREIGN KEY (`IDholon_organization`) REFERENCES `holon` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=463 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Propriétés assignées à des tempales (holons)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property`
--

LOCK TABLES `property` WRITE;
/*!40000 ALTER TABLE `property` DISABLE KEYS */;
/*!40000 ALTER TABLE `property` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `propertyformat`
--

DROP TABLE IF EXISTS `propertyformat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `propertyformat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Formats autorisés pour les blocs (tels que chaînes, textes libre, liste, case à cocher, etc...)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `propertyformat`
--

LOCK TABLES `propertyformat` WRITE;
/*!40000 ALTER TABLE `propertyformat` DISABLE KEYS */;
INSERT INTO `propertyformat` VALUES
(1,'Texte libre'),
(2,'Liste'),
(3,'Chiffre'),
(4,'Date'),
(6,'Texte avec detail HTML'),
(7,'HTML et liste');
/*!40000 ALTER TABLE `propertyformat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pv`
--

DROP TABLE IF EXISTS `pv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pv` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` mediumtext NOT NULL,
  `IDuser` int(11) NOT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime NOT NULL DEFAULT current_timestamp(),
  `codeaffichage` varchar(200) NOT NULL,
  `codeedition` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pv`
--

LOCK TABLES `pv` WRITE;
/*!40000 ALTER TABLE `pv` DISABLE KEYS */;
/*!40000 ALTER TABLE `pv` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qr`
--

DROP TABLE IF EXISTS `qr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniquekey` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `IDuser` int(11) NOT NULL,
  `shortcut` varchar(255) DEFAULT NULL COMMENT 'Raccourci défini par l''utilisateur (unique pour lui)',
  `description` varchar(255) NOT NULL,
  `cpt` int(11) NOT NULL DEFAULT 0,
  `datelastaccess` datetime DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Se rappeler quand ça a été créé, pour certain affichages',
  `active` int(11) NOT NULL DEFAULT 1 COMMENT 'Permet de désactive temporairement l''élément',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qr`
--

LOCK TABLES `qr` WRITE;
/*!40000 ALTER TABLE `qr` DISABLE KEYS */;
/*!40000 ALTER TABLE `qr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question`
--

DROP TABLE IF EXISTS `question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDhowto` int(11) DEFAULT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `detail` text DEFAULT NULL,
  `displayorder` int(11) DEFAULT 0,
  `isactive` tinyint(1) DEFAULT 1,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_question_displayorder` (`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question`
--

LOCK TABLES `question` WRITE;
/*!40000 ALTER TABLE `question` DISABLE KEYS */;
/*!40000 ALTER TABLE `question` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_choice`
--

DROP TABLE IF EXISTS `question_choice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_choice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDquestion` int(11) DEFAULT NULL,
  `label` mediumtext DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_question_choice_question` (`IDquestion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_choice`
--

LOCK TABLES `question_choice` WRITE;
/*!40000 ALTER TABLE `question_choice` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_choice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recurring_task`
--

DROP TABLE IF EXISTS `recurring_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) DEFAULT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDuser_responsible` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `frequency` varchar(20) NOT NULL,
  `schedule` varchar(20) NOT NULL,
  `display_lead_value` int(11) NOT NULL DEFAULT 0,
  `display_lead_unit` varchar(20) DEFAULT NULL,
  `execution_duration_value` int(11) NOT NULL DEFAULT 1,
  `execution_duration_unit` varchar(20) NOT NULL DEFAULT 'day',
  `position` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_control_task_active` (`active`),
  KEY `idx_control_task_context` (`IDorganization`,`IDholon`),
  KEY `fk_control_task_holon` (`IDholon`),
  KEY `idx_recurring_task_responsible` (`IDuser_responsible`),
  CONSTRAINT `fk_control_task_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_control_task_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recurring_task_responsible` FOREIGN KEY (`IDuser_responsible`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recurring_task`
--

LOCK TABLES `recurring_task` WRITE;
/*!40000 ALTER TABLE `recurring_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `recurring_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recurring_task_check`
--

DROP TABLE IF EXISTS `recurring_task_check`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurring_task_check` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDcontroltask` int(11) NOT NULL,
  `IDuser` int(11) NOT NULL,
  `scheduled_for` datetime NOT NULL,
  `checked_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_control_task_check_occurrence` (`IDcontroltask`,`scheduled_for`),
  KEY `idx_control_task_check_user` (`IDuser`),
  KEY `idx_control_task_check_checked_at` (`checked_at`),
  CONSTRAINT `fk_control_task_check_task` FOREIGN KEY (`IDcontroltask`) REFERENCES `recurring_task` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_control_task_check_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recurring_task_check`
--

LOCK TABLES `recurring_task_check` WRITE;
/*!40000 ALTER TABLE `recurring_task_check` DISABLE KEYS */;
/*!40000 ALTER TABLE `recurring_task_check` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resource_attendance`
--

DROP TABLE IF EXISTS `resource_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_type` varchar(50) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `display_name` varchar(190) DEFAULT NULL,
  `is_present` tinyint(1) NOT NULL DEFAULT 0,
  `IDuser_checked_by` int(11) DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_resource_attendance_user` (`resource_type`,`resource_id`,`IDuser`),
  UNIQUE KEY `uniq_resource_attendance_email` (`resource_type`,`resource_id`,`email`),
  KEY `idx_resource_attendance_resource` (`resource_type`,`resource_id`,`active`),
  KEY `idx_resource_attendance_present` (`is_present`),
  KEY `fk_resource_attendance_user` (`IDuser`),
  KEY `fk_resource_attendance_checked_by` (`IDuser_checked_by`),
  CONSTRAINT `fk_resource_attendance_checked_by` FOREIGN KEY (`IDuser_checked_by`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_resource_attendance_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource_attendance`
--

LOCK TABLES `resource_attendance` WRITE;
/*!40000 ALTER TABLE `resource_attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `resource_attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resource_invitation`
--

DROP TABLE IF EXISTS `resource_invitation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_invitation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_type` varchar(50) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `display_name` varchar(190) DEFAULT NULL,
  `invitation_type` varchar(30) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'invited',
  `accepted` tinyint(1) DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_resource_invitation_holon` (`resource_type`,`resource_id`,`IDholon`),
  UNIQUE KEY `uniq_resource_invitation_user` (`resource_type`,`resource_id`,`IDuser`),
  UNIQUE KEY `uniq_resource_invitation_email` (`resource_type`,`resource_id`,`email`),
  KEY `idx_resource_invitation_resource` (`resource_type`,`resource_id`,`active`),
  KEY `idx_resource_invitation_type` (`invitation_type`),
  KEY `idx_resource_invitation_status` (`status`),
  KEY `fk_resource_invitation_holon` (`IDholon`),
  KEY `fk_resource_invitation_user` (`IDuser`),
  CONSTRAINT `fk_resource_invitation_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_resource_invitation_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource_invitation`
--

LOCK TABLES `resource_invitation` WRITE;
/*!40000 ALTER TABLE `resource_invitation` DISABLE KEYS */;
/*!40000 ALTER TABLE `resource_invitation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rule`
--

DROP TABLE IF EXISTS `rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDauthority` int(11) DEFAULT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDorganization` int(11) DEFAULT NULL,
  `scope` varchar(20) NOT NULL DEFAULT 'local',
  `title` varchar(255) NOT NULL,
  `intention` mediumtext DEFAULT NULL,
  `description` mediumtext NOT NULL,
  `review_date` date NOT NULL,
  `expiration_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `IDuser_creation` int(11) DEFAULT NULL,
  `IDuser_modification` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rule_authority` (`IDauthority`),
  KEY `idx_rule_holon` (`IDholon`),
  KEY `idx_rule_scope` (`scope`),
  KEY `idx_rule_review` (`review_date`),
  KEY `idx_rule_expiration` (`expiration_date`),
  KEY `idx_rule_user_creation` (`IDuser_creation`),
  KEY `idx_rule_user_modification` (`IDuser_modification`),
  KEY `idx_rule_organization` (`IDorganization`),
  CONSTRAINT `fk_rule_authority` FOREIGN KEY (`IDauthority`) REFERENCES `authority` (`id`),
  CONSTRAINT `fk_rule_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rule_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rule_user_creation` FOREIGN KEY (`IDuser_creation`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rule_user_modification` FOREIGN KEY (`IDuser_modification`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_rule_source` CHECK (`IDauthority` is null and `IDholon` is null and `IDorganization` is not null or `IDauthority` is null <> (`IDholon` is null)),
  CONSTRAINT `chk_rule_scope` CHECK (`scope` in ('global','descendants','circle','local'))
) ENGINE=InnoDB AUTO_INCREMENT=2120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rule`
--

LOCK TABLES `rule` WRITE;
/*!40000 ALTER TABLE `rule` DISABLE KEYS */;
/*!40000 ALTER TABLE `rule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_job`
--

DROP TABLE IF EXISTS `search_job`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jobtype` varchar(40) NOT NULL DEFAULT 'topbar_search',
  `status` varchar(20) NOT NULL DEFAULT 'queued',
  `query` text NOT NULL,
  `scopesjson` mediumtext DEFAULT NULL,
  `timerangejson` mediumtext DEFAULT NULL,
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
  KEY `idx_search_job_org_status` (`IDorganization`,`status`),
  KEY `idx_search_job_creation` (`datecreation`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_job`
--

LOCK TABLES `search_job` WRITE;
/*!40000 ALTER TABLE `search_job` DISABLE KEYS */;
/*!40000 ALTER TABLE `search_job` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sql_migration`
--

DROP TABLE IF EXISTS `sql_migration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sql_migration` (
  `filename` varchar(255) NOT NULL,
  `checksum` char(64) NOT NULL,
  `executed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sql_migration`
--

LOCK TABLES `sql_migration` WRITE;
/*!40000 ALTER TABLE `sql_migration` DISABLE KEYS */;
INSERT INTO `sql_migration` VALUES
('2026-04-20-login-code-auth.sql','6c2311d07a24b8c11dfe95955b7998776fa046a4f798cc3429403d948cd8ec26','2026-07-24 17:40:17'),
('2026-04-20-remote-utf8mb4-and-org-normalization.sql','96823359fdbf968a8943fefbaf763852e510b4daba9784cf5476338dd9d1e37f','2026-07-24 17:40:17'),
('2026-04-23-document-context.sql','3e147324ffd8eaeef1df78c17f3651f29e2f3218382700ba0bcb3524ec6831c7','2026-07-24 17:40:17'),
('2026-04-23-organization-applications.sql','0ac9537f556cafc92169df52068c371711769a9e85b89696ff02e1c857c90013','2026-07-24 17:40:17'),
('2026-04-23-team-application.sql','8d9c7b5db8175dc67ea73ae0f84664889f736971a326fdf9f42a5fc6ef2d7954','2026-07-24 17:40:17'),
('2026-04-23-user-organization-scoped-fields.sql','10ebbdf8e59de3e12ac108d55df14148d76c0de2d324bea33e28fcdfc66a9cb1','2026-07-24 17:40:17'),
('2026-04-24-holonproperty-mandatory-locked.sql','aebbdb1f9dac68a3f525c97b7e0ff8203fa098ce3eca014108c67f542b87ec1e','2026-07-24 17:40:17'),
('2026-04-24-property-format-number-date.sql','7ee5e124d5eda93761a3ab67fc4f4de6d91e838253b93d4319949a845886a2b7','2026-07-24 17:40:17'),
('2026-04-24-property-list-metadata.sql','581699eec1f66c148cfef22462ee112c928b538d371fb411534db392642aa222','2026-07-24 17:40:17'),
('2026-04-27-user-patreon.sql','1bac34e5318788f4a824049a05e55a09820f558ef1cf9d80c45118aaa1f4bace','2026-07-24 17:40:17'),
('2026-04-30-add-image-profil.sql','9dd2dfa4a40a20c5955476fcc7c3751d4935e2973e3f2b94c882a967bf9157f0','2026-07-24 17:40:17'),
('2026-04-30-history.sql','2758edb68039c2bb50a0c5f31749bb5f8d4fd2ef4295ead56748bf060e0407c9','2026-07-24 17:40:17'),
('2026-04-30-invitation.sql','7743b2f2c585eee5bb7bb811c6ad4b4940ba28c3901d6056172f8164c8419ffb','2026-07-24 17:40:17'),
('2026-05-01-lms-questions.sql','ad1f1e9032bdf01a0405b0712bf414fd33aeca76311ae46b5eec3e7c97f8f2de','2026-07-24 17:40:17'),
('2026-05-01-z-faq-popup.sql','bc11cd74c9d5cf4745df6865c06b307528405cfed36230f847634118f59e4cdc','2026-07-24 17:40:17'),
('2026-05-02-holon-illustrations.sql','44524b568979ed02df6880810d9521b46289f6783514810c9ee3bc30de46197e','2026-07-24 17:40:17'),
('2026-05-04-lms-homework.sql','2dc4e5b90d3aa337d7fae28e3469b021f96a5a174bd0b06a6c55be3240965867','2026-07-24 17:40:17'),
('2026-05-05-omo-holon-share-link.sql','9e0884a89df528725fea6ec535d0aa4b3833d3db8da12349b298e3fc2e6baca2','2026-07-24 17:40:17'),
('2026-05-05-organization-delete-cascade.sql','f510b8a1261082e241a0dc59977dcb59fd1c34c0ef927c1a0a2c9307812e617f','2026-07-24 17:40:17'),
('2026-05-06-property-format-html.sql','30de7995c79dda2786c39da7e17365124ec2db9a0cd825526f06d96e0a3287ab','2026-07-24 17:40:17'),
('2026-05-09-search-job.sql','904229f7659f288354c55f6b1b8ae394b2722dd53a5a6a4849f4fb495d5af0af','2026-07-24 17:40:17'),
('2026-05-11-user-competences.sql','bc32fae2096efcbb468547559212e9321783eccc75a0206dd7d2b36313e15026','2026-07-24 17:40:17'),
('2026-05-12-faq-contextual-holon.sql','05394ac18010082eb6bdd126d30f6a6900130d2caa940744cb43e472de3d1cf4','2026-07-24 17:40:17'),
('2026-05-12-user-competence-description.sql','b17d58918d96731d03d8f1d8928a8d2810efc9627624e563455733e35dc087dc','2026-07-24 17:40:17'),
('2026-05-13-history-circle-link.sql','4e0a61843ed0a3b659305c23afc15a1c1b150daf53e4508c52fdeaf30036ae83','2026-07-24 17:40:17'),
('2026-05-13-holonproperty-update-tracking.sql','d8f6f31bcff080fef5ea6027aec9af74b7756d38157048ad08f4ed3e336332fc','2026-07-24 17:40:17'),
('2026-05-13-user-profile-presentation-birthdate.sql','bf96a7bec60bdfb2c2782d2fe7a1de901652a0754ad61e231c3c0b50df72044f','2026-07-24 17:40:17'),
('2026-05-14-user-password-length.sql','928e4adf8d2c4cb373309cc3b3065ce3185a7afa12f1312c0418ce939f805330','2026-07-24 17:40:17'),
('2026-05-14-user-siteadmin.sql','4ad715dea4cb4ed2b54b14e0c606adc502fe328d824e2d6343bfdd10ff4eb9f9','2026-07-24 17:40:17'),
('2026-05-15-01-translation-bundles.sql','70ac5a41b18dc96657b9f500707e0381e928195b8bee0a2b6ca4fb77016a4a6e','2026-07-24 17:40:17'),
('2026-05-15-02-translation-bundle-refresh-jobs.sql','dd81bdecd6e7f94808710b558f8441e5706c218f48bcaec892f90a34cf0347b4','2026-07-24 17:40:17'),
('2026-05-15-03-omo-index-translation-bundles.sql','6d1d7cf95a55217a53b82b1d94046f41a9e5f58df3253365b34c20b9d92e44a2','2026-07-24 17:40:17'),
('2026-05-15-04-omo-navigation-translation-bundles.sql','a46ba2663dac77207c1e9bd8b336afc6b5a8108a5d76045b10f8ef11d0d9860a','2026-07-24 17:40:17'),
('2026-05-15-05-drop-legacy-translation.sql','738133025906732f5f973c6c31432a2bbb1000368c1195fa84f7cf57c464a9cb','2026-07-24 17:40:17'),
('2026-05-17-01-translation-languages.sql','03af793097f97d39d277f208d1e6225aa527dc96fd08717f8886e853fe54ff6e','2026-07-24 17:40:17'),
('2026-05-18-01-permission-catalog.sql','17b2373642a353900cc81f69175424999a10dd716be1c87ff0023cb25d11a972','2026-07-24 17:40:17'),
('2026-05-18-02-holon-permission.sql','3a109fb25b68dce10bddb1506b2ee4d99a11038925bc769962a8e0175639492f','2026-07-24 17:40:17'),
('2026-05-18-03-member-permissions.sql','a2b3cd6981d60a6c7f659c6e2092a72afbf9cb0c914bee5305faa4f17e6d9503','2026-07-24 17:40:17'),
('2026-05-18-04-holon-permission-multi-range.sql','03d4e25784936e8c6d0f798e40d3f80b5c5c1982c283c5689c21b514325c727e','2026-07-24 17:40:17'),
('2026-05-21-01-user-organization-latlong.sql','59e7c7d892851a164c6e41abc4cfd224a01b3919d53a94c0929763e43dd0cbca','2026-07-24 17:40:17'),
('2026-05-21-02-user-latlong.sql','71d7e0bbce7a977be4e80462101c0c6abed09cb56f49adffda450ce35be38f61','2026-07-24 17:40:17'),
('2026-05-21-03-decision-process.sql','1e8e289262b27d0e60dfe9871794b2a4ef04333fe85c0dd7e51299f15ab39d66','2026-07-24 17:40:17'),
('2026-05-22-01-decision-invitation.sql','54aedf6005afab9ec5ecaaf3816dbf2571eff849ac91e63e1f1deaa18b34bbec','2026-07-24 17:40:17'),
('2026-05-23-01-decision-participant-public-access.sql','6c1eb2b549b20858153eb166aa6c69c8dc37c96d8be383e32d06347aa350600a','2026-07-24 17:40:17'),
('2026-05-24-01-decision-proposal-info-url.sql','74fa5ae989c416eea0e7b7d7d09b0a0661f7436d84caecf1c7fc3553e0a72020','2026-07-24 17:40:17'),
('2026-05-28-01-faq-organization-scope.sql','e433a1fa8661e7bd07c966070c267de80d336884f48d34a2c084e52fb9cad99f','2026-07-24 17:40:17'),
('2026-05-30-01-decision-groups.sql','6bec957ba5eb0b4d1379795faf02e9b531196035f9894d3603c32c3deb1acae1','2026-07-24 17:40:17'),
('2026-05-30-02-structure-application-drawer.sql','cae3e8fdf452f0e287e5fc6e54b24e1a79e7dd6c27fac1d2e1944d2ee8505983','2026-07-24 17:40:17'),
('2026-06-01-01-object-visibility.sql','3e492c72b51d86893422725e6e83b15673d3ef45692157552345915cf4c61872','2026-07-24 17:40:17'),
('2026-06-02-01-document-folders.sql','78b45bf27c28f23a3584a087261bb78b00d14b0d5efae893c3657e7b2ba2f75e','2026-07-24 17:40:17'),
('2026-06-02-02-document-update-tracking.sql','0cc00c68eb251f786c32a2ac2cc79b43b7b585fe4a0e73d226612e79945f9782','2026-07-24 17:40:17'),
('2026-06-02-03-document-edit-lock.sql','36a042d9563eb3d02f823b6c634349a920a3eb0a2b21968f283e24bcd70c5c23','2026-07-24 17:40:17'),
('2026-06-02-04-document-share-link.sql','1092d1b2033174d0c25668085b704004b579eaf57ae7f4aea80cf979dab581cd','2026-07-24 17:40:17'),
('2026-06-02-05-document-draft-content.sql','139963598bae6f7c06502680bd6b4f3fb39b381239ce9c4a27b51aa41138b8af','2026-07-24 17:40:17'),
('2026-06-05-01-faq-votes.sql','42d5e63ee9e17635b8ad60eda00942395f22a75862a24e5f1088af5d24c4e027','2026-07-24 17:40:17'),
('2026-06-05-02-faq-score-decay.sql','76837410ac04fcfd03d8261e8643351b0fa4e876cf597cacbbf5332555b052e1','2026-07-24 17:40:17'),
('2026-06-08-01-document-types.sql','c707e2fc3725934607aa67fb735a61266cd6a68e235e8d1e46c19c7219d76f75','2026-07-24 17:40:17'),
('2026-06-08-02-document-upload-storage.sql','a114074c763a6c1ee66a013533fe1f7ed00938cb2bada84b4f9bd7c7aeae3967','2026-07-24 17:40:17'),
('2026-06-08-03-organization-parameters.sql','7583a492bf25e26ee2c4e9ed9bc3ef2b7476a4f876cceafa64eff79ff54bf82c','2026-07-24 17:40:17'),
('2026-06-08-04-event.sql','05676a46d38fbfc95729088c4fa8e30b7269ec4dddd336da73ba027a6fb418e4','2026-07-24 17:40:17'),
('2026-06-14-01-invitation-request-origin.sql','cf0594a12bf4f9e099e7e01d60237b12850c3eae6c0c73777ac5b76496e79e47','2026-07-24 17:40:17'),
('2026-06-16-01-faq-media.sql','a2ca82e1d0449a8eff5c2b0c8be92acbbfcd91e1b6bda5fc62bf5c1d062542ad','2026-07-24 17:40:17'),
('2026-06-17-01-organization-parcours-anonymous.sql','69fbaf7865df128011d6444efbf5bb569e2f4d20060e792114b12ea87a90e6b0','2026-07-24 17:40:17'),
('2026-06-18-01-organization-latlong.sql','847a01cb24bcddbaa6097aaaa9937d3a8ccf50c13c5a5c065d556de1e1c672ea','2026-07-24 17:40:17'),
('2026-06-19-01-holon-template-create-permissions.sql','68ba55bdc9fc68b12ed020a4eb118277e0d051e1ab572834998dc253ce9ce5ca','2026-07-24 17:40:17'),
('2026-06-22-01-faq-user-help-content.sql','4bdcfe99b58ee98968263adb7e00b2a8fc6a84ce6bd2d5ba2e05b7acf16c4033','2026-07-24 17:40:17'),
('2026-06-22-01-lms-organization-parcours-anonymous.sql','c34ec97a9a3721ef6fc2ca5f0f8f06ce87128f275e1208cac20c4e7a2c08a385','2026-07-24 17:40:18'),
('2026-06-22-02-decision-process-visibility.sql','c20bd33ebbec0bb3ee8f2343e2bb42c9e340301b070cf7cca9c10bd162705b3f','2026-07-24 17:40:18'),
('2026-06-22-02-parcours-metadata.sql','bc16c619ec96aec27f90fc950385292d6e54a79029e550355636870b62407d17','2026-07-24 17:40:18'),
('2026-06-23-01-parcours-mission-position.sql','c5067846e9e0b9c1d15d65288218a96afac1c9c984743a0ef9cb895c39372c8a','2026-07-24 17:40:18'),
('2026-06-23-02-faq-table-recovery.sql','c43805239154eb808bd5bb5b2bbd48efc101d89c6263203cfef11f6a038aa56f','2026-07-24 17:40:18'),
('2026-06-27-01-faq-parcours-scope.sql','cb9929733b535d8f6e154b0838e16129fff1b6f88b4bbaa1380a33f5921c6a43','2026-07-24 17:40:18'),
('2026-06-28-01-parcours-application-link.sql','6ceefb67b023c7d2d8053274528f375e610401692989738e845b0b83bafd3349','2026-07-24 17:40:18'),
('2026-06-28-02-parcours-pack-links.sql','e39394103f740d99c4e3d68d32866422a86611de0b606f9287439e7ca28bb839','2026-07-24 17:40:18'),
('2026-06-29-01-lms-parcours-permissions.sql','29ee2fda8da2d5cf54b34b4871825bd92b48dac836194c595f1440b1d9e77bcc','2026-07-24 17:40:18'),
('2026-06-29-02-permission-contextual-flag.sql','634af9133452ef8ec10dae9e11e67889f1a6578007a5d1a189ea90e92b6782ae','2026-07-24 17:40:18'),
('2026-06-29-03-parcours-prerequisites.sql','704a3d859b84e574c15fe2ee3af0899d29a70adab06d6b1baadd02fa7b68263a','2026-07-24 17:40:18'),
('2026-06-30-01-omo-tension.sql','9b5a243f3e094b4c9b56848335147f097c815936cbb78668a49b6cd103598fa9','2026-07-24 17:40:18'),
('2026-07-01-01-user-organization-image-guard.sql','a494fdb70b56189075b8cffaaf9eb3b3356a0d36cee0a01c002c327af656796d','2026-07-24 17:40:18'),
('2026-07-02-01-lms-mission-video-providers.sql','a1b7ed9c7be9cdc619ba7fe5c51f57abd3836ecc915431cf4f51e3f13943e2ec','2026-07-24 17:40:18'),
('2026-07-04-01-lms-homework-only-admin.sql','4016872bb8ae63347c37551f432b0caf81ac98d0b5b17e8e90fc73890be3b49f','2026-07-24 17:40:18'),
('2026-07-04-02-can-add-app-permission.sql','d39bc12794d0f25e6a1c2f0ac013694cdeae7e55cd6237e817fd3780830e6acf','2026-07-24 17:40:18'),
('2026-07-05-01-holonproperty-value-mediumtext.sql','277879e940dd15a2c0f9f37ad4a7b7515489b644b15cf45ee7dc3d6e9f547ad8','2026-07-24 17:40:18'),
('2026-07-06-01-organization-application-parameters.sql','d429d7c1a05f15f90128557c7690450ea517380de47046ef3e82bc293539f70d','2026-07-24 17:40:18'),
('2026-07-07-01-holon-nomcomplet.sql','7f18ab8b4ca73e0d71566fbb4af6dd0df11de0c37091351fdbf3cc3f5512c7f8','2026-07-24 17:40:18'),
('2026-07-07-02-language-system-bundles.sql','8eb775e7d821e129530517139f897bda9bfb51e1bda1ea68f31700566f3ab743','2026-07-24 17:40:18'),
('2026-07-09-01-document-pv-points.sql','e2b8b536f04bd495c7c12a1b7dfd7b83b944173ba4c2438f1691b8ae642abfdf','2026-07-24 17:40:18'),
('2026-07-09-02-event-location-and-document.sql','c673a6dd04d2283fbde1fd8323e87bbbaf7b35354f8aa59afa29fa3ddece6230','2026-07-24 17:40:18'),
('2026-07-09-03-document-event-link.sql','751e472953a5384924c26990ff3c43cbdbc8c5ba3c7579eaef459360378725ad','2026-07-24 17:40:18'),
('2026-07-09-04-event-invitations.sql','7a12992017b5459389425d22fa4db11612dd27d07c7be00a3f2161de4f24cb4f','2026-07-24 17:40:18'),
('2026-07-10-01-document-pv-point-handled.sql','dd56616ce56fb1cb09d02771c01008d79ea711c04b47a63be6166d9ff8d156fc','2026-07-24 17:40:18'),
('2026-07-10-02-document-pv-point-sync-lock.sql','b2790cd2e8e3539cf74271415c4052f8f734e466a0a7955401c0ad0d4c56851b','2026-07-24 17:40:18'),
('2026-07-10-03-document-pv-stage.sql','b8b48c4c20b6328be6c5ca6d99de4a965391772eee5aebc19dd2eadee68e6a17','2026-07-24 17:40:18'),
('2026-07-12-01-event-attendance.sql','ea1c7ac036e7e873009118f0f588d8dc141fbdc8b956306713fd3745541ece4a','2026-07-24 17:40:18'),
('2026-07-13-01-document-pv-editor.sql','e4cd05f2f4246eaa80c838b0576039297824c12475b8dd28669ef6dac7e777c6','2026-07-24 17:40:18'),
('2026-07-13-02-document-pv-point-external-author.sql','4cb9fed08611ea7d3a938a882397430f70c1fd025afba06a732e15a40513cd63','2026-07-24 17:40:18'),
('2026-07-13-03-resource-invitations.sql','9e46661c30b6713861096d6d06eb9cdacf4b21516eb31190e62783e7906f2a5e','2026-07-24 17:40:18'),
('2026-07-13-04-resource-attendance.sql','ec88779f11acd9196703e5a48dcecb6ca400e02e7cc365ca8f6dfc89cbab0717','2026-07-24 17:40:18'),
('2026-07-13-05-resource-invitation-accepted.sql','599e9fe978f4ce35ca7859806cf19f91251f423036c6865bb3f26708d933185e','2026-07-24 17:40:18'),
('2026-07-13-06-document-archive.sql','3a95d20bca1ad4a16c7b5236bbf3ae15750f2d47198ef02107b5dc287bcb56f8','2026-07-24 17:40:18'),
('2026-07-14-01-document-pv-point-groups.sql','b396ba0bc4fa8f60f7ff88020815678b570ddd84f6e9695c104416ad09601a36','2026-07-24 17:40:18'),
('2026-07-14-02-document-pv-templates.sql','8d29f7af5245d9524547f20a175fa9216ed976f5acd63f9c6ef3cb156770623b','2026-07-24 17:40:18'),
('2026-07-14-03-document-pv-editor-handover.sql','88a491833a865abab8078ea1b574a13e0f012fad76f7829bc1b6a696b53003cc','2026-07-24 17:40:18'),
('2026-07-15-01-stats-indicators.sql','5b694c38f6bd2ae2d3789075ad1bf00fbfea8fd3f93f8f873ed10bb61b637361','2026-07-24 17:40:18'),
('2026-07-15-02-stat-indicator-contexts.sql','a173961beaecc1f93664999f7dec693de1f079f918921c583f697928ad067829','2026-07-24 17:40:18'),
('2026-07-15-03-stat-indicator-schedule.sql','45db234d4676aa080d6fcb4ef85cb91de251fe8c1d75ae19018556a5ae669352','2026-07-24 17:40:18'),
('2026-07-15-04-topbar-search-period.sql','b7b4816957b2cd31cfb784e4435a226d594a7bd07b3a771f61d553b796cc4db9','2026-07-24 17:40:18'),
('2026-07-17-01-event-delete-permission.sql','ba594a8decf2f6fa35c82786e3e0a9f20d95e2a4d96cb653a663432b8011757c','2026-07-24 17:40:18'),
('2026-07-18-01-stat-indicator-group-references.sql','4311fdcfce27633ee11aade982b619c537d6d23d9c8d4cd0b24a0e4f3ab5a9e2','2026-07-24 17:40:18'),
('2026-07-19-01-projects.sql','95c5404d38cbf7a0b24b69d5541f0e1474e6993a485f1191da05f0e850ceac72','2026-07-24 17:40:18'),
('2026-07-20-01-project-size.sql','fe1c79abc4de215959b8f881c8e34dfefb8de07a262d8285e280a1d3804f13bd','2026-07-24 17:40:18'),
('2026-07-21-01-create-project-indicator-permissions.sql','4a2f33d6def8c121208ea11049ce83094054d84fa87c6638a8823b2ede4c8356','2026-07-24 17:40:18'),
('2026-07-21-01-property-composite-formats.sql','5a56bc0fdfd1b6037e00b766af90edf92c390622a50c672243dad3ce67d14fa2','2026-07-24 17:40:18'),
('2026-07-21-02-checklists.sql','98da47d7b77023987bbdee20a261b3d376eb8260f45c55dbbd5ae7c3cc3183e2','2026-07-24 17:40:18'),
('2026-07-21-03-checklist-manual-runs.sql','31d590239d0b262f11335b7857a5b1c1132ad4388e55c65a58ff6c14dbb8d6b3','2026-07-24 17:40:18'),
('2026-07-22-01-checklist-item-recurrence.sql','49e337d1e68457f23b7bb74ff4e6176f89208a25fb62843e86fef0c80d5939d8','2026-07-24 17:40:18'),
('2026-07-23-01-authorities-rules.sql','087c6022bed14bdef5180e69f355b1e090cdcda630946dc1f38cbb96f379bd32','2026-07-24 17:40:18'),
('2026-07-23-02-rule-scope-and-local-holon.sql','200a50494fce05daf4405c62826ab26ef51f71a87aba6fa07f50ab581c448e78','2026-07-24 17:40:18'),
('2026-07-23-03-policy-application.sql','5d6b6cad1303bd611d43fa181480b07d16b4e7ccacfc28487eb91ae3dfc7312f','2026-07-24 17:40:18'),
('2026-07-23-04-checklist-item-timing.sql','d34de0180b4ae4eca60c2c03130d10a092d823f1b6f66c7d3895d149d47b7f1f','2026-07-24 17:40:18'),
('2026-07-24-01-project-calculated-importance.sql','bf8e03beb0597cec009198a4f12fabaad3d570f49534d7b4a041787d55deb742','2026-07-24 17:40:18'),
('2026-07-24-01-stat-indicator-chart-lower-value.sql','b7309373bf75232aec5ba62ce4ed78a7e65c6a5777e19f26763fc028c5c4140e','2026-07-24 17:40:18'),
('2026-07-24-02-stat-indicator-group-chart-lower-value.sql','08dfef1e03de0432d8a418e8ae09149a0d4d4d5f79a62522ee0ddccffad36771','2026-07-24 17:40:18'),
('2026-07-25-01-organization-1-logo.sql','7b1751255c4321769fa6cc9e79f7c0e9055d32b49c8aca04dafe4b821aaaa131','2026-07-25 06:56:04'),
('2026-07-25-02-organization-1-banner.sql','7173d210f96f3916fbab43c98feae12b364339743ca6c0abe63932329801adf8','2026-07-25 07:09:54'),
('2026-07-25-03-organization-2-banner.sql','d027dfe52ba42044bafb3f8d107fb87b4d059542ba6564e96399191786f51866','2026-07-25 07:19:05'),
('2026-07-25-04-organization-2-logo.sql','7726d16a1d1016d292c4a7de61b908bd4306390fb8ab1a7fcc866314a913791c','2026-07-25 07:39:26'),
('2026-07-26-01-faq-application-link.sql','dd088e47fe64ec2d364d1a205c668f93ff0e062526ebd466d990bfdda09a826f','2026-07-26 06:02:18'),
('2026-07-26-01-repair-invitation-request-origin.sql','48a96b281e3a59cf8accfbe040f6b5de82d1d1d100113ffe875e73736a1c1140','2026-07-26 06:02:18'),
('2026-07-27-01-authority-description.sql','b3a345c4888120c5022a4d6f0b62dc3574ad07ac416532d43b543b9ba3690f83','2026-07-28 08:32:05'),
('2026-07-27-02-authority-complete-delegation.sql','2136e513a25be85f678ac49de2e03b9a6347dfcc868e2c2c325459c76bbb21ba','2026-07-28 08:32:05'),
('2026-07-27-03-rule-audit-users.sql','fe1491c4915966bd13d1690e018b066a397fe8ea712fd6960cd42a84be977021','2026-07-28 08:32:05'),
('2026-07-27-04-property-permissions.sql','311a415a817d19c6e6b3a22cf8805612460ef6b10d42363a50731946b981067e','2026-07-28 08:32:05'),
('2026-07-27-05-holon-permission-member-types.sql','ea17d5ff059198eb1a8af9af4ce4e7dba233de6dd53adcac6c6e93182342fb43','2026-07-28 08:32:05'),
('2026-07-28-01-holon-admin-bounds.sql','2eb1d7f42962c6f0d2e3015348a03675c5125b2b51e972608546e83f20a2b00b','2026-07-28 08:32:05'),
('2026-07-28-02-holon-admin-bound-locks.sql','272303e70b952fbf16342dc4eea4d9ddc1ab6730c9c1182fe4105caea21b413c','2026-07-28 08:32:05'),
('2026-07-28-03-holon-admin-parent-and-inheritance.sql','0c1b53be2bc5c921125d8ff1e1cd2ac122d59c3e0ba3f900a108bc29741246f','2026-07-28 08:32:05'),
('2026-07-30-01-rule-intention-nullable.sql','4d0bdaad48dafc4ae884ed68b55145e6cfb32c127bacd7ed46ba943d2feca6eb','2026-07-30 09:30:33'),
('2026-07-30-02-template-authority-instances.sql','d95dcad3522bd28daa6d9dc593c958db8fffd42311b18434fca5858419abf566','2026-08-31 14:05:21'),
('2026-07-31-01-generic-chat.sql','c4cd274e7d955e317714ecc3e545387dc8841feccbb2a9a131e5139cf333781c','2026-08-31 14:05:21'),
('2026-07-31-02-chat-message-anonymity.sql','aee4f3d6b161080104270b484cb241d15abbb287ccc4cca096f7086d364c3bfa','2026-08-31 14:05:21'),
('2026-07-31-03-relink-proposal-discussions.sql','72cbf0b858331c8c71042c47b29a3a13500368cd9f55417c5fc3c03012f55334','2026-08-31 14:05:21'),
('2026-08-01-01-stat-indicator-show-cumulative.sql','2466240fa92c89444356fe5897f57178fced22d4507257c140c61d0e6778334d','2026-08-31 14:05:21'),
('2026-08-01-01-telegram-chat-destinations.sql','be6dc827006378e4865d486f05651a7a2302bf5368a5057b17a42c4515537652','2026-08-31 14:05:21'),
('2026-08-01-02-faq-dav-sync.sql','4ca0e474ea51f8cf3af593b42744b08ccc9c2172d303b48ee043ff2dddbb7977','2026-08-31 14:05:21'),
('2026-08-01-03-telegram-chat-main-thread.sql','b807eff80e29675ebbd85dc8f7d127db8962faf02c60cbf8ab56b69107f53962','2026-08-31 14:05:21'),
('2026-08-01-04-document-etherpad.sql','e73bad10bd6258b189dadea0eba33d99ff97c791cd7331bd9cfd05ef80f8bcb6','2026-08-31 14:05:21'),
('2026-08-04-01-faq-scope-local-label.sql','3a11cfc6f8b67c6842d0b9b02a3c1922d121dfef6ffcffddb706fbb5fa3e7c0e','2026-08-31 14:05:21'),
('2026-08-04-02-event-project.sql','30ff145a17d6eef8ab6a192b0ecef5326167539731437b7d30f2ebf1513fe4ed','2026-08-31 14:05:21'),
('2026-08-04-03-decision-guest-proposal-discussions.sql','fb46a0bc812ddbc05e5971a8f8d0842dafbd4f3ff1ffcea0d9034563b74b37db','2026-08-04 13:33:53'),
('2026-08-05-01-checklist-permissions.sql','ff5c971e979b0acb781ff48bc4fb4f84b08d0e8bbfcc1f96a17b8464b0074d75','2026-08-31 14:05:21'),
('2026-08-06-01-notification-push-subscriptions.sql','0aab0c0964b59eaacc50e7f7daa9d95f1bf0d56f22e559e590180f82703f1088','2026-08-31 14:05:21'),
('2026-08-06-02-notification-center.sql','9edf0c31a20ae319009e166d9f4ea51de3dc90520624f3d7872947ed0e066fa5','2026-08-31 14:05:21'),
('2026-08-06-03-notification-open-links.sql','a503112e8c0a9937c2c780a298b41451f3f322464cf18414d277e5893be2ca1e','2026-08-31 14:06:19'),
('2026-08-06-04-notification-deduplication.sql','57a2f80375be6489a52f0002ac34f01e0a04d621be0c945d1b3aa9e19e788794','2026-08-31 14:06:34'),
('2026-08-07-01-delete-project-permission.sql','df5e7c22fd99e68b92848a24aca78307cebc143873c4dd91b0b534e14d7a3ca2','2026-08-31 14:06:34'),
('2026-08-09-01-document-ethercalc.sql','6c62fd01587e4567a703ea767c56d84cfa87e0ef851e2d508009d98237eeaf6b','2026-08-31 14:06:35'),
('2026-08-09-02-stat-indicator-ethercalc-sources.sql','d433a12fa75416a582a02c0383233adcf6746a7e04ed980ea8ef4382e96e70b8','2026-08-31 14:06:54'),
('2026-08-09-03-stat-indicator-catalog-visibility.sql','0769a51d7dc4783677ef957e8cc892af2afa6dd978c043e0d775b8584911bd15','2026-08-31 14:06:54'),
('2026-08-09-04-stat-indicator-group-source-visibility.sql','17abf53ce24eed66b2ce2995d06485c72ee7fbcdc71ced594f2e1dd82ebc7732','2026-08-31 14:06:54'),
('2026-08-11-01-user-holon-focus.sql','5f6a151da9abba2581115a4a7ff3bc2ae747a9e1b77a02d6b1e177c4d55ceb3e','2026-08-11 05:45:46'),
('2026-08-12-01-notification-reminder-preferences.sql','c85412b057342913f0f95146a6af8307735975b47bc287c665d84a409e683c06','2026-08-31 14:07:06'),
('2026-08-12-02-decision-governance-actions.sql','ebf225d27991017aa48aad0d4350cd392524da70cd5f772e893ba33b52508062','2026-08-31 14:07:06'),
('2026-08-12-03-document-pv-point-confidential.sql','9d22973b2b876926ceb8c4e03d56fc9e5da0686f00aef7a8417cea40bffab181','2026-08-31 14:07:06'),
('2026-08-17-01-project-archive-date.sql','14809bcd5fd43ab354f0b096bd424aaa437272b04581e17fb53870ce602fd672','2026-08-31 14:07:28'),
('2026-08-19-01-organization-interface-level.sql','b3eef35b979ae06cde501b39bd21b9c85794c8b598b49726d28b4d2dc8dac71e','2026-08-31 14:07:28'),
('2026-08-20-01-holon-permissions.sql','694dba6a21ead68fee72bc5bab426c82bd1bd604c4f24d43b3a2708d873c0e0e','2026-08-31 14:07:28'),
('2026-08-20-02-event-edit-permission.sql','9c88357ee2a1f93429dabe773f6c7f25a80c27c5149473cba877f0a10ce0232a','2026-08-31 14:07:28'),
('2026-08-21-01-document-pv-review-editor.sql','e4fa6e596d4e51227043d4b071726d90d2dba4ef5713a8f01993045d6c99df73','2026-08-31 14:07:28'),
('2026-08-24-01-rename-checklist-application.sql','c078b0e05ecf686b59fae649cefae9c2815c376dc0c0e587926bb8a1a99a39cd','2026-08-31 14:07:28'),
('2026-08-24-02-project-document-holon-visibility.sql','8ed155f1679fb362cf6c56c4cdfb7938719e1a07e631aaa9eeff1a7b5786bf7b','2026-08-31 14:07:28'),
('2026-08-24-03-stat-indicator-spreadsheet-sources.sql','73e6ec06429d10702976dd951f168bab1456cf190c243e2c2f94ce50ea7c7541','2026-08-31 14:07:28'),
('2026-08-26-01-stats-indicator-translations.sql','83f4858ee26670f90d6e9e9ac41451eeb71c68388aa95016f7f4f4cdac4a98dc','2026-08-31 14:07:28'),
('2026-08-27-01-document-spacedeck.sql','591901cee0d480539b9d80e660d81b1345d5562aa346f48fb6234854eb27d2a4','2026-08-31 14:07:28'),
('2026-08-28-01-user-holon-assignment-budgets.sql','6f3c83a0d120b54d193eb09e1af63d2ce61ea305c1ffe6668e4c8c533fc499e8','2026-08-31 14:07:28'),
('2026-08-28-02-user-holon-assignment-review-date.sql','5cd91b93bc74ec5a749c50828dc791ba2759c985bcf516822e0d539fb654e7d5','2026-08-31 14:07:28'),
('2026-08-29-01-organizational-maturity-assessments.sql','bcb4dbf54b8989665bb86fc96a524c1b5bdd813e40ccd79074d5eb9a95498d55','2026-08-29 07:20:44'),
('2026-08-29-02-organizational-maturity-invitations.sql','32ccf6997135a054ce4a76dba6f39b53567b108a7de6208f9a9e7cc698609e74','2026-08-29 08:09:41'),
('2026-08-31-01-user-holon-membership-flag.sql','0fe590d08f887e04c20399ddf84ccfa34f70ea2d643a263c71e9144b4f685ece','2026-08-31 14:07:28'),
('2026-08-31-02-holon-dashboard-parameters.sql','dab4cd0e88ecb88eef211ef4c8b136ecbc0265bb8e06cb48f36ecb1662a796b7','2026-08-31 14:07:28'),
('2026-08-31-03-application-dashboard-default.sql','a5e8b7f6a702609813f86dd3cb169a79b6968bfd65596efc851e0298ca0bb02d','2026-08-31 14:07:28'),
('2026-08-31-04-processus-terminology.sql','dcf88706e11f27af4e2a34e65ffbb1393be55f951550c064e55e5cb4be1afb4c','2026-08-31 14:07:28'),
('2026-08-31-05-activities.sql','078bc1418362c512aae1e0c3243a60570514803d1aa21d5f7ae275126dfdb24e','2026-09-18 09:12:47'),
('2026-08-31-07-activity-permissions-and-app.sql','a9f7f52df952301b7e033c079ce61ea2376d806822137f66535d35d58057767f','2026-08-31 14:19:31'),
('2026-09-02-01-document-consultation.sql','4b9727c980ea4bf4347afbafe14f32ada28b6e219026f9038bdbabf523ca1b82','2026-09-03 12:12:47'),
('2026-09-03-01-work-time.sql','518d2d7c3f51b02584b1c67254d56e18a2b45e7ecb25131ee1de5cdbd3415207','2026-09-03 12:12:47'),
('2026-09-04-01-auth-rate-limit.sql','a952bced60f9a07d000ca45952341b9af37cfa257bb4bfcbd3bb8b52806b768e','2026-09-08 09:10:16'),
('2026-09-04-02-user-password-login-permission.sql','49ca1c9622046f3ded94b24a61e981db20d3fbdea979041bd07ce1d54993c9ec','2026-09-08 09:10:16'),
('2026-09-04-03-user-totp-authentication.sql','5136fe39414466e24ef6593a51582fa70559cb1158e407ff7a5cea98d6f38c46','2026-09-08 09:10:16'),
('2026-09-04-04-document-share-link-pv-contribution.sql','763476d5f30a2b376ae33a5999b06c9642302e7084916f7ced6364997280ab54','2026-09-08 09:10:16'),
('2026-09-05-01-pv-participation-chat.sql','a5acf45474252b94fac7de5c96a013ab1769f8fb7dc3767335df919e4e3c4572','2026-09-08 09:10:16'),
('2026-09-05-02-document-application-tabs.sql','e92e21a816056ee017bece9d1ef451fbfcd848257acd660a0494cad26325ab07','2026-09-05 11:32:38'),
('2026-09-07-01-budget-application.sql','adb91c3868163fad168359ab5d54bbab9cd49a00e70815d6f5bea6adb97c95cd','2026-09-08 09:10:16'),
('2026-09-07-02-decision-proposal-nullable-title.sql','e6e4a1ce3484bc56d066d1c94c2f5ee6cdc4dbe92016234f273681245915f160','2026-09-08 09:10:16'),
('2026-09-07-03-holon-budgets.sql','3f4d9fc7ff0c14b7c54157fcbd69765afc2f0c02808ce754c406b289051eca04','2026-09-08 09:10:16'),
('2026-09-07-04-budget-permissions.sql','a2f97cdf8de3d4bfb4584137fb697c8ee3008b89a9f948739fc4a6b5f72885f6','2026-09-08 09:10:16'),
('2026-09-08-01-work-time-label.sql','a5048a443a532a3ff88033c9c7e5d19238df1d2813d7696a4c957b3f8945e82c','2026-09-08 09:10:16'),
('2026-09-08-02-project-blocked-details.sql','3dc03722ff247b7062482af5aa551b1fa1a1d3e30116814ab3e49d7dfaf0d005','2026-09-08 09:10:17'),
('2026-09-08-03-history-generic-target.sql','ad03c4aaf626d7d5602c1c1b01339a065aedc6ff11fdf4789094f1e9e23c669e','2026-09-08 12:43:41'),
('2026-09-08-04-history-target-centralization.sql','9e7d0ccc9643b55554db04e8e46ec0e4207484ef41dc544652bd6ae9a6ef47f2','2026-09-08 12:52:33'),
('2026-09-09-01-project-proposals.sql','2b069cf221507bb9597c92be8e22378c383efdc507b9f07d9b72f863b8355d34','2026-09-09 09:32:21'),
('2026-09-09-02-project-proposal-normal-status.sql','2f8c0914de5fc72556b1c75050af30e890c092b9befcd2a27c319a510b39d2d0','2026-09-09 09:52:03'),
('2026-09-10-01-document-nextcloud-folders.sql','c405ba1de17818ae7b68bf86571e16d1af4aaf37d61596a780bb4573a7643dc9','2026-09-10 16:24:54'),
('2026-09-10-01-stats-automatic-indicator-frequencies.sql','ebd59c2869bb9cd827f69234f26e3cc3aa23c135245c81f305fa8cefa5d56e7a','2026-09-10 16:24:54'),
('2026-09-10-02-document-nextcloud-folder-fileid.sql','848f3ec37fc0325f6e3b40e84e222bdea0290367b31bc494931267f751e09952','2026-09-10 16:24:54'),
('2026-09-11-01-document-edit-visibility.sql','3e13428e9d4471168cad2a60aadd3cdc1576c919482685d06da1dec30b68ecce','2026-09-16 07:54:26'),
('2026-09-11-02-document-pv-point-priority.sql','8aad46d9661e8058fdf2f5b14301a656e3bda813e30889e247ce7b040bb8d66f','2026-09-16 07:54:26'),
('2026-09-15-01-caldav-sync-changes.sql','0ff80c6fea08d704a96b4212882901a619f37a578ff659afcef01e41bbe0e476','2026-09-16 07:54:26'),
('2026-09-15-01-organizational-maturity-public-links.sql','3dca56570e3b136bc03d31d1708bbc19756ae287e0d177e31f4c3eb3f16d04a9','2026-09-16 07:54:26'),
('2026-09-15-02-organizational-maturity-public-access-codes.sql','19e4fcf9fc4b1606390777f189e4ab2de0c0b302f2909f8f41409254ea1936f8','2026-09-16 07:54:26'),
('2026-09-15-03-organizational-maturity-assessment-drafts.sql','1f6e1f9927b1da95778aec8f6c64bc1814f44b1c189be363a255c672c5ead8fa','2026-09-16 07:54:26'),
('2026-09-16-01-external-calendar.sql','db5f76230ef41bde16688095c42c3cc1ee9d0c7a78d6cbb9871d1962d5683d29','2026-09-18 09:12:47'),
('2026-09-16-01-user-phone.sql','0527cc020d91b4ac5e22ff69f1e3a07d9665dfcd33d6bdd874426451ceb1351f','2026-09-18 09:12:47'),
('2026-09-16-02-external-calendar-sync-version.sql','d424254e1a3a91b8d677f8669a9530f43b33cbeab620256557333395f51325b3','2026-09-28 16:39:57'),
('2026-09-16-03-meeting.sql','5dabc9fa660d3da4b06b4678b6edd0469c6ec664a1468891da09e570ac0bb77a','2026-09-16 11:17:01'),
('2026-09-16-04-calendar-share.sql','cb4e341b1e90e35eb33003f8c1da4fc2393c05c18e552c8953e54c29983c15fc','2026-09-16 17:29:36'),
('2026-09-17-01-indicator-checklist-responsible.sql','e49c6916688ec1cae8517efdf45d391a85518b1479442869b368b9ae97177e62','2026-09-28 16:39:57'),
('2026-09-17-01-object-crud-permissions.sql','55cf5d6caca461bf1f68a678602c02cf0237c2222f31f171f753c58f1bb05938','2026-09-17 08:28:39'),
('2026-09-17-02-control-activity-responsible.sql','c74c319311d56850f43d079a7c69a83234c7156430ab16d55be2fa25bb860870','2026-09-28 16:39:57'),
('2026-09-17-02-holon-unassigned-color.sql','6989cee1b7af865a6e3f1e7763b77177677c686f1cbb9efd2e134cca79f4b3df','2026-09-28 16:39:57'),
('2026-09-17-03-remove-holon-banner.sql','f8e92acd5396f8c82305a49dc00e855ffd2334de877be494912fedee423c875c','2026-09-28 16:39:57'),
('2026-09-17-04-remove-legacy-control-lists.sql','dec2b6eeb76614f734f8ebf1547ee40c36ca51cff52d8be4a1897894de824d82','2026-09-17 10:45:40'),
('2026-09-17-05-checklist-overlap-policies.sql','29dad525dc82abd34f650294693b5a6a44660b85d485f470f5e5a453281acae2','2026-09-17 11:01:04'),
('2026-09-17-06-pv-point-lock-takeover.sql','893773fb3804860af5e57cba9809bea7b1ebe25e8126515894f48b38e9902d11','2026-09-28 16:39:57'),
('2026-09-18-01-project-followers.sql','76a0b44b3bf29286bf00a4b14821213d4d06af59b5713a924739540c766a60b7','2026-09-18 09:13:34'),
('2026-09-19-01-stat-indicator-reference-scale.sql','e15288ba43d94545ebb9950e21218acc801b10ca5bd59e6e70d7239b1778a3a1','2026-09-21 15:58:04'),
('2026-09-21-01-processes-recurring-tasks.sql','66e44f92f174a69fa09e17d68c545ffa0925f21f7beca741be8dde1cce8ec1d9','2026-09-28 16:39:57'),
('2026-09-21-02-organization-public-model.sql','04d2dae9790e587a77d8b4f31ae49c0ec18192f72f44b380fbc27338795fda0a','2026-09-28 16:39:57'),
('2026-09-21-03-process-and-recurring-task-permissions.sql','8290dfc8eee8dcba28f640e6e5b68300ba60a514d8bdc487f953665784cab074','2026-09-21 15:38:46'),
('2026-09-21-04-recurring-task-responsible.sql','5cad2ee81da1f6a202df760e96cf72c006994c18bee7197ea42025a1589eb14e','2026-09-21 15:46:31'),
('2026-09-21-05-calendar-scoped-ics.sql','dec39ee9ed8725b7e3bb7812152b2691853c4f2bc5ca9bdf17ec1719115161b0','2026-09-28 16:39:57'),
('2026-09-22-01-deferred-proposals.sql','4cc0d275d33e8077c39abfb73351dc48ef019edaca1819f1e9ce85a0be1bfa76','2026-09-22 06:06:25'),
('2026-09-24-01-patreon-oauth-transactions.sql','a5d660611c32446f47829d995dcfbed426c0eb4188bdd92396aa2b5f6b3e9590','2026-09-28 16:40:04'),
('2026-09-24-02-organization-rules.sql','ef669bccdc68535e589045eaa68261d4b22a9847c521a92ee0d19cd3484d0582','2026-09-28 16:40:04'),
('2026-09-24-03-faq-recent-features.sql','5f078695d4444fb4636da2c0fe5229cf2dacb8936782f5cc0ec917bfed8a5545','2026-09-28 16:40:04'),
('2026-09-24-04-faq-questions-utilisateurs.sql','ac0b06bad0e67dc9ca8f583fbceab786eea9c4353dea75153251d95ace62ee63','2026-09-28 16:40:04'),
('2026-09-24-05-faq-reponses-en-attente.sql','37f4a31eb7657b9dcd328dc7076f5fd2397472414a29684cef606bae33256f3e','2026-09-28 16:40:04'),
('2026-09-24-06-faq-question-relay.sql','f6a8b8a2053095ac260013804725700c96373bd7f74c1801b36b1315f0080ed6','2026-09-28 16:40:04'),
('2026-09-24-07-faq-ai-draft.sql','758faf91d29567cc4b63071dc9a5b7af6329e8b53e6e90edd36034646c7a3db8','2026-09-28 16:40:05'),
('2026-09-25-01-move-holon-permission.sql','70d5e63db12304078608f91929e0a09ebbb802c07623e27da58b4183cef86c6b','2026-09-28 16:40:05'),
('2026-09-25-02-extended-authorities.sql','a7ca959ae447c7aba5ecb8c2902ecd708debc5256150e75a49424affdc5caaf9','2026-09-28 16:40:05'),
('2026-09-25-03-authority-label-mediumtext.sql','e887d0c9f45e16f3408b2b90c5debdaaf02e396ba36aaee5410fa5cb4a078097','2026-09-28 16:40:05'),
('2026-09-25-04-rule-circle-scope.sql','5b548c37b26c3d619dc94d571530ce369c6f0270e554fb8928f0a97d5a522778','2026-09-28 16:40:05'),
('2026-09-28-01-property-types.sql','2d123a09355ca7e63639605514da091a9cef4d3f68e63c0441625b87a043f45e','2026-09-28 16:40:05'),
('2026-09-28-02-property-permission-scopes.sql','ce15fc201f33b750ccfe97dc1663eb2ebc11c815028832d9b2cff92cc39f38ae','2026-09-28 16:40:05'),
('2026-09-28-03-restore-parcours-permissions.sql','37e3d92db86b7124687579601484f88b2242278229757cd121cda79eeb698112','2026-09-28 16:40:05'),
('2026-09-28-04-parcours-delete-permission-and-archive.sql','00a06e2c1e50030546ff3fd086f3bb5033b0b7550e0eda9b0d08318d053bd927','2026-09-28 16:40:05'),
('2026-09-28-05-property-type-activation.sql','81dd743356f3e3423f20b76441102a132825e47a6c5cd0e4538a8220df4063c7','2026-09-28 16:40:05');
/*!40000 ALTER TABLE `sql_migration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stat_indicator`
--

DROP TABLE IF EXISTS `stat_indicator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stat_indicator` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `IDuser_responsible` int(11) DEFAULT NULL,
  `IDdocument` int(11) DEFAULT NULL,
  `name` varchar(190) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `source_url` varchar(2000) DEFAULT NULL,
  `source_type` varchar(30) NOT NULL DEFAULT 'manual',
  `ethercalc_cell` varchar(20) DEFAULT NULL,
  `ethercalc_frequency` varchar(20) DEFAULT NULL,
  `ethercalc_range` varchar(40) DEFAULT NULL,
  `ethercalc_date_column` varchar(10) DEFAULT NULL,
  `ethercalc_value_column` varchar(10) DEFAULT NULL,
  `ethercalc_last_sync_at` datetime DEFAULT NULL,
  `spreadsheet_sheet` varchar(190) DEFAULT NULL,
  `spreadsheet_cell` varchar(20) DEFAULT NULL,
  `spreadsheet_frequency` varchar(20) DEFAULT NULL,
  `spreadsheet_range` varchar(40) DEFAULT NULL,
  `spreadsheet_date_column` varchar(10) DEFAULT NULL,
  `spreadsheet_value_column` varchar(10) DEFAULT NULL,
  `spreadsheet_last_sync_at` datetime DEFAULT NULL,
  `reference_type` varchar(20) NOT NULL DEFAULT 'none',
  `reference_scale` varchar(20) NOT NULL DEFAULT 'cumulative',
  `measurement_frequency` varchar(20) DEFAULT NULL,
  `measurement_schedule` varchar(20) DEFAULT NULL,
  `chart_min_value` decimal(20,6) DEFAULT NULL,
  `show_cumulative` tinyint(1) NOT NULL DEFAULT 0,
  `hide_from_catalog` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stat_indicator_organization` (`IDorganization`),
  KEY `idx_stat_indicator_holon` (`IDholon`),
  KEY `idx_stat_indicator_user` (`IDuser`),
  KEY `idx_stat_indicator_active` (`active`),
  KEY `idx_stat_indicator_measurement_frequency` (`measurement_frequency`),
  KEY `idx_stat_indicator_source_sync` (`source_type`,`active`,`ethercalc_last_sync_at`),
  KEY `idx_stat_indicator_document` (`IDdocument`),
  KEY `idx_stat_indicator_catalog_visibility` (`active`,`hide_from_catalog`),
  KEY `idx_stat_indicator_spreadsheet_sync` (`source_type`,`active`,`spreadsheet_last_sync_at`),
  KEY `idx_stat_indicator_responsible` (`IDuser_responsible`),
  CONSTRAINT `fk_stat_indicator_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stat_indicator_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stat_indicator_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stat_indicator_responsible` FOREIGN KEY (`IDuser_responsible`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stat_indicator_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1415 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stat_indicator`
--

LOCK TABLES `stat_indicator` WRITE;
/*!40000 ALTER TABLE `stat_indicator` DISABLE KEYS */;
/*!40000 ALTER TABLE `stat_indicator` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stat_indicator_group`
--

DROP TABLE IF EXISTS `stat_indicator_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stat_indicator_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `name` varchar(190) NOT NULL,
  `display_mode` varchar(20) NOT NULL DEFAULT 'overlay',
  `reference_type` varchar(20) NOT NULL DEFAULT 'none',
  `chart_min_value` decimal(20,6) DEFAULT NULL,
  `hide_same_holon_sources` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stat_indicator_group_context` (`IDorganization`,`IDholon`,`active`),
  KEY `idx_stat_indicator_group_user` (`IDuser`),
  KEY `fk_stat_indicator_group_holon` (`IDholon`),
  KEY `idx_stat_indicator_group_source_visibility` (`active`,`hide_same_holon_sources`,`IDholon`),
  CONSTRAINT `fk_stat_indicator_group_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stat_indicator_group_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stat_indicator_group_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stat_indicator_group`
--

LOCK TABLES `stat_indicator_group` WRITE;
/*!40000 ALTER TABLE `stat_indicator_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `stat_indicator_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stat_indicator_group_item`
--

DROP TABLE IF EXISTS `stat_indicator_group_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stat_indicator_group_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDstatindicatorgroup` int(11) NOT NULL,
  `IDstatindicator` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_stat_indicator_group_item` (`IDstatindicatorgroup`,`IDstatindicator`),
  KEY `idx_stat_indicator_group_item_position` (`IDstatindicatorgroup`,`position`),
  KEY `idx_stat_indicator_group_item_indicator` (`IDstatindicator`),
  CONSTRAINT `fk_stat_indicator_group_item_group` FOREIGN KEY (`IDstatindicatorgroup`) REFERENCES `stat_indicator_group` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stat_indicator_group_item_indicator` FOREIGN KEY (`IDstatindicator`) REFERENCES `stat_indicator` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stat_indicator_group_item`
--

LOCK TABLES `stat_indicator_group_item` WRITE;
/*!40000 ALTER TABLE `stat_indicator_group_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `stat_indicator_group_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stat_indicator_import`
--

DROP TABLE IF EXISTS `stat_indicator_import`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stat_indicator_import` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDstatindicator` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stat_indicator_import_context` (`IDorganization`,`IDholon`,`active`),
  KEY `idx_stat_indicator_import_indicator` (`IDstatindicator`),
  KEY `fk_stat_indicator_import_holon` (`IDholon`),
  CONSTRAINT `fk_stat_indicator_import_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stat_indicator_import_indicator` FOREIGN KEY (`IDstatindicator`) REFERENCES `stat_indicator` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stat_indicator_import_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stat_indicator_import`
--

LOCK TABLES `stat_indicator_import` WRITE;
/*!40000 ALTER TABLE `stat_indicator_import` DISABLE KEYS */;
/*!40000 ALTER TABLE `stat_indicator_import` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stat_indicator_reference_point`
--

DROP TABLE IF EXISTS `stat_indicator_reference_point`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stat_indicator_reference_point` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDstatindicator` int(11) DEFAULT NULL,
  `IDstatindicatorgroup` int(11) DEFAULT NULL,
  `position_percent` decimal(7,4) NOT NULL,
  `value` decimal(20,6) NOT NULL,
  `point_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_stat_indicator_reference_position` (`IDstatindicator`,`position_percent`),
  UNIQUE KEY `uniq_stat_indicator_reference_group_position` (`IDstatindicatorgroup`,`position_percent`),
  KEY `idx_stat_indicator_reference_indicator` (`IDstatindicator`),
  KEY `idx_stat_indicator_reference_group` (`IDstatindicatorgroup`),
  CONSTRAINT `fk_stat_indicator_reference_indicator` FOREIGN KEY (`IDstatindicator`) REFERENCES `stat_indicator` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stat_indicator_reference_point_group` FOREIGN KEY (`IDstatindicatorgroup`) REFERENCES `stat_indicator_group` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stat_indicator_reference_point`
--

LOCK TABLES `stat_indicator_reference_point` WRITE;
/*!40000 ALTER TABLE `stat_indicator_reference_point` DISABLE KEYS */;
/*!40000 ALTER TABLE `stat_indicator_reference_point` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stat_indicator_value`
--

DROP TABLE IF EXISTS `stat_indicator_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stat_indicator_value` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDstatindicator` int(11) NOT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `value` decimal(20,6) NOT NULL,
  `measured_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stat_indicator_value_indicator_date` (`IDstatindicator`,`measured_at`),
  KEY `idx_stat_indicator_value_user` (`IDuser`),
  CONSTRAINT `fk_stat_indicator_value_indicator` FOREIGN KEY (`IDstatindicator`) REFERENCES `stat_indicator` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stat_indicator_value_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=28591 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stat_indicator_value`
--

LOCK TABLES `stat_indicator_value` WRITE;
/*!40000 ALTER TABLE `stat_indicator_value` DISABLE KEYS */;
/*!40000 ALTER TABLE `stat_indicator_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telegram_chat_destination`
--

DROP TABLE IF EXISTS `telegram_chat_destination`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telegram_chat_destination` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `telegram_chat_id` varchar(32) NOT NULL,
  `telegram_thread_id` varchar(32) NOT NULL DEFAULT '__main__',
  `IDorganization` int(11) NOT NULL,
  `destination_type` varchar(20) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDproject` int(11) DEFAULT NULL,
  `IDuser_configured` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_telegram_chat_destination` (`telegram_chat_id`,`telegram_thread_id`),
  KEY `idx_telegram_destination_organization` (`IDorganization`),
  KEY `idx_telegram_destination_holon` (`IDholon`),
  KEY `idx_telegram_destination_project` (`IDproject`),
  KEY `idx_telegram_destination_user` (`IDuser_configured`),
  CONSTRAINT `fk_telegram_destination_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_telegram_destination_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_telegram_destination_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_telegram_destination_user` FOREIGN KEY (`IDuser_configured`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telegram_chat_destination`
--

LOCK TABLES `telegram_chat_destination` WRITE;
/*!40000 ALTER TABLE `telegram_chat_destination` DISABLE KEYS */;
/*!40000 ALTER TABLE `telegram_chat_destination` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tension`
--

DROP TABLE IF EXISTS `tension`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tension` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `IDuser` int(11) NOT NULL,
  `title` varchar(80) NOT NULL,
  `description` text NOT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_tension_organization` (`IDorganization`),
  KEY `idx_tension_holon` (`IDholon`),
  KEY `idx_tension_user` (`IDuser`),
  KEY `idx_tension_creation` (`datecreation`),
  KEY `idx_tension_active` (`active`)
) ENGINE=InnoDB AUTO_INCREMENT=9303 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tension`
--

LOCK TABLES `tension` WRITE;
/*!40000 ALTER TABLE `tension` DISABLE KEYS */;
/*!40000 ALTER TABLE `tension` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tips`
--

DROP TABLE IF EXISTS `tips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tips` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `youtube` varchar(500) DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tips`
--

LOCK TABLES `tips` WRITE;
/*!40000 ALTER TABLE `tips` DISABLE KEYS */;
/*!40000 ALTER TABLE `tips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `translation_bundle_refresh_jobs`
--

DROP TABLE IF EXISTS `translation_bundle_refresh_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `translation_bundle_refresh_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bundle_key` varchar(190) NOT NULL,
  `locale` varchar(10) NOT NULL,
  `source_hash` char(64) NOT NULL,
  `source_json` longtext NOT NULL,
  `status` enum('pending','running','failed','completed') NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_error` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bundle_locale_hash` (`bundle_key`,`locale`,`source_hash`),
  KEY `idx_status_created` (`status`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation_bundle_refresh_jobs`
--

LOCK TABLES `translation_bundle_refresh_jobs` WRITE;
/*!40000 ALTER TABLE `translation_bundle_refresh_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `translation_bundle_refresh_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `translation_bundles`
--

DROP TABLE IF EXISTS `translation_bundles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `translation_bundles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bundle_key` varchar(190) NOT NULL,
  `locale` varchar(10) NOT NULL,
  `source_hash` char(64) NOT NULL,
  `translated_json` longtext NOT NULL,
  `status` enum('machine_translated','approved','outdated') NOT NULL DEFAULT 'machine_translated',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bundle_locale` (`bundle_key`,`locale`),
  KEY `idx_bundle_locale_hash` (`bundle_key`,`locale`,`source_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation_bundles`
--

LOCK TABLES `translation_bundles` WRITE;
/*!40000 ALTER TABLE `translation_bundles` DISABLE KEYS */;
INSERT INTO `translation_bundles` VALUES
(1,'omo_index_page','en','99f2a401887e9cdb2832728667434150979e96a5dec67b7f0e6e6fd29c708d41','{\"app.access_denied.message\":{\"text\":\"Your account is connected, but it does not yet have access to the organization {organizationName}.\"},\"app.access_denied.organization_fallback\":{\"text\":\"requested\"},\"app.access_denied.page_description\":{\"text\":\"For now, access to this space is reserved for people on the list of authorized members.\"},\"app.access_denied.page_heading\":{\"text\":\"Access Denied\"},\"app.access_denied.page_title\":{\"text\":\"Access Denied - OMO\"},\"app.access_denied.request_action\":{\"text\":\"Request Access\"},\"app.access_denied.request_modal_title\":{\"text\":\"Request Access to the Organization\"},\"app.access_denied.request_pending\":{\"text\":\"Request Already Sent\"},\"app.access_denied.request_pending_notice\":{\"text\":\"A request is already pending with the administrators of this organization.\"},\"app.directory.create.action\":{\"text\":\"Open Form\"},\"app.directory.create.aria_label\":{\"text\":\"Create a New Organization\"},\"app.directory.create.badge\":{\"text\":\"New\"},\"app.directory.create.description\":{\"text\":\"Name, domain, logo, banner, color\"},\"app.directory.create.modal_title\":{\"text\":\"Create a New Organization\"},\"app.directory.create.title\":{\"text\":\"Create a New Organization\"},\"app.directory.cta.connect\":{\"text\":\"Connect\"},\"app.directory.cta.view_invitation\":{\"text\":\"View Invitation\"},\"app.directory.description.empty\":{\"text\":\"Your account is connected, but it is not attached to any organization at the moment. You can create a new one below.\"},\"app.directory.description.empty.patreon_connect\":{\"text\":\"Your account is connected, but it is not attached to any organization at the moment. Connect Patreon below to create a new one.\"},\"app.directory.description.with_results\":{\"text\":\"Choose the organization you want to open. Each card redirects you to its dedicated space.\"},\"app.directory.fallback_badge\":{\"text\":\"OMO Space\"},\"app.directory.fallback_organization_name\":{\"text\":\"Organization\"},\"app.directory.heading\":{\"text\":\"Your OMO Spaces\"},\"app.directory.import.action\":{\"text\":\"Choose an Export\"},\"app.directory.import.aria_label\":{\"text\":\"Import an Existing Organization\"},\"app.directory.import.badge\":{\"text\":\"Migration\"},\"app.directory.import.description\":{\"text\":\"Structure, members, documents, projects, and calendar\"},\"app.directory.import.modal_title\":{\"text\":\"Import an Organization\"},\"app.directory.import.title\":{\"text\":\"Import an Organization\"},\"app.directory.invitation.badge\":{\"text\":\"Pending Invitation\"},\"app.directory.invitation.pending_holons\":{\"one\":\"{count} holon pending\",\"other\":\"{count} holons pending\"},\"app.directory.invitation.pending_organization\":{\"text\":\"Access to Confirm\"},\"app.directory.js.action_error\":{\"text\":\"Action not possible.\"},\"app.directory.js.default_organization_name\":{\"text\":\"this organization\"},\"app.directory.js.delete_confirm\":{\"text\":\"Delete {organizationName}?\\n\\nThe structure, members, circles, roles, shares, and related documents will be deleted.\"},\"app.directory.js.leave_confirm\":{\"text\":\"Leave {organizationName}?\\n\\nYour links with the organization, its circles, and roles will be removed.\"},\"app.directory.menu.actions_aria_label\":{\"text\":\"Actions for {organizationName}\"},\"app.directory.menu.delete\":{\"text\":\"Delete\"},\"app.directory.menu.leave\":{\"text\":\"Leave\"},\"app.directory.menu.system_organization_notice\":{\"text\":\"This base organization is used by the system for messages and tutorials. It cannot be deleted, and its administrators cannot leave it.\"},\"app.directory.modal.close\":{\"text\":\"Close\"},\"app.directory.open_organization_aria_label\":{\"text\":\"Open {organizationName} Space\"},\"app.directory.page_title\":{\"text\":\"Your OMO Spaces\"},\"app.directory.patreon_connect.action\":{\"text\":\"Connect with Patreon\"},\"app.directory.patreon_connect.aria_label\":{\"text\":\"Connect your Patreon Profile\"},\"app.directory.patreon_connect.badge\":{\"text\":\"Patreon Required\"},\"app.directory.patreon_connect.description\":{\"text\":\"Connect Patreon to unlock organization creation\"},\"app.directory.patreon_connect.title\":{\"text\":\"Connect Patreon\"},\"app.directory.status.available\":{\"one\":\"{count} organization available\",\"other\":\"{count} organizations available\"},\"app.directory.status.none\":{\"text\":\"No organization at the moment\"},\"app.directory.template.badge\":{\"text\":\"Shared Template\"},\"app.directory.templates.heading\":{\"text\":\"Your Organization Templates\"},\"app.login.intro\":{\"text\":\"Log in to access the structure and governance tools.\"},\"app.login.page_title\":{\"text\":\"{organizationName} - OMO\"},\"app.main.page_title\":{\"text\":\"Governance UI\"},\"app.mobile.context\":{\"text\":\"Context\"},\"app.mobile.menu\":{\"text\":\"Tools\"},\"app.mobile.right_panel\":{\"text\":\"Summary\"},\"app.not_found.message\":{\"text\":\"The requested organization does not exist or is no longer available.\"},\"app.not_found.page_description\":{\"text\":\"You can return to the OMO home and choose another space.\"},\"app.not_found.page_heading\":{\"text\":\"Organization Not Found\"},\"app.not_found.page_title\":{\"text\":\"Organization Not Found - OMO\"},\"app.patreon.prompt_title\":{\"text\":\"Support the Project\"},\"app.user.demo\":{\"text\":\"Demo\"},\"common.back_to_home\":{\"text\":\"Back to Home\"},\"common.logout\":{\"text\":\"Log Out\"}}','machine_translated','2026-07-25 11:14:00','2026-07-25 11:14:13'),
(2,'omo_topbar','en','63bc941593ac5e3955c168a0997f53faa3c967e686af49fe333b24f34908d4d9','{\"topbar.bug.button\":{\"text\":\"Bug\"},\"topbar.bug.title\":{\"text\":\"Report a bug\"},\"topbar.bug.unavailable_html\":{\"text\":\"<p>Form unavailable.</p>\"},\"topbar.close\":{\"text\":\"Close\"},\"topbar.drawer.default_title\":{\"text\":\"Sidebar\"},\"topbar.help.button\":{\"text\":\"Help\"},\"topbar.help.fallback_label\":{\"text\":\"Help\"},\"topbar.help.faq.description\":{\"text\":\"Access the most common questions, with a search engine to easily find answers to your questions.\"},\"topbar.help.faq.label\":{\"text\":\"FAQ\"},\"topbar.help.faq.title\":{\"text\":\"OMO FAQ\"},\"topbar.help.pending_html\":{\"text\":\"<p>Content coming soon.</p>\"},\"topbar.help.privacy.label\":{\"text\":\"Privacy Policy\"},\"topbar.help.terms.label\":{\"text\":\"Terms and Conditions\"},\"topbar.help.tour.description\":{\"text\":\"Tour of the visible functions on the screen with explanations for each button and possibility.\"},\"topbar.help.tour.label\":{\"text\":\"Guided Tour\"},\"topbar.help.tutorials.description\":{\"text\":\"Targeted training to improve skills in using the software.\"},\"topbar.help.tutorials.label\":{\"text\":\"Tutorials\"},\"topbar.help.tutorials.title\":{\"text\":\"Tutorials\"},\"topbar.help.unavailable_html\":{\"text\":\"<p>Content unavailable.</p>\"},\"topbar.help.webmaster.label\":{\"text\":\"Webmaster: {email}\"},\"topbar.load_error\":{\"text\":\"Loading error\"},\"topbar.load_error_description\":{\"text\":\"The content could not be loaded. Check your connection and try again.\"},\"topbar.loading\":{\"text\":\"Loading...\"},\"topbar.logout\":{\"text\":\"Log out\"},\"topbar.modal.default_title\":{\"text\":\"Panel\"},\"topbar.profile.admin_mode.active\":{\"text\":\"Organization Admin Mode active\"},\"topbar.profile.admin_mode.disable\":{\"text\":\"Exit Organization Admin Mode\"},\"topbar.profile.admin_mode.enable\":{\"text\":\"Enable Organization Admin Mode\"},\"topbar.profile.admin_mode.inactive\":{\"text\":\"Organization Admin Mode inactive\"},\"topbar.profile.button\":{\"text\":\"Profile\"},\"topbar.profile.details.email\":{\"text\":\"Email\"},\"topbar.profile.details.empty_value\":{\"text\":\"Not provided\"},\"topbar.profile.details.name\":{\"text\":\"Name\"},\"topbar.profile.details.username\":{\"text\":\"Username\"},\"topbar.profile.edit_label\":{\"text\":\"Edit Profile\"},\"topbar.profile.edit_title\":{\"text\":\"Your Profile\"},\"topbar.profile.preferences.color_style_default\":{\"text\":\"Black and White\"},\"topbar.profile.preferences.color_style_label\":{\"text\":\"Color\"},\"topbar.profile.preferences.color_style_ocean_blue\":{\"text\":\"Ocean Blue\"},\"topbar.profile.preferences.color_style_turquoise\":{\"text\":\"Turquoise\"},\"topbar.profile.preferences.language_label\":{\"text\":\"Language\"},\"topbar.profile.preferences.language_system\":{\"text\":\"System\"},\"topbar.profile.preferences.theme_dark\":{\"text\":\"Dark\"},\"topbar.profile.preferences.theme_label\":{\"text\":\"Theme\"},\"topbar.profile.preferences.theme_light\":{\"text\":\"Light\"},\"topbar.profile.preferences.theme_system\":{\"text\":\"System\"},\"topbar.profile.site_admin_mode.active\":{\"text\":\"Super Admin Mode active\"},\"topbar.profile.site_admin_mode.disable\":{\"text\":\"Exit Super Admin Mode\"},\"topbar.profile.site_admin_mode.enable\":{\"text\":\"Enable Super Admin Mode\"},\"topbar.profile.site_admin_mode.inactive\":{\"text\":\"Super Admin Mode inactive\"},\"topbar.profile.summary_fallback\":{\"text\":\"Profile Summary\"},\"topbar.retry\":{\"text\":\"Retry\"},\"topbar.search.advanced_hint\":{\"text\":\"Other advanced filters may be added here.\"},\"topbar.search.button\":{\"text\":\"Search\"},\"topbar.search.period\":{\"text\":\"Period\"},\"topbar.search.period_end\":{\"text\":\"To\"},\"topbar.search.period_start\":{\"text\":\"From\"},\"topbar.search.placeholder\":{\"text\":\"Search for a circle, role, tool, FAQ, or tutorial\"},\"topbar.search.scope\":{\"text\":\"Search in\"},\"topbar.search.submit\":{\"text\":\"Submit\"},\"topbar.tension.button\":{\"text\":\"Tension\"},\"topbar.tension.title\":{\"text\":\"Declare a tension\"},\"topbar.tension.title_template\":{\"text\":\"Declare {tensionArticle} {tensionLabel}\"},\"topbar.tension.unavailable_html\":{\"text\":\"<p>Form unavailable.</p>\"}}','machine_translated','2026-07-25 11:14:00','2026-08-02 06:11:08'),
(3,'omo_checklist','en','5aa1c0266001320570b9d890e91579ed082601ecb747cbd0ad27cb8096a5eaa5','{\"checklist.action.activate\":{\"text\":\"Activate\"},\"checklist.action.add_item\":{\"text\":\"Add an item\"},\"checklist.action.cancel\":{\"text\":\"Cancel\"},\"checklist.action.close\":{\"text\":\"Close\"},\"checklist.action.edit\":{\"text\":\"Edit\"},\"checklist.action.edit_item\":{\"text\":\"Edit the item\"},\"checklist.action.move_down\":{\"text\":\"Move down\"},\"checklist.action.move_up\":{\"text\":\"Move up\"},\"checklist.action.new\":{\"text\":\"Add\"},\"checklist.action.remove_item\":{\"text\":\"Remove\"},\"checklist.action.save\":{\"text\":\"Save\"},\"checklist.action.save_item\":{\"text\":\"Save the item\"},\"checklist.activation.after_completion\":{\"text\":\"Visible after another item\"},\"checklist.activation.after_start\":{\"text\":\"According to the reference date\"},\"checklist.activation.immediate\":{\"text\":\"Visible immediately\"},\"checklist.delay.day\":{\"text\":\"Day(s)\"},\"checklist.delay.month\":{\"text\":\"Month(s)\"},\"checklist.delay.week\":{\"text\":\"Week(s)\"},\"checklist.description\":{\"text\":\"Reusable processes that become projects at the right time.\"},\"checklist.detail.activated_at\":{\"text\":\"Activated on\"},\"checklist.detail.context\":{\"text\":\"Context\"},\"checklist.detail.display_lead\":{\"text\":\"Displayed {delay} before\"},\"checklist.detail.empty_items\":{\"text\":\"This checklist contains no steps yet.\"},\"checklist.detail.empty_runs\":{\"text\":\"No ongoing instance.\"},\"checklist.detail.execution_duration\":{\"text\":\"Execution duration: {delay}\"},\"checklist.detail.item_count\":{\"one\":\"{count} item\",\"other\":\"{count} items\"},\"checklist.detail.items\":{\"text\":\"Structure\"},\"checklist.detail.no_delay\":{\"text\":\"No delay\"},\"checklist.detail.no_description\":{\"text\":\"No description.\"},\"checklist.detail.open_run_count\":{\"one\":\"{count} ongoing instance\",\"other\":\"{count} ongoing instances\"},\"checklist.detail.open_runs\":{\"text\":\"Ongoing instances\"},\"checklist.detail.overdue\":{\"text\":\"Overdue\"},\"checklist.detail.project_instance_count\":{\"one\":\"{count} project from the checklist\",\"other\":\"{count} projects from the checklist\"},\"checklist.detail.project_status\":{\"text\":\"Status: {status}\"},\"checklist.detail.project_status.blocked\":{\"text\":\"Blocked\"},\"checklist.detail.project_status.done\":{\"text\":\"Done\"},\"checklist.detail.project_status.in_progress\":{\"text\":\"In progress\"},\"checklist.detail.project_status.ready\":{\"text\":\"Ready\"},\"checklist.detail.project_status.review\":{\"text\":\"To review\"},\"checklist.detail.project_status.someday\":{\"text\":\"Someday maybe\"},\"checklist.detail.recurrence\":{\"text\":\"Recurring: {schedule}\"},\"checklist.detail.recurring_deadline\":{\"text\":\"Deadline {date}\"},\"checklist.detail.recurring_instance_count\":{\"one\":\"{count} active occurrence\",\"other\":\"{count} active occurrences\"},\"checklist.detail.recurring_planned_start\":{\"text\":\"Planned on {date}\"},\"checklist.detail.reference_date\":{\"text\":\"Reference\"},\"checklist.detail.root\":{\"text\":\"Root project\"},\"checklist.detail.run_item_count\":{\"one\":\"{count} step\",\"other\":\"{count} steps\"},\"checklist.detail.trigger\":{\"text\":\"Trigger\"},\"checklist.detail.updated\":{\"text\":\"Updated\"},\"checklist.drawer.description\":{\"text\":\"Structure, activation, and responsibilities.\"},\"checklist.drawer.title\":{\"text\":\"Checklist\"},\"checklist.empty.children\":{\"text\":\"No checklist in this context or its direct children.\"},\"checklist.empty.contextual\":{\"text\":\"No checklist in this context.\"},\"checklist.empty.descendants\":{\"text\":\"No checklist in this context or its descendants.\"},\"checklist.error.action\":{\"text\":\"Unknown action.\"},\"checklist.error.activation_unavailable\":{\"text\":\"This checklist cannot be activated on demand.\"},\"checklist.error.context\":{\"text\":\"Invalid or inaccessible context.\"},\"checklist.error.forbidden\":{\"text\":\"You cannot edit this checklist.\"},\"checklist.error.instance_title\":{\"text\":\"The instance name is required.\"},\"checklist.error.item_holon\":{\"text\":\"The chosen role or holon for an item is invalid.\"},\"checklist.error.item_not_found\":{\"text\":\"Checklist item not found.\"},\"checklist.error.item_recurrence_structure\":{\"text\":\"A recurring item must be independent and visible immediately.\"},\"checklist.error.item_relation\":{\"text\":\"A relationship between items is invalid or forms a loop.\"},\"checklist.error.item_title\":{\"text\":\"Each item must have a title.\"},\"checklist.error.items\":{\"text\":\"Add at least one item to the checklist.\"},\"checklist.error.load\":{\"text\":\"Unable to load this checklist.\"},\"checklist.error.method\":{\"text\":\"This action must be sent via POST.\"},\"checklist.error.not_found\":{\"text\":\"Checklist not found.\"},\"checklist.error.open_instance\":{\"text\":\"An instance is already running for this checklist.\"},\"checklist.error.organization\":{\"text\":\"Invalid or inaccessible organization.\"},\"checklist.error.reference_date\":{\"text\":\"The reference date is invalid.\"},\"checklist.error.save\":{\"text\":\"Unable to save the checklist.\"},\"checklist.error.schedule\":{\"text\":\"The chosen recurrence is incomplete or invalid.\"},\"checklist.error.title\":{\"text\":\"The checklist title is required.\"},\"checklist.form.activate_intro\":{\"text\":\"The reference date serves as the starting point for scheduled steps, including those planned before this date.\"},\"checklist.form.activate_title\":{\"text\":\"Activate the checklist\"},\"checklist.form.activation\":{\"text\":\"Visibility\"},\"checklist.form.base_intro\":{\"text\":\"Start with general information. Steps will be added later from the checklist view.\"},\"checklist.form.confirm_overlap\":{\"text\":\"Create a new instance despite already open instances\"},\"checklist.form.create_item_title\":{\"text\":\"Add a step\"},\"checklist.form.create_title\":{\"text\":\"New checklist\"},\"checklist.form.delay\":{\"text\":\"Delay\"},\"checklist.form.dependency\":{\"text\":\"After the item\"},\"checklist.form.description\":{\"text\":\"Description\"},\"checklist.form.display_lead\":{\"text\":\"Display in advance\"},\"checklist.form.display_lead_unit\":{\"text\":\"Lead time unit\"},\"checklist.form.edit_item_title\":{\"text\":\"Edit the step\"},\"checklist.form.edit_title\":{\"text\":\"Edit the checklist\"},\"checklist.form.end_date\":{\"text\":\"Deadline\"},\"checklist.form.execution_duration\":{\"text\":\"Execution duration\"},\"checklist.form.execution_duration_unit\":{\"text\":\"Duration unit\"},\"checklist.form.frequency\":{\"text\":\"Frequency\"},\"checklist.form.holon\":{\"text\":\"Responsible role or holon\"},\"checklist.form.identity\":{\"text\":\"Model identity\"},\"checklist.form.importance\":{\"text\":\"Strategic importance\"},\"checklist.form.instance_title\":{\"text\":\"Instance name\"},\"checklist.form.instance_title_help\":{\"text\":\"This name becomes the title of the root project created for this instance.\"},\"checklist.form.intro\":{\"text\":\"Define the model, its elements, and the conditions that make them visible.\"},\"checklist.form.item\":{\"text\":\"Item\"},\"checklist.form.item_description\":{\"text\":\"Description\"},\"checklist.form.item_recurrence\":{\"text\":\"Recurrence of this item\"},\"checklist.form.item_recurrence_help\":{\"text\":\"Each occurrence creates a simple project for the chosen role. You can make it appear in advance and set its execution duration.\"},\"checklist.form.item_timing\":{\"text\":\"Visibility and delay\"},\"checklist.form.item_timing_help\":{\"text\":\"Make the project appear before its scheduled date and set its execution duration. These settings apply to each item.\"},\"checklist.form.item_title\":{\"text\":\"Item title\"},\"checklist.form.items\":{\"text\":\"Checklist items\"},\"checklist.form.items_help\":{\"text\":\"Each item is a model project. It can be immediate, delayed around the reference date, or wait for another item.\"},\"checklist.form.overlap\":{\"text\":\"If an execution is still open\"},\"checklist.form.parent\":{\"text\":\"Sub-project of\"},\"checklist.form.parent_root\":{\"text\":\"Checklist root\"},\"checklist.form.priority\":{\"text\":\"Priority\"},\"checklist.form.reference_date\":{\"text\":\"Reference date\"},\"checklist.form.reference_help\":{\"text\":\"For example, the arrival date. A step at D-5 will be scheduled five days before.\"},\"checklist.form.revision_note\":{\"text\":\"Internal note\"},\"checklist.form.schedule\":{\"text\":\"Expected time\"},\"checklist.form.select_item\":{\"text\":\"Choose an item...\"},\"checklist.form.size\":{\"text\":\"Size\"},\"checklist.form.status\":{\"text\":\"Status\"},\"checklist.form.title\":{\"text\":\"Title\"},\"checklist.form.trigger\":{\"text\":\"Checklist trigger\"},\"checklist.form.trigger_help\":{\"text\":\"It can be launched on demand, following a recurrence, or serve only as a container.\"},\"checklist.form.trigger_type\":{\"text\":\"Mode\"},\"checklist.form.unit\":{\"text\":\"Unit\"},\"checklist.frequency.daily\":{\"text\":\"Every day\"},\"checklist.frequency.monthly\":{\"text\":\"Every month\"},\"checklist.frequency.quarterly\":{\"text\":\"Every quarter\"},\"checklist.frequency.semiannual\":{\"text\":\"Every semester\"},\"checklist.frequency.weekly\":{\"text\":\"Every week\"},\"checklist.frequency.yearly\":{\"text\":\"Every year\"},\"checklist.loading\":{\"text\":\"Loading checklist...\"},\"checklist.overlap.ask\":{\"text\":\"Ask when the time comes\"},\"checklist.overlap.create_new\":{\"text\":\"Create a new execution\"},\"checklist.overlap.reuse_open\":{\"text\":\"Reuse the open execution\"},\"checklist.overlap.skip\":{\"text\":\"Skip this occurrence\"},\"checklist.run.status.running\":{\"text\":\"In progress\"},\"checklist.schedule.month.1\":{\"text\":\"January\"},\"checklist.schedule.month.10\":{\"text\":\"October\"},\"checklist.schedule.month.11\":{\"text\":\"November\"},\"checklist.schedule.month.12\":{\"text\":\"December\"},\"checklist.schedule.month.2\":{\"text\":\"February\"},\"checklist.schedule.month.3\":{\"text\":\"March\"},\"checklist.schedule.month.4\":{\"text\":\"April\"},\"checklist.schedule.month.5\":{\"text\":\"May\"},\"checklist.schedule.month.6\":{\"text\":\"June\"},\"checklist.schedule.month.7\":{\"text\":\"July\"},\"checklist.schedule.month.8\":{\"text\":\"August\"},\"checklist.schedule.month.9\":{\"text\":\"September\"},\"checklist.schedule.month_day\":{\"text\":\"The {day} of the month\"},\"checklist.schedule.none\":{\"text\":\"Choose...\"},\"checklist.schedule.quarter.1\":{\"text\":\"First month of the quarter\"},\"checklist.schedule.quarter.2\":{\"text\":\"Second month of the quarter\"},\"checklist.schedule.quarter.3\":{\"text\":\"Third month of the quarter\"},\"checklist.schedule.semester.1\":{\"text\":\"First month of the semester\"},\"checklist.schedule.semester.2\":{\"text\":\"Second month of the semester\"},\"checklist.schedule.semester.3\":{\"text\":\"Third month of the semester\"},\"checklist.schedule.semester.4\":{\"text\":\"Fourth month of the semester\"},\"checklist.schedule.semester.5\":{\"text\":\"Fifth month of the semester\"},\"checklist.schedule.semester.6\":{\"text\":\"Sixth month of the semester\"},\"checklist.schedule.weekday.1\":{\"text\":\"Monday\"},\"checklist.schedule.weekday.2\":{\"text\":\"Tuesday\"},\"checklist.schedule.weekday.3\":{\"text\":\"Wednesday\"},\"checklist.schedule.weekday.4\":{\"text\":\"Thursday\"},\"checklist.schedule.weekday.5\":{\"text\":\"Friday\"},\"checklist.schedule.weekday.6\":{\"text\":\"Saturday\"},\"checklist.schedule.weekday.7\":{\"text\":\"Sunday\"},\"checklist.scope.children\":{\"text\":\"Direct children\"},\"checklist.scope.contextual\":{\"text\":\"Local\"},\"checklist.scope.descendants\":{\"text\":\"Descendants\"},\"checklist.status.draft\":{\"text\":\"Draft\"},\"checklist.status.published\":{\"text\":\"Available\"},\"checklist.status.retired\":{\"text\":\"Disabled\"},\"checklist.success.activated\":{\"text\":\"The new instance is active.\"},\"checklist.success.reused\":{\"text\":\"The already open instance has been retained.\"},\"checklist.success.save\":{\"text\":\"Checklist saved.\"},\"checklist.title\":{\"text\":\"Checklists\"},\"checklist.trigger.container\":{\"text\":\"Container\"},\"checklist.trigger.manual\":{\"text\":\"On demand\"},\"checklist.trigger.scheduled\":{\"text\":\"Recurring\"}}','machine_translated','2026-07-25 11:14:00','2026-07-25 11:14:23'),
(4,'omo_get_org_panel','en','f660d62944993eed0bbc6a8f27f6a4ddc787303284c54683255c477c2af742c8','{\"leftbar.actions.add\":{\"text\":\"Add\"},\"leftbar.actions.delete\":{\"text\":\"Delete\"},\"leftbar.actions.edit\":{\"text\":\"Edit\"},\"leftbar.actions.history\":{\"text\":\"History\"},\"leftbar.actions.move\":{\"text\":\"Move\"},\"leftbar.authority.delegated\":{\"text\":\"delegated\"},\"leftbar.authority.delegated_count\":{\"one\":\"{count} delegated authority\",\"other\":\"{count} delegated authorities\"},\"leftbar.authority.delegated_to\":{\"text\":\"Delegated to\"},\"leftbar.authority.description\":{\"text\":\"Description\"},\"leftbar.authority.inherited_from\":{\"text\":\"Inherited from\"},\"leftbar.authority.internal_children\":{\"text\":\"Sub-authorities\"},\"leftbar.authority.root\":{\"text\":\"Root authority\"},\"leftbar.children.circles\":{\"text\":\"Circles\"},\"leftbar.children.roles\":{\"text\":\"Roles\"},\"leftbar.children.section_title\":{\"text\":\"Dependencies\"},\"leftbar.copy_link.error\":{\"text\":\"Unable to copy the direct link.\"},\"leftbar.copy_link.success\":{\"text\":\"Link copied\"},\"leftbar.detail.item_fallback\":{\"text\":\"Item\"},\"leftbar.detail.property_fallback\":{\"text\":\"Property {propertyId}\"},\"leftbar.detail.show\":{\"text\":\"See details\"},\"leftbar.detail.updated_at\":{\"text\":\"Updated on {date}\"},\"leftbar.detail.updated_by\":{\"text\":\"Updated on {date} by {userName}\"},\"leftbar.empty.message\":{\"text\":\"No content has been provided for this holon yet.\"},\"leftbar.empty.section_title\":{\"text\":\"Information\"},\"leftbar.error.holon_access_denied\":{\"text\":\"Access denied to this holon.\"},\"leftbar.error.holon_not_found\":{\"text\":\"Holon not found for this organization.\"},\"leftbar.error.organization_access_denied\":{\"text\":\"Access denied to this organization.\"},\"leftbar.error.organization_invalid\":{\"text\":\"Invalid organization.\"},\"leftbar.error.organization_not_found\":{\"text\":\"Organization not found.\"},\"leftbar.error.root_not_found\":{\"text\":\"No root structure found for this organization.\"},\"leftbar.members.add\":{\"text\":\"Add a member\"},\"leftbar.members.admin_tooltip\":{\"text\":\"{memberName} - {adminLabel}\"},\"leftbar.members.pending_tooltip\":{\"text\":\"{memberName} - invitation pending\"},\"leftbar.members.section_title\":{\"text\":\"Members\"},\"leftbar.members.view_all\":{\"text\":\"View all\"},\"leftbar.project.children.empty\":{\"text\":\"No direct sub-project.\"},\"leftbar.project.children.error\":{\"text\":\"Unable to load sub-projects.\"},\"leftbar.project.children.loading\":{\"text\":\"Loading sub-projects...\"}}','machine_translated','2026-07-25 11:14:00','2026-07-28 10:31:06'),
(5,'omo_get_sidebar_panel','en','7258f1301d443eca5c81e9ee4fba09a9b428a3544f0e08f73bc31af8cbc584b7','{\"sidebar.applications.manage_label\":{\"text\":\"Manage\"},\"sidebar.applications.manage_title\":{\"text\":\"Manage applications\"},\"sidebar.parameters.label\":{\"text\":\"Settings\"}}','machine_translated','2026-07-25 11:14:00','2026-07-25 11:14:03'),
(7,'omo_stats','en','799a66e8cbfd42243f00223d577fe045cfd9687cdbd86d570920d809ace99048','{\"stats.action.add\": {\"text\": \"Add\"}, \"stats.action.cancel\": {\"text\": \"Cancel\"}, \"stats.action.close\": {\"text\": \"Close\"}, \"stats.action.create_group\": {\"text\": \"Create group\"}, \"stats.action.delete\": {\"text\": \"Delete\"}, \"stats.action.delete_group\": {\"text\": \"Remove group\"}, \"stats.action.delete_import\": {\"text\": \"Remove from this context\"}, \"stats.action.delete_indicator\": {\"text\": \"Delete indicator\"}, \"stats.action.detail\": {\"text\": \"Detail\"}, \"stats.action.edit\": {\"text\": \"Edit\"}, \"stats.action.edit_group\": {\"text\": \"Edit group\"}, \"stats.action.edit_import\": {\"text\": \"Change source\"}, \"stats.action.group\": {\"text\": \"Group indicators\"}, \"stats.action.import\": {\"text\": \"Import indicator\"}, \"stats.action.more\": {\"text\": \"More actions\"}, \"stats.action.new\": {\"text\": \"New indicator\"}, \"stats.action.save\": {\"text\": \"Save\"}, \"stats.action.update\": {\"text\": \"Save\"}, \"stats.card.context\": {\"text\": \"Context\"}, \"stats.card.group\": {\"text\": \"Group\"}, \"stats.card.imported\": {\"text\": \"Imported\"}, \"stats.card.latest\": {\"text\": \"Latest value\"}, \"stats.card.member_count\": {\"one\": \"{count} indicator\", \"other\": \"{count} indicators\"}, \"stats.card.no_value\": {\"text\": \"No value\"}, \"stats.card.open\": {\"text\": \"Open indicator {name}\"}, \"stats.card.overdue\": {\"text\": \"Overdue value\"}, \"stats.card.overdue_days\": {\"one\": \"Overdue by {count} day\", \"other\": \"Overdue by {count} days\"}, \"stats.card.to_complete\": {\"text\": \"To complete\"}, \"stats.card.value_count\": {\"one\": \"{count} value\", \"other\": \"{count} values\"}, \"stats.chart.empty\": {\"text\": \"No data to display yet.\"}, \"stats.chart.tooltip.date\": {\"text\": \"Date\"}, \"stats.chart.tooltip.value\": {\"text\": \"Value\"}, \"stats.column.context\": {\"text\": \"Context\"}, \"stats.column.history\": {\"text\": \"History\"}, \"stats.column.indicator\": {\"text\": \"Indicator\"}, \"stats.column.latest\": {\"text\": \"Latest value\"}, \"stats.controls.sort.alpha\": {\"text\": \"Alphabetical\"}, \"stats.controls.sort.aria\": {\"text\": \"Indicator sorting\"}, \"stats.controls.sort.temporal\": {\"text\": \"Temporal\"}, \"stats.detail.add\": {\"text\": \"Add value\"}, \"stats.detail.add_help\": {\"text\": \"The current date and time are automatically suggested.\"}, \"stats.detail.add_title\": {\"text\": \"Add current value\"}, \"stats.detail.chart_min_value\": {\"text\": \"Lower value\"}, \"stats.detail.confirm_delete\": {\"text\": \"Permanently delete this value?\"}, \"stats.detail.confirm_delete_group\": {\"text\": \"Remove this group from the context?\"}, \"stats.detail.confirm_delete_import\": {\"text\": \"Remove this indicator from the context?\"}, \"stats.detail.confirm_delete_indicator\": {\"text\": \"Delete this indicator from the list? Its values will be retained.\"}, \"stats.detail.frequency\": {\"text\": \"Expected frequency\"}, \"stats.detail.latest\": {\"text\": \"Current value\"}, \"stats.detail.no_values\": {\"text\": \"No values have been recorded yet.\"}, \"stats.detail.range.end\": {\"text\": \"End of displayed period\"}, \"stats.detail.range.label\": {\"text\": \"Displayed period\"}, \"stats.detail.range.start\": {\"text\": \"Start of displayed period\"}, \"stats.detail.reference\": {\"text\": \"Reference\"}, \"stats.detail.reference_ceiling\": {\"text\": \"Horizontal ceiling\"}, \"stats.detail.reference_none\": {\"text\": \"No reference curve\"}, \"stats.detail.reference_objective\": {\"text\": \"Objective or trajectory\"}, \"stats.detail.schedule\": {\"text\": \"Expected moment\"}, \"stats.detail.source\": {\"text\": \"View source\"}, \"stats.detail.tab.chart\": {\"text\": \"Chart\"}, \"stats.detail.tab.values\": {\"text\": \"Values\"}, \"stats.detail.value\": {\"text\": \"Value\"}, \"stats.detail.value_date\": {\"text\": \"Date\"}, \"stats.drawer.description\": {\"text\": \"Chart, values, and manual entry.\"}, \"stats.drawer.title\": {\"text\": \"Indicator\"}, \"stats.empty.children\": {\"text\": \"No indicators are defined in this context or its direct children yet.\"}, \"stats.empty.contextual\": {\"text\": \"No indicators are defined in this context yet.\"}, \"stats.empty.descendants\": {\"text\": \"No indicators are defined in this context or its descendants yet.\"}, \"stats.error.action\": {\"text\": \"Unknown action.\"}, \"stats.error.ceiling\": {\"text\": \"All points of a ceiling must use the same value.\"}, \"stats.error.ceiling_value\": {\"text\": \"Ceiling value is required.\"}, \"stats.error.chart_min_value\": {\"text\": \"Invalid chart lower value.\"}, \"stats.error.context\": {\"text\": \"Invalid or inaccessible context.\"}, \"stats.error.date\": {\"text\": \"The entered date is invalid.\"}, \"stats.error.forbidden\": {\"text\": \"You cannot edit this indicator.\"}, \"stats.error.group_name\": {\"text\": \"Group name is required.\"}, \"stats.error.load\": {\"text\": \"Unable to load this indicator.\"}, \"stats.error.method\": {\"text\": \"This action must be sent via POST.\"}, \"stats.error.name\": {\"text\": \"Indicator name is required.\"}, \"stats.error.not_found\": {\"text\": \"Indicator not found.\"}, \"stats.error.organization\": {\"text\": \"Invalid or inaccessible organization.\"}, \"stats.error.reference_dates\": {\"text\": \"The end date of the reference must be after its start date.\"}, \"stats.error.reference_endpoints\": {\"text\": \"Endpoints at 0% and 100% are required and must have a date.\"}, \"stats.error.reference_points\": {\"text\": \"The reference curve must contain unique points between 0 and 100%.\"}, \"stats.error.save\": {\"text\": \"Unable to save the indicator.\"}, \"stats.error.schedule\": {\"text\": \"Invalid measurement frequency.\"}, \"stats.error.selection\": {\"text\": \"Select at least one visible indicator.\"}, \"stats.error.url\": {\"text\": \"URL must start with http:// or https://.\"}, \"stats.error.value\": {\"text\": \"The entered value is invalid.\"}, \"stats.error.value_save\": {\"text\": \"Unable to save this value.\"}, \"stats.form.add_point\": {\"text\": \"Add point\"}, \"stats.form.ceiling_help\": {\"text\": \"Enter a single value. The marker will be displayed over the entire visible period of the chart.\"}, \"stats.form.ceiling_title\": {\"text\": \"Ceiling\"}, \"stats.form.ceiling_value\": {\"text\": \"Ceiling value\"}, \"stats.form.create_title\": {\"text\": \"New indicator\"}, \"stats.form.edit_title\": {\"text\": \"Edit indicator\"}, \"stats.form.endpoint\": {\"text\": \"Dated endpoint\"}, \"stats.form.frequency\": {\"text\": \"Frequency\"}, \"stats.form.intermediate\": {\"text\": \"Intermediate point\"}, \"stats.form.intro\": {\"text\": \"Define the series and, if necessary, its reference curve.\"}, \"stats.form.point_date\": {\"text\": \"Date\"}, \"stats.form.point_date_auto\": {\"text\": \"Calculated date\"}, \"stats.form.point_value\": {\"text\": \"Value\"}, \"stats.form.position\": {\"text\": \"Position (%)\"}, \"stats.form.reference_ceiling\": {\"text\": \"Horizontal ceiling\"}, \"stats.form.reference_help\": {\"text\": \"Dated endpoints use 0% and 100%. Intermediate dates are calculated based on the point\'s position.\"}, \"stats.form.reference_none\": {\"text\": \"No reference\"}, \"stats.form.reference_objective\": {\"text\": \"Objective or trajectory\"}, \"stats.form.reference_title\": {\"text\": \"Reference curve\"}, \"stats.form.remove_point\": {\"text\": \"Remove\"}, \"stats.form.schedule\": {\"text\": \"When\"}, \"stats.form.schedule_help\": {\"text\": \"Define the expected rhythm. The moment is optional: without it, the system can rely on the observed interval between measurements.\"}, \"stats.form.schedule_title\": {\"text\": \"Measurement rhythm\"}, \"stats.frequency.daily\": {\"text\": \"Daily\"}, \"stats.frequency.monthly\": {\"text\": \"Monthly\"}, \"stats.frequency.none\": {\"text\": \"No defined frequency\"}, \"stats.frequency.quarterly\": {\"text\": \"Quarterly\"}, \"stats.frequency.semiannual\": {\"text\": \"Semiannual\"}, \"stats.frequency.weekly\": {\"text\": \"Weekly\"}, \"stats.frequency.yearly\": {\"text\": \"Yearly\"}, \"stats.group.combined\": {\"text\": \"Totals\"}, \"stats.group.detail.sources\": {\"text\": \"Source indicators\"}, \"stats.group.detail.sum\": {\"text\": \"Calculated sum\"}, \"stats.group.edit_title\": {\"text\": \"Edit group\"}, \"stats.group.mode\": {\"text\": \"Display\"}, \"stats.group.mode.overlay\": {\"text\": \"Overlaid curves\"}, \"stats.group.mode.sum\": {\"text\": \"Sum of values\"}, \"stats.group.name\": {\"text\": \"Group name\"}, \"stats.group.title\": {\"text\": \"Group indicators\"}, \"stats.import.edit_title\": {\"text\": \"Edit imported source\"}, \"stats.import.search\": {\"text\": \"Search\"}, \"stats.import.search_placeholder\": {\"text\": \"Name or context\"}, \"stats.import.title\": {\"text\": \"Import indicator\"}, \"stats.import.visible\": {\"text\": \"Visible indicators\"}, \"stats.loading\": {\"text\": \"Loading indicator...\"}, \"stats.schedule.month.1\": {\"text\": \"January\"}, \"stats.schedule.month.10\": {\"text\": \"October\"}, \"stats.schedule.month.11\": {\"text\": \"November\"}, \"stats.schedule.month.12\": {\"text\": \"December\"}, \"stats.schedule.month.2\": {\"text\": \"February\"}, \"stats.schedule.month.3\": {\"text\": \"March\"}, \"stats.schedule.month.4\": {\"text\": \"April\"}, \"stats.schedule.month.5\": {\"text\": \"May\"}, \"stats.schedule.month.6\": {\"text\": \"June\"}, \"stats.schedule.month.7\": {\"text\": \"July\"}, \"stats.schedule.month.8\": {\"text\": \"August\"}, \"stats.schedule.month.9\": {\"text\": \"September\"}, \"stats.schedule.month_day\": {\"text\": \"On the {day}\"}, \"stats.schedule.none\": {\"text\": \"No specification\"}, \"stats.schedule.quarter.1\": {\"text\": \"January, April, July, October\"}, \"stats.schedule.quarter.2\": {\"text\": \"February, May, August, November\"}, \"stats.schedule.quarter.3\": {\"text\": \"March, June, September, December\"}, \"stats.schedule.semester.1\": {\"text\": \"January, July\"}, \"stats.schedule.semester.2\": {\"text\": \"February, August\"}, \"stats.schedule.semester.3\": {\"text\": \"March, September\"}, \"stats.schedule.semester.4\": {\"text\": \"April, October\"}, \"stats.schedule.semester.5\": {\"text\": \"May, November\"}, \"stats.schedule.semester.6\": {\"text\": \"June, December\"}, \"stats.schedule.weekday.1\": {\"text\": \"Monday\"}, \"stats.schedule.weekday.2\": {\"text\": \"Tuesday\"}, \"stats.schedule.weekday.3\": {\"text\": \"Wednesday\"}, \"stats.schedule.weekday.4\": {\"text\": \"Thursday\"}, \"stats.schedule.weekday.5\": {\"text\": \"Friday\"}, \"stats.schedule.weekday.6\": {\"text\": \"Saturday\"}, \"stats.schedule.weekday.7\": {\"text\": \"Sunday\"}, \"stats.scope.children\": {\"text\": \"Direct children\"}, \"stats.scope.contextual\": {\"text\": \"Contextual\"}, \"stats.scope.descendants\": {\"text\": \"Descendants\"}, \"stats.title\": {\"text\": \"Indicators\"}, \"stats.view.cards\": {\"text\": \"Cards\"}, \"stats.view.compact\": {\"text\": \"Compact\"}, \"stats.error.document_ethercalc\": {\"text\": \"The selected Framacalc document was not found or is inaccessible.\"}, \"stats.error.document_spreadsheet\": {\"text\": \"The selected spreadsheet document was not found or is inaccessible.\"}, \"stats.error.ethercalc_config\": {\"text\": \"Framacalc is not configured.\"}, \"stats.error.ethercalc_cell\": {\"text\": \"The Framacalc cell must be written in the form A1.\"}, \"stats.error.spreadsheet_cell\": {\"text\": \"The spreadsheet cell must be written in the form A1.\"}, \"stats.error.ethercalc_mode\": {\"text\": \"The Framacalc reading mode is invalid.\"}, \"stats.error.spreadsheet_mode\": {\"text\": \"The spreadsheet reading mode is invalid.\"}, \"stats.error.columns_required\": {\"text\": \"The range, date column, and at least one value column are required.\"}, \"stats.error.single_value_column_required\": {\"text\": \"The range, date column, and one value column are required.\"}, \"stats.error.columns_in_range\": {\"text\": \"The date and value columns must be included in the range.\"}, \"stats.error.create_permission\": {\"text\": \"The permission to create indicators is required to add series.\"}, \"stats.error.ethercalc_synced\": {\"text\": \"The values of this indicator are synchronized from Framacalc.\"}, \"stats.error.spreadsheet_synced\": {\"text\": \"The values of this indicator are synchronized from a spreadsheet document.\"}, \"stats.import.source_indicators\": {\"text\": \"Existing indicators\"}, \"stats.import.source_ethercalc\": {\"text\": \"Framacalc\"}, \"stats.import.source_spreadsheet\": {\"text\": \"Spreadsheet documents\"}, \"stats.import.ethercalc.document\": {\"text\": \"Framacalc document\"}, \"stats.import.ethercalc.no_documents\": {\"text\": \"No visible collaborative spreadsheet is available in this organization.\"}, \"stats.import.ethercalc.mode\": {\"text\": \"Reading mode\"}, \"stats.import.ethercalc.mode_cell\": {\"text\": \"Read one cell\"}, \"stats.import.ethercalc.mode_table\": {\"text\": \"Read a table\"}, \"stats.import.ethercalc.cell\": {\"text\": \"Cell\"}, \"stats.import.ethercalc.frequency\": {\"text\": \"Frequency\"}, \"stats.import.ethercalc.frequency_measurement\": {\"text\": \"Measurement rhythm\"}, \"stats.import.ethercalc.frequency_sync\": {\"text\": \"Synchronization frequency\"}, \"stats.import.ethercalc.frequency_hourly\": {\"text\": \"Every hour\"}, \"stats.import.ethercalc.frequency_daily\": {\"text\": \"Daily\"}, \"stats.import.ethercalc.frequency_weekly\": {\"text\": \"Weekly\"}, \"stats.import.ethercalc.range\": {\"text\": \"Data range\"}, \"stats.import.ethercalc.date_column\": {\"text\": \"Date column\"}, \"stats.import.ethercalc.value_columns\": {\"text\": \"Value columns\"}, \"stats.import.ethercalc.table_help\": {\"text\": \"The range must include a date column and one or more value columns.\"}, \"stats.import.ethercalc.prototype_action\": {\"text\": \"Test configuration\"}, \"stats.import.ethercalc.prototype_notice\": {\"text\": \"The Framacalc configuration can create indicators from a cell or a table.\"}, \"stats.import.ethercalc.name\": {\"text\": \"Indicator name\"}, \"stats.import.ethercalc.create_action\": {\"text\": \"Create indicators\"}, \"stats.import.spreadsheet.document\": {\"text\": \"Spreadsheet document\"}, \"stats.import.spreadsheet.no_documents\": {\"text\": \"No compatible spreadsheet document is visible in this organization.\"}, \"stats.import.spreadsheet.sheet\": {\"text\": \"Sheet\"}, \"stats.import.spreadsheet.mode\": {\"text\": \"Reading mode\"}, \"stats.import.spreadsheet.mode_cell\": {\"text\": \"Read one cell\"}, \"stats.import.spreadsheet.mode_table\": {\"text\": \"Read a table\"}, \"stats.import.spreadsheet.name\": {\"text\": \"Indicator name\"}, \"stats.import.spreadsheet.cell\": {\"text\": \"Cell\"}, \"stats.import.spreadsheet.frequency\": {\"text\": \"Synchronization frequency\"}, \"stats.import.spreadsheet.frequency_measurement\": {\"text\": \"Measurement rhythm\"}, \"stats.import.spreadsheet.frequency_sync\": {\"text\": \"Synchronization frequency\"}, \"stats.import.spreadsheet.frequency_hourly\": {\"text\": \"Every hour\"}, \"stats.import.spreadsheet.frequency_daily\": {\"text\": \"Daily\"}, \"stats.import.spreadsheet.frequency_weekly\": {\"text\": \"Weekly\"}, \"stats.import.spreadsheet.range\": {\"text\": \"Data range\"}, \"stats.import.spreadsheet.date_column\": {\"text\": \"Date column\"}, \"stats.import.spreadsheet.value_columns\": {\"text\": \"Value columns\"}, \"stats.import.spreadsheet.table_help\": {\"text\": \"The range must include a date column and one or more value columns.\"}, \"stats.import.spreadsheet.create_action\": {\"text\": \"Create indicators\"}, \"stats.import.spreadsheet.source_title\": {\"text\": \"Spreadsheet document source\"}, \"stats.import.spreadsheet.value_column\": {\"text\": \"Value column\"}, \"stats.import.ethercalc.source_title\": {\"text\": \"Framacalc source\"}, \"stats.import.ethercalc.value_column\": {\"text\": \"Value column\"}, \"stats.group.hide_same_holon_sources\": {\"text\": \"Hide indicators in the same holon\"}, \"stats.import.ethercalc.frequency_monthly\": {\"text\": \"Monthly\"}, \"stats.import.ethercalc.frequency_quarterly\": {\"text\": \"Quarterly\"}, \"stats.import.ethercalc.frequency_semiannual\": {\"text\": \"Semiannual\"}, \"stats.import.ethercalc.frequency_yearly\": {\"text\": \"Yearly\"}, \"stats.import.spreadsheet.frequency_monthly\": {\"text\": \"Monthly\"}, \"stats.import.spreadsheet.frequency_quarterly\": {\"text\": \"Quarterly\"}, \"stats.import.spreadsheet.frequency_semiannual\": {\"text\": \"Semiannual\"}, \"stats.import.spreadsheet.frequency_yearly\": {\"text\": \"Yearly\"}, \"stats.form.source_title\": {\"text\": \"Value source\"}, \"stats.form.source_help\": {\"text\": \"Choose manual entry or an automatic source.\"}, \"stats.form.source_type\": {\"text\": \"Source type\"}, \"stats.form.source_manual\": {\"text\": \"Manual entry\"}, \"stats.form.source_ethercalc_cell\": {\"text\": \"Framacalc: cell\"}, \"stats.form.source_ethercalc_table\": {\"text\": \"Framacalc: table\"}, \"stats.form.source_spreadsheet_cell\": {\"text\": \"Spreadsheet document: cell\"}, \"stats.form.source_spreadsheet_table\": {\"text\": \"Spreadsheet document: table\"}, \"stats.detail.source_document\": {\"text\": \"Show source document\"}, \"stats.detail.source_document_new_window\": {\"text\": \"Open in a new tab\"}, \"stats.detail.source_document_title\": {\"text\": \"Indicator source document\"}}','machine_translated','2026-07-25 11:14:03','2026-09-10 16:24:54'),
(9,'omo_documents_index','en','c0d0b752d905747423a299a5e4cfd1f379fc2757e3541bc77ed4ff308db3257a','{\"documents.action.loading\":{\"text\":\"Loading...\"},\"documents.action.new\":{\"text\":\"New\"},\"documents.controls.density.aria\":{\"text\":\"Document display density\"},\"documents.controls.density.compact\":{\"text\":\"Compact\"},\"documents.controls.density.detail\":{\"text\":\"Detail\"},\"documents.controls.sort.alpha\":{\"text\":\"Alphabetical\"},\"documents.controls.sort.aria\":{\"text\":\"Document sorting\"},\"documents.controls.sort.date\":{\"text\":\"Date\"},\"documents.date_column.created\":{\"text\":\"Created on\"},\"documents.date_column.updated\":{\"text\":\"Updated on\"},\"documents.drawer.close\":{\"text\":\"Close\"},\"documents.drawer.detail_description\":{\"text\":\"Reading the document in OMO.\"},\"documents.drawer.detail_title\":{\"text\":\"Document Detail\"},\"documents.drawer.editor_description\":{\"text\":\"Creating a document in the current context.\"},\"documents.drawer.editor_title\":{\"text\":\"New Document\"},\"documents.empty.available_children\":{\"text\":\"No documents available for this context or its direct children.\"},\"documents.empty.available_contextual\":{\"text\":\"No documents available for this context.\"},\"documents.empty.available_descendants\":{\"text\":\"No documents available for this context and its descendants.\"},\"documents.empty.visible_children\":{\"one\":\"No visible documents for this context or its direct children. {count} file is hidden.\",\"other\":\"No visible documents for this context or its direct children. {count} files are hidden.\"},\"documents.empty.visible_contextual\":{\"one\":\"No visible documents for this context. {count} file is hidden.\",\"other\":\"No visible documents for this context. {count} files are hidden.\"},\"documents.empty.visible_descendants\":{\"one\":\"No visible documents for this context and its descendants. {count} file is hidden.\",\"other\":\"No visible documents for this context and its descendants. {count} files are hidden.\"},\"documents.error.load_document\":{\"text\":\"Unable to load this document.\"},\"documents.error.load_editor\":{\"text\":\"Unable to load the document editor.\"},\"documents.group.earlier\":{\"text\":\"Older\"},\"documents.group.last_month\":{\"text\":\"Last month\"},\"documents.group.last_week\":{\"text\":\"Last week\"},\"documents.group.this_month\":{\"text\":\"This month\"},\"documents.group.this_week\":{\"text\":\"This week\"},\"documents.group.this_year\":{\"text\":\"This year\"},\"documents.group.today\":{\"text\":\"Today\"},\"documents.group.too_far\":{\"text\":\"Unknown date\"},\"documents.group.yesterday\":{\"text\":\"Yesterday\"},\"documents.menu.action_error\":{\"text\":\"Action not possible.\"},\"documents.menu.archive\":{\"text\":\"Archive\"},\"documents.menu.confirm_archive\":{\"text\":\"Archive this document? It will no longer be visible in the list.\"},\"documents.menu.confirm_delete\":{\"text\":\"Permanently delete this document?\"},\"documents.menu.delete\":{\"text\":\"Delete\"},\"documents.menu.export_pdf\":{\"text\":\"Export as PDF\"},\"documents.page.title\":{\"text\":\"Documents\"},\"documents.scope.children\":{\"text\":\"Direct children\"},\"documents.scope.contextual\":{\"text\":\"Contextual\"},\"documents.scope.descendants\":{\"text\":\"Descendants\"},\"documents.scope.edit\":{\"text\":\"Edit\"},\"documents.scope.toggle_aria\":{\"text\":\"Document scope\"},\"documents.scope.view\":{\"text\":\"View\"},\"documents.sort.alpha_aria\":{\"text\":\"Alphabetical\"},\"documents.sort.created\":{\"text\":\"Creation\"},\"documents.sort.created_aria\":{\"text\":\"Creation date\"},\"documents.sort.updated\":{\"text\":\"Modification\"},\"documents.sort.updated_aria\":{\"text\":\"Modification date\"},\"documents.upload_missing.badge\":{\"text\":\"File missing\"}}','machine_translated','2026-07-25 11:14:06','2026-07-25 11:14:13'),
(15,'omo_get_structure_panel','en','542d102221f255e01f98cbcc450c9164056b4d03f2a94fc2ccbdba944505fd4c','{\"structure.actions.export\":{\"text\":\"Export\"},\"structure.actions.export.download\":{\"text\":\"Download\"},\"structure.actions.export.format.csv\":{\"text\":\"CSV\"},\"structure.actions.export.format.csv_description\":{\"text\":\"Flat view of holons. Rights are listed in a cell with their code and scope.\"},\"structure.actions.export.format.json\":{\"text\":\"JSON\"},\"structure.actions.export.format.json_description\":{\"text\":\"Complete format to re-import the structure. It also includes the rights of holons and templates.\"},\"structure.actions.export.format.xml\":{\"text\":\"XML\"},\"structure.actions.export.format.xml_description\":{\"text\":\"Structured and readable format, with the same rights codes as JSON.\"},\"structure.actions.export.modal_intro\":{\"text\":\"Choose the export format for this structure.\"},\"structure.actions.export.modal_title\":{\"text\":\"Export Structure\"},\"structure.actions.menu_aria\":{\"text\":\"Actions\"},\"structure.actions.print\":{\"text\":\"Print\"},\"structure.actions.share\":{\"text\":\"Share\"},\"structure.browser.generic_name\":{\"text\":\"this browser\"},\"structure.error.organization_access_denied\":{\"text\":\"Access denied to this organization.\"},\"structure.list.empty_search\":{\"text\":\"No nodes match this search.\"},\"structure.list.properties.hide_aria\":{\"text\":\"Hide properties\"},\"structure.list.properties.show_aria\":{\"text\":\"Show properties\"},\"structure.list.search.placeholder\":{\"text\":\"Quick filter\"},\"structure.message.disabled\":{\"text\":\"The Structure app is disabled for this organization.\"},\"structure.message.invalid\":{\"text\":\"Invalid structure.\"},\"structure.message.load_error\":{\"text\":\"Unable to load the structure.\"},\"structure.message.no_structure\":{\"text\":\"No structure available for this organization.\"},\"structure.placeholder.action\":{\"text\":\"Open the Structure app\"},\"structure.placeholder.text\":{\"text\":\"No structure is yet defined for this organization. Open the Structure app in the leftbar to create an empty structure, import an export, or start from a template.\"},\"structure.placeholder.title\":{\"text\":\"No Structure\"},\"structure.share.modal_title\":{\"text\":\"Share Structure\"},\"structure.view.toggle_label\":{\"text\":\"G   L\"},\"structure.warning.brave\":{\"text\":\"Brave seems to block the canvas reading used for graphical navigation, probably due to the anti-fingerprint shield. The list view has been activated to continue navigating. You can also relax the shield for this site.\"},\"structure.warning.dismiss_aria\":{\"text\":\"Reduce this message\"},\"structure.warning.pixel_mismatch\":{\"text\":\"{browserName} blocks or alters the canvas reading used for graphical navigation. The list view has been activated to continue navigating.\"},\"structure.warning.restore\":{\"text\":\"Browser info\"},\"structure.warning.unavailable\":{\"text\":\"The canvas reading used for graphical navigation is not available in {browserName}. The list view has been activated to continue navigating.\"}}','machine_translated','2026-07-25 11:14:27','2026-07-25 11:14:32'),
(17,'omo_parameters_index','en','6b0cd91accb5c1957173774d20c5fc1a18b401cb94dde95f45a5e7bad31ca604','{\"parameters.index.action.close\":{\"text\":\"Close\"},\"parameters.index.card.application.description\":{\"text\":\"Configure the options and integrations of {applicationName} for {organizationName}.\"},\"parameters.index.card.application.eyebrow\":{\"text\":\"Application\"},\"parameters.index.card.application.forbidden\":{\"text\":\"Enable the {adminLabel} mode of the organization to modify the settings of {applicationName}.\"},\"parameters.index.card.export.description\":{\"text\":\"Export the chosen structure and data of {organizationName} into a reusable JSON file.\"},\"parameters.index.card.export.eyebrow\":{\"text\":\"Data\"},\"parameters.index.card.export.forbidden\":{\"text\":\"You must be {adminLabel} of the organization to export its data.\"},\"parameters.index.card.export.title\":{\"text\":\"Export\"},\"parameters.index.card.holon_templates.admin_mode_cta\":{\"text\":\"{adminLabel} mode required\"},\"parameters.index.card.holon_templates.admin_mode_required\":{\"text\":\"Enable {adminLabel} mode in your profile to open this editor.\"},\"parameters.index.card.holon_templates.description\":{\"text\":\"Configure the node types and their properties for your organization.\"},\"parameters.index.card.holon_templates.eyebrow\":{\"text\":\"Architecture\"},\"parameters.index.card.holon_templates.title\":{\"text\":\"Holon Templates\"},\"parameters.index.card.lexicon.description\":{\"text\":\"Adapt the terms used in the interface of this organization.\"},\"parameters.index.card.lexicon.eyebrow\":{\"text\":\"Vocabulary\"},\"parameters.index.card.lexicon.title\":{\"text\":\"Lexicon\"},\"parameters.index.card.organization.description\":{\"text\":\"Modify the name, short name, geographical position, illustrations, and color of {organizationName}.\"},\"parameters.index.card.organization.eyebrow\":{\"text\":\"Your structure\"},\"parameters.index.card.organization.fallback_name\":{\"text\":\"this organization\"},\"parameters.index.card.organization.forbidden\":{\"text\":\"You must be {adminLabel} of the organization to modify these settings.\"},\"parameters.index.card.organization.title\":{\"text\":\"Organization\"},\"parameters.index.card.profile.description\":{\"text\":\"Open your profile editor.\"},\"parameters.index.card.profile.eyebrow\":{\"text\":\"My account\"},\"parameters.index.card.profile.title\":{\"text\":\"Profile\"},\"parameters.index.card.server_admin.description\":{\"text\":\"Open the sensitive global settings of the .env file, excluding database configuration.\"},\"parameters.index.card.server_admin.eyebrow\":{\"text\":\"Maintenance\"},\"parameters.index.card.server_admin.title\":{\"text\":\"Server Admin\"},\"parameters.index.description\":{\"text\":\"Find your personal settings here as well as the available configuration screens for the organization.\"},\"parameters.index.drawer.error\":{\"text\":\"Unable to load this module.\"},\"parameters.index.drawer.loading\":{\"text\":\"Loading...\"},\"parameters.index.empty.login\":{\"text\":\"Log in to access your user settings.\"},\"parameters.index.title\":{\"text\":\"Settings\"}}','machine_translated','2026-07-25 11:14:51','2026-07-30 07:52:52'),
(19,'omo_parameters_server_env','en','53f1e52aa3ba401a5f5971f323bdf1e702282e5ac309e63a500ff365e0dd0a1f','{\"parameters.server_env.action.close\":{\"text\":\"Close\"},\"parameters.server_env.action.save\":{\"text\":\"Save {target}\"},\"parameters.server_env.auth.forbidden_message\":{\"text\":\"This panel is reserved for the server admin.\"},\"parameters.server_env.auth.forbidden_title\":{\"text\":\"Access Denied\"},\"parameters.server_env.auth.required_message\":{\"text\":\"Log in to access this panel.\"},\"parameters.server_env.auth.required_title\":{\"text\":\"Login Required\"},\"parameters.server_env.edit.secret_hint\":{\"text\":\"Secret fields remain hidden. If you leave a secret field empty, the current value is retained.\"},\"parameters.server_env.edit.title\":{\"text\":\"Edit {target}\"},\"parameters.server_env.error.forbidden\":{\"text\":\"Access reserved for the server admin.\"},\"parameters.server_env.error.invalid_field_value\":{\"text\":\"The chosen value for {label} is invalid.\"},\"parameters.server_env.error.password_invalid\":{\"text\":\"Invalid password.\"},\"parameters.server_env.error.password_required\":{\"text\":\"Please enter your password.\"},\"parameters.server_env.error.password_unavailable\":{\"text\":\"This account does not have a verifiable local password.\"},\"parameters.server_env.error.read_failed\":{\"text\":\"Unable to read the file {target}.\"},\"parameters.server_env.error.required\":{\"text\":\"Login required.\"},\"parameters.server_env.error.unlock_required\":{\"text\":\"Password confirmation required.\"},\"parameters.server_env.error.write_failed\":{\"text\":\"Unable to write the file {target}. Check permissions or a read-only Docker mount.\"},\"parameters.server_env.feedback.invalid_response\":{\"text\":\"Invalid response.\"},\"parameters.server_env.feedback.operation_done\":{\"text\":\"Operation completed.\"},\"parameters.server_env.feedback.save_failed\":{\"text\":\"Unable to save the file {target}.\"},\"parameters.server_env.feedback.unlock_failed\":{\"text\":\"Verification failed.\"},\"parameters.server_env.feedback.unlock_success\":{\"text\":\"Verification successful.\"},\"parameters.server_env.field.APP_LANG.label\":{\"text\":\"Default Language\"},\"parameters.server_env.field.COOKIE_ROOT_HOST.help\":{\"text\":\"Optional. If provided, forces cookie sharing to this exact root, e.g., dev.opengov.tools to share between dev.opengov.tools and *.dev.opengov.tools without affecting prod.\"},\"parameters.server_env.field.COOKIE_ROOT_HOST.label\":{\"text\":\"Cookie Root\"},\"parameters.server_env.field.COOKIE_SCOPE_MODE.help\":{\"text\":\"Auto isolates dev, beta, and deploy by default in host-only. Environment shares in *.dev.domain.tld. Parent shares in *.domain.tld. Host forces a cookie limited to the current host.\"},\"parameters.server_env.field.COOKIE_SCOPE_MODE.label\":{\"text\":\"Cookie Scope\"},\"parameters.server_env.field.GITHUB_BUGREPORT_LABELS.label\":{\"text\":\"GitHub Labels\"},\"parameters.server_env.field.GITHUB_BUGREPORT_REPO_NAME.label\":{\"text\":\"GitHub Repository Name\"},\"parameters.server_env.field.GITHUB_BUGREPORT_REPO_OWNER.label\":{\"text\":\"GitHub Repository Owner\"},\"parameters.server_env.field.GITHUB_BUGREPORT_TOKEN.label\":{\"text\":\"GitHub Bug Report Token\"},\"parameters.server_env.field.GITHUB_BUGREPORT_USER_AGENT.label\":{\"text\":\"GitHub User-Agent\"},\"parameters.server_env.field.HOME_TITLE.label\":{\"text\":\"Home Page Title\"},\"parameters.server_env.field.MAIL_AUTH.label\":{\"text\":\"SMTP Authentication\"},\"parameters.server_env.field.MAIL_CHARSET.label\":{\"text\":\"Email Charset\"},\"parameters.server_env.field.MAIL_HOST.label\":{\"text\":\"SMTP Server\"},\"parameters.server_env.field.MAIL_PASS.label\":{\"text\":\"SMTP Password\"},\"parameters.server_env.field.MAIL_PORT.label\":{\"text\":\"SMTP Port\"},\"parameters.server_env.field.MAIL_SECURE.label\":{\"text\":\"SMTP Security\"},\"parameters.server_env.field.MAIL_SECURE.placeholder\":{\"text\":\"SSL, tls or empty\"},\"parameters.server_env.field.MAIL_USER.label\":{\"text\":\"SMTP User\"},\"parameters.server_env.field.OPENAI_API_KEY.label\":{\"text\":\"OpenAI Key\"},\"parameters.server_env.field.OPENAI_MODEL.label\":{\"text\":\"OpenAI Model\"},\"parameters.server_env.field.OPENAI_TRANSLATION_MODEL.label\":{\"text\":\"OpenAI Translation Model\"},\"parameters.server_env.field.OPENAI_UPLOAD_API_KEY.label\":{\"text\":\"OpenAI Upload Key\"},\"parameters.server_env.field.ORGANIZATION_SUBDOMAIN_ROUTING.help\":{\"text\":\"Enables URLs like orgname.domain.com. This requires special hosting configuration, with wildcard DNS and a web server capable of accepting subdomains.\"},\"parameters.server_env.field.ORGANIZATION_SUBDOMAIN_ROUTING.label\":{\"text\":\"Organization Subdomains\"},\"parameters.server_env.field.PATREON_CLIENT_ID.label\":{\"text\":\"Patreon Client ID\"},\"parameters.server_env.field.PATREON_CLIENT_SECRET.label\":{\"text\":\"Patreon Client Secret\"},\"parameters.server_env.field.PATREON_CREATOR_CAMPAIGN_ID.label\":{\"text\":\"Patreon Campaign ID\"},\"parameters.server_env.field.PATREON_REDIRECT_URI.label\":{\"text\":\"Patreon Redirect URI\"},\"parameters.server_env.field.PATREON_USER_AGENT.label\":{\"text\":\"Patreon User-Agent\"},\"parameters.server_env.field.PAYPAL_CLIENT_ID.label\":{\"text\":\"PayPal Client ID\"},\"parameters.server_env.field.SITE_TITLE.label\":{\"text\":\"Site Title\"},\"parameters.server_env.field.STADIA_MAPS_API_KEY.label\":{\"text\":\"Stadia Maps Key\"},\"parameters.server_env.field.TELEGRAM_BOT_TOKEN.label\":{\"text\":\"Telegram Token\"},\"parameters.server_env.field.secret_keep.help\":{\"text\":\"Leave empty to keep the current value.\"},\"parameters.server_env.hero.description\":{\"text\":\"This panel allows you to complete global environment file variables outside the database, such as Telegram, Patreon, OpenAI, SMTP, or GitHub.\"},\"parameters.server_env.hero.eyebrow\":{\"text\":\"Sensitive Configuration\"},\"parameters.server_env.hero.target\":{\"text\":\"Target file: {target}\"},\"parameters.server_env.hero.title\":{\"text\":\"Server Admin\"},\"parameters.server_env.hero.unlock_ttl\":{\"text\":\"Verification valid for {minutes} min\"},\"parameters.server_env.option.boolean.false\":{\"text\":\"No\"},\"parameters.server_env.option.boolean.true\":{\"text\":\"Yes\"},\"parameters.server_env.password.unavailable_message\":{\"text\":\"This account does not have a verifiable local password. Editing {target} via this panel is currently blocked.\"},\"parameters.server_env.password.unavailable_title\":{\"text\":\"Password Unavailable\"},\"parameters.server_env.secret.configured\":{\"text\":\"Already Configured\"},\"parameters.server_env.secret.empty\":{\"text\":\"Not Provided\"},\"parameters.server_env.section.ai.intro\":{\"text\":\"Keys and models used by OpenAI functions.\"},\"parameters.server_env.section.ai.title\":{\"text\":\"AI\"},\"parameters.server_env.section.general.intro\":{\"text\":\"Global site settings visible on multiple pages.\"},\"parameters.server_env.section.general.title\":{\"text\":\"General Settings\"},\"parameters.server_env.section.integrations.intro\":{\"text\":\"Optional external server services.\"},\"parameters.server_env.section.integrations.title\":{\"text\":\"Integrations\"},\"parameters.server_env.section.mail.intro\":{\"text\":\"General SMTP server configuration.\"},\"parameters.server_env.section.mail.title\":{\"text\":\"Email\"},\"parameters.server_env.status.saved\":{\"text\":\"The file {target} has been updated.\"},\"parameters.server_env.status.unlocked\":{\"text\":\"Verification successful.\"},\"parameters.server_env.unlock.description\":{\"text\":\"Before displaying the form, enter the password of the connected account. This temporarily unlocks editing of this panel.\"},\"parameters.server_env.unlock.password_label\":{\"text\":\"Current Password\"},\"parameters.server_env.unlock.submit\":{\"text\":\"Open Form\"},\"parameters.server_env.unlock.title\":{\"text\":\"Verify Your Identity\"}}','machine_translated','2026-07-25 11:14:57','2026-07-25 11:15:10'),
(23,'omo_projects','en','ffcce9ebb2bec5eb75efb65337c720625c463ded7016ae23e4bfb3d84e785e04','{\"projects.action.archive\":{\"text\":\"Archive\"},\"projects.action.attach\":{\"text\":\"Attach a project\"},\"projects.action.cancel\":{\"text\":\"Cancel\"},\"projects.action.close\":{\"text\":\"Close\"},\"projects.action.delete\":{\"text\":\"Delete\"},\"projects.action.edit\":{\"text\":\"Edit\"},\"projects.action.move\":{\"text\":\"Move\"},\"projects.action.new\":{\"text\":\"New project\"},\"projects.action.save\":{\"text\":\"Save\"},\"projects.action_error\":{\"text\":\"Unable to update the project.\"},\"projects.archive.confirm\":{\"text\":\"This project is not finished. Archive it anyway?\"},\"projects.attach.empty\":{\"text\":\"No orphan project matches the search.\"},\"projects.attach.hint\":{\"text\":\"Choose an orphan project in the structure.\"},\"projects.attach.search\":{\"text\":\"Search for a project\"},\"projects.attach.select_required\":{\"text\":\"Choose a project to attach.\"},\"projects.attach.submit\":{\"text\":\"Attach\"},\"projects.attach.title\":{\"text\":\"Attach a project\"},\"projects.column.next\":{\"text\":\"Next column\"},\"projects.column.previous\":{\"text\":\"Previous column\"},\"projects.delete.confirm\":{\"text\":\"Permanently delete this project and its {count} subprojects? This action is irreversible.\"},\"projects.detail.badge\":{\"text\":\"Project\"},\"projects.detail.breadcrumb\":{\"text\":\"Parent projects\"},\"projects.detail.breadcrumb.expand\":{\"text\":\"Show all parent projects\"},\"projects.detail.calculated_importance\":{\"text\":\"Calculated strategic importance\"},\"projects.detail.calculated_importance_help\":{\"text\":\"Calculated from declared strategic importance, project chain, and holarchic position.\"},\"projects.detail.context\":{\"text\":\"Context\"},\"projects.detail.created\":{\"text\":\"Created on\"},\"projects.detail.date_end\":{\"text\":\"End\"},\"projects.detail.date_start\":{\"text\":\"Start\"},\"projects.detail.description\":{\"text\":\"Description\"},\"projects.detail.empty_description\":{\"text\":\"No description for this project.\"},\"projects.detail.importance\":{\"text\":\"Strategic importance\"},\"projects.detail.importance_level\":{\"one\":\"{count}/5\",\"other\":\"{count}/5\"},\"projects.detail.none\":{\"text\":\"Not provided\"},\"projects.detail.organisation\":{\"text\":\"Organization\"},\"projects.detail.parent\":{\"text\":\"Parent project\"},\"projects.detail.priority\":{\"text\":\"Priority\"},\"projects.detail.priority_level\":{\"one\":\"P{count}\",\"other\":\"P{count}\"},\"projects.detail.responsible\":{\"text\":\"Responsible\"},\"projects.detail.schedule\":{\"text\":\"Planned dates\"},\"projects.detail.size\":{\"text\":\"Size\"},\"projects.detail.status\":{\"text\":\"Status\"},\"projects.detail.subprojects\":{\"text\":\"Subprojects\"},\"projects.detail.subprojects_empty\":{\"text\":\"No subproject at the moment.\"},\"projects.detail.subprojects_new\":{\"text\":\"New\"},\"projects.drawer.description\":{\"text\":\"Project details and information.\"},\"projects.drawer.title\":{\"text\":\"Project\"},\"projects.empty.children\":{\"text\":\"No project in this context or its direct children.\"},\"projects.empty.column\":{\"text\":\"No project in this column.\"},\"projects.empty.contextual\":{\"text\":\"No project in this context.\"},\"projects.empty.descendants\":{\"text\":\"No project in this context or its descendants.\"},\"projects.error.action\":{\"text\":\"Unknown action.\"},\"projects.error.context\":{\"text\":\"Invalid or inaccessible context.\"},\"projects.error.dates\":{\"text\":\"The end date must be later than or equal to the start date.\"},\"projects.error.forbidden\":{\"text\":\"You cannot modify this project.\"},\"projects.error.holon\":{\"text\":\"The destination holon is invalid or inaccessible.\"},\"projects.error.method\":{\"text\":\"This action must be sent via POST.\"},\"projects.error.not_found\":{\"text\":\"Project not found.\"},\"projects.error.organization\":{\"text\":\"Invalid or inaccessible organization.\"},\"projects.error.save\":{\"text\":\"Unable to save the project.\"},\"projects.error.status\":{\"text\":\"The project status is invalid.\"},\"projects.error.title\":{\"text\":\"The title is required.\"},\"projects.field.capture_mode\":{\"text\":\"Telegram capture mode\"},\"projects.field.description\":{\"text\":\"Description\"},\"projects.field.description_placeholder\":{\"text\":\"What result do you want to achieve?\"},\"projects.field.end_date\":{\"text\":\"Planned end\"},\"projects.field.holon\":{\"text\":\"Associated circle or role\"},\"projects.field.importance\":{\"text\":\"Strategic importance\"},\"projects.field.parent\":{\"text\":\"Parent project\"},\"projects.field.priority\":{\"text\":\"Priority\"},\"projects.field.responsible\":{\"text\":\"Responsible\"},\"projects.field.size\":{\"text\":\"Size\"},\"projects.field.start_date\":{\"text\":\"Planned start\"},\"projects.field.status\":{\"text\":\"Initial status\"},\"projects.field.title\":{\"text\":\"Project title\"},\"projects.form.assignment\":{\"text\":\"Responsibility and hierarchy\"},\"projects.form.attention\":{\"text\":\"Attention level\"},\"projects.form.description\":{\"text\":\"Define the goal, dates, and attention level of the project.\"},\"projects.form.description_field\":{\"text\":\"Simple HTML description\"},\"projects.form.edit_description\":{\"text\":\"Update the goal, dates, and settings of the project.\"},\"projects.form.edit_submit\":{\"text\":\"Save changes\"},\"projects.form.edit_title\":{\"text\":\"Edit project\"},\"projects.form.more_options\":{\"text\":\"Additional options\"},\"projects.form.more_options_toggle\":{\"text\":\"Show or hide additional options\"},\"projects.form.planning\":{\"text\":\"Planning\"},\"projects.form.submit\":{\"text\":\"Create project\"},\"projects.form.title\":{\"text\":\"New project\"},\"projects.holon.choose\":{\"text\":\"Choose a circle or role\"},\"projects.holon_picker.confirm\":{\"text\":\"Use this context\"},\"projects.holon_picker.hint\":{\"text\":\"Choose the circle or role to assign this project.\"},\"projects.holon_picker.title\":{\"text\":\"Choose the circle or role\"},\"projects.importance.none\":{\"text\":\"Not defined\"},\"projects.level.none\":{\"text\":\"Not defined\"},\"projects.list.planned.after_tomorrow\":{\"text\":\"Day after tomorrow\"},\"projects.list.planned.in_progress\":{\"text\":\"In progress\"},\"projects.list.planned.later\":{\"text\":\"Later\"},\"projects.list.planned.next_week\":{\"text\":\"Next week\"},\"projects.list.planned.none\":{\"text\":\"No planning\"},\"projects.list.planned.overdue\":{\"text\":\"Overdue\"},\"projects.list.planned.this_week\":{\"text\":\"This week\"},\"projects.list.planned.tomorrow\":{\"text\":\"Tomorrow\"},\"projects.list.priority.none\":{\"text\":\"No priority\"},\"projects.loading\":{\"text\":\"Loading project...\"},\"projects.loading_error\":{\"text\":\"Unable to load this project.\"},\"projects.move.hint\":{\"text\":\"Choose the destination holon in the structure.\"},\"projects.move.select_required\":{\"text\":\"Choose a destination holon.\"},\"projects.move.submit\":{\"text\":\"Move here\"},\"projects.move.title\":{\"text\":\"Move project\"},\"projects.parent.choose\":{\"text\":\"Choose a project\"},\"projects.parent.none\":{\"text\":\"No parent project\"},\"projects.parent_picker.choose\":{\"text\":\"Use this project\"},\"projects.parent_picker.empty\":{\"text\":\"No project matches the search.\"},\"projects.parent_picker.none\":{\"text\":\"No parent project\"},\"projects.parent_picker.scope_children\":{\"text\":\"Direct children\"},\"projects.parent_picker.scope_descendants\":{\"text\":\"Descendants\"},\"projects.parent_picker.scope_local\":{\"text\":\"Local\"},\"projects.parent_picker.search\":{\"text\":\"Search for a project\"},\"projects.parent_picker.title\":{\"text\":\"Choose the parent project\"},\"projects.priority.none\":{\"text\":\"Not defined\"},\"projects.responsible.help\":{\"text\":\"Only active people from this organization are suggested.\"},\"projects.responsible.none\":{\"text\":\"No responsible\"},\"projects.scope.children\":{\"text\":\"Direct children\"},\"projects.scope.contextual\":{\"text\":\"Local\"},\"projects.scope.descendants\":{\"text\":\"Descendants\"},\"projects.sort.aria\":{\"text\":\"Sort projects\"},\"projects.sort.holon\":{\"text\":\"Holon\"},\"projects.sort.importance\":{\"text\":\"Strategic importance\"},\"projects.sort.planned\":{\"text\":\"Planning\"},\"projects.sort.priority\":{\"text\":\"Priority\"},\"projects.status.blocked\":{\"text\":\"Blocked\"},\"projects.status.done\":{\"text\":\"Done\"},\"projects.status.in_progress\":{\"text\":\"In progress\"},\"projects.status.ready\":{\"text\":\"Ready\"},\"projects.status.review\":{\"text\":\"To review\"},\"projects.status.someday\":{\"text\":\"Someday\"},\"projects.status_move\":{\"text\":\"Change status\"},\"projects.status_update_error\":{\"text\":\"Unable to change status.\"},\"projects.subprojects.label\":{\"text\":\"Subproject status\"},\"projects.success.save\":{\"text\":\"Project saved.\"},\"projects.success.status\":{\"text\":\"Status updated.\"},\"projects.title\":{\"text\":\"Projects\"},\"projects.view.aria\":{\"text\":\"Display mode\"},\"projects.view.kanban\":{\"text\":\"Kanban\"},\"projects.view.list\":{\"text\":\"List\"}}','machine_translated','2026-07-25 11:15:20','2026-07-25 11:15:40'),
(24,'omo_decisions_index','en','5185290b694c46963ce1b3cce5ac0cf6f985b6957738bd6c2511dcd6d70abc95','{\"decisions.index.action.archive\":{\"text\":\"Archive\"},\"decisions.index.action.confirm_archive\":{\"text\":\"Archive this decision?\"},\"decisions.index.action.confirm_delete\":{\"text\":\"Permanently delete this decision and its related items?\"},\"decisions.index.action.consult\":{\"text\":\"Consult\"},\"decisions.index.action.delete\":{\"text\":\"Delete\"},\"decisions.index.action.edit_continue\":{\"text\":\"Continue editing\"},\"decisions.index.action.error_update\":{\"text\":\"Unable to update this decision at the moment.\"},\"decisions.index.action.export\":{\"text\":\"Export\"},\"decisions.index.action.manage\":{\"text\":\"Manage\"},\"decisions.index.action.more\":{\"text\":\"...\"},\"decisions.index.action.more_aria\":{\"text\":\"More actions for this decision\"},\"decisions.index.action.open\":{\"text\":\"Open\"},\"decisions.index.action.open_editor_title\":{\"text\":\"Decisions\"},\"decisions.index.action.participant_qr_codes\":{\"text\":\"Print QR codes\"},\"decisions.index.action.participate\":{\"text\":\"Participate\"},\"decisions.index.action.view\":{\"text\":\"View\"},\"decisions.index.action.view_edit\":{\"text\":\"View / edit\"},\"decisions.index.action.view_results\":{\"text\":\"View results\"},\"decisions.index.card.invited_email\":{\"text\":\"Email invitation\"},\"decisions.index.card.manage\":{\"text\":\"Management\"},\"decisions.index.card.owner\":{\"text\":\"Created by you\"},\"decisions.index.compact.header.activity\":{\"text\":\"Activity\"},\"decisions.index.compact.header.name\":{\"text\":\"Decision\"},\"decisions.index.compact.header.scope\":{\"text\":\"Structure\"},\"decisions.index.compact.header.status\":{\"text\":\"Status\"},\"decisions.index.context.holon_denied\":{\"text\":\"Access denied to this holon.\"},\"decisions.index.context.holon_not_found\":{\"text\":\"Holon not found for this organization.\"},\"decisions.index.context.organization_denied\":{\"text\":\"Access denied to this organization.\"},\"decisions.index.context.organization_invalid\":{\"text\":\"Invalid organization.\"},\"decisions.index.context.organization_not_found\":{\"text\":\"Organization not found.\"},\"decisions.index.controls.density.aria\":{\"text\":\"Display density of decisions\"},\"decisions.index.controls.density.compact\":{\"text\":\"Compact\"},\"decisions.index.controls.density.detail\":{\"text\":\"Detail\"},\"decisions.index.controls.sort.alpha\":{\"text\":\"Alphabetical\"},\"decisions.index.controls.sort.aria\":{\"text\":\"Sort decisions\"},\"decisions.index.controls.sort.time\":{\"text\":\"Time-based\"},\"decisions.index.deadline_label\":{\"text\":\"Deadline\"},\"decisions.index.description\":{\"text\":\"Centralize consultations and decision-making accessible in your organization here, then open the right flow according to their status.\"},\"decisions.index.empty.cta\":{\"text\":\"Create the first decision\"},\"decisions.index.empty.text\":{\"text\":\"Create your first decision to prepare a vote, majority judgment, consent, or consultation.\"},\"decisions.index.empty.title\":{\"text\":\"No decisions at the moment\"},\"decisions.index.error\":{\"text\":\"Unable to load the list at the moment.\"},\"decisions.index.export.format.coming_soon\":{\"text\":\"Coming soon\"},\"decisions.index.export.format.csv\":{\"text\":\"CSV\"},\"decisions.index.export.format.csv_description\":{\"text\":\"Enriched table with type, block, question, details, and results.\"},\"decisions.index.export.format.json\":{\"text\":\"JSON\"},\"decisions.index.export.format.json_description\":{\"text\":\"Ballot blueprint and structured results, without full dump.\"},\"decisions.index.export.format.pdf\":{\"text\":\"PDF\"},\"decisions.index.export.format.pdf_description\":{\"text\":\"Presentation version prepared for later.\"},\"decisions.index.export.format.xml\":{\"text\":\"XML\"},\"decisions.index.export.format.xml_description\":{\"text\":\"Same structured content as JSON, in XML format.\"},\"decisions.index.export.modal_intro\":{\"text\":\"Choose the export format suitable for this decision-making mode.\"},\"decisions.index.export.modal_title\":{\"text\":\"Export this ballot\"},\"decisions.index.export.open\":{\"text\":\"Download\"},\"decisions.index.filters.holon.all\":{\"text\":\"All structures\"},\"decisions.index.filters.holon.label\":{\"text\":\"Structure\"},\"decisions.index.filters.holon.none\":{\"text\":\"Without structure\"},\"decisions.index.filters.method.all\":{\"text\":\"All methods\"},\"decisions.index.filters.method.consent\":{\"text\":\"Consent\"},\"decisions.index.filters.method.label\":{\"text\":\"Method\"},\"decisions.index.filters.method.majority_judgment\":{\"text\":\"Majority judgment\"},\"decisions.index.filters.method.simple_vote\":{\"text\":\"Simple vote\"},\"decisions.index.filters.reset\":{\"text\":\"Reset\"},\"decisions.index.filters.search.label\":{\"text\":\"Search by title\"},\"decisions.index.filters.search.placeholder\":{\"text\":\"Search for a decision\"},\"decisions.index.filters.status.active\":{\"text\":\"Active\"},\"decisions.index.filters.status.all\":{\"text\":\"All\"},\"decisions.index.filters.status.archived\":{\"text\":\"Archived\"},\"decisions.index.filters.status.consultation\":{\"text\":\"In consultation\"},\"decisions.index.filters.status.draft\":{\"text\":\"In preparation\"},\"decisions.index.filters.status.evaluation\":{\"text\":\"In evaluation\"},\"decisions.index.filters.status.results\":{\"text\":\"Results\"},\"decisions.index.filters.status.scheduled\":{\"text\":\"Scheduled\"},\"decisions.index.filters.toggle.hide\":{\"text\":\"Hide filters\"},\"decisions.index.filters.toggle.show\":{\"text\":\"Show filters\"},\"decisions.index.filters.type.all\":{\"text\":\"All types\"},\"decisions.index.filters.type.consultation\":{\"text\":\"Consultative\"},\"decisions.index.filters.type.decision\":{\"text\":\"Decision-making\"},\"decisions.index.filters.type.label\":{\"text\":\"Type\"},\"decisions.index.group.earlier\":{\"text\":\"Earlier\"},\"decisions.index.group.last_month\":{\"text\":\"Last month\"},\"decisions.index.group.last_week\":{\"text\":\"Last week\"},\"decisions.index.group.last_year\":{\"text\":\"Last year\"},\"decisions.index.group.this_month\":{\"text\":\"This month\"},\"decisions.index.group.this_week\":{\"text\":\"This week\"},\"decisions.index.group.this_year\":{\"text\":\"This year\"},\"decisions.index.group.today\":{\"text\":\"Today\"},\"decisions.index.group.too_far\":{\"text\":\"Too far\"},\"decisions.index.group.yesterday\":{\"text\":\"Yesterday\"},\"decisions.index.last_activity_label\":{\"text\":\"Last activity\"},\"decisions.index.loading\":{\"text\":\"Loading decisions…\"},\"decisions.index.method_label\":{\"text\":\"Method\"},\"decisions.index.new\":{\"text\":\"New decision\"},\"decisions.index.no_holon\":{\"text\":\"No linked structure\"},\"decisions.index.no_results.text\":{\"text\":\"Try another status, broaden the search, or reset the filters.\"},\"decisions.index.no_results.title\":{\"text\":\"No results with these filters\"},\"decisions.index.owner_label\":{\"text\":\"In charge\"},\"decisions.index.participants_label\":{\"text\":\"Participants\"},\"decisions.index.proposals_label\":{\"text\":\"Proposals\"},\"decisions.index.responses_label\":{\"text\":\"Responses\"},\"decisions.index.scope.children\":{\"text\":\"Direct children\"},\"decisions.index.scope.contextual\":{\"text\":\"Contextual\"},\"decisions.index.scope.descendants\":{\"text\":\"Descendants\"},\"decisions.index.scope_label\":{\"text\":\"Structure\"},\"decisions.index.title\":{\"text\":\"Decisions\"},\"decisions.index.type_label\":{\"text\":\"Type\"}}','machine_translated','2026-07-25 11:15:26','2026-07-25 11:15:40'),
(27,'omo_policy','en','f8552f359ef3640d166c6644fc54771ee825759d8085d611bf7567fde4012d66','{\"policy.close\":{\"text\":\"Close\"},\"policy.description\":{\"text\":\"Rules applicable to the current context.\"},\"policy.description_label\":{\"text\":\"Rule\"},\"policy.drawer.description\":{\"text\":\"This rule will be linked to the current context.\"},\"policy.drawer.title\":{\"text\":\"New local rule\"},\"policy.empty\":{\"text\":\"No rules in this context.\"},\"policy.error.context\":{\"text\":\"Invalid or inaccessible context.\"},\"policy.error.forbidden\":{\"text\":\"You cannot create a rule in this context.\"},\"policy.error.load\":{\"text\":\"Unable to load the form.\"},\"policy.error.method\":{\"text\":\"This action must be sent via POST.\"},\"policy.error.save\":{\"text\":\"Unable to save the rule.\"},\"policy.expiration\":{\"text\":\"Expires on {date}\"},\"policy.field.description\":{\"text\":\"Rule\"},\"policy.field.expiration_date\":{\"text\":\"Expiration date\"},\"policy.field.intention\":{\"text\":\"Intention\"},\"policy.field.review_date\":{\"text\":\"Review date\"},\"policy.field.title\":{\"text\":\"Title\"},\"policy.intention\":{\"text\":\"Intention\"},\"policy.new\":{\"text\":\"New rule\"},\"policy.review\":{\"text\":\"To be reviewed on {date}\"},\"policy.save\":{\"text\":\"Save\"},\"policy.success.save\":{\"text\":\"Rule saved.\"},\"policy.title\":{\"text\":\"Regulation\"}}','machine_translated','2026-07-25 11:15:46','2026-07-25 11:15:50'),
(29,'omo_calendar_index','en','ba735f0a0155ce61529b7855b48be4302cda03f70df56628cb88ffcd7bf3e145','{\"calendar.action.add\":{\"text\":\"Add an event\"},\"calendar.action.delete\":{\"text\":\"Delete\"},\"calendar.action.edit\":{\"text\":\"Edit\"},\"calendar.action.more\":{\"text\":\"Actions\"},\"calendar.action.today\":{\"text\":\"Today\"},\"calendar.axis.all_day\":{\"text\":\"All day\"},\"calendar.confirm.delete\":{\"text\":\"Delete this event?\"},\"calendar.context.organization\":{\"text\":\"Organization\"},\"calendar.day.fri\":{\"text\":\"Fri\"},\"calendar.day.mon\":{\"text\":\"Mon\"},\"calendar.day.more\":{\"one\":\"+{count} other\",\"other\":\"+{count} others\"},\"calendar.day.sat\":{\"text\":\"Sat\"},\"calendar.day.sun\":{\"text\":\"Sun\"},\"calendar.day.thu\":{\"text\":\"Thu\"},\"calendar.day.tue\":{\"text\":\"Tue\"},\"calendar.day.wed\":{\"text\":\"Wed\"},\"calendar.delete.documents.no\":{\"text\":\"No\"},\"calendar.delete.documents.question\":{\"text\":\"Do you want to delete the associated documents?\"},\"calendar.delete.documents.title\":{\"text\":\"Associated documents\"},\"calendar.delete.documents.yes\":{\"text\":\"Yes\"},\"calendar.drawer.description\":{\"text\":\"View the details and edit if necessary.\"},\"calendar.drawer.title\":{\"text\":\"Event\"},\"calendar.empty.day\":{\"text\":\"No events on this day.\"},\"calendar.empty.list\":{\"text\":\"No upcoming events.\"},\"calendar.empty.month\":{\"text\":\"No events this period.\"},\"calendar.empty.week\":{\"text\":\"No events this week.\"},\"calendar.error.delete\":{\"text\":\"Unable to delete this event.\"},\"calendar.error.load_form\":{\"text\":\"Unable to load this content.\"},\"calendar.list.column.context\":{\"text\":\"Context\"},\"calendar.list.column.date\":{\"text\":\"Date\"},\"calendar.list.column.event\":{\"text\":\"Event\"},\"calendar.list.column.schedule\":{\"text\":\"Schedule\"},\"calendar.loading\":{\"text\":\"Loading...\"},\"calendar.page.description\":{\"text\":\"View your organization\'s events and add new ones.\"},\"calendar.page.title\":{\"text\":\"Calendar\"},\"calendar.scope.children\":{\"text\":\"Direct children\"},\"calendar.scope.contextual\":{\"text\":\"Contextual\"},\"calendar.scope.descendants\":{\"text\":\"Descendants\"},\"calendar.section.next_month\":{\"text\":\"Next month\"},\"calendar.section.next_week\":{\"text\":\"Next week\"},\"calendar.section.this_month\":{\"text\":\"This month\"},\"calendar.section.this_week\":{\"text\":\"This week\"},\"calendar.section.today\":{\"text\":\"Today\"},\"calendar.section.tomorrow\":{\"text\":\"Tomorrow\"},\"calendar.summary.day\":{\"text\":\"{count} event(s) this day\"},\"calendar.summary.list\":{\"text\":\"{count} upcoming event(s)\"},\"calendar.summary.month\":{\"text\":\"{count} event(s) this month\"},\"calendar.summary.week\":{\"text\":\"{count} event(s) this week\"},\"calendar.view.day\":{\"text\":\"Day\"},\"calendar.view.list\":{\"text\":\"List\"},\"calendar.view.month\":{\"text\":\"Month\"},\"calendar.view.week\":{\"text\":\"Week\"}}','machine_translated','2026-07-25 11:15:55','2026-07-25 11:16:01'),
(31,'omo_personal_space_panel','en','99a14c53fe57c583c8aecee4a7b4021b12046df2284878edf489a61a4338598a','{\"personal_space.calendar.context.organization\":{\"text\":\"Org\"},\"personal_space.calendar.empty\":{\"text\":\"No upcoming dates for your contexts.\"},\"personal_space.date.unknown\":{\"text\":\"Unknown date\"},\"personal_space.decisions.action\":{\"one\":\"{count} decision to make\",\"other\":\"{count} decisions to make\"},\"personal_space.decisions.consultation\":{\"one\":\"{count} consultation in progress\",\"other\":\"{count} consultations in progress\"},\"personal_space.decisions.empty\":{\"text\":\"No decisions to track at the moment.\"},\"personal_space.decisions.finalize\":{\"one\":\"{count} draft decision to finalize\",\"other\":\"{count} draft decisions to finalize\"},\"personal_space.decisions.responded\":{\"one\":\"including {count} already responded\",\"other\":\"including {count} already responded\"},\"personal_space.decisions.results\":{\"one\":\"{count} completed decision with result to review\",\"other\":\"{count} completed decisions with results to review\"},\"personal_space.documents.empty\":{\"text\":\"No recent documents in this context.\"},\"personal_space.empty\":{\"text\":\"No personal summary available with active applications at the moment.\"},\"personal_space.heading\":{\"text\":\"Personal Space\"},\"personal_space.intro\":{\"text\":\"A quick summary of topics that concern you in this space.\"},\"personal_space.login_required\":{\"text\":\"Log in to view your personal summary.\"},\"personal_space.open_app\":{\"text\":\"Open\"},\"personal_space.section.calendar\":{\"text\":\"My Upcoming Meetings\"},\"personal_space.section.decisions\":{\"text\":\"Decisions\"},\"personal_space.section.documents_recent\":{\"text\":\"Documents - Latest Changes\"},\"personal_space.section.structure\":{\"text\":\"Structure\"},\"personal_space.section.team\":{\"text\":\"Team\"},\"personal_space.structure.empty\":{\"text\":\"No recent changes to display.\"},\"personal_space.team.empty\":{\"text\":\"No upcoming anniversaries to display.\"},\"personal_space.team.pro.new\":{\"text\":\"New\"},\"personal_space.team.pro.new_detail_prefix\":{\"text\":\"Arrived on\"},\"personal_space.team.pro.soon_prefix\":{\"text\":\"Pro anniversary in\"},\"personal_space.team.pro.today\":{\"text\":\"Pro anniversary today\"},\"personal_space.team.tag.personal\":{\"text\":\"Personal\"},\"personal_space.team.tag.pro\":{\"text\":\"Pro\"}}','machine_translated','2026-07-25 11:22:13','2026-07-25 11:22:17'),
(33,'omo_team_module','en','e7a26a02adc42dd74ae6c21138da71c738f7ca52f05c6eb1de51633990ae57bb','{\"team.action.add_member\":{\"text\":\"Add a member\"},\"team.action.cancel_invitation\":{\"text\":\"Cancel invitation\"},\"team.action.grant_context_admin\":{\"text\":\"Set as admin of the context {context}\"},\"team.action.remove_from_context\":{\"text\":\"Remove from context {context}\"},\"team.action.revoke_context_admin\":{\"text\":\"Remove admin status from context {context}\"},\"team.api.action_completed\":{\"text\":\"Action completed.\"},\"team.api.context_not_found\":{\"text\":\"Context not found.\"},\"team.api.invalid_action\":{\"text\":\"Invalid member action.\"},\"team.api.invitation_resend_failed\":{\"text\":\"The invitation could not be resent.\"},\"team.api.invitation_resent\":{\"text\":\"Invitation resent.\"},\"team.api.no_right_add_member\":{\"text\":\"You do not have the right to add a member in this context.\"},\"team.api.no_right_manage_admin\":{\"text\":\"You do not have the right to manage admin status in this context.\"},\"team.api.no_right_modify_context\":{\"text\":\"You do not have the right to modify this context.\"},\"team.api.pending_admin_invitation_not_found\":{\"text\":\"No pending admin invitation was found for this person.\"},\"team.api.pending_invitation_not_found\":{\"text\":\"No pending invitation was found for this person.\"},\"team.api.unknown_action\":{\"text\":\"Unknown action.\"},\"team.column.first_name\":{\"text\":\"First name\"},\"team.column.identity\":{\"text\":\"Identity\"},\"team.column.name\":{\"text\":\"Surname\"},\"team.column.phone\":{\"text\":\"Phone\"},\"team.confirm.cancel_invitation\":{\"text\":\"Cancel the invitation sent to {name}?\"},\"team.confirm.grant_context_admin\":{\"text\":\"Set {name} as admin of the context {context}?\"},\"team.confirm.remove\":{\"text\":\"Remove {name} from the context {context}?\"},\"team.confirm.revoke_context_admin\":{\"text\":\"Remove admin status from {name} for the context {context}?\"},\"team.empty.children\":{\"text\":\"No person is yet linked to this context or its direct children.\"},\"team.empty.contextual\":{\"text\":\"No person is yet linked to this {context_type}.\"},\"team.empty.descendants\":{\"text\":\"No person is yet linked to this context and its descendants.\"},\"team.error.invalid_organization\":{\"text\":\"Invalid organization.\"},\"team.error.people_forbidden\":{\"text\":\"Access to the people list denied.\"},\"team.holon_type.circle\":{\"text\":\"circle\"},\"team.holon_type.context\":{\"text\":\"context\"},\"team.holon_type.group\":{\"text\":\"group\"},\"team.holon_type.holon\":{\"text\":\"holon\"},\"team.holon_type.organization\":{\"text\":\"organization\"},\"team.holon_type.role\":{\"text\":\"role\"},\"team.map.empty.children\":{\"text\":\"No member has a geographical position in this context or its direct children yet.\"},\"team.map.empty.contextual\":{\"text\":\"No member has a geographical position in this context yet.\"},\"team.map.empty.descendants\":{\"text\":\"No member has a geographical position in this context and its descendants yet.\"},\"team.map.open_profile\":{\"text\":\"Open profile\"},\"team.map.summary\":{\"one\":\"{count} geolocated member.\",\"other\":\"{count} geolocated members.\"},\"team.member.actions_for\":{\"text\":\"Actions for {name}\"},\"team.member.added\":{\"text\":\"Added\"},\"team.member.admin_context\":{\"text\":\"Context admin\"},\"team.member.admin_organization\":{\"text\":\"Organization admin\"},\"team.member.admin_short\":{\"text\":\"Admin\"},\"team.member.email\":{\"text\":\"Email\"},\"team.member.last_connection\":{\"text\":\"Connection\"},\"team.member.last_seen_global\":{\"text\":\"{organization} (global: {global})\"},\"team.member.never\":{\"text\":\"Never\"},\"team.member.not_provided\":{\"text\":\"Not provided\"},\"team.member.open_contextual_profile\":{\"text\":\"Open contextual profile of {name}\"},\"team.member.pending\":{\"text\":\"Pending\"},\"team.member.photo_coming\":{\"text\":\"Photo coming soon\"},\"team.member.this_member\":{\"text\":\"this member\"},\"team.member.user_fallback\":{\"text\":\"User {userId}\"},\"team.message.update_failed\":{\"text\":\"Unable to update this member.\"},\"team.message.update_failed_later\":{\"text\":\"Unable to update this member at the moment.\"},\"team.message.update_success\":{\"text\":\"Update successful.\"},\"team.popup.choose_action\":{\"text\":\"Choose the action to apply in this {context}.\"},\"team.popup.context_forbidden\":{\"text\":\"Access to this context denied.\"},\"team.popup.context_not_found\":{\"text\":\"Context not found for this organization.\"},\"team.popup.context_prefix\":{\"text\":\"Context\"},\"team.popup.contextual_actions\":{\"text\":\"Contextual actions\"},\"team.popup.invalid_member_context\":{\"text\":\"Invalid member context.\"},\"team.popup.member_management\":{\"text\":\"Member management\"},\"team.popup.no_manage_rights\":{\"text\":\"You do not have the rights to modify this {context}.\"},\"team.popup.organization_context_missing\":{\"text\":\"No organizational context is available.\"},\"team.popup.organization_forbidden\":{\"text\":\"Access to this organization denied.\"},\"team.popup.organization_not_found\":{\"text\":\"Organization not found.\"},\"team.popup.user_forbidden\":{\"text\":\"Access to this user denied.\"},\"team.popup.user_not_found\":{\"text\":\"User not found.\"},\"team.scope.children\":{\"text\":\"Direct children\"},\"team.scope.contextual\":{\"text\":\"Contextual\"},\"team.scope.descendants\":{\"text\":\"Descendants\"},\"team.scope.members_aria\":{\"text\":\"Members scope\"},\"team.title\":{\"text\":\"Team\"},\"team.view.cards\":{\"text\":\"Cards\"},\"team.view.choice_aria\":{\"text\":\"View choice\"},\"team.view.compact\":{\"text\":\"Compact\"},\"team.view.map\":{\"text\":\"Geo map\"}}','machine_translated','2026-07-25 11:23:39','2026-07-25 11:23:49'),
(39,'omo_parameters_holon_templates','en','4e48d5b40f48a478abf8cb59509f11c4747309fadaa196dde0bdcd243f99503e','{\"parameters.holon_templates.action.add_property\":{\"text\":\"Add a property\"},\"parameters.holon_templates.action.close\":{\"text\":\"Close\"},\"parameters.holon_templates.action.delete_model\":{\"text\":\"Delete\"},\"parameters.holon_templates.action.new_model\":{\"text\":\"New model\"},\"parameters.holon_templates.action.new_submodel\":{\"text\":\"Sub-model\"},\"parameters.holon_templates.action.resize_columns\":{\"text\":\"Adjust column width\"},\"parameters.holon_templates.action.save_model\":{\"text\":\"Save\"},\"parameters.holon_templates.action.save_organization\":{\"text\":\"Save organization\"},\"parameters.holon_templates.badge.active_inheritance\":{\"text\":\"Active inheritance\"},\"parameters.holon_templates.confirm.delete_model\":{\"text\":\"Delete the model {templateName}?\\n\\nIts sub-models will also be deleted. This action is final.\"},\"parameters.holon_templates.confirm.inheritance_change\":{\"text\":\"Changing the inheritance of this model may modify or hide properties and values on this model, as well as on the models and holons that inherit from it.\\n\\nConfirm this operation?\"},\"parameters.holon_templates.error.admin_mode_required\":{\"text\":\"Enable the organization\'s Admin mode to use this editor.\"},\"parameters.holon_templates.error.admin_required\":{\"text\":\"This editor is reserved for admins of this organization.\"},\"parameters.holon_templates.error.delete_model\":{\"text\":\"The model could not be deleted.\"},\"parameters.holon_templates.error.invalid_request\":{\"text\":\"The request sent is invalid.\"},\"parameters.holon_templates.error.no_organization\":{\"text\":\"No organization is currently selected.\"},\"parameters.holon_templates.error.organization_not_found\":{\"text\":\"The requested organization cannot be found.\"},\"parameters.holon_templates.error.save_model\":{\"text\":\"The model could not be saved.\"},\"parameters.holon_templates.error.save_organization\":{\"text\":\"The organization could not be saved.\"},\"parameters.holon_templates.error.structure_required\":{\"text\":\"Holon templates are available only when a structure exists and the Structure app is active.\"},\"parameters.holon_templates.field.admin_bound_inherited_placeholder\":{\"text\":\"Inherited ({value})\"},\"parameters.holon_templates.field.admin_max\":{\"text\":\"Maximum number of {adminLabel}\"},\"parameters.holon_templates.field.admin_max_placeholder\":{\"text\":\"unlimited\"},\"parameters.holon_templates.field.admin_min\":{\"text\":\"Minimum number of {adminLabel}\"},\"parameters.holon_templates.field.banner\":{\"text\":\"Banner\"},\"parameters.holon_templates.field.base_type\":{\"text\":\"Base type\"},\"parameters.holon_templates.field.color\":{\"text\":\"Color\"},\"parameters.holon_templates.field.color_empty_help\":{\"text\":\"Otherwise the color remains empty.\"},\"parameters.holon_templates.field.icon\":{\"text\":\"Icon\"},\"parameters.holon_templates.field.inherits_from\":{\"text\":\"Inherits from\"},\"parameters.holon_templates.field.lock_admin_max\":{\"text\":\"Lock maximum\"},\"parameters.holon_templates.field.lock_admin_min\":{\"text\":\"Lock minimum\"},\"parameters.holon_templates.field.locked_banner\":{\"text\":\"Locked banner\"},\"parameters.holon_templates.field.locked_icon\":{\"text\":\"Locked icon\"},\"parameters.holon_templates.field.logo_icon\":{\"text\":\"Logo / Icon\"},\"parameters.holon_templates.field.model_name\":{\"text\":\"Model name\"},\"parameters.holon_templates.field.name\":{\"text\":\"Name\"},\"parameters.holon_templates.field.override\":{\"text\":\"Override\"},\"parameters.holon_templates.field.public_model_name\":{\"text\":\"Public model name\"},\"parameters.holon_templates.field.share_public\":{\"text\":\"Publicly share this organization model\"},\"parameters.holon_templates.field.share_public_help\":{\"text\":\"Enables an organization model retrievable by others.\"},\"parameters.holon_templates.field.shared_media\":{\"text\":\"Transmitted illustrations\"},\"parameters.holon_templates.field.shared_model_media\":{\"text\":\"Shared model illustrations\"},\"parameters.holon_templates.flag.admin_parent\":{\"text\":\"{adminLabel} parent\"},\"parameters.holon_templates.flag.admin_parent_help\":{\"text\":\"The {adminLabel} of this role are also {adminLabel} of the parent circle.\"},\"parameters.holon_templates.flag.link\":{\"text\":\"Link\"},\"parameters.holon_templates.flag.link_help\":{\"text\":\"Indicates that the role also belongs to the encompassing circle.\"},\"parameters.holon_templates.flag.locked_name\":{\"text\":\"Locked name\"},\"parameters.holon_templates.flag.locked_name_help\":{\"text\":\"Imposes the same name on all instances of this template.\"},\"parameters.holon_templates.flag.mandatory\":{\"text\":\"Mandatory\"},\"parameters.holon_templates.flag.mandatory_help\":{\"text\":\"Indicates that sub-circles must implement this template.\"},\"parameters.holon_templates.flag.unique\":{\"text\":\"Unique\"},\"parameters.holon_templates.flag.unique_help\":{\"text\":\"Limits to a single implementation per circle, including groups.\"},\"parameters.holon_templates.flag.visible\":{\"text\":\"Visible\"},\"parameters.holon_templates.flag.visible_help\":{\"text\":\"Display this template in the circle where it is defined.\"},\"parameters.holon_templates.form.existing_model_description\":{\"text\":\"Adjust this model and its inheritable properties.\"},\"parameters.holon_templates.form.eyebrow\":{\"text\":\"Editing\"},\"parameters.holon_templates.form.model_title\":{\"text\":\"Model\"},\"parameters.holon_templates.form.new_model\":{\"text\":\"New model\"},\"parameters.holon_templates.form.new_model_description\":{\"text\":\"Choose a base type, its place in the hierarchy, and the properties it will transmit.\"},\"parameters.holon_templates.form.new_model_description_short\":{\"text\":\"Choose its base type then add the properties to transmit.\"},\"parameters.holon_templates.form.organization\":{\"text\":\"Organization\"},\"parameters.holon_templates.form.organization_description\":{\"text\":\"Adjust the local properties of this organization here.\"},\"parameters.holon_templates.form.selection_hint_definition\":{\"text\":\"Modifying the local properties of this organization.\"},\"parameters.holon_templates.form.selection_hint_existing\":{\"text\":\"Modifying the selected model.\"},\"parameters.holon_templates.form.selection_hint_new\":{\"text\":\"New model not yet saved.\"},\"parameters.holon_templates.form.welcome_action\":{\"text\":\"Create a new model\"},\"parameters.holon_templates.form.welcome_description\":{\"text\":\"Select a model from the list to modify it, or create a new one to define a reusable structure.\"},\"parameters.holon_templates.form.welcome_title\":{\"text\":\"Manage your holon models\"},\"parameters.holon_templates.header.organization_description\":{\"text\":\"Modify the properties, illustrations, and local settings of the organization holon here, even when it is not a template.\"},\"parameters.holon_templates.header.organization_title\":{\"text\":\"Organization properties\"},\"parameters.holon_templates.hero.current_context\":{\"text\":\"Current context\"},\"parameters.holon_templates.hero.definition_mode\":{\"text\":\"This panel acts as a local definition editor for this real holon.\"},\"parameters.holon_templates.hero.template_mode\":{\"text\":\"Create a library of reusable models for your circles, roles, projects, and other structures.\"},\"parameters.holon_templates.media.banner_label\":{\"text\":\"Banner\"},\"parameters.holon_templates.media.icon_label\":{\"text\":\"Icon\"},\"parameters.holon_templates.permission.add_range\":{\"text\":\"Add a scope...\"},\"parameters.holon_templates.permission.admins\":{\"text\":\"{adminLabel}\"},\"parameters.holon_templates.permission.children\":{\"text\":\"Sub-elements\"},\"parameters.holon_templates.permission.members\":{\"text\":\"Members\"},\"parameters.holon_templates.permission.none_available\":{\"text\":\"No rights are available.\"},\"parameters.holon_templates.permission.none_selected\":{\"text\":\"No scope selected.\"},\"parameters.holon_templates.permission.organization\":{\"text\":\"Entire organization\"},\"parameters.holon_templates.permission.parent_circle_elements\":{\"text\":\"Parent circle elements\"},\"parameters.holon_templates.permission.remove_range\":{\"text\":\"Remove this scope\"},\"parameters.holon_templates.permission.self\":{\"text\":\"Current element\"},\"parameters.holon_templates.property.action.exclude\":{\"text\":\"Exclude\"},\"parameters.holon_templates.property.action.move_down\":{\"text\":\"Move down\"},\"parameters.holon_templates.property.action.move_up\":{\"text\":\"Move up\"},\"parameters.holon_templates.property.action.remove\":{\"text\":\"Remove\"},\"parameters.holon_templates.property.allowed_holon_types\":{\"text\":\"Allowed holon types\"},\"parameters.holon_templates.property.detail_fallback\":{\"text\":\"Item\"},\"parameters.holon_templates.property.empty\":{\"text\":\"No property for this model. You can start by adding one.\"},\"parameters.holon_templates.property.format\":{\"text\":\"Format\"},\"parameters.holon_templates.property.help.date\":{\"text\":\"Leave empty to impose nothing. The date will be inherited in YYYY-MM-DD format.\"},\"parameters.holon_templates.property.help.default\":{\"text\":\"If this value remains empty, each derived holon can freely define its content.\"},\"parameters.holon_templates.property.help.html\":{\"text\":\"Simple HTML format: bold, italic, lists, and links.\"},\"parameters.holon_templates.property.help.list_authority\":{\"text\":\"The local authorities defined here are automatically instantiated on each holon using this model.\"},\"parameters.holon_templates.property.help.list_date\":{\"text\":\"One line per date in YYYY-MM-DD format. Leave empty to impose nothing.\"},\"parameters.holon_templates.property.help.list_detail\":{\"text\":\"Each line contains a title and a description. Leave empty to impose nothing.\"},\"parameters.holon_templates.property.help.list_holon\":{\"text\":\"Check the base holons to include in this template. Instances can then add more.\"},\"parameters.holon_templates.property.help.list_number\":{\"text\":\"One line per number. Leave empty to impose nothing.\"},\"parameters.holon_templates.property.help.list_project\":{\"text\":\"Check the projects to include in this list.\"},\"parameters.holon_templates.property.help.list_text\":{\"text\":\"One line per item. Leave empty to impose nothing.\"},\"parameters.holon_templates.property.help.number\":{\"text\":\"Leave empty to impose nothing. Use an integer or decimal number.\"},\"parameters.holon_templates.property.list_item_type\":{\"text\":\"List item type\"},\"parameters.holon_templates.property.name\":{\"text\":\"Name\"},\"parameters.holon_templates.property.no_template_for_types\":{\"text\":\"No template available for the chosen types.\"},\"parameters.holon_templates.property.origin_inherited\":{\"text\":\"Inherited\"},\"parameters.holon_templates.property.origin_local\":{\"text\":\"Local\"},\"parameters.holon_templates.property.placeholder.description\":{\"text\":\"Description\"},\"parameters.holon_templates.property.placeholder.empty\":{\"text\":\"Leave empty to impose nothing.\"},\"parameters.holon_templates.property.placeholder.generic\":{\"text\":\"Ex.: Property\"},\"parameters.holon_templates.property.placeholder.number\":{\"text\":\"Ex.: 42\"},\"parameters.holon_templates.property.placeholder.reason\":{\"text\":\"Ex.: Reason for being\"},\"parameters.holon_templates.property.placeholder.title\":{\"text\":\"Title\"},\"parameters.holon_templates.property.toggle_locked\":{\"text\":\"Locked\"},\"parameters.holon_templates.property.toggle_mandatory\":{\"text\":\"Mandatory\"},\"parameters.holon_templates.property.value_default\":{\"text\":\"Default inherited value\"},\"parameters.holon_templates.property.value_inherited\":{\"text\":\"Inherited value\"},\"parameters.holon_templates.property.value_local_added\":{\"text\":\"Local added value\"},\"parameters.holon_templates.scope.aria\":{\"text\":\"Model scope\"},\"parameters.holon_templates.scope.children\":{\"text\":\"Direct children\"},\"parameters.holon_templates.scope.contextual\":{\"text\":\"Contextual\"},\"parameters.holon_templates.scope.current_context_fallback\":{\"text\":\"current\"},\"parameters.holon_templates.scope.descendants\":{\"text\":\"Descendants\"},\"parameters.holon_templates.scope.note_children\":{\"text\":\"models defined in {contextName} and its direct children are displayed here.\"},\"parameters.holon_templates.scope.note_children_prefix\":{\"text\":\"Direct children mode:\"},\"parameters.holon_templates.scope.note_contextual\":{\"text\":\"only models useful to the {contextName} context are displayed here.\"},\"parameters.holon_templates.scope.note_contextual_prefix\":{\"text\":\"Contextual mode:\"},\"parameters.holon_templates.scope.note_descendants\":{\"text\":\"models defined in {contextName} and all its descendants are displayed here.\"},\"parameters.holon_templates.scope.note_descendants_prefix\":{\"text\":\"Descendants mode:\"},\"parameters.holon_templates.section.admin_bounds\":{\"text\":\"{adminLabel}\"},\"parameters.holon_templates.section.admin_bounds_description\":{\"text\":\"The minimum is required before adding a normal member. Leave the maximum empty to not limit the number of {adminLabel}.\"},\"parameters.holon_templates.section.appearance\":{\"text\":\"Appearance\"},\"parameters.holon_templates.section.appearance_description\":{\"text\":\"The color, icon, and banner come after defining the properties.\"},\"parameters.holon_templates.section.holon\":{\"text\":\"Holon\"},\"parameters.holon_templates.section.permissions\":{\"text\":\"Permissions\"},\"parameters.holon_templates.section.permissions_description\":{\"text\":\"Enable the rights carried by this template here and choose their scopes.\"},\"parameters.holon_templates.section.properties\":{\"text\":\"Properties\"},\"parameters.holon_templates.section.properties_description_model\":{\"text\":\"Add the properties visible on the nodes derived from this model.\"},\"parameters.holon_templates.section.properties_description_organization\":{\"text\":\"Add here the properties directly carried by this organization.\"},\"parameters.holon_templates.section.public_share\":{\"text\":\"Public sharing\"},\"parameters.holon_templates.section.public_share_description\":{\"text\":\"These fields are used only when the organization is shared as a reusable model.\"},\"parameters.holon_templates.section.structure\":{\"text\":\"Model structure\"},\"parameters.holon_templates.status.close_message\":{\"text\":\"Close message\"},\"parameters.holon_templates.status.deleted_model\":{\"text\":\"Model deleted.\"},\"parameters.holon_templates.status.saved_model\":{\"text\":\"Model saved.\"},\"parameters.holon_templates.status.saved_organization\":{\"text\":\"Organization saved.\"},\"parameters.holon_templates.tree.current_holon\":{\"text\":\"Edited holon\"},\"parameters.holon_templates.tree.empty\":{\"text\":\"No model is defined yet.\"},\"parameters.holon_templates.tree.models\":{\"text\":\"Model tree\"},\"parameters.holon_templates.tree.root\":{\"text\":\"Model root\"}}','machine_translated','2026-07-28 10:31:04','2026-07-30 07:53:24'),
(49,'omo_decision_public','en','5f8f9bdb60a71cd1b1f00dcee4b307bcd44e57056c69e1c528e78540aefed4e4','{\"decisions.public.access.code\":{\"text\":\"Code received by email\"},\"decisions.public.access.code_placeholder\":{\"text\":\"123456\"},\"decisions.public.access.code_sent\":{\"text\":\"A personal code and a direct link have just been sent to {email}.\"},\"decisions.public.access.code_valid\":{\"text\":\"Valid code. Redirecting…\"},\"decisions.public.access.code_verification_failed\":{\"text\":\"Unable to verify this code at the moment.\"},\"decisions.public.access.consume_failed\":{\"text\":\"The code is correct, but access could not be completed. Please try again shortly.\"},\"decisions.public.access.create_failed\":{\"text\":\"Unable to create this participation at the moment.\"},\"decisions.public.access.email\":{\"text\":\"Email address\"},\"decisions.public.access.email_placeholder\":{\"text\":\"name@example.org\"},\"decisions.public.access.empty_code\":{\"text\":\"Please enter the code received by email.\"},\"decisions.public.access.enter\":{\"text\":\"Access the ballot\"},\"decisions.public.access.expired_code\":{\"text\":\"This code has expired. Request a new one from this page.\"},\"decisions.public.access.finalize_failed\":{\"text\":\"The personal link could not be finalized. Please try again shortly.\"},\"decisions.public.access.invalid_code\":{\"text\":\"The entered code is incorrect.\"},\"decisions.public.access.invalid_decision\":{\"text\":\"This decision cannot be found.\"},\"decisions.public.access.invalid_email\":{\"text\":\"Please enter a valid email address.\"},\"decisions.public.access.invited_description\":{\"text\":\"Enter the authorized email address for this ballot to receive a personal code and a direct participation link.\"},\"decisions.public.access.missing_code\":{\"text\":\"No valid code was found for this address. Request a new one.\"},\"decisions.public.access.not_allowed_invited\":{\"text\":\"This email address is not among those authorized to participate in this ballot.\"},\"decisions.public.access.not_allowed_open\":{\"text\":\"This email address cannot be used for this ballot at the moment.\"},\"decisions.public.access.open_description\":{\"text\":\"Enter your email address to receive a personal code and a direct personal link. If this address is not yet associated with this ballot, a participation will be created automatically.\"},\"decisions.public.access.participant_unavailable\":{\"text\":\"This email address is already linked to a participant who can no longer use this ballot.\"},\"decisions.public.access.resend\":{\"text\":\"Resend the code\"},\"decisions.public.access.send\":{\"text\":\"Send my access\"},\"decisions.public.access.sync_failed\":{\"text\":\"Unable to verify authorized participants at the moment.\"},\"decisions.public.access.title\":{\"text\":\"Receive my personal access\"},\"decisions.public.access.title_eyebrow\":{\"text\":\"Public access\"},\"decisions.public.banner\":{\"text\":\"Public ballot organized for {organization}\"},\"decisions.public.banner_personal_access\":{\"text\":\" · personal access of {participant}\"},\"decisions.public.banner_prefix\":{\"text\":\"Public ballot organized for\"},\"decisions.public.block\":{\"text\":\"Block {index}\"},\"decisions.public.block_unavailable\":{\"text\":\"This block does not yet have an available interface.\"},\"decisions.public.context.contact_organizer\":{\"text\":\"Contact the organizer:\"},\"decisions.public.context.invited\":{\"text\":\"Invited\"},\"decisions.public.context.method\":{\"text\":\"Method\"},\"decisions.public.context.methods\":{\"text\":\"Methods\"},\"decisions.public.context.options\":{\"text\":\"Options\"},\"decisions.public.context.organizer\":{\"text\":\"Organizer\"},\"decisions.public.default_organization\":{\"text\":\"Organization\"},\"decisions.public.default_title\":{\"text\":\"Decision making\"},\"decisions.public.help.webmaster\":{\"text\":\"Webmaster: {email}\"},\"decisions.public.js.access_process_failed\":{\"text\":\"Unable to process this request at the moment.\"},\"decisions.public.js.code_sent\":{\"text\":\"Code sent.\"},\"decisions.public.js.invalid_response\":{\"text\":\"Invalid server response.\"},\"decisions.public.js.proposal_add_failed\":{\"text\":\"Unable to add the proposal at the moment.\"},\"decisions.public.js.proposal_placeholder\":{\"text\":\"Proposal\"},\"decisions.public.js.remove_proposal\":{\"text\":\"Remove\"},\"decisions.public.method.consent\":{\"text\":\"Consent\"},\"decisions.public.method.majority_judgment\":{\"text\":\"Majority judgment\"},\"decisions.public.method.simple_vote\":{\"text\":\"Simple vote\"},\"decisions.public.method.unknown\":{\"text\":\"Decision mode\"},\"decisions.public.navigation.aria\":{\"text\":\"Ballot navigation\"},\"decisions.public.navigation.decision\":{\"text\":\"Ballot\"},\"decisions.public.navigation.help\":{\"text\":\"Help\"},\"decisions.public.navigation.info\":{\"text\":\"Info\"},\"decisions.public.options.anonymous\":{\"text\":\"This ballot is anonymous.\"},\"decisions.public.options.discussions_all\":{\"text\":\"You can discuss the proposals during the consultation\"},\"decisions.public.options.discussions_anonymous\":{\"text\":\" anonymously.\"},\"decisions.public.options.discussions_optional_anonymity\":{\"text\":\", anonymously if you wish.\"},\"decisions.public.options.discussions_some\":{\"text\":\"You can discuss the proposals during the consultation for certain blocks\"},\"decisions.public.options.mixed_anonymity\":{\"text\":\"The anonymity may vary depending on the blocks.\"},\"decisions.public.options.not_anonymous\":{\"text\":\"This ballot is not anonymous.\"},\"decisions.public.options.proposals_all\":{\"text\":\"You can make proposals during the consultation.\"},\"decisions.public.options.proposals_some\":{\"text\":\"You can make proposals during the consultation for certain blocks.\"},\"decisions.public.options.responses_editable\":{\"text\":\"Your responses can be changed.\"},\"decisions.public.options.responses_locked\":{\"text\":\"Your responses can no longer be changed.\"},\"decisions.public.options.results_hidden\":{\"text\":\"The results are not visible until the end of the vote.\"},\"decisions.public.options.results_visible\":{\"text\":\"The results are visible.\"},\"decisions.public.status.can_participate\":{\"text\":\"You can participate in this ballot from this public page.\"},\"decisions.public.status.request_invited\":{\"text\":\"Enter your authorized email address to receive a personal participation code.\"},\"decisions.public.status.request_open\":{\"text\":\"Enter your email address to receive a personal participation code.\"},\"decisions.public.status.unavailable\":{\"text\":\"This link does not allow access to this decision.\"},\"decisions.public.timeline.consultation_end\":{\"text\":\"End of consultation\"},\"decisions.public.timeline.consultation_open\":{\"text\":\"In consultation\"},\"decisions.public.timeline.consultation_start\":{\"text\":\"Start of consultation\"},\"decisions.public.timeline.consultation_until\":{\"text\":\"In consultation until {date}\"},\"decisions.public.timeline.evaluation_end\":{\"text\":\"End of voting\"},\"decisions.public.timeline.evaluation_start\":{\"text\":\"Start of voting\"},\"decisions.public.timeline.finished\":{\"text\":\"Finished\"},\"decisions.public.timeline.hint\":{\"text\":\"Show graphical representation\"},\"decisions.public.timeline.now\":{\"text\":\"Now\"},\"decisions.public.timeline.results\":{\"text\":\"Results\"},\"decisions.public.timeline.segment_consultation\":{\"text\":\"Consultation\"},\"decisions.public.timeline.segment_results\":{\"text\":\"Results\"},\"decisions.public.timeline.segment_vote\":{\"text\":\"Vote\"},\"decisions.public.timeline.title\":{\"text\":\"Ballot stages\"},\"decisions.public.timeline.vote_open\":{\"text\":\"Voting open\"},\"decisions.public.timeline.vote_until\":{\"text\":\"Voting open until {date}\"},\"decisions.public.timeline.waiting_consultation\":{\"text\":\"Waiting for the consultation to start on {date}\"},\"decisions.public.timeline.waiting_vote\":{\"text\":\"Waiting for the vote to start on {date}\"},\"decisions.public.type.consultation\":{\"text\":\"consultation\"},\"decisions.public.type.decision\":{\"text\":\"decision\"}}','machine_translated','2026-08-01 09:08:55','2026-08-01 09:09:08'),
(50,'common_omo_public_pages','en','81f93460f052ecc06a3824190f355d4cbe7cc6a9e5a0873ab84c60589164cabf','{\"common.public_help.omo.description\":{\"text\":\"Quick overview of the software\"},\"common.public_help.omo.paragraph_1\":{\"text\":\"OpenMyOrganization (OMO) is a collaborative software designed to help an organization make its structure, responsibilities, and operations clearer. It allows for the representation of organizations, groups, circles, roles, and responsibilities, and centralizes useful information for collective work.\"},\"common.public_help.omo.paragraph_2\":{\"text\":\"Depending on the activated applications, OMO supports decision-making, projects, documents and minutes, the calendar, indicators, checklists, as well as rules and authorities. Access rights depend on the context and responsibilities of each person.\"},\"common.public_help.omo.paragraph_3\":{\"text\":\"By bringing these elements together in their context, OMO facilitates daily cooperation: everyone\'s roles and responsibilities are clearer, decisions can be collectively prepared and followed, and rules, processes, and projects remain documented and accessible.\"},\"common.public_help.omo.title\":{\"text\":\"What is OpenMyOrganization?\"},\"common.public_help.organization_fallback\":{\"text\":\"the concerned organization\"},\"common.public_help.page.decision_description\":{\"text\":\"Understanding this public decision\"},\"common.public_help.page.decision_paragraph_1\":{\"text\":\"This page is the public access point to a decision-making process organized by {organization}. It presents the context, questions, chosen method, steps, and rules useful for understanding how the consultation and voting will proceed.\"},\"common.public_help.page.decision_paragraph_2\":{\"text\":\"Depending on the voting parameters and your access, you can view the information, request personal access via email, participate in the consultation or vote, and view the results when their publication is authorized.\"},\"common.public_help.page.decision_paragraph_3\":{\"text\":\"When proposals are allowed, you can also add them during the consultation. If discussions are enabled, participants with an account can exchange views on each proposal and, depending on the settings, choose to publish their message anonymously.\"},\"common.public_help.page.decision_paragraph_4\":{\"text\":\"The information panel serves as a guide throughout the process: it reminds who is organizing the vote, for whom it is open, what steps are planned, and what participation options are available.\"},\"common.public_help.page.generic_description\":{\"text\":\"Understanding this public page\"},\"common.public_help.page.generic_paragraph\":{\"text\":\"This public page presents part of the content shared by {organization}. It does not provide access to internal spaces that are not part of the public link.\"},\"common.public_help.page.share_description\":{\"text\":\"Understanding this shared structure\"},\"common.public_help.page.share_paragraph_1\":{\"text\":\"This page is used to browse an organizational structure shared publicly by {organization}. You can explore the circles, roles, and relationships visible within the shared scope.\"},\"common.public_help.page.share_paragraph_2\":{\"text\":\"It does not allow access to internal spaces or information that are not part of the public link.\"},\"common.public_help.page.title\":{\"text\":\"What is the purpose of this page?\"},\"common.public_help.privacy.description\":{\"text\":\"Data processing and provisional framework\"},\"common.public_help.privacy.label\":{\"text\":\"Privacy Policy\"}}','machine_translated','2026-08-01 09:08:55','2026-08-01 09:09:03'),
(51,'omo_decision_group_majority_judgment','en','094e9ddc94cdf8561572dec90453b2ff8e5961a0c3f04a225aa44c5ce99bead0','{\"decisions.majority_judgment.action.apply\":{\"text\":\"Apply\"},\"decisions.majority_judgment.action.close\":{\"text\":\"Close\"},\"decisions.majority_judgment.action.configure\":{\"text\":\"Configure\"},\"decisions.majority_judgment.action.create\":{\"text\":\"Create the poll\"},\"decisions.majority_judgment.action.proposal_apply\":{\"text\":\"Save details\"},\"decisions.majority_judgment.action.save\":{\"text\":\"Save the poll\"},\"decisions.majority_judgment.action.saving\":{\"text\":\"Saving…\"},\"decisions.majority_judgment.action.submit_response\":{\"text\":\"Save my mentions\"},\"decisions.majority_judgment.action.submitting_response\":{\"text\":\"Saving the vote…\"},\"decisions.majority_judgment.action.update_response\":{\"text\":\"Update my mentions\"},\"decisions.majority_judgment.change_method\":{\"text\":\"Change method\"},\"decisions.majority_judgment.description\":{\"text\":\"Create a poll where each participant assigns a mention to each proposal on a common scale.\"},\"decisions.majority_judgment.drawer_title\":{\"text\":\"Decision Making\"},\"decisions.majority_judgment.empty_proposals\":{\"text\":\"No active proposal at the moment.\"},\"decisions.majority_judgment.empty_results\":{\"text\":\"No mention has been recorded for this poll yet.\"},\"decisions.majority_judgment.feedback.error\":{\"text\":\"Unable to save this poll at the moment.\"},\"decisions.majority_judgment.feedback.response_error\":{\"text\":\"Unable to save your vote at the moment.\"},\"decisions.majority_judgment.feedback.response_success\":{\"text\":\"Vote saved.\"},\"decisions.majority_judgment.feedback.success\":{\"text\":\"Poll saved.\"},\"decisions.majority_judgment.field.allow_anonymous_votes\":{\"text\":\"Allow anonymous votes\"},\"decisions.majority_judgment.field.allow_consultation_proposals\":{\"text\":\"Allow proposals during consultation\"},\"decisions.majority_judgment.field.allow_proposal_discussions\":{\"text\":\"Allow proposal discussions\"},\"decisions.majority_judgment.field.anonymous\":{\"text\":\"Anonymous vote\"},\"decisions.majority_judgment.field.consultation_end\":{\"text\":\"End of consultation\"},\"decisions.majority_judgment.field.consultation_start\":{\"text\":\"Start of consultation\"},\"decisions.majority_judgment.field.counted_mentions\":{\"text\":\"Mentions counted\"},\"decisions.majority_judgment.field.counted_weight\":{\"text\":\"Cumulative weight counted\"},\"decisions.majority_judgment.field.current_response\":{\"text\":\"Vote saved\"},\"decisions.majority_judgment.field.description\":{\"text\":\"Question description\"},\"decisions.majority_judgment.field.distribution\":{\"text\":\"Distribution of mentions\"},\"decisions.majority_judgment.field.evaluation_end\":{\"text\":\"End of voting\"},\"decisions.majority_judgment.field.evaluation_start\":{\"text\":\"Start of voting\"},\"decisions.majority_judgment.field.group_section\":{\"text\":\"Question of this group\"},\"decisions.majority_judgment.field.majority_mention\":{\"text\":\"Majority mention\"},\"decisions.majority_judgment.field.no_opinion_weight\":{\"text\":\"Cumulative weight without opinion\"},\"decisions.majority_judgment.field.process_description\":{\"text\":\"Context description\"},\"decisions.majority_judgment.field.process_section\":{\"text\":\"Process context\"},\"decisions.majority_judgment.field.process_title\":{\"text\":\"Process title\"},\"decisions.majority_judgment.field.proposal_actions\":{\"text\":\"Actions\"},\"decisions.majority_judgment.field.proposal_description\":{\"text\":\"Proposal description\"},\"decisions.majority_judgment.field.proposal_details\":{\"text\":\"Details\"},\"decisions.majority_judgment.field.proposal_info_url\":{\"text\":\"Information URL\"},\"decisions.majority_judgment.field.proposal_votes\":{\"text\":\"Mentions received\"},\"decisions.majority_judgment.field.proposals\":{\"text\":\"Proposals\"},\"decisions.majority_judgment.field.proposals_add\":{\"text\":\"Add a proposal\"},\"decisions.majority_judgment.field.proposals_hint\":{\"text\":\"Add one proposal per line, then reorder them by drag and drop. At least two proposals are required unless proposals are allowed during a consultation period.\"},\"decisions.majority_judgment.field.proposals_item\":{\"text\":\"Proposal {index}\"},\"decisions.majority_judgment.field.proposals_remove\":{\"text\":\"Remove\"},\"decisions.majority_judgment.field.proposals_reorder\":{\"text\":\"Reorder\"},\"decisions.majority_judgment.field.scale\":{\"text\":\"Mention scale\"},\"decisions.majority_judgment.field.scale_active\":{\"text\":\"Active\"},\"decisions.majority_judgment.field.scale_center_hint\":{\"text\":\"The central mention remains excluded from the calculation if enabled.\"},\"decisions.majority_judgment.field.scale_customize\":{\"text\":\"Redefine mentions\"},\"decisions.majority_judgment.field.scale_default_summary\":{\"text\":\"Default values\"},\"decisions.majority_judgment.field.scale_empty\":{\"text\":\"No active mention\"},\"decisions.majority_judgment.field.scale_label\":{\"text\":\"Label\"},\"decisions.majority_judgment.field.scale_slot\":{\"text\":\"Mention {index}\"},\"decisions.majority_judgment.field.scale_slot_prefix\":{\"text\":\"Mention\"},\"decisions.majority_judgment.field.scale_summary\":{\"text\":\"Configurable scale up to 7 mentions\"},\"decisions.majority_judgment.field.select_all\":{\"text\":\"Assign a mention to each proposal.\"},\"decisions.majority_judgment.field.settings\":{\"text\":\"Poll settings\"},\"decisions.majority_judgment.field.status\":{\"text\":\"Status\"},\"decisions.majority_judgment.field.title\":{\"text\":\"Question\"},\"decisions.majority_judgment.field.total_votes\":{\"text\":\"Votes recorded\"},\"decisions.majority_judgment.field.type\":{\"text\":\"Decision type\"},\"decisions.majority_judgment.field.your_scores\":{\"text\":\"Your mentions\"},\"decisions.majority_judgment.notice.consultation_proposals\":{\"text\":\"Proposals remain adjustable during consultation as long as no response has been submitted.\"},\"decisions.majority_judgment.notice.responses\":{\"text\":\"At least one response has already been submitted. Only the status and end dates remain adjustable.\"},\"decisions.majority_judgment.notice.results\":{\"text\":\"This poll is over. Only result consultation remains available.\"},\"decisions.majority_judgment.notice.started\":{\"text\":\"The poll has started. The title, description, questions, and settings are now locked.\"},\"decisions.majority_judgment.option.common.no\":{\"text\":\"No\"},\"decisions.majority_judgment.option.common.yes\":{\"text\":\"Yes\"},\"decisions.majority_judgment.option.status.archived\":{\"text\":\"Archived\"},\"decisions.majority_judgment.option.status.consultation\":{\"text\":\"In consultation\"},\"decisions.majority_judgment.option.status.draft\":{\"text\":\"In preparation\"},\"decisions.majority_judgment.option.status.evaluation\":{\"text\":\"In evaluation\"},\"decisions.majority_judgment.option.status.results\":{\"text\":\"Results\"},\"decisions.majority_judgment.option.status.scheduled\":{\"text\":\"Scheduled\"},\"decisions.majority_judgment.option.type.consultation\":{\"text\":\"Consultative\"},\"decisions.majority_judgment.option.type.decision\":{\"text\":\"Decision-making\"},\"decisions.majority_judgment.participate_description\":{\"text\":\"Assign a mention to each proposal on the majority judgment scale.\"},\"decisions.majority_judgment.participate_title\":{\"text\":\"Participate in the poll\"},\"decisions.majority_judgment.placeholder.description\":{\"text\":\"Specify the question, nuances, and useful criteria…\"},\"decisions.majority_judgment.placeholder.process_description\":{\"text\":\"Overall context, common information, consultation framework…\"},\"decisions.majority_judgment.placeholder.process_title\":{\"text\":\"E.g. Year-end meal organization\"},\"decisions.majority_judgment.placeholder.proposal_info_url\":{\"text\":\"https://...\"},\"decisions.majority_judgment.placeholder.proposals\":{\"text\":\"Proposal name\"},\"decisions.majority_judgment.placeholder.title\":{\"text\":\"E.g. Which option do you prefer?\"},\"decisions.majority_judgment.results_compare.toggle\":{\"text\":\"Show unweighted result\"},\"decisions.majority_judgment.results_compare.unweighted\":{\"text\":\"Unweighted result\"},\"decisions.majority_judgment.results_sort.alpha\":{\"text\":\"Alphabetical\"},\"decisions.majority_judgment.results_sort.aria\":{\"text\":\"Display order of majority judgment results\"},\"decisions.majority_judgment.results_sort.initial\":{\"text\":\"Initial order\"},\"decisions.majority_judgment.results_sort.rank\":{\"text\":\"Ranking\"},\"decisions.majority_judgment.title\":{\"text\":\"Configure a majority judgment\"},\"decisions.majority_judgment.tooltip.segment\":{\"text\":\"{mention}: {count} mention(s) ({percent} %)\"},\"decisions.majority_judgment.view_description\":{\"text\":\"View the poll, its settings, and its proposals without modifying its configuration.\"},\"decisions.majority_judgment.view_title\":{\"text\":\"View the poll\"}}','machine_translated','2026-08-01 09:08:55','2026-08-01 09:09:17'),
(52,'omo_decision_proposals','en','712cca4686d7e6dfdacf9ede998972a5a5c80d130c76074d1369558da959f829','{\"decisions.proposals.add_title\":{\"text\":\"Add a proposal\"},\"decisions.proposals.denied.consultation_not_started\":{\"text\":\"The consultation has not started yet.\"},\"decisions.proposals.denied.default\":{\"text\":\"This link does not allow adding proposals at the moment.\"},\"decisions.proposals.denied.evaluation_started\":{\"text\":\"The voting phase has already started.\"},\"decisions.proposals.denied.invalid_decision\":{\"text\":\"The ballot could not be loaded.\"},\"decisions.proposals.denied.option_disabled\":{\"text\":\"Adding proposals is not enabled for this ballot.\"},\"decisions.proposals.denied.participant_inactive\":{\"text\":\"This participant is no longer active for this ballot.\"},\"decisions.proposals.denied.participant_not_found\":{\"text\":\"No authorized participant was found for this link or account.\"},\"decisions.proposals.denied.participant_status_declined\":{\"text\":\"Your participation has been declined for this ballot.\"},\"decisions.proposals.denied.participant_status_revoked\":{\"text\":\"Your access to this ballot has been revoked.\"},\"decisions.proposals.description_label\":{\"text\":\"Description\"},\"decisions.proposals.description_placeholder\":{\"text\":\"Context, details, useful arguments…\"},\"decisions.proposals.feedback_denied\":{\"text\":\"This link does not allow adding proposals at the moment.\"},\"decisions.proposals.feedback_duplicate\":{\"text\":\"All submitted proposals already exist.\"},\"decisions.proposals.feedback_empty\":{\"text\":\"Add at least one proposal.\"},\"decisions.proposals.feedback_error\":{\"text\":\"Unable to add the proposal at the moment.\"},\"decisions.proposals.feedback_success_one\":{\"text\":\"Proposal added to the consultation.\"},\"decisions.proposals.feedback_success_other\":{\"text\":\"{count} proposals added to the consultation.\"},\"decisions.proposals.info_url_label\":{\"text\":\"Information URL\"},\"decisions.proposals.open_intro\":{\"text\":\"The consultation is open. You can propose a new option with its context and an information link.\"},\"decisions.proposals.order_hint\":{\"text\":\"The proposal will be added at the end of the list. Its detailed order can be managed later in the main interface.\"},\"decisions.proposals.submit\":{\"text\":\"Add the proposal\"},\"decisions.proposals.title_label\":{\"text\":\"Title\"},\"decisions.proposals.title_placeholder\":{\"text\":\"Name of the proposal\"}}','machine_translated','2026-08-01 09:08:56','2026-08-01 09:09:03'),
(58,'common_auth_page','en','4a8d0dd706526abaffec6d1fb0c5d22994a80b580f9ec42b5e6f22f5d0932043','{\"auth.button.continue\":{\"text\":\"Continue\"},\"auth.button.resend_code\":{\"text\":\"Send a new code\"},\"auth.button.send_code\":{\"text\":\"Send code\"},\"auth.button.sign_in_password\":{\"text\":\"Sign in\"},\"auth.button.validate\":{\"text\":\"Validate\"},\"auth.button.validate_code\":{\"text\":\"Validate code\"},\"auth.challenge.answer_placeholder\":{\"text\":\"Your answer\"},\"auth.challenge.number.1\":{\"text\":\"one\"},\"auth.challenge.number.2\":{\"text\":\"two\"},\"auth.challenge.number.3\":{\"text\":\"three\"},\"auth.challenge.number.4\":{\"text\":\"four\"},\"auth.challenge.number.5\":{\"text\":\"five\"},\"auth.challenge.number.6\":{\"text\":\"six\"},\"auth.challenge.number.7\":{\"text\":\"seven\"},\"auth.challenge.number.8\":{\"text\":\"eight\"},\"auth.challenge.number.9\":{\"text\":\"nine\"},\"auth.challenge.operator.plus\":{\"text\":\"plus\"},\"auth.challenge.prompt\":{\"text\":\"{left} {operator} {right}\"},\"auth.code.instructions\":{\"text\":\"Enter the code received by email on this device.\"},\"auth.code.placeholder\":{\"text\":\"ABC123\"},\"auth.copy.login_code\":{\"text\":\"A login code will be sent to you by email. It is valid for 5 minutes.\"},\"auth.copy.login_password\":{\"text\":\"Use your password to sign in directly on this device.\"},\"auth.email.body.connection_heading\":{\"text\":\"Login to your account\"},\"auth.email.body.continue_button\":{\"text\":\"Continue login\"},\"auth.email.body.enter_code\":{\"text\":\"Enter this code in the app to sign in:\"},\"auth.email.body.from_name_fallback\":{\"text\":\"Organization\"},\"auth.email.body.network_notice\":{\"text\":\"If your network changes, simply request a new code.\"},\"auth.email.body.open_link\":{\"text\":\"Or simply click this link from the same device:\"},\"auth.email.body.validity_notice\":{\"text\":\"This code is valid for 5 minutes and must be entered from the same network.\"},\"auth.email.reset.body.button\":{\"text\":\"Reset password\"},\"auth.email.reset.body.copy\":{\"text\":\"Click the button below to set a new password for your account.\"},\"auth.email.reset.body.heading\":{\"text\":\"Choose a new password\"},\"auth.email.reset.body.ignore_notice\":{\"text\":\"If you did not request this, you can simply ignore this email.\"},\"auth.email.reset.body.validity_notice\":{\"text\":\"This link is valid for 1 hour.\"},\"auth.email.reset.subject\":{\"text\":\"Password reset\"},\"auth.email.subject\":{\"text\":\"Login code\"},\"auth.error.reset_send_failed\":{\"text\":\"Unable to send the reset email.\"},\"auth.link.reset_password\":{\"text\":\"Reset password\"},\"auth.page.invalid_request\":{\"text\":\"Please return to the app and enter the code received by email.\"},\"auth.page.language_label\":{\"text\":\"Language\"},\"auth.page.language_system_label\":{\"text\":\"System\"},\"auth.page.login.app_default\":{\"text\":\"Account\"},\"auth.page.login.intro_default\":{\"text\":\"Sign in to continue.\"},\"auth.page.login.title_default\":{\"text\":\"Login\"},\"auth.page.logo_alt\":{\"text\":\"Logo\"},\"auth.page.verify.auto_unavailable\":{\"text\":\"Automatic verification unavailable. Use the button below.\"},\"auth.page.verify.description\":{\"text\":\"We are verifying your code on this device.\"},\"auth.page.verify.heading\":{\"text\":\"Logging in\"},\"auth.page.verify.status\":{\"text\":\"Verifying...\"},\"auth.page.verify.title\":{\"text\":\"Logging in\"},\"auth.placeholder.full_email\":{\"text\":\"name@domain.com\"},\"auth.placeholder.password\":{\"text\":\"Your password\"},\"auth.placeholder.username\":{\"text\":\"Username\"},\"auth.remember_me\":{\"text\":\"Remember me on this device\"},\"auth.toggle.use_magic_login\":{\"text\":\"Sign in with an email code instead\"},\"auth.toggle.use_other_email\":{\"text\":\"Use a different email address\"},\"auth.toggle.use_password_login\":{\"text\":\"Sign in with a password instead\"}}','machine_translated','2026-08-02 06:10:58','2026-08-02 06:11:06'),
(59,'common_auth_js','en','5be77eb01e9055252219583907da622efeaed379bda19260436189472a423c90','{\"auth.button.resend_code\":{\"text\":\"Send a new code\"},\"auth.button.send_code\":{\"text\":\"Send code\"},\"auth.button.send_other_challenge\":{\"text\":\"Send another challenge\"},\"auth.button.sign_in_password\":{\"text\":\"Sign in\"},\"auth.button.validate\":{\"text\":\"Validate\"},\"auth.button.validate_and_send_code\":{\"text\":\"Validate and send code\"},\"auth.button.validate_code\":{\"text\":\"Validate code\"},\"auth.challenge.answer_placeholder\":{\"text\":\"Your answer\"},\"auth.code.instructions\":{\"text\":\"Enter the code received by email on this device.\"},\"auth.code.placeholder\":{\"text\":\"ABC123\"},\"auth.copy.login_code\":{\"text\":\"A login code will be sent to you by email. It remains valid for 5 minutes.\"},\"auth.copy.login_password\":{\"text\":\"Use your password to log in directly on this device.\"},\"auth.error.ask_new_code_first\":{\"text\":\"Please request a new code first.\"},\"auth.error.challenge_expired\":{\"text\":\"The challenge has expired. Restart the login.\"},\"auth.error.enter_full_code\":{\"text\":\"Please enter the full 6-character code.\"},\"auth.error.expired\":{\"text\":\"The code has expired. Request a new code.\"},\"auth.error.invalid_code\":{\"text\":\"Invalid code. Request a new code.\"},\"auth.error.invalid_credentials\":{\"text\":\"Invalid username or password.\"},\"auth.error.invalid_email\":{\"text\":\"Please enter a valid email address.\"},\"auth.error.ip_changed\":{\"text\":\"Your network has changed. For your security, request a new code.\"},\"auth.error.locked\":{\"text\":\"Too many attempts. Request a new code.\"},\"auth.error.missing_code\":{\"text\":\"Please enter the code received by email.\"},\"auth.error.missing_password\":{\"text\":\"Please enter your password.\"},\"auth.error.request_failed\":{\"text\":\"Unable to send the request.\"},\"auth.error.reset_send_failed\":{\"text\":\"Unable to send the reset email.\"},\"auth.error.restart_login\":{\"text\":\"Please restart the login.\"},\"auth.error.send_failed\":{\"text\":\"Unable to send the code by email.\"},\"auth.error.unexpected\":{\"text\":\"An error occurred.\"},\"auth.error.verify_failed\":{\"text\":\"Unable to verify the code.\"},\"auth.error.wrong_answer\":{\"text\":\"Incorrect answer. Please try again.\"},\"auth.error.wrong_code\":{\"one\":\"Incorrect code. {count} attempt remaining.\",\"other\":\"Incorrect code. {count} attempts remaining.\"},\"auth.link.reset_password\":{\"text\":\"Reset password\"},\"auth.placeholder.full_email\":{\"text\":\"name@domain.com\"},\"auth.placeholder.password\":{\"text\":\"Your password\"},\"auth.placeholder.username\":{\"text\":\"Username\"},\"auth.remember_me\":{\"text\":\"Remember me on this device\"},\"auth.status.answer_verification\":{\"text\":\"Please answer the verification question.\"},\"auth.status.code_pending\":{\"text\":\"The code may have already been sent. If you have received it, enter it below.\"},\"auth.status.code_sent\":{\"text\":\"The login code has been sent by email.\"},\"auth.status.enter_received_code\":{\"text\":\"Enter the code received by email.\"},\"auth.status.password_signing_in\":{\"text\":\"Signing in...\"},\"auth.status.reset_email_sent\":{\"text\":\"If this address exists, a reset link has just been sent.\"},\"auth.status.reset_sending\":{\"text\":\"Preparing the reset email...\"},\"auth.status.sending\":{\"text\":\"Sending...\"},\"auth.status.verifying_code\":{\"text\":\"Verifying code...\"},\"auth.toggle.use_magic_login\":{\"text\":\"Sign in with an email code instead\"},\"auth.toggle.use_org_email\":{\"text\":\"Use the organization\'s email address\"},\"auth.toggle.use_other_email\":{\"text\":\"Use another email address\"},\"auth.toggle.use_password_login\":{\"text\":\"Sign in with a password instead\"}}','machine_translated','2026-08-02 06:10:58','2026-08-02 06:11:06');
/*!40000 ALTER TABLE `translation_bundles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `translation_languages`
--

DROP TABLE IF EXISTS `translation_languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `translation_languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locale` varchar(10) NOT NULL,
  `name` varchar(120) NOT NULL,
  `native_name` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 100,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `is_source` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_translation_language_locale` (`locale`),
  KEY `idx_translation_language_active_order` (`active`,`is_source`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation_languages`
--

LOCK TABLES `translation_languages` WRITE;
/*!40000 ALTER TABLE `translation_languages` DISABLE KEYS */;
INSERT INTO `translation_languages` VALUES
(1,'fr','Francais','Francais',10,1,1,'2026-07-23 11:51:17','2026-07-23 11:51:17'),
(2,'en','Anglais','English',20,1,0,'2026-07-23 11:51:17','2026-07-23 11:51:17'),
(3,'de','Allemand','Deutsch',30,1,0,'2026-07-23 11:51:17','2026-07-23 11:51:17'),
(4,'es','Espagnol','Espanol',40,1,0,'2026-07-23 11:51:17','2026-07-23 11:51:17'),
(5,'it','Italien','Italiano',50,1,0,'2026-07-23 11:51:17','2026-07-23 11:51:17'),
(6,'pt','Portugais','Portugues',60,1,0,'2026-07-23 11:51:17','2026-07-23 11:51:17'),
(7,'nl','Neerlandais','Nederlands',70,1,0,'2026-07-23 11:51:17','2026-07-23 11:51:17'),
(8,'pl','Polonais','Polski',80,1,0,'2026-07-23 11:51:17','2026-07-23 11:51:17');
/*!40000 ALTER TABLE `translation_languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `typeholon`
--

DROP TABLE IF EXISTS `typeholon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `typeholon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `hastemplate` tinyint(1) NOT NULL DEFAULT 0,
  `haschild` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `typeholon`
--

LOCK TABLES `typeholon` WRITE;
/*!40000 ALTER TABLE `typeholon` DISABLE KEYS */;
INSERT INTO `typeholon` VALUES
(1,'Rôle',1,0),
(2,'Cercle',1,1),
(3,'Groupe',0,1),
(4,'Organisation',0,1);
/*!40000 ALTER TABLE `typeholon` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `lastname` varchar(150) DEFAULT NULL,
  `presentation` text DEFAULT NULL,
  `latlong` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `firstname` varchar(150) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `password` varchar(80) DEFAULT NULL,
  `allow_password_login` tinyint(1) NOT NULL DEFAULT 0,
  `totp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `totp_secret` varchar(255) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateconnexion` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `siteadmin` tinyint(1) NOT NULL DEFAULT 0,
  `code` varchar(30) DEFAULT NULL,
  `codeexpiration` datetime DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `param_easypv` mediumtext DEFAULT NULL,
  `param_easymemo` mediumtext DEFAULT NULL,
  `param_easycircle` mediumtext DEFAULT NULL,
  `telegramID` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=244 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES
(1,'admin@org1.opengov.tools',NULL,'Org1',NULL,NULL,NULL,'Admin','Admin Org1',NULL,NULL,0,0,NULL,'2026-09-28 19:22:04',NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(2,'member1@org1.opengov.tools',NULL,'Org1',NULL,NULL,NULL,'Membre','Membre Org1',NULL,NULL,0,0,NULL,'2026-09-28 19:22:04',NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(3,'admin@org2.opengov.tools',NULL,'Org2',NULL,NULL,NULL,'Admin','Admin Org2',NULL,NULL,0,0,NULL,'2026-09-28 19:22:04',NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_competence`
--

DROP TABLE IF EXISTS `user_competence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_competence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `IDcompetence` int(11) NOT NULL,
  `IDorganization` int(11) DEFAULT NULL,
  `level` tinyint(4) NOT NULL DEFAULT 1,
  `description` varchar(500) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_competence_user_scope` (`IDuser`,`IDorganization`),
  KEY `idx_user_competence_competence` (`IDcompetence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_competence`
--

LOCK TABLES `user_competence` WRITE;
/*!40000 ALTER TABLE `user_competence` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_competence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_competence_validation`
--

DROP TABLE IF EXISTS `user_competence_validation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_competence_validation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser_competence` int(11) NOT NULL,
  `IDvalidator_user` int(11) NOT NULL,
  `IDorganization` int(11) NOT NULL,
  `level` tinyint(4) NOT NULL DEFAULT 1,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_competence_validation` (`IDuser_competence`,`IDvalidator_user`,`IDorganization`),
  KEY `idx_user_competence_validation_org` (`IDorganization`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_competence_validation`
--

LOCK TABLES `user_competence_validation` WRITE;
/*!40000 ALTER TABLE `user_competence_validation` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_competence_validation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_faq_response`
--

DROP TABLE IF EXISTS `user_faq_response`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_faq_response` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) DEFAULT NULL,
  `IDfaq` int(11) DEFAULT NULL,
  `IDchoice` int(11) DEFAULT NULL,
  `IDmission` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_faq_response`
--

LOCK TABLES `user_faq_response` WRITE;
/*!40000 ALTER TABLE `user_faq_response` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_faq_response` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_holon`
--

DROP TABLE IF EXISTS `user_holon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_holon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `IDholon` int(11) NOT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `focus` varchar(250) DEFAULT NULL,
  `time_budget_hours` decimal(12,2) DEFAULT NULL,
  `time_budget_recurrence` varchar(10) DEFAULT NULL,
  `money_budget` decimal(12,2) DEFAULT NULL,
  `money_budget_recurrence` varchar(10) DEFAULT NULL,
  `assignment_review_date` date DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateconnexion` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `is_membership` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_user_holon_holon_user` (`IDholon`,`IDuser`),
  CONSTRAINT `fk_user_holon_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4039 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_holon`
--

LOCK TABLES `user_holon` WRITE;
/*!40000 ALTER TABLE `user_holon` DISABLE KEYS */;
INSERT INTO `user_holon` VALUES
(1,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-28 19:22:04',NULL,1,1),
(2,2,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-28 19:22:04',NULL,1,1),
(3,3,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-28 19:22:04',NULL,1,1);
/*!40000 ALTER TABLE `user_holon` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_homework`
--

DROP TABLE IF EXISTS `user_homework`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_homework` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `IDmission` int(11) NOT NULL,
  `IDhomework` int(11) NOT NULL,
  `IDparcours` int(11) NOT NULL,
  `done` datetime DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateupdate` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_homework` (`IDuser`,`IDmission`,`IDhomework`,`IDparcours`),
  KEY `idx_user_homework_user` (`IDuser`),
  KEY `idx_user_homework_mission` (`IDmission`),
  KEY `idx_user_homework_homework` (`IDhomework`),
  KEY `idx_user_homework_parcours` (`IDparcours`),
  KEY `idx_user_homework_done` (`done`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_homework`
--

LOCK TABLES `user_homework` WRITE;
/*!40000 ALTER TABLE `user_homework` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_homework` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_login_token`
--

DROP TABLE IF EXISTS `user_login_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_login_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) DEFAULT NULL,
  `token` varchar(64) DEFAULT NULL,
  `code_hash` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `request_ip` varchar(45) DEFAULT NULL,
  `attempt_count` int(11) NOT NULL DEFAULT 0,
  `used` tinyint(4) DEFAULT 0,
  `remember` tinyint(1) DEFAULT 0,
  `mfa_pending` tinyint(1) NOT NULL DEFAULT 0,
  `mfa_attempt_count` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `last_attempt_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_login_token`
--

LOCK TABLES `user_login_token` WRITE;
/*!40000 ALTER TABLE `user_login_token` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_login_token` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_mission`
--

DROP TABLE IF EXISTS `user_mission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_mission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `IDmission` int(11) NOT NULL,
  `IDparcours` int(11) NOT NULL,
  `done` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_mission`
--

LOCK TABLES `user_mission` WRITE;
/*!40000 ALTER TABLE `user_mission` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_mission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_organization`
--

DROP TABLE IF EXISTS `user_organization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_organization` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `IDorganization` int(11) NOT NULL,
  `username` varchar(250) DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `presentation` text DEFAULT NULL,
  `latlong` varchar(100) DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateconnexion` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_organization_organization_user` (`IDorganization`,`IDuser`),
  CONSTRAINT `fk_user_organization_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=585 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_organization`
--

LOCK TABLES `user_organization` WRITE;
/*!40000 ALTER TABLE `user_organization` DISABLE KEYS */;
INSERT INTO `user_organization` VALUES
(1,1,1,'Admin Org1',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-28 19:22:04',NULL,1),
(2,2,1,'Membre Org1',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-28 19:22:04',NULL,1),
(3,3,2,'Admin Org2',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-28 19:22:04',NULL,1);
/*!40000 ALTER TABLE `user_organization` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_patreon`
--

DROP TABLE IF EXISTS `user_patreon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_patreon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `scope` varchar(255) DEFAULT NULL,
  `token_type` varchar(50) DEFAULT NULL,
  `patreon_user_id` varchar(50) DEFAULT NULL,
  `patreon_member_id` varchar(100) DEFAULT NULL,
  `campaign_id` varchar(50) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `profile_url` varchar(500) DEFAULT NULL,
  `vanity` varchar(255) DEFAULT NULL,
  `patron_status` varchar(50) DEFAULT NULL,
  `last_charge_status` varchar(50) DEFAULT NULL,
  `last_charge_date` datetime DEFAULT NULL,
  `next_charge_date` datetime DEFAULT NULL,
  `currently_entitled_amount_cents` int(11) NOT NULL DEFAULT 0,
  `campaign_lifetime_support_cents` int(11) NOT NULL DEFAULT 0,
  `tier_titles` mediumtext DEFAULT NULL,
  `is_connected` tinyint(1) NOT NULL DEFAULT 0,
  `connected_at` datetime DEFAULT NULL,
  `last_sync_at` datetime DEFAULT NULL,
  `last_sync_status` varchar(50) DEFAULT NULL,
  `last_sync_error` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_patreon_user` (`IDuser`),
  KEY `idx_user_patreon_connected` (`is_connected`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_patreon`
--

LOCK TABLES `user_patreon` WRITE;
/*!40000 ALTER TABLE `user_patreon` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_patreon` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_question_response`
--

DROP TABLE IF EXISTS `user_question_response`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_question_response` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) DEFAULT NULL,
  `IDquestion` int(11) DEFAULT NULL,
  `IDchoice` int(11) DEFAULT NULL,
  `IDmission` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_question_response_lookup` (`IDuser`,`IDmission`,`IDquestion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_question_response`
--

LOCK TABLES `user_question_response` WRITE;
/*!40000 ALTER TABLE `user_question_response` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_question_response` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_remember`
--

DROP TABLE IF EXISTS `user_remember`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_remember` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) DEFAULT NULL,
  `token` varchar(64) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` mediumtext DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_remember`
--

LOCK TABLES `user_remember` WRITE;
/*!40000 ALTER TABLE `user_remember` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_remember` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_time`
--

DROP TABLE IF EXISTS `work_time`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_time` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDuser` int(11) NOT NULL,
  `IDorganization` int(11) NOT NULL,
  `IDholon` int(11) NOT NULL,
  `IDproject` int(11) DEFAULT NULL,
  `label` varchar(1000) DEFAULT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `last_heartbeat_at` datetime DEFAULT NULL,
  `end_reason` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_work_time_user_open` (`IDuser`,`end_reason`,`id`),
  KEY `idx_work_time_organization_holon` (`IDorganization`,`IDholon`,`started_at`),
  KEY `idx_work_time_project` (`IDproject`),
  KEY `fk_work_time_holon` (`IDholon`),
  CONSTRAINT `fk_work_time_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_work_time_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_work_time_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_work_time_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_time`
--

LOCK TABLES `work_time` WRITE;
/*!40000 ALTER TABLE `work_time` DISABLE KEYS */;
/*!40000 ALTER TABLE `work_time` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'omodev'
--

--
-- Dumping routines for database 'omodev'
--
CREATE TABLE IF NOT EXISTS `project_indicator` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDproject` int(11) NOT NULL,
  `IDstatindicator` int(11) NOT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_indicator` (`IDproject`, `IDstatindicator`),
  KEY `idx_project_indicator_indicator` (`IDstatindicator`),
  CONSTRAINT `fk_project_indicator_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_indicator_indicator` FOREIGN KEY (`IDstatindicator`) REFERENCES `stat_indicator` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_recurring_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDproject` int(11) NOT NULL,
  `IDrecurringtask` int(11) NOT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_recurring_task` (`IDproject`, `IDrecurringtask`),
  KEY `idx_project_recurring_task_task` (`IDrecurringtask`),
  CONSTRAINT `fk_project_recurring_task_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_recurring_task_task` FOREIGN KEY (`IDrecurringtask`) REFERENCES `recurring_task` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Automatic backups start disabled; delivery state is specific to each installation.
CREATE TABLE IF NOT EXISTS `organization_backup` (
  `id` int NOT NULL AUTO_INCREMENT,
  `IDorganization` int NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `email` varchar(254) DEFAULT NULL,
  `frequency` varchar(3) NOT NULL DEFAULT '1m',
  `last_sent_at` datetime DEFAULT NULL,
  `last_attempt_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organization_backup_organization` (`IDorganization`),
  CONSTRAINT `organization_backup_organization_fk` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-09-28 19:23:49

-- Empty MCP OAuth schema exported from a fresh seed database after its migrations.
/*M!999999\- enable the sandbox mode */

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mcp_oauth_client` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(150) NOT NULL,
  `redirect_uris` text NOT NULL,
  `created_at` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mcp_client_identifier` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mcp_oauth_grant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDclient` int(11) NOT NULL,
  `IDuser` int(11) NOT NULL,
  `IDorganization` int(11) NOT NULL,
  `resource` varchar(512) NOT NULL,
  `scope` varchar(100) NOT NULL,
  `redirect_uri` varchar(2048) NOT NULL,
  `code_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code_challenge` varchar(43) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code_expires_at` bigint(20) NOT NULL,
  `code_used_at` bigint(20) DEFAULT NULL,
  `access_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `access_expires_at` bigint(20) DEFAULT NULL,
  `refresh_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `refresh_expires_at` bigint(20) DEFAULT NULL,
  `revoked_at` bigint(20) DEFAULT NULL,
  `created_at` bigint(20) NOT NULL,
  `used_refresh_hashes` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mcp_grant_code` (`code_hash`),
  UNIQUE KEY `mcp_grant_access` (`access_hash`),
  UNIQUE KEY `mcp_grant_refresh` (`refresh_hash`),
  KEY `mcp_grant_owner` (`IDuser`,`created_at`),
  KEY `mcp_grant_client_fk` (`IDclient`),
  KEY `mcp_grant_organization_fk` (`IDorganization`),
  CONSTRAINT `mcp_grant_client_fk` FOREIGN KEY (`IDclient`) REFERENCES `mcp_oauth_client` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mcp_grant_organization_fk` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mcp_grant_user_fk` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
-- @migration
-- Atomic creation and replay of MCP calendar requests; no private agenda data stored.
CREATE TABLE IF NOT EXISTS `mcp_event_creation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `IDuser` int NOT NULL,
  `IDorganization` int NOT NULL,
  `IDevent` int DEFAULT NULL,
  `key_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `payload_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `completed` tinyint NOT NULL DEFAULT 0,
  `created_at` bigint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mcp_event_request` (`IDuser`, `IDorganization`, `key_hash`),
  CONSTRAINT `mcp_event_user_fk` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mcp_event_org_fk` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mcp_event_result_fk` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

INSERT INTO `sql_migration` (`filename`, `checksum`, `executed_at`) VALUES
('2026-10-02-04-mcp-structure-oauth.sql', 'a5586cfa5440235dda71c9be8692fd9b43916c8a19fd44f3481d7a875eb189f3', '2026-10-02 00:00:00'),
('2026-10-02-05-mcp-refresh-replay.sql', '359181229c14562ce7533c1d06d379f34df567ff13c1ed2a5eaf5417ffb16438', '2026-10-02 00:00:00');

INSERT INTO `sql_migration` (`filename`, `checksum`, `executed_at`) VALUES
('2026-10-03-04-mcp-event-creation.sql', '79e97cfcad2abcb3dcc9d9eaf71d2c68142001f3417847c0152ace9ca539c467', '2026-10-03 00:00:00');

INSERT INTO `sql_migration` (`filename`, `checksum`, `executed_at`) VALUES
('2026-10-03-05-decision-proposal-dates.sql', 'a24a2680d09ce624448bd71ccd2e7c9897c18e289d9a85a3fe3f942cf51b5a63', '2026-10-03 00:00:00');
