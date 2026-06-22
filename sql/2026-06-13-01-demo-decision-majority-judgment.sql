-- OpenMyOrganization
-- Jeu de donnees fictif pour tester une prise de decision OMO en jugement majoritaire
--
-- Contenu :
--   - 1 prise de decision rattachee a l organisation 1, creee par le membre 1
--   - 3 blocs / groupes
--   - 5 propositions par bloc
--   - 15 participants externes invites par e-mail
--   - 45 reponses soumises (15 participants x 3 blocs) avec mentions pseudo-aleatoires
--
-- Usage conseille :
--   mariadb -u <user> -p <database> < sql/2026-06-13-01-demo-decision-majority-judgment.sql
--
-- Ce script utilise des IDs dedies (6100 a 6263) pour pouvoir etre rejoue.

SET NAMES utf8mb4;

DELETE FROM `decision_result`
WHERE `id` BETWEEN 6261 AND 6263
   OR `IDdecision_process` = 6100;

DELETE FROM `decision_response`
WHERE `id` BETWEEN 6201 AND 6255
   OR `IDdecision_process` = 6100;

DELETE FROM `decision_invitation`
WHERE `id` BETWEEN 6161 AND 6175
   OR `IDdecision_process` = 6100;

DELETE FROM `decision_participant`
WHERE `id` BETWEEN 6140 AND 6155
   OR `IDdecision_process` = 6100;

DELETE FROM `decision_proposal`
WHERE `id` BETWEEN 6121 AND 6135
   OR `IDdecision_process` = 6100;

DELETE FROM `decision_group`
WHERE `id` BETWEEN 6111 AND 6113
   OR `IDdecision_process` = 6100;

DELETE FROM `decision_process`
WHERE `id` = 6100;

INSERT INTO `decision_process` (
    `id`,
    `IDorganization`,
    `IDholon`,
    `IDuser`,
    `title`,
    `description`,
    `decision_type`,
    `status`,
    `evaluation_method`,
    `parameters`,
    `consultation_start_at`,
    `consultation_end_at`,
    `evaluation_start_at`,
    `evaluation_end_at`,
    `results_published_at`,
    `archived_at`,
    `created_at`,
    `updated_at`
) VALUES (
    6100,
    1,
    NULL,
    1,
    'Demo MJ - choix multi blocs',
    'Jeu de test pour le module de jugement majoritaire avec trois blocs, quinze participants externes et des reponses generees automatiquement.',
    'decision',
    'results',
    'majority_judgment',
    '{"majority_judgment":{"is_anonymous":0,"allow_consultation_proposals":0,"scale_size":7},"seed":"demo_majority_judgment_multi_block"}',
    '2026-06-05 09:00:00',
    '2026-06-08 18:00:00',
    '2026-06-08 18:15:00',
    '2026-06-11 18:00:00',
    '2026-06-12 09:30:00',
    NULL,
    '2026-06-01 10:00:00',
    '2026-06-12 09:30:00'
);

INSERT INTO `decision_group` (
    `id`,
    `IDdecision_process`,
    `decision_type`,
    `evaluation_method`,
    `title`,
    `description`,
    `parameters`,
    `position`,
    `active`,
    `created_at`,
    `updated_at`
) VALUES
    (
        6111,
        6100,
        'decision',
        'majority_judgment',
        'Bloc 1 - Lieu de rencontre',
        'Premier bloc de test avec cinq options de lieu.',
        '{"majority_judgment":{"is_anonymous":0,"allow_consultation_proposals":0,"scale_size":7}}',
        1,
        1,
        '2026-06-01 10:05:00',
        '2026-06-12 09:30:00'
    ),
    (
        6112,
        6100,
        'decision',
        'majority_judgment',
        'Bloc 2 - Format de session',
        'Deuxieme bloc de test avec cinq options de format.',
        '{"majority_judgment":{"is_anonymous":0,"allow_consultation_proposals":0,"scale_size":7}}',
        2,
        1,
        '2026-06-01 10:06:00',
        '2026-06-12 09:30:00'
    ),
    (
        6113,
        6100,
        'decision',
        'majority_judgment',
        'Bloc 3 - Niveau de budget',
        'Troisieme bloc de test avec cinq options de budget.',
        '{"majority_judgment":{"is_anonymous":0,"allow_consultation_proposals":0,"scale_size":7}}',
        3,
        1,
        '2026-06-01 10:07:00',
        '2026-06-12 09:30:00'
    );

INSERT INTO `decision_proposal` (
    `id`,
    `IDdecision_process`,
    `IDdecision_group`,
    `title`,
    `description`,
    `info_url`,
    `position`,
    `parameters`,
    `active`,
    `created_at`,
    `updated_at`
) VALUES
    (6121, 6100, 6111, 'Salle centrale', 'Option de lieu 1 pour le bloc 1.', NULL, 1, '{"seed":"demo_mj"}', 1, '2026-06-01 10:10:00', '2026-06-12 09:30:00'),
    (6122, 6100, 6111, 'Atelier nord', 'Option de lieu 2 pour le bloc 1.', NULL, 2, '{"seed":"demo_mj"}', 1, '2026-06-01 10:11:00', '2026-06-12 09:30:00'),
    (6123, 6100, 6111, 'Maison commune', 'Option de lieu 3 pour le bloc 1.', NULL, 3, '{"seed":"demo_mj"}', 1, '2026-06-01 10:12:00', '2026-06-12 09:30:00'),
    (6124, 6100, 6111, 'Jardin couvert', 'Option de lieu 4 pour le bloc 1.', NULL, 4, '{"seed":"demo_mj"}', 1, '2026-06-01 10:13:00', '2026-06-12 09:30:00'),
    (6125, 6100, 6111, 'Studio mobile', 'Option de lieu 5 pour le bloc 1.', NULL, 5, '{"seed":"demo_mj"}', 1, '2026-06-01 10:14:00', '2026-06-12 09:30:00'),
    (6126, 6100, 6112, 'Table ronde', 'Option de format 1 pour le bloc 2.', NULL, 1, '{"seed":"demo_mj"}', 1, '2026-06-01 10:15:00', '2026-06-12 09:30:00'),
    (6127, 6100, 6112, 'Atelier en binomes', 'Option de format 2 pour le bloc 2.', NULL, 2, '{"seed":"demo_mj"}', 1, '2026-06-01 10:16:00', '2026-06-12 09:30:00'),
    (6128, 6100, 6112, 'Forum ouvert', 'Option de format 3 pour le bloc 2.', NULL, 3, '{"seed":"demo_mj"}', 1, '2026-06-01 10:17:00', '2026-06-12 09:30:00'),
    (6129, 6100, 6112, 'Session hybride', 'Option de format 4 pour le bloc 2.', NULL, 4, '{"seed":"demo_mj"}', 1, '2026-06-01 10:18:00', '2026-06-12 09:30:00'),
    (6130, 6100, 6112, 'Parcours libre', 'Option de format 5 pour le bloc 2.', NULL, 5, '{"seed":"demo_mj"}', 1, '2026-06-01 10:19:00', '2026-06-12 09:30:00'),
    (6131, 6100, 6113, 'Budget tres sobre', 'Option de budget 1 pour le bloc 3.', NULL, 1, '{"seed":"demo_mj"}', 1, '2026-06-01 10:20:00', '2026-06-12 09:30:00'),
    (6132, 6100, 6113, 'Budget sobre', 'Option de budget 2 pour le bloc 3.', NULL, 2, '{"seed":"demo_mj"}', 1, '2026-06-01 10:21:00', '2026-06-12 09:30:00'),
    (6133, 6100, 6113, 'Budget equilibre', 'Option de budget 3 pour le bloc 3.', NULL, 3, '{"seed":"demo_mj"}', 1, '2026-06-01 10:22:00', '2026-06-12 09:30:00'),
    (6134, 6100, 6113, 'Budget confortable', 'Option de budget 4 pour le bloc 3.', NULL, 4, '{"seed":"demo_mj"}', 1, '2026-06-01 10:23:00', '2026-06-12 09:30:00'),
    (6135, 6100, 6113, 'Budget ambitieux', 'Option de budget 5 pour le bloc 3.', NULL, 5, '{"seed":"demo_mj"}', 1, '2026-06-01 10:24:00', '2026-06-12 09:30:00');

INSERT INTO `decision_participant` (
    `id`,
    `IDdecision_process`,
    `IDuser`,
    `email`,
    `display_name`,
    `role`,
    `status`,
    `access_token`,
    `parameters`,
    `active`,
    `invitation_sent_at`,
    `invitation_opened_at`,
    `created_at`,
    `updated_at`
) VALUES
    (6140, 6100, 1, NULL, 'Owner demo', 'owner', 'active', NULL, '{"seed":"demo_mj_owner"}', 1, NULL, NULL, '2026-06-01 10:30:00', '2026-06-12 09:30:00'),
    (6141, 6100, NULL, 'participant01@test.com', 'Participant 01', 'participant', 'active', 'demo-mj-p-01', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:00:00', '2026-06-04 08:10:00', '2026-06-01 10:31:00', '2026-06-12 09:30:00'),
    (6142, 6100, NULL, 'participant02@test.com', 'Participant 02', 'participant', 'active', 'demo-mj-p-02', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:01:00', '2026-06-04 08:11:00', '2026-06-01 10:32:00', '2026-06-12 09:30:00'),
    (6143, 6100, NULL, 'participant03@test.com', 'Participant 03', 'participant', 'active', 'demo-mj-p-03', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:02:00', '2026-06-04 08:12:00', '2026-06-01 10:33:00', '2026-06-12 09:30:00'),
    (6144, 6100, NULL, 'participant04@test.com', 'Participant 04', 'participant', 'active', 'demo-mj-p-04', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:03:00', '2026-06-04 08:13:00', '2026-06-01 10:34:00', '2026-06-12 09:30:00'),
    (6145, 6100, NULL, 'participant05@test.com', 'Participant 05', 'participant', 'active', 'demo-mj-p-05', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:04:00', '2026-06-04 08:14:00', '2026-06-01 10:35:00', '2026-06-12 09:30:00'),
    (6146, 6100, NULL, 'participant06@test.com', 'Participant 06', 'participant', 'active', 'demo-mj-p-06', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:05:00', '2026-06-04 08:15:00', '2026-06-01 10:36:00', '2026-06-12 09:30:00'),
    (6147, 6100, NULL, 'participant07@test.com', 'Participant 07', 'participant', 'active', 'demo-mj-p-07', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:06:00', '2026-06-04 08:16:00', '2026-06-01 10:37:00', '2026-06-12 09:30:00'),
    (6148, 6100, NULL, 'participant08@test.com', 'Participant 08', 'participant', 'active', 'demo-mj-p-08', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:07:00', '2026-06-04 08:17:00', '2026-06-01 10:38:00', '2026-06-12 09:30:00'),
    (6149, 6100, NULL, 'participant09@test.com', 'Participant 09', 'participant', 'active', 'demo-mj-p-09', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:08:00', '2026-06-04 08:18:00', '2026-06-01 10:39:00', '2026-06-12 09:30:00'),
    (6150, 6100, NULL, 'participant10@test.com', 'Participant 10', 'participant', 'active', 'demo-mj-p-10', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:09:00', '2026-06-04 08:19:00', '2026-06-01 10:40:00', '2026-06-12 09:30:00'),
    (6151, 6100, NULL, 'participant11@test.com', 'Participant 11', 'participant', 'active', 'demo-mj-p-11', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:10:00', '2026-06-04 08:20:00', '2026-06-01 10:41:00', '2026-06-12 09:30:00'),
    (6152, 6100, NULL, 'participant12@test.com', 'Participant 12', 'participant', 'active', 'demo-mj-p-12', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:11:00', '2026-06-04 08:21:00', '2026-06-01 10:42:00', '2026-06-12 09:30:00'),
    (6153, 6100, NULL, 'participant13@test.com', 'Participant 13', 'participant', 'active', 'demo-mj-p-13', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:12:00', '2026-06-04 08:22:00', '2026-06-01 10:43:00', '2026-06-12 09:30:00'),
    (6154, 6100, NULL, 'participant14@test.com', 'Participant 14', 'participant', 'active', 'demo-mj-p-14', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:13:00', '2026-06-04 08:23:00', '2026-06-01 10:44:00', '2026-06-12 09:30:00'),
    (6155, 6100, NULL, 'participant15@test.com', 'Participant 15', 'participant', 'active', 'demo-mj-p-15', '{"seed":"demo_mj_email"}', 1, '2026-06-04 08:14:00', '2026-06-04 08:24:00', '2026-06-01 10:45:00', '2026-06-12 09:30:00');

INSERT INTO `decision_invitation` (
    `id`,
    `IDdecision_process`,
    `IDholon`,
    `IDuser`,
    `email`,
    `display_name`,
    `invitation_type`,
    `status`,
    `parameters`,
    `active`,
    `created_at`,
    `updated_at`
) VALUES
    (6161, 6100, NULL, NULL, 'participant01@test.com', 'Participant 01', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:00:00', '2026-06-04 08:10:00'),
    (6162, 6100, NULL, NULL, 'participant02@test.com', 'Participant 02', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:01:00', '2026-06-04 08:11:00'),
    (6163, 6100, NULL, NULL, 'participant03@test.com', 'Participant 03', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:02:00', '2026-06-04 08:12:00'),
    (6164, 6100, NULL, NULL, 'participant04@test.com', 'Participant 04', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:03:00', '2026-06-04 08:13:00'),
    (6165, 6100, NULL, NULL, 'participant05@test.com', 'Participant 05', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:04:00', '2026-06-04 08:14:00'),
    (6166, 6100, NULL, NULL, 'participant06@test.com', 'Participant 06', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:05:00', '2026-06-04 08:15:00'),
    (6167, 6100, NULL, NULL, 'participant07@test.com', 'Participant 07', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:06:00', '2026-06-04 08:16:00'),
    (6168, 6100, NULL, NULL, 'participant08@test.com', 'Participant 08', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:07:00', '2026-06-04 08:17:00'),
    (6169, 6100, NULL, NULL, 'participant09@test.com', 'Participant 09', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:08:00', '2026-06-04 08:18:00'),
    (6170, 6100, NULL, NULL, 'participant10@test.com', 'Participant 10', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:09:00', '2026-06-04 08:19:00'),
    (6171, 6100, NULL, NULL, 'participant11@test.com', 'Participant 11', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:10:00', '2026-06-04 08:20:00'),
    (6172, 6100, NULL, NULL, 'participant12@test.com', 'Participant 12', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:11:00', '2026-06-04 08:21:00'),
    (6173, 6100, NULL, NULL, 'participant13@test.com', 'Participant 13', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:12:00', '2026-06-04 08:22:00'),
    (6174, 6100, NULL, NULL, 'participant14@test.com', 'Participant 14', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:13:00', '2026-06-04 08:23:00'),
    (6175, 6100, NULL, NULL, 'participant15@test.com', 'Participant 15', 'email', 'accepted', '{"seed":"demo_mj_email_invitation"}', 1, '2026-06-04 08:14:00', '2026-06-04 08:24:00');

INSERT INTO `decision_response` (
    `id`,
    `IDdecision_process`,
    `IDdecision_group`,
    `IDdecision_participant`,
    `status`,
    `parameters`,
    `submitted_at`,
    `created_at`,
    `updated_at`
)
SELECT
    6200 + ((seed.`group_position` - 1) * 20) + seed.`participant_index` AS `id`,
    6100 AS `IDdecision_process`,
    seed.`group_id` AS `IDdecision_group`,
    seed.`participant_id` AS `IDdecision_participant`,
    'submitted' AS `status`,
    CONCAT(
        '{"majority_judgment":{"scores":{',
        GROUP_CONCAT(
            CONCAT('"', seed.`proposal_id`, '":', seed.`score`)
            ORDER BY seed.`proposal_position` ASC
            SEPARATOR ','
        ),
        '},"details":{',
        GROUP_CONCAT(
            CONCAT(
                '"', seed.`proposal_id`, '":{"score":', seed.`score`,
                ',"mention":"', seed.`mention`,
                '","position":', seed.`proposal_position`,
                ',"title":"', REPLACE(REPLACE(seed.`proposal_title`, '\\', '\\\\'), '"', '\\"'),
                '"}'
            )
            ORDER BY seed.`proposal_position` ASC
            SEPARATOR ','
        ),
        '}}}'
    ) AS `parameters`,
    DATE_ADD('2026-06-11 09:00:00', INTERVAL ((seed.`group_position` - 1) * 90) + (seed.`participant_index` * 11) MINUTE) AS `submitted_at`,
    DATE_ADD('2026-06-11 08:30:00', INTERVAL ((seed.`group_position` - 1) * 90) + (seed.`participant_index` * 11) MINUTE) AS `created_at`,
    DATE_ADD('2026-06-11 09:00:00', INTERVAL ((seed.`group_position` - 1) * 90) + (seed.`participant_index` * 11) MINUTE) AS `updated_at`
FROM (
    SELECT
        grp.`id` AS `group_id`,
        grp.`position` AS `group_position`,
        participant.`id` AS `participant_id`,
        participant.`id` - 6140 AS `participant_index`,
        proposal.`id` AS `proposal_id`,
        proposal.`position` AS `proposal_position`,
        proposal.`title` AS `proposal_title`,
        MOD(((participant.`id` - 6140) * 5) + (grp.`position` * 3) + (proposal.`position` * 2), 7) AS `score`,
        CASE MOD(((participant.`id` - 6140) * 5) + (grp.`position` * 3) + (proposal.`position` * 2), 7)
            WHEN 0 THEN 'Aucunement'
            WHEN 1 THEN 'Tres peu'
            WHEN 2 THEN 'Pas assez'
            WHEN 3 THEN 'Sans avis'
            WHEN 4 THEN 'Suffisamment'
            WHEN 5 THEN 'Correctement'
            ELSE 'Parfaitement'
        END AS `mention`
    FROM `decision_group` grp
    INNER JOIN `decision_proposal` proposal
        ON proposal.`IDdecision_group` = grp.`id`
       AND proposal.`active` = 1
    INNER JOIN `decision_participant` participant
        ON participant.`IDdecision_process` = grp.`IDdecision_process`
       AND participant.`id` BETWEEN 6141 AND 6155
       AND participant.`active` = 1
    WHERE grp.`IDdecision_process` = 6100
      AND grp.`active` = 1
) AS seed
GROUP BY
    seed.`group_id`,
    seed.`group_position`,
    seed.`participant_id`,
    seed.`participant_index`
ORDER BY
    seed.`group_position` ASC,
    seed.`participant_index` ASC;

INSERT INTO `decision_result` (
    `id`,
    `IDdecision_process`,
    `IDdecision_group`,
    `status`,
    `summary`,
    `parameters`,
    `computed_at`,
    `published_at`,
    `created_at`,
    `updated_at`
) VALUES
    (
        6261,
        6100,
        6111,
        'final',
        'Resultat de demonstration pour le bloc 1.',
        '{"seed":"demo_majority_judgment","block_position":1}',
        '2026-06-12 09:00:00',
        '2026-06-12 09:30:00',
        '2026-06-12 09:00:00',
        '2026-06-12 09:30:00'
    ),
    (
        6262,
        6100,
        6112,
        'final',
        'Resultat de demonstration pour le bloc 2.',
        '{"seed":"demo_majority_judgment","block_position":2}',
        '2026-06-12 09:05:00',
        '2026-06-12 09:30:00',
        '2026-06-12 09:05:00',
        '2026-06-12 09:30:00'
    ),
    (
        6263,
        6100,
        6113,
        'final',
        'Resultat de demonstration pour le bloc 3.',
        '{"seed":"demo_majority_judgment","block_position":3}',
        '2026-06-12 09:10:00',
        '2026-06-12 09:30:00',
        '2026-06-12 09:10:00',
        '2026-06-12 09:30:00'
    );

SELECT
    dp.`id` AS `decision_id`,
    dp.`title`,
    dp.`status`,
    COUNT(DISTINCT dg.`id`) AS `group_count`,
    COUNT(DISTINCT proposal.`id`) AS `proposal_count`,
    COUNT(DISTINCT participant.`id`) AS `participant_count`,
    COUNT(DISTINCT response.`id`) AS `response_count`
FROM `decision_process` dp
LEFT JOIN `decision_group` dg
    ON dg.`IDdecision_process` = dp.`id`
LEFT JOIN `decision_proposal` proposal
    ON proposal.`IDdecision_process` = dp.`id`
LEFT JOIN `decision_participant` participant
    ON participant.`IDdecision_process` = dp.`id`
LEFT JOIN `decision_response` response
    ON response.`IDdecision_process` = dp.`id`
WHERE dp.`id` = 6100
GROUP BY dp.`id`, dp.`title`, dp.`status`;
