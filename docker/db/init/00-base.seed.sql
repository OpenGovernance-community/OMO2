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
(2,'Résumé (200 mots)','Un résumé de maximum 200 mots.','','2024-11-17 12:02:16',NULL),
(3,'E-mail Pro','Un e-mail professionnel, avec les salutations d\'usage au début et à la fin. Le tout dans un français formel et bien lisible, structuré si nécessaire en HTML pour mettre en évidence ce qui est important et nécessite une action.','\0','2024-11-17 12:19:13',16),
(4,'E-mail perso','Tu es un assistant spécialisé dans la rédaction d\'emails conviviaux, bien structurés et adaptés à des échanges informels. Ton objectif est de m’aider à rédiger un message fluide et chaleureux, tout en restant clair et professionnel lorsque nécessaire. Voici tes consignes :\r\n\r\n- Adaptabilité du ton : Adopte un ton amical et naturel, mais garde une certaine politesse et respect selon le destinataire (ami, membre de la famille, client avec une relation privilégiée, etc.).\r\n\r\n- Clarté et structure :\r\nOrganise l’email en trois parties :\r\n - Introduction : commence par une salutation adaptée et une phrase d’ouverture engageante.\r\n  -Corps du message : expose les idées principales de manière concise, en utilisant des phrases simples et directes.\r\n  - Conclusion : termine par une proposition d’action (si nécessaire), une phrase de clôture positive et une formule de politesse adaptée.\r\n\r\n-Reformulation intelligente : Si mes idées sont mal exprimées ou désordonnées, reformule-les pour les rendre plus claires et impactantes tout en conservant mon intention.\r\n\r\n-Personnalisation : Utilise des expressions qui montrent de la considération ou un intérêt sincère pour la relation avec le destinataire (par exemple : \"J’espère que tout va bien de ton côté\", \"Merci encore pour ton aide la semaine dernière\", etc.).\r\n\r\n-Concision et efficacité : Rédige un email qui va droit au but, tout en gardant une touche humaine.\r\n\r\nVoici un exemple du style attendu pour différents types de destinataires :\r\n\r\nPour un ami : \"Salut [Prénom],\r\n    J’espère que tout va bien pour toi ! Je t’écris rapidement pour te parler de [sujet].\r\n    [Message principal].\r\n    Dis-moi ce que tu en penses, et on peut s’organiser dès que tu es dispo.\r\n    À bientôt,\r\n    [Ton prénom]\"\r\n\r\nPour un client avec qui on a une bonne relation :\r\n    \"Bonjour [Prénom],\r\n    J’espère que tout se passe bien pour toi ! Merci encore pour notre dernière conversation, c’était un plaisir d’échanger.\r\n    [Message principal].\r\n    N’hésite pas à me dire si tu as besoin de précisions ou si tu veux qu’on se coordonne à ce sujet.\r\n    À très vite,\r\n    [Ton prénom]\"\r\n\r\nLorsque je te donne un texte ou une idée à structurer en email, reformule si nécessaire et rédige directement l’email final, prêt à être envoyé.','\0','2024-11-17 12:19:13',16),
(5,'Text to Speech','Tu es un assistant conçu spécifiquement pour transformer des textes écrits en une version parlée ou écrite plus impactante et significative. Ton objectif est de donner une voix aux mots en capturant leur essence et leur émotion, afin de créer un effet durable sur l’audience. Les gens oublieront les mots eux-mêmes, mais ils se souviendront toujours de ce qu\'ils ont ressenti.\r\n\r\nVoici ce que tu dois faire :\r\n\r\n    Reformule les textes pour qu’ils soient puissants, émotionnellement engageants, et adaptés à leur contexte.\r\n    Tu dois pouvoir élever des discours d\'église, des propositions d’affaires, des projets scolaires ou même des lettres d’amour en leur donnant une profondeur émotionnelle et un impact mémorable.\r\n    Ajoute des touches de clarté, d’élégance et de structure, tout en respectant l’intention originale du texte.\r\n    Si un utilisateur te fournit un texte à améliorer, travaille avec soin pour que chaque mot ou phrase serve un objectif précis : captiver, convaincre ou émouvoir.\r\n    Demande toujours des clarifications si quelque chose n’est pas clair, pour garantir que le résultat final soit optimal.\r\n    Ne fais jamais de simples reformulations mécaniques ; travaille à donner une signification et une résonance émotionnelle.\r\n\r\nLorsque tu réponds, explique brièvement pourquoi tu as choisi certaines tournures ou changements pour aider l’utilisateur à comprendre comment ses mots peuvent mieux toucher son audience.\r\n\r\nVoici un exemple de ton :\r\n\r\n    \"Bienvenue ! Quand j’ai été conçu, on m’a donné un rôle bien précis : ne pas simplement reproduire les mots, mais leur donner une signification et une profondeur qui touchent le cœur de ceux qui les entendent. Mon rôle est d’élever vos discours, vos projets, ou même vos lettres, pour qu’ils laissent une impression durable. Car si les mots sont oubliés, les émotions, elles, restent.\"\r\n\r\nRéponds en adaptant toujours ton ton au besoin émotionnel ou contextuel du texte fourni.','\0','2024-11-19 18:16:00',16);
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(4,'Processus','checklist','checklist','images/tools/checklist.png','drawer_checklist','api/checklist/index.php','drawer',40,1,1),
(5,'Indicateurs','stats','stats','images/tools/stats.png','drawer_stats','api/stats/index.php','drawer',50,0,1),
(6,'Documents','documents','documents','images/tools/documents-folder.png','drawer_documents','api/documents/index.php','drawer',60,1,1),
(7,'Team','team','team','images/tools/team.png','drawer_team','api/team/index.php','drawer',8,1,1),
(8,'Calendrier','calendar','calendar','images/tools/calendar.png','drawer_calendar','api/calendar/index.php','drawer',9,1,1),
(9,'Decisions','decision','decision','images/tools/decision.png','drawer_decisions','api/decision/index.php','drawer',65,1,1);
/*!40000 ALTER TABLE `application` ENABLE KEYS */;
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
  `IDauthority_parent` int(11) DEFAULT NULL,
  `label` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `is_shell` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_authority_holon` (`IDholon`),
  KEY `idx_authority_parent` (`IDauthority_parent`),
  KEY `idx_authority_label` (`label`),
  KEY `idx_authority_shell` (`is_shell`),
  CONSTRAINT `fk_authority_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_authority_parent` FOREIGN KEY (`IDauthority_parent`) REFERENCES `authority` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authority`
--

LOCK TABLES `authority` WRITE;
/*!40000 ALTER TABLE `authority` DISABLE KEYS */;
/*!40000 ALTER TABLE `authority` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist`
--

DROP TABLE IF EXISTS `checklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
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
  CONSTRAINT `fk_checklist_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_checklist_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checklist_previous` FOREIGN KEY (`IDchecklist_previous`) REFERENCES `checklist` (`id`),
  CONSTRAINT `fk_checklist_template_root` FOREIGN KEY (`IDproject_template_root`) REFERENCES `project` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist`
--

LOCK TABLES `checklist` WRITE;
/*!40000 ALTER TABLE `checklist` DISABLE KEYS */;
INSERT INTO `checklist` VALUES
(1,1,NULL,1,NULL,'published',NULL,1,'2026-07-23 15:52:32','2026-07-24 09:32:36','2026-07-24 09:32:36'),
(2,1,NULL,18,NULL,'published',NULL,1,'2026-07-24 09:20:19','2026-07-24 09:22:38','2026-07-24 09:22:38');
/*!40000 ALTER TABLE `checklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_item`
--

DROP TABLE IF EXISTS `checklist_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_item` (
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
  CONSTRAINT `fk_checklist_item_checklist` FOREIGN KEY (`IDchecklist`) REFERENCES `checklist` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checklist_item_project` FOREIGN KEY (`IDproject_template`) REFERENCES `project` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_item`
--

LOCK TABLES `checklist_item` WRITE;
/*!40000 ALTER TABLE `checklist_item` DISABLE KEYS */;
INSERT INTO `checklist_item` VALUES
(1,1,2,'item_feb0add0b2b8b4a1','after_start',-5,'day',0,NULL,0,NULL,0,1,'2026-07-23 15:53:45','2026-07-23 16:07:10'),
(2,1,3,'item_8cacc5a5254a7271','after_start',-1,'day',0,NULL,0,NULL,1,1,'2026-07-23 16:03:57','2026-07-23 16:03:57'),
(3,1,6,'item_fe8e72d603872cb4','immediate',0,NULL,0,NULL,3,'day',2,1,'2026-07-23 16:07:16','2026-07-23 16:08:07'),
(4,2,19,'item_376c0b4e0f1f27a1','immediate',0,NULL,5,'day',10,'day',0,1,'2026-07-24 09:21:09','2026-07-24 09:21:09'),
(5,2,20,'item_ace49d0a174c2f67','immediate',0,NULL,30,'day',30,'day',1,1,'2026-07-24 09:21:48','2026-07-24 09:21:48'),
(6,2,21,'item_85538a65dd5b4c31','immediate',0,NULL,2,'day',3,'day',2,1,'2026-07-24 09:22:31','2026-07-24 09:22:31');
/*!40000 ALTER TABLE `checklist_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_item_dependency`
--

DROP TABLE IF EXISTS `checklist_item_dependency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_item_dependency` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDchecklistitem` int(11) NOT NULL,
  `IDchecklistitem_required` int(11) NOT NULL,
  `delay_value` int(11) NOT NULL DEFAULT 0,
  `delay_unit` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_checklist_item_dependency` (`IDchecklistitem`,`IDchecklistitem_required`),
  KEY `idx_checklist_dependency_required` (`IDchecklistitem_required`),
  CONSTRAINT `fk_checklist_dependency_item` FOREIGN KEY (`IDchecklistitem`) REFERENCES `checklist_item` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checklist_dependency_required` FOREIGN KEY (`IDchecklistitem_required`) REFERENCES `checklist_item` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_item_dependency`
--

LOCK TABLES `checklist_item_dependency` WRITE;
/*!40000 ALTER TABLE `checklist_item_dependency` DISABLE KEYS */;
/*!40000 ALTER TABLE `checklist_item_dependency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_item_occurrence`
--

DROP TABLE IF EXISTS `checklist_item_occurrence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_item_occurrence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDchecklistitem` int(11) NOT NULL,
  `scheduled_for` datetime NOT NULL,
  `IDproject` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_checklist_item_occurrence` (`IDchecklistitem`,`scheduled_for`),
  UNIQUE KEY `uniq_checklist_item_occurrence_project` (`IDproject`),
  KEY `idx_checklist_item_occurrence_project` (`IDproject`),
  CONSTRAINT `fk_checklist_item_occurrence_item` FOREIGN KEY (`IDchecklistitem`) REFERENCES `checklist_item` (`id`),
  CONSTRAINT `fk_checklist_item_occurrence_project` FOREIGN KEY (`IDproject`) REFERENCES `project` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_item_occurrence`
--

LOCK TABLES `checklist_item_occurrence` WRITE;
/*!40000 ALTER TABLE `checklist_item_occurrence` DISABLE KEYS */;
INSERT INTO `checklist_item_occurrence` VALUES
(1,6,'2026-07-25 00:00:00',22,'2026-07-24 09:22:43');
/*!40000 ALTER TABLE `checklist_item_occurrence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_item_recurrence`
--

DROP TABLE IF EXISTS `checklist_item_recurrence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_item_recurrence` (
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
  CONSTRAINT `fk_checklist_item_recurrence_item` FOREIGN KEY (`IDchecklistitem`) REFERENCES `checklist_item` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_item_recurrence`
--

LOCK TABLES `checklist_item_recurrence` WRITE;
/*!40000 ALTER TABLE `checklist_item_recurrence` DISABLE KEYS */;
INSERT INTO `checklist_item_recurrence` VALUES
(1,4,'quarterly','1',5,'day',10,'day','2026-09-26 00:00:00',1,'2026-07-24 09:21:09','2026-07-24 09:21:09'),
(2,5,'yearly','1',30,'day',30,'day','2026-12-02 00:00:00',1,'2026-07-24 09:21:48','2026-07-24 09:21:48'),
(3,6,'monthly','25',2,'day',3,'day','2026-08-23 00:00:00',1,'2026-07-24 09:22:31','2026-07-24 09:22:43');
/*!40000 ALTER TABLE `checklist_item_recurrence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_run`
--

DROP TABLE IF EXISTS `checklist_run`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_run` (
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
  CONSTRAINT `fk_checklist_run_checklist` FOREIGN KEY (`IDchecklist`) REFERENCES `checklist` (`id`),
  CONSTRAINT `fk_checklist_run_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_checklist_run_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checklist_run_project` FOREIGN KEY (`IDproject_root`) REFERENCES `project` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_checklist_run_trigger` FOREIGN KEY (`IDchecklisttrigger`) REFERENCES `checklist_trigger` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_checklist_run_user` FOREIGN KEY (`IDuser_created`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_run`
--

LOCK TABLES `checklist_run` WRITE;
/*!40000 ALTER TABLE `checklist_run` DISABLE KEYS */;
INSERT INTO `checklist_run` VALUES
(1,1,1,1,708,23,1,'2026-08-01 00:00:00','running','2026-07-24 09:33:08','2026-07-24 09:33:08',NULL);
/*!40000 ALTER TABLE `checklist_run` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_run_item`
--

DROP TABLE IF EXISTS `checklist_run_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_run_item` (
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
  CONSTRAINT `fk_checklist_run_item_run` FOREIGN KEY (`IDchecklistrun`) REFERENCES `checklist_run` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checklist_run_item_template` FOREIGN KEY (`IDchecklistitem`) REFERENCES `checklist_item` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_run_item`
--

LOCK TABLES `checklist_run_item` WRITE;
/*!40000 ALTER TABLE `checklist_run_item` DISABLE KEYS */;
INSERT INTO `checklist_run_item` VALUES
(1,1,1,25,'2026-07-27 00:00:00','created','2026-07-24 09:33:08','2026-07-28 10:32:27','2026-07-28 10:32:27',NULL),
(2,1,2,NULL,'2026-07-31 00:00:00','waiting','2026-07-24 09:33:08','2026-07-24 09:33:08',NULL,NULL),
(3,1,3,24,'2026-07-24 09:33:08','created','2026-07-24 09:33:08','2026-07-24 09:33:08','2026-07-24 09:33:08',NULL);
/*!40000 ALTER TABLE `checklist_run_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_trigger`
--

DROP TABLE IF EXISTS `checklist_trigger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checklist_trigger` (
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
  CONSTRAINT `fk_checklist_trigger_checklist` FOREIGN KEY (`IDchecklist`) REFERENCES `checklist` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_trigger`
--

LOCK TABLES `checklist_trigger` WRITE;
/*!40000 ALTER TABLE `checklist_trigger` DISABLE KEYS */;
INSERT INTO `checklist_trigger` VALUES
(1,1,'primary','manual',NULL,NULL,NULL,'create_new',1,'2026-07-23 15:52:32','2026-07-24 09:32:36'),
(2,2,'primary','container',NULL,NULL,NULL,'reuse_open',0,'2026-07-24 09:20:19','2026-07-24 09:22:38');
/*!40000 ALTER TABLE `checklist_trigger` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `title` varchar(190) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `info_url` varchar(500) DEFAULT NULL,
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
  KEY `idx_decision_proposal_author` (`IDuser_author`),
  KEY `idx_decision_proposal_active` (`active`),
  CONSTRAINT `fk_decision_proposal_group` FOREIGN KEY (`IDdecision_group`) REFERENCES `decision_group` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_decision_proposal_author` FOREIGN KEY (`IDuser_author`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_decision_proposal_process` FOREIGN KEY (`IDdecision_process`) REFERENCES `decision_process` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decision_proposal`
--

LOCK TABLES `decision_proposal` WRITE;
/*!40000 ALTER TABLE `decision_proposal` DISABLE KEYS */;
/*!40000 ALTER TABLE `decision_proposal` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decision_governance_action`
--

LOCK TABLES `decision_governance_action` WRITE;
/*!40000 ALTER TABLE `decision_governance_action` DISABLE KEYS */;
/*!40000 ALTER TABLE `decision_governance_action` ENABLE KEYS */;
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
  CONSTRAINT `fk_chat_thread_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_thread_creator` FOREIGN KEY (`IDuser_created`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chat_message`
--

DROP TABLE IF EXISTS `chat_message`;
/*!40101 SET @saved_cs_client     = @@CHARACTER_SET_CLIENT */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDchat_thread` int(11) NOT NULL,
  `IDorganization` int(11) NOT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `IDdecision_participant` int(11) DEFAULT NULL,
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
  KEY `idx_chat_message_decision_participant` (`IDdecision_participant`),
  KEY `idx_chat_message_type` (`message_type`),
  CONSTRAINT `fk_chat_message_thread` FOREIGN KEY (`IDchat_thread`) REFERENCES `chat_thread` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_message_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_message_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_chat_message_decision_participant` FOREIGN KEY (`IDdecision_participant`) REFERENCES `decision_participant` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decision_result`
--

LOCK TABLES `decision_result` WRITE;
/*!40000 ALTER TABLE `decision_result` DISABLE KEYS */;
/*!40000 ALTER TABLE `decision_result` ENABLE KEYS */;
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
  `etherpadpadid` varchar(255) DEFAULT NULL,
  `ethercalcroomid` varchar(255) DEFAULT NULL,
  `IDdocument_parent` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL,
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
  KEY `idx_document_pv_official_editor` (`IDuser_pv_official_editor`),
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
  CONSTRAINT `fk_document_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_parent` FOREIGN KEY (`IDdocument_parent`) REFERENCES `document` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_pv_editor` FOREIGN KEY (`IDuser_pv_editor`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_pv_official_editor` FOREIGN KEY (`IDuser_pv_official_editor`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_user_creation` FOREIGN KEY (`IDusercreation`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_user_editing` FOREIGN KEY (`IDuseredition`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_user_modification` FOREIGN KEY (`IDusermodification`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2306 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document`
--

LOCK TABLES `document` WRITE;
/*!40000 ALTER TABLE `document` DISABLE KEYS */;
INSERT INTO `document` (`id`, `title`, `description`, `content`, `contentedition`, `datecontentedition`, `keywords`, `IDuser`, `IDusercreation`, `IDorganization`, `IDholon`, `IDevent`, `estDossier`, `active`, `documenttype`, `pvstage`, `is_template`, `externalurl`, `openinnewwindow`, `storedfilepath`, `storedfilename`, `storedfilemime`, `storedfilesize`, `etherpadpadid`, `ethercalcroomid`, `IDdocument_parent`, `datecreation`, `datemodification`, `dateedition`, `IDuseredition`, `IDuser_pv_editor`, `IDuser_pv_official_editor`, `pv_editor_handover_open`, `IDusermodification`, `version`, `codeview`, `codeedit`) VALUES
(9,'Analyse et Conséquences d\'un Test Fonctionnel','Evaluation d\'un test montrant son succès et les opportunités d\'amélioration.','<div><h2>Première section: Compte rendu</h2><ul><li>Rouge</li><li>Vert</li><li>Bleu</li></ul><h2>Deuxième partie: Conséquences à en tirer</h2><ol><li>Ça fonctionne</li><li>Ça peut être amélioré</li></ol></div>',NULL,NULL,NULL,1,1,NULL,NULL,NULL,0,1,'html',NULL,0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2024-03-03 08:52:50',NULL,NULL,NULL,NULL,0,1,1,'',''),
(2003,'Liste des actions urgentes','Document de travail pour les tâches à finaliser avant la fin de semaine.','<ul><li>Relancer les partenaires</li><li>Valider le budget</li><li>Préparer la réunion du lundi</li></ul>',NULL,NULL,'actions,urgent,suivi',1,1,1,679,NULL,0,1,'html',NULL,0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2026-04-20 08:05:00','2026-04-20 08:20:00',NULL,NULL,NULL,0,1,1,'',''),
(2004,'Préparation réunion trimestrielle','Première trame pour la réunion de gouvernance du trimestre.','<p>Ordre du jour provisoire, points de vigilance et sujets à arbitrer.</p>',NULL,NULL,'réunion,gouvernance,trimestre',1,1,1,687,NULL,0,1,'html',NULL,0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2026-04-16 14:40:00','2026-04-17 07:55:00',NULL,NULL,NULL,0,1,1,'',''),
(2005,'Retour sur les inscriptions','Analyse rapide du rythme des inscriptions et des canaux les plus efficaces.','<p>Les recommandations portent sur la simplification du formulaire et le rappel des échéances.</p>',NULL,NULL,'inscriptions,analyse,communication',1,1,1,687,NULL,0,1,'html',NULL,0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2026-04-10 11:10:00','2026-04-10 11:55:00',NULL,NULL,NULL,0,1,1,'',''),
(2006,'Synthèse du mois de mars','Vue d’ensemble des dossiers ouverts et des points encore bloqués.','<p>Document récapitulatif pour garder une trace des arbitrages en cours.</p>',NULL,NULL,'mars,synthèse,dossiers',1,1,1,687,NULL,0,1,'html',NULL,0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2026-03-28 17:05:00','2026-03-29 09:00:00',NULL,NULL,NULL,0,1,1,'',''),
(2007,'Organisation de la journée portes ouvertes','Document de cadrage pour la préparation de l’événement du printemps.','<p>Planning, matériel, besoins d’accueil et répartition des responsabilités.</p>',NULL,NULL,'événement,portes ouvertes,planning',1,1,1,NULL,NULL,0,1,'html',NULL,0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2026-03-05 15:25:00','2026-03-06 08:10:00',NULL,NULL,NULL,0,1,1,'',''),
(2008,'Suivi budget février','Point intermédiaire sur les dépenses et les engagements en cours.','<p>Mise à jour des postes sensibles et des arbitrages à prendre avant clôture.</p>',NULL,NULL,'budget,finances,février',1,1,1,NULL,NULL,0,1,'html',NULL,0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2026-02-14 10:30:00','2026-02-14 12:00:00',NULL,NULL,NULL,0,1,1,'',''),
(2009,'Plan de communication hiver','Proposition de calendrier éditorial et de messages clés.','<p>Inclut une série de publications, une newsletter et une relance ciblée des membres.</p>',NULL,NULL,'communication,newsletter,planning',1,1,1,NULL,NULL,0,1,'html',NULL,0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2026-01-18 09:45:00','2026-01-18 10:15:00',NULL,NULL,NULL,0,1,1,'',''),
(2010,'Bilan de fin d’année','Résumé des projets terminés et des enseignements tirés.','<p>Le document recense les réussites, les points à améliorer et quelques pistes pour l’année suivante.</p>',NULL,NULL,'bilan,année,rétrospective',1,1,1,NULL,NULL,0,1,'html',NULL,0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-12-19 16:20:00','2025-12-20 09:10:00',NULL,NULL,NULL,0,1,1,'',''),
(2011,'Compte rendu rentrée associative','Notes prises au moment de la reprise des activités de septembre.','<p>Reprise des permanences, remise à jour des contacts et coordination de l’accueil.</p>',NULL,NULL,'association,rentrée,organisation',1,1,1,NULL,NULL,0,1,'html',NULL,0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 18:35:00','2025-09-08 19:00:00',NULL,NULL,NULL,0,1,1,'',''),
(2012,'Préparation séminaire d’été','Liste des besoins logistiques et des sujets à traiter pendant le séminaire.','<p>Repas, hébergement, ateliers et coordination de l’animation.</p>',NULL,NULL,'séminaire,été,logistique',1,1,1,NULL,NULL,0,1,'html',NULL,0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-07-01 13:50:00','2025-07-02 08:30:00',NULL,NULL,NULL,0,1,1,'',''),
(2013,'Feuille de route printemps 2025','Première version de la feuille de route pour les mois à venir.','<p>Définition des priorités, clarification des ressources disponibles et répartition des responsabilités.</p>',NULL,NULL,'feuille de route,stratégie,printemps',1,1,2,NULL,NULL,0,1,'html',NULL,0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-05-21 10:05:00','2025-05-21 10:40:00',NULL,NULL,NULL,0,1,1,'',''),
(2014,'Carnet de bord lancement annuel','Notes de cadrage prises au début du cycle annuel précédent.','<p>Objectifs de départ, points d’attention et premiers engagements opérationnels.</p>',NULL,NULL,'lancement,année,cadrage',1,1,1,NULL,NULL,0,1,'html',NULL,0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-04-24 08:40:00','2025-04-24 09:15:00',NULL,NULL,NULL,0,1,1,'',''),
(2305,'PV Réunion OP du 25.07.2026 09:00','Document associe a l evenement \"Réunion OP\".',NULL,NULL,NULL,'PV',1,1,1,678,4,0,1,'pv','preparation',0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2026-07-25 10:00:00','2026-07-24 08:37:27',NULL,NULL,1,0,1,1,'f22d6d443864711b8001','c23361ca4a2be1758381');
/*!40000 ALTER TABLE `document` ENABLE KEYS */;
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
  `author_email` varchar(250) DEFAULT NULL,
  `IDholon_concerned` int(11) DEFAULT NULL,
  `content` mediumtext DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 1,
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
  CONSTRAINT `fk_document_pv_point_document` FOREIGN KEY (`IDdocument`) REFERENCES `document` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_pv_point_editing_user` FOREIGN KEY (`IDuser_editing`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_pv_point_holon_concerned` FOREIGN KEY (`IDholon_concerned`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_pv_point_modification_user` FOREIGN KEY (`IDuser_modification`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_pv_point_parent` FOREIGN KEY (`IDparent`) REFERENCES `document_pv_point` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2318 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_pv_point`
--

LOCK TABLES `document_pv_point` WRITE;
/*!40000 ALTER TABLE `document_pv_point` DISABLE KEYS */;
INSERT INTO `document_pv_point` (`id`, `IDdocument`, `item_type`, `IDparent`, `title`, `IDuser_author`, `IDuser_modification`, `IDuser_editing`, `edit_lock_token`, `author_email`, `IDholon_concerned`, `content`, `position`, `desired_duration_minutes`, `actual_duration_minutes`, `pointtype`, `is_handled`, `active`, `datecreation`, `datemodification`, `dateedition`) VALUES
(2315,2305,'point',NULL,'Revue des indicateurs',1,1,NULL,NULL,NULL,NULL,'<h3>Indicateurs financiers</h3><p><span class=\"omo-indicator-embed omo-indicator-embed--current\" contenteditable=\"false\" data-omo-embed-type=\"indicator\" data-omo-indicator-id=\"1\" data-omo-indicator-kind=\"group\" data-omo-indicator-title=\"Liquidités\" data-omo-indicator-value=\"2 indicateurs\" data-omo-indicator-context=\"Groupe cumule\" data-omo-indicator-status=\"A jour\"><span class=\"omo-indicator-embed__main\"><span class=\"omo-indicator-embed__chart\"><span class=\"omo-indicator-embed__chart-plot\"><span class=\"omo-indicator-embed__chart-svg\"><svg class=\"omo-stats-chart omo-stats-chart--compact omo-stats-chart--group\" viewBox=\"0 0 180 54\" aria-hidden=\"true\"><polyline class=\"omo-stats-chart__line omo-stats-chart__line--sum\" points=\"3,11.07 91.43,10.76 94.28,10.78 177,11.22\" style=\"stroke:#2563eb\"></polyline><line class=\"omo-stats-chart__scale-line\" x1=\"21\" y1=\"3\" x2=\"177\" y2=\"3\"></line><line class=\"omo-stats-chart__scale-line\" x1=\"21\" y1=\"51\" x2=\"177\" y2=\"51\"></line><text class=\"omo-stats-chart__scale-label\" x=\"0\" y=\"7\">100 000</text><text class=\"omo-stats-chart__scale-label\" x=\"0\" y=\"51\">0</text></svg></span></span></span><span class=\"omo-indicator-embed__copy\"><strong><a class=\"omo-indicator-embed__title\" href=\"#stats\"><span class=\"omo-indicator-embed__status-dot omo-indicator-embed__status-dot--current\" aria-hidden=\"true\"></span><span>Liquidités</span></a></strong></span><span class=\"omo-indicator-embed__values\"><b>2 indicateurs</b><em>A jour</em></span></span></span><span class=\"omo-indicator-embed omo-indicator-embed--current\" contenteditable=\"false\" data-omo-embed-type=\"indicator\" data-omo-indicator-id=\"1\" data-omo-indicator-kind=\"indicator\" data-omo-indicator-title=\"Solde du compte bancaire\" data-omo-indicator-description=\"Cash disponible sur le compte bancaire\" data-omo-indicator-value=\"82 345\" data-omo-indicator-date=\"01.07.2026\" data-omo-indicator-context=\"Comptabilite et budget\" data-omo-indicator-chart-min=\"82 200\" data-omo-indicator-chart-max=\"83 600\" data-omo-indicator-status=\"A jour\"><span class=\"omo-indicator-embed__main\"><span class=\"omo-indicator-embed__chart\"><span class=\"omo-indicator-embed__chart-plot\"><span class=\"omo-indicator-embed__chart-svg\"><svg class=\"omo-stats-chart omo-stats-chart--compact\" viewBox=\"0 0 180 54\" aria-hidden=\"true\"><polyline class=\"omo-stats-chart__line\" points=\"2,6.62 91.44,5 178,7.31\"></polyline><circle class=\"omo-stats-chart__point\" cx=\"178\" cy=\"7.31\" r=\"2.5\"></circle><line class=\"omo-stats-chart__scale-line\" x1=\"20\" y1=\"2\" x2=\"178\" y2=\"2\"></line><line class=\"omo-stats-chart__scale-line\" x1=\"20\" y1=\"52\" x2=\"178\" y2=\"52\"></line><text class=\"omo-stats-chart__scale-label\" x=\"0\" y=\"6\">85 000</text><text class=\"omo-stats-chart__scale-label\" x=\"0\" y=\"52\">60 000</text></svg></span></span></span><span class=\"omo-indicator-embed__copy\"><strong><a class=\"omo-indicator-embed__title\" href=\"#stats-i1\"><span class=\"omo-indicator-embed__status-dot omo-indicator-embed__status-dot--current\" aria-hidden=\"true\"></span><span>Solde du compte bancaire</span></a></strong><span class=\"omo-indicator-embed__description\">Cash disponible sur le compte bancaire</span></span><span class=\"omo-indicator-embed__values\"><b>82 345</b><time>01.07.2026</time><em>A jour</em></span></span></span><span class=\"omo-indicator-embed omo-indicator-embed--current\" contenteditable=\"false\" data-omo-embed-type=\"indicator\" data-omo-indicator-id=\"2\" data-omo-indicator-kind=\"indicator\" data-omo-indicator-title=\"Solde en caisse\" data-omo-indicator-description=\"Montant disponible en liquide dans la caisse\" data-omo-indicator-value=\"536\" data-omo-indicator-date=\"01.07.2026\" data-omo-indicator-context=\"Comptabilite et budget\" data-omo-indicator-chart-min=\"300\" data-omo-indicator-chart-max=\"550\" data-omo-indicator-status=\"A jour\"><span class=\"omo-indicator-embed__main\"><span class=\"omo-indicator-embed__chart\"><span class=\"omo-indicator-embed__chart-plot\"><span class=\"omo-indicator-embed__chart-svg\"><svg class=\"omo-stats-chart omo-stats-chart--compact\" viewBox=\"0 0 180 54\" aria-hidden=\"true\"><polyline class=\"omo-stats-chart__line\" points=\"2,11.33 94.33,24.58 178,7.33\"></polyline><circle class=\"omo-stats-chart__point\" cx=\"178\" cy=\"7.33\" r=\"2.5\"></circle><line class=\"omo-stats-chart__scale-line\" x1=\"20\" y1=\"2\" x2=\"178\" y2=\"2\"></line><line class=\"omo-stats-chart__scale-line\" x1=\"20\" y1=\"52\" x2=\"178\" y2=\"52\"></line><text class=\"omo-stats-chart__scale-label\" x=\"0\" y=\"6\">600</text><text class=\"omo-stats-chart__scale-label\" x=\"0\" y=\"52\">0</text></svg></span></span></span><span class=\"omo-indicator-embed__copy\"><strong><a class=\"omo-indicator-embed__title\" href=\"#stats-i2\"><span class=\"omo-indicator-embed__status-dot omo-indicator-embed__status-dot--current\" aria-hidden=\"true\"></span><span>Solde en caisse</span></a></strong><span class=\"omo-indicator-embed__description\">Montant disponible en liquide dans la caisse</span></span><span class=\"omo-indicator-embed__values\"><b>536</b><time>01.07.2026</time><em>A jour</em></span></span></span><br></p>',1,NULL,NULL,'information',0,1,'2026-07-24 08:37:33','2026-07-24 09:54:44',NULL),
(2316,2305,'point',NULL,'Revue des checklists',1,1,NULL,NULL,NULL,682,'<p><span class=\"omo-checklist-embed\" contenteditable=\"false\" data-omo-embed-type=\"checklist\" data-omo-checklist-id=\"2\" data-omo-checklist-title=\"Factures récurrentes\"><strong><a href=\"#checklist-c2\">Factures récurrentes</a></strong><em>Gestion administrative</em></span><span class=\"omo-checklist-embed\" contenteditable=\"false\" data-omo-embed-type=\"checklist\" data-omo-checklist-id=\"1\" data-omo-checklist-title=\"Processus d\'accueil des nouveaux et nouvelles\"><strong><a href=\"#checklist-c1\">Processus d\'accueil des nouveaux et nouvelles</a></strong><em>Inclusion</em></span><br></p>',2,NULL,NULL,'information',0,1,'2026-07-24 08:57:32','2026-07-24 07:37:32',NULL),
(2317,2305,'point',NULL,'Revue des projets',1,1,NULL,NULL,NULL,NULL,'<h3>Projets stratégiques:</h3><p><span class=\"omo-project-embed\" contenteditable=\"false\" data-omo-embed-type=\"project\" data-omo-project-id=\"9\" data-omo-project-title=\"Consolider nos pratiques administratives\"><strong><a href=\"#projects-d9\">Consolider nos pratiques administratives</a><a href=\"/omo/c/678#projects-d9\" target=\"_blank\" rel=\"noopener noreferrer\">↗</a><em>En cours</em><em>En cours</em><em>En cours</em><em>P2</em><em>M</em></strong><em>Ancrage · Admin · Planifie 01.01.2026 · Fin 31.12.2026</em></span><span class=\"omo-project-embed\" contenteditable=\"false\" data-omo-embed-type=\"project\" data-omo-project-id=\"8\" data-omo-project-title=\"Elargir notre réseau professionnel\"><strong><a href=\"#projects-d8\">Elargir notre réseau professionnel</a><a href=\"/omo/c/678#projects-d8\" target=\"_blank\" rel=\"noopener noreferrer\">↗</a><em>En cours</em><em>En cours</em><em>En cours</em><em>P2</em><em>M</em></strong><em>Ancrage · Admin · Planifie 01.01.2026 · Fin 31.12.2026</em></span><span class=\"omo-project-embed\" contenteditable=\"false\" data-omo-embed-type=\"project\" data-omo-project-id=\"7\" data-omo-project-title=\"Refondre notre communication et notre marketing\"><strong><a href=\"#projects-d7\">Refondre notre communication et notre marketing</a><a href=\"/omo/c/678#projects-d7\" target=\"_blank\" rel=\"noopener noreferrer\">↗</a><em>En cours</em><em>En cours</em><em>En cours</em><em>P3</em><em>M</em></strong><em>Ancrage · Admin · Planifie 01.01.2026 · Fin 31.12.2026</em></span><br></p>',3,NULL,NULL,'information',0,1,'2026-07-24 09:00:17','2026-07-24 09:54:44',NULL);
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
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `IDorganization` int(11) NOT NULL,
  `IDholon` int(11) DEFAULT NULL,
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
  CONSTRAINT `fk_event_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_event_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event`
--

LOCK TABLES `event` WRITE;
/*!40000 ALTER TABLE `event` DISABLE KEYS */;
INSERT INTO `event` VALUES
(4,1,678,1,'Réunion OP',NULL,'confirmed','Europe/Zurich','virtual',NULL,'https://david.instantz.org','2026-07-25 09:00:00','2026-07-25 10:00:00',0,NULL,1,'2026-07-24 06:37:27','2026-07-24 06:37:27');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `answer` text NOT NULL,
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
  PRIMARY KEY (`id`),
  KEY `idx_faq_parcours` (`IDparcours`),
  KEY `idx_faq_application` (`IDapplication`),
  KEY `idx_faq_reliability` (`reliability`),
  KEY `idx_faq_reliability_updated_at` (`reliability_updated_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3223 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faq`
--

LOCK TABLES `faq` WRITE;
/*!40000 ALTER TABLE `faq` DISABLE KEYS */;
INSERT INTO `faq` VALUES
(1,NULL,NULL,NULL,NULL,NULL,'Ma première question','Réponse de ma première question',NULL,NULL,'Détail de la réponse de la première question',0,0,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-04-12 08:08:05','2026-07-23 11:51:09'),
(2,NULL,NULL,NULL,NULL,NULL,'Ma deuxième question','Réponse de ma deuxième question',NULL,NULL,'Détail de la réponse de la deuxième question',0,0,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-04-12 08:08:05','2026-07-23 11:51:09'),
(3201,NULL,NULL,NULL,NULL,NULL,'Comment ouvrir les outils d aide dans OMO ?','Ouvrez le bouton Aide dans la topbar pour retrouver la FAQ, les tutoriels et la visite guidee.',NULL,NULL,'<p>La zone Aide centralise les ressources utiles quand vous avez un doute ou que vous decouvrez un ecran.</p><p>La FAQ donne des reponses rapides, les tutoriels vont plus loin et la visite guidee explique les boutons visibles sur la page en cours.</p>',10,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 09:00:00','2026-06-22 09:00:00'),
(3202,NULL,NULL,NULL,NULL,NULL,'Comment utiliser la recherche de la topbar ?','Tapez quelques mots cles dans la recherche puis ouvrez le resultat qui correspond a votre besoin.',NULL,NULL,'<p>La recherche de la topbar sert a retrouver rapidement un cercle, un role, un outil ou un acces utile.</p><p>Si plusieurs modules sont proposes, commencez par ceux qui correspondent a votre besoin puis affinez avec des mots simples et precis.</p>',20,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 09:05:00','2026-06-22 09:05:00'),
(3203,NULL,NULL,NULL,NULL,NULL,'Comment changer ma langue ou mon theme ?','Ouvrez le menu Profil dans la topbar pour regler la langue et le theme d affichage.',NULL,NULL,'<p>Le menu Profil permet de retrouver les reglages personnels les plus utiles sans quitter votre espace de travail.</p><p>Vous pouvez y adapter la langue de l interface et choisir le theme qui vous convient le mieux pour votre usage quotidien.</p>',30,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 09:10:00','2026-06-22 09:10:00'),
(3204,NULL,NULL,NULL,NULL,NULL,'A quoi sert le switch Local / Enfants directs / Descendants ?','Il permet d afficher le holon courant seul, avec ses enfants directs ou avec tous ses descendants.',NULL,NULL,'<p>Le mode Local affiche uniquement les elements lies au holon courant.</p><p>Enfants directs ajoute les elements lies a ses enfants, tandis que Descendants couvre tout son sous-arbre.</p>',40,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 09:15:00','2026-06-22 09:15:00'),
(3205,NULL,NULL,NULL,NULL,NULL,'Comment passer du tri Date au tri Alphabetique ?','Utilisez le controle de tri dans l entete du drawer pour choisir l ordre qui vous aide le plus.',NULL,NULL,'<p>Le tri par date est utile pour revoir ce qui vient d etre cree ou modifie recemment.</p><p>Le tri alphabetique est souvent plus confortable quand vous cherchez un nom connu dans une longue liste.</p>',50,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 09:20:00','2026-06-22 09:20:00'),
(3206,NULL,NULL,NULL,NULL,NULL,'A quoi sert le mode Detail / Compact ?','Le mode Detail montre plus d informations par carte, tandis que Compact affiche plus d elements a l ecran.',NULL,NULL,'<p>Choisissez Detail quand vous voulez lire les resumes, les metadonnees ou mieux comparer plusieurs cartes.</p><p>Choisissez Compact quand vous voulez parcourir beaucoup d elements rapidement, en particulier sur mobile ou dans une colonne etroite.</p>',60,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 09:25:00','2026-06-22 09:25:00'),
(3207,NULL,NULL,NULL,NULL,NULL,'Comment creer un document dans OMO ?','Ouvrez l app Documents puis utilisez le bouton Ajouter si votre role vous y autorise.',NULL,NULL,'<p>Sur grand ecran, le bouton de creation apparait dans l entete du module. Sur mobile, il peut etre reduit a une icone en haut a droite.</p><p>Si vous ne voyez pas ce bouton, cela signifie en general que votre contexte actuel ou vos droits ne permettent pas cette creation.</p>',70,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 09:30:00','2026-06-22 09:30:00'),
(3208,NULL,NULL,NULL,NULL,NULL,'Comment modifier un document existant ?','Ouvrez le document puis lancez l action Editer depuis le drawer ou le menu prevu.',NULL,NULL,'<p>L edition passe par le formulaire du document et enregistre les changements dans le contexte de ce document.</p><p>Si un document appartient deja a une organisation ou a un holon, les droits de ce contexte continuent a s appliquer au moment de la sauvegarde.</p>',80,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 09:35:00','2026-06-22 09:35:00'),
(3209,NULL,NULL,NULL,NULL,NULL,'A quoi sert l app Memo ?','Memo rassemble vos documents personnels et vos notes dans une vue simple a parcourir.',NULL,NULL,'<p>La liste Memo peut regrouper les documents dont vous etes l auteur, y compris quand ils proviennent de plusieurs holons.</p><p>Le detail se consulte ensuite dans un drawer interne, ce qui permet de rester dans le meme espace sans ouvrir une nouvelle page.</p>',90,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 09:40:00','2026-06-22 09:40:00'),
(3210,NULL,NULL,NULL,NULL,NULL,'Comment reediter un memo depuis l app Memo ?','Ouvrez le menu ... sur un memo puis choisissez Editer.',NULL,NULL,'<p>L action Editer ouvre le formulaire du memo dans un drawer, avec un parcours proche de celui du module Documents.</p><p>Les memos sans contexte d organisation peuvent etre reedites par leur auteur, alors que les documents deja classes gardent les droits de leur contexte habituel.</p>',100,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 09:45:00','2026-06-22 09:45:00'),
(3211,NULL,NULL,NULL,NULL,NULL,'Comment terminer ou classer un memo depuis Telegram ?','Utilisez les boutons proposes par le bot pour choisir une destination autorisee ou terminer dans le contexte courant.',NULL,NULL,'<p>Le bot ne propose que les destinations de classement qui restent autorisees pour votre contexte et vos droits.</p><p>Si le bouton Terminer ici ou certaines destinations ne sont pas visibles, cela signifie simplement que cette action nest pas disponible pour vous a cet endroit.</p>',110,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 09:50:00','2026-06-22 09:50:00'),
(3212,NULL,NULL,NULL,NULL,NULL,'Comment creer une prise de decision ?','Ouvrez l app Decisions puis utilisez le bouton de creation disponible dans l entete si vous avez le droit necessaire.',NULL,NULL,'<p>La creation se fait dans le contexte courant, par exemple pour une organisation, un cercle ou un autre niveau de structure.</p><p>Prenez le temps de definir un titre clair, une description utile et les dates importantes avant de lancer la participation.</p>',120,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 09:55:00','2026-06-22 09:55:00'),
(3213,NULL,NULL,NULL,NULL,NULL,'Comment participer a une decision avec un lien public ou un acces personnel ?','Ouvrez la page de participation recue par lien ou demandez votre acces personnel depuis l ecran public du scrutin.',NULL,NULL,'<p>Certaines decisions peuvent accepter la participation sans invitation classique, directement depuis un lien public partage par l organisateur.</p><p>Si ce nest pas le cas, utilisez la page Recevoir mon acces personnel pour demander un lien individuel avant de voter.</p>',130,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 10:00:00','2026-06-22 10:00:00'),
(3214,NULL,NULL,NULL,NULL,NULL,'Comment noter des propositions en jugement majoritaire ?','Attribuez une mention a chaque proposition selon l echelle affichee, de la plus favorable a la moins favorable.',NULL,NULL,'<p>Le jugement majoritaire ne consiste pas a choisir une seule proposition. Vous evaluez chaque option avec la meme echelle.</p><p>Le resultat final compare ensuite la repartition des mentions pour aider a faire ressortir la proposition la plus solide.</p>',140,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 10:05:00','2026-06-22 10:05:00'),
(3215,NULL,NULL,NULL,NULL,NULL,'Comment ajouter un evenement dans le calendrier ?','Ouvrez le calendrier puis utilisez le bouton Ajouter si votre contexte vous autorise a creer des dates.',NULL,NULL,'<p>Comme pour les autres modules, le bouton peut etre plein texte sur grand ecran ou reduit a une icone sur mobile.</p><p>Si vous ne pouvez pas creer de date a cet endroit, changez de contexte ou demandez a une personne administratrice de verifier vos droits.</p>',150,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 10:10:00','2026-06-22 10:10:00'),
(3216,NULL,NULL,NULL,NULL,NULL,'Comment changer de vue dans le calendrier ?','Utilisez le selecteur Mois, Semaine, Jour ou Liste pour choisir la lecture la plus pratique.',NULL,NULL,'<p>Chaque vue repond a un besoin different: Mois pour la planification generale, Semaine ou Jour pour le detail, Liste pour un balayage rapide.</p><p>Sur mobile, ces vues peuvent apparaitre sous forme d icones plus compactes afin de laisser davantage de place au contenu.</p>',160,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 10:15:00','2026-06-22 10:15:00'),
(3217,NULL,NULL,NULL,NULL,NULL,'Pourquoi je ne vois pas toujours le bouton Ajouter ?','Le bouton apparait seulement si vous avez la permission de creation dans le contexte ouvert.',NULL,NULL,'<p>Ce principe vaut notamment pour les documents, les prises de decision, les dates et la creation de FAQ.</p><p>Si vous pensez que ce bouton devrait etre disponible, verifiez le contexte courant ou demandez une verification des permissions sur le holon concerne.</p>',170,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 10:20:00','2026-06-22 10:20:00'),
(3218,NULL,NULL,NULL,NULL,NULL,'Comment ajouter une question dans la FAQ ?','Ouvrez la FAQ du contexte voulu puis utilisez le bouton Ajouter une question si cette action est disponible.',NULL,NULL,'<p>Selon votre ecran, la nouvelle question peut etre creee au niveau du contexte courant, du niveau organisation ou dans un scope plus global.</p><p>Si aucun bouton de creation ne saffiche, cela signifie que la permission de creation de FAQ nest pas accordee dans ce contexte.</p>',180,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 10:25:00','2026-06-22 10:25:00'),
(3219,NULL,NULL,NULL,NULL,NULL,'A quoi servent les votes sur les reponses de la FAQ ?','Les boutons de vote permettent de signaler si une reponse est utile afin de mieux mettre en avant les bonnes explications.',NULL,NULL,'<p>Quand une reponse vous aide vraiment, un vote positif aide a la faire remonter dans la FAQ.</p><p>Ces retours servent a rendre les questions les plus utiles plus visibles pour les autres membres de l organisation.</p>',190,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 10:30:00','2026-06-22 10:30:00'),
(3220,NULL,NULL,NULL,NULL,NULL,'Comment faire apparaitre mon organisation sur la carte publique ?','Renseignez un emplacement dans les parametres de l organisation et verifiez que les informations utiles sont lisibles sans connexion.',NULL,NULL,'<p>La carte publique utilise un emplacement facultatif, generalement saisi en latitude et longitude dans les parametres de l organisation.</p><p>Seules les informations explicitement exposees comme publiques sont reprises sur cette carte, ce qui permet de garder le controle sur ce qui est visible sans connexion.</p>',200,1,0,0,0,0,0,'2026-07-25 12:27:29','2026-07-25 12:27:29','2026-06-22 10:35:00','2026-06-22 10:35:00'),
(3221,NULL,NULL,NULL,NULL,NULL,'Est-ce que je peux ajouter les contacts de OMO a mon telephone ou mon ordinateur ?','Oui. Ajoutez un compte CardDAV avec votre identifiant OMO pour synchroniser les contacts auxquels vous avez acces.',NULL,NULL,'<p>OMO propose un annuaire CardDAV utilisable par la plupart des telephones et applications de contacts.</p><ol><li>Dans OMO, ouvrez votre profil et definissez un mot de passe si ce n est pas deja fait.</li><li>Dans les reglages de votre appareil ou de votre application de contacts, ajoutez un compte <strong>CardDAV</strong>.</li><li>Comme adresse du serveur, saisissez <code><em>url_serveur</em>/omo/api/carddav/</code>.</li><li>Utilisez votre adresse e-mail OMO, ou votre identifiant de connexion, ainsi que votre mot de passe OMO.</li></ol><p>Seuls les contacts des membres que vous etes autorise a voir sont proposes. La synchronisation est actuellement en lecture seule : les modifications faites sur votre appareil ne sont pas renvoyees dans OMO.</p>',210,1,0,0,0,0,0,'2026-08-01 00:00:00','2026-08-01 00:00:00','2026-08-01 00:00:00','2026-08-01 00:00:00'),
(3222,NULL,NULL,NULL,NULL,NULL,'Est-ce que je peux ajouter les rendez-vous et reunions de OMO a mon telephone ou mon ordinateur ?','Oui. Ajoutez un compte CalDAV pour consulter dans votre calendrier les reunions OMO auxquelles vous avez acces.',NULL,NULL,'<p>OMO propose un calendrier CalDAV utilisable par la plupart des telephones et applications de calendrier.</p><ol><li>Dans OMO, ouvrez votre profil et definissez un mot de passe si ce n est pas deja fait.</li><li>Dans les reglages de votre appareil ou de votre application de calendrier, ajoutez un compte <strong>CalDAV</strong>.</li><li>Comme adresse du serveur, saisissez <code><em>url_serveur</em>/omo/api/caldav/</code>.</li><li>Utilisez votre adresse e-mail OMO, ou votre identifiant de connexion, ainsi que votre mot de passe OMO.</li></ol><p>Les calendriers des organisations dont vous etes membre et dont le calendrier est actif sont proposes automatiquement. La synchronisation est actuellement en lecture seule : creez ou modifiez les reunions directement dans OMO.</p>',220,1,0,0,0,0,0,'2026-08-01 00:00:00','2026-08-01 00:00:00','2026-08-01 00:00:00','2026-08-01 00:00:00');
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
INSERT INTO `faq_choice` VALUES
(1,1,'Propisition 1 (la bonne)',1),
(2,1,'Proposition 2 (la mauvaise)',0),
(3,2,'Propisition 1 (la mauvaise)',0),
(4,2,'Proposition 2 (la bonne)',1),
(5,2,'Proposition 3 (la bonne)',1);
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
  `IDholon_circle` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `content` mediumtext NOT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_history_organization` (`IDorganization`),
  KEY `idx_history_user` (`IDuser`),
  KEY `idx_history_holon_circle` (`IDholon_circle`),
  KEY `idx_history_action` (`action`),
  KEY `idx_history_datecreation` (`datecreation`),
  FULLTEXT KEY `ft_history_content` (`content`),
  CONSTRAINT `fk_history_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `history`
--

LOCK TABLES `history` WRITE;
/*!40000 ALTER TABLE `history` DISABLE KEYS */;
INSERT INTO `history` VALUES
(1,1,1,686,'holon_updated','Modification de [holon|690|Memoire administration (rôle)] :\n- le modele parent a ete retire.','{\"IDholon\":690,\"before\":{\"holon\":{\"id\":690,\"name\":\"Memoire administration\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":686,\"templateId\":682,\"inheritsFromName\":\"Memoire\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"rde\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Garantir une memoire administrative exploitable, de sorte que les decisions et documents utiles restent accessibles et reutilisables.\",\"inheritedValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"visibleValue\":\"Garantir une memoire administrative exploitable, de sorte que les decisions et documents utiles restent accessibles et reutilisables.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domain\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Organisation documentaire du cercle, comptes rendus, historiques de decision et maintenance du systeme d\'information local.\",\"inheritedValue\":\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\",\"visibleValue\":\"Organisation documentaire du cercle, comptes rendus, historiques de decision et maintenance du systeme d\'information local.\",\"visibleItems\":[]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"redevability\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Tracer les decisions, consolider les reperes documentaires et garder les versions utiles a jour.\",\"inheritedValue\":\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\",\"visibleValue\":\"Tracer les decisions, consolider les reperes documentaires et garder les versions utiles a jour.\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":690,\"name\":\"Memoire administration\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":686,\"templateId\":0,\"inheritsFromName\":\"\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"rde\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Garantir une memoire administrative exploitable, de sorte que les decisions et documents utiles restent accessibles et reutilisables.\",\"inheritedValue\":\"\",\"visibleValue\":\"Garantir une memoire administrative exploitable, de sorte que les decisions et documents utiles restent accessibles et reutilisables.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domain\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Organisation documentaire du cercle, comptes rendus, historiques de decision et maintenance du systeme d\'information local.\",\"inheritedValue\":\"\",\"visibleValue\":\"Organisation documentaire du cercle, comptes rendus, historiques de decision et maintenance du systeme d\'information local.\",\"visibleItems\":[]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"redevability\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Tracer les decisions, consolider les reperes documentaires et garder les versions utiles a jour.\",\"inheritedValue\":\"\",\"visibleValue\":\"Tracer les decisions, consolider les reperes documentaires et garder les versions utiles a jour.\",\"visibleItems\":[]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"inheritsFromName\",\"before\":\"Memoire\",\"after\":\"\"}]}','2026-07-23 13:34:17',1),
(2,1,1,678,'holon_member_added','[user|1|Open Organization] a été ajouté au [holon|683|rôle Operations] par [user|1|Open Organization].','{\"IDtargetuser\":1,\"IDholon\":683,\"authorUserId\":1}','2026-07-23 13:38:59',1),
(3,1,1,678,'holon_updated','Modification de [holon|680|Lien pilotage (rôle)] :\n- le parametre \"obligatoire\" a ete active.\n- le parametre \"nom verrouille\" a ete active.\n- le parametre \"unique\" a ete active.\n- le parametre \"lien\" a ete active.','{\"IDholon\":680,\"before\":{\"holon\":{\"id\":680,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"rde\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"inheritedValue\":\"\",\"visibleValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domain\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\",\"inheritedValue\":\"\",\"visibleValue\":\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\",\"visibleItems\":[]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"redevability\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\",\"inheritedValue\":\"\",\"visibleValue\":\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":680,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":true},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"rde\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"inheritedValue\":\"\",\"visibleValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domain\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\",\"inheritedValue\":\"\",\"visibleValue\":\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\",\"visibleItems\":[]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"redevability\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\",\"inheritedValue\":\"\",\"visibleValue\":\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\",\"visibleItems\":[]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"mandatory\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"lockedName\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"unique\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"link\",\"before\":false,\"after\":true}]}','2026-07-23 13:43:48',1),
(4,1,1,678,'holon_updated','Modification de [holon|680|Lien pilotage (rôle)] :\n- la couleur a ete modifiee.','{\"IDholon\":680,\"before\":{\"holon\":{\"id\":680,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":true},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"inheritedValue\":\"\",\"visibleValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\\\"]\",\"visibleItems\":[\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleItems\":[\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\"]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":680,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"#f55c0a\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":true},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"inheritedValue\":\"\",\"visibleValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\\\"]\",\"visibleItems\":[\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleItems\":[\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\"]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"color\",\"before\":\"\",\"after\":\"#f55c0a\"}]}','2026-07-23 13:44:59',1),
(5,1,1,678,'holon_updated','Modification de [holon|681|Facilitation (rôle)] :\n- la couleur a ete modifiee.','{\"IDholon\":681,\"before\":{\"holon\":{\"id\":681,\"name\":\"Facilitation\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.\",\"inheritedValue\":\"\",\"visibleValue\":\"Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\\\"]\",\"visibleItems\":[\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\\\"]\",\"visibleItems\":[\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\"]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":681,\"name\":\"Facilitation\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"#f5870a\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.\",\"inheritedValue\":\"\",\"visibleValue\":\"Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\\\"]\",\"visibleItems\":[\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\\\"]\",\"visibleItems\":[\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\"]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"color\",\"before\":\"\",\"after\":\"#f5870a\"}]}','2026-07-23 13:46:11',1),
(6,1,1,678,'holon_updated','Modification de [holon|682|Memoire (rôle)] :\n- la couleur a ete modifiee.','{\"IDholon\":682,\"before\":{\"holon\":{\"id\":682,\"name\":\"Memoire\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"inheritedValue\":\"\",\"visibleValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\\\"]\",\"visibleItems\":[\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\\\"]\",\"visibleItems\":[\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\"]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":682,\"name\":\"Memoire\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"#f59e0b\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"inheritedValue\":\"\",\"visibleValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\\\"]\",\"visibleItems\":[\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\\\"]\",\"visibleItems\":[\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\"]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"color\",\"before\":\"\",\"after\":\"#f59e0b\"}]}','2026-07-23 13:46:25',1),
(7,1,1,678,'holon_updated','Modification de [holon|682|Memoire (rôle)] :\n- le parametre \"obligatoire\" a ete active.\n- le parametre \"nom verrouille\" a ete active.\n- le parametre \"unique\" a ete active.','{\"IDholon\":682,\"before\":{\"holon\":{\"id\":682,\"name\":\"Memoire\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"#f59e0b\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"inheritedValue\":\"\",\"visibleValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\\\"]\",\"visibleItems\":[\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\\\"]\",\"visibleItems\":[\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\"]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":682,\"name\":\"Memoire\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"#f59e0b\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"inheritedValue\":\"\",\"visibleValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\\\"]\",\"visibleItems\":[\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\\\"]\",\"visibleItems\":[\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\"]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"mandatory\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"lockedName\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"unique\",\"before\":false,\"after\":true}]}','2026-07-23 13:46:45',1),
(8,1,1,678,'holon_updated','Modification de [holon|681|Facilitation (rôle)] :\n- le parametre \"obligatoire\" a ete active.\n- le parametre \"nom verrouille\" a ete active.\n- le parametre \"unique\" a ete active.','{\"IDholon\":681,\"before\":{\"holon\":{\"id\":681,\"name\":\"Facilitation\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"#f5870a\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.\",\"inheritedValue\":\"\",\"visibleValue\":\"Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\\\"]\",\"visibleItems\":[\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\\\"]\",\"visibleItems\":[\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\"]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":681,\"name\":\"Facilitation\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"#f5870a\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.\",\"inheritedValue\":\"\",\"visibleValue\":\"Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\\\"]\",\"visibleItems\":[\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\\\"]\",\"visibleItems\":[\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\"]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"mandatory\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"lockedName\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"unique\",\"before\":false,\"after\":true}]}','2026-07-23 13:46:50',1),
(9,1,1,686,'holon_created','Creation de [holon|702|Facilitation (rôle)].','{\"IDholon\":702,\"after\":{\"holon\":{\"id\":702,\"name\":\"Facilitation\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":686,\"templateId\":681,\"inheritsFromName\":\"Facilitation\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.\",\"visibleValue\":\"Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\\\"]\",\"visibleValue\":\"[\\\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\\\"]\",\"visibleItems\":[\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\\\"]\",\"visibleValue\":\"[\\\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\\\"]\",\"visibleItems\":[\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\"]}},\"permissions\":[]}}','2026-07-23 13:47:11',1),
(10,1,1,686,'holon_created','Creation de [holon|703|Memoire (rôle)].','{\"IDholon\":703,\"after\":{\"holon\":{\"id\":703,\"name\":\"Memoire\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":686,\"templateId\":682,\"inheritsFromName\":\"Memoire\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"visibleValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\\\"]\",\"visibleValue\":\"[\\\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\\\"]\",\"visibleItems\":[\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\\\"]\",\"visibleValue\":\"[\\\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\\\"]\",\"visibleItems\":[\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\"]}},\"permissions\":[]}}','2026-07-23 13:47:19',1),
(11,1,1,686,'holon_created','Creation de [holon|704|Lien pilotage (rôle)].','{\"IDholon\":704,\"after\":{\"holon\":{\"id\":704,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":686,\"templateId\":680,\"inheritsFromName\":\"Lien pilotage\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\\\"]\",\"visibleValue\":\"[\\\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\\\"]\",\"visibleItems\":[\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleItems\":[\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\"]}},\"permissions\":[]}}','2026-07-23 13:47:27',1),
(12,1,1,687,'holon_created','Creation de [holon|705|Memoire (rôle)].','{\"IDholon\":705,\"after\":{\"holon\":{\"id\":705,\"name\":\"Memoire\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":687,\"templateId\":682,\"inheritsFromName\":\"Memoire\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"visibleValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\\\"]\",\"visibleValue\":\"[\\\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\\\"]\",\"visibleItems\":[\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\\\"]\",\"visibleValue\":\"[\\\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\\\"]\",\"visibleItems\":[\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\"]}},\"permissions\":[]}}','2026-07-23 13:47:37',1),
(13,1,1,687,'holon_created','Creation de [holon|706|Facilitation (rôle)].','{\"IDholon\":706,\"after\":{\"holon\":{\"id\":706,\"name\":\"Facilitation\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":687,\"templateId\":681,\"inheritsFromName\":\"Facilitation\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.\",\"visibleValue\":\"Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\\\"]\",\"visibleValue\":\"[\\\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\\\"]\",\"visibleItems\":[\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\\\"]\",\"visibleValue\":\"[\\\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\\\"]\",\"visibleItems\":[\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\"]}},\"permissions\":[]}}','2026-07-23 13:47:45',1),
(14,1,1,687,'holon_created','Creation de [holon|707|Lien pilotage (rôle)].','{\"IDholon\":707,\"after\":{\"holon\":{\"id\":707,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":687,\"templateId\":680,\"inheritsFromName\":\"Lien pilotage\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\\\"]\",\"visibleValue\":\"[\\\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\\\"]\",\"visibleItems\":[\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleItems\":[\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\"]}},\"permissions\":[]}}','2026-07-23 13:47:54',1),
(15,1,1,686,'holon_member_added','[user|1|Admin] a été ajouté au [holon|693|rôle Comptabilite et budget] par [user|1|Admin].','{\"IDtargetuser\":1,\"IDholon\":693,\"authorUserId\":1}','2026-07-23 13:48:17',1),
(16,1,1,686,'holon_member_added','[user|1|Admin] a été ajouté au [holon|692|rôle Gestion administrative] par [user|1|Admin].','{\"IDtargetuser\":1,\"IDholon\":692,\"authorUserId\":1}','2026-07-23 13:49:46',1),
(17,1,1,678,'holon_member_added','[user|1|Admin] a été ajouté au [holon|682|rôle Memoire] par [user|1|Admin].','{\"IDtargetuser\":1,\"IDholon\":682,\"authorUserId\":1}','2026-07-23 13:50:25',1),
(18,1,1,678,'holon_member_removed','[user|1|Admin] a ete retire du [holon|683|rôle Operations] par [user|1|Admin].','{\"IDtargetuser\":1,\"IDholon\":683,\"authorUserId\":1,\"removedHolonIds\":[683],\"membershipUpdated\":false}','2026-07-23 13:50:34',1),
(19,1,1,678,'holon_created','Creation de [holon|708|Inclusion (rôle)].','{\"IDholon\":708,\"after\":{\"holon\":{\"id\":708,\"name\":\"Inclusion\",\"fullName\":\"Inclusion\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Accueillir les nouveaux arrivants au sein de l\'organisation\",\"inheritedValue\":\"\",\"visibleValue\":\"Accueillir les nouveaux arrivants au sein de l\'organisation\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]}},\"permissions\":[]}}','2026-07-23 13:51:27',1),
(20,1,1,678,'holon_member_added','[user|1|Admin] a été ajouté au [holon|708|rôle Inclusion] par [user|1|Admin].','{\"IDtargetuser\":1,\"IDholon\":708,\"authorUserId\":1}','2026-07-23 13:51:33',1),
(21,1,1,NULL,'holon_updated','Modification de [holon|678|Ancrage (cercle)] :\n- la propriete [property|46|Strategie] a ete modifiee : {\"before\":\"<p><b>Cap à tenir:</b></p><p>Trouver notre place et professionnaliser nos services</p><p><b>Objectifs prioritaires:</b></p>\",\"items\":[8,7,9],\"after\":\"<p><b>Privilég[...].','{\"IDholon\":678,\"before\":{\"holon\":{\"id\":678,\"name\":\"Ancrage\",\"fullName\":\"\",\"typeId\":2,\"typeLabel\":\"Cercle\",\"parentId\":674,\"templateId\":676,\"inheritsFromName\":\"Cercle\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Tenir ensemble la coherence globale de l\'organisation, ses priorites structurelles et la capacite des sous-cercles a agir dans un cadre commun.\",\"inheritedValue\":\"\",\"visibleValue\":\"Tenir ensemble la coherence globale de l\'organisation, ses priorites structurelles et la capacite des sous-cercles a agir dans un cadre commun.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Cadre de gouvernance courante, roles structurels transversaux, arbitrages inter-cercles et supervision de la capacite d\'execution globale.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Cadre de gouvernance courante, roles structurels transversaux, arbitrages inter-cercles et supervision de la capacite d\'execution globale.\\\"]\",\"visibleItems\":[\"Cadre de gouvernance courante, roles structurels transversaux, arbitrages inter-cercles et supervision de la capacite d\'execution globale.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Porter les roles structurels de base, arbitrer les tensions de coordination, faire vivre les sous-cercles et assurer une articulation claire avec le CA.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Porter les roles structurels de base, arbitrer les tensions de coordination, faire vivre les sous-cercles et assurer une articulation claire avec le CA.\\\"]\",\"visibleItems\":[\"Porter les roles structurels de base, arbitrer les tensions de coordination, faire vivre les sous-cercles et assurer une articulation claire avec le CA.\"]},\"46\":{\"id\":46,\"name\":\"Strategie\",\"shortname\":\"strategie\",\"formatId\":7,\"formatName\":\"HTML et liste\",\"listItemType\":\"project\",\"localValue\":\"Structurer progressivement l\'organisation autour de cercles specialises capables d\'apprendre sans perdre leur alignement global.\",\"inheritedValue\":\"\",\"visibleValue\":\"{\\\"before\\\":\\\"\\\",\\\"items\\\":[],\\\"after\\\":\\\"\\\"}\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":678,\"name\":\"Ancrage\",\"fullName\":\"\",\"typeId\":2,\"typeLabel\":\"Cercle\",\"parentId\":674,\"templateId\":676,\"inheritsFromName\":\"Cercle\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Tenir ensemble la coherence globale de l\'organisation, ses priorites structurelles et la capacite des sous-cercles a agir dans un cadre commun.\",\"inheritedValue\":\"\",\"visibleValue\":\"Tenir ensemble la coherence globale de l\'organisation, ses priorites structurelles et la capacite des sous-cercles a agir dans un cadre commun.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Cadre de gouvernance courante, roles structurels transversaux, arbitrages inter-cercles et supervision de la capacite d\'execution globale.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Cadre de gouvernance courante, roles structurels transversaux, arbitrages inter-cercles et supervision de la capacite d\'execution globale.\\\"]\",\"visibleItems\":[\"Cadre de gouvernance courante, roles structurels transversaux, arbitrages inter-cercles et supervision de la capacite d\'execution globale.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Porter les roles structurels de base, arbitrer les tensions de coordination, faire vivre les sous-cercles et assurer une articulation claire avec le CA.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Porter les roles structurels de base, arbitrer les tensions de coordination, faire vivre les sous-cercles et assurer une articulation claire avec le CA.\\\"]\",\"visibleItems\":[\"Porter les roles structurels de base, arbitrer les tensions de coordination, faire vivre les sous-cercles et assurer une articulation claire avec le CA.\"]},\"46\":{\"id\":46,\"name\":\"Strategie\",\"shortname\":\"strategie\",\"formatId\":7,\"formatName\":\"HTML et liste\",\"listItemType\":\"project\",\"localValue\":\"{\\\"before\\\":\\\"<p><b>Cap à tenir:</b></p><p>Trouver notre place et professionnaliser nos services</p><p><b>Objectifs prioritaires:</b></p>\\\",\\\"items\\\":[8,7,9],\\\"after\\\":\\\"<p><b>Privilégier:</b></p><ul><li>Les événements locaux sur la dispersion géographique</li></ul>\\\"}\",\"inheritedValue\":\"\",\"visibleValue\":\"{\\\"before\\\":\\\"<p><b>Cap à tenir:</b></p><p>Trouver notre place et professionnaliser nos services</p><p><b>Objectifs prioritaires:</b></p>\\\",\\\"items\\\":[8,7,9],\\\"after\\\":\\\"<p><b>Privilégier:</b></p><ul><li>Les événements locaux sur la dispersion géographique</li></ul>\\\"}\",\"visibleItems\":[]}},\"permissions\":[]},\"changes\":[{\"type\":\"property_value_changed\",\"propertyId\":46,\"before\":{\"id\":46,\"name\":\"Strategie\",\"shortname\":\"strategie\",\"formatId\":7,\"formatName\":\"HTML et liste\",\"listItemType\":\"project\",\"localValue\":\"Structurer progressivement l\'organisation autour de cercles specialises capables d\'apprendre sans perdre leur alignement global.\",\"inheritedValue\":\"\",\"visibleValue\":\"{\\\"before\\\":\\\"\\\",\\\"items\\\":[],\\\"after\\\":\\\"\\\"}\",\"visibleItems\":[]},\"after\":{\"id\":46,\"name\":\"Strategie\",\"shortname\":\"strategie\",\"formatId\":7,\"formatName\":\"HTML et liste\",\"listItemType\":\"project\",\"localValue\":\"{\\\"before\\\":\\\"<p><b>Cap à tenir:</b></p><p>Trouver notre place et professionnaliser nos services</p><p><b>Objectifs prioritaires:</b></p>\\\",\\\"items\\\":[8,7,9],\\\"after\\\":\\\"<p><b>Privilégier:</b></p><ul><li>Les événements locaux sur la dispersion géographique</li></ul>\\\"}\",\"inheritedValue\":\"\",\"visibleValue\":\"{\\\"before\\\":\\\"<p><b>Cap à tenir:</b></p><p>Trouver notre place et professionnaliser nos services</p><p><b>Objectifs prioritaires:</b></p>\\\",\\\"items\\\":[8,7,9],\\\"after\\\":\\\"<p><b>Privilégier:</b></p><ul><li>Les événements locaux sur la dispersion géographique</li></ul>\\\"}\",\"visibleItems\":[]}}]}','2026-07-24 05:25:49',1),
(22,1,1,678,'holon_updated','Modification de [holon|687|Marketing (cercle)] :\n- la propriete [property|46|Strategie] a ete modifiee : {\"before\":\"\",\"items\":[16,12],\"after\":\"\"}.','{\"IDholon\":687,\"before\":{\"holon\":{\"id\":687,\"name\":\"Marketing\",\"fullName\":\"\",\"typeId\":2,\"typeLabel\":\"Cercle\",\"parentId\":678,\"templateId\":676,\"inheritsFromName\":\"Cercle\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Renforcer la visibilite de l\'organisation et la qualite de sa presence publique afin d\'attirer les bonnes relations, les bonnes opportunites et une meilleure lisibilite de sa proposition.\",\"inheritedValue\":\"\",\"visibleValue\":\"Renforcer la visibilite de l\'organisation et la qualite de sa presence publique afin d\'attirer les bonnes relations, les bonnes opportunites et une meilleure lisibilite de sa proposition.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Positionnement public, canaux de communication, campagnes de visibilite, relations de partenariat, calendrier editorial et suivi des retombees.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Positionnement public, canaux de communication, campagnes de visibilite, relations de partenariat, calendrier editorial et suivi des retombees.\\\"]\",\"visibleItems\":[\"Positionnement public, canaux de communication, campagnes de visibilite, relations de partenariat, calendrier editorial et suivi des retombees.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"Animer une presence coherente, produire des messages utiles, soutenir les campagnes prioritaires et faire circuler les retours du terrain vers le reste de l\'organisation.\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Animer une presence coherente, produire des messages utiles, soutenir les campagnes prioritaires et faire circuler les retours du terrain vers le reste de l\'organisation.\\\"]\",\"visibleItems\":[\"Animer une presence coherente, produire des messages utiles, soutenir les campagnes prioritaires et faire circuler les retours du terrain vers le reste de l\'organisation.\"]},\"46\":{\"id\":46,\"name\":\"Strategie\",\"shortname\":\"strategie\",\"formatId\":7,\"formatName\":\"HTML et liste\",\"listItemType\":\"project\",\"localValue\":\"Construire un dispositif de communication progressif, ancre dans les besoins reels du terrain et dans la capacite de production du cercle.\",\"inheritedValue\":\"\",\"visibleValue\":\"{\\\"before\\\":\\\"\\\",\\\"items\\\":[],\\\"after\\\":\\\"\\\"}\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":687,\"name\":\"Marketing\",\"fullName\":\"\",\"typeId\":2,\"typeLabel\":\"Cercle\",\"parentId\":678,\"templateId\":676,\"inheritsFromName\":\"Cercle\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Renforcer la visibilite de l\'organisation et la qualite de sa presence publique afin d\'attirer les bonnes relations, les bonnes opportunites et une meilleure lisibilite de sa proposition.\",\"inheritedValue\":\"\",\"visibleValue\":\"Renforcer la visibilite de l\'organisation et la qualite de sa presence publique afin d\'attirer les bonnes relations, les bonnes opportunites et une meilleure lisibilite de sa proposition.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Positionnement public, canaux de communication, campagnes de visibilite, relations de partenariat, calendrier editorial et suivi des retombees.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Positionnement public, canaux de communication, campagnes de visibilite, relations de partenariat, calendrier editorial et suivi des retombees.\\\"]\",\"visibleItems\":[\"Positionnement public, canaux de communication, campagnes de visibilite, relations de partenariat, calendrier editorial et suivi des retombees.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Animer une presence coherente, produire des messages utiles, soutenir les campagnes prioritaires et faire circuler les retours du terrain vers le reste de l\'organisation.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Animer une presence coherente, produire des messages utiles, soutenir les campagnes prioritaires et faire circuler les retours du terrain vers le reste de l\'organisation.\\\"]\",\"visibleItems\":[\"Animer une presence coherente, produire des messages utiles, soutenir les campagnes prioritaires et faire circuler les retours du terrain vers le reste de l\'organisation.\"]},\"46\":{\"id\":46,\"name\":\"Strategie\",\"shortname\":\"strategie\",\"formatId\":7,\"formatName\":\"HTML et liste\",\"listItemType\":\"project\",\"localValue\":\"{\\\"before\\\":\\\"\\\",\\\"items\\\":[16,12],\\\"after\\\":\\\"\\\"}\",\"inheritedValue\":\"\",\"visibleValue\":\"{\\\"before\\\":\\\"\\\",\\\"items\\\":[16,12],\\\"after\\\":\\\"\\\"}\",\"visibleItems\":[]}},\"permissions\":[]},\"changes\":[{\"type\":\"property_value_changed\",\"propertyId\":46,\"before\":{\"id\":46,\"name\":\"Strategie\",\"shortname\":\"strategie\",\"formatId\":7,\"formatName\":\"HTML et liste\",\"listItemType\":\"project\",\"localValue\":\"Construire un dispositif de communication progressif, ancre dans les besoins reels du terrain et dans la capacite de production du cercle.\",\"inheritedValue\":\"\",\"visibleValue\":\"{\\\"before\\\":\\\"\\\",\\\"items\\\":[],\\\"after\\\":\\\"\\\"}\",\"visibleItems\":[]},\"after\":{\"id\":46,\"name\":\"Strategie\",\"shortname\":\"strategie\",\"formatId\":7,\"formatName\":\"HTML et liste\",\"listItemType\":\"project\",\"localValue\":\"{\\\"before\\\":\\\"\\\",\\\"items\\\":[16,12],\\\"after\\\":\\\"\\\"}\",\"inheritedValue\":\"\",\"visibleValue\":\"{\\\"before\\\":\\\"\\\",\\\"items\\\":[16,12],\\\"after\\\":\\\"\\\"}\",\"visibleItems\":[]}}]}','2026-07-24 05:42:21',1),
(23,2,1,712,'holon_updated','Modification de [holon|714|Lien pilotage (rôle)] :\n- le parametre \"obligatoire\" a ete active.\n- le parametre \"nom verrouille\" a ete active.\n- le parametre \"unique\" a ete active.\n- le parametre \"lien\" a ete active.','{\"IDholon\":714,\"before\":{\"holon\":{\"id\":714,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":0,\"inheritsFromName\":\"\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"rde\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.\",\"inheritedValue\":\"\",\"visibleValue\":\"S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":714,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":0,\"inheritsFromName\":\"\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":true},\"properties\":{\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"rde\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.\",\"inheritedValue\":\"\",\"visibleValue\":\"S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.\",\"visibleItems\":[]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"mandatory\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"lockedName\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"unique\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"link\",\"before\":false,\"after\":true}]}','2026-07-24 14:52:56',1),
(24,2,1,712,'holon_updated','Modification de [holon|715|Mémoire (rôle)] :\n- le parametre \"obligatoire\" a ete active.\n- le parametre \"nom verrouille\" a ete active.\n- le parametre \"unique\" a ete active.','{\"IDholon\":715,\"before\":{\"holon\":{\"id\":715,\"name\":\"Mémoire\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":0,\"inheritsFromName\":\"\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"inheritedValue\":\"\",\"visibleValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":715,\"name\":\"Mémoire\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":0,\"inheritsFromName\":\"\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":false},\"properties\":{\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"inheritedValue\":\"\",\"visibleValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"visibleItems\":[]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"mandatory\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"lockedName\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"unique\",\"before\":false,\"after\":true}]}','2026-07-24 14:53:09',1),
(25,2,1,712,'holon_updated','Modification de [holon|713|Facilitation (rôle)] :\n- le parametre \"obligatoire\" a ete active.\n- le parametre \"nom verrouille\" a ete active.\n- le parametre \"unique\" a ete active.','{\"IDholon\":713,\"before\":{\"holon\":{\"id\":713,\"name\":\"Facilitation\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":710,\"inheritsFromName\":\"Rôle\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"48\":{\"id\":48,\"name\":\"Attendus\",\"shortname\":\"redevability\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"fdsfsdfdsfds\",\"inheritedValue\":\"\",\"visibleValue\":\"fdsfsdfdsfds\",\"visibleItems\":[]},\"49\":{\"id\":49,\"name\":\"Domaines d\'autorité\",\"shortname\":\"domain\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Domaine du rôle facilitation\",\"inheritedValue\":\"\",\"visibleValue\":\"Domaine du rôle facilitation\",\"visibleItems\":[]},\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.\",\"inheritedValue\":\"\",\"visibleValue\":\"Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":713,\"name\":\"Facilitation\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":710,\"inheritsFromName\":\"Rôle\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":false},\"properties\":{\"48\":{\"id\":48,\"name\":\"Attendus\",\"shortname\":\"redevability\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"fdsfsdfdsfds\",\"inheritedValue\":\"\",\"visibleValue\":\"fdsfsdfdsfds\",\"visibleItems\":[]},\"49\":{\"id\":49,\"name\":\"Domaines d\'autorité\",\"shortname\":\"domain\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Domaine du rôle facilitation\",\"inheritedValue\":\"\",\"visibleValue\":\"Domaine du rôle facilitation\",\"visibleItems\":[]},\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.\",\"inheritedValue\":\"\",\"visibleValue\":\"Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.\",\"visibleItems\":[]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"mandatory\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"lockedName\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"unique\",\"before\":false,\"after\":true}]}','2026-07-24 14:53:22',1),
(26,2,1,NULL,'holon_updated','Modification de [holon|710|Rôle (rôle)] :\n- la propriete [property|51|Raison d\'être] a ete ajoutee.','{\"IDholon\":710,\"before\":{\"holon\":{\"id\":710,\"name\":\"Rôle\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":709,\"templateId\":0,\"inheritsFromName\":\"\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":false,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":[],\"permissions\":[]},\"after\":{\"holon\":{\"id\":710,\"name\":\"Rôle\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":709,\"templateId\":0,\"inheritsFromName\":\"\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":false,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"51\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]}},\"permissions\":[]},\"changes\":[{\"type\":\"property_added\",\"propertyId\":51,\"after\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]}}]}','2026-07-24 14:54:54',1),
(27,2,1,712,'holon_updated','Modification de [holon|714|Lien pilotage (rôle)] :\n- le modele parent a ete defini sur \"Rôle\".\n- la propriete [property|51|Raison d\'être] a ete ajoutee.','{\"IDholon\":714,\"before\":{\"holon\":{\"id\":714,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":0,\"inheritsFromName\":\"\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":true},\"properties\":{\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.\",\"inheritedValue\":\"\",\"visibleValue\":\"S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":714,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":710,\"inheritsFromName\":\"Rôle\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":true},\"properties\":{\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.\",\"inheritedValue\":\"\",\"visibleValue\":\"S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.\",\"visibleItems\":[]},\"51\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"inheritsFromName\",\"before\":\"\",\"after\":\"Rôle\"},{\"type\":\"property_added\",\"propertyId\":51,\"after\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]}}]}','2026-07-24 14:56:02',1),
(28,2,1,712,'holon_updated','Modification de [holon|715|Mémoire (rôle)] :\n- le modele parent a ete defini sur \"Rôle\".\n- la propriete [property|51|Raison d\'être] a ete ajoutee.','{\"IDholon\":715,\"before\":{\"holon\":{\"id\":715,\"name\":\"Mémoire\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":0,\"inheritsFromName\":\"\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":false},\"properties\":{\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"inheritedValue\":\"\",\"visibleValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":715,\"name\":\"Mémoire\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":710,\"inheritsFromName\":\"Rôle\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":false},\"properties\":{\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"inheritedValue\":\"\",\"visibleValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"visibleItems\":[]},\"51\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"inheritedValue\":\"\",\"visibleValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"visibleItems\":[]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"inheritsFromName\",\"before\":\"\",\"after\":\"Rôle\"},{\"type\":\"property_added\",\"propertyId\":51,\"after\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"inheritedValue\":\"\",\"visibleValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"visibleItems\":[]}}]}','2026-07-24 14:56:43',1),
(29,2,1,712,'holon_created','Creation de [holon|722|Facilitation (rôle)].','{\"IDholon\":722,\"after\":{\"holon\":{\"id\":722,\"name\":\"Facilitation\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":713,\"inheritsFromName\":\"Facilitation\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"48\":{\"id\":48,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"fdsfsdfdsfds\",\"visibleValue\":\"fdsfsdfdsfds\",\"visibleItems\":[]},\"51\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"49\":{\"id\":49,\"name\":\"Domaines d\'autorité\",\"shortname\":\"domaines_d_autorite\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"Domaine du rôle facilitation\",\"visibleValue\":\"Domaine du rôle facilitation\",\"visibleItems\":[]},\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.\",\"visibleValue\":\"Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.\",\"visibleItems\":[]}},\"permissions\":[]}}','2026-07-24 14:57:01',1),
(30,2,1,712,'holon_updated','Modification de [holon|714|Lien pilotage (rôle)] :\n- la couleur a ete modifiee.','{\"IDholon\":714,\"before\":{\"holon\":{\"id\":714,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":710,\"inheritsFromName\":\"Rôle\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":true},\"properties\":{\"51\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":714,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":710,\"inheritsFromName\":\"Rôle\",\"color\":\"#f52d0a\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":true},\"properties\":{\"51\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"color\",\"before\":\"\",\"after\":\"#f52d0a\"}]}','2026-07-24 15:06:49',1),
(31,2,1,712,'holon_updated','Modification de [holon|715|Mémoire (rôle)] :\n- la couleur a ete modifiee.','{\"IDholon\":715,\"before\":{\"holon\":{\"id\":715,\"name\":\"Mémoire\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":710,\"inheritsFromName\":\"Rôle\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":false},\"properties\":{\"51\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"inheritedValue\":\"\",\"visibleValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":715,\"name\":\"Mémoire\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":710,\"inheritsFromName\":\"Rôle\",\"color\":\"#f5740a\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":false},\"properties\":{\"51\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"inheritedValue\":\"\",\"visibleValue\":\"S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.\",\"visibleItems\":[]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"color\",\"before\":\"\",\"after\":\"#f5740a\"}]}','2026-07-24 15:07:03',1),
(32,2,1,712,'holon_updated','Modification de [holon|713|Facilitation (rôle)] :\n- la couleur a ete modifiee.','{\"IDholon\":713,\"before\":{\"holon\":{\"id\":713,\"name\":\"Facilitation\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":710,\"inheritsFromName\":\"Rôle\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":false},\"properties\":{\"48\":{\"id\":48,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"fdsfsdfdsfds\",\"inheritedValue\":\"\",\"visibleValue\":\"fdsfsdfdsfds\",\"visibleItems\":[]},\"51\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"49\":{\"id\":49,\"name\":\"Domaines d\'autorité\",\"shortname\":\"domaines_d_autorite\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Domaine du rôle facilitation\",\"inheritedValue\":\"\",\"visibleValue\":\"Domaine du rôle facilitation\",\"visibleItems\":[]},\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.\",\"inheritedValue\":\"\",\"visibleValue\":\"Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":713,\"name\":\"Facilitation\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":710,\"inheritsFromName\":\"Rôle\",\"color\":\"#f59f0a\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":false},\"properties\":{\"48\":{\"id\":48,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"fdsfsdfdsfds\",\"inheritedValue\":\"\",\"visibleValue\":\"fdsfsdfdsfds\",\"visibleItems\":[]},\"49\":{\"id\":49,\"name\":\"Domaines d\'autorité\",\"shortname\":\"domaines_d_autorite\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Domaine du rôle facilitation\",\"inheritedValue\":\"\",\"visibleValue\":\"Domaine du rôle facilitation\",\"visibleItems\":[]},\"51\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.\",\"inheritedValue\":\"\",\"visibleValue\":\"Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.\",\"visibleItems\":[]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"color\",\"before\":\"\",\"after\":\"#f59f0a\"}]}','2026-07-24 15:07:15',1),
(33,2,1,NULL,'holon_updated','Modification de [holon|710|Rôle (rôle)] :\n- le droit \"Ajouter un membre\" a ete ajoute : Element courant.\n- le droit \"Creer des prises de decision\" a ete ajoute : Element courant.\n- le droit \"Creer des fichiers\" a ete ajoute : Element courant.\n- le droit \"Creer des dates\" a ete ajoute : Element courant.\n- le droit \"Creer des FAQ\" a ete ajoute : Element courant.\n- le droit \"Creer des indicateurs\" a ete ajoute : Element courant.\n- le droit \"Creer des projets\" a ete ajoute : Element courant.','{\"IDholon\":710,\"before\":{\"holon\":{\"id\":710,\"name\":\"Rôle\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":709,\"templateId\":0,\"inheritsFromName\":\"\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":false,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"51\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":710,\"name\":\"Rôle\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":709,\"templateId\":0,\"inheritsFromName\":\"\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":false,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false},\"properties\":{\"51\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]}},\"permissions\":{\"CAN_ADD_MEMBER\":{\"id\":1,\"key\":\"CAN_ADD_MEMBER\",\"name\":\"Ajouter un membre\",\"shortname\":\"CAN_ADD_MEMBER\",\"description\":\"Autorise l ajout d un membre dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\"},\"CAN_CREATE_DECISION\":{\"id\":4,\"key\":\"CAN_CREATE_DECISION\",\"name\":\"Creer des prises de decision\",\"shortname\":\"CAN_CREATE_DECISION\",\"description\":\"Autorise la creation de prises de decision dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\"},\"CAN_CREATE_DOCUMENT\":{\"id\":3,\"key\":\"CAN_CREATE_DOCUMENT\",\"name\":\"Creer des fichiers\",\"shortname\":\"CAN_CREATE_DOCUMENT\",\"description\":\"Autorise la creation de fichiers dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\"},\"CAN_CREATE_EVENT\":{\"id\":5,\"key\":\"CAN_CREATE_EVENT\",\"name\":\"Creer des dates\",\"shortname\":\"CAN_CREATE_EVENT\",\"description\":\"Autorise la creation de dates dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\"},\"CAN_CREATE_FAQ\":{\"id\":8,\"key\":\"CAN_CREATE_FAQ\",\"name\":\"Creer des FAQ\",\"shortname\":\"CAN_CREATE_FAQ\",\"description\":\"Autorise la creation de FAQ dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\"},\"CAN_CREATE_INDICATOR\":{\"id\":10,\"key\":\"CAN_CREATE_INDICATOR\",\"name\":\"Creer des indicateurs\",\"shortname\":\"CAN_CREATE_INDICATOR\",\"description\":\"Autorise la creation d indicateurs dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\"},\"CAN_CREATE_PROJECT\":{\"id\":9,\"key\":\"CAN_CREATE_PROJECT\",\"name\":\"Creer des projets\",\"shortname\":\"CAN_CREATE_PROJECT\",\"description\":\"Autorise la creation de projets dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\"}}},\"changes\":[{\"type\":\"permission_added\",\"permissionKey\":\"CAN_ADD_MEMBER\",\"after\":{\"id\":1,\"key\":\"CAN_ADD_MEMBER\",\"name\":\"Ajouter un membre\",\"shortname\":\"CAN_ADD_MEMBER\",\"description\":\"Autorise l ajout d un membre dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\"}},{\"type\":\"permission_added\",\"permissionKey\":\"CAN_CREATE_DECISION\",\"after\":{\"id\":4,\"key\":\"CAN_CREATE_DECISION\",\"name\":\"Creer des prises de decision\",\"shortname\":\"CAN_CREATE_DECISION\",\"description\":\"Autorise la creation de prises de decision dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\"}},{\"type\":\"permission_added\",\"permissionKey\":\"CAN_CREATE_DOCUMENT\",\"after\":{\"id\":3,\"key\":\"CAN_CREATE_DOCUMENT\",\"name\":\"Creer des fichiers\",\"shortname\":\"CAN_CREATE_DOCUMENT\",\"description\":\"Autorise la creation de fichiers dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\"}},{\"type\":\"permission_added\",\"permissionKey\":\"CAN_CREATE_EVENT\",\"after\":{\"id\":5,\"key\":\"CAN_CREATE_EVENT\",\"name\":\"Creer des dates\",\"shortname\":\"CAN_CREATE_EVENT\",\"description\":\"Autorise la creation de dates dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\"}},{\"type\":\"permission_added\",\"permissionKey\":\"CAN_CREATE_FAQ\",\"after\":{\"id\":8,\"key\":\"CAN_CREATE_FAQ\",\"name\":\"Creer des FAQ\",\"shortname\":\"CAN_CREATE_FAQ\",\"description\":\"Autorise la creation de FAQ dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\"}},{\"type\":\"permission_added\",\"permissionKey\":\"CAN_CREATE_INDICATOR\",\"after\":{\"id\":10,\"key\":\"CAN_CREATE_INDICATOR\",\"name\":\"Creer des indicateurs\",\"shortname\":\"CAN_CREATE_INDICATOR\",\"description\":\"Autorise la creation d indicateurs dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\"}},{\"type\":\"permission_added\",\"permissionKey\":\"CAN_CREATE_PROJECT\",\"after\":{\"id\":9,\"key\":\"CAN_CREATE_PROJECT\",\"name\":\"Creer des projets\",\"shortname\":\"CAN_CREATE_PROJECT\",\"description\":\"Autorise la creation de projets dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\"}}]}','2026-07-25 10:29:21',1),
(34,1,1,NULL,'holon_updated','Modification de [holon|675|Role (rôle)] :\n- le parametre \"minimum d admins verrouille\" a ete active.\n- le parametre \"maximum d admins verrouille\" a ete active.\n- le parametre \"nombre minimum d admins\" a ete modifie.\n- le parametre \"nombre maximum d admins\" a ete modifie.\n- le droit \"Admin - Ajouter des proprietes de holons\" a ete ajoute : Element courant.\n- le droit \"Admin - Ajouter un membre\" a ete ajoute : Element courant.\n- le droit \"Admin - Creer des prises de decision\" a ete ajoute : Element courant.\n- le droit \"Admin - Creer des indicateurs\" a ete ajoute : Element courant.\n- le droit \"Admin - Supprimer des dates\" a ete ajoute : Element courant.\n- le droit \"Admin - Supprimer les proprietes de holons\" a ete ajoute : Element courant.\n- le droit \"Admin - Modifier les proprietes de holons\" a ete ajoute : Element courant.\n- le droit \"Membre - Devenir secretaire de PV\" a ete ajoute : Element courant.\n- le droit \"Membre - Creer des fichiers\" a ete ajoute : Element courant.\n- le droit \"Membre - Creer des dates\" a ete ajoute : Element courant.\n- le droit \"Membre - Creer des FAQ\" a ete ajoute : Element courant.\n- le droit \"Membre - Creer des projets\" a ete ajoute : Element courant.','{\"IDholon\":675,\"before\":{\"holon\":{\"id\":675,\"name\":\"Role\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":674,\"templateId\":0,\"inheritsFromName\":\"\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":false,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false,\"adminParent\":false,\"adminMin\":0,\"adminMax\":null,\"lockedAdminMin\":false,\"lockedAdminMax\":false,\"adminMinOverride\":false,\"adminMaxOverride\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":675,\"name\":\"Role\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":674,\"templateId\":0,\"inheritsFromName\":\"\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":false,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false,\"adminParent\":false,\"adminMin\":1,\"adminMax\":1,\"lockedAdminMin\":true,\"lockedAdminMax\":true,\"adminMinOverride\":false,\"adminMaxOverride\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]}},\"permissions\":{\"admin:CAN_ADD_HOLON_PROPERTIES\":{\"id\":16,\"key\":\"CAN_ADD_HOLON_PROPERTIES\",\"name\":\"Admin - Ajouter des proprietes de holons\",\"shortname\":\"CAN_ADD_HOLON_PROPERTIES\",\"description\":\"Autorise l ajout de proprietes directement sur un holon dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"admin\"},\"admin:CAN_ADD_MEMBER\":{\"id\":1,\"key\":\"CAN_ADD_MEMBER\",\"name\":\"Admin - Ajouter un membre\",\"shortname\":\"CAN_ADD_MEMBER\",\"description\":\"Autorise l ajout d un membre dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"admin\"},\"admin:CAN_CREATE_DECISION\":{\"id\":4,\"key\":\"CAN_CREATE_DECISION\",\"name\":\"Admin - Creer des prises de decision\",\"shortname\":\"CAN_CREATE_DECISION\",\"description\":\"Autorise la creation de prises de decision dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"admin\"},\"admin:CAN_CREATE_INDICATOR\":{\"id\":10,\"key\":\"CAN_CREATE_INDICATOR\",\"name\":\"Admin - Creer des indicateurs\",\"shortname\":\"CAN_CREATE_INDICATOR\",\"description\":\"Autorise la creation d indicateurs dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"admin\"},\"admin:CAN_DELETE_EVENT\":{\"id\":6,\"key\":\"CAN_DELETE_EVENT\",\"name\":\"Admin - Supprimer des dates\",\"shortname\":\"CAN_DELETE_EVENT\",\"description\":\"Autorise la suppression de dates dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"admin\"},\"admin:CAN_DELETE_HOLON_PROPERTIES\":{\"id\":17,\"key\":\"CAN_DELETE_HOLON_PROPERTIES\",\"name\":\"Admin - Supprimer les proprietes de holons\",\"shortname\":\"CAN_DELETE_HOLON_PROPERTIES\",\"description\":\"Autorise le retrait des proprietes ajoutees directement a un holon dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"admin\"},\"admin:CAN_EDIT_HOLON_PROPERTIES\":{\"id\":15,\"key\":\"CAN_EDIT_HOLON_PROPERTIES\",\"name\":\"Admin - Modifier les proprietes de holons\",\"shortname\":\"CAN_EDIT_HOLON_PROPERTIES\",\"description\":\"Autorise la modification des proprietes ajoutees directement a un holon dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"admin\"},\"member:CAN_CLAIM_PV\":{\"id\":7,\"key\":\"CAN_CLAIM_PV\",\"name\":\"Membre - Devenir secretaire de PV\",\"shortname\":\"CAN_CLAIM_PV\",\"description\":\"Autorise a prendre le role de secretaire pendant une reunion associee a un PV.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"member\"},\"member:CAN_CREATE_DOCUMENT\":{\"id\":3,\"key\":\"CAN_CREATE_DOCUMENT\",\"name\":\"Membre - Creer des fichiers\",\"shortname\":\"CAN_CREATE_DOCUMENT\",\"description\":\"Autorise la creation de fichiers dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"member\"},\"member:CAN_CREATE_EVENT\":{\"id\":5,\"key\":\"CAN_CREATE_EVENT\",\"name\":\"Membre - Creer des dates\",\"shortname\":\"CAN_CREATE_EVENT\",\"description\":\"Autorise la creation de dates dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"member\"},\"member:CAN_CREATE_FAQ\":{\"id\":8,\"key\":\"CAN_CREATE_FAQ\",\"name\":\"Membre - Creer des FAQ\",\"shortname\":\"CAN_CREATE_FAQ\",\"description\":\"Autorise la creation de FAQ dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"member\"},\"member:CAN_CREATE_PROJECT\":{\"id\":9,\"key\":\"CAN_CREATE_PROJECT\",\"name\":\"Membre - Creer des projets\",\"shortname\":\"CAN_CREATE_PROJECT\",\"description\":\"Autorise la creation de projets dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"member\"}}},\"changes\":[{\"type\":\"field_changed\",\"field\":\"lockedAdminMin\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"lockedAdminMax\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"adminMin\",\"before\":0,\"after\":1},{\"type\":\"field_changed\",\"field\":\"adminMax\",\"before\":null,\"after\":1},{\"type\":\"permission_added\",\"permissionKey\":\"admin:CAN_ADD_HOLON_PROPERTIES\",\"after\":{\"id\":16,\"key\":\"CAN_ADD_HOLON_PROPERTIES\",\"name\":\"Admin - Ajouter des proprietes de holons\",\"shortname\":\"CAN_ADD_HOLON_PROPERTIES\",\"description\":\"Autorise l ajout de proprietes directement sur un holon dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"admin\"}},{\"type\":\"permission_added\",\"permissionKey\":\"admin:CAN_ADD_MEMBER\",\"after\":{\"id\":1,\"key\":\"CAN_ADD_MEMBER\",\"name\":\"Admin - Ajouter un membre\",\"shortname\":\"CAN_ADD_MEMBER\",\"description\":\"Autorise l ajout d un membre dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"admin\"}},{\"type\":\"permission_added\",\"permissionKey\":\"admin:CAN_CREATE_DECISION\",\"after\":{\"id\":4,\"key\":\"CAN_CREATE_DECISION\",\"name\":\"Admin - Creer des prises de decision\",\"shortname\":\"CAN_CREATE_DECISION\",\"description\":\"Autorise la creation de prises de decision dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"admin\"}},{\"type\":\"permission_added\",\"permissionKey\":\"admin:CAN_CREATE_INDICATOR\",\"after\":{\"id\":10,\"key\":\"CAN_CREATE_INDICATOR\",\"name\":\"Admin - Creer des indicateurs\",\"shortname\":\"CAN_CREATE_INDICATOR\",\"description\":\"Autorise la creation d indicateurs dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"admin\"}},{\"type\":\"permission_added\",\"permissionKey\":\"admin:CAN_DELETE_EVENT\",\"after\":{\"id\":6,\"key\":\"CAN_DELETE_EVENT\",\"name\":\"Admin - Supprimer des dates\",\"shortname\":\"CAN_DELETE_EVENT\",\"description\":\"Autorise la suppression de dates dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"admin\"}},{\"type\":\"permission_added\",\"permissionKey\":\"admin:CAN_DELETE_HOLON_PROPERTIES\",\"after\":{\"id\":17,\"key\":\"CAN_DELETE_HOLON_PROPERTIES\",\"name\":\"Admin - Supprimer les proprietes de holons\",\"shortname\":\"CAN_DELETE_HOLON_PROPERTIES\",\"description\":\"Autorise le retrait des proprietes ajoutees directement a un holon dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"admin\"}},{\"type\":\"permission_added\",\"permissionKey\":\"admin:CAN_EDIT_HOLON_PROPERTIES\",\"after\":{\"id\":15,\"key\":\"CAN_EDIT_HOLON_PROPERTIES\",\"name\":\"Admin - Modifier les proprietes de holons\",\"shortname\":\"CAN_EDIT_HOLON_PROPERTIES\",\"description\":\"Autorise la modification des proprietes ajoutees directement a un holon dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"admin\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CLAIM_PV\",\"after\":{\"id\":7,\"key\":\"CAN_CLAIM_PV\",\"name\":\"Membre - Devenir secretaire de PV\",\"shortname\":\"CAN_CLAIM_PV\",\"description\":\"Autorise a prendre le role de secretaire pendant une reunion associee a un PV.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CREATE_DOCUMENT\",\"after\":{\"id\":3,\"key\":\"CAN_CREATE_DOCUMENT\",\"name\":\"Membre - Creer des fichiers\",\"shortname\":\"CAN_CREATE_DOCUMENT\",\"description\":\"Autorise la creation de fichiers dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CREATE_EVENT\",\"after\":{\"id\":5,\"key\":\"CAN_CREATE_EVENT\",\"name\":\"Membre - Creer des dates\",\"shortname\":\"CAN_CREATE_EVENT\",\"description\":\"Autorise la creation de dates dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CREATE_FAQ\",\"after\":{\"id\":8,\"key\":\"CAN_CREATE_FAQ\",\"name\":\"Membre - Creer des FAQ\",\"shortname\":\"CAN_CREATE_FAQ\",\"description\":\"Autorise la creation de FAQ dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CREATE_PROJECT\",\"after\":{\"id\":9,\"key\":\"CAN_CREATE_PROJECT\",\"name\":\"Membre - Creer des projets\",\"shortname\":\"CAN_CREATE_PROJECT\",\"description\":\"Autorise la creation de projets dans le contexte cible.\",\"visibleItems\":[{\"id\":\"self\",\"label\":\"Element courant\"}],\"visibleValue\":\"Element courant\",\"memberType\":\"member\"}}]}','2026-07-28 08:53:42',1),
(35,1,1,678,'holon_updated','Modification de [holon|682|Memoire (rôle)] :\n- le parametre \"minimum d admins verrouille\" a ete active.\n- le parametre \"maximum d admins verrouille\" a ete active.\n- le parametre \"nombre minimum d admins\" a ete modifie.\n- le parametre \"nombre maximum d admins\" a ete modifie.\n- le droit \"Membre - Ajouter des proprietes de holons\" a ete ajoute : Cercle englobant seul; Elements du cercle parent.\n- le droit \"Membre - Devenir secretaire de PV\" a ete ajoute : Cercle englobant seul; Elements du cercle parent.\n- le droit \"Membre - Creer des prises de decision\" a ete ajoute : Cercle englobant seul.\n- le droit \"Membre - Creer des fichiers\" a ete ajoute : Cercle englobant seul.\n- le droit \"Membre - Creer des dates\" a ete ajoute : Cercle englobant seul.\n- le droit \"Membre - Creer des indicateurs\" a ete ajoute : Cercle englobant seul.\n- le droit \"Membre - Supprimer des dates\" a ete ajoute : Cercle englobant seul; Elements du cercle parent.\n- le droit \"Membre - Supprimer les proprietes de holons\" a ete ajoute : Cercle englobant seul; Elements du cercle parent.\n- le droit \"Membre - Modifier les proprietes de holons\" a ete ajoute : Cercle englobant seul; Elements du cercle parent.','{\"IDholon\":682,\"before\":{\"holon\":{\"id\":682,\"name\":\"Memoire\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"#f59e0b\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":false,\"adminParent\":false,\"adminMin\":0,\"adminMax\":null,\"lockedAdminMin\":false,\"lockedAdminMax\":false,\"adminMinOverride\":false,\"adminMaxOverride\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"inheritedValue\":\"\",\"visibleValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"authority\",\"localValue\":\"[\\\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\\\"]\",\"visibleItems\":[\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\\\"]\",\"visibleItems\":[\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\"]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":682,\"name\":\"Memoire\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"#f59e0b\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":false,\"adminParent\":false,\"adminMin\":1,\"adminMax\":1,\"lockedAdminMin\":true,\"lockedAdminMax\":true,\"adminMinOverride\":false,\"adminMaxOverride\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"inheritedValue\":\"\",\"visibleValue\":\"Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"authority\",\"localValue\":\"[\\\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\\\"]\",\"visibleItems\":[\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\\\"]\",\"visibleItems\":[\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\"]}},\"permissions\":{\"member:CAN_ADD_HOLON_PROPERTIES\":{\"id\":16,\"key\":\"CAN_ADD_HOLON_PROPERTIES\",\"name\":\"Membre - Ajouter des proprietes de holons\",\"shortname\":\"CAN_ADD_HOLON_PROPERTIES\",\"description\":\"Autorise l ajout de proprietes directement sur un holon dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"},\"member:CAN_CLAIM_PV\":{\"id\":7,\"key\":\"CAN_CLAIM_PV\",\"name\":\"Membre - Devenir secretaire de PV\",\"shortname\":\"CAN_CLAIM_PV\",\"description\":\"Autorise a prendre le role de secretaire pendant une reunion associee a un PV.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"},\"member:CAN_CREATE_DECISION\":{\"id\":4,\"key\":\"CAN_CREATE_DECISION\",\"name\":\"Membre - Creer des prises de decision\",\"shortname\":\"CAN_CREATE_DECISION\",\"description\":\"Autorise la creation de prises de decision dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_DOCUMENT\":{\"id\":3,\"key\":\"CAN_CREATE_DOCUMENT\",\"name\":\"Membre - Creer des fichiers\",\"shortname\":\"CAN_CREATE_DOCUMENT\",\"description\":\"Autorise la creation de fichiers dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_EVENT\":{\"id\":5,\"key\":\"CAN_CREATE_EVENT\",\"name\":\"Membre - Creer des dates\",\"shortname\":\"CAN_CREATE_EVENT\",\"description\":\"Autorise la creation de dates dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_INDICATOR\":{\"id\":10,\"key\":\"CAN_CREATE_INDICATOR\",\"name\":\"Membre - Creer des indicateurs\",\"shortname\":\"CAN_CREATE_INDICATOR\",\"description\":\"Autorise la creation d indicateurs dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_DELETE_EVENT\":{\"id\":6,\"key\":\"CAN_DELETE_EVENT\",\"name\":\"Membre - Supprimer des dates\",\"shortname\":\"CAN_DELETE_EVENT\",\"description\":\"Autorise la suppression de dates dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"},\"member:CAN_DELETE_HOLON_PROPERTIES\":{\"id\":17,\"key\":\"CAN_DELETE_HOLON_PROPERTIES\",\"name\":\"Membre - Supprimer les proprietes de holons\",\"shortname\":\"CAN_DELETE_HOLON_PROPERTIES\",\"description\":\"Autorise le retrait des proprietes ajoutees directement a un holon dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"},\"member:CAN_EDIT_HOLON_PROPERTIES\":{\"id\":15,\"key\":\"CAN_EDIT_HOLON_PROPERTIES\",\"name\":\"Membre - Modifier les proprietes de holons\",\"shortname\":\"CAN_EDIT_HOLON_PROPERTIES\",\"description\":\"Autorise la modification des proprietes ajoutees directement a un holon dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"}}},\"changes\":[{\"type\":\"field_changed\",\"field\":\"lockedAdminMin\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"lockedAdminMax\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"adminMin\",\"before\":0,\"after\":1},{\"type\":\"field_changed\",\"field\":\"adminMax\",\"before\":null,\"after\":1},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_ADD_HOLON_PROPERTIES\",\"after\":{\"id\":16,\"key\":\"CAN_ADD_HOLON_PROPERTIES\",\"name\":\"Membre - Ajouter des proprietes de holons\",\"shortname\":\"CAN_ADD_HOLON_PROPERTIES\",\"description\":\"Autorise l ajout de proprietes directement sur un holon dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CLAIM_PV\",\"after\":{\"id\":7,\"key\":\"CAN_CLAIM_PV\",\"name\":\"Membre - Devenir secretaire de PV\",\"shortname\":\"CAN_CLAIM_PV\",\"description\":\"Autorise a prendre le role de secretaire pendant une reunion associee a un PV.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CREATE_DECISION\",\"after\":{\"id\":4,\"key\":\"CAN_CREATE_DECISION\",\"name\":\"Membre - Creer des prises de decision\",\"shortname\":\"CAN_CREATE_DECISION\",\"description\":\"Autorise la creation de prises de decision dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CREATE_DOCUMENT\",\"after\":{\"id\":3,\"key\":\"CAN_CREATE_DOCUMENT\",\"name\":\"Membre - Creer des fichiers\",\"shortname\":\"CAN_CREATE_DOCUMENT\",\"description\":\"Autorise la creation de fichiers dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CREATE_EVENT\",\"after\":{\"id\":5,\"key\":\"CAN_CREATE_EVENT\",\"name\":\"Membre - Creer des dates\",\"shortname\":\"CAN_CREATE_EVENT\",\"description\":\"Autorise la creation de dates dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CREATE_INDICATOR\",\"after\":{\"id\":10,\"key\":\"CAN_CREATE_INDICATOR\",\"name\":\"Membre - Creer des indicateurs\",\"shortname\":\"CAN_CREATE_INDICATOR\",\"description\":\"Autorise la creation d indicateurs dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_DELETE_EVENT\",\"after\":{\"id\":6,\"key\":\"CAN_DELETE_EVENT\",\"name\":\"Membre - Supprimer des dates\",\"shortname\":\"CAN_DELETE_EVENT\",\"description\":\"Autorise la suppression de dates dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_DELETE_HOLON_PROPERTIES\",\"after\":{\"id\":17,\"key\":\"CAN_DELETE_HOLON_PROPERTIES\",\"name\":\"Membre - Supprimer les proprietes de holons\",\"shortname\":\"CAN_DELETE_HOLON_PROPERTIES\",\"description\":\"Autorise le retrait des proprietes ajoutees directement a un holon dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_EDIT_HOLON_PROPERTIES\",\"after\":{\"id\":15,\"key\":\"CAN_EDIT_HOLON_PROPERTIES\",\"name\":\"Membre - Modifier les proprietes de holons\",\"shortname\":\"CAN_EDIT_HOLON_PROPERTIES\",\"description\":\"Autorise la modification des proprietes ajoutees directement a un holon dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"}}]}','2026-07-28 09:00:45',1),
(36,1,1,678,'holon_updated','Modification de [holon|680|Lien pilotage (rôle)] :\n- le parametre \"minimum d admins verrouille\" a ete active.\n- le parametre \"maximum d admins verrouille\" a ete active.\n- le parametre \"admin parent\" a ete active.\n- le parametre \"nombre minimum d admins\" a ete modifie.\n- le parametre \"nombre maximum d admins\" a ete modifie.\n- le droit \"Membre - Definir un admin de contexte\" a ete ajoute : Elements du cercle parent.\n- le droit \"Membre - Ajouter un membre\" a ete ajoute : Cercle englobant seul; Elements du cercle parent.\n- le droit \"Membre - Creer des prises de decision\" a ete ajoute : Cercle englobant seul.\n- le droit \"Membre - Creer des fichiers\" a ete ajoute : Cercle englobant seul.\n- le droit \"Membre - Creer des dates\" a ete ajoute : Cercle englobant seul.\n- le droit \"Membre - Creer des FAQ\" a ete ajoute : Cercle englobant seul.\n- le droit \"Membre - Creer des indicateurs\" a ete ajoute : Cercle englobant seul; Elements du cercle parent.\n- le droit \"Membre - Creer des projets\" a ete ajoute : Cercle englobant seul.','{\"IDholon\":680,\"before\":{\"holon\":{\"id\":680,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"#f55c0a\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":true,\"adminParent\":false,\"adminMin\":0,\"adminMax\":null,\"lockedAdminMin\":false,\"lockedAdminMax\":false,\"adminMinOverride\":false,\"adminMaxOverride\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"inheritedValue\":\"\",\"visibleValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"authority\",\"localValue\":\"[\\\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\\\"]\",\"visibleItems\":[\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleItems\":[\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\"]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":680,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"#f55c0a\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":true,\"adminParent\":true,\"adminMin\":1,\"adminMax\":1,\"lockedAdminMin\":true,\"lockedAdminMax\":true,\"adminMinOverride\":false,\"adminMaxOverride\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"inheritedValue\":\"\",\"visibleValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"authority\",\"localValue\":\"[\\\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\\\"]\",\"visibleItems\":[\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\"]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleItems\":[\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\"]}},\"permissions\":{\"member:CAN_ADD_ADMIN\":{\"id\":2,\"key\":\"CAN_ADD_ADMIN\",\"name\":\"Membre - Definir un admin de contexte\",\"shortname\":\"CAN_ADD_ADMIN\",\"description\":\"Autorise l attribution ou le retrait du statut admin dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Elements du cercle parent\",\"memberType\":\"member\"},\"member:CAN_ADD_MEMBER\":{\"id\":1,\"key\":\"CAN_ADD_MEMBER\",\"name\":\"Membre - Ajouter un membre\",\"shortname\":\"CAN_ADD_MEMBER\",\"description\":\"Autorise l ajout d un membre dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"},\"member:CAN_CREATE_DECISION\":{\"id\":4,\"key\":\"CAN_CREATE_DECISION\",\"name\":\"Membre - Creer des prises de decision\",\"shortname\":\"CAN_CREATE_DECISION\",\"description\":\"Autorise la creation de prises de decision dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_DOCUMENT\":{\"id\":3,\"key\":\"CAN_CREATE_DOCUMENT\",\"name\":\"Membre - Creer des fichiers\",\"shortname\":\"CAN_CREATE_DOCUMENT\",\"description\":\"Autorise la creation de fichiers dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_EVENT\":{\"id\":5,\"key\":\"CAN_CREATE_EVENT\",\"name\":\"Membre - Creer des dates\",\"shortname\":\"CAN_CREATE_EVENT\",\"description\":\"Autorise la creation de dates dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_FAQ\":{\"id\":8,\"key\":\"CAN_CREATE_FAQ\",\"name\":\"Membre - Creer des FAQ\",\"shortname\":\"CAN_CREATE_FAQ\",\"description\":\"Autorise la creation de FAQ dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_INDICATOR\":{\"id\":10,\"key\":\"CAN_CREATE_INDICATOR\",\"name\":\"Membre - Creer des indicateurs\",\"shortname\":\"CAN_CREATE_INDICATOR\",\"description\":\"Autorise la creation d indicateurs dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"},\"member:CAN_CREATE_PROJECT\":{\"id\":9,\"key\":\"CAN_CREATE_PROJECT\",\"name\":\"Membre - Creer des projets\",\"shortname\":\"CAN_CREATE_PROJECT\",\"description\":\"Autorise la creation de projets dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"}}},\"changes\":[{\"type\":\"field_changed\",\"field\":\"lockedAdminMin\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"lockedAdminMax\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"adminParent\",\"before\":false,\"after\":true},{\"type\":\"field_changed\",\"field\":\"adminMin\",\"before\":0,\"after\":1},{\"type\":\"field_changed\",\"field\":\"adminMax\",\"before\":null,\"after\":1},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_ADD_ADMIN\",\"after\":{\"id\":2,\"key\":\"CAN_ADD_ADMIN\",\"name\":\"Membre - Definir un admin de contexte\",\"shortname\":\"CAN_ADD_ADMIN\",\"description\":\"Autorise l attribution ou le retrait du statut admin dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Elements du cercle parent\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_ADD_MEMBER\",\"after\":{\"id\":1,\"key\":\"CAN_ADD_MEMBER\",\"name\":\"Membre - Ajouter un membre\",\"shortname\":\"CAN_ADD_MEMBER\",\"description\":\"Autorise l ajout d un membre dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CREATE_DECISION\",\"after\":{\"id\":4,\"key\":\"CAN_CREATE_DECISION\",\"name\":\"Membre - Creer des prises de decision\",\"shortname\":\"CAN_CREATE_DECISION\",\"description\":\"Autorise la creation de prises de decision dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CREATE_DOCUMENT\",\"after\":{\"id\":3,\"key\":\"CAN_CREATE_DOCUMENT\",\"name\":\"Membre - Creer des fichiers\",\"shortname\":\"CAN_CREATE_DOCUMENT\",\"description\":\"Autorise la creation de fichiers dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CREATE_EVENT\",\"after\":{\"id\":5,\"key\":\"CAN_CREATE_EVENT\",\"name\":\"Membre - Creer des dates\",\"shortname\":\"CAN_CREATE_EVENT\",\"description\":\"Autorise la creation de dates dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CREATE_FAQ\",\"after\":{\"id\":8,\"key\":\"CAN_CREATE_FAQ\",\"name\":\"Membre - Creer des FAQ\",\"shortname\":\"CAN_CREATE_FAQ\",\"description\":\"Autorise la creation de FAQ dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CREATE_INDICATOR\",\"after\":{\"id\":10,\"key\":\"CAN_CREATE_INDICATOR\",\"name\":\"Membre - Creer des indicateurs\",\"shortname\":\"CAN_CREATE_INDICATOR\",\"description\":\"Autorise la creation d indicateurs dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"}},{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_CREATE_PROJECT\",\"after\":{\"id\":9,\"key\":\"CAN_CREATE_PROJECT\",\"name\":\"Membre - Creer des projets\",\"shortname\":\"CAN_CREATE_PROJECT\",\"description\":\"Autorise la creation de projets dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"}}]}','2026-07-28 09:05:15',1),
(37,1,1,678,'holon_updated','Modification de [holon|680|Lien pilotage (rôle)] :\n- le parametre \"visible\" a ete desactive.','{\"IDholon\":680,\"before\":{\"holon\":{\"id\":680,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"#f55c0a\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":true,\"adminParent\":true,\"adminMin\":1,\"adminMax\":1,\"lockedAdminMin\":true,\"lockedAdminMax\":true,\"adminMinOverride\":false,\"adminMaxOverride\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"inheritedValue\":\"\",\"visibleValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"authority\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleItems\":[\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\"]}},\"permissions\":{\"member:CAN_ADD_ADMIN\":{\"id\":2,\"key\":\"CAN_ADD_ADMIN\",\"name\":\"Membre - Definir un admin de contexte\",\"shortname\":\"CAN_ADD_ADMIN\",\"description\":\"Autorise l attribution ou le retrait du statut admin dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Elements du cercle parent\",\"memberType\":\"member\"},\"member:CAN_ADD_MEMBER\":{\"id\":1,\"key\":\"CAN_ADD_MEMBER\",\"name\":\"Membre - Ajouter un membre\",\"shortname\":\"CAN_ADD_MEMBER\",\"description\":\"Autorise l ajout d un membre dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"},\"member:CAN_CREATE_DECISION\":{\"id\":4,\"key\":\"CAN_CREATE_DECISION\",\"name\":\"Membre - Creer des prises de decision\",\"shortname\":\"CAN_CREATE_DECISION\",\"description\":\"Autorise la creation de prises de decision dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_DOCUMENT\":{\"id\":3,\"key\":\"CAN_CREATE_DOCUMENT\",\"name\":\"Membre - Creer des fichiers\",\"shortname\":\"CAN_CREATE_DOCUMENT\",\"description\":\"Autorise la creation de fichiers dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_EVENT\":{\"id\":5,\"key\":\"CAN_CREATE_EVENT\",\"name\":\"Membre - Creer des dates\",\"shortname\":\"CAN_CREATE_EVENT\",\"description\":\"Autorise la creation de dates dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_FAQ\":{\"id\":8,\"key\":\"CAN_CREATE_FAQ\",\"name\":\"Membre - Creer des FAQ\",\"shortname\":\"CAN_CREATE_FAQ\",\"description\":\"Autorise la creation de FAQ dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_INDICATOR\":{\"id\":10,\"key\":\"CAN_CREATE_INDICATOR\",\"name\":\"Membre - Creer des indicateurs\",\"shortname\":\"CAN_CREATE_INDICATOR\",\"description\":\"Autorise la creation d indicateurs dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"},\"member:CAN_CREATE_PROJECT\":{\"id\":9,\"key\":\"CAN_CREATE_PROJECT\",\"name\":\"Membre - Creer des projets\",\"shortname\":\"CAN_CREATE_PROJECT\",\"description\":\"Autorise la creation de projets dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"}}},\"after\":{\"holon\":{\"id\":680,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":675,\"inheritsFromName\":\"Role\",\"color\":\"#f55c0a\",\"icon\":\"\",\"banner\":\"\",\"visible\":false,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":true,\"adminParent\":true,\"adminMin\":1,\"adminMax\":1,\"lockedAdminMin\":true,\"lockedAdminMax\":true,\"adminMinOverride\":false,\"adminMaxOverride\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"inheritedValue\":\"\",\"visibleValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"authority\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"inheritedValue\":\"\",\"visibleValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleItems\":[\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\"]}},\"permissions\":{\"member:CAN_ADD_ADMIN\":{\"id\":2,\"key\":\"CAN_ADD_ADMIN\",\"name\":\"Membre - Definir un admin de contexte\",\"shortname\":\"CAN_ADD_ADMIN\",\"description\":\"Autorise l attribution ou le retrait du statut admin dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Elements du cercle parent\",\"memberType\":\"member\"},\"member:CAN_ADD_MEMBER\":{\"id\":1,\"key\":\"CAN_ADD_MEMBER\",\"name\":\"Membre - Ajouter un membre\",\"shortname\":\"CAN_ADD_MEMBER\",\"description\":\"Autorise l ajout d un membre dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"},\"member:CAN_CREATE_DECISION\":{\"id\":4,\"key\":\"CAN_CREATE_DECISION\",\"name\":\"Membre - Creer des prises de decision\",\"shortname\":\"CAN_CREATE_DECISION\",\"description\":\"Autorise la creation de prises de decision dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_DOCUMENT\":{\"id\":3,\"key\":\"CAN_CREATE_DOCUMENT\",\"name\":\"Membre - Creer des fichiers\",\"shortname\":\"CAN_CREATE_DOCUMENT\",\"description\":\"Autorise la creation de fichiers dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_EVENT\":{\"id\":5,\"key\":\"CAN_CREATE_EVENT\",\"name\":\"Membre - Creer des dates\",\"shortname\":\"CAN_CREATE_EVENT\",\"description\":\"Autorise la creation de dates dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_FAQ\":{\"id\":8,\"key\":\"CAN_CREATE_FAQ\",\"name\":\"Membre - Creer des FAQ\",\"shortname\":\"CAN_CREATE_FAQ\",\"description\":\"Autorise la creation de FAQ dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"},\"member:CAN_CREATE_INDICATOR\":{\"id\":10,\"key\":\"CAN_CREATE_INDICATOR\",\"name\":\"Membre - Creer des indicateurs\",\"shortname\":\"CAN_CREATE_INDICATOR\",\"description\":\"Autorise la creation d indicateurs dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"},{\"id\":\"parent_circle_elements\",\"label\":\"Elements du cercle parent\"}],\"visibleValue\":\"Cercle englobant seul; Elements du cercle parent\",\"memberType\":\"member\"},\"member:CAN_CREATE_PROJECT\":{\"id\":9,\"key\":\"CAN_CREATE_PROJECT\",\"name\":\"Membre - Creer des projets\",\"shortname\":\"CAN_CREATE_PROJECT\",\"description\":\"Autorise la creation de projets dans le contexte cible.\",\"visibleItems\":[{\"id\":\"parent_circle\",\"label\":\"Cercle englobant seul\"}],\"visibleValue\":\"Cercle englobant seul\",\"memberType\":\"member\"}}},\"changes\":[{\"type\":\"field_changed\",\"field\":\"visible\",\"before\":true,\"after\":false}]}','2026-07-28 09:06:32',1),
(38,1,1,678,'holon_created','Creation de [holon|833|Lien pilotage (rôle)].','{\"IDholon\":833,\"after\":{\"holon\":{\"id\":833,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":680,\"inheritsFromName\":\"Lien pilotage\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false,\"adminParent\":false,\"adminMin\":1,\"adminMax\":1,\"lockedAdminMin\":false,\"lockedAdminMax\":false,\"adminMinOverride\":false,\"adminMaxOverride\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"authority\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleItems\":[\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\"]}},\"permissions\":[]}}','2026-07-28 09:06:48',1),
(39,1,1,678,'holon_updated','Modification de [holon|833|Lien pilotage (rôle)] :\n- le droit \"Membre - Gerer les applications\" a ete ajoute : Toute l organisation.','{\"IDholon\":833,\"before\":{\"holon\":{\"id\":833,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":680,\"inheritsFromName\":\"Lien pilotage\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false,\"adminParent\":false,\"adminMin\":1,\"adminMax\":1,\"lockedAdminMin\":false,\"lockedAdminMax\":false,\"adminMinOverride\":false,\"adminMaxOverride\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"authority\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleItems\":[\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\"]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":833,\"name\":\"Lien pilotage\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":678,\"templateId\":680,\"inheritsFromName\":\"Lien pilotage\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":false,\"lockedName\":false,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":false,\"link\":false,\"adminParent\":false,\"adminMin\":1,\"adminMax\":1,\"lockedAdminMin\":false,\"lockedAdminMax\":false,\"adminMinOverride\":false,\"adminMaxOverride\":false},\"properties\":{\"43\":{\"id\":43,\"name\":\"Raison d\'etre\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleValue\":\"Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.\",\"visibleItems\":[]},\"45\":{\"id\":45,\"name\":\"Domaines d\'autorite\",\"shortname\":\"domaines_d_autorite\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"authority\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"44\":{\"id\":44,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":2,\"formatName\":\"Liste\",\"listItemType\":\"text\",\"localValue\":\"\",\"inheritedValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleValue\":\"[\\\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\\\"]\",\"visibleItems\":[\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\"]}},\"permissions\":{\"member:CAN_ADD_APP\":{\"id\":11,\"key\":\"CAN_ADD_APP\",\"name\":\"Membre - Gerer les applications\",\"shortname\":\"CAN_ADD_APP\",\"description\":\"Autorise la gestion des applications actives et de leur ordre dans l organisation.\",\"visibleItems\":[{\"id\":\"organization\",\"label\":\"Toute l organisation\"}],\"visibleValue\":\"Toute l organisation\",\"memberType\":\"member\"}}},\"changes\":[{\"type\":\"permission_added\",\"permissionKey\":\"member:CAN_ADD_APP\",\"after\":{\"id\":11,\"key\":\"CAN_ADD_APP\",\"name\":\"Membre - Gerer les applications\",\"shortname\":\"CAN_ADD_APP\",\"description\":\"Autorise la gestion des applications actives et de leur ordre dans l organisation.\",\"visibleItems\":[{\"id\":\"organization\",\"label\":\"Toute l organisation\"}],\"visibleValue\":\"Toute l organisation\",\"memberType\":\"member\"}}]}','2026-07-28 09:07:42',1),
(40,1,1,678,'holon_member_added','[user|1|Admin] a été ajouté au [holon|833|rôle Lien pilotage] par [user|1|Admin].','{\"IDtargetuser\":1,\"IDholon\":833,\"authorUserId\":1}','2026-07-28 09:08:57',1);
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
  `icon` varchar(255) DEFAULT NULL COMMENT 'Illustration carre du holon ou du template.',
  `banner` varchar(255) DEFAULT NULL COMMENT 'Illustration large du holon ou du template.',
  `IDholon_org` int(11) DEFAULT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Est supprimé ? Peut éventuellement être sorti d''une corbeille ou consulté pour archivage, mais sinon n''est plus utilisé',
  `visible` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Est visible? Ou plutôt caché pour pouvoir être réaffiché plus tard ou pour servir de template invisible',
  `mandatory` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Est obligatoire, et est ajouté à tout cercle nouvellement créé',
  `lockedname` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Le nom est impose par le template pour toutes ses instances',
  `lockedicon` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'L''icone est imposee par le template pour toutes ses instances',
  `lockedbanner` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'La banniere est imposee par le template pour toutes ses instances',
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
  PRIMARY KEY (`id`),
  KEY `idx_holon_organization` (`IDorganization`),
  KEY `idx_holon_root` (`IDholon_org`),
  KEY `idx_holon_parent` (`IDholon_parent`),
  KEY `idx_holon_template` (`IDholon_template`),
  CONSTRAINT `fk_holon_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_holon_parent` FOREIGN KEY (`IDholon_parent`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_holon_root` FOREIGN KEY (`IDholon_org`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_holon_template` FOREIGN KEY (`IDholon_template`) REFERENCES `holon` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=834 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holon`
--

LOCK TABLES `holon` WRITE;
/*!40000 ALTER TABLE `holon` DISABLE KEYS */;
INSERT INTO `holon` VALUES
(1,NULL,'OpenMyOrganization',NULL,NULL,NULL,NULL,NULL,1,'2024-11-30 09:50:26',NULL,1,1,0,0,0,0,0,0,0,0,0,0,NULL,0,0,'Organisation basique',4,NULL,NULL,NULL),
(2,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2024-11-30 09:50:26',NULL,1,0,0,0,0,0,0,0,0,0,0,0,NULL,0,0,'Rôle',1,1,NULL,NULL),
(3,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2024-11-30 09:51:41',NULL,1,0,0,0,0,0,0,0,0,0,0,0,NULL,0,0,'Cercle',2,1,NULL,NULL),
(4,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2024-11-30 09:53:13',NULL,1,0,0,0,0,0,0,0,0,0,0,0,NULL,0,0,'Groupe',3,1,NULL,NULL),
(674,1,'OpenMyOrganization',NULL,'#005c8a',NULL,NULL,NULL,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,0,0,0,NULL,0,0,NULL,4,NULL,NULL,NULL),
(675,NULL,'Role',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,0,0,0,0,0,0,0,0,1,1,0,1,1,0,'Role',1,674,NULL,NULL),
(676,NULL,'Cercle',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,0,0,0,0,0,0,0,0,0,0,0,NULL,0,0,'Cercle',2,674,NULL,NULL),
(677,NULL,NULL,NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,0,0,0,0,0,0,0,0,0,0,0,NULL,0,0,'Groupe',3,674,NULL,NULL),
(678,NULL,'Ancrage',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,2,674,676,NULL),
(679,NULL,'CA',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,2,674,676,NULL),
(680,NULL,'Lien pilotage',NULL,'#f55c0a',NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,0,1,1,0,0,1,1,1,1,1,0,1,1,0,'Lien pilotage',1,678,675,NULL),
(681,NULL,'Facilitation',NULL,'#f5870a',NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,1,1,0,0,1,0,0,NULL,0,0,NULL,0,0,'Facilitation',1,678,675,NULL),
(682,NULL,'Memoire',NULL,'#f59e0b',NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,1,1,0,0,1,0,0,1,1,0,1,1,0,'Memoire',1,678,675,NULL),
(683,NULL,'Operations',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,678,675,NULL),
(684,NULL,'President',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,679,675,NULL),
(685,NULL,'Tresorier',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,679,675,NULL),
(686,NULL,'Administration',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,2,678,676,NULL),
(687,NULL,'Marketing',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,2,678,676,NULL),
(691,NULL,'Operations administration',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,686,683,NULL),
(692,NULL,'Gestion administrative',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,686,675,NULL),
(693,NULL,'Comptabilite et budget',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,686,675,NULL),
(694,NULL,'Support interne',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,686,675,NULL),
(698,NULL,'Operations marketing',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,687,683,NULL),
(699,NULL,'Communication digitale',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,687,675,NULL),
(700,NULL,'Partenariats et visibilite',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,687,675,NULL),
(701,NULL,'Contenus et campagnes',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,687,675,NULL),
(702,NULL,'Facilitation',NULL,NULL,NULL,NULL,674,1,'2026-07-23 13:47:11',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,686,681,NULL),
(703,NULL,'Memoire',NULL,NULL,NULL,NULL,674,1,'2026-07-23 13:47:19',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,686,682,NULL),
(704,NULL,'Lien pilotage',NULL,NULL,NULL,NULL,674,1,'2026-07-23 13:47:27',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,686,680,NULL),
(705,NULL,'Memoire',NULL,NULL,NULL,NULL,674,1,'2026-07-23 13:47:37',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,687,682,NULL),
(706,NULL,'Facilitation',NULL,NULL,NULL,NULL,674,1,'2026-07-23 13:47:45',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,687,681,NULL),
(707,NULL,'Lien pilotage',NULL,NULL,NULL,NULL,674,1,'2026-07-23 13:47:54',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,687,680,NULL),
(708,NULL,'Inclusion','Inclusion',NULL,NULL,NULL,674,1,'2026-07-23 13:51:27',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,1,678,675,NULL),
(709,2,'Exemple de modèle',NULL,'#803e89',NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,0,0,0,0,0,0,0,0,0,0,NULL,0,0,'Modèle de base',4,NULL,NULL,NULL),
(710,NULL,'Rôle',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,0,0,0,0,0,0,0,0,0,0,0,NULL,0,0,'Rôle',1,709,NULL,NULL),
(711,NULL,'Cercle',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,0,0,0,0,0,0,0,0,0,0,0,NULL,0,0,'Cercle',2,709,NULL,NULL),
(712,NULL,'Ancrage',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,0,0,0,0,0,0,0,NULL,0,0,NULL,0,0,NULL,2,709,711,NULL),
(713,NULL,'Facilitation',NULL,'#f59f0a',NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,1,1,0,0,1,0,0,NULL,0,0,NULL,0,0,'Facilitation',1,712,710,NULL),
(714,NULL,'Lien pilotage',NULL,'#f52d0a',NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,1,1,0,0,1,1,0,NULL,0,0,NULL,0,0,'Lien pilotage',1,712,710,NULL),
(715,NULL,'Mémoire',NULL,'#f5740a',NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,1,1,0,0,1,0,0,NULL,0,0,NULL,0,0,'Mémoire',1,712,710,NULL),
(716,NULL,'Opérations',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,0,0,0,0,0,0,0,0,0,0,NULL,0,0,NULL,1,712,NULL,NULL),
(719,NULL,'CA',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,0,0,0,0,0,0,0,0,0,0,NULL,0,0,NULL,2,709,NULL,NULL),
(720,NULL,'Président',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,0,0,0,0,0,0,0,0,0,0,NULL,0,0,NULL,1,719,NULL,NULL),
(721,NULL,'Trésorier',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,0,0,0,0,0,0,0,0,0,0,NULL,0,0,NULL,1,719,NULL,NULL),
(833,NULL,'Lien pilotage',NULL,NULL,NULL,NULL,674,1,'2026-07-28 09:06:48',NULL,1,1,0,0,0,0,0,0,0,1,0,0,1,0,0,NULL,1,678,680,NULL);
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
  `range` varchar(40) NOT NULL DEFAULT 'self',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_holon_permission_profile_range` (`IDholon`,`IDpermission`,`member_type`,`range`),
  KEY `idx_holon_permission_permission` (`IDpermission`),
  KEY `idx_holon_permission_range` (`range`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holon_permission`
--

LOCK TABLES `holon_permission` WRITE;
/*!40000 ALTER TABLE `holon_permission` DISABLE KEYS */;
INSERT INTO `holon_permission` VALUES
(1,710,1,'member','self','2026-07-25 10:29:21','2026-07-25 10:29:21'),
(2,710,5,'member','self','2026-07-25 10:29:21','2026-07-25 10:29:21'),
(3,710,8,'member','self','2026-07-25 10:29:21','2026-07-25 10:29:21'),
(4,710,3,'member','self','2026-07-25 10:29:21','2026-07-25 10:29:21'),
(5,710,10,'member','self','2026-07-25 10:29:21','2026-07-25 10:29:21'),
(6,710,4,'member','self','2026-07-25 10:29:21','2026-07-25 10:29:21'),
(7,710,9,'member','self','2026-07-25 10:29:21','2026-07-25 10:29:21'),
(8,675,5,'member','self','2026-07-28 08:53:42','2026-07-28 08:53:42'),
(9,675,8,'member','self','2026-07-28 08:53:42','2026-07-28 08:53:42'),
(10,675,3,'member','self','2026-07-28 08:53:42','2026-07-28 08:53:42'),
(11,675,9,'member','self','2026-07-28 08:53:42','2026-07-28 08:53:42'),
(12,675,7,'member','self','2026-07-28 08:53:42','2026-07-28 08:53:42'),
(13,675,16,'admin','self','2026-07-28 08:53:42','2026-07-28 08:53:42'),
(14,675,1,'admin','self','2026-07-28 08:53:42','2026-07-28 08:53:42'),
(15,675,10,'admin','self','2026-07-28 08:53:42','2026-07-28 08:53:42'),
(16,675,4,'admin','self','2026-07-28 08:53:42','2026-07-28 08:53:42'),
(17,675,15,'admin','self','2026-07-28 08:53:42','2026-07-28 08:53:42'),
(18,675,6,'admin','self','2026-07-28 08:53:42','2026-07-28 08:53:42'),
(19,675,17,'admin','self','2026-07-28 08:53:42','2026-07-28 08:53:42'),
(20,682,16,'member','parent_circle_elements','2026-07-28 09:00:45','2026-07-28 09:00:45'),
(21,682,16,'member','parent_circle','2026-07-28 09:00:45','2026-07-28 09:00:45'),
(22,682,5,'member','parent_circle','2026-07-28 09:00:45','2026-07-28 09:00:45'),
(23,682,3,'member','parent_circle','2026-07-28 09:00:45','2026-07-28 09:00:45'),
(24,682,10,'member','parent_circle','2026-07-28 09:00:45','2026-07-28 09:00:45'),
(25,682,4,'member','parent_circle','2026-07-28 09:00:45','2026-07-28 09:00:45'),
(26,682,7,'member','parent_circle','2026-07-28 09:00:45','2026-07-28 09:00:45'),
(27,682,7,'member','parent_circle_elements','2026-07-28 09:00:45','2026-07-28 09:00:45'),
(28,682,15,'member','parent_circle_elements','2026-07-28 09:00:45','2026-07-28 09:00:45'),
(29,682,15,'member','parent_circle','2026-07-28 09:00:45','2026-07-28 09:00:45'),
(30,682,6,'member','parent_circle','2026-07-28 09:00:45','2026-07-28 09:00:45'),
(31,682,6,'member','parent_circle_elements','2026-07-28 09:00:45','2026-07-28 09:00:45'),
(32,682,17,'member','parent_circle','2026-07-28 09:00:45','2026-07-28 09:00:45'),
(33,682,17,'member','parent_circle_elements','2026-07-28 09:00:45','2026-07-28 09:00:45'),
(34,680,1,'member','parent_circle','2026-07-28 09:05:15','2026-07-28 09:05:15'),
(35,680,1,'member','parent_circle_elements','2026-07-28 09:05:15','2026-07-28 09:05:15'),
(36,680,5,'member','parent_circle','2026-07-28 09:05:15','2026-07-28 09:05:15'),
(37,680,8,'member','parent_circle','2026-07-28 09:05:15','2026-07-28 09:05:15'),
(38,680,3,'member','parent_circle','2026-07-28 09:05:15','2026-07-28 09:05:15'),
(39,680,10,'member','parent_circle','2026-07-28 09:05:15','2026-07-28 09:05:15'),
(40,680,10,'member','parent_circle_elements','2026-07-28 09:05:15','2026-07-28 09:05:15'),
(41,680,4,'member','parent_circle','2026-07-28 09:05:15','2026-07-28 09:05:15'),
(42,680,9,'member','parent_circle','2026-07-28 09:05:15','2026-07-28 09:05:15'),
(43,680,2,'member','parent_circle_elements','2026-07-28 09:05:15','2026-07-28 09:05:15'),
(44,833,11,'member','organization','2026-07-28 09:07:42','2026-07-28 09:07:42');
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
) ENGINE=InnoDB AUTO_INCREMENT=340 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holonproperty`
--

LOCK TABLES `holonproperty` WRITE;
/*!40000 ALTER TABLE `holonproperty` DISABLE KEYS */;
INSERT INTO `holonproperty` VALUES
(1,1,1,NULL,1,NULL,NULL,0,0,1),
(2,1,2,NULL,2,NULL,NULL,0,0,1),
(3,1,3,NULL,3,NULL,NULL,0,0,1),
(4,2,1,NULL,1,NULL,NULL,0,0,1),
(5,2,2,NULL,2,NULL,NULL,0,0,1),
(6,2,3,NULL,3,NULL,NULL,0,0,1),
(7,3,1,NULL,1,NULL,NULL,0,0,1),
(8,3,2,NULL,2,NULL,NULL,0,0,1),
(9,3,3,NULL,3,NULL,NULL,0,0,1),
(10,1,4,NULL,4,NULL,NULL,0,0,1),
(11,3,4,NULL,4,NULL,NULL,0,0,1),
(122,674,43,'Rendre possible une gouvernance distribuee, lisible et apprenante, dans laquelle chaque cercle sait ou il contribue et avec quelle marge d\'autonomie.',1,NULL,NULL,0,0,1),
(123,674,44,'[\"Donner un cap commun, soutenir la prise de role, assurer un cadre de decision fiable et permettre aux sous-cercles de cooperer sans confusion structurelle.\"]',3,'2026-07-24 16:36:31',1,0,0,1),
(124,674,45,'[\"Architecture generale de la gouvernance, definition des espaces de responsabilite, arbitrage des tensions de structure et cadre d\'evolution de l\'organisation.\"]',2,'2026-07-24 16:36:31',1,0,0,1),
(125,674,46,NULL,4,'2026-07-24 16:36:31',1,0,0,1),
(126,675,43,NULL,1,NULL,NULL,1,0,1),
(127,675,44,NULL,3,NULL,NULL,0,0,1),
(128,675,45,NULL,2,NULL,NULL,0,0,1),
(129,676,43,NULL,1,NULL,NULL,0,0,1),
(130,676,44,NULL,3,NULL,NULL,0,0,1),
(131,676,45,NULL,2,NULL,NULL,0,0,1),
(132,676,46,NULL,4,NULL,NULL,0,0,1),
(133,678,43,'Tenir ensemble la coherence globale de l\'organisation, ses priorites structurelles et la capacite des sous-cercles a agir dans un cadre commun.',1,NULL,NULL,0,0,1),
(134,678,44,'[\"Porter les roles structurels de base, arbitrer les tensions de coordination, faire vivre les sous-cercles et assurer une articulation claire avec le CA.\"]',3,'2026-07-24 07:25:49',1,0,0,1),
(135,678,45,'[\"Cadre de gouvernance courante, roles structurels transversaux, arbitrages inter-cercles et supervision de la capacite d\'execution globale.\"]',2,'2026-07-24 07:25:49',1,0,0,1),
(136,678,46,'{\"before\":\"<p><b>Cap à tenir:</b></p><p>Trouver notre place et professionnaliser nos services</p><p><b>Objectifs prioritaires:</b></p>\",\"items\":[8,7,9],\"after\":\"<p><b>Privilégier:</b></p><ul><li>Les événements locaux sur la dispersion géographique</li></ul>\"}',4,'2026-07-24 07:25:49',1,0,0,1),
(137,679,43,'Garantir la solidite institutionnelle, la responsabilite fiduciaire et la tenue des engagements de l\'organisation sur ses enjeux de gouvernance formelle.',NULL,NULL,NULL,0,0,1),
(138,679,44,'Suivre les obligations du conseil, veiller aux grands equilibres, soutenir les decisions engageantes et offrir un cadre de redevabilite au niveau strategique.',NULL,NULL,NULL,0,0,1),
(139,679,45,'Questions statutaires, decisions engageant l\'organisation, surveillance budgetaire de haut niveau et responsabilites institutionnelles du conseil.',NULL,NULL,NULL,0,0,1),
(140,680,43,'Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.',1,NULL,NULL,0,0,1),
(141,680,44,'[\"Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.\"]',3,'2026-07-23 15:44:59',1,0,0,1),
(142,680,45,NULL,2,'2026-07-28 11:05:15',1,0,0,1),
(143,681,43,'Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.',1,NULL,NULL,0,0,1),
(144,681,44,'[\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\"]',3,'2026-07-23 15:46:11',1,0,0,1),
(145,681,45,'[\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\"]',2,'2026-07-23 15:46:11',1,0,0,1),
(146,682,43,'Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.',1,NULL,NULL,0,0,1),
(147,682,44,'[\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\"]',3,'2026-07-23 15:46:25',1,0,0,1),
(148,682,45,NULL,2,'2026-07-28 11:00:45',1,0,0,1),
(149,683,43,'Soutenir l\'execution concrete du cercle d\'ancrage afin que les sujets avances, les dependances soient visibles et les actions suivies.',NULL,NULL,NULL,0,0,1),
(150,683,44,'Coordonner le flux de travail, rendre visibles les points de blocage et soutenir une progression reguliere des engagements du cercle.',NULL,NULL,NULL,0,0,1),
(151,683,45,'Suivi d\'execution, coordination quotidienne, priorisation operationnelle et gestion des dependances de travail.',NULL,NULL,NULL,0,0,1),
(152,684,43,'Assurer une presidence lisible, capable d\'orienter les deliberations du conseil et de tenir le cadre de responsabilite de l\'organisation.',NULL,NULL,NULL,0,0,1),
(153,684,44,'Convoquer, orienter, representer et garantir que les decisions du CA soient prises dans un cadre clair et suivi.',NULL,NULL,NULL,0,0,1),
(154,684,45,'Animation politique du conseil, representation institutionnelle et tenue du cadre des decisions du CA.',NULL,NULL,NULL,0,0,1),
(155,685,43,'Veiller a la solidite economique de l\'organisation et a la lisibilite des engagements financiers pris au niveau du conseil.',NULL,NULL,NULL,0,0,1),
(156,685,44,'Suivre les grands equilibres, alerter en cas d\'ecart significatif et soutenir le CA dans ses lectures budgetaires et financieres.',NULL,NULL,NULL,0,0,1),
(157,685,45,'Suivi des finances au niveau conseil, lecture budgetaire, vigilance de tresorerie et appui aux arbitrages economiques structurants.',NULL,NULL,NULL,0,0,1),
(158,686,43,'Fournir un socle administratif fiable, simple et lisible pour que l\'organisation puisse agir sans friction et avec de bonnes bases de pilotage.',NULL,NULL,NULL,0,0,1),
(159,686,44,'Tenir a jour les processus, clarifier les responsabilites, fiabiliser les echeances et permettre aux autres cercles de trouver rapidement les informations administratives utiles.',NULL,NULL,NULL,0,0,1),
(160,686,45,'Processus administratifs courants, coordination documentaire, calendrier administratif interne, relation fournisseurs de support et cadre de suivi budgetaire du quotidien.',NULL,NULL,NULL,0,0,1),
(161,686,46,'Stabiliser d\'abord les flux recurrent, puis standardiser ce qui peut l\'etre sans rigidifier les interactions avec les autres cercles.',NULL,NULL,NULL,0,0,1),
(162,687,43,'Renforcer la visibilite de l\'organisation et la qualite de sa presence publique afin d\'attirer les bonnes relations, les bonnes opportunites et une meilleure lisibilite de sa proposition.',1,NULL,NULL,0,0,1),
(163,687,44,'[\"Animer une presence coherente, produire des messages utiles, soutenir les campagnes prioritaires et faire circuler les retours du terrain vers le reste de l\'organisation.\"]',3,'2026-07-24 07:42:21',1,0,0,1),
(164,687,45,'[\"Positionnement public, canaux de communication, campagnes de visibilite, relations de partenariat, calendrier editorial et suivi des retombees.\"]',2,'2026-07-24 07:42:21',1,0,0,1),
(165,687,46,'{\"before\":\"\",\"items\":[16,12],\"after\":\"\"}',4,'2026-07-24 07:42:21',1,0,0,1),
(175,691,43,'Faire avancer le flux operationnel du cercle Administration pour que les besoins internes trouvent une reponse concrete et suivie.',NULL,NULL,NULL,0,0,1),
(176,691,44,'Suivre les demandes en cours, coordonner les actions transversales et reduire les points de friction.',NULL,NULL,NULL,0,0,1),
(177,691,45,'Coordination quotidienne du travail, priorisation des demandes et synchronisation des roles operationnels du cercle.',NULL,NULL,NULL,0,0,1),
(178,692,43,'Prendre en charge les gestes administratifs recurrents afin que les equipes puissent s\'appuyer sur un cadre simple, fiable et accueillant. Ce role fait gagner du temps collectif en rendant les demarches ordinaires plus lisibles, plus fluides et moins dependantes de la memoire individuelle.',NULL,NULL,NULL,0,0,1),
(179,692,44,'Recevoir et traiter les demandes administratives courantes, tenir a jour les repertoires et formulaires utiles, suivre les pieces attendues, relancer avec tact lorsque cela est necessaire et rendre visible l\'etat d\'avancement des demandes afin que personne ne reste bloque sans information.',NULL,NULL,NULL,0,0,1),
(180,692,45,'Gestion des formulaires, suivi des contrats simples, archivage courant, preparation des dossiers administratifs de base, coordination logistique legere, mise a disposition des modeles utiles et maintenance des reperes pratiques dont les autres roles ont besoin pour agir.',NULL,NULL,NULL,0,0,1),
(181,693,43,'Rendre visible la realite economique du quotidien en tenant les comptes, les engagements et les reperes budgetaires de maniere fiable et pedagogique. Ce role permet aux autres roles de prendre de meilleures decisions parce qu\'ils comprennent mieux les consequences economiques de leurs actions.',NULL,NULL,NULL,0,0,1),
(182,693,44,'Saisir et suivre les operations, rapprocher les informations utiles, signaler les ecarts significatifs, preparer des vues budgetaires lisibles, contribuer a la fiabilite des echeances financieres et apporter aux cercles des points de lecture suffisamment clairs pour soutenir les arbitrages du quotidien.',NULL,NULL,NULL,0,0,1),
(183,693,45,'Suivi budgetaire courant, enregistrement comptable, echeancier de paiements, suivi de facturation, justification des depenses, collecte des pieces utiles, mise a jour des tableaux de bord economiques et coordination avec les roles qui engagent des moyens financiers au nom de l\'organisation.',NULL,NULL,NULL,0,0,1),
(184,694,43,'Fluidifier le quotidien des membres en apportant une aide pratique sur les outils, les demandes internes et les petits blocages organisationnels. Ce role existe pour que les irritants du terrain trouvent rapidement une reponse simple au lieu de ralentir inutilement le travail collectif.',NULL,NULL,NULL,0,0,1),
(185,694,44,'Recevoir les sollicitations, qualifier rapidement le besoin, orienter vers la bonne ressource, documenter les resolutions utiles, assurer un suivi minimum des demandes ouvertes et contribuer a un climat de service interne ou chacun sait a qui s\'adresser et sous quel delai attendre une premiere reponse.',NULL,NULL,NULL,0,0,1),
(186,694,45,'Support de premier niveau, coordination des demandes internes, orientation vers les bons interlocuteurs, centralisation des questions recurrentes, maintenance d\'une base de resolutions utiles, appui ponctuel a l\'onboarding et accompagnement pratique sur les outils ou procedures du quotidien.',NULL,NULL,NULL,0,0,1),
(196,698,43,'Transformer les intentions marketing en execution suivie, de sorte que les actions de visibilite avancent avec regularite.',NULL,NULL,NULL,0,0,1),
(197,698,44,'Coordonner la production, suivre les echeances et rendre visibles les blocages.',NULL,NULL,NULL,0,0,1),
(198,698,45,'Coordination quotidienne, suivi d\'execution et gestion du flux des actions marketing en cours.',NULL,NULL,NULL,0,0,1),
(199,699,43,'Faire vivre la presence numerique de l\'organisation avec des messages reguliers, lisibles et alignes avec son identite. Ce role transforme l\'activite reelle du terrain en signaux publics comprehensibles, de sorte que l\'organisation soit visible sans perdre sa coherence ni son ton propre.',NULL,NULL,NULL,0,0,1),
(200,699,44,'Planifier et publier les contenus, suivre les performances utiles, adapter les formats aux canaux, remonter les apprentissages au cercle Marketing et maintenir une presence suffisamment reguliere pour que les publics percoivent la continuite de l\'action menee par l\'organisation.',NULL,NULL,NULL,0,0,1),
(201,699,45,'Animation des canaux digitaux, publication, optimisation des formats, lecture des retours quantitatifs, adaptation des calendriers de diffusion, coordination des assets numeriques et gestion du cycle de vie des publications sur les canaux prioritaires.',NULL,NULL,NULL,0,0,1),
(202,700,43,'Developper des relations externes de qualite pour etendre la portee de l\'organisation, faire emerger des cooperations utiles et multiplier les relais de confiance autour de ses actions. Ce role contribue a rendre l\'organisation plus visible en travaillant d\'abord la qualite des liens.',NULL,NULL,NULL,0,0,1),
(203,700,44,'Identifier les partenaires pertinents, entretenir les relations existantes, preparer les prises de contact, suivre les suites donnees aux echanges, faire circuler les opportunites utiles vers les autres roles et contribuer a une presence externe plus coherente et mieux coordonnee.',NULL,NULL,NULL,0,0,1),
(204,700,45,'Cartographie de partenaires, prises de contact, suivi des relations, coordination d\'actions communes, veille sur les opportunites de collaboration, preparation de rendez-vous, maintenance des reperes relationnels et valorisation des occasions de visibilite partagee.',NULL,NULL,NULL,0,0,1),
(205,701,43,'Produire des contenus qui rendent l\'organisation comprehensible, desirable et credible, tout en soutenant ses temps forts et ses besoins de communication. Ce role aide a transformer les intentions du cercle en messages concrets, portables et reutilisables sur plusieurs supports.',NULL,NULL,NULL,0,0,1),
(206,701,44,'Concevoir le calendrier editorial, preparer les textes et assets necessaires, soutenir les campagnes prioritaires, coordonner la collecte de matiere premiere aupres des autres roles et maintenir une qualite narrative constante dans les contenus produits.',NULL,NULL,NULL,0,0,1),
(207,701,45,'Production de contenus, coordination editoriale, preparation des campagnes, maintenance des messages de reference, suivi du calendrier de diffusion, adaptation de la forme aux differents supports et capitalisation des contenus reutilisables pour les communications futures.',NULL,NULL,NULL,0,0,1),
(208,692,46,'Stabiliser d\'abord les demandes les plus frequentes, puis documenter les cas types afin de reduire progressivement la charge mentale administrative sur l\'ensemble des autres roles.',NULL,NULL,NULL,0,0,1),
(209,693,46,'Passer d\'une logique de simple tenue des comptes a une logique d\'aide a la decision economique, avec des vues plus lisibles et un rythme de reporting adapte aux besoins reels des cercles.',NULL,NULL,NULL,0,0,1),
(210,694,46,'Construire une base de support suffisamment robuste pour absorber les demandes courantes rapidement, puis identifier les irritants repetitifs a traiter a la racine.',NULL,NULL,NULL,0,0,1),
(211,699,46,'Chercher d\'abord la regularite et la coherence editoriale avant la sophistication des formats, afin que la presence digitale gagne en credibilite et en lisibilite dans la duree.',NULL,NULL,NULL,0,0,1),
(212,700,46,'Consolider quelques partenariats a forte valeur relationnelle avant d\'elargir le reseau, afin de faire de chaque lien externe un point d\'appui concret pour la visibilite de l\'organisation.',NULL,NULL,NULL,0,0,1),
(213,701,46,'Capitaliser sur les contenus qui expliquent le mieux la proposition de valeur de l\'organisation, puis decliner cette matiere en campagnes, formats courts et supports reutilisables.',NULL,NULL,NULL,0,0,1),
(214,708,43,'Accueillir les nouveaux arrivants au sein de l\'organisation',1,'2026-07-23 15:51:27',1,0,0,1),
(215,711,47,NULL,1,NULL,NULL,0,0,1),
(216,711,48,NULL,2,NULL,NULL,0,0,1),
(217,711,49,NULL,3,NULL,NULL,0,0,1),
(218,711,50,NULL,4,NULL,NULL,0,0,1),
(219,713,47,'Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.',4,'2026-07-24 16:37:17',1,0,0,1),
(220,713,49,'Domaine du rôle facilitation',3,'2026-07-24 16:37:17',1,0,0,1),
(221,713,48,'fdsfsdfdsfds',1,'2026-07-24 16:37:17',1,0,0,1),
(222,714,47,'S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.',1,'2026-07-24 16:37:17',1,0,0,0),
(223,715,47,'S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.',1,'2026-07-24 16:37:17',1,0,0,0),
(231,710,51,NULL,1,NULL,NULL,0,0,1),
(232,714,51,'S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.',1,'2026-07-24 16:56:02',1,0,1,1),
(233,715,51,'S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.',1,'2026-07-24 16:56:43',1,0,0,1),
(234,713,51,NULL,2,NULL,NULL,0,0,1);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
INSERT INTO `mission` VALUES
(1,'Introduction','Bienvenue dans le parcours','https://video.example.test/module-introduction','<div class=\"module-intro\">\r\n\r\n  <p><strong>Bonjour, et bienvenue.</strong></p>\r\n\r\n  <p>\r\n    Vous arrivez dans une organisation qui fonctionne selon un mode de gouvernance \r\n    que vous ne connaissez peut-être pas… ou peu.<br>\r\n    Un mode de fonctionnement différent, parfois déroutant au premier abord.\r\n  </p>\r\n\r\n  <p>\r\n    Qu’on l’appelle gouvernance partagée, gouvernance horizontale… peu importe le terme.<br>\r\n    Vous vous posez sans doute des questions.<br>\r\n    Et c’est normal.\r\n  </p>\r\n\r\n  <p>\r\n    Alors si vous avez envie de mieux comprendre de quoi il s’agit, \r\n    vous êtes exactement au bon endroit.\r\n  </p>\r\n\r\n  <p>\r\n    Dans ce module, nous allons découvrir ensemble ce que cela signifie, concrètement.\r\n  </p>\r\n\r\n  <p>\r\n    Mais avant d’entrer dans les outils, les méthodes ou les pratiques,<br>\r\n    nous allons prendre un temps pour explorer quelque chose de plus fondamental :\r\n  </p>\r\n\r\n  <p><strong>la philosophie qui se cache derrière cette démarche.</strong></p>\r\n\r\n  <p>\r\n    Car trop souvent, on aborde ces nouvelles formes de gouvernance à travers les outils \r\n    qu’elles proposent.<br>\r\n    Alors qu’en réalité… ces outils ne sont qu’un support.\r\n  </p>\r\n\r\n  <p>\r\n    Ils viennent soutenir une posture.<br>\r\n    Une manière d’être.<br>\r\n    Une façon de travailler ensemble… profondément différente.\r\n  </p>\r\n\r\n  <p>\r\n    Dans ce module, nous allons donc nous intéresser à ce qui se trouve en dessous.\r\n  </p>\r\n\r\n  <p>\r\n    Les valeurs qui fondent ces pratiques.<br>\r\n    Celles qui traversent — ou devraient traverser — l’ensemble de l’organisation.\r\n  </p>\r\n\r\n  <p>\r\n    Nous parlerons de confiance, de coopération, de responsabilité…<br>\r\n    mais aussi de souveraineté.\r\n  </p>\r\n\r\n  <p>\r\n    Vous verrez que ces valeurs ne sont pas seulement des intentions.<br>\r\n    Elles sont au cœur du fonctionnement des outils que vous allez découvrir.\r\n  </p>\r\n\r\n  <p>\r\n    Et plus encore : elles constituent un objectif en soi.\r\n  </p>\r\n\r\n  <p>\r\n    Nous verrons aussi comment ces valeurs soutiennent la capacité de l’organisation \r\n    à remplir sa mission.\r\n  </p>\r\n\r\n  <p>\r\n    Enfin, nous aborderons les grands principes qui structurent cette gouvernance.<br>\r\n    Des principes essentiels, qui permettent de garder une cohérence entre ce qui est affiché…<br>\r\n    et ce qui est réellement vécu au quotidien.\r\n  </p>\r\n\r\n  <p>\r\n    Alors si, avant de vous plonger dans les outils,<br>\r\n    vous souhaitez comprendre pourquoi on cherche à faire différemment…<br>\r\n    et à quoi tout cela sert…\r\n  </p>\r\n\r\n  <p><strong>Encore une fois, vous êtes au bon endroit.</strong></p>\r\n\r\n  <p><strong>Bienvenue dans ce premier module de formation.</strong></p>\r\n\r\n</div>',1,'2026-04-03 10:40:41','2026-04-07 08:28:13'),
(2,'Bases de l’inclusion','Comprendre les fondamentaux','https://video.example.test/module-inclusion','<h1>Bases</h1>',2,'2026-04-03 10:40:41','2026-04-04 01:55:23'),
(3,'Tronc commun','Concepts clés à maîtriser','https://video.example.test/module-tronc-commun','<h1>Tronc commun</h1>',3,'2026-04-03 10:40:41','2026-04-04 01:55:43'),
(4,'Branche A - Cercles','Comprendre les cercles','https://video.example.test/module-cercles','<h1>Cercles</h1>',4,'2026-04-03 10:40:41','2026-04-04 01:56:21'),
(5,'Branche B - Rôles','Comprendre les rôles',NULL,'<h1>Rôles</h1>',5,'2026-04-03 10:40:41',NULL),
(6,'Branche C - Réunions','Comprendre les réunions',NULL,'<h1>Réunions</h1>',6,'2026-04-03 10:40:41',NULL),
(7,'Synthèse','Mettre ensemble les apprentissages',NULL,'<h1>Synthèse</h1>',7,'2026-04-03 10:40:41',NULL),
(8,'Conclusion','Clôture du parcours',NULL,'<h1>Conclusion</h1>',8,'2026-04-03 10:40:41',NULL),
(101,'Introduction test','Découvrir le parcours test',NULL,'<p>Bienvenue dans ce parcours test.</p>',1,'2026-04-04 11:47:52',NULL),
(102,'Étape 1','Première étape','https://video.example.test/module-introduction','<p>Contenu étape 1</p>',2,'2026-04-04 11:47:52','2026-04-12 20:21:12'),
(103,'Étape 2','Deuxième étape',NULL,'<p>Contenu étape 2</p>',3,'2026-04-04 11:47:52',NULL),
(104,'Bonus','Mission bonus',NULL,'<p>Contenu bonus</p>',4,'2026-04-04 11:47:52',NULL);
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
INSERT INTO `mission_dependencies` VALUES
(1,1,2,1,1),
(2,2,3,1,1),
(3,3,4,1,1),
(4,3,5,1,1),
(5,3,6,1,1),
(6,4,7,1,1),
(7,5,7,1,1),
(8,6,7,1,1),
(9,7,8,1,1),
(10,101,102,2,1),
(11,102,103,2,1),
(12,103,104,2,0);
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
INSERT INTO `mission_faq` VALUES
(1,102,1,NULL),
(2,102,2,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `object_visibility`
--

LOCK TABLES `object_visibility` WRITE;
/*!40000 ALTER TABLE `object_visibility` DISABLE KEYS */;
INSERT INTO `object_visibility` VALUES
(2,1,'document',2304,1,'organization',NULL,1,'2026-07-24 08:31:39','2026-07-24 08:31:39'),
(3,1,'document_edit',2304,1,'self',NULL,1,'2026-07-24 08:31:39','2026-07-24 08:31:39'),
(4,1,'document',2305,1,'organization',NULL,1,'2026-07-24 08:37:27','2026-07-24 08:37:27'),
(5,1,'document_edit',2305,1,'self',NULL,1,'2026-07-24 08:37:27','2026-07-24 08:37:27');
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
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization`
--

LOCK TABLES `organization` WRITE;
/*!40000 ALTER TABLE `organization` DISABLE KEYS */;
INSERT INTO `organization` (`id`,`name`,`shortname`,`domain`,`logo`,`banner`,`color`,`latlong`,`parameters`,`datecreation`) VALUES
(1,'OpenMyOrganization','org1','org1.opengov.tools','/img/org1-logo.png','/img/org1-banner.png','#0f766e','46.204391;6.143158',NULL,'2026-04-01 00:00:00'),
(2,'Exemple de modèle','org2','org2.opengov.tools','/img/org2-logo.png','/img/org2-banner.png','#984ea2','46.519653;6.632273',NULL,'2026-04-01 00:00:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization_application`
--

LOCK TABLES `organization_application` WRITE;
/*!40000 ALTER TABLE `organization_application` DISABLE KEYS */;
INSERT INTO `organization_application` VALUES
(1,1,1,3,1,NULL),
(2,2,1,10,1,NULL),
(3,1,2,4,1,'{\"importanceCalculationVersion\":3,\"importanceCalculation\":{\"parentWeight\":0.9,\"depthPenalty\":0.15}}'),
(4,2,2,20,1,'{\"importanceCalculation\":{\"parentWeight\":0.7,\"depthPenalty\":0.15},\"importanceCalculationVersion\":3}'),
(5,1,3,5,0,NULL),
(6,2,3,30,1,NULL),
(7,1,4,6,1,NULL),
(8,2,4,40,1,NULL),
(9,1,5,7,1,NULL),
(10,2,5,50,1,NULL),
(11,1,6,8,1,NULL),
(12,2,6,60,1,NULL),
(16,1,7,1,1,NULL),
(17,1,8,2,1,NULL),
(18,1,9,9,1,NULL),
(19,2,9,65,1,NULL),
(20,2,7,8,1,NULL),
(21,2,8,9,1,NULL);
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
INSERT INTO `organization_parcours` VALUES
(1,2,1,NULL,1,0),
(2,2,2,NULL,1,0),
(3,1,1,2,1,0),
(4,1,2,1,1,0),
(5,1,3,3,1,0);
/*!40000 ALTER TABLE `organization_parcours` ENABLE KEYS */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcours`
--

LOCK TABLES `parcours` WRITE;
/*!40000 ALTER TABLE `parcours` DISABLE KEYS */;
INSERT INTO `parcours` VALUES
(1,'Les clés de la Gouvernance Partagée','Découvrez les bases nécessaires pour coopérer avec confiance et efficacité dans la gouvernance de votre organisation.','/img/fondamentaux.png',1,1,1,'2026-07-23 11:51:17','2026-07-23 11:51:17',1,0),
(2,'Intention et objectifs de la Gouvernance Partagée','Découvrez l\'intention derrière la mise en place des outils de la gouvernance partagée et comment celle-ci impacte sur la dynamique coopérative.','/img/uploads/parcours/orientation.png',1,1,1,'2026-07-23 11:51:17','2026-07-23 11:51:17',1,0),
(3,'Mieux communiquer au sein des équipes et des organistaions','Découvrez comment mieux communiquer au sein de vos équipes, et comment donner du feedback à vos collègues.','/img/uploads/parcours/communication.png',1,1,1,'2026-07-23 11:51:17','2026-07-23 11:51:17',1,0);
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
INSERT INTO `parcours_mission` VALUES
(1,1,1,1,NULL),
(2,1,2,1,NULL),
(3,1,3,1,NULL),
(4,1,4,1,'Branche 1'),
(5,1,5,1,'Branche 2'),
(6,1,6,1,'Branche 2'),
(7,1,7,1,NULL),
(8,1,8,1,NULL),
(9,2,101,1,NULL),
(10,2,102,1,NULL),
(11,2,103,1,NULL),
(12,2,104,0,'bonus'),
(13,3,101,1,NULL);
/*!40000 ALTER TABLE `parcours_mission` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(24,'CAN_DELETE_PROJECT','Supprimer des projets','Autorise la suppression de projets dans le contexte cible.',1,'2026-08-07 00:00:00','2026-08-07 00:00:00');
/*!40000 ALTER TABLE `permission` ENABLE KEYS */;
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
  `IDproject_parent` int(11) DEFAULT NULL,
  `IDdocument_journal` int(11) DEFAULT NULL,
  `project_kind` varchar(30) NOT NULL DEFAULT 'standard',
  `IDproject_template` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'someday',
  `planned_start_date` date DEFAULT NULL,
  `planned_end_date` date DEFAULT NULL,
  `priority` tinyint(3) DEFAULT NULL,
  `importance` tinyint(3) DEFAULT NULL,
  `calculated_importance` decimal(10,8) NOT NULL DEFAULT 0.00000000,
  `project_size` varchar(3) NOT NULL DEFAULT 'M',
  `capture_mode` varchar(30) NOT NULL DEFAULT 'multiple_documents',
  `active` tinyint(1) NOT NULL DEFAULT 1,
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
  CONSTRAINT `fk_project_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_project_journal` FOREIGN KEY (`IDdocument_journal`) REFERENCES `document` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_project_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_parent` FOREIGN KEY (`IDproject_parent`) REFERENCES `project` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_project_template` FOREIGN KEY (`IDproject_template`) REFERENCES `project` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_project_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project`
--

LOCK TABLES `project` WRITE;
/*!40000 ALTER TABLE `project` DISABLE KEYS */;
INSERT INTO `project` VALUES
(1,1,708,NULL,NULL,NULL,'checklist_template',NULL,'Processus d\'accueil des nouveaux et nouvelles','Ensemble des étapes à suivre pour accueillir une nouvelle personne au sein de l\'organisation.','someday',NULL,NULL,NULL,NULL,0.00000000,'M','multiple_documents',1,'2026-07-23 15:52:32','2026-07-24 09:32:36'),
(2,1,708,NULL,1,NULL,'checklist_template',NULL,'Créer l\'adresse e-mail du nouveau venu',NULL,'someday',NULL,NULL,4,NULL,0.00000000,'S','multiple_documents',1,'2026-07-23 15:53:45','2026-07-23 16:07:10'),
(3,1,708,NULL,1,NULL,'checklist_template',NULL,'Un restaurant est réservé pour le premier repas de midi',NULL,'someday',NULL,NULL,3,NULL,0.00000000,'S','multiple_documents',1,'2026-07-23 16:03:57','2026-07-23 16:03:57'),
(6,1,708,NULL,1,NULL,'checklist_template',NULL,'Un dîner est organisé avec le nouvel arrivant',NULL,'someday',NULL,NULL,3,4,0.00000000,'S','multiple_documents',1,'2026-07-23 16:07:16','2026-07-23 16:08:07'),
(7,1,678,1,NULL,NULL,'standard',NULL,'Refondre notre communication et notre marketing',NULL,'in_progress','2026-01-01','2026-12-31',1,5,0.86070798,'M','multiple_documents',1,'2026-07-24 07:19:44','2026-07-24 13:03:44'),
(8,1,678,1,NULL,NULL,'standard',NULL,'Elargir notre réseau professionnel',NULL,'in_progress','2026-01-01','2026-12-31',2,5,0.86070798,'M','multiple_documents',1,'2026-07-24 07:20:48','2026-07-24 10:45:47'),
(9,1,678,1,NULL,NULL,'standard',NULL,'Consolider nos pratiques administratives',NULL,'in_progress','2026-01-01','2026-12-31',2,5,0.86070798,'M','multiple_documents',1,'2026-07-24 07:21:59','2026-07-24 10:53:06'),
(10,1,693,NULL,9,NULL,'standard',NULL,'Identifier un logiciel de compta professionnel',NULL,'ready',NULL,NULL,3,3,0.81520577,'M','multiple_documents',1,'2026-07-24 07:30:44','2026-07-24 12:22:08'),
(11,1,692,1,NULL,NULL,'standard',NULL,'Créer une checklist avec les charges administratives récurrentes',NULL,'review',NULL,'2026-06-30',3,2,0.15940704,'M','multiple_documents',1,'2026-07-24 07:32:37','2026-07-24 10:45:47'),
(12,1,687,NULL,7,NULL,'standard',NULL,'Refondre notre site Internet',NULL,'in_progress',NULL,NULL,5,5,0.86070798,'M','multiple_documents',1,'2026-07-24 07:34:36','2026-07-24 12:22:08'),
(13,1,699,NULL,12,NULL,'standard',NULL,'Les maquettes du nouveau site sont évaluées par un panel représentatif de nos utilisateurs.',NULL,'ready',NULL,NULL,NULL,NULL,0.86070798,'M','multiple_documents',1,'2026-07-24 07:35:38','2026-07-24 12:22:08'),
(14,1,699,NULL,12,NULL,'standard',NULL,'La refonte du nouveau site est confiée à un prestataire.',NULL,'blocked',NULL,NULL,NULL,NULL,0.86070798,'M','multiple_documents',1,'2026-07-24 07:36:41','2026-07-24 12:22:08'),
(15,1,699,1,14,NULL,'standard',NULL,'Un prestataire est identifié',NULL,'done',NULL,NULL,NULL,NULL,0.86070798,'M','multiple_documents',1,'2026-07-24 07:37:57','2026-07-24 12:22:08'),
(16,1,687,NULL,7,NULL,'standard',NULL,'Moderniser notre charte graphique',NULL,'done',NULL,NULL,5,5,0.86070798,'M','multiple_documents',1,'2026-07-24 07:41:01','2026-07-24 12:22:08'),
(17,1,686,NULL,14,NULL,'standard',NULL,'Le budget disponible pour le site est défini',NULL,'ready',NULL,NULL,1,5,0.86070798,'M','multiple_documents',1,'2026-07-24 07:52:44','2026-07-24 14:24:23'),
(18,1,692,NULL,NULL,NULL,'checklist_template',NULL,'Factures récurrentes',NULL,'someday',NULL,NULL,NULL,NULL,0.00000000,'M','multiple_documents',1,'2026-07-24 09:20:19','2026-07-24 09:22:38'),
(19,1,692,NULL,18,NULL,'checklist_template',NULL,'Payer la TVA',NULL,'someday',NULL,NULL,5,3,0.00000000,'M','multiple_documents',1,'2026-07-24 09:21:09','2026-07-24 09:21:09'),
(20,1,692,NULL,18,NULL,'checklist_template',NULL,'Clôturer la compta',NULL,'someday',NULL,NULL,4,4,0.00000000,'XL','multiple_documents',1,'2026-07-24 09:21:48','2026-07-24 09:21:48'),
(21,1,692,NULL,18,NULL,'checklist_template',NULL,'Payer les salaires',NULL,'someday',NULL,NULL,4,4,0.00000000,'M','multiple_documents',1,'2026-07-24 09:22:31','2026-07-24 09:22:31'),
(22,1,692,NULL,NULL,NULL,'standard',21,'Payer les salaires - 25 juillet 2026',NULL,'ready','2026-07-25','2026-07-28',4,4,0.47822111,'M','multiple_documents',1,'2026-07-24 09:22:43','2026-07-24 10:45:47'),
(23,1,708,NULL,NULL,NULL,'standard',1,'Processus d\'accueil de Jean-Claude','Ensemble des étapes à suivre pour accueillir une nouvelle personne au sein de l\'organisation.','ready','2026-08-01',NULL,NULL,NULL,0.00000000,'M','multiple_documents',1,'2026-07-24 09:33:08','2026-07-24 12:01:33'),
(24,1,708,NULL,23,NULL,'standard',6,'Un dîner est organisé avec le nouvel arrivant',NULL,'ready','2026-08-01','2026-08-04',3,4,0.75000000,'S','multiple_documents',1,'2026-07-24 09:33:08','2026-07-24 12:22:08'),
(25,1,708,NULL,23,NULL,'standard',2,'Créer l\'adresse e-mail du nouveau venu',NULL,'ready','2026-07-27',NULL,4,NULL,0.00000000,'S','multiple_documents',1,'2026-07-28 10:32:27','2026-07-28 10:32:27');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_document`
--

LOCK TABLES `project_document` WRITE;
/*!40000 ALTER TABLE `project_document` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_document` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Propriétés assignées à des tempales (holons)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property`
--

LOCK TABLES `property` WRITE;
/*!40000 ALTER TABLE `property` DISABLE KEYS */;
INSERT INTO `property` VALUES
(1,'rde','Raison d\'être',1,NULL,NULL,1,'2024-03-05 16:59:41',NULL,1),
(2,'redevability','Attendus',1,NULL,NULL,1,'2024-03-05 16:59:41',NULL,1),
(3,'domain','Domaines d\'autorité',1,NULL,NULL,1,'2024-03-05 16:59:50',NULL,1),
(4,'strat','Stratégie',1,NULL,NULL,1,'2024-12-03 13:09:48',NULL,1),
(43,'raison_d_etre','Raison d\'etre',1,NULL,NULL,674,'2026-04-19 16:46:34',1,1),
(44,'attendus','Attendus',2,'text',NULL,674,'2026-04-19 16:46:34',3,1),
(45,'domaines_d_autorite','Domaines d\'autorite',2,'authority',NULL,674,'2026-04-19 16:46:34',2,1),
(46,'strategie','Strategie',7,'project',NULL,674,'2026-04-19 16:46:34',4,1),
(47,'raison_d_etre','Raison d\'être',1,NULL,NULL,709,'2026-07-24 14:37:17',4,1),
(48,'attendus','Attendus',1,NULL,NULL,709,'2026-07-24 14:37:17',1,1),
(49,'domaines_d_autorite','Domaines d\'autorité',1,NULL,NULL,709,'2026-07-24 14:37:17',3,1),
(50,'strategie','Stratégie',1,NULL,NULL,709,'2026-07-24 14:37:17',4,1),
(51,'raison_d_etre','Raison d\'être',1,NULL,NULL,709,'2026-07-24 14:54:54',1,1);
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
INSERT INTO `pv` VALUES
(1,'Data Test',1,'2026-04-21 12:10:00','2026-04-21 12:10:00','','');
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
INSERT INTO `qr` VALUES
(1,'WebSite_Home','https://org1.opengov.tools/omo/',1,'org1','Portail Org1',0,NULL,'2026-04-21 12:00:00',1);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource_invitation`
--

LOCK TABLES `resource_invitation` WRITE;
/*!40000 ALTER TABLE `resource_invitation` DISABLE KEYS */;
INSERT INTO `resource_invitation` VALUES
(1,'event',3,693,NULL,NULL,'Comptabilite et budget','holon','invited',NULL,'[]',1,'2026-07-24 06:31:39','2026-07-24 06:31:39'),
(2,'event',4,678,NULL,NULL,'Ancrage','holon','invited',NULL,'[]',1,'2026-07-24 06:37:27','2026-07-24 06:37:27');
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
  `scope` varchar(20) NOT NULL DEFAULT 'local',
  `title` varchar(255) NOT NULL,
  `intention` mediumtext NOT NULL,
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
  CONSTRAINT `fk_rule_authority` FOREIGN KEY (`IDauthority`) REFERENCES `authority` (`id`),
  CONSTRAINT `fk_rule_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rule_user_creation` FOREIGN KEY (`IDuser_creation`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rule_user_modification` FOREIGN KEY (`IDuser_modification`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_rule_source` CHECK (`IDauthority` is null <> (`IDholon` is null)),
  CONSTRAINT `chk_rule_scope` CHECK (`scope` in ('global','descendants','local'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
('2026-07-28-03-holon-admin-parent-and-inheritance.sql','0c1b53be2bc5c921125d8ff1e1cd2ac122d59c3e0ba3f900a108bc29741246f','2026-07-28 08:32:05');
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
  `name` varchar(190) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `source_url` varchar(2000) DEFAULT NULL,
  `reference_type` varchar(20) NOT NULL DEFAULT 'none',
  `measurement_frequency` varchar(20) DEFAULT NULL,
  `measurement_schedule` varchar(20) DEFAULT NULL,
  `chart_min_value` decimal(20,6) DEFAULT NULL,
  `show_cumulative` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stat_indicator_organization` (`IDorganization`),
  KEY `idx_stat_indicator_holon` (`IDholon`),
  KEY `idx_stat_indicator_user` (`IDuser`),
  KEY `idx_stat_indicator_active` (`active`),
  KEY `idx_stat_indicator_measurement_frequency` (`measurement_frequency`),
  CONSTRAINT `fk_stat_indicator_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stat_indicator_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stat_indicator_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stat_indicator`
--

LOCK TABLES `stat_indicator` WRITE;
/*!40000 ALTER TABLE `stat_indicator` DISABLE KEYS */;
INSERT INTO `stat_indicator` VALUES
(1,1,693,1,'Solde du compte bancaire','Cash disponible sur le compte bancaire','https://bas.ch','none','monthly','1',60000.000000,0,1,'2026-07-23 16:11:14','2026-07-24 08:45:24'),
(2,1,693,1,'Solde en caisse','Montant disponible en liquide dans la caisse',NULL,'none','monthly','1',0.000000,0,1,'2026-07-23 16:15:03','2026-07-24 08:44:54'),
(3,1,693,1,'Chiffre d\'affaire',NULL,NULL,'objective','monthly','1',NULL,0,1,'2026-07-25 08:55:58','2026-07-25 08:56:12');
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
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stat_indicator_group_context` (`IDorganization`,`IDholon`,`active`),
  KEY `idx_stat_indicator_group_user` (`IDuser`),
  KEY `fk_stat_indicator_group_holon` (`IDholon`),
  CONSTRAINT `fk_stat_indicator_group_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stat_indicator_group_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stat_indicator_group_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stat_indicator_group`
--

LOCK TABLES `stat_indicator_group` WRITE;
/*!40000 ALTER TABLE `stat_indicator_group` DISABLE KEYS */;
INSERT INTO `stat_indicator_group` VALUES
(1,1,678,1,'Liquidités','sum','none',0.000000,1,'2026-07-23 14:17:05','2026-07-23 14:17:05');
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stat_indicator_group_item`
--

LOCK TABLES `stat_indicator_group_item` WRITE;
/*!40000 ALTER TABLE `stat_indicator_group_item` DISABLE KEYS */;
INSERT INTO `stat_indicator_group_item` VALUES
(11,1,1,1,'2026-07-24 06:44:10'),
(12,1,2,2,'2026-07-24 06:44:10');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stat_indicator_reference_point`
--

LOCK TABLES `stat_indicator_reference_point` WRITE;
/*!40000 ALTER TABLE `stat_indicator_reference_point` DISABLE KEYS */;
INSERT INTO `stat_indicator_reference_point` VALUES
(9,3,NULL,0.0000,0.000000,'2026-01-01 08:54:00','2026-07-25 08:56:12','2026-07-25 08:56:12'),
(10,3,NULL,100.0000,230000.000000,'2026-12-31 08:54:00','2026-07-25 08:56:12','2026-07-25 08:56:12');
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stat_indicator_value`
--

LOCK TABLES `stat_indicator_value` WRITE;
/*!40000 ALTER TABLE `stat_indicator_value` DISABLE KEYS */;
INSERT INTO `stat_indicator_value` VALUES
(1,1,1,82345.000000,'2026-07-01 16:11:00','2026-07-23 16:12:41','2026-07-23 16:12:41'),
(2,1,1,83498.000000,'2026-06-01 16:12:00','2026-07-23 16:12:55','2026-07-23 16:12:55'),
(4,2,1,536.000000,'2026-07-01 16:15:00','2026-07-23 16:15:36','2026-07-23 16:15:36'),
(5,2,1,329.000000,'2026-06-02 16:15:00','2026-07-23 16:15:57','2026-07-23 16:15:57'),
(6,2,1,488.000000,'2026-05-01 16:15:00','2026-07-23 16:16:27','2026-07-23 16:16:27'),
(7,1,1,82691.000000,'2026-05-01 16:19:00','2026-07-23 16:19:58','2026-07-23 16:19:58'),
(8,3,1,156000.000000,'2026-07-02 08:56:00','2026-07-25 08:56:29','2026-07-25 08:56:29'),
(9,3,1,123000.000000,'2026-06-01 08:56:00','2026-07-25 08:56:45','2026-07-25 08:56:45'),
(10,3,1,101345.000000,'2026-05-01 08:56:00','2026-07-25 08:57:02','2026-07-25 08:57:02'),
(11,3,1,67567.000000,'2026-04-01 08:57:00','2026-07-25 08:57:23','2026-07-25 08:57:23'),
(12,3,1,32987.000000,'2026-03-01 08:57:00','2026-07-25 08:57:37','2026-07-25 08:57:37'),
(13,3,1,0.000000,'2026-01-01 08:57:00','2026-07-25 08:57:57','2026-07-25 08:57:57'),
(14,3,1,567.000000,'2026-02-01 08:57:00','2026-07-25 08:58:07','2026-07-25 08:58:07');
/*!40000 ALTER TABLE `stat_indicator_value` ENABLE KEYS */;
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
INSERT INTO `tension` VALUES
(9301,1,687,1,'Suivi budget','Besoin de clarifier la projection budgetaire du prochain trimestre et les arbitrages a venir.','2026-07-09 08:40:00','2026-07-09 08:40:00',1),
(9302,1,686,1,'Charge equipe','Question ouverte sur la charge de travail actuelle et la repartition entre marketing et administration.','2026-07-09 08:45:00','2026-07-09 08:45:00',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation_bundle_refresh_jobs`
--

LOCK TABLES `translation_bundle_refresh_jobs` WRITE;
/*!40000 ALTER TABLE `translation_bundle_refresh_jobs` DISABLE KEYS */;
INSERT INTO `translation_bundle_refresh_jobs` VALUES
(1,'omo_index_page','en','99f2a401887e9cdb2832728667434150979e96a5dec67b7f0e6e6fd29c708d41','{\"app.access_denied.message\":{\"context\":\"Message shown on the forbidden access page when the user is logged in but has no access to the organization.\",\"text\":\"Votre compte est bien connecté, mais il n\'a pas encore accès à l\'organisation {organizationName}.\"},\"app.access_denied.organization_fallback\":{\"context\":\"Fallback organization name used on the forbidden access page when the organization name is missing.\",\"text\":\"demandee\"},\"app.access_denied.page_description\":{\"context\":\"Additional explanation shown on the forbidden access page.\",\"text\":\"Pour le moment, l\'accès à cet espace est réservé aux personnes présentes dans la liste des membres autorisés.\"},\"app.access_denied.page_heading\":{\"context\":\"Main heading shown on the forbidden access page.\",\"text\":\"Accès interdit\"},\"app.access_denied.page_title\":{\"context\":\"Browser title shown on the forbidden access page.\",\"text\":\"Accès interdit - OMO\"},\"app.access_denied.request_action\":{\"context\":\"Button label shown on the forbidden access page to let the current user request access to the organization.\",\"text\":\"Demander l\'accès\"},\"app.access_denied.request_modal_title\":{\"context\":\"Modal title shown for the organization access request popup.\",\"text\":\"Demander l\'accès à l\'organisation\"},\"app.access_denied.request_pending\":{\"context\":\"Disabled button label shown when a membership request is already pending for the current user.\",\"text\":\"Demande déjà envoyée\"},\"app.access_denied.request_pending_notice\":{\"context\":\"Notice shown on the forbidden access page when the current user already requested access.\",\"text\":\"Une demande est déjà en attente auprès des administrateurs de cette organisation.\"},\"app.directory.create.action\":{\"context\":\"Action label displayed on the create organization card.\",\"text\":\"Ouvrir le formulaire\"},\"app.directory.create.aria_label\":{\"context\":\"Aria label for the create organization card button.\",\"text\":\"Créer une nouvelle organisation\"},\"app.directory.create.badge\":{\"context\":\"Badge shown on the create organization card.\",\"text\":\"Nouveau\"},\"app.directory.create.description\":{\"context\":\"Subtitle shown on the create organization card.\",\"text\":\"Nom, domaine, logo, bannière, couleur\"},\"app.directory.create.modal_title\":{\"context\":\"Title shown in the create organization modal and iframe title.\",\"text\":\"Créer une nouvelle organisation\"},\"app.directory.create.title\":{\"context\":\"Title shown on the create organization card.\",\"text\":\"Créer une nouvelle organisation\"},\"app.directory.cta.connect\":{\"context\":\"Action label shown on an organization card to enter its workspace.\",\"text\":\"Se connecter\"},\"app.directory.cta.view_invitation\":{\"context\":\"Action label shown on a pending invitation card to open the invitation instead of the organization workspace.\",\"text\":\"Voir l\'invitation\"},\"app.directory.description.empty\":{\"context\":\"Message shown on the organization directory when the user has no accessible organizations.\",\"text\":\"Votre compte est bien connecté, mais il n\'est rattaché à aucune organisation pour le moment. Vous pouvez en créer une nouvelle ci-dessous.\"},\"app.directory.description.empty.patreon_connect\":{\"context\":\"Message shown on the organization directory when no organization is available and Patreon must be connected before creating one.\",\"text\":\"Votre compte est bien connecté, mais il n\'est rattaché à aucune organisation pour le moment. Connectez Patreon ci-dessous pour pouvoir en créer une nouvelle.\"},\"app.directory.description.with_results\":{\"context\":\"Message shown on the organization directory when organizations are available.\",\"text\":\"Choisissez l\'organisation que vous souhaitez ouvrir. Chaque carte vous redirige vers son espace dédié.\"},\"app.directory.fallback_badge\":{\"context\":\"Fallback badge label on an organization card when no custom domain is available.\",\"text\":\"Espace OMO\"},\"app.directory.fallback_organization_name\":{\"context\":\"Fallback organization name used when an organization record has no visible name.\",\"text\":\"Organisation\"},\"app.directory.heading\":{\"context\":\"Main heading shown on the organization directory page.\",\"text\":\"Vos espaces OMO\"},\"app.directory.import.action\":{\"context\":\"Action label displayed on the organization import card.\",\"text\":\"Choisir un export\"},\"app.directory.import.aria_label\":{\"context\":\"Aria label for the organization import card button.\",\"text\":\"Importer une organisation existante\"},\"app.directory.import.badge\":{\"context\":\"Badge shown on the organization import card.\",\"text\":\"Migration\"},\"app.directory.import.description\":{\"context\":\"Subtitle shown on the organization import card.\",\"text\":\"Structure, membres, documents, projets et calendrier\"},\"app.directory.import.modal_title\":{\"context\":\"Title shown in the organization import modal.\",\"text\":\"Importer une organisation\"},\"app.directory.import.title\":{\"context\":\"Title shown on the organization import card.\",\"text\":\"Importer une organisation\"},\"app.directory.invitation.badge\":{\"context\":\"Badge shown on an organization card when the user has a pending invitation for that organization.\",\"text\":\"Invitation en attente\"},\"app.directory.invitation.pending_holons\":{\"context\":\"Summary shown on a pending invitation organization card with the number of holons included in the invitation.\",\"one\":\"{count} holon en attente\",\"other\":\"{count} holons en attente\"},\"app.directory.invitation.pending_organization\":{\"context\":\"Summary shown on a pending invitation organization card when there is no holon detail to display.\",\"text\":\"Accès à confirmer\"},\"app.directory.js.action_error\":{\"context\":\"Fallback error message shown in JavaScript when an organization card action fails.\",\"text\":\"Action impossible.\"},\"app.directory.js.default_organization_name\":{\"context\":\"Fallback organization name used in JavaScript confirmation dialogs when the name is missing.\",\"text\":\"cette organisation\"},\"app.directory.js.delete_confirm\":{\"context\":\"Confirmation dialog shown before deleting an organization from the directory page.\",\"text\":\"Supprimer {organizationName} ?\\n\\nLa structure, les membres, les cercles, les rôles, les partages et les documents liés seront supprimés.\"},\"app.directory.js.leave_confirm\":{\"context\":\"Confirmation dialog shown before leaving an organization from the directory page.\",\"text\":\"Quitter {organizationName} ?\\n\\nVos liens avec l\'organisation, ses cercles et ses rôles seront retirés.\"},\"app.directory.menu.actions_aria_label\":{\"context\":\"Aria label for the actions menu button on an organization card.\",\"text\":\"Actions pour {organizationName}\"},\"app.directory.menu.delete\":{\"context\":\"Menu item label used to delete an organization from the directory page.\",\"text\":\"Supprimer\"},\"app.directory.menu.leave\":{\"context\":\"Menu item label used to leave an organization from the directory page.\",\"text\":\"Quitter\"},\"app.directory.menu.system_organization_notice\":{\"context\":\"Notice shown instead of destructive actions for the protected system organization card.\",\"text\":\"Cette organisation de base est utilisée par le système pour les messages et les tutoriels. Elle ne peut pas être supprimée et ses administrateurs ne peuvent pas la quitter.\"},\"app.directory.modal.close\":{\"context\":\"Button label used to close the create organization modal.\",\"text\":\"Fermer\"},\"app.directory.open_organization_aria_label\":{\"context\":\"Aria label for the clickable overlay opening an organization workspace.\",\"text\":\"Ouvrir l\'espace {organizationName}\"},\"app.directory.page_title\":{\"context\":\"Browser title shown on the organization directory page.\",\"text\":\"Vos espaces OMO\"},\"app.directory.patreon_connect.action\":{\"context\":\"Action label displayed on the Patreon connect card shown before organization creation is allowed.\",\"text\":\"Se connecter avec Patreon\"},\"app.directory.patreon_connect.aria_label\":{\"context\":\"Aria label for the Patreon connect card button shown on the directory page.\",\"text\":\"Connecter votre profil Patreon\"},\"app.directory.patreon_connect.badge\":{\"context\":\"Badge shown on the Patreon connect card on the organization directory page.\",\"text\":\"Patreon requis\"},\"app.directory.patreon_connect.description\":{\"context\":\"Subtitle shown on the Patreon connect card on the organization directory page.\",\"text\":\"Connectez Patreon pour débloquer la création d\'une organisation\"},\"app.directory.patreon_connect.title\":{\"context\":\"Title shown on the Patreon connect card on the organization directory page.\",\"text\":\"Connecter Patreon\"},\"app.directory.status.available\":{\"context\":\"Status label shown on the organization directory page with the number of accessible organizations.\",\"one\":\"{count} organisation disponible\",\"other\":\"{count} organisations disponibles\"},\"app.directory.status.none\":{\"context\":\"Status label shown on the organization directory page when the user has no accessible organizations.\",\"text\":\"Aucune organisation pour le moment\"},\"app.directory.template.badge\":{\"context\":\"Badge shown on a shared organization template card.\",\"text\":\"Template partagé\"},\"app.directory.templates.heading\":{\"context\":\"Heading shown above the shared organization template cards on the directory page.\",\"text\":\"Vos templates d\'organisation\"},\"app.login.intro\":{\"context\":\"Intro text shown on the login page before accessing OMO.\",\"text\":\"Connectez-vous pour acceder à la structure et aux outils de gouvernance.\"},\"app.login.page_title\":{\"context\":\"Browser title shown on the login page.\",\"text\":\"{organizationName} - OMO\"},\"app.main.page_title\":{\"context\":\"Browser title shown on the main OMO application page.\",\"text\":\"Gouvernance UI\"},\"app.mobile.context\":{\"context\":\"Mobile navigation label for the context panel.\",\"text\":\"Contexte\"},\"app.mobile.menu\":{\"context\":\"Mobile navigation label for the tools panel.\",\"text\":\"Outils\"},\"app.mobile.right_panel\":{\"context\":\"Mobile navigation label for the right panel.\",\"text\":\"Résumé\"},\"app.not_found.message\":{\"context\":\"Main message shown on the organization not found page.\",\"text\":\"L\'organisation demandée n\'existe pas ou n\'est plus disponible.\"},\"app.not_found.page_description\":{\"context\":\"Additional explanation shown on the organization not found page.\",\"text\":\"Vous pouvez revenir à l\'accueil OMO et choisir un autre espace.\"},\"app.not_found.page_heading\":{\"context\":\"Main heading shown on the organization not found page.\",\"text\":\"Organisation introuvable\"},\"app.not_found.page_title\":{\"context\":\"Browser title shown on the organization not found page.\",\"text\":\"Organisation introuvable - OMO\"},\"app.patreon.prompt_title\":{\"context\":\"Title passed to the Patreon welcome popup configuration.\",\"text\":\"Soutenir le projet\"},\"app.user.demo\":{\"context\":\"Display name shown for a demo guest user.\",\"text\":\"Demo\"},\"common.back_to_home\":{\"context\":\"Generic action label used to return to the OMO home page.\",\"text\":\"Revenir à l\'accueil\"},\"common.logout\":{\"context\":\"Generic action label used to log out from OMO.\",\"text\":\"Se déconnecter\"}}','completed',1,NULL,'2026-07-25 11:14:00','2026-07-25 11:14:13','2026-07-25 11:14:01','2026-07-25 11:14:13'),
(2,'omo_topbar','en','a3fc08586b17e0db82a46b8816a97ffa042a902b7f037e6f8d22b2647657a4d3','{\"topbar.bug.button\":{\"context\":\"Topbar bug report button label in OMO pages.\",\"text\":\"Bug\"},\"topbar.bug.title\":{\"context\":\"Topbar bug report modal title in OMO pages.\",\"text\":\"Signaler un bug\"},\"topbar.bug.unavailable_html\":{\"context\":\"Fallback HTML shown when the bug report form cannot be loaded from the OMO topbar.\",\"text\":\"<p>Formulaire indisponible.</p>\"},\"topbar.close\":{\"context\":\"Generic close button label for the OMO topbar modal and drawer.\",\"text\":\"Fermer\"},\"topbar.drawer.default_title\":{\"context\":\"Fallback drawer title for the OMO topbar when no specific title is provided.\",\"text\":\"Panneau lateral\"},\"topbar.help.button\":{\"context\":\"Topbar help menu button label in OMO pages.\",\"text\":\"Aide\"},\"topbar.help.fallback_label\":{\"context\":\"Fallback label for a help item when no label is available in the OMO topbar.\",\"text\":\"Aide\"},\"topbar.help.faq.description\":{\"context\":\"Description of the FAQ help entry in the OMO topbar.\",\"text\":\"Acces aux questions les plus courantes, avec moteur de recherche pour trouver facilement la reponse a ses questions.\"},\"topbar.help.faq.label\":{\"context\":\"Label of the FAQ help entry in the OMO topbar.\",\"text\":\"FAQ\"},\"topbar.help.faq.title\":{\"context\":\"Title of the FAQ help entry in the OMO topbar.\",\"text\":\"FAQ OMO\"},\"topbar.help.pending_html\":{\"context\":\"Fallback HTML shown when a help item exists but does not yet have content in the OMO topbar.\",\"text\":\"<p>Contenu a venir.</p>\"},\"topbar.help.privacy.label\":{\"context\":\"Label of the privacy policy text link shown in the OMO topbar help menu.\",\"text\":\"Politique de confidentialite\"},\"topbar.help.terms.label\":{\"context\":\"Label of the terms and conditions text link shown in the OMO topbar help menu.\",\"text\":\"Conditions generales\"},\"topbar.help.tour.description\":{\"context\":\"Description of the guided tour help entry in the OMO topbar.\",\"text\":\"Tour des fonctions visibles a l ecran avec explication pour chaque bouton et chaque possibilite.\"},\"topbar.help.tour.label\":{\"context\":\"Label of the guided tour help entry in the OMO topbar.\",\"text\":\"Visite guidee\"},\"topbar.help.tutorials.description\":{\"context\":\"Description of the tutorials help entry in the OMO topbar.\",\"text\":\"Des formations ciblees pour monter en competences dans l utilisation du logiciel.\"},\"topbar.help.tutorials.label\":{\"context\":\"Label of the tutorials help entry in the OMO topbar.\",\"text\":\"Tutoriels\"},\"topbar.help.tutorials.title\":{\"context\":\"Title of the tutorials help entry in the OMO topbar.\",\"text\":\"Tutoriels\"},\"topbar.help.unavailable_html\":{\"context\":\"Fallback HTML shown when a help item cannot be loaded from the OMO topbar.\",\"text\":\"<p>Contenu indisponible.</p>\"},\"topbar.load_error\":{\"context\":\"Fallback message displayed inside the OMO topbar modal or drawer when remote content fails to load.\",\"text\":\"Erreur de chargement\"},\"topbar.loading\":{\"context\":\"Temporary message displayed inside the OMO topbar modal or drawer while remote content is loading.\",\"text\":\"Chargement...\"},\"topbar.logout\":{\"context\":\"Logout button label in the OMO topbar profile panel.\",\"text\":\"Se deconnecter\"},\"topbar.modal.default_title\":{\"context\":\"Fallback modal title for the OMO topbar when no specific title is provided.\",\"text\":\"Panneau\"},\"topbar.profile.admin_mode.active\":{\"context\":\"Status label shown in the OMO topbar profile panel when organization admin mode is active.\",\"text\":\"Mode admin d organisation actif\"},\"topbar.profile.admin_mode.disable\":{\"context\":\"Button label used in the OMO topbar profile panel to disable organization admin mode for the current session.\",\"text\":\"Quitter le mode admin d organisation\"},\"topbar.profile.admin_mode.enable\":{\"context\":\"Button label used in the OMO topbar profile panel to enable organization admin mode for the current session.\",\"text\":\"Activer le mode admin d organisation\"},\"topbar.profile.admin_mode.inactive\":{\"context\":\"Status label shown in the OMO topbar profile panel when organization admin mode is inactive.\",\"text\":\"Mode admin d organisation inactif\"},\"topbar.profile.button\":{\"context\":\"Topbar profile button label in OMO pages.\",\"text\":\"Profil\"},\"topbar.profile.details.email\":{\"context\":\"Email field label in the OMO topbar profile panel.\",\"text\":\"E-mail\"},\"topbar.profile.details.empty_value\":{\"context\":\"Fallback value shown for missing profile information in the OMO topbar profile panel.\",\"text\":\"Non renseigne\"},\"topbar.profile.details.name\":{\"context\":\"Name field label in the OMO topbar profile panel.\",\"text\":\"Nom\"},\"topbar.profile.details.username\":{\"context\":\"Username field label in the OMO topbar profile panel.\",\"text\":\"Nom d\'utilisateur\"},\"topbar.profile.edit_label\":{\"context\":\"Button label used to open the profile editor from the OMO topbar.\",\"text\":\"Editer le profil\"},\"topbar.profile.edit_title\":{\"context\":\"Modal title used when opening the profile editor from the OMO topbar.\",\"text\":\"Votre profil\"},\"topbar.profile.preferences.color_style_default\":{\"context\":\"Default monochrome color style option label shown in the OMO topbar profile panel.\",\"text\":\"Noir et blanc\"},\"topbar.profile.preferences.color_style_label\":{\"context\":\"Label of the compact color style selector shown in the OMO topbar profile panel.\",\"text\":\"Couleur\"},\"topbar.profile.preferences.color_style_ocean_blue\":{\"context\":\"Ocean Blue color style option label shown in the OMO topbar profile panel.\",\"text\":\"Ocean Blue\"},\"topbar.profile.preferences.color_style_turquoise\":{\"context\":\"Turquoise color style option label shown in the OMO topbar profile panel.\",\"text\":\"Turquoise\"},\"topbar.profile.preferences.language_label\":{\"context\":\"Label of the compact language selector shown in the OMO topbar profile panel.\",\"text\":\"Langue\"},\"topbar.profile.preferences.language_system\":{\"context\":\"System language option label shown in the compact language selector of the OMO topbar profile panel.\",\"text\":\"Systeme\"},\"topbar.profile.preferences.theme_dark\":{\"context\":\"Dark theme option label shown in the OMO topbar profile panel.\",\"text\":\"Sombre\"},\"topbar.profile.preferences.theme_label\":{\"context\":\"Label of the compact theme selector shown in the OMO topbar profile panel.\",\"text\":\"Theme\"},\"topbar.profile.preferences.theme_light\":{\"context\":\"Light theme option label shown in the OMO topbar profile panel.\",\"text\":\"Clair\"},\"topbar.profile.preferences.theme_system\":{\"context\":\"System theme option label shown in the OMO topbar profile panel.\",\"text\":\"Systeme\"},\"topbar.profile.site_admin_mode.active\":{\"context\":\"Status label shown in the OMO topbar profile panel when site admin mode is active.\",\"text\":\"Mode super admin actif\"},\"topbar.profile.site_admin_mode.disable\":{\"context\":\"Button label used in the OMO topbar profile panel to disable site admin mode for the current session.\",\"text\":\"Quitter le mode super admin\"},\"topbar.profile.site_admin_mode.enable\":{\"context\":\"Button label used in the OMO topbar profile panel to enable site admin mode for the current session.\",\"text\":\"Activer le mode super admin\"},\"topbar.profile.site_admin_mode.inactive\":{\"context\":\"Status label shown in the OMO topbar profile panel when site admin mode is inactive.\",\"text\":\"Mode super admin inactif\"},\"topbar.profile.summary_fallback\":{\"context\":\"Fallback summary text shown below the profile name in the OMO topbar when no email is available.\",\"text\":\"Resume du profil\"},\"topbar.search.advanced_hint\":{\"context\":\"Fallback hint shown in the OMO topbar search panel when no scoped search options are available.\",\"text\":\"D autres filtres avances pourront s ajouter ici.\"},\"topbar.search.button\":{\"context\":\"Topbar search menu button label in OMO pages.\",\"text\":\"Recherche\"},\"topbar.search.period\":{\"context\":\"Label shown above the date range filter in the OMO topbar search panel.\",\"text\":\"Periode\"},\"topbar.search.period_end\":{\"context\":\"Label for the end date of the OMO topbar search period.\",\"text\":\"Au\"},\"topbar.search.period_start\":{\"context\":\"Label for the start date of the OMO topbar search period.\",\"text\":\"Du\"},\"topbar.search.placeholder\":{\"context\":\"Placeholder and label for the OMO topbar search field.\",\"text\":\"Rechercher un cercle, un role, un outil, une FAQ ou un tutoriel\"},\"topbar.search.scope\":{\"context\":\"Label shown above the scoped filters in the OMO topbar search panel.\",\"text\":\"Chercher dans\"},\"topbar.search.submit\":{\"context\":\"Submit button label for the OMO topbar search field.\",\"text\":\"Lancer\"},\"topbar.tension.button\":{\"context\":\"Topbar tension button label in OMO pages.\",\"text\":\"Tension\"},\"topbar.tension.title\":{\"context\":\"Topbar tension modal title in OMO pages.\",\"text\":\"Declarer une tension\"},\"topbar.tension.unavailable_html\":{\"context\":\"Fallback HTML shown when the tension form cannot be loaded from the OMO topbar.\",\"text\":\"<p>Formulaire indisponible.</p>\"}}','completed',1,NULL,'2026-07-25 11:14:00','2026-07-25 11:14:10','2026-07-25 11:14:01','2026-07-25 11:14:10'),
(3,'omo_checklist','en','5aa1c0266001320570b9d890e91579ed082601ecb747cbd0ad27cb8096a5eaa5','{\"checklist.action.activate\":{\"context\":\"Button creating a checklist instance.\",\"text\":\"Activer\"},\"checklist.action.add_item\":{\"context\":\"Button adding an item to a checklist.\",\"text\":\"Ajouter un élément\"},\"checklist.action.cancel\":{\"context\":\"Button cancelling checklist edition.\",\"text\":\"Annuler\"},\"checklist.action.close\":{\"context\":\"Button closing the checklist drawer.\",\"text\":\"Fermer\"},\"checklist.action.edit\":{\"context\":\"Button opening checklist edition.\",\"text\":\"Modifier\"},\"checklist.action.edit_item\":{\"context\":\"Button editing a checklist item.\",\"text\":\"Modifier l élément\"},\"checklist.action.move_down\":{\"context\":\"Button moving an item downward.\",\"text\":\"Descendre\"},\"checklist.action.move_up\":{\"context\":\"Button moving an item upward.\",\"text\":\"Monter\"},\"checklist.action.new\":{\"context\":\"Button opening checklist creation.\",\"text\":\"Ajouter\"},\"checklist.action.remove_item\":{\"context\":\"Button removing an item from a checklist.\",\"text\":\"Retirer\"},\"checklist.action.save\":{\"context\":\"Button saving a checklist.\",\"text\":\"Enregistrer\"},\"checklist.action.save_item\":{\"context\":\"Button saving a checklist item.\",\"text\":\"Enregistrer l élément\"},\"checklist.activation.after_completion\":{\"context\":\"Dependent checklist item activation.\",\"text\":\"Visible après un autre élément\"},\"checklist.activation.after_start\":{\"context\":\"Checklist item activation relative to the run reference date.\",\"text\":\"Selon la date de référence\"},\"checklist.activation.immediate\":{\"context\":\"Immediate checklist item activation.\",\"text\":\"Visible immédiatement\"},\"checklist.delay.day\":{\"context\":\"Day delay unit.\",\"text\":\"Jour(s)\"},\"checklist.delay.month\":{\"context\":\"Month delay unit.\",\"text\":\"Mois\"},\"checklist.delay.week\":{\"context\":\"Week delay unit.\",\"text\":\"Semaine(s)\"},\"checklist.description\":{\"context\":\"Introduction of the checklist application.\",\"text\":\"Des processus réutilisables qui deviennent des projets au bon moment.\"},\"checklist.detail.activated_at\":{\"context\":\"Checklist run creation date label.\",\"text\":\"Activée le\"},\"checklist.detail.context\":{\"context\":\"Checklist detail context section.\",\"text\":\"Contexte\"},\"checklist.detail.display_lead\":{\"context\":\"Recurring item display lead time.\",\"text\":\"Affiché {delay} avant\"},\"checklist.detail.empty_items\":{\"context\":\"Empty checklist structure message.\",\"text\":\"Cette checklist ne contient encore aucune étape.\"},\"checklist.detail.empty_runs\":{\"context\":\"Checklist open runs empty state.\",\"text\":\"Aucune instance en cours.\"},\"checklist.detail.execution_duration\":{\"context\":\"Recurring item execution duration.\",\"text\":\"Délai de réalisation : {delay}\"},\"checklist.detail.item_count\":{\"context\":\"Checklist item count.\",\"one\":\"{count} élément\",\"other\":\"{count} éléments\"},\"checklist.detail.items\":{\"context\":\"Checklist detail item section.\",\"text\":\"Structure\"},\"checklist.detail.no_delay\":{\"context\":\"No checklist item delay.\",\"text\":\"Sans délai\"},\"checklist.detail.no_description\":{\"context\":\"Missing checklist description.\",\"text\":\"Aucune description.\"},\"checklist.detail.open_run_count\":{\"context\":\"Checklist open run count.\",\"one\":\"{count} instance en cours\",\"other\":\"{count} instances en cours\"},\"checklist.detail.open_runs\":{\"context\":\"Checklist open runs section title.\",\"text\":\"Instances en cours\"},\"checklist.detail.overdue\":{\"context\":\"Overdue project marker in a checklist project bar tooltip.\",\"text\":\"En retard\"},\"checklist.detail.project_instance_count\":{\"context\":\"Generated project count shown on checklist item bars.\",\"one\":\"{count} projet issu de la checklist\",\"other\":\"{count} projets issus de la checklist\"},\"checklist.detail.project_status\":{\"context\":\"Project status in a checklist project bar tooltip.\",\"text\":\"Statut : {status}\"},\"checklist.detail.project_status.blocked\":{\"context\":\"Blocked project status in a checklist project bar tooltip.\",\"text\":\"Bloqué\"},\"checklist.detail.project_status.done\":{\"context\":\"Done project status in a checklist project bar tooltip.\",\"text\":\"Terminé\"},\"checklist.detail.project_status.in_progress\":{\"context\":\"In progress project status in a checklist project bar tooltip.\",\"text\":\"En cours\"},\"checklist.detail.project_status.ready\":{\"context\":\"Ready project status in a checklist project bar tooltip.\",\"text\":\"Prêt\"},\"checklist.detail.project_status.review\":{\"context\":\"Review project status in a checklist project bar tooltip.\",\"text\":\"À vérifier\"},\"checklist.detail.project_status.someday\":{\"context\":\"Someday project status in a checklist project bar tooltip.\",\"text\":\"Un jour peut-être\"},\"checklist.detail.recurrence\":{\"context\":\"Recurring independent checklist item schedule.\",\"text\":\"Récurrent : {schedule}\"},\"checklist.detail.recurring_deadline\":{\"context\":\"Deadline date in a recurring project tooltip.\",\"text\":\"Date limite {date}\"},\"checklist.detail.recurring_instance_count\":{\"context\":\"Active recurring checklist project count.\",\"one\":\"{count} occurrence active\",\"other\":\"{count} occurrences actives\"},\"checklist.detail.recurring_planned_start\":{\"context\":\"Planned start date in a recurring project tooltip.\",\"text\":\"Planifié le {date}\"},\"checklist.detail.reference_date\":{\"context\":\"Checklist run reference date label.\",\"text\":\"Référence\"},\"checklist.detail.root\":{\"context\":\"Checklist root project label.\",\"text\":\"Projet racine\"},\"checklist.detail.run_item_count\":{\"context\":\"Checklist run item count.\",\"one\":\"{count} étape\",\"other\":\"{count} étapes\"},\"checklist.detail.trigger\":{\"context\":\"Checklist detail trigger section.\",\"text\":\"Déclenchement\"},\"checklist.detail.updated\":{\"context\":\"Checklist last update label.\",\"text\":\"Mise à jour\"},\"checklist.drawer.description\":{\"context\":\"Default nested drawer description.\",\"text\":\"Structure, activation et responsabilités.\"},\"checklist.drawer.title\":{\"context\":\"Default nested drawer title.\",\"text\":\"Checklist\"},\"checklist.empty.children\":{\"context\":\"Empty state for direct children scope.\",\"text\":\"Aucune checklist dans ce contexte ou ses enfants directs.\"},\"checklist.empty.contextual\":{\"context\":\"Empty state for local checklist scope.\",\"text\":\"Aucune checklist dans ce contexte.\"},\"checklist.empty.descendants\":{\"context\":\"Empty state for descendant scope.\",\"text\":\"Aucune checklist dans ce contexte ou ses descendants.\"},\"checklist.error.action\":{\"context\":\"Unsupported checklist action error.\",\"text\":\"Action inconnue.\"},\"checklist.error.activation_unavailable\":{\"context\":\"Checklist cannot be manually activated.\",\"text\":\"Cette checklist ne peut pas être activée à la demande.\"},\"checklist.error.context\":{\"context\":\"Invalid holon context error.\",\"text\":\"Contexte invalide ou inaccessible.\"},\"checklist.error.forbidden\":{\"context\":\"Checklist permission error.\",\"text\":\"Vous ne pouvez pas modifier cette checklist.\"},\"checklist.error.instance_title\":{\"context\":\"Missing or too long checklist instance title.\",\"text\":\"Le nom de l instance est obligatoire.\"},\"checklist.error.item_holon\":{\"context\":\"Invalid checklist item holon error.\",\"text\":\"Le rôle ou holon choisi pour un élément est invalide.\"},\"checklist.error.item_not_found\":{\"context\":\"Checklist item not found error.\",\"text\":\"Élément de checklist introuvable.\"},\"checklist.error.item_recurrence_structure\":{\"context\":\"Recurring container checklist item cannot have a parent or dependency.\",\"text\":\"Un élément récurrent doit être indépendant et visible immédiatement.\"},\"checklist.error.item_relation\":{\"context\":\"Invalid checklist relationship error.\",\"text\":\"Une relation entre les éléments est invalide ou forme une boucle.\"},\"checklist.error.item_title\":{\"context\":\"Missing checklist item title error.\",\"text\":\"Chaque élément doit avoir un titre.\"},\"checklist.error.items\":{\"context\":\"Missing checklist item error.\",\"text\":\"Ajoutez au moins un élément à la checklist.\"},\"checklist.error.load\":{\"context\":\"Checklist drawer loading error.\",\"text\":\"Impossible de charger cette checklist.\"},\"checklist.error.method\":{\"context\":\"Invalid HTTP method error.\",\"text\":\"Cette action doit être envoyée en POST.\"},\"checklist.error.not_found\":{\"context\":\"Checklist not found error.\",\"text\":\"Checklist introuvable.\"},\"checklist.error.open_instance\":{\"context\":\"Checklist overlap prevents another run.\",\"text\":\"Une instance est déjà en cours pour cette checklist.\"},\"checklist.error.organization\":{\"context\":\"Invalid organization error.\",\"text\":\"Organisation invalide ou inaccessible.\"},\"checklist.error.reference_date\":{\"context\":\"Invalid checklist run reference date.\",\"text\":\"La date de référence est invalide.\"},\"checklist.error.save\":{\"context\":\"Generic checklist persistence error.\",\"text\":\"Impossible d enregistrer la checklist.\"},\"checklist.error.schedule\":{\"context\":\"Invalid checklist recurrence error.\",\"text\":\"La récurrence choisie est incomplète ou invalide.\"},\"checklist.error.title\":{\"context\":\"Missing checklist title error.\",\"text\":\"Le titre de la checklist est obligatoire.\"},\"checklist.form.activate_intro\":{\"context\":\"Checklist activation form introduction.\",\"text\":\"La date de référence sert de point de départ aux étapes planifiées, y compris celles prévues avant cette date.\"},\"checklist.form.activate_title\":{\"context\":\"Checklist activation drawer title.\",\"text\":\"Activer la checklist\"},\"checklist.form.activation\":{\"context\":\"Checklist item activation field.\",\"text\":\"Visibilité\"},\"checklist.form.base_intro\":{\"context\":\"Checklist base information editor introduction.\",\"text\":\"Commencez par les informations générales. Les étapes seront ajoutées ensuite depuis la vue de la checklist.\"},\"checklist.form.confirm_overlap\":{\"context\":\"Confirmation required by ask overlap policy.\",\"text\":\"Créer une nouvelle instance malgré les instances déjà ouvertes\"},\"checklist.form.create_item_title\":{\"context\":\"Checklist item creation drawer title.\",\"text\":\"Ajouter une étape\"},\"checklist.form.create_title\":{\"context\":\"Checklist creation drawer title.\",\"text\":\"Nouvelle checklist\"},\"checklist.form.delay\":{\"context\":\"Checklist item delay field.\",\"text\":\"Délai\"},\"checklist.form.dependency\":{\"context\":\"Checklist item dependency field.\",\"text\":\"Après l élément\"},\"checklist.form.description\":{\"context\":\"Checklist description field.\",\"text\":\"Description\"},\"checklist.form.display_lead\":{\"context\":\"How long before the scheduled date a recurring project becomes visible.\",\"text\":\"Afficher en avance\"},\"checklist.form.display_lead_unit\":{\"context\":\"Unit for recurring project display lead time.\",\"text\":\"Unité d anticipation\"},\"checklist.form.edit_item_title\":{\"context\":\"Checklist item edition drawer title.\",\"text\":\"Modifier l étape\"},\"checklist.form.edit_title\":{\"context\":\"Checklist edition drawer title.\",\"text\":\"Modifier la checklist\"},\"checklist.form.end_date\":{\"context\":\"Deadline shown for a recurring checklist project.\",\"text\":\"Date limite\"},\"checklist.form.execution_duration\":{\"context\":\"How long a recurring project can be completed.\",\"text\":\"Délai de réalisation\"},\"checklist.form.execution_duration_unit\":{\"context\":\"Unit for recurring project execution duration.\",\"text\":\"Unité du délai\"},\"checklist.form.frequency\":{\"context\":\"Checklist recurrence frequency field.\",\"text\":\"Fréquence\"},\"checklist.form.holon\":{\"context\":\"Checklist item holon field.\",\"text\":\"Rôle ou holon responsable\"},\"checklist.form.identity\":{\"context\":\"Checklist identity form section.\",\"text\":\"Identité du modèle\"},\"checklist.form.importance\":{\"context\":\"Checklist item importance field.\",\"text\":\"Importance strategique\"},\"checklist.form.instance_title\":{\"context\":\"Checklist manual activation instance title field.\",\"text\":\"Nom de l instance\"},\"checklist.form.instance_title_help\":{\"context\":\"Explanation for the checklist instance title field.\",\"text\":\"Ce nom devient le titre du projet racine créé pour cette instance.\"},\"checklist.form.intro\":{\"context\":\"Checklist editor introduction.\",\"text\":\"Définissez le modèle, ses éléments et les conditions qui les rendent visibles.\"},\"checklist.form.item\":{\"context\":\"Checklist item card label.\",\"text\":\"Élément\"},\"checklist.form.item_description\":{\"context\":\"Checklist item description field.\",\"text\":\"Description\"},\"checklist.form.item_recurrence\":{\"context\":\"Recurring schedule section for a container checklist item.\",\"text\":\"Récurrence de cet élément\"},\"checklist.form.item_recurrence_help\":{\"context\":\"Explanation for independent recurring checklist item projects.\",\"text\":\"Chaque occurrence crée un projet simple pour le rôle choisi. Vous pouvez le faire apparaître en avance et définir son délai de réalisation.\"},\"checklist.form.item_timing\":{\"context\":\"Shared scheduling section for every checklist item.\",\"text\":\"Visibilité et délai\"},\"checklist.form.item_timing_help\":{\"context\":\"Explanation for visibility and completion timing shared by every checklist item.\",\"text\":\"Faites apparaître le projet avant sa date prévue et fixez son délai de réalisation. Ces paramètres s appliquent à chaque élément.\"},\"checklist.form.item_title\":{\"context\":\"Checklist item title field.\",\"text\":\"Titre de l élément\"},\"checklist.form.items\":{\"context\":\"Checklist item editor section.\",\"text\":\"Éléments de la checklist\"},\"checklist.form.items_help\":{\"context\":\"Checklist item editor help.\",\"text\":\"Chaque élément est un projet-modèle. Il peut être immédiat, décalé autour de la date de référence ou attendre un autre élément.\"},\"checklist.form.overlap\":{\"context\":\"Checklist overlap policy field.\",\"text\":\"Si une exécution est encore ouverte\"},\"checklist.form.parent\":{\"context\":\"Checklist item project parent field.\",\"text\":\"Sous-projet de\"},\"checklist.form.parent_root\":{\"context\":\"Checklist root project parent option.\",\"text\":\"Racine de la checklist\"},\"checklist.form.priority\":{\"context\":\"Checklist item priority field.\",\"text\":\"Priorité\"},\"checklist.form.reference_date\":{\"context\":\"Checklist run reference date field.\",\"text\":\"Date de référence\"},\"checklist.form.reference_help\":{\"context\":\"Checklist run reference date help.\",\"text\":\"Par exemple, la date d arrivée. Une étape à J-5 sera planifiée cinq jours avant.\"},\"checklist.form.revision_note\":{\"context\":\"Checklist revision note field.\",\"text\":\"Note interne\"},\"checklist.form.schedule\":{\"context\":\"Checklist recurrence schedule field.\",\"text\":\"Moment attendu\"},\"checklist.form.select_item\":{\"context\":\"Empty checklist item relation option.\",\"text\":\"Choisir un élément...\"},\"checklist.form.size\":{\"context\":\"Checklist item size field.\",\"text\":\"Taille\"},\"checklist.form.status\":{\"context\":\"Checklist publication status field.\",\"text\":\"État\"},\"checklist.form.title\":{\"context\":\"Checklist title field.\",\"text\":\"Titre\"},\"checklist.form.trigger\":{\"context\":\"Checklist trigger form section.\",\"text\":\"Déclenchement de la checklist\"},\"checklist.form.trigger_help\":{\"context\":\"Checklist trigger help.\",\"text\":\"Elle peut être lancée à la demande, suivant une récurrence ou servir uniquement de conteneur.\"},\"checklist.form.trigger_type\":{\"context\":\"Checklist trigger type field.\",\"text\":\"Mode\"},\"checklist.form.unit\":{\"context\":\"Checklist item delay unit field.\",\"text\":\"Unité\"},\"checklist.frequency.daily\":{\"context\":\"Daily recurrence.\",\"text\":\"Chaque jour\"},\"checklist.frequency.monthly\":{\"context\":\"Monthly recurrence.\",\"text\":\"Chaque mois\"},\"checklist.frequency.quarterly\":{\"context\":\"Quarterly recurrence.\",\"text\":\"Chaque trimestre\"},\"checklist.frequency.semiannual\":{\"context\":\"Semiannual recurrence.\",\"text\":\"Chaque semestre\"},\"checklist.frequency.weekly\":{\"context\":\"Weekly recurrence.\",\"text\":\"Chaque semaine\"},\"checklist.frequency.yearly\":{\"context\":\"Yearly recurrence.\",\"text\":\"Chaque année\"},\"checklist.loading\":{\"context\":\"Loading state inside the checklist drawer.\",\"text\":\"Chargement de la checklist...\"},\"checklist.overlap.ask\":{\"context\":\"Ask checklist run overlap policy.\",\"text\":\"Demander au moment venu\"},\"checklist.overlap.create_new\":{\"context\":\"Create a new checklist run overlap policy.\",\"text\":\"Créer une nouvelle exécution\"},\"checklist.overlap.reuse_open\":{\"context\":\"Reuse open checklist run overlap policy.\",\"text\":\"Réutiliser l exécution ouverte\"},\"checklist.overlap.skip\":{\"context\":\"Skip checklist run overlap policy.\",\"text\":\"Ignorer cette occurrence\"},\"checklist.run.status.running\":{\"context\":\"Running checklist instance status.\",\"text\":\"En cours\"},\"checklist.schedule.month.1\":{\"context\":\"January recurrence option.\",\"text\":\"Janvier\"},\"checklist.schedule.month.10\":{\"context\":\"October recurrence option.\",\"text\":\"Octobre\"},\"checklist.schedule.month.11\":{\"context\":\"November recurrence option.\",\"text\":\"Novembre\"},\"checklist.schedule.month.12\":{\"context\":\"December recurrence option.\",\"text\":\"Décembre\"},\"checklist.schedule.month.2\":{\"context\":\"February recurrence option.\",\"text\":\"Février\"},\"checklist.schedule.month.3\":{\"context\":\"March recurrence option.\",\"text\":\"Mars\"},\"checklist.schedule.month.4\":{\"context\":\"April recurrence option.\",\"text\":\"Avril\"},\"checklist.schedule.month.5\":{\"context\":\"May recurrence option.\",\"text\":\"Mai\"},\"checklist.schedule.month.6\":{\"context\":\"June recurrence option.\",\"text\":\"Juin\"},\"checklist.schedule.month.7\":{\"context\":\"July recurrence option.\",\"text\":\"Juillet\"},\"checklist.schedule.month.8\":{\"context\":\"August recurrence option.\",\"text\":\"Août\"},\"checklist.schedule.month.9\":{\"context\":\"September recurrence option.\",\"text\":\"Septembre\"},\"checklist.schedule.month_day\":{\"context\":\"Monthly day recurrence option.\",\"text\":\"Le {day} du mois\"},\"checklist.schedule.none\":{\"context\":\"Empty recurrence schedule option.\",\"text\":\"Choisir...\"},\"checklist.schedule.quarter.1\":{\"context\":\"First month of quarter recurrence.\",\"text\":\"Premier mois du trimestre\"},\"checklist.schedule.quarter.2\":{\"context\":\"Second month of quarter recurrence.\",\"text\":\"Deuxième mois du trimestre\"},\"checklist.schedule.quarter.3\":{\"context\":\"Third month of quarter recurrence.\",\"text\":\"Troisième mois du trimestre\"},\"checklist.schedule.semester.1\":{\"context\":\"First month of semester recurrence.\",\"text\":\"Premier mois du semestre\"},\"checklist.schedule.semester.2\":{\"context\":\"Second month of semester recurrence.\",\"text\":\"Deuxième mois du semestre\"},\"checklist.schedule.semester.3\":{\"context\":\"Third month of semester recurrence.\",\"text\":\"Troisième mois du semestre\"},\"checklist.schedule.semester.4\":{\"context\":\"Fourth month of semester recurrence.\",\"text\":\"Quatrième mois du semestre\"},\"checklist.schedule.semester.5\":{\"context\":\"Fifth month of semester recurrence.\",\"text\":\"Cinquième mois du semestre\"},\"checklist.schedule.semester.6\":{\"context\":\"Sixth month of semester recurrence.\",\"text\":\"Sixième mois du semestre\"},\"checklist.schedule.weekday.1\":{\"context\":\"Monday recurrence option.\",\"text\":\"Lundi\"},\"checklist.schedule.weekday.2\":{\"context\":\"Tuesday recurrence option.\",\"text\":\"Mardi\"},\"checklist.schedule.weekday.3\":{\"context\":\"Wednesday recurrence option.\",\"text\":\"Mercredi\"},\"checklist.schedule.weekday.4\":{\"context\":\"Thursday recurrence option.\",\"text\":\"Jeudi\"},\"checklist.schedule.weekday.5\":{\"context\":\"Friday recurrence option.\",\"text\":\"Vendredi\"},\"checklist.schedule.weekday.6\":{\"context\":\"Saturday recurrence option.\",\"text\":\"Samedi\"},\"checklist.schedule.weekday.7\":{\"context\":\"Sunday recurrence option.\",\"text\":\"Dimanche\"},\"checklist.scope.children\":{\"context\":\"Scope showing checklists attached to the current holon and direct children.\",\"text\":\"Enfants directs\"},\"checklist.scope.contextual\":{\"context\":\"Scope showing checklists attached to the current holon.\",\"text\":\"Local\"},\"checklist.scope.descendants\":{\"context\":\"Scope showing checklists attached to the current holon and descendants.\",\"text\":\"Descendants\"},\"checklist.status.draft\":{\"context\":\"Draft checklist status.\",\"text\":\"Brouillon\"},\"checklist.status.published\":{\"context\":\"Published checklist status.\",\"text\":\"Disponible\"},\"checklist.status.retired\":{\"context\":\"Retired checklist status.\",\"text\":\"Désactivée\"},\"checklist.success.activated\":{\"context\":\"Checklist run creation success.\",\"text\":\"La nouvelle instance est active.\"},\"checklist.success.reused\":{\"context\":\"Existing checklist run reused.\",\"text\":\"L instance déjà ouverte a été conservée.\"},\"checklist.success.save\":{\"context\":\"Checklist save success.\",\"text\":\"Checklist enregistrée.\"},\"checklist.title\":{\"context\":\"Main title of the checklist application.\",\"text\":\"Checklists\"},\"checklist.trigger.container\":{\"context\":\"Checklist that only groups independently triggered items.\",\"text\":\"Conteneur\"},\"checklist.trigger.manual\":{\"context\":\"Manual checklist trigger.\",\"text\":\"À la demande\"},\"checklist.trigger.scheduled\":{\"context\":\"Scheduled checklist trigger.\",\"text\":\"Récurrent\"}}','completed',1,NULL,'2026-07-25 11:14:00','2026-07-25 11:14:23','2026-07-25 11:14:01','2026-07-25 11:14:23'),
(4,'omo_get_org_panel','en','77c8484a43b0c4a61e1878fdbac840097e1e4699c36d82c5d0497229dc179454','{\"leftbar.actions.add\":{\"context\":\"Action menu label to create a child holon in the left panel.\",\"text\":\"Ajouter\"},\"leftbar.actions.delete\":{\"context\":\"Action menu label to delete the current holon in the left panel.\",\"text\":\"Supprimer\"},\"leftbar.actions.edit\":{\"context\":\"Action menu label to edit the current holon in the left panel.\",\"text\":\"Modifier\"},\"leftbar.actions.history\":{\"context\":\"Action menu label to open the current holon history in the left panel.\",\"text\":\"Historique\"},\"leftbar.actions.move\":{\"context\":\"Action menu label to move the current holon in the left panel.\",\"text\":\"Deplacer\"},\"leftbar.children.circles\":{\"context\":\"Subtitle for child circles listed in the left panel navigation.\",\"text\":\"Cercles\"},\"leftbar.children.roles\":{\"context\":\"Subtitle for child roles listed in the left panel navigation.\",\"text\":\"Roles\"},\"leftbar.children.section_title\":{\"context\":\"Accordion title for child navigation in the left panel.\",\"text\":\"Dependances\"},\"leftbar.copy_link.error\":{\"context\":\"Console error message when the direct holon link cannot be copied from the left panel.\",\"text\":\"Impossible de copier le lien direct.\"},\"leftbar.copy_link.success\":{\"context\":\"Temporary button label shown after copying a direct holon link from the left panel.\",\"text\":\"Lien copie\"},\"leftbar.detail.item_fallback\":{\"context\":\"Fallback title for a detail card item in the left panel when no title is available.\",\"text\":\"Element\"},\"leftbar.detail.property_fallback\":{\"context\":\"Fallback section title for a holon property in the left panel when no label is available.\",\"text\":\"Propriete {propertyId}\"},\"leftbar.detail.show\":{\"context\":\"Label shown beside the control that expands the HTML detail of a text property.\",\"text\":\"Voir détail\"},\"leftbar.detail.updated_at\":{\"context\":\"Update metadata shown below a left panel section when the updater is unknown.\",\"text\":\"Mis a jour le {date}\"},\"leftbar.detail.updated_by\":{\"context\":\"Update metadata shown below a left panel section when the updater is known.\",\"text\":\"Mis a jour le {date} par {userName}\"},\"leftbar.empty.message\":{\"context\":\"Message shown in the left panel when the current holon has no visible content.\",\"text\":\"Aucun contenu n est encore renseigne pour ce holon.\"},\"leftbar.empty.section_title\":{\"context\":\"Section title shown in the left panel when the current holon has no visible content.\",\"text\":\"Informations\"},\"leftbar.error.holon_access_denied\":{\"context\":\"Error message shown in the left panel when the current holon cannot be viewed.\",\"text\":\"Acces refuse a ce holon.\"},\"leftbar.error.holon_not_found\":{\"context\":\"Error message shown in the left panel when the requested holon cannot be found.\",\"text\":\"Holon introuvable pour cette organisation.\"},\"leftbar.error.organization_access_denied\":{\"context\":\"Error message shown in the left panel when the current organization cannot be viewed.\",\"text\":\"Acces refuse a cette organisation.\"},\"leftbar.error.organization_invalid\":{\"context\":\"Error message shown in the left panel when no valid organization identifier is available.\",\"text\":\"Organisation invalide.\"},\"leftbar.error.organization_not_found\":{\"context\":\"Error message shown in the left panel when the requested organization cannot be found.\",\"text\":\"Organisation introuvable.\"},\"leftbar.error.root_not_found\":{\"context\":\"Error message shown in the left panel when the organization has no structural root holon.\",\"text\":\"Aucune structure racine n a ete trouvee pour cette organisation.\"},\"leftbar.members.add\":{\"context\":\"Button label and modal title used to add a member from the left panel.\",\"text\":\"Ajouter un membre\"},\"leftbar.members.pending_tooltip\":{\"context\":\"Tooltip shown for a pending invited member avatar in the left panel.\",\"text\":\"{memberName} - invitation en attente\"},\"leftbar.members.section_title\":{\"context\":\"Section title shown above the member avatars in the left panel.\",\"text\":\"Membres\"},\"leftbar.members.view_all\":{\"context\":\"Button label to open the complete team drawer from the left panel.\",\"text\":\"Voir tout\"},\"leftbar.project.children.empty\":{\"context\":\"Message shown when expanding a project reference without direct subprojects.\",\"text\":\"Aucun sous-projet direct.\"},\"leftbar.project.children.error\":{\"context\":\"Message shown when loading direct subprojects of a project reference fails.\",\"text\":\"Impossible de charger les sous-projets.\"},\"leftbar.project.children.loading\":{\"context\":\"Temporary message while direct subprojects of a project reference are loading.\",\"text\":\"Chargement des sous-projets...\"}}','completed',1,NULL,'2026-07-25 11:14:00','2026-07-25 11:14:06','2026-07-25 11:14:01','2026-07-25 11:14:06'),
(5,'omo_get_sidebar_panel','en','7258f1301d443eca5c81e9ee4fba09a9b428a3544f0e08f73bc31af8cbc584b7','{\"sidebar.applications.manage_label\":{\"context\":\"Label of the sidebar item used to open the application management picker.\",\"text\":\"Gérer\"},\"sidebar.applications.manage_title\":{\"context\":\"Tooltip of the sidebar item used to open the application management picker.\",\"text\":\"Gérer les applications\"},\"sidebar.parameters.label\":{\"context\":\"Label of the parameters entry in the sidebar.\",\"text\":\"Paramètres\"}}','completed',1,NULL,'2026-07-25 11:14:00','2026-07-25 11:14:03','2026-07-25 11:14:01','2026-07-25 11:14:03'),
(6,'omo_stats','en','708c2416140325ae4c4266248f090bfb04f4699c8bc580e2fabe72c71762512c','{\"stats.action.add\":{\"context\":\"Generic add action.\",\"text\":\"Ajouter\"},\"stats.action.cancel\":{\"context\":\"Button cancelling indicator edition.\",\"text\":\"Annuler\"},\"stats.action.close\":{\"context\":\"Button closing the nested indicator drawer.\",\"text\":\"Fermer\"},\"stats.action.create_group\":{\"context\":\"Button creating an indicator group.\",\"text\":\"Creer le groupe\"},\"stats.action.delete\":{\"context\":\"Button deleting one dated indicator value.\",\"text\":\"Supprimer\"},\"stats.action.delete_group\":{\"context\":\"Menu action removing a contextual indicator group.\",\"text\":\"Retirer le groupe\"},\"stats.action.delete_import\":{\"context\":\"Menu action removing an indicator import from the current context.\",\"text\":\"Retirer de ce contexte\"},\"stats.action.delete_indicator\":{\"context\":\"Menu action archiving an indicator from the current catalogue.\",\"text\":\"Supprimer l indicateur\"},\"stats.action.detail\":{\"context\":\"Button opening an indicator detail view.\",\"text\":\"Détail\"},\"stats.action.edit\":{\"context\":\"Button opening indicator edition.\",\"text\":\"Modifier\"},\"stats.action.edit_group\":{\"context\":\"Menu action editing a contextual indicator group.\",\"text\":\"Modifier le groupe\"},\"stats.action.edit_import\":{\"context\":\"Menu action changing the source of a contextual indicator import.\",\"text\":\"Changer la source\"},\"stats.action.group\":{\"context\":\"Menu action creating a multi-indicator chart group.\",\"text\":\"Grouper des indicateurs\"},\"stats.action.import\":{\"context\":\"Menu action creating a contextual indicator import.\",\"text\":\"Importer un indicateur\"},\"stats.action.more\":{\"context\":\"Menu button opening additional indicator actions.\",\"text\":\"Plus d actions\"},\"stats.action.new\":{\"context\":\"Primary button opening indicator creation.\",\"text\":\"Nouvel indicateur\"},\"stats.action.save\":{\"context\":\"Button saving indicator edition.\",\"text\":\"Enregistrer\"},\"stats.action.update\":{\"context\":\"Button saving an edited contextual import or indicator group.\",\"text\":\"Enregistrer\"},\"stats.card.context\":{\"context\":\"Label for the holon owning an indicator.\",\"text\":\"Contexte\"},\"stats.card.group\":{\"context\":\"Label on a composite indicator group card.\",\"text\":\"Groupe\"},\"stats.card.imported\":{\"context\":\"Label on an indicator imported into the current context.\",\"text\":\"Importe\"},\"stats.card.latest\":{\"context\":\"Label introducing the latest indicator value on a card.\",\"text\":\"Dernière valeur\"},\"stats.card.member_count\":{\"context\":\"Number of indicators in a group.\",\"one\":\"{count} indicateur\",\"other\":\"{count} indicateurs\"},\"stats.card.no_value\":{\"context\":\"Card fallback when an indicator has no dated values.\",\"text\":\"Aucune valeur\"},\"stats.card.open\":{\"context\":\"Accessible label on an interactive indicator card or row.\",\"text\":\"Ouvrir l indicateur {name}\"},\"stats.card.overdue\":{\"context\":\"Label shown when an indicator has passed its expected measurement deadline.\",\"text\":\"Valeur dépassée\"},\"stats.card.overdue_days\":{\"context\":\"Delay shown below the latest value label on an overdue indicator card.\",\"one\":\"En retard de {count} jour\",\"other\":\"En retard de {count} jours\"},\"stats.card.to_complete\":{\"context\":\"Label shown when an indicator is due but still within its grace period.\",\"text\":\"À compléter\"},\"stats.card.value_count\":{\"context\":\"Count of dated values attached to an indicator.\",\"one\":\"{count} valeur\",\"other\":\"{count} valeurs\"},\"stats.chart.empty\":{\"context\":\"Empty chart message.\",\"text\":\"Pas encore de données à représenter.\"},\"stats.chart.tooltip.date\":{\"context\":\"Tooltip label for a chart point date.\",\"text\":\"Date\"},\"stats.chart.tooltip.value\":{\"context\":\"Tooltip label for a chart point value.\",\"text\":\"Valeur\"},\"stats.column.context\":{\"context\":\"Compact list column for the owning context.\",\"text\":\"Contexte\"},\"stats.column.history\":{\"context\":\"Compact list column for the mini chart.\",\"text\":\"Historique\"},\"stats.column.indicator\":{\"context\":\"Compact list column for the indicator identity.\",\"text\":\"Indicateur\"},\"stats.column.latest\":{\"context\":\"Compact list column for the latest value.\",\"text\":\"Dernière valeur\"},\"stats.controls.sort.alpha\":{\"context\":\"Alphabetical indicator sorting option.\",\"text\":\"Alphabétique\"},\"stats.controls.sort.aria\":{\"context\":\"Accessible label for the indicator sorting selector.\",\"text\":\"Classement des indicateurs\"},\"stats.controls.sort.temporal\":{\"context\":\"Measurement frequency indicator sorting option.\",\"text\":\"Temporalité\"},\"stats.detail.add\":{\"context\":\"Submit button for a new dated value.\",\"text\":\"Ajouter la valeur\"},\"stats.detail.add_help\":{\"context\":\"Help below the quick value form heading.\",\"text\":\"La date et l heure actuelles sont proposées automatiquement.\"},\"stats.detail.add_title\":{\"context\":\"Heading above the quick value form.\",\"text\":\"Ajouter la valeur du moment\"},\"stats.detail.chart_min_value\":{\"context\":\"Label for the optional lower chart value in indicator detail.\",\"text\":\"Valeur basse\"},\"stats.detail.confirm_delete\":{\"context\":\"Confirmation before deleting one value.\",\"text\":\"Supprimer définitivement cette valeur ?\"},\"stats.detail.confirm_delete_group\":{\"context\":\"Confirmation before removing a contextual indicator group.\",\"text\":\"Retirer ce groupe du contexte ?\"},\"stats.detail.confirm_delete_import\":{\"context\":\"Confirmation before removing a contextual import.\",\"text\":\"Retirer cet indicateur du contexte ?\"},\"stats.detail.confirm_delete_indicator\":{\"context\":\"Confirmation before hiding an indicator.\",\"text\":\"Supprimer cet indicateur de la liste ? Ses valeurs seront conservees.\"},\"stats.detail.frequency\":{\"context\":\"Label for the expected measurement frequency in indicator detail.\",\"text\":\"Fréquence attendue\"},\"stats.detail.latest\":{\"context\":\"Label for the latest value in indicator detail.\",\"text\":\"Valeur actuelle\"},\"stats.detail.no_values\":{\"context\":\"Empty state in the indicator value list.\",\"text\":\"Aucune valeur n a encore été enregistrée.\"},\"stats.detail.range.end\":{\"context\":\"Accessible label for the end handle of the chart time range selector.\",\"text\":\"Fin de la periode affichee\"},\"stats.detail.range.label\":{\"context\":\"Label above the interactive chart time range selector.\",\"text\":\"Periode affichee\"},\"stats.detail.range.start\":{\"context\":\"Accessible label for the start handle of the chart time range selector.\",\"text\":\"Debut de la periode affichee\"},\"stats.detail.reference\":{\"context\":\"Label for the reference type.\",\"text\":\"Référence\"},\"stats.detail.reference_ceiling\":{\"context\":\"Indicator reference type label for ceiling.\",\"text\":\"Plafond horizontal\"},\"stats.detail.reference_none\":{\"context\":\"Indicator reference type label for no reference.\",\"text\":\"Sans courbe de référence\"},\"stats.detail.reference_objective\":{\"context\":\"Indicator reference type label for objective.\",\"text\":\"Objectif ou trajectoire\"},\"stats.detail.schedule\":{\"context\":\"Label for the optional expected measurement moment in indicator detail.\",\"text\":\"Moment attendu\"},\"stats.detail.source\":{\"context\":\"External link to the indicator source.\",\"text\":\"Consulter la source\"},\"stats.detail.tab.chart\":{\"context\":\"Tab showing the large chart.\",\"text\":\"Graphique\"},\"stats.detail.tab.values\":{\"context\":\"Tab showing dated values.\",\"text\":\"Valeurs\"},\"stats.detail.value\":{\"context\":\"Heading for the dated value number.\",\"text\":\"Valeur\"},\"stats.detail.value_date\":{\"context\":\"Heading for the dated value date.\",\"text\":\"Date\"},\"stats.drawer.description\":{\"context\":\"Nested drawer description.\",\"text\":\"Graphique, valeurs et saisie manuelle.\"},\"stats.drawer.title\":{\"context\":\"Nested drawer title.\",\"text\":\"Indicateur\"},\"stats.empty.children\":{\"context\":\"Empty state for the direct child scope.\",\"text\":\"Aucun indicateur n est encore défini dans ce contexte ou ses enfants directs.\"},\"stats.empty.contextual\":{\"context\":\"Empty state for the contextual scope.\",\"text\":\"Aucun indicateur n est encore défini dans ce contexte.\"},\"stats.empty.descendants\":{\"context\":\"Empty state for the descendants scope.\",\"text\":\"Aucun indicateur n est encore défini dans ce contexte ou ses descendants.\"},\"stats.error.action\":{\"context\":\"Error returned for an unsupported stats action.\",\"text\":\"Action inconnue.\"},\"stats.error.ceiling\":{\"context\":\"Validation error when a ceiling is not horizontal.\",\"text\":\"Tous les points d un plafond doivent utiliser la même valeur.\"},\"stats.error.ceiling_value\":{\"context\":\"Validation error for a missing or invalid ceiling value.\",\"text\":\"La valeur du plafond est obligatoire.\"},\"stats.error.chart_min_value\":{\"context\":\"Validation error for a malformed chart lower value.\",\"text\":\"La valeur basse du graphique est invalide.\"},\"stats.error.context\":{\"context\":\"Error shown when the holon context is invalid.\",\"text\":\"Contexte invalide ou inaccessible.\"},\"stats.error.date\":{\"context\":\"Validation error for an invalid measurement date.\",\"text\":\"La date saisie est invalide.\"},\"stats.error.forbidden\":{\"context\":\"Error shown when indicator edition is forbidden.\",\"text\":\"Vous ne pouvez pas modifier cet indicateur.\"},\"stats.error.group_name\":{\"context\":\"Validation error for a missing indicator group name.\",\"text\":\"Le nom du groupe est obligatoire.\"},\"stats.error.load\":{\"context\":\"Generic nested drawer loading error.\",\"text\":\"Impossible de charger cet indicateur.\"},\"stats.error.method\":{\"context\":\"Error returned for a mutation using the wrong HTTP method.\",\"text\":\"Cette action doit être envoyée en POST.\"},\"stats.error.name\":{\"context\":\"Validation error for a missing indicator name.\",\"text\":\"Le nom de l indicateur est obligatoire.\"},\"stats.error.not_found\":{\"context\":\"Error shown when an indicator is unavailable.\",\"text\":\"Indicateur introuvable.\"},\"stats.error.organization\":{\"context\":\"Error shown when the organization context is invalid.\",\"text\":\"Organisation invalide ou inaccessible.\"},\"stats.error.reference_dates\":{\"context\":\"Validation error for inverted endpoint dates.\",\"text\":\"La date de fin de la référence doit être postérieure à sa date de début.\"},\"stats.error.reference_endpoints\":{\"context\":\"Validation error for missing dated reference endpoints.\",\"text\":\"Les points à 0 % et 100 % sont obligatoires et doivent avoir une date.\"},\"stats.error.reference_points\":{\"context\":\"Validation error for malformed reference positions.\",\"text\":\"La courbe de référence doit contenir des points uniques entre 0 et 100 %.\"},\"stats.error.save\":{\"context\":\"Generic indicator persistence error.\",\"text\":\"Impossible d enregistrer l indicateur.\"},\"stats.error.schedule\":{\"context\":\"Validation error for an invalid expected measurement frequency or moment.\",\"text\":\"Le rythme de mesure est invalide.\"},\"stats.error.selection\":{\"context\":\"Validation error for an empty or invalid indicator selection.\",\"text\":\"Selectionnez au moins un indicateur visible.\"},\"stats.error.url\":{\"context\":\"Validation error for an unsafe source URL.\",\"text\":\"L URL doit commencer par http:// ou https://.\"},\"stats.error.value\":{\"context\":\"Validation error for a non numeric indicator value.\",\"text\":\"La valeur saisie est invalide.\"},\"stats.error.value_save\":{\"context\":\"Generic indicator value persistence error.\",\"text\":\"Impossible d enregistrer cette valeur.\"},\"stats.form.add_point\":{\"context\":\"Button adding an intermediate reference point.\",\"text\":\"Ajouter un point\"},\"stats.form.ceiling_help\":{\"context\":\"Help text for the simple ceiling reference editor.\",\"text\":\"Saisissez une valeur unique. Le repère sera affiché sur toute la période visible du graphique.\"},\"stats.form.ceiling_title\":{\"context\":\"Heading of the simple ceiling reference editor.\",\"text\":\"Plafond\"},\"stats.form.ceiling_value\":{\"context\":\"Label for the simple ceiling value input.\",\"text\":\"Valeur du plafond\"},\"stats.form.create_title\":{\"context\":\"Heading of the create indicator form.\",\"text\":\"Nouvel indicateur\"},\"stats.form.edit_title\":{\"context\":\"Heading of the edit indicator form.\",\"text\":\"Modifier l indicateur\"},\"stats.form.endpoint\":{\"context\":\"Badge shown on reference curve endpoint rows.\",\"text\":\"Extrémité datée\"},\"stats.form.frequency\":{\"context\":\"Label for expected measurement frequency select.\",\"text\":\"Fréquence\"},\"stats.form.intermediate\":{\"context\":\"Badge shown on intermediate reference curve rows.\",\"text\":\"Point intermédiaire\"},\"stats.form.intro\":{\"context\":\"Introductory copy in the indicator form.\",\"text\":\"Définissez la série et, si nécessaire, sa courbe de référence.\"},\"stats.form.point_date\":{\"context\":\"Reference point editor endpoint date label.\",\"text\":\"Date\"},\"stats.form.point_date_auto\":{\"context\":\"Reference point editor calculated intermediate date label.\",\"text\":\"Date calculée\"},\"stats.form.point_value\":{\"context\":\"Reference point editor value label.\",\"text\":\"Valeur\"},\"stats.form.position\":{\"context\":\"Reference point editor position label.\",\"text\":\"Position (%)\"},\"stats.form.reference_ceiling\":{\"context\":\"Reference type select option for ceiling.\",\"text\":\"Plafond horizontal\"},\"stats.form.reference_help\":{\"context\":\"Help text for the reference point editor.\",\"text\":\"Les extrémités datées utilisent 0 % et 100 %. Les dates intermédiaires sont calculées selon la position du point.\"},\"stats.form.reference_none\":{\"context\":\"Reference type select option for none.\",\"text\":\"Aucune référence\"},\"stats.form.reference_objective\":{\"context\":\"Reference type select option for objective.\",\"text\":\"Objectif ou trajectoire\"},\"stats.form.reference_title\":{\"context\":\"Heading of the reference point editor.\",\"text\":\"Courbe de référence\"},\"stats.form.remove_point\":{\"context\":\"Button removing an intermediate reference point.\",\"text\":\"Retirer\"},\"stats.form.schedule\":{\"context\":\"Label for expected measurement moment select.\",\"text\":\"Quand\"},\"stats.form.schedule_help\":{\"context\":\"Help text for optional measurement timing.\",\"text\":\"Définissez le rythme attendu. Le moment est facultatif: sans lui, le système pourra s appuyer sur l intervalle observé entre les mesures.\"},\"stats.form.schedule_title\":{\"context\":\"Heading of the expected measurement schedule editor.\",\"text\":\"Rythme de mesure\"},\"stats.frequency.daily\":{\"context\":\"Expected measurement frequency option.\",\"text\":\"Chaque jour\"},\"stats.frequency.monthly\":{\"context\":\"Expected measurement frequency option.\",\"text\":\"Chaque mois\"},\"stats.frequency.none\":{\"context\":\"Empty option for an indicator without expected measurement frequency.\",\"text\":\"Aucune fréquence définie\"},\"stats.frequency.quarterly\":{\"context\":\"Expected measurement frequency option.\",\"text\":\"Chaque trimestre\"},\"stats.frequency.semiannual\":{\"context\":\"Expected measurement frequency option.\",\"text\":\"Chaque semestre\"},\"stats.frequency.weekly\":{\"context\":\"Expected measurement frequency option.\",\"text\":\"Chaque semaine\"},\"stats.frequency.yearly\":{\"context\":\"Expected measurement frequency option.\",\"text\":\"Chaque année\"},\"stats.group.combined\":{\"context\":\"Section label for composite indicator groups in the temporal sorting mode.\",\"text\":\"Cumuls\"},\"stats.group.detail.sources\":{\"context\":\"Heading above the source indicator legend in a group detail.\",\"text\":\"Indicateurs sources\"},\"stats.group.detail.sum\":{\"context\":\"Legend label for the main aggregated group curve.\",\"text\":\"Somme calculee\"},\"stats.group.edit_title\":{\"context\":\"Title of the indicator group edit picker modal.\",\"text\":\"Modifier le groupe\"},\"stats.group.mode\":{\"context\":\"Label for the group chart display mode.\",\"text\":\"Affichage\"},\"stats.group.mode.overlay\":{\"context\":\"Group chart mode drawing one curve per indicator.\",\"text\":\"Courbes superposees\"},\"stats.group.mode.sum\":{\"context\":\"Group chart mode aggregating indicator values.\",\"text\":\"Somme des valeurs\"},\"stats.group.name\":{\"context\":\"Label for the indicator group name.\",\"text\":\"Nom du groupe\"},\"stats.group.title\":{\"context\":\"Title of the indicator group picker modal.\",\"text\":\"Grouper des indicateurs\"},\"stats.import.edit_title\":{\"context\":\"Title of the indicator import edit picker modal.\",\"text\":\"Modifier la source importée\"},\"stats.import.search\":{\"context\":\"Label for the indicator picker search field.\",\"text\":\"Rechercher\"},\"stats.import.search_placeholder\":{\"context\":\"Placeholder for the indicator picker search field.\",\"text\":\"Nom ou contexte\"},\"stats.import.title\":{\"context\":\"Title of the indicator import picker modal.\",\"text\":\"Importer un indicateur\"},\"stats.import.visible\":{\"context\":\"Label for the indicator picker result list.\",\"text\":\"Indicateurs visibles\"},\"stats.loading\":{\"context\":\"Loading message in the nested drawer.\",\"text\":\"Chargement de l indicateur...\"},\"stats.schedule.month.1\":{\"context\":\"Month option for an expected yearly measurement.\",\"text\":\"Janvier\"},\"stats.schedule.month.10\":{\"context\":\"Month option for an expected yearly measurement.\",\"text\":\"Octobre\"},\"stats.schedule.month.11\":{\"context\":\"Month option for an expected yearly measurement.\",\"text\":\"Novembre\"},\"stats.schedule.month.12\":{\"context\":\"Month option for an expected yearly measurement.\",\"text\":\"Décembre\"},\"stats.schedule.month.2\":{\"context\":\"Month option for an expected yearly measurement.\",\"text\":\"Février\"},\"stats.schedule.month.3\":{\"context\":\"Month option for an expected yearly measurement.\",\"text\":\"Mars\"},\"stats.schedule.month.4\":{\"context\":\"Month option for an expected yearly measurement.\",\"text\":\"Avril\"},\"stats.schedule.month.5\":{\"context\":\"Month option for an expected yearly measurement.\",\"text\":\"Mai\"},\"stats.schedule.month.6\":{\"context\":\"Month option for an expected yearly measurement.\",\"text\":\"Juin\"},\"stats.schedule.month.7\":{\"context\":\"Month option for an expected yearly measurement.\",\"text\":\"Juillet\"},\"stats.schedule.month.8\":{\"context\":\"Month option for an expected yearly measurement.\",\"text\":\"Août\"},\"stats.schedule.month.9\":{\"context\":\"Month option for an expected yearly measurement.\",\"text\":\"Septembre\"},\"stats.schedule.month_day\":{\"context\":\"Day of month option for an expected monthly measurement.\",\"text\":\"Le {day}\"},\"stats.schedule.none\":{\"context\":\"Empty option for the optional expected measurement moment.\",\"text\":\"Sans précision\"},\"stats.schedule.quarter.1\":{\"context\":\"Quarter cycle option for an expected quarterly measurement.\",\"text\":\"Janvier, avril, juillet, octobre\"},\"stats.schedule.quarter.2\":{\"context\":\"Quarter cycle option for an expected quarterly measurement.\",\"text\":\"Février, mai, août, novembre\"},\"stats.schedule.quarter.3\":{\"context\":\"Quarter cycle option for an expected quarterly measurement.\",\"text\":\"Mars, juin, septembre, décembre\"},\"stats.schedule.semester.1\":{\"context\":\"Semester cycle option for an expected semiannual measurement.\",\"text\":\"Janvier, juillet\"},\"stats.schedule.semester.2\":{\"context\":\"Semester cycle option for an expected semiannual measurement.\",\"text\":\"Février, août\"},\"stats.schedule.semester.3\":{\"context\":\"Semester cycle option for an expected semiannual measurement.\",\"text\":\"Mars, septembre\"},\"stats.schedule.semester.4\":{\"context\":\"Semester cycle option for an expected semiannual measurement.\",\"text\":\"Avril, octobre\"},\"stats.schedule.semester.5\":{\"context\":\"Semester cycle option for an expected semiannual measurement.\",\"text\":\"Mai, novembre\"},\"stats.schedule.semester.6\":{\"context\":\"Semester cycle option for an expected semiannual measurement.\",\"text\":\"Juin, décembre\"},\"stats.schedule.weekday.1\":{\"context\":\"Weekday option for an expected weekly measurement.\",\"text\":\"Lundi\"},\"stats.schedule.weekday.2\":{\"context\":\"Weekday option for an expected weekly measurement.\",\"text\":\"Mardi\"},\"stats.schedule.weekday.3\":{\"context\":\"Weekday option for an expected weekly measurement.\",\"text\":\"Mercredi\"},\"stats.schedule.weekday.4\":{\"context\":\"Weekday option for an expected weekly measurement.\",\"text\":\"Jeudi\"},\"stats.schedule.weekday.5\":{\"context\":\"Weekday option for an expected weekly measurement.\",\"text\":\"Vendredi\"},\"stats.schedule.weekday.6\":{\"context\":\"Weekday option for an expected weekly measurement.\",\"text\":\"Samedi\"},\"stats.schedule.weekday.7\":{\"context\":\"Weekday option for an expected weekly measurement.\",\"text\":\"Dimanche\"},\"stats.scope.children\":{\"context\":\"Scope label for indicators defined in the current holon and its direct children.\",\"text\":\"Enfants directs\"},\"stats.scope.contextual\":{\"context\":\"Scope label for indicators defined in the current holon.\",\"text\":\"Contextuel\"},\"stats.scope.descendants\":{\"context\":\"Scope label for indicators in the current holon and descendants.\",\"text\":\"Descendants\"},\"stats.title\":{\"context\":\"Main title of the contextual steering indicators application.\",\"text\":\"Indicateurs\"},\"stats.view.cards\":{\"context\":\"Button switching the indicator list to cards.\",\"text\":\"Cartes\"},\"stats.view.compact\":{\"context\":\"Button switching the indicator list to compact rows.\",\"text\":\"Compact\"}}','completed',1,NULL,'2026-07-25 11:14:03','2026-07-25 11:14:23','2026-07-25 11:14:04','2026-07-25 11:14:23'),
(7,'omo_documents_index','en','c0d0b752d905747423a299a5e4cfd1f379fc2757e3541bc77ed4ff308db3257a','{\"documents.action.loading\":{\"context\":\"Loading state shown while a document drawer is loading.\",\"text\":\"Chargement...\"},\"documents.action.new\":{\"context\":\"Primary action used to create a new document.\",\"text\":\"Nouveau\"},\"documents.controls.density.aria\":{\"context\":\"Accessible label for the documents density control.\",\"text\":\"Densité d’affichage des documents\"},\"documents.controls.density.compact\":{\"context\":\"Label used for compact document density.\",\"text\":\"Compact\"},\"documents.controls.density.detail\":{\"context\":\"Label used for detailed document density.\",\"text\":\"Détail\"},\"documents.controls.sort.alpha\":{\"context\":\"Short alphabetical sort label shown before the sort control is rebuilt in JavaScript.\",\"text\":\"Alphabétique\"},\"documents.controls.sort.aria\":{\"context\":\"Accessible label for the documents sort control.\",\"text\":\"Tri des documents\"},\"documents.controls.sort.date\":{\"context\":\"Short sort label shown before the sort control is rebuilt in JavaScript.\",\"text\":\"Date\"},\"documents.date_column.created\":{\"context\":\"Compact column label used when sorting by creation date.\",\"text\":\"Créé le\"},\"documents.date_column.updated\":{\"context\":\"Compact column label used when sorting by updated date.\",\"text\":\"Modifié le\"},\"documents.drawer.close\":{\"context\":\"Button label used to close document drawers.\",\"text\":\"Fermer\"},\"documents.drawer.detail_description\":{\"context\":\"Description shown in the document detail drawer.\",\"text\":\"Lecture du document dans OMO.\"},\"documents.drawer.detail_title\":{\"context\":\"Title shown in the document detail drawer.\",\"text\":\"Détail du document\"},\"documents.drawer.editor_description\":{\"context\":\"Description shown in the document editor drawer.\",\"text\":\"Création d’un document dans le contexte courant.\"},\"documents.drawer.editor_title\":{\"context\":\"Title shown in the document editor drawer.\",\"text\":\"Nouveau document\"},\"documents.empty.available_children\":{\"context\":\"Empty state shown when no document exists in direct child scope.\",\"text\":\"Aucun document disponible pour ce contexte ou ses enfants directs.\"},\"documents.empty.available_contextual\":{\"context\":\"Empty state shown when no document exists in contextual scope.\",\"text\":\"Aucun document disponible pour ce contexte.\"},\"documents.empty.available_descendants\":{\"context\":\"Empty state shown when no document exists in descendant scope.\",\"text\":\"Aucun document disponible pour ce contexte et ses descendants.\"},\"documents.empty.visible_children\":{\"context\":\"Empty state shown when hidden documents exist in direct child scope.\",\"one\":\"Aucun document visible pour ce contexte ou ses enfants directs. {count} fichier est caché.\",\"other\":\"Aucun document visible pour ce contexte ou ses enfants directs. {count} fichiers sont cachés.\"},\"documents.empty.visible_contextual\":{\"context\":\"Empty state shown when hidden documents exist in contextual scope.\",\"one\":\"Aucun document visible pour ce contexte. {count} fichier est caché.\",\"other\":\"Aucun document visible pour ce contexte. {count} fichiers sont cachés.\"},\"documents.empty.visible_descendants\":{\"context\":\"Empty state shown when hidden documents exist in descendant scope.\",\"one\":\"Aucun document visible pour ce contexte et ses descendants. {count} fichier est caché.\",\"other\":\"Aucun document visible pour ce contexte et ses descendants. {count} fichiers sont cachés.\"},\"documents.error.load_document\":{\"context\":\"Error shown when a document drawer cannot load its detail view.\",\"text\":\"Impossible de charger ce document.\"},\"documents.error.load_editor\":{\"context\":\"Error shown when the document editor drawer cannot load.\",\"text\":\"Impossible de charger l’éditeur du document.\"},\"documents.group.earlier\":{\"context\":\"Relative date group title for older documents.\",\"text\":\"Plus ancien\"},\"documents.group.last_month\":{\"context\":\"Relative date group title for documents updated last month.\",\"text\":\"Mois dernier\"},\"documents.group.last_week\":{\"context\":\"Relative date group title for documents updated last week.\",\"text\":\"Semaine dernière\"},\"documents.group.this_month\":{\"context\":\"Relative date group title for documents updated earlier this month.\",\"text\":\"Ce mois\"},\"documents.group.this_week\":{\"context\":\"Relative date group title for documents updated earlier this week.\",\"text\":\"Cette semaine\"},\"documents.group.this_year\":{\"context\":\"Relative date group title for documents updated earlier this year.\",\"text\":\"Cette année\"},\"documents.group.today\":{\"context\":\"Relative date group title for documents updated today.\",\"text\":\"Aujourd\'hui\"},\"documents.group.too_far\":{\"context\":\"Fallback relative date group title for documents with missing or invalid dates.\",\"text\":\"Date inconnue\"},\"documents.group.yesterday\":{\"context\":\"Relative date group title for documents updated yesterday.\",\"text\":\"Hier\"},\"documents.menu.action_error\":{\"context\":\"Fallback error shown when a document lifecycle action fails.\",\"text\":\"Action impossible.\"},\"documents.menu.archive\":{\"context\":\"Menu action used to hide a document from the document list.\",\"text\":\"Archiver\"},\"documents.menu.confirm_archive\":{\"context\":\"Confirmation shown before archiving a document.\",\"text\":\"Archiver ce document ? Il ne sera plus visible dans la liste.\"},\"documents.menu.confirm_delete\":{\"context\":\"Confirmation shown before permanently deleting a document.\",\"text\":\"Supprimer definitivement ce document ?\"},\"documents.menu.delete\":{\"context\":\"Menu action used to permanently delete an unreferenced document.\",\"text\":\"Supprimer\"},\"documents.menu.export_pdf\":{\"context\":\"Menu action used to download a PV document as a PDF file.\",\"text\":\"Exporter en PDF\"},\"documents.page.title\":{\"context\":\"Main title of the documents application.\",\"text\":\"Documents\"},\"documents.scope.children\":{\"context\":\"Label used to show documents from the current holon and its direct children.\",\"text\":\"Enfants directs\"},\"documents.scope.contextual\":{\"context\":\"Label used to show only documents from the current holon.\",\"text\":\"Contextuel\"},\"documents.scope.descendants\":{\"context\":\"Label used to show documents from the current holon and its descendants.\",\"text\":\"Descendants\"},\"documents.scope.edit\":{\"context\":\"Short label used before the document edit scope in tooltips.\",\"text\":\"Editer\"},\"documents.scope.toggle_aria\":{\"context\":\"Accessible label for the document scope toggle.\",\"text\":\"Portée des documents\"},\"documents.scope.view\":{\"context\":\"Short label used before the document visibility scope in tooltips.\",\"text\":\"Voir\"},\"documents.sort.alpha_aria\":{\"context\":\"Accessible label for the alphabetical sort button.\",\"text\":\"Alphabétique\"},\"documents.sort.created\":{\"context\":\"Label for the creation date sort button.\",\"text\":\"Création\"},\"documents.sort.created_aria\":{\"context\":\"Accessible label for the creation date sort button.\",\"text\":\"Date de création\"},\"documents.sort.updated\":{\"context\":\"Label for the updated date sort button.\",\"text\":\"Modification\"},\"documents.sort.updated_aria\":{\"context\":\"Accessible label for the updated date sort button.\",\"text\":\"Date de modification\"},\"documents.upload_missing.badge\":{\"context\":\"Warning badge shown on an uploaded document without a stored file.\",\"text\":\"Fichier absent\"}}','completed',1,NULL,'2026-07-25 11:14:06','2026-07-25 11:14:13','2026-07-25 11:14:06','2026-07-25 11:14:13'),
(8,'omo_get_structure_panel','en','542d102221f255e01f98cbcc450c9164056b4d03f2a94fc2ccbdba944505fd4c','{\"structure.actions.export\":{\"context\":\"Structure action menu item used to export the current structure.\",\"text\":\"Export\"},\"structure.actions.export.download\":{\"context\":\"Button label used in the structure export modal to start a file download.\",\"text\":\"Telecharger\"},\"structure.actions.export.format.csv\":{\"context\":\"Label used for the CSV structure export format.\",\"text\":\"CSV\"},\"structure.actions.export.format.csv_description\":{\"context\":\"Description shown for the CSV structure export format.\",\"text\":\"Vue a plat des holons. Les droits sont listes dans une cellule avec leur code et leur portee.\"},\"structure.actions.export.format.json\":{\"context\":\"Label used for the JSON structure export format.\",\"text\":\"JSON\"},\"structure.actions.export.format.json_description\":{\"context\":\"Description shown for the JSON structure export format.\",\"text\":\"Format complet pour reimporter la structure. Il inclut aussi les droits des holons et des templates.\"},\"structure.actions.export.format.xml\":{\"context\":\"Label used for the XML structure export format.\",\"text\":\"XML\"},\"structure.actions.export.format.xml_description\":{\"context\":\"Description shown for the XML structure export format.\",\"text\":\"Format structure et lisible, avec les memes codes de droits que le JSON.\"},\"structure.actions.export.modal_intro\":{\"context\":\"Intro text shown in the structure export modal.\",\"text\":\"Choisissez le format d export de cette structure.\"},\"structure.actions.export.modal_title\":{\"context\":\"Modal title shown when choosing a structure export format.\",\"text\":\"Exporter la structure\"},\"structure.actions.menu_aria\":{\"context\":\"Aria label for the structure action menu toggle button.\",\"text\":\"Actions\"},\"structure.actions.print\":{\"context\":\"Structure action menu item used to print the current structure.\",\"text\":\"Imprimer\"},\"structure.actions.share\":{\"context\":\"Structure action menu item used to open the sharing dialog for the current structure.\",\"text\":\"Partager\"},\"structure.browser.generic_name\":{\"context\":\"Fallback browser name used in structure warnings when the exact browser cannot be detected.\",\"text\":\"ce navigateur\"},\"structure.error.organization_access_denied\":{\"context\":\"Error message shown when the structure view cannot access the current organization.\",\"text\":\"Acces refuse a cette organisation.\"},\"structure.list.empty_search\":{\"context\":\"Message shown in the structure list view when the current search returns no visible nodes.\",\"text\":\"Aucun noeud ne correspond a cette recherche.\"},\"structure.list.properties.hide_aria\":{\"context\":\"Aria label for the button that collapses role details in the structure list view.\",\"text\":\"Masquer les proprietes\"},\"structure.list.properties.show_aria\":{\"context\":\"Aria label for the button that expands role details in the structure list view.\",\"text\":\"Afficher les proprietes\"},\"structure.list.search.placeholder\":{\"context\":\"Placeholder shown above the structure list view search field.\",\"text\":\"Filtre rapide\"},\"structure.message.disabled\":{\"context\":\"Message shown when the Structure app is disabled for the current organization.\",\"text\":\"L app Structure est desactivee pour cette organisation.\"},\"structure.message.invalid\":{\"context\":\"Fallback error message shown when the structure data payload is invalid.\",\"text\":\"Structure invalide.\"},\"structure.message.load_error\":{\"context\":\"Fallback error message shown when the structure view fails to load its data.\",\"text\":\"Impossible de charger la structure.\"},\"structure.message.no_structure\":{\"context\":\"Message shown when the current organization has no visible structure to display.\",\"text\":\"Aucune structure disponible pour cette organisation.\"},\"structure.placeholder.action\":{\"context\":\"Call to action shown in the main structure panel when the organization has no structure yet.\",\"text\":\"Ouvrir l app Structure\"},\"structure.placeholder.text\":{\"context\":\"Informational text shown in the main structure panel when the organization has no structure yet.\",\"text\":\"Aucune structure n est encore definie pour cette organisation. Ouvrez l app Structure dans la leftbar pour creer une structure vide, importer un export ou partir d un modele.\"},\"structure.placeholder.title\":{\"context\":\"Title shown in the main structure panel when the organization has no structure yet.\",\"text\":\"Aucune structure\"},\"structure.share.modal_title\":{\"context\":\"Modal title used when opening the share dialog from the structure action menu.\",\"text\":\"Partager la structure\"},\"structure.view.toggle_label\":{\"context\":\"Short toggle label used to switch between organization graph view and list view in the structure panel.\",\"text\":\"O   L\"},\"structure.warning.brave\":{\"context\":\"Warning shown in the structure panel when Brave blocks canvas pixel reading.\",\"text\":\"Brave semble bloquer la lecture du canvas utilisee pour la navigation graphique, probablement a cause du bouclier anti-empreinte numerique. La vue liste a ete activee pour continuer a naviguer. Vous pouvez aussi assouplir le bouclier pour ce site.\"},\"structure.warning.dismiss_aria\":{\"context\":\"Aria label for the button that collapses the structure browser warning.\",\"text\":\"Reduire ce message\"},\"structure.warning.pixel_mismatch\":{\"context\":\"Warning shown in the structure panel when the browser alters canvas pixel reading.\",\"text\":\"{browserName} bloque ou altere la lecture du canvas utilisee pour la navigation graphique. La vue liste a ete activee pour continuer a naviguer.\"},\"structure.warning.restore\":{\"context\":\"Button label used to reopen the collapsed browser warning in the structure panel.\",\"text\":\"Info navigateur\"},\"structure.warning.unavailable\":{\"context\":\"Warning shown in the structure panel when canvas pixel reading is unavailable in the current browser.\",\"text\":\"La lecture du canvas utilisee pour la navigation graphique n est pas disponible dans {browserName}. La vue liste a ete activee pour continuer a naviguer.\"}}','completed',1,NULL,'2026-07-25 11:14:27','2026-07-25 11:14:32','2026-07-25 11:14:27','2026-07-25 11:14:32'),
(9,'omo_parameters_index','en','5ce9a7de80546eb9722918e4eefef9fae72cd46d2851970ea7b7c68ed1edf225','{\"parameters.index.action.close\":{\"context\":\"Close button label used in the nested settings drawer header.\",\"text\":\"Fermer\"},\"parameters.index.card.application.description\":{\"context\":\"Card description used to open an installed application settings screen from the settings hub.\",\"text\":\"Configurer les options et integrations de {applicationName} pour {organizationName}.\"},\"parameters.index.card.application.eyebrow\":{\"context\":\"Small eyebrow label shown on app-specific settings cards in the settings hub.\",\"text\":\"Application\"},\"parameters.index.card.application.forbidden\":{\"context\":\"Card description shown when the current user cannot manage an installed application settings screen.\",\"text\":\"Activez le mode admin de l organisation pour modifier les parametres de {applicationName}.\"},\"parameters.index.card.holon_templates.description\":{\"context\":\"Card description used to open the holon template settings editor from the settings hub.\",\"text\":\"Configurer les types de nœuds et leurs propriétés pour votre organisation.\"},\"parameters.index.card.holon_templates.eyebrow\":{\"context\":\"Small eyebrow label shown on the holon templates card of the settings hub.\",\"text\":\"Architecture\"},\"parameters.index.card.holon_templates.title\":{\"context\":\"Card title used to open the holon template settings editor from the settings hub.\",\"text\":\"Modèles de holons\"},\"parameters.index.card.organization.description\":{\"context\":\"Card description used when the current user can edit the organization settings.\",\"text\":\"Modifier le nom, le nom court, la position géographique, les illustrations et la couleur de {organizationName}.\"},\"parameters.index.card.organization.eyebrow\":{\"context\":\"Small eyebrow label shown on the organization card of the settings hub.\",\"text\":\"Votre structure\"},\"parameters.index.card.organization.fallback_name\":{\"context\":\"Fallback organization name used in the settings hub when the current organization has no display name.\",\"text\":\"cette organisation\"},\"parameters.index.card.organization.forbidden\":{\"context\":\"Card description shown when the current user cannot edit the organization settings.\",\"text\":\"Vous devez être admin de l\'organisation pour modifier ces paramètres.\"},\"parameters.index.card.organization.title\":{\"context\":\"Card title used to open the organization settings editor from the settings hub.\",\"text\":\"Organisation\"},\"parameters.index.card.profile.description\":{\"context\":\"Card description used to open the current user profile editor from the settings hub.\",\"text\":\"Ouvrir l\'édition de votre profil.\"},\"parameters.index.card.profile.eyebrow\":{\"context\":\"Small eyebrow label shown on the profile card of the settings hub.\",\"text\":\"Mon compte\"},\"parameters.index.card.profile.title\":{\"context\":\"Card title used to open the current user profile editor from the settings hub.\",\"text\":\"Profil\"},\"parameters.index.card.server_admin.description\":{\"context\":\"Card description used to open the sensitive server environment settings popup from the settings hub.\",\"text\":\"Ouvrir les réglages globaux sensibles du fichier .env, hors configuration de la base de données.\"},\"parameters.index.card.server_admin.eyebrow\":{\"context\":\"Small eyebrow label shown on the server administration card of the settings hub.\",\"text\":\"Maintenance\"},\"parameters.index.card.server_admin.title\":{\"context\":\"Card title used to open the server administration settings popup from the settings hub.\",\"text\":\"Admin du serveur\"},\"parameters.index.description\":{\"context\":\"Intro text shown at the top of the OMO settings hub.\",\"text\":\"Retrouvez ici vos réglages personnels ainsi que les écrans de configuration disponibles pour l\'organisation.\"},\"parameters.index.drawer.error\":{\"context\":\"Error shown inside the nested settings drawer when the requested content cannot be loaded.\",\"text\":\"Impossible de charger ce module.\"},\"parameters.index.drawer.loading\":{\"context\":\"Loading placeholder shown while a nested settings drawer is fetching its content.\",\"text\":\"Chargement...\"},\"parameters.index.empty.login\":{\"context\":\"Empty state shown in the settings hub when no user is connected.\",\"text\":\"Connectez-vous pour accéder à vos paramètres utilisateur.\"},\"parameters.index.title\":{\"context\":\"Main title of the OMO settings hub.\",\"text\":\"Paramètres\"}}','completed',1,NULL,'2026-07-25 11:14:51','2026-07-25 11:14:55','2026-07-25 11:14:51','2026-07-25 11:14:55'),
(10,'omo_parameters_server_env','en','53f1e52aa3ba401a5f5971f323bdf1e702282e5ac309e63a500ff365e0dd0a1f','{\"parameters.server_env.action.close\":{\"context\":\"Close button label used in the server environment popup.\",\"text\":\"Fermer\"},\"parameters.server_env.action.save\":{\"context\":\"Submit button label used to save the editable .env form.\",\"text\":\"Enregistrer {target}\"},\"parameters.server_env.auth.forbidden_message\":{\"context\":\"Message shown in the server environment popup when the current user is not a site admin.\",\"text\":\"Ce panneau est réservé à l\'admin du serveur.\"},\"parameters.server_env.auth.forbidden_title\":{\"context\":\"Title shown in the server environment popup when the current user is not a site admin.\",\"text\":\"Accès refusé\"},\"parameters.server_env.auth.required_message\":{\"context\":\"Message shown in the server environment popup when the user is not connected.\",\"text\":\"Connectez-vous pour accéder à ce panneau.\"},\"parameters.server_env.auth.required_title\":{\"context\":\"Title shown in the server environment popup when the user is not connected.\",\"text\":\"Connexion requise\"},\"parameters.server_env.edit.secret_hint\":{\"context\":\"Hint shown above the editable .env form.\",\"text\":\"Les champs secrets restent masqués. Si vous laissez un champ secret vide, la valeur actuelle est conservée.\"},\"parameters.server_env.edit.title\":{\"context\":\"Title shown above the editable .env form.\",\"text\":\"Modifier {target}\"},\"parameters.server_env.error.forbidden\":{\"context\":\"JSON error returned when the current user is not allowed to use the server environment endpoints.\",\"text\":\"Accès réservé à l\'admin du serveur.\"},\"parameters.server_env.error.invalid_field_value\":{\"context\":\"Validation error returned when a field value is invalid in the server environment editor.\",\"text\":\"La valeur choisie pour {label} est invalide.\"},\"parameters.server_env.error.password_invalid\":{\"context\":\"JSON error returned when the provided password is invalid for the server environment endpoints.\",\"text\":\"Mot de passe invalide.\"},\"parameters.server_env.error.password_required\":{\"context\":\"JSON error returned when no password was provided to unlock the server environment endpoints.\",\"text\":\"Veuillez renseigner votre mot de passe.\"},\"parameters.server_env.error.password_unavailable\":{\"context\":\"JSON error returned when the current account has no local password for the server environment endpoints.\",\"text\":\"Ce compte ne dispose pas de mot de passe local vérifiable.\"},\"parameters.server_env.error.read_failed\":{\"context\":\"Error returned when the server environment target file cannot be read.\",\"text\":\"Impossible de lire le fichier {target}.\"},\"parameters.server_env.error.required\":{\"context\":\"JSON error returned when the current user is not connected to use the server environment endpoints.\",\"text\":\"Connexion requise.\"},\"parameters.server_env.error.unlock_required\":{\"context\":\"JSON error returned when the server environment save endpoint requires a password confirmation.\",\"text\":\"Confirmation du mot de passe requise.\"},\"parameters.server_env.error.write_failed\":{\"context\":\"Error returned when the server environment target file cannot be written.\",\"text\":\"Impossible d\'écrire le fichier {target}. Vérifiez les permissions ou un montage Docker en lecture seule.\"},\"parameters.server_env.feedback.invalid_response\":{\"context\":\"Error shown when the server environment popup receives an invalid JSON response.\",\"text\":\"Réponse invalide.\"},\"parameters.server_env.feedback.operation_done\":{\"context\":\"Generic fallback message shown after saving the server environment popup form.\",\"text\":\"Opération terminée.\"},\"parameters.server_env.feedback.save_failed\":{\"context\":\"Error shown when saving the .env file failed unexpectedly in the server environment popup.\",\"text\":\"Impossible d\'enregistrer le fichier {target}.\"},\"parameters.server_env.feedback.unlock_failed\":{\"context\":\"Error shown when the password verification failed unexpectedly in the server environment popup.\",\"text\":\"Vérification impossible.\"},\"parameters.server_env.feedback.unlock_success\":{\"context\":\"Success message shown after unlocking the server environment popup form.\",\"text\":\"Vérification effectuée.\"},\"parameters.server_env.field.APP_LANG.label\":{\"context\":\"Label of the APP_LANG field in the server environment editor.\",\"text\":\"Langue par défaut\"},\"parameters.server_env.field.COOKIE_ROOT_HOST.help\":{\"context\":\"Help text of the COOKIE_ROOT_HOST field in the server environment editor.\",\"text\":\"Optionnel. Si renseigné, force le partage des cookies à cette racine exacte, par exemple dev.opengov.tools pour partager entre dev.opengov.tools et *.dev.opengov.tools sans toucher à la prod.\"},\"parameters.server_env.field.COOKIE_ROOT_HOST.label\":{\"context\":\"Label of the COOKIE_ROOT_HOST field in the server environment editor.\",\"text\":\"Racine cookies\"},\"parameters.server_env.field.COOKIE_SCOPE_MODE.help\":{\"context\":\"Help text of the COOKIE_SCOPE_MODE field in the server environment editor.\",\"text\":\"Auto isole par défaut dev, beta et deploy en host-only. Environment partage dans *.dev.domaine.tld. Parent partage dans *.domaine.tld. Host force un cookie limité au host courant.\"},\"parameters.server_env.field.COOKIE_SCOPE_MODE.label\":{\"context\":\"Label of the COOKIE_SCOPE_MODE field in the server environment editor.\",\"text\":\"Portée des cookies\"},\"parameters.server_env.field.GITHUB_BUGREPORT_LABELS.label\":{\"context\":\"Label of the GITHUB_BUGREPORT_LABELS field in the server environment editor.\",\"text\":\"Labels GitHub\"},\"parameters.server_env.field.GITHUB_BUGREPORT_REPO_NAME.label\":{\"context\":\"Label of the GITHUB_BUGREPORT_REPO_NAME field in the server environment editor.\",\"text\":\"Repository name GitHub\"},\"parameters.server_env.field.GITHUB_BUGREPORT_REPO_OWNER.label\":{\"context\":\"Label of the GITHUB_BUGREPORT_REPO_OWNER field in the server environment editor.\",\"text\":\"Repository owner GitHub\"},\"parameters.server_env.field.GITHUB_BUGREPORT_TOKEN.label\":{\"context\":\"Label of the GITHUB_BUGREPORT_TOKEN field in the server environment editor.\",\"text\":\"Token GitHub bug report\"},\"parameters.server_env.field.GITHUB_BUGREPORT_USER_AGENT.label\":{\"context\":\"Label of the GITHUB_BUGREPORT_USER_AGENT field in the server environment editor.\",\"text\":\"User-Agent GitHub\"},\"parameters.server_env.field.HOME_TITLE.label\":{\"context\":\"Label of the HOME_TITLE field in the server environment editor.\",\"text\":\"Titre de la page d\'accueil\"},\"parameters.server_env.field.MAIL_AUTH.label\":{\"context\":\"Label of the MAIL_AUTH field in the server environment editor.\",\"text\":\"Authentification SMTP\"},\"parameters.server_env.field.MAIL_CHARSET.label\":{\"context\":\"Label of the MAIL_CHARSET field in the server environment editor.\",\"text\":\"Jeu de caractères e-mail\"},\"parameters.server_env.field.MAIL_HOST.label\":{\"context\":\"Label of the MAIL_HOST field in the server environment editor.\",\"text\":\"Serveur SMTP\"},\"parameters.server_env.field.MAIL_PASS.label\":{\"context\":\"Label of the MAIL_PASS field in the server environment editor.\",\"text\":\"Mot de passe SMTP\"},\"parameters.server_env.field.MAIL_PORT.label\":{\"context\":\"Label of the MAIL_PORT field in the server environment editor.\",\"text\":\"Port SMTP\"},\"parameters.server_env.field.MAIL_SECURE.label\":{\"context\":\"Label of the MAIL_SECURE field in the server environment editor.\",\"text\":\"Sécurité SMTP\"},\"parameters.server_env.field.MAIL_SECURE.placeholder\":{\"context\":\"Placeholder of the MAIL_SECURE field in the server environment editor.\",\"text\":\"SSL, tls ou vide\"},\"parameters.server_env.field.MAIL_USER.label\":{\"context\":\"Label of the MAIL_USER field in the server environment editor.\",\"text\":\"Utilisateur SMTP\"},\"parameters.server_env.field.OPENAI_API_KEY.label\":{\"context\":\"Label of the OPENAI_API_KEY field in the server environment editor.\",\"text\":\"Clé OpenAI\"},\"parameters.server_env.field.OPENAI_MODEL.label\":{\"context\":\"Label of the OPENAI_MODEL field in the server environment editor.\",\"text\":\"Modèle OpenAI\"},\"parameters.server_env.field.OPENAI_TRANSLATION_MODEL.label\":{\"context\":\"Label of the OPENAI_TRANSLATION_MODEL field in the server environment editor.\",\"text\":\"Modèle de traduction OpenAI\"},\"parameters.server_env.field.OPENAI_UPLOAD_API_KEY.label\":{\"context\":\"Label of the OPENAI_UPLOAD_API_KEY field in the server environment editor.\",\"text\":\"Clé OpenAI upload\"},\"parameters.server_env.field.ORGANIZATION_SUBDOMAIN_ROUTING.help\":{\"context\":\"Help text of the ORGANIZATION_SUBDOMAIN_ROUTING field in the server environment editor.\",\"text\":\"Active les URL du type orgname.domaine.com. Cela demande une configuration spéciale de l\'hébergement, avec DNS wildcard et serveur web capable d\'accepter les sous-domaines.\"},\"parameters.server_env.field.ORGANIZATION_SUBDOMAIN_ROUTING.label\":{\"context\":\"Label of the ORGANIZATION_SUBDOMAIN_ROUTING field in the server environment editor.\",\"text\":\"Sous-domaines par organisation\"},\"parameters.server_env.field.PATREON_CLIENT_ID.label\":{\"context\":\"Label of the PATREON_CLIENT_ID field in the server environment editor.\",\"text\":\"Client ID Patreon\"},\"parameters.server_env.field.PATREON_CLIENT_SECRET.label\":{\"context\":\"Label of the PATREON_CLIENT_SECRET field in the server environment editor.\",\"text\":\"Client secret Patreon\"},\"parameters.server_env.field.PATREON_CREATOR_CAMPAIGN_ID.label\":{\"context\":\"Label of the PATREON_CREATOR_CAMPAIGN_ID field in the server environment editor.\",\"text\":\"Campaign ID Patreon\"},\"parameters.server_env.field.PATREON_REDIRECT_URI.label\":{\"context\":\"Label of the PATREON_REDIRECT_URI field in the server environment editor.\",\"text\":\"Redirect URI Patreon\"},\"parameters.server_env.field.PATREON_USER_AGENT.label\":{\"context\":\"Label of the PATREON_USER_AGENT field in the server environment editor.\",\"text\":\"User-Agent Patreon\"},\"parameters.server_env.field.PAYPAL_CLIENT_ID.label\":{\"context\":\"Label of the PAYPAL_CLIENT_ID field in the server environment editor.\",\"text\":\"Client ID PayPal\"},\"parameters.server_env.field.SITE_TITLE.label\":{\"context\":\"Label of the SITE_TITLE field in the server environment editor.\",\"text\":\"Titre du site\"},\"parameters.server_env.field.STADIA_MAPS_API_KEY.label\":{\"context\":\"Label of the STADIA_MAPS_API_KEY field in the server environment editor.\",\"text\":\"Clé Stadia Maps\"},\"parameters.server_env.field.TELEGRAM_BOT_TOKEN.label\":{\"context\":\"Label of the TELEGRAM_BOT_TOKEN field in the server environment editor.\",\"text\":\"Token Telegram\"},\"parameters.server_env.field.secret_keep.help\":{\"context\":\"Help text shown under secret fields in the server environment editor.\",\"text\":\"Laissez vide pour conserver la valeur actuelle.\"},\"parameters.server_env.hero.description\":{\"context\":\"Intro text shown in the server environment popup.\",\"text\":\"Ce panneau permet de compléter les variables globales du fichier d\'environnement hors base de données, comme Telegram, Patreon, OpenAI, SMTP ou GitHub.\"},\"parameters.server_env.hero.eyebrow\":{\"context\":\"Small eyebrow shown above the server environment popup title.\",\"text\":\"Configuration sensible\"},\"parameters.server_env.hero.target\":{\"context\":\"Badge showing the target .env file edited by the server environment popup.\",\"text\":\"Fichier cible : {target}\"},\"parameters.server_env.hero.title\":{\"context\":\"Main title of the server environment popup.\",\"text\":\"Admin du serveur\"},\"parameters.server_env.hero.unlock_ttl\":{\"context\":\"Badge showing how long the password confirmation remains valid in the server environment popup.\",\"text\":\"Vérification valable {minutes} min\"},\"parameters.server_env.option.boolean.false\":{\"context\":\"No option label used in boolean selects in the server environment editor.\",\"text\":\"Non\"},\"parameters.server_env.option.boolean.true\":{\"context\":\"Yes option label used in boolean selects in the server environment editor.\",\"text\":\"Oui\"},\"parameters.server_env.password.unavailable_message\":{\"context\":\"Message shown when the current account has no local password for server environment editing.\",\"text\":\"Ce compte n\'a pas de mot de passe local vérifiable. L\'édition de {target} via ce panneau est donc bloquée pour le moment.\"},\"parameters.server_env.password.unavailable_title\":{\"context\":\"Title shown when the current account has no local password for server environment editing.\",\"text\":\"Mot de passe indisponible\"},\"parameters.server_env.secret.configured\":{\"context\":\"Status label shown next to a configured secret field in the server environment popup.\",\"text\":\"Déjà configuré\"},\"parameters.server_env.secret.empty\":{\"context\":\"Status label shown next to an empty secret field in the server environment popup.\",\"text\":\"Non renseigné\"},\"parameters.server_env.section.ai.intro\":{\"context\":\"Intro of the AI section in the server environment editor.\",\"text\":\"Clés et modèles utilisés par les fonctions OpenAI.\"},\"parameters.server_env.section.ai.title\":{\"context\":\"Title of the AI section in the server environment editor.\",\"text\":\"IA\"},\"parameters.server_env.section.general.intro\":{\"context\":\"Intro of the general section in the server environment editor.\",\"text\":\"Réglages globaux du site visibles sur plusieurs pages.\"},\"parameters.server_env.section.general.title\":{\"context\":\"Title of the general section in the server environment editor.\",\"text\":\"Paramètres généraux\"},\"parameters.server_env.section.integrations.intro\":{\"context\":\"Intro of the integrations section in the server environment editor.\",\"text\":\"Services externes optionnels du serveur.\"},\"parameters.server_env.section.integrations.title\":{\"context\":\"Title of the integrations section in the server environment editor.\",\"text\":\"Intégrations\"},\"parameters.server_env.section.mail.intro\":{\"context\":\"Intro of the mail section in the server environment editor.\",\"text\":\"Configuration SMTP générale du serveur.\"},\"parameters.server_env.section.mail.title\":{\"context\":\"Title of the mail section in the server environment editor.\",\"text\":\"E-mail\"},\"parameters.server_env.status.saved\":{\"context\":\"JSON success message returned after saving the server environment file.\",\"text\":\"Le fichier {target} a été mis à jour.\"},\"parameters.server_env.status.unlocked\":{\"context\":\"JSON success message returned after unlocking the server environment endpoints.\",\"text\":\"Vérification effectuée.\"},\"parameters.server_env.unlock.description\":{\"context\":\"Intro text shown before unlocking the server environment popup form.\",\"text\":\"Avant d\'afficher le formulaire, saisissez le mot de passe du compte connecté. Cela déverrouille temporairement l\'édition de ce panneau.\"},\"parameters.server_env.unlock.password_label\":{\"context\":\"Label of the password field used to unlock the server environment popup.\",\"text\":\"Mot de passe actuel\"},\"parameters.server_env.unlock.submit\":{\"context\":\"Submit button label used to unlock the server environment popup form.\",\"text\":\"Ouvrir le formulaire\"},\"parameters.server_env.unlock.title\":{\"context\":\"Title shown before unlocking the server environment popup form.\",\"text\":\"Vérifier votre identité\"}}','completed',1,NULL,'2026-07-25 11:14:57','2026-07-25 11:15:10','2026-07-25 11:14:57','2026-07-25 11:15:10'),
(11,'omo_projects','en','ffcce9ebb2bec5eb75efb65337c720625c463ded7016ae23e4bfb3d84e785e04','{\"projects.action.archive\":{\"context\":\"Context menu action archiving a project.\",\"text\":\"Archiver\"},\"projects.action.attach\":{\"context\":\"Button attaching an existing orphan project as a subproject.\",\"text\":\"Attacher un projet\"},\"projects.action.cancel\":{\"context\":\"Button cancelling project creation.\",\"text\":\"Annuler\"},\"projects.action.close\":{\"context\":\"Button closing the project subdrawer.\",\"text\":\"Fermer\"},\"projects.action.delete\":{\"context\":\"Context menu action permanently deleting a project.\",\"text\":\"Supprimer\"},\"projects.action.edit\":{\"context\":\"Button opening project edition from the detail header.\",\"text\":\"Modifier\"},\"projects.action.move\":{\"context\":\"Context menu action moving a project to another holon.\",\"text\":\"Deplacer\"},\"projects.action.new\":{\"context\":\"Primary action opening project creation.\",\"text\":\"Nouveau projet\"},\"projects.action.save\":{\"context\":\"Submit action saving a project.\",\"text\":\"Enregistrer\"},\"projects.action_error\":{\"context\":\"Fallback error shown for a project context action.\",\"text\":\"Impossible de mettre a jour le projet.\"},\"projects.archive.confirm\":{\"context\":\"Confirmation before archiving an unfinished project.\",\"text\":\"Ce projet n est pas termine. L archiver quand meme ?\"},\"projects.attach.empty\":{\"context\":\"Empty state in the attach existing project modal.\",\"text\":\"Aucun projet sans parent ne correspond a la recherche.\"},\"projects.attach.hint\":{\"context\":\"Instruction in the attach existing project modal.\",\"text\":\"Choisissez un projet sans parent dans la structure.\"},\"projects.attach.search\":{\"context\":\"Search placeholder in the attach existing project modal.\",\"text\":\"Rechercher un projet\"},\"projects.attach.select_required\":{\"context\":\"Validation message when no project is selected for attachment.\",\"text\":\"Choisissez un projet a attacher.\"},\"projects.attach.submit\":{\"context\":\"Submit button attaching the selected project.\",\"text\":\"Attacher\"},\"projects.attach.title\":{\"context\":\"Modal title for attaching an orphan project as a subproject.\",\"text\":\"Attacher un projet\"},\"projects.column.next\":{\"context\":\"Accessible label for the next mobile Kanban column button.\",\"text\":\"Colonne suivante\"},\"projects.column.previous\":{\"context\":\"Accessible label for the previous mobile Kanban column button.\",\"text\":\"Colonne precedente\"},\"projects.delete.confirm\":{\"context\":\"Confirmation before permanent project deletion.\",\"text\":\"Supprimer definitivement ce projet et ses {count} sous-projets ? Cette action est irreversible.\"},\"projects.detail.badge\":{\"context\":\"Eyebrow label shown above the project detail title.\",\"text\":\"Projet\"},\"projects.detail.breadcrumb\":{\"context\":\"Accessible label for the project parent breadcrumb.\",\"text\":\"Projets parents\"},\"projects.detail.breadcrumb.expand\":{\"context\":\"Accessible title for the collapsed project breadcrumb button.\",\"text\":\"Afficher tous les projets parents\"},\"projects.detail.calculated_importance\":{\"context\":\"Server-calculated project importance label.\",\"text\":\"Importance strategique calculee\"},\"projects.detail.calculated_importance_help\":{\"context\":\"Help text for server-calculated project importance.\",\"text\":\"Calculee a partir de l importance strategique declaree, de la chaine de projets et de la position holarchique.\"},\"projects.detail.context\":{\"context\":\"Project detail holon section label.\",\"text\":\"Contexte\"},\"projects.detail.created\":{\"context\":\"Project detail creation date label.\",\"text\":\"Cree le\"},\"projects.detail.date_end\":{\"context\":\"Planned end date label.\",\"text\":\"Fin\"},\"projects.detail.date_start\":{\"context\":\"Planned start date label.\",\"text\":\"Debut\"},\"projects.detail.description\":{\"context\":\"Project detail description section label.\",\"text\":\"Description\"},\"projects.detail.empty_description\":{\"context\":\"Fallback when the project has no description.\",\"text\":\"Aucune description pour ce projet.\"},\"projects.detail.importance\":{\"context\":\"Project detail importance label.\",\"text\":\"Importance strategique\"},\"projects.detail.importance_level\":{\"context\":\"Project importance level.\",\"one\":\"{count}/5\",\"other\":\"{count}/5\"},\"projects.detail.none\":{\"context\":\"Fallback for missing project metadata.\",\"text\":\"Non renseigne\"},\"projects.detail.organisation\":{\"context\":\"Project detail organization label.\",\"text\":\"Organisation\"},\"projects.detail.parent\":{\"context\":\"Project detail parent label.\",\"text\":\"Projet parent\"},\"projects.detail.priority\":{\"context\":\"Project detail priority label.\",\"text\":\"Priorite\"},\"projects.detail.priority_level\":{\"context\":\"Project priority level.\",\"one\":\"P{count}\",\"other\":\"P{count}\"},\"projects.detail.responsible\":{\"context\":\"Project detail responsible person label.\",\"text\":\"Responsable\"},\"projects.detail.schedule\":{\"context\":\"Project detail planned dates section label.\",\"text\":\"Dates planifiees\"},\"projects.detail.size\":{\"context\":\"Project detail project size label.\",\"text\":\"Taille\"},\"projects.detail.status\":{\"context\":\"Project detail status label.\",\"text\":\"Statut\"},\"projects.detail.subprojects\":{\"context\":\"Project detail subprojects section label.\",\"text\":\"Sous-projets\"},\"projects.detail.subprojects_empty\":{\"context\":\"Empty state shown in the project detail subprojects section.\",\"text\":\"Aucun sous-projet pour le moment.\"},\"projects.detail.subprojects_new\":{\"context\":\"Button creating a new subproject from a project detail.\",\"text\":\"Nouveau\"},\"projects.drawer.description\":{\"context\":\"Default description of the project subdrawer.\",\"text\":\"Details et informations du projet.\"},\"projects.drawer.title\":{\"context\":\"Default title of the project subdrawer.\",\"text\":\"Projet\"},\"projects.empty.children\":{\"context\":\"Empty state for the direct child holon scope.\",\"text\":\"Aucun projet dans ce contexte ou ses enfants directs.\"},\"projects.empty.column\":{\"context\":\"Empty state for one empty Kanban column.\",\"text\":\"Aucun projet dans cette colonne.\"},\"projects.empty.contextual\":{\"context\":\"Empty state for the local project scope.\",\"text\":\"Aucun projet dans ce contexte.\"},\"projects.empty.descendants\":{\"context\":\"Empty state for the descendant project scope.\",\"text\":\"Aucun projet dans ce contexte ou ses descendants.\"},\"projects.error.action\":{\"context\":\"Error for an unsupported project action.\",\"text\":\"Action inconnue.\"},\"projects.error.context\":{\"context\":\"Error for an invalid holon context.\",\"text\":\"Contexte invalide ou inaccessible.\"},\"projects.error.dates\":{\"context\":\"Validation error when the planned end date precedes the planned start date.\",\"text\":\"La date de fin doit etre posterieure ou egale a la date de debut.\"},\"projects.error.forbidden\":{\"context\":\"Error when project mutation is forbidden.\",\"text\":\"Vous ne pouvez pas modifier ce projet.\"},\"projects.error.holon\":{\"context\":\"Error for an invalid project move target holon.\",\"text\":\"Le holon de destination est invalide ou inaccessible.\"},\"projects.error.method\":{\"context\":\"Error for a mutation sent with the wrong HTTP method.\",\"text\":\"Cette action doit etre envoyee en POST.\"},\"projects.error.not_found\":{\"context\":\"Error when a project cannot be loaded.\",\"text\":\"Projet introuvable.\"},\"projects.error.organization\":{\"context\":\"Error for an invalid organization context.\",\"text\":\"Organisation invalide ou inaccessible.\"},\"projects.error.save\":{\"context\":\"Generic project persistence error.\",\"text\":\"Impossible d enregistrer le projet.\"},\"projects.error.status\":{\"context\":\"Validation error for an invalid project status.\",\"text\":\"Le statut du projet est invalide.\"},\"projects.error.title\":{\"context\":\"Validation error for a missing project title.\",\"text\":\"Le titre est obligatoire.\"},\"projects.field.capture_mode\":{\"context\":\"Project Telegram capture mode field label.\",\"text\":\"Mode de capture Telegram\"},\"projects.field.description\":{\"context\":\"Project description field label.\",\"text\":\"Description\"},\"projects.field.description_placeholder\":{\"context\":\"Placeholder for the project description field.\",\"text\":\"Quel resultat voulez-vous obtenir ?\"},\"projects.field.end_date\":{\"context\":\"Project planned end date field label.\",\"text\":\"Fin planifiee\"},\"projects.field.holon\":{\"context\":\"Project assignment holon field label.\",\"text\":\"Cercle ou role associe\"},\"projects.field.importance\":{\"context\":\"Project creation importance field label.\",\"text\":\"Importance strategique\"},\"projects.field.parent\":{\"context\":\"Project parent field label.\",\"text\":\"Projet parent\"},\"projects.field.priority\":{\"context\":\"Project creation priority field label.\",\"text\":\"Priorite\"},\"projects.field.responsible\":{\"context\":\"Project responsible user field label.\",\"text\":\"Responsable\"},\"projects.field.size\":{\"context\":\"Project size field label.\",\"text\":\"Taille\"},\"projects.field.start_date\":{\"context\":\"Project planned start date field label.\",\"text\":\"Debut planifie\"},\"projects.field.status\":{\"context\":\"Project creation status field label.\",\"text\":\"Statut initial\"},\"projects.field.title\":{\"context\":\"Project title field label.\",\"text\":\"Titre du projet\"},\"projects.form.assignment\":{\"context\":\"Section title grouping the responsible person and parent project in the project form.\",\"text\":\"Responsabilite et hierarchie\"},\"projects.form.attention\":{\"context\":\"Section title grouping priority and importance controls in the project form.\",\"text\":\"Niveau d attention\"},\"projects.form.description\":{\"context\":\"Project creation form introduction.\",\"text\":\"Definissez le but, les dates et le niveau d attention du projet.\"},\"projects.form.description_field\":{\"context\":\"Label for the project HTML description editor.\",\"text\":\"Description HTML simple\"},\"projects.form.edit_description\":{\"context\":\"Project edition form introduction.\",\"text\":\"Mettez a jour le but, les dates et les parametres du projet.\"},\"projects.form.edit_submit\":{\"context\":\"Submit button saving project changes.\",\"text\":\"Enregistrer les modifications\"},\"projects.form.edit_title\":{\"context\":\"Project edition form title.\",\"text\":\"Modifier le projet\"},\"projects.form.more_options\":{\"context\":\"Collapsed project form section title for secondary settings.\",\"text\":\"Options supplementaires\"},\"projects.form.more_options_toggle\":{\"context\":\"Accessible label for the secondary project form options accordion.\",\"text\":\"Afficher ou masquer les options supplementaires\"},\"projects.form.planning\":{\"context\":\"Section title grouping status and planned dates in the project form.\",\"text\":\"Planification\"},\"projects.form.submit\":{\"context\":\"Submit button creating a project.\",\"text\":\"Creer le projet\"},\"projects.form.title\":{\"context\":\"Project creation form title.\",\"text\":\"Nouveau projet\"},\"projects.holon.choose\":{\"context\":\"Button opening the project holon picker.\",\"text\":\"Choisir un cercle ou role\"},\"projects.holon_picker.confirm\":{\"context\":\"Confirmation button in the project holon picker modal.\",\"text\":\"Utiliser ce contexte\"},\"projects.holon_picker.hint\":{\"context\":\"Instruction in the project holon picker modal.\",\"text\":\"Choisissez le cercle ou le role auquel confier ce projet.\"},\"projects.holon_picker.title\":{\"context\":\"Modal title for selecting the project assignment holon.\",\"text\":\"Choisir le cercle ou role\"},\"projects.importance.none\":{\"context\":\"Empty option for project importance.\",\"text\":\"Non definie\"},\"projects.level.none\":{\"context\":\"Zero level label for priority and importance range controls.\",\"text\":\"Non definie\"},\"projects.list.planned.after_tomorrow\":{\"context\":\"Project list planned group for the day after tomorrow.\",\"text\":\"Apres-demain\"},\"projects.list.planned.in_progress\":{\"context\":\"Project list planned group for projects currently within their planned dates.\",\"text\":\"En cours\"},\"projects.list.planned.later\":{\"context\":\"Project list planned group for future dates.\",\"text\":\"Plus tard\"},\"projects.list.planned.next_week\":{\"context\":\"Project list planned group for next week.\",\"text\":\"La semaine prochaine\"},\"projects.list.planned.none\":{\"context\":\"Project list planned group without dates.\",\"text\":\"Sans planification\"},\"projects.list.planned.overdue\":{\"context\":\"Project list planned group for past dates.\",\"text\":\"En retard\"},\"projects.list.planned.this_week\":{\"context\":\"Project list planned group for the rest of this week.\",\"text\":\"Cette semaine\"},\"projects.list.planned.tomorrow\":{\"context\":\"Project list planned group for tomorrow.\",\"text\":\"Demain\"},\"projects.list.priority.none\":{\"context\":\"Project list priority group without priority.\",\"text\":\"Sans priorite\"},\"projects.loading\":{\"context\":\"Loading message shown inside the project subdrawer.\",\"text\":\"Chargement du projet...\"},\"projects.loading_error\":{\"context\":\"Error shown when a project drawer cannot be loaded.\",\"text\":\"Impossible de charger ce projet.\"},\"projects.move.hint\":{\"context\":\"Instruction in the project holon move dialog.\",\"text\":\"Choisissez le holon de destination dans la structure.\"},\"projects.move.select_required\":{\"context\":\"Validation message when no target holon is selected.\",\"text\":\"Choisissez un holon de destination.\"},\"projects.move.submit\":{\"context\":\"Submit button in the project holon move dialog.\",\"text\":\"Deplacer ici\"},\"projects.move.title\":{\"context\":\"Title of the project holon move dialog.\",\"text\":\"Deplacer le projet\"},\"projects.parent.choose\":{\"context\":\"Button opening the parent project picker in the project form.\",\"text\":\"Choisir un projet\"},\"projects.parent.none\":{\"context\":\"Empty parent project value in the project form.\",\"text\":\"Aucun projet parent\"},\"projects.parent_picker.choose\":{\"context\":\"Confirmation button in the parent project picker modal.\",\"text\":\"Utiliser ce projet\"},\"projects.parent_picker.empty\":{\"context\":\"Empty state in the parent project picker modal.\",\"text\":\"Aucun projet ne correspond a la recherche.\"},\"projects.parent_picker.none\":{\"context\":\"Empty option in the parent project picker modal.\",\"text\":\"Sans projet parent\"},\"projects.parent_picker.scope_children\":{\"context\":\"Direct child scope label in the parent project picker structure navigation.\",\"text\":\"Enfants directs\"},\"projects.parent_picker.scope_descendants\":{\"context\":\"Descendant scope label in the parent project picker structure navigation.\",\"text\":\"Descendants\"},\"projects.parent_picker.scope_local\":{\"context\":\"Local scope label in the parent project picker structure navigation.\",\"text\":\"Local\"},\"projects.parent_picker.search\":{\"context\":\"Search placeholder in the parent project picker modal.\",\"text\":\"Rechercher un projet\"},\"projects.parent_picker.title\":{\"context\":\"Modal title for selecting a parent project.\",\"text\":\"Choisir le projet parent\"},\"projects.priority.none\":{\"context\":\"Empty option for project priority.\",\"text\":\"Non definie\"},\"projects.responsible.help\":{\"context\":\"Help text below the responsible person selector in the project form.\",\"text\":\"Seules les personnes actives de cette organisation sont proposees.\"},\"projects.responsible.none\":{\"context\":\"Empty responsible person option in the project form.\",\"text\":\"Aucun responsable\"},\"projects.scope.children\":{\"context\":\"Scope showing projects attached to the current holon and its direct children.\",\"text\":\"Enfants directs\"},\"projects.scope.contextual\":{\"context\":\"Scope showing projects attached to the current holon.\",\"text\":\"Local\"},\"projects.scope.descendants\":{\"context\":\"Scope showing projects attached to the current holon and its descendants.\",\"text\":\"Descendants\"},\"projects.sort.aria\":{\"context\":\"Accessible label for the project list sort selector.\",\"text\":\"Classer les projets\"},\"projects.sort.holon\":{\"context\":\"Project list sort button.\",\"text\":\"Holon\"},\"projects.sort.importance\":{\"context\":\"Project list sort button.\",\"text\":\"Importance strategique\"},\"projects.sort.planned\":{\"context\":\"Project list sort button.\",\"text\":\"Planification\"},\"projects.sort.priority\":{\"context\":\"Project list sort button.\",\"text\":\"Priorite\"},\"projects.status.blocked\":{\"context\":\"Project status label.\",\"text\":\"Bloque\"},\"projects.status.done\":{\"context\":\"Project status label.\",\"text\":\"Termine\"},\"projects.status.in_progress\":{\"context\":\"Project status label.\",\"text\":\"En cours\"},\"projects.status.ready\":{\"context\":\"Project status label.\",\"text\":\"Pret\"},\"projects.status.review\":{\"context\":\"Project status label.\",\"text\":\"A verifier\"},\"projects.status.someday\":{\"context\":\"Project status label.\",\"text\":\"Un jour peut-etre\"},\"projects.status_move\":{\"context\":\"Accessible label for the project status move control.\",\"text\":\"Changer le statut\"},\"projects.status_update_error\":{\"context\":\"Fallback error shown when a project status cannot be changed.\",\"text\":\"Impossible de changer le statut.\"},\"projects.subprojects.label\":{\"context\":\"Accessible label for the recursive subproject status bar.\",\"text\":\"Etat des sous-projets\"},\"projects.success.save\":{\"context\":\"Success message after project creation.\",\"text\":\"Projet enregistre.\"},\"projects.success.status\":{\"context\":\"Success message after changing project status.\",\"text\":\"Statut mis a jour.\"},\"projects.title\":{\"context\":\"Main title of the projects application.\",\"text\":\"Projets\"},\"projects.view.aria\":{\"context\":\"Accessible label for the project display mode selector.\",\"text\":\"Mode d affichage\"},\"projects.view.kanban\":{\"context\":\"Project display mode button.\",\"text\":\"Kanban\"},\"projects.view.list\":{\"context\":\"Project display mode button.\",\"text\":\"Liste\"}}','completed',1,NULL,'2026-07-25 11:15:20','2026-07-25 11:15:40','2026-07-25 11:15:20','2026-07-25 11:15:40'),
(12,'omo_decisions_index','en','5185290b694c46963ce1b3cce5ac0cf6f985b6957738bd6c2511dcd6d70abc95','{\"decisions.index.action.archive\":{\"context\":\"Manager action used to archive a decision that already has votes.\",\"text\":\"Archiver\"},\"decisions.index.action.confirm_archive\":{\"context\":\"Confirmation message before archiving a decision from the list.\",\"text\":\"Archiver cette prise de décision ?\"},\"decisions.index.action.confirm_delete\":{\"context\":\"Confirmation message before deleting a decision from the list.\",\"text\":\"Supprimer définitivement cette prise de décision et ses éléments liés ?\"},\"decisions.index.action.consult\":{\"context\":\"Primary action label for an archived decision.\",\"text\":\"Consulter\"},\"decisions.index.action.delete\":{\"context\":\"Manager action used to delete a decision that has no submitted votes yet.\",\"text\":\"Supprimer\"},\"decisions.index.action.edit_continue\":{\"context\":\"Primary action label for a draft decision.\",\"text\":\"Continuer l’édition\"},\"decisions.index.action.error_update\":{\"context\":\"Fallback error message shown when a decision archive or delete action fails.\",\"text\":\"Impossible de mettre à jour cette prise de décision pour le moment.\"},\"decisions.index.action.export\":{\"context\":\"Menu action used to open the export picker for one decision.\",\"text\":\"Export\"},\"decisions.index.action.manage\":{\"context\":\"Primary action label for an evaluation decision when the user can manage it.\",\"text\":\"Gérer\"},\"decisions.index.action.more\":{\"context\":\"Label of the secondary menu button shown on detailed decision cards.\",\"text\":\"...\"},\"decisions.index.action.more_aria\":{\"context\":\"Accessible label for the secondary action menu button shown on detailed cards.\",\"text\":\"Plus d\'actions pour cette prise de décision\"},\"decisions.index.action.open\":{\"context\":\"Primary action label for a consultation decision.\",\"text\":\"Ouvrir\"},\"decisions.index.action.open_editor_title\":{\"context\":\"Drawer title used when opening the decision editor from the list.\",\"text\":\"Décisions\"},\"decisions.index.action.participant_qr_codes\":{\"context\":\"Menu action used to open a printable sheet of participant QR codes for one decision.\",\"text\":\"Imprimer les codes QR\"},\"decisions.index.action.participate\":{\"context\":\"Primary action label for an evaluation decision when the user is a participant.\",\"text\":\"Participer\"},\"decisions.index.action.view\":{\"context\":\"Secondary action label for viewing an ongoing decision.\",\"text\":\"Voir\"},\"decisions.index.action.view_edit\":{\"context\":\"Primary action label for a scheduled decision.\",\"text\":\"Voir / modifier\"},\"decisions.index.action.view_results\":{\"context\":\"Primary action label for a results decision.\",\"text\":\"Voir les résultats\"},\"decisions.index.card.invited_email\":{\"context\":\"Badge shown when the current access comes from an e-mail invitation.\",\"text\":\"Invitation par e-mail\"},\"decisions.index.card.manage\":{\"context\":\"Badge shown when the user can manage the decision.\",\"text\":\"Gestion\"},\"decisions.index.card.owner\":{\"context\":\"Badge shown when the current user created the decision.\",\"text\":\"Créée par vous\"},\"decisions.index.compact.header.activity\":{\"context\":\"Column header for the compact decisions list activity column.\",\"text\":\"Activité\"},\"decisions.index.compact.header.name\":{\"context\":\"Column header for the compact decisions list main column.\",\"text\":\"Décision\"},\"decisions.index.compact.header.scope\":{\"context\":\"Column header for the compact decisions list scope column.\",\"text\":\"Structure\"},\"decisions.index.compact.header.status\":{\"context\":\"Column header for the compact decisions list status column.\",\"text\":\"Statut\"},\"decisions.index.context.holon_denied\":{\"context\":\"Error message when the user cannot access the requested holon context.\",\"text\":\"Accès refusé à ce holon.\"},\"decisions.index.context.holon_not_found\":{\"context\":\"Error message when the requested holon context cannot be loaded.\",\"text\":\"Holon introuvable pour cette organisation.\"},\"decisions.index.context.organization_denied\":{\"context\":\"Error message when the current user cannot view the organization.\",\"text\":\"Accès refusé à cette organisation.\"},\"decisions.index.context.organization_invalid\":{\"context\":\"Error message when the organization context is missing or invalid.\",\"text\":\"Organisation invalide.\"},\"decisions.index.context.organization_not_found\":{\"context\":\"Error message when the organization cannot be loaded.\",\"text\":\"Organisation introuvable.\"},\"decisions.index.controls.density.aria\":{\"context\":\"Accessible label for the display density segmented control.\",\"text\":\"Densité d\'affichage des prises de décision\"},\"decisions.index.controls.density.compact\":{\"context\":\"Label for the compact decisions list density.\",\"text\":\"Compact\"},\"decisions.index.controls.density.detail\":{\"context\":\"Label for the detailed decisions list density.\",\"text\":\"Détail\"},\"decisions.index.controls.sort.alpha\":{\"context\":\"Label for alphabetical sorting in the decisions list.\",\"text\":\"Alphabétique\"},\"decisions.index.controls.sort.aria\":{\"context\":\"Accessible label for the sort segmented control.\",\"text\":\"Tri des prises de décision\"},\"decisions.index.controls.sort.time\":{\"context\":\"Label for time-based sorting in the decisions list.\",\"text\":\"Temporel\"},\"decisions.index.deadline_label\":{\"context\":\"Card metadata label for a closing date or deadline.\",\"text\":\"Échéance\"},\"decisions.index.description\":{\"context\":\"Short description shown under the decisions module title.\",\"text\":\"Centralisez ici les consultations et prises de décision accessibles dans votre organisation, puis ouvrez le bon flux selon leur statut.\"},\"decisions.index.empty.cta\":{\"context\":\"Call to action inside the empty state.\",\"text\":\"Créer la première prise de décision\"},\"decisions.index.empty.text\":{\"context\":\"Empty state body when no decision exists yet.\",\"text\":\"Créez votre première prise de décision pour préparer un vote, un jugement majoritaire, un consentement ou une consultation.\"},\"decisions.index.empty.title\":{\"context\":\"Empty state title when the organization has no decisions yet.\",\"text\":\"Aucune prise de décision pour le moment\"},\"decisions.index.error\":{\"context\":\"Fallback error label when the client rendering of the decision list fails.\",\"text\":\"Impossible de charger la liste pour le moment.\"},\"decisions.index.export.format.coming_soon\":{\"context\":\"Label shown for unavailable export formats.\",\"text\":\"Bientôt disponible\"},\"decisions.index.export.format.csv\":{\"context\":\"Label for the CSV export format.\",\"text\":\"CSV\"},\"decisions.index.export.format.csv_description\":{\"context\":\"Description of the CSV export format.\",\"text\":\"Tableau enrichi avec type, bloc, question, détails et résultats.\"},\"decisions.index.export.format.json\":{\"context\":\"Label for the JSON export format.\",\"text\":\"JSON\"},\"decisions.index.export.format.json_description\":{\"context\":\"Description of the JSON export format.\",\"text\":\"Blueprint du scrutin et résultats structurés, sans dump complet.\"},\"decisions.index.export.format.pdf\":{\"context\":\"Label for the PDF export format.\",\"text\":\"PDF\"},\"decisions.index.export.format.pdf_description\":{\"context\":\"Description of the PDF export format.\",\"text\":\"Version de présentation préparée pour plus tard.\"},\"decisions.index.export.format.xml\":{\"context\":\"Label for the XML export format.\",\"text\":\"XML\"},\"decisions.index.export.format.xml_description\":{\"context\":\"Description of the XML export format.\",\"text\":\"Même contenu structuré que le JSON, dans un format XML.\"},\"decisions.index.export.modal_intro\":{\"context\":\"Intro text shown inside the export picker modal.\",\"text\":\"Choisissez le format d\'export adapté à ce mode de prise de décision.\"},\"decisions.index.export.modal_title\":{\"context\":\"Modal title shown when choosing one export format from the decision list.\",\"text\":\"Exporter ce scrutin\"},\"decisions.index.export.open\":{\"context\":\"Button label used to trigger the selected export.\",\"text\":\"Télécharger\"},\"decisions.index.filters.holon.all\":{\"context\":\"Select option showing every structure.\",\"text\":\"Toutes les structures\"},\"decisions.index.filters.holon.label\":{\"context\":\"Label for the structure select filter.\",\"text\":\"Structure\"},\"decisions.index.filters.holon.none\":{\"context\":\"Select option for organization-level decisions without a linked structure.\",\"text\":\"Sans structure\"},\"decisions.index.filters.method.all\":{\"context\":\"Select option showing every evaluation method.\",\"text\":\"Toutes les méthodes\"},\"decisions.index.filters.method.consent\":{\"context\":\"UI label for the consent method.\",\"text\":\"Consentement\"},\"decisions.index.filters.method.label\":{\"context\":\"Label for the evaluation method select filter.\",\"text\":\"Méthode\"},\"decisions.index.filters.method.majority_judgment\":{\"context\":\"UI label for the majority judgment method.\",\"text\":\"Jugement majoritaire\"},\"decisions.index.filters.method.simple_vote\":{\"context\":\"UI label for the simple vote method.\",\"text\":\"Vote simple\"},\"decisions.index.filters.reset\":{\"context\":\"Secondary button resetting the list filters.\",\"text\":\"Réinitialiser\"},\"decisions.index.filters.search.label\":{\"context\":\"Accessible label for the decision search input.\",\"text\":\"Recherche par titre\"},\"decisions.index.filters.search.placeholder\":{\"context\":\"Placeholder inside the decision search input.\",\"text\":\"Rechercher une prise de décision\"},\"decisions.index.filters.status.active\":{\"context\":\"Status filter label for currently active decisions.\",\"text\":\"Actif\"},\"decisions.index.filters.status.all\":{\"context\":\"Status filter label showing every decision.\",\"text\":\"Toutes\"},\"decisions.index.filters.status.archived\":{\"context\":\"Status filter label for archived decisions.\",\"text\":\"Archivées\"},\"decisions.index.filters.status.consultation\":{\"context\":\"Status filter label for consultation decisions.\",\"text\":\"En consultation\"},\"decisions.index.filters.status.draft\":{\"context\":\"Status filter label for draft decisions.\",\"text\":\"En préparation\"},\"decisions.index.filters.status.evaluation\":{\"context\":\"Status filter label for evaluation decisions.\",\"text\":\"En évaluation\"},\"decisions.index.filters.status.results\":{\"context\":\"Status filter label for decisions with published results.\",\"text\":\"Résultats\"},\"decisions.index.filters.status.scheduled\":{\"context\":\"Status filter label for scheduled decisions.\",\"text\":\"Planifiées\"},\"decisions.index.filters.toggle.hide\":{\"context\":\"Button label used to hide advanced filters below the status tabs.\",\"text\":\"Masquer les filtres\"},\"decisions.index.filters.toggle.show\":{\"context\":\"Button label used to reveal advanced filters below the status tabs.\",\"text\":\"Afficher les filtres\"},\"decisions.index.filters.type.all\":{\"context\":\"Select option showing every decision type.\",\"text\":\"Tous les types\"},\"decisions.index.filters.type.consultation\":{\"context\":\"UI label for a consultation-oriented decision process.\",\"text\":\"Consultative\"},\"decisions.index.filters.type.decision\":{\"context\":\"UI label for a decision-oriented decision process.\",\"text\":\"Décisionnaire\"},\"decisions.index.filters.type.label\":{\"context\":\"Label for the type select filter.\",\"text\":\"Type\"},\"decisions.index.group.earlier\":{\"context\":\"Relative date group title for older decisions.\",\"text\":\"Précédemment\"},\"decisions.index.group.last_month\":{\"context\":\"Relative date group title for decisions updated last month.\",\"text\":\"Le mois passé\"},\"decisions.index.group.last_week\":{\"context\":\"Relative date group title for decisions updated last week.\",\"text\":\"La semaine passée\"},\"decisions.index.group.last_year\":{\"context\":\"Relative date group title for decisions updated last year.\",\"text\":\"L\'année passée\"},\"decisions.index.group.this_month\":{\"context\":\"Relative date group title for decisions updated this month.\",\"text\":\"Ce mois\"},\"decisions.index.group.this_week\":{\"context\":\"Relative date group title for decisions updated this week.\",\"text\":\"Cette semaine\"},\"decisions.index.group.this_year\":{\"context\":\"Relative date group title for decisions updated this year.\",\"text\":\"Cette année\"},\"decisions.index.group.today\":{\"context\":\"Relative date group title for decisions updated today.\",\"text\":\"Aujourd\'hui\"},\"decisions.index.group.too_far\":{\"context\":\"Fallback relative date group title for decisions with missing dates.\",\"text\":\"Trop loin\"},\"decisions.index.group.yesterday\":{\"context\":\"Relative date group title for decisions updated yesterday.\",\"text\":\"Hier\"},\"decisions.index.last_activity_label\":{\"context\":\"Card metadata label for last activity.\",\"text\":\"Dernière activité\"},\"decisions.index.loading\":{\"context\":\"Temporary loading label displayed while the decision list initializes.\",\"text\":\"Chargement des décisions…\"},\"decisions.index.method_label\":{\"context\":\"Card metadata label for the evaluation method.\",\"text\":\"Méthode\"},\"decisions.index.new\":{\"context\":\"Primary call to action opening the decision creation screen.\",\"text\":\"Nouvelle prise de décision\"},\"decisions.index.no_holon\":{\"context\":\"Fallback label when a decision has no associated structure.\",\"text\":\"Sans structure liée\"},\"decisions.index.no_results.text\":{\"context\":\"State body when filters hide every decision.\",\"text\":\"Essayez un autre statut, élargissez la recherche ou réinitialisez les filtres.\"},\"decisions.index.no_results.title\":{\"context\":\"State title when filters hide every decision.\",\"text\":\"Aucun résultat avec ces filtres\"},\"decisions.index.owner_label\":{\"context\":\"Card metadata label for the person in charge of the decision.\",\"text\":\"En charge\"},\"decisions.index.participants_label\":{\"context\":\"Card stat label for participant count.\",\"text\":\"Participants\"},\"decisions.index.proposals_label\":{\"context\":\"Card stat label for proposal count.\",\"text\":\"Propositions\"},\"decisions.index.responses_label\":{\"context\":\"Card stat label for submitted response count.\",\"text\":\"Réponses\"},\"decisions.index.scope.children\":{\"context\":\"Label used to show decisions from the current holon and its direct children.\",\"text\":\"Enfants directs\"},\"decisions.index.scope.contextual\":{\"context\":\"Label used to show only decisions from the current holon context.\",\"text\":\"Contextuel\"},\"decisions.index.scope.descendants\":{\"context\":\"Label used to show decisions from the current holon and its descendants.\",\"text\":\"Descendants\"},\"decisions.index.scope_label\":{\"context\":\"Fallback card metadata label for the related structure.\",\"text\":\"Structure\"},\"decisions.index.title\":{\"context\":\"Main title of the decisions drawer entry screen.\",\"text\":\"Décisions\"},\"decisions.index.type_label\":{\"context\":\"Card metadata label for the decision type.\",\"text\":\"Type\"}}','completed',1,NULL,'2026-07-25 11:15:26','2026-07-25 11:15:40','2026-07-25 11:15:26','2026-07-25 11:15:40'),
(13,'omo_policy','en','f8552f359ef3640d166c6644fc54771ee825759d8085d611bf7567fde4012d66','{\"policy.close\":{\"context\":\"Close drawer action.\",\"text\":\"Fermer\"},\"policy.description\":{\"context\":\"Policy application description.\",\"text\":\"Regles applicables au contexte courant.\"},\"policy.description_label\":{\"context\":\"Rule content section title.\",\"text\":\"Regle\"},\"policy.drawer.description\":{\"context\":\"Local rule creation drawer description.\",\"text\":\"Cette regle sera rattachee au contexte actuel.\"},\"policy.drawer.title\":{\"context\":\"Local rule creation drawer title.\",\"text\":\"Nouvelle regle locale\"},\"policy.empty\":{\"context\":\"Empty policy list.\",\"text\":\"Aucune regle dans ce contexte.\"},\"policy.error.context\":{\"context\":\"Invalid policy context.\",\"text\":\"Contexte invalide ou inaccessible.\"},\"policy.error.forbidden\":{\"context\":\"Unauthorized rule creation.\",\"text\":\"Vous ne pouvez pas creer de regle dans ce contexte.\"},\"policy.error.load\":{\"context\":\"Local rule editor load error.\",\"text\":\"Impossible de charger le formulaire.\"},\"policy.error.method\":{\"context\":\"Invalid HTTP method.\",\"text\":\"Cette action doit etre envoyee en POST.\"},\"policy.error.save\":{\"context\":\"Rule save error.\",\"text\":\"Impossible d enregistrer la regle.\"},\"policy.expiration\":{\"context\":\"Rule expiration date label.\",\"text\":\"Echeance le {date}\"},\"policy.field.description\":{\"context\":\"Rule HTML content field.\",\"text\":\"Regle\"},\"policy.field.expiration_date\":{\"context\":\"Rule expiration date field.\",\"text\":\"Date d echeance\"},\"policy.field.intention\":{\"context\":\"Rule intent field.\",\"text\":\"Intention\"},\"policy.field.review_date\":{\"context\":\"Rule review date field.\",\"text\":\"Date de requestionnement\"},\"policy.field.title\":{\"context\":\"Rule title field.\",\"text\":\"Titre\"},\"policy.intention\":{\"context\":\"Rule intent section title.\",\"text\":\"Intention\"},\"policy.new\":{\"context\":\"Create a new local rule.\",\"text\":\"Nouvelle regle\"},\"policy.review\":{\"context\":\"Rule review date label.\",\"text\":\"A requestionner le {date}\"},\"policy.save\":{\"context\":\"Save local rule action.\",\"text\":\"Enregistrer\"},\"policy.success.save\":{\"context\":\"Rule creation confirmation.\",\"text\":\"Regle enregistree.\"},\"policy.title\":{\"context\":\"Policy application title.\",\"text\":\"Reglement\"}}','completed',1,NULL,'2026-07-25 11:15:46','2026-07-25 11:15:50','2026-07-25 11:15:46','2026-07-25 11:15:50'),
(14,'omo_calendar_index','en','ba735f0a0155ce61529b7855b48be4302cda03f70df56628cb88ffcd7bf3e145','{\"calendar.action.add\":{\"context\":\"Primary button used to open the event creation drawer.\",\"text\":\"Ajouter un événement\"},\"calendar.action.delete\":{\"context\":\"Action shown in the compact event menu for events the current user can delete.\",\"text\":\"Supprimer\"},\"calendar.action.edit\":{\"context\":\"Action shown in the compact event menu for events the current user can edit.\",\"text\":\"Editer\"},\"calendar.action.more\":{\"context\":\"Accessible label for the compact event action menu.\",\"text\":\"Actions\"},\"calendar.action.today\":{\"context\":\"Button used to return to the current month in the calendar application.\",\"text\":\"Aujourd\'hui\"},\"calendar.axis.all_day\":{\"context\":\"Label used for the all-day row in week and day views.\",\"text\":\"Journée\"},\"calendar.confirm.delete\":{\"context\":\"Confirmation shown before deleting an event from the compact menu.\",\"text\":\"Supprimer cet evenement ?\"},\"calendar.context.organization\":{\"context\":\"Fallback context label when the organization root is displayed in the calendar application.\",\"text\":\"Organisation\"},\"calendar.day.fri\":{\"context\":\"Short weekday label in the monthly calendar view.\",\"text\":\"Ven\"},\"calendar.day.mon\":{\"context\":\"Short weekday label in the monthly calendar view.\",\"text\":\"Lun\"},\"calendar.day.more\":{\"context\":\"Label shown inside a day cell when additional events are hidden.\",\"one\":\"+{count} autre\",\"other\":\"+{count} autres\"},\"calendar.day.sat\":{\"context\":\"Short weekday label in the monthly calendar view.\",\"text\":\"Sam\"},\"calendar.day.sun\":{\"context\":\"Short weekday label in the monthly calendar view.\",\"text\":\"Dim\"},\"calendar.day.thu\":{\"context\":\"Short weekday label in the monthly calendar view.\",\"text\":\"Jeu\"},\"calendar.day.tue\":{\"context\":\"Short weekday label in the monthly calendar view.\",\"text\":\"Mar\"},\"calendar.day.wed\":{\"context\":\"Short weekday label in the monthly calendar view.\",\"text\":\"Mer\"},\"calendar.delete.documents.no\":{\"context\":\"Choice that keeps documents linked to the event.\",\"text\":\"Non\"},\"calendar.delete.documents.question\":{\"context\":\"Question shown before deleting documents linked to an event.\",\"text\":\"Voulez-vous supprimer les documents associes ?\"},\"calendar.delete.documents.title\":{\"context\":\"Title of the choice dialog shown before deleting documents linked to an event.\",\"text\":\"Documents associes\"},\"calendar.delete.documents.yes\":{\"context\":\"Choice that deletes documents linked to the event.\",\"text\":\"Oui\"},\"calendar.drawer.description\":{\"context\":\"Description shown in the internal event drawer.\",\"text\":\"Consultez les détails puis modifiez si besoin.\"},\"calendar.drawer.title\":{\"context\":\"Title of the internal drawer used to inspect or edit an event from the calendar application.\",\"text\":\"Événement\"},\"calendar.empty.day\":{\"context\":\"Empty state shown when the current day contains no event.\",\"text\":\"Aucun événement sur cette journée.\"},\"calendar.empty.list\":{\"context\":\"Empty state shown when no upcoming event is available.\",\"text\":\"Aucun événement à venir.\"},\"calendar.empty.month\":{\"context\":\"Empty state shown when the current month contains no event.\",\"text\":\"Aucun événement sur cette période.\"},\"calendar.empty.week\":{\"context\":\"Empty state shown when the current week contains no event.\",\"text\":\"Aucun événement sur cette semaine.\"},\"calendar.error.delete\":{\"context\":\"Fallback error shown when deleting an event from the compact menu fails.\",\"text\":\"Impossible de supprimer cet evenement.\"},\"calendar.error.load_form\":{\"context\":\"Error shown inside the drawer when the event detail or form could not be loaded.\",\"text\":\"Impossible de charger ce contenu.\"},\"calendar.list.column.context\":{\"context\":\"Header label for the context column in the upcoming list view.\",\"text\":\"Contexte\"},\"calendar.list.column.date\":{\"context\":\"Header label for the date column in the upcoming list view.\",\"text\":\"Date\"},\"calendar.list.column.event\":{\"context\":\"Header label for the event title column in the upcoming list view.\",\"text\":\"Événement\"},\"calendar.list.column.schedule\":{\"context\":\"Header label for the schedule column in the upcoming list view.\",\"text\":\"Horaire\"},\"calendar.loading\":{\"context\":\"Loading label shown while fetching the event creation form.\",\"text\":\"Chargement...\"},\"calendar.page.description\":{\"context\":\"Introductory text shown in the calendar application.\",\"text\":\"Visualisez les événements de votre organisation et ajoutez-en de nouveaux.\"},\"calendar.page.title\":{\"context\":\"Main title of the calendar application.\",\"text\":\"Calendrier\"},\"calendar.scope.children\":{\"context\":\"Label used to show events from the current holon and its direct children.\",\"text\":\"Enfants directs\"},\"calendar.scope.contextual\":{\"context\":\"Label used to show only events from the current context.\",\"text\":\"Contextuel\"},\"calendar.scope.descendants\":{\"context\":\"Label used to show events from the current holon and its descendants.\",\"text\":\"Descendants\"},\"calendar.section.next_month\":{\"context\":\"Upcoming events section for events happening next month.\",\"text\":\"Le mois prochain\"},\"calendar.section.next_week\":{\"context\":\"Upcoming events section for events happening next week.\",\"text\":\"La semaine prochaine\"},\"calendar.section.this_month\":{\"context\":\"Upcoming events section for events happening later this month.\",\"text\":\"Ce mois\"},\"calendar.section.this_week\":{\"context\":\"Upcoming events section for events happening later this week.\",\"text\":\"Cette semaine\"},\"calendar.section.today\":{\"context\":\"Upcoming events section for events happening today.\",\"text\":\"Aujourd\'hui\"},\"calendar.section.tomorrow\":{\"context\":\"Upcoming events section for events happening tomorrow.\",\"text\":\"Demain\"},\"calendar.summary.day\":{\"context\":\"Summary badge for the daily calendar view.\",\"text\":\"{count} événement(s) ce jour\"},\"calendar.summary.list\":{\"context\":\"Summary badge for the upcoming list view.\",\"text\":\"{count} événement(s) à venir\"},\"calendar.summary.month\":{\"context\":\"Summary badge for the monthly calendar view.\",\"text\":\"{count} événement(s) ce mois\"},\"calendar.summary.week\":{\"context\":\"Summary badge for the weekly calendar view.\",\"text\":\"{count} événement(s) cette semaine\"},\"calendar.view.day\":{\"context\":\"Label used for the daily calendar view switch.\",\"text\":\"Jour\"},\"calendar.view.list\":{\"context\":\"Label used for the upcoming list view switch.\",\"text\":\"Liste\"},\"calendar.view.month\":{\"context\":\"Label used for the monthly calendar view switch.\",\"text\":\"Mois\"},\"calendar.view.week\":{\"context\":\"Label used for the weekly calendar view switch.\",\"text\":\"Semaine\"}}','completed',1,NULL,'2026-07-25 11:15:55','2026-07-25 11:16:01','2026-07-25 11:15:55','2026-07-25 11:16:01'),
(15,'omo_personal_space_panel','en','99a14c53fe57c583c8aecee4a7b4021b12046df2284878edf489a61a4338598a','{\"personal_space.calendar.context.organization\":{\"context\":\"Short fallback context label used for organization-wide events in the personal space panel.\",\"text\":\"Orga\"},\"personal_space.calendar.empty\":{\"context\":\"Empty state shown when no upcoming organization or member-holon event is available for the current user.\",\"text\":\"Aucune date à venir pour vos contextes.\"},\"personal_space.date.unknown\":{\"context\":\"Fallback label shown when no usable date is available for a listed item.\",\"text\":\"Date inconnue\"},\"personal_space.decisions.action\":{\"context\":\"Decision summary line for active decisions the user can answer.\",\"one\":\"{count} décision à prendre\",\"other\":\"{count} décisions à prendre\"},\"personal_space.decisions.consultation\":{\"context\":\"Decision summary line for consultation processes currently active.\",\"one\":\"{count} consultation en cours\",\"other\":\"{count} consultations en cours\"},\"personal_space.decisions.empty\":{\"context\":\"Empty state shown when the user has no tracked decisions in the current organization.\",\"text\":\"Aucune décision à suivre pour le moment.\"},\"personal_space.decisions.finalize\":{\"context\":\"Decision summary line for draft or scheduled decisions managed by the user.\",\"one\":\"{count} décision en préparation à finaliser\",\"other\":\"{count} décisions en préparation à finaliser\"},\"personal_space.decisions.responded\":{\"context\":\"Extra detail appended to the action summary line for already submitted responses.\",\"one\":\"dont {count} déjà répondue\",\"other\":\"dont {count} déjà répondues\"},\"personal_space.decisions.results\":{\"context\":\"Decision summary line for finished decisions with available results.\",\"one\":\"{count} décision terminée avec résultat à consulter\",\"other\":\"{count} décisions terminées avec résultats à consulter\"},\"personal_space.documents.empty\":{\"context\":\"Empty state shown when no recent documents are available in the current context.\",\"text\":\"Aucun document récent dans ce contexte.\"},\"personal_space.empty\":{\"context\":\"Fallback empty state when no supported applications are enabled in the sidebar.\",\"text\":\"Aucun résumé personnel disponible avec les applications actives pour le moment.\"},\"personal_space.heading\":{\"context\":\"Main title of the personal space panel shown on the right side of the OMO workspace.\",\"text\":\"Espace personnel\"},\"personal_space.intro\":{\"context\":\"Intro text displayed below the personal space title.\",\"text\":\"Un résumé rapide des sujets qui vous concernent dans cet espace.\"},\"personal_space.login_required\":{\"context\":\"Message shown when the personal space is requested without a logged in user.\",\"text\":\"Connectez-vous pour afficher votre résumé personnel.\"},\"personal_space.open_app\":{\"context\":\"Button label used to open the full application from the personal space card.\",\"text\":\"Ouvrir\"},\"personal_space.section.calendar\":{\"context\":\"Title of the upcoming meetings summary card in the personal space panel.\",\"text\":\"Mes prochaines réunions\"},\"personal_space.section.decisions\":{\"context\":\"Title of the decision summary card in the personal space panel.\",\"text\":\"Décisions\"},\"personal_space.section.documents_recent\":{\"context\":\"Title of the recent document activity card in the personal space panel.\",\"text\":\"Documents - dernières modifications\"},\"personal_space.section.structure\":{\"context\":\"Title of the structure summary card in the personal space panel.\",\"text\":\"Structure\"},\"personal_space.section.team\":{\"context\":\"Title of the team summary card in the personal space panel.\",\"text\":\"Team\"},\"personal_space.structure.empty\":{\"context\":\"Empty state shown when no recent structure history items are available.\",\"text\":\"Aucune modification récente à afficher.\"},\"personal_space.team.empty\":{\"context\":\"Empty state shown when no upcoming personal or professional anniversaries are found.\",\"text\":\"Aucun anniversaire proche à afficher.\"},\"personal_space.team.pro.new\":{\"context\":\"Headline shown for a new collaborator during the week after their arrival.\",\"text\":\"Nouveau\"},\"personal_space.team.pro.new_detail_prefix\":{\"context\":\"Detail prefix shown with the arrival date for a new collaborator.\",\"text\":\"Arrivé le\"},\"personal_space.team.pro.soon_prefix\":{\"context\":\"Prefix used before the remaining day count for a nearby professional anniversary.\",\"text\":\"Anniversaire pro dans\"},\"personal_space.team.pro.today\":{\"context\":\"Headline shown when a professional anniversary happens today.\",\"text\":\"Anniversaire pro aujourd\'hui\"},\"personal_space.team.tag.personal\":{\"context\":\"Short badge shown for a personal birthday in the team card.\",\"text\":\"Perso\"},\"personal_space.team.tag.pro\":{\"context\":\"Short badge shown for a professional join-date anniversary in the team card.\",\"text\":\"Pro\"}}','completed',1,NULL,'2026-07-25 11:22:13','2026-07-25 11:22:17','2026-07-25 11:22:13','2026-07-25 11:22:17'),
(16,'omo_team_module','en','e7a26a02adc42dd74ae6c21138da71c738f7ca52f05c6eb1de51633990ae57bb','{\"team.action.add_member\":{\"context\":\"Primary action button used to add a member in the team module.\",\"text\":\"Ajouter un membre\"},\"team.action.cancel_invitation\":{\"context\":\"Menu action used to cancel a pending invitation in the team module.\",\"text\":\"Annuler l\'invitation\"},\"team.action.grant_context_admin\":{\"context\":\"Menu action used to grant context admin rights in the team module.\",\"text\":\"Définir comme admin du contexte {context}\"},\"team.action.remove_from_context\":{\"context\":\"Menu action used to remove a member from a context in the team module.\",\"text\":\"Retirer du contexte {context}\"},\"team.action.revoke_context_admin\":{\"context\":\"Menu action used to revoke context admin rights in the team module.\",\"text\":\"Retirer le statut admin du contexte {context}\"},\"team.api.action_completed\":{\"context\":\"Fallback JSON message returned by the team member action endpoint when a member action completed without a specific message.\",\"text\":\"Action terminée.\"},\"team.api.context_not_found\":{\"context\":\"JSON error message returned by the team member action endpoint when the context cannot be loaded.\",\"text\":\"Contexte introuvable.\"},\"team.api.invalid_action\":{\"context\":\"JSON error message returned by the team member action endpoint when the payload is invalid.\",\"text\":\"Action membre invalide.\"},\"team.api.invitation_resend_failed\":{\"context\":\"JSON error message returned by the team member action endpoint when an invitation resend fails without a more specific error.\",\"text\":\"L\'invitation n\'a pas pu être renvoyée.\"},\"team.api.invitation_resent\":{\"context\":\"JSON success message returned by the team member action endpoint when a pending invitation is resent.\",\"text\":\"Invitation renvoyée.\"},\"team.api.no_right_add_member\":{\"context\":\"JSON error message returned by the team member action endpoint when the current user cannot add a member in the current context.\",\"text\":\"Vous n\'avez pas le droit d\'ajouter un membre dans ce contexte.\"},\"team.api.no_right_manage_admin\":{\"context\":\"JSON error message returned by the team member action endpoint when the current user cannot manage admin rights in the current context.\",\"text\":\"Vous n\'avez pas le droit de gérer le statut admin dans ce contexte.\"},\"team.api.no_right_modify_context\":{\"context\":\"JSON error message returned by the team member action endpoint when the current user cannot modify the current context.\",\"text\":\"Vous n\'avez pas le droit de modifier ce contexte.\"},\"team.api.pending_admin_invitation_not_found\":{\"context\":\"JSON error message returned by the team member action endpoint when no pending admin invitation is found for the target user.\",\"text\":\"Aucune invitation admin en attente n\'a été trouvée pour cette personne.\"},\"team.api.pending_invitation_not_found\":{\"context\":\"JSON error message returned by the team member action endpoint when no pending invitation is found for the target user.\",\"text\":\"Aucune invitation en attente n\'a été trouvée pour cette personne.\"},\"team.api.unknown_action\":{\"context\":\"JSON error message returned by the team member action endpoint when the requested action is unknown.\",\"text\":\"Action inconnue.\"},\"team.column.first_name\":{\"context\":\"First name column label in the compact team view.\",\"text\":\"Prénom\"},\"team.column.identity\":{\"context\":\"Identity field label in the compact team view.\",\"text\":\"Identité\"},\"team.column.name\":{\"context\":\"Surname column label in the compact team view.\",\"text\":\"Nom\"},\"team.column.phone\":{\"context\":\"Phone column label in the compact team view.\",\"text\":\"Téléphone\"},\"team.confirm.cancel_invitation\":{\"context\":\"Confirmation message shown before canceling a pending invitation in the team module.\",\"text\":\"Annuler l\'invitation envoyée à {name} ?\"},\"team.confirm.grant_context_admin\":{\"context\":\"Confirmation message shown before granting context admin rights in the team module.\",\"text\":\"Définir {name} comme admin du contexte {context} ?\"},\"team.confirm.remove\":{\"context\":\"Confirmation message shown before removing a member from a context in the team module.\",\"text\":\"Retirer {name} du contexte {context} ?\"},\"team.confirm.revoke_context_admin\":{\"context\":\"Confirmation message shown before revoking context admin rights in the team module.\",\"text\":\"Retirer le statut admin de {name} pour le contexte {context} ?\"},\"team.empty.children\":{\"context\":\"Empty state shown in the team module for the direct child scope.\",\"text\":\"Aucune personne n\'est encore liée à ce contexte ou à ses enfants directs.\"},\"team.empty.contextual\":{\"context\":\"Empty state shown in the team module for the contextual scope.\",\"text\":\"Aucune personne n\'est encore liée à ce {context_type}.\"},\"team.empty.descendants\":{\"context\":\"Empty state shown in the team module for the descendants scope.\",\"text\":\"Aucune personne n\'est encore liée à ce contexte et à ses descendants.\"},\"team.error.invalid_organization\":{\"context\":\"Error shown in the team module when no valid organization identifier is available.\",\"text\":\"Organisation invalide.\"},\"team.error.people_forbidden\":{\"context\":\"Error shown in the team module when the current share link cannot access the people list.\",\"text\":\"Accès refusé à la liste des personnes.\"},\"team.holon_type.circle\":{\"context\":\"Fallback holon type label used in the team module for a circle.\",\"text\":\"cercle\"},\"team.holon_type.context\":{\"context\":\"Fallback holon type label used in the team module for a generic context.\",\"text\":\"contexte\"},\"team.holon_type.group\":{\"context\":\"Fallback holon type label used in the team module for a group.\",\"text\":\"groupe\"},\"team.holon_type.holon\":{\"context\":\"Fallback holon type label used in the team module when no specific type matches.\",\"text\":\"holon\"},\"team.holon_type.organization\":{\"context\":\"Fallback holon type label used in the team module for an organization.\",\"text\":\"organisation\"},\"team.holon_type.role\":{\"context\":\"Fallback holon type label used in the team module for a role.\",\"text\":\"rôle\"},\"team.map.empty.children\":{\"context\":\"Map empty state shown in the team module for the direct child scope.\",\"text\":\"Aucun membre n\'a encore de position géographique dans ce contexte ou ses enfants directs.\"},\"team.map.empty.contextual\":{\"context\":\"Map empty state shown in the team module for the contextual scope.\",\"text\":\"Aucun membre n\'a encore de position géographique dans ce contexte.\"},\"team.map.empty.descendants\":{\"context\":\"Map empty state shown in the team module for the descendants scope.\",\"text\":\"Aucun membre n\'a encore de position géographique dans ce contexte et ses descendants.\"},\"team.map.open_profile\":{\"context\":\"Button label used in the team map popup to open the member profile.\",\"text\":\"Ouvrir la fiche\"},\"team.map.summary\":{\"context\":\"Summary shown above the team map with the number of geolocated members.\",\"one\":\"{count} membre géolocalisé.\",\"other\":\"{count} membres géolocalisés.\"},\"team.member.actions_for\":{\"context\":\"Accessible label used for the team member action menu button.\",\"text\":\"Actions pour {name}\"},\"team.member.added\":{\"context\":\"Label of the member creation date in the team module.\",\"text\":\"Ajout\"},\"team.member.admin_context\":{\"context\":\"Badge shown for a context admin in the team module.\",\"text\":\"Admin du contexte\"},\"team.member.admin_organization\":{\"context\":\"Badge shown for an organization admin in the team module.\",\"text\":\"Admin de l\'organisation\"},\"team.member.admin_short\":{\"context\":\"Short admin badge shown on member cards in the team module.\",\"text\":\"Admin\"},\"team.member.email\":{\"context\":\"Email field label in the team module.\",\"text\":\"E-mail\"},\"team.member.last_connection\":{\"context\":\"Label of the member last connection date in the team module.\",\"text\":\"Connexion\"},\"team.member.last_seen_global\":{\"context\":\"Composite label used when both organization and global last-seen dates are available in the team module.\",\"text\":\"{organization} (générale : {global})\"},\"team.member.never\":{\"context\":\"Fallback value used when a member never connected in the team module.\",\"text\":\"Jamais\"},\"team.member.not_provided\":{\"context\":\"Fallback value used when a member field is missing in the team module.\",\"text\":\"Non renseigné\"},\"team.member.open_contextual_profile\":{\"context\":\"Accessible label used to open the member contextual profile from the team module.\",\"text\":\"Ouvrir le profil contextuel de {name}\"},\"team.member.pending\":{\"context\":\"Badge shown for a pending member in the team module.\",\"text\":\"En attente\"},\"team.member.photo_coming\":{\"context\":\"Placeholder label shown when a member photo is missing in the team module.\",\"text\":\"Photo à venir\"},\"team.member.this_member\":{\"context\":\"Fallback label used in confirmations when a team member display name is missing.\",\"text\":\"ce membre\"},\"team.member.user_fallback\":{\"context\":\"Fallback display name used in the team module when a user has no visible name.\",\"text\":\"Utilisateur {userId}\"},\"team.message.update_failed\":{\"context\":\"Fallback error message shown when a team member update fails.\",\"text\":\"Impossible de mettre à jour ce membre.\"},\"team.message.update_failed_later\":{\"context\":\"Fallback error message shown when a team member update fails due to a request error.\",\"text\":\"Impossible de mettre à jour ce membre pour le moment.\"},\"team.message.update_success\":{\"context\":\"Fallback success message shown after a team member update succeeds.\",\"text\":\"Mise à jour effectuée.\"},\"team.popup.choose_action\":{\"context\":\"Helper text shown in the team member popup above the member action buttons.\",\"text\":\"Choisissez l’action à appliquer dans ce {context}.\"},\"team.popup.context_forbidden\":{\"context\":\"Error shown in the team member popup when the context cannot be viewed.\",\"text\":\"Accès refusé à ce contexte.\"},\"team.popup.context_not_found\":{\"context\":\"Error shown in the team member popup when the context cannot be loaded.\",\"text\":\"Contexte introuvable pour cette organisation.\"},\"team.popup.context_prefix\":{\"context\":\"Prefix shown before the current context in the team member popup.\",\"text\":\"Contexte\"},\"team.popup.contextual_actions\":{\"context\":\"Eyebrow title shown in the team member popup.\",\"text\":\"Actions contextuelles\"},\"team.popup.invalid_member_context\":{\"context\":\"Error shown when the team member popup is opened without a valid organization or user context.\",\"text\":\"Contexte membre invalide.\"},\"team.popup.member_management\":{\"context\":\"Section title shown in the team member popup.\",\"text\":\"Gestion du membre\"},\"team.popup.no_manage_rights\":{\"context\":\"Message shown in the team member popup when the current user cannot manage members in the current context.\",\"text\":\"Vous n’avez pas les droits pour modifier ce {context}.\"},\"team.popup.organization_context_missing\":{\"context\":\"Error shown in the team member popup when the organization has no structural root context.\",\"text\":\"Aucun contexte organisationnel n\'est disponible.\"},\"team.popup.organization_forbidden\":{\"context\":\"Error shown in the team member popup when the organization cannot be viewed.\",\"text\":\"Accès refusé à cette organisation.\"},\"team.popup.organization_not_found\":{\"context\":\"Error shown in the team member popup when the organization cannot be loaded.\",\"text\":\"Organisation introuvable.\"},\"team.popup.user_forbidden\":{\"context\":\"Error shown in the team member popup when the user cannot be viewed.\",\"text\":\"Accès refusé à cet utilisateur.\"},\"team.popup.user_not_found\":{\"context\":\"Error shown in the team member popup when the user cannot be loaded.\",\"text\":\"Utilisateur introuvable.\"},\"team.scope.children\":{\"context\":\"Label of the current holon and direct child scope in the team module.\",\"text\":\"Enfants directs\"},\"team.scope.contextual\":{\"context\":\"Label of the contextual scope in the team module.\",\"text\":\"Contextuel\"},\"team.scope.descendants\":{\"context\":\"Label of the descendants scope in the team module.\",\"text\":\"Descendants\"},\"team.scope.members_aria\":{\"context\":\"Accessible label of the team scope switcher.\",\"text\":\"Portée des membres\"},\"team.title\":{\"context\":\"Title of the team app drawer in OMO.\",\"text\":\"Team\"},\"team.view.cards\":{\"context\":\"Cards view label in the team module.\",\"text\":\"Cartes\"},\"team.view.choice_aria\":{\"context\":\"Accessible label of the team view switcher.\",\"text\":\"Choix de la vue\"},\"team.view.compact\":{\"context\":\"Compact view label in the team module.\",\"text\":\"Compact\"},\"team.view.map\":{\"context\":\"Map view label in the team module.\",\"text\":\"Carte géo\"}}','completed',1,NULL,'2026-07-25 11:23:39','2026-07-25 11:23:49','2026-07-25 11:23:39','2026-07-25 11:23:49');
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
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation_bundles`
--

LOCK TABLES `translation_bundles` WRITE;
/*!40000 ALTER TABLE `translation_bundles` DISABLE KEYS */;
INSERT INTO `translation_bundles` VALUES
(1,'omo_index_page','en','99f2a401887e9cdb2832728667434150979e96a5dec67b7f0e6e6fd29c708d41','{\"app.access_denied.message\":{\"text\":\"Your account is connected, but it does not yet have access to the organization {organizationName}.\"},\"app.access_denied.organization_fallback\":{\"text\":\"requested\"},\"app.access_denied.page_description\":{\"text\":\"For now, access to this space is reserved for people on the list of authorized members.\"},\"app.access_denied.page_heading\":{\"text\":\"Access Denied\"},\"app.access_denied.page_title\":{\"text\":\"Access Denied - OMO\"},\"app.access_denied.request_action\":{\"text\":\"Request Access\"},\"app.access_denied.request_modal_title\":{\"text\":\"Request Access to the Organization\"},\"app.access_denied.request_pending\":{\"text\":\"Request Already Sent\"},\"app.access_denied.request_pending_notice\":{\"text\":\"A request is already pending with the administrators of this organization.\"},\"app.directory.create.action\":{\"text\":\"Open Form\"},\"app.directory.create.aria_label\":{\"text\":\"Create a New Organization\"},\"app.directory.create.badge\":{\"text\":\"New\"},\"app.directory.create.description\":{\"text\":\"Name, domain, logo, banner, color\"},\"app.directory.create.modal_title\":{\"text\":\"Create a New Organization\"},\"app.directory.create.title\":{\"text\":\"Create a New Organization\"},\"app.directory.cta.connect\":{\"text\":\"Connect\"},\"app.directory.cta.view_invitation\":{\"text\":\"View Invitation\"},\"app.directory.description.empty\":{\"text\":\"Your account is connected, but it is not attached to any organization at the moment. You can create a new one below.\"},\"app.directory.description.empty.patreon_connect\":{\"text\":\"Your account is connected, but it is not attached to any organization at the moment. Connect Patreon below to create a new one.\"},\"app.directory.description.with_results\":{\"text\":\"Choose the organization you want to open. Each card redirects you to its dedicated space.\"},\"app.directory.fallback_badge\":{\"text\":\"OMO Space\"},\"app.directory.fallback_organization_name\":{\"text\":\"Organization\"},\"app.directory.heading\":{\"text\":\"Your OMO Spaces\"},\"app.directory.import.action\":{\"text\":\"Choose an Export\"},\"app.directory.import.aria_label\":{\"text\":\"Import an Existing Organization\"},\"app.directory.import.badge\":{\"text\":\"Migration\"},\"app.directory.import.description\":{\"text\":\"Structure, members, documents, projects, and calendar\"},\"app.directory.import.modal_title\":{\"text\":\"Import an Organization\"},\"app.directory.import.title\":{\"text\":\"Import an Organization\"},\"app.directory.invitation.badge\":{\"text\":\"Pending Invitation\"},\"app.directory.invitation.pending_holons\":{\"one\":\"{count} holon pending\",\"other\":\"{count} holons pending\"},\"app.directory.invitation.pending_organization\":{\"text\":\"Access to Confirm\"},\"app.directory.js.action_error\":{\"text\":\"Action not possible.\"},\"app.directory.js.default_organization_name\":{\"text\":\"this organization\"},\"app.directory.js.delete_confirm\":{\"text\":\"Delete {organizationName}?\\n\\nThe structure, members, circles, roles, shares, and related documents will be deleted.\"},\"app.directory.js.leave_confirm\":{\"text\":\"Leave {organizationName}?\\n\\nYour links with the organization, its circles, and roles will be removed.\"},\"app.directory.menu.actions_aria_label\":{\"text\":\"Actions for {organizationName}\"},\"app.directory.menu.delete\":{\"text\":\"Delete\"},\"app.directory.menu.leave\":{\"text\":\"Leave\"},\"app.directory.menu.system_organization_notice\":{\"text\":\"This base organization is used by the system for messages and tutorials. It cannot be deleted, and its administrators cannot leave it.\"},\"app.directory.modal.close\":{\"text\":\"Close\"},\"app.directory.open_organization_aria_label\":{\"text\":\"Open {organizationName} Space\"},\"app.directory.page_title\":{\"text\":\"Your OMO Spaces\"},\"app.directory.patreon_connect.action\":{\"text\":\"Connect with Patreon\"},\"app.directory.patreon_connect.aria_label\":{\"text\":\"Connect your Patreon Profile\"},\"app.directory.patreon_connect.badge\":{\"text\":\"Patreon Required\"},\"app.directory.patreon_connect.description\":{\"text\":\"Connect Patreon to unlock organization creation\"},\"app.directory.patreon_connect.title\":{\"text\":\"Connect Patreon\"},\"app.directory.status.available\":{\"one\":\"{count} organization available\",\"other\":\"{count} organizations available\"},\"app.directory.status.none\":{\"text\":\"No organization at the moment\"},\"app.directory.template.badge\":{\"text\":\"Shared Template\"},\"app.directory.templates.heading\":{\"text\":\"Your Organization Templates\"},\"app.login.intro\":{\"text\":\"Log in to access the structure and governance tools.\"},\"app.login.page_title\":{\"text\":\"{organizationName} - OMO\"},\"app.main.page_title\":{\"text\":\"Governance UI\"},\"app.mobile.context\":{\"text\":\"Context\"},\"app.mobile.menu\":{\"text\":\"Tools\"},\"app.mobile.right_panel\":{\"text\":\"Summary\"},\"app.not_found.message\":{\"text\":\"The requested organization does not exist or is no longer available.\"},\"app.not_found.page_description\":{\"text\":\"You can return to the OMO home and choose another space.\"},\"app.not_found.page_heading\":{\"text\":\"Organization Not Found\"},\"app.not_found.page_title\":{\"text\":\"Organization Not Found - OMO\"},\"app.patreon.prompt_title\":{\"text\":\"Support the Project\"},\"app.user.demo\":{\"text\":\"Demo\"},\"common.back_to_home\":{\"text\":\"Back to Home\"},\"common.logout\":{\"text\":\"Log Out\"}}','machine_translated','2026-07-25 11:14:00','2026-07-25 11:14:13'),
(2,'omo_topbar','en','a3fc08586b17e0db82a46b8816a97ffa042a902b7f037e6f8d22b2647657a4d3','{\"topbar.bug.button\":{\"text\":\"Bug\"},\"topbar.bug.title\":{\"text\":\"Report a bug\"},\"topbar.bug.unavailable_html\":{\"text\":\"<p>Form unavailable.</p>\"},\"topbar.close\":{\"text\":\"Close\"},\"topbar.drawer.default_title\":{\"text\":\"Sidebar\"},\"topbar.help.button\":{\"text\":\"Help\"},\"topbar.help.fallback_label\":{\"text\":\"Help\"},\"topbar.help.faq.description\":{\"text\":\"Access the most common questions, with a search engine to easily find answers to your questions.\"},\"topbar.help.faq.label\":{\"text\":\"FAQ\"},\"topbar.help.faq.title\":{\"text\":\"OMO FAQ\"},\"topbar.help.pending_html\":{\"text\":\"<p>Content coming soon.</p>\"},\"topbar.help.privacy.label\":{\"text\":\"Privacy Policy\"},\"topbar.help.terms.label\":{\"text\":\"Terms and Conditions\"},\"topbar.help.tour.description\":{\"text\":\"Tour of the visible functions on the screen with explanations for each button and possibility.\"},\"topbar.help.tour.label\":{\"text\":\"Guided Tour\"},\"topbar.help.tutorials.description\":{\"text\":\"Targeted training to improve skills in using the software.\"},\"topbar.help.tutorials.label\":{\"text\":\"Tutorials\"},\"topbar.help.tutorials.title\":{\"text\":\"Tutorials\"},\"topbar.help.unavailable_html\":{\"text\":\"<p>Content unavailable.</p>\"},\"topbar.load_error\":{\"text\":\"Loading error\"},\"topbar.loading\":{\"text\":\"Loading...\"},\"topbar.logout\":{\"text\":\"Log out\"},\"topbar.modal.default_title\":{\"text\":\"Panel\"},\"topbar.profile.admin_mode.active\":{\"text\":\"Organization admin mode active\"},\"topbar.profile.admin_mode.disable\":{\"text\":\"Exit organization admin mode\"},\"topbar.profile.admin_mode.enable\":{\"text\":\"Enable organization admin mode\"},\"topbar.profile.admin_mode.inactive\":{\"text\":\"Organization admin mode inactive\"},\"topbar.profile.button\":{\"text\":\"Profile\"},\"topbar.profile.details.email\":{\"text\":\"Email\"},\"topbar.profile.details.empty_value\":{\"text\":\"Not provided\"},\"topbar.profile.details.name\":{\"text\":\"Name\"},\"topbar.profile.details.username\":{\"text\":\"Username\"},\"topbar.profile.edit_label\":{\"text\":\"Edit profile\"},\"topbar.profile.edit_title\":{\"text\":\"Your profile\"},\"topbar.profile.preferences.color_style_default\":{\"text\":\"Black and white\"},\"topbar.profile.preferences.color_style_label\":{\"text\":\"Color\"},\"topbar.profile.preferences.color_style_ocean_blue\":{\"text\":\"Ocean Blue\"},\"topbar.profile.preferences.color_style_turquoise\":{\"text\":\"Turquoise\"},\"topbar.profile.preferences.language_label\":{\"text\":\"Language\"},\"topbar.profile.preferences.language_system\":{\"text\":\"System\"},\"topbar.profile.preferences.theme_dark\":{\"text\":\"Dark\"},\"topbar.profile.preferences.theme_label\":{\"text\":\"Theme\"},\"topbar.profile.preferences.theme_light\":{\"text\":\"Light\"},\"topbar.profile.preferences.theme_system\":{\"text\":\"System\"},\"topbar.profile.site_admin_mode.active\":{\"text\":\"Super admin mode active\"},\"topbar.profile.site_admin_mode.disable\":{\"text\":\"Exit super admin mode\"},\"topbar.profile.site_admin_mode.enable\":{\"text\":\"Enable super admin mode\"},\"topbar.profile.site_admin_mode.inactive\":{\"text\":\"Super admin mode inactive\"},\"topbar.profile.summary_fallback\":{\"text\":\"Profile summary\"},\"topbar.search.advanced_hint\":{\"text\":\"Other advanced filters may be added here.\"},\"topbar.search.button\":{\"text\":\"Search\"},\"topbar.search.period\":{\"text\":\"Period\"},\"topbar.search.period_end\":{\"text\":\"To\"},\"topbar.search.period_start\":{\"text\":\"From\"},\"topbar.search.placeholder\":{\"text\":\"Search for a circle, role, tool, FAQ, or tutorial\"},\"topbar.search.scope\":{\"text\":\"Search in\"},\"topbar.search.submit\":{\"text\":\"Submit\"},\"topbar.tension.button\":{\"text\":\"Tension\"},\"topbar.tension.title\":{\"text\":\"Report a tension\"},\"topbar.tension.unavailable_html\":{\"text\":\"<p>Form unavailable.</p>\"}}','machine_translated','2026-07-25 11:14:00','2026-07-25 11:14:10'),
(3,'omo_checklist','en','5aa1c0266001320570b9d890e91579ed082601ecb747cbd0ad27cb8096a5eaa5','{\"checklist.action.activate\":{\"text\":\"Activate\"},\"checklist.action.add_item\":{\"text\":\"Add an item\"},\"checklist.action.cancel\":{\"text\":\"Cancel\"},\"checklist.action.close\":{\"text\":\"Close\"},\"checklist.action.edit\":{\"text\":\"Edit\"},\"checklist.action.edit_item\":{\"text\":\"Edit the item\"},\"checklist.action.move_down\":{\"text\":\"Move down\"},\"checklist.action.move_up\":{\"text\":\"Move up\"},\"checklist.action.new\":{\"text\":\"Add\"},\"checklist.action.remove_item\":{\"text\":\"Remove\"},\"checklist.action.save\":{\"text\":\"Save\"},\"checklist.action.save_item\":{\"text\":\"Save the item\"},\"checklist.activation.after_completion\":{\"text\":\"Visible after another item\"},\"checklist.activation.after_start\":{\"text\":\"According to the reference date\"},\"checklist.activation.immediate\":{\"text\":\"Visible immediately\"},\"checklist.delay.day\":{\"text\":\"Day(s)\"},\"checklist.delay.month\":{\"text\":\"Month(s)\"},\"checklist.delay.week\":{\"text\":\"Week(s)\"},\"checklist.description\":{\"text\":\"Reusable processes that become projects at the right time.\"},\"checklist.detail.activated_at\":{\"text\":\"Activated on\"},\"checklist.detail.context\":{\"text\":\"Context\"},\"checklist.detail.display_lead\":{\"text\":\"Displayed {delay} before\"},\"checklist.detail.empty_items\":{\"text\":\"This checklist contains no steps yet.\"},\"checklist.detail.empty_runs\":{\"text\":\"No ongoing instance.\"},\"checklist.detail.execution_duration\":{\"text\":\"Execution duration: {delay}\"},\"checklist.detail.item_count\":{\"one\":\"{count} item\",\"other\":\"{count} items\"},\"checklist.detail.items\":{\"text\":\"Structure\"},\"checklist.detail.no_delay\":{\"text\":\"No delay\"},\"checklist.detail.no_description\":{\"text\":\"No description.\"},\"checklist.detail.open_run_count\":{\"one\":\"{count} ongoing instance\",\"other\":\"{count} ongoing instances\"},\"checklist.detail.open_runs\":{\"text\":\"Ongoing instances\"},\"checklist.detail.overdue\":{\"text\":\"Overdue\"},\"checklist.detail.project_instance_count\":{\"one\":\"{count} project from the checklist\",\"other\":\"{count} projects from the checklist\"},\"checklist.detail.project_status\":{\"text\":\"Status: {status}\"},\"checklist.detail.project_status.blocked\":{\"text\":\"Blocked\"},\"checklist.detail.project_status.done\":{\"text\":\"Done\"},\"checklist.detail.project_status.in_progress\":{\"text\":\"In progress\"},\"checklist.detail.project_status.ready\":{\"text\":\"Ready\"},\"checklist.detail.project_status.review\":{\"text\":\"To review\"},\"checklist.detail.project_status.someday\":{\"text\":\"Someday maybe\"},\"checklist.detail.recurrence\":{\"text\":\"Recurring: {schedule}\"},\"checklist.detail.recurring_deadline\":{\"text\":\"Deadline {date}\"},\"checklist.detail.recurring_instance_count\":{\"one\":\"{count} active occurrence\",\"other\":\"{count} active occurrences\"},\"checklist.detail.recurring_planned_start\":{\"text\":\"Planned on {date}\"},\"checklist.detail.reference_date\":{\"text\":\"Reference\"},\"checklist.detail.root\":{\"text\":\"Root project\"},\"checklist.detail.run_item_count\":{\"one\":\"{count} step\",\"other\":\"{count} steps\"},\"checklist.detail.trigger\":{\"text\":\"Trigger\"},\"checklist.detail.updated\":{\"text\":\"Updated\"},\"checklist.drawer.description\":{\"text\":\"Structure, activation, and responsibilities.\"},\"checklist.drawer.title\":{\"text\":\"Checklist\"},\"checklist.empty.children\":{\"text\":\"No checklist in this context or its direct children.\"},\"checklist.empty.contextual\":{\"text\":\"No checklist in this context.\"},\"checklist.empty.descendants\":{\"text\":\"No checklist in this context or its descendants.\"},\"checklist.error.action\":{\"text\":\"Unknown action.\"},\"checklist.error.activation_unavailable\":{\"text\":\"This checklist cannot be activated on demand.\"},\"checklist.error.context\":{\"text\":\"Invalid or inaccessible context.\"},\"checklist.error.forbidden\":{\"text\":\"You cannot edit this checklist.\"},\"checklist.error.instance_title\":{\"text\":\"The instance name is required.\"},\"checklist.error.item_holon\":{\"text\":\"The chosen role or holon for an item is invalid.\"},\"checklist.error.item_not_found\":{\"text\":\"Checklist item not found.\"},\"checklist.error.item_recurrence_structure\":{\"text\":\"A recurring item must be independent and visible immediately.\"},\"checklist.error.item_relation\":{\"text\":\"A relationship between items is invalid or forms a loop.\"},\"checklist.error.item_title\":{\"text\":\"Each item must have a title.\"},\"checklist.error.items\":{\"text\":\"Add at least one item to the checklist.\"},\"checklist.error.load\":{\"text\":\"Unable to load this checklist.\"},\"checklist.error.method\":{\"text\":\"This action must be sent via POST.\"},\"checklist.error.not_found\":{\"text\":\"Checklist not found.\"},\"checklist.error.open_instance\":{\"text\":\"An instance is already running for this checklist.\"},\"checklist.error.organization\":{\"text\":\"Invalid or inaccessible organization.\"},\"checklist.error.reference_date\":{\"text\":\"The reference date is invalid.\"},\"checklist.error.save\":{\"text\":\"Unable to save the checklist.\"},\"checklist.error.schedule\":{\"text\":\"The chosen recurrence is incomplete or invalid.\"},\"checklist.error.title\":{\"text\":\"The checklist title is required.\"},\"checklist.form.activate_intro\":{\"text\":\"The reference date serves as the starting point for scheduled steps, including those planned before this date.\"},\"checklist.form.activate_title\":{\"text\":\"Activate the checklist\"},\"checklist.form.activation\":{\"text\":\"Visibility\"},\"checklist.form.base_intro\":{\"text\":\"Start with general information. Steps will be added later from the checklist view.\"},\"checklist.form.confirm_overlap\":{\"text\":\"Create a new instance despite already open instances\"},\"checklist.form.create_item_title\":{\"text\":\"Add a step\"},\"checklist.form.create_title\":{\"text\":\"New checklist\"},\"checklist.form.delay\":{\"text\":\"Delay\"},\"checklist.form.dependency\":{\"text\":\"After the item\"},\"checklist.form.description\":{\"text\":\"Description\"},\"checklist.form.display_lead\":{\"text\":\"Display in advance\"},\"checklist.form.display_lead_unit\":{\"text\":\"Lead time unit\"},\"checklist.form.edit_item_title\":{\"text\":\"Edit the step\"},\"checklist.form.edit_title\":{\"text\":\"Edit the checklist\"},\"checklist.form.end_date\":{\"text\":\"Deadline\"},\"checklist.form.execution_duration\":{\"text\":\"Execution duration\"},\"checklist.form.execution_duration_unit\":{\"text\":\"Duration unit\"},\"checklist.form.frequency\":{\"text\":\"Frequency\"},\"checklist.form.holon\":{\"text\":\"Responsible role or holon\"},\"checklist.form.identity\":{\"text\":\"Model identity\"},\"checklist.form.importance\":{\"text\":\"Strategic importance\"},\"checklist.form.instance_title\":{\"text\":\"Instance name\"},\"checklist.form.instance_title_help\":{\"text\":\"This name becomes the title of the root project created for this instance.\"},\"checklist.form.intro\":{\"text\":\"Define the model, its elements, and the conditions that make them visible.\"},\"checklist.form.item\":{\"text\":\"Item\"},\"checklist.form.item_description\":{\"text\":\"Description\"},\"checklist.form.item_recurrence\":{\"text\":\"Recurrence of this item\"},\"checklist.form.item_recurrence_help\":{\"text\":\"Each occurrence creates a simple project for the chosen role. You can make it appear in advance and set its execution duration.\"},\"checklist.form.item_timing\":{\"text\":\"Visibility and delay\"},\"checklist.form.item_timing_help\":{\"text\":\"Make the project appear before its scheduled date and set its execution duration. These settings apply to each item.\"},\"checklist.form.item_title\":{\"text\":\"Item title\"},\"checklist.form.items\":{\"text\":\"Checklist items\"},\"checklist.form.items_help\":{\"text\":\"Each item is a model project. It can be immediate, delayed around the reference date, or wait for another item.\"},\"checklist.form.overlap\":{\"text\":\"If an execution is still open\"},\"checklist.form.parent\":{\"text\":\"Sub-project of\"},\"checklist.form.parent_root\":{\"text\":\"Checklist root\"},\"checklist.form.priority\":{\"text\":\"Priority\"},\"checklist.form.reference_date\":{\"text\":\"Reference date\"},\"checklist.form.reference_help\":{\"text\":\"For example, the arrival date. A step at D-5 will be scheduled five days before.\"},\"checklist.form.revision_note\":{\"text\":\"Internal note\"},\"checklist.form.schedule\":{\"text\":\"Expected time\"},\"checklist.form.select_item\":{\"text\":\"Choose an item...\"},\"checklist.form.size\":{\"text\":\"Size\"},\"checklist.form.status\":{\"text\":\"Status\"},\"checklist.form.title\":{\"text\":\"Title\"},\"checklist.form.trigger\":{\"text\":\"Checklist trigger\"},\"checklist.form.trigger_help\":{\"text\":\"It can be launched on demand, following a recurrence, or serve only as a container.\"},\"checklist.form.trigger_type\":{\"text\":\"Mode\"},\"checklist.form.unit\":{\"text\":\"Unit\"},\"checklist.frequency.daily\":{\"text\":\"Every day\"},\"checklist.frequency.monthly\":{\"text\":\"Every month\"},\"checklist.frequency.quarterly\":{\"text\":\"Every quarter\"},\"checklist.frequency.semiannual\":{\"text\":\"Every semester\"},\"checklist.frequency.weekly\":{\"text\":\"Every week\"},\"checklist.frequency.yearly\":{\"text\":\"Every year\"},\"checklist.loading\":{\"text\":\"Loading checklist...\"},\"checklist.overlap.ask\":{\"text\":\"Ask when the time comes\"},\"checklist.overlap.create_new\":{\"text\":\"Create a new execution\"},\"checklist.overlap.reuse_open\":{\"text\":\"Reuse the open execution\"},\"checklist.overlap.skip\":{\"text\":\"Skip this occurrence\"},\"checklist.run.status.running\":{\"text\":\"In progress\"},\"checklist.schedule.month.1\":{\"text\":\"January\"},\"checklist.schedule.month.10\":{\"text\":\"October\"},\"checklist.schedule.month.11\":{\"text\":\"November\"},\"checklist.schedule.month.12\":{\"text\":\"December\"},\"checklist.schedule.month.2\":{\"text\":\"February\"},\"checklist.schedule.month.3\":{\"text\":\"March\"},\"checklist.schedule.month.4\":{\"text\":\"April\"},\"checklist.schedule.month.5\":{\"text\":\"May\"},\"checklist.schedule.month.6\":{\"text\":\"June\"},\"checklist.schedule.month.7\":{\"text\":\"July\"},\"checklist.schedule.month.8\":{\"text\":\"August\"},\"checklist.schedule.month.9\":{\"text\":\"September\"},\"checklist.schedule.month_day\":{\"text\":\"The {day} of the month\"},\"checklist.schedule.none\":{\"text\":\"Choose...\"},\"checklist.schedule.quarter.1\":{\"text\":\"First month of the quarter\"},\"checklist.schedule.quarter.2\":{\"text\":\"Second month of the quarter\"},\"checklist.schedule.quarter.3\":{\"text\":\"Third month of the quarter\"},\"checklist.schedule.semester.1\":{\"text\":\"First month of the semester\"},\"checklist.schedule.semester.2\":{\"text\":\"Second month of the semester\"},\"checklist.schedule.semester.3\":{\"text\":\"Third month of the semester\"},\"checklist.schedule.semester.4\":{\"text\":\"Fourth month of the semester\"},\"checklist.schedule.semester.5\":{\"text\":\"Fifth month of the semester\"},\"checklist.schedule.semester.6\":{\"text\":\"Sixth month of the semester\"},\"checklist.schedule.weekday.1\":{\"text\":\"Monday\"},\"checklist.schedule.weekday.2\":{\"text\":\"Tuesday\"},\"checklist.schedule.weekday.3\":{\"text\":\"Wednesday\"},\"checklist.schedule.weekday.4\":{\"text\":\"Thursday\"},\"checklist.schedule.weekday.5\":{\"text\":\"Friday\"},\"checklist.schedule.weekday.6\":{\"text\":\"Saturday\"},\"checklist.schedule.weekday.7\":{\"text\":\"Sunday\"},\"checklist.scope.children\":{\"text\":\"Direct children\"},\"checklist.scope.contextual\":{\"text\":\"Local\"},\"checklist.scope.descendants\":{\"text\":\"Descendants\"},\"checklist.status.draft\":{\"text\":\"Draft\"},\"checklist.status.published\":{\"text\":\"Available\"},\"checklist.status.retired\":{\"text\":\"Disabled\"},\"checklist.success.activated\":{\"text\":\"The new instance is active.\"},\"checklist.success.reused\":{\"text\":\"The already open instance has been retained.\"},\"checklist.success.save\":{\"text\":\"Checklist saved.\"},\"checklist.title\":{\"text\":\"Checklists\"},\"checklist.trigger.container\":{\"text\":\"Container\"},\"checklist.trigger.manual\":{\"text\":\"On demand\"},\"checklist.trigger.scheduled\":{\"text\":\"Recurring\"}}','machine_translated','2026-07-25 11:14:00','2026-07-25 11:14:23'),
(4,'omo_get_org_panel','en','77c8484a43b0c4a61e1878fdbac840097e1e4699c36d82c5d0497229dc179454','{\"leftbar.actions.add\":{\"text\":\"Add\"},\"leftbar.actions.delete\":{\"text\":\"Delete\"},\"leftbar.actions.edit\":{\"text\":\"Edit\"},\"leftbar.actions.history\":{\"text\":\"History\"},\"leftbar.actions.move\":{\"text\":\"Move\"},\"leftbar.children.circles\":{\"text\":\"Circles\"},\"leftbar.children.roles\":{\"text\":\"Roles\"},\"leftbar.children.section_title\":{\"text\":\"Dependencies\"},\"leftbar.copy_link.error\":{\"text\":\"Unable to copy the direct link.\"},\"leftbar.copy_link.success\":{\"text\":\"Link copied\"},\"leftbar.detail.item_fallback\":{\"text\":\"Element\"},\"leftbar.detail.property_fallback\":{\"text\":\"Property {propertyId}\"},\"leftbar.detail.show\":{\"text\":\"See details\"},\"leftbar.detail.updated_at\":{\"text\":\"Updated on {date}\"},\"leftbar.detail.updated_by\":{\"text\":\"Updated on {date} by {userName}\"},\"leftbar.empty.message\":{\"text\":\"No content has been provided for this holon yet.\"},\"leftbar.empty.section_title\":{\"text\":\"Information\"},\"leftbar.error.holon_access_denied\":{\"text\":\"Access denied to this holon.\"},\"leftbar.error.holon_not_found\":{\"text\":\"Holon not found for this organization.\"},\"leftbar.error.organization_access_denied\":{\"text\":\"Access denied to this organization.\"},\"leftbar.error.organization_invalid\":{\"text\":\"Invalid organization.\"},\"leftbar.error.organization_not_found\":{\"text\":\"Organization not found.\"},\"leftbar.error.root_not_found\":{\"text\":\"No root structure found for this organization.\"},\"leftbar.members.add\":{\"text\":\"Add a member\"},\"leftbar.members.pending_tooltip\":{\"text\":\"{memberName} - invitation pending\"},\"leftbar.members.section_title\":{\"text\":\"Members\"},\"leftbar.members.view_all\":{\"text\":\"View all\"},\"leftbar.project.children.empty\":{\"text\":\"No direct subprojects.\"},\"leftbar.project.children.error\":{\"text\":\"Unable to load subprojects.\"},\"leftbar.project.children.loading\":{\"text\":\"Loading subprojects...\"}}','machine_translated','2026-07-25 11:14:00','2026-07-25 11:14:06'),
(5,'omo_get_sidebar_panel','en','7258f1301d443eca5c81e9ee4fba09a9b428a3544f0e08f73bc31af8cbc584b7','{\"sidebar.applications.manage_label\":{\"text\":\"Manage\"},\"sidebar.applications.manage_title\":{\"text\":\"Manage applications\"},\"sidebar.parameters.label\":{\"text\":\"Settings\"}}','machine_translated','2026-07-25 11:14:00','2026-07-25 11:14:03'),
(7,'omo_stats','en','708c2416140325ae4c4266248f090bfb04f4699c8bc580e2fabe72c71762512c','{\"stats.action.add\":{\"text\":\"Add\"},\"stats.action.cancel\":{\"text\":\"Cancel\"},\"stats.action.close\":{\"text\":\"Close\"},\"stats.action.create_group\":{\"text\":\"Create group\"},\"stats.action.delete\":{\"text\":\"Delete\"},\"stats.action.delete_group\":{\"text\":\"Remove group\"},\"stats.action.delete_import\":{\"text\":\"Remove from this context\"},\"stats.action.delete_indicator\":{\"text\":\"Delete indicator\"},\"stats.action.detail\":{\"text\":\"Detail\"},\"stats.action.edit\":{\"text\":\"Edit\"},\"stats.action.edit_group\":{\"text\":\"Edit group\"},\"stats.action.edit_import\":{\"text\":\"Change source\"},\"stats.action.group\":{\"text\":\"Group indicators\"},\"stats.action.import\":{\"text\":\"Import indicator\"},\"stats.action.more\":{\"text\":\"More actions\"},\"stats.action.new\":{\"text\":\"New indicator\"},\"stats.action.save\":{\"text\":\"Save\"},\"stats.action.update\":{\"text\":\"Save\"},\"stats.card.context\":{\"text\":\"Context\"},\"stats.card.group\":{\"text\":\"Group\"},\"stats.card.imported\":{\"text\":\"Imported\"},\"stats.card.latest\":{\"text\":\"Latest value\"},\"stats.card.member_count\":{\"one\":\"{count} indicator\",\"other\":\"{count} indicators\"},\"stats.card.no_value\":{\"text\":\"No value\"},\"stats.card.open\":{\"text\":\"Open indicator {name}\"},\"stats.card.overdue\":{\"text\":\"Overdue value\"},\"stats.card.overdue_days\":{\"one\":\"Overdue by {count} day\",\"other\":\"Overdue by {count} days\"},\"stats.card.to_complete\":{\"text\":\"To complete\"},\"stats.card.value_count\":{\"one\":\"{count} value\",\"other\":\"{count} values\"},\"stats.chart.empty\":{\"text\":\"No data to display yet.\"},\"stats.chart.tooltip.date\":{\"text\":\"Date\"},\"stats.chart.tooltip.value\":{\"text\":\"Value\"},\"stats.column.context\":{\"text\":\"Context\"},\"stats.column.history\":{\"text\":\"History\"},\"stats.column.indicator\":{\"text\":\"Indicator\"},\"stats.column.latest\":{\"text\":\"Latest value\"},\"stats.controls.sort.alpha\":{\"text\":\"Alphabetical\"},\"stats.controls.sort.aria\":{\"text\":\"Indicator sorting\"},\"stats.controls.sort.temporal\":{\"text\":\"Temporal\"},\"stats.detail.add\":{\"text\":\"Add value\"},\"stats.detail.add_help\":{\"text\":\"The current date and time are automatically suggested.\"},\"stats.detail.add_title\":{\"text\":\"Add current value\"},\"stats.detail.chart_min_value\":{\"text\":\"Lower value\"},\"stats.detail.confirm_delete\":{\"text\":\"Permanently delete this value?\"},\"stats.detail.confirm_delete_group\":{\"text\":\"Remove this group from the context?\"},\"stats.detail.confirm_delete_import\":{\"text\":\"Remove this indicator from the context?\"},\"stats.detail.confirm_delete_indicator\":{\"text\":\"Delete this indicator from the list? Its values will be retained.\"},\"stats.detail.frequency\":{\"text\":\"Expected frequency\"},\"stats.detail.latest\":{\"text\":\"Current value\"},\"stats.detail.no_values\":{\"text\":\"No values have been recorded yet.\"},\"stats.detail.range.end\":{\"text\":\"End of displayed period\"},\"stats.detail.range.label\":{\"text\":\"Displayed period\"},\"stats.detail.range.start\":{\"text\":\"Start of displayed period\"},\"stats.detail.reference\":{\"text\":\"Reference\"},\"stats.detail.reference_ceiling\":{\"text\":\"Horizontal ceiling\"},\"stats.detail.reference_none\":{\"text\":\"No reference curve\"},\"stats.detail.reference_objective\":{\"text\":\"Objective or trajectory\"},\"stats.detail.schedule\":{\"text\":\"Expected moment\"},\"stats.detail.source\":{\"text\":\"View source\"},\"stats.detail.tab.chart\":{\"text\":\"Chart\"},\"stats.detail.tab.values\":{\"text\":\"Values\"},\"stats.detail.value\":{\"text\":\"Value\"},\"stats.detail.value_date\":{\"text\":\"Date\"},\"stats.drawer.description\":{\"text\":\"Chart, values, and manual entry.\"},\"stats.drawer.title\":{\"text\":\"Indicator\"},\"stats.empty.children\":{\"text\":\"No indicators are defined in this context or its direct children yet.\"},\"stats.empty.contextual\":{\"text\":\"No indicators are defined in this context yet.\"},\"stats.empty.descendants\":{\"text\":\"No indicators are defined in this context or its descendants yet.\"},\"stats.error.action\":{\"text\":\"Unknown action.\"},\"stats.error.ceiling\":{\"text\":\"All points of a ceiling must use the same value.\"},\"stats.error.ceiling_value\":{\"text\":\"Ceiling value is required.\"},\"stats.error.chart_min_value\":{\"text\":\"Invalid chart lower value.\"},\"stats.error.context\":{\"text\":\"Invalid or inaccessible context.\"},\"stats.error.date\":{\"text\":\"The entered date is invalid.\"},\"stats.error.forbidden\":{\"text\":\"You cannot edit this indicator.\"},\"stats.error.group_name\":{\"text\":\"Group name is required.\"},\"stats.error.load\":{\"text\":\"Unable to load this indicator.\"},\"stats.error.method\":{\"text\":\"This action must be sent via POST.\"},\"stats.error.name\":{\"text\":\"Indicator name is required.\"},\"stats.error.not_found\":{\"text\":\"Indicator not found.\"},\"stats.error.organization\":{\"text\":\"Invalid or inaccessible organization.\"},\"stats.error.reference_dates\":{\"text\":\"The end date of the reference must be after its start date.\"},\"stats.error.reference_endpoints\":{\"text\":\"Endpoints at 0% and 100% are required and must have a date.\"},\"stats.error.reference_points\":{\"text\":\"The reference curve must contain unique points between 0 and 100%.\"},\"stats.error.save\":{\"text\":\"Unable to save the indicator.\"},\"stats.error.schedule\":{\"text\":\"Invalid measurement frequency.\"},\"stats.error.selection\":{\"text\":\"Select at least one visible indicator.\"},\"stats.error.url\":{\"text\":\"URL must start with http:// or https://.\"},\"stats.error.value\":{\"text\":\"The entered value is invalid.\"},\"stats.error.value_save\":{\"text\":\"Unable to save this value.\"},\"stats.form.add_point\":{\"text\":\"Add point\"},\"stats.form.ceiling_help\":{\"text\":\"Enter a single value. The marker will be displayed over the entire visible period of the chart.\"},\"stats.form.ceiling_title\":{\"text\":\"Ceiling\"},\"stats.form.ceiling_value\":{\"text\":\"Ceiling value\"},\"stats.form.create_title\":{\"text\":\"New indicator\"},\"stats.form.edit_title\":{\"text\":\"Edit indicator\"},\"stats.form.endpoint\":{\"text\":\"Dated endpoint\"},\"stats.form.frequency\":{\"text\":\"Frequency\"},\"stats.form.intermediate\":{\"text\":\"Intermediate point\"},\"stats.form.intro\":{\"text\":\"Define the series and, if necessary, its reference curve.\"},\"stats.form.point_date\":{\"text\":\"Date\"},\"stats.form.point_date_auto\":{\"text\":\"Calculated date\"},\"stats.form.point_value\":{\"text\":\"Value\"},\"stats.form.position\":{\"text\":\"Position (%)\"},\"stats.form.reference_ceiling\":{\"text\":\"Horizontal ceiling\"},\"stats.form.reference_help\":{\"text\":\"Dated endpoints use 0% and 100%. Intermediate dates are calculated based on the point\'s position.\"},\"stats.form.reference_none\":{\"text\":\"No reference\"},\"stats.form.reference_objective\":{\"text\":\"Objective or trajectory\"},\"stats.form.reference_title\":{\"text\":\"Reference curve\"},\"stats.form.remove_point\":{\"text\":\"Remove\"},\"stats.form.schedule\":{\"text\":\"When\"},\"stats.form.schedule_help\":{\"text\":\"Define the expected rhythm. The moment is optional: without it, the system can rely on the observed interval between measurements.\"},\"stats.form.schedule_title\":{\"text\":\"Measurement rhythm\"},\"stats.frequency.daily\":{\"text\":\"Daily\"},\"stats.frequency.monthly\":{\"text\":\"Monthly\"},\"stats.frequency.none\":{\"text\":\"No defined frequency\"},\"stats.frequency.quarterly\":{\"text\":\"Quarterly\"},\"stats.frequency.semiannual\":{\"text\":\"Semiannual\"},\"stats.frequency.weekly\":{\"text\":\"Weekly\"},\"stats.frequency.yearly\":{\"text\":\"Yearly\"},\"stats.group.combined\":{\"text\":\"Totals\"},\"stats.group.detail.sources\":{\"text\":\"Source indicators\"},\"stats.group.detail.sum\":{\"text\":\"Calculated sum\"},\"stats.group.edit_title\":{\"text\":\"Edit group\"},\"stats.group.mode\":{\"text\":\"Display\"},\"stats.group.mode.overlay\":{\"text\":\"Overlaid curves\"},\"stats.group.mode.sum\":{\"text\":\"Sum of values\"},\"stats.group.name\":{\"text\":\"Group name\"},\"stats.group.title\":{\"text\":\"Group indicators\"},\"stats.import.edit_title\":{\"text\":\"Edit imported source\"},\"stats.import.search\":{\"text\":\"Search\"},\"stats.import.search_placeholder\":{\"text\":\"Name or context\"},\"stats.import.title\":{\"text\":\"Import indicator\"},\"stats.import.visible\":{\"text\":\"Visible indicators\"},\"stats.loading\":{\"text\":\"Loading indicator...\"},\"stats.schedule.month.1\":{\"text\":\"January\"},\"stats.schedule.month.10\":{\"text\":\"October\"},\"stats.schedule.month.11\":{\"text\":\"November\"},\"stats.schedule.month.12\":{\"text\":\"December\"},\"stats.schedule.month.2\":{\"text\":\"February\"},\"stats.schedule.month.3\":{\"text\":\"March\"},\"stats.schedule.month.4\":{\"text\":\"April\"},\"stats.schedule.month.5\":{\"text\":\"May\"},\"stats.schedule.month.6\":{\"text\":\"June\"},\"stats.schedule.month.7\":{\"text\":\"July\"},\"stats.schedule.month.8\":{\"text\":\"August\"},\"stats.schedule.month.9\":{\"text\":\"September\"},\"stats.schedule.month_day\":{\"text\":\"On the {day}\"},\"stats.schedule.none\":{\"text\":\"No specification\"},\"stats.schedule.quarter.1\":{\"text\":\"January, April, July, October\"},\"stats.schedule.quarter.2\":{\"text\":\"February, May, August, November\"},\"stats.schedule.quarter.3\":{\"text\":\"March, June, September, December\"},\"stats.schedule.semester.1\":{\"text\":\"January, July\"},\"stats.schedule.semester.2\":{\"text\":\"February, August\"},\"stats.schedule.semester.3\":{\"text\":\"March, September\"},\"stats.schedule.semester.4\":{\"text\":\"April, October\"},\"stats.schedule.semester.5\":{\"text\":\"May, November\"},\"stats.schedule.semester.6\":{\"text\":\"June, December\"},\"stats.schedule.weekday.1\":{\"text\":\"Monday\"},\"stats.schedule.weekday.2\":{\"text\":\"Tuesday\"},\"stats.schedule.weekday.3\":{\"text\":\"Wednesday\"},\"stats.schedule.weekday.4\":{\"text\":\"Thursday\"},\"stats.schedule.weekday.5\":{\"text\":\"Friday\"},\"stats.schedule.weekday.6\":{\"text\":\"Saturday\"},\"stats.schedule.weekday.7\":{\"text\":\"Sunday\"},\"stats.scope.children\":{\"text\":\"Direct children\"},\"stats.scope.contextual\":{\"text\":\"Contextual\"},\"stats.scope.descendants\":{\"text\":\"Descendants\"},\"stats.title\":{\"text\":\"Indicators\"},\"stats.view.cards\":{\"text\":\"Cards\"},\"stats.view.compact\":{\"text\":\"Compact\"}}','machine_translated','2026-07-25 11:14:03','2026-07-25 11:14:23'),
(9,'omo_documents_index','en','c0d0b752d905747423a299a5e4cfd1f379fc2757e3541bc77ed4ff308db3257a','{\"documents.action.loading\":{\"text\":\"Loading...\"},\"documents.action.new\":{\"text\":\"New\"},\"documents.controls.density.aria\":{\"text\":\"Document display density\"},\"documents.controls.density.compact\":{\"text\":\"Compact\"},\"documents.controls.density.detail\":{\"text\":\"Detail\"},\"documents.controls.sort.alpha\":{\"text\":\"Alphabetical\"},\"documents.controls.sort.aria\":{\"text\":\"Document sorting\"},\"documents.controls.sort.date\":{\"text\":\"Date\"},\"documents.date_column.created\":{\"text\":\"Created on\"},\"documents.date_column.updated\":{\"text\":\"Updated on\"},\"documents.drawer.close\":{\"text\":\"Close\"},\"documents.drawer.detail_description\":{\"text\":\"Reading the document in OMO.\"},\"documents.drawer.detail_title\":{\"text\":\"Document Detail\"},\"documents.drawer.editor_description\":{\"text\":\"Creating a document in the current context.\"},\"documents.drawer.editor_title\":{\"text\":\"New Document\"},\"documents.empty.available_children\":{\"text\":\"No documents available for this context or its direct children.\"},\"documents.empty.available_contextual\":{\"text\":\"No documents available for this context.\"},\"documents.empty.available_descendants\":{\"text\":\"No documents available for this context and its descendants.\"},\"documents.empty.visible_children\":{\"one\":\"No visible documents for this context or its direct children. {count} file is hidden.\",\"other\":\"No visible documents for this context or its direct children. {count} files are hidden.\"},\"documents.empty.visible_contextual\":{\"one\":\"No visible documents for this context. {count} file is hidden.\",\"other\":\"No visible documents for this context. {count} files are hidden.\"},\"documents.empty.visible_descendants\":{\"one\":\"No visible documents for this context and its descendants. {count} file is hidden.\",\"other\":\"No visible documents for this context and its descendants. {count} files are hidden.\"},\"documents.error.load_document\":{\"text\":\"Unable to load this document.\"},\"documents.error.load_editor\":{\"text\":\"Unable to load the document editor.\"},\"documents.group.earlier\":{\"text\":\"Older\"},\"documents.group.last_month\":{\"text\":\"Last month\"},\"documents.group.last_week\":{\"text\":\"Last week\"},\"documents.group.this_month\":{\"text\":\"This month\"},\"documents.group.this_week\":{\"text\":\"This week\"},\"documents.group.this_year\":{\"text\":\"This year\"},\"documents.group.today\":{\"text\":\"Today\"},\"documents.group.too_far\":{\"text\":\"Unknown date\"},\"documents.group.yesterday\":{\"text\":\"Yesterday\"},\"documents.menu.action_error\":{\"text\":\"Action not possible.\"},\"documents.menu.archive\":{\"text\":\"Archive\"},\"documents.menu.confirm_archive\":{\"text\":\"Archive this document? It will no longer be visible in the list.\"},\"documents.menu.confirm_delete\":{\"text\":\"Permanently delete this document?\"},\"documents.menu.delete\":{\"text\":\"Delete\"},\"documents.menu.export_pdf\":{\"text\":\"Export as PDF\"},\"documents.page.title\":{\"text\":\"Documents\"},\"documents.scope.children\":{\"text\":\"Direct children\"},\"documents.scope.contextual\":{\"text\":\"Contextual\"},\"documents.scope.descendants\":{\"text\":\"Descendants\"},\"documents.scope.edit\":{\"text\":\"Edit\"},\"documents.scope.toggle_aria\":{\"text\":\"Document scope\"},\"documents.scope.view\":{\"text\":\"View\"},\"documents.sort.alpha_aria\":{\"text\":\"Alphabetical\"},\"documents.sort.created\":{\"text\":\"Creation\"},\"documents.sort.created_aria\":{\"text\":\"Creation date\"},\"documents.sort.updated\":{\"text\":\"Modification\"},\"documents.sort.updated_aria\":{\"text\":\"Modification date\"},\"documents.upload_missing.badge\":{\"text\":\"File missing\"}}','machine_translated','2026-07-25 11:14:06','2026-07-25 11:14:13'),
(15,'omo_get_structure_panel','en','542d102221f255e01f98cbcc450c9164056b4d03f2a94fc2ccbdba944505fd4c','{\"structure.actions.export\":{\"text\":\"Export\"},\"structure.actions.export.download\":{\"text\":\"Download\"},\"structure.actions.export.format.csv\":{\"text\":\"CSV\"},\"structure.actions.export.format.csv_description\":{\"text\":\"Flat view of holons. Rights are listed in a cell with their code and scope.\"},\"structure.actions.export.format.json\":{\"text\":\"JSON\"},\"structure.actions.export.format.json_description\":{\"text\":\"Complete format to re-import the structure. It also includes the rights of holons and templates.\"},\"structure.actions.export.format.xml\":{\"text\":\"XML\"},\"structure.actions.export.format.xml_description\":{\"text\":\"Structured and readable format, with the same rights codes as JSON.\"},\"structure.actions.export.modal_intro\":{\"text\":\"Choose the export format for this structure.\"},\"structure.actions.export.modal_title\":{\"text\":\"Export Structure\"},\"structure.actions.menu_aria\":{\"text\":\"Actions\"},\"structure.actions.print\":{\"text\":\"Print\"},\"structure.actions.share\":{\"text\":\"Share\"},\"structure.browser.generic_name\":{\"text\":\"this browser\"},\"structure.error.organization_access_denied\":{\"text\":\"Access denied to this organization.\"},\"structure.list.empty_search\":{\"text\":\"No nodes match this search.\"},\"structure.list.properties.hide_aria\":{\"text\":\"Hide properties\"},\"structure.list.properties.show_aria\":{\"text\":\"Show properties\"},\"structure.list.search.placeholder\":{\"text\":\"Quick filter\"},\"structure.message.disabled\":{\"text\":\"The Structure app is disabled for this organization.\"},\"structure.message.invalid\":{\"text\":\"Invalid structure.\"},\"structure.message.load_error\":{\"text\":\"Unable to load the structure.\"},\"structure.message.no_structure\":{\"text\":\"No structure available for this organization.\"},\"structure.placeholder.action\":{\"text\":\"Open the Structure app\"},\"structure.placeholder.text\":{\"text\":\"No structure is yet defined for this organization. Open the Structure app in the leftbar to create an empty structure, import an export, or start from a template.\"},\"structure.placeholder.title\":{\"text\":\"No Structure\"},\"structure.share.modal_title\":{\"text\":\"Share Structure\"},\"structure.view.toggle_label\":{\"text\":\"G   L\"},\"structure.warning.brave\":{\"text\":\"Brave seems to block the canvas reading used for graphical navigation, probably due to the anti-fingerprint shield. The list view has been activated to continue navigating. You can also relax the shield for this site.\"},\"structure.warning.dismiss_aria\":{\"text\":\"Reduce this message\"},\"structure.warning.pixel_mismatch\":{\"text\":\"{browserName} blocks or alters the canvas reading used for graphical navigation. The list view has been activated to continue navigating.\"},\"structure.warning.restore\":{\"text\":\"Browser info\"},\"structure.warning.unavailable\":{\"text\":\"The canvas reading used for graphical navigation is not available in {browserName}. The list view has been activated to continue navigating.\"}}','machine_translated','2026-07-25 11:14:27','2026-07-25 11:14:32'),
(17,'omo_parameters_index','en','5ce9a7de80546eb9722918e4eefef9fae72cd46d2851970ea7b7c68ed1edf225','{\"parameters.index.action.close\":{\"text\":\"Close\"},\"parameters.index.card.application.description\":{\"text\":\"Configure the options and integrations of {applicationName} for {organizationName}.\"},\"parameters.index.card.application.eyebrow\":{\"text\":\"Application\"},\"parameters.index.card.application.forbidden\":{\"text\":\"Enable the organization\'s admin mode to modify the settings of {applicationName}.\"},\"parameters.index.card.holon_templates.description\":{\"text\":\"Configure node types and their properties for your organization.\"},\"parameters.index.card.holon_templates.eyebrow\":{\"text\":\"Architecture\"},\"parameters.index.card.holon_templates.title\":{\"text\":\"Holon Templates\"},\"parameters.index.card.organization.description\":{\"text\":\"Edit the name, short name, geographical position, illustrations, and color of {organizationName}.\"},\"parameters.index.card.organization.eyebrow\":{\"text\":\"Your Structure\"},\"parameters.index.card.organization.fallback_name\":{\"text\":\"this organization\"},\"parameters.index.card.organization.forbidden\":{\"text\":\"You must be an admin of the organization to modify these settings.\"},\"parameters.index.card.organization.title\":{\"text\":\"Organization\"},\"parameters.index.card.profile.description\":{\"text\":\"Open your profile editor.\"},\"parameters.index.card.profile.eyebrow\":{\"text\":\"My Account\"},\"parameters.index.card.profile.title\":{\"text\":\"Profile\"},\"parameters.index.card.server_admin.description\":{\"text\":\"Open the sensitive global settings of the .env file, excluding database configuration.\"},\"parameters.index.card.server_admin.eyebrow\":{\"text\":\"Maintenance\"},\"parameters.index.card.server_admin.title\":{\"text\":\"Server Admin\"},\"parameters.index.description\":{\"text\":\"Find your personal settings here as well as the available configuration screens for the organization.\"},\"parameters.index.drawer.error\":{\"text\":\"Unable to load this module.\"},\"parameters.index.drawer.loading\":{\"text\":\"Loading...\"},\"parameters.index.empty.login\":{\"text\":\"Log in to access your user settings.\"},\"parameters.index.title\":{\"text\":\"Settings\"}}','machine_translated','2026-07-25 11:14:51','2026-07-25 11:14:55'),
(19,'omo_parameters_server_env','en','53f1e52aa3ba401a5f5971f323bdf1e702282e5ac309e63a500ff365e0dd0a1f','{\"parameters.server_env.action.close\":{\"text\":\"Close\"},\"parameters.server_env.action.save\":{\"text\":\"Save {target}\"},\"parameters.server_env.auth.forbidden_message\":{\"text\":\"This panel is reserved for the server admin.\"},\"parameters.server_env.auth.forbidden_title\":{\"text\":\"Access Denied\"},\"parameters.server_env.auth.required_message\":{\"text\":\"Log in to access this panel.\"},\"parameters.server_env.auth.required_title\":{\"text\":\"Login Required\"},\"parameters.server_env.edit.secret_hint\":{\"text\":\"Secret fields remain hidden. If you leave a secret field empty, the current value is retained.\"},\"parameters.server_env.edit.title\":{\"text\":\"Edit {target}\"},\"parameters.server_env.error.forbidden\":{\"text\":\"Access reserved for the server admin.\"},\"parameters.server_env.error.invalid_field_value\":{\"text\":\"The chosen value for {label} is invalid.\"},\"parameters.server_env.error.password_invalid\":{\"text\":\"Invalid password.\"},\"parameters.server_env.error.password_required\":{\"text\":\"Please enter your password.\"},\"parameters.server_env.error.password_unavailable\":{\"text\":\"This account does not have a verifiable local password.\"},\"parameters.server_env.error.read_failed\":{\"text\":\"Unable to read the file {target}.\"},\"parameters.server_env.error.required\":{\"text\":\"Login required.\"},\"parameters.server_env.error.unlock_required\":{\"text\":\"Password confirmation required.\"},\"parameters.server_env.error.write_failed\":{\"text\":\"Unable to write the file {target}. Check permissions or a read-only Docker mount.\"},\"parameters.server_env.feedback.invalid_response\":{\"text\":\"Invalid response.\"},\"parameters.server_env.feedback.operation_done\":{\"text\":\"Operation completed.\"},\"parameters.server_env.feedback.save_failed\":{\"text\":\"Unable to save the file {target}.\"},\"parameters.server_env.feedback.unlock_failed\":{\"text\":\"Verification failed.\"},\"parameters.server_env.feedback.unlock_success\":{\"text\":\"Verification successful.\"},\"parameters.server_env.field.APP_LANG.label\":{\"text\":\"Default Language\"},\"parameters.server_env.field.COOKIE_ROOT_HOST.help\":{\"text\":\"Optional. If provided, forces cookie sharing to this exact root, e.g., dev.opengov.tools to share between dev.opengov.tools and *.dev.opengov.tools without affecting prod.\"},\"parameters.server_env.field.COOKIE_ROOT_HOST.label\":{\"text\":\"Cookie Root\"},\"parameters.server_env.field.COOKIE_SCOPE_MODE.help\":{\"text\":\"Auto isolates dev, beta, and deploy by default in host-only. Environment shares in *.dev.domain.tld. Parent shares in *.domain.tld. Host forces a cookie limited to the current host.\"},\"parameters.server_env.field.COOKIE_SCOPE_MODE.label\":{\"text\":\"Cookie Scope\"},\"parameters.server_env.field.GITHUB_BUGREPORT_LABELS.label\":{\"text\":\"GitHub Labels\"},\"parameters.server_env.field.GITHUB_BUGREPORT_REPO_NAME.label\":{\"text\":\"GitHub Repository Name\"},\"parameters.server_env.field.GITHUB_BUGREPORT_REPO_OWNER.label\":{\"text\":\"GitHub Repository Owner\"},\"parameters.server_env.field.GITHUB_BUGREPORT_TOKEN.label\":{\"text\":\"GitHub Bug Report Token\"},\"parameters.server_env.field.GITHUB_BUGREPORT_USER_AGENT.label\":{\"text\":\"GitHub User-Agent\"},\"parameters.server_env.field.HOME_TITLE.label\":{\"text\":\"Home Page Title\"},\"parameters.server_env.field.MAIL_AUTH.label\":{\"text\":\"SMTP Authentication\"},\"parameters.server_env.field.MAIL_CHARSET.label\":{\"text\":\"Email Charset\"},\"parameters.server_env.field.MAIL_HOST.label\":{\"text\":\"SMTP Server\"},\"parameters.server_env.field.MAIL_PASS.label\":{\"text\":\"SMTP Password\"},\"parameters.server_env.field.MAIL_PORT.label\":{\"text\":\"SMTP Port\"},\"parameters.server_env.field.MAIL_SECURE.label\":{\"text\":\"SMTP Security\"},\"parameters.server_env.field.MAIL_SECURE.placeholder\":{\"text\":\"SSL, tls or empty\"},\"parameters.server_env.field.MAIL_USER.label\":{\"text\":\"SMTP User\"},\"parameters.server_env.field.OPENAI_API_KEY.label\":{\"text\":\"OpenAI Key\"},\"parameters.server_env.field.OPENAI_MODEL.label\":{\"text\":\"OpenAI Model\"},\"parameters.server_env.field.OPENAI_TRANSLATION_MODEL.label\":{\"text\":\"OpenAI Translation Model\"},\"parameters.server_env.field.OPENAI_UPLOAD_API_KEY.label\":{\"text\":\"OpenAI Upload Key\"},\"parameters.server_env.field.ORGANIZATION_SUBDOMAIN_ROUTING.help\":{\"text\":\"Enables URLs like orgname.domain.com. This requires special hosting configuration, with wildcard DNS and a web server capable of accepting subdomains.\"},\"parameters.server_env.field.ORGANIZATION_SUBDOMAIN_ROUTING.label\":{\"text\":\"Organization Subdomains\"},\"parameters.server_env.field.PATREON_CLIENT_ID.label\":{\"text\":\"Patreon Client ID\"},\"parameters.server_env.field.PATREON_CLIENT_SECRET.label\":{\"text\":\"Patreon Client Secret\"},\"parameters.server_env.field.PATREON_CREATOR_CAMPAIGN_ID.label\":{\"text\":\"Patreon Campaign ID\"},\"parameters.server_env.field.PATREON_REDIRECT_URI.label\":{\"text\":\"Patreon Redirect URI\"},\"parameters.server_env.field.PATREON_USER_AGENT.label\":{\"text\":\"Patreon User-Agent\"},\"parameters.server_env.field.PAYPAL_CLIENT_ID.label\":{\"text\":\"PayPal Client ID\"},\"parameters.server_env.field.SITE_TITLE.label\":{\"text\":\"Site Title\"},\"parameters.server_env.field.STADIA_MAPS_API_KEY.label\":{\"text\":\"Stadia Maps Key\"},\"parameters.server_env.field.TELEGRAM_BOT_TOKEN.label\":{\"text\":\"Telegram Token\"},\"parameters.server_env.field.secret_keep.help\":{\"text\":\"Leave empty to keep the current value.\"},\"parameters.server_env.hero.description\":{\"text\":\"This panel allows you to complete global environment file variables outside the database, such as Telegram, Patreon, OpenAI, SMTP, or GitHub.\"},\"parameters.server_env.hero.eyebrow\":{\"text\":\"Sensitive Configuration\"},\"parameters.server_env.hero.target\":{\"text\":\"Target file: {target}\"},\"parameters.server_env.hero.title\":{\"text\":\"Server Admin\"},\"parameters.server_env.hero.unlock_ttl\":{\"text\":\"Verification valid for {minutes} min\"},\"parameters.server_env.option.boolean.false\":{\"text\":\"No\"},\"parameters.server_env.option.boolean.true\":{\"text\":\"Yes\"},\"parameters.server_env.password.unavailable_message\":{\"text\":\"This account does not have a verifiable local password. Editing {target} via this panel is currently blocked.\"},\"parameters.server_env.password.unavailable_title\":{\"text\":\"Password Unavailable\"},\"parameters.server_env.secret.configured\":{\"text\":\"Already Configured\"},\"parameters.server_env.secret.empty\":{\"text\":\"Not Provided\"},\"parameters.server_env.section.ai.intro\":{\"text\":\"Keys and models used by OpenAI functions.\"},\"parameters.server_env.section.ai.title\":{\"text\":\"AI\"},\"parameters.server_env.section.general.intro\":{\"text\":\"Global site settings visible on multiple pages.\"},\"parameters.server_env.section.general.title\":{\"text\":\"General Settings\"},\"parameters.server_env.section.integrations.intro\":{\"text\":\"Optional external server services.\"},\"parameters.server_env.section.integrations.title\":{\"text\":\"Integrations\"},\"parameters.server_env.section.mail.intro\":{\"text\":\"General SMTP server configuration.\"},\"parameters.server_env.section.mail.title\":{\"text\":\"Email\"},\"parameters.server_env.status.saved\":{\"text\":\"The file {target} has been updated.\"},\"parameters.server_env.status.unlocked\":{\"text\":\"Verification successful.\"},\"parameters.server_env.unlock.description\":{\"text\":\"Before displaying the form, enter the password of the connected account. This temporarily unlocks editing of this panel.\"},\"parameters.server_env.unlock.password_label\":{\"text\":\"Current Password\"},\"parameters.server_env.unlock.submit\":{\"text\":\"Open Form\"},\"parameters.server_env.unlock.title\":{\"text\":\"Verify Your Identity\"}}','machine_translated','2026-07-25 11:14:57','2026-07-25 11:15:10'),
(23,'omo_projects','en','ffcce9ebb2bec5eb75efb65337c720625c463ded7016ae23e4bfb3d84e785e04','{\"projects.action.archive\":{\"text\":\"Archive\"},\"projects.action.attach\":{\"text\":\"Attach a project\"},\"projects.action.cancel\":{\"text\":\"Cancel\"},\"projects.action.close\":{\"text\":\"Close\"},\"projects.action.delete\":{\"text\":\"Delete\"},\"projects.action.edit\":{\"text\":\"Edit\"},\"projects.action.move\":{\"text\":\"Move\"},\"projects.action.new\":{\"text\":\"New project\"},\"projects.action.save\":{\"text\":\"Save\"},\"projects.action_error\":{\"text\":\"Unable to update the project.\"},\"projects.archive.confirm\":{\"text\":\"This project is not finished. Archive it anyway?\"},\"projects.attach.empty\":{\"text\":\"No orphan project matches the search.\"},\"projects.attach.hint\":{\"text\":\"Choose an orphan project in the structure.\"},\"projects.attach.search\":{\"text\":\"Search for a project\"},\"projects.attach.select_required\":{\"text\":\"Choose a project to attach.\"},\"projects.attach.submit\":{\"text\":\"Attach\"},\"projects.attach.title\":{\"text\":\"Attach a project\"},\"projects.column.next\":{\"text\":\"Next column\"},\"projects.column.previous\":{\"text\":\"Previous column\"},\"projects.delete.confirm\":{\"text\":\"Permanently delete this project and its {count} subprojects? This action is irreversible.\"},\"projects.detail.badge\":{\"text\":\"Project\"},\"projects.detail.breadcrumb\":{\"text\":\"Parent projects\"},\"projects.detail.breadcrumb.expand\":{\"text\":\"Show all parent projects\"},\"projects.detail.calculated_importance\":{\"text\":\"Calculated strategic importance\"},\"projects.detail.calculated_importance_help\":{\"text\":\"Calculated from declared strategic importance, project chain, and holarchic position.\"},\"projects.detail.context\":{\"text\":\"Context\"},\"projects.detail.created\":{\"text\":\"Created on\"},\"projects.detail.date_end\":{\"text\":\"End\"},\"projects.detail.date_start\":{\"text\":\"Start\"},\"projects.detail.description\":{\"text\":\"Description\"},\"projects.detail.empty_description\":{\"text\":\"No description for this project.\"},\"projects.detail.importance\":{\"text\":\"Strategic importance\"},\"projects.detail.importance_level\":{\"one\":\"{count}/5\",\"other\":\"{count}/5\"},\"projects.detail.none\":{\"text\":\"Not provided\"},\"projects.detail.organisation\":{\"text\":\"Organization\"},\"projects.detail.parent\":{\"text\":\"Parent project\"},\"projects.detail.priority\":{\"text\":\"Priority\"},\"projects.detail.priority_level\":{\"one\":\"P{count}\",\"other\":\"P{count}\"},\"projects.detail.responsible\":{\"text\":\"Responsible\"},\"projects.detail.schedule\":{\"text\":\"Planned dates\"},\"projects.detail.size\":{\"text\":\"Size\"},\"projects.detail.status\":{\"text\":\"Status\"},\"projects.detail.subprojects\":{\"text\":\"Subprojects\"},\"projects.detail.subprojects_empty\":{\"text\":\"No subproject at the moment.\"},\"projects.detail.subprojects_new\":{\"text\":\"New\"},\"projects.drawer.description\":{\"text\":\"Project details and information.\"},\"projects.drawer.title\":{\"text\":\"Project\"},\"projects.empty.children\":{\"text\":\"No project in this context or its direct children.\"},\"projects.empty.column\":{\"text\":\"No project in this column.\"},\"projects.empty.contextual\":{\"text\":\"No project in this context.\"},\"projects.empty.descendants\":{\"text\":\"No project in this context or its descendants.\"},\"projects.error.action\":{\"text\":\"Unknown action.\"},\"projects.error.context\":{\"text\":\"Invalid or inaccessible context.\"},\"projects.error.dates\":{\"text\":\"The end date must be later than or equal to the start date.\"},\"projects.error.forbidden\":{\"text\":\"You cannot modify this project.\"},\"projects.error.holon\":{\"text\":\"The destination holon is invalid or inaccessible.\"},\"projects.error.method\":{\"text\":\"This action must be sent via POST.\"},\"projects.error.not_found\":{\"text\":\"Project not found.\"},\"projects.error.organization\":{\"text\":\"Invalid or inaccessible organization.\"},\"projects.error.save\":{\"text\":\"Unable to save the project.\"},\"projects.error.status\":{\"text\":\"The project status is invalid.\"},\"projects.error.title\":{\"text\":\"The title is required.\"},\"projects.field.capture_mode\":{\"text\":\"Telegram capture mode\"},\"projects.field.description\":{\"text\":\"Description\"},\"projects.field.description_placeholder\":{\"text\":\"What result do you want to achieve?\"},\"projects.field.end_date\":{\"text\":\"Planned end\"},\"projects.field.holon\":{\"text\":\"Associated circle or role\"},\"projects.field.importance\":{\"text\":\"Strategic importance\"},\"projects.field.parent\":{\"text\":\"Parent project\"},\"projects.field.priority\":{\"text\":\"Priority\"},\"projects.field.responsible\":{\"text\":\"Responsible\"},\"projects.field.size\":{\"text\":\"Size\"},\"projects.field.start_date\":{\"text\":\"Planned start\"},\"projects.field.status\":{\"text\":\"Initial status\"},\"projects.field.title\":{\"text\":\"Project title\"},\"projects.form.assignment\":{\"text\":\"Responsibility and hierarchy\"},\"projects.form.attention\":{\"text\":\"Attention level\"},\"projects.form.description\":{\"text\":\"Define the goal, dates, and attention level of the project.\"},\"projects.form.description_field\":{\"text\":\"Simple HTML description\"},\"projects.form.edit_description\":{\"text\":\"Update the goal, dates, and settings of the project.\"},\"projects.form.edit_submit\":{\"text\":\"Save changes\"},\"projects.form.edit_title\":{\"text\":\"Edit project\"},\"projects.form.more_options\":{\"text\":\"Additional options\"},\"projects.form.more_options_toggle\":{\"text\":\"Show or hide additional options\"},\"projects.form.planning\":{\"text\":\"Planning\"},\"projects.form.submit\":{\"text\":\"Create project\"},\"projects.form.title\":{\"text\":\"New project\"},\"projects.holon.choose\":{\"text\":\"Choose a circle or role\"},\"projects.holon_picker.confirm\":{\"text\":\"Use this context\"},\"projects.holon_picker.hint\":{\"text\":\"Choose the circle or role to assign this project.\"},\"projects.holon_picker.title\":{\"text\":\"Choose the circle or role\"},\"projects.importance.none\":{\"text\":\"Not defined\"},\"projects.level.none\":{\"text\":\"Not defined\"},\"projects.list.planned.after_tomorrow\":{\"text\":\"Day after tomorrow\"},\"projects.list.planned.in_progress\":{\"text\":\"In progress\"},\"projects.list.planned.later\":{\"text\":\"Later\"},\"projects.list.planned.next_week\":{\"text\":\"Next week\"},\"projects.list.planned.none\":{\"text\":\"No planning\"},\"projects.list.planned.overdue\":{\"text\":\"Overdue\"},\"projects.list.planned.this_week\":{\"text\":\"This week\"},\"projects.list.planned.tomorrow\":{\"text\":\"Tomorrow\"},\"projects.list.priority.none\":{\"text\":\"No priority\"},\"projects.loading\":{\"text\":\"Loading project...\"},\"projects.loading_error\":{\"text\":\"Unable to load this project.\"},\"projects.move.hint\":{\"text\":\"Choose the destination holon in the structure.\"},\"projects.move.select_required\":{\"text\":\"Choose a destination holon.\"},\"projects.move.submit\":{\"text\":\"Move here\"},\"projects.move.title\":{\"text\":\"Move project\"},\"projects.parent.choose\":{\"text\":\"Choose a project\"},\"projects.parent.none\":{\"text\":\"No parent project\"},\"projects.parent_picker.choose\":{\"text\":\"Use this project\"},\"projects.parent_picker.empty\":{\"text\":\"No project matches the search.\"},\"projects.parent_picker.none\":{\"text\":\"No parent project\"},\"projects.parent_picker.scope_children\":{\"text\":\"Direct children\"},\"projects.parent_picker.scope_descendants\":{\"text\":\"Descendants\"},\"projects.parent_picker.scope_local\":{\"text\":\"Local\"},\"projects.parent_picker.search\":{\"text\":\"Search for a project\"},\"projects.parent_picker.title\":{\"text\":\"Choose the parent project\"},\"projects.priority.none\":{\"text\":\"Not defined\"},\"projects.responsible.help\":{\"text\":\"Only active people from this organization are suggested.\"},\"projects.responsible.none\":{\"text\":\"No responsible\"},\"projects.scope.children\":{\"text\":\"Direct children\"},\"projects.scope.contextual\":{\"text\":\"Local\"},\"projects.scope.descendants\":{\"text\":\"Descendants\"},\"projects.sort.aria\":{\"text\":\"Sort projects\"},\"projects.sort.holon\":{\"text\":\"Holon\"},\"projects.sort.importance\":{\"text\":\"Strategic importance\"},\"projects.sort.planned\":{\"text\":\"Planning\"},\"projects.sort.priority\":{\"text\":\"Priority\"},\"projects.status.blocked\":{\"text\":\"Blocked\"},\"projects.status.done\":{\"text\":\"Done\"},\"projects.status.in_progress\":{\"text\":\"In progress\"},\"projects.status.ready\":{\"text\":\"Ready\"},\"projects.status.review\":{\"text\":\"To review\"},\"projects.status.someday\":{\"text\":\"Someday\"},\"projects.status_move\":{\"text\":\"Change status\"},\"projects.status_update_error\":{\"text\":\"Unable to change status.\"},\"projects.subprojects.label\":{\"text\":\"Subproject status\"},\"projects.success.save\":{\"text\":\"Project saved.\"},\"projects.success.status\":{\"text\":\"Status updated.\"},\"projects.title\":{\"text\":\"Projects\"},\"projects.view.aria\":{\"text\":\"Display mode\"},\"projects.view.kanban\":{\"text\":\"Kanban\"},\"projects.view.list\":{\"text\":\"List\"}}','machine_translated','2026-07-25 11:15:20','2026-07-25 11:15:40'),
(24,'omo_decisions_index','en','5185290b694c46963ce1b3cce5ac0cf6f985b6957738bd6c2511dcd6d70abc95','{\"decisions.index.action.archive\":{\"text\":\"Archive\"},\"decisions.index.action.confirm_archive\":{\"text\":\"Archive this decision?\"},\"decisions.index.action.confirm_delete\":{\"text\":\"Permanently delete this decision and its related items?\"},\"decisions.index.action.consult\":{\"text\":\"Consult\"},\"decisions.index.action.delete\":{\"text\":\"Delete\"},\"decisions.index.action.edit_continue\":{\"text\":\"Continue editing\"},\"decisions.index.action.error_update\":{\"text\":\"Unable to update this decision at the moment.\"},\"decisions.index.action.export\":{\"text\":\"Export\"},\"decisions.index.action.manage\":{\"text\":\"Manage\"},\"decisions.index.action.more\":{\"text\":\"...\"},\"decisions.index.action.more_aria\":{\"text\":\"More actions for this decision\"},\"decisions.index.action.open\":{\"text\":\"Open\"},\"decisions.index.action.open_editor_title\":{\"text\":\"Decisions\"},\"decisions.index.action.participant_qr_codes\":{\"text\":\"Print QR codes\"},\"decisions.index.action.participate\":{\"text\":\"Participate\"},\"decisions.index.action.view\":{\"text\":\"View\"},\"decisions.index.action.view_edit\":{\"text\":\"View / edit\"},\"decisions.index.action.view_results\":{\"text\":\"View results\"},\"decisions.index.card.invited_email\":{\"text\":\"Email invitation\"},\"decisions.index.card.manage\":{\"text\":\"Management\"},\"decisions.index.card.owner\":{\"text\":\"Created by you\"},\"decisions.index.compact.header.activity\":{\"text\":\"Activity\"},\"decisions.index.compact.header.name\":{\"text\":\"Decision\"},\"decisions.index.compact.header.scope\":{\"text\":\"Structure\"},\"decisions.index.compact.header.status\":{\"text\":\"Status\"},\"decisions.index.context.holon_denied\":{\"text\":\"Access denied to this holon.\"},\"decisions.index.context.holon_not_found\":{\"text\":\"Holon not found for this organization.\"},\"decisions.index.context.organization_denied\":{\"text\":\"Access denied to this organization.\"},\"decisions.index.context.organization_invalid\":{\"text\":\"Invalid organization.\"},\"decisions.index.context.organization_not_found\":{\"text\":\"Organization not found.\"},\"decisions.index.controls.density.aria\":{\"text\":\"Display density of decisions\"},\"decisions.index.controls.density.compact\":{\"text\":\"Compact\"},\"decisions.index.controls.density.detail\":{\"text\":\"Detail\"},\"decisions.index.controls.sort.alpha\":{\"text\":\"Alphabetical\"},\"decisions.index.controls.sort.aria\":{\"text\":\"Sort decisions\"},\"decisions.index.controls.sort.time\":{\"text\":\"Time-based\"},\"decisions.index.deadline_label\":{\"text\":\"Deadline\"},\"decisions.index.description\":{\"text\":\"Centralize consultations and decision-making accessible in your organization here, then open the right flow according to their status.\"},\"decisions.index.empty.cta\":{\"text\":\"Create the first decision\"},\"decisions.index.empty.text\":{\"text\":\"Create your first decision to prepare a vote, majority judgment, consent, or consultation.\"},\"decisions.index.empty.title\":{\"text\":\"No decisions at the moment\"},\"decisions.index.error\":{\"text\":\"Unable to load the list at the moment.\"},\"decisions.index.export.format.coming_soon\":{\"text\":\"Coming soon\"},\"decisions.index.export.format.csv\":{\"text\":\"CSV\"},\"decisions.index.export.format.csv_description\":{\"text\":\"Enriched table with type, block, question, details, and results.\"},\"decisions.index.export.format.json\":{\"text\":\"JSON\"},\"decisions.index.export.format.json_description\":{\"text\":\"Ballot blueprint and structured results, without full dump.\"},\"decisions.index.export.format.pdf\":{\"text\":\"PDF\"},\"decisions.index.export.format.pdf_description\":{\"text\":\"Presentation version prepared for later.\"},\"decisions.index.export.format.xml\":{\"text\":\"XML\"},\"decisions.index.export.format.xml_description\":{\"text\":\"Same structured content as JSON, in XML format.\"},\"decisions.index.export.modal_intro\":{\"text\":\"Choose the export format suitable for this decision-making mode.\"},\"decisions.index.export.modal_title\":{\"text\":\"Export this ballot\"},\"decisions.index.export.open\":{\"text\":\"Download\"},\"decisions.index.filters.holon.all\":{\"text\":\"All structures\"},\"decisions.index.filters.holon.label\":{\"text\":\"Structure\"},\"decisions.index.filters.holon.none\":{\"text\":\"Without structure\"},\"decisions.index.filters.method.all\":{\"text\":\"All methods\"},\"decisions.index.filters.method.consent\":{\"text\":\"Consent\"},\"decisions.index.filters.method.label\":{\"text\":\"Method\"},\"decisions.index.filters.method.majority_judgment\":{\"text\":\"Majority judgment\"},\"decisions.index.filters.method.simple_vote\":{\"text\":\"Simple vote\"},\"decisions.index.filters.reset\":{\"text\":\"Reset\"},\"decisions.index.filters.search.label\":{\"text\":\"Search by title\"},\"decisions.index.filters.search.placeholder\":{\"text\":\"Search for a decision\"},\"decisions.index.filters.status.active\":{\"text\":\"Active\"},\"decisions.index.filters.status.all\":{\"text\":\"All\"},\"decisions.index.filters.status.archived\":{\"text\":\"Archived\"},\"decisions.index.filters.status.consultation\":{\"text\":\"In consultation\"},\"decisions.index.filters.status.draft\":{\"text\":\"In preparation\"},\"decisions.index.filters.status.evaluation\":{\"text\":\"In evaluation\"},\"decisions.index.filters.status.results\":{\"text\":\"Results\"},\"decisions.index.filters.status.scheduled\":{\"text\":\"Scheduled\"},\"decisions.index.filters.toggle.hide\":{\"text\":\"Hide filters\"},\"decisions.index.filters.toggle.show\":{\"text\":\"Show filters\"},\"decisions.index.filters.type.all\":{\"text\":\"All types\"},\"decisions.index.filters.type.consultation\":{\"text\":\"Consultative\"},\"decisions.index.filters.type.decision\":{\"text\":\"Decision-making\"},\"decisions.index.filters.type.label\":{\"text\":\"Type\"},\"decisions.index.group.earlier\":{\"text\":\"Earlier\"},\"decisions.index.group.last_month\":{\"text\":\"Last month\"},\"decisions.index.group.last_week\":{\"text\":\"Last week\"},\"decisions.index.group.last_year\":{\"text\":\"Last year\"},\"decisions.index.group.this_month\":{\"text\":\"This month\"},\"decisions.index.group.this_week\":{\"text\":\"This week\"},\"decisions.index.group.this_year\":{\"text\":\"This year\"},\"decisions.index.group.today\":{\"text\":\"Today\"},\"decisions.index.group.too_far\":{\"text\":\"Too far\"},\"decisions.index.group.yesterday\":{\"text\":\"Yesterday\"},\"decisions.index.last_activity_label\":{\"text\":\"Last activity\"},\"decisions.index.loading\":{\"text\":\"Loading decisions…\"},\"decisions.index.method_label\":{\"text\":\"Method\"},\"decisions.index.new\":{\"text\":\"New decision\"},\"decisions.index.no_holon\":{\"text\":\"No linked structure\"},\"decisions.index.no_results.text\":{\"text\":\"Try another status, broaden the search, or reset the filters.\"},\"decisions.index.no_results.title\":{\"text\":\"No results with these filters\"},\"decisions.index.owner_label\":{\"text\":\"In charge\"},\"decisions.index.participants_label\":{\"text\":\"Participants\"},\"decisions.index.proposals_label\":{\"text\":\"Proposals\"},\"decisions.index.responses_label\":{\"text\":\"Responses\"},\"decisions.index.scope.children\":{\"text\":\"Direct children\"},\"decisions.index.scope.contextual\":{\"text\":\"Contextual\"},\"decisions.index.scope.descendants\":{\"text\":\"Descendants\"},\"decisions.index.scope_label\":{\"text\":\"Structure\"},\"decisions.index.title\":{\"text\":\"Decisions\"},\"decisions.index.type_label\":{\"text\":\"Type\"}}','machine_translated','2026-07-25 11:15:26','2026-07-25 11:15:40'),
(27,'omo_policy','en','f8552f359ef3640d166c6644fc54771ee825759d8085d611bf7567fde4012d66','{\"policy.close\":{\"text\":\"Close\"},\"policy.description\":{\"text\":\"Rules applicable to the current context.\"},\"policy.description_label\":{\"text\":\"Rule\"},\"policy.drawer.description\":{\"text\":\"This rule will be linked to the current context.\"},\"policy.drawer.title\":{\"text\":\"New local rule\"},\"policy.empty\":{\"text\":\"No rules in this context.\"},\"policy.error.context\":{\"text\":\"Invalid or inaccessible context.\"},\"policy.error.forbidden\":{\"text\":\"You cannot create a rule in this context.\"},\"policy.error.load\":{\"text\":\"Unable to load the form.\"},\"policy.error.method\":{\"text\":\"This action must be sent via POST.\"},\"policy.error.save\":{\"text\":\"Unable to save the rule.\"},\"policy.expiration\":{\"text\":\"Expires on {date}\"},\"policy.field.description\":{\"text\":\"Rule\"},\"policy.field.expiration_date\":{\"text\":\"Expiration date\"},\"policy.field.intention\":{\"text\":\"Intention\"},\"policy.field.review_date\":{\"text\":\"Review date\"},\"policy.field.title\":{\"text\":\"Title\"},\"policy.intention\":{\"text\":\"Intention\"},\"policy.new\":{\"text\":\"New rule\"},\"policy.review\":{\"text\":\"To be reviewed on {date}\"},\"policy.save\":{\"text\":\"Save\"},\"policy.success.save\":{\"text\":\"Rule saved.\"},\"policy.title\":{\"text\":\"Regulation\"}}','machine_translated','2026-07-25 11:15:46','2026-07-25 11:15:50'),
(29,'omo_calendar_index','en','ba735f0a0155ce61529b7855b48be4302cda03f70df56628cb88ffcd7bf3e145','{\"calendar.action.add\":{\"text\":\"Add an event\"},\"calendar.action.delete\":{\"text\":\"Delete\"},\"calendar.action.edit\":{\"text\":\"Edit\"},\"calendar.action.more\":{\"text\":\"Actions\"},\"calendar.action.today\":{\"text\":\"Today\"},\"calendar.axis.all_day\":{\"text\":\"All day\"},\"calendar.confirm.delete\":{\"text\":\"Delete this event?\"},\"calendar.context.organization\":{\"text\":\"Organization\"},\"calendar.day.fri\":{\"text\":\"Fri\"},\"calendar.day.mon\":{\"text\":\"Mon\"},\"calendar.day.more\":{\"one\":\"+{count} other\",\"other\":\"+{count} others\"},\"calendar.day.sat\":{\"text\":\"Sat\"},\"calendar.day.sun\":{\"text\":\"Sun\"},\"calendar.day.thu\":{\"text\":\"Thu\"},\"calendar.day.tue\":{\"text\":\"Tue\"},\"calendar.day.wed\":{\"text\":\"Wed\"},\"calendar.delete.documents.no\":{\"text\":\"No\"},\"calendar.delete.documents.question\":{\"text\":\"Do you want to delete the associated documents?\"},\"calendar.delete.documents.title\":{\"text\":\"Associated documents\"},\"calendar.delete.documents.yes\":{\"text\":\"Yes\"},\"calendar.drawer.description\":{\"text\":\"View the details and edit if necessary.\"},\"calendar.drawer.title\":{\"text\":\"Event\"},\"calendar.empty.day\":{\"text\":\"No events on this day.\"},\"calendar.empty.list\":{\"text\":\"No upcoming events.\"},\"calendar.empty.month\":{\"text\":\"No events this period.\"},\"calendar.empty.week\":{\"text\":\"No events this week.\"},\"calendar.error.delete\":{\"text\":\"Unable to delete this event.\"},\"calendar.error.load_form\":{\"text\":\"Unable to load this content.\"},\"calendar.list.column.context\":{\"text\":\"Context\"},\"calendar.list.column.date\":{\"text\":\"Date\"},\"calendar.list.column.event\":{\"text\":\"Event\"},\"calendar.list.column.schedule\":{\"text\":\"Schedule\"},\"calendar.loading\":{\"text\":\"Loading...\"},\"calendar.page.description\":{\"text\":\"View your organization\'s events and add new ones.\"},\"calendar.page.title\":{\"text\":\"Calendar\"},\"calendar.scope.children\":{\"text\":\"Direct children\"},\"calendar.scope.contextual\":{\"text\":\"Contextual\"},\"calendar.scope.descendants\":{\"text\":\"Descendants\"},\"calendar.section.next_month\":{\"text\":\"Next month\"},\"calendar.section.next_week\":{\"text\":\"Next week\"},\"calendar.section.this_month\":{\"text\":\"This month\"},\"calendar.section.this_week\":{\"text\":\"This week\"},\"calendar.section.today\":{\"text\":\"Today\"},\"calendar.section.tomorrow\":{\"text\":\"Tomorrow\"},\"calendar.summary.day\":{\"text\":\"{count} event(s) this day\"},\"calendar.summary.list\":{\"text\":\"{count} upcoming event(s)\"},\"calendar.summary.month\":{\"text\":\"{count} event(s) this month\"},\"calendar.summary.week\":{\"text\":\"{count} event(s) this week\"},\"calendar.view.day\":{\"text\":\"Day\"},\"calendar.view.list\":{\"text\":\"List\"},\"calendar.view.month\":{\"text\":\"Month\"},\"calendar.view.week\":{\"text\":\"Week\"}}','machine_translated','2026-07-25 11:15:55','2026-07-25 11:16:01'),
(31,'omo_personal_space_panel','en','99a14c53fe57c583c8aecee4a7b4021b12046df2284878edf489a61a4338598a','{\"personal_space.calendar.context.organization\":{\"text\":\"Org\"},\"personal_space.calendar.empty\":{\"text\":\"No upcoming dates for your contexts.\"},\"personal_space.date.unknown\":{\"text\":\"Unknown date\"},\"personal_space.decisions.action\":{\"one\":\"{count} decision to make\",\"other\":\"{count} decisions to make\"},\"personal_space.decisions.consultation\":{\"one\":\"{count} consultation in progress\",\"other\":\"{count} consultations in progress\"},\"personal_space.decisions.empty\":{\"text\":\"No decisions to track at the moment.\"},\"personal_space.decisions.finalize\":{\"one\":\"{count} draft decision to finalize\",\"other\":\"{count} draft decisions to finalize\"},\"personal_space.decisions.responded\":{\"one\":\"including {count} already responded\",\"other\":\"including {count} already responded\"},\"personal_space.decisions.results\":{\"one\":\"{count} completed decision with result to review\",\"other\":\"{count} completed decisions with results to review\"},\"personal_space.documents.empty\":{\"text\":\"No recent documents in this context.\"},\"personal_space.empty\":{\"text\":\"No personal summary available with active applications at the moment.\"},\"personal_space.heading\":{\"text\":\"Personal Space\"},\"personal_space.intro\":{\"text\":\"A quick summary of topics that concern you in this space.\"},\"personal_space.login_required\":{\"text\":\"Log in to view your personal summary.\"},\"personal_space.open_app\":{\"text\":\"Open\"},\"personal_space.section.calendar\":{\"text\":\"My Upcoming Meetings\"},\"personal_space.section.decisions\":{\"text\":\"Decisions\"},\"personal_space.section.documents_recent\":{\"text\":\"Documents - Latest Changes\"},\"personal_space.section.structure\":{\"text\":\"Structure\"},\"personal_space.section.team\":{\"text\":\"Team\"},\"personal_space.structure.empty\":{\"text\":\"No recent changes to display.\"},\"personal_space.team.empty\":{\"text\":\"No upcoming anniversaries to display.\"},\"personal_space.team.pro.new\":{\"text\":\"New\"},\"personal_space.team.pro.new_detail_prefix\":{\"text\":\"Arrived on\"},\"personal_space.team.pro.soon_prefix\":{\"text\":\"Pro anniversary in\"},\"personal_space.team.pro.today\":{\"text\":\"Pro anniversary today\"},\"personal_space.team.tag.personal\":{\"text\":\"Personal\"},\"personal_space.team.tag.pro\":{\"text\":\"Pro\"}}','machine_translated','2026-07-25 11:22:13','2026-07-25 11:22:17'),
(33,'omo_team_module','en','e7a26a02adc42dd74ae6c21138da71c738f7ca52f05c6eb1de51633990ae57bb','{\"team.action.add_member\":{\"text\":\"Add a member\"},\"team.action.cancel_invitation\":{\"text\":\"Cancel invitation\"},\"team.action.grant_context_admin\":{\"text\":\"Set as admin of the context {context}\"},\"team.action.remove_from_context\":{\"text\":\"Remove from context {context}\"},\"team.action.revoke_context_admin\":{\"text\":\"Remove admin status from context {context}\"},\"team.api.action_completed\":{\"text\":\"Action completed.\"},\"team.api.context_not_found\":{\"text\":\"Context not found.\"},\"team.api.invalid_action\":{\"text\":\"Invalid member action.\"},\"team.api.invitation_resend_failed\":{\"text\":\"The invitation could not be resent.\"},\"team.api.invitation_resent\":{\"text\":\"Invitation resent.\"},\"team.api.no_right_add_member\":{\"text\":\"You do not have the right to add a member in this context.\"},\"team.api.no_right_manage_admin\":{\"text\":\"You do not have the right to manage admin status in this context.\"},\"team.api.no_right_modify_context\":{\"text\":\"You do not have the right to modify this context.\"},\"team.api.pending_admin_invitation_not_found\":{\"text\":\"No pending admin invitation was found for this person.\"},\"team.api.pending_invitation_not_found\":{\"text\":\"No pending invitation was found for this person.\"},\"team.api.unknown_action\":{\"text\":\"Unknown action.\"},\"team.column.first_name\":{\"text\":\"First name\"},\"team.column.identity\":{\"text\":\"Identity\"},\"team.column.name\":{\"text\":\"Surname\"},\"team.column.phone\":{\"text\":\"Phone\"},\"team.confirm.cancel_invitation\":{\"text\":\"Cancel the invitation sent to {name}?\"},\"team.confirm.grant_context_admin\":{\"text\":\"Set {name} as admin of the context {context}?\"},\"team.confirm.remove\":{\"text\":\"Remove {name} from the context {context}?\"},\"team.confirm.revoke_context_admin\":{\"text\":\"Remove admin status from {name} for the context {context}?\"},\"team.empty.children\":{\"text\":\"No person is yet linked to this context or its direct children.\"},\"team.empty.contextual\":{\"text\":\"No person is yet linked to this {context_type}.\"},\"team.empty.descendants\":{\"text\":\"No person is yet linked to this context and its descendants.\"},\"team.error.invalid_organization\":{\"text\":\"Invalid organization.\"},\"team.error.people_forbidden\":{\"text\":\"Access to the people list denied.\"},\"team.holon_type.circle\":{\"text\":\"circle\"},\"team.holon_type.context\":{\"text\":\"context\"},\"team.holon_type.group\":{\"text\":\"group\"},\"team.holon_type.holon\":{\"text\":\"holon\"},\"team.holon_type.organization\":{\"text\":\"organization\"},\"team.holon_type.role\":{\"text\":\"role\"},\"team.map.empty.children\":{\"text\":\"No member has a geographical position in this context or its direct children yet.\"},\"team.map.empty.contextual\":{\"text\":\"No member has a geographical position in this context yet.\"},\"team.map.empty.descendants\":{\"text\":\"No member has a geographical position in this context and its descendants yet.\"},\"team.map.open_profile\":{\"text\":\"Open profile\"},\"team.map.summary\":{\"one\":\"{count} geolocated member.\",\"other\":\"{count} geolocated members.\"},\"team.member.actions_for\":{\"text\":\"Actions for {name}\"},\"team.member.added\":{\"text\":\"Added\"},\"team.member.admin_context\":{\"text\":\"Context admin\"},\"team.member.admin_organization\":{\"text\":\"Organization admin\"},\"team.member.admin_short\":{\"text\":\"Admin\"},\"team.member.email\":{\"text\":\"Email\"},\"team.member.last_connection\":{\"text\":\"Connection\"},\"team.member.last_seen_global\":{\"text\":\"{organization} (global: {global})\"},\"team.member.never\":{\"text\":\"Never\"},\"team.member.not_provided\":{\"text\":\"Not provided\"},\"team.member.open_contextual_profile\":{\"text\":\"Open contextual profile of {name}\"},\"team.member.pending\":{\"text\":\"Pending\"},\"team.member.photo_coming\":{\"text\":\"Photo coming soon\"},\"team.member.this_member\":{\"text\":\"this member\"},\"team.member.user_fallback\":{\"text\":\"User {userId}\"},\"team.message.update_failed\":{\"text\":\"Unable to update this member.\"},\"team.message.update_failed_later\":{\"text\":\"Unable to update this member at the moment.\"},\"team.message.update_success\":{\"text\":\"Update successful.\"},\"team.popup.choose_action\":{\"text\":\"Choose the action to apply in this {context}.\"},\"team.popup.context_forbidden\":{\"text\":\"Access to this context denied.\"},\"team.popup.context_not_found\":{\"text\":\"Context not found for this organization.\"},\"team.popup.context_prefix\":{\"text\":\"Context\"},\"team.popup.contextual_actions\":{\"text\":\"Contextual actions\"},\"team.popup.invalid_member_context\":{\"text\":\"Invalid member context.\"},\"team.popup.member_management\":{\"text\":\"Member management\"},\"team.popup.no_manage_rights\":{\"text\":\"You do not have the rights to modify this {context}.\"},\"team.popup.organization_context_missing\":{\"text\":\"No organizational context is available.\"},\"team.popup.organization_forbidden\":{\"text\":\"Access to this organization denied.\"},\"team.popup.organization_not_found\":{\"text\":\"Organization not found.\"},\"team.popup.user_forbidden\":{\"text\":\"Access to this user denied.\"},\"team.popup.user_not_found\":{\"text\":\"User not found.\"},\"team.scope.children\":{\"text\":\"Direct children\"},\"team.scope.contextual\":{\"text\":\"Contextual\"},\"team.scope.descendants\":{\"text\":\"Descendants\"},\"team.scope.members_aria\":{\"text\":\"Members scope\"},\"team.title\":{\"text\":\"Team\"},\"team.view.cards\":{\"text\":\"Cards\"},\"team.view.choice_aria\":{\"text\":\"View choice\"},\"team.view.compact\":{\"text\":\"Compact\"},\"team.view.map\":{\"text\":\"Geo map\"}}','machine_translated','2026-07-25 11:23:39','2026-07-25 11:23:49');
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
  `lastname` varchar(150) DEFAULT NULL,
  `presentation` text DEFAULT NULL,
  `latlong` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `firstname` varchar(150) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `password` varchar(80) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES
(1,'admin@omo.test',NULL,NULL,NULL,NULL,NULL,'Admin',NULL,'$2y$10$ES6a68iJbT4z8MxzjNBMoOEtBAn7HJCEqdUnTdBNXQGSerKh.ZQC6','2026-04-21 09:01:00','2026-07-24 18:09:07',1,1,NULL,NULL,'{\"lang\":\"fr\"}',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_push_subscription`
--

LOCK TABLES `notification_push_subscription` WRITE;
/*!40000 ALTER TABLE `notification_push_subscription` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_push_subscription` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_preference`
--

DROP TABLE IF EXISTS `notification_preference`;
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
  CONSTRAINT `fk_notification_preference_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notification_preference_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
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
  `open_token` char(64) NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_notification_user_source` (`IDuser`,`source_key`),
  UNIQUE KEY `uniq_notification_open_token` (`open_token`),
  KEY `idx_notification_inbox` (`IDuser`,`IDorganization`,`read_at`,`created_at`),
  KEY `idx_notification_unread_dedupe` (`IDuser`,`IDorganization`,`dedupe_key`,`read_at`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`IDuser`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notification_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateconnexion` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_holon_holon_user` (`IDholon`,`IDuser`),
  CONSTRAINT `fk_user_holon_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_holon`
--

LOCK TABLES `user_holon` WRITE;
/*!40000 ALTER TABLE `user_holon` DISABLE KEYS */;
INSERT INTO `user_holon` VALUES
(1,1,1,NULL,NULL,'2024-03-05 16:43:15',NULL,1),
(2,1,683,'[]',NULL,'2026-07-23 13:38:59',NULL,0),
(3,1,693,NULL,NULL,'2026-07-23 13:48:17',NULL,1),
(4,1,692,NULL,NULL,'2026-07-23 13:49:46',NULL,1),
(5,1,682,NULL,NULL,'2026-07-23 13:50:25',NULL,1),
(6,1,708,NULL,NULL,'2026-07-23 13:51:33',NULL,1),
(7,1,833,'{\"isAdmin\":true}',NULL,'2026-07-28 09:08:57',NULL,1);
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
  `created_at` datetime DEFAULT NULL,
  `last_attempt_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_login_token`
--

LOCK TABLES `user_login_token` WRITE;
/*!40000 ALTER TABLE `user_login_token` DISABLE KEYS */;
INSERT INTO `user_login_token` VALUES
(2,1,'ff435c87e6488009a8455830caa4201a015879417cc8ff1de7025f7b33fb765c','$2y$10$to/GR1O/6J98g1PRCSsZDOkmV2f5ds2.6I0NNdISYVoZ9He0NAA6O','2026-04-23 14:14:34','172.19.0.1',0,1,1,'2026-04-23 14:09:34',NULL),
(3,1,'0459f3a0a75fe29f4fa153e50ae9ea30c4ae5fe44f24ba91e49d1faae6eaf365','$2y$10$qKXEYX/UrDiLQTEuQotP/uyyX7lfUkI4f8nj7q5n7mkkDPLA61O8K','2026-07-23 13:58:27','172.18.0.1',0,1,0,'2026-07-23 13:53:27',NULL),
(4,1,'78e6e9f3d2b696fa274de5f329c077cefce68cb7beae1466e2b28bdcffd7e20b','$2y$10$LeJo8.3KF6tdJG/Fz.SPE./VdYyHWJBHb1OUU55mAv1QBDyC0PwIi','2026-07-23 14:03:59','172.18.0.1',0,1,0,'2026-07-23 13:58:59',NULL);
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
  `presentation` text DEFAULT NULL,
  `latlong` varchar(100) DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateconnexion` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_organization_organization_user` (`IDorganization`,`IDuser`),
  CONSTRAINT `fk_user_organization_org` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_organization`
--

LOCK TABLES `user_organization` WRITE;
/*!40000 ALTER TABLE `user_organization` DISABLE KEYS */;
INSERT INTO `user_organization` VALUES
(1,1,1,NULL,NULL,'admin@omo.test',NULL,NULL,'{\"isAdmin\":true}','2026-04-21 12:20:00','2026-07-28 12:03:00',1),
(2,1,2,'Admin',NULL,'admin@omo.test',NULL,NULL,'{\"isAdmin\":true}','2026-04-21 12:25:00',NULL,1);
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
INSERT INTO `user_patreon` VALUES
(1,1,'docker-local-access-token','docker-local-refresh-token','2027-07-23 11:51:20','identity identity[email] campaigns.members','Bearer','docker-local-user-1','docker-local-member-1','docker-local-campaign-1','Open Organization Admin','admin@omo.test',NULL,NULL,NULL,'active_patron','Paid',NULL,NULL,500,500,'[\"Local Dev\"]',1,'2026-07-23 11:51:20','2026-07-23 11:51:20','ok',NULL,'2026-07-23 11:51:20','2026-07-23 11:51:20');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_remember`
--

LOCK TABLES `user_remember` WRITE;
/*!40000 ALTER TABLE `user_remember` DISABLE KEYS */;
INSERT INTO `user_remember` VALUES
(1,1,'9e7e62a214f4927ce26226cf9d8576c9d7467861a03a217507ad5f8c1cf7dd7c','2026-05-23 13:59:14','172.19.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','Chrome','Windows','2026-04-23 11:59:14'),
(2,1,'aaa1f5df7bd344491268df922a68381ec463e7be9153450dc3a6f421b09cc2d3','2026-05-23 14:09:43','172.19.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','Chrome','Windows','2026-04-23 12:09:43');
/*!40000 ALTER TABLE `user_remember` ENABLE KEYS */;
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
  CONSTRAINT `fk_telegram_destination_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_telegram_destination_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE SET NULL,
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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-07-28 10:10:25
