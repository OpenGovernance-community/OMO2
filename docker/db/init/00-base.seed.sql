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
(4,'Checklist','checklist','checklist','images/tools/checklist.png','drawer_checklist','api/checklist/index.php','drawer',40,1,1),
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
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_authority_holon` (`IDholon`),
  KEY `idx_authority_parent` (`IDauthority_parent`),
  KEY `idx_authority_label` (`label`),
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
(1,1,1,NULL,'2026-07-27 00:00:00','waiting','2026-07-24 09:33:08','2026-07-24 09:33:08',NULL,NULL),
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
  KEY `idx_decision_proposal_active` (`active`),
  CONSTRAINT `fk_decision_proposal_group` FOREIGN KEY (`IDdecision_group`) REFERENCES `decision_group` (`id`) ON DELETE CASCADE,
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
  `storedfilepath` varchar(1000) DEFAULT NULL,
  `storedfilename` varchar(255) DEFAULT NULL,
  `storedfilemime` varchar(255) DEFAULT NULL,
  `storedfilesize` int(11) DEFAULT NULL,
  `IDdocument_parent` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL,
  `dateedition` datetime DEFAULT NULL,
  `IDuseredition` int(11) DEFAULT NULL,
  `IDuser_pv_editor` int(11) DEFAULT NULL,
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
  CONSTRAINT `fk_document_event` FOREIGN KEY (`IDevent`) REFERENCES `event` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_organization` FOREIGN KEY (`IDorganization`) REFERENCES `organization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_parent` FOREIGN KEY (`IDdocument_parent`) REFERENCES `document` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_pv_editor` FOREIGN KEY (`IDuser_pv_editor`) REFERENCES `user` (`id`) ON DELETE SET NULL,
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
INSERT INTO `document` VALUES
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
INSERT INTO `document_pv_point` VALUES
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
  KEY `idx_faq_reliability` (`reliability`),
  KEY `idx_faq_reliability_updated_at` (`reliability_updated_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3221 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faq`
--

LOCK TABLES `faq` WRITE;
/*!40000 ALTER TABLE `faq` DISABLE KEYS */;
INSERT INTO `faq` VALUES
(1,NULL,NULL,NULL,'Ma première question','Réponse de ma première question',NULL,NULL,'Détail de la réponse de la première question',0,0,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-04-12 08:08:05','2026-07-23 11:51:09'),
(2,NULL,NULL,NULL,'Ma deuxième question','Réponse de ma deuxième question',NULL,NULL,'Détail de la réponse de la deuxième question',0,0,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-04-12 08:08:05','2026-07-23 11:51:09'),
(3201,NULL,NULL,NULL,'Comment ouvrir les outils d aide dans OMO ?','Ouvrez le bouton Aide dans la topbar pour retrouver la FAQ, les tutoriels et la visite guidee.',NULL,NULL,'<p>La zone Aide centralise les ressources utiles quand vous avez un doute ou que vous decouvrez un ecran.</p><p>La FAQ donne des reponses rapides, les tutoriels vont plus loin et la visite guidee explique les boutons visibles sur la page en cours.</p>',10,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 09:00:00','2026-06-22 09:00:00'),
(3202,NULL,NULL,NULL,'Comment utiliser la recherche de la topbar ?','Tapez quelques mots cles dans la recherche puis ouvrez le resultat qui correspond a votre besoin.',NULL,NULL,'<p>La recherche de la topbar sert a retrouver rapidement un cercle, un role, un outil ou un acces utile.</p><p>Si plusieurs modules sont proposes, commencez par ceux qui correspondent a votre besoin puis affinez avec des mots simples et precis.</p>',20,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 09:05:00','2026-06-22 09:05:00'),
(3203,NULL,NULL,NULL,'Comment changer ma langue ou mon theme ?','Ouvrez le menu Profil dans la topbar pour regler la langue et le theme d affichage.',NULL,NULL,'<p>Le menu Profil permet de retrouver les reglages personnels les plus utiles sans quitter votre espace de travail.</p><p>Vous pouvez y adapter la langue de l interface et choisir le theme qui vous convient le mieux pour votre usage quotidien.</p>',30,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 09:10:00','2026-06-22 09:10:00'),
(3204,NULL,NULL,NULL,'A quoi sert le switch Contextuel / Global ?','Il permet de limiter la vue au contexte courant ou d elargir la liste a toute l organisation.',NULL,NULL,'<p>Le mode contextuel est pratique quand vous travaillez dans un cercle ou un role precis et que vous voulez rester centre sur ce perimetre.</p><p>Le mode global sert plutot a retrouver un element dans toute l organisation, meme en dehors du contexte actuellement ouvert.</p>',40,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 09:15:00','2026-06-22 09:15:00'),
(3205,NULL,NULL,NULL,'Comment passer du tri Date au tri Alphabetique ?','Utilisez le controle de tri dans l entete du drawer pour choisir l ordre qui vous aide le plus.',NULL,NULL,'<p>Le tri par date est utile pour revoir ce qui vient d etre cree ou modifie recemment.</p><p>Le tri alphabetique est souvent plus confortable quand vous cherchez un nom connu dans une longue liste.</p>',50,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 09:20:00','2026-06-22 09:20:00'),
(3206,NULL,NULL,NULL,'A quoi sert le mode Detail / Compact ?','Le mode Detail montre plus d informations par carte, tandis que Compact affiche plus d elements a l ecran.',NULL,NULL,'<p>Choisissez Detail quand vous voulez lire les resumes, les metadonnees ou mieux comparer plusieurs cartes.</p><p>Choisissez Compact quand vous voulez parcourir beaucoup d elements rapidement, en particulier sur mobile ou dans une colonne etroite.</p>',60,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 09:25:00','2026-06-22 09:25:00'),
(3207,NULL,NULL,NULL,'Comment creer un document dans OMO ?','Ouvrez l app Documents puis utilisez le bouton Ajouter si votre role vous y autorise.',NULL,NULL,'<p>Sur grand ecran, le bouton de creation apparait dans l entete du module. Sur mobile, il peut etre reduit a une icone en haut a droite.</p><p>Si vous ne voyez pas ce bouton, cela signifie en general que votre contexte actuel ou vos droits ne permettent pas cette creation.</p>',70,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 09:30:00','2026-06-22 09:30:00'),
(3208,NULL,NULL,NULL,'Comment modifier un document existant ?','Ouvrez le document puis lancez l action Editer depuis le drawer ou le menu prevu.',NULL,NULL,'<p>L edition passe par le formulaire du document et enregistre les changements dans le contexte de ce document.</p><p>Si un document appartient deja a une organisation ou a un holon, les droits de ce contexte continuent a s appliquer au moment de la sauvegarde.</p>',80,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 09:35:00','2026-06-22 09:35:00'),
(3209,NULL,NULL,NULL,'A quoi sert l app Memo ?','Memo rassemble vos documents personnels et vos notes dans une vue simple a parcourir.',NULL,NULL,'<p>La liste Memo peut regrouper les documents dont vous etes l auteur, y compris quand ils proviennent de plusieurs holons.</p><p>Le detail se consulte ensuite dans un drawer interne, ce qui permet de rester dans le meme espace sans ouvrir une nouvelle page.</p>',90,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 09:40:00','2026-06-22 09:40:00'),
(3210,NULL,NULL,NULL,'Comment reediter un memo depuis l app Memo ?','Ouvrez le menu ... sur un memo puis choisissez Editer.',NULL,NULL,'<p>L action Editer ouvre le formulaire du memo dans un drawer, avec un parcours proche de celui du module Documents.</p><p>Les memos sans contexte d organisation peuvent etre reedites par leur auteur, alors que les documents deja classes gardent les droits de leur contexte habituel.</p>',100,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 09:45:00','2026-06-22 09:45:00'),
(3211,NULL,NULL,NULL,'Comment terminer ou classer un memo depuis Telegram ?','Utilisez les boutons proposes par le bot pour choisir une destination autorisee ou terminer dans le contexte courant.',NULL,NULL,'<p>Le bot ne propose que les destinations de classement qui restent autorisees pour votre contexte et vos droits.</p><p>Si le bouton Terminer ici ou certaines destinations ne sont pas visibles, cela signifie simplement que cette action nest pas disponible pour vous a cet endroit.</p>',110,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 09:50:00','2026-06-22 09:50:00'),
(3212,NULL,NULL,NULL,'Comment creer une prise de decision ?','Ouvrez l app Decisions puis utilisez le bouton de creation disponible dans l entete si vous avez le droit necessaire.',NULL,NULL,'<p>La creation se fait dans le contexte courant, par exemple pour une organisation, un cercle ou un autre niveau de structure.</p><p>Prenez le temps de definir un titre clair, une description utile et les dates importantes avant de lancer la participation.</p>',120,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 09:55:00','2026-06-22 09:55:00'),
(3213,NULL,NULL,NULL,'Comment participer a une decision avec un lien public ou un acces personnel ?','Ouvrez la page de participation recue par lien ou demandez votre acces personnel depuis l ecran public du scrutin.',NULL,NULL,'<p>Certaines decisions peuvent accepter la participation sans invitation classique, directement depuis un lien public partage par l organisateur.</p><p>Si ce nest pas le cas, utilisez la page Recevoir mon acces personnel pour demander un lien individuel avant de voter.</p>',130,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 10:00:00','2026-06-22 10:00:00'),
(3214,NULL,NULL,NULL,'Comment noter des propositions en jugement majoritaire ?','Attribuez une mention a chaque proposition selon l echelle affichee, de la plus favorable a la moins favorable.',NULL,NULL,'<p>Le jugement majoritaire ne consiste pas a choisir une seule proposition. Vous evaluez chaque option avec la meme echelle.</p><p>Le resultat final compare ensuite la repartition des mentions pour aider a faire ressortir la proposition la plus solide.</p>',140,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 10:05:00','2026-06-22 10:05:00'),
(3215,NULL,NULL,NULL,'Comment ajouter un evenement dans le calendrier ?','Ouvrez le calendrier puis utilisez le bouton Ajouter si votre contexte vous autorise a creer des dates.',NULL,NULL,'<p>Comme pour les autres modules, le bouton peut etre plein texte sur grand ecran ou reduit a une icone sur mobile.</p><p>Si vous ne pouvez pas creer de date a cet endroit, changez de contexte ou demandez a une personne administratrice de verifier vos droits.</p>',150,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 10:10:00','2026-06-22 10:10:00'),
(3216,NULL,NULL,NULL,'Comment changer de vue dans le calendrier ?','Utilisez le selecteur Mois, Semaine, Jour ou Liste pour choisir la lecture la plus pratique.',NULL,NULL,'<p>Chaque vue repond a un besoin different: Mois pour la planification generale, Semaine ou Jour pour le detail, Liste pour un balayage rapide.</p><p>Sur mobile, ces vues peuvent apparaitre sous forme d icones plus compactes afin de laisser davantage de place au contenu.</p>',160,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 10:15:00','2026-06-22 10:15:00'),
(3217,NULL,NULL,NULL,'Pourquoi je ne vois pas toujours le bouton Ajouter ?','Le bouton apparait seulement si vous avez la permission de creation dans le contexte ouvert.',NULL,NULL,'<p>Ce principe vaut notamment pour les documents, les prises de decision, les dates et la creation de FAQ.</p><p>Si vous pensez que ce bouton devrait etre disponible, verifiez le contexte courant ou demandez une verification des permissions sur le holon concerne.</p>',170,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 10:20:00','2026-06-22 10:20:00'),
(3218,NULL,NULL,NULL,'Comment ajouter une question dans la FAQ ?','Ouvrez la FAQ du contexte voulu puis utilisez le bouton Ajouter une question si cette action est disponible.',NULL,NULL,'<p>Selon votre ecran, la nouvelle question peut etre creee au niveau du contexte courant, du niveau organisation ou dans un scope plus global.</p><p>Si aucun bouton de creation ne saffiche, cela signifie que la permission de creation de FAQ nest pas accordee dans ce contexte.</p>',180,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 10:25:00','2026-06-22 10:25:00'),
(3219,NULL,NULL,NULL,'A quoi servent les votes sur les reponses de la FAQ ?','Les boutons de vote permettent de signaler si une reponse est utile afin de mieux mettre en avant les bonnes explications.',NULL,NULL,'<p>Quand une reponse vous aide vraiment, un vote positif aide a la faire remonter dans la FAQ.</p><p>Ces retours servent a rendre les questions les plus utiles plus visibles pour les autres membres de l organisation.</p>',190,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 10:30:00','2026-06-22 10:30:00'),
(3220,NULL,NULL,NULL,'Comment faire apparaitre mon organisation sur la carte publique ?','Renseignez un emplacement dans les parametres de l organisation et verifiez que les informations utiles sont lisibles sans connexion.',NULL,NULL,'<p>La carte publique utilise un emplacement facultatif, generalement saisi en latitude et longitude dans les parametres de l organisation.</p><p>Seules les informations explicitement exposees comme publiques sont reprises sur cette carte, ce qui permet de garder le controle sur ce qui est visible sans connexion.</p>',200,1,0,0,0,0,0,'2026-07-24 09:42:35','2026-07-24 09:42:35','2026-06-22 10:35:00','2026-06-22 10:35:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(32,2,1,712,'holon_updated','Modification de [holon|713|Facilitation (rôle)] :\n- la couleur a ete modifiee.','{\"IDholon\":713,\"before\":{\"holon\":{\"id\":713,\"name\":\"Facilitation\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":710,\"inheritsFromName\":\"Rôle\",\"color\":\"\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":false},\"properties\":{\"48\":{\"id\":48,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"fdsfsdfdsfds\",\"inheritedValue\":\"\",\"visibleValue\":\"fdsfsdfdsfds\",\"visibleItems\":[]},\"51\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"49\":{\"id\":49,\"name\":\"Domaines d\'autorité\",\"shortname\":\"domaines_d_autorite\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Domaine du rôle facilitation\",\"inheritedValue\":\"\",\"visibleValue\":\"Domaine du rôle facilitation\",\"visibleItems\":[]},\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.\",\"inheritedValue\":\"\",\"visibleValue\":\"Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.\",\"visibleItems\":[]}},\"permissions\":[]},\"after\":{\"holon\":{\"id\":713,\"name\":\"Facilitation\",\"fullName\":\"\",\"typeId\":1,\"typeLabel\":\"Role\",\"parentId\":712,\"templateId\":710,\"inheritsFromName\":\"Rôle\",\"color\":\"#f59f0a\",\"icon\":\"\",\"banner\":\"\",\"visible\":true,\"mandatory\":true,\"lockedName\":true,\"lockedIcon\":false,\"lockedBanner\":false,\"unique\":true,\"link\":false},\"properties\":{\"48\":{\"id\":48,\"name\":\"Attendus\",\"shortname\":\"attendus\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"fdsfsdfdsfds\",\"inheritedValue\":\"\",\"visibleValue\":\"fdsfsdfdsfds\",\"visibleItems\":[]},\"49\":{\"id\":49,\"name\":\"Domaines d\'autorité\",\"shortname\":\"domaines_d_autorite\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Domaine du rôle facilitation\",\"inheritedValue\":\"\",\"visibleValue\":\"Domaine du rôle facilitation\",\"visibleItems\":[]},\"51\":{\"id\":51,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"\",\"inheritedValue\":\"\",\"visibleValue\":\"\",\"visibleItems\":[]},\"47\":{\"id\":47,\"name\":\"Raison d\'être\",\"shortname\":\"raison_d_etre\",\"formatId\":1,\"formatName\":\"Texte libre\",\"listItemType\":\"\",\"localValue\":\"Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.\",\"inheritedValue\":\"\",\"visibleValue\":\"Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.\",\"visibleItems\":[]}},\"permissions\":[]},\"changes\":[{\"type\":\"field_changed\",\"field\":\"color\",\"before\":\"\",\"after\":\"#f59f0a\"}]}','2026-07-24 15:07:15',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=723 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holon`
--

LOCK TABLES `holon` WRITE;
/*!40000 ALTER TABLE `holon` DISABLE KEYS */;
INSERT INTO `holon` VALUES
(1,NULL,'OpenMyOrganization',NULL,NULL,NULL,NULL,NULL,1,'2024-11-30 09:50:26',NULL,1,1,0,0,0,0,0,0,'Organisation basique',4,NULL,NULL,NULL),
(2,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2024-11-30 09:50:26',NULL,1,0,0,0,0,0,0,0,'Rôle',1,1,NULL,NULL),
(3,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2024-11-30 09:51:41',NULL,1,0,0,0,0,0,0,0,'Cercle',2,1,NULL,NULL),
(4,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2024-11-30 09:53:13',NULL,1,0,0,0,0,0,0,0,'Groupe',3,1,NULL,NULL),
(674,1,'OpenMyOrganization',NULL,'#005c8a',NULL,NULL,NULL,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,4,NULL,NULL,NULL),
(675,NULL,'Role',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,0,0,0,0,0,0,0,'Role',1,674,NULL,NULL),
(676,NULL,'Cercle',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,0,0,0,0,0,0,0,'Cercle',2,674,NULL,NULL),
(677,NULL,NULL,NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,0,0,0,0,0,0,0,'Groupe',3,674,NULL,NULL),
(678,NULL,'Ancrage',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,2,674,676,NULL),
(679,NULL,'CA',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,2,674,676,NULL),
(680,NULL,'Lien pilotage',NULL,'#f55c0a',NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,1,1,0,0,1,1,'Lien pilotage',1,678,675,NULL),
(681,NULL,'Facilitation',NULL,'#f5870a',NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,1,1,0,0,1,0,'Facilitation',1,678,675,NULL),
(682,NULL,'Memoire',NULL,'#f59e0b',NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,1,1,0,0,1,0,'Memoire',1,678,675,NULL),
(683,NULL,'Operations',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,1,678,675,NULL),
(684,NULL,'President',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,1,679,675,NULL),
(685,NULL,'Tresorier',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,1,679,675,NULL),
(686,NULL,'Administration',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,2,678,676,NULL),
(687,NULL,'Marketing',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,2,678,676,NULL),
(691,NULL,'Operations administration',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,1,686,683,NULL),
(692,NULL,'Gestion administrative',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,1,686,675,NULL),
(693,NULL,'Comptabilite et budget',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,1,686,675,NULL),
(694,NULL,'Support interne',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,1,686,675,NULL),
(698,NULL,'Operations marketing',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,1,687,683,NULL),
(699,NULL,'Communication digitale',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,1,687,675,NULL),
(700,NULL,'Partenariats et visibilite',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,1,687,675,NULL),
(701,NULL,'Contenus et campagnes',NULL,NULL,NULL,NULL,674,1,'2026-04-19 16:46:34',NULL,1,1,0,0,0,0,0,0,NULL,1,687,675,NULL),
(702,NULL,'Facilitation',NULL,NULL,NULL,NULL,674,1,'2026-07-23 13:47:11',NULL,1,1,0,0,0,0,0,0,NULL,1,686,681,NULL),
(703,NULL,'Memoire',NULL,NULL,NULL,NULL,674,1,'2026-07-23 13:47:19',NULL,1,1,0,0,0,0,0,0,NULL,1,686,682,NULL),
(704,NULL,'Lien pilotage',NULL,NULL,NULL,NULL,674,1,'2026-07-23 13:47:27',NULL,1,1,0,0,0,0,0,0,NULL,1,686,680,NULL),
(705,NULL,'Memoire',NULL,NULL,NULL,NULL,674,1,'2026-07-23 13:47:37',NULL,1,1,0,0,0,0,0,0,NULL,1,687,682,NULL),
(706,NULL,'Facilitation',NULL,NULL,NULL,NULL,674,1,'2026-07-23 13:47:45',NULL,1,1,0,0,0,0,0,0,NULL,1,687,681,NULL),
(707,NULL,'Lien pilotage',NULL,NULL,NULL,NULL,674,1,'2026-07-23 13:47:54',NULL,1,1,0,0,0,0,0,0,NULL,1,687,680,NULL),
(708,NULL,'Inclusion','Inclusion',NULL,NULL,NULL,674,1,'2026-07-23 13:51:27',NULL,1,1,0,0,0,0,0,0,NULL,1,678,675,NULL),
(709,2,'Exemple de modèle',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,0,0,0,0,0,0,'Modèle de base',4,NULL,NULL,NULL),
(710,NULL,'Rôle',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,0,0,0,0,0,0,0,'Rôle',1,709,NULL,NULL),
(711,NULL,'Cercle',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,0,0,0,0,0,0,0,'Cercle',2,709,NULL,NULL),
(712,NULL,'Ancrage',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,0,0,0,0,0,0,NULL,2,709,711,NULL),
(713,NULL,'Facilitation',NULL,'#f59f0a',NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,1,1,0,0,1,0,'Facilitation',1,712,710,NULL),
(714,NULL,'Lien pilotage',NULL,'#f52d0a',NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,1,1,0,0,1,1,'Lien pilotage',1,712,710,NULL),
(715,NULL,'Mémoire',NULL,'#f5740a',NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,1,1,0,0,1,0,'Mémoire',1,712,710,NULL),
(716,NULL,'Opérations',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,0,0,0,0,0,0,NULL,1,712,NULL,NULL),
(719,NULL,'CA',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,0,0,0,0,0,0,NULL,2,709,NULL,NULL),
(720,NULL,'Président',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,0,0,0,0,0,0,NULL,1,719,NULL,NULL),
(721,NULL,'Trésorier',NULL,NULL,NULL,NULL,709,1,'2026-07-24 14:37:17',NULL,1,1,0,0,0,0,0,0,NULL,1,719,NULL,NULL);
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
  `range` varchar(40) NOT NULL DEFAULT 'self',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_holon_permission_range` (`IDholon`,`IDpermission`,`range`),
  KEY `idx_holon_permission_permission` (`IDpermission`),
  KEY `idx_holon_permission_range` (`range`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=235 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(142,680,45,'[\"Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.\"]',2,'2026-07-23 15:44:59',1,0,0,1),
(143,681,43,'Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.',1,NULL,NULL,0,0,1),
(144,681,44,'[\"Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.\"]',3,'2026-07-23 15:46:11',1,0,0,1),
(145,681,45,'[\"Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.\"]',2,'2026-07-23 15:46:11',1,0,0,1),
(146,682,43,'Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.',1,NULL,NULL,0,0,1),
(147,682,44,'[\"Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.\"]',3,'2026-07-23 15:46:25',1,0,0,1),
(148,682,45,'[\"Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.\"]',2,'2026-07-23 15:46:25',1,0,0,1),
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
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization`
--

LOCK TABLES `organization` WRITE;
/*!40000 ALTER TABLE `organization` DISABLE KEYS */;
INSERT INTO `organization` VALUES
(1,'OpenMyOrganization','org1','org1.opengov.tools','/img/org1-logo.png','/img/org1-banner.png','#0f766e','46.204391;6.143158',NULL,'2026-04-01 00:00:00'),
(2,'Exemple de modèle','org2','org2.opengov.tools','/img/org2-logo.png','/img/org2-banner.png','#1d4ed8','46.519653;6.632273',NULL,'2026-04-01 00:00:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(4,2,2,20,1,NULL),
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(11,'CAN_ADD_APP','Gerer les applications','Autorise la gestion des applications actives et de leur ordre dans l organisation.',0,'2026-07-23 11:51:22','2026-07-23 11:51:22');
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(24,1,708,NULL,23,NULL,'standard',6,'Un dîner est organisé avec le nouvel arrivant',NULL,'ready','2026-08-01','2026-08-04',3,4,0.75000000,'S','multiple_documents',1,'2026-07-24 09:33:08','2026-07-24 12:22:08');
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
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Propriétés assignées à des tempales (holons)';
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
(45,'domaines_d_autorite','Domaines d\'autorite',2,'text',NULL,674,'2026-04-19 16:46:34',2,1),
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
  PRIMARY KEY (`id`),
  KEY `idx_rule_authority` (`IDauthority`),
  KEY `idx_rule_holon` (`IDholon`),
  KEY `idx_rule_scope` (`scope`),
  KEY `idx_rule_review` (`review_date`),
  KEY `idx_rule_expiration` (`expiration_date`),
  CONSTRAINT `fk_rule_authority` FOREIGN KEY (`IDauthority`) REFERENCES `authority` (`id`),
  CONSTRAINT `fk_rule_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE,
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
('2026-07-24-02-stat-indicator-group-chart-lower-value.sql','08dfef1e03de0432d8a418e8ae09149a0d4d4d5f79a62522ee0ddccffad36771','2026-07-24 17:40:18');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stat_indicator`
--

LOCK TABLES `stat_indicator` WRITE;
/*!40000 ALTER TABLE `stat_indicator` DISABLE KEYS */;
INSERT INTO `stat_indicator` VALUES
(1,1,693,1,'Solde du compte bancaire','Cash disponible sur le compte bancaire','https://bas.ch','none','monthly','1',60000.000000,1,'2026-07-23 16:11:14','2026-07-24 08:45:24'),
(2,1,693,1,'Solde en caisse','Montant disponible en liquide dans la caisse',NULL,'none','monthly','1',0.000000,1,'2026-07-23 16:15:03','2026-07-24 08:44:54');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(7,1,1,82691.000000,'2026-05-01 16:19:00','2026-07-23 16:19:58','2026-07-23 16:19:58');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation_bundles`
--

LOCK TABLES `translation_bundles` WRITE;
/*!40000 ALTER TABLE `translation_bundles` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES
(1,'admin@omo.test',NULL,NULL,NULL,NULL,NULL,'Admin',NULL,'$2y$10$ES6a68iJbT4z8MxzjNBMoOEtBAn7HJCEqdUnTdBNXQGSerKh.ZQC6','2026-04-21 09:01:00','2026-07-24 18:09:07',1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
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
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateconnexion` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_holon_holon_user` (`IDholon`,`IDuser`),
  CONSTRAINT `fk_user_holon_holon` FOREIGN KEY (`IDholon`) REFERENCES `holon` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_holon`
--

LOCK TABLES `user_holon` WRITE;
/*!40000 ALTER TABLE `user_holon` DISABLE KEYS */;
INSERT INTO `user_holon` VALUES
(1,1,1,NULL,'2024-03-05 16:43:15',NULL,1),
(2,1,683,'[]','2026-07-23 13:38:59',NULL,0),
(3,1,693,NULL,'2026-07-23 13:48:17',NULL,1),
(4,1,692,NULL,'2026-07-23 13:49:46',NULL,1),
(5,1,682,NULL,'2026-07-23 13:50:25',NULL,1),
(6,1,708,NULL,'2026-07-23 13:51:33',NULL,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_organization`
--

LOCK TABLES `user_organization` WRITE;
/*!40000 ALTER TABLE `user_organization` DISABLE KEYS */;
INSERT INTO `user_organization` VALUES
(1,1,1,NULL,NULL,'admin@omo.test',NULL,NULL,'{\"isAdmin\":true}','2026-04-21 12:20:00','2026-07-23 13:59:25',1),
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
-- Dumping events for database 'omodev'
--

--
-- Dumping routines for database 'omodev'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-07-24 17:40:31
