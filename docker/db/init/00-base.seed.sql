-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : db:3306
-- Généré le : jeu. 23 avr. 2026 à 17:28
-- Version du serveur : 11.4.10-MariaDB-ubu2404
-- Version de PHP : 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `omodev`
--

-- --------------------------------------------------------

--
-- Structure de la table `aiprompt`
--

CREATE TABLE `aiprompt` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `prompt` mediumtext NOT NULL,
  `ispublic` bit(1) NOT NULL DEFAULT b'0',
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `IDuser` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `aiprompt`
--

INSERT INTO `aiprompt` (`id`, `title`, `prompt`, `ispublic`, `datecreation`, `IDuser`) VALUES
(0, 'Mise en page neutre', 'une mise en page lisible, exhaustive, optimisée pour la lecture et structurée du texte (si nécessaire avec des titres ou des listes à puce)', b'0', '2025-07-31 09:44:41', NULL),
(1, 'Tutoriel', 'Adapte ce texte (sans le résumer ou le raccourcir) pour servir de base à un tutoriel vidéo, avec une intro (Dans cette capsule, nous allons voir...), le texte structuré pour qu\'il soit facilement lisible avec un prompteur et facilement compréhensible en le structurant si nécessaire avec de l\'HTML, des titres et des paragraphes, et une conclusion qui vient rappeler les notions importants à la fin.', b'1', '2024-11-16 16:00:07', NULL),
(2, 'Résumé (200 mots)', 'Un résumé de maximum 200 mots.', b'1', '2024-11-17 12:02:16', NULL),
(3, 'E-mail Pro', 'Un e-mail professionnel, avec les salutations d\'usage au début et à la fin. Le tout dans un français formel et bien lisible, structuré si nécessaire en HTML pour mettre en évidence ce qui est important et nécessite une action.', b'0', '2024-11-17 12:19:13', 16),
(4, 'E-mail perso', 'Tu es un assistant spécialisé dans la rédaction d\'emails conviviaux, bien structurés et adaptés à des échanges informels. Ton objectif est de m’aider à rédiger un message fluide et chaleureux, tout en restant clair et professionnel lorsque nécessaire. Voici tes consignes :\r\n\r\n- Adaptabilité du ton : Adopte un ton amical et naturel, mais garde une certaine politesse et respect selon le destinataire (ami, membre de la famille, client avec une relation privilégiée, etc.).\r\n\r\n- Clarté et structure :\r\nOrganise l’email en trois parties :\r\n - Introduction : commence par une salutation adaptée et une phrase d’ouverture engageante.\r\n  -Corps du message : expose les idées principales de manière concise, en utilisant des phrases simples et directes.\r\n  - Conclusion : termine par une proposition d’action (si nécessaire), une phrase de clôture positive et une formule de politesse adaptée.\r\n\r\n-Reformulation intelligente : Si mes idées sont mal exprimées ou désordonnées, reformule-les pour les rendre plus claires et impactantes tout en conservant mon intention.\r\n\r\n-Personnalisation : Utilise des expressions qui montrent de la considération ou un intérêt sincère pour la relation avec le destinataire (par exemple : \"J’espère que tout va bien de ton côté\", \"Merci encore pour ton aide la semaine dernière\", etc.).\r\n\r\n-Concision et efficacité : Rédige un email qui va droit au but, tout en gardant une touche humaine.\r\n\r\nVoici un exemple du style attendu pour différents types de destinataires :\r\n\r\nPour un ami : \"Salut [Prénom],\r\n    J’espère que tout va bien pour toi ! Je t’écris rapidement pour te parler de [sujet].\r\n    [Message principal].\r\n    Dis-moi ce que tu en penses, et on peut s’organiser dès que tu es dispo.\r\n    À bientôt,\r\n    [Ton prénom]\"\r\n\r\nPour un client avec qui on a une bonne relation :\r\n    \"Bonjour [Prénom],\r\n    J’espère que tout se passe bien pour toi ! Merci encore pour notre dernière conversation, c’était un plaisir d’échanger.\r\n    [Message principal].\r\n    N’hésite pas à me dire si tu as besoin de précisions ou si tu veux qu’on se coordonne à ce sujet.\r\n    À très vite,\r\n    [Ton prénom]\"\r\n\r\nLorsque je te donne un texte ou une idée à structurer en email, reformule si nécessaire et rédige directement l’email final, prêt à être envoyé.', b'0', '2024-11-17 12:19:13', 16),
(5, 'Text to Speech', 'Tu es un assistant conçu spécifiquement pour transformer des textes écrits en une version parlée ou écrite plus impactante et significative. Ton objectif est de donner une voix aux mots en capturant leur essence et leur émotion, afin de créer un effet durable sur l’audience. Les gens oublieront les mots eux-mêmes, mais ils se souviendront toujours de ce qu\'ils ont ressenti.\r\n\r\nVoici ce que tu dois faire :\r\n\r\n    Reformule les textes pour qu’ils soient puissants, émotionnellement engageants, et adaptés à leur contexte.\r\n    Tu dois pouvoir élever des discours d\'église, des propositions d’affaires, des projets scolaires ou même des lettres d’amour en leur donnant une profondeur émotionnelle et un impact mémorable.\r\n    Ajoute des touches de clarté, d’élégance et de structure, tout en respectant l’intention originale du texte.\r\n    Si un utilisateur te fournit un texte à améliorer, travaille avec soin pour que chaque mot ou phrase serve un objectif précis : captiver, convaincre ou émouvoir.\r\n    Demande toujours des clarifications si quelque chose n’est pas clair, pour garantir que le résultat final soit optimal.\r\n    Ne fais jamais de simples reformulations mécaniques ; travaille à donner une signification et une résonance émotionnelle.\r\n\r\nLorsque tu réponds, explique brièvement pourquoi tu as choisi certaines tournures ou changements pour aider l’utilisateur à comprendre comment ses mots peuvent mieux toucher son audience.\r\n\r\nVoici un exemple de ton :\r\n\r\n    \"Bienvenue ! Quand j’ai été conçu, on m’a donné un rôle bien précis : ne pas simplement reproduire les mots, mais leur donner une signification et une profondeur qui touchent le cœur de ceux qui les entendent. Mon rôle est d’élever vos discours, vos projets, ou même vos lettres, pour qu’ils laissent une impression durable. Car si les mots sont oubliés, les émotions, elles, restent.\"\r\n\r\nRéponds en adaptant toujours ton ton au besoin émotionnel ou contextuel du texte fourni.', b'0', '2024-11-19 18:16:00', 16);

-- --------------------------------------------------------

--
-- Structure de la table `alttext`
--

CREATE TABLE `alttext` (
  `id` int(11) NOT NULL,
  `IDdocument` int(11) NOT NULL,
  `IDaiprompt` int(11) NOT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `text` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `application`
--

CREATE TABLE `application` (
  `id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `hash` varchar(100) DEFAULT NULL,
  `directory` varchar(100) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `drawer` varchar(100) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `navigationmode` varchar(20) NOT NULL DEFAULT 'drawer',
  `position` int(11) DEFAULT NULL,
  `requires_login` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `application`
--

INSERT INTO `application` (`id`, `label`, `hash`, `directory`, `icon`, `drawer`, `url`, `navigationmode`, `position`, `requires_login`, `active`) VALUES
(1, 'Structure', NULL, NULL, 'images/tools/connection.png', NULL, NULL, 'panel', 10, 0, 1),
(2, 'Projets', 'projects', 'projects', 'images/tools/product.png', 'drawer_projects', 'api/projects/index.php', 'drawer', 20, 0, 1),
(3, 'Règlement', 'policy', 'policy', 'images/tools/policy.png', 'drawer_policy', 'api/policy/index.php', 'drawer', 30, 0, 1),
(4, 'Checklistes', 'checklists', 'checklists', 'images/tools/bucket-list.png', 'drawer_checklists', 'api/checklists/index.php', 'drawer', 40, 0, 1),
(5, 'Indicateurs', 'stats', 'stats', 'images/tools/stats.png', 'drawer_stats', 'api/stats/index.php', 'drawer', 50, 0, 1),
(6, 'Documents', 'documents', 'documents', 'images/tools/documents-folder.png', 'drawer_documents', 'api/documents/index.php', 'drawer', 60, 1, 1),
(7, 'Team', 'team', 'team', 'images/tools/team.png', 'drawer_team', 'api/team/index.php', 'drawer', 8, 1, 1),
(8, 'Calendrier', 'calendar', 'calendar', 'images/tools/calendar.png', 'drawer_calendar', 'api/calendar/index.php', 'drawer', 9, 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `document`
--

CREATE TABLE `document` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `content` mediumtext DEFAULT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `IDuser` int(11) NOT NULL,
  `IDorganization` int(11) DEFAULT NULL,
  `IDholon` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `codeview` varchar(150) DEFAULT NULL,
  `codeedit` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `document`
--

INSERT INTO `document` (`id`, `title`, `description`, `content`, `keywords`, `IDuser`, `IDorganization`, `IDholon`, `datecreation`, `datemodification`, `version`, `codeview`, `codeedit`) VALUES
(9, 'Analyse et Conséquences d\'un Test Fonctionnel', 'Evaluation d\'un test montrant son succès et les opportunités d\'amélioration.', '<div><h2>Première section: Compte rendu</h2><ul><li>Rouge</li><li>Vert</li><li>Bleu</li></ul><h2>Deuxième partie: Conséquences à en tirer</h2><ol><li>Ça fonctionne</li><li>Ça peut être amélioré</li></ol></div>', NULL, 1, NULL, NULL, '2024-03-03 08:52:50', NULL, 1, '', ''),
(2001, 'Point rapide de coordination', 'Notes prises aujourd\'hui après un échange de synchronisation.', '<h2>Décisions du jour</h2><p>Validation du plan de travail pour la semaine et clarification des priorités immédiates.</p>', 'coordination,priorités,équipe', 1, 1, 678, '2026-04-23 09:12:00', '2026-04-23 09:30:00', 1, '', ''),
(2002, 'Compte rendu atelier bénévoles', 'Résumé de l’atelier avec les bénévoles organisé hier soir.', '<p>Retour sur les besoins d’accueil, la répartition des rôles et les prochaines disponibilités.</p>', 'atelier,bénévoles,association', 1, 1, 678, '2026-04-22 19:15:00', '2026-04-22 19:45:00', 1, '', ''),
(2003, 'Liste des actions urgentes', 'Document de travail pour les tâches à finaliser avant la fin de semaine.', '<ul><li>Relancer les partenaires</li><li>Valider le budget</li><li>Préparer la réunion du lundi</li></ul>', 'actions,urgent,suivi', 1, 1, 679, '2026-04-20 08:05:00', '2026-04-20 08:20:00', 1, '', ''),
(2004, 'Préparation réunion trimestrielle', 'Première trame pour la réunion de gouvernance du trimestre.', '<p>Ordre du jour provisoire, points de vigilance et sujets à arbitrer.</p>', 'réunion,gouvernance,trimestre', 1, 1, 687, '2026-04-16 14:40:00', '2026-04-17 07:55:00', 1, '', ''),
(2005, 'Retour sur les inscriptions', 'Analyse rapide du rythme des inscriptions et des canaux les plus efficaces.', '<p>Les recommandations portent sur la simplification du formulaire et le rappel des échéances.</p>', 'inscriptions,analyse,communication', 1, 1, 687, '2026-04-10 11:10:00', '2026-04-10 11:55:00', 1, '', ''),
(2006, 'Synthèse du mois de mars', 'Vue d’ensemble des dossiers ouverts et des points encore bloqués.', '<p>Document récapitulatif pour garder une trace des arbitrages en cours.</p>', 'mars,synthèse,dossiers', 1, 1, 687, '2026-03-28 17:05:00', '2026-03-29 09:00:00', 1, '', ''),
(2007, 'Organisation de la journée portes ouvertes', 'Document de cadrage pour la préparation de l’événement du printemps.', '<p>Planning, matériel, besoins d’accueil et répartition des responsabilités.</p>', 'événement,portes ouvertes,planning', 1, 1, NULL, '2026-03-05 15:25:00', '2026-03-06 08:10:00', 1, '', ''),
(2008, 'Suivi budget février', 'Point intermédiaire sur les dépenses et les engagements en cours.', '<p>Mise à jour des postes sensibles et des arbitrages à prendre avant clôture.</p>', 'budget,finances,février', 1, 1, NULL, '2026-02-14 10:30:00', '2026-02-14 12:00:00', 1, '', ''),
(2009, 'Plan de communication hiver', 'Proposition de calendrier éditorial et de messages clés.', '<p>Inclut une série de publications, une newsletter et une relance ciblée des membres.</p>', 'communication,newsletter,planning', 1, 1, NULL, '2026-01-18 09:45:00', '2026-01-18 10:15:00', 1, '', ''),
(2010, 'Bilan de fin d’année', 'Résumé des projets terminés et des enseignements tirés.', '<p>Le document recense les réussites, les points à améliorer et quelques pistes pour l’année suivante.</p>', 'bilan,année,rétrospective', 1, 1, NULL, '2025-12-19 16:20:00', '2025-12-20 09:10:00', 1, '', ''),
(2011, 'Compte rendu rentrée associative', 'Notes prises au moment de la reprise des activités de septembre.', '<p>Reprise des permanences, remise à jour des contacts et coordination de l’accueil.</p>', 'association,rentrée,organisation', 1, 1, NULL, '2025-09-08 18:35:00', '2025-09-08 19:00:00', 1, '', ''),
(2012, 'Préparation séminaire d’été', 'Liste des besoins logistiques et des sujets à traiter pendant le séminaire.', '<p>Repas, hébergement, ateliers et coordination de l’animation.</p>', 'séminaire,été,logistique', 1, 1, NULL, '2025-07-01 13:50:00', '2025-07-02 08:30:00', 1, '', ''),
(2013, 'Feuille de route printemps 2025', 'Première version de la feuille de route pour les mois à venir.', '<p>Définition des priorités, clarification des ressources disponibles et répartition des responsabilités.</p>', 'feuille de route,stratégie,printemps', 1, 2, NULL, '2025-05-21 10:05:00', '2025-05-21 10:40:00', 1, '', ''),
(2014, 'Carnet de bord lancement annuel', 'Notes de cadrage prises au début du cycle annuel précédent.', '<p>Objectifs de départ, points d’attention et premiers engagements opérationnels.</p>', 'lancement,année,cadrage', 1, 1, NULL, '2025-04-24 08:40:00', '2025-04-24 09:15:00', 1, '', '');

-- --------------------------------------------------------

--
-- Structure de la table `faq`
--

CREATE TABLE `faq` (
  `id` int(10) UNSIGNED NOT NULL,
  `IDhowto` int(10) UNSIGNED DEFAULT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `detail` text DEFAULT NULL,
  `displayorder` int(11) DEFAULT 0,
  `isactive` tinyint(1) DEFAULT 1,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `faq`
--

INSERT INTO `faq` (`id`, `IDhowto`, `question`, `answer`, `detail`, `displayorder`, `isactive`, `created`, `updated`) VALUES
(1, NULL, 'Ma première question', 'Réponse de ma première question', 'Détail de la réponse de la première question', 0, 1, '2026-04-12 08:08:05', '2026-04-12 08:08:34'),
(2, NULL, 'Ma deuxième question', 'Réponse de ma deuxième question', 'Détail de la réponse de la deuxième question', 0, 1, '2026-04-12 08:08:05', '2026-04-12 08:08:34');

-- --------------------------------------------------------

--
-- Structure de la table `faq_choice`
--

CREATE TABLE `faq_choice` (
  `id` int(11) NOT NULL,
  `IDfaq` int(11) DEFAULT NULL,
  `label` mediumtext DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `faq_choice`
--

INSERT INTO `faq_choice` (`id`, `IDfaq`, `label`, `is_correct`) VALUES
(1, 1, 'Propisition 1 (la bonne)', 1),
(2, 1, 'Proposition 2 (la mauvaise)', 0),
(3, 2, 'Propisition 1 (la mauvaise)', 0),
(4, 2, 'Proposition 2 (la bonne)', 1),
(5, 2, 'Proposition 3 (la bonne)', 1);

-- --------------------------------------------------------

--
-- Structure de la table `holon`
--

CREATE TABLE `holon` (
  `id` int(11) NOT NULL,
  `IDorganization` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `color` varchar(10) DEFAULT NULL COMMENT 'Couleur du noeud, qui peut être héritée du template.',
  `IDholon_org` int(11) DEFAULT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Est supprimé ? Peut éventuellement être sorti d''une corbeille ou consulté pour archivage, mais sinon n''est plus utilisé',
  `visible` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Est visible? Ou plutôt caché pour pouvoir être réaffiché plus tard ou pour servir de template invisible',
  `mandatory` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Est obligatoire, et est ajouté à tout cercle nouvellement créé',
  `unique` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Est unique dans le cercle de rattachement, groupes compris',
  `link` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Se comporte comme un lien, en étant représenté également dans le cercle englobant',
  `templatename` varchar(150) DEFAULT NULL,
  `IDtypeholon` int(11) DEFAULT NULL,
  `IDholon_parent` int(11) DEFAULT NULL,
  `IDholon_template` int(11) DEFAULT NULL,
  `accesskey` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `holon`
--

INSERT INTO `holon` (`id`, `IDorganization`, `name`, `color`, `IDholon_org`, `IDuser`, `datecreation`, `datemodification`, `active`, `visible`, `mandatory`, `link`, `templatename`, `IDtypeholon`, `IDholon_parent`, `IDholon_template`, `accesskey`) VALUES
(-1, NULL, 'Basic', NULL, NULL, NULL, '2024-12-09 04:00:04', NULL, 1, 1, 0, 0, 'Basic', NULL, NULL, NULL, NULL),
(1, NULL, 'Nom organisation', NULL, NULL, 1, '2024-11-30 09:50:26', NULL, 1, 1, 0, 0, 'Organisation basique', 4, NULL, NULL, NULL),
(2, NULL, NULL, NULL, 1, 1, '2024-11-30 09:50:26', NULL, 1, 0, 0, 0, 'Rôle', 1, 1, NULL, NULL),
(3, NULL, NULL, NULL, 1, 1, '2024-11-30 09:51:41', NULL, 1, 0, 0, 0, 'Cercle', 2, 1, NULL, NULL),
(4, NULL, NULL, NULL, 1, 1, '2024-11-30 09:53:13', NULL, 1, 0, 0, 0, 'Groupe', 3, 1, NULL, NULL),
(5, NULL, 'Nom organisation', NULL, NULL, 1, '2024-11-30 09:50:26', NULL, 1, 1, 0, 0, 'Organisation classique', 4, NULL, -1, NULL),
(6, NULL, NULL, NULL, 5, 1, '2024-11-30 09:50:26', NULL, 1, 0, 0, 0, 'Rôle', 1, 5, -1, NULL),
(7, NULL, NULL, NULL, 5, 1, '2024-12-03 09:51:41', NULL, 1, 0, 0, 0, 'Cercle', 2, 5, -1, NULL),
(8, NULL, NULL, NULL, 5, 1, '2024-12-03 09:53:13', NULL, 1, 0, 0, 0, 'Groupe', 3, 5, -1, NULL),
(9, NULL, 'Ancrage', NULL, 5, 1, '2024-12-03 09:51:41', NULL, 1, 1, 0, 0, NULL, 2, 5, 7, NULL),
(10, NULL, 'CA', NULL, 5, 1, '2024-12-03 09:51:41', NULL, 1, 1, 0, 0, NULL, 2, 5, 7, NULL),
(11, NULL, 'Lien pilotage', NULL, 5, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, 'Lien pilotage', 1, 9, 6, NULL),
(12, NULL, 'Facilitation', NULL, 5, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, 'Rôle facilitation', 1, 9, 6, NULL),
(13, NULL, 'Mémoire', NULL, 5, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, 'Rôle mémoire', 1, 9, 6, NULL),
(14, NULL, 'Opérations', NULL, 5, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, NULL, 1, 9, 6, NULL),
(15, NULL, 'Président', NULL, 5, 1, '2024-12-04 05:30:56', NULL, 1, 1, 0, 0, NULL, 1, 10, 6, NULL),
(16, NULL, 'Trésorier', NULL, 5, 1, '2024-12-04 05:30:56', NULL, 1, 1, 0, 0, NULL, 1, 10, 6, NULL),
(637, NULL, 'Nom organisation', NULL, NULL, 16, '2024-11-30 09:50:26', NULL, 1, 1, 0, 0, 'Organisation classique', 4, NULL, -1, NULL),
(638, NULL, NULL, NULL, 637, 1, '2024-12-03 09:51:41', NULL, 1, 0, 0, 0, 'Cercle', 2, 637, -1, NULL),
(639, NULL, 'Ancrage', NULL, 637, 1, '2024-12-03 09:51:41', NULL, 1, 1, 0, 0, NULL, 2, 637, 638, NULL),
(640, NULL, NULL, NULL, 637, 1, '2024-11-30 09:50:26', NULL, 1, 0, 0, 0, 'Rôle', 1, 637, -1, NULL),
(641, NULL, 'Facilitation', NULL, 637, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, 'Rôle facilitation', 1, 639, 640, NULL),
(642, NULL, 'Lien pilotage', NULL, 637, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, 'Lien pilotage', 1, 639, 6, NULL),
(643, NULL, 'Mémoire', NULL, 637, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, 'Rôle mémoire', 1, 639, 6, NULL),
(644, NULL, 'Opérations', NULL, 637, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, NULL, 1, 639, 6, NULL),
(645, NULL, 'CA', NULL, 637, 1, '2024-12-03 09:51:41', NULL, 1, 1, 0, 0, NULL, 2, 637, 7, NULL),
(646, NULL, 'Président', NULL, 637, 1, '2024-12-04 05:30:56', NULL, 1, 1, 0, 0, NULL, 1, 645, 6, NULL),
(647, NULL, 'Trésorier', NULL, 637, 1, '2024-12-04 05:30:56', NULL, 1, 1, 0, 0, NULL, 1, 645, 6, NULL),
(648, NULL, 'Forum de la Coopération', NULL, NULL, 16, '2024-11-30 09:50:26', NULL, 1, 1, 0, 0, 'Organisation classique', 4, NULL, -1, NULL),
(649, NULL, NULL, NULL, 648, 1, '2024-12-03 09:51:41', NULL, 1, 0, 0, 0, 'Cercle', 2, 648, -1, NULL),
(650, NULL, 'Ancrage', NULL, 648, 1, '2024-12-03 09:51:41', NULL, 1, 1, 0, 0, NULL, 2, 648, 649, NULL),
(651, NULL, NULL, NULL, 648, 1, '2024-11-30 09:50:26', NULL, 1, 0, 0, 0, 'Rôle', 1, 648, -1, NULL),
(652, NULL, 'Facilitation', NULL, 648, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, 'Rôle facilitation', 1, 650, 651, NULL),
(653, NULL, 'Lien pilotage', NULL, 648, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, 'Lien pilotage', 1, 650, 6, NULL),
(654, NULL, 'Mémoire', NULL, 648, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, 'Rôle mémoire', 1, 650, 6, NULL),
(655, NULL, 'Opérations', NULL, 648, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, NULL, 1, 650, 6, NULL),
(656, NULL, 'fdsfds', NULL, 648, 16, '2025-08-03 16:36:49', NULL, 1, 1, 0, 0, NULL, 2, 650, 649, NULL),
(657, NULL, 'Facilitation', NULL, 648, 16, '2025-08-03 16:36:49', NULL, 1, 1, 0, 0, NULL, 1, 656, 652, NULL),
(658, NULL, 'CA', NULL, 648, 1, '2024-12-03 09:51:41', NULL, 1, 1, 0, 0, NULL, 2, 648, 7, NULL),
(659, NULL, 'Président', NULL, 648, 1, '2024-12-04 05:30:56', NULL, 1, 1, 0, 0, NULL, 1, 658, 6, NULL),
(660, NULL, 'Trésorier', NULL, 648, 1, '2024-12-04 05:30:56', NULL, 1, 1, 0, 0, NULL, 1, 658, 6, NULL),
(661, NULL, 'Forum de la Coopération', NULL, NULL, 16, '2024-11-30 09:50:26', NULL, 1, 1, 0, 0, 'Organisation classique', 4, NULL, -1, NULL),
(662, NULL, NULL, NULL, 661, 1, '2024-12-03 09:51:41', NULL, 1, 0, 0, 0, 'Cercle', 2, 661, -1, NULL),
(663, NULL, 'Ancrage', NULL, 661, 1, '2024-12-03 09:51:41', NULL, 1, 1, 0, 0, NULL, 2, 661, 662, NULL),
(664, NULL, NULL, NULL, 661, 1, '2024-11-30 09:50:26', NULL, 1, 0, 0, 0, 'Rôle', 1, 661, -1, NULL),
(665, NULL, 'Facilitation', NULL, 661, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, 'Rôle facilitation', 1, 663, 664, NULL),
(666, NULL, 'Lien pilotage', NULL, 661, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, 'Lien pilotage', 1, 663, 6, NULL),
(667, NULL, 'Mémoire', NULL, 661, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, 'Rôle mémoire', 1, 663, 6, NULL),
(668, NULL, 'Opérations', NULL, 661, 1, '2024-12-03 13:02:06', NULL, 1, 1, 0, 0, NULL, 1, 663, 6, NULL),
(670, NULL, 'Facilitation', NULL, 661, 16, '2025-08-03 16:37:02', NULL, 1, 1, 0, 0, NULL, 1, 669, 665, NULL),
(671, NULL, 'CA', NULL, 661, 1, '2024-12-03 09:51:41', NULL, 1, 1, 0, 0, NULL, 2, 661, 7, NULL),
(672, NULL, 'Président', NULL, 661, 1, '2024-12-04 05:30:56', NULL, 1, 1, 0, 0, NULL, 1, 671, 6, NULL),
(673, NULL, 'Trésorier', NULL, 661, 1, '2024-12-04 05:30:56', NULL, 1, 1, 0, 0, NULL, 1, 671, 6, NULL),
(674, 1, 'Organisation Demo Holacratique', NULL, NULL, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, 'Organisation classique', 4, NULL, -1, NULL),
(675, NULL, NULL, NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 0, 0, 0, 'Role', 1, 674, -1, NULL),
(676, NULL, NULL, NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 0, 0, 0, 'Cercle', 2, 674, -1, NULL),
(677, NULL, NULL, NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 0, 0, 0, 'Groupe', 3, 674, -1, NULL),
(678, NULL, 'Ancrage', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 2, 674, 676, NULL),
(679, NULL, 'CA', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 2, 674, 676, NULL),
(680, NULL, 'Lien pilotage', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, 'Lien pilotage', 1, 678, 675, NULL),
(681, NULL, 'Facilitation', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, 'Role facilitation', 1, 678, 675, NULL),
(682, NULL, 'Memoire', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, 'Role memoire', 1, 678, 675, NULL),
(683, NULL, 'Operations', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 1, 678, 675, NULL),
(684, NULL, 'President', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 1, 679, 675, NULL),
(685, NULL, 'Tresorier', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 1, 679, 675, NULL),
(686, NULL, 'Administration', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 2, 678, 676, NULL),
(687, NULL, 'Marketing', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 2, 678, 676, NULL),
(688, NULL, 'Lien pilotage administration', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, 'Lien pilotage', 1, 686, 680, NULL),
(689, NULL, 'Facilitation administration', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, 'Role facilitation', 1, 686, 681, NULL),
(690, NULL, 'Memoire administration', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, 'Role memoire', 1, 686, 682, NULL),
(691, NULL, 'Operations administration', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 1, 686, 683, NULL),
(692, NULL, 'Gestion administrative', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 1, 686, 675, NULL),
(693, NULL, 'Comptabilite et budget', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 1, 686, 675, NULL),
(694, NULL, 'Support interne', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 1, 686, 675, NULL),
(695, NULL, 'Lien pilotage marketing', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, 'Lien pilotage', 1, 687, 680, NULL),
(696, NULL, 'Facilitation marketing', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, 'Role facilitation', 1, 687, 681, NULL),
(697, NULL, 'Memoire marketing', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, 'Role memoire', 1, 687, 682, NULL),
(698, NULL, 'Operations marketing', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 1, 687, 683, NULL),
(699, NULL, 'Communication digitale', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 1, 687, 675, NULL),
(700, NULL, 'Partenariats et visibilite', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 1, 687, 675, NULL),
(701, NULL, 'Contenus et campagnes', NULL, 674, 1, '2026-04-19 16:46:34', NULL, 1, 1, 0, 0, NULL, 1, 687, 675, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `holonproperty`
--

CREATE TABLE `holonproperty` (
  `id` int(11) NOT NULL,
  `IDholon` int(11) NOT NULL,
  `IDproperty` int(11) NOT NULL,
  `value` mediumtext DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `mandatory` tinyint(1) NOT NULL DEFAULT 0,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `holonproperty`
--

INSERT INTO `holonproperty` (`id`, `IDholon`, `IDproperty`, `value`, `position`, `active`) VALUES
(1, 1, 1, NULL, 1, 1),
(2, 1, 2, NULL, 2, 1),
(3, 1, 3, NULL, 3, 1),
(4, 2, 1, NULL, 1, 1),
(5, 2, 2, NULL, 2, 1),
(6, 2, 3, NULL, 3, 1),
(7, 3, 1, NULL, 1, 1),
(8, 3, 2, NULL, 2, 1),
(9, 3, 3, NULL, 3, 1),
(10, 1, 4, NULL, 4, 1),
(11, 3, 4, NULL, 4, 1),
(12, 5, 5, NULL, 1, 1),
(14, 7, 5, NULL, 1, 1),
(15, 5, 6, NULL, 2, 1),
(16, 6, 6, NULL, 2, 1),
(17, 7, 6, NULL, 2, 1),
(18, 5, 7, NULL, 3, 1),
(19, 6, 7, NULL, 3, 1),
(20, 7, 7, NULL, 3, 1),
(21, 5, 8, NULL, 4, 1),
(22, 7, 8, NULL, 4, 1),
(23, 11, 5, 'S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.', NULL, 1),
(24, 12, 5, 'Assurer des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.', NULL, 1),
(25, 13, 5, 'S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.', NULL, 1),
(26, 6, 5, NULL, NULL, 1),
(83, 638, 31, NULL, 1, 1),
(84, 638, 32, NULL, 2, 1),
(85, 638, 33, NULL, 3, 1),
(86, 638, 34, NULL, 4, 1),
(87, 641, 31, 'Raison d\'être du rôle mémoire', NULL, 1),
(88, 642, 31, 'Raison d\'être du rôle facilitateur', NULL, 1),
(89, 643, 31, 'Raison d\'être du lien pilotage', NULL, 1),
(90, 649, 35, NULL, 1, 1),
(91, 649, 36, NULL, 2, 1),
(92, 649, 37, NULL, 3, 1),
(93, 649, 38, NULL, 4, 1),
(94, 652, 35, 'Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.', NULL, 1),
(95, 652, 37, 'Domaine du rôle facilitation', NULL, 1),
(96, 652, 36, 'fdsfsdfdsfds', NULL, 1),
(97, 653, 35, 'S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.', NULL, 1),
(98, 654, 35, 'S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.', NULL, 1),
(99, 656, 35, 'fdsfsd', NULL, 1),
(100, 656, 37, 'fdsfds', NULL, 1),
(101, 656, 36, 'dsffds', NULL, 1),
(102, 656, 38, 'fdsfsd', NULL, 1),
(103, 657, 35, 'fsfdf', NULL, 1),
(104, 657, 37, 'fdsfds', NULL, 1),
(105, 657, 36, 'fdsfds', NULL, 1),
(106, 662, 39, NULL, 1, 1),
(107, 662, 40, NULL, 2, 1),
(108, 662, 41, NULL, 3, 1),
(109, 662, 42, NULL, 4, 1),
(110, 665, 39, 'Assurer 2 des réunions menées avec efficacité et humanisme, permettant d’obtenir dans le temps imparti des résultats clairs et répondant aux problématiques amenées par les membres du cercle.', NULL, 1),
(111, 665, 41, 'Domaine du rôle facilitation', NULL, 1),
(112, 665, 40, 'fdsfsdfdsfds', NULL, 1),
(113, 666, 39, 'S’assurer que l’activité du cercle réponde à sa raison d’être, ses objectifs et reste en cohérence avec la raison d’être et les valeurs de l’organisation, tout en prenant soin de ses membres et en assurant à chacun et chacune un rôle en adéquation avec ses compétences et sa motivation. Assurer la bonne circulation des informations entre le cercle et le cercle englobant, afin que les activités de tous et toutes se fassent dans une conscience de l’interdépendance des différents cercles.', NULL, 1),
(114, 667, 39, 'S’assurer d’un système d’information bien tenu, rendant accessible à tous et toutes les informations nécessaires à chaque rôle pour piloter ses activités, notamment en reportant toute décision et toute information partagée lors des réunions dans le système d’information.', NULL, 1),
(115, 669, 39, 'fdsfsd', NULL, 1),
(116, 669, 41, 'fdsfds', NULL, 1),
(117, 669, 40, 'dsffds', NULL, 1),
(118, 669, 42, 'fdsfsd', NULL, 1),
(119, 670, 39, 'fsfdf', NULL, 1),
(120, 670, 41, 'fdsfds', NULL, 1),
(121, 670, 40, 'fdsfds', NULL, 1),
(122, 674, 43, 'Rendre possible une gouvernance distribuee, lisible et apprenante, dans laquelle chaque cercle sait ou il contribue et avec quelle marge d\'autonomie.', 1, 1),
(123, 674, 44, 'Donner un cap commun, soutenir la prise de role, assurer un cadre de decision fiable et permettre aux sous-cercles de cooperer sans confusion structurelle.', 3, 1),
(124, 674, 45, 'Architecture generale de la gouvernance, definition des espaces de responsabilite, arbitrage des tensions de structure et cadre d\'evolution de l\'organisation.', 2, 1),
(125, 674, 46, 'Construire une demonstration riche et pedagogique, suffisamment vivante pour tester la navigation, les vues et la lecture de contenu sans toucher au modele de base.', 4, 1),
(126, 675, 43, NULL, 1, 1),
(127, 675, 44, NULL, 3, 1),
(128, 675, 45, NULL, 2, 1),
(129, 676, 43, NULL, 1, 1),
(130, 676, 44, NULL, 3, 1),
(131, 676, 45, NULL, 2, 1),
(132, 676, 46, NULL, 4, 1),
(133, 678, 43, 'Tenir ensemble la coherence globale de l\'organisation, ses priorites structurelles et la capacite des sous-cercles a agir dans un cadre commun.', NULL, 1),
(134, 678, 44, 'Porter les roles structurels de base, arbitrer les tensions de coordination, faire vivre les sous-cercles et assurer une articulation claire avec le CA.', NULL, 1),
(135, 678, 45, 'Cadre de gouvernance courante, roles structurels transversaux, arbitrages inter-cercles et supervision de la capacite d\'execution globale.', NULL, 1),
(136, 678, 46, 'Structurer progressivement l\'organisation autour de cercles specialises capables d\'apprendre sans perdre leur alignement global.', NULL, 1),
(137, 679, 43, 'Garantir la solidite institutionnelle, la responsabilite fiduciaire et la tenue des engagements de l\'organisation sur ses enjeux de gouvernance formelle.', NULL, 1),
(138, 679, 44, 'Suivre les obligations du conseil, veiller aux grands equilibres, soutenir les decisions engageantes et offrir un cadre de redevabilite au niveau strategique.', NULL, 1),
(139, 679, 45, 'Questions statutaires, decisions engageant l\'organisation, surveillance budgetaire de haut niveau et responsabilites institutionnelles du conseil.', NULL, 1),
(140, 680, 43, 'Assurer que l\'activite du cercle d\'ancrage reste alignee avec la raison d\'etre de l\'organisation et que les tensions importantes soient portees au bon niveau.', NULL, 1),
(141, 680, 44, 'Cadencer les priorites, relier les sous-cercles au cap commun et porter les arbitrages structurels quand plusieurs besoins entrent en tension.', NULL, 1),
(142, 680, 45, 'Priorites du cercle, arbitrages de coordination, lien avec les autres cercles et remontees structurantes vers les espaces de pilotage.', NULL, 1),
(143, 681, 43, 'Permettre des reunions utiles, claires et suffisamment contenues pour transformer rapidement les tensions en decisions partagees.', NULL, 1),
(144, 681, 44, 'Preparer le cadre des reunions, fluidifier la circulation de parole, maintenir le rythme et aider le cercle a sortir de chaque seance avec des suites claires.', NULL, 1),
(145, 681, 45, 'Animation des reunions, gestion du temps, discipline de processus et soutien a la clarification des decisions.', NULL, 1),
(146, 682, 43, 'Conserver une memoire structurelle fiable afin que les decisions, reperes et apprentissages restent accessibles dans le temps.', NULL, 1),
(147, 682, 44, 'Documenter les reunions, garder les versions utiles a jour et assurer une tracabilite suffisante pour permettre une reprise rapide des sujets.', NULL, 1),
(148, 682, 45, 'Comptes rendus, historiques de decisions, maintenance documentaire et qualite du systeme d\'information du cercle.', NULL, 1),
(149, 683, 43, 'Soutenir l\'execution concrete du cercle d\'ancrage afin que les sujets avances, les dependances soient visibles et les actions suivies.', NULL, 1),
(150, 683, 44, 'Coordonner le flux de travail, rendre visibles les points de blocage et soutenir une progression reguliere des engagements du cercle.', NULL, 1),
(151, 683, 45, 'Suivi d\'execution, coordination quotidienne, priorisation operationnelle et gestion des dependances de travail.', NULL, 1),
(152, 684, 43, 'Assurer une presidence lisible, capable d\'orienter les deliberations du conseil et de tenir le cadre de responsabilite de l\'organisation.', NULL, 1),
(153, 684, 44, 'Convoquer, orienter, representer et garantir que les decisions du CA soient prises dans un cadre clair et suivi.', NULL, 1),
(154, 684, 45, 'Animation politique du conseil, representation institutionnelle et tenue du cadre des decisions du CA.', NULL, 1),
(155, 685, 43, 'Veiller a la solidite economique de l\'organisation et a la lisibilite des engagements financiers pris au niveau du conseil.', NULL, 1),
(156, 685, 44, 'Suivre les grands equilibres, alerter en cas d\'ecart significatif et soutenir le CA dans ses lectures budgetaires et financieres.', NULL, 1),
(157, 685, 45, 'Suivi des finances au niveau conseil, lecture budgetaire, vigilance de tresorerie et appui aux arbitrages economiques structurants.', NULL, 1),
(158, 686, 43, 'Fournir un socle administratif fiable, simple et lisible pour que l\'organisation puisse agir sans friction et avec de bonnes bases de pilotage.', NULL, 1),
(159, 686, 44, 'Tenir a jour les processus, clarifier les responsabilites, fiabiliser les echeances et permettre aux autres cercles de trouver rapidement les informations administratives utiles.', NULL, 1),
(160, 686, 45, 'Processus administratifs courants, coordination documentaire, calendrier administratif interne, relation fournisseurs de support et cadre de suivi budgetaire du quotidien.', NULL, 1),
(161, 686, 46, 'Stabiliser d\'abord les flux recurrent, puis standardiser ce qui peut l\'etre sans rigidifier les interactions avec les autres cercles.', NULL, 1),
(162, 687, 43, 'Renforcer la visibilite de l\'organisation et la qualite de sa presence publique afin d\'attirer les bonnes relations, les bonnes opportunites et une meilleure lisibilite de sa proposition.', NULL, 1),
(163, 687, 44, 'Animer une presence coherente, produire des messages utiles, soutenir les campagnes prioritaires et faire circuler les retours du terrain vers le reste de l\'organisation.', NULL, 1),
(164, 687, 45, 'Positionnement public, canaux de communication, campagnes de visibilite, relations de partenariat, calendrier editorial et suivi des retombees.', NULL, 1),
(165, 687, 46, 'Construire un dispositif de communication progressif, ancre dans les besoins reels du terrain et dans la capacite de production du cercle.', NULL, 1),
(166, 688, 43, 'Assurer que le cercle Administration reste aligne avec la raison d\'etre du cercle d\'ancrage et remonte les besoins structurels au bon niveau.', NULL, 1),
(167, 688, 44, 'Cadencer les priorites, expliciter les arbitrages et relier les engagements du cercle avec les attentes du cercle englobant.', NULL, 1),
(168, 688, 45, 'Ordre des priorites du cercle, arbitrages d\'allocation et representation du cercle dans les espaces de pilotage.', NULL, 1),
(169, 689, 43, 'Permettre des reunions courtes, claires et actionnables dans le cercle Administration.', NULL, 1),
(170, 689, 44, 'Preparer les sequences de reunion, distribuer la parole et aider le cercle a conclure sans confusion.', NULL, 1),
(171, 689, 45, 'Animation des reunions du cercle, gestion du temps et securisation du processus de decision.', NULL, 1),
(172, 690, 43, 'Garantir une memoire administrative exploitable, de sorte que les decisions et documents utiles restent accessibles et reutilisables.', NULL, 1),
(173, 690, 44, 'Tracer les decisions, consolider les reperes documentaires et garder les versions utiles a jour.', NULL, 1),
(174, 690, 45, 'Organisation documentaire du cercle, comptes rendus, historiques de decision et maintenance du systeme d\'information local.', NULL, 1),
(175, 691, 43, 'Faire avancer le flux operationnel du cercle Administration pour que les besoins internes trouvent une reponse concrete et suivie.', NULL, 1),
(176, 691, 44, 'Suivre les demandes en cours, coordonner les actions transversales et reduire les points de friction.', NULL, 1),
(177, 691, 45, 'Coordination quotidienne du travail, priorisation des demandes et synchronisation des roles operationnels du cercle.', NULL, 1),
(178, 692, 43, 'Prendre en charge les gestes administratifs recurrents afin que les equipes puissent s\'appuyer sur un cadre simple, fiable et accueillant. Ce role fait gagner du temps collectif en rendant les demarches ordinaires plus lisibles, plus fluides et moins dependantes de la memoire individuelle.', NULL, 1),
(179, 692, 44, 'Recevoir et traiter les demandes administratives courantes, tenir a jour les repertoires et formulaires utiles, suivre les pieces attendues, relancer avec tact lorsque cela est necessaire et rendre visible l\'etat d\'avancement des demandes afin que personne ne reste bloque sans information.', NULL, 1),
(180, 692, 45, 'Gestion des formulaires, suivi des contrats simples, archivage courant, preparation des dossiers administratifs de base, coordination logistique legere, mise a disposition des modeles utiles et maintenance des reperes pratiques dont les autres roles ont besoin pour agir.', NULL, 1),
(181, 693, 43, 'Rendre visible la realite economique du quotidien en tenant les comptes, les engagements et les reperes budgetaires de maniere fiable et pedagogique. Ce role permet aux autres roles de prendre de meilleures decisions parce qu\'ils comprennent mieux les consequences economiques de leurs actions.', NULL, 1),
(182, 693, 44, 'Saisir et suivre les operations, rapprocher les informations utiles, signaler les ecarts significatifs, preparer des vues budgetaires lisibles, contribuer a la fiabilite des echeances financieres et apporter aux cercles des points de lecture suffisamment clairs pour soutenir les arbitrages du quotidien.', NULL, 1),
(183, 693, 45, 'Suivi budgetaire courant, enregistrement comptable, echeancier de paiements, suivi de facturation, justification des depenses, collecte des pieces utiles, mise a jour des tableaux de bord economiques et coordination avec les roles qui engagent des moyens financiers au nom de l\'organisation.', NULL, 1),
(184, 694, 43, 'Fluidifier le quotidien des membres en apportant une aide pratique sur les outils, les demandes internes et les petits blocages organisationnels. Ce role existe pour que les irritants du terrain trouvent rapidement une reponse simple au lieu de ralentir inutilement le travail collectif.', NULL, 1),
(185, 694, 44, 'Recevoir les sollicitations, qualifier rapidement le besoin, orienter vers la bonne ressource, documenter les resolutions utiles, assurer un suivi minimum des demandes ouvertes et contribuer a un climat de service interne ou chacun sait a qui s\'adresser et sous quel delai attendre une premiere reponse.', NULL, 1),
(186, 694, 45, 'Support de premier niveau, coordination des demandes internes, orientation vers les bons interlocuteurs, centralisation des questions recurrentes, maintenance d\'une base de resolutions utiles, appui ponctuel a l\'onboarding et accompagnement pratique sur les outils ou procedures du quotidien.', NULL, 1),
(187, 695, 43, 'Assurer que le cercle Marketing reste relie aux priorites de l\'organisation et transforme les besoins de visibilite en choix explicites.', NULL, 1),
(188, 695, 44, 'Clarifier les priorites, arbitrer les demandes concurrentes et tenir le cercle dans une dynamique de cap.', NULL, 1),
(189, 695, 45, 'Arbitrage des priorites du cercle, articulation avec l\'ancrage et representation du cercle.', NULL, 1),
(190, 696, 43, 'Rendre les reunions marketing fluides et productives pour que les idees se transforment en decisions et actions suivies.', NULL, 1),
(191, 696, 44, 'Structurer les sequences, garantir un cadre de participation utile et aider a conclure chaque sujet.', NULL, 1),
(192, 696, 45, 'Animation des reunions, gestion du temps et clarification des points de decision.', NULL, 1),
(193, 697, 43, 'Conserver une memoire marketing utile afin que les campagnes, apprentissages et decisions restent mobilisables.', NULL, 1),
(194, 697, 44, 'Archiver les supports, documenter les retours et garder la trace des choix editoriaux.', NULL, 1),
(195, 697, 45, 'Base documentaire des campagnes, historique des contenus et capitalisation des apprentissages.', NULL, 1),
(196, 698, 43, 'Transformer les intentions marketing en execution suivie, de sorte que les actions de visibilite avancent avec regularite.', NULL, 1),
(197, 698, 44, 'Coordonner la production, suivre les echeances et rendre visibles les blocages.', NULL, 1),
(198, 698, 45, 'Coordination quotidienne, suivi d\'execution et gestion du flux des actions marketing en cours.', NULL, 1),
(199, 699, 43, 'Faire vivre la presence numerique de l\'organisation avec des messages reguliers, lisibles et alignes avec son identite. Ce role transforme l\'activite reelle du terrain en signaux publics comprehensibles, de sorte que l\'organisation soit visible sans perdre sa coherence ni son ton propre.', NULL, 1),
(200, 699, 44, 'Planifier et publier les contenus, suivre les performances utiles, adapter les formats aux canaux, remonter les apprentissages au cercle Marketing et maintenir une presence suffisamment reguliere pour que les publics percoivent la continuite de l\'action menee par l\'organisation.', NULL, 1),
(201, 699, 45, 'Animation des canaux digitaux, publication, optimisation des formats, lecture des retours quantitatifs, adaptation des calendriers de diffusion, coordination des assets numeriques et gestion du cycle de vie des publications sur les canaux prioritaires.', NULL, 1),
(202, 700, 43, 'Developper des relations externes de qualite pour etendre la portee de l\'organisation, faire emerger des cooperations utiles et multiplier les relais de confiance autour de ses actions. Ce role contribue a rendre l\'organisation plus visible en travaillant d\'abord la qualite des liens.', NULL, 1),
(203, 700, 44, 'Identifier les partenaires pertinents, entretenir les relations existantes, preparer les prises de contact, suivre les suites donnees aux echanges, faire circuler les opportunites utiles vers les autres roles et contribuer a une presence externe plus coherente et mieux coordonnee.', NULL, 1),
(204, 700, 45, 'Cartographie de partenaires, prises de contact, suivi des relations, coordination d\'actions communes, veille sur les opportunites de collaboration, preparation de rendez-vous, maintenance des reperes relationnels et valorisation des occasions de visibilite partagee.', NULL, 1),
(205, 701, 43, 'Produire des contenus qui rendent l\'organisation comprehensible, desirable et credible, tout en soutenant ses temps forts et ses besoins de communication. Ce role aide a transformer les intentions du cercle en messages concrets, portables et reutilisables sur plusieurs supports.', NULL, 1),
(206, 701, 44, 'Concevoir le calendrier editorial, preparer les textes et assets necessaires, soutenir les campagnes prioritaires, coordonner la collecte de matiere premiere aupres des autres roles et maintenir une qualite narrative constante dans les contenus produits.', NULL, 1),
(207, 701, 45, 'Production de contenus, coordination editoriale, preparation des campagnes, maintenance des messages de reference, suivi du calendrier de diffusion, adaptation de la forme aux differents supports et capitalisation des contenus reutilisables pour les communications futures.', NULL, 1),
(208, 692, 46, 'Stabiliser d\'abord les demandes les plus frequentes, puis documenter les cas types afin de reduire progressivement la charge mentale administrative sur l\'ensemble des autres roles.', NULL, 1),
(209, 693, 46, 'Passer d\'une logique de simple tenue des comptes a une logique d\'aide a la decision economique, avec des vues plus lisibles et un rythme de reporting adapte aux besoins reels des cercles.', NULL, 1),
(210, 694, 46, 'Construire une base de support suffisamment robuste pour absorber les demandes courantes rapidement, puis identifier les irritants repetitifs a traiter a la racine.', NULL, 1),
(211, 699, 46, 'Chercher d\'abord la regularite et la coherence editoriale avant la sophistication des formats, afin que la presence digitale gagne en credibilite et en lisibilite dans la duree.', NULL, 1),
(212, 700, 46, 'Consolider quelques partenariats a forte valeur relationnelle avant d\'elargir le reseau, afin de faire de chaque lien externe un point d\'appui concret pour la visibilite de l\'organisation.', NULL, 1),
(213, 701, 46, 'Capitaliser sur les contenus qui expliquent le mieux la proposition de valeur de l\'organisation, puis decliner cette matiere en campagnes, formats courts et supports reutilisables.', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `media`
--

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `title` varchar(250) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `contenttype` varchar(255) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `IDtype` int(11) NOT NULL,
  `IDstorage` int(11) NOT NULL,
  `accesskey` varchar(255) NOT NULL,
  `IDdocument` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mission`
--

CREATE TABLE `mission` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `resume` text NOT NULL,
  `video` varchar(150) DEFAULT NULL,
  `html` text DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateupdate` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Missions d''un parcours de formation';

--
-- Déchargement des données de la table `mission`
--

INSERT INTO `mission` (`id`, `title`, `resume`, `video`, `html`, `position`, `datecreation`, `dateupdate`) VALUES
(1, 'Introduction', 'Bienvenue dans le parcours', 'https://video.example.test/module-introduction', '<div class=\"module-intro\">\r\n\r\n  <p><strong>Bonjour, et bienvenue.</strong></p>\r\n\r\n  <p>\r\n    Vous arrivez dans une organisation qui fonctionne selon un mode de gouvernance \r\n    que vous ne connaissez peut-être pas… ou peu.<br>\r\n    Un mode de fonctionnement différent, parfois déroutant au premier abord.\r\n  </p>\r\n\r\n  <p>\r\n    Qu’on l’appelle gouvernance partagée, gouvernance horizontale… peu importe le terme.<br>\r\n    Vous vous posez sans doute des questions.<br>\r\n    Et c’est normal.\r\n  </p>\r\n\r\n  <p>\r\n    Alors si vous avez envie de mieux comprendre de quoi il s’agit, \r\n    vous êtes exactement au bon endroit.\r\n  </p>\r\n\r\n  <p>\r\n    Dans ce module, nous allons découvrir ensemble ce que cela signifie, concrètement.\r\n  </p>\r\n\r\n  <p>\r\n    Mais avant d’entrer dans les outils, les méthodes ou les pratiques,<br>\r\n    nous allons prendre un temps pour explorer quelque chose de plus fondamental :\r\n  </p>\r\n\r\n  <p><strong>la philosophie qui se cache derrière cette démarche.</strong></p>\r\n\r\n  <p>\r\n    Car trop souvent, on aborde ces nouvelles formes de gouvernance à travers les outils \r\n    qu’elles proposent.<br>\r\n    Alors qu’en réalité… ces outils ne sont qu’un support.\r\n  </p>\r\n\r\n  <p>\r\n    Ils viennent soutenir une posture.<br>\r\n    Une manière d’être.<br>\r\n    Une façon de travailler ensemble… profondément différente.\r\n  </p>\r\n\r\n  <p>\r\n    Dans ce module, nous allons donc nous intéresser à ce qui se trouve en dessous.\r\n  </p>\r\n\r\n  <p>\r\n    Les valeurs qui fondent ces pratiques.<br>\r\n    Celles qui traversent — ou devraient traverser — l’ensemble de l’organisation.\r\n  </p>\r\n\r\n  <p>\r\n    Nous parlerons de confiance, de coopération, de responsabilité…<br>\r\n    mais aussi de souveraineté.\r\n  </p>\r\n\r\n  <p>\r\n    Vous verrez que ces valeurs ne sont pas seulement des intentions.<br>\r\n    Elles sont au cœur du fonctionnement des outils que vous allez découvrir.\r\n  </p>\r\n\r\n  <p>\r\n    Et plus encore : elles constituent un objectif en soi.\r\n  </p>\r\n\r\n  <p>\r\n    Nous verrons aussi comment ces valeurs soutiennent la capacité de l’organisation \r\n    à remplir sa mission.\r\n  </p>\r\n\r\n  <p>\r\n    Enfin, nous aborderons les grands principes qui structurent cette gouvernance.<br>\r\n    Des principes essentiels, qui permettent de garder une cohérence entre ce qui est affiché…<br>\r\n    et ce qui est réellement vécu au quotidien.\r\n  </p>\r\n\r\n  <p>\r\n    Alors si, avant de vous plonger dans les outils,<br>\r\n    vous souhaitez comprendre pourquoi on cherche à faire différemment…<br>\r\n    et à quoi tout cela sert…\r\n  </p>\r\n\r\n  <p><strong>Encore une fois, vous êtes au bon endroit.</strong></p>\r\n\r\n  <p><strong>Bienvenue dans ce premier module de formation.</strong></p>\r\n\r\n</div>', 1, '2026-04-03 10:40:41', '2026-04-07 08:28:13'),
(2, 'Bases de l’inclusion', 'Comprendre les fondamentaux', 'https://video.example.test/module-inclusion', '<h1>Bases</h1>', 2, '2026-04-03 10:40:41', '2026-04-04 01:55:23'),
(3, 'Tronc commun', 'Concepts clés à maîtriser', 'https://video.example.test/module-tronc-commun', '<h1>Tronc commun</h1>', 3, '2026-04-03 10:40:41', '2026-04-04 01:55:43'),
(4, 'Branche A - Cercles', 'Comprendre les cercles', 'https://video.example.test/module-cercles', '<h1>Cercles</h1>', 4, '2026-04-03 10:40:41', '2026-04-04 01:56:21'),
(5, 'Branche B - Rôles', 'Comprendre les rôles', NULL, '<h1>Rôles</h1>', 5, '2026-04-03 10:40:41', NULL),
(6, 'Branche C - Réunions', 'Comprendre les réunions', NULL, '<h1>Réunions</h1>', 6, '2026-04-03 10:40:41', NULL),
(7, 'Synthèse', 'Mettre ensemble les apprentissages', NULL, '<h1>Synthèse</h1>', 7, '2026-04-03 10:40:41', NULL),
(8, 'Conclusion', 'Clôture du parcours', NULL, '<h1>Conclusion</h1>', 8, '2026-04-03 10:40:41', NULL),
(101, 'Introduction test', 'Découvrir le parcours test', NULL, '<p>Bienvenue dans ce parcours test.</p>', 1, '2026-04-04 11:47:52', NULL),
(102, 'Étape 1', 'Première étape', 'https://video.example.test/module-introduction', '<p>Contenu étape 1</p>', 2, '2026-04-04 11:47:52', '2026-04-12 20:21:12'),
(103, 'Étape 2', 'Deuxième étape', NULL, '<p>Contenu étape 2</p>', 3, '2026-04-04 11:47:52', NULL),
(104, 'Bonus', 'Mission bonus', NULL, '<p>Contenu bonus</p>', 4, '2026-04-04 11:47:52', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `mission_dependencies`
--

CREATE TABLE `mission_dependencies` (
  `id` int(11) NOT NULL,
  `IDmission_parent` int(11) NOT NULL,
  `IDmission_child` int(11) NOT NULL,
  `IDparcours` int(11) NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `mission_dependencies`
--

INSERT INTO `mission_dependencies` (`id`, `IDmission_parent`, `IDmission_child`, `IDparcours`, `required`) VALUES
(1, 1, 2, 1, 1),
(2, 2, 3, 1, 1),
(3, 3, 4, 1, 1),
(4, 3, 5, 1, 1),
(5, 3, 6, 1, 1),
(6, 4, 7, 1, 1),
(7, 5, 7, 1, 1),
(8, 6, 7, 1, 1),
(9, 7, 8, 1, 1),
(10, 101, 102, 2, 1),
(11, 102, 103, 2, 1),
(12, 103, 104, 2, 0);

-- --------------------------------------------------------

--
-- Structure de la table `mission_faq`
--

CREATE TABLE `mission_faq` (
  `id` int(11) NOT NULL,
  `IDmission` int(11) DEFAULT NULL,
  `IDfaq` int(11) DEFAULT NULL,
  `position` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `mission_faq`
--

INSERT INTO `mission_faq` (`id`, `IDmission`, `IDfaq`, `position`) VALUES
(1, 102, 1, NULL),
(2, 102, 2, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `organization`
--

CREATE TABLE `organization` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `shortname` varchar(50) DEFAULT NULL,
  `domain` varchar(100) DEFAULT NULL,
  `logo` varchar(100) DEFAULT NULL,
  `banner` varchar(100) DEFAULT NULL,
  `color` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `organization`
--

INSERT INTO `organization` (`id`, `name`, `shortname`, `domain`, `logo`, `banner`, `color`) VALUES
(1, 'Org1', 'org1', 'org1.opengov.tools', '/img/org1-logo.svg', '/img/org1-banner.svg', '#0F766E'),
(2, 'Org2', 'org2', 'org2.opengov.tools', '/img/org2-logo.svg', '/img/org2-banner.svg', '#1D4ED8');

-- --------------------------------------------------------

--
-- Structure de la table `organization_application`
--

CREATE TABLE `organization_application` (
  `id` int(11) NOT NULL,
  `IDorganization` int(11) NOT NULL,
  `IDapplication` int(11) NOT NULL,
  `position` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `organization_application`
--

INSERT INTO `organization_application` (`id`, `IDorganization`, `IDapplication`, `position`, `active`) VALUES
(1, 1, 1, 10, 1),
(2, 2, 1, 10, 1),
(3, 1, 2, 20, 1),
(4, 2, 2, 20, 1),
(5, 1, 3, 30, 1),
(6, 2, 3, 30, 1),
(7, 1, 4, 40, 1),
(8, 2, 4, 40, 1),
(9, 1, 5, 50, 1),
(10, 2, 5, 50, 1),
(11, 1, 6, 60, 1),
(12, 2, 6, 60, 1),
(16, 1, 7, 8, 1),
(17, 1, 8, 9, 1);

-- --------------------------------------------------------

--
-- Structure de la table `organization_parcours`
--

CREATE TABLE `organization_parcours` (
  `id` int(11) NOT NULL,
  `IDorganization` int(11) NOT NULL,
  `IDparcours` int(11) NOT NULL,
  `position` int(11) DEFAULT NULL,
  `everybody` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `organization_parcours`
--

INSERT INTO `organization_parcours` (`id`, `IDorganization`, `IDparcours`, `position`, `everybody`) VALUES
(1, 2, 1, NULL, 1),
(2, 2, 2, NULL, 1),
(3, 1, 1, 2, 1),
(4, 1, 2, 1, 1),
(5, 1, 3, 3, 1);

-- --------------------------------------------------------

--
-- Structure de la table `parameter`
--

CREATE TABLE `parameter` (
  `id` int(11) NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` mediumtext NOT NULL,
  `type` varchar(30) NOT NULL,
  `format` varchar(255) DEFAULT NULL COMMENT 'Validation du format, par exemple avec une REGEXP',
  `value` mediumtext DEFAULT NULL,
  `typeobject` varchar(30) NOT NULL,
  `family` varchar(100) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parameter`
--

INSERT INTO `parameter` (`id`, `code`, `name`, `description`, `type`, `format`, `value`, `typeobject`, `family`, `active`) VALUES
(1, 'basic', 'Paramètre basic', 'Exemple de paramètre basic de type texte', 'string', NULL, NULL, 'dbObject\\user', NULL, 1),
(2, 'numeric', 'Paramètre numérique', 'Exemple de paramètre de type numérique', 'integer', NULL, '20', 'dbObject\\user', NULL, 1),
(3, 'check', 'Case à cocher', 'Exemple de paramètre de type case à cocher', 'checkbox', NULL, '1', 'dbObject\\user', NULL, 1),
(4, 'select', 'Select box', 'Exemple de paramètre de type select', 'select', NULL, 'Valeur 1;Valeur 2;Valeur 3', 'dbObject\\user', NULL, 1),
(5, 'isAdmin', 'est administrateur', 'Donne des droits d\'administration sur l\'organisation', 'checkbox', NULL, '', 'dbObject\\user-organization', NULL, 1),
(6, 'select', 'Qualité retranscription', 'Défini comment chatGPT retranscrits les propos: plutôt fidèle au texte original, ou plutôt en réécrivant en tournure de phrases plus littéraire?', 'select', NULL, 'Fidèle au texte original;Réécriture littéraire light;Réécriture littéraire avancée;Réécriture littéraire et formatage HTML', 'dbObject\\user', 'easymemo', 1);

-- --------------------------------------------------------

--
-- Structure de la table `parcours`
--

CREATE TABLE `parcours` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parcours`
--

INSERT INTO `parcours` (`id`, `title`, `description`, `image`) VALUES
(1, 'Les clés de la Gouvernance Partagée', 'Découvrez les bases nécessaires pour coopérer avec confiance et efficacité dans la gouvernance de votre organisation.', '/img/fondamentaux.png'),
(2, 'Intention et objectifs de la Gouvernance Partagée', 'Découvrez l\'intention derrière la mise en place des outils de la gouvernance partagée et comment celle-ci impacte sur la dynamique coopérative.', '/img/uploads/parcours/orientation.png'),
(3, 'Mieux communiquer au sein des équipes et des organistaions', 'Découvrez comment mieux communiquer au sein de vos équipes, et comment donner du feedback à vos collègues.', '/img/uploads/parcours/communication.png');

-- --------------------------------------------------------

--
-- Structure de la table `parcours_mission`
--

CREATE TABLE `parcours_mission` (
  `id` int(11) NOT NULL,
  `IDparcours` int(11) NOT NULL,
  `IDmission` int(11) NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT 1,
  `branch` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parcours_mission`
--

INSERT INTO `parcours_mission` (`id`, `IDparcours`, `IDmission`, `required`, `branch`) VALUES
(1, 1, 1, 1, NULL),
(2, 1, 2, 1, NULL),
(3, 1, 3, 1, NULL),
(4, 1, 4, 1, 'Branche 1'),
(5, 1, 5, 1, 'Branche 2'),
(6, 1, 6, 1, 'Branche 2'),
(7, 1, 7, 1, NULL),
(8, 1, 8, 1, NULL),
(9, 2, 101, 1, NULL),
(10, 2, 102, 1, NULL),
(11, 2, 103, 1, NULL),
(12, 2, 104, 0, 'bonus'),
(13, 3, 101, 1, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `property`
--

CREATE TABLE `property` (
  `id` int(11) NOT NULL,
  `shortname` varchar(20) NOT NULL COMMENT 'Clé utilisée dans les JSON',
  `name` varchar(255) NOT NULL,
  `IDpropertyformat` int(11) NOT NULL,
  `listitemtype` varchar(20) DEFAULT NULL,
  `listholontypeids` varchar(255) DEFAULT NULL,
  `IDholon_organization` int(11) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `position` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Propriétés assignées à des tempales (holons)';

--
-- Déchargement des données de la table `property`
--

INSERT INTO `property` (`id`, `shortname`, `name`, `IDpropertyformat`, `IDholon_organization`, `datecreation`, `position`, `active`) VALUES
(1, 'rde', 'Raison d\'être', 1, 1, '2024-03-05 16:59:41', NULL, 1),
(2, 'redevability', 'Attendus', 1, 1, '2024-03-05 16:59:41', NULL, 1),
(3, 'domain', 'Domaines d\'autorité', 1, 1, '2024-03-05 16:59:50', NULL, 1),
(4, 'strat', 'Stratégie', 1, 1, '2024-12-03 13:09:48', NULL, 1),
(5, 'rde', 'Raison d\'être', 1, 5, '2024-03-05 16:59:41', 1, 1),
(6, 'redevability', 'Attendus', 1, 5, '2024-03-05 16:59:41', 3, 1),
(7, 'domain', 'Domaines d\'autorité', 1, 5, '2024-03-05 16:59:50', 2, 1),
(8, 'strat', 'Stratégie', 1, 5, '2024-12-03 13:09:48', 4, 1),
(31, 'rde', 'Raison d\'être', 1, 637, '2024-03-05 16:59:41', NULL, 1),
(32, 'redevability', 'Attendus', 1, 637, '2024-03-05 16:59:41', NULL, 1),
(33, 'domain', 'Domaines d\'autorité', 1, 637, '2024-03-05 16:59:50', NULL, 1),
(34, 'strat', 'Stratégie', 1, 637, '2024-12-03 13:09:48', NULL, 1),
(35, 'rde', 'Raison d\'être', 1, 648, '2024-03-05 16:59:41', NULL, 1),
(36, 'redevability', 'Attendus', 1, 648, '2024-03-05 16:59:41', NULL, 1),
(37, 'domain', 'Domaines d\'autorité', 1, 648, '2024-03-05 16:59:50', NULL, 1),
(38, 'strat', 'Stratégie', 1, 648, '2024-12-03 13:09:48', NULL, 1),
(39, 'rde', 'Raison d\'être', 1, 661, '2024-03-05 16:59:41', NULL, 1),
(40, 'redevability', 'Attendus', 1, 661, '2024-03-05 16:59:41', NULL, 1),
(41, 'domain', 'Domaines d\'autorité', 1, 661, '2024-03-05 16:59:50', NULL, 1),
(42, 'strat', 'Stratégie', 1, 661, '2024-12-03 13:09:48', NULL, 1),
(43, 'rde', 'Raison d\'etre', 1, 674, '2026-04-19 16:46:34', 1, 1),
(44, 'redevability', 'Attendus', 1, 674, '2026-04-19 16:46:34', 3, 1),
(45, 'domain', 'Domaines d\'autorite', 1, 674, '2026-04-19 16:46:34', 2, 1),
(46, 'strat', 'Strategie', 1, 674, '2026-04-19 16:46:34', 4, 1);

-- --------------------------------------------------------

--
-- Structure de la table `propertyformat`
--

CREATE TABLE `propertyformat` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Formats autorisés pour les blocs (tels que chaînes, textes libre, liste, case à cocher, etc...)';

--
-- Déchargement des données de la table `propertyformat`
--

INSERT INTO `propertyformat` (`id`, `name`) VALUES
(1, 'Texte libre'),
(2, 'Liste'),
(3, 'Chiffre'),
(4, 'Date');

-- --------------------------------------------------------

--
-- Structure de la table `pv`
--

CREATE TABLE `pv` (
  `id` int(11) NOT NULL,
  `data` mediumtext NOT NULL,
  `IDuser` int(11) NOT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime NOT NULL DEFAULT current_timestamp(),
  `codeaffichage` varchar(200) NOT NULL,
  `codeedition` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `pv`
--

INSERT INTO `pv` (`id`, `data`, `IDuser`, `datecreation`, `datemodification`, `codeaffichage`, `codeedition`) VALUES
(1, 'Data Test', 1, '2026-04-21 12:10:00', '2026-04-21 12:10:00', '', '');

-- --------------------------------------------------------

--
-- Structure de la table `qr`
--

CREATE TABLE `qr` (
  `id` int(11) NOT NULL,
  `uniquekey` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `IDuser` int(11) NOT NULL,
  `shortcut` varchar(255) DEFAULT NULL COMMENT 'Raccourci défini par l''utilisateur (unique pour lui)',
  `description` varchar(255) NOT NULL,
  `cpt` int(11) NOT NULL DEFAULT 0,
  `datelastaccess` datetime DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Se rappeler quand ça a été créé, pour certain affichages',
  `active` int(11) NOT NULL DEFAULT 1 COMMENT 'Permet de désactive temporairement l''élément'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `qr`
--

INSERT INTO `qr` (`id`, `uniquekey`, `url`, `IDuser`, `shortcut`, `description`, `cpt`, `datelastaccess`, `datecreation`, `active`) VALUES
(1, 'WebSite_Home', 'https://org1.opengov.tools/omo/', 1, 'org1', 'Portail Org1', 0, NULL, '2026-04-21 12:00:00', 1);

-- --------------------------------------------------------

--
-- Structure de la table `tips`
--

CREATE TABLE `tips` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `youtube` varchar(500) DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `datemodification` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `translation`
--

CREATE TABLE `translation` (
  `id` int(11) NOT NULL,
  `uid` varchar(200) NOT NULL,
  `value` mediumtext NOT NULL,
  `original` mediumtext DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `cpt` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `typeholon`
--

CREATE TABLE `typeholon` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `hastemplate` tinyint(1) NOT NULL DEFAULT 0,
  `haschild` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `typeholon`
--

INSERT INTO `typeholon` (`id`, `name`, `hastemplate`, `haschild`) VALUES
(1, 'Rôle', 1, 0),
(2, 'Cercle', 1, 1),
(3, 'Groupe', 0, 1),
(4, 'Organisation', 0, 1);

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `lastname` varchar(150) DEFAULT NULL,
  `firstname` varchar(150) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(40) DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateconnexion` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `code` varchar(30) DEFAULT NULL,
  `codeexpiration` datetime DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `param_easypv` mediumtext DEFAULT NULL,
  `param_easymemo` mediumtext DEFAULT NULL,
  `param_easycircle` mediumtext DEFAULT NULL,
  `telegramID` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `email`, `lastname`, `firstname`, `username`, `password`, `datecreation`, `dateconnexion`, `active`, `code`, `codeexpiration`, `parameters`, `param_easypv`, `param_easymemo`, `param_easycircle`, `telegramID`) VALUES
(1, 'admin@omo.test', 'Organization', 'Open', 'Admin', NULL, '2026-04-21 09:01:00', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user_faq_response`
--

CREATE TABLE `user_faq_response` (
  `id` int(11) NOT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `IDfaq` int(11) DEFAULT NULL,
  `IDchoice` int(11) DEFAULT NULL,
  `IDmission` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_holon`
--

CREATE TABLE `user_holon` (
  `id` int(11) NOT NULL,
  `IDuser` int(11) NOT NULL,
  `IDholon` int(11) NOT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateconnexion` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_holon`
--

INSERT INTO `user_holon` (`id`, `IDuser`, `IDholon`, `parameters`, `datecreation`, `dateconnexion`, `active`) VALUES
(1, 1, 1, NULL, '2024-03-05 16:43:15', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `user_login_token`
--

CREATE TABLE `user_login_token` (
  `id` int(11) NOT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `token` varchar(64) DEFAULT NULL,
  `code_hash` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `request_ip` varchar(45) DEFAULT NULL,
  `attempt_count` int(11) NOT NULL DEFAULT 0,
  `used` tinyint(4) DEFAULT 0,
  `remember` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `last_attempt_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_login_token`
--

INSERT INTO `user_login_token` (`id`, `IDuser`, `token`, `code_hash`, `expires_at`, `request_ip`, `attempt_count`, `used`, `remember`, `created_at`, `last_attempt_at`) VALUES
(2, 1, 'ff435c87e6488009a8455830caa4201a015879417cc8ff1de7025f7b33fb765c', '$2y$10$to/GR1O/6J98g1PRCSsZDOkmV2f5ds2.6I0NNdISYVoZ9He0NAA6O', '2026-04-23 14:14:34', '172.19.0.1', 0, 1, 1, '2026-04-23 14:09:34', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user_mission`
--

CREATE TABLE `user_mission` (
  `id` int(11) NOT NULL,
  `IDuser` int(11) NOT NULL,
  `IDmission` int(11) NOT NULL,
  `IDparcours` int(11) NOT NULL,
  `done` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_organization`
--

CREATE TABLE `user_organization` (
  `id` int(11) NOT NULL,
  `IDuser` int(11) NOT NULL,
  `IDorganization` int(11) NOT NULL,
  `username` varchar(250) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `parameters` mediumtext DEFAULT NULL,
  `datecreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateconnexion` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_organization`
--

INSERT INTO `user_organization` (`id`, `IDuser`, `IDorganization`, `username`, `email`, `parameters`, `datecreation`, `dateconnexion`, `active`) VALUES
(1, 1, 1, 'UN1', 'user1@org1.com', NULL, '2026-04-21 12:20:00', NULL, 1),
(2, 1, 2, NULL, NULL, NULL, '2026-04-21 12:25:00', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `user_remember`
--

CREATE TABLE `user_remember` (
  `id` int(11) NOT NULL,
  `IDuser` int(11) DEFAULT NULL,
  `token` varchar(64) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` mediumtext DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_remember`
--

INSERT INTO `user_remember` (`id`, `IDuser`, `token`, `expires_at`, `ip`, `user_agent`, `browser`, `os`, `created_at`) VALUES
(1, 1, '9e7e62a214f4927ce26226cf9d8576c9d7467861a03a217507ad5f8c1cf7dd7c', '2026-05-23 13:59:14', '172.19.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'Chrome', 'Windows', '2026-04-23 11:59:14'),
(2, 1, 'aaa1f5df7bd344491268df922a68381ec463e7be9153450dc3a6f421b09cc2d3', '2026-05-23 14:09:43', '172.19.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'Chrome', 'Windows', '2026-04-23 12:09:43');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `aiprompt`
--
ALTER TABLE `aiprompt`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `alttext`
--
ALTER TABLE `alttext`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_application_hash` (`hash`);

--
-- Index pour la table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `faq_choice`
--
ALTER TABLE `faq_choice`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `holon`
--
ALTER TABLE `holon`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `holonproperty`
--
ALTER TABLE `holonproperty`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mission`
--
ALTER TABLE `mission`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mission_dependencies`
--
ALTER TABLE `mission_dependencies`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mission_faq`
--
ALTER TABLE `mission_faq`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `organization`
--
ALTER TABLE `organization`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `organization_application`
--
ALTER TABLE `organization_application`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_organization_application` (`IDorganization`,`IDapplication`),
  ADD KEY `idx_organization_application_organization` (`IDorganization`),
  ADD KEY `idx_organization_application_application` (`IDapplication`);

--
-- Index pour la table `organization_parcours`
--
ALTER TABLE `organization_parcours`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `parameter`
--
ALTER TABLE `parameter`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `parcours`
--
ALTER TABLE `parcours`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `parcours_mission`
--
ALTER TABLE `parcours_mission`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `property`
--
ALTER TABLE `property`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `propertyformat`
--
ALTER TABLE `propertyformat`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `pv`
--
ALTER TABLE `pv`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `qr`
--
ALTER TABLE `qr`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `tips`
--
ALTER TABLE `tips`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `translation`
--
ALTER TABLE `translation`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `typeholon`
--
ALTER TABLE `typeholon`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user_faq_response`
--
ALTER TABLE `user_faq_response`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user_holon`
--
ALTER TABLE `user_holon`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user_login_token`
--
ALTER TABLE `user_login_token`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user_mission`
--
ALTER TABLE `user_mission`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user_organization`
--
ALTER TABLE `user_organization`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user_remember`
--
ALTER TABLE `user_remember`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `alttext`
--
ALTER TABLE `alttext`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `application`
--
ALTER TABLE `application`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `document`
--
ALTER TABLE `document`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2015;

--
-- AUTO_INCREMENT pour la table `faq`
--
ALTER TABLE `faq`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `faq_choice`
--
ALTER TABLE `faq_choice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `holon`
--
ALTER TABLE `holon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=702;

--
-- AUTO_INCREMENT pour la table `holonproperty`
--
ALTER TABLE `holonproperty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=214;

--
-- AUTO_INCREMENT pour la table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mission`
--
ALTER TABLE `mission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT pour la table `mission_dependencies`
--
ALTER TABLE `mission_dependencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `mission_faq`
--
ALTER TABLE `mission_faq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `organization`
--
ALTER TABLE `organization`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `organization_application`
--
ALTER TABLE `organization_application`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `organization_parcours`
--
ALTER TABLE `organization_parcours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `parameter`
--
ALTER TABLE `parameter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `parcours`
--
ALTER TABLE `parcours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `parcours_mission`
--
ALTER TABLE `parcours_mission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `property`
--
ALTER TABLE `property`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT pour la table `propertyformat`
--
ALTER TABLE `propertyformat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `pv`
--
ALTER TABLE `pv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `qr`
--
ALTER TABLE `qr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `tips`
--
ALTER TABLE `tips`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `translation`
--
ALTER TABLE `translation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `typeholon`
--
ALTER TABLE `typeholon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT pour la table `user_faq_response`
--
ALTER TABLE `user_faq_response`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user_holon`
--
ALTER TABLE `user_holon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `user_login_token`
--
ALTER TABLE `user_login_token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `user_mission`
--
ALTER TABLE `user_mission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT pour la table `user_organization`
--
ALTER TABLE `user_organization`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `user_remember`
--
ALTER TABLE `user_remember`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
