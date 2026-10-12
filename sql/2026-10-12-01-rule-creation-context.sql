-- @migration
-- Keep provenance even when the originating decision or meeting is removed.
ALTER TABLE `rule` ADD COLUMN IF NOT EXISTS `creation_context` mediumtext DEFAULT NULL;

UPDATE `rule` r
INNER JOIN `decision_governance_action` a ON a.target_type = 'rule' AND a.target_id = r.id
    AND a.action_type = 'rule.create' AND a.status = 'applied'
INNER JOIN `decision_proposal` p ON p.id = a.IDdecision_proposal
INNER JOIN `decision_process` d ON d.id = p.IDdecision_process AND d.IDorganization = r.IDorganization
SET r.creation_context = JSON_OBJECT('type', 'decision', 'id', d.id, 'label', d.title, 'holon_id', d.IDholon),
    r.IDuser_creation = COALESCE(r.IDuser_creation, p.IDuser_author)
WHERE r.creation_context IS NULL;

UPDATE `rule` r
INNER JOIN `deferred_proposal` a ON a.target_type = 'rule' AND a.target_id = r.id
    AND a.operation = 'create' AND a.status = 'applied' AND a.IDorganization = r.IDorganization
LEFT JOIN `decision_proposal` p ON p.id = a.IDdecision_proposal
LEFT JOIN `decision_process` d ON d.id = p.IDdecision_process AND d.IDorganization = r.IDorganization
LEFT JOIN `document_pv_point` point ON point.id = a.IDdocument_pv_point
LEFT JOIN `document` doc ON doc.id = point.IDdocument AND doc.IDorganization = r.IDorganization
LEFT JOIN `event` e ON e.id = doc.IDevent AND e.IDorganization = r.IDorganization
SET r.creation_context = CASE
        WHEN d.id IS NOT NULL THEN JSON_OBJECT('type', 'decision', 'id', d.id, 'label', d.title, 'holon_id', d.IDholon)
        WHEN e.id IS NOT NULL THEN JSON_OBJECT('type', 'event', 'id', e.id, 'label', CONCAT(e.title, ' du ', DATE_FORMAT(e.start_at, '%d.%m.%Y')), 'holon_id', e.IDholon, 'document_id', doc.id)
        ELSE JSON_OBJECT('type', 'document', 'id', doc.id, 'label', doc.title, 'holon_id', doc.IDholon)
    END,
    r.IDuser_creation = COALESCE(r.IDuser_creation, a.IDuser_author)
WHERE r.creation_context IS NULL AND (d.id IS NOT NULL OR doc.id IS NOT NULL);
