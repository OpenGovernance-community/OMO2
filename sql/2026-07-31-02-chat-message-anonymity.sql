-- @migration
-- Preserve the anonymity of messages already posted in currently anonymous decision discussions.

SET NAMES utf8mb4;

UPDATE `chat_message` message
INNER JOIN `chat_thread` thread
    ON thread.`id` = message.`IDchat_thread`
    AND thread.`subject_type` = 'decision_proposal'
INNER JOIN `decision_proposal` proposal
    ON proposal.`id` = thread.`subject_id`
INNER JOIN `decision_group` decision_group
    ON decision_group.`id` = proposal.`IDdecision_group`
SET message.`parameters` = JSON_SET(
    IF(JSON_VALID(message.`parameters`), message.`parameters`, JSON_OBJECT()),
    '$.is_anonymous',
    TRUE
)
WHERE message.`message_type` = 'user'
  AND (
      COALESCE(JSON_UNQUOTE(JSON_EXTRACT(
          IF(JSON_VALID(decision_group.`parameters`), decision_group.`parameters`, JSON_OBJECT()),
          CONCAT('$.', decision_group.`evaluation_method`, '.is_anonymous')
      )), '') IN ('1', 'true')
      OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT(
          IF(JSON_VALID(decision_group.`parameters`), decision_group.`parameters`, JSON_OBJECT()),
          '$.is_anonymous'
      )), '') IN ('1', 'true')
  );
