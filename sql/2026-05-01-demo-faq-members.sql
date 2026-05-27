-- OpenMyOrganization
-- Jeu de données fictif pour tester la FAQ liée à l'ajout de membres
--
-- Usage conseillé :
--   mariadb -u <user> -p <database> < sql/2026-05-01-demo-faq-members.sql
--
-- Ce script utilise des IDs dédiés (3011 à 3018) pour pouvoir être rejoué.

SET NAMES utf8mb4;

DELETE FROM `faq`
WHERE `id` BETWEEN 3011 AND 3018;

INSERT INTO `faq` (
  `id`,
  `IDhowto`,
  `question`,
  `answer`,
  `detail`,
  `displayorder`,
  `isactive`,
  `viewcount`,
  `created`,
  `updated`
) VALUES
  (
    3011,
    NULL,
    'Comment ajouter une personne à mon organisation ?',
    'Ouvrez le holon de type organisation puis utilisez le bouton + dans la zone Membres.',
    '<p>Depuis le holon racine de l''organisation, le bouton <strong>+</strong> permet d''ajouter une personne comme membre de l''organisation.</p><p>Vous pouvez choisir un membre déjà présent dans l''organisation ou saisir une nouvelle adresse e-mail.</p>',
    110,
    1,
    0,
    '2026-05-01 14:00:00',
    '2026-05-01 14:00:00'
  ),
  (
    3012,
    NULL,
    'Quelle est la différence entre ajouter quelqu''un à l''organisation et à un cercle ?',
    'L''organisation donne l''accès général, tandis qu''un cercle ou un rôle précise l''implication dans la structure.',
    '<p>Être ajouté à l''organisation signifie que la personne devient membre de cet espace global.</p><p>Être ajouté à un cercle, un groupe ou un rôle permet ensuite de préciser où cette personne est impliquée dans la structure holarchique.</p>',
    120,
    1,
    0,
    '2026-05-01 14:05:00',
    '2026-05-01 14:05:00'
  ),
  (
    3013,
    NULL,
    'Pourquoi un membre apparaît-il en grisé ?',
    'Un affichage grisé indique généralement qu''une invitation est encore en attente de confirmation.',
    '<p>Lorsqu''une personne n''a pas encore confirmé son entrée dans l''organisation, elle peut rester visible mais apparaître en grisé.</p><p>Cela permet de distinguer les membres actifs des ajouts encore en attente de validation.</p>',
    130,
    1,
    0,
    '2026-05-01 14:10:00',
    '2026-05-01 14:10:00'
  ),
  (
    3014,
    NULL,
    'Que se passe-t-il si j''ajoute une adresse e-mail qui n''existe pas encore ?',
    'Un profil minimal est créé puis une invitation peut être envoyée pour confirmer l''adhésion.',
    '<p>Si l''adresse n''est liée à aucun utilisateur existant, OMO crée un profil minimal avec cette adresse e-mail.</p><p>Ce profil peut ensuite être rattaché à l''organisation et aux holons visés, en attente de confirmation si nécessaire.</p>',
    140,
    1,
    0,
    '2026-05-01 14:15:00',
    '2026-05-01 14:15:00'
  ),
  (
    3015,
    NULL,
    'Faut-il envoyer une nouvelle invitation pour chaque cercle ajouté ?',
    'Non, une seule invitation d''organisation peut suffire, même si la personne a été ajoutée à plusieurs holons.',
    '<p>Quand une personne est ajoutée à plusieurs cercles avant d''avoir confirmé son adhésion, OMO peut regrouper cela dans une même invitation.</p><p>La confirmation valide alors en une seule fois l''entrée dans l''organisation et les différents holons concernés.</p>',
    150,
    1,
    0,
    '2026-05-01 14:20:00',
    '2026-05-01 14:20:00'
  ),
  (
    3016,
    NULL,
    'Pourquoi certaines personnes sont-elles proposées dans la liste déroulante et pas d''autres ?',
    'La liste déroulante reprend d''abord les personnes déjà rattachées à l''organisation active.',
    '<p>Quand vous ajoutez un membre depuis un holon, la liste propose en priorité les profils déjà connus dans l''organisation.</p><p>Si la personne n''y figure pas encore, vous pouvez utiliser le champ e-mail pour créer ou rattacher un profil supplémentaire.</p>',
    160,
    1,
    0,
    '2026-05-01 14:25:00',
    '2026-05-01 14:25:00'
  ),
  (
    3017,
    NULL,
    'Pourquoi je vois le message qu''une invitation a déjà reçu une réponse ?',
    'Cela signifie que le lien ouvert n''est plus actif, soit parce qu''il a déjà été accepté ou refusé, soit parce qu''une invitation plus récente existe.',
    '<p>Un lien d''invitation n''est pas réutilisable indéfiniment.</p><p>Si vous ouvrez un ancien lien, OMO peut indiquer qu''une réponse a déjà été donnée. Dans ce cas, il faut vérifier si la personne est déjà activée ou, si besoin, générer une nouvelle invitation.</p>',
    170,
    1,
    0,
    '2026-05-01 14:30:00',
    '2026-05-01 14:30:00'
  ),
  (
    3018,
    NULL,
    'Puis-je ajouter quelqu''un à un rôle sans l''ajouter manuellement à l''organisation d''abord ?',
    'Oui, OMO peut gérer le rattachement à l''organisation automatiquement si nécessaire.',
    '<p>Quand vous ajoutez une personne directement à un rôle, un cercle ou un groupe, OMO vérifie d''abord son lien avec l''organisation.</p><p>Si ce lien n''existe pas encore ou n''est pas confirmé, le système prépare l''adhésion globale puis l''association au bon endroit dans la structure.</p>',
    180,
    1,
    0,
    '2026-05-01 14:35:00',
    '2026-05-01 14:35:00'
  );

SELECT
  `id`,
  `question`,
  `displayorder`,
  `isactive`,
  `viewcount`
FROM `faq`
WHERE `id` BETWEEN 3011 AND 3018
ORDER BY `displayorder` ASC, `id` ASC;
