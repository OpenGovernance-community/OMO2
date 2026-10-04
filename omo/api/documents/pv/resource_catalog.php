<?php
/** PV resource picker payloads, loaded only when the corresponding picker opens. */
function omoDocumentsPvEditorCanLoadResourceCatalog(\dbObject\Organization $organization, int $userId, string $type): bool
{
    return match ($type) {
        'documents' => $organization->isApplicationEnabled('documents', $userId),
        'decisions' => $organization->isApplicationEnabled('decision', $userId),
        'events' => $organization->isApplicationEnabled('calendar', $userId),
        'indicators' => $organization->isApplicationEnabled('stats', $userId),
        'checklists' => $organization->isApplicationEnabled('processus', $userId) || $organization->isApplicationEnabled('checklist', $userId),
        'projects' => true,
        default => false,
    };
}

function omoDocumentsPvEditorLoadResourceCatalog(\dbObject\Document $document, \dbObject\Organization $organization, int $currentUserId, string $type, callable $translate): array
{
    $organizationId = (int)$organization->getId();
    $isPvEditor = $document->isPvEditor($currentUserId);
    $formatProjectDate = static fn ($value): string => $value instanceof DateTimeInterface ? $value->format('d.m.Y') : '';
    $embeddableDocumentsPayload = $embeddableDecisionsPayload = $embeddableProjectsPayload = [];
    $embeddableChecklistsPayload = $embeddableEventsPayload = $embeddableIndicatorsPayload = [];
    if ($type === 'documents') {
        $embeddableDocuments = new \dbObject\ArrayDocument();
        $embeddableDocuments->loadVisibleForOrganization($organizationId);

        foreach ($embeddableDocuments as $embeddableDocument) {
            if (
                !($embeddableDocument instanceof \dbObject\Document)
                || !$embeddableDocument->canBeEmbedded()
                || (int)$embeddableDocument->getId() <= 0
                || (int)$embeddableDocument->getId() === (int)$document->getId()
            ) {
                continue;
            }

            $embeddableDocumentsPayload[] = [
                'id' => (int)$embeddableDocument->getId(),
                'contextHolonId' => (int)$embeddableDocument->get('IDholon'),
                'title' => trim((string)$embeddableDocument->get('title')),
                'description' => trim((string)$embeddableDocument->get('description')),
                'contextLabel' => trim((string)$embeddableDocument->getOrganizationContextLabel()),
            ];
        }

        usort($embeddableDocumentsPayload, static function (array $left, array $right): int {
            return strnatcasecmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
        });
    }

    if ($type === 'decisions') {
        $embeddableDecisions = new \dbObject\ArrayDecisionProcess();
        $decisionTypeLabels = [
            \dbObject\DecisionProcess::TYPE_DECISION => $translate('documents.pv_editor.decision.type.decision'),
            \dbObject\DecisionProcess::TYPE_CONSULTATION => $translate('documents.pv_editor.decision.type.consultation'),
        ];

        foreach ($embeddableDecisions->loadVisibleForOrganization($organizationId, $currentUserId) as $embeddableDecision) {
            if (!($embeddableDecision instanceof \dbObject\DecisionProcess) || (int)$embeddableDecision->getId() <= 0) {
                continue;
            }

            $decisionType = \dbObject\DecisionProcess::normalizeDecisionType($embeddableDecision->get('decision_type'));
            $embeddableDecisionsPayload[] = [
                'id' => (int)$embeddableDecision->getId(),
                'contextHolonId' => (int)$embeddableDecision->get('IDholon'),
                'title' => trim((string)$embeddableDecision->get('title')),
                'typeLabel' => (string)($decisionTypeLabels[$decisionType] ?? $decisionType),
                'summary' => $embeddableDecision->getCompactEmbedSummary(),
            ];
        }

        usort($embeddableDecisionsPayload, static function (array $left, array $right): int {
            return strnatcasecmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
        });
    }

    if ($type === 'projects') {
        $embeddableProjects = new \dbObject\ArrayProject();
        $embeddableProjects->loadForOrganization($organizationId, true, \dbObject\Project::KIND_STANDARD, true);
        $projectProposalContext = omoProjectsResolveContext($organizationId);
        $embeddableProjectIds = [];
        foreach ($embeddableProjects as $embeddableProject) {
            if ($embeddableProject instanceof \dbObject\Project && (int)$embeddableProject->getId() > 0) {
                $embeddableProjectIds[] = (int)$embeddableProject->getId();
            }
        }
        $followedEmbeddableProjectIds = \dbObject\ProjectFollower::getActiveProjectIds($embeddableProjectIds);
        foreach ($embeddableProjects as $embeddableProject) {
            if (!($embeddableProject instanceof \dbObject\Project) || (int)$embeddableProject->getId() <= 0 || empty($projectProposalContext['status']) || !omoProjectsCanViewProject($embeddableProject, $projectProposalContext)) {
                continue;
            }
            $projectHolon = $embeddableProject->getHolon();
            $projectSummary = trim(preg_replace('/\s+/', ' ', strip_tags((string)$embeddableProject->get('description'))));
            $projectResponsibleId = (int)$embeddableProject->get('IDuser');
            $projectStatus = \dbObject\Project::normalizeStatus($embeddableProject->get('status'));
            $embeddableProjectsPayload[] = [
                'id' => (int)$embeddableProject->getId(),
                'contextHolonId' => (int)$embeddableProject->get('IDholon'),
                'contextLabel' => $projectHolon instanceof \dbObject\Holon ? trim((string)$projectHolon->getDisplayName()) : '',
                'title' => trim((string)$embeddableProject->get('title')),
                'summary' => $projectSummary,
                'isMine' => $projectResponsibleId > 0 && $projectResponsibleId === $currentUserId,
                'isFollowed' => isset($followedEmbeddableProjectIds[(int)$embeddableProject->getId()]),
                'responsibleLabel' => $projectResponsibleId > 0
                    ? trim((string)\dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($projectResponsibleId, $organizationId))
                    : '',
                'status' => $projectStatus,
                'statusLabel' => \dbObject\Project::getOrganizationStatusLabel($organizationId, $projectStatus),
                'priorityLabel' => \dbObject\Project::normalizeLevel($embeddableProject->get('priority')) !== null
                    ? 'P' . (string)\dbObject\Project::normalizeLevel($embeddableProject->get('priority'))
                    : '',
                'sizeLabel' => \dbObject\Project::normalizeSize($embeddableProject->get('project_size')),
                'plannedStartLabel' => $formatProjectDate($embeddableProject->get('planned_start_date')),
                'plannedEndLabel' => $formatProjectDate($embeddableProject->get('planned_end_date')),
            ];
        }
        usort($embeddableProjectsPayload, static function (array $left, array $right): int {
            return strnatcasecmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
        });

    }

    if ($type === 'checklists') {
        $embeddableChecklists = new \dbObject\ArrayChecklist();
        $embeddableChecklists->loadForOrganization($organizationId, true, true);
        foreach ($embeddableChecklists as $embeddableChecklist) {
            if (!($embeddableChecklist instanceof \dbObject\Checklist) || \dbObject\Checklist::normalizeStatus($embeddableChecklist->get('status')) === \dbObject\Checklist::STATUS_RETIRED) { continue; }
            $checklistRoot = $embeddableChecklist->getTemplateRoot();
            if (!($checklistRoot instanceof \dbObject\Project)) { continue; }
            $checklistHolon = $checklistRoot->getHolon();
            $review = $embeddableChecklist->getPvReviewSummary();
            $embeddableChecklistsPayload[] = [
                'id' => (int)$embeddableChecklist->getId(),
                'contextHolonId' => (int)$checklistRoot->get('IDholon'),
                'contextLabel' => $checklistHolon instanceof \dbObject\Holon ? trim((string)$checklistHolon->getDisplayName()) : '',
                'title' => trim((string)$review['title']),
                'summary' => !empty($review['isContainer']) ? $translate('documents.pv_editor.checklist.review_container') : $translate('documents.pv_editor.checklist.review_runs'),
            ];
        }
        usort($embeddableChecklistsPayload, static fn (array $left, array $right): int => strnatcasecmp((string)($left['title'] ?? ''), (string)($right['title'] ?? '')));
    }

    if ($type === 'events') {
        $embeddableEvents = new \dbObject\ArrayEvent();
        $embeddableEvents->loadVisibleForOrganization($organizationId, $currentUserId);

        foreach ($embeddableEvents as $embeddableEvent) {
            if (!($embeddableEvent instanceof \dbObject\Event) || (int)$embeddableEvent->getId() <= 0) {
                continue;
            }

            $startAt = $embeddableEvent->get('start_at');
            $endAt = $embeddableEvent->get('end_at');
            $scheduleLabel = trim(
                ($startAt instanceof DateTimeInterface ? omoDocumentsPvEditorFormatDateTime($startAt) : '')
                . ($endAt instanceof DateTimeInterface ? ' - ' . omoDocumentsPvEditorFormatDateTime($endAt) : '')
            );
            $locationData = $embeddableEvent->getLocationDisplayData();
            $locationLabel = trim(implode(' | ', array_filter([
                trim((string)($locationData['address'] ?? '')),
                trim((string)($locationData['videoUrl'] ?? '')),
            ])));
            $embeddableEventsPayload[] = [
                'id' => (int)$embeddableEvent->getId(),
                'contextHolonId' => (int)$embeddableEvent->get('IDholon'),
                'title' => trim((string)$embeddableEvent->get('title')),
                'scheduleLabel' => $scheduleLabel,
                'locationLabel' => $locationLabel,
                'startAt' => $startAt instanceof DateTimeInterface ? $startAt->format(DATE_ATOM) : '',
            ];
        }

        usort($embeddableEventsPayload, static function (array $left, array $right): int {
            return strcmp((string)($left['startAt'] ?? ''), (string)($right['startAt'] ?? ''));
        });
    }

    if ($type === 'indicators') {
        $embeddableIndicators = new \dbObject\ArrayStatIndicator();
        $embeddableIndicators->loadForOrganization($organizationId);

        foreach ($embeddableIndicators as $embeddableIndicator) {
            if (!($embeddableIndicator instanceof \dbObject\StatIndicator) || (int)$embeddableIndicator->getId() <= 0) {
                continue;
            }

            $embeddableIndicatorsPayload[] = omoDocumentsPvEditorBuildIndicatorEmbedPayload(
                $embeddableIndicator,
                $isPvEditor,
                $translate
            );
        }

        $embeddableIndicatorGroups = new \dbObject\ArrayStatIndicatorGroup();
        $embeddableIndicatorGroups->loadForOrganization($organizationId);
        foreach ($embeddableIndicatorGroups as $embeddableIndicatorGroup) {
            if (!($embeddableIndicatorGroup instanceof \dbObject\StatIndicatorGroup) || !$embeddableIndicatorGroup->canView() || (int)$embeddableIndicatorGroup->getId() <= 0) {
                continue;
            }

            $groupAvailability = omoStatsGetGroupSourceAvailability($embeddableIndicatorGroup);
            $groupSeries = omoStatsGetGroupSeries($embeddableIndicatorGroup, $groupAvailability);
            $groupMode = \dbObject\StatIndicatorGroup::normalizeDisplayMode($embeddableIndicatorGroup->get('display_mode'));
            $groupMemberCount = count(omoStatsCollectionItems($embeddableIndicatorGroup->getItems(), \dbObject\StatIndicatorGroupItem::class));
            $groupIsOverdue = omoStatsGetGroupOverdueInfo($embeddableIndicatorGroup, null, $groupAvailability)['is_overdue'];
            $embeddableIndicatorsPayload[] = [
                'id' => (int)$embeddableIndicatorGroup->getId(),
                'kind' => 'group',
                'contextHolonId' => (int)$embeddableIndicatorGroup->get('IDholon'),
                'title' => trim((string)$embeddableIndicatorGroup->get('name')),
                'contextLabel' => $groupMode === \dbObject\StatIndicatorGroup::DISPLAY_SUM
                    ? $translate('documents.pv_editor.indicator.group_sum')
                    : $translate('documents.pv_editor.indicator.group_overlay'),
                'valueLabel' => $translate('documents.pv_editor.indicator.group_members', ['count' => $groupMemberCount]),
                'dateLabel' => '',
                'statusLabel' => $groupAvailability['status'] === 'current'
                    ? ($groupIsOverdue
                        ? $translate('documents.pv_editor.indicator.overdue')
                        : $translate('documents.pv_editor.indicator.current'))
                    : implode(' ', omoStatsGroupSourceMessages($groupAvailability)),
                'sourceStatus' => $groupAvailability['status'],
                'isOverdue' => $groupIsOverdue,
                'overdueSeverity' => $groupIsOverdue ? 'error' : 'none',
                'chartHtml' => omoStatsRenderGroupChart($embeddableIndicatorGroup, $groupSeries, 'compact', $groupIsOverdue, false, $groupAvailability),
            ];
        }

        usort($embeddableIndicatorsPayload, static function (array $left, array $right): int {
            return strnatcasecmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
        });
    }


    return match ($type) {
        'documents' => $embeddableDocumentsPayload,
        'decisions' => $embeddableDecisionsPayload,
        'projects' => $embeddableProjectsPayload,
        'checklists' => $embeddableChecklistsPayload,
        'events' => $embeddableEventsPayload,
        'indicators' => $embeddableIndicatorsPayload,
        default => [],
    };
}
