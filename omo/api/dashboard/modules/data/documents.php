<?php
use dbObject\ArrayDocument;

$documents = new ArrayDocument();
$recentDocuments = array();
$dashboardDocumentCounts = array('created' => 0, 'modified' => 0);
if (!empty($enabledAppHashes['documents'])) {
    $documentScopeHolonIds = $personalSpaceScope === 'contextual'
        ? []
        : $personalSpaceScopeHolonIds;
    $documents->loadRecentForOrganizationContext(
        $currentOrganizationId,
        $currentHolonId,
        500,
        $personalSpaceScope,
        $documentScopeHolonIds
    );
    $recentDocuments = $documents->buildPersonalSpaceItems($currentOrganizationId);
    $documentThreshold = new DateTimeImmutable('-7 days');
    $recentDocuments = array_values(array_filter(array_map(static function (array $documentItem) use (&$dashboardDocumentCounts, $documentThreshold): array {
        $createdAt = $documentItem['datecreation'] ?? null;
        $modifiedAt = $documentItem['datemodification'] ?? null;
        $filters = array();
        if ($createdAt instanceof DateTimeInterface && $createdAt >= $documentThreshold) {
            $dashboardDocumentCounts['created']++;
            $filters[] = 'created';
        }
        if (
            $modifiedAt instanceof DateTimeInterface
            && $modifiedAt >= $documentThreshold
            && (!($createdAt instanceof DateTimeInterface) || $modifiedAt->getTimestamp() > $createdAt->getTimestamp())
        ) {
            $dashboardDocumentCounts['modified']++;
            $filters[] = 'modified';
        }
        $documentItem['filters'] = $filters;
        return $documentItem;
    }, $recentDocuments), static function (array $documentItem): bool {
        return !empty($documentItem['filters']);
    }));
}
