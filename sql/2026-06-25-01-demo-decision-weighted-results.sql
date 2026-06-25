-- OpenMyOrganization
-- Jeu de donnees fictif pour tester l affichage des resultats ponderes
--
-- Contenu :
--   - 1 scrutin "Vote simple" avec inversion visible entre resultat pondere et non pondere
--   - 1 scrutin "Jugement majoritaire" avec inversion visible entre resultat pondere et non pondere
--   - plusieurs participants externes avec coefficients 0.5, 1 et 2
--
-- Resultats attendus :
--   - Vote simple :
--       non pondere -> La pizzeria gagne en nombre de voix
--       pondere     -> Le bistrot local gagne grace aux votants a poids fort
--   - Jugement majoritaire :
--       non pondere -> La pizzeria ressort en tete
--       pondere     -> La cantine bio ressort en tete
--       distribution -> plusieurs mentions differentes restent visibles pour mieux lire l effet de la ponderation
--
-- Usage conseille :
--   mariadb -u <user> -p <database> < sql/2026-06-25-01-demo-decision-weighted-results.sql
--
-- Ce script utilise des IDs dedies (6300 a 6466) pour pouvoir etre rejoue.

SET NAMES utf8mb4;

DELETE FROM `decision_result`
WHERE `IDdecision_process` IN (6300, 6400)
   OR `id` BETWEEN 6371 AND 6372
   OR `id` BETWEEN 6471 AND 6472;

DELETE FROM `decision_response`
WHERE `IDdecision_process` IN (6300, 6400)
   OR `id` BETWEEN 6361 AND 6367
   OR `id` BETWEEN 6461 AND 6466;

DELETE FROM `decision_invitation`
WHERE `IDdecision_process` IN (6300, 6400)
   OR `id` BETWEEN 6351 AND 6357
   OR `id` BETWEEN 6451 AND 6456;

DELETE FROM `decision_participant`
WHERE `IDdecision_process` IN (6300, 6400)
   OR `id` BETWEEN 6340 AND 6347
   OR `id` BETWEEN 6440 AND 6446;

DELETE FROM `decision_proposal`
WHERE `IDdecision_process` IN (6300, 6400)
   OR `id` BETWEEN 6321 AND 6323
   OR `id` BETWEEN 6421 AND 6423;

DELETE FROM `decision_group`
WHERE `IDdecision_process` IN (6300, 6400)
   OR `id` IN (6311, 6411);

DELETE FROM `decision_process`
WHERE `id` IN (6300, 6400);

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
    `visibility_type`,
    `parameters`,
    `consultation_start_at`,
    `consultation_end_at`,
    `evaluation_start_at`,
    `evaluation_end_at`,
    `results_published_at`,
    `archived_at`,
    `created_at`,
    `updated_at`
) VALUES
    (
        6300,
        1,
        NULL,
        1,
        'Demo vote pondere - restaurant',
        'Jeu de test pour verifier l affichage du vote simple pondere face au resultat non pondere.',
        'decision',
        'results',
        'simple_vote',
        'organization',
        '{"simple_vote":{"choice_mode":"single","max_choices":1,"is_anonymous":0,"allow_consultation_proposals":0,"vote_weighting":{"enabled":1,"question":"A quel point ce choix de restaurant est important pour vous ?","options":[{"weight":"0.5","label":"Peu important"},{"weight":"1","label":"Souhaitable"},{"weight":"1.5","label":"Important"},{"weight":"2","label":"Vital"}]}},"seed":"demo_weighted_results_simple_vote"}',
        '2026-06-15 09:00:00',
        '2026-06-17 18:00:00',
        '2026-06-17 18:15:00',
        '2026-06-19 18:00:00',
        '2026-06-20 09:00:00',
        NULL,
        '2026-06-14 10:00:00',
        '2026-06-20 09:00:00'
    ),
    (
        6400,
        1,
        NULL,
        1,
        'Demo jugement majoritaire pondere - restaurant',
        'Jeu de test pour verifier l affichage du jugement majoritaire pondere face au resultat non pondere.',
        'decision',
        'results',
        'majority_judgment',
        'organization',
        '{"majority_judgment":{"is_anonymous":0,"allow_consultation_proposals":0,"mention_options":{"0":{"label":"A rejeter","active":1},"1":{"label":"Insuffisant","active":1},"2":{"label":"Passable","active":1},"3":{"label":"Sans avis","active":1},"4":{"label":"Assez bien","active":1},"5":{"label":"Tres bien","active":1},"6":{"label":"Excellent","active":1}},"vote_weighting":{"enabled":1,"question":"A quel point aller au restaurant ensemble est important pour vous ?","options":[{"weight":"0.5","label":"Peu important"},{"weight":"1","label":"Souhaitable"},{"weight":"1.5","label":"Important"},{"weight":"2","label":"Vital"}]}},"seed":"demo_weighted_results_majority_judgment"}',
        '2026-06-15 09:30:00',
        '2026-06-17 18:30:00',
        '2026-06-17 18:45:00',
        '2026-06-19 18:30:00',
        '2026-06-20 09:30:00',
        NULL,
        '2026-06-14 10:30:00',
        '2026-06-20 09:30:00'
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
        6311,
        6300,
        'decision',
        'simple_vote',
        'Quel restaurant choisir pour la soiree ?',
        'Bloc de test pour comparer visuellement les barres ponderees et non ponderees.',
        '{"simple_vote":{"choice_mode":"single","max_choices":1,"is_anonymous":0,"allow_consultation_proposals":0,"vote_weighting":{"enabled":1,"question":"A quel point ce choix de restaurant est important pour vous ?","options":[{"weight":"0.5","label":"Peu important"},{"weight":"1","label":"Souhaitable"},{"weight":"1.5","label":"Important"},{"weight":"2","label":"Vital"}]}}}',
        1,
        1,
        '2026-06-14 10:05:00',
        '2026-06-20 09:00:00'
    ),
    (
        6411,
        6400,
        'decision',
        'majority_judgment',
        'Quel restaurant choisir pour la soiree ?',
        'Bloc de test pour comparer le jugement majoritaire pondere avec son equivalent non pondere.',
        '{"majority_judgment":{"is_anonymous":0,"allow_consultation_proposals":0,"mention_options":{"0":{"label":"A rejeter","active":1},"1":{"label":"Insuffisant","active":1},"2":{"label":"Passable","active":1},"3":{"label":"Sans avis","active":1},"4":{"label":"Assez bien","active":1},"5":{"label":"Tres bien","active":1},"6":{"label":"Excellent","active":1}},"vote_weighting":{"enabled":1,"question":"A quel point aller au restaurant ensemble est important pour vous ?","options":[{"weight":"0.5","label":"Peu important"},{"weight":"1","label":"Souhaitable"},{"weight":"1.5","label":"Important"},{"weight":"2","label":"Vital"}]}}}',
        1,
        1,
        '2026-06-14 10:35:00',
        '2026-06-20 09:30:00'
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
    (6321, 6300, 6311, 'La pizzeria', 'Option populaire mais surtout choisie par les participants a faible poids.', NULL, 1, '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-14 10:10:00', '2026-06-20 09:00:00'),
    (6322, 6300, 6311, 'Le bistrot local', 'Option moins choisie en nombre, mais soutenue par les participants a poids fort.', NULL, 2, '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-14 10:11:00', '2026-06-20 09:00:00'),
    (6323, 6300, 6311, 'Le buffet vegetal', 'Option temoin avec un soutien moyen.', NULL, 3, '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-14 10:12:00', '2026-06-20 09:00:00'),
    (6421, 6400, 6411, 'La pizzeria', 'Recoit beaucoup de bonnes mentions legeres.', NULL, 1, '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-14 10:40:00', '2026-06-20 09:30:00'),
    (6422, 6400, 6411, 'La cantine bio', 'Recoit peu de bonnes mentions en nombre brut, mais tres fortes en poids.', NULL, 2, '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-14 10:41:00', '2026-06-20 09:30:00'),
    (6423, 6400, 6411, 'Le buffet maison', 'Option intermediaire stable servant de comparaison.', NULL, 3, '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-14 10:42:00', '2026-06-20 09:30:00');

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
    (6340, 6300, 1, NULL, 'Owner demo vote', 'owner', 'active', NULL, '{"seed":"demo_weighted_results_simple_vote_owner"}', 1, NULL, NULL, '2026-06-14 10:20:00', '2026-06-20 09:00:00'),
    (6341, 6300, NULL, 'vote-weight-01@test.local', 'Participant vote 01', 'participant', 'active', 'demo-vote-weight-01', '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-16 08:00:00', '2026-06-16 08:05:00', '2026-06-14 10:21:00', '2026-06-20 09:00:00'),
    (6342, 6300, NULL, 'vote-weight-02@test.local', 'Participant vote 02', 'participant', 'active', 'demo-vote-weight-02', '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-16 08:01:00', '2026-06-16 08:06:00', '2026-06-14 10:22:00', '2026-06-20 09:00:00'),
    (6343, 6300, NULL, 'vote-weight-03@test.local', 'Participant vote 03', 'participant', 'active', 'demo-vote-weight-03', '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-16 08:02:00', '2026-06-16 08:07:00', '2026-06-14 10:23:00', '2026-06-20 09:00:00'),
    (6344, 6300, NULL, 'vote-weight-04@test.local', 'Participant vote 04', 'participant', 'active', 'demo-vote-weight-04', '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-16 08:03:00', '2026-06-16 08:08:00', '2026-06-14 10:24:00', '2026-06-20 09:00:00'),
    (6345, 6300, NULL, 'vote-weight-05@test.local', 'Participant vote 05', 'participant', 'active', 'demo-vote-weight-05', '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-16 08:04:00', '2026-06-16 08:09:00', '2026-06-14 10:25:00', '2026-06-20 09:00:00'),
    (6346, 6300, NULL, 'vote-weight-06@test.local', 'Participant vote 06', 'participant', 'active', 'demo-vote-weight-06', '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-16 08:05:00', '2026-06-16 08:10:00', '2026-06-14 10:26:00', '2026-06-20 09:00:00'),
    (6347, 6300, NULL, 'vote-weight-07@test.local', 'Participant vote 07', 'participant', 'active', 'demo-vote-weight-07', '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-16 08:06:00', '2026-06-16 08:11:00', '2026-06-14 10:27:00', '2026-06-20 09:00:00'),
    (6440, 6400, 1, NULL, 'Owner demo MJ', 'owner', 'active', NULL, '{"seed":"demo_weighted_results_majority_judgment_owner"}', 1, NULL, NULL, '2026-06-14 10:50:00', '2026-06-20 09:30:00'),
    (6441, 6400, NULL, 'mj-weight-01@test.local', 'Participant MJ 01', 'participant', 'active', 'demo-mj-weight-01', '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-16 09:00:00', '2026-06-16 09:05:00', '2026-06-14 10:51:00', '2026-06-20 09:30:00'),
    (6442, 6400, NULL, 'mj-weight-02@test.local', 'Participant MJ 02', 'participant', 'active', 'demo-mj-weight-02', '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-16 09:01:00', '2026-06-16 09:06:00', '2026-06-14 10:52:00', '2026-06-20 09:30:00'),
    (6443, 6400, NULL, 'mj-weight-03@test.local', 'Participant MJ 03', 'participant', 'active', 'demo-mj-weight-03', '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-16 09:02:00', '2026-06-16 09:07:00', '2026-06-14 10:53:00', '2026-06-20 09:30:00'),
    (6444, 6400, NULL, 'mj-weight-04@test.local', 'Participant MJ 04', 'participant', 'active', 'demo-mj-weight-04', '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-16 09:03:00', '2026-06-16 09:08:00', '2026-06-14 10:54:00', '2026-06-20 09:30:00'),
    (6445, 6400, NULL, 'mj-weight-05@test.local', 'Participant MJ 05', 'participant', 'active', 'demo-mj-weight-05', '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-16 09:04:00', '2026-06-16 09:09:00', '2026-06-14 10:55:00', '2026-06-20 09:30:00'),
    (6446, 6400, NULL, 'mj-weight-06@test.local', 'Participant MJ 06', 'participant', 'active', 'demo-mj-weight-06', '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-16 09:05:00', '2026-06-16 09:10:00', '2026-06-14 10:56:00', '2026-06-20 09:30:00');

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
    (6351, 6300, NULL, NULL, 'vote-weight-01@test.local', 'Participant vote 01', 'email', 'accepted', '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-16 08:00:00', '2026-06-16 08:05:00'),
    (6352, 6300, NULL, NULL, 'vote-weight-02@test.local', 'Participant vote 02', 'email', 'accepted', '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-16 08:01:00', '2026-06-16 08:06:00'),
    (6353, 6300, NULL, NULL, 'vote-weight-03@test.local', 'Participant vote 03', 'email', 'accepted', '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-16 08:02:00', '2026-06-16 08:07:00'),
    (6354, 6300, NULL, NULL, 'vote-weight-04@test.local', 'Participant vote 04', 'email', 'accepted', '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-16 08:03:00', '2026-06-16 08:08:00'),
    (6355, 6300, NULL, NULL, 'vote-weight-05@test.local', 'Participant vote 05', 'email', 'accepted', '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-16 08:04:00', '2026-06-16 08:09:00'),
    (6356, 6300, NULL, NULL, 'vote-weight-06@test.local', 'Participant vote 06', 'email', 'accepted', '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-16 08:05:00', '2026-06-16 08:10:00'),
    (6357, 6300, NULL, NULL, 'vote-weight-07@test.local', 'Participant vote 07', 'email', 'accepted', '{"seed":"demo_weighted_results_simple_vote"}', 1, '2026-06-16 08:06:00', '2026-06-16 08:11:00'),
    (6451, 6400, NULL, NULL, 'mj-weight-01@test.local', 'Participant MJ 01', 'email', 'accepted', '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-16 09:00:00', '2026-06-16 09:05:00'),
    (6452, 6400, NULL, NULL, 'mj-weight-02@test.local', 'Participant MJ 02', 'email', 'accepted', '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-16 09:01:00', '2026-06-16 09:06:00'),
    (6453, 6400, NULL, NULL, 'mj-weight-03@test.local', 'Participant MJ 03', 'email', 'accepted', '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-16 09:02:00', '2026-06-16 09:07:00'),
    (6454, 6400, NULL, NULL, 'mj-weight-04@test.local', 'Participant MJ 04', 'email', 'accepted', '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-16 09:03:00', '2026-06-16 09:08:00'),
    (6455, 6400, NULL, NULL, 'mj-weight-05@test.local', 'Participant MJ 05', 'email', 'accepted', '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-16 09:04:00', '2026-06-16 09:09:00'),
    (6456, 6400, NULL, NULL, 'mj-weight-06@test.local', 'Participant MJ 06', 'email', 'accepted', '{"seed":"demo_weighted_results_majority_judgment"}', 1, '2026-06-16 09:05:00', '2026-06-16 09:10:00');

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
) VALUES
    (
        6361,
        6300,
        6311,
        6341,
        'submitted',
        '{"simple_vote":{"choice_mode":"single","selected_proposal_id":6321,"selected_proposal_ids":[6321],"selected_position":1,"selected_positions":[1],"selected_title":"La pizzeria","selected_titles":["La pizzeria"],"vote_weight":"0.5","vote_weight_label":"Peu important"}}',
        '2026-06-18 19:00:00',
        '2026-06-18 19:00:00',
        '2026-06-18 19:00:00'
    ),
    (
        6362,
        6300,
        6311,
        6342,
        'submitted',
        '{"simple_vote":{"choice_mode":"single","selected_proposal_id":6321,"selected_proposal_ids":[6321],"selected_position":1,"selected_positions":[1],"selected_title":"La pizzeria","selected_titles":["La pizzeria"],"vote_weight":"0.5","vote_weight_label":"Peu important"}}',
        '2026-06-18 19:02:00',
        '2026-06-18 19:02:00',
        '2026-06-18 19:02:00'
    ),
    (
        6363,
        6300,
        6311,
        6343,
        'submitted',
        '{"simple_vote":{"choice_mode":"single","selected_proposal_id":6321,"selected_proposal_ids":[6321],"selected_position":1,"selected_positions":[1],"selected_title":"La pizzeria","selected_titles":["La pizzeria"],"vote_weight":"0.5","vote_weight_label":"Peu important"}}',
        '2026-06-18 19:04:00',
        '2026-06-18 19:04:00',
        '2026-06-18 19:04:00'
    ),
    (
        6364,
        6300,
        6311,
        6344,
        'submitted',
        '{"simple_vote":{"choice_mode":"single","selected_proposal_id":6321,"selected_proposal_ids":[6321],"selected_position":1,"selected_positions":[1],"selected_title":"La pizzeria","selected_titles":["La pizzeria"],"vote_weight":"0.5","vote_weight_label":"Peu important"}}',
        '2026-06-18 19:06:00',
        '2026-06-18 19:06:00',
        '2026-06-18 19:06:00'
    ),
    (
        6365,
        6300,
        6311,
        6345,
        'submitted',
        '{"simple_vote":{"choice_mode":"single","selected_proposal_id":6322,"selected_proposal_ids":[6322],"selected_position":2,"selected_positions":[2],"selected_title":"Le bistrot local","selected_titles":["Le bistrot local"],"vote_weight":"2","vote_weight_label":"Vital"}}',
        '2026-06-18 19:08:00',
        '2026-06-18 19:08:00',
        '2026-06-18 19:08:00'
    ),
    (
        6366,
        6300,
        6311,
        6346,
        'submitted',
        '{"simple_vote":{"choice_mode":"single","selected_proposal_id":6322,"selected_proposal_ids":[6322],"selected_position":2,"selected_positions":[2],"selected_title":"Le bistrot local","selected_titles":["Le bistrot local"],"vote_weight":"2","vote_weight_label":"Vital"}}',
        '2026-06-18 19:10:00',
        '2026-06-18 19:10:00',
        '2026-06-18 19:10:00'
    ),
    (
        6367,
        6300,
        6311,
        6347,
        'submitted',
        '{"simple_vote":{"choice_mode":"single","selected_proposal_id":6323,"selected_proposal_ids":[6323],"selected_position":3,"selected_positions":[3],"selected_title":"Le buffet vegetal","selected_titles":["Le buffet vegetal"],"vote_weight":"1","vote_weight_label":"Souhaitable"}}',
        '2026-06-18 19:12:00',
        '2026-06-18 19:12:00',
        '2026-06-18 19:12:00'
    ),
    (
        6461,
        6400,
        6411,
        6441,
        'submitted',
        '{"majority_judgment":{"scores":{"6421":6,"6422":1,"6423":4},"details":{"6421":{"score":6,"mention":"Excellent","position":1,"title":"La pizzeria"},"6422":{"score":1,"mention":"Insuffisant","position":2,"title":"La cantine bio"},"6423":{"score":4,"mention":"Assez bien","position":3,"title":"Le buffet maison"}},"vote_weight":"0.5","vote_weight_label":"Peu important"}}',
        '2026-06-18 20:00:00',
        '2026-06-18 20:00:00',
        '2026-06-18 20:00:00'
    ),
    (
        6462,
        6400,
        6411,
        6442,
        'submitted',
        '{"majority_judgment":{"scores":{"6421":5,"6422":2,"6423":3},"details":{"6421":{"score":5,"mention":"Tres bien","position":1,"title":"La pizzeria"},"6422":{"score":2,"mention":"Passable","position":2,"title":"La cantine bio"},"6423":{"score":3,"mention":"Sans avis","position":3,"title":"Le buffet maison"}},"vote_weight":"0.5","vote_weight_label":"Peu important"}}',
        '2026-06-18 20:02:00',
        '2026-06-18 20:02:00',
        '2026-06-18 20:02:00'
    ),
    (
        6463,
        6400,
        6411,
        6443,
        'submitted',
        '{"majority_judgment":{"scores":{"6421":4,"6422":2,"6423":5},"details":{"6421":{"score":4,"mention":"Assez bien","position":1,"title":"La pizzeria"},"6422":{"score":2,"mention":"Passable","position":2,"title":"La cantine bio"},"6423":{"score":5,"mention":"Tres bien","position":3,"title":"Le buffet maison"}},"vote_weight":"0.5","vote_weight_label":"Peu important"}}',
        '2026-06-18 20:04:00',
        '2026-06-18 20:04:00',
        '2026-06-18 20:04:00'
    ),
    (
        6464,
        6400,
        6411,
        6444,
        'submitted',
        '{"majority_judgment":{"scores":{"6421":5,"6422":1,"6423":4},"details":{"6421":{"score":5,"mention":"Tres bien","position":1,"title":"La pizzeria"},"6422":{"score":1,"mention":"Insuffisant","position":2,"title":"La cantine bio"},"6423":{"score":4,"mention":"Assez bien","position":3,"title":"Le buffet maison"}},"vote_weight":"0.5","vote_weight_label":"Peu important"}}',
        '2026-06-18 20:06:00',
        '2026-06-18 20:06:00',
        '2026-06-18 20:06:00'
    ),
    (
        6465,
        6400,
        6411,
        6445,
        'submitted',
        '{"majority_judgment":{"scores":{"6421":1,"6422":6,"6423":4},"details":{"6421":{"score":1,"mention":"Insuffisant","position":1,"title":"La pizzeria"},"6422":{"score":6,"mention":"Excellent","position":2,"title":"La cantine bio"},"6423":{"score":4,"mention":"Assez bien","position":3,"title":"Le buffet maison"}},"vote_weight":"2","vote_weight_label":"Vital"}}',
        '2026-06-18 20:08:00',
        '2026-06-18 20:08:00',
        '2026-06-18 20:08:00'
    ),
    (
        6466,
        6400,
        6411,
        6446,
        'submitted',
        '{"majority_judgment":{"scores":{"6421":2,"6422":6,"6423":3},"details":{"6421":{"score":2,"mention":"Passable","position":1,"title":"La pizzeria"},"6422":{"score":6,"mention":"Excellent","position":2,"title":"La cantine bio"},"6423":{"score":3,"mention":"Sans avis","position":3,"title":"Le buffet maison"}},"vote_weight":"2","vote_weight_label":"Vital"}}',
        '2026-06-18 20:10:00',
        '2026-06-18 20:10:00',
        '2026-06-18 20:10:00'
    );
