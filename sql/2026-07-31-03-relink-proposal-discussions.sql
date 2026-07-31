-- @migration
-- Reattach proposal discussions after legacy saves recreated otherwise identical proposals.

SET NAMES utf8mb4;

CREATE TEMPORARY TABLE `tmp_relink_proposal_discussion_map` AS
SELECT
    thread.`id` AS `source_thread_id`,
    MIN(current_proposal.`id`) AS `target_proposal_id`
FROM `chat_thread` thread
INNER JOIN `decision_proposal` former_proposal
    ON former_proposal.`id` = thread.`subject_id`
INNER JOIN `decision_proposal` current_proposal
    ON current_proposal.`IDdecision_process` = former_proposal.`IDdecision_process`
    AND current_proposal.`IDdecision_group` = former_proposal.`IDdecision_group`
    AND current_proposal.`active` = 1
    AND current_proposal.`title` = former_proposal.`title`
WHERE thread.`subject_type` = 'decision_proposal'
  AND former_proposal.`active` = 0
GROUP BY thread.`id`
HAVING COUNT(current_proposal.`id`) = 1;

CREATE TEMPORARY TABLE `tmp_relink_proposal_discussion_canonical` AS
SELECT
    mapping.`target_proposal_id`,
    COALESCE(MAX(current_thread.`id`), MIN(mapping.`source_thread_id`)) AS `canonical_thread_id`
FROM `tmp_relink_proposal_discussion_map` mapping
LEFT JOIN `chat_thread` current_thread
    ON current_thread.`subject_type` = 'decision_proposal'
    AND current_thread.`subject_id` = mapping.`target_proposal_id`
GROUP BY mapping.`target_proposal_id`;

UPDATE `chat_message` message
INNER JOIN `tmp_relink_proposal_discussion_map` mapping
    ON mapping.`source_thread_id` = message.`IDchat_thread`
INNER JOIN `tmp_relink_proposal_discussion_canonical` canonical
    ON canonical.`target_proposal_id` = mapping.`target_proposal_id`
SET message.`IDchat_thread` = canonical.`canonical_thread_id`
WHERE message.`IDchat_thread` <> canonical.`canonical_thread_id`;

DELETE source_thread
FROM `chat_thread` source_thread
INNER JOIN `tmp_relink_proposal_discussion_map` mapping
    ON mapping.`source_thread_id` = source_thread.`id`
INNER JOIN `tmp_relink_proposal_discussion_canonical` canonical
    ON canonical.`target_proposal_id` = mapping.`target_proposal_id`
WHERE source_thread.`id` <> canonical.`canonical_thread_id`;

UPDATE `chat_thread` thread
INNER JOIN `tmp_relink_proposal_discussion_canonical` canonical
    ON canonical.`canonical_thread_id` = thread.`id`
SET thread.`subject_id` = canonical.`target_proposal_id`,
    thread.`active` = 1
WHERE thread.`subject_id` <> canonical.`target_proposal_id`;

DROP TEMPORARY TABLE `tmp_relink_proposal_discussion_canonical`;
DROP TEMPORARY TABLE `tmp_relink_proposal_discussion_map`;
