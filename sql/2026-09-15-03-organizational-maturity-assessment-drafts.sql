-- @migration
ALTER TABLE `organizational_maturity_assessment`
  ADD COLUMN IF NOT EXISTS `completed_at` datetime DEFAULT NULL AFTER `updated_at`;

UPDATE `organizational_maturity_assessment` AS a
INNER JOIN (
  SELECT r.`IDassessment`
  FROM `organizational_maturity_assessment_response` AS r
  GROUP BY r.`IDassessment`
  HAVING COUNT(*) = 10
     AND MIN(r.`affinity_score`) BETWEEN 1 AND 5
     AND MIN(r.`today_score`) BETWEEN 1 AND 5
     AND MIN(r.`tomorrow_score`) BETWEEN 1 AND 5
) AS completed ON completed.`IDassessment` = a.`id`
SET a.`completed_at` = COALESCE(a.`completed_at`, a.`updated_at`);
