-- OpenMyOrganization
-- Jeu de données fictif pour tester la FAQ liée à la navigation entre cercles et rôles
--
-- Usage conseillé :
--   mariadb -u <user> -p <database> < sql/2026-05-01-demo-faq-navigation.sql
--
-- Ce script utilise des IDs dédiés (3021 à 3028) pour pouvoir être rejoué.

SET NAMES utf8mb4;

DELETE FROM `faq`
WHERE `id` BETWEEN 3021 AND 3028;

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
    3021,
    NULL,
    'Comment naviguer dans la structure en cercles et rôles ?',
    'Cliquez sur un cercle, un groupe ou un rôle pour le centrer et afficher ses informations dans le panneau latéral.',
    '<p>La navigation se fait directement dans la structure visuelle.</p><p>Quand vous sélectionnez un nœud, OMO met à jour le contexte affiché, recharge les informations utiles et vous permet de poursuivre l''exploration depuis cet élément.</p>',
    210,
    1,
    0,
    '2026-05-01 15:00:00',
    '2026-05-01 15:00:00'
  ),
  (
    3022,
    NULL,
    'Quelle est la différence entre un cercle, un groupe et un rôle ?',
    'Un cercle structure un périmètre, un groupe rassemble des éléments à l''intérieur d''un cercle, et un rôle décrit une responsabilité plus précise.',
    '<p>Les cercles servent souvent de conteneurs principaux dans l''organisation.</p><p>Les groupes permettent d''organiser plus finement certains éléments à l''intérieur d''un cercle, tandis que les rôles représentent des fonctions ou responsabilités portées par une ou plusieurs personnes.</p>',
    220,
    1,
    0,
    '2026-05-01 15:05:00',
    '2026-05-01 15:05:00'
  ),
  (
    3023,
    NULL,
    'Comment revenir au cercle parent après avoir ouvert un rôle ?',
    'Sélectionnez le cercle parent dans la structure ou utilisez la navigation qui recharge le contexte sur le nœud supérieur.',
    '<p>Lorsqu''un rôle est ouvert, l''interface garde la trace de son emplacement dans la structure.</p><p>Vous pouvez donc revenir au cercle qui le contient en cliquant directement sur ce cercle dans le canevas ou en utilisant les éléments de navigation déjà présents dans l''écran.</p>',
    230,
    1,
    0,
    '2026-05-01 15:10:00',
    '2026-05-01 15:10:00'
  ),
  (
    3024,
    NULL,
    'Pourquoi le panneau de gauche change-t-il quand je clique dans la structure ?',
    'Le panneau de gauche affiche toujours les informations du nœud actuellement sélectionné.',
    '<p>Le canevas et le panneau latéral sont synchronisés.</p><p>Quand vous cliquez sur un cercle, un groupe ou un rôle, OMO met à jour le contexte courant pour montrer son nom, ses propriétés, ses membres éventuels et les actions disponibles à cet endroit.</p>',
    240,
    1,
    0,
    '2026-05-01 15:15:00',
    '2026-05-01 15:15:00'
  ),
  (
    3025,
    NULL,
    'Pourquoi certains rôles ou cercles n''apparaissent-ils pas au même niveau ?',
    'Leur position dépend de la structure réelle de l''organisation et du cercle ou groupe auquel ils sont rattachés.',
    '<p>Un rôle n''est pas affiché au hasard dans le schéma.</p><p>Sa place dépend de son parent dans l''arborescence. Si un rôle se trouve dans un groupe ou dans un sous-cercle, il apparaîtra plus loin dans la navigation que les éléments définis directement au niveau du cercle principal.</p>',
    250,
    1,
    0,
    '2026-05-01 15:20:00',
    '2026-05-01 15:20:00'
  ),
  (
    3026,
    NULL,
    'Comment retrouver rapidement un rôle dans une grande organisation ?',
    'Utilisez la recherche et combinez-la avec la navigation visuelle pour recentrer la structure sur l''élément voulu.',
    '<p>Dans une organisation complexe, le plus simple est souvent de commencer par la recherche.</p><p>Une fois le rôle ou le cercle retrouvé, l''ouverture de ce nœud permet de repositionner l''interface sur le bon contexte et de poursuivre la navigation à partir de là.</p>',
    260,
    1,
    0,
    '2026-05-01 15:25:00',
    '2026-05-01 15:25:00'
  ),
  (
    3027,
    NULL,
    'À quoi sert la vue structure par rapport à une vue liste ?',
    'La vue structure aide à comprendre les relations entre cercles, groupes et rôles, alors qu''une vue liste est souvent plus pratique pour parcourir des entrées de manière linéaire.',
    '<p>La structure visuelle montre qui dépend de qui et où se situe chaque élément dans l''organisation.</p><p>Une vue liste peut être plus simple pour chercher rapidement un nom, mais elle montre moins bien les liens hiérarchiques et les emboîtements entre nœuds.</p>',
    270,
    1,
    0,
    '2026-05-01 15:30:00',
    '2026-05-01 15:30:00'
  ),
  (
    3028,
    NULL,
    'Pourquoi l''écran se recentre-t-il sur un nœud après certaines actions ?',
    'Le recentrage permet de garder visible le nœud concerné après une création, une modification ou un changement de contexte.',
    '<p>Quand vous créez ou modifiez un élément, OMO peut recharger les données puis repositionner la structure.</p><p>Cela évite de perdre le fil de la navigation et aide à vérifier immédiatement que le cercle ou le rôle attendu apparaît bien au bon endroit.</p>',
    280,
    1,
    0,
    '2026-05-01 15:35:00',
    '2026-05-01 15:35:00'
  );

SELECT
  `id`,
  `question`,
  `displayorder`,
  `isactive`,
  `viewcount`
FROM `faq`
WHERE `id` BETWEEN 3021 AND 3028
ORDER BY `displayorder` ASC, `id` ASC;
