<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/helpers.php';
require_once dirname(__DIR__, 4) . '/common/patreon.php';
require_once dirname(__DIR__, 4) . '/common/openai_text.php';
require_once dirname(__DIR__, 4) . '/common/object_visibility_selector.php';
require_once dirname(__DIR__, 2) . '/stats/shared.php';
require_once dirname(__DIR__, 2) . '/projects/shared.php';

$sourceLang = omoDocumentsPvEditorSourceLang();
$lang = omoLoadTranslationBundle('omo_documents_pv_editor', $sourceLang);

function omoDocumentsPvEditorT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$organizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$currentUserId = (int)commonGetCurrentUserId();
$publicParticipationLink = commonGetPublicPvParticipationLink();
$isPublicParticipation = $publicParticipationLink instanceof \dbObject\DocumentShareLink
    && (int)$publicParticipationLink->get('IDdocument') === $documentId
    && (int)$publicParticipationLink->get('IDorganization') === $organizationId;
$editorActorKey = $isPublicParticipation ? 'share:' . (int)$publicParticipationLink->getId() : 'user:' . $currentUserId;
$tokenSessionKey = $organizationId . ':' . $documentId . ':' . $editorActorKey;
if (!isset($_SESSION['omo_pv_editor_tokens']) || !is_array($_SESSION['omo_pv_editor_tokens'])) {
    $_SESSION['omo_pv_editor_tokens'] = [];
}
$editorToken = trim((string)($_SESSION['omo_pv_editor_tokens'][$tokenSessionKey] ?? ''));
if ($editorToken === '') {
    try {
        $editorToken = bin2hex(random_bytes(16));
    } catch (\Throwable $exception) {
        $editorToken = uniqid('pv', true);
    }
    $_SESSION['omo_pv_editor_tokens'][$tokenSessionKey] = $editorToken;
}

$document = new \dbObject\Document();
$accessGranted = $documentId > 0
    && $organizationId > 0
    && $document->load($documentId)
    && ($isPublicParticipation || ($currentUserId > 0 && $document->canUserOpenPvEditor($currentUserId, $organizationId)));

$escape = 'omoApiEscape';
$uiText = omoDocumentsPvEditorBuildUiText('omoDocumentsPvEditorT');

$formatProjectDate = static function ($value): string {
    return $value instanceof DateTimeInterface ? $value->format('d.m.Y') : '';
};

if (!$accessGranted) {
    http_response_code(403);
    ?>
    <div class="omo-empty-state"><?= $escape(omoDocumentsPvEditorT('documents.pv_editor.error.forbidden')) ?></div>
    <?php
    exit;
}

$event = $document->getAssociatedEvent();
$hasAssociatedEvent = $event instanceof \dbObject\Event;
$pvApplicationContextHolonId = $document->getPvContextHolonId();
$resourcePickerInitialHolonId = (int)$document->get('IDholon');
if ($resourcePickerInitialHolonId <= 0 && $hasAssociatedEvent) {
    $resourcePickerInitialHolonId = (int)$event->get('IDholon');
}
$organization = new \dbObject\Organization();
$hasOrganization = $organizationId > 0 && $organization->load($organizationId);
$uiText = omoDocumentsPvEditorBuildUiText(
    'omoDocumentsPvEditorT',
    $hasOrganization ? $organization->getPvPriorityLabels() : []
);
$projectEmbedCreateHolon = $resourcePickerInitialHolonId > 0 ? new \dbObject\Holon() : null;
if (!($projectEmbedCreateHolon instanceof \dbObject\Holon) || !$projectEmbedCreateHolon->load($resourcePickerInitialHolonId)) {
    $projectEmbedCreateHolon = $hasOrganization ? $organization->getEnabledStructuralRootHolon() : null;
}
$projectEmbedCreateHolonId = $projectEmbedCreateHolon instanceof \dbObject\Holon ? (int)$projectEmbedCreateHolon->getId() : 0;
$projectEmbedCreateHolonLabel = $projectEmbedCreateHolon instanceof \dbObject\Holon ? trim((string)$projectEmbedCreateHolon->getDisplayName()) : '';
$projectEmbedCreateResponsibleLabel = $currentUserId > 0
    ? trim((string)\dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($currentUserId, $organizationId))
    : '';
$publicParticipationCanUseStructure = $isPublicParticipation
    && commonPvParticipationRecipientCanUseStructure($publicParticipationLink, $organizationId);
$hasTeamApplication = !$isPublicParticipation && $hasOrganization && $organization->isApplicationEnabled('team', $currentUserId);
$hasStructureApplication = $isPublicParticipation
    ? $publicParticipationCanUseStructure
    : ($hasOrganization && $organization->isStructureApplicationEnabled($currentUserId));
$hasDocumentsApplication = !$isPublicParticipation && $hasOrganization && $organization->isApplicationEnabled('documents', $currentUserId);
$hasDecisionApplication = !$isPublicParticipation && $hasOrganization && $organization->isApplicationEnabled('decision', $currentUserId);
$hasCalendarApplication = !$isPublicParticipation && $hasOrganization && $organization->isApplicationEnabled('calendar', $currentUserId);
$hasStatsApplication = !$isPublicParticipation && $hasOrganization && $organization->isApplicationEnabled('stats', $currentUserId);
$openAiAvailable = commonOpenAiGetApiKey() !== '';
$canUseAiTools = $openAiAvailable && patreonUserCanUseAi($currentUserId);
$hasUpcomingAssociatedEvent = $hasAssociatedEvent && $event->isUpcoming();
$publicParticipationUserId = $isPublicParticipation
    ? commonPvParticipationRecipientOrganizationUserId($publicParticipationLink, $organizationId)
    : 0;
$canManagePvStage = $document->canManagePvStage(
    $organizationId,
    $isPublicParticipation ? $publicParticipationUserId : $currentUserId
);
$pvEditorUserId = $document->getPvEditorUserId();
$pvEditorLabel = $pvEditorUserId > 0
    ? \dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($pvEditorUserId, $organizationId)
    : '';
$pvCreatorUserId = $document->getCreatedByUserId();
$pvCreatorLabel = $pvCreatorUserId > 0
    ? \dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($pvCreatorUserId, $organizationId)
    : '';
$isPvEditor = !$isPublicParticipation && $document->isPvEditor($currentUserId);
$canManagePvDocument = !$isPublicParticipation && $document->canUserManagePvDocument($currentUserId);
$pvApplicationTabsCsrf = '';
if (!$isPublicParticipation) {
    if (empty($_SESSION['omo_pv_application_tabs_csrf'])) {
        $_SESSION['omo_pv_application_tabs_csrf'] = bin2hex(random_bytes(32));
    }
    $pvApplicationTabsCsrf = (string)$_SESSION['omo_pv_application_tabs_csrf'];
}
$isPvReview = $document->getPvStage() === \dbObject\Document::PV_STAGE_REVIEW;
$canEditPvDocumentHeader = $canManagePvDocument && !$isPvReview;
$canExtendAssociatedEvent = !$isPublicParticipation
    && $hasAssociatedEvent
    && $document->getPvStage() === \dbObject\Document::PV_STAGE_MEETING;
$canPassPvEditor = $isPvEditor && !$isPvReview;
$isPvTemplate = $document->isPvTemplate();
$canCreatePvGroups = !$isPublicParticipation && $document->canUserCreatePvGroups($currentUserId);
$canClaimPvEditor = !$isPublicParticipation && $document->canUserClaimPvEditor($organizationId, $currentUserId);
$canReplacePvEditor = !$isPublicParticipation && $document->canUserReplacePvEditor($organizationId, $currentUserId);
$pvEditorHandoverOpen = $document->isPvEditorHandoverOpen();
$isPvValidated = $document->isPvValidated();
$locationData = $hasAssociatedEvent ? $event->getLocationDisplayData() : [];
$locationParts = [];
if (trim((string)($locationData['address'] ?? '')) !== '') {
    $locationParts[] = trim((string)$locationData['address']);
}
if (trim((string)($locationData['videoUrl'] ?? '')) !== '') {
    $locationParts[] = trim((string)$locationData['videoUrl']);
}
$eventStartAt = $hasAssociatedEvent ? $event->get('start_at') : null;
$eventEndAt = $hasAssociatedEvent ? $event->get('end_at') : null;
$documentTitle = trim((string)$document->get('title')) !== ''
    ? trim((string)$document->get('title'))
    : omoDocumentsPvEditorT('documents.pv_editor.page.title');
$documentDescription = trim((string)$document->get('description'));
$documentVisibility = $document->getVisibilityDisplayData($organizationId);
$documentVisibilityType = (string)($documentVisibility['type'] ?? \dbObject\ObjectVisibility::TYPE_ORGANIZATION);
$documentVisibilityOptions = \dbObject\ObjectVisibility::getVisibilityTypeOptions();
$documentModifiedAt = $document->get('datemodification');
$documentModifiedAtValue = $documentModifiedAt instanceof DateTimeInterface ? $documentModifiedAt->format('Y-m-d H:i:s.u') : '';
$documentSyncVersion = hash('sha256', implode('|', [
    $documentModifiedAtValue,
    $document->getPvStage(),
    (string)$pvEditorUserId,
    $pvEditorHandoverOpen ? '1' : '0',
    $documentTitle,
    $documentDescription,
    $documentVisibilityType,
    $isPvTemplate ? '1' : '0',
]));
$pollingRevision = $document->getPvEditorPollingRevision($organizationId);
$eventTitle = $hasAssociatedEvent
    ? (trim((string)$event->get('title')) !== ''
        ? trim((string)$event->get('title'))
        : omoDocumentsPvEditorT('documents.pv_editor.event.fallback', ['id' => (int)$event->getId()]))
    : '';
$eventSchedule = '';
if ($eventStartAt instanceof DateTimeInterface) {
    $eventSchedule = omoDocumentsPvEditorFormatDateTime($eventStartAt);
    if ($eventEndAt instanceof DateTimeInterface) {
        $eventSchedule .= $eventStartAt->format('Y-m-d') === $eventEndAt->format('Y-m-d')
            ? ' - ' . $eventEndAt->format('H:i')
            : ' - ' . omoDocumentsPvEditorFormatDateTime($eventEndAt);
    }
}
$eventLocation = implode(' | ', $locationParts);
$eventStartAtIso = $eventStartAt instanceof DateTimeInterface ? $eventStartAt->format(DATE_ATOM) : '';
$eventEndAtIso = $eventEndAt instanceof DateTimeInterface ? $eventEndAt->format(DATE_ATOM) : '';
$pvStage = $document->getPvStage();
$pvStageOptions = [
    \dbObject\Document::PV_STAGE_PREPARATION => omoDocumentsPvEditorT('documents.pv_editor.field.stage.preparation'),
    \dbObject\Document::PV_STAGE_MEETING => omoDocumentsPvEditorT('documents.pv_editor.field.stage.meeting'),
    \dbObject\Document::PV_STAGE_REVIEW => omoDocumentsPvEditorT('documents.pv_editor.field.stage.review'),
    \dbObject\Document::PV_STAGE_VALIDATED => omoDocumentsPvEditorT('documents.pv_editor.field.stage.validated'),
];
$hasPvInvitationEditor = true;
$canManagePvInvitations = $hasPvInvitationEditor
    && $pvStage === \dbObject\Document::PV_STAGE_PREPARATION
    && $canManagePvDocument;
$pvInvitationPopupUrl = '/omo/api/calendar/invitations_popup.php?oid=' . rawurlencode((string)$organizationId)
    . ($hasAssociatedEvent
        ? '&id=' . rawurlencode((string)(int)$event->getId())
        : '&document_id=' . rawurlencode((string)(int)$document->getId()))
    . '&pv_editor=1';
$pvInvitationSendPopupUrl = '/omo/api/documents/pv/send_invitations_popup.php?oid=' . rawurlencode((string)$organizationId)
    . '&id=' . rawurlencode((string)(int)$document->getId());

$points = new \dbObject\ArrayDocumentPvPoint();
$points = $document->getVisiblePvPointsForUser($currentUserId, true);
$groupSummaryMap = omoDocumentsPvEditorBuildGroupSummaryMap($points);
$pointPositionLabels = \dbObject\DocumentPvPoint::buildHierarchyPositionLabels($points);
$authorOptions = $isPublicParticipation ? [[
    'value' => $publicParticipationLink->getRecipientUserId() > 0
        ? 'user:' . $publicParticipationLink->getRecipientUserId()
        : 'email:' . $publicParticipationLink->getRecipientEmail(),
    'userId' => $publicParticipationLink->getRecipientUserId(),
    'email' => $publicParticipationLink->getRecipientEmail(),
    'label' => $publicParticipationLink->getRecipientUserId() > 0
        ? \dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($publicParticipationLink->getRecipientUserId(), $organizationId)
        : $publicParticipationLink->getRecipientEmail(),
]] : $document->getPvPointAuthorOptions($organizationId);
$pointCards = [];
$pointNavItems = [];
$pointPayloads = [];
$embeddableDocumentsPayload = [];
$embeddableDecisionsPayload = [];
$embeddableEventsPayload = [];
$embeddableIndicatorsPayload = [];
$embeddableProjectsPayload = [];
$embeddableChecklistsPayload = [];
$showPublicAttendance = $isPublicParticipation;
$showAttendance = $hasTeamApplication || $showPublicAttendance;
$attendancePayload = $showAttendance
    ? ($showPublicAttendance
        ? omoDocumentsPvEditorBuildPublicAttendancePayloadFromDocument($document, $organizationId)
        : omoDocumentsPvEditorBuildAttendancePayloadFromDocument($document, $organizationId))
    : null;

$pvApplicationCatalog = [];
$pvApplicationTabsPayload = [];
if (!$isPublicParticipation && $hasOrganization) {
    foreach ($organization->getApplications($currentUserId) as $application) {
        if (!($application instanceof \dbObject\Application) || (int)$application->getId() <= 0) {
            continue;
        }

        $applicationUrl = trim((string)$application->getResolvedUrl());
        if ($applicationUrl === '') {
            continue;
        }

        $applicationIcon = trim((string)$application->get('icon'));
        if ($applicationIcon !== '' && $applicationIcon[0] !== '/' && preg_match('/^[a-z][a-z0-9+.-]*:/i', $applicationIcon) !== 1) {
            $applicationIcon = '/omo/' . ltrim($applicationIcon, '/');
        }

        $pvApplicationCatalog[(int)$application->getId()] = [
            'applicationId' => (int)$application->getId(),
            'label' => trim((string)$application->get('label')),
            'icon' => $applicationIcon,
            'url' => $applicationUrl,
        ];
    }

    foreach ($document->getPvApplicationTabs(true) as $applicationTab) {
        if (!($applicationTab instanceof \dbObject\DocumentApplicationTab)) {
            continue;
        }
        $applicationId = (int)$applicationTab->get('IDapplication');
        if (!isset($pvApplicationCatalog[$applicationId])) {
            continue;
        }

        $pvApplicationTabsPayload[] = array_merge(
            $pvApplicationCatalog[$applicationId],
            ['tabId' => (int)$applicationTab->getId()]
        );
    }
}
$pvApplicationCatalog = array_values($pvApplicationCatalog);
$showPvApplicationTabs = !$isPublicParticipation
    && ($canManagePvDocument || count($pvApplicationTabsPayload) > 0);

if ($hasDocumentsApplication) {
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

if ($hasDecisionApplication) {
    $embeddableDecisions = new \dbObject\ArrayDecisionProcess();
    $decisionTypeLabels = [
        \dbObject\DecisionProcess::TYPE_DECISION => omoDocumentsPvEditorT('documents.pv_editor.decision.type.decision'),
        \dbObject\DecisionProcess::TYPE_CONSULTATION => omoDocumentsPvEditorT('documents.pv_editor.decision.type.consultation'),
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

if ($hasOrganization && ($organization->isApplicationEnabled('processus', $currentUserId) || $organization->isApplicationEnabled('checklist', $currentUserId))) {
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
            'summary' => !empty($review['isContainer']) ? omoDocumentsPvEditorT('documents.pv_editor.checklist.review_container') : omoDocumentsPvEditorT('documents.pv_editor.checklist.review_runs'),
        ];
    }
    usort($embeddableChecklistsPayload, static fn (array $left, array $right): int => strnatcasecmp((string)($left['title'] ?? ''), (string)($right['title'] ?? '')));
}

if ($hasCalendarApplication) {
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

if ($hasStatsApplication) {
    $embeddableIndicators = new \dbObject\ArrayStatIndicator();
    $embeddableIndicators->loadForOrganization($organizationId);

    foreach ($embeddableIndicators as $embeddableIndicator) {
        if (!($embeddableIndicator instanceof \dbObject\StatIndicator) || (int)$embeddableIndicator->getId() <= 0) {
            continue;
        }

        $embeddableIndicatorsPayload[] = omoDocumentsPvEditorBuildIndicatorEmbedPayload(
            $embeddableIndicator,
            $isPvEditor,
            'omoDocumentsPvEditorT'
        );
    }

    $embeddableIndicatorGroups = new \dbObject\ArrayStatIndicatorGroup();
    $embeddableIndicatorGroups->loadForOrganization($organizationId);
    foreach ($embeddableIndicatorGroups as $embeddableIndicatorGroup) {
        if (!($embeddableIndicatorGroup instanceof \dbObject\StatIndicatorGroup) || !$embeddableIndicatorGroup->canView() || (int)$embeddableIndicatorGroup->getId() <= 0) {
            continue;
        }

        $groupSeries = omoStatsGetGroupSeries($embeddableIndicatorGroup);
        $groupMode = \dbObject\StatIndicatorGroup::normalizeDisplayMode($embeddableIndicatorGroup->get('display_mode'));
        $groupMemberCount = count(omoStatsCollectionItems($embeddableIndicatorGroup->getItems(), \dbObject\StatIndicatorGroupItem::class));
        $groupIsOverdue = omoStatsIsGroupOverdue($embeddableIndicatorGroup);
        $embeddableIndicatorsPayload[] = [
            'id' => (int)$embeddableIndicatorGroup->getId(),
            'kind' => 'group',
            'contextHolonId' => (int)$embeddableIndicatorGroup->get('IDholon'),
            'title' => trim((string)$embeddableIndicatorGroup->get('name')),
            'contextLabel' => $groupMode === \dbObject\StatIndicatorGroup::DISPLAY_SUM
                ? omoDocumentsPvEditorT('documents.pv_editor.indicator.group_sum')
                : omoDocumentsPvEditorT('documents.pv_editor.indicator.group_overlay'),
            'valueLabel' => omoDocumentsPvEditorT('documents.pv_editor.indicator.group_members', ['count' => $groupMemberCount]),
            'dateLabel' => '',
            'statusLabel' => $groupIsOverdue
                ? omoDocumentsPvEditorT('documents.pv_editor.indicator.overdue')
                : omoDocumentsPvEditorT('documents.pv_editor.indicator.current'),
            'isOverdue' => $groupIsOverdue,
            'overdueSeverity' => $groupIsOverdue ? 'error' : 'none',
            'chartHtml' => omoStatsRenderGroupChart($embeddableIndicatorGroup, $groupSeries, 'compact', $groupIsOverdue),
        ];
    }

    usort($embeddableIndicatorsPayload, static function (array $left, array $right): int {
        return strnatcasecmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
    });
}

$authorHolonOptions = omoDocumentsPvEditorBuildAuthorHolonOptions(
    $document,
    $authorOptions,
    $hasStructureApplication
);
$pointDiscussionSummaryMap = $pvStage === \dbObject\Document::PV_STAGE_REVIEW
    ? omoDocumentsPvEditorBuildPointDiscussionSummaryMap(
        $organizationId,
        $points,
        $isPublicParticipation ? $publicParticipationUserId : $currentUserId,
        $isPublicParticipation ? (int)$publicParticipationLink->getId() : 0
    )
    : [];

foreach ($points as $point) {
    if (!($point instanceof \dbObject\DocumentPvPoint) || (int)$point->getId() <= 0) {
        continue;
    }

    $payload = omoDocumentsPvEditorBuildContextualPointPayload(
        $point,
        $document,
        $organizationId,
        $currentUserId,
        $editorToken,
        $uiText,
        $hasStructureApplication,
        $authorOptions,
        $authorHolonOptions,
        (string)($pointPositionLabels[(int)$point->getId()] ?? '--'),
        $groupSummaryMap[(int)$point->getId()] ?? [],
        $pointDiscussionSummaryMap[(int)$point->getId()] ?? [],
        $isPublicParticipation ? $publicParticipationLink : null
    );
    if (!$point->isGroup()) {
        $pointCards[] = $payload['cardHtml'];
    }
    $pointNavItems[] = $payload['navHtml'];
    $pointPayloads[] = $payload;
}
$isPvReviewDiscussion = $pvStage === \dbObject\Document::PV_STAGE_REVIEW;
?>
<link rel="stylesheet" href="/common/choice/change-details.css?v=20260923-lifecycle-details">
<?php if ($isPvReviewDiscussion): ?>
<link rel="stylesheet" href="/common/chat/thread.css?v=20260821-pv-review-access-2">
<?php endif; ?>
<div
    class="omo-pv-editor<?= $showPvApplicationTabs ? ' omo-pv-editor--has-application-tabs' : '' ?>"
    data-omo-pv-editor-root="1"
    data-omo-pv-editor-document-id="<?= (int)$document->getId() ?>"
    data-omo-pv-editor-oid="<?= (int)$organizationId ?>"
    data-omo-pv-editor-user-id="<?= (int)$currentUserId ?>"
    data-omo-pv-editor-token="<?= $escape($editorToken) ?>"
    data-omo-pv-editor-action-url="<?= $escape('/omo/api/documents/pv/action.php') ?>"
    data-omo-pv-application-tabs-action-url="<?= $escape('/omo/api/documents/pv/application_tabs.php') ?>"
    data-omo-pv-application-tabs-csrf="<?= $escape($pvApplicationTabsCsrf) ?>"
    data-omo-pv-application-tabs-manage="<?= $canManagePvDocument ? '1' : '0' ?>"
    data-omo-pv-application-tabs-cid="<?= (int)$pvApplicationContextHolonId ?>"
>
    <link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/documents/pv/editor.css') ?>">

    <?php if ($showPvApplicationTabs): ?>
    <nav class="generic-tabs generic-tabs__list omo-pv-editor__application-tabs" data-omo-pv-application-tabs role="tablist" aria-label="<?= $escape((string)$uiText['applicationPickerTitle']) ?>">
        <button type="button" class="generic-tabs__tab omo-pv-editor__application-tab is-active" data-omo-pv-application-tab="0" role="tab" aria-selected="true">
            <img class="omo-pv-editor__application-tab-icon black-icon" src="/omo/assets/images/documents/pv.png" alt="" aria-hidden="true">
            <span class="omo-pv-editor__application-tab-label"><?= $escape((string)$uiText['applicationEditorTab']) ?></span>
        </button>
        <?php foreach ($pvApplicationTabsPayload as $applicationTabPayload): ?>
            <span class="omo-pv-editor__application-tab-item" data-omo-pv-application-tab-item="<?= (int)$applicationTabPayload['tabId'] ?>">
                <button type="button" class="generic-tabs__tab omo-pv-editor__application-tab" data-omo-pv-application-tab="<?= (int)$applicationTabPayload['tabId'] ?>" role="tab" aria-selected="false">
                    <?php if ((string)$applicationTabPayload['icon'] !== ''): ?><img class="omo-pv-editor__application-tab-icon black-icon" src="<?= $escape((string)$applicationTabPayload['icon']) ?>" alt="" aria-hidden="true"><?php endif; ?>
                    <span class="omo-pv-editor__application-tab-label"><?= $escape((string)$applicationTabPayload['label']) ?></span>
                </button>
            </span>
        <?php endforeach; ?>
        <?php if ($canManagePvDocument): ?>
            <button type="button" class="generic-action-button generic-action-button--main generic-action-button--icon-only omo-pv-editor__application-tab-add" data-omo-pv-application-tab-add aria-label="<?= $escape((string)$uiText['applicationAdd']) ?>" title="<?= $escape((string)$uiText['applicationAdd']) ?>">+</button>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

    <aside class="omo-pv-editor__sidebar">
        <section class="omo-pv-editor__panel generic-section omo-pv-editor__agenda-panel">
            <div class="omo-pv-editor__toolbar">
                <?php if ($isPvEditor && !$isPvReview): ?>
                    <details class="omo-pv-editor__sort-menu" data-omo-pv-sort-menu>
                        <summary class="generic-action-button generic-action-button--secondary" title="<?= $escape((string)$uiText['sort']) ?>" aria-label="<?= $escape((string)$uiText['sort']) ?>"><img src="/omo/assets/images/documents/sort-ascending.png" class="omo-pv-editor__toolbar-icon black-icon" alt="" aria-hidden="true"></summary>
                        <form class="omo-pv-editor__sort-menu-panel" data-omo-pv-sort-form>
                            <fieldset class="omo-pv-editor__sort-options">
                                <legend><?= $escape((string)$uiText['sort']) ?></legend>
                                <label class="omo-pv-editor__sort-option"><input type="radio" name="omo_pv_sort_mode" value="none" checked> <?= $escape((string)$uiText['sortNone']) ?></label>
                                <label class="omo-pv-editor__sort-option"><input type="radio" name="omo_pv_sort_mode" value="priority"> <?= $escape((string)$uiText['sortPriority']) ?></label>
                                <label class="omo-pv-editor__sort-option"><input type="radio" name="omo_pv_sort_mode" value="creation"> <?= $escape((string)$uiText['sortCreation']) ?></label>
                                <label class="omo-pv-editor__sort-option"><input type="radio" name="omo_pv_sort_mode" value="person"> <?= $escape((string)$uiText['sortPerson']) ?></label>
                                <label class="omo-pv-editor__sort-option"><input type="radio" name="omo_pv_sort_mode" value="role"> <?= $escape((string)$uiText['sortRole']) ?></label>
                                <label class="omo-pv-editor__sort-option"><input type="radio" name="omo_pv_sort_mode" value="duration"> <?= $escape((string)$uiText['sortDuration']) ?></label>
                            </fieldset>
                            <div class="omo-pv-editor__sort-flags">
                                <label class="omo-pv-editor__sort-option"><input type="checkbox" name="omo_pv_sort_randomize_ties" value="1"> <?= $escape((string)$uiText['sortRandomizeTies']) ?></label>
                                <label class="omo-pv-editor__sort-option"><input type="checkbox" name="omo_pv_sort_handled_last" value="1"> <?= $escape((string)$uiText['sortHandledLast']) ?></label>
                                <label class="omo-pv-editor__sort-option"><input type="checkbox" name="omo_pv_sort_group_by_type" value="1"> <?= $escape((string)$uiText['sortGroupByType']) ?></label>
                                <label class="omo-pv-editor__sort-option"><input type="checkbox" name="omo_pv_sort_compact_display" value="1"> <?= $escape((string)$uiText['sortCompactDisplay']) ?></label>
                            </div>
                            <button type="submit" class="generic-action-button generic-action-button--main omo-pv-editor__sort-submit" data-omo-pv-sort-submit data-omo-pv-sort-label="<?= $escape((string)$uiText['sortApply']) ?>" data-omo-pv-sort-applying-label="<?= $escape((string)$uiText['sortApplying']) ?>"><?= $escape((string)$uiText['sortApply']) ?></button>
                        </form>
                    </details>
                <?php endif; ?>
                <button type="button" class="omo-pv-editor__delete-dropzone" data-omo-pv-delete-dropzone title="<?= $escape((string)$uiText['deleteItem']) ?>" aria-label="<?= $escape((string)$uiText['deleteItem']) ?>"<?= $isPvReview ? ' hidden' : '' ?>><img src="/omo/assets/images/documents/poubelle.png" alt="" aria-hidden="true"></button>
                <?php if ($canCreatePvGroups): ?>
                    <button type="button" class="generic-action-button generic-action-button--secondary omo-pv-editor__add-button" data-omo-pv-editor-add-group title="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.action.add_group')) ?>" aria-label="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.action.add_group')) ?>"><img src="/omo/assets/images/documents/add-folder.png" class="omo-pv-editor__toolbar-icon black-icon" alt="" aria-hidden="true"></button>
                <?php endif; ?>
                <button type="button" class="generic-action-button generic-action-button--main omo-pv-editor__add-button" data-omo-pv-editor-add-point<?= $isPvValidated || $isPvReview ? ' disabled' : '' ?> title="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.action.add_point')) ?>" aria-label="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.action.add_point')) ?>"><img src="/omo/assets/images/documents/add.png" class="omo-pv-editor__toolbar-icon omo-pv-editor__toolbar-icon--on-main" alt="" aria-hidden="true"></button>
            </div>
            <div class="omo-pv-editor__nav generic-stack generic-stack--compact" data-omo-pv-editor-nav>
                <?php if (count($pointNavItems) === 0): ?>
                    <div class="omo-empty-state omo-pv-editor__empty"><?= $escape(omoDocumentsPvEditorT('documents.pv_editor.nav.empty')) ?></div>
                <?php else: ?>
                    <?= implode('', $pointNavItems) ?>
                <?php endif; ?>
            </div>
        </section>

        <div
            class="omo-pv-editor__timing-resizer"
            data-omo-pv-timing-resizer
            role="separator"
            aria-orientation="horizontal"
            aria-label="<?= $escape((string)$uiText['resizeTiming']) ?>"
        ></div>

        <section class="omo-pv-editor__panel generic-section omo-pv-editor__timing" data-omo-pv-timing-panel>
            <div class="omo-pv-editor__timing-chart-shell">
                <div class="omo-pv-editor__timing-chart" data-omo-pv-timing-chart="1">
                    <svg viewBox="0 0 100 100" aria-hidden="true" focusable="false">
                        <circle cx="50" cy="50" r="50" fill="#e2e8f0" data-omo-pv-timing-outer-base></circle>
                        <path data-omo-pv-timing-outer-handled fill="#22c55e"></path>
                        <path data-omo-pv-timing-outer-remaining fill="#f59e0b"></path>
                        <path data-omo-pv-timing-outer-buffer fill="#cbd5e1"></path>
                        <circle cx="50" cy="50" r="34" fill="#e2e8f0"></circle>
                        <path data-omo-pv-timing-sector fill="#ef4444"></path>
                    </svg>
                    <div class="omo-pv-editor__timing-center">
                        <strong data-omo-pv-timing-center><?= $escape((string)$uiText['notStartedValue']) ?></strong>

                    </div>
                </div>
                <div class="omo-pv-editor__timing-legend">
                    <div class="omo-pv-editor__timing-legend-item">
                        <span class="omo-pv-editor__timing-legend-swatch" style="background:#22c55e"></span>
                        <span><?= $escape((string)$uiText['handledLegend']) ?></span>
                        <strong data-omo-pv-timing-legend-handled><?= $escape((string)$uiText['notStartedValue']) ?></strong>
                    </div>
                    <div class="omo-pv-editor__timing-legend-item">
                        <span class="omo-pv-editor__timing-legend-swatch" style="background:#f59e0b"></span>
                        <span><?= $escape((string)$uiText['remainingLegend']) ?></span>
                        <strong data-omo-pv-timing-legend-remaining><?= $escape((string)$uiText['notStartedValue']) ?></strong>
                    </div>
                    <div class="omo-pv-editor__timing-legend-item" data-omo-pv-timing-buffer-legend<?= $hasAssociatedEvent ? '' : ' hidden' ?>>
                        <span class="omo-pv-editor__timing-legend-swatch" style="background:#cbd5e1" data-omo-pv-timing-buffer-swatch></span>
                        <span data-omo-pv-timing-buffer-label><?= $escape((string)$uiText['marginLegend']) ?></span>
                        <strong data-omo-pv-timing-legend-buffer><?= $escape((string)$uiText['notStartedValue']) ?></strong>
                    </div>
                </div>
            </div>
        </section>
    </aside>

    <button
        type="button"
        class="omo-pv-editor__resizer"
        data-omo-pv-editor-resizer
        aria-label="<?= $escape($documentTitle !== '' ? $documentTitle : omoDocumentsPvEditorT('documents.pv_editor.page.title')) ?>"
    ></button>

    <section class="omo-pv-editor__main generic-stack">
        <section class="omo-pv-editor__page-head generic-stack">
            <div class="omo-pv-editor__panel generic-section omo-pv-editor__header-card">
            <div class="omo-pv-editor__page-title">
                <div class="omo-pv-editor__identity-copy generic-stack generic-stack--compact">
                <?php if ($canEditPvDocumentHeader): ?>
                    <div class="omo-pv-editor__document-meta-editor" data-omo-pv-document-meta-editor>
                        <input
                            type="text"
                            class="omo-pv-editor__document-title-input"
                            value="<?= $escape($documentTitle) ?>"
                            aria-label="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.field.document_title')) ?>"
                            data-omo-pv-document-title
                        >
                        <textarea
                            class="omo-pv-editor__document-description-input"
                            rows="1"
                            placeholder="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.field.document_description')) ?>"
                            aria-label="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.field.document_description')) ?>"
                            data-omo-pv-document-description
                        ><?= $escape($documentDescription) ?></textarea>
                        <div class="omo-pv-editor__document-meta-actions">
                            <button type="button" class="generic-action-button omo-pv-editor__document-meta-save" data-omo-pv-document-meta-save disabled><?= $escape(omoDocumentsPvEditorT('documents.pv_editor.state.saved')) ?></button>
                            <?php if ($canUseAiTools): ?>
                                <button type="button" class="generic-action-button generic-action-button--secondary omo-pv-editor__document-meta-summary" data-omo-pv-document-auto-summary<?= $document->getPvStage() !== \dbObject\Document::PV_STAGE_REVIEW ? ' hidden' : '' ?>><?= $escape((string)$uiText['autoSummary']) ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <h2 data-omo-pv-document-title-display><?= $escape($documentTitle) ?></h2>
                    <p data-omo-pv-document-description-display<?= $documentDescription === '' ? ' hidden' : '' ?>><?= $escape($documentDescription) ?></p>
                <?php endif; ?>
                </div>
            </div>
            <div class="omo-pv-editor__page-side generic-stack">
                <?php if ($canEditPvDocumentHeader): ?>
                    <div class="omo-pv-editor__document-visibility" data-omo-pv-document-visibility>
                        <?= commonRenderObjectVisibilitySelector(array(
                            'inputName' => 'pv_document_visibility',
                            'idPrefix' => 'pv-document-visibility',
                            'ariaLabel' => omoDocumentsPvEditorT('documents.pv_editor.field.document_visibility'),
                            'selectedValue' => $documentVisibilityType,
                            'optionLabels' => $documentVisibilityOptions,
                        )) ?>
                    </div>
                <?php endif; ?>
                <div class="omo-pv-editor__stage-field">
                    <div
                        class="omo-visibility-choice omo-pv-editor__stage-choice"
                        data-omo-pv-stage-choice
                        role="group"
                        aria-label="<?= $escape((string)$uiText['stage']) ?>"
                        style="--omo-visibility-option-count: <?= max(1, count($pvStageOptions)) ?>; --omo-visibility-active-index: <?= max(0, array_search($pvStage, array_keys($pvStageOptions), true)) ?>;"
                    >
                        <?php foreach ($pvStageOptions as $stageValue => $stageLabel): ?>
                            <button
                                type="button"
                                class="omo-visibility-choice__button omo-pv-editor__stage-choice-button<?= $pvStage === $stageValue ? ' is-active' : '' ?>"
                                data-omo-pv-stage-option
                                data-omo-pv-stage-value="<?= $escape($stageValue) ?>"
                                aria-pressed="<?= $pvStage === $stageValue ? 'true' : 'false' ?>"
                                <?= $canManagePvStage && (!$isPvReview || $stageValue === \dbObject\Document::PV_STAGE_VALIDATED) ? '' : ' disabled' ?>
                            ><span class="omo-visibility-choice__text"><?= $escape($stageLabel) ?></span></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if (!$canManagePvStage): ?>
                    <div class="omo-pv-editor__stage-help"><?= $escape((string)$uiText['stageReadonly']) ?></div>
                <?php endif; ?>
            </div>
            <?php if ($eventTitle !== '' || $eventSchedule !== '' || $eventLocation !== ''): ?>
                <div class="omo-pv-editor__event-info">
                    <?php if ($eventTitle !== ''): ?>
                        <div class="omo-pv-editor__event-info-item omo-pv-editor__event-info-item--title">
                            <span class="omo-pv-editor__event-info-icon" aria-hidden="true"><img src="/omo/assets/images/documents/event-name.png" alt="" class="black-icon"></span>
                            <span class="omo-pv-editor__event-info-value"><?= $escape($eventTitle) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($eventSchedule !== ''): ?>
                        <div class="omo-pv-editor__event-info-item omo-pv-editor__event-info-item--schedule">
                            <span class="omo-pv-editor__event-info-icon" aria-hidden="true"><img src="/omo/assets/images/documents/event-schedule.png" alt="" class="black-icon"></span>
                            <span class="omo-pv-editor__event-info-value" data-omo-pv-event-schedule><?= $escape($eventSchedule) ?></span>
                            <?php if (!$isPublicParticipation && $hasAssociatedEvent): ?>
                                <div class="generic-menu omo-pv-editor__event-extension-menu" data-omo-pv-event-extension-menu<?= $canExtendAssociatedEvent && $canManagePvDocument ? '' : ' hidden' ?>>
                                    <button type="button" class="generic-menu-toggle omo-pv-editor__event-extension-toggle" data-omo-pv-event-extension-toggle aria-haspopup="menu" aria-expanded="false" aria-controls="omoPvEventExtensionMenu" aria-label="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.event.extend.button_title')) ?>" title="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.event.extend.button_title')) ?>">+</button>
                                    <div id="omoPvEventExtensionMenu" class="generic-menu-panel generic-menu-panel--wide omo-pv-editor__event-extension-panel" data-omo-pv-event-extension-panel role="menu" aria-label="<?= $escape(omoDocumentsPvEditorT('documents.pv_editor.event.extend.menu_aria')) ?>" hidden>
                                        <?php foreach ([5, 10, 15, 30] as $extensionMinutes): ?>
                                            <button type="button" class="generic-menu-item" data-omo-pv-event-extension-minutes="<?= $extensionMinutes ?>" role="menuitem"><?= $escape(omoDocumentsPvEditorT('documents.pv_editor.event.extend.option', ['minutes' => $extensionMinutes])) ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($eventLocation !== ''): ?>
                        <div class="omo-pv-editor__event-info-item omo-pv-editor__event-info-item--location">
                            <span class="omo-pv-editor__event-info-icon" aria-hidden="true"><img src="/omo/assets/images/documents/event-location.png" alt="" class="black-icon"></span>
                            <span class="omo-pv-editor__event-info-value"><?= $escape($eventLocation) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="omo-pv-editor__secretary" data-omo-pv-secretary>
                <div class="omo-pv-editor__secretary-people">
                    <div class="omo-pv-editor__person-block">
                        <span class="omo-pv-editor__person-icon" aria-hidden="true"><img src="/omo/assets/images/documents/pv-initial-author.png" class="black-icon" alt=""></span>
                        <span class="omo-pv-editor__person-copy">
                            <span class="omo-pv-editor__field-label"><?= $escape((string)$uiText['initialAuthor']) ?></span>
                            <span class="omo-pv-editor__secretary-name" data-omo-pv-initial-author><?= $escape($pvCreatorLabel !== '' ? $pvCreatorLabel : (string)$uiText['pvEditorEmpty']) ?></span>
                        </span>
                    </div>
                    <div class="omo-pv-editor__person-block">
                        <span class="omo-pv-editor__person-icon" aria-hidden="true"><img src="/omo/assets/images/documents/pv-editor.png" class="black-icon" alt=""></span>
                        <span class="omo-pv-editor__person-copy">
                            <span class="omo-pv-editor__field-label"><?= $escape((string)$uiText['pvEditor']) ?></span>
                            <span class="omo-pv-editor__secretary-name" data-omo-pv-secretary-name><?= $escape($pvEditorLabel !== '' ? $pvEditorLabel : (string)$uiText['pvEditorEmpty']) ?></span>
                            <span class="omo-pv-editor__secretary-state<?= $isPvEditor && $pvEditorHandoverOpen ? ' is-waiting' : '' ?>" data-omo-pv-secretary-state<?= $isPvEditor ? '' : ' hidden' ?>><?= $escape($isPvEditor && $pvEditorHandoverOpen ? (string)$uiText['pvEditorHandoverWaiting'] : (string)$uiText['pvEditorActive']) ?></span>
                        </span>
                    </div>
                </div>
                <div class="omo-pv-editor__secretary-actions">
                    <button type="button" class="generic-action-button generic-action-button--main omo-pv-editor__secretary-claim<?= $isPvEditor && $pvEditorHandoverOpen ? ' is-waiting' : '' ?>" data-omo-pv-claim-secretary data-omo-pv-secretary-action="<?= $escape($isPvEditor ? 'pass_pv_editor' : ($canClaimPvEditor ? 'claim_pv_editor' : 'replace_pv_editor')) ?>"<?= (!$isPvReview && ($canPassPvEditor || $canClaimPvEditor || $canReplacePvEditor)) ? '' : ' hidden' ?><?= $isPvEditor && $pvEditorHandoverOpen ? ' disabled' : '' ?>><?php if ($isPvEditor && $pvEditorHandoverOpen): ?><svg class="omo-pv-editor__secretary-claim-spinner" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="32 18"></circle></svg><span><?= $escape((string)$uiText['pvEditorHandoverWaiting']) ?></span><?php else: ?><?= $escape($isPvEditor ? (string)$uiText['passPvEditor'] : ($canClaimPvEditor && $pvEditorUserId > 0 ? (string)$uiText['reclaimPvEditor'] : ($canReplacePvEditor ? (string)$uiText['replacePvEditor'] : (string)$uiText['claimPvEditor']))) ?><?php endif; ?></button>
                    <?php if ($pvInvitationPopupUrl !== ''): ?>
                        <div class="generic-menu generic-menu--split" data-omo-pv-invitations-menu<?= $canManagePvInvitations ? '' : ' hidden' ?>>
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-invitations-url="<?= $escape($pvInvitationPopupUrl) ?>" data-omo-pv-invitations-title="<?= $escape((string)$uiText['inviteTitle']) ?>"<?= $canManagePvInvitations ? '' : ' disabled' ?>><?= $escape((string)$uiText['invite']) ?></button>
                            <button type="button" class="generic-menu-toggle" data-omo-pv-invitations-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-controls="omoPvInvitationsMenu" aria-label="<?= $escape((string)$uiText['invitationOptions']) ?>" title="<?= $escape((string)$uiText['invitationOptions']) ?>"<?= $canManagePvInvitations ? '' : ' disabled' ?>>&#9662;</button>
                            <div id="omoPvInvitationsMenu" class="generic-menu-panel generic-menu-panel--wide" data-omo-pv-invitations-menu-panel role="menu" hidden>
                                <button type="button" class="generic-menu-item" data-omo-pv-invitations-send-url="<?= $escape($pvInvitationSendPopupUrl) ?>" data-omo-pv-invitations-send-title="<?= $escape((string)$uiText['sendInvitations']) ?>" role="menuitem"><?= $escape((string)$uiText['sendInvitations']) ?></button>
                            </div>
                        </div>
                    <?php endif; ?>
                    <details class="omo-pv-editor__more-actions" data-omo-pv-more-actions>
                        <summary aria-label="<?= $escape((string)$uiText['moreActions']) ?>" title="<?= $escape((string)$uiText['moreActions']) ?>">...</summary>
                        <div class="omo-pv-editor__more-actions-menu">
                            <a
                                class="generic-action-button"
                                href="<?= $escape('/omo/api/documents/pv/export_pdf.php?id=' . rawurlencode((string)(int)$document->getId()) . '&oid=' . rawurlencode((string)$organizationId)) ?>"
                                download
                            ><?= $escape((string)$uiText['exportPdf']) ?></a>
                            <?php if ($canEditPvDocumentHeader): ?>
                                <button
                                    type="button"
                                    class="generic-action-button"
                                    data-omo-pv-template-toggle
                                    data-omo-pv-template-mark-label="<?= $escape((string)$uiText['markTemplate']) ?>"
                                    data-omo-pv-template-unmark-label="<?= $escape((string)$uiText['unmarkTemplate']) ?>"
                                    data-omo-pv-template-state="<?= $isPvTemplate ? '1' : '0' ?>"
                                ><?= $escape($isPvTemplate ? (string)$uiText['unmarkTemplate'] : (string)$uiText['markTemplate']) ?></button>
                            <?php endif; ?>
                        </div>
                    </details>
                </div>
            </div>
            </div>
            <?php if ($showAttendance): ?>
            <div class="omo-pv-editor__attendance" data-omo-pv-attendance-root<?= is_array($attendancePayload) ? '' : ' hidden' ?>>
                <div class="omo-pv-editor__attendance-icon" aria-hidden="true"><img src="/omo/assets/images/documents/pv-attendance.png" class="black-icon" alt=""></div>
                <div class="omo-pv-editor__attendance-body generic-stack generic-stack--compact">
                <div class="omo-pv-editor__attendance-head">
                    <span class="omo-pv-editor__field-label"><?= $escape((string)$uiText['attendance']) ?></span>
                    <span class="omo-pv-editor__attendance-count" data-omo-pv-attendance-count>
                        <?= $escape(str_replace(
                            ['{present}', '{total}'],
                            [
                                (string)(is_array($attendancePayload) ? (int)($attendancePayload['presentCount'] ?? 0) : 0),
                                (string)(is_array($attendancePayload) ? (int)($attendancePayload['totalCount'] ?? 0) : 0),
                            ],
                            (string)$uiText['attendanceCount']
                        )) ?>
                    </span>
                </div>
                <div class="omo-pv-editor__attendance-list" data-omo-pv-attendance-list>
                    <?php if (is_array($attendancePayload) && count((array)($attendancePayload['entries'] ?? [])) > 0): ?>
                        <?php foreach ((array)$attendancePayload['entries'] as $attendanceEntry): ?>
                            <?php $attendanceSecondaryLabel = trim((string)($attendanceEntry['secondaryLabel'] ?? '')); ?>
                            <label class="omo-pv-editor__attendance-item"<?= $attendanceSecondaryLabel !== '' ? ' title="' . $escape($attendanceSecondaryLabel) . '"' : '' ?>>
                                <input
                                    type="checkbox"
                                    data-omo-pv-attendance-toggle="<?= $escape((string)($attendanceEntry['identityKey'] ?? '')) ?>"
                                    <?= !$canEditPvDocumentHeader ? 'disabled' : '' ?>
                                    <?= !empty($attendanceEntry['isPresent']) ? 'checked' : '' ?>
                                >
                                <span class="omo-pv-editor__attendance-copy">
                                    <span class="omo-pv-editor__attendance-name"><?= $escape((string)($attendanceEntry['displayLabel'] ?? '')) ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p class="omo-pv-editor__attendance-empty" data-omo-pv-attendance-empty<?= is_array($attendancePayload) && count((array)($attendancePayload['entries'] ?? [])) > 0 ? ' hidden' : '' ?>>
                    <?= $escape((string)$uiText['attendanceEmpty']) ?>
                </p>
                </div>
            </div>
            <?php endif; ?>
        </section>
        <div class="omo-pv-editor__points generic-stack" data-omo-pv-editor-points>
            <?= count($pointCards) > 0 ? implode('', $pointCards) : '' ?>
        </div>
    </section>
    <?php if ($showPvApplicationTabs): ?>
        <section class="omo-pv-editor__application-workspace" data-omo-pv-application-workspace hidden></section>
    <?php endif; ?>
</div>

<script src="/common/choice/word-diff.js?v=20260922-deferred-proposals"></script>
<script src="/common/choice/change-details.js?v=20260924-readable-diffs"></script>
<?php if ($isPvReviewDiscussion): ?>
<script src="/common/chat/thread.js?v=20260821-pv-review-access-2"></script>
<?php endif; ?>

<?= commonPageScriptTags('/omo/api/documents/pv/editor.js', [
    'initialApplicationCatalog' => $pvApplicationCatalog,
    'initialApplicationTabs' => $pvApplicationTabsPayload,
    'applicationTabsUi' => [
        'pickerTitle' => (string)$uiText['applicationPickerTitle'],
        'pickerDescription' => (string)$uiText['applicationPickerDescription'],
        'pickerEmpty' => (string)$uiText['applicationPickerEmpty'],
        'pickerSubmit' => (string)$uiText['applicationPickerSubmit'],
        'pickerCancel' => (string)$uiText['applicationPickerCancel'],
        'error' => (string)$uiText['applicationError'],
    ],
    'initialPointPayloads' => $pointPayloads,
    'initialDocumentPayload' => [
        'pvStage' => $pvStage,
        'pvStageLabel' => (string)($pvStageOptions[$pvStage] ?? ''),
        'title' => $documentTitle,
        'description' => $documentDescription,
        'visibilityType' => $documentVisibilityType,
        'syncVersion' => $documentSyncVersion,
        'canManagePvStage' => $canManagePvStage,
        'pvEditorUserId' => $pvEditorUserId,
        'pvEditorLabel' => $pvEditorLabel,
        'pvCreatorUserId' => $pvCreatorUserId,
        'pvCreatorLabel' => $pvCreatorLabel,
        'isPvEditor' => $isPvEditor,
        'canManagePvDocument' => $canManagePvDocument,
        'canClaimPvEditor' => $canClaimPvEditor,
        'canReplacePvEditor' => $canReplacePvEditor,
        'pvEditorHandoverOpen' => $pvEditorHandoverOpen,
        'isPvValidated' => $isPvValidated,
        'isPvTemplate' => $isPvTemplate,
        'canManagePvTemplate' => $canEditPvDocumentHeader,
    ],
    'initialAttendancePayload' => $attendancePayload,
    'initialPollingRevision' => $pollingRevision,
    'attendanceEnabled' => $showAttendance,
    'eventStartAtIso' => $eventStartAtIso,
    'eventEndAtIso' => $eventEndAtIso,
    'saveLabel' => omoDocumentsPvEditorT('documents.pv_editor.action.save'),
    'savingLabel' => omoDocumentsPvEditorT('documents.pv_editor.action.saving'),
    'savedLabel' => omoDocumentsPvEditorT('documents.pv_editor.state.saved'),
    'takeOverLockLabel' => (string)$uiText['takeOverLock'],
    'takeOverWaitingLabel' => (string)$uiText['takeOverWaiting'],
    'takeoverDraftCopiedMessage' => (string)$uiText['takeoverDraftCopied'],
    'takeoverDraftCopyFailedMessage' => (string)$uiText['takeoverDraftCopyFailed'],
    'autoSummaryLoadingLabel' => (string)$uiText['autoSummaryLoading'],
    'autoSummaryReadyLabel' => (string)$uiText['autoSummaryReady'],
    'autoSummaryAvailable' => ($canUseAiTools),
    'dirtyLabel' => omoDocumentsPvEditorT('documents.pv_editor.state.dirty'),
    'unsavedCloseMessage' => omoDocumentsPvEditorT('documents.pv_editor.warning.unsaved_close'),
    'deletePointMessage' => omoDocumentsPvEditorT('documents.pv_editor.warning.delete_point'),
    'deleteItemMessage' => (string)$uiText['deleteItemMessage'],
    'reviewIrreversibleMessage' => omoDocumentsPvEditorT('documents.pv_editor.warning.review_irreversible'),
    'validateIrreversibleMessage' => omoDocumentsPvEditorT('documents.pv_editor.warning.validate_irreversible'),
    'unsavedHandoverMessage' => (string)$uiText['unsavedHandover'],
    'notStartedValue' => (string)$uiText['notStartedValue'],
    'marginLegendLabel' => (string)$uiText['marginLegend'],
    'overrunLegendLabel' => (string)$uiText['overrunLegend'],
    'groupPointsLabel' => (string)$uiText['groupPoints'],
    'groupMinutesLabel' => (string)$uiText['groupMinutes'],
    'canEmbedDocuments' => ($hasDocumentsApplication),
    'resourcePickerOrganizationId' => (int)$organizationId,
    'resourcePickerInitialHolonId' => (int)$resourcePickerInitialHolonId,
    'resourcePickerScopeUi' => [
        'local' => omoDocumentsPvEditorT('documents.pv_editor.embed.scope_local'),
        'children' => omoDocumentsPvEditorT('documents.pv_editor.embed.scope_children'),
        'descendants' => omoDocumentsPvEditorT('documents.pv_editor.embed.scope_descendants'),
    ],
    'embeddableDocuments' => $embeddableDocumentsPayload,
    'documentEmbedUi' => [
        'buttonTitle' => omoDocumentsPvEditorT('documents.pv_editor.embed.button_title'),
        'modalTitle' => omoDocumentsPvEditorT('documents.pv_editor.embed.modal_title'),
        'search' => omoDocumentsPvEditorT('documents.pv_editor.embed.search'),
        'searchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.search_placeholder'),
        'quickSearchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.quick_search_placeholder'),
        'visibleDocuments' => omoDocumentsPvEditorT('documents.pv_editor.embed.visible_documents'),
        'none' => omoDocumentsPvEditorT('documents.pv_editor.embed.none'),
        'insert' => omoDocumentsPvEditorT('documents.pv_editor.embed.insert'),
        'cancel' => omoDocumentsPvEditorT('documents.pv_editor.action.cancel'),
        'remove' => omoDocumentsPvEditorT('documents.pv_editor.action.remove_embed'),
        'linkedLabel' => omoDocumentsPvEditorT('documents.pv_editor.embed.linked_label'),
        'openExternal' => omoDocumentsPvEditorT('documents.pv_editor.embed.open_external'),
        'fallbackTitle' => omoDocumentsPvEditorT('documents.pv_editor.embed.document_fallback', ['id' => '{id}']),
        'toolbarLabel' => omoDocumentsPvEditorT('documents.pv_editor.toolbar.document'),
    ],
    'canEmbedDecisions' => ($hasDecisionApplication),
    'embeddableDecisions' => $embeddableDecisionsPayload,
    'decisionEmbedUi' => [
        'buttonTitle' => omoDocumentsPvEditorT('documents.pv_editor.decision.button_title'),
        'modalTitle' => omoDocumentsPvEditorT('documents.pv_editor.decision.modal_title'),
        'search' => omoDocumentsPvEditorT('documents.pv_editor.embed.search'),
        'searchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.search_placeholder'),
        'quickSearchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.quick_search_placeholder'),
        'visibleDecisions' => omoDocumentsPvEditorT('documents.pv_editor.decision.visible'),
        'none' => omoDocumentsPvEditorT('documents.pv_editor.embed.none'),
        'insert' => omoDocumentsPvEditorT('documents.pv_editor.decision.insert'),
        'cancel' => omoDocumentsPvEditorT('documents.pv_editor.action.cancel'),
        'remove' => omoDocumentsPvEditorT('documents.pv_editor.action.remove_embed'),
        'linkedLabel' => omoDocumentsPvEditorT('documents.pv_editor.decision.linked_label'),
        'fallbackTitle' => omoDocumentsPvEditorT('documents.pv_editor.decision.fallback', ['id' => '{id}']),
        'toolbarLabel' => omoDocumentsPvEditorT('documents.pv_editor.toolbar.decision'),
    ],
    'embeddableProjects' => $embeddableProjectsPayload,
    'projectEmbedUi' => [
        'buttonTitle' => omoDocumentsPvEditorT('documents.pv_editor.project.button_title'),
        'modalTitle' => omoDocumentsPvEditorT('documents.pv_editor.project.modal_title'),
        'search' => omoDocumentsPvEditorT('documents.pv_editor.embed.search'),
        'quickSearchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.quick_search_placeholder'),
        'visibleProjects' => omoDocumentsPvEditorT('documents.pv_editor.project.visible'),
        'assignmentAria' => omoDocumentsPvEditorT('documents.pv_editor.project.assignment_aria'),
        'assignmentMine' => omoDocumentsPvEditorT('documents.pv_editor.project.assignment_mine'),
        'assignmentFollowed' => omoDocumentsPvEditorT('documents.pv_editor.project.assignment_followed'),
        'assignmentEveryone' => omoDocumentsPvEditorT('documents.pv_editor.project.assignment_everyone'),
        'none' => omoDocumentsPvEditorT('documents.pv_editor.embed.none'),
        'insert' => omoDocumentsPvEditorT('documents.pv_editor.project.insert'),
        'cancel' => omoDocumentsPvEditorT('documents.pv_editor.action.cancel'),
        'remove' => omoDocumentsPvEditorT('documents.pv_editor.action.remove_embed'),
        'linkedLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.linked_label'),
        'openExternal' => omoDocumentsPvEditorT('documents.pv_editor.embed.open_external'),
        'plannedDate' => omoDocumentsPvEditorT('documents.pv_editor.project.planned_date', ['date' => '{date}']),
        'endDate' => omoDocumentsPvEditorT('documents.pv_editor.project.end_date', ['date' => '{date}']),
        'tabExisting' => omoDocumentsPvEditorT('documents.pv_editor.project.tab_existing'),
        'tabNew' => omoDocumentsPvEditorT('documents.pv_editor.project.tab_new'),
        'tabsAria' => omoDocumentsPvEditorT('documents.pv_editor.project.tabs_aria'),
        'titleLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.title'),
        'descriptionLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.description'),
        'statusLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.status'),
        'priorityLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.priority'),
        'sizeLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.size'),
        'startDateLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.start_date'),
        'endDateLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.end_date_label'),
        'holonLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.holon'),
        'responsibleLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.responsible'),
        'responsibleEmpty' => omoDocumentsPvEditorT('documents.pv_editor.project.responsible_empty'),
        'membersLoading' => omoDocumentsPvEditorT('documents.pv_editor.project.members_loading'),
        'membersEmpty' => omoDocumentsPvEditorT('documents.pv_editor.project.members_empty'),
        'createInsert' => omoDocumentsPvEditorT('documents.pv_editor.project.create_insert'),
        'createError' => omoDocumentsPvEditorT('documents.pv_editor.project.create_error'),
        'childrenLabel' => omoDocumentsPvEditorT('documents.pv_editor.project.children'),
        'childrenLoading' => omoDocumentsPvEditorT('documents.pv_editor.project.children_loading'),
        'childrenEmpty' => omoDocumentsPvEditorT('documents.pv_editor.project.children_empty'),
        'childrenError' => omoDocumentsPvEditorT('documents.pv_editor.project.children_error'),
        'fallbackTitle' => omoDocumentsPvEditorT('documents.pv_editor.project.fallback', ['id' => '{id}']),
        'toolbarLabel' => omoDocumentsPvEditorT('documents.pv_editor.toolbar.project'),
        'reviewLoadError' => omoDocumentsPvEditorT('documents.pv_editor.project.review_load_error'),
        'statusOptions' => array_map(static fn (string $status): array => ['value' => $status, 'label' => \dbObject\Project::getOrganizationStatusLabel($organizationId, $status)], \dbObject\Project::statuses()),
        'sizeOptions' => \dbObject\Project::sizes(),
    ],
    'projectEmbedCreateContext' => [
        'organizationId' => $organizationId,
        'holonId' => $projectEmbedCreateHolonId,
        'holonLabel' => $projectEmbedCreateHolonLabel,
        'responsibleId' => $currentUserId,
        'responsibleLabel' => $projectEmbedCreateResponsibleLabel,
        'pvMeetingDocumentId' => $isPvEditor && $document->getPvStage() === \dbObject\Document::PV_STAGE_MEETING ? (int)$document->getId() : 0,
        'pvMeetingEditorToken' => $isPvEditor && $document->getPvStage() === \dbObject\Document::PV_STAGE_MEETING ? $editorToken : '',
    ],
    'pvPriorityLabels' => $uiText['priorityLabels'] ?? [],
    'pvPriorityFieldLabel' => $uiText['priority'] ?? 'Priorité',
    'canEmbedChecklists' => (count($embeddableChecklistsPayload) > 0),
    'canCompleteChecklistProjects' => ($canEditPvDocumentHeader),
    'embeddableChecklists' => $embeddableChecklistsPayload,
    'checklistEmbedUi' => [
        'buttonTitle' => omoDocumentsPvEditorT('documents.pv_editor.checklist.button_title'),
        'modalTitle' => omoDocumentsPvEditorT('documents.pv_editor.checklist.modal_title'),
        'quickSearchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.quick_search_placeholder'),
        'visible' => omoDocumentsPvEditorT('documents.pv_editor.checklist.visible'),
        'none' => omoDocumentsPvEditorT('documents.pv_editor.embed.none'),
        'insert' => omoDocumentsPvEditorT('documents.pv_editor.checklist.insert'),
        'cancel' => omoDocumentsPvEditorT('documents.pv_editor.action.cancel'),
        'remove' => omoDocumentsPvEditorT('documents.pv_editor.action.remove_embed'),
        'completeArchive' => omoDocumentsPvEditorT('documents.pv_editor.checklist.complete_archive'),
        'completeArchiving' => omoDocumentsPvEditorT('documents.pv_editor.checklist.complete_archiving'),
        'completeArchiveError' => omoDocumentsPvEditorT('documents.pv_editor.checklist.complete_archive_error'),
        'emptyRuns' => omoDocumentsPvEditorT('documents.pv_editor.checklist.empty_runs'),
        'reviewContainer' => omoDocumentsPvEditorT('documents.pv_editor.checklist.review_container'),
        'reviewRuns' => omoDocumentsPvEditorT('documents.pv_editor.checklist.review_runs'),
        'runInvalid' => omoDocumentsPvEditorT('documents.pv_editor.checklist.run_invalid'),
        'runLoadError' => omoDocumentsPvEditorT('documents.pv_editor.checklist.run_load_error'),
        'itemsLoading' => omoDocumentsPvEditorT('documents.pv_editor.checklist.items_loading'),
        'itemsEmpty' => omoDocumentsPvEditorT('documents.pv_editor.checklist.items_empty'),
        'itemsLoadError' => omoDocumentsPvEditorT('documents.pv_editor.checklist.items_load_error'),
        'activitiesEmpty' => omoDocumentsPvEditorT('documents.pv_editor.checklist.activities_empty'),
        'itemFallback' => omoDocumentsPvEditorT('documents.pv_editor.checklist.item_fallback'),
        'statusPending' => omoDocumentsPvEditorT('documents.pv_editor.checklist.status_pending'),
        'runFallback' => omoDocumentsPvEditorT('documents.pv_editor.checklist.run_fallback'),
        'toolbarLabel' => omoDocumentsPvEditorT('documents.pv_editor.toolbar.process'),
    ],
    'canEmbedEvents' => ($hasCalendarApplication),
    'embeddableEvents' => $embeddableEventsPayload,
    'eventEmbedUi' => [
        'buttonTitle' => omoDocumentsPvEditorT('documents.pv_editor.event.button_title'),
        'modalTitle' => omoDocumentsPvEditorT('documents.pv_editor.event.modal_title'),
        'search' => omoDocumentsPvEditorT('documents.pv_editor.embed.search'),
        'searchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.search_placeholder'),
        'quickSearchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.quick_search_placeholder'),
        'visibleEvents' => omoDocumentsPvEditorT('documents.pv_editor.event.visible'),
        'none' => omoDocumentsPvEditorT('documents.pv_editor.embed.none'),
        'insert' => omoDocumentsPvEditorT('documents.pv_editor.event.insert'),
        'cancel' => omoDocumentsPvEditorT('documents.pv_editor.action.cancel'),
        'remove' => omoDocumentsPvEditorT('documents.pv_editor.action.remove_embed'),
        'tabExisting' => omoDocumentsPvEditorT('documents.pv_editor.event.tab_existing'),
        'tabNew' => omoDocumentsPvEditorT('documents.pv_editor.event.tab_new'),
        'tabsAria' => omoDocumentsPvEditorT('documents.pv_editor.event.tabs_aria'),
        'titleLabel' => omoDocumentsPvEditorT('documents.pv_editor.event.title'),
        'descriptionLabel' => omoDocumentsPvEditorT('documents.pv_editor.event.description'),
        'startAtLabel' => omoDocumentsPvEditorT('documents.pv_editor.event.start_at'),
        'endAtLabel' => omoDocumentsPvEditorT('documents.pv_editor.event.end_at'),
        'createInsert' => omoDocumentsPvEditorT('documents.pv_editor.event.create_insert'),
        'createError' => omoDocumentsPvEditorT('documents.pv_editor.event.create_error'),
        'endAfterStart' => omoDocumentsPvEditorT('documents.pv_editor.event.end_after_start'),
        'fallbackTitle' => omoDocumentsPvEditorT('documents.pv_editor.event.fallback', ['id' => '{id}']),
        'toolbarLabel' => omoDocumentsPvEditorT('documents.pv_editor.toolbar.event'),
    ],
    'eventEmbedCreateContext' => [
        'organizationId' => $organizationId,
        'holonId' => $projectEmbedCreateHolonId,
    ],
    'canEmbedIndicators' => ($hasStatsApplication),
    'embeddableIndicators' => $embeddableIndicatorsPayload,
    'indicatorEmbedUi' => [
        'buttonTitle' => omoDocumentsPvEditorT('documents.pv_editor.indicator.button_title'),
        'modalTitle' => omoDocumentsPvEditorT('documents.pv_editor.indicator.modal_title'),
        'search' => omoDocumentsPvEditorT('documents.pv_editor.embed.search'),
        'searchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.search_placeholder'),
        'quickSearchPlaceholder' => omoDocumentsPvEditorT('documents.pv_editor.embed.quick_search_placeholder'),
        'visibleIndicators' => omoDocumentsPvEditorT('documents.pv_editor.indicator.visible'),
        'none' => omoDocumentsPvEditorT('documents.pv_editor.embed.none'),
        'insert' => omoDocumentsPvEditorT('documents.pv_editor.indicator.insert'),
        'cancel' => omoDocumentsPvEditorT('documents.pv_editor.action.cancel'),
        'remove' => omoDocumentsPvEditorT('documents.pv_editor.action.remove_embed'),
        'fallbackTitle' => omoDocumentsPvEditorT('documents.pv_editor.indicator.fallback', ['id' => '{id}']),
        'toolbarLabel' => omoDocumentsPvEditorT('documents.pv_editor.toolbar.indicator'),
    ],
    'editorClientUi' => [
        'genericError' => omoDocumentsPvEditorT('documents.pv_editor.error.generic'),
        'documentSaveError' => omoDocumentsPvEditorT('documents.pv_editor.error.document_save'),
        'autoSummaryError' => omoDocumentsPvEditorT('documents.pv_editor.error.auto_summary'),
        'autoSummaryEmpty' => omoDocumentsPvEditorT('documents.pv_editor.error.auto_summary_empty'),
        'eventExtensionSaving' => omoDocumentsPvEditorT('documents.pv_editor.event.extend.saving'),
        'highlightLabel' => omoDocumentsPvEditorT('documents.pv_editor.toolbar.highlight'),
        'highlightTitle' => omoDocumentsPvEditorT('documents.pv_editor.toolbar.highlight_title'),
    ],
    'placeholder' => omoDocumentsPvEditorT('documents.pv_editor.indicator.value_placeholder'),
    'addLabel' => omoDocumentsPvEditorT('documents.pv_editor.indicator.add_value'),
    'documentsPvEditorIndicatorValueSaving' => omoDocumentsPvEditorT('documents.pv_editor.indicator.value_saving'),
    'documentsPvEditorIndicatorValueError' => omoDocumentsPvEditorT('documents.pv_editor.indicator.value_error'),
    'documentsPvEditorNavEmpty' => omoDocumentsPvEditorT('documents.pv_editor.nav.empty'),
    'label' => (string)$uiText['pvEditorEmpty'],
    'pvEditorHandoverWaiting' => (string)$uiText['pvEditorHandoverWaiting'],
    'pvEditorActive' => (string)$uiText['pvEditorActive'],
    'passPvEditor' => (string)$uiText['passPvEditor'],
    'reclaimPvEditor' => (string)$uiText['reclaimPvEditor'],
    'claimPvEditor' => (string)$uiText['claimPvEditor'],
    'replacePvEditor' => (string)$uiText['replacePvEditor'],
    'attendanceCount' => (string)$uiText['attendanceCount'],
    'placeholder2' => omoDocumentsPvEditorT('documents.pv_editor.field.content'),
    'resourceGapHelperLabel' => (string)$uiText['embedAddLine'],
    'concernedHolonEmpty' => (string)$uiText['concernedHolonEmpty'],
    'dropInsideLabel' => omoDocumentsPvEditorT('documents.pv_editor.group.drop_inside'),
]) ?>
