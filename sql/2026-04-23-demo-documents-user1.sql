-- OpenMyOrganization
-- Jeu de données fictif pour tester l'affichage des documents OMO
-- Tous les documents sont associés à l'utilisateur 1
--
-- Usage conseillé :
--   mariadb -u <user> -p <database> < sql/2026-04-23-demo-documents-user1.sql
--
-- Ce script utilise des IDs dédiés (2001 à 2014) pour pouvoir être rejoué.

SET NAMES utf8mb4;

DELETE FROM `document`
WHERE `id` BETWEEN 2001 AND 2014;

INSERT INTO `document` (
  `id`,
  `title`,
  `description`,
  `content`,
  `keywords`,
  `IDuser`,
  `IDorganization`,
  `IDholon`,
  `datecreation`,
  `datemodification`,
  `version`,
  `codeview`,
  `codeedit`
) VALUES
  (
    2001,
    'Point rapide de coordination',
    'Notes prises aujourd''hui après un échange de synchronisation.',
    '<h2>Décisions du jour</h2><p>Validation du plan de travail pour la semaine et clarification des priorités immédiates.</p>',
    'coordination,priorités,équipe',
    1,
    1,
    NULL,
    '2026-04-23 09:12:00',
    '2026-04-23 09:30:00',
    1,
    '',
    ''
  ),
  (
    2002,
    'Compte rendu atelier bénévoles',
    'Résumé de l’atelier avec les bénévoles organisé hier soir.',
    '<p>Retour sur les besoins d’accueil, la répartition des rôles et les prochaines disponibilités.</p>',
    'atelier,bénévoles,association',
    1,
    1,
    NULL,
    '2026-04-22 19:15:00',
    '2026-04-22 19:45:00',
    1,
    '',
    ''
  ),
  (
    2003,
    'Liste des actions urgentes',
    'Document de travail pour les tâches à finaliser avant la fin de semaine.',
    '<ul><li>Relancer les partenaires</li><li>Valider le budget</li><li>Préparer la réunion du lundi</li></ul>',
    'actions,urgent,suivi',
    1,
    1,
    NULL,
    '2026-04-20 08:05:00',
    '2026-04-20 08:20:00',
    1,
    '',
    ''
  ),
  (
    2004,
    'Préparation réunion trimestrielle',
    'Première trame pour la réunion de gouvernance du trimestre.',
    '<p>Ordre du jour provisoire, points de vigilance et sujets à arbitrer.</p>',
    'réunion,gouvernance,trimestre',
    1,
    1,
    NULL,
    '2026-04-16 14:40:00',
    '2026-04-17 07:55:00',
    1,
    '',
    ''
  ),
  (
    2005,
    'Retour sur les inscriptions',
    'Analyse rapide du rythme des inscriptions et des canaux les plus efficaces.',
    '<p>Les recommandations portent sur la simplification du formulaire et le rappel des échéances.</p>',
    'inscriptions,analyse,communication',
    1,
    1,
    NULL,
    '2026-04-10 11:10:00',
    '2026-04-10 11:55:00',
    1,
    '',
    ''
  ),
  (
    2006,
    'Synthèse du mois de mars',
    'Vue d’ensemble des dossiers ouverts et des points encore bloqués.',
    '<p>Document récapitulatif pour garder une trace des arbitrages en cours.</p>',
    'mars,synthèse,dossiers',
    1,
    1,
    NULL,
    '2026-03-28 17:05:00',
    '2026-03-29 09:00:00',
    1,
    '',
    ''
  ),
  (
    2007,
    'Organisation de la journée portes ouvertes',
    'Document de cadrage pour la préparation de l’événement du printemps.',
    '<p>Planning, matériel, besoins d’accueil et répartition des responsabilités.</p>',
    'événement,portes ouvertes,planning',
    1,
    1,
    NULL,
    '2026-03-05 15:25:00',
    '2026-03-06 08:10:00',
    1,
    '',
    ''
  ),
  (
    2008,
    'Suivi budget février',
    'Point intermédiaire sur les dépenses et les engagements en cours.',
    '<p>Mise à jour des postes sensibles et des arbitrages à prendre avant clôture.</p>',
    'budget,finances,février',
    1,
    1,
    NULL,
    '2026-02-14 10:30:00',
    '2026-02-14 12:00:00',
    1,
    '',
    ''
  ),
  (
    2009,
    'Plan de communication hiver',
    'Proposition de calendrier éditorial et de messages clés.',
    '<p>Inclut une série de publications, une newsletter et une relance ciblée des membres.</p>',
    'communication,newsletter,planning',
    1,
    1,
    NULL,
    '2026-01-18 09:45:00',
    '2026-01-18 10:15:00',
    1,
    '',
    ''
  ),
  (
    2010,
    'Bilan de fin d’année',
    'Résumé des projets terminés et des enseignements tirés.',
    '<p>Le document recense les réussites, les points à améliorer et quelques pistes pour l’année suivante.</p>',
    'bilan,année,rétrospective',
    1,
    1,
    NULL,
    '2025-12-19 16:20:00',
    '2025-12-20 09:10:00',
    1,
    '',
    ''
  ),
  (
    2011,
    'Compte rendu rentrée associative',
    'Notes prises au moment de la reprise des activités de septembre.',
    '<p>Reprise des permanences, remise à jour des contacts et coordination de l’accueil.</p>',
    'association,rentrée,organisation',
    1,
    1,
    NULL,
    '2025-09-08 18:35:00',
    '2025-09-08 19:00:00',
    1,
    '',
    ''
  ),
  (
    2012,
    'Préparation séminaire d’été',
    'Liste des besoins logistiques et des sujets à traiter pendant le séminaire.',
    '<p>Repas, hébergement, ateliers et coordination de l’animation.</p>',
    'séminaire,été,logistique',
    1,
    1,
    NULL,
    '2025-07-01 13:50:00',
    '2025-07-02 08:30:00',
    1,
    '',
    ''
  ),
  (
    2013,
    'Feuille de route printemps 2025',
    'Première version de la feuille de route pour les mois à venir.',
    '<p>Définition des priorités, clarification des ressources disponibles et répartition des responsabilités.</p>',
    'feuille de route,stratégie,printemps',
    1,
    1,
    NULL,
    '2025-05-21 10:05:00',
    '2025-05-21 10:40:00',
    1,
    '',
    ''
  ),
  (
    2014,
    'Carnet de bord lancement annuel',
    'Notes de cadrage prises au début du cycle annuel précédent.',
    '<p>Objectifs de départ, points d’attention et premiers engagements opérationnels.</p>',
    'lancement,année,cadrage',
    1,
    1,
    NULL,
    '2025-04-24 08:40:00',
    '2025-04-24 09:15:00',
    1,
    '',
    ''
  );

SELECT
  `id`,
  `title`,
  `IDuser`,
  `IDorganization`,
  `IDholon`,
  `datecreation`,
  `datemodification`
FROM `document`
WHERE `id` BETWEEN 2001 AND 2014
ORDER BY `datecreation` DESC;
