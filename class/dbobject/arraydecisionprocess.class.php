<?php
namespace dbObject;

class ArrayDecisionProcess extends ArrayDbObject
{
    public static function objectName()
    {
        return '\dbObject\DecisionProcess';
    }

    protected function resolveViewerScopedEmail($userId, $organizationId)
    {
        $userId = (int)$userId;
        $organizationId = (int)$organizationId;

        if ($userId <= 0 || $organizationId <= 0) {
            return '';
        }

        $user = new \dbObject\User();
        if (!$user->load($userId)) {
            return '';
        }

        return trim(mb_strtolower((string)$user->getScopedEmail($organizationId), 'UTF-8'));
    }

    protected function resolveDecisionParticipant(\dbObject\DecisionProcess $decision, $userId, $organizationId, $scopedEmail = '')
    {
        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        $participant = null;

        if ($userId > 0) {
            $participant = \dbObject\DecisionParticipant::findByDecisionAndUser((int)$decision->getId(), $userId);
        }

        if (!$participant || (int)$participant->get('active') !== 1) {
            $scopedEmail = trim(mb_strtolower((string)$scopedEmail, 'UTF-8'));
            if ($scopedEmail !== '') {
                $participant = \dbObject\DecisionParticipant::findByDecisionAndEmail((int)$decision->getId(), $scopedEmail);
            }
        }

        if (!($participant instanceof \dbObject\DecisionParticipant) || (int)$participant->get('active') !== 1) {
            return null;
        }

        $participantStatus = \dbObject\DecisionParticipant::normalizeStatus($participant->get('status'));
        if (in_array($participantStatus, array(
            \dbObject\DecisionParticipant::STATUS_DECLINED,
            \dbObject\DecisionParticipant::STATUS_REVOKED,
        ), true)) {
            return null;
        }

        return $participant;
    }

    protected function resolveDecisionAccess(\dbObject\DecisionProcess $decision, $organizationId, $userId, $scopedEmail = '')
    {
        $organizationId = (int)$organizationId;
        $userId = (int)$userId;

        if (
            $organizationId <= 0
            || $userId <= 0
            || (int)$decision->getId() <= 0
            || (int)$decision->get('IDorganization') !== $organizationId
        ) {
            return false;
        }

        $decisionHolonId = (int)$decision->get('IDholon');
        if ($decisionHolonId > 0) {
            $decisionHolon = new \dbObject\Holon();
            if (!$decisionHolon->load($decisionHolonId) || !$decisionHolon->canViewDetail()) {
                return false;
            }
        }

        $participant = $this->resolveDecisionParticipant($decision, $userId, $organizationId, $scopedEmail);
        $hasParticipation = $participant instanceof \dbObject\DecisionParticipant;
        $isOwner = (int)$decision->get('IDuser') === $userId;
        $status = \dbObject\DecisionProcess::normalizeStatus($decision->get('status'));
        $visibilityAccess = $decision->currentViewerCanAccessVisibility($organizationId);

        if ($participant instanceof \dbObject\DecisionParticipant) {
            $isOwner = $isOwner || \dbObject\DecisionParticipant::normalizeRole($participant->get('role')) === \dbObject\DecisionParticipant::ROLE_OWNER;
        }

        $canManage = $isOwner;
        $canParticipate = ($isOwner || $hasParticipation) && $decision->isParticipationOpen();
        $canView = $canManage
            || $hasParticipation
            || ($status !== \dbObject\DecisionProcess::STATUS_DRAFT && $visibilityAccess);

        if (!$canView && !$canParticipate) {
            return false;
        }

        return array(
            'canManage' => $canManage,
            'canParticipate' => $canParticipate,
            'canView' => $canView,
            'participant' => $participant,
        );
    }

    protected function appendSummaryItem(array &$items, $bucket, array $item, $limit)
    {
        if (!isset($items[$bucket]) || !is_array($items[$bucket])) {
            $items[$bucket] = array();
        }

        if (count($items[$bucket]) >= $limit) {
            return;
        }

        $items[$bucket][] = $item;
    }

    public function loadForPersonalSpace($organizationId, $holonId = 0)
    {
        $organizationId = (int)$organizationId;
        $holonId = (int)$holonId;

        $this->exchangeArray([]);

        if ($organizationId <= 0) {
            return;
        }

        $loadParams = array(
            'where' => array(
                array('field' => 'IDorganization', 'value' => $organizationId),
            ),
            'orderBy' => array(
                array('field' => 'updated_at', 'dir' => 'DESC'),
                array('field' => 'created_at', 'dir' => 'DESC'),
                array('field' => 'id', 'dir' => 'DESC'),
            ),
        );

        if ($holonId > 0) {
            $loadParams['where'][] = array('field' => 'IDholon', 'value' => $holonId);
        }

        $this->load($loadParams);
    }

    public function buildPersonalSpaceSummary($organizationId, $userId, $holonId = 0, $previewLimit = 3)
    {
        $organizationId = (int)$organizationId;
        $userId = (int)$userId;
        $holonId = (int)$holonId;
        $previewLimit = max(1, (int)$previewLimit);
        $summary = array(
            'counts' => array(
                'finalize' => 0,
                'consultation' => 0,
                'action' => 0,
                'actionResponded' => 0,
                'results' => 0,
            ),
            'items' => array(
                'finalize' => array(),
                'consultation' => array(),
                'action' => array(),
                'results' => array(),
            ),
        );

        if ($organizationId <= 0 || $userId <= 0) {
            return $summary;
        }

        $this->loadForPersonalSpace($organizationId, $holonId);
        $scopedEmail = $this->resolveViewerScopedEmail($userId, $organizationId);
        $statusCatalog = \dbObject\DecisionProcess::getStatusCatalog();

        foreach ($this as $decision) {
            if (!($decision instanceof \dbObject\DecisionProcess) || (int)$decision->getId() <= 0) {
                continue;
            }

            $access = $this->resolveDecisionAccess($decision, $organizationId, $userId, $scopedEmail);
            if (!is_array($access)) {
                continue;
            }

            $status = \dbObject\DecisionProcess::normalizeStatus($decision->get('status'));
            $decisionType = \dbObject\DecisionProcess::normalizeDecisionType($decision->get('decision_type'));
            $statusDefinition = $statusCatalog[$status] ?? array();
            $item = array(
                'decisionId' => (int)$decision->getId(),
                'holonId' => (int)$decision->get('IDholon'),
                'title' => trim((string)$decision->get('title')) !== ''
                    ? trim((string)$decision->get('title'))
                    : 'Decision #' . (int)$decision->getId(),
                'status' => $status,
                'statusLabel' => trim((string)($statusDefinition['label'] ?? $status)),
                'decisionType' => $decisionType,
            );

            if ($access['canManage'] && in_array($status, array(
                \dbObject\DecisionProcess::STATUS_DRAFT,
                \dbObject\DecisionProcess::STATUS_SCHEDULED,
            ), true)) {
                $summary['counts']['finalize'] += 1;
                $this->appendSummaryItem($summary['items'], 'finalize', $item, $previewLimit);
                continue;
            }

            if (in_array($status, array(
                \dbObject\DecisionProcess::STATUS_CONSULTATION,
                \dbObject\DecisionProcess::STATUS_EVALUATION,
            ), true)) {
                $isPersonallyRelevant = $access['canManage']
                    || ($access['participant'] instanceof \dbObject\DecisionParticipant)
                    || $access['canParticipate'];

                if ($decisionType === \dbObject\DecisionProcess::TYPE_CONSULTATION && $isPersonallyRelevant) {
                    $summary['counts']['consultation'] += 1;
                    $this->appendSummaryItem($summary['items'], 'consultation', $item, $previewLimit);
                    continue;
                }

                if ($access['canParticipate'] || $access['canManage']) {
                    $participant = $access['participant'] ?? null;
                    $response = $participant
                        ? \dbObject\DecisionResponse::findByDecisionAndParticipant((int)$decision->getId(), (int)$participant->getId())
                        : null;
                    $hasResponded = $response instanceof \dbObject\DecisionResponse
                        && \dbObject\DecisionResponse::normalizeStatus($response->get('status')) === \dbObject\DecisionResponse::STATUS_SUBMITTED;

                    $summary['counts']['action'] += 1;
                    if ($hasResponded) {
                        $summary['counts']['actionResponded'] += 1;
                    }

                    $item['hasResponded'] = $hasResponded;
                    $this->appendSummaryItem($summary['items'], 'action', $item, $previewLimit);
                    continue;
                }
            }

            if (in_array($status, array(
                \dbObject\DecisionProcess::STATUS_RESULTS,
                \dbObject\DecisionProcess::STATUS_ARCHIVED,
            ), true) && (
                $access['canManage']
                || ($access['participant'] instanceof \dbObject\DecisionParticipant)
            )) {
                $summary['counts']['results'] += 1;
                $this->appendSummaryItem($summary['items'], 'results', $item, $previewLimit);
            }
        }

        return $summary;
    }
}

?>
