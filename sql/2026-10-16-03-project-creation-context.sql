-- @migration
-- Snapshot project origins so deleting a decision or meeting does not erase them.
ALTER TABLE `project` ADD COLUMN IF NOT EXISTS `creation_context` mediumtext DEFAULT NULL;

-- Recover historical origins from applied creation proposals only.
UPDATE `project` project
INNER JOIN `deferred_proposal` change_item ON change_item.target_type = 'project'
    AND change_item.target_id = project.id AND change_item.IDorganization = project.IDorganization
    AND change_item.operation = 'create' AND change_item.status = 'applied'
    AND change_item.id = (
        SELECT MIN(first_change.id) FROM `deferred_proposal` first_change
        WHERE first_change.target_type = 'project' AND first_change.target_id = project.id
          AND first_change.IDorganization = project.IDorganization
          AND first_change.operation = 'create' AND first_change.status = 'applied'
    )
LEFT JOIN `decision_proposal` proposal ON proposal.id = change_item.IDdecision_proposal
LEFT JOIN `decision_process` decision_item ON decision_item.id = proposal.IDdecision_process
    AND decision_item.IDorganization = project.IDorganization
LEFT JOIN `document_pv_point` point ON point.id = change_item.IDdocument_pv_point
LEFT JOIN `document` document_item ON document_item.id = point.IDdocument
    AND document_item.IDorganization = project.IDorganization
LEFT JOIN `event` meeting ON meeting.id = document_item.IDevent
    AND meeting.IDorganization = project.IDorganization
LEFT JOIN `user` proposer ON proposer.id = CASE
    WHEN JSON_VALID(change_item.after_state) AND JSON_CONTAINS_PATH(change_item.after_state, 'one', '$.IDuser_proposed')
        THEN CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(change_item.after_state, '$.IDuser_proposed')), 'null'), '') AS UNSIGNED)
    ELSE change_item.IDuser_author
END
SET project.creation_context = CASE
        WHEN decision_item.id IS NOT NULL THEN JSON_OBJECT('type', 'decision', 'id', decision_item.id,
            'label', decision_item.title, 'holon_id', decision_item.IDholon)
        WHEN meeting.id IS NOT NULL THEN JSON_OBJECT('type', 'event', 'id', meeting.id,
            'label', CONCAT(meeting.title, COALESCE(CONCAT(' du ', DATE_FORMAT(meeting.start_at, '%d.%m.%Y')), '')),
            'title', meeting.title, 'holon_id', meeting.IDholon, 'document_id', document_item.id)
        ELSE JSON_OBJECT('type', 'document', 'id', document_item.id,
            'label', document_item.title, 'holon_id', document_item.IDholon)
    END,
    project.IDuser_proposed = COALESCE(project.IDuser_proposed, proposer.id),
    project.proposed_at = COALESCE(project.proposed_at, change_item.created_at)
WHERE project.creation_context IS NULL AND (decision_item.id IS NOT NULL OR document_item.id IS NOT NULL);
