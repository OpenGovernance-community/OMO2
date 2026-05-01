-- OpenMyOrganization
-- Jeu de données fictif pour tester l'affichage de la FAQ OMO
--
-- Usage conseillé :
--   mariadb -u <user> -p <database> < sql/2026-05-01-demo-faq.sql
--
-- Ce script utilise des IDs dédiés (3001 à 3006) pour pouvoir être rejoué.

SET NAMES utf8mb4;

DELETE FROM `faq`
WHERE `id` BETWEEN 3001 AND 3006;

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
    3001,
    NULL,
    'À quoi sert OMO ?',
    'OMO aide à visualiser la structure d''une organisation et à naviguer entre ses rôles, cercles et outils.',
    '<p>OMO permet de représenter les cercles, les rôles et différents objets utiles à la gouvernance partagée.</p><p>On peut s''en servir pour clarifier qui fait quoi, explorer la structure de l''organisation et accéder plus facilement aux différents espaces de travail.</p>',
    10,
    1,
    12,
    '2026-05-01 09:00:00',
    '2026-05-01 09:00:00'
  ),
  (
    3002,
    NULL,
    'Comment rechercher un rôle ou un cercle ?',
    'Utilisez la recherche dans la barre du haut pour filtrer rapidement les rôles, cercles ou outils visibles.',
    '<p>Le champ de recherche de la topbar permet de taper quelques mots-clés.</p><p>Les résultats visibles à l''écran sont alors filtrés pour vous aider à retrouver rapidement un cercle, un rôle ou un outil déjà chargé dans l''interface.</p>',
    20,
    1,
    5,
    '2026-05-01 09:05:00',
    '2026-05-01 09:05:00'
  ),
  (
    3003,
    NULL,
    'Pourquoi je ne vois pas certaines organisations ?',
    'Vous ne voyez que les organisations auxquelles votre compte a accès.',
    '<p>Si une organisation n''apparaît pas dans votre liste, cela signifie généralement que votre utilisateur n''est pas encore rattaché à cet espace.</p><p>Dans ce cas, il faut demander à une personne administratrice de vous ajouter à l''organisation concernée.</p>',
    30,
    1,
    3,
    '2026-05-01 09:10:00',
    '2026-05-01 09:10:00'
  ),
  (
    3004,
    NULL,
    'À quoi sert la visite guidée ?',
    'La visite guidée présente les principales zones de l''interface et explique le rôle des boutons visibles.',
    '<p>La visite guidée est utile lors d''une première prise en main.</p><p>Elle met en évidence les zones importantes de l''application, décrit le fonctionnement général et aide à comprendre comment naviguer entre contexte, structure et outils.</p>',
    40,
    1,
    2,
    '2026-05-01 09:15:00',
    '2026-05-01 09:15:00'
  ),
  (
    3005,
    NULL,
    'Comment modifier mon profil ?',
    'Ouvrez le menu Profil dans la barre du haut puis choisissez l''édition du profil.',
    '<p>Depuis ce panneau, vous pouvez mettre à jour vos informations générales et, selon le contexte, vos informations spécifiques à l''organisation active.</p><p>Cela peut inclure le nom affiché, l''adresse e-mail ou la photo de profil.</p>',
    50,
    1,
    1,
    '2026-05-01 09:20:00',
    '2026-05-01 09:20:00'
  ),
  (
    3006,
    NULL,
    'Que faire si une page ne se charge pas correctement ?',
    'Commencez par recharger la page, puis vérifiez que vous êtes toujours connecté à la bonne organisation.',
    '<p>Si le problème persiste, notez le contexte exact: organisation ouverte, écran affiché, action déclenchée et éventuel message d''erreur.</p><p>Ces informations aideront beaucoup pour reproduire et corriger le souci.</p>',
    60,
    1,
    0,
    '2026-05-01 09:25:00',
    '2026-05-01 09:25:00'
  );

SELECT
  `id`,
  `question`,
  `displayorder`,
  `isactive`,
  `viewcount`
FROM `faq`
WHERE `id` BETWEEN 3001 AND 3006
ORDER BY `displayorder` ASC, `id` ASC;
