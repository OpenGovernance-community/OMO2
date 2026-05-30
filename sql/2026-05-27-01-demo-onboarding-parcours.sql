-- OpenMyOrganization
-- Seed de demo pour un parcours de premiere prise en main du logiciel.
-- IDs reserves:
--   parcours: 7101
--   mission: 7111-7120
--   homework: 7201-7210
--   question: 7301-7310
--   question_choice: 7401-7430
--   parcours_mission: 7501-7510
--   mission_homework: 7601-7610
--   mission_question: 7701-7710
--   mission_dependencies: 7801-7810
--   organization_parcours: 7901-7902
--
-- Ce script suppose que les tables LMS existent deja:
--   parcours, mission, parcours_mission, homework, mission_homework,
--   question, question_choice, mission_question, mission_dependencies,
--   organization_parcours.

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
WHERE `IDparcours` = 7101
   OR `IDmission` BETWEEN 7111 AND 7120
   OR `IDhomework` BETWEEN 7201 AND 7210;

DELETE FROM `user_mission`
WHERE `IDparcours` = 7101
   OR `IDmission` BETWEEN 7111 AND 7120;

SET @delete_user_question_response = (
  SELECT IF(
    EXISTS (
      SELECT 1
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'user_question_response'
    ),
    'DELETE FROM `user_question_response` WHERE `IDmission` BETWEEN 7111 AND 7120 OR `IDquestion` BETWEEN 7301 AND 7310',
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
    'DELETE FROM `user_faq_response` WHERE `IDmission` BETWEEN 7111 AND 7120 OR `IDfaq` BETWEEN 7301 AND 7310',
    'SELECT 1'
  )
);
PREPARE stmt FROM @delete_user_faq_response;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

DELETE FROM `question_choice`
WHERE `id` BETWEEN 7401 AND 7430;

DELETE FROM `mission_question`
WHERE `id` BETWEEN 7701 AND 7710;

DELETE FROM `mission_homework`
WHERE `id` BETWEEN 7601 AND 7610;

DELETE FROM `mission_dependencies`
WHERE `id` BETWEEN 7801 AND 7810;

DELETE FROM `parcours_mission`
WHERE `id` BETWEEN 7501 AND 7510;

DELETE FROM `organization_parcours`
WHERE `id` BETWEEN 7901 AND 7902;

DELETE FROM `question`
WHERE `id` BETWEEN 7301 AND 7310;

DELETE FROM `homework`
WHERE `id` BETWEEN 7201 AND 7210;

DELETE FROM `mission`
WHERE `id` BETWEEN 7111 AND 7120;

DELETE FROM `parcours`
WHERE `id` = 7101;

INSERT INTO `parcours` (
  `id`,
  `title`,
  `description`,
  `image`
) VALUES (
  7101,
  'Premiere prise en main du logiciel',
  'Parcours de demo pour guider un nouvel utilisateur depuis la creation du compte jusqu a la mise en place des premiers droits d administration.',
  '/img/uploads/parcours/premiere-prise-en-main.png'
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
    7111,
    'Se connecter et creer son compte',
    'Creer un compte par e-mail, passer le capcha et valider son adresse pour acceder au logiciel.',
    'https://video.example.test/onboarding-01-compte',
    '<p>Cette premiere mission sert a entrer dans le logiciel dans de bonnes conditions.</p><p>Montrez comment ouvrir la page de connexion, choisir la creation de compte, renseigner l e-mail, passer le capcha puis confirmer le compte via le lien recu par e-mail.</p><p>Le but est que la personne arrive sur une session active et sache comment revenir se connecter ensuite.</p>',
    10,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7112,
    'Creer sa premiere organisation',
    'Initialiser une organisation et renseigner ses parametres de base.',
    'https://video.example.test/onboarding-02-organisation',
    '<p>Dans cette mission, l utilisateur cree son premier espace d organisation.</p><p>Le texte peut expliquer quels champs sont obligatoires, comment choisir un nom clair, verifier le domaine ou l identite visuelle de base, puis enregistrer l organisation.</p><p>On peut aussi rappeler qu il est utile de verifier tout de suite les reglages minimums avant de passer a la structure.</p>',
    20,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7113,
    'Creer une premiere structure depuis un modele',
    'Partir d un modele predefini pour poser rapidement un premier groupe.',
    'https://video.example.test/onboarding-03-structure-modele',
    '<p>Cette mission montre la voie la plus rapide pour obtenir une premiere structure exploitable.</p><p>Expliquez comment choisir un modele existant, verifier ce qu il contient, le rattacher au bon endroit puis enregistrer le nouveau groupe cree a partir de ce modele.</p><p>Cette branche peut servir de point de depart pour les personnes qui veulent avancer vite avec un cadre deja prepare.</p>',
    30,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7114,
    'Creer une premiere structure vide',
    'Poser un autre groupe en partant de zero pour comprendre la logique de construction.',
    'https://video.example.test/onboarding-04-structure-vide',
    '<p>Ici, l utilisateur cree une structure sans modele pour comprendre les bases de la construction manuelle.</p><p>Le texte peut decrire le choix du type de structure, le nom du groupe, le rattachement dans l organisation et la validation de la creation.</p><p>Cette mission est parallele a la mission sur le modele predefini afin de montrer que les deux approches peuvent coexister.</p>',
    31,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7115,
    'Creer des modeles de holons de base',
    'Definir les premiers modeles de roles et de cercles utiles au groupe cree depuis un modele.',
    'https://video.example.test/onboarding-05-modeles-base',
    '<p>Cette etape sert a fabriquer les briques reutilisables de base.</p><p>Montrez comment creer au moins un modele de cercle et un modele de role, quels champs ont du sens au minimum, puis comment les sauvegarder pour un usage ulterieur.</p><p>Le but est de faire comprendre la difference entre un modele et un element concret de la structure.</p>',
    40,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7116,
    'Modifier des modeles de holons existants',
    'Ajuster les modeles de base sur la branche creee sans modele.',
    'https://video.example.test/onboarding-06-modeles-modification',
    '<p>Une fois les premiers modeles en place, cette mission montre comment les faire evoluer.</p><p>Expliquez comment retrouver un modele, le renommer, corriger ses proprietes ou ajuster sa description afin qu il corresponde mieux a l usage reel du groupe.</p><p>Cette etape aide a faire passer l idee que les modeles sont vivants et doivent suivre les besoins de l organisation.</p>',
    41,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7117,
    'Creer des roles structurels',
    'Ajouter les premiers roles concrets dans la structure de l organisation.',
    'https://video.example.test/onboarding-07-roles-structurels',
    '<p>Cette mission traduit les modeles en elements concrets de la structure.</p><p>Le contenu peut montrer comment choisir le bon cercle parent, creer un role structurel, renseigner son nom et sa raison d etre, puis verifier son affichage dans la structure.</p><p>Apres cette etape, l organisation commence a ressembler a un espace reellement utilisable.</p>',
    50,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7118,
    'Ajouter des membres a l organisation',
    'Inviter des personnes ou rattacher des membres deja connus a l organisation.',
    'https://video.example.test/onboarding-08-membres-organisation',
    '<p>Cette mission prepare l arrivee des personnes dans l espace de travail.</p><p>Expliquez les deux methodes principales: envoyer une invitation par e-mail ou ajouter un membre deja present dans la base.</p><p>Le texte peut aussi rappeler comment suivre l etat d une invitation et verifier qu un membre est bien rattache a l organisation.</p>',
    60,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7119,
    'Ajouter des membres aux cercles et roles',
    'Associer les bonnes personnes aux bons endroits dans la structure.',
    'https://video.example.test/onboarding-09-affectations',
    '<p>Maintenant que les membres existent dans l organisation, cette mission montre comment les affecter.</p><p>Le contenu peut decrire l ajout d une personne dans un cercle, puis dans un role, et la verification visuelle de sa presence au bon endroit.</p><p>C est une etape importante pour comprendre la difference entre appartenance a l organisation et implication dans la structure.</p>',
    70,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7120,
    'Definir des statuts d admin',
    'Donner les bons droits d administration aux bonnes personnes.',
    'https://video.example.test/onboarding-10-admin',
    '<p>La derniere mission sert a clarifier la gouvernance de l espace.</p><p>Expliquez comment ouvrir les parametres ou la fiche membre adequate, attribuer un statut d administration puis verifier l effet attendu sur les droits d acces.</p><p>Vous pouvez aussi rappeler qu il vaut mieux limiter ces droits aux personnes qui en ont vraiment besoin.</p>',
    80,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  );

INSERT INTO `homework` (
  `id`,
  `title`,
  `detail`,
  `position`,
  `datecreation`,
  `dateupdate`
) VALUES
  (
    7201,
    'Valider le compte par e-mail',
    'Creer un compte avec une adresse e-mail de test, passer le capcha et cliquer sur le lien de validation recu par e-mail.',
    10,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7202,
    'Renseigner les parametres de base de l organisation',
    'Creer une organisation avec son nom, son identite minimale et les reglages de base necessaires a la demo.',
    20,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7203,
    'Creer un groupe depuis un modele',
    'Choisir un modele predefini, creer une structure a partir de celui-ci et verifier le resultat dans l organisation.',
    30,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7204,
    'Creer un groupe vide',
    'Ajouter un second groupe en partant de zero pour comparer la methode manuelle avec l approche par modele.',
    40,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7205,
    'Creer un modele de cercle et un modele de role',
    'Ajouter au moins deux modeles reutilisables: un cercle de base et un role de base.',
    50,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7206,
    'Modifier un modele existant',
    'Ouvrir un modele deja cree, changer au moins un libelle ou une propriete utile, puis enregistrer.',
    60,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7207,
    'Ajouter un role structurel concret',
    'Creer un role structurel dans la structure et verifier qu il apparait au bon endroit.',
    70,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7208,
    'Ajouter ou inviter des membres',
    'Ajouter au moins deux personnes a l organisation en utilisant une invitation e-mail ou un rattachement direct.',
    80,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7209,
    'Affecter des membres a la structure',
    'Associer au moins un membre a un cercle et un autre a un role pour valider le principe d affectation.',
    90,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7210,
    'Attribuer un statut d admin',
    'Choisir une personne de confiance, lui donner un statut d administration et verifier que le droit est bien enregistre.',
    100,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  );

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
  (
    7301,
    NULL,
    'Quel est l objectif principal de la premiere mission ?',
    'Creer un compte actif et confirme pour pouvoir utiliser le logiciel.',
    'La premiere mission doit permettre a la personne de se connecter avec un compte fonctionnel, valide par e-mail, afin d acceder ensuite a l organisation et au parcours.',
    10,
    1,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7302,
    NULL,
    'Que faut-il faire juste apres la creation de la premiere organisation ?',
    'Verifier et renseigner ses parametres de base.',
    'Le but n est pas seulement de creer l organisation, mais aussi de lui donner des parametres minimums exploitables pour la suite de la demo.',
    20,
    1,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7303,
    NULL,
    'Pourquoi creer une structure depuis un modele predefini ?',
    'Pour obtenir rapidement une premiere base deja structuree.',
    'La mission sur le modele predefini sert a gagner du temps et a montrer comment reutiliser une structure preparee a l avance.',
    30,
    1,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7304,
    NULL,
    'Quel est l interet de creer une structure vide ?',
    'Comprendre la logique de construction manuelle en partant de zero.',
    'Cette etape complete l approche par modele et aide a comprendre ce qui se passe quand on construit une structure sans point de depart preconfigure.',
    40,
    1,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7305,
    NULL,
    'A quoi servent les modeles de holons de base ?',
    'A preparer des briques reutilisables pour les futurs cercles et roles.',
    'Les modeles permettent de normaliser la creation des elements de structure et d eviter de reconfigurer les memes bases a chaque fois.',
    50,
    1,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7306,
    NULL,
    'Pourquoi modifier un modele existant ?',
    'Pour l adapter au besoin reel du groupe ou de l organisation.',
    'Un modele n est pas fige. Il peut evoluer pour mieux correspondre aux usages observes pendant la mise en place de la structure.',
    60,
    1,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7307,
    NULL,
    'Que represente un role structurel dans ce parcours ?',
    'Un element concret ajoute dans la structure de l organisation.',
    'A ce stade, on n est plus au niveau du modele: on cree un role reel, place dans un cercle ou un groupe de la structure.',
    70,
    1,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7308,
    NULL,
    'Quelles methodes permettent d ajouter des membres a l organisation ?',
    'On peut envoyer une invitation ou rattacher directement un membre deja connu.',
    'Cette question peut etre configuree en choix multiples pour valider les deux methodes presentees pendant la mission.',
    80,
    1,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7309,
    NULL,
    'Quelle difference faut-il comprendre entre un membre de l organisation et un membre d un role ?',
    'L organisation donne l acces global, le cercle ou le role precise l implication dans la structure.',
    'Cette distinction est importante pour ne pas confondre appartenance generale a l espace et affectation precise dans la structure.',
    90,
    1,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  ),
  (
    7310,
    NULL,
    'Quel principe doit guider l attribution d un statut d admin ?',
    'Donner ce droit seulement aux personnes qui en ont reellement besoin.',
    'La mission finale rappelle que les droits d administration doivent rester limites, explicites et verifies.',
    100,
    1,
    '2026-05-27 09:00:00',
    '2026-05-27 09:00:00'
  );

INSERT INTO `question_choice` (
  `id`,
  `IDquestion`,
  `label`,
  `is_correct`
) VALUES
  (7401, 7301, 'Creer un compte et confirmer son e-mail', 1),
  (7402, 7301, 'Creer directement un role structurel', 0),
  (7403, 7301, 'Attribuer un statut d admin avant toute chose', 0),
  (7404, 7302, 'Verifier les parametres de base de l organisation', 1),
  (7405, 7302, 'Creer des invitations pour tous les membres', 0),
  (7406, 7302, 'Supprimer les modeles existants', 0),
  (7407, 7303, 'Gagner du temps avec une base deja preparee', 1),
  (7408, 7303, 'Eviter toute configuration initiale a vie', 0),
  (7409, 7303, 'Remplacer la creation de l organisation', 0),
  (7410, 7304, 'Comprendre la construction manuelle de la structure', 1),
  (7411, 7304, 'Contourner la creation du compte utilisateur', 0),
  (7412, 7304, 'Desactiver les groupes existants', 0),
  (7413, 7305, 'Preparer des briques reutilisables de cercle et de role', 1),
  (7414, 7305, 'Ajouter automatiquement des membres dans tous les groupes', 0),
  (7415, 7305, 'Transformer un membre en administrateur', 0),
  (7416, 7306, 'Adapter un modele aux besoins reels', 1),
  (7417, 7306, 'Rendre obligatoire un statut d admin pour tous', 0),
  (7418, 7306, 'Supprimer toute trace du modele original', 0),
  (7419, 7307, 'Un role concret place dans la structure', 1),
  (7420, 7307, 'Une simple etiquette sans impact dans l organisation', 0),
  (7421, 7307, 'Un compte utilisateur temporaire', 0),
  (7422, 7308, 'Envoyer une invitation par e-mail', 1),
  (7423, 7308, 'Rattacher directement un membre deja connu', 1),
  (7424, 7308, 'Modifier le logo de l organisation', 0),
  (7425, 7309, 'Le membre de l organisation a un acces global, le role precise son implication', 1),
  (7426, 7309, 'Il n y a aucune difference entre les deux', 0),
  (7427, 7309, 'Le role remplace l organisation comme espace principal', 0),
  (7428, 7310, 'Attribuer le droit d admin seulement quand c est necessaire', 1),
  (7429, 7310, 'Donner ce droit a toutes les personnes invitees', 0),
  (7430, 7310, 'Utiliser ce droit pour remplacer les affectations de roles', 0);

INSERT INTO `parcours_mission` (
  `id`,
  `IDparcours`,
  `IDmission`,
  `required`,
  `branch`
) VALUES
  (7501, 7101, 7111, 1, NULL),
  (7502, 7101, 7112, 1, NULL),
  (7503, 7101, 7113, 1, 'groupe_modele'),
  (7504, 7101, 7114, 1, 'groupe_vide'),
  (7505, 7101, 7115, 1, 'groupe_modele'),
  (7506, 7101, 7116, 1, 'groupe_vide'),
  (7507, 7101, 7117, 1, NULL),
  (7508, 7101, 7118, 1, NULL),
  (7509, 7101, 7119, 1, NULL),
  (7510, 7101, 7120, 1, NULL);

INSERT INTO `mission_homework` (
  `id`,
  `IDmission`,
  `IDhomework`,
  `position`
) VALUES
  (7601, 7111, 7201, 10),
  (7602, 7112, 7202, 10),
  (7603, 7113, 7203, 10),
  (7604, 7114, 7204, 10),
  (7605, 7115, 7205, 10),
  (7606, 7116, 7206, 10),
  (7607, 7117, 7207, 10),
  (7608, 7118, 7208, 10),
  (7609, 7119, 7209, 10),
  (7610, 7120, 7210, 10);

INSERT INTO `mission_question` (
  `id`,
  `IDmission`,
  `IDquestion`,
  `position`
) VALUES
  (7701, 7111, 7301, 10),
  (7702, 7112, 7302, 10),
  (7703, 7113, 7303, 10),
  (7704, 7114, 7304, 10),
  (7705, 7115, 7305, 10),
  (7706, 7116, 7306, 10),
  (7707, 7117, 7307, 10),
  (7708, 7118, 7308, 10),
  (7709, 7119, 7309, 10),
  (7710, 7120, 7310, 10);

INSERT INTO `mission_dependencies` (
  `id`,
  `IDmission_parent`,
  `IDmission_child`,
  `IDparcours`,
  `required`
) VALUES
  (7801, 7111, 7112, 7101, 1),
  (7802, 7112, 7113, 7101, 1),
  (7803, 7112, 7114, 7101, 1),
  (7804, 7113, 7115, 7101, 1),
  (7805, 7114, 7116, 7101, 1),
  (7806, 7115, 7117, 7101, 0),
  (7807, 7116, 7117, 7101, 0),
  (7808, 7117, 7118, 7101, 1),
  (7809, 7118, 7119, 7101, 1),
  (7810, 7119, 7120, 7101, 1);

INSERT INTO `organization_parcours` (
  `id`,
  `IDorganization`,
  `IDparcours`,
  `position`,
  `everybody`
) SELECT
  7901,
  @organization_id_1,
  7101,
  10,
  1
WHERE @organization_id_1 IS NOT NULL;

INSERT INTO `organization_parcours` (
  `id`,
  `IDorganization`,
  `IDparcours`,
  `position`,
  `everybody`
) SELECT
  7902,
  @organization_id_2,
  7101,
  11,
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
WHERE p.`id` = 7101
GROUP BY p.`id`, p.`title`;
