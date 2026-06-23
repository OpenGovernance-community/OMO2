-- OpenMyOrganization
-- Seed de demo pour un parcours de prise en main de la homepage.
-- IDs reserves:
--   parcours: 7102
--   mission: 7131-7145
--   homework: 7221-7235
--   question: 7321-7335
--   question_choice: 7441-7485
--   parcours_mission: 7521-7535
--   mission_homework: 7621-7635
--   mission_question: 7721-7735
--   organization_parcours: 7903-7904
--
-- Ce script suppose que les tables LMS existent deja:
--   parcours, mission, parcours_mission, homework, mission_homework,
--   question, question_choice, mission_question, organization_parcours.
--
-- Ce parcours n ajoute volontairement aucune dependance de mission:
-- toutes les missions sont accessibles en meme temps, groupees par branche.

SET NAMES utf8mb4;

SET @organization_id_1 = (
  SELECT `id`
  FROM `organization`
  ORDER BY `id` ASC
  LIMIT 1
);

SET @organization_id_2 = (
  SELECT `id`
  FROM `organization`
  ORDER BY `id` ASC
  LIMIT 1 OFFSET 1
);

DELETE FROM `user_homework`
WHERE `IDparcours` = 7102
   OR `IDmission` BETWEEN 7131 AND 7145
   OR `IDhomework` BETWEEN 7221 AND 7235;

DELETE FROM `user_mission`
WHERE `IDparcours` = 7102
   OR `IDmission` BETWEEN 7131 AND 7145;

SET @delete_user_question_response = (
  SELECT IF(
    EXISTS (
      SELECT 1
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'user_question_response'
    ),
    'DELETE FROM `user_question_response` WHERE `IDmission` BETWEEN 7131 AND 7145 OR `IDquestion` BETWEEN 7321 AND 7335',
    'SELECT 1'
  )
);
PREPARE stmt FROM @delete_user_question_response;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @delete_user_faq_response = (
  SELECT IF(
    EXISTS (
      SELECT 1
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'user_faq_response'
    ),
    'DELETE FROM `user_faq_response` WHERE `IDmission` BETWEEN 7131 AND 7145 OR `IDfaq` BETWEEN 7321 AND 7335',
    'SELECT 1'
  )
);
PREPARE stmt FROM @delete_user_faq_response;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

DELETE FROM `question_choice`
WHERE `id` BETWEEN 7441 AND 7485;

DELETE FROM `mission_question`
WHERE `id` BETWEEN 7721 AND 7735;

DELETE FROM `mission_homework`
WHERE `id` BETWEEN 7621 AND 7635;

DELETE FROM `parcours_mission`
WHERE `id` BETWEEN 7521 AND 7535;

DELETE FROM `organization_parcours`
WHERE `id` BETWEEN 7903 AND 7904;

DELETE FROM `question`
WHERE `id` BETWEEN 7321 AND 7335;

DELETE FROM `homework`
WHERE `id` BETWEEN 7221 AND 7235;

DELETE FROM `mission`
WHERE `id` BETWEEN 7131 AND 7145;

DELETE FROM `parcours`
WHERE `id` = 7102;

INSERT INTO `parcours` (
  `id`,
  `title`,
  `description`,
  `image`,
  `IDorganization`,
  `IDusercreation`,
  `IDusermodification`,
  `datecreation`,
  `datemodification`,
  `ispublic`,
  `isbasic`
) VALUES (
  7102,
  'Prise en main de la homepage',
  'Parcours de demo pour decouvrir les principaux elements de la homepage et apprendre a naviguer rapidement dans l interface.',
  '/img/uploads/parcours/homepage-prise-en-main.png',
  1,
  1,
  1,
  '2026-05-27 10:00:00',
  '2026-05-27 10:00:00',
  1,
  1
);

INSERT INTO `mission` (
  `id`,
  `title`,
  `resume`,
  `video`,
  `html`,
  `position`,
  `datecreation`,
  `dateupdate`
) VALUES
  (
    7131,
    'Topbar - Edition du profil',
    'Comprendre comment ouvrir et modifier son profil utilisateur depuis la topbar.',
    'https://video.example.test/homepage-01-topbar-profil',
    '<p>Cette mission montre comment acceder rapidement a la fiche profil depuis la topbar.</p><p>Le contenu peut expliquer comment ouvrir le menu utilisateur, modifier les informations utiles puis verifier que les changements sont bien pris en compte dans l interface.</p>',
    10,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  ),
  (
    7132,
    'Topbar - Aide et accompagnement',
    'Trouver les tutoriels, la FAQ et la visite guidee depuis les outils d aide.',
    'https://video.example.test/homepage-02-topbar-aide',
    '<p>Cette mission sert a reperer les ressources d aide disponibles depuis la topbar.</p><p>Le texte peut presenter l acces aux tutoriels, a la FAQ et a la visite guidee afin que la personne sache ou chercher de l aide en autonomie.</p>',
    20,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  ),
  (
    7133,
    'Topbar - Recherche',
    'Utiliser la recherche globale de la topbar pour retrouver rapidement un element.',
    'https://video.example.test/homepage-03-topbar-recherche',
    '<p>Cette mission introduit la recherche accessible dans la topbar.</p><p>Expliquez comment saisir quelques mots-cles, lire les resultats proposes puis ouvrir rapidement l element voulu depuis la zone de recherche.</p>',
    30,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  ),
  (
    7134,
    'Leftbar - Choix des applications',
    'Changer d application depuis la leftbar et comprendre la logique des raccourcis.',
    'https://video.example.test/homepage-04-leftbar-apps',
    '<p>Cette mission presente la leftbar comme point d entree vers les differentes applications du systeme.</p><p>Le texte peut montrer comment basculer entre les apps et comment reconnaitre l application actuellement active.</p>',
    40,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  ),
  (
    7135,
    'Leftbar - Parametres de l organisation',
    'Acceder aux reglages de l organisation depuis la leftbar.',
    'https://video.example.test/homepage-05-leftbar-parametres',
    '<p>Cette mission sert a trouver rapidement les reglages de l organisation active.</p><p>Le contenu peut decrire ou cliquer, quels types de parametres on y retrouve et dans quels cas il vaut mieux verifier ces reglages.</p>',
    50,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  ),
  (
    7136,
    'Context - Fil d ariane',
    'Lire le fil d ariane pour comprendre le niveau courant dans la structure.',
    'https://video.example.test/homepage-06-context-breadcrumb',
    '<p>Cette mission montre comment utiliser le fil d ariane pour se reperer dans l organisation.</p><p>Le texte peut expliquer comment remonter d un niveau, revenir vers un parent et garder une vision claire du contexte courant.</p>',
    60,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  ),
  (
    7137,
    'Context - Menu contextuel',
    'Utiliser le menu contextuel pour ajouter, deplacer ou supprimer un element.',
    'https://video.example.test/homepage-07-context-menu',
    '<p>Cette mission sert a comprendre les actions disponibles autour du holon courant.</p><p>Expliquez comment ouvrir le menu contextuel, identifier les actions sensibles et choisir entre ajout, deplacement ou suppression selon le besoin.</p>',
    70,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  ),
  (
    7138,
    'Context - Lien rapide a copier',
    'Recuperer un lien direct vers le contexte ouvert pour le partager.',
    'https://video.example.test/homepage-08-context-link',
    '<p>Cette mission montre comment copier un lien rapide depuis le contexte courant.</p><p>Le texte peut expliquer dans quels cas ce lien est utile, par exemple pour partager un point precis de la structure avec une autre personne.</p>',
    80,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  ),
  (
    7139,
    'Context - Informations sur le holon',
    'Lire la fiche d information du holon courant.',
    'https://video.example.test/homepage-09-context-holon-info',
    '<p>Cette mission apprend a lire la partie descriptive du contexte.</p><p>Le contenu peut decrire quelles informations sont disponibles sur le holon, comment les interpreter et comment s en servir pour mieux comprendre l element selectionne.</p>',
    90,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  ),
  (
    7140,
    'Context - Liste des membres',
    'Identifier les membres associes au holon courant.',
    'https://video.example.test/homepage-10-context-members',
    '<p>Cette mission sert a lire la liste des membres visibles dans le contexte.</p><p>Le texte peut montrer comment verifier qui est rattache au holon et distinguer la simple consultation des actions de gestion des membres.</p>',
    100,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  ),
  (
    7141,
    'Context - Dependances et navigation vers les enfants',
    'Explorer les liens de dependance et ouvrir les enfants du holon courant.',
    'https://video.example.test/homepage-11-context-dependencies',
    '<p>Cette mission explique comment naviguer vers les elements enfants depuis la zone de contexte.</p><p>Le contenu peut montrer comment lire les dependances affichees puis cliquer pour descendre dans la structure.</p>',
    110,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  ),
  (
    7142,
    'Structure - Conventions et couleurs',
    'Comprendre les conventions visuelles et les couleurs utilisees dans la structure.',
    'https://video.example.test/homepage-12-structure-colors',
    '<p>Cette mission aide a lire la structure plus vite.</p><p>Le texte peut presenter les conventions de representation, la signification des couleurs et les reperes visuels utiles pour distinguer les types d elements.</p>',
    120,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  ),
  (
    7143,
    'Structure - Naviguer avec clic et zoom',
    'Se deplacer dans la structure a l aide du clic et du zoom.',
    'https://video.example.test/homepage-13-structure-zoom',
    '<p>Cette mission montre les gestes de base pour explorer la structure.</p><p>Le contenu peut decrire comment selectionner un element, dezoomer pour prendre du recul puis zoomer a nouveau pour travailler plus finement.</p>',
    130,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  ),
  (
    7144,
    'Structure - Affichage en liste',
    'Basculer sur un affichage en liste quand cela est plus pratique.',
    'https://video.example.test/homepage-14-structure-liste',
    '<p>Cette mission presente l affichage en liste comme alternative a la vue graphique.</p><p>Le texte peut expliquer quand utiliser cette vue et quels cas de navigation ou de verification elle simplifie.</p>',
    140,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  ),
  (
    7145,
    'Structure - Recherche rapide',
    'Retrouver rapidement un element directement depuis la vue structure.',
    'https://video.example.test/homepage-15-structure-recherche',
    '<p>Cette mission complete la navigation de la structure avec une recherche rapide.</p><p>Expliquez comment localiser un element, le faire ressortir visuellement puis l ouvrir pour continuer la navigation.</p>',
    150,
    '2026-05-27 10:00:00',
    '2026-05-27 10:00:00'
  );

INSERT INTO `homework` (
  `id`,
  `title`,
  `detail`,
  `position`,
  `datecreation`,
  `dateupdate`
) VALUES
  (7221, 'Ouvrir et modifier son profil', 'Acceder a son profil depuis la topbar et modifier au moins une information visible.', 10, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7222, 'Trouver les outils d aide', 'Ouvrir la zone d aide et reperer le tuto, la FAQ et la visite guidee.', 20, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7223, 'Faire une recherche globale', 'Lancer une recherche depuis la topbar et ouvrir un resultat pertinent.', 30, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7224, 'Changer d application', 'Passer d une application a une autre depuis la leftbar.', 40, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7225, 'Ouvrir les parametres d organisation', 'Acceder a la page ou au panneau de parametres de l organisation active.', 50, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7226, 'Lire le fil d ariane', 'Utiliser le fil d ariane pour revenir a un niveau parent.', 60, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7227, 'Tester le menu contextuel', 'Ouvrir le menu contextuel et identifier les actions ajouter, deplacer et supprimer.', 70, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7228, 'Copier un lien rapide', 'Copier le lien rapide du contexte courant et le coller dans un bloc note de test.', 80, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7229, 'Lire la fiche du holon', 'Ouvrir un holon et consulter ses informations principales.', 90, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7230, 'Consulter la liste des membres', 'Verifier quels membres sont associes au holon courant.', 100, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7231, 'Naviguer vers un enfant', 'Utiliser la zone de contexte pour ouvrir un element enfant.', 110, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7232, 'Lire les conventions visuelles', 'Identifier au moins deux codes visuels ou couleurs utiles dans la structure.', 120, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7233, 'Utiliser clic et zoom', 'Cliquer sur un element puis zoomer ou dezoomer pour changer de niveau de lecture.', 130, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7234, 'Basculer en affichage liste', 'Passer en vue liste puis revenir a la vue structure.', 140, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7235, 'Faire une recherche rapide dans la structure', 'Retrouver un element avec la recherche rapide de la vue structure.', 150, '2026-05-27 10:00:00', '2026-05-27 10:00:00');

INSERT INTO `question` (
  `id`,
  `IDhowto`,
  `question`,
  `answer`,
  `detail`,
  `displayorder`,
  `isactive`,
  `created`,
  `updated`
) VALUES
  (7321, NULL, 'Ou trouve-t-on l acces a la fiche profil ?', 'Depuis la topbar.', 'La fiche profil doit etre accessible rapidement depuis la topbar afin de modifier les informations personnelles sans changer de contexte principal.', 10, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7322, NULL, 'Que regroupe la zone d aide ?', 'Les tutoriels, la FAQ et la visite guidee.', 'La zone d aide centralise les ressources d accompagnement pour apprendre ou retrouver une information sans quitter la homepage.', 20, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7323, NULL, 'A quoi sert la recherche de la topbar ?', 'A retrouver rapidement un element ou un acces utile.', 'La recherche globale sert a gagner du temps en ouvrant directement un element pertinent depuis la topbar.', 30, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7324, NULL, 'Quel est le role principal de la leftbar pour les applications ?', 'Permettre de changer rapidement d application.', 'La leftbar sert de zone de bascule entre les applications disponibles dans l organisation.', 40, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7325, NULL, 'Pourquoi ouvrir les parametres de l organisation ?', 'Pour consulter ou ajuster les reglages de l espace actif.', 'Les parametres d organisation permettent de verifier la configuration generale de l espace en cours.', 50, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7326, NULL, 'A quoi sert le fil d ariane ?', 'A se reperer et remonter dans la structure.', 'Le fil d ariane permet de comprendre ou l on se trouve et de revenir vers des niveaux parents sans se perdre.', 60, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7327, NULL, 'Que permet le menu contextuel ?', 'Ajouter, deplacer ou supprimer selon les droits disponibles.', 'Le menu contextuel regroupe les actions de gestion autour de l element courant.', 70, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7328, NULL, 'Pourquoi copier un lien rapide ?', 'Pour partager un acces direct vers le contexte courant.', 'Le lien rapide permet d envoyer un point precis de navigation a une autre personne ou de le conserver.', 80, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7329, NULL, 'Que trouve-t-on dans les informations du holon ?', 'Les informations descriptives utiles sur l element selectionne.', 'La zone d information du holon aide a comprendre ce que represente l element courant dans la structure.', 90, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7330, NULL, 'Que montre la liste des membres ?', 'Les personnes rattachees au holon courant.', 'La liste des membres permet de voir qui participe a l element selectionne.', 100, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7331, NULL, 'A quoi servent les dependances visibles dans le contexte ?', 'A naviguer vers les elements enfants ou lies.', 'Les dependances servent de point d entree pour poursuivre la navigation dans la structure.', 110, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7332, NULL, 'Pourquoi apprendre les conventions et couleurs ?', 'Pour lire plus vite la structure et distinguer les types d elements.', 'Les conventions visuelles aident a reconnaitre les categories d elements sans devoir tout relire en detail.', 120, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7333, NULL, 'Quels gestes de base servent a naviguer dans la structure ?', 'Le clic, le zoom et le dezoom.', 'Ces gestes permettent de passer d une lecture globale a une lecture fine de la structure.', 130, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7334, NULL, 'Quand la vue liste est-elle utile ?', 'Quand une lecture lineaire est plus pratique que la vue graphique.', 'La vue liste peut etre plus efficace pour verifier ou parcourir rapidement des elements.', 140, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00'),
  (7335, NULL, 'A quoi sert la recherche rapide de la structure ?', 'A retrouver visuellement un element dans la vue structure.', 'La recherche rapide aide a localiser un element sans parcourir toute la structure manuellement.', 150, 1, '2026-05-27 10:00:00', '2026-05-27 10:00:00');

INSERT INTO `question_choice` (
  `id`,
  `IDquestion`,
  `label`,
  `is_correct`
) VALUES
  (7441, 7321, 'Depuis la topbar', 1),
  (7442, 7321, 'Depuis la vue structure uniquement', 0),
  (7443, 7321, 'Depuis le footer', 0),
  (7444, 7322, 'Tutoriels, FAQ et visite guidee', 1),
  (7445, 7322, 'Seulement les parametres de l organisation', 0),
  (7446, 7322, 'Uniquement la recherche globale', 0),
  (7447, 7323, 'Retrouver rapidement un element ou un acces utile', 1),
  (7448, 7323, 'Supprimer un holon directement', 0),
  (7449, 7323, 'Changer la couleur de tous les cercles', 0),
  (7450, 7324, 'Changer rapidement d application', 1),
  (7451, 7324, 'Modifier automatiquement le profil', 0),
  (7452, 7324, 'Valider les quiz du parcours', 0),
  (7453, 7325, 'Consulter ou ajuster les reglages de l espace actif', 1),
  (7454, 7325, 'Changer de navigateur', 0),
  (7455, 7325, 'Fermer toutes les applications', 0),
  (7456, 7326, 'Se reperer et remonter dans la structure', 1),
  (7457, 7326, 'Lancer la visite guidee', 0),
  (7458, 7326, 'Ouvrir le profil utilisateur', 0),
  (7459, 7327, 'Ajouter, deplacer ou supprimer selon les droits', 1),
  (7460, 7327, 'Afficher uniquement les couleurs de la structure', 0),
  (7461, 7327, 'Creer automatiquement une organisation', 0),
  (7462, 7328, 'Partager un acces direct vers le contexte courant', 1),
  (7463, 7328, 'Deconnecter les membres du holon', 0),
  (7464, 7328, 'Remplacer le fil d ariane', 0),
  (7465, 7329, 'Les informations descriptives de l element selectionne', 1),
  (7466, 7329, 'Uniquement les quiz du parcours', 0),
  (7467, 7329, 'Les applications disponibles dans la leftbar', 0),
  (7468, 7330, 'Les personnes rattachees au holon courant', 1),
  (7469, 7330, 'Les seules personnes administratrices du site', 0),
  (7470, 7330, 'Les videos de demo de la mission', 0),
  (7471, 7331, 'Naviguer vers les elements enfants ou lies', 1),
  (7472, 7331, 'Modifier le mot de passe du compte', 0),
  (7473, 7331, 'Trier la FAQ par date', 0),
  (7474, 7332, 'Lire plus vite la structure et distinguer les types d elements', 1),
  (7475, 7332, 'Rendre toutes les missions obligatoires', 0),
  (7476, 7332, 'Desactiver la vue liste', 0),
  (7477, 7333, 'Le clic, le zoom et le dezoom', 1),
  (7478, 7333, 'Le scroll horizontal uniquement', 0),
  (7479, 7333, 'Le copier coller des liens rapides', 0),
  (7480, 7334, 'Quand une lecture lineaire est plus pratique', 1),
  (7481, 7334, 'Seulement quand aucun membre n est associe', 0),
  (7482, 7334, 'Uniquement pour modifier le profil', 0),
  (7483, 7335, 'Retrouver visuellement un element dans la vue structure', 1),
  (7484, 7335, 'Changer les couleurs de tous les holons', 0),
  (7485, 7335, 'Supprimer les dependances affichees', 0);

INSERT INTO `parcours_mission` (
  `id`,
  `IDparcours`,
  `IDmission`,
  `required`,
  `branch`
) VALUES
  (7521, 7102, 7131, 1, 'Topbar'),
  (7522, 7102, 7132, 1, 'Topbar'),
  (7523, 7102, 7133, 1, 'Topbar'),
  (7524, 7102, 7134, 1, 'Leftbar'),
  (7525, 7102, 7135, 1, 'Leftbar'),
  (7526, 7102, 7136, 1, 'Context'),
  (7527, 7102, 7137, 1, 'Context'),
  (7528, 7102, 7138, 1, 'Context'),
  (7529, 7102, 7139, 1, 'Context'),
  (7530, 7102, 7140, 1, 'Context'),
  (7531, 7102, 7141, 1, 'Context'),
  (7532, 7102, 7142, 1, 'Structure'),
  (7533, 7102, 7143, 1, 'Structure'),
  (7534, 7102, 7144, 1, 'Structure'),
  (7535, 7102, 7145, 1, 'Structure');

INSERT INTO `mission_homework` (
  `id`,
  `IDmission`,
  `IDhomework`,
  `position`
) VALUES
  (7621, 7131, 7221, 10),
  (7622, 7132, 7222, 10),
  (7623, 7133, 7223, 10),
  (7624, 7134, 7224, 10),
  (7625, 7135, 7225, 10),
  (7626, 7136, 7226, 10),
  (7627, 7137, 7227, 10),
  (7628, 7138, 7228, 10),
  (7629, 7139, 7229, 10),
  (7630, 7140, 7230, 10),
  (7631, 7141, 7231, 10),
  (7632, 7142, 7232, 10),
  (7633, 7143, 7233, 10),
  (7634, 7144, 7234, 10),
  (7635, 7145, 7235, 10);

INSERT INTO `mission_question` (
  `id`,
  `IDmission`,
  `IDquestion`,
  `position`
) VALUES
  (7721, 7131, 7321, 10),
  (7722, 7132, 7322, 10),
  (7723, 7133, 7323, 10),
  (7724, 7134, 7324, 10),
  (7725, 7135, 7325, 10),
  (7726, 7136, 7326, 10),
  (7727, 7137, 7327, 10),
  (7728, 7138, 7328, 10),
  (7729, 7139, 7329, 10),
  (7730, 7140, 7330, 10),
  (7731, 7141, 7331, 10),
  (7732, 7142, 7332, 10),
  (7733, 7143, 7333, 10),
  (7734, 7144, 7334, 10),
  (7735, 7145, 7335, 10);

INSERT INTO `organization_parcours` (
  `id`,
  `IDorganization`,
  `IDparcours`,
  `position`,
  `everybody`
) SELECT
  7903,
  @organization_id_1,
  7102,
  12,
  1
WHERE @organization_id_1 IS NOT NULL;

INSERT INTO `organization_parcours` (
  `id`,
  `IDorganization`,
  `IDparcours`,
  `position`,
  `everybody`
) SELECT
  7904,
  @organization_id_2,
  7102,
  13,
  1
WHERE @organization_id_2 IS NOT NULL
  AND @organization_id_2 <> @organization_id_1;

SELECT
  p.`id`,
  p.`title`,
  COUNT(DISTINCT pm.`IDmission`) AS `missions`,
  COUNT(DISTINCT mh.`IDhomework`) AS `homeworks`,
  COUNT(DISTINCT mq.`IDquestion`) AS `questions`
FROM `parcours` p
LEFT JOIN `parcours_mission` pm
  ON pm.`IDparcours` = p.`id`
LEFT JOIN `mission_homework` mh
  ON mh.`IDmission` = pm.`IDmission`
LEFT JOIN `mission_question` mq
  ON mq.`IDmission` = pm.`IDmission`
WHERE p.`id` = 7102
GROUP BY p.`id`, p.`title`;
