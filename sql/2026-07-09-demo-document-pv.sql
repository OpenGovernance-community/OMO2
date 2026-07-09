-- OpenMyOrganization
-- Demo PV document for the OMO documents viewer
--
-- Usage:
--   mariadb -u <user> -p <database> < sql/2026-07-09-demo-document-pv.sql
--
-- This script targets the demo organization seeded in Docker:
--   - organization 1
--   - user 1
--   - holons 678, 679, 686, 687
--
-- Dedicated IDs:
--   - tensions: 9301 to 9302
--   - document: 2301
--   - points: 2311 to 2313
--   - holon links: 2321 to 2324
--   - tension links: 2331 to 2333

SET NAMES utf8mb4;

DELETE FROM `document_pv_point_tension`
WHERE `id` BETWEEN 2331 AND 2333
   OR `IDdocument_pv_point` BETWEEN 2311 AND 2313;

DELETE FROM `document_pv_point_holon`
WHERE `id` BETWEEN 2321 AND 2324
   OR `IDdocument_pv_point` BETWEEN 2311 AND 2313;

DELETE FROM `document_pv_point`
WHERE `id` BETWEEN 2311 AND 2313
   OR `IDdocument` = 2301;

DELETE FROM `document`
WHERE `id` = 2301;

DELETE FROM `tension`
WHERE `id` BETWEEN 9301 AND 9302;

INSERT INTO `tension` (
  `id`,
  `IDorganization`,
  `IDholon`,
  `IDuser`,
  `title`,
  `description`,
  `datecreation`,
  `datemodification`,
  `active`
) VALUES
  (
    9301,
    1,
    687,
    1,
    'Suivi budget',
    'Besoin de clarifier la projection budgetaire du prochain trimestre et les arbitrages a venir.',
    '2026-07-09 08:40:00',
    '2026-07-09 08:40:00',
    1
  ),
  (
    9302,
    1,
    686,
    1,
    'Charge equipe',
    'Question ouverte sur la charge de travail actuelle et la repartition entre marketing et administration.',
    '2026-07-09 08:45:00',
    '2026-07-09 08:45:00',
    1
  );

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
  `codeedit`,
  `documenttype`
) VALUES (
  2301,
  'PV gouvernance 09.07.2026',
  'Premier PV de demonstration pour verifier le viewer des points a l ordre du jour.',
  '',
  'pv,reunion,gouvernance',
  1,
  1,
  678,
  '2026-07-09 09:00:00',
  '2026-07-09 11:15:00',
  1,
  '',
  '',
  'pv'
);

INSERT INTO `document_pv_point` (
  `id`,
  `IDdocument`,
  `title`,
  `IDuser_author`,
  `IDholon_concerned`,
  `content`,
  `position`,
  `desired_duration_minutes`,
  `actual_duration_minutes`,
  `pointtype`,
  `active`,
  `datecreation`,
  `datemodification`
) VALUES
  (
    2311,
    2301,
    'Budget',
    1,
    686,
    '<p>Presentation rapide du cadrage budgetaire du trimestre.</p><p>Un point de vigilance reste ouvert sur la marge de securite disponible.</p>',
    1,
    10,
    8,
    'information',
    1,
    '2026-07-09 09:05:00',
    '2026-07-09 09:15:00'
  ),
  (
    2312,
    2301,
    'Campagne ete',
    1,
    687,
    '<p>Consultation sur le rythme de diffusion et le niveau d effort soutenable pour l equipe.</p><ul><li>Besoin de sequence courte</li><li>Besoin de relais internes</li></ul>',
    2,
    20,
    24,
    'consultation',
    1,
    '2026-07-09 09:20:00',
    '2026-07-09 09:50:00'
  ),
  (
    2313,
    2301,
    'Validation',
    1,
    678,
    '<p>Decision prise: valider le lancement d un test de deux semaines avec suivi budgetaire hebdomadaire.</p>',
    3,
    15,
    12,
    'decision',
    1,
    '2026-07-09 10:00:00',
    '2026-07-09 10:20:00'
  );

INSERT INTO `document_pv_point_holon` (
  `id`,
  `IDdocument_pv_point`,
  `IDholon`,
  `position`
) VALUES
  (2321, 2311, 679, 1),
  (2322, 2312, 686, 1),
  (2323, 2312, 679, 2),
  (2324, 2313, 687, 1);

INSERT INTO `document_pv_point_tension` (
  `id`,
  `IDdocument_pv_point`,
  `IDtension`,
  `position`
) VALUES
  (2331, 2311, 9301, 1),
  (2332, 2312, 9302, 1),
  (2333, 2313, 9301, 1);

SELECT
  d.`id` AS `document_id`,
  d.`title`,
  d.`documenttype`,
  p.`id` AS `point_id`,
  p.`position`,
  p.`title` AS `point_title`,
  p.`pointtype`
FROM `document` d
LEFT JOIN `document_pv_point` p
  ON p.`IDdocument` = d.`id`
WHERE d.`id` = 2301
ORDER BY p.`position` ASC, p.`id` ASC;
