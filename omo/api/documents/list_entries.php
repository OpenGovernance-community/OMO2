<?php

if (!function_exists('omoDocumentsGetListGroupSourceLang')) {
    function omoDocumentsGetListGroupSourceLang(): array
    {
        return array(
            'documents.group.today' => array('text' => "Aujourd'hui", 'context' => 'Relative date group title for documents updated today.'),
            'documents.group.yesterday' => array('text' => 'Hier', 'context' => 'Relative date group title for documents updated yesterday.'),
            'documents.group.this_week' => array('text' => 'Cette semaine', 'context' => 'Relative date group title for documents updated earlier this week.'),
            'documents.group.last_week' => array('text' => 'Semaine dernière', 'context' => 'Relative date group title for documents updated last week.'),
            'documents.group.this_month' => array('text' => 'Ce mois', 'context' => 'Relative date group title for documents updated earlier this month.'),
            'documents.group.last_month' => array('text' => 'Mois dernier', 'context' => 'Relative date group title for documents updated last month.'),
            'documents.group.this_year' => array('text' => 'Cette année', 'context' => 'Relative date group title for documents updated earlier this year.'),
            'documents.group.earlier' => array('text' => 'Plus ancien', 'context' => 'Relative date group title for older documents.'),
            'documents.group.too_far' => array('text' => 'Date inconnue', 'context' => 'Fallback relative date group title for documents with missing or invalid dates.'),
        );
    }
}

if (!function_exists('omoDocumentsBuildListEntries')) {
    function omoDocumentsBuildListEntries(iterable $documents, array $context): array
    {
        $organizationId = (int)($context['organizationId'] ?? 0);
        $currentUserId = (int)($context['currentUserId'] ?? 0);
        $organization = $context['organization'] ?? null;
        $documentScope = (string)($context['documentScope'] ?? 'contextual');
        $visibilityRuleMap = is_array($context['visibilityRuleMap'] ?? null) ? $context['visibilityRuleMap'] : array();
        $editVisibilityRuleMap = is_array($context['editVisibilityRuleMap'] ?? null) ? $context['editVisibilityRuleMap'] : array();
        $documentListMetadata = is_array($context['documentListMetadata'] ?? null) ? $context['documentListMetadata'] : array();
        $pvEventsById = is_array($context['pvEventsById'] ?? null) ? $context['pvEventsById'] : array();
        $documentViewerContext = is_array($context['documentViewerContext'] ?? null) ? $context['documentViewerContext'] : array();
        $isPvApplicationTab = !empty($context['isPvApplicationTab']);
        $groups = is_array($context['groups'] ?? null) ? $context['groups'] : array();
        $today = $context['today'] ?? new DateTimeImmutable('today');
        $formatDate = is_callable($context['formatDate'] ?? null)
            ? $context['formatDate']
            : static function ($value): string { return ''; };
        $resolveDocumentVisibilityIconUrl = is_callable($context['resolveDocumentVisibilityIconUrl'] ?? null)
            ? $context['resolveDocumentVisibilityIconUrl']
            : static function (): string { return ''; };
        $normalizeSortValue = is_callable($context['normalizeSortValue'] ?? null)
            ? $context['normalizeSortValue']
            : static function ($value): string { return trim((string)$value); };

        $fallbackGroup = count($groups) > 0
            ? $groups[count($groups) - 1]
            : array('key' => 'too_far', 'label' => '');
        $entries = array();

        foreach ($documents as $document) {
            if (!($document instanceof \dbObject\Document) || (int)$document->getId() <= 0) {
                continue;
            }

            $createdAt = $document->get('datecreation');
            $documentId = (int)$document->getId();
            $activityMetadata = $documentListMetadata['activityByDocumentId'][$documentId] ?? array();
            $updatedAt = $activityMetadata['date'] ?? $document->get('datemodification');
            $resolvedCreatedAt = $createdAt instanceof DateTimeInterface
                ? $createdAt
                : ($updatedAt instanceof DateTimeInterface ? $updatedAt : null);
            $resolvedUpdatedAt = $updatedAt instanceof DateTimeInterface
                ? $updatedAt
                : $resolvedCreatedAt;
            $documentOrganizationId = (int)$document->get('IDorganization');
            $documentHolonId = (int)$document->get('IDholon');
            $parentDocumentId = (int)$document->get('IDdocument_parent');
            $visibility = $document->getVisibilityDisplayData(
                $organizationId,
                $visibilityRuleMap[$documentId] ?? null
            );
            $editVisibility = $document->getEditVisibilityDisplayData(
                $organizationId,
                $editVisibilityRuleMap[$documentId] ?? null
            );
            $visibilityRule = $visibilityRuleMap[$documentId] ?? null;
            $editVisibilityRule = $editVisibilityRuleMap[$documentId] ?? null;
            $canOpenPvEditor = $document->canUserOpenPvEditor($currentUserId, $organizationId);
            $canManageDocument = $document->canManageInOrganizationContextWithVisibilityRule(
                $documentOrganizationId,
                $currentUserId,
                $visibilityRule,
                $documentViewerContext,
                true
            );
            $canMoveDocument = ($document->isPvDocument() && $document->canUserManagePvDocument($currentUserId))
                || $canManageDocument;
            $canMergeDocument = $document->supportsHtmlContent()
                && $canManageDocument
                && $document->canEditInOrganizationContext($documentOrganizationId, $currentUserId, false);
            $canManageLifecycle = $document->canManageLifecycle($documentOrganizationId, $currentUserId);
            $associatedEvent = $pvEventsById[(int)$document->get('IDevent')] ?? null;
            $hasUpcomingPvEvent = $document->isPvDocument()
                && $associatedEvent instanceof \dbObject\Event
                && $associatedEvent->isUpcoming();
            $pvPreparationUrl = $canOpenPvEditor
                ? $document->buildPvEditorUrl($organizationId)
                : '';
            $isPvDocument = $document->isPvDocument();
            $isPvValidated = $isPvDocument && $document->isPvValidated();
            $canOpenInPvApplicationTab = !$isPvDocument || $isPvValidated;
            $canOpenInCurrentView = !$isPvApplicationTab || $canOpenInPvApplicationTab;
            $isFolder = $document->isFolder();
            $canUploadToFolder = $isFolder
                && !$document->isNextcloudFolder()
                && $organization instanceof \dbObject\Organization
                && $organization->hasDocumentStorage()
                && \dbObject\Document::canCreateInOrganizationContext(
                    $documentOrganizationId,
                    $documentHolonId > 0 ? $documentHolonId : null,
                    $currentUserId,
                    $documentId,
                    true
                );
            $canMoveToFolder = $isFolder && \dbObject\Document::canCreateInOrganizationContext(
                $documentOrganizationId,
                $documentHolonId > 0 ? $documentHolonId : null,
                $currentUserId,
                $documentId,
                true
            );
            $isExternalLink = $document->isExternalLink();
            $isDocumentTemplate = $document->isDocumentTemplate();
            $canShareDocument = !$isFolder && $document->supportsHtmlContent();
            $documentTitle = (string)$document->get('title');
            $listTitle = $documentTitle;
            if ($document->isPvDocument() && !$document->isPvValidated()) {
                $listTitle .= ' (' . $document->getPvStageLabel() . ')';
            }
            $createdGroupIndex = sharedGetRelativeDateGroupIndexForDate($resolvedCreatedAt, $groups, $today);
            $createdGroup = $groups[$createdGroupIndex] ?? $fallbackGroup;
            $createdGroupKey = (string)($createdGroup['key'] ?? 'too_far');
            $updatedGroupIndex = sharedGetRelativeDateGroupIndexForDate($resolvedUpdatedAt, $groups, $today);
            $updatedGroup = $groups[$updatedGroupIndex] ?? $fallbackGroup;
            $updatedGroupKey = (string)($updatedGroup['key'] ?? 'too_far');

            $entries[] = array(
                'id' => $documentId,
                'holonId' => $documentHolonId,
                'href' => '/memo/' . $documentId,
                'title' => $documentTitle,
                'listTitle' => $listTitle,
                'documentType' => $document->getDocumentType(),
                'isTemplate' => $isDocumentTemplate,
                'canManageTemplate' => $document->isTemplateEligible() && ($document->isPvDocument()
                    ? $document->canUserManagePvDocument($currentUserId)
                    : $canManageDocument),
                'isPvValidated' => $isPvValidated,
                'canOpenInPvApplicationTab' => $canOpenInPvApplicationTab,
                'storedFileKind' => $document->isUploadedFile() ? $document->getStoredFileKind() : '',
                'isMissingUploadedFile' => $document->hasMissingUploadedFile(),
                'canExportPdf' => $document->isPvDocument(),
                'pdfExportUrl' => $document->isPvDocument()
                    ? '/omo/api/documents/pv/export_pdf.php?id=' . rawurlencode((string)$documentId)
                        . '&oid=' . rawurlencode((string)$organizationId)
                    : '',
                'isFolder' => $isFolder,
                'isNextcloudFolder' => $document->isNextcloudFolder(),
                'childrenLoaded' => !$isFolder,
                'canUpload' => $canUploadToFolder,
                'canMoveInto' => $canMoveToFolder,
                'isExternalLink' => $isExternalLink,
                'externalUrl' => $document->getExternalUrl(),
                'openInNewWindow' => $document->shouldOpenExternalLinkInNewWindow(),
                'canShare' => $canShareDocument,
                'pvPreparationUrl' => $pvPreparationUrl,
                'parentDocumentId' => $parentDocumentId > 0 ? $parentDocumentId : 0,
                'contextLabel' => $documentScope !== 'contextual'
                    ? trim((string)$document->getOrganizationContextLabel())
                    : '',
                'contextBreadcrumb' => $documentScope !== 'contextual'
                    ? array_values(array_map(
                        static function (array $item): array {
                            $breadcrumbOrganizationId = (int)($item['organizationId'] ?? 0);
                            $breadcrumbHolonId = (int)($item['holonId'] ?? 0);

                            return array(
                                'label' => trim((string)($item['label'] ?? '')),
                                'organizationId' => $breadcrumbOrganizationId,
                                'holonId' => $breadcrumbHolonId,
                            );
                        },
                        $document->getOrganizationContextBreadcrumbItems()
                    ))
                    : array(),
                'description' => trim((string)$document->get('description')),
                'keywords' => trim((string)$document->get('keywords')),
                'hasUpcomingPvEvent' => $hasUpcomingPvEvent,
                'canMove' => $canMoveDocument,
                'canMerge' => $canMergeDocument,
                'canArchive' => $canManageLifecycle && !$document->isArchived(),
                'canDelete' => $document->canDeleteInOrganizationContext($documentOrganizationId, $currentUserId)
                    && (int)$document->get('IDevent') <= 0
                    && !isset($documentListMetadata['documentsWithChildren'][$documentId]),
                'canEdit' => $document->isPvDocument()
                    ? ($canOpenInCurrentView && $canOpenPvEditor)
                    : (
                        $canManageDocument
                        || (!$document->isEtherpadDocument() && !$document->isEthercalcDocument() && !$document->isWhiteboardDocument() && $document->canEditInOrganizationContextWithVisibilityRules($documentOrganizationId, $currentUserId, $visibilityRule, $editVisibilityRule, $documentViewerContext))
                    ),
                'editUrl' => $document->isPvDocument()
                    ? ($canOpenInCurrentView ? $pvPreparationUrl : '')
                    : ('/omo/api/documents/create.php?id=' . $documentId
                        . ($documentOrganizationId > 0 ? '&oid=' . $documentOrganizationId : '')
                        . ($documentHolonId > 0 ? '&cid=' . $documentHolonId : '')),
                'visibilityBadge' => (string)($visibility['badgeText'] ?? ''),
                'visibilityType' => (string)($visibility['type'] ?? ''),
                'visibilityIconUrl' => $resolveDocumentVisibilityIconUrl((string)($visibility['type'] ?? '')),
                'editVisibilityBadge' => (string)($editVisibility['badgeText'] ?? ''),
                'editVisibilityType' => (string)($editVisibility['type'] ?? ''),
                'editVisibilityIconUrl' => $resolveDocumentVisibilityIconUrl((string)($editVisibility['type'] ?? '')),
                'dateLabel' => $formatDate($resolvedUpdatedAt, in_array($updatedGroupKey, array('earlier', 'too_far'), true)),
                'fullDateLabel' => $formatDate($resolvedCreatedAt, true),
                'timestamp' => $resolvedUpdatedAt instanceof DateTimeInterface ? (int)$resolvedUpdatedAt->getTimestamp() : 0,
                'groupKey' => $updatedGroupKey,
                'groupLabel' => (string)($updatedGroup['label'] ?? ''),
                'createdDateLabel' => $formatDate($resolvedCreatedAt, in_array($createdGroupKey, array('earlier', 'too_far'), true)),
                'createdFullDateLabel' => $formatDate($resolvedCreatedAt, true),
                'createdTimestamp' => $resolvedCreatedAt instanceof DateTimeInterface ? (int)$resolvedCreatedAt->getTimestamp() : 0,
                'createdGroupKey' => $createdGroupKey,
                'createdGroupLabel' => (string)($createdGroup['label'] ?? ''),
                'updatedDateLabel' => $formatDate($resolvedUpdatedAt, in_array($updatedGroupKey, array('earlier', 'too_far'), true)),
                'updatedFullDateLabel' => $formatDate($resolvedUpdatedAt, true),
                'updatedTimestamp' => $resolvedUpdatedAt instanceof DateTimeInterface ? (int)$resolvedUpdatedAt->getTimestamp() : 0,
                'updatedGroupKey' => $updatedGroupKey,
                'updatedGroupLabel' => (string)($updatedGroup['label'] ?? ''),
                'sortTitle' => $normalizeSortValue($document->get('title')),
                'contextUrl' => '/omo/api/documents/detail.php?id=' . $documentId
                    . '&oid=' . $organizationId
                    . ($documentHolonId > 0 ? '&cid=' . $documentHolonId : ''),
            );
        }

        return $entries;
    }
}
